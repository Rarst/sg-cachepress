jQuery( document ).ready( function ( $ ) {

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
				'responsive'             : false,
				'animation.animateRotate': false,
				'legend'                 : {
					'display': false
				}
			}
		} );
	}
} );