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

/** SG CachePress main plugin class */

class SG_CachePress {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since 1.1.0
	 *
	 * @type string
	 */
	const VERSION = '1.1.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since 1.1.0
	 *
	 * @type string
	 */
	const PLUGIN_SLUG = 'sg-cachepress';

	/**
	 * Holds the options object
	 *
	 * @since 1.1.0
	 *
	 * @type SG_CachePress_Options
	 */
	protected $options_handler;

	/**
	 * Assign dependencies.
	 *
	 * @since 1.1.0
	 */
	public function __construct( $options_handler ) {
		$this->options_handler = $options_handler;
	}

	/**
	 * Initialize the class by hooking and running methods.
	 *
	 * @since 1.1.0
	 *
	 * @uses SG_CachePress::load_plugin_textdomain() Allow localised language files to be applied.
	 * @uses SG_CachePress::activate_new_site()      Handle activation on multisite.
	 * @uses SG_CachePress_Options::upgrade()        Convert old saved settings to new settings.
	 * @uses SG_CachePress::set_headers_cookies()    Set headers and cookies.
	 */
	public function run() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Allow a check to see if this plugin is running
		// curl -s http://domain.com/?sgCacheCheck=022870ae06716782ce17e4f6e7f69cc2
		if ( isset( $_GET['sgCacheCheck'] ) && md5( 'wpCheck' ) === $_GET['sgCacheCheck'] ) {
                  die( 'OK' );
                }
                
                // Check PHP version
//		// curl -s http://domain.com/?sgphpCheck=819483ed1511baac6c92a176da3bcfca
//                if ( isset( $_GET['sgphpCheck'] ) && md5( 'showmeversion' ) === $_GET['sgphpCheck'] ) {
//                  die( PHP_VERSION );
//                }
		$this->options_handler->upgrade();

		$this->set_headers_cookies();
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since 1.1.0
	 *
	 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is
	 *                              disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {   
                // call versionChecker's active method
                $versionChecker = new SG_CachePress_PHPVersionChecker(new SG_CachePress_Options);
                $versionChecker->activate();

		if ( $network_wide && is_multisite() ) {

			$sg_cachepress_multisite = new SG_CachePress_Multisite();
			$sg_cachepress_multisite->toggle_network_activation( true );
		} else {
			self::single_activate();
		}
		self::disable_first_run_option();
		self::clean_object_cache();
	}
	
