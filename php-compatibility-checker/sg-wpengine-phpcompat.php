<?php
// Exit if this file is directly accessed
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once( __DIR__ . '/vendor/autoload.php' );

// Add the phpcompat WP-CLI command.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once( __DIR__ . '/src/wpcli.php' );
}

/**
 * This handles hooking into WordPress.
 */
class SG_WPEngine_PHPCompat {

	/* Define and register singleton */
	private static $instance = false;

	/* Hook for the settings page  */
	private $page;

	/**
	 * Returns an instance of this class.
	 *
	 * @return self An instance of this class.
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Initialize hooks and setup environment variables.
	 *
	 * @since 2.3.11
	 */
	public static function init() {
		// Load our JavaScript.
		add_action( 'admin_enqueue_scripts', array( self::instance(), 'admin_enqueue' ) );

		// The action to run the compatibility test.
		add_action( 'wp_ajax_sg_wpephpcompat_start_test', array( self::instance(), 'start_test' ) );
		add_action( 'wp_ajax_sg_wpephpcompat_check_status', array( self::instance(), 'check_status' ) );
		add_action( 'sg_wpephpcompat_start_test_cron', array( self::instance(), 'start_test' ) );
		add_action( 'wp_ajax_sg_wpephpcompat_clean_up', array( self::instance(), 'clean_up' ) );
        add_action( 'wp_ajax_sg_wpephpcompat_change_version', array( self::instance(), 'change_current_php_version' ) );

		// Create custom post type.
		add_action( 'init', array( self::instance(), 'create_job_queue' ) );              
	}

	/**
	 * Start the test!
	 *
	 * @since  2.3.11
	 * @action wp_ajax_sg_wpephpcompat_start_test
	 * @action sg_wpephpcompat_start_test_cron
	 * @return null
	 */
	function start_test() {
	    $isCron = ( defined( 'DOING_CRON' ) && DOING_CRON );
		if ( ( current_user_can( 'manage_options' )
                && isset($_POST['nonce'])
                && wp_verify_nonce( $_POST['nonce'], 'sg_wpephpcompat_start_test' )
             ) ||  $isCron ) {
			global $wpdb;

			$wpephpc = new \SG_WPEPHPCompat( dirname(__DIR__) );

			if ( isset( $_POST['startScan'] ) ) {
				$test_version = sanitize_text_field( $_POST['test_version'] );
				$only_active = sanitize_text_field( $_POST['only_active'] );

				$wpephpc->test_version = $test_version;
				$wpephpc->only_active = $only_active;
				$wpephpc->clean_after_scan();
			}

			$wpephpc->start_test();
			wp_die();
		}
	}

	/**
	 * Check the progress or result of the tests.
	 *
	 * @todo Use heartbeat API.
	 * @since  2.3.11
	 * @action wp_ajax_wpephpcompat_check_status
	 * @return null
	 */
	function check_status() {
            $isCron = ( defined( 'DOING_CRON' ) && DOING_CRON );
            if ( ( current_user_can( 'manage_options' )
                    && isset($_POST['nonce'])
                    && wp_verify_nonce( $_POST['nonce'], 'sg_wpephpcompat_check_status' )
                ) ||  $isCron ) {
                    $scan_status = get_option( 'sg_wpephpcompat.status' );
                    $count_jobs = wp_count_posts( 'sg_optimizer_jobs' );
                    $total_jobs = get_option( 'sg_wpephpcompat.numdirs' );
                    $test_version = get_option( 'sg_wpephpcompat.test_version' );
                    $only_active = get_option( 'sg_wpephpcompat.only_active' );

                    $active_job = false;
                    $jobs = get_posts( array(
                            'posts_per_page' => -1,
                            'post_type'      => 'sg_optimizer_jobs',
                            'orderby'        => 'title',
                            'order'          => 'ASC',
                    ) );

                    if ( 0 < count( $jobs ) ) {
                            $active_job = $jobs[0]->post_title;
                    }

                    $to_encode = array(
                            'status'     => $scan_status,
                            'count'      => $count_jobs->publish,
                            'total'      => $total_jobs,
                            'activeJob'  => $active_job,
                            'version'    => $test_version,
                            'onlyActive' => $only_active,
                    );

                    // If the scan is still running.
                    if ( $scan_status ) {
                            $to_encode['results'] = '0';
                            $to_encode['progress'] = ( ( $total_jobs - $count_jobs->publish ) / $total_jobs) * 100;
                    } else {
                            // Else return the results and clean up!
                            $scan_results = get_option( 'sg_wpephpcompat.scan_results' );
                            // Not using esc_html since the results are shown in a textarea.
                            $to_encode['results'] = $scan_results;

                            $wpephpc = new \SG_WPEPHPCompat( dirname(__DIR__) );
                            $wpephpc->clean_after_scan();
                    }
                    wp_send_json( $to_encode );
            }
	}

