<?php
/**
 * @package   SG_CachePress
 * @author    SiteGround
 * @author    George Penkov
 * @author    Gary Jones <gamajo@gamajo.com>
 * @link      http://www.siteground.com/
 * @copyright 2014 SiteGround
 */

/** SG CachePress main plugin class */

class SG_CachePress_Supercacher {

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
	 * Assign dependencies.
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
		if ( $this->environment->is_using_cli() )
			return;

		$this->assign_hooks_for_autoflush();
	}
	
	/**
	 * Flush Memcache or Memcached
	 * 
	 * @return boolean
	 */
	public static function flush_memcache()
	{
	    return wp_cache_flush();
	}

    /**
     * Call the cache server to purge the cache.
     *
     * Since this method is also called via Ajax (where $this doesn't exist --it does not exist because the method is invoked as static--), we replace the usual $this with the global
     * variable that contains an instance of this class. Not ideal but apparently works.
     *
     *
     * @since Unknown
     *
     * @return null
     */
	public static function purge_cache($dontDie = false) {
		global $sg_cachepress_supercacher;
		if ( $sg_cachepress_supercacher->environment->is_using_cli() )
			return;

		if ( self::$_flushed )
			return;

		$purge_request = $sg_cachepress_supercacher->environment->get_application_path() . '(.*)';
        
		// Check if caching server is varnish or nginx.
		$sgcache_ip = '/etc/sgcache_ip';
		
		$hostname = parse_url(get_home_url(), PHP_URL_HOST);
		
		if ( isset($_SERVER['SERVER_ADDR']) ) {
			$hostname = $_SERVER['SERVER_ADDR'];
		}
			
		$purge_method = "PURGE";

		if (file_exists($sgcache_ip) && !self::is_nginx_server()) {
	        $hostname = trim( file_get_contents( $sgcache_ip, true ) );
	        $purge_method = "BAN";
		}

		$cache_server_socket = fsockopen( $hostname, 80, $errno, $errstr, 2 );
		if( ! $cache_server_socket ) {
			return;
		}
		
		$realhost= $hostname;
		
		if ( isset($_SERVER['HTTP_REFERER']) ) {
			$realhost= parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST );   
		}
		
		
		$request = "$purge_method {$purge_request} HTTP/1.0\r\n";
      	$request .= "Host: {$realhost}\r\n";
      	$request .= "Connection: Close\r\n\r\n";
      	
      	fwrite( $cache_server_socket, $request );
      	$response = fgets( $cache_server_socket );

      	fclose( $cache_server_socket );
      	
      	self::flush_memcache();

      	// Only die (or notify) if doing an Ajax request
		if ( $sg_cachepress_supercacher->environment->action_data_is( 'sg-cachepress-purge' ) ) {
			if ( preg_match( '/200/', $response ) )
			{
				if ($dontDie)
					return true;

				wp_die( 1 );
			}
			else
			{
				if ($dontDie)
					return false;

				wp_die( 0 );
			}
		}
	}
	
	/**
	 * Returns if the server is using Nginx
	 * @return boolean
	 */
	private static function is_nginx_server()
	{
	    return SG_CachePress_Admin::return_and_cache_server_type();
	}

	/**
	 * Purges the cache and redirects to referrer (admin bar button)
	 *
	 * @since 2.2.1
	 */
	public static function purge_cache_admin_bar()
	{
		if ( isset( $_GET['_wpnonce'] ) )
		{
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'sg-cachepress-purge' ) )
			{
				wp_nonce_ays( '' );
			}

			self::purge_cache(true);
			wp_redirect( $_SERVER["HTTP_REFERER"] );
		}
	}
	/**
	 * Print notification in the admin section, or via AJAX
	 *
	 * @since Unknown
	 *
	 * @param string|bool $message Message to be displayed if purge is successful. If this param is false no output would be done
	 * @return null
	 */
	public static function notify( $message ) {
		add_action( 'muplugins_loaded', SG_CachePress_Memcache::notice( $message ) );
	}

	/**
	 * Add autoflush-related hooks.
	 *
	 * The check for autoflush disabled is down here, instead of within each callback - autoflush being enabled or not
	 * is unlikely to change between when plugin_loaded hook, and each of the listed hooks.
	 *
	 * @since Unknown
	 *
	 * @return null Return early if cache is not enabled, or autoflush is disabled.
	 */
	public function assign_hooks_for_autoflush() {

		add_action( 'save_post', array( $this,'hook_add_post' ) );
		add_action( 'edit_post', array( $this,'hook_add_post' ) );
		add_action( 'publish_phone', array( $this,'hook_add_post' ) );
		add_action( 'publish_future_post', array( $this,'hook_add_post' ) );
		add_action( 'xmlrpc_publish_post', array( $this,'hook_add_post' ) );
		add_action( 'before_delete_post', array( $this,'hook_delete_post' ) );
		add_action( 'trash_post', array( $this,'hook_delete_post' ) );
		add_action( 'add_category', array( $this,'hook_add_category' ) );
		add_action( 'edit_category', array( $this,'hook_edit_category' ) );
		add_action( 'delete_category', array( $this,'hook_delete_category' ) );
		add_action( 'add_link', array( $this,'hook_add_link' ) );
		add_action( 'edit_link', array( $this,'hook_edit_link' ) );
		add_action( 'delete_link', array( $this,'hook_delete_link' ) );
		add_action( 'comment_post', array( $this,'hook_add_comment' ),10,2 );
		add_action( 'comment_unapproved_to_approved', array( $this,'hook_approve_unapprove_comment' ) );
		add_action( 'comment_approved_to_unapproved', array( $this,'hook_approve_unapprove_comment' ) );
		add_action( 'delete_comment', array( $this,'hook_delete_comment' ) );
		add_action( 'trash_comment', array( $this,'hook_delete_comment' ) );
		add_action( 'switch_theme', array( $this,'hook_switch_theme' ) );
		add_action( 'customize_save', array( $this,'hook_switch_theme' ) );
		add_action( 'automatic_updates_complete', array( $this,'hook_atomatic_update' ) );
		add_action( 'future_to_publish', array( $this,'scheduled_goes_live' ) );
		add_action( '_core_updated_successfully', array( $this,'core_update_hook' ) );		

		// @todo Move the rest of this to a new method - and document what events it is capturing!

		if ( ! empty( $_POST ) ) {

			if (isset($_POST['save-header-options']) || isset($_POST['removeheader'])
					|| isset($_POST['skip-cropping']) || (isset($_POST['submit']) && $_POST['submit'] == 'Crop and Publish')
					|| isset($_POST['remove-background']) || (isset($_POST['submit']) && $_POST['submit'] == 'Upload')
					|| isset($_POST['save-background-options']))
				self::purge_cache();

			if ( isset( $_POST['action'] ) ) {
				if ( in_array( $_POST['action'], array( 'widgets-order','save-widget','delete-selected' ) ) )
					self::purge_cache();

				if ( isset( $_POST['submit'] ) && 'update' === $_POST['action'] ) {
					if ( in_array( $_POST['submit'], array( 'Update File', 'Save Changes' ) ) )
						self::purge_cache();
				}

			}

			// Settings -> Permalinks
			if ( isset( $_POST['submit'] ) && 'Save Change' === $_POST['submit'] && isset( $_POST['_wp_http_referer'] ) ) {
				$ref = explode( '/', $_POST['_wp_http_referer'] );
				$ref = array_pop( $ref );

				if ( 'options-permalink.php' === $ref ) {
					if ( 'update' === $_POST['action'] && 'Save Changes' === $_POST['submit'] && 'permalinks' === $_POST['option_page'] )
						self::purge_cache();
				}
			}

			if( isset( $_POST['save_menu'] ) ) {
				// Add Menu
				if( in_array( $_POST['save_menu'], array( 'Create Menu', 'Save Menu' ) ) )
					self::purge_cache();

			}
		}

		if ( ! empty( $_GET ) && isset( $_GET['action'] ) ) {
			if ( isset( $_GET['menu'] ) && 'delete' === $_GET['action'] )
				self::purge_cache();
			if ( isset( $_GET['plugin'] ) && 'activate' === $_GET['action'] )
				self::purge_cache();
		}
	}

	/**
	 * Purge cache when a post is potentially made live.
	 *
	 * @since Unknown
	 *
	 * @param  int $post_id
	 */
	public function hook_add_post( $post_id ) {
		if ( $this->environment->post_data_is( 'Publish', 'publish' ) || $this->environment->action_data_is( 'editpost' ) || $this->environment->action_data_is( 'inline-save' ) )
			self::purge_cache();
	}

	/**
	 * Purge cache when a post is deleted.
	 *
	 * @since Unknown
	 *
	 * @param  int $post_id
	 */
	public function hook_delete_post( $post_id ) {
		if( isset( $_GET['action'] ) && ( 'delete' === $_GET['action'] || 'trash' === $_GET['action'] ) )
			self::purge_cache();
	}

	/**
	 * Purge cache when a category is added.
	 *
	 * @since Unknown
	 *
	 * @param  int $cat_id
	 */
	public function hook_add_category( $cat_id ) {
		if ( $this->environment->action_data_is( 'add-tag' ) )
			self::purge_cache();
	}

	/**
	 * Purge cache when a category is edited.
	 *
	 * @since Unknown
	 *
	 * @param  int $cat_id
	 */
	public function hook_edit_category( $cat_id ) {
		if ( $this->environment->action_data_is( 'editedtag' ) )
			self::purge_cache();
	}

	/**
	 * Purge cache when a category is deleted.
	 *
	 * @since Unknown
	 *
	 * @param  int $cat_id
	 */
	public function hook_delete_category( $cat_id ) {
		if ( $this->environment->action_data_is( 'delete-tag' ) )
			self::purge_cache();
	}

	/**
	 * Purge cache when a link is added.
	 *
	 * @since Unknown
	 *
	 * @param  int $link_id
	 */
	public function hook_add_link( $link_id ) {
		if ( $this->environment->action_data_is( 'add' ) && $this->environment->post_data_is( 'Add Link', 'save' ) )
			self::purge_cache();
	}

	/**
	 * Purge cache when a link is edited.
	 *
	 * @since Unknown
	 *
	 * @param  int $link_id
	 */
	public function hook_edit_link( $link_id ) {
		if ( $this->environment->action_data_is( 'editedtag' ) && $this->environment->post_data_is( 'Update', 'submit' ) )
			self::purge_cache();
	}

	/**
	 * Purge cache when a link is deleted.
	 *
	 * @since Unknown
	 *
	 * @param  int $link_id
	 */
	public function hook_delete_link( $link_id ) {
		if ( $this->environment->action_data_is( 'delete-tag' ) && $this->environment->post_data_is( 'link_category', 'taxonomy' ) )
			self::purge_cache();
	}

	/**
	 * Purge cache when a comment is added.
	 *
	 * @since Unknown
	 *
	 * @param  int    $comment_id
	 * @param  string $status
	 */
	public function hook_add_comment( $comment_id, $status = null ) {
		if ( isset( $_POST['comment_post_ID'] ) ) {
			$comment = get_comment( $comment_id );

			//  Purge post page
			if ( $comment )
				self::purge_cache();
		}
	}

	/**
	 * Purge cache when a comment is approved or unapproved.
	 *
	 * @since Unknown
	 *
	 * @param  int    $comment_id
	 * @param  string $status
	 */
	public function hook_approve_unapprove_comment( $comment_id, $status = null ) {
		if ( isset( $_POST['id'] ) ) {
			$comment = get_comment( $comment_id );

			//  Purge post page
			if( $comment )
				self::purge_cache();
		}
	}

	/**
	 * Purge cache when a comment is deleted.
	 *
	 * @since Unknown
	 *
	 * @param  int    $comment_id
	 */
	public function hook_delete_comment( $comment_id ) {
		$comment_actions = array( 'dim-comment','delete-comment' );

		if ( ( isset( $_POST['action'] ) && isset( $_POST['id'] ) && in_array( $_POST['action'], $comment_actions ) )
			|| ( isset( $_GET['action'] ) && isset( $_GET['c'] ) && 'trashcomment' === $_GET['action'] ) ) {
			$comment = get_comment( $_POST['id'] );
			if ( $comment )
				self::purge_cache();
		}
	}

	/**
	 * Purge cache when the theme is switched.
	 *
	 * @since Unknown
	 */
	public function hook_switch_theme() {
		self::purge_cache();
	}

	/**
	 * Purge cache when the Automatic Updates are completd.
	 *
	 * @since 3.8.1
	 */
	public function hook_atomatic_update() {
		self::purge_cache();
	}

	/**
	 * Purge cache when Scheduled post becomes Published
	 *
	 * @since 3.8.1
	 */
	public function scheduled_goes_live() {
		self::purge_cache();
	}
	
	/**
	 * Purge cache when after successful WordPress core update
	 *
	 * @since 3.8.1
	 */
	public function core_update_hook() {
	    self::purge_cache();
	}
	
	/**
	 * Checks the header returned by calling the home url and determine if the server is nginx
	 */
	public static function return_check_is_nginx()
	{
	    $url = get_home_url();
	    $headers = self::request_data( $url );
	    
	    if( isset($headers['server']) && !empty($headers['server']) )
	    {
	        if( preg_match('#(nginx)#', $headers['server'] ) )
	            return true;
	    }
	    
	    return false;
	}
	
	/**
	 * Returns if the cache header is on
	 * @param string $url
	 * @return bool
	 */
	public static function return_cache_result( $url )
	{
	    $headers = self::request_data( $url );
	    //Status 0: Cache is not working, 1: Cache is working, 2: Unable to connect
	    if (!$headers || (!is_array($headers) && !($headers instanceof ArrayAccess))) {
	        $status = 2;
	    } else {
	        if( isset($headers['x-proxy-cache']) && mb_strtoupper( $headers['x-proxy-cache'] ) == 'HIT' )
	            $status = 1;
	        else if( isset($headers['x-cache']) && mb_strtoupper( $headers['x-cache'] ) == 'SGCACHE-HIT' )
	            $status = 1;
	        else
	            $status = 0;
	    }
        
	    return $status;
	}
	
	/**
	 * Defines the function used to initial the cURL library.
	 *
	 * @param  string  $url        To URL to which the request is being made
	 * @return string  $response   The response, if available; otherwise, null
	 */
	private static function curl( $url )
	{
	    $curl = curl_init( $url );
	
	    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
	    curl_setopt( $curl, CURLOPT_HEADER, true );
	    curl_setopt( $curl, CURLOPT_NOBODY, true);
	    curl_setopt( $curl, CURLOPT_TIMEOUT, 10 );
	
	    $response = curl_exec( $curl );
	    if( 0 !== curl_errno( $curl ) || 200 !== curl_getinfo( $curl, CURLINFO_HTTP_CODE ) ) {
	        $response = null;
	    } // end if
	    curl_close( $curl );
	
	    return $response;
	}
	
	/**
	 * Retrieves the headers from the specified URL using one of PHPs requests methods
	 * @param	$url | URL to retrieve headers from
	 * @return	array $headers | Returns headers as array
	 */
	private static function request_data( $url )
	{
	    $response = null;
	    $returnHeaders = array();
	
	    //First, we try wp_remote_get
	    $response = wp_remote_get( $url );
	    if( !is_wp_error( $response ) )
        {
            $returnHeaders = wp_remote_retrieve_headers( $response );
            $returnHeaders['method'] = 'wp_remote_get';
        }
        else 
        {
	        //If that doesn't work, then we'll try file_get_contents
	        $response = file_get_contents( $url );
	        if( $response && isset($http_response_header) ) {
                $returnHeaders = self::parse_headers($http_response_header);
                $returnHeaders['method'] = 'file_get_contents';
	        }
	        else
	        {
	            //And if that doesn't work, then we'll try curl
	            $response = self::curl( $url );
	            if( $response )
	            {
	                $returnHeaders = self::parse_headers( $response );
	                $returnHeaders['method_requested'] = 'curl';
	            }
	        }
	    }
	
	    return $returnHeaders;
	}
	
	/**
	 * Parses the header to array from string on array input
	 * @param array|string $headers
	 * @return array $head 
	 */
	private static function parse_headers( $headers )
	{
	    $head = array();
	    
	    if( !is_array($headers) )
	        $headers = explode("\n", $headers); 
	    
	    if( is_array($headers) && count($headers) > 0 )
	    {
    	    foreach( $headers as $v )
    	    {
    	        $t = explode( ':', $v, 2 );
    	        if( isset( $t[1] ) )
    	            $head[ mb_strtolower( trim($t[0]) ) ] = trim( $t[1] );
    	    }
	    }
	    
	    return $head;
	}

	
}