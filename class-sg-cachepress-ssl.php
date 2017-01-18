<?php
/**
 * SG CachePress
 *
 * @package   SG_CachePress
 * @author    SiteGround 
 * @author    PACETO
 * @link      http://www.siteground.com/
 * @copyright 2014 SiteGround
 * @since 2.3.11
 */
 

/** SG CachePress main plugin class  */

class SG_CachePress_SSL {

	
	/**
	 * Holds the options object.
	 *
	 * @type SG_CachePress_Options
	 */
	protected $options_handler;

	public function __construct( $options_handler, $environment ) {
            $this->options_handler = $options_handler;
	}
        
        public static function enable_ssl() {
            //return false;
        }
        
	
}