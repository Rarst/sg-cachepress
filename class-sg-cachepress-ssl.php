<?php

/**
 * SG CachePress
 *
 * @package   SG_CachePress
 * @author    SiteGround 
 * @author    PACETO
 * @link      http://www.siteground.com/
 * @copyright 2014 SiteGround
 * @since 3.0.0
 */

/** SG CachePress main plugin class  */
class SG_CachePress_SSL
{
    
    public static $http_urls = array();
    public static $is_certificate_enabled = null;
    public static $is_fully_enabled = null;

    /**
     * Holds the options object.
     *
     * @type SG_CachePress_Options
     */

    public function __construct()
    {
    }
    
    public static function is_certificate_enabled() {
        if (self::$is_certificate_enabled !== null) {
            return self::$is_certificate_enabled;
        }
        
	$siteurl = get_option('siteurl').'?sgCacheCheck=022870ae06716782ce17e4f6e7f69cc2';
        $siteurlHTTPS = SG_CachePress_SSL::switchProtocol('http', 'https', $siteurl);

        ini_set('user_agent','SG-Optimizer 3.0.2;');
        $stream = stream_context_create (array("ssl" => array("capture_peer_cert" => true)));                
        $read = @fopen($siteurlHTTPS, "rb", false, $stream);
        $cont = @stream_context_get_params($read);
        $var = ($cont["options"]["ssl"]["peer_certificate"]);
        $result = (!is_null($var)) ? true : false;
        
        self::$is_certificate_enabled = $result;
        return $result;
    }


    /**
     * 
     * Enable SSL in .htaccess
     * 
     * @since 3.0.0    
     * 
     * @return
     * 
     * dies with:
     * 1 - enabled
     * 0 - disabled
     * or error message
     * 
     */
    public static function toggle()
    {	
		sg_cachepress_purge_cache();
                   
        if (self::is_fully_enabled()) {
            self::disable();
            die('0');
        }
        
        if (self::is_partially_enabled()) {           
            self::enable();            
            die('1');
        }
        
        if (!self::is_fully_enabled()) {
            self::enable();
            die('1');
        }
        

        
//        if (self::is_enabled_from_wordpress_options() && !self::is_enabled_from_htaccess()) {
//            //self::enable_from_htaccess();
//            die('1');
//        }
        
    }

    /**
     * 
     */
    private static function disable()
    {
              
        if (self::disable_from_wordpress_options() && self::disable_from_htaccess()) {
          update_option('sg_cachepress_ssl_enabled', 0);
          return true;
        } else {
          return false;
        }

    }

    /**
     * @return boolean
     */
    private static function enable()
    {
                
        if (!self::is_enabled_from_htaccess()) {           
            self::enable_from_htaccess();
        }
        
        if (!self::is_enabled_from_wordpress_options()) {
            self::enable_from_wordpress_options();
        }
        
        update_option('sg_cachepress_ssl_enabled', 1);

        return true;        
    }

    /**
     * 
     * @return type
     */
    public static function is_partially_enabled()
    {
        if (!self::is_certificate_enabled()) {
            return false;
        }
        
        return
                !self::is_fully_enabled() &&
                (self::is_enabled_from_htaccess() || self::is_enabled_from_one_of_the_wordpress_options());
    }

    /**
     * @return boolean
     */
	public static function is_fully_enabled() {
		if ( self::$is_fully_enabled !== null ) {
			return self::$is_fully_enabled;
		}

		if ( ! self::is_certificate_enabled() ) {
			return false;
		}

		if ( is_multisite() && self::is_enabled_from_wordpress_options() ) {
			return true;
		}

		return ( self::is_enabled_from_htaccess() && self::is_enabled_from_wordpress_options() );
	}

    /**
     * 
     * @return boolean
     */
    public static function is_enabled_from_htaccess()
    {                
        $filename = self::get_htaccess_filename();
        
        if ($filename === false) {
        	return false;
        }
        
        $htaccessContent = file_get_contents($filename);

        if (preg_match('/HTTPS forced by SG-Optimizer/s', $htaccessContent, $m)) {
            return true;
        }

        return false;
    }

    /**
     * @since 3.0.0
     * 
     */
    public static function disable_from_htaccess()
    {
	    if ( is_multisite() ) { // MS doesn’t use htaccess, but this is also being used as turn off success check. R.
		    return true;
	    }

        $filename = self::get_htaccess_filename(false);
        if ($filename === false) {
        	return false;
        }
        
        $htaccessContent = file_get_contents($filename);

        $htaccessNewContent = preg_replace("/\#\s+HTTPS\s+forced\s+by\s+SG-Optimizer(.+?)\#\s+END\s+HTTPS/ims", '', $htaccessContent);

        if (substr($htaccessNewContent, 0, 1) === PHP_EOL) {
            $htaccessNewContent = substr($htaccessNewContent, 1);
        }

        $fp = fopen($filename, "w+");
        if (flock($fp, LOCK_EX)) { // do an exclusive lock
            fwrite($fp, $htaccessNewContent);
            flock($fp, LOCK_UN); // release the lock
            return true;
        } else {
            return false;
        }
    }

