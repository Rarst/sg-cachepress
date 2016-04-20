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

/** SG CachePress environment class */

class SG_CachePress_Environment {

	/**
	 * Holds the options object.
	 *
	 * @since 1.1.0
	 *
	 * @type SG_CachePress_Options
	 */
	protected $options_handler;

	/**
	 * Hold environment data.
	 *
	 * @todo Make this class implement ArrayAccess so it can grab values direct from this property.
	 *
	 * @type array
	 */
	protected $data = array();

	/**
	 * Assign dependencies.
	 *
	 * @since 1.1.0
	 *
	 * @param SG_CachePress_Options $options_handler
	 */
	public function __construct( $options_handler ) {
		$this->options_handler = $options_handler;
	}

	/**
	 * Obtain and set application's path and URL for further usage.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function get_application_path() {
		if ( ! isset( $this->data['application_path'] ) ) {
			$homeUrl = home_url( '/' );
			
			if( isset( $_SERVER['HTTP_HOST'] ) )
                $httpHost = $_SERVER['HTTP_HOST'];
			else
			    $httpHost = get_home_url();
			
			$urlExplode = explode( $httpHost, $homeUrl );
			$this->data['application_path'] = $urlExplode[1];
		}
		return $this->data['application_path'];
	}

	/**
	 * Crude check to see if the script is being called via CLI or not.
	 *
	 * @since 1.1.0
	 *
	 * @return boolean True is a remote address from server superglobal could be found, false otherwise.
	 */
	public function is_using_cli() {
		if ( ! isset( $this->data['cli'] ) ) {
			$this->data['cli'] = false;
			if ( ! isset( $_SERVER['REMOTE_ADDR'] ) || ! $_SERVER['REMOTE_ADDR'] )
				$this->data['cli'] = true;
		}

		return $this->data['cli'];
	}

	/**
	 * Check if the SG Cache is enabled.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True is enabled, false otherwise.
	 */
	public function cache_is_enabled() {
		return $this->options_handler->is_enabled( 'enable_cache' );
	}

	/**
	 * Check if the autoflush setting is enabled.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True is autoflush enabled, false otherwise.
	 */
	public function autoflush_enabled() {
		return $this->options_handler->is_enabled( 'autoflush_cache' );
	}

	/**
	 * Check if memcached setting is enable.
	 *
	 * @todo Probably want to check if Memcached is actually responding or not as well.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function memcached_is_enabled() {
		return $this->options_handler->is_enabled( 'enable_memcached' );
	}

	/**
	 * Helper function to check if a key in the $_POST superglobal is both set and a certain value.
	 *
	 * Args here are reversed from usual to better match the action_data_is() method.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $value Post data value
	 * @param  string $key   Post data key
	 *
	 * @return bool True is key exists and has a matching value.
	 */
	public function post_data_is( $value, $key ) {
		return isset( $_POST[ $key ] ) && $value === $_POST[ $key ];
	}

	/**
	 * Helper function to check if the action key in the $_POST superglobal is both set and a certain value.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $value Post data value
	 *
	 * @return bool True is key exists and has a matching value.
	 */
	public function action_data_is( $value ) {
		return $this->post_data_is( $value, 'action' );
	}
}