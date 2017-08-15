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
} );