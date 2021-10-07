/**
 * Membership Account Page JS ( [subscription_details] shortcode )
 *
 * @package   Restrict Content Pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* global rcpAccountVars */

jQuery( document ).ready( function ( $ ) {

	/**
	 * Show alert when disabling auto renew
	 */
	$( '.rcp-disable-auto-renew' ).on( 'click', function( e ) {
		return confirm( rcpAccountVars.confirmDisableAutoRenew );
	} );

	/**
	 * Show alert when enabling auto renew
	 */
	$( '.rcp-enable-auto-renew' ).on( 'click', function( e ) {
		const expiration = $( this ).data( 'expiration' );
		let message = rcpAccountVars.confirmEnableAutoRenew;

		message = message.replace( '%s', expiration );

		return confirm( message );
	} );

} );
