<?php
/**
 * Customer Actions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set the customer's `has_trialed` flag when their trialing membership is set to active.
 *
 * @param string $old_status    Previous membership status.
 * @param int    $membership_id ID of the membership that was just set to active.
 *
 * @since 3.1.2
 * @return void
 */
function rcp_set_customer_trialing_flag( $old_status, $membership_id ) {

	$membership = rcp_get_membership( $membership_id );

	if ( $membership && $membership->is_trialing() ) {
		$membership->get_customer()->update( array(
			'has_trialed' => true
		) );

		$membership->get_customer()->add_note( sprintf( __( 'Free trial used via membership "%s" (#%d).', 'rcp' ), $membership->get_membership_level_name(), $membership_id ) );
	}

}
add_action( 'rcp_transition_membership_status_active', 'rcp_set_customer_trialing_flag', 10, 2 );

/**
 * Sets the email verification key when a customer's "email_verification" status is set to "pending".
 *
 * @param string $old_status  Old email verification status value.
 * @param string $new_status  New email verification status value.
 * @param int    $customer_id ID of the customer that was updated.
 *
 * @since 3.3.9
 * @return void
 */
function rcp_set_pending_email_verification_key( $old_status, $new_status, $customer_id ) {

	if ( 'pending' !== $new_status ) {
		return;
	}

	$customer = rcp_get_customer( $customer_id );

	if ( ! $customer instanceof RCP_Customer ) {
		return;
	}

	// Set flag.
	update_user_meta( $customer->get_user_id(), 'rcp_pending_email_verification', strtolower( md5( uniqid() ) ) );

	// Send email.
	rcp_send_email_verification( $customer->get_user_id() );

}
add_action( 'rcp_transition_customer_email_verification', 'rcp_set_pending_email_verification_key', 10, 3 );
