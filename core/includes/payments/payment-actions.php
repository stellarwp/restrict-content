<?php
/**
 * Payment Actions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.1
 */

/**
 * Add a note to customer and membership records when a payment is completed.
 *
 * @param int $payment_id
 *
 * @since 3.1
 * @return void
 */
function rcp_log_notes_on_payment_completion( $payment_id ) {

	$payments = new RCP_Payments();
	$payment  = $payments->get_payment( $payment_id );

	if ( empty( $payment ) ) {
		return;
	}

	$charge_type_slug = ! empty( $payment->transaction_type ) ? $payment->transaction_type : 'new';

	$note = sprintf( __( 'Successful payment for membership "%s". Payment ID: #%d; Amount: %s; Gateway: %s; Type: %s.', 'rcp' ), rcp_get_subscription_name( $payment->object_id ), $payment->id, rcp_currency_filter( $payment->amount ), $payment->gateway, $charge_type_slug );

	if ( ! empty( $payment->customer_id ) && $customer = rcp_get_customer( $payment->customer_id ) ) {
		$customer->add_note( $note );
	}

	if ( ! empty( $payment->membership_id ) && $membership = rcp_get_membership( $payment->membership_id ) ) {
		$membership->add_note( $note );
	}

}

add_action( 'rcp_update_payment_status_complete', 'rcp_log_notes_on_payment_completion' );

/**
 * Deletes the membership `renewal_payment_failed_emails_number` meta when a payment is completed.
 * This allows renewal payment failed emails to be sent again.
 *
 * @param int $payment_id
 *
 * @since 3.3.2
 * @return void
 */
function rcp_remove_renewal_failure_meta_on_payment_completion( $payment_id ) {

	$payments = new RCP_Payments();
	$payment  = $payments->get_payment( $payment_id );

	if ( empty( $payment ) || empty( $payment->membership_id ) ) {
		return;
	}

	rcp_delete_membership_level_meta( $payment_id, 'renewal_payment_failed_emails_number' );

}

add_action( 'rcp_update_payment_status_complete', 'rcp_remove_renewal_failure_meta_on_payment_completion' );
