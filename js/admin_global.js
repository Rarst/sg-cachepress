jQuery(document).ready(function(){
	if(jQuery('#dismiss-sg-cahepress-notification').length)
		jQuery('#dismiss-sg-cahepress-notification').click(function(){ sg_cachepress_notice_hide(); });
            
        if(jQuery('.dismiss-sg-cahepress-notification-by-id').length) {
            jQuery('.dismiss-sg-cahepress-notification-by-id').click(
                    function(event){ 
                        sg_cachepress_notice_hide_by_id(event.target.id); 
                    }
            );
        }
            
});

function sg_cachepress_notice_hide()
{
    jQuery('.sg-cachepress-notification').slideUp();
    jQuery.post(ajaxurl,{action:'sg-cachepress-cache-test-message-hide', nonce: jQuery('#dismiss-sg-cahepress-notification').attr('nonce')});
}

/**
 * 
 * @param {string} id  could be 'notification-1' , 'notification-2', ... etc
 * @returns {undefined}
 */
function sg_cachepress_notice_hide_by_id(id)
{
    jQuery('#ajax-' + id).slideUp();
    $ajaxArgs = {
        action:  'sg-cachepress-message-hide',
        notice_id: id,
        nonce: jQuery('#ajax-' + id).find('#ajax-notification-nonce').html()
    };

    jQuery.post(ajaxurl, $ajaxArgs);
}