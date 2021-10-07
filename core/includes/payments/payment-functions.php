<?php
/**
 * Payment Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Sandhills Development, LLC
 * @license   GPL2+
 * @since     3.2.3
 */

/**
 * Get the recovery URL for a payment.
 *
 * @param int $payment_id
 *
 * @since 3.2.3
 * @return string
 */
function rcp_get_payment_recovery_url( $payment_id ) {

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	$payment = $rcp_payments_db->get_payment( $payment_id );

	if ( empty( $payment ) ) {
		return '';
	}

	$url = add_query_arg( array(
		'registration_type'           => urlencode( $payment->transaction_type ),
		'rcp_registration_payment_id' => urlencode( $payment_id ),
		'level'                       => urlencode( $payment->object_id )
	), rcp_get_registration_page_url() );

	return $url;

}
