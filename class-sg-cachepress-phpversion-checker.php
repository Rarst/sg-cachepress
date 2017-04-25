<?php
/**
 * SG CachePress
 *
 * @package   SG_CachePress
 * @author    SiteGround
 * @author    PACETO
 * @link      http://www.siteground.com/
 * @copyright 2017 SiteGround
 */
class SG_CachePress_PHPVersionChecker
{

    /**
     * Holds the options object
     *
     * @since 2.3.11
     *
     * @type SG_CachePress_Options
     */
    protected $options_handler;

    /**
     * Assign dependencies.
     *
     * @since 2.3.11
     *
     * @param SG_CachePress_Options $options_handler
     */
    public function __construct($options_handler)
    {
        $this->options_handler = $options_handler;
    }

    /**
     * Initialize the administration functions.
     *
     * @since 2.3.11
     */
    public function run()
    {  
        // Register the SG_WPEngine_PHPCompat instance
        $phpcompat = SG_WPEngine_PHPCompat::instance();                
        add_action('admin_notices', array($this, 'global_notice_phpversion_not_updated'));
        add_action('wp_ajax_sg-cachepress-message-hide', array($this, 'message_hide'));
    }
    
    public function activate()
    {      
        if (SG_CachePress_Admin::$enable_php_version_checker &&  !SG_WPEngine_PHPCompat::isUpToDate()) {
            // @Todo to enable this message also onPluginUpdate
            $this->options_handler->enable_option('show_notice_notification-1');
        }                                  
    }

    /**
     * This function hides the notice from displaying when it is manually closed
     *
     * @since 2.2.7
     * @return void
     */
    function message_hide()
    {
        $id = $_POST['notice_id'];
        $this->options_handler->disable_option('show_notice_' . $id);

        echo $id;
        wp_die();
    }

    /**
     * Template for global messages
     *
     * @since 2.2.7
     * @return void
     */
    public function global_notice_template($msg, $id)
    {
        if ($this->options_handler->is_enabled('show_notice_' . $id)) {
            $html = '<div id="ajax-' . $id . '" class="updated sg-cachepress-notification-by-id">';
            $html .= '<p>';
            $html .= __('<strong>SG Optimizer:</strong>'
                    . $msg . ' Click <a href="./admin.php?page=php-check" target="_self">here</a> for more details. '
                    . '<a href="javascript:;" id="' . $id . '" class="dismiss-sg-cahepress-notification-by-id">Click here to hide this notice</a>.', 'ajax-notification');
            $html .= '</p>';
            $html .= '<span id="ajax-notification-nonce" class="hidden">' . wp_create_nonce('ajax-notification-nonce') . '</span>';
            $html .= '</div>';
            echo $html;
        }
    }

    /**
     * This notice is printed on plugin activation or update
     *
     * @since 2.2.7
     * @return void
     */
    public function global_notice_phpversion_not_updated()
    {
        $this->global_notice_template(__(' You website doesn\'t run on the recommended by SiteGround PHP version. ',
                'ajax-notification'), 'notification-1');
    }                          
}