    /**
     * @since 3.0.0
     * @return boolean
     */
    public static function enable_from_htaccess()
    {
	    if ( is_multisite() ) {
		    return false;
	    }

        $filename = self::get_htaccess_filename();
        
        if ($filename === false) {
        	return false;
        }
        
        $htaccessContent = file_get_contents($filename);

        $forceSSL = '# HTTPS forced by SG-Optimizer' . PHP_EOL;
        $forceSSL .= '<IfModule mod_rewrite.c>' . PHP_EOL;
        $forceSSL .= 'RewriteEngine On' . PHP_EOL;
        $forceSSL .= 'RewriteCond %{HTTPS} off' . PHP_EOL;
        $forceSSL .= 'RewriteRule ^(.*)$ https://%{SERVER_NAME}%{REQUEST_URI} [R=301,L]' . PHP_EOL;
        $forceSSL .= '</IfModule>' . PHP_EOL;
        $forceSSL .= '# END HTTPS';

        if (substr($htaccessContent, 0, 1) !== PHP_EOL) {
            $htaccessNewContent = $forceSSL . PHP_EOL . $htaccessContent;
        } else {
            $htaccessNewContent = $forceSSL . $htaccessContent;
        }

        $fp = fopen($filename, "w+");

        if (flock($fp, LOCK_EX)) { // do an exclusive lock
            fwrite($fp, $htaccessNewContent);
            flock($fp, LOCK_UN); // release the lock
            return true;
        } else {
            return false;
        }
    }

    /**
     * @since 3.0.0
     * @param string protocol $from  ('http', 'https')
     * @param string protocol $to    ('http', 'https')
     * @param string $url  
     * @return type
     */
    public static function switchProtocol($from, $to, $url)
    {
        if (preg_match("/^$from\:/s", $url)) {
            return preg_replace("/^$from:/i", "$to:", $url);
        } else {
            return $url;
        }
    }

    /**
     * @since 3.0.0
     * @return boolean
     */
    public static function disable_from_wordpress_options()
    {
        $siteurl = get_option('siteurl');
        $home = get_option('home');

        return
                update_option('siteurl', self::switchProtocol('https', 'http', $siteurl)) &&
                update_option('home', self::switchProtocol('https', 'http', $home));
    }

    /**
     * 
     * @since 3.0.0
     * @return boolean
     * 
     */
    public static function enable_from_wordpress_options()
    {
        $siteurl = get_option('siteurl');
        $home = get_option('home');
        
        update_option('siteurl', self::switchProtocol('http', 'https', $siteurl));
        update_option('home', self::switchProtocol('http', 'https', $home));
        
        return true;
    }

    /**
     * @since 3.0.0
     * @return boolean
     * 
     */
    public static function is_enabled_from_one_of_the_wordpress_options()
    {
        if (preg_match('/^https\:/s', get_option('siteurl')) || preg_match('/^https\:/s', get_option('home'))) {
            return true;
        }

        return false;
    }

    /**
     * @since 3.0.0
     * @return boolean
     * 
     */
    public static function is_enabled_from_wordpress_options()
    {
        if (preg_match('/^https\:/s', get_option('siteurl')) && preg_match('/^https\:/s', get_option('home'))) {
            return true;
        }

        return false;
    }

    /**
     * @since 3.0.0
     * @param boolean $create
     * @return string | false
     */
    public static function get_htaccess_filename($create = true)
    {
	    if ( is_multisite() ) {
		    return false;
	    }

        $basedir = dirname(dirname(dirname(__DIR__)));
        $filename = $basedir . '/.htaccess';

        if (!is_file($filename) && $create) {
            touch($filename);
        }

        if (!is_writable($filename)) {
            return false;
        }

        return $filename;
    }

    /**
     * add action hooks at the start and at the end of the WP process.
     * @since  3.0.0
     * @access public
     */
    public static function fix_mixed_content()
    {
        self::build_url_list();

        if (is_admin()) {
            add_action("admin_init", array("SG_CachePress_SSL", "start_buffer"));
        } else {
            add_action("init", array("SG_CachePress_SSL", "start_buffer"));
        }
        add_action("shutdown", array("SG_CachePress_SSL", "end_buffer"));
    }

    /**
     * Apply the mixed content fixer.
     * @since  3.0.0
     * @access public
     */
    public static function filter_buffer($buffer)
    {
        global $rsssl_front_end;
        $buffer = self::replace_insecure_links($buffer);
        return $buffer;
    }

    public static function start_buffer()
    {
        ob_start(array("SG_CachePress_SSL", "filter_buffer"));
    }

    public static function end_buffer()
    {
        if (ob_get_length())
            ob_end_flush();
    }

    /**
     * Creates an array of insecure links that should be https and an array of secure links to replace with
     * @since  3.0.0
     * @access public
     */
    public static function build_url_list()
    {
        $home_no_www = str_replace("://www.", "://", get_option('home'));
        $home_yes_www = str_replace("://", "://www.", $home_no_www);

        self::$http_urls = array(
            str_replace("https://", "http://", $home_yes_www),
            str_replace("https://", "http://", $home_no_www),
            "src='http://",
            'src="http://',
        );
    }

    /**
     * Just before the page is sent to the visitor's browser, all homeurl links are replaced with https.
     * @since  3.0.0
     * @access public
     */
    public static function replace_insecure_links($str)
    {
        $search_array = apply_filters('rlrsssl_replace_url_args', self::$http_urls);
        $ssl_array = str_replace("http://", "https://", $search_array);
        //now replace these links
        $str = str_replace($search_array, $ssl_array, $str);

        //replace all http links except hyperlinks
        //all tags with src attr are already fixed by str_replace
        $pattern = array(
            '/url\([\'"]?\K(http:\/\/)(?=[^)]+)/i',
            '/<link .*?href=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
            '/<meta property="og:image" .*?content=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
            '/<form [^>]*?action=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
                /* Don't use these, these links are taken care of by the src replace */
                //'/<(?:img|iframe) .*?src=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
                //'/<script [^>]*?src=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
        );
        $str = preg_replace($pattern, 'https://', $str);
        return apply_filters("rsssl_fixer_output", $str);
    }
}