<?php
/**
 * SG CachePress
 *
 * @package   SG_CachePress
 * @author    SiteGround
 * @author    George Penkov
 * @author    Gary Jones <gamajo@gamajo.com>
 * @link      http://www.siteground.com/
 * @copyright 2014 SiteGround
 */

/** SG CachePress purge cache admin class */

class SG_CachePress_Admin {    
	public static $enable_php_version_checker = true;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $page_hook = null;

	/**
	 * Holds the options object
	 *
	 * @since 1.1.0
	 *
	 * @type SG_CachePress_Options
	 */
	protected $options_handler;

	/** @var SG_CachePress_Performance_Tool $performance_tool */
	protected $performance_tool;

	/**
	 * Assign dependencies.
	 *
	 * @since 1.1.0
	 *
	 * @param SG_CachePress_Options $options_handler
	 */
	public function __construct( $options_handler ) {
		$this->options_handler = $options_handler;

		global $sg_cachepress_performance_tool;
		$this->performance_tool = $sg_cachepress_performance_tool;
	}

	/**
	 * Initialize the administration functions.
	 *
	 * @since 1.1.0
	 */
	public function run() {

		$disallow_cache_config = $this->options_handler->is_enabled( 'disallow_cache_config' );
		$disallow_https_config = $this->options_handler->is_enabled( 'disallow_https_config' );

		if ( $disallow_cache_config && $disallow_https_config ) {
			return;
		}

		// Add the admin page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ));
		
		// Add the submenu pages and menu items
		if ( ! $disallow_cache_config ) {
			add_action( 'admin_menu', array( $this, 'add_plugin_caching_menu' ) );
		}

		if ( ! $disallow_https_config ) {
			add_action( 'admin_menu', array( $this, 'add_plugin_ssl_menu' ) );
		}

		if ( self::$enable_php_version_checker && ! is_multisite() ) {
			add_action( 'admin_menu', array( $this, 'add_plugin_php_menu' ) );
		}

		//if ( ! is_multisite() ) {
		//	add_action( 'admin_menu', [ $this, 'add_plugin_performance_menu' ] );
		//}

		// Admin Init
		add_action( 'admin_init', array( $this, 'load_admin_global_js' ));
		
		// Add admin notification notices, so it can display when there is a problem with the plugin
		add_action( 'admin_notices', array( $this, 'plugin_admin_notices'));

		// Add the admin bar purge button
		if ( ! $disallow_cache_config ) {
			add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_purge' ), PHP_INT_MAX );

			// Add the admin bar purge button handler
			add_action( 'admin_post_sg-cachepress-purge',  array( 'SG_CachePress_Supercacher', 'purge_cache_admin_bar' ) );
		}

		// Load admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Ajax callback
		add_action( 'wp_ajax_sg-cachepress-purge', array( $this, 'purge_cache' ) );
		add_action( 'wp_ajax_sg-cachepress-blacklist-update', array( $this, 'update_blacklist' ) );
		add_action( 'wp_ajax_sg-cachepress-parameter-update', array( $this, 'update_parameter' ) );
		add_action( 'wp_ajax_sg-cachepress-cache-test', array( $this, 'cache_test_callback' ) );
		add_action( 'wp_ajax_sg-cachepress-cache-test-message-hide', array( $this, 'cache_test_message_hide' ) );
		add_action( 'wp_ajax_sg-cachepress-ssl-toggle', array( $this, 'ajax_toggle' ) );

		// Add the admin bar purge button handler
		add_action( 'admin_post_sg-cachepress-purge',  array( 'SG_CachePress_Supercacher', 'purge_cache_admin_bar' ) );

		$is_login = ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] );

		if ( ! $is_login && ! is_admin() && get_option( 'sg_cachepress_ssl_enabled' ) === '1' ) {
			SG_CachePress_SSL::fix_mixed_content();
		}


	}

    /**
     * Ajax handler for ssl toggle
     */
    function ajax_toggle() {
        if (!current_user_can( 'manage_options' )) {
            return;
        }

        if (!isset($_POST['nonce'])) {
            return;
        }

        if (!wp_verify_nonce( $_POST['nonce'], 'sg-cachepress-ssl-toggle' )) {
            return;
        }

        SG_CachePress_SSL::toggle();
    }

    /**
     * Ajax handler for purge cache
     */
    function purge_cache() {
        if (!current_user_can( 'manage_options' )) {
            return;
        }

        if (!isset($_POST['nonce'])) {
            return;
        }

        if (!wp_verify_nonce( $_POST['nonce'], 'sg-cachepress-purge' )) {
            return;
        }

	    SG_CachePress_Supercacher::purge_cache();
    }
	
	/**
	 * Displays the notice on the top of admin panel if it has caching issues
	 * 
	 * @since 2.2.7
	 */
	function plugin_admin_notices() {
	    $options = new SG_CachePress_Options();
	    
	    if( $options->get_option('show_notice') == 1 )
	    {
    	    $html = '<div id="ajax-notification" class="updated sg-cachepress-notification">';
    	    $html .= '<p>';
    	    $html .= __( '<strong>SG Optimizer:</strong> Your site '.get_home_url().' is <strong>not cached</strong>! Make sure the Dynamic Cache is enabled in the SuperCacher tool in cPanel. <a href="javascript:;" id="dismiss-sg-cahepress-notification" nonce="' . wp_create_nonce( 'sg-cachepress-cache-test-message-hide' ) . '">Click here to hide this notice</a>.', 'ajax-notification' );
    	    $html .= '</p>';
    	    $html .= '<span id="ajax-notification-nonce" class="hidden">' . wp_create_nonce( 'ajax-notification-nonce' ) . '</span>';
    	    $html .= '</div>';
    	    echo $html;
	    }
	}
	
	/**
	 * Loads the global admin js
	 * 
	 * @since 2.2.7
	 */
	function load_admin_global_js()
	{
	    wp_enqueue_script( '', plugins_url( 'js/admin_global.js', __FILE__ ), array( 'jquery' ), SG_CachePress::VERSION, true );

	}
	
	/**
	 * This make test if the cache is on returning the value of the x-proxy-cache header from the desired page by $_POST['url'] parameter
	 * 
	 * @since 2.2.7
	 */
	function cache_test_callback()
	{
        if (!current_user_can( 'manage_options' )) {
            return;
        }

        if (!isset($_POST['nonce'])) {
            return;
        }

        if (!wp_verify_nonce( $_POST['nonce'], 'sg-cachepress-cache-test' )) {
            return;
        }

	    $urlToCheck = get_home_url()."/".$_POST['url'];
	    $result = SG_CachePress_Supercacher::return_cache_result($urlToCheck);
	    
	    if($result == 1 && empty($_POST['url']))
	    {
	        $options = new SG_CachePress_Options();
	        $options->disable_option('show_notice');
	    }
	    
	    echo (int) $result;
        wp_die();
	}
	
	/**
	 * This function hides the notice from displaying when it is manually closed
	 *
	 * @since 2.2.7
	 */
	function cache_test_message_hide()
	{
        if (!current_user_can( 'manage_options' )) {
            return;
        }

        if (!isset($_POST['nonce'])) {
            return;
        }

        if (!wp_verify_nonce( $_POST['nonce'], 'sg-cachepress-cache-test-message-hide' )) {
            return;
        }

	    $options = new SG_CachePress_Options();
	    $options->disable_option('show_notice');
	    
	    echo 1;
	    wp_die();
	}
	
	/**
	 * Preloads and caches the server type so it wont have to make query each time when is needed
	 */
	public static function return_and_cache_server_type()
	{
	    $sgcachepress_options = new SG_CachePress_Options();
	    if( !$sgcachepress_options->is_enabled('checked_nginx') ) 
	    {
	        $sgcachepress_options->enable_option('checked_nginx');
	        if( SG_CachePress_Supercacher::return_check_is_nginx() )
	        {
	            $sgcachepress_options->enable_option('is_nginx');
	            return true;
	        }
	        else
	        {
	            $sgcachepress_options->disable_option('is_nginx');
	            return false;
	        }
	    }
	    
	    if( $sgcachepress_options->is_enabled('is_nginx') )
	        return true;
	        
	    return false;
	}

	/**
	 * Adds a purge buttion in the admin bar menu
	 *
	 * @param $wp_admin_bar WP_Admin_Bar
	 * @since 2.2.1
	 */
	function add_admin_bar_purge( $wp_admin_bar ){
		$args = array(
				'id'    => 'SG_CachePress_Supercacher_Purge',
				'title' => __('Purge SG Cache','sg-cachepress'),
				'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=sg-cachepress-purge' ),'sg-cachepress-purge' ),
				'meta'  => array( 'class' => 'sg-cachepress-admin-bar-purge' )
		);
		if ( current_user_can('manage_options') ) {
			$wp_admin_bar->add_node( $args );
		}
	}

	/**
	 * Updates a param from ajax request
	 *
	 * @since 1.1.0
	 */
	public function update_parameter() {
        if (!current_user_can( 'manage_options' )) {
            return;
        }

        if (!isset($_POST['nonce'])) {
            return;
        }

        if (!wp_verify_nonce( $_POST['nonce'], 'sg-cachepress-parameter-update' )) {
            return;
        }

		$paramTranslator = array(
			'dynamic-cache' 	=> 'enable_cache',
			'memcached'			=> 'enable_memcached',
			'autoflush-cache'	=> 'autoflush_cache'
		);

		$paramName    = $_POST['parameterName'];

		if ( in_array( $paramName, [ 'default-enable-cache', 'default-autoflush-cache' ], true ) ) {
			$paramName    = 'sg-cachepress-' . $paramName;
			$currentValue = (int) get_site_option( $paramName, 0 );
			$toggledValue = (int)!$currentValue;

			$result = update_site_option( $paramName, $toggledValue );
			die( $result ? (string)$toggledValue : (string)$currentValue );
		}

		$paramName    = $paramTranslator[ $paramName ];
		$currentValue = (int)$this->options_handler->get_option($paramName);
		$toggledValue = (int)!$currentValue;               

		//if cache is turned on or off it's a good idea to flush it on right away
		if ($paramName == 'enable_cache') {
			SG_CachePress_Supercacher::purge_cache();
		}

		if ($paramName == 'enable_memcached') {
			global $sg_cachepress_memcache;
			//check if we can actually enable memcached and display error if not
			if ($toggledValue == 1) {
				if (!$sg_cachepress_memcache->check_and_create_memcached_dropin())
					die( "Please, first enable Memcached from your cPanel!" );
			}
			else {
				if (!$sg_cachepress_memcache->remove_memcached_dropin())
					die( "Could not disable memcache!" );
			}
		}

		if ($this->options_handler->update_option($paramName,$toggledValue))
		{
		    if($paramName == 'enable_cache' && $toggledValue == 1)
		    {
		        SG_CachePress::check_if_plugin_caches();
		    }
		    else if($paramName == 'enable_cache' && $toggledValue == 0)
		    {
		        $sg_cachepress_options = new SG_CachePress_Options();
		        $sg_cachepress_options->disable_option('show_notice');
		    }
			die((string)$toggledValue);
		}
		else
			die((string)$currentValue);
	}
	/**
	 * Updates the blacklist from ajax request
	 *
	 * @since 1.1.0
	 */
	public function update_blacklist() {
        if (!current_user_can( 'manage_options' )) {
            return;
        }

        if (!isset($_POST['nonce'])) {
            return;
        }

        if (!wp_verify_nonce( $_POST['nonce'], 'sg-cachepress-blacklist-update' )) {
            return;
        }

		die((int)$this->options_handler->update_option('blacklist',$_POST['blacklist']));
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since 1.1.0
	 */
	public function enqueue_admin_styles() {
		if ( ! isset( $this->page_hook ) )
			return;

		$screen = get_current_screen();
		if ( in_array( $screen->id, array(
			'sg-optimizer_page_ssl',
			'sg-optimizer_page_caching',
			'toplevel_page_sg-cachepress',
			'sg-optimizer_page_php-check',
			'sg-optimizer_page_php-check-network',
			'sg-optimizer_page_performance-test',
			'toplevel_page_sg-cachepress-network',
			'sg-optimiser_page_ssl',
			'sg-optimiser_page_caching',
			'sg-optimiser_page_php-check',
			'sg-optimiser_page_php-check-network',
			'sg-optimiser_page_performance-test'
		), true ) )
		{
			wp_enqueue_style( 'SGOptimizer', plugins_url( 'css/admin.css', __FILE__ ), array(), SG_CachePress::VERSION );
		}
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since 1.1.0
	 *
	 * @return null Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
  
		if ( ! isset( $this->page_hook ) )
			return;
		$screen = get_current_screen();

		if ( in_array( $screen->id, array(
			'sg-optimizer_page_ssl',
			'sg-optimizer_page_caching',
			'toplevel_page_sg-cachepress',
			'toplevel_page_sg-cachepress-network',
			'sg-optimizer_page_php-check',
			'sg-optimizer_page_php-check-network',
			'sg-optimiser_page_ssl',
			'sg-optimiser_page_caching',
			'sg-optimiser_page_php-check',
			'sg-optimiser_page_php-check-network'
		), true ) )
		{
			wp_enqueue_script( SG_CachePress::PLUGIN_SLUG . '-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), SG_CachePress::VERSION, true );
			$strings = array(
				'purge'   => __( 'Purge the Cache', 'sg-cachepress' ),
				'purging' => __( 'Purging, please wait...', 'sg-cachepress' ),
				'updating' => __( 'Updating, please wait...', 'sg-cachepress' ),
				'updated'  => __( 'Update the Exclude List', 'sg-cachepress' ),
				'purged'  => __( 'Successfully Purged', 'sg-cachepress' ),
			    'ajax_url' => admin_url( 'admin-ajax.php' ),
			    'testing' => __( 'Testing...', 'sg-cachepress' ),
			    'cached' => __( 'CACHED', 'sg-cachepress' ),
			    'notcached' => __( 'DYNAMIC', 'sg-cachepress' ),
			    'noheaders' => __( 'CAN\'T GET HEADERS', 'sg-cachepress' ),
			    'testurl' => __( 'Test URL', 'sg-cachepress' ),
			    'showstat' => __( 'Test URL', 'sg-cachepress' ),
                            'phpversion_check' => __( 'Check PHP Version', 'sg-cachepress' ),
                            'phpversion_checking' => __( 'Checking, please wait...', 'sg-cachepress' ),
                            'phpversion_change' => __( 'Change PHP Version', 'sg-cachepress' ),    
                            'ssl_toggle_failed' => __( 'SSL toggle failed', 'sg-cachepress' ),                            
			);
			wp_localize_script( SG_CachePress::PLUGIN_SLUG . '-admin', 'sgCachePressL10n', $strings );
		}

		if ( in_array( $screen->id, [
			'sg-optimizer_page_performance-test',
			'sg-optimiser_page_performance-test',
			'toplevel_page_sg-cachepress-network'
		], true ) ) {
			wp_enqueue_script(
				SG_CachePress::PLUGIN_SLUG . '-performance',
				plugins_url( 'js/performance.js', __FILE__ ),
				[ 'jquery' ],
				SG_CachePress::VERSION,
				true
			);

			wp_enqueue_script(
				SG_CachePress::PLUGIN_SLUG . '-chart',
				plugins_url( 'js/chart.min.js', __FILE__ ),
				[],
				2.6,
				true
			);
		}
	}

	/**
	 * Register the top level page into the WordPress admin menu.
	 *
	 * @since 1.1.0
	 */
	public function add_plugin_admin_menu() {
		$this->page_hook = add_menu_page(
			__( 'SG Optimizer', 'sg-cachepress' ), // Page title
			__( 'SG Optimizer', 'sg-cachepress' ),    // Menu item title
			'manage_options',
			SG_CachePress::PLUGIN_SLUG,   // Page slug
			array( $this, 'display_plugin_admin_page' ),
			plugins_url('sg-cachepress/css/logo-white.svg')
		);
	}
	
	/**
	 * Register the sub-pages in the WordPress Admin Menu
	 *
	 * @since 3.0.0
	 */
	
	public function add_plugin_ssl_menu() {
		$this->page_hook = add_submenu_page(
			SG_CachePress::PLUGIN_SLUG, 
			__( 'HTTPS Config', 'sg-cachepress' ), // Page title
			__( 'HTTPS Config', 'sg-cachepress' ),    // Menu item title
			'manage_options',
			'ssl',   // Page slug
			array( $this, 'display_plugin_ssl_page' ),
			plugins_url('sg-cachepress/css/logo-white.svg')
		);
	}

	public function add_plugin_caching_menu() {
		$this->page_hook = add_submenu_page(
			SG_CachePress::PLUGIN_SLUG,
			__( 'SuperCacher Config', 'sg-cachepress' ), // Page title
			__( 'SuperCacher Config', 'sg-cachepress' ),    // Menu item title
			'manage_options',
			'caching',   // Page slug
			array( $this, 'display_plugin_caching_page' ),
			plugins_url('sg-cachepress/css/logo-white.svg')
		);
	}
	
	public function add_plugin_php_menu() {
		$this->page_hook = add_submenu_page(
			SG_CachePress::PLUGIN_SLUG, 
			__( 'PHP Config', 'sg-cachepress' ), // Page title
			__( 'PHP Config', 'sg-cachepress' ),    // Menu item title
			'manage_options',
			'php-check',   // Page slug
			array( $this, 'display_plugin_php_page' ),
			plugins_url('sg-cachepress/css/logo-white.svg')
		);
	}

	public function add_plugin_performance_menu() {

		$this->page_hook = $this->performance_tool->admin_menu();
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since 1.1.0
	 */
	public function display_plugin_admin_page() {
		include 'views/sg-cache.php';
	}
	public function display_plugin_ssl_page() {
		include 'views/ssl.php';
	}
	public function display_plugin_caching_page() {
		include 'views/caching.php';
	}
	public function display_plugin_php_page() {
		include 'views/php-check.php';
	}
}
