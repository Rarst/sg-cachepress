jQuery( function ($) {
	'use strict';
	// Bind behaviour to event
	jQuery('#sg-cachepress-purge').on('click.sg-cachepress', sg_cachepress_purge);
	jQuery('#sg-cachepress-dynamic-cache-toggle').on('click.sg-cachepress', function(event){event.preventDefault();sg_cachepress_toggle_option('dynamic-cache');});
	jQuery('#sg-cachepress-memcached-toggle').on('click.sg-cachepress', function(event){event.preventDefault();sg_cachepress_toggle_option('memcached');});
	jQuery('#sg-cachepress-autoflush-cache-toggle').on('click.sg-cachepress', function(event){event.preventDefault();sg_cachepress_toggle_option('autoflush-cache');});
	jQuery('#sg-cachepress-blacklist').on('click.sg-cachepress', sg_cachepress_save_blacklist);
    jQuery('#sg-cachepress-phpversion-check').on('click.sg-cachepress', sg_cachepress_phpversion_check);       
});
var sg_cachepress_toggle_in_progress = false;
var sg_cachepress_toggle_ssl_in_progress = false;


/**
 * 0 - disabled
 * 1 - enabled
 * or error message
 */
jQuery('#sg-cachepress-ssl-toggle').on('click.sg-cachepress', function(event){    
    event.preventDefault();
    if (sg_cachepress_toggle_ssl_in_progress) {
        return;
    }   	
    sg_cachepress_toggle_ssl_in_progress = true;
    
    var $ajaxArgs = {
            action:  'sg-cachepress-ssl-toggle',
            objects: 'all'
    };

    jQuery.post(ajaxurl, $ajaxArgs).done(function(data){
        sg_cachepress_toggle_ssl_in_progress = false;
        jQuery('#sg-cachepress-ssl-text').show();
        jQuery('#sg-cachepress-ssl-error').hide();
        
        if (data === '0') {
            //HTTP
            jQuery('#sg-cachepress-ssl-toggle').removeClass('toggleon').addClass('toggleoff', 1000);
            location.href = 'http:' + window.location.href.substring(window.location.protocol.length);
        } else if (data === '1') {
            //HTTPS
            jQuery('#sg-cachepress-ssl-toggle').removeClass('toggleoff').addClass('toggleon', 1000);
            location.href = 'https:' + window.location.href.substring(window.location.protocol.length);
        } else {
            jQuery('#sg-cachepress-ssl-text').hide();
            jQuery('#sg-cachepress-ssl-error').html(sgCachePressL10n.ssl_toggle_failed).show();	
        }        
    });
});
 
/**
 * Update a setting parameter
 *
 * @since 1.1.0
 *
 * @function
 *
 * @param {jQuery.event} event
 */
function sg_cachepress_toggle_option(optionName) {
    if (sg_cachepress_toggle_in_progress) {
            return;
    }

    sg_cachepress_toggle_in_progress = true;
    var $ajaxArgs;
    $ajaxArgs = {
            action:  'sg-cachepress-parameter-update',
            parameterName: optionName,
            objects: 'all'
    };
    jQuery.post(ajaxurl, $ajaxArgs).done(function(data){
        sg_cachepress_toggle_in_progress = false;
        jQuery('#sg-cachepress-'+optionName+'-text').show();
        jQuery('#sg-cachepress-'+optionName+'-error').hide();
        if (data == 1) 
        {
            jQuery('#sg-cachepress-'+optionName+'-toggle').removeClass('toggleoff').addClass('toggleon', 1000);
            return;
        }
        if (data == 0)
        {
            jQuery('#sg-cachepress-'+optionName+'-toggle').removeClass('toggleon').addClass('toggleoff', 1000);
            return;
        }

        jQuery('#sg-cachepress-'+optionName+'-text').hide();
        jQuery('#sg-cachepress-'+optionName+'-error').html(data).show();		
    });
}

/**
 * Update the blacklist
 *
 * @since 1.1.0
 *
 * @function
 *
 * @param {jQuery.event} event
 */
