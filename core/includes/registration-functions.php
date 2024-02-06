<?php
/**
 * Registration Functions
 *
 * Processes the registration form
 *
 * @package     Restrict Content Pro
 * @subpackage  Registration Functions
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
use RCP\Membership_Level;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Register a new user
 *
 * @access public
 * @since  1.0
 * @return void
 */
function rcp_process_registration() {

	// check nonce
	if ( ! ( isset( $_POST['rcp_register_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rcp_register_nonce'] ) ), 'rcp-register-nonce' ) ) ) {
		rcp_errors()->add( 'invalid_nonce', __( 'An authentication error occurred. Please try again.', 'rcp' ), 'register' );

		wp_send_json_error(
			array(
				'success' => false,
				'errors'  => rcp_get_error_messages_html( 'register' ),
				'nonce'   => wp_create_nonce( 'rcp-register-nonce' ),
			)
		);
	}

	global $rcp_options;

	$membership_level = rcp_get_membership_level( rcp_get_registration()->get_membership_level_id() );
		// We are already sanitizing, but PHPCS keep complaining about the isset function.
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$discount       = isset( $_POST['rcp_discount'] ) ? sanitize_text_field( strtolower( wp_unslash( $_POST['rcp_discount'] ) ) ) : '';
	$price          = number_format( $membership_level->get_price(), 2, '.', '' );
	$initial_amount = rcp_get_registration()->get_total();
	$auto_renew     = rcp_registration_is_recurring();
	// if both today's total and the recurring total are 0, the there is a full discount
	// if this is not a recurring membership only check today's total
	$full_discount     = ( $auto_renew ) ? ( rcp_get_registration()->get_total() == 0 && rcp_get_registration()->get_recurring_total() == 0 ) : ( rcp_get_registration()->get_total() == 0 );
	$customer          = rcp_get_customer_by_user_id();
	$has_trialed       = ! empty( $customer ) ? $customer->has_trialed() : false;
	$registration_type = rcp_get_registration()->get_registration_type(); // Whether this is a `new` membership, `renewal`, `upgrade`, or `downgrade`.
	$recovered_payment = rcp_get_registration()->get_recovered_payment();

	// get the selected payment method/gateway
	if ( ! isset( $_POST['rcp_gateway'] ) ) {
		$gateway = 'paypal';
	} else {
		$gateway = sanitize_text_field( wp_unslash( $_POST['rcp_gateway'] ) );
	}

	// Change gateway to "free" if this membership doesn't require payment.
	if ( empty( $initial_amount ) && ! $auto_renew ) {
		$gateway = 'free';
	}

	rcp_log( sprintf( 'Started new registration for membership level #%d via %s.', $membership_level->get_id(), $gateway ) );

	/***********************
	 * validate the form
	 */

	do_action( 'rcp_before_form_errors', $_POST, $customer );

	$validate_only = isset( $_POST['validate_only'] );

	$user_data = rcp_validate_user_data();

	if ( ! rcp_is_registration() ) {
		// no membership level was chosen
		rcp_errors()->add( 'no_level', __( 'Please choose a membership level', 'rcp' ), 'register' );
	}

	if ( $membership_level->is_free() && ! $membership_level->is_lifetime() && $has_trialed ) {
		// this ensures that users only sign up for a free trial once
		rcp_errors()->add( 'free_trial_used', __( 'You may only sign up for a free trial once', 'rcp' ), 'register' );
	}

	if ( ! empty( $discount ) ) {

		// make sure we have a valid discount
		if ( rcp_validate_discount( $discount, $membership_level->get_id() ) ) {

			// check if the user has already used this discount
			if ( $price > 0 && ! $user_data['need_new'] && rcp_user_has_used_discount( $user_data['id'], $discount ) && apply_filters( 'rcp_discounts_once_per_user', false, $discount, $membership_level->get_id() ) ) {
				rcp_errors()->add( 'discount_already_used', __( 'You can only use the discount code once', 'rcp' ), 'register' );
			}
		} else {
			// the entered discount code is incorrect
			rcp_errors()->add( 'invalid_discount', __( 'The discount you entered is invalid', 'rcp' ), 'register' );
		}
	}

	// Validate extra fields in gateways with the 2.1+ gateway API
	if ( ! has_action( 'rcp_gateway_' . $gateway ) && $price > 0 && ! $full_discount ) {

		$gateways    = new RCP_Payment_Gateways();
		$gateway_var = $gateways->get_gateway( $gateway );
		$gateway_obj = new $gateway_var['class']();
		$gateway_obj->validate_fields();
	}

	// If enabled, terms agreement must be checked.
	if ( ! empty( $rcp_options['enable_terms'] ) && ! isset( $_POST['rcp_agree_to_terms'] ) ) {
		rcp_errors()->add( 'terms_not_agreed', __( 'You must agree to the terms and conditions', 'rcp' ), 'register' );
	}

	// If enabled, privacy policy agreement must be checked.
	if ( ! empty( $rcp_options['enable_privacy_policy'] ) && ! isset( $_POST['rcp_agree_to_privacy_policy'] ) ) {
		rcp_errors()->add( 'privacy_policy_not_agreed', __( 'You must agree to the privacy policy', 'rcp' ), 'register' );
	}

	do_action( 'rcp_form_errors', $_POST, $customer );

	// retrieve all error messages, if any
	$errors = rcp_errors()->get_error_messages();

	if ( ! empty( $errors ) ) {
		rcp_log( sprintf( 'Registration cancelled with the following errors: %s.', implode( ', ', $errors ) ) );

		wp_send_json_error(
			array(
				'success' => false,
				'errors'  => rcp_get_error_messages_html( 'register' ),
				'nonce'   => wp_create_nonce( 'rcp-register-nonce' ),
				'gateway' => array(
					'slug'     => $gateway,
					'supports' => ! empty( $gateway_obj->supports ) ? $gateway_obj->supports : false,
				),
			)
		);

	} elseif ( $validate_only ) {
		wp_send_json_success(
			array(
				'success'         => true,
				'nonce'           => wp_create_nonce( 'rcp-register-nonce' ),
				'total'           => rcp_get_registration()->get_total(),
				'total_formatted' => rcp_currency_filter( rcp_get_registration()->get_total() ),
				'recurring_total' => rcp_get_registration()->get_recurring_total(),
				'auto_renew'      => $auto_renew,
				'gateway'         => array(
					'slug'     => $gateway,
					'supports' => ! empty( $gateway_obj->supports ) ? $gateway_obj->supports : false,
				),
				'level'           => array(
					'id'          => $membership_level->get_id(),
					'name'        => $membership_level->get_name(),
					'description' => $membership_level->get_description(),
					'trial'       => $membership_level->has_trial(),
				),
			)
		);

	}

	if ( $user_data['need_new'] ) {
		$display_name = trim( $user_data['first_name'] . ' ' . $user_data['last_name'] );

		$user_data['id'] = wp_insert_user(
			array(
				'user_login'      => $user_data['login'],
				'user_pass'       => $user_data['password'],
				'user_email'      => $user_data['email'],
				'first_name'      => $user_data['first_name'],
				'last_name'       => $user_data['last_name'],
				'display_name'    => ! empty( $display_name ) ? $display_name : $user_data['login'],
				'user_registered' => date( 'Y-m-d H:i:s' ),
			)
		);

		if ( ! is_wp_error( $user_data['id'] ) ) {
			wp_set_auth_cookie( $user_data['id'] );
			wp_set_current_user( $user_data['id'] );

			if ( ! isset( $rcp_options['disable_new_user_notices'] ) ) {
				// Send an email to the admin alerting them of the registration.
				wp_new_user_notification( absint( $user_data['id'] ) );

			}
		}
	}

	if ( is_wp_error( $user_data['id'] ) ) {

		rcp_errors()->add( $user_data['id']->get_error_code(), $user_data['id']->get_error_message(), 'register' );

	} elseif ( ! empty( $user_data['id'] ) && ! is_wp_error( $user_data['id'] ) && empty( $customer ) ) {

		// Create the customer record if it doesn't already exist.
		$customer_id = rcp_add_customer( array( 'user_id' => absint( $user_data['id'] ) ) );
		$customer    = ! empty( $customer_id ) ? rcp_get_customer( $customer_id ) : false;

		if ( empty( $customer ) ) {
			rcp_errors()->add( 'customer_creation_failed', __( 'Failed to create customer record', 'rcp' ), 'register' );
		}
	}

	// Refresh error messages to account for any user creation errors.
	$errors = rcp_errors()->get_error_messages();

	if ( ! empty( $errors ) ) {
		rcp_log( sprintf( 'Registration cancelled with the following errors: %s.', implode( ', ', $errors ) ) );

		wp_send_json_error(
			array(
				'success' => false,
				'errors'  => rcp_get_error_messages_html( 'register' ),
				'nonce'   => wp_create_nonce( 'rcp-register-nonce' ),
				'gateway' => array(
					'slug'     => $gateway,
					'supports' => ! empty( $gateway_obj->supports ) ? $gateway_obj->supports : false,
				),
			)
		);
	}

	$user_id = $user_data['id'];

	// Save agreement to terms and privacy policy.
	if ( ! empty( $_POST['rcp_agree_to_terms'] ) ) {
		$terms_agreed = get_user_meta( $user_id, 'rcp_terms_agreed', true );

		if ( ! is_array( $terms_agreed ) ) {
			$terms_agreed = array();
		}

		$terms_agreed[] = current_time( 'timestamp' );

		update_user_meta( $user_id, 'rcp_terms_agreed', $terms_agreed );
	}
	if ( ! empty( $_POST['rcp_agree_to_privacy_policy'] ) ) {
		$privacy_policy_agreed = get_user_meta( $user_id, 'rcp_privacy_policy_agreed', true );

		if ( ! is_array( $privacy_policy_agreed ) ) {
			$privacy_policy_agreed = array();
		}

		$privacy_policy_agreed[] = current_time( 'timestamp' );

		update_user_meta( $user_id, 'rcp_privacy_policy_agreed', $privacy_policy_agreed );
	}

	update_user_meta( $user_id, '_rcp_new_subscription', '1' );

	$subscription_key = rcp_generate_subscription_key();

	// If we're recovering a payment, then let's recover the associated membership as well.
	if ( ! empty( $recovered_payment ) && ! empty( $recovered_payment->membership_id ) && $recovered_payment->object_id == $membership_level->get_id() ) {
		$previous_membership = rcp_get_membership( $recovered_payment->membership_id );

		// Re-enable this membership if it's been disabled.
		if ( $previous_membership->is_disabled() ) {
			$previous_membership->enable();
		}

		rcp_log( sprintf( 'Using recovered membership #%d for registration.', $previous_membership->get_id() ) );
	} else {
		$previous_membership = rcp_get_registration()->get_membership();
	}
	$previous_membership_level = ! empty( $previous_membership ) ? rcp_get_membership_level( $previous_membership->get_object_id() ) : false;

	if ( $previous_membership instanceof Membership_Level ) {
		// Backwards compat.
		update_user_meta( $user_id, '_rcp_old_subscription_id', $previous_membership->get_object_id() );

		// If the current user isn't the "owner" of the previous membership, something is wrong...
		if ( $previous_membership->get_user_id() != get_current_user_id() ) {
			wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
		}

		// If this is an upgrade/downgrade but the membership isn't allowed to be changed, something is wrong...
		if ( in_array( $registration_type, array( 'upgrade', 'downgrade' ) ) && ! $previous_membership->upgrade_possible() ) {
			wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
		}

		// If this is a renewal, but the membership isn't allowed to be renewed, something is wrong...
		if ( 'renewal' == $registration_type && ! $previous_membership->can_renew() ) {
			wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
		}
	}

	rcp_log( sprintf( 'Registration type: %s.', $registration_type ) );

	// Delete pending payment ID. A new one may be created for paid membership.
	delete_user_meta( $user_id, 'rcp_pending_payment_id' );

	// Delete old pending data that may have been added in previous versions.
	delete_user_meta( $user_id, 'rcp_pending_expiration_date' );
	delete_user_meta( $user_id, 'rcp_pending_subscription_key' );

	// Backwards compatibility pre-2.9: set pending subscription key.
	update_user_meta( $user_id, 'rcp_pending_subscription_key', $subscription_key );

	// Backwards compatibility: we still need to set this for Hard-set Expiration dates.
	update_user_meta( $user_id, 'rcp_pending_subscription_level', $membership_level->get_id() );

	$amount = ( $membership_level->has_trial() && ! $has_trialed ) ? 0.00 : rcp_get_registration()->get_total();

	/**
	 * Create a pending membership if this is not a manual renewal.
	 */
	if ( 'renewal' != $registration_type && empty( $recovered_payment ) ) {
		$membership_data = array(
			'customer_id'      => $customer->get_id(),
			'object_id'        => $membership_level->get_id(),
			'object_type'      => 'membership',
			'initial_amount'   => $amount,
			'recurring_amount' => $membership_level->is_lifetime() ? 0.00 : rcp_get_registration()->get_recurring_total( true, true ),
			'auto_renew'       => $auto_renew,
			'maximum_renewals' => $membership_level->get_maximum_renewals(),
			'status'           => 'pending',
			'gateway'          => $gateway,
			'subscription_key' => $subscription_key,
		);

		if ( in_array( $registration_type, array( 'upgrade', 'downgrade' ) ) && ! empty( $previous_membership ) ) {
			$membership_data['upgraded_from'] = $previous_membership->get_id();
		}

		$membership_id = $customer->add_membership( $membership_data );

		if ( in_array( $registration_type, array( 'upgrade', 'downgrade' ) ) ) {
			$membership = rcp_get_membership( $membership_id );
			$membership->add_note( sprintf( __( 'Upgraded from %1$s (membership #%2$d).', 'rcp' ), $previous_membership_level->get_name(), $previous_membership->get_id() ) );
		}
	} elseif ( ! empty( $previous_membership ) ) {
		/**
		 * Use the existing membership record if this is a renewal and we have a previous membership to work with.
		 */
		$membership_id = $previous_membership->get_id();

		$new_membership_data = array(
			'auto_renew'       => $auto_renew,
			'gateway'          => $gateway,
			'recurring_amount' => $membership_level->is_lifetime() ? 0.00 : rcp_get_registration()->get_recurring_total( true, true ),
		);

		if ( $auto_renew || ! $previous_membership->get_subscription_key() ) {
			// Use new generated subscription key if auto renewing, or if the existing subscription key is blank.
			$new_membership_data['subscription_key'] = $subscription_key;
		} else {
			// Keep the same subscription key if not auto renew.
			$subscription_key = $previous_membership->get_subscription_key();
		}

		if ( ! $previous_membership->is_active() ) {
			$new_membership_data['status']          = 'pending';
			$new_membership_data['expiration_date'] = $previous_membership->calculate_expiration( true, ! $has_trialed );

			// Maybe re-calculate trialing period.
			if ( $membership_level->has_trial() && ! $has_trialed ) {
				$new_membership_data['trial_end_date'] = $new_membership_data['expiration_date'];
			}
		}

		rcp_update_membership( $membership_id, $new_membership_data );

		rcp_log( sprintf( 'Using existing membership #%d for payment.', $membership_id ) );
	}

	if ( rcp_get_registration()->get_total() == 0 && rcp_get_registration()->get_recurring_total() == 0 && ! empty( $discount ) && '2' != rcp_get_auto_renew_behavior() ) {
		rcp_update_membership( $membership_id, array( 'expiration_date' => null ) );
	}

	// Create a pending payment
	$credits = ! empty( $previous_membership ) && in_array( $registration_type, array( 'upgrade', 'downgrade' ) ) ? $previous_membership->get_prorate_credit_amount() : 0;

	$payment_data = array(
		'date'             => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
		'subscription'     => $membership_level->get_name(),
		'object_id'        => $membership_level->get_id(),
		'object_type'      => 'subscription',
		'gateway'          => $gateway,
		'subscription_key' => $subscription_key,
		'amount'           => $amount,
		'user_id'          => $user_id,
		'customer_id'      => $customer->get_id(),
		'membership_id'    => $membership_id,
		'status'           => 'pending',
		'subtotal'         => $membership_level->get_price(),
		'credits'          => $credits,
		'fees'             => rcp_get_registration()->get_total_fees() + $credits,
		'discount_amount'  => rcp_get_registration()->get_total_discounts(),
		'discount_code'    => $discount,
		'transaction_type' => $registration_type,
	);

	$rcp_payments = new RCP_Payments();

	if ( ! empty( $recovered_payment ) ) {
		// Update the recovered payment.
		rcp_log( sprintf( 'Updating recovered payment #%d with new data.', $recovered_payment->id ) );
		$rcp_payments->update( $recovered_payment->id, $payment_data );
		$payment_id = $recovered_payment->id;
	} else {
		// Insert a new payment record.
		$payment_id = $rcp_payments->insert( $payment_data );
	}

	// Store the pending payment ID. This is so we know which payment is responsible for activating the membership.
	rcp_update_membership_meta( $membership_id, 'pending_payment_id', $payment_id );

	// This is for backwards compatibility only. We now use membership meta (see above).
	update_user_meta( $user_data['id'], 'rcp_pending_payment_id', $payment_id );

	if ( in_array( $registration_type, array( 'upgrade', 'downgrade' ) ) ) {
		// Flag the member as having just upgraded
		update_user_meta( $user_id, '_rcp_just_upgraded', current_time( 'timestamp' ) );
	}

	/**
	 * Triggers after all the form data has been processed, but before the user is sent to the payment gateway.
	 * The user's membership is pending at this point.
	 *
	 * @param array                $_POST               Posted data.
	 * @param int                  $user_id             ID of the user registering.
	 * @param float                $price               Price of the membership.
	 * @param int                  $payment_id          ID of the pending payment associated with this registration.
	 * @param RCP_Customer         $customer            Customer object.
	 * @param int                  $membership_id       ID of the new pending membership.
	 * @param RCP_Membership|false $previous_membership Previous membership object, or false if none.
	 * @param string               $registration_type   Type of registration: 'new', 'renewal', or 'upgrade'.
	 */
	do_action( 'rcp_form_processing', $_POST, $user_id, $price, $payment_id, $customer, $membership_id, $previous_membership, $registration_type );

	$success_data = array(
		'success'         => true,
		'nonce'           => wp_create_nonce( 'rcp-register-nonce' ),
		'total'           => rcp_get_registration()->get_total(),
		'recurring_total' => rcp_get_registration()->get_recurring_total(),
		'auto_renew'      => $auto_renew,
		'gateway'         => array(
			'slug'     => $gateway,
			'supports' => ! empty( $gateway_obj->supports ) ? $gateway_obj->supports : false,
		),
		'level'           => array(
			'trial' => $membership_level->has_trial(),
		),
		'payment_id'      => $payment_id,
	);

	// Handle gateway ajax processing.
	if ( 'free' != $gateway && rcp_gateway_supports( $gateway, 'ajax-payment' ) ) {

		// send all of the membership data off for processing by the gateway
		$result = rcp_handle_gateway_ajax_processing( $gateway, rcp_get_payment_registration_details( $payment_id ) );

		if ( is_wp_error( $result ) ) {
			rcp_errors()->add( $result->get_error_code(), $result->get_error_message(), 'register' );

			wp_send_json_error(
				array(
					'success' => false,
					'errors'  => rcp_get_error_messages_html( 'register' ),
					'nonce'   => wp_create_nonce( 'rcp-register-nonce' ),
					'gateway' => array(
						'slug'     => $gateway,
						'supports' => ! empty( $gateway_obj->supports ) ? $gateway_obj->supports : false,
					),
				)
			);
		} elseif ( is_array( $result ) ) {
			$success_data['gateway']['data'] = $result;
		}
	}

	wp_send_json_success( $success_data );
	exit;

}

