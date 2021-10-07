<?php
/**
 * Membership Actions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro
 * @license   GPL2+
 * @since     3.0
 */

use RCP\Membership_Level;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Edit a membership
 *
 * @since 3.0
 * @return void
 */
function rcp_process_add_membership() {

	if ( ! wp_verify_nonce( $_POST['rcp_add_membership_nonce'], 'rcp_add_membership' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	$current_user = wp_get_current_user();

	rcp_log( sprintf( '%s has started adding a new membership.', $current_user->display_name ) );

	$membership_level = rcp_get_membership_level( absint( $_POST['object_id'] ) );

	if ( ! $membership_level instanceof Membership_Level ) {
		wp_die( __( 'Invalid membership level.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	if ( ! empty( $_POST['customer_id'] ) ) {
		$customer = rcp_get_customer( absint( $_POST['customer_id'] ) );
	} else {

		$customer_email = ! empty( $_POST['user_email'] ) ? $_POST['user_email'] : false;

		if ( empty( $customer_email ) ) {
			wp_die( __( 'Please enter a valid customer email.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
		}

		$user = get_user_by( 'email', $customer_email );

		// If no user exists with this email, create one.
		if ( empty( $user ) ) {
			rcp_log( 'Creating new user account.' );

			$user_id = wp_insert_user( array(
				'user_login' => sanitize_text_field( $customer_email ),
				'user_email' => sanitize_text_field( $customer_email ),
				'user_pass'  => wp_generate_password()
			) );

			if ( empty( $user_id ) ) {
				wp_die( __( 'Error creating customer account.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
			}

			$user = get_userdata( $user_id );
		} else {
			rcp_log( sprintf( 'Adding membership for existing user #%d.', $user->ID ) );
		}

		// Check for a customer record.
		$customer = rcp_get_customer_by_user_id( $user->ID );

		// Create a new customer.
		if ( empty( $customer ) ) {
			rcp_log( sprintf( 'Creating new customer record for user #%d.', $user->ID ) );

			$customer_id = rcp_add_customer( array(
				'user_id' => absint( $user->ID )
			) );

			if ( empty( $customer_id ) ) {
				wp_die( __( 'Error creating customer record.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
			}

			$customer = rcp_get_customer( $customer_id );
		} else {
			rcp_log( sprintf( 'Adding membership to existing customer #%d.', $customer->get_id() ) );
		}
	}

	if ( ! is_object( $customer ) ) {
		wp_die( __( 'Error locating customer record.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
	}

	$data = array(
		'object_id'               => absint( $_POST['object_id'] ),
		'object_type'             => 'membership',
		'initial_amount'          => sanitize_text_field( $_POST['initial_amount'] ),
		'recurring_amount'        => sanitize_text_field( $_POST['recurring_amount'] ),
		'created_date'            => date( 'Y-m-d H:i:s', strtotime( $_POST['created_date'], current_time( 'timestamp' ) ) ),
		'expiration_date'         => ! empty( $_POST['expiration_date_none'] ) ? 'none' : date( 'Y-m-d 23:59:59', strtotime( $_POST['expiration_date'], current_time( 'timestamp' ) ) ),
		'auto_renew'              => ! empty( $_POST['auto_renew'] ) ? 1 : 0,
		'times_billed'            => absint( $_POST['times_billed'] ),
		'maximum_renewals'        => $membership_level->get_maximum_renewals(),
		'status'                  => sanitize_text_field( wp_unslash( $_POST['status'] ) ),
		'gateway_customer_id'     => sanitize_text_field( wp_unslash( $_POST['gateway_customer_id'] ) ),
		'gateway_subscription_id' => sanitize_text_field( wp_unslash( $_POST['gateway_subscription_id'] ) ),
		'gateway'                 => sanitize_text_field( wp_unslash( $_POST['gateway'] ) ),
		'signup_method'           => 'manual'
	);

	/**
	 * Add new membership.
	 */
	$membership_id = $customer->add_membership( $data );

	if ( empty( $membership_id ) ) {
		wp_die( __( 'Error adding membership record.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
	}

	$membership = rcp_get_membership( $membership_id );
	if ( $membership instanceof RCP_Membership ) {
		$membership->add_note( sprintf( __( 'Membership manually created by %s.', 'rcp' ), $current_user->user_login ) );
	}

	$redirect = add_query_arg( 'rcp_message', 'membership_updated', rcp_get_memberships_admin_page( array(
		'membership_id' => urlencode( $membership_id ),
		'view'          => 'edit'
	) ) );

	wp_safe_redirect( $redirect );
	exit;

}

add_action( 'rcp_action_add_membership', 'rcp_process_add_membership' );

/**
 * Edit a membership
 *
 * @since 3.0
 * @return void
 */
function rcp_process_edit_membership() {

	if ( ! wp_verify_nonce( $_POST['rcp_edit_membership_nonce'], 'rcp_edit_membership' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_POST['membership_id'] ) ) {
		wp_die( __( 'Invalid membership ID.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$membership_id = absint( $_POST['membership_id'] );
	$membership    = rcp_get_membership( $membership_id );


	if ( empty( $membership ) ) {
		wp_die( __( 'Invalid membership.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$current_user = wp_get_current_user();

	/**
	 * Delete membership.
	 */
	if ( ! empty( $_POST['rcp_delete_membership'] ) ) {
		rcp_log( sprintf( '%s deleting membership #%d.', $current_user->user_login, $membership_id ) );

		$membership->disable();

		$redirect = add_query_arg( 'rcp_message', 'membership_deleted', rcp_get_memberships_admin_page() );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Change membership level.
	 */
	if ( ! empty( $_POST['rcp_change_membership_level'] ) ) {
		$new_level_id = ! empty( $_POST['object_id'] ) ? absint( $_POST['object_id'] ) : 0;
		$old_level_id = $membership->get_object_id();

		if ( ! empty( $new_level_id ) && $new_level_id != $old_level_id ) {
			rcp_log( sprintf( '%s changing membership level for membership #%d. Old level ID: %d; New level ID: %d.', $current_user->user_login, $membership_id, $old_level_id, $new_level_id ) );

			$new_status = $membership->get_status();
			$recurring  = $membership->is_recurring();

			// Disable the old membership.
			$membership->disable();

			$new_membership_id = $membership->get_customer()->add_membership( array(
				'status'              => $new_status, // keep the same status
				'object_id'           => $new_level_id,
				'gateway'             => $membership->get_gateway(),
				'upgraded_from'       => $membership->get_id()
			) );

			if ( empty ( $new_membership_id ) ) {
				wp_die( __( 'Error changing membership level.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
			}

			/**
			 * Triggers when the membership level is manually changed.
			 *
			 * @param int            $new_membership_id ID number of the new membership record.
			 * @param RCP_Membership $membership        Membership object for the old record, which is now disabled.
			 * @param bool           $recurring         Whether or not the old membership was recurring.
			 */
			do_action( 'rcp_membership_level_manually_changed', $new_membership_id, $membership, $recurring );

			$redirect = add_query_arg( 'rcp_message', 'membership_level_changed', rcp_get_memberships_admin_page( array(
				'membership_id' => urlencode( $new_membership_id ),
				'view'          => 'edit'
			) ) );
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	$redirect_query_args = array( 'rcp_message' => 'membership_updated' );

	rcp_log( sprintf( '%s has started editing membership #%d.', $current_user->user_login, $membership_id ) );

	// Data to update.
	$args = array();

	// Membership level ID. The role is automatically updated in rcp_update_user_role_on_membership_object_id_transition()
	// This is commented out because I'm not sure I want to encourage even doing this...
	/*$object_id = ! empty( $_POST['object_id'] ) ? $_POST['object_id'] : false;
	if ( ! empty( $object_id ) && $object_id != $membership->get_object_id() ) {
		$args['object_id'] = absint( $object_id );

		// Change recurring amount. @todo move to action
		$new_membership_level     = rcp_get_subscription_details( absint( $object_id ) );
		$args['recurring_amount'] = $new_membership_level->price;
		$args['maximum_renewals'] = ! empty( $new_membership_level->maximum_renewals ) ? absint( $new_membership_level->maximum_renewals ) : 0;
	}*/

	// Status
	$status = ! empty( $_POST['status'] ) ? $_POST['status'] : false;
	if ( ! empty( $status ) && $status != $membership->get_status() ) {
		switch ( $status ) {
			case 'cancelled' :
				if ( $membership->can_cancel() ) {
					$cancelled = $membership->cancel_payment_profile();

					if ( is_wp_error( $cancelled ) ) {
						// Bail if cancellation failed.
						$redirect = add_query_arg( array(
							'rcp_message'                => 'membership_cancellation_failed',
							'rcp_cancel_failure_message' => urlencode( $cancelled->get_error_message() )
						), rcp_get_memberships_admin_page( array(
							'membership_id' => urlencode( $membership_id ),
							'view'          => 'edit'
						) ) );
						wp_safe_redirect( $redirect );
						exit;
					}
				} else {
					$membership->cancel();
				}
				break;
			default :
				$args['status'] = sanitize_text_field( $status );
				break;
		}
	}

	// Recurring amount.
	$recurring_amount = ! empty( $_POST['recurring_amount'] ) ? sanitize_text_field( $_POST['recurring_amount'] ) : 0;
	if ( ! empty( $recurring_amount ) && $recurring_amount != $membership->get_recurring_amount() && $membership->can_change_gateway_price() ) {
		$amount_changed = $membership->change_gateway_price( $recurring_amount );

		if ( ! is_wp_error( $amount_changed ) ) {
			$args['recurring_amount'] = $recurring_amount;
		}
	}

	// Date Created
	$date_created = ! empty( $_POST['created_date'] ) ? date( 'Y-m-d H:i:s', strtotime( $_POST['created_date'], current_time( 'timestamp' ) ) ) : false;
	if ( $date_created != $membership->get_created_date( false ) ) {
		$args['created_date'] = sanitize_text_field( $date_created );
	}

	// Expiration Date
	$expiration_date = ! empty( $_POST['expiration_date_none'] ) ? 'none' : date( 'Y-m-d 23:59:59', strtotime( $_POST['expiration_date'], current_time( 'timestamp' ) ) );
	if ( $expiration_date != $membership->get_expiration_date( false ) ) {
		$args['expiration_date'] = sanitize_text_field( $expiration_date );

		if ( $membership->is_trialing() ) {
			$args['trial_end_date'] = sanitize_text_field( $expiration_date );
		}

		if ( $membership->can_change_next_bill_date() ) {
			$membership->change_next_bill_date( $expiration_date );
		}
	}

	// Gateway
	$gateway = ! empty( $_POST['gateway'] ) ? wp_unslash( $_POST['gateway'] ) : '';
	if ( ! empty( $gateway ) && $gateway != $membership->get_gateway() ) {
		$args['gateway'] = sanitize_text_field( $gateway );
	}

	// Gateway Customer ID
	$gateway_customer_id = ! empty( $_POST['gateway_customer_id'] ) ? wp_unslash( $_POST['gateway_customer_id'] ) : '';
	if ( $gateway_customer_id != $membership->get_gateway_customer_id() ) {
		$args['gateway_customer_id'] = sanitize_text_field( $gateway_customer_id );
	}

	// Gateway Subscription ID
	$gateway_subscription_id = ! empty( $_POST['gateway_subscription_id'] ) ? wp_unslash( $_POST['gateway_subscription_id'] ) : '';
	if ( $gateway_subscription_id != $membership->get_gateway_subscription_id() ) {
		$args['gateway_subscription_id'] = sanitize_text_field( $gateway_subscription_id );
	}

	// Auto renew
	$auto_renew = ! empty( $_POST['auto_renew'] ) ? true : false;
	if ( $auto_renew != $membership->is_recurring() ) {
		if ( $membership->can_toggle_auto_renew() ) {
			if ( $auto_renew ) {
				/**
				 * Toggle auto renew on.
				 */
				$auto_renew_toggle_result = $membership->toggle_auto_renew_on();

				if ( true === $auto_renew_toggle_result ) {
					$membership->add_note( sprintf( __( 'Auto renew enabled by %s.', 'rcp' ), $current_user->user_login ) );

					// Prevent the admin from overwriting this.
					if ( isset( $args['gateway_subscription_id'] ) ) {
						unset( $args['gateway_subscription_id'] );
					}
				}
			} else {
				$auto_renew_toggle_result = $membership->toggle_auto_renew_off();

				if ( true === $auto_renew_toggle_result ) {
					$membership->add_note( sprintf( __( 'Auto renew disabled by %s.', 'rcp' ), $current_user->user_login ) );

					// This should be wiped.
					if ( isset( $args['gateway_subscription_id'] ) ) {
						$args['gateway_subscription_id'] = '';
					}
				}
			}

			if ( is_wp_error( $auto_renew_toggle_result ) ) {
				$redirect_query_args['rcp_auto_renew_toggle_error'] = rawurlencode( $auto_renew_toggle_result->get_error_message() );
			}
		} else {
			$args['auto_renew'] = (int) $auto_renew;
		}
	}

	if ( ! empty( $args ) ) {
		$membership->update( $args );
		$membership->add_note( sprintf( __( 'Membership edited by %s.', 'rcp' ), $current_user->user_login ) );
	}

	// New action that users can use to save custom fields.
	do_action( 'rcp_after_membership_admin_update', $membership, $args );

	$redirect = add_query_arg( $redirect_query_args, rcp_get_memberships_admin_page( array(
		'membership_id' => urlencode( $membership_id ),
		'view'          => 'edit'
	) ) );
	wp_safe_redirect( $redirect );
	exit;

}

add_action( 'rcp_action_edit_membership', 'rcp_process_edit_membership' );

/**
 * When a membership level is manually changed, create a new recurring subscription for the new membership
 * if the old one was recurring.
 *
 * @param int            $new_membership_id ID of the new membership record.
 * @param RCP_Membership $old_membership    Membership object for the old record, which is now disabled.
 * @param bool           $recurring         Whether or not the old membership was recurring.
 *
 * @since 3.5
 * @return void
 */
function rcp_create_gateway_subscription_on_level_change( $new_membership_id, $old_membership, $recurring ) {

	// We only create subscriptions for recurring memberships.
	if ( ! $recurring ) {
		return;
	}

	$membership = rcp_get_membership( $new_membership_id );

	if ( empty( $membership ) ) {
		return;
	}

	if ( ! $membership->can_create_gateway_subscription() ) {
		return;
	}

	$membership->create_gateway_subscription( false );

}
add_action( 'rcp_membership_level_manually_changed', 'rcp_create_gateway_subscription_on_level_change', 10, 3 );

/**
 * Process adding a new note to the membership.
 *
 * @todo  ajaxify
 *
 * @since 3.0
 * @return void
 */
function rcp_process_add_membership_note() {


	if ( ! wp_verify_nonce( $_POST['rcp_add_membership_note_nonce'], 'rcp_add_membership_note' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_POST['membership_id'] ) ) {
		wp_die( __( 'Invalid membership ID.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$membership_id = absint( $_POST['membership_id'] );
	$membership    = rcp_get_membership( $membership_id );

	if ( empty( $membership ) ) {
		wp_die( __( 'Invalid membership.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$new_note = wp_unslash( $_POST['new_note'] );

	if ( empty( $new_note ) ) {
		wp_die( __( 'Please enter a note.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$current_user = wp_get_current_user();

	rcp_log( sprintf( '%s is adding a new note to membership #%d.', $current_user->user_login, $membership_id ) );

	$membership->add_note( sanitize_text_field( $new_note ) );

	$redirect = add_query_arg( 'rcp_message', 'membership_note_added', rcp_get_memberships_admin_page( array( 'membership_id' => urlencode( $membership_id ), 'view' => 'edit' ) ) );
	wp_safe_redirect( $redirect );
	exit;

}

add_action( 'rcp_action_add_membership_note', 'rcp_process_add_membership_note' );

/**
 * Process expiring a membership. This expires the membership and cancels the payment profile.
 *
 * @since 3.0
 * @return void
 */
function rcp_process_expire_membership() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'expire_membership' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_GET['membership_id'] ) ) {
		wp_die( __( 'Invalid membership ID.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$membership_id = absint( $_GET['membership_id'] );
	$membership    = rcp_get_membership( $membership_id );


	if ( empty( $membership ) ) {
		wp_die( __( 'Invalid membership.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$current_user = wp_get_current_user();

	rcp_log( sprintf( '%s is expiring membership #%d.', $current_user->user_login, $membership_id ) );

	// Cancel gateway subscription.
	if ( $membership->can_cancel() ) {
		$membership->cancel_payment_profile( false );
		$membership->set_recurring( false );
	}

	$membership->expire();

	$redirect = add_query_arg( 'rcp_message', 'membership_expired', rcp_get_memberships_admin_page( array( 'membership_id' => urlencode( $membership_id ), 'view' => 'edit' ) ) );
	wp_safe_redirect( $redirect );
	exit;

}

add_action( 'rcp_action_expire_membership', 'rcp_process_expire_membership' );

/**
 * Process revoking membership access. This expires the membership and cancels the payment profile.
 *
 * @since 3.0
 * @return void
 */
function rcp_process_cancel_membership() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'cancel_membership' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_GET['membership_id'] ) ) {
		wp_die( __( 'Invalid membership ID.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$membership_id = absint( $_GET['membership_id'] );
	$membership    = rcp_get_membership( $membership_id );


	if ( empty( $membership ) ) {
		wp_die( __( 'Invalid membership.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$current_user = wp_get_current_user();

	rcp_log( sprintf( '%s is cancelling membership #%d.', $current_user->user_login, $membership_id ) );

	$membership->add_note( sprintf( __( 'Membership cancelled via admin link by user %s (#%d).', 'rcp' ), $current_user->user_login, $current_user->ID ) );

	// Cancel gateway subscription.
	if ( $membership->can_cancel() ) {
		$cancelled = $membership->cancel_payment_profile();

		if ( is_wp_error( $cancelled ) ) {
			// Bail and show error if cancellation failed.
			$redirect = add_query_arg( array(
				'rcp_message'                => 'membership_cancellation_failed',
				'rcp_cancel_failure_message' => urlencode( $cancelled->get_error_message() )
			), rcp_get_memberships_admin_page( array(
				'membership_id' => urlencode( $membership_id ),
				'view'          => 'edit'
			) ) );

			wp_safe_redirect( $redirect );
			exit;
		}
	} else {
		$membership->cancel();
	}

	$redirect = add_query_arg( 'rcp_message', 'membership_cancelled', rcp_get_memberships_admin_page( array( 'membership_id' => urlencode( $membership_id ), 'view' => 'edit' ) ) );
	wp_safe_redirect( $redirect );
	exit;

}

add_action( 'rcp_action_cancel_membership', 'rcp_process_cancel_membership' );

/**
 * Insert a new payment for a membership.
 *
 * @since 3.0
 * @return void
 */
function rcp_process_add_membership_payment() {

	if ( ! wp_verify_nonce( $_POST['rcp_add_membership_payment_nonce'], 'rcp_add_membership_payment' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_payments' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_POST['membership_id'] ) ) {
		wp_die( __( 'Invalid membership ID.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$current_user = wp_get_current_user();
	$membership   = rcp_get_membership( absint( $_GET['membership_id'] ) );

	rcp_log( sprintf( '%s manually inserting new payment record for membership #%d.', $current_user->user_login, $membership->get_id() ) );

	// Renew first, if specified.
	if ( ! empty( $_POST['renew_and_add_payment'] ) ) {
		$membership->renew( $membership->is_recurring() );
	}

	$auth_key               = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
	$default_transaction_id = strtolower( md5( $membership->get_subscription_key() . date( 'Y-m-d H:i:s' ) . $auth_key . uniqid( 'rcp', true ) ) );
	$transaction_id         = ! empty( $_POST['transaction_id'] ) ? wp_unslash( $_POST['transaction_id'] ) : $default_transaction_id;

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	$membership_level = rcp_get_membership_level( $membership->get_object_id() );

	$data = array(
		'amount'           => ! empty( $_POST['amount'] ) ? sanitize_text_field( $_POST['amount'] ) : 0.00,
		'subtotal'         => $membership_level instanceof Membership_Level ? sanitize_text_field( $membership_level->get_price() ) : 0.00,
		'user_id'          => $membership->get_user_id(),
		'customer_id'      => $membership->get_customer()->get_id(),
		'membership_id'    => $membership->get_id(),
		'date'             => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
		'payment_type'     => 'manual',
		'transaction_type' => 'renewal',
		'subscription'     => $membership->get_membership_level_name(),
		'subscription_key' => $membership->get_subscription_key(),
		'transaction_id'   => sanitize_text_field( $transaction_id ),
		'status'           => ! empty( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'complete',
		'object_id'        => $membership->get_object_id(),
		'object_type'      => 'subscription'
	);

	$add = $rcp_payments_db->insert( $data );

	if ( ! empty( $add ) ) {
		$cache_args = array( 'earnings' => 1, 'subscription' => 0, 'user_id' => 0, 'date' => '' );
		$cache_key  = md5( implode( ',', $cache_args ) );
		delete_transient( $cache_key );

		$message = 'payment_added';
	} else {
		rcp_log( sprintf( 'Failed adding new manual payment by %s.', $current_user->user_login ), true );
		$message = 'payment_not_added';
	}

	$url = rcp_get_memberships_admin_page( array(
		'membership_id' => $membership->get_id(),
		'view'          => 'edit',
		'rcp_message'   => $message
	) );

	wp_safe_redirect( $url );
	exit;

}

add_action( 'rcp_action_add_membership_payment', 'rcp_process_add_membership_payment' );