	/**
	 * Remove all database options from the database.
	 *
	 * @since 2.3.11
	 * @action wp_ajax_wpephpcompat_clean_up
	 */
	function clean_up() {
        $isCron = ( defined( 'DOING_CRON' ) && DOING_CRON );
        if ( ( current_user_can( 'manage_options' )
                && isset($_GET['nonce'])
                && wp_verify_nonce( $_GET['nonce'], 'sg_wpephpcompat_clean_up' )
            ) ||  $isCron )
        {
			$wpephpc = new \SG_WPEPHPCompat( dirname(__DIR__) );
			$wpephpc->clean_after_scan();
			delete_option( 'sg_wpephpcompat.scan_results' );
			wp_send_json( 'success' );
		}
	}

	/**
	 * Create custom post type to store the directories we need to process.
	 *
	 * @since 2.3.11
	 * @return  null
	 */
	function create_job_queue() {
		register_post_type( 'sg_optimizer_jobs',
			array(
				'labels' => array(
					'name' => __( 'Jobs' ),
					'singular_name' => __( 'Job' ),
				),
			'public' => false,
			'has_archive' => false,
			)
		);
	}

	/**
	 * Enqueue our JavaScript and CSS.
	 *
	 * @since 2.3.11
	 * @action admin_enqueue_scripts
	 * @return  null
	 */
	function admin_enqueue( $hook ) {
		 if (( $hook !== 'sg-optimizer_page_php-check') and ( $hook !== 'sg-optimiser_page_php-check') ) {
			return;
		}

		// Styles
		wp_enqueue_style( 'sg_wpephpcompat-style', plugins_url( '/src/css/style.css', __FILE__ ) );

		// Scripts
		wp_enqueue_script( 'sg_wpephpcompat-handlebars', plugins_url( '/src/js/handlebars.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'sg_wpephpcompat-download', plugins_url( '/src/js/download.min.js', __FILE__ ) );
		wp_enqueue_script( 'sg_wpephpcompat', plugins_url( '/src/js/run.js', __FILE__ ), array( 'jquery', 'sg_wpephpcompat-handlebars', 'sg_wpephpcompat-download' ) );

		// Progress Bar
		wp_enqueue_script( 'jquery-ui-progressbar' );
		wp_enqueue_style( 'jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

		/**
		 * i18n strings
		 *
		 * These translated strings can be access in jquery with window.sg_wpephpcompat object.
		 */
                
                $recommended = self::get_recommended_php_versions();
		$strings = array(
			'name'       => __( 'Name', 'sg-cachepress' ),
			'compatible' => __( 'compatible', 'sg-cachepress' ),
                        'not_compatible' => __( 'The following plugins/themes are not compatible with PHP ', 'sg-cachepress' ),
                        'see_details_below' => __( '', 'sg-cachepress' ),
                        'click_upgrade_version' => __( 'Click upgrade button to ugprade your PHP version.', 'sg-cachepress' ),
			//'are_not'    => __( 'plugins/themes are possibly not compatible', 'sg-cachepress' ),
			//'is_not'     => __( 'Your WordPress site is possibly not PHP', 'sg-cachepress' ),
			//'out_of'     => __( 'out of', 'sg-cachepress' ),
			'run'        => __( 'Check PHP ' . $recommended [0] . ' Compatibility', 'sg-cachepress' ),
                        'loading'      => __( 'Updating PHP Version', 'sg-cachepress' ),
                        'rerun'        => __( 'Check PHP ' . $recommended [0] . ' Compatibility Again', 'sg-cachepress' ),
			'your_wp'    => __( 'Your WordPress site is', 'sg-cachepress' ),
                        'check_your_php_version' => __( 'Checks the PHP version your WordPress site is running and whether you\'re on the fastest possible PHP version.', 'sg-cachepress' ),
                        'upgrade_to' => __( 'Upgrade to', 'sg-cachepress' ),
                        'you_running_running_on'    => __( 'Site is compatible and running on', 'sg-cachepress' ),
                        'recommended_or_higher'    => __( 'which is our recommended PHP version or higher.', 'sg-cachepress' ),
                        'if_you_fixed_retry'    => __( 'If you have fixed the reported errors, you may <a style="cursor: pointer;"  onclick="runAction();">try to check the PHP 7.1 compatibility</a> of your Wordpress site again. ', 'sg-cachepress' ),
                        'recommend_to_switch' => __( 'If you can\'t update to PHP 7.1 right away, we recommend that you <a style="cursor: pointer;" onclick="upgradeTo(\'5.6\');">switch to PHP 5.6</a> which is the safest and fastest version of the 5 branch.')
		);
                               

		wp_localize_script( 'sg_wpephpcompat', 'sg_wpephpcompat', $strings );
	}       
            
    /**
     * 
     * Change current php version in .htaccess
     * 
     * @since 2.3.11
     * 
     * @return string
     * 0 - failed 
     * 1 - success
     * 2 - no changes     
    */        
    public static function change_current_php_version() {

        if (!current_user_can( 'manage_options' )) {
            die(0);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce( $_POST['nonce'], 'sg_wpephpcompat_change_version' ) ) {
            die(0);
        }

        $availableVersions = self::get_available_php_versions();
        
        if ( !in_array($_POST['version'], $availableVersions) ) {
            die(0);
        } else {
            $newVersionOrig = $_POST['version'];
        }                
        
        $currentVersion = self::get_current_php_version();
	    $basedir = ABSPATH; // This is wrong, but less wrong than previous implementation which broke on symlinks, etc. R.
        $filename = $basedir. '/.htaccess';

        if (!is_writable($filename)) {
            die(0);
        }

        $htaccessContent = file_get_contents($filename);            
        $newVersion = str_replace('.', '', $newVersionOrig);

        $addHandlerOption = 'AddHandler application/x-httpd-php' . $newVersion . ' .php .php5 .php4 .php3';

        $htaccessNewContent = preg_replace(
            '/(AddHandler\s+application\/x-httpd-php)(\w+)(\s+\.php\s+\.php5\s+\.php4\s+\.php3)/s', 
            '${1}'. $newVersion .'${3}', 
            $htaccessContent,
            -1,
            $count
        );
        
        // add it manually
        if (!$count) {
            $htaccessNewContent .= PHP_EOL . $addHandlerOption;
        }
          
        // no changes
        if ($htaccessContent === $htaccessNewContent) {
            die('2');
        }
        
        $fp = fopen($filename, "w+");

        if (flock($fp, LOCK_EX)) { // do an exclusive lock
            fwrite($fp, $htaccessNewContent);
            flock($fp, LOCK_UN); // release the lock
        } else {
            die(0);
        }
        
        update_option('sg_wpephpcompat.current_php_version', $newVersionOrig);
        update_option('sg_wpephpcompat.prev_php_version', $currentVersion); 
        
        // Log
        $user = @get_current_user();
        $r = @posix_getpwnam($user);
        
        $report = time() . 
                ' from_' . $currentVersion . 
                ' to_' . $newVersionOrig .
                ' ' . $basedir .
                ' ' . get_site_url();

        @file_put_contents($r['dir'] . '/.wp_version_change', $report . PHP_EOL , FILE_APPEND);
 
        if (self::isUpToDate($newVersionOrig)) {
            $options_handler = new \SG_CachePress_Options();
            $options_handler->disable_option('show_notice_notification-1');
        }
        
        die('1');
    }
    
    /**
     * 
     * Returns an associative array of all available PHP versions we support.
     * 
     * @since 2.3.11
     */        
    public static function get_available_php_versions() {
        return apply_filters('phpcompat_phpversions', array(
            'PHP 7.1' => '7.1',
            'PHP 7.0' => '7.0',
            'PHP 5.6' => '5.6',
            'PHP 5.5' => '5.5',
            'PHP 5.4' => '5.4',
        ));
    }
    
    /**
     * get previous PHP version
     * @since 2.3.11
     */
    public static function get_prev_php_version() {
        return get_option('sg_wpephpcompat.prev_php_version');
    }
    
    /**
     * 
     * @param type $url
     * @return type
     */
    public static function curl_get_content($url) {       
        $options = array(
            CURLOPT_RETURNTRANSFER => true,   // return web page
            CURLOPT_HEADER         => false,  // don't return headers
            CURLOPT_FOLLOWLOCATION => true,   // follow redirects
            CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
            CURLOPT_ENCODING       => "",     // handle compressed
            CURLOPT_USERAGENT      => "test", // name of client
            CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
            CURLOPT_TIMEOUT        => 120,    // time-out on response
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );

        $ch = curl_init($url);        
        curl_setopt_array($ch, $options);       
        $content  = curl_exec($ch);
       
//        if (false === $content) {
//          var_dump(curl_error($ch));
//          throw new Exception(curl_error($ch), curl_errno($ch));
//        }
        
        curl_close($ch);

        return $content;
    }
    
    public static function create_tmp_phpversion_script() {   
      $basedir = dirname(dirname(dirname(dirname(__DIR__))));
      $filename = $basedir. '/sgtestphpver.php';
        
      $myfile = fopen($filename, "w") or die("Unable to open file!");
      $txt = "<?php die( PHP_VERSION );?>";
      fwrite($myfile, $txt);
      fclose($myfile);
    }
    
    public static function delete_tmp_phpversion_script() {   
      $basedir = dirname(dirname(dirname(dirname(__DIR__))));
      $filename = $basedir. '/sgtestphpver.php';
      unlink($filename);
    }

        
    /**
     * 
     * Returns the current PHP version Wordpress is running on.
     * example (5.6, 7.0 ... etc)
     * 
     * @since 2.3.11
     */
    public static function get_current_php_version() {
      if (php_sapi_name() == "cli") {
        self::create_tmp_phpversion_script();
        // md5( 'showmeversion ')
        $url = get_option('siteurl') . '/sgtestphpver.php';
        $phpversion = self::curl_get_content($url);
        
        // when wunning via cli if unable to get current version
        if (!preg_match("/^\d+\.\d+/", $phpversion)) {
          $recommended = self::get_recommended_php_versions();
          $phpversion = $recommended[0];
        }
        
        self::delete_tmp_phpversion_script();
        $version = explode('.', $phpversion);

        return $version[0] .'.'. $version[1];
      } else {
        $phpversion = PHP_VERSION;
      }      
      
      if (!defined('PHP_VERSION_ID')) {
        $version = explode('.', $phpversion);
        define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
      }

      if (PHP_VERSION_ID < 50207) {
          define('PHP_MAJOR_VERSION',   $version[0]);
          define('PHP_MINOR_VERSION',   $version[1]);
          define('PHP_RELEASE_VERSION', $version[2]);
      }

      return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    }
    
    /**
     * 
     * Returns an associative array of recommended php versions by priority
     * 
     * @since 2.3.11
    */        
    public static function get_recommended_php_versions() {
        return array(
            '7.1', '5.6'
        );
    }
    /**
     * @since 2.3.11
     * @param type $newVersionOrig
     * @return bool
     */
    public static function isUpToDate($newVersionOrig=false) {
        if ($newVersionOrig !== false) {
            $currentVersion = $newVersionOrig;
        } else {
            $currentVersion = self::get_current_php_version();
        }
                
        $recommendedPHPVersions = self::get_recommended_php_versions();
        $recommendedPHPVersion = $recommendedPHPVersions[0];
        $recommendedPHPVersion  = intval(str_replace('.', '', $recommendedPHPVersion ));
        $currentVersion = intval(str_replace('.', '', $currentVersion ));
        
        return ($currentVersion >= $recommendedPHPVersion);
    }

}