add_action( 'wp_ajax_rcp_process_register_form', 'rcp_process_registration', 100 );
add_action( 'wp_ajax_nopriv_rcp_process_register_form', 'rcp_process_registration', 100 );

/**
 * Generate a new nonce for registration
 *
 * We have to do this via ajax after the form processing step because we log in new users, which means
 * nonce generations done in the same request after log in will fail (due to the new logged in cookies not
 * being properly set in PHP yet). We have to do a whole new request to get a properly validated nonce.
 *
 * Annoying, but it's the price we pay for not doing a full page refresh.
 *
 * @since 3.2
 * @return void
 */
function rcp_generate_registration_nonce() {
	wp_send_json_success( wp_create_nonce( 'rcp-register-nonce' ) );
}
add_action( 'wp_ajax_rcp_generate_registration_nonce', 'rcp_generate_registration_nonce', 100 );
add_action( 'wp_ajax_nopriv_rcp_generate_registration_nonce', 'rcp_generate_registration_nonce', 100 );

/**
 * Send registration payment to the gateway for processing.
 *
 * @since 3.2
 * @return void
 */
function rcp_send_registration_to_gateway() {

	// Don't listen if we're doing ajax processes.
	if ( ! empty( $_POST['rcp_ajax'] ) ) {
		return;
	}

	// Check nonce
	if ( ! ( isset( $_POST['rcp_register_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rcp_register_nonce'] ) ), 'rcp-register-nonce' ) ) ) {
		return;
	}

	if ( empty( $_POST['rcp_registration_payment_id'] ) ) {
		rcp_errors()->add( 'missing_payment_id', __( 'Missing payment ID.', 'rcp' ), 'register' );

		return;
	}

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	$payment = $rcp_payments_db->get_payment( absint( sanitize_text_field( wp_unslash( $_POST['rcp_registration_payment_id'] ) ) ) );

	if ( empty( $payment ) ) {
		rcp_errors()->add( 'invalid_payment', __( 'Invalid payment. Please try again.', 'rcp' ), 'register' );

		return;
	}

	// Process a paid membership.
	if ( 'free' != $payment->gateway ) {

		// Send all of the membership data off for processing by the gateway.
		rcp_send_to_gateway( $payment->gateway, rcp_get_payment_registration_details( $payment->id ) );

	} else {
		// Process a free or trial membership.

		// Complete payment. This also activates the membership.
		$rcp_payments_db->update( $payment->id, array( 'status' => 'complete' ) );

		rcp_log( sprintf( 'Completed free registration to membership level #%d for customer #%d.', $payment->object_id, $payment->customer_id ) );

		// send the newly created user to the redirect page after logging them in
		wp_redirect( rcp_get_return_url( absint( $payment->user_id ) ) );
		exit;

	}

}
add_action( 'init', 'rcp_send_registration_to_gateway', 100 );

