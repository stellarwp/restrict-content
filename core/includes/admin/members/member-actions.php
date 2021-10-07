<?php
/**
 * Member Actions
 *
 * @package     restrict-content-pro
 * @subpackage  Admin/Member Actions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Edit a member
 *
 * @deprecated 3.0 In favour of `rcp_process_edit_customer()`.
 * @see rcp_process_edit_customer()
 *
 * @since 2.9
 * @return void
 */
function rcp_process_edit_member() {

	if ( ! wp_verify_nonce( $_POST['rcp_edit_member_nonce'], 'rcp_edit_member_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	$levels        = new RCP_Levels();
	$user_id       = absint( $_POST['user'] );
	$member        = new RCP_Member( $user_id );
	$email         = sanitize_text_field( $_POST['email'] );
	$status        = sanitize_text_field( $_POST['status'] );
	$level_id      = absint( $_POST['level'] );
	$expiration    = isset( $_POST['expiration'] ) ? sanitize_text_field( $_POST['expiration'] ) : 'none';
	$expiration    = 'none' !== $expiration ? date( 'Y-m-d 23:59:59', strtotime( $_POST['expiration'], current_time( 'timestamp' ) ) ) : $expiration;
	$revoke_access = isset( $_POST['rcp-revoke-access'] );
	$current_user = wp_get_current_user();

	rcp_log( sprintf( '%s has started editing member #%d.', $current_user->user_login, $user_id ) );

	$previous_expiration = $member->get_expiration_date( false );

	if ( $previous_expiration != $expiration ) {
		rcp_log( sprintf( 'Updated member #%d expiration date from %s to %s.', $user_id, $previous_expiration, $expiration ) );
	}

	if ( isset( $_POST['notes'] ) ) {
		update_user_meta( $user_id, 'rcp_notes', wp_kses( $_POST['notes'], array() ) );
	}

	if ( isset( $_POST['cancel_subscription'] ) && $member->can_cancel() ) {
		rcp_log( sprintf( 'Cancelling payment profile for member #%d.', $user_id ) );
		$cancelled = $member->cancel_payment_profile( false );
	}

	if( ! empty( $_POST['expiration'] ) && ( 'cancelled' != $status || ! $revoke_access ) ) {
		$member->set_expiration_date( $expiration );
	} elseif( $revoke_access && ! $member->is_expired() ) {
		$member->set_expiration_date( date( 'Y-m-d H:i:s', strtotime( '-1 day', current_time( 'timestamp' ) ) ) );
		// Set status to 'expired' later.
		$status = 'expired';
	}

	if ( isset( $_POST['level'] ) ) {

		$current_id = rcp_get_subscription_id( $user_id );
		$new_level  = $levels->get_level( $level_id );
		$old_level  = $levels->get_level( $current_id );

		if ( $current_id != $level_id ) {

			rcp_log( sprintf( 'Changed member #%d membership level from %d to %d.', $user_id, $current_id, $level_id ) );

			$member->set_subscription_id( $level_id );

			// Remove the old user role
			$role = ! empty( $old_level->role ) ? $old_level->role : 'subscriber';
			$member->remove_role( $role );

			// Add the new user role
			$role = ! empty( $new_level->role ) ? $new_level->role : 'subscriber';
			$member->add_role( $role );

			// Set joined date for the new subscription
			$member->set_joined_date( '', $level_id );

		}
	}

	if ( isset( $_POST['recurring'] ) ) {
		$member->set_recurring( true );
	} else {
		$member->set_recurring( false );
	}

	if ( isset( $_POST['trialing'] ) ) {
		update_user_meta( $user_id, 'rcp_is_trialing', 'yes' );
	} else {
		delete_user_meta( $user_id, 'rcp_is_trialing' );
	}

	if ( isset( $_POST['signup_method'] ) ) {
		update_user_meta( $user_id, 'rcp_signup_method', $_POST['signup_method'] );
	}

	if ( $status !== $member->get_status() ) {
		$member->set_status( $status );
	}

	if ( isset( $_POST['payment-profile-id'] ) ) {
		$member->set_payment_profile_id( $_POST['payment-profile-id'] );
	}

	if ( $email != $member->user_email ) {
		rcp_log( sprintf( 'Changing email for member #%d.', $user_id ) );
		wp_update_user( array( 'ID' => $user_id, 'user_email' => $email ) );
	}

	do_action( 'rcp_edit_member', $user_id );

	rcp_log( sprintf( '%s finished editing member #%d.', $current_user->user_login, $user_id ) );

	$redirect = admin_url( 'admin.php?page=rcp-members&edit_member=' . $user_id );

	if ( isset( $cancelled ) && is_wp_error( $cancelled ) ) {
		$redirect = add_query_arg( 'rcp_message', 'member_cancelled_error', $redirect );
	} else {
		$redirect = add_query_arg( 'rcp_message', 'user_updated', $redirect );
	}

	wp_safe_redirect( $redirect );
	exit;

}
add_action( 'rcp_action_edit-member', 'rcp_process_edit_member' );

/**
 * Add a subscription to an existing member
 *
 * @deprecated 3.0 In favour of `rcp_process_add_membership()`.
 * @see rcp_process_add_membership()
 *
 * @since 2.9
 * @return void
 */
function rcp_process_add_member_subscription() {

	if ( ! wp_verify_nonce( $_POST['rcp_add_member_nonce'], 'rcp_add_member_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_POST['level'] ) || empty( $_POST['user'] ) ) {
		wp_die( __( 'Please fill out all fields.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	// Don't add if chosen expiration date is in the past.
	if ( isset( $_POST['expiration'] ) && strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $_POST['expiration'], current_time( 'timestamp' ) ) && 'none' !== $_POST['expiration'] ) {
		rcp_log( sprintf( 'Failed adding subscription to an existing user: chosen expiration date ( %s ) is in the past.', $_POST['expiration'] ), true );
		wp_safe_redirect( admin_url( 'admin.php?page=rcp-members&rcp_message=user_not_added' ) );
		exit;
	}

	$levels = new RCP_Levels();
	$user   = get_user_by( 'login', $_POST['user'] );

	if ( ! $user ) {
		wp_die( __( 'You entered a username that does not exist.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$member       = new RCP_Member( $user->ID );
	$expiration   = isset( $_POST['expiration'] ) ? sanitize_text_field( $_POST['expiration'] ) : 'none';
	$level_id     = absint( $_POST['level'] );
	$subscription = $levels->get_level( $level_id );

	if ( ! $subscription ) {
		wp_die( __( 'Please supply a valid membership level.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$member->set_expiration_date( $expiration );

	$new_subscription = get_user_meta( $user->ID, '_rcp_new_subscription', true );

	if ( empty( $new_subscription ) ) {
		update_user_meta( $user->ID, '_rcp_new_subscription', '1' );
	}

	update_user_meta( $user->ID, 'rcp_signup_method', 'manual' );

	$member->set_subscription_id( $level_id );

	$status = $subscription->price == 0 ? 'free' : 'active';

	$member->set_status( $status );

	// Add the new user role
	$role = ! empty( $subscription->role ) ? $subscription->role : 'subscriber';
	$user->add_role( $role );

	// Set joined date for the new subscription
	$member->set_joined_date( '', $level_id );

	if ( isset( $_POST['recurring'] ) ) {
		update_user_meta( $user->ID, 'rcp_recurring', 'yes' );
	} else {
		delete_user_meta( $user->ID, 'rcp_recurring' );
	}

	rcp_log( sprintf( 'Successfully added new subscription for user #%d. Level ID: %d; Status: %s; Expiration Date: %s; Role: %s', $member->ID, $level_id, $status, $expiration, $role ) );

	wp_safe_redirect( admin_url( 'admin.php?page=rcp-members&rcp_message=user_added' ) );
	exit;

}
add_action( 'rcp_action_add-subscription', 'rcp_process_add_member_subscription' );

/**
 * Process bulk edit members
 *
 * @deprecated 3.0
 *
 * @since 2.9
 * @return void
 */
function rcp_process_bulk_edit_members() {

	if ( ! wp_verify_nonce( $_POST['rcp_bulk_edit_nonce'], 'rcp_bulk_edit_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_POST['member-ids'] ) ) {
		wp_die( __( 'Please select at least one member to edit.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$member_ids = array_map( 'absint', $_POST['member-ids'] );
	$action     = ! empty( $_POST['rcp-bulk-action'] ) ? sanitize_text_field( $_POST['rcp-bulk-action'] ) : false;

	foreach ( $member_ids as $member_id ) {

		$member = new RCP_Member( $member_id );

		if ( ! empty( $_POST['expiration'] ) && 'delete' !== $action ) {
			$member->set_expiration_date( date( 'Y-m-d 23:59:59', strtotime( $_POST['expiration'], current_time( 'timestamp' ) ) ) );
		}

		if ( $action ) {

			switch ( $action ) {

				case 'mark-active' :

					$member->set_status( 'active' );

					break;

				case 'mark-expired' :

					$member->set_status( 'expired' );

					break;

				case 'mark-cancelled' :

					$member->cancel();

					if( ! empty( $_POST['rcp-revoke-access'] ) && ! $member->is_expired() ) {
						$member->set_expiration_date( date( 'Y-m-d H:i:s', strtotime( '-1 day', current_time( 'timestamp' ) ) ) );
					}

					break;

			}

		}

	}

	wp_safe_redirect( admin_url( 'admin.php?page=rcp-members&rcp_message=members_updated' ) );
	exit;

}
add_action( 'rcp_action_bulk_edit_members', 'rcp_process_bulk_edit_members' );

/**
 * Cancel a member from the Members table
 *
 * @deprecated 3.0 In favour of `rcp_process_cancel_membership()`.
 * @see rcp_process_cancel_membership()
 *
 * @since 2.9
 * @return void
 */
function rcp_process_cancel_member() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp-cancel-nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! isset( $_GET['member_id'] ) ) {
		wp_die( __( 'Please select a member.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	rcp_cancel_member_payment_profile( urldecode( absint( $_GET['member_id'] ) ) );
	wp_safe_redirect( admin_url( add_query_arg( 'rcp_message', 'member_cancelled', 'admin.php?page=rcp-members' ) ) );
	exit;

}
add_action( 'rcp_action_cancel_member', 'rcp_process_cancel_member' );

/**
 * Re-send a member's verification email
 *
 * @since 2.9
 * @return void
 */
function rcp_process_resend_verification() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp-verification-nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! isset( $_GET['member_id'] ) && ! isset( $_GET['customer_id'] ) ) {
		wp_die( __( 'Please select a customer.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$customer_id  = ! empty( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : false;

	if ( empty( $customer_id ) ) {
		$customer = rcp_get_customer_by_user_id( absint( $_GET['member_id'] ) );

		if ( ! empty( $customer ) ) {
			$customer_id = $customer->get_id();
		}
	}

	$current_user = wp_get_current_user();

	rcp_log( sprintf( '%s re-sending email verification to customer #%d.', $current_user->user_login, $customer_id ) );

	rcp_send_email_verification( urldecode( absint( $_GET['member_id'] ) ) );

	$page_args = array(
		'rcp_message' => 'verification_sent'
	);

	if ( ! empty( $customer_id ) ) {
		$page_args['customer_id'] = $customer_id;
		$page_args['view']        = 'edit';
	}

	wp_safe_redirect( rcp_get_customers_admin_page( $page_args ) );
	exit;

}
add_action( 'rcp_action_send_verification', 'rcp_process_resend_verification' );

/**
 * Manually verify a member's email
 *
 * @since 2.9.5
 * @return void
 */
function rcp_process_manually_verify_email() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp-manually-verify-email-nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! isset( $_GET['member_id'] ) && ! isset( $_GET['customer_id'] ) ) {
		wp_die( __( 'Please select a customer.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$current_user = wp_get_current_user();

	if ( ! empty( $_GET['customer_id'] ) ) {
		/*
		 * New: Verify by customer ID.
		 */
		$customer = rcp_get_customer( absint( $_GET['customer_id'] ) );

		if ( empty( $customer ) ) {
			wp_die( __( 'Invalid customer ID.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
		}

		rcp_log( sprintf( '%s manually verifying email of customer #%d.', $current_user->user_login, $customer->get_id() ) );

		$customer->verify_email();
		$customer->add_note( sprintf( __( 'Email manually verified by %s.', 'rcp' ), $current_user->user_login ) );
	} else {
		/*
		 * Backwards Compatibility
		 * Verify by user ID.
		 */
		$member = new RCP_Member( absint( $_GET['member_id'] ) );

		rcp_log( sprintf( '%s manually verifying email of member #%d.', $current_user->user_login, $member->ID ) );

		$member->verify_email();
		$member->add_note( sprintf( __( 'Email manually verified by %s.', 'rcp' ), $current_user->user_login ) );
	}

	$redirect_url = wp_get_referer();

	if ( empty( $redirect_url ) ) {
		$redirect_url = rcp_get_customers_admin_page();
	} else {
		$redirect_url = remove_query_arg( 'rcp_message', $redirect_url );
	}

	wp_safe_redirect( add_query_arg( 'rcp_message', 'email_verified', $redirect_url ) );
	exit;

}
add_action( 'rcp_action_verify_email', 'rcp_process_manually_verify_email' );

/**
 * If someone visits ?page=rcp-members&edit_member=123 we need to redirect them to the new customer edit page for
 * this corresponding user account.
 *
 * @since 3.0
 * @return void
 */
function rcp_redirect_edit_member_url() {

	if ( empty( $_GET['page'] ) || 'rcp-members' != $_GET['page'] ) {
		return;
	}

	if ( empty( $_GET['edit_member'] ) ) {
		return;
	}

	$user_id  = absint( $_GET['edit_member'] );
	$customer = rcp_get_customer_by( 'user_id', $user_id );

	if ( empty( $customer ) ) {
		return;
	}

	wp_safe_redirect( rcp_get_customers_admin_page( array(
		'customer_id' => $customer->get_id(),
		'view'        => 'edit'
	) ) );
	exit;

}
add_action( 'admin_init', 'rcp_redirect_edit_member_url' );