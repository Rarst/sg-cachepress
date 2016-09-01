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
 

/** SG CachePress main plugin class  */

class SG_CachePress_Memcache {

	/**
	 * Flag which raises when we already flushed on this page exec.
	 *
	 * @since Unknown
	 *
	 * @type bool
	 */
	private static $_flushed = false;

	/**
	 * Holds the options object.
	 *
	 * @since 1.1.0
	 *
	 * @type SG_CachePress_Options
	 */
	protected $options_handler;

	/**
	 * Holds the environment object.
	 *
	 * @since 1.1.0
	 *
	 * @type SG_CachePress_Environment
	 */
	protected $environment;

	/**
	 * Assign depdndencies.
	 *
	 * @since 1.1.0
	 *
	 * @param SG_CachePress_Options     $options_handler
	 * @param SG_CachePress_Environment $environment
	 */
	public function __construct( $options_handler, $environment ) {
		$this->options_handler = $options_handler;
		$this->environment     = $environment;
	}

	/**
	 * Initialize the class by hooking and running methods.
	 *
	 * @since 1.1.0
	 *
	 * @return null Return early and avoid any further interaction if accessing the script via CLI.
	 */
	public function run() {
	    // If memcache is enabled and object cache is missing
		if ( $this->options_handler->is_enabled( 'enable_memcached' ) && !$this->check_if_dropin_exists()){
		    $this->environment->log('Memcached option in wp-admin is enabled, but object-cache.php file does not exist or Memcached connection can\'t be established. Trying to establish new connection and create object-cache.php file.');
			// If cannot create memcache - FAIL
		    if (!$this->check_and_create_memcached_dropin()) {
		        $this->environment->log('Creating of new object-cache.php failed or Memcached connection can\'t be established.');
				// Start fail timer of 5 minutes cooldown until memcache will be disabled as option in wp-admin
				if ($this->options_handler->get_option('last_fail') == 0) {
				    $this->environment->log('Set timer to disable Memcached option in wp-admin after 5 minutes');
				    $this->options_handler->update_option('last_fail', time());
				} elseif ($this->options_handler->get_option('last_fail') < time() - 60*5) {
				    $this->environment->log('Disabling Memcached option in wp-admin panel');
                    $this->options_handler->disable_option('enable_memcached');
                    $this->options_handler->disable_option('last_fail');
				}
			} else {
			    $this->environment->log('Memcached connection has been established successfully and object-cache.php file has been created. The Memcached is now working and the timer has been reset.');
			    $this->options_handler->disable_option('last_fail');
			}
		}
        
		// If there is object cache file but memcache is not enabled
		if ( !$this->options_handler->is_enabled( 'enable_memcached' ) && $this->check_if_dropin_exists())
		{
		    $this->environment->log('object-cache.php file exist, but memcache is disabled! Trying to delete object-cache.php file.');
			if (!$this->remove_memcached_dropin()) {
			    // Enable Memcache
				$this->options_handler->enable_option('enable_memcached');
				$this->environment->log('object-cache.php can not be removed, so memcache is enabled.');
			} else {
			    $this->environment->log('object-cache.php is removed.');
			}
		}
	}

	/**
	 * Check if the object-cache.php dropin file exists (is readable).
	 *
	 * @since 1.2.0
	 *
 	 * @return bool
	 */
	protected function check_if_dropin_exists(){
		return is_readable( $this->get_object_cache_file() );
	}

	/**
	 * Get the path to where the object cache dropin should be.
	 *
	 * @since 1.1.0
	 */
	protected function get_object_cache_file() {
		return trailingslashit( WP_CONTENT_DIR ) . 'object-cache.php';
	}

	/**
	 * Get the contents of a port file specific to an account.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $account_name Name of the account (bit after /home/).
	 *
	 * @return string|false Contents of the port file, or empty string if it couldn't be read.
	 */
	protected function get_port_file_contents( $account_name ) {
		$port_file = "/home/{$account_name}/.SGCache/cache_status";
		if ( ! is_readable( $port_file ) )
			return '';
		return file_get_contents( $port_file );
	}

