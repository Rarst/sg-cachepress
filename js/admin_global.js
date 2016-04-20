jQuery(document).ready(function(){
	if(jQuery('#dismiss-sg-cahepress-notification').length)
		jQuery('#dismiss-sg-cahepress-notification').click(function(){ sg_cachepress_notice_hide(); });
});

function sg_cachepress_notice_hide()
{
	jQuery('.sg-cachepress-notification').slideUp();
	jQuery.post(ajaxurl,{action:'sg-cachepress-cache-test-message-hide'});
}