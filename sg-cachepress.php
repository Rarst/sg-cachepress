<?php
/**
 * SG CachePress
 *
 * @package           SG_CachePress
 * @author            SiteGround  
 * @link              http://www.siteground.com/
 *
 * @wordpress-plugin
 * Plugin Name:       SG Optimizer
 * Description:       This plugin will link your WordPress application with all the performance optimizations provided by SiteGround
 * Version:           3.3.1
 * Author:            SiteGround
 * Text Domain:       sg-cachepress
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load text Domain
add_action( 'plugins_loaded', 'sgcachepress_load_textdomain' );
function sgcachepress_load_textdomain() {
  load_plugin_textdomain( 'sg-cachepress', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' ); 
}


// @todo Consider an autoloader?
require plugin_dir_path( __FILE__ ) . 'class-sg-cachepress.php';
require plugin_dir_path( __FILE__ ) . 'class-sg-cachepress-options.php';
require plugin_dir_path( __FILE__ ) . 'class-sg-cachepress-environment.php';
require plugin_dir_path( __FILE__ ) . 'class-sg-cachepress-supercacher.php';
require plugin_dir_path( __FILE__ ) . 'class-sg-cachepress-memcache.php';
require plugin_dir_path( __FILE__ ) . 'class-sg-cachepress-admin.php';
require plugin_dir_path( __FILE__ ) . 'class-sg-cachepress-phpversion-checker.php';
require plugin_dir_path( __FILE__ ) . 'php-compatibility-checker/sg-wpengine-phpcompat.php';
require plugin_dir_path( __FILE__ ) . 'class-sg-cachepress-ssl.php';


//Register WP-CLI command
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$sgpurge = function() {
		sg_cachepress_purge_cache();
	    WP_CLI::success( 'Purge request sent' );
	};
	WP_CLI::add_command( 'sg purge', $sgpurge );
}

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'SG_CachePress', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SG_CachePress', 'deactivate' ) );

add_action( 'plugins_loaded','sg_cachepress_start' );
add_action( 'admin_init', array('SG_CachePress','admin_init_cachepress') );

add_action( 'init', 'disable_other_caching_plugins' );

function filter_xmlrpc_login_error( $this_error, $user ) {
	if (function_exists('c74ce9b9ffdebe0331d8e43e97206424_notify')) {
		c74ce9b9ffdebe0331d8e43e97206424_notify("wpxmlrpc", getcwd(), "UNKNOWN");
	}
	return $this_error;
}
add_filter( 'xmlrpc_login_error', 'filter_xmlrpc_login_error', 10, 2 );

/**
 * Disables Other Caching Plugins if SG SuperCacher is enabled
 */
function disable_other_caching_plugins()
{
    $sg_cachepress_options        = new SG_CachePress_Options;
    if( $sg_cachepress_options->is_enabled('enable_cache') )
        add_filter( 'do_rocket_generate_caching_files', '__return_false' );
}

/**
 * Initialise the classes in this plugin.
 *
 * @since 1.1.0
 *
 * @todo Consider moving this to a dependency injection container, so we can avoid globals?
 */
function sg_cachepress_start() {

 	global $sg_cachepress, $sg_cachepress_options, $sg_cachepress_environment, $sg_cachepress_memcache,
 	$sg_cachepress_admin, $sg_cachepress_supercacher;

	$sg_cachepress_options        = new SG_CachePress_Options;
	$sg_cachepress_environment    = new SG_CachePress_Environment( $sg_cachepress_options );
	$sg_cachepress_admin    		= new SG_CachePress_Admin( $sg_cachepress_options );
	$sg_cachepress_memcache       = new SG_CachePress_Memcache( $sg_cachepress_options, $sg_cachepress_environment );
	$sg_cachepress_supercacher    = new SG_CachePress_Supercacher( $sg_cachepress_options, $sg_cachepress_environment );
	$sg_cachepress                = new SG_CachePress( $sg_cachepress_options);
        $sg_cachepress_phpversion_checker    		= new SG_CachePress_PHPVersionChecker( $sg_cachepress_options );
        
        $sg_cachepress_phpversion_checker->run();
	$sg_cachepress->run();
	$sg_cachepress_admin->run();

	if ( $sg_cachepress_environment->cache_is_enabled() ){
		if ( $sg_cachepress_environment->autoflush_enabled() ){
			$sg_cachepress_supercacher->run();
		}
	}

	if ( $sg_cachepress_environment->memcached_is_enabled() ){
		$sg_cachepress_memcache->run();
	}
}

/**
 * Public function to purge cache
 */
function sg_cachepress_purge_cache()
{
    global $sg_cachepress_supercacher;
    
    return $sg_cachepress_supercacher->purge_cache();
}