	/**
	 * Search a string for what looks like a Memcached port.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $string Any string, but likely the contents of a port file.
	 *
	 * @return string Port number, or empty string if it couldn't be determined.
	 */
	protected function get_memcached_port_from_string( $string ) {
		if ( ! preg_match( '#memcache\|\|([0-9]+)#', $string, $matches ) )
			return '';
		if ( ! isset( $matches[1] ) || ! $matches[1] )
			return '';
		return $matches[1];
	}

	/**
	 * Get the Memcached port for the current account.
	 *
	 * @since Unknown
	 *
	 * @return string Memcached port number, or empty string if error.
	 */
	protected function get_memcached_port() {
		$account_name = get_current_user();

		$port_file_content = $this->get_port_file_contents( $account_name );
		if ( ! $port_file_content )
			return '';

		return $this->get_memcached_port_from_string( $port_file_content );
	}

	/**
	 * Check and create a Memcached dropin.
	 *
	 *
	 * @since Unknown
	 *
	 * @return bool True on dropin creation, false otherwise.
	 */
	public function check_and_create_memcached_dropin() {
		$ip = '127.0.0.1';
		$port = $this->get_memcached_port();
		if ( ! $port )
			return false;

		$object_cache_file = $this->get_object_cache_file();
        
		if ( class_exists( 'Memcached' ) )
		{
		    //Use Memcached
		    $memcache = new Memcached();
		    @$memcache->addServer( $ip, $port );
		    $file = 'memcached.tpl';
		    $type = 'Memcached';
		}
		else
		{
		    //Use Memcache
		    $memcache = new Memcache;
		    @$memcache->connect( $ip, $port );
		    $file = 'memcache.tpl';
		    $type = 'Memcache';
		}

		if ( $this->memcached_connection_is_working( $memcache, $type ) )
		{
		    return $this->create_memcached_dropin( $ip, $port, $file );
		}

		$this->remove_memcached_dropin();

		return false;
	}

	/**
	 * Check if a Memcached connection is working by setting and immediately getting a value.
	 *
	 * @since 1.1.0
	 *
	 * @param  object $connection Memcache object.
	 *
	 * @return bool True on retrieving exactly the value set, false otherwise.
	 */
	protected function memcached_connection_is_working( $connection, $type = 'Memcache' ) {
		if ( ! $connection )
			return false;
		
		if( $type == 'Memcache' )
            @$connection->set( 'SGCP_Memcached_Test', 'Test!1', MEMCACHE_COMPRESSED, 50 );
		elseif( $type == 'Memcached' )
		    @$connection->set( 'SGCP_Memcached_Test', 'Test!1', 50 );
		    
		if ( @$connection->get( 'SGCP_Memcached_Test' ) === 'Test!1' )
		{
			$connection->flush();
 			return true;
		}
 		return false;
	}

	/**
	 * Copy the Memcache template contents into object-cache.php, replacing IP and Port where needed.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $ip   Memcached IP.
	 * @param  string $port Memcached Port.
	 *
	 * @return bool True if the template was successfully copied, false otherwise.
	 */
	protected function create_memcached_dropin( $ip, $port, $file ) {
		$object_cache_file = $this->get_object_cache_file();
		$template = file_get_contents( dirname( __FILE__ ) . "/" . $file );
		$find = '@changedefaults@';
 		$replace = "{$ip}:{$port}";
 		$new_object_cache = str_replace( $find, $replace, $template );
 		if ( file_put_contents( $object_cache_file, $new_object_cache ) )
 			return true;
 		return false;
	}

	/**
	 * Remove the object-cache.php file.
	 *
	 * @since Unknown
	 *
	 * @return bool True on successful removal, false otherwise.
	 */
	public function remove_memcached_dropin() {
		$object_cache_file = $this->get_object_cache_file();

		if ( is_readable( $object_cache_file ) ) {
			unlink( $object_cache_file );
			return true;
		}
		return false;
	}
}