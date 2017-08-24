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
	 * This is the path to the log file withing the plugin directory
	 * 
	 * @var string
	 */
	private $log_file = 'debug.log';
	
	/**
	 * Max allowed filesize for the log file in MB
	 * 
	 * @var integer
	 */
	private $log_max_filesize_mb = 200;

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

		return parse_url( home_url( '/' ), PHP_URL_PATH );
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
	
	
	/**
	 * This function is used to store log data into debug.log file into the plugin directory.
	 * 
	 * @since 2.3.9
	 * 
	 * @param string $message Message for logging
	 * 
	 * @return bool Trus if writing is successful
	 */
	public function log( $message ) {
	    
	    if ($this->log_file_check()) {
    	    $logMessage = date('Y-m-d H:i:s') . " | {$message}\n";
    	    $file = $this->log_file_path();
    	    return @file_put_contents($file, $logMessage, FILE_APPEND);
	    }
	    
	}
	
	/**
	 * Returns the full path of the log file
	 * 
	 * @since 2.3.9
	 * 
	 * @return string
	 */
	private function log_file_path() {
	    return plugin_dir_path(__FILE__) . $this->log_file;
	}
	
	/**
	 * This function keeps the log file with maximum size 200MB,
	 * and if it is over that size it will backup the file, 
	 * also deleting the last backup.
	 * 
	 * @since 2.3.9
	 */
	private function log_file_check() {
	    
        $file = $this->log_file_path();
        if (@file_exists($file) && @is_readable($file) && @is_writable($file)) {
            
            if (@filesize($file) > $this->log_max_filesize_mb*1000000){
                // The file has exceeded the maximum allowed filesize
                $new_name = $file . '.1';
                @rename($file, $new_name);
            }
            
        }
        
        if (@file_exists($file) && !@is_writable($file)) {
            return false;
        }
        
        return true;
	}
}