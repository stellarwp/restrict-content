<?php
/**
 * Customer Actions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add a new customer
 *
 * @since 3.0
 * @return void
 */
function rcp_process_add_customer() {

	if ( ! wp_verify_nonce( $_POST['rcp_add_customer_nonce'], 'rcp_add_customer_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	$current_user = wp_get_current_user();

	rcp_log( sprintf( '%s has started adding a new customer.', $current_user->display_name ) );

	$type = ! empty( $_POST['rcp_customer_user_account'] ) ? $_POST['rcp_customer_user_account'] : 'new';

	$customer_email = ! empty( $_POST['user_email'] ) ? $_POST['user_email'] : false;

	if ( empty( $customer_email ) ) {
		wp_die( __( 'Please enter a valid customer email.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	// Create new user account.
	if ( 'new' == $type ) {
		rcp_log( 'Creating new user account.' );

		$user_login = ! empty( $_POST['user_login'] ) ? $_POST['user_login'] : $customer_email;

		$existing_user = get_user_by( 'login', $user_login );

		if ( ! empty( $existing_user ) ) {
			wp_die( sprintf( __( 'A user account already exists with the login %s.', 'rcp' ), esc_html( $user_login ) ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
		}

		$user_args = array(
			'user_login' => sanitize_text_field( $user_login ),
			'user_email' => sanitize_text_field( $customer_email ),
			'user_pass'  => ! empty( $_POST['user_password'] ) ? $_POST['user_password'] : wp_generate_password( 24 ),
			'first_name' => ! empty( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '',
			'last_name'  => ! empty( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : ''
		);

		$user_args['display_name'] = trim( $user_args['first_name'] . ' ' . $user_args['last_name'] );

		if ( empty( $user_args['display_name'] ) ) {
			$user_args['display_name'] = $user_args['user_login'];
		}

		$user_id = wp_insert_user( $user_args );

		if ( empty( $user_id ) ) {
			wp_die( __( 'Error creating customer account.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
		}

		$user = get_userdata( $user_id );
	} else {
		// Use existing user account.
		$user = get_user_by( 'email', $customer_email );

		if ( ! is_a( $user, 'WP_User' ) ) {
			wp_die( sprintf( __( 'Unable to locate existing account with the email %s.', 'rcp' ), esc_html( $customer_email ) ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
		}
	}

	// Check for a customer record.
	$customer = rcp_get_customer_by_user_id( $user->ID );

	if ( ! empty( $customer ) ) {
		wp_die( sprintf( __( 'A customer with the ID %d already exists with this account.', 'rcp' ), $customer->get_id() ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
	}

	rcp_log( sprintf( 'Creating new customer record for user #%d.', $user->ID ) );

	$customer_id = rcp_add_customer( array(
		'user_id'         => absint( $user->ID ),
		'date_registered' => date( 'Y-m-d H:i:s', strtotime( $_POST['date_registered'], current_time( 'timestamp' ) ) )
	) );

	if ( empty( $customer_id ) ) {
		wp_die( __( 'Error creating customer record.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
	}

	$redirect = add_query_arg( 'rcp_message', 'customer_added', rcp_get_customers_admin_page( array(
		'customer_id' => urlencode( $customer_id ),
		'view'        => 'edit'
	) ) );
	wp_safe_redirect( $redirect );
	exit;

}

add_action( 'rcp_action_add_customer', 'rcp_process_add_customer' );

/**
 * Process editing a customer.
 *
 * Funnily enough, this actually only updates the user account.
 *
 * @since 3.0
 * @return void
 */
function rcp_process_edit_customer() {

	if ( ! wp_verify_nonce( $_POST['rcp_edit_member_nonce'], 'rcp_edit_member_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_POST['customer_id'] ) ) {
		wp_die( __( 'Invalid customer ID.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$customer_id = absint( $_POST['customer_id'] );
	$customer    = rcp_get_customer( $customer_id );

	if ( empty( $customer ) ) {
		wp_die( __( 'Invalid customer.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$user         = get_userdata( $customer->get_user_id() );
	$current_user = wp_get_current_user();

	rcp_log( sprintf( '%s has started editing customer #%d.', $current_user->display_name, $customer_id ) );

	/**
	 * Maybe update user account record.
	 */
	$user_args  = array();
	$first_name = ! empty( $_POST['first_name'] ) ? $_POST['first_name'] : '';
	$last_name  = ! empty( $_POST['last_name'] ) ? $_POST['last_name'] : '';
	$email      = ! empty( $_POST['user_email'] ) ? $_POST['user_email'] : '';

	if ( $user->first_name != $first_name ) {
		$user_args['first_name'] = sanitize_text_field( $first_name );
	}

	if ( $user->last_name != $last_name ) {
		$user_args['last_name'] = sanitize_text_field( $last_name );
	}

	if ( $user->user_email != $email && is_email( $email ) ) {
		$user_args['user_email'] = sanitize_text_field( $email );

		rcp_log( sprintf( 'Changing email for user account #%d.', $user->ID ) );
	}

	$display_name = trim( $first_name . ' ' . $last_name );
	if ( empty( $display_name ) ) {
		$display_name = $user->user_login;
	}
	$user_args['display_name'] = sanitize_text_field( trim( $display_name ) );

	if ( ! empty( $user_args ) ) {
		$user_args['ID'] = $user->ID;

		wp_update_user( $user_args );
	}

	/**
	 * @deprecated 3.0 Use `rcp_edit_customer` instead.
	 */
	do_action( 'rcp_edit_member', $customer->get_user_id() );

	/**
	 * Triggers when a customer is edited.
	 *
	 * @param RCP_Customer $customer Customer object.
	 *
	 * @since 3.0
	 */
	do_action( 'rcp_edit_customer', $customer );

	$redirect = add_query_arg( 'rcp_message', 'user_updated', rcp_get_customers_admin_page( array(
		'customer_id' => urlencode( $customer_id ),
		'view'        => 'edit'
	) ) );
	wp_safe_redirect( $redirect );
	exit;

}

add_action( 'rcp_action_edit_customer', 'rcp_process_edit_customer' );

/**
 * Process deleting a customer.
 *
 * @since 3.0
 * @return void
 */
function rcp_process_delete_customer() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp_delete_customer' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_GET['customer_id'] ) ) {
		wp_die( __( 'Invalid customer ID.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$current_user = wp_get_current_user();

	rcp_log( sprintf( '%s is deleting customer #%d.', $current_user->user_login, absint( $_GET['customer_id'] ) ) );

	$deleted = rcp_delete_customer( absint( $_GET['customer_id'] ) );

	if ( ! $deleted ) {
		wp_die( __( 'Failed to delete customer.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$redirect = add_query_arg( 'rcp_message', 'customer_deleted', rcp_get_customers_admin_page() );
	wp_safe_redirect( $redirect );
	exit;

}

add_action( 'rcp_action_delete_customer', 'rcp_process_delete_customer' );

/**
 * Process adding a new note to the customer.
 *
 * @todo  ajaxify
 *
 * @since 3.0
 * @return void
 */
function rcp_process_add_customer_note() {


	if ( ! wp_verify_nonce( $_POST['rcp_add_customer_note_nonce'], 'rcp_add_customer_note' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_POST['customer_id'] ) ) {
		wp_die( __( 'Invalid customer ID.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$customer_id = absint( $_POST['customer_id'] );
	$customer    = rcp_get_customer( $customer_id );

	if ( empty( $customer ) ) {
		wp_die( __( 'Invalid customer.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$new_note = wp_unslash( $_POST['new_note'] );

	if ( empty( $new_note ) ) {
		wp_die( __( 'Please enter a note.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$current_user = wp_get_current_user();

	rcp_log( sprintf( '%s is adding a new note to customer #%d.', $current_user->user_login, $customer_id ) );

	$customer->add_note( sanitize_text_field( $new_note ) );

	$redirect = add_query_arg( 'rcp_message', 'customer_note_added', rcp_get_customers_admin_page( array( 'customer_id' => urlencode( $customer_id ), 'view' => 'edit' ) ) );
	wp_safe_redirect( $redirect );
	exit;

}

add_action( 'rcp_action_add_customer_note', 'rcp_process_add_customer_note' );
