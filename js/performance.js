jQuery( document ).ready( function ( $ ) {

	jQuery('#sg-cachepress-gzip-toggle').on('click.sg-cachepress', function(event){
		event.preventDefault();
		sg_cachepress_toggle_htaccess('gzip');
	});

	jQuery('#sg-cachepress-browser-cache-toggle').on('click.sg-cachepress', function(event){
		event.preventDefault();
		sg_cachepress_toggle_htaccess('browser-cache');
	});

	$( 'input:radio[name="scan-type"]' ).change(
		function () {
			if ( this.checked && this.value === 'default' ) {
				$( '#advanced-options' ).hide();
				$( 'input:radio[name="login"]' ).attr( 'disabled', 'disabled' );
				$( 'textarea[name="urls"]' ).attr( 'disabled', 'disabled' );
			} else {
				$( 'input:radio[name="login"]' ).removeAttr( 'disabled' );
				$( 'textarea[name="urls"]' ).removeAttr( 'disabled' );
				$( '#advanced-options' ).show();
			}
		} );

	if ( typeof sgOptimizerLoadingTimes !== 'undefined' ) {

		var ctx = document.getElementById( "loadingTimes" ).getContext( '2d' );
		var myChart = new Chart( ctx, {
			type   : 'pie',
			data   : {
				labels  : sgOptimizerLoadingTimes.labels,
				datasets: [
					{
						data             : sgOptimizerLoadingTimes.data,
						'backgroundColor': ['#3D4A69', '#9EB4ED', '#6E83B0', '#9EB4ED', '#d3d3d3']
					}
				]
			},
			options: {
				'maintainAspectRatio'    : false,
				'animation.animateRotate': false,
				'legend'                 : {
					'display' : true,
					'position': 'right'
				}
			}
		} );
	}

	var sg_cachepress_toggle_in_progress = false;

	function sg_cachepress_toggle_htaccess(optionName) {
	    if (sg_cachepress_toggle_in_progress) {
	            return;
	    }

	    sg_cachepress_toggle_in_progress = true;
	    var $ajaxArgs;
	    $ajaxArgs = {
	            action:  'sg-cachepress-htaccess-update',
	            parameterName: optionName,
	            objects: 'all'
	    };
	    jQuery.post(ajaxurl, $ajaxArgs).done(function(data){
	        sg_cachepress_toggle_in_progress = false;
//	        jQuery('#sg-cachepress-'+optionName+'-text').show();
//	        jQuery('#sg-cachepress-'+optionName+'-error').hide();
			console.log(data);
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

//	        jQuery('#sg-cachepress-'+optionName+'-text').hide();
//	        jQuery('#sg-cachepress-'+optionName+'-error').html(data).show();
	    });
	}
} );