function sg_cachepress_save_blacklist(event) {
	event.preventDefault();
	var $ajaxArgs;
	$ajaxArgs = {
		action:  'sg-cachepress-blacklist-update',
		blacklist: jQuery('#sg-cachepress-blacklist-textarea').val(),
		objects: 'all'
	};
	jQuery(event.target).attr('disabled','disabled').attr('value', sgCachePressL10n.updating);
	jQuery('#sg-cachepress-spinner-blacklist').show();
	jQuery.post(ajaxurl, $ajaxArgs).done(function(){
		jQuery('#sg-cachepress-spinner-blacklist').hide();
		jQuery('#sg-cachepress-blacklist').removeAttr('disabled').attr('value', sgCachePressL10n.updated);
		});
}

/**
 * Find optimal PHP version
 *
 * @since 2.3.11
 *
 * @function
 *
 * @param {jQuery.event} event
 */
function sg_cachepress_phpversion_check(event) {
	event.preventDefault();
	var $ajaxArgs;
	$ajaxArgs = {
		action:  'sg-cachepress-phpversion-check',
		objects: 'all'
	};
	jQuery(event.target).attr('disabled','disabled').attr('value', sgCachePressL10n.phpversion_checking);   
	jQuery.post(ajaxurl, $ajaxArgs).done(function(){
            jQuery('#sg-cachepress-phpversion-check').removeAttr('disabled').attr('value', sgCachePressL10n.phpversion_check);
	});
}


/**
 * Start the purge procedure from a button click.
 *
 * @since 1.1.0
 *
 * @function
 *
 * @param {jQuery.event} event
 */
function sg_cachepress_purge(event) {
	jQuery('#sg-cachepress-purgesuccess').hide();
	jQuery('#sg-cachepress-purgefailure').hide();
	event.preventDefault();
	'use strict';
	var $ajaxArgs;
	$ajaxArgs = {
		action:  'sg-cachepress-purge',
		objects: 'all'
	};
	jQuery(event.target).attr('disabled','disabled').attr('value', sgCachePressL10n.purging);
	jQuery('#sg-cachepress-spinner').css({'visibility': 'visible'});
	jQuery.post(ajaxurl, $ajaxArgs).done(sg_cachepress_purged);
}

/**
 * Tidy-up the UI after purge has successfully completed.
 *
 * @since 1.1.0
 *
 * @function
 *
 * @param {string} data
 */
function sg_cachepress_purged(data) {
	'use strict';
	jQuery('#sg-cachepress-purge').removeAttr('disabled').attr('value', sgCachePressL10n.purge);
	jQuery('#sg-cachepress-spinner').css({'visibility':'hidden'});
	if ('1' == data){
		jQuery('#sg-cachepress-purgesuccess').fadeIn();
	} else {
		jQuery('#sg-cachepress-purgefailure').fadeIn();
	}
}


jQuery("#cachetest").submit(function( event ) { sg_cachepress_test_submit();  event.preventDefault(); });

var cachepress_test_counter = 0;
function sg_cachepress_test_submit( )
{
	jQuery('.status_test').slideUp();
	jQuery('#sg-cachepress-test').prop('disabled',true).attr('value',sgCachePressL10n.testing);
	jQuery('#testurl').prop('disabled',true);
	var postUrl = jQuery("#testurl").val();
	
	var ajaxUrl = sgCachePressL10n.ajax_url;
	jQuery.post(ajaxUrl,{action:'sg-cachepress-cache-test',url:postUrl},function(result){ sg_cachepress_test_result(result); });
}


function sg_cachepress_test_result(result)
{
	cachepress_test_counter = cachepress_test_counter + 1;
	
	if(result == 1)
		sg_cachepress_test_result_display_output( sgCachePressL10n.cached, 'cached' );

	if(result == 0 && cachepress_test_counter == 1)
		setTimeout("sg_cachepress_test_submit();",2000);
	else if(result == 0)
		sg_cachepress_test_result_display_output( sgCachePressL10n.notcached, 'notcached' );
	else if(result == 2)
		sg_cachepress_test_result_display_output( sgCachePressL10n.noheaders, 'notcached' );

	if(result == 1 || cachepress_test_counter == 2)
		cachepress_test_counter = 0;
}

function sg_cachepress_test_result_display_output( text, classText )
{
	jQuery('#sg-cachepress-test').prop('disabled',false).attr('value',sgCachePressL10n.testurl);
	jQuery('#testurl').prop('disabled',false);
	
	jQuery('#status_test_value').html('<span class="'+classText+'">'+text+'</span>');
	jQuery('.status_test').slideDown();
}
