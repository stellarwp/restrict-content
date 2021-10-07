<?php
/**
 * Invoice Functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Invoice Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Generate URL to view / download an invoice
 *
 * @param int $payment_id ID of the payment to get the invoice for.
 *
 * @since 2.6
 * @return string URL to view/download invoice.
*/
function rcp_get_invoice_url( $payment_id = 0 ) {

	if ( empty( $payment_id ) ) {
		return false;
	}

	return add_query_arg( array( 'payment_id' => urlencode( $payment_id ), 'rcp-action' => 'download_invoice' ), home_url() );
}

/**
 * Trigger invoice download
 *
 * @uses rcp_generate_invoice()
 *
 * @return void
 */
function rcp_trigger_invoice_download() {

	if( ! isset( $_GET['rcp-action'] ) || 'download_invoice' != $_GET['rcp-action'] ) {
		return;
	}

	$payment_id = absint( $_GET['payment_id'] );

	rcp_generate_invoice( $payment_id );

}
add_action( 'init', 'rcp_trigger_invoice_download' );

/**
 * Generate Invoice
 *
 * @param int $payment_id ID of the payment to generate the invoice for.
 *
 * @since 2.6
 * @return void
*/
function rcp_generate_invoice( $payment_id = 0 ) {

	global $rcp_options, $rcp_payment, $rcp_member;

	if ( empty( $payment_id ) ) {
		return;
	}

	$payments_db  = new RCP_Payments;
	$payment      = $payments_db->get_payment( $payment_id );

	if( ! $payment ) {
		wp_die( __( 'This payment record does not exist', 'rcp' ) );
	}

	if( $payment->user_id != get_current_user_id() && ! current_user_can( 'rcp_manage_payments' ) ) {
		wp_die( __( 'You do not have permission to download this invoice', 'rcp' ) );
	}

	$rcp_payment = $payment;
	$rcp_member = new RCP_Member( $payment->user_id );

	rcp_get_template_part( 'invoice' );

	die(); // Stop the rest of the page from processsing and being sent to the browser
}
