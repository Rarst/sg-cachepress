<?php
/*
Plugin Name: PHP Compatibility Checker
Plugin URI: https://wpengine.com
Description: Make sure your plugins and themes are compatible with newer PHP versions.
Author: WP Engine
Version: 1.3.2
Author URI: https://wpengine.com
Text Domain: php-compatibility-checker
*/

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
	 * @since 0.1.0
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
	 * @since  1.0.0
	 * @action wp_ajax_sg_wpephpcompat_start_test
	 * @action sg_wpephpcompat_start_test_cron
	 * @return null
	 */
	function start_test() {
		if ( current_user_can( 'manage_options' ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
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
	 * @since  1.0.0
	 * @action wp_ajax_wpephpcompat_check_status
	 * @return null
	 */
	function check_status() {            
            if ( current_user_can( 'manage_options' ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
                    $scan_status = get_option( 'sg_wpephpcompat.status' );
                    $count_jobs = wp_count_posts( 'sg_wpephpcompat_jobs' );
                    $total_jobs = get_option( 'sg_wpephpcompat.numdirs' );
                    $test_version = get_option( 'sg_wpephpcompat.test_version' );
                    $only_active = get_option( 'sg_wpephpcompat.only_active' );

                    $active_job = false;
                    $jobs = get_posts( array(
                            'posts_per_page' => -1,
                            'post_type'      => 'sg_wpephpcompat_jobs',
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
	 * @since 1.3.2
	 * @action wp_ajax_wpephpcompat_clean_up
	 */
	function clean_up() {
		if ( current_user_can( 'manage_options' ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			$wpephpc = new \SG_WPEPHPCompat( dirname(__DIR__) );
			$wpephpc->clean_after_scan();
			delete_option( 'sg_wpephpcompat.scan_results' );
			wp_send_json( 'success' );
		}
	}

	/**
	 * Create custom post type to store the directories we need to process.
	 *
	 * @since 1.0.0
	 * @return  null
	 */
	function create_job_queue() {
		register_post_type( 'sg_wpephpcompat_jobs',
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
	 * @since 1.0.0
	 * @action admin_enqueue_scripts
	 * @return  null
	 */
	function admin_enqueue( $hook ) {
		if ( $hook !== 'toplevel_page_sg-cachepress' ) {
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
		$strings = array(
			'name'       => __( 'Name', 'sg-cachepress' ),
			'compatible' => __( 'compatible', 'sg-cachepress' ),
                        'not_compatible' => __( 'Your WordPress site is NOT compatible with ', 'sg-cachepress' ),
                        'see_details_below' => __( 'See details below.', 'sg-cachepress' ),
                        'click_upgrade_version' => __( 'Click upgrade button to ugprade your PHP version.', 'sg-cachepress' ),
			//'are_not'    => __( 'plugins/themes are possibly not compatible', 'sg-cachepress' ),
			//'is_not'     => __( 'Your WordPress site is possibly not PHP', 'sg-cachepress' ),
			//'out_of'     => __( 'out of', 'sg-cachepress' ),
			'run'        => __( 'Check PHP Version', 'sg-cachepress' ),
			'rerun'      => __( 'Check Again', 'sg-cachepress' ),
			'your_wp'    => __( 'Your WordPress site is', 'sg-cachepress' ),
                        'check_your_php_version' => __( 'Checks the PHP version your WordPress site is running and whether you\'re on the fastest possible PHP version.', 'sg-cachepress' ),
                        'upgrade_to' => __( 'Upgrade to', 'sg-cachepress' ),
		);
                               

		wp_localize_script( 'sg_wpephpcompat', 'sg_wpephpcompat', $strings );
	}
        
        
        /**
        * This function hides the notice from displaying when it is manually closed
        *
        * @since 2.3.11
        */
        function message_hide()
        {
           $id = $_POST['notice_id'];           
           update_option('show_notice_' . $id, 0); // disable option                 
           echo $id;
           wp_die();
        }
       
        public function global_notice_template($msg, $id)
        {
            if (get_option('show_notice_' . $id)) {
                $html = '<div id="ajax-' . $id . '" class="updated sg-cachepress-notification-by-id">';
                $html .= '<p>';
                $html .= __('<strong>SG CachePress PHP Version:</strong>'
                        . $msg . ' Click <a href="http://www.siteground.com/tutorials/supercacher/" target="_blank">here</a> for mode details. '
                        . '<a href="javascript:;" id="' . $id . '" class="dismiss-sg-cahepress-notification-by-id">Click here to hide this notice</a>.', 'ajax-notification');
                $html .= '</p>';
                $html .= '<span id="ajax-notification-nonce" class="hidden">' . wp_create_nonce('ajax-notification-nonce') . '</span>';
                $html .= '</div>';
                echo $html;
            }
        }

    public function global_notice_phpversion_changed()
    {
        global_notice_template(' Your PHP version has been changed to <strong>PHP 7.0.13</strong>.', 'notification-1');
    }

    public function global_notice_phpversion_not_updated()
    {
        global_notice_template(' You website doesn\'t run on the recommended by SiteGround PHP version. ', 'notification-2');
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
        $availableVersions = self::get_available_php_versions();
        
        if ( !in_array($_POST['version'], $availableVersions) ) {
            die(0);
        } else {
            $version = $_POST['version'];
        }                
        
        $basedir = dirname(dirname(dirname(dirname(__DIR__))));
        $filename = $basedir. '/.htaccess';

        if (!is_writable($filename)) {
            die(0);
        }

        $htaccessContent = file_get_contents($filename);            
        $version = str_replace('.', '', $version);

        $addHandlerOption = 'AddHandler application/x-httpd-php' . $version . ' .php .php5 .php4 .php3';

        $htaccessNewContent = preg_replace(
            '/(AddHandler\s+application\/x-httpd-php)(\w+)(\s+\.php\s+\.php5\s+\.php4\s+\.php3)/s', 
            '${1}'. $version .'${3}', 
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
            'PHP 5.3' => '5.3',                                                                                
        ));
    }
        
    /**
     * 
     * Returns the current PHP version Wordpress is running on.
     * example (5.6, 7.0 ... etc)
     * 
     * @since 2.3.11
     */
    public static function get_current_php_version() {
        if (!defined('PHP_VERSION_ID')) {
            $version = explode('.', PHP_VERSION);
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
            '7.0', '5.6'
        );
    }

}
