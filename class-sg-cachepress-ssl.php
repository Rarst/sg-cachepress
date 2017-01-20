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
class SG_CachePress_SSL
{

    /**
     * Holds the options object.
     *
     * @type SG_CachePress_Options
     */
    protected $options_handler;

    public function __construct($options_handler, $environment)
    {
        $this->options_handler = $options_handler;
    }

    /**
     * 
     * Enable SSL in .htaccess
     * 
     * @since 2.3.11
     * 
     * 1 - enabled
     * 2 - semi-enabled
     * 3 - disabled
     * 4 - failed
     */
    public static function toggle()
    {     
        if (!self::is_enabled() && self::enable()) {
            die('1');      
        } else {
            if (self::disable()) {
                die('3');
            }            
        }
    }
    
    /**
     * 
     */

    private static function disable()
    {
        return self::disable_from_wordpress_options() && self::disable_from_htaccess();
    }
    
    /**
     * @return boolean
     */

    private static function enable()
    {
        return self::enable_from_htaccess() && self::enable_from_wordpress_options();
    }
    
    /**
     * 
     * @return type
     */

    public static function is_enabled()
    {
        return self::is_enabled_from_htaccess() && self::is_enabled_from_wordpress_options();
    }
    
    /**
     * 
     * @return boolean
     */
    public static function is_enabled_from_htaccess()
    {
        $filename = self::get_htaccess_filename();
        $htaccessContent = file_get_contents($filename);

        if (preg_match('/HTTPS forced by SG-CachePress/s', $htaccessContent, $m)) {
            return true;
        }

        return false;
    }
    
    /**
     * @since 2.3.11
     * 
     */

    public static function disable_from_htaccess()
    {
        $filename = self::get_htaccess_filename(false);
        $htaccessContent = file_get_contents($filename);

        $htaccessNewContent = preg_replace( "/\#\s+HTTPS\s+forced\s+by\s+SG-CachePress(.+?)\#\s+END\s+HTTPS/ims", '', $htaccessContent );        

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
     * @since 2.3.11
     * @return boolean
     */

    public static function enable_from_htaccess()
    {
        $filename = self::get_htaccess_filename();
        $htaccessContent = file_get_contents($filename);

        $forceSSL = '# HTTPS forced by SG-CachePress' . PHP_EOL;
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
     * @since 2.3.11
     * @param string protocol $from  ('http', 'https')
     * @param string protocol $to    ('http', 'https')
     * @param string $url  
     * @return type
     */

    private static function switchProtocol($from, $to, $url)
    {
        if (preg_match("/^$from\:/s", $url)) {
            return preg_replace("/^$from:/i", "$to:", $url);
        } else {
            return $url;
        }
    }
    
    /**
     * @since 2.3.11
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
     * @since 2.3.11
     * @return boolean
     * 
     */

    public static function enable_from_wordpress_options()
    {
        $siteurl = get_option('siteurl');
        $home = get_option('home');

        return 
            update_option('siteurl', self::switchProtocol('http', 'https', $siteurl)) &&
            update_option('home', self::switchProtocol('http', 'https', $home));
    }
    
    /**
     * @since 2.3.11
     * @return boolean
     * 
     */

    public static function is_enabled_from_wordpress_options()
    {
        $siteurl = get_option('siteurl');
        $home = get_option('home');

        if (
                preg_match('/^https\:/s', $siteurl) &&
                preg_match('/^https\:/s', $home)
        ) {
            return true;
        }

        return false;
    }
    
    /**
     * @since 2.3.11
     * @param type $create
     * @return string | false
     */

    public static function get_htaccess_filename($create = true)
    {
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
}
