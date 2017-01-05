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
        add_action('wp_ajax_sg-cachepress-phpversion-check', array($this, 'phpversion_check'));
        add_action('admin_notices', array($this, 'global_notice_phpversion_changed'));
        add_action('admin_notices', array($this, 'global_notice_phpversion_not_updated'));
        add_action('wp_ajax_sg-cachepress-message-hide', array($this, 'message_hide'));
    }

    public function activate()
    {
        $this->phpversion_check();
    }

    /**
     * This function hides the notice from displaying when it is manually closed
     *
     * @since 2.2.7
     */
    function message_hide()
    {
        $id = $_POST['notice_id'];
        $this->options_handler->disable_option('show_notice_' . $id);

        echo $id;
        wp_die();
    }

    public function global_notice_template($msg, $id)
    {
        if ($this->options_handler->is_enabled('show_notice_' . $id)) {
            $html = '<div id="ajax-' . $id . '" class="updated sg-cachepress-notification-by-id">';
            $html .= '<p>';
            $html .= __('<strong>SG CachePress PHP Version:</strong>'
                    . $msg . ' Click <a href="http://www.siteground.com/tutorials/supercacher/" target="_blank">here</a> for mode details. '
                    . '<a href="javascript:;" id="' . $id . '" class="dismiss-sg-cahepress-notification-by-id">Click here to hide this notice</a>.', 'ajax-notification');
            $html .= '</p>';
            $html .= '<span id="ajax-notification-nonce" class="hidden">' . wp_create_nonce('ajax-notification-nonce') . '</span>';
            $html .= '</div>';
            echo $html;
        }
    }

    public function global_notice_phpversion_changed()
    {
        $this->global_notice_template(' Your PHP version has been changed to <strong>PHP 7.0.13</strong>.', 'notification-1');
    }

    public function global_notice_phpversion_not_updated()
    {
        $this->global_notice_template(' You website doesn\'t run on the recommended by SiteGround PHP version. ', 'notification-2');
    }

    /**
     * Check phpversion from ajax request
     *
     * @since 2.3.11
     */
    public function phpversion_check()
    {

        $this->options_handler->enable_option('show_notice_notification-2');
        $this->options_handler->enable_option('show_notice_notification-1');

        //die((int) $this->options_handler->update_option('prev_phpversion', $_POST['prev_phpversion']));
    }
}