	/**
	 * Cleans object cache to replace it with newer one
	 */
	private static function clean_object_cache()
	{
	    $file = trailingslashit( WP_CONTENT_DIR ) . 'object-cache.php';
	    if ( is_readable( $file ) ) {
	        unlink( $file );
	    }
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since 1.1.0
	 *
	 * @param boolean $network_wide True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is
	 *                              disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( $network_wide && is_multisite() ) {

			/**@var SG_CachePress_Multisite $sg_cachepress_multisite */
			global $sg_cachepress_multisite;
			$sg_cachepress_multisite->toggle_network_activation(false);
			return;
		}

		self::single_deactivate();
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since 1.1.0
	 *
	 * @param int $blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {
		if ( 1 !== did_action( 'wpmu_new_blog' ) )
			return;

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();
	}
	
	/**
	 * Fired when admin is initialised, currently used to make one time check if the plugin runs correctly.
	 */
	public static function admin_init_cachepress()
	{
	    if( current_user_can('activate_plugins') )
	    {
    	    $sg_cachepress_options  = new SG_CachePress_Options;
    	    if(!$sg_cachepress_options->is_enabled('first_run'))
    	    {
    	       self::enable_first_run_option();
    	       self::check_if_plugin_caches();
    	    }
	    }
	}
	
    /**
     * Resets the first run counter, so it can be called once on the first run
     */
    private static function disable_first_run_option()
	{
	    $sg_cachepress_options  = new SG_CachePress_Options;
	    $sg_cachepress_options->disable_option('first_run');
	}
	
	/**
	 * Sets the first run counter to enabled, to prevent running commands more than once when admin panel is initialised
	 */
	private static function enable_first_run_option()
	{
	    $sg_cachepress_options  = new SG_CachePress_Options;
	    $sg_cachepress_options->enable_option('first_run');
	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since 1.1.0
	 */
	public static function single_activate() {
		$sg_cachepress_options  = new SG_CachePress_Options;
		$sg_cachepress          = new SG_CachePress( $sg_cachepress_options );
		if ( ! $sg_cachepress_options->get_option() )
			$sg_cachepress_options->init_options();
	}
	
	/**
	 * Checks if the plugin caches correctly and shows notice when it does not
	 * 
	 * @since 2.2.7
	 */
	public static function check_if_plugin_caches()
	{
	    $sg_cachepress_options = new SG_CachePress_Options();
	    $urlToCheck = get_home_url();
        
	    if( $sg_cachepress_options->is_enabled('enable_cache') )
	    {
	        if( SG_CachePress_Supercacher::return_cache_result($urlToCheck) == 0 )
	        {
	            if( SG_CachePress_Supercacher::return_cache_result($urlToCheck) == 0 )
	            {
	                $sg_cachepress_options->enable_option('show_notice');
	                return false;
	            }
	            else
	            {
	                $sg_cachepress_options->disable_option('show_notice');
	                return true;
	            }
	        }
	        else
	        {
	            $sg_cachepress_options->disable_option('show_notice');
	            return true;
	        }
	    }
	    else
	    {
	        $sg_cachepress_options->disable_option('show_notice');
	        return true;
	    }
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since 1.1.0
	 */
	public static function single_deactivate() {
		// TODO: Define deactivation functionality here?
	    $sg_cachepress_options = new SG_CachePress_Options();
	    $sg_cachepress_options->disable_option('show_notice');
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.1.0
	 */
	public function load_plugin_textdomain() {
		$domain = self::PLUGIN_SLUG;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
     * Check if url is in caching blacklist
	 *
	 * @since 1.1.1
     * @return bool
     */
    protected function is_url_blacklisted() {
    	global $sg_cachepress_environment;
        $blacklistArray = explode("\n",$this->options_handler->get_blacklist());

        $blacklistRegexArray = array();
        $indexIsBlacklisted = false;
        foreach($blacklistArray as $key=>$row)
        {
        	$row = trim($row);

        	if ($row != '/' && $quoted = preg_quote($row,'/'))
        		$blacklistRegexArray[$key] = $quoted;

        	if ($row == '/')
        		$indexIsBlacklisted = true;
        }

        if ($indexIsBlacklisted && $_SERVER['REQUEST_URI'] == $sg_cachepress_environment->get_application_path())
        	return true;

        if (empty($blacklistRegexArray))
        	return false;

        $blacklistRegex = '/('.implode('|',$blacklistRegexArray) . ')/i';

        return preg_match($blacklistRegex, $_SERVER['REQUEST_URI']);
    }

	/**
	 * Set headers and cookies.
	 *
	 * @since 1.1.0
	 */
	protected function set_headers_cookies() {
		if ( ! $this->options_handler->is_enabled( 'enable_cache' ) || $this->is_url_blacklisted()) {
			header( 'X-Cache-Enabled: False' );
			return;
		}

		header( 'X-Cache-Enabled: True' );
		
		// Check if WP LOGGED_IN_COOKIE is set, validate it and define $userIsLoggedIn
		if ( isset( $_COOKIE[LOGGED_IN_COOKIE] ) ) {
			$userIsLoggedIn = wp_validate_auth_cookie( $_COOKIE[LOGGED_IN_COOKIE], 'logged_in' );
		} else {
		$userIsLoggedIn = false;
		}
		
		// Logged In Users
		if ( $userIsLoggedIn || ( ! empty( $_POST['wp-submit'] ) && 'Log In' === $_POST['wp-submit'] ) ) {
			// Enable the cache bypass for logged users by setting a cache bypass cookie
 			setcookie( 'wpSGCacheBypass', 1, time() + 100 * MINUTE_IN_SECONDS, '/' );
		} elseif ( ! $userIsLoggedIn || 'logout' === $_GET['action'] ) {
			setcookie( 'wpSGCacheBypass', 0, time() - HOUR_IN_SECONDS, '/' );
		}
	}

}