/**
 * Get all the registration information associated with a payment. This is used when sending the
 * payment to the gateway for processing.
 *
 * @param int $payment_id Payment ID.
 *
 * @since 3.2
 * @return array
 */
function rcp_get_payment_registration_details( $payment_id ) {

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	$payment = $rcp_payments_db->get_payment( absint( $payment_id ) );

	if ( empty( $payment ) ) {
		return array();
	}

	$user             = get_userdata( absint( $payment->user_id ) );
	$membership_level = rcp_get_membership_level( $payment->object_id );
	$customer         = rcp_get_customer( $payment->customer_id );

	/*
	 * At this point we can't know for sure if this was a brand new user, so we will assume they were if their account was created today.
	 */
	$new_user = date( 'Y-m-d', strtotime( $user->user_registered, current_time( 'timestamp' ) ) ) === date( 'Y-m-d', current_time( 'timestamp' ) );

	$registration_data = array(
		'price'                   => rcp_get_registration()->get_total( true, false ), // get total without the fee
		'initial_price'           => rcp_get_registration()->get_total( true, true ),
		'recurring_price'         => rcp_get_registration()->get_recurring_total( true, true ),
		'discount'                => rcp_get_registration()->get_total_discounts(),
		'discount_code'           => ! empty( $payment->discount_code ) ? $payment->discount_code : '',
		'fee'                     => rcp_get_registration()->get_total_fees(),
		'length'                  => $membership_level instanceof Membership_Level ? $membership_level->get_duration() : 0,
		'length_unit'             => $membership_level instanceof Membership_Level ? strtolower( $membership_level->get_duration_unit() ) : 'day',
		'subscription_id'         => $membership_level instanceof Membership_Level ? $membership_level->get_id() : 0,
		'subscription_name'       => $membership_level instanceof Membership_Level ? $membership_level->get_name() : '',
		'key'                     => $payment->subscription_key,
		'user_id'                 => $user->ID,
		'user_name'               => $user->user_login,
		'user_email'              => $user->user_email,
		'currency'                => rcp_get_currency(),
		'auto_renew'              => rcp_registration_is_recurring(),
		'return_url'              => rcp_get_return_url( $user->ID ),
		'new_user'                => $new_user,
		'trial_duration'          => $membership_level instanceof Membership_Level ? $membership_level->get_trial_duration() : 0,
		'trial_duration_unit'     => $membership_level instanceof Membership_Level ? $membership_level->get_trial_duration_unit() : 'day',
		'trial_eligible'          => ! $customer->has_trialed(),
		'post_data'               => $_POST,
		'payment_id'              => $payment_id,
		'membership_id'           => absint( $payment->membership_id ),
		'customer'                => $customer,
		'subscription_start_date' => '', // Empty means it starts today.
	);

	// if giving the user a credit, make sure the credit does not exceed the first payment
	if ( $registration_data['fee'] < 0 && abs( $registration_data['fee'] ) > $registration_data['price'] ) {
		$registration_data['fee'] = -1 * $registration_data['price'];
	}

	// Add prorate credit length to extend the subscription start date.
	$prorate_credit_length = 0;
	if ( 'upgrade' === rcp_get_registration()->get_registration_type() ) {
		$previous_membership = rcp_get_registration()->get_membership();

		$is_free = $membership_level instanceof Membership_Level ? $membership_level->is_free() : false;

		if (
			$previous_membership
			&& ! $is_free
			&& $membership_level instanceof Membership_Level
		) {
			// Get prorate credit in units based on the new membership level price.
			$total_credits         = $previous_membership->get_prorate_credit_amount() - $membership_level->get_fee();
			$prorate_credit_length = floor( $total_credits / $membership_level->get_price() );

			// Make sure the prorate credit length is not negative.
			if ( $prorate_credit_length <= 0 ) {
				$prorate_credit_length = 0;
			}
		}
	}

	/*
	 * Delay the subscription start date if this is a free trial OR the amount due today is $0,
	 * the recurring amount is greater than $0, and auto renew is enabled.
	 */
	if ( $registration_data['trial_eligible'] && ! empty( $registration_data['trial_duration'] ) ) {
		$duration = $registration_data['trial_duration'] + $prorate_credit_length;

		// Subscription start date is the end of the trial.
		$registration_data['subscription_start_date'] = date( 'Y-m-d H:i:s', strtotime( $duration . ' ' . $registration_data['trial_duration_unit'], current_time( 'timestamp' ) ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		// Set the amount due today to $0.
		$registration_data['price'] = 0;
		$registration_data['fee']   = 0;
	} elseif ( empty( $registration_data['price'] + $registration_data['fee'] ) && ! empty( $registration_data['recurring_price'] ) && rcp_registration_is_recurring() ) {
		$duration = $registration_data['length'] + $prorate_credit_length;

		// Start date is delayed due to discounts, negative signup fees, or credits.
		$registration_data['subscription_start_date'] = date( 'Y-m-d H:i:s', strtotime( $duration . ' ' . $registration_data['length_unit'], current_time( 'timestamp' ) ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
	}

	update_user_meta( $user->ID, 'rcp_pending_subscription_amount', round( $registration_data['price'] + $registration_data['fee'], 2 ) );

	/**
	 * Filters the registration data.
	 *
	 * @param array $registration_data
	 */
	return apply_filters( 'rcp_subscription_data', $registration_data );

}

/**
 * Provide the default registration values when checking out with Stripe Checkout.
 *
 * @return void
 */
function rcp_handle_stripe_checkout() {

	if ( empty( $_POST['rcp_stripe_checkout'] ) || empty( $_POST['rcp_gateway'] ) || empty( $_POST['rcp_user_email'] ) || 'stripe' !== $_POST['rcp_gateway'] ) {
		return;
	}

	if ( empty( $_POST['rcp_user_login'] ) ) {
		$_POST['rcp_user_login'] = sanitize_text_field( wp_unslash( $_POST['rcp_user_email'] ) );
	}

	if ( empty( $_POST['rcp_user_first'] ) ) {
		$user_email              = explode( '@', sanitize_text_field( wp_unslash( $_POST['rcp_user_email'] ) ) );
		$_POST['rcp_user_first'] = $user_email[0];
	}

	if ( empty( $_POST['rcp_user_last'] ) ) {
		$_POST['rcp_user_last'] = '';
	}

	if ( empty( $_POST['rcp_user_pass'] ) ) {
		$_POST['rcp_user_pass'] = wp_generate_password();
	}

	if ( empty( $_POST['rcp_user_pass_confirm'] ) ) {
		$_POST['rcp_user_pass_confirm'] = sanitize_text_field( wp_unslash( $_POST['rcp_user_pass'] ) );
	}

}

add_action( 'rcp_before_form_errors', 'rcp_handle_stripe_checkout' );


/**
 * Validate and set up the user data for registration
 *
 * @access      public
 * @since       1.5
 * @return      array
 */
function rcp_validate_user_data() {

	$user = array();

	if ( ! is_user_logged_in() ) {
		$user['id']               = 0;
		$user['login']            = sanitize_text_field( wp_unslash( isset( $_POST['rcp_user_login'] ) ? $_POST['rcp_user_login'] : '' ) );
		$user['email']            = sanitize_text_field( wp_unslash( isset( $_POST['rcp_user_email'] ) ? $_POST['rcp_user_email'] : '' ) );
		$user['first_name']       = sanitize_text_field( wp_unslash( isset( $_POST['rcp_user_first'] ) ? $_POST['rcp_user_first'] : '' ) );
		$user['last_name']        = sanitize_text_field( wp_unslash( isset( $_POST['rcp_user_last'] ) ? $_POST['rcp_user_last'] : '' ) );
		$user['password']         = sanitize_text_field( wp_unslash( isset( $_POST['rcp_user_pass'] ) ? $_POST['rcp_user_pass'] : '' ) );
		$user['password_confirm'] = sanitize_text_field( wp_unslash( isset( $_POST['rcp_user_pass_confirm'] ) ? $_POST['rcp_user_pass_confirm'] : '' ) );
		$user['need_new']         = true;
	} else {
		$userdata         = get_userdata( get_current_user_id() );
		$user['id']       = $userdata->ID;
		$user['login']    = $userdata->user_login;
		$user['email']    = $userdata->user_email;
		$user['need_new'] = false;
	}

	if ( $user['need_new'] ) {
		if ( username_exists( $user['login'] ) ) {
			// Username already registered
			rcp_errors()->add(
				'username_unavailable',
				sprintf(
					__( 'This username is already in use. If this is your username, please <a href="%s">log in</a> and try again.', 'rcp' ),
					esc_url( rcp_get_login_url() )
				),
				'register'
			);
		}
		if ( ! rcp_validate_username( $user['login'] ) ) {
			// invalid username
			rcp_errors()->add( 'username_invalid', __( 'Invalid username', 'rcp' ), 'register' );
		}
		if ( empty( $user['login'] ) ) {
			// empty username
			rcp_errors()->add( 'username_empty', __( 'Please enter a username', 'rcp' ), 'register' );
		}
		if ( ! is_email( $user['email'] ) ) {
			// invalid email
			rcp_errors()->add( 'email_invalid', __( 'Invalid email', 'rcp' ), 'register' );
		}
		if ( email_exists( $user['email'] ) ) {
			// Email address already registered
			rcp_errors()->add(
				'email_used',
				sprintf(
					__( 'This email address is already in use. If this is your email address, please <a href="%s">log in</a> and try again.', 'rcp' ),
					esc_url( rcp_get_login_url() )
				),
				'register'
			);
		}
		if ( empty( $user['password'] ) ) {
			// passwords do not match
			rcp_errors()->add( 'password_empty', __( 'Please enter a password', 'rcp' ), 'register' );
		}
		if ( $user['password'] !== $user['password_confirm'] ) {
			// passwords do not match
			rcp_errors()->add( 'password_mismatch', __( 'Passwords do not match', 'rcp' ), 'register' );
		}
	}

	return apply_filters( 'rcp_user_registration_data', $user );
}


/**
 * Get the registration success/return URL
 *
 * @param       $user_id int The user ID we have just registered
 *
 * @access      public
 * @since       1.5
 * @since       3.5.8 Looking inside the $_POST for the content_upgrade_redirect block
 * @return      string
 */
function rcp_get_return_url( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	// Redirect coming from Content Upgrade Redirect block
	if ( ! empty( $_POST['rcp_redirect'] ) ) {
		$validated_rcp_redirect = wp_validate_redirect( sanitize_text_field( wp_unslash( $_POST['rcp_redirect'] ) ) );
		if ( ! empty( $validated_rcp_redirect ) ) {
			return apply_filters( 'rcp_return_url', $validated_rcp_redirect, $user_id );
		}
	}

	global $rcp_options;

	if ( isset( $rcp_options['redirect'] ) ) {
		$redirect = get_permalink( $rcp_options['redirect'] );
	} else {
		$redirect = home_url();
	}
	return apply_filters( 'rcp_return_url', $redirect, $user_id );
}

/**
 * Determine if the current page is a registration page
 *
 * @access      public
 * @since       2.0
 * @return      bool
 */
function rcp_is_registration_page() {

	global $rcp_options, $post;

	$ret = false;

	if ( isset( $rcp_options['registration_page'] ) ) {
		$ret = is_page( $rcp_options['registration_page'] );
	}

	if ( ! empty( $post ) && has_shortcode( $post->post_content, 'register_form' ) ) {
		$ret = true;
	}

	return apply_filters( 'rcp_is_registration_page', $ret );
}

/**
 * Get the auto renew behavior
 *
 * 1 == All memberships auto renew
 * 2 == No memberships auto renew
 * 3 == Customer chooses whether to auto renew
 *
 * @access      public
 * @since       2.0
 * @return      int
 */
function rcp_get_auto_renew_behavior() {

	global $rcp_options, $rcp_level;

	// Check for old disable auto renew option
	if ( isset( $rcp_options['disable_auto_renew'] ) ) {
		$rcp_options['auto_renew'] = '2';
		unset( $rcp_options['disable_auto_renew'] );
		update_option( 'rcp_settings', $rcp_options );
	}

	$behavior = isset( $rcp_options['auto_renew'] ) ? $rcp_options['auto_renew'] : '3';

	if ( $rcp_level ) {
		$level = rcp_get_membership_level( $rcp_level );
		if ( $level instanceof Membership_Level && $level->is_free() ) {
			$behavior = '2';
		}
	}

	return apply_filters( 'rcp_auto_renew_behavior', $behavior );
}

/**
 * When new memberships are registered, a flag is set
 *
 * This removes the flag as late as possible so other systems can hook into
 * rcp_set_status and perform actions on new memberships
 *
 * @deprecated 3.0 Still here for backwards compatibility.
 *
 * @param string $status  User's membership status.
 * @param int    $user_id ID of the member.
 *
 * @access      public
 * @since       2.3.6
 * @return      void
 */
function rcp_remove_new_subscription_flag( $status, $user_id ) {

	if ( ! in_array( $status, array( 'active', 'free' ) ) ) {
		return;
	}

	delete_user_meta( $user_id, '_rcp_old_subscription_id' );
	delete_user_meta( $user_id, '_rcp_new_subscription' );
}

add_action( 'rcp_set_status', 'rcp_remove_new_subscription_flag', 9999999, 2 );

/**
 * When upgrading memberships, the new level / key are stored as pending. Once payment is received, the pending
 * values are set as the permanent values.
 *
 * See https://github.com/restrictcontentpro/restrict-content-pro/issues/294
 *
 * @deprecated 3.0 Still here for backwards compatibility.
 *
 * @param string     $status     User's membership status.
 * @param int        $user_id    ID of the user.
 * @param string     $old_status Previous membership status.
 * @param RCP_Member $member     Member object.
 *
 * @access      public
 * @since       2.4.3
 * @return      void
 */
function rcp_set_pending_subscription_on_upgrade( $status, $user_id, $old_status, $member ) {

	if ( 'active' !== $status ) {
		return;
	}

	$subscription_id  = get_user_meta( $user_id, 'rcp_pending_subscription_level', true );
	$subscription_key = get_user_meta( $user_id, 'rcp_pending_subscription_key', true );

	if ( ! empty( $subscription_id ) && ! empty( $subscription_key ) ) {

		delete_user_meta( $user_id, 'rcp_pending_subscription_level' );
		delete_user_meta( $user_id, 'rcp_pending_subscription_key' );

	}
}

add_action( 'rcp_set_status', 'rcp_set_pending_subscription_on_upgrade', 10, 4 );

/**
 * Determine if this registration is recurring
 *
 * @since 2.5
 * @return bool
 */
function rcp_registration_is_recurring() {

	$auto_renew = false;

	if ( '3' == rcp_get_auto_renew_behavior() ) {
		$auto_renew = ! empty( $_POST['rcp_auto_renew'] );
	}

	if ( '1' == rcp_get_auto_renew_behavior() ) {
		$auto_renew = true;
	}

	// make sure this gateway supports recurring payments
	if ( $auto_renew && ! empty( $_POST['rcp_gateway'] ) ) {
		$auto_renew = rcp_gateway_supports( sanitize_text_field( wp_unslash( $_POST['rcp_gateway'] ) ), 'recurring' );
	}

	if ( $auto_renew && ! empty( $_POST['rcp_level'] ) ) {
		$membership_level = rcp_get_membership_level( sanitize_text_field( wp_unslash( $_POST['rcp_level'] ) ) );

		if ( $membership_level instanceof Membership_Level ) {
			// check if this is an unlimited or free membership
			if ( $membership_level->is_lifetime() || $membership_level->is_free() ) {
				$auto_renew = false;
			}

			// Disable auto renew if this is a free trial but the gateway doesn't support them built into subscriptions.
			if ( $auto_renew && $membership_level->has_trial() ) {
				$customer       = rcp_get_customer();
				$trial_eligible = empty( $customer ) || ( ! empty( $customer ) && ! $customer->has_trialed() );

				if ( $trial_eligible && ! rcp_gateway_supports( sanitize_text_field( wp_unslash( $_POST['rcp_gateway'] ) ), 'trial' ) ) {
					$auto_renew = false;
				}
			}
		}
	}

	if ( ! rcp_get_registration_recurring_total() > 0 ) {
		$auto_renew = false;
	}

	return apply_filters( 'rcp_registration_is_recurring', $auto_renew );

}

/**
 * Add the registration total before the gateway fields
 *
 * @since 2.5
 * @return void
 */
function rcp_registration_total_field() {
	?>
	<div class="rcp_registration_total"></div>
	<?php
}

add_action( 'rcp_after_register_form_fields', 'rcp_registration_total_field' );

/**
 * Get formatted total for this registration
 *
 * @param bool $echo Whether or not to echo the value.
 *
 * @since      2.5
 * @return string|bool
 */
function rcp_registration_total( $echo = true ) {
	$total = rcp_get_registration_total();

	// the registration has not been setup yet
	if ( false === $total ) {
		return false;
	}

	if ( 0 < $total ) {
		$total = rcp_currency_filter( $total );
	} else {
		$total = __( 'free', 'rcp' );
	}

	$level = rcp_get_membership_level( rcp_get_registration()->get_membership_level_id() );

	if ( $level instanceof Membership_Level && $level->has_trial() && ! rcp_customer_has_trialed() ) {
		$total = sprintf(
			__( 'Free trial - %s', 'rcp' ),
			$level->get_trial_duration() . ' ' . rcp_filter_duration_unit( $level->get_trial_duration_unit(), $level->get_trial_duration() )
		);
	}

	$total = apply_filters( 'rcp_registration_total', $total );

	if ( $echo ) {
		echo $total;
	}

	return $total;
}

/**
 * Get the total for this registration
 *
 * @since  2.5
 * @return float|false
 */
function rcp_get_registration_total() {

	if ( ! rcp_is_registration() ) {
		return false;
	}

	return rcp_get_registration()->get_total();
}

/**
 * Get formatted recurring total for this registration
 *
 * @param bool $echo Whether or not to echo the value.
 *
 * @since  2.5
 * @return string|bool
 */
function rcp_registration_recurring_total( $echo = true ) {
	$total = rcp_get_registration_recurring_total();

	// the registration has not been setup yet
	if ( false === $total ) {
		return false;
	}

	if ( 0 < $total ) {
		$total            = rcp_currency_filter( $total );
		$membership_level = rcp_get_membership_level( rcp_get_registration()->get_membership_level_id() );

		if ( $membership_level instanceof Membership_Level ) {
			if ( $membership_level->get_duration() == 1 ) {
				$total .= '/' . rcp_filter_duration_unit( $membership_level->get_duration_unit(), 1 );
			} else {
				$total .= sprintf(
					__( ' every %1$s %2$s', 'rcp' ),
					$membership_level->get_duration(),
					rcp_filter_duration_unit( $membership_level->get_duration_unit(), $membership_level->get_duration() )
				);
			}
		}
	} else {
		$total = __( 'free', 'rcp' );

	}

	$total = apply_filters( 'rcp_registration_recurring_total', $total );

	if ( $echo ) {
		echo $total;
	}

	return $total;
}

/**
 * Get the recurring total payment
 *
 * @since 2.5
 * @return bool|int
 */
function rcp_get_registration_recurring_total() {

	if ( ! rcp_is_registration() ) {
		return false;
	}

	return rcp_get_registration()->get_recurring_total();
}

/**
 * Is the registration object setup?
 *
 * @since 2.5
 * @return bool
 */
function rcp_is_registration() {
	return (bool) rcp_get_registration()->get_membership_level_id();
}

/**
 * Get the registration object. If it hasn't been setup, setup an empty
 * registration object.
 *
 * @return RCP_Registration
 */
function rcp_get_registration() {
	global $rcp_registration;

	// setup empty registration object if one doesn't exist
	if ( ! is_a( $rcp_registration, 'RCP_Registration' ) ) {
		rcp_setup_registration();
	}

	return $rcp_registration;
}

/**
 * Setup the registration object
 *
 * Auto setup cart on page load if $_POST parameters are found
 *
 * @param int|null    $level_id ID of the membership level for this registration.
 * @param string|null $discount Discount code to apply to this registration.
 *
 * @since  2.5
 * @return void
 */
function rcp_setup_registration( $level_id = null, $discount = null ) {
	global $rcp_registration;

	$rcp_registration = new RCP_Registration( $level_id, $discount );
	do_action( 'rcp_setup_registration', $level_id, $discount );
}

/**
 * Automatically setup the registration object
 *
 * @uses rcp_setup_registration()
 *
 * @return void
 */
function rcp_setup_registration_init() {
	// Then, check the value of $_POST['rcp_level'].
	if (
		empty( $_POST['rcp_level'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		|| (
			isset( $_POST['rcp_level'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			&& 'undefined' === $_POST['rcp_level'] // phpcs:ignore WordPress.Security.NonceVerification.Missing
		)
	) {
		return;
	}

	$level_id = abs( sanitize_text_field( wp_unslash( $_POST['rcp_level'] ) ) );
	$discount = ! empty( $_REQUEST['discount'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['discount'] ) ) : null;
	$discount = ! empty( $_POST['rcp_discount'] ) ? sanitize_text_field( wp_unslash( $_POST['rcp_discount'] ) ) : $discount;

	rcp_setup_registration( $level_id, $discount );
}

add_action( 'init', 'rcp_setup_registration_init' );


/**
 * Filter levels to only show valid upgrade levels
 *
 * @param array $levels Array of membership levels.
 *
 * @since 2.5
 * @return array Array of membership levels.
 */
function rcp_filter_registration_upgrade_levels( $levels = array() ) {

	global $rcp_payments_db;

	remove_filter( 'rcp_get_levels', 'rcp_filter_registration_upgrade_levels' );

	// Filter available levels based on query args.
	$type       = rcp_get_registration()->get_registration_type();
	$membership = rcp_get_registration()->get_membership();
	$payment_id = ! empty( $_GET['rcp_registration_payment_id'] ) ? absint( $_GET['rcp_registration_payment_id'] ) : 0;

	if ( ! empty( $payment_id ) && $payment = $rcp_payments_db->get_payment( $payment_id ) ) {

		// We're recovering a payment -- only show the level associated with that payment.
		$levels = array( rcp_get_membership_level( $payment->object_id ) );

	} else {

		// Renewals / upgrades / normal.

		if ( 'renewal' == $type && ! empty( $membership ) ) {
			$level_id = $membership->get_object_id();
			$levels   = $membership->can_renew() ? array( rcp_get_membership_level( $level_id ) ) : array();
		} elseif ( in_array( $type, array( 'upgrade', 'downgrade' ) ) && ! empty( $membership ) ) {
			$levels = $membership->upgrade_possible() ? $membership->get_upgrade_paths() : array();
		} elseif ( 'new' == $type && ! rcp_multiple_memberships_enabled() ) {
			$levels = rcp_get_upgrade_paths();
		}
	}

	add_filter( 'rcp_get_levels', 'rcp_filter_registration_upgrade_levels' );

	return $levels;

}

/**
 * Hook into registration page and filter upgrade path
 */
add_action( 'rcp_before_subscription_form_fields', 'rcp_filter_registration_upgrade_levels' );

/**
 * Display "new membership" message.
 *
 * Adds a notice to the registration form if a user is signing up for a new membership but they
 * already have existing memberships that could be renewed or upgraded instead. This is to help
 * avoid confusion if someone thinks they're renewing a membership but actually they're signing
 * up for a second one instead.
 *
 * This message is not displayed if multiple memberships is disabled.
 *
 * @since 3.1
 * @return void
 */
function rcp_display_new_membership_message() {

	// Bail if multiple memberships aren't enabled.
	if ( ! rcp_multiple_memberships_enabled() ) {
		return;
	}

	// Bail if we're already renewing or upgrading.
	if ( 'new' != rcp_get_registration()->get_registration_type() ) {
		return;
	}

	$customer = rcp_get_customer(); // current customer

	// Bail if this user isn't already a customer.
	if ( empty( $customer ) ) {
		return;
	}

	// Bail if this customer doesn't have any memberships.
	$memberships = $customer->get_memberships(
		array(
			'status__in' => array( 'active', 'cancelled', 'expired' ),
		)
	);

	// Final array of filtered memberships.
	$membership_array = array();

	/**
	 * @var RCP_Membership $membership
	 */
	foreach ( $memberships as $membership ) {
		if ( ! $membership->can_renew() && ! $membership->upgrade_possible() ) {
			continue;
		}

		$actions = array();

		if ( $membership->can_renew() ) {
			$actions[] = '<a href="' . esc_url( rcp_get_membership_renewal_url( $membership->get_id() ) ) . '">' . __( 'Renew', 'rcp' ) . '</a>';
		}

		if ( $membership->upgrade_possible() ) {
			$actions[] = '<a href="' . esc_url( rcp_get_membership_upgrade_url( $membership->get_id() ) ) . '">' . __( 'Change', 'rcp' ) . '</a>';
		}

		$membership_array[ $membership->get_id() ] = array(
			'name'    => sprintf( '%s (%s)', $membership->get_membership_level_name(), $membership->get_status() ),
			'actions' => implode( sprintf( ' %s ', __( 'or', 'rcp' ) ), $actions ),
		);
	}

	if ( empty( $membership_array ) ) {
		return;
	}
	?>
	<div id="rcp-membership-new-signup-notice">
		<p>
			<?php printf( '%s <a href="#" id="rcp-membership-renew-upgrade-toggle">%s</a>', __( 'You are signing up for a new membership.', 'rcp' ), __( 'Click here to renew or change an existing membership instead.', 'rcp' ) ); ?>
		</p>
		<div id="rcp-membership-renew-upgrade-choice" style="display:none;">
			<ul>
				<?php
				/**
				 * @var RCP_Membership $membership
				 */
				foreach ( $membership_array as $id => $this_membership ) :
					?>
					<li>
						<?php printf( '%s - %s', $this_membership['name'], $this_membership['actions'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php

}

add_action( 'rcp_before_subscription_form_fields', 'rcp_display_new_membership_message' );

/**
 * Display "change membership" message.
 *
 * Adds a notice to the registration form if a user is changing their membership but they have
 * the option of signing up for an additional membership instead. This is to help avoid
 * confusion if someone thinks they're signing up for a second membership, but actually they're
 * changing an existing one.
 *
 * This message is not displayed if multiple memberships is disabled.
 *
 * @since 3.1
 * @return void
 */
function rcp_display_change_membership_message() {

	// Bail if multiple memberships aren't enabled.
	if ( ! rcp_multiple_memberships_enabled() ) {
		return;
	}

	// Bail if this isn't an upgrade/downgrade.
	if ( ! in_array( rcp_get_registration()->get_registration_type(), array( 'upgrade', 'downgrade' ) ) ) {
		return;
	}

	$membership = rcp_get_registration()->get_membership();

	if ( empty( $membership ) ) {
		return;
	}

	?>
	<div id="rcp-membership-renew-upgrade-notice">
		<p>
			<?php printf( __( 'You are changing your "%1$s" membership. <a href="%2$s">Click here to sign up for an additional membership instead.</a>', 'rcp' ), $membership->get_membership_level_name(), esc_url( rcp_get_registration_page_url() ) ); ?>
		</p>
	</div>
	<?php

}

add_action( 'rcp_before_subscription_form_fields', 'rcp_display_change_membership_message' );

/**
 * Add prorate credit to member registration
 *
 * @param RCP_Registration $registration
 *
 * @since 2.5
 * @return void
 */
function rcp_add_prorate_fee( $registration ) {
	// If this isn't an upgrade, don't show the message.
	if ( ! in_array( $registration->get_registration_type(), array( 'upgrade', 'downgrade' ) ) ) {
		return;
	}

	if ( $membership = $registration->get_membership() ) {
		$amount = $membership->get_prorate_credit_amount();
	} else {
		$amount = rcp_get_member_prorate_credit();
	}

	if ( empty( $amount ) ) {
		return;
	}

	$registration->add_fee( -1 * $amount, __( 'Proration Credit', 'rcp' ), false, true );

	rcp_log( sprintf( 'Adding %.2f proration credits to registration for user #%d.', $amount, get_current_user_id() ) );
}

add_action( 'rcp_registration_init', 'rcp_add_prorate_fee' );

/**
 * Add message to checkout specifying proration credit
 *
 * @since 2.5
 * @return void
 */
function rcp_add_prorate_message() {
	// If this isn't an upgrade, don't show the message.
	if ( ! in_array( rcp_get_registration()->get_registration_type(), array( 'upgrade', 'downgrade' ) ) ) {
		return;
	}

	$upgrade_paths = rcp_get_upgrade_paths( get_current_user_id() );
	$has_upgrade   = false;

	/*
	 * The proration message should only be shown if the user has at least one upgrade
	 * option available where the price is greater than $0.
	 */
	if ( ! empty( $upgrade_paths ) ) {
		foreach ( $upgrade_paths as $subscription_level ) {
			if ( ( $subscription_level->price > 0 || $subscription_level->fee > 0 ) ) {
				$has_upgrade = true;
				break;
			}
		}
	}

	if ( $membership = rcp_get_registration()->get_membership() ) {
		$amount = $membership->get_prorate_credit_amount();
	} else {
		$amount = rcp_get_member_prorate_credit();
	}

	if ( empty( $amount ) || ( ! $has_upgrade ) ) {
		return;
	}

	$prorate_message = sprintf( '<p>%s</p>', __( 'If you upgrade or downgrade your account, the new membership will be prorated up to %s for the first payment. Prorated prices are shown below.', 'rcp' ) );

	printf( apply_filters( 'rcp_registration_prorate_message', $prorate_message ), esc_html( rcp_currency_filter( $amount ) ) );
}

add_action( 'rcp_before_subscription_form_fields', 'rcp_add_prorate_message' );

/**
 * Add the registration type to the form.
 *
 * @since 3.1
 * @return void
 */
function rcp_add_registration_type_field() {

	$reg_type      = ! empty( $_GET['registration_type'] ) ? sanitize_text_field( wp_unslash( $_GET['registration_type'] ) ) : '';
	$membership_id = ! empty( $_GET['membership_id'] ) ? sanitize_text_field( wp_unslash( $_GET['membership_id'] ) ) : 0;
	$payment_id    = ! empty( $_GET['rcp_registration_payment_id'] ) ? sanitize_text_field( wp_unslash( $_GET['rcp_registration_payment_id'] ) ) : 0;
	?>
	<input type="hidden" id="rcp-registration-type" name="registration_type" value="<?php echo esc_attr( $reg_type ); ?>" />
	<input type="hidden" id="rcp-membership-id" name="membership_id" value="<?php echo absint( $membership_id ); ?>" />
	<input type="hidden" id="rcp-payment-id" name="rcp_registration_payment_id" value="<?php echo absint( $payment_id ); ?>" />
	<?php

}
add_action( 'rcp_before_registration_submit_field', 'rcp_add_registration_type_field' );

/**
 * Removes the reminder sent flags when the member's status is set to active.
 * This allows the reminders to be re-sent for the next membership period.
 *
 * @deprecated 3.0 In favour of `rcp_remove_membership_reminder_flags()`.
 * @see rcp_remove_membership_reminder_flags()
 *
 * @param string     $status     User's membership status.
 * @param int        $user_id    ID of the user.
 * @param string     $old_status Old status from before the update.
 * @param RCP_Member $member     Member object.
 *
 * @since 2.5.5
 * @return void
 */
function rcp_remove_expiring_soon_email_sent_flag( $status, $user_id, $old_status, $member ) {

	if ( 'active' !== $status ) {
		return;
	}

	global $wpdb;

	$query = $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s", $user_id, '_rcp_reminder_sent_' . absint( $member->get_subscription_id() ) . '_%' );
	$wpdb->query( $query );

}
// add_action( 'rcp_set_status', 'rcp_remove_expiring_soon_email_sent_flag', 10, 4 );

/**
 * Trigger email verification during registration.
 *
 * @uses rcp_send_email_verification()
 *
 * @param array                $posted              Posted form data.
 * @param int                  $user_id             ID of the user making this registration.
 * @param float                $price               Price of the membership level.
 * @param int                  $payment_id          ID of the pending payment associated with this registration.
 * @param RCP_Customer         $customer            Customer object.
 * @param int                  $membership_id       ID of the new pending membership.
 * @param RCP_Membership|false $previous_membership Previous membership object, or false if none.
 * @param string               $registration_type   Type of registration: 'new', 'renewal', or 'upgrade'.
 *
 * @return void
 */
function rcp_set_email_verification_flag( $posted, $user_id, $price, $payment_id, $customer, $membership_id, $previous_membership, $registration_type ) {

	global $rcp_options;

	$require_verification = isset( $rcp_options['email_verification'] ) ? $rcp_options['email_verification'] : 'off';
	$required             = in_array( $require_verification, array( 'free', 'all' ) );

	// Not required if this is a paid registration and email verification is required for free only.
	if ( $price > 0 && 'free' == $require_verification ) {
		$required = false;
	}

	// Not required if they've already had a membership level.
	// This prevents email verification from popping up for old users on upgrades/downgrades/renewals.
	if ( $previous_membership ) {
		$required = false;
	}

	// Bail if verification not required.
	if ( ! apply_filters( 'rcp_require_email_verification', $required, $posted, $user_id, $price ) ) {
		return;
	}

	// Email verification already completed.
	if ( 'verified' === $customer->get_email_verification_status() ) {
		return;
	}

	// Add meta flag to indicate they're pending email verification. This also sends the email.
	$customer->update(
		array(
			'email_verification' => 'pending',
		)
	);

}

add_action( 'rcp_form_processing', 'rcp_set_email_verification_flag', 10, 8 );

/**
 * Remove membership data if registration payment fails. Includes:
 *
 *  - Update pending payment status to "Failed"
 *  - If this is a brand new registration, disable the associated membership. @todo Maybe this could be improved.
 *
 * @param RCP_Payment_Gateway $gateway
 *
 * @since  2.8
 * @return void
 */
function rcp_remove_subscription_data_on_failure( $gateway ) {

	if ( ! empty( $gateway->user_id ) && is_object( $gateway->payment ) ) {

		/**
		 * @var RCP_Payments $rcp_payments_db
		 */
		global $rcp_payments_db;

		// Mark the pending payment as failed.
		$rcp_payments_db->update( $gateway->payment->id, array( 'status' => 'failed' ) );

		// Disable the membership.
		$transaction_type = ! empty( $gateway->payment->transaction_type ) ? $gateway->payment->transaction_type : 'new';
		if ( is_object( $gateway->membership ) && ! $gateway->membership->is_active() && 'renewal' != $transaction_type ) {
			$gateway->membership->disable();
		}

		if ( is_object( $gateway->membership ) ) {
			$pending_payment_id = rcp_get_membership_meta( $gateway->membership->get_id(), 'pending_payment_id', true );

			if ( $pending_payment_id == $gateway->payment->id ) {
				rcp_delete_membership_meta( $gateway->membership->get_id(), 'pending_payment_id' );
			}
		}

		// Delete the pending payment ID meta value if that matches this payment.
		// This is for backwards compatibility only; we now use membership meta.
		$customer = rcp_get_customer_by_user_id( $gateway->user_id );
		if ( ! empty( $customer ) ) {
			$pending_payment_id = $customer->get_pending_payment_id();

			if ( $pending_payment_id == $gateway->payment->id ) {
				delete_user_meta( $customer->get_user_id(), 'rcp_pending_payment_id' );
			}
		}
	}

	// Log error.
	rcp_log( sprintf( '%s registration failed for user #%d. Error message: %s', rcp_get_gateway_name_from_object( $gateway ), $gateway->user_id, $gateway->error_message ), true );

}

add_action( 'rcp_registration_failed', 'rcp_remove_subscription_data_on_failure' );

/**
 * Complete a registration when a payment is completed by updating the following:
 *
 *      - Add discount code to member's profile (if applicable).
 *      - Increase discount code usage (if applicable).
 *      - Mark as trialing (if applicable).
 *      - Remove the role granted by the previous membership level and apply new one.
 *
 * @param int $payment_id ID of the payment being completed.
 *
 * @since 2.9
 * @return void
 */
function rcp_complete_registration( $payment_id ) {

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	$payment = $rcp_payments_db->get_payment( $payment_id );

	if ( ! empty( $payment->customer_id ) ) {
		$customer = rcp_get_customer( $payment->customer_id );
	} else {
		$customer = false;
	}

	// No customer - bail.
	if ( empty( $customer ) ) {
		return;
	}

	$membership_id = ! empty( $payment->membership_id ) ? $payment->membership_id : 0;
	$membership    = ! empty( $membership_id ) ? rcp_get_membership( $membership_id ) : false;

	if ( empty( $membership ) ) {
		return;
	}

	$pending_payment_id = rcp_get_membership_meta( $membership_id, 'pending_payment_id', true );

	// If this payment doesn't correspond to the initial signup ID, bail.
	if ( empty( $pending_payment_id ) || $pending_payment_id != $payment_id ) {
		return;
	}

	rcp_log( sprintf( 'Completing registration for customer #%d via payment #%d.', $customer->get_id(), $pending_payment_id ) );

	if ( ! empty( $payment->transaction_type ) && 'renewal' != $payment->transaction_type ) {
		// If this is a brand new membership, activate it.
		$membership->activate();
	} else {
		// Otherwise this is a manual renewal, so let's renew it.
		$expiration = '';

		if ( 'pending' == $membership->get_status() ) {
			// If membership is pending, use existing expiration date. This would have been set during registration.
			$expiration = $membership->get_expiration_date( false );
		}

		$membership->renew( $membership->is_recurring(), 'active', $expiration );
	}

	// Increase the number of uses for this discount code.
	if ( ! empty( $payment->discount_code ) ) {
		$discount = rcp_get_discount_by( 'code', $payment->discount_code );

		if ( ! empty( $discount ) ) {
			rcp_log( sprintf( 'Recording usage of discount code #%d.', $discount->get_id() ) );

			// Record the usage of this discount code
			$discount->store_for_user( $customer->get_user_id() );

			// Increase the usage count for the code
			$discount->increment_use_count();
		}
	}

	// Delete the pending payment record.
	rcp_delete_membership_meta( $membership_id, 'pending_payment_id' );

	// Backwards compatibility.
	if ( ! empty( $payment->user_id ) ) {
		delete_user_meta( $payment->user_id, 'rcp_pending_payment_id' );
	}

	$member = new RCP_Member( $customer->get_user_id() ); // for backwards compatibility in the below action

	/**
	 * Registration successful! Hook into this action if you need to execute code
	 * after a successful registration, but not during an automatic renewal.
	 *
	 * @var RCP_Member     $member
	 * @var RCP_Customer   $customer
	 * @var RCP_Membership $membership
	 * @since 2.9
	 */
	do_action( 'rcp_successful_registration', $member, $customer, $membership );

}

add_action( 'rcp_update_payment_status_complete', 'rcp_complete_registration' );

/**
 * Register a user account as an RCP member, assign a membership level,
 * calculate the expiration date, etc.
 *
 * @deprecated 3.0 Use `RCP_Customer::add_membership()` instead.
 * @see        RCP_Customer::add_membership()
 *
 * @param int   $user_id             ID of the user to add the membership to.
 * @param array $args                {
 *                                   Array of membership arguments. Only `subscription_id` is required.
 *
 * @type string   $status              Optional.    Status to set: free, active, cancelled, or expired. If omitted, set
 *       to free or active.
 * @type int      $subscription_id     Required. ID number of the membership level to give the user.
 * @type string   $expiration          Optional. Expiration date to give the user in MySQL format. If omitted,
 *       calculated automatically.
 * @type string   $discount_code       Optional. Name of a discount code to add to the user's profile and increment
 *       usage count.
 * @type string   $subscription_key    Optional. Subscription key to add to the user's profile.
 * @type int|bool $trial_duration      Optional. Only supply this to give the user a free trial.
 * @type string   $trial_duration_unit Optional. `day`, `month`, or `year`.
 * @type bool     $recurring           Optional. Whether or not the membership is automatically recurring. Default is
 *       `false`.
 * @type string   $payment_profile_id  Optional. Payment profile ID to add to the user's profile.
 * }
 *
 * @since      2.9
 * @return bool
 */
function rcp_add_user_to_subscription( $user_id, $args = array() ) {

	$defaults = array(
		'status'              => '',
		'subscription_id'     => 0,
		'expiration'          => '',    // Calculated automatically if not provided.
		'discount_code'       => '',    // To add to their profile and increase usage.
		'subscription_key'    => '',
		'trial_duration'      => false, // To set as trialing.
		'trial_duration_unit' => 'day',
		'recurring'           => false,
		'payment_profile_id'  => '',
	);

	$args = wp_parse_args( $args, $defaults );

	// Membership level ID is required.
	if ( empty( $args['subscription_id'] ) ) {
		return false;
	}

	// Check to see if a customer exists, and if not, create one.
	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( empty( $customer ) ) {
		$customer_id = rcp_add_customer(
			array(
				'user_id' => $user_id,
			)
		);

		if ( empty( $customer_id ) ) {
			return false;
		}

		$customer = rcp_get_customer( $customer_id );
	}

	$member              = new RCP_Member( $user_id );
	$previous_membership = rcp_get_customer_single_membership( $customer->get_id() );
	$old_membership_id   = ! empty( $previous_membership ) ? $previous_membership->get_object_id() : false;
	$membership_level    = rcp_get_membership_level( $args['subscription_id'] );

	// Invalid membership level - bail.
	if ( ! $membership_level instanceof Membership_Level ) {
		return false;
	}

	if ( ! empty( $previous_membership ) && $previous_membership->get_object_id() == $args['subscription_id'] ) {

		/**
		 * If the previous membership is the same as this new one, renew it instead of adding a new one.
		 */

		$expiration = ! empty( $args['expiration'] ) ? $args['expiration'] : '';
		$previous_membership->renew( false, 'active', $expiration );

		if ( ! empty( $args['subscription_key'] ) ) {
			$previous_membership->set_subscription_key( $args['subscription_key'] );
		}

		if ( ! empty( $args['payment_profile_id'] ) ) {
			$previous_membership->set_gateway_customer_id( $args['payment_profile_id'] );
		}
	} else {

		/**
		 * Gather all the membership data for a brand new membership.
		 */

		$membership_data = array(
			'object_id' => $membership_level->get_id(),
		);

		if ( ! empty( $args['subscription_key'] ) ) {
			$membership_data['subscription_key'] = $args['subscription_key'];
		}

		/*
		 * Initial and recurring amounts.
		 */
		$membership_data['initial_amount']   = $membership_level->get_price() + $membership_level->get_fee();
		$membership_data['recurring_amount'] = $membership_level->get_price();

		/*
		 * Expiration date
		 * Calculate it if not provided.
		 */
		$expiration = $args['expiration'];
		if ( empty( $expiration ) ) {
			$expiration = rcp_calculate_subscription_expiration( $membership_level->get_id(), ! $member->has_trialed() );
		}
		$membership_data['expiration_date'] = $expiration;

		/*
		 * Discount code
		 * Increase the number of uses.
		 */
		if ( ! empty( $args['discount_code'] ) ) {
			$discount = rcp_get_discount_by( 'code', $args['discount_code'] );

			if ( ! empty( $discount ) ) {
				// Record the usage of this discount code
				$discount->store_for_user( $user_id );

				// Increase the usage count for the code
				$discount->increment_use_count();
			}
		}

		/*
		 * Set the status
		 */
		$status = $args['status'];
		if ( empty( $status ) || 'free' == $status ) {
			$status = 'active';
		}
		$membership_data['status'] = $status;

		// Recurring
		$membership_data['auto_renew'] = ! empty( $args['recurring'] ) ? 1 : 0;

		// Payment profile ID
		if ( ! empty( $args['payment_profile_id'] ) ) {
			$membership_data['gateway_subscription_id'] = $args['payment_profile_id'];
		}

		/**
		 * Now actually insert or update the membership.
		 */
		$membership_id = $customer->add_membership( $membership_data );

		if ( empty( $membership_id ) ) {
			return false;
		}
	}

	/**
	 * Registration successful! Hook into this action if you need to execute code
	 * after a successful registration, but not during an automatic renewal.
	 *
	 * @var RCP_Member $member
	 * @since 2.9
	 */
	do_action( 'rcp_successful_registration', $member );

	return true;

}

/**
 * Automatically add new users to a membership level if enabled
 *
 * @param int $user_id ID of the newly created user.
 *
 * @since 2.9
 * @return void
 */
function rcp_user_register_add_subscription_level( $user_id ) {

	global $rcp_options;

	if ( empty( $rcp_options['auto_add_users'] ) ) {
		return;
	}

	$level_id = absint( $rcp_options['auto_add_users_level'] );

	if ( empty( $level_id ) ) {
		return;
	}

	// Don't run if we're on the registration form.
	if ( did_action( 'rcp_form_errors' ) ) {
		return;
	}

	rcp_log( sprintf( 'Auto adding membership level to user #%d.', $user_id ) );

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( empty( $customer ) ) {
		$customer_id = rcp_add_customer( array( 'user_id' => $user_id ) );

		if ( empty( $customer_id ) ) {
			rcp_log( sprintf( 'Error creating customer record for user #%d.', $user_id ) );

			return;
		}

		$customer = rcp_get_customer( $customer_id );
	}

	if ( empty( $customer ) ) {
		rcp_log( sprintf( 'Error retrieving customer record for user #%d.', $user_id ) );

		return;
	}

	$customer->add_membership(
		array(
			'object_id'     => $level_id,
			'status'        => 'active', // Note: this bypasses RCP_Membership::activate(). Not sure if desired.
			'signup_method' => 'manual',
		)
	);

}

add_action( 'user_register', 'rcp_user_register_add_subscription_level' );

/**
 * Returns the URL to the designated registration page.
 *
 * @since 3.1
 * @return string
 */
function rcp_get_registration_page_url() {

	global $rcp_options;

	$url = get_permalink( $rcp_options['registration_page'] );

	/**
	 * Filters the registration page URL.
	 *
	 * @param string $url
	 *
	 * @since 3.3.5
	 */
	return apply_filters( 'rcp_registration_page_url', $url );

}

/**
 * Adds the membership "Next Renewal Due" date to the registration "Total Details" table.
 *
 * @since 3.3
 * @return void
 */
function rcp_add_membership_renewal_date_to_total_details() {

	$renewal_date        = false;
	$registration        = rcp_get_registration();
	$membership          = $registration->get_membership();
	$membership_level_id = $registration->get_membership_level_id();

	// Bail if we have a discount code and the initial & recurring amounts are 0.
	if ( $registration->get_total() == 0 && $registration->get_recurring_total() == 0 && $registration->get_discounts() && '2' != rcp_get_auto_renew_behavior() ) {
		return;
	}

	if ( ! empty( $membership ) && 'renewal' == $registration->get_registration_type() ) {
		/*
		 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/1259
		 *
		 * Not all gateways currently support extending the expiration date another full billing cycle during
		 * renewals while auto renew is selected.
		 */
		$from_today = false;

		if ( ! empty( $_POST['rcp_gateway'] ) ) {
			$from_today = rcp_gateway_supports( sanitize_text_field( wp_unslash( $_POST['rcp_gateway'] ) ), 'expiration-extension-on-renewals' ) ? false : rcp_registration_is_recurring();
		}

		$renewal_date = $membership->calculate_expiration( $from_today );
	}

	if ( empty( $renewal_date ) && ! empty( $membership_level_id ) ) {
		$membership_level = rcp_get_membership_level( $membership_level_id );

		// Bail if duration is `0` or price is `0`.
		if ( ! $membership_level instanceof Membership_Level || $membership_level->is_free() || $membership_level->is_lifetime() ) {
			return;
		}

		// Get the previous membership ID if this is an upgrade.
		$upgraded_from = 0;
		if ( 'upgrade' === $registration->get_registration_type() ) {
			$membership    = $registration->get_membership();
			$upgraded_from = $membership ? $membership->get_id() : 0;
		}

		$renewal_date = rcp_calculate_subscription_expiration( $membership_level_id, $registration->is_trial(), $upgraded_from );
	}

	if ( empty( $renewal_date ) || 'none' == $renewal_date ) {
		return;
	}
	?>
	<tr class="row rcp-renewal-date">
		<td class="cell" scope="col"><?php _e( 'Next Renewal Due', 'rcp' ); ?></td>
		<td class="cell" data-th="<?php esc_attr_e( 'Next Renewal Due', 'rcp' ); ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $renewal_date, current_time( 'timestamp' ) ) ); ?></td>
	</tr>
	<?php

}
add_action( 'rcp_register_total_details_footer_bottom', 'rcp_add_membership_renewal_date_to_total_details' );
