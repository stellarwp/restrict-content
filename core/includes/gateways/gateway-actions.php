<?php
/**
 * Gateway Actions
 *
 * @package     Restrict Content Pro
 * @subpackage  Gateways/Actions
 * @copyright   Copyright (c) 2020, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Load webhook processor for all gateways
 *
 * @access      public
 * @since       2.1
 * @return      void
 */
function rcp_process_gateway_webooks() {

	if ( ! apply_filters( 'rcp_process_gateway_webhooks', ! empty( $_GET['listener'] ) ) ) {
		return;
	}

	$gateways = new RCP_Payment_Gateways;

	foreach( $gateways->available_gateways  as $key => $gateway ) {

		$payment_gateway = rcp_get_gateway_class( $key );

		if( $payment_gateway ) {
			$payment_gateway->process_webhooks();
		}

	}

}
add_action( 'init', 'rcp_process_gateway_webooks', -99999 );

/**
 * Process gateway confirmations.
 *
 * @access      public
 * @since       2.1
 * @return      void
 */
function rcp_process_gateway_confirmations() {

	global $rcp_options;

	if( empty( $rcp_options['registration_page'] ) ) {
		return;
	}

	if( empty( $_GET['rcp-confirm'] ) ) {
		return;
	}

	if( ! rcp_is_registration_page() ) {
		return;
	}

	$gateways = new RCP_Payment_Gateways;
	$gateway  = sanitize_text_field( $_GET['rcp-confirm'] );

	if( ! $gateways->is_gateway_enabled( $gateway ) ) {
		return;
	}

	$payment_gateway = rcp_get_gateway_class( $gateway );

	if( $payment_gateway && method_exists( $payment_gateway, 'process_confirmation' ) ) {
		$payment_gateway->process_confirmation();
	}

}
add_action( 'template_redirect', 'rcp_process_gateway_confirmations', -99999 );

/**
 * Load gateway scripts on registration page
 *
 * @access      public
 * @since       2.1
 * @return      void
 */
function rcp_load_gateway_scripts() {

	global $rcp_options;

	$is_rcp_page = rcp_is_registration_page();
	if ( ! $is_rcp_page ) {
		// Check other known pages.
		$pages = array( 'redirect', 'account_page', 'edit_profile', 'update_card' );

		foreach ( $pages as $page_key ) {
			if ( ! empty( $rcp_options[ $page_key ] ) && is_page( absint( $rcp_options[ $page_key ] ) ) ) {
				$is_rcp_page = true;
				break;
			}
		}
	}

	$load_scripts = $is_rcp_page || defined( 'RCP_LOAD_SCRIPTS_GLOBALLY' );
	$gateways     = new RCP_Payment_Gateways;

	/*
	 * Unless the option is disabled, Stripe.js is loaded on all pages for advanced fraud functionality.
	 */
	$global_scripts = empty( $rcp_options['disable_sitewide_scripts'] ) ? array( 'stripe', 'stripe_checkout' ) : array();

	foreach( $gateways->enabled_gateways  as $key => $gateway ) {

		$payment_gateway = rcp_get_gateway_class( $key );

		if( $payment_gateway && ( $load_scripts || in_array( $key, $global_scripts ) ) ) {
			$payment_gateway->scripts();
		}

	}

}
add_action( 'wp_enqueue_scripts', 'rcp_load_gateway_scripts', 100 );

/**
 * Process an update card form request
 *
 * @uses rcp_member_can_update_billing_card()
 *
 * @access      private
 * @since       2.1
 * @return      void
 */
function rcp_process_update_card_form_post() {

	if( ! is_user_logged_in() ) {
		return;
	}

	if( is_admin() ) {
		return;
	}

	if ( ! isset( $_POST['rcp_update_card_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_update_card_nonce'], 'rcp-update-card-nonce' ) ) {
		return;
	}

	$membership_id = isset( $_POST['rcp_membership_id'] ) ? absint( $_POST['rcp_membership_id'] ) : false;

	if ( empty( $membership_id ) ) {
		$customer   = rcp_get_customer_by_user_id(); // current customer
		$membership = ! empty( $customer ) ? rcp_get_customer_single_membership( $customer->get_id() ) : false;
	} else {
		$membership = rcp_get_membership( $membership_id );
	}

	if ( ! is_object( $membership ) || 0 == $membership->get_id() ) {
		wp_die( __( 'Invalid membership.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
	}

	// Bail if this user isn't actually the customer associated with this membership.
	if ( $membership->get_user_id() != get_current_user_id() ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if( ! $membership->can_update_billing_card() ) {
		wp_die( __( 'Your account does not support updating your billing card', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( has_action( 'rcp_update_billing_card' ) ) {
		$member = new RCP_Member( get_current_user_id() );

		if ( $member ) {
			/**
			 * @deprecated 3.0 Use `rcp_update_membership_billing_card` instead.
			 */
			do_action( 'rcp_update_billing_card', $member->ID, $member );
		}
	}

	/**
	 * Processes the billing card update. Individual gateways hook into here.
	 *
	 * @param RCP_Membership $membership
	 *
	 * @since 3.0
	 */
	do_action( 'rcp_update_membership_billing_card', $membership );

}
add_action( 'init', 'rcp_process_update_card_form_post' );

/**
 * Log cancellation via webhook
 *
 * @param RCP_Member          $member  Member object.
 * @param RCP_Payment_Gateway $gateway Gateway object.
 *
 * @since 2.9
 * @return void
 */
function rcp_log_webhook_cancel( $member, $gateway ) {
	rcp_log( sprintf( 'Membership cancelled via %s webhook for member ID #%d.', rcp_get_gateway_name_from_object( $gateway ), $member->ID ) );
}
add_action( 'rcp_webhook_cancel', 'rcp_log_webhook_cancel', 10, 2 );

/**
 * Log new recurring payment profile created via webhook. This is when the
 * subscription is initially created, it does not include renewals.
 *
 * @param RCP_Member          $member  Member object.
 * @param RCP_Payment_Gateway $gateway Gateway object.
 *
 * @since 2.9
 * @return void
 */
function rcp_log_webhook_recurring_payment_profile_created( $member, $gateway ) {
	rcp_log( sprintf( 'New recurring payment profile created for member #%d in gateway %s.', $member->ID, rcp_get_gateway_name_from_object( $gateway ) ) );
}
add_action( 'rcp_webhook_recurring_payment_profile_created', 'rcp_log_webhook_recurring_payment_profile_created', 10, 2 );

/**
 * Log error when duplicate payment is detected.
 *
 * @param string              $payment_txn_id Payment transaction ID.
 * @param RCP_Member          $member         Member object.
 * @param RCP_Payment_Gateway $gateway        Gateway object.
 *
 * @since 2.9
 * @return void
 */
function rcp_log_duplicate_ipn_payment( $payment_txn_id, $member, $gateway ) {
	rcp_log( sprintf( 'A duplicate payment was detected for user #%d. Check to make sure both payments weren\'t recorded. Transaction ID: %s', $member->ID, $payment_txn_id ) );
}
add_action( 'rcp_ipn_duplicate_payment', 'rcp_log_duplicate_ipn_payment', 10, 3 );

/**
 * Log payment inserted via gateway. This can run on renewals and/or one-time payments.
 *
 * @param RCP_Member          $member     Member object.
 * @param int                 $payment_id ID of the payment that was just inserted.
 * @param RCP_Payment_Gateway $gateway    Gateway object.
 *
 * @since 2.9
 * @return void
 */
function rcp_log_gateway_payment_processed( $member, $payment_id, $gateway ) {
	rcp_log( sprintf( 'Payment #%d completed for member #%d via %s gateway.', $payment_id, $member->ID, rcp_get_gateway_name_from_object( $gateway ) ) );
}
add_action( 'rcp_gateway_payment_processed', 'rcp_log_gateway_payment_processed', 10, 3 );

/**
 * Update the membership's "recurring_amount" when a renewal payment is processed.
 * This will correct any invalid recurring_amount values due to recurring discounts
 * or level price changes.
 *
 * @param RCP_Member          $member     Member object.
 * @param int                 $payment_id ID of the payment that was just inserted.
 * @param RCP_Payment_Gateway $gateway    Gateway object.
 *
 * @since 3.0.5
 * @return void
 */
function rcp_update_membership_recurring_amount_on_renewal( $member, $payment_id, $gateway ) {

	$payments   = new RCP_Payments();
	$payment    = $payments->get_payment( $payment_id );
	$membership = false;

	if ( empty( $payment ) || empty( $payment->amount ) ) {
		return;
	}

	if ( ! empty( $gateway->membership ) ) {
		$membership = $gateway->membership;
	} elseif ( ! empty( $payment->membership_id ) ) {
		$membership = rcp_get_membership( $payment->membership_id );
	}

	if ( ! is_a( $membership, 'RCP_Membership' ) ) {
		return;
	}

	if ( $payment->amount != $membership->get_recurring_amount() ) {
		$membership->update( array(
			'recurring_amount' => $payment->amount
		) );
	}

}
add_action( 'rcp_webhook_recurring_payment_processed', 'rcp_update_membership_recurring_amount_on_renewal', 10, 3 );

/**
 * Change the price of a subscription.
 *
 * @param true|WP_Error  $changed    True if successfully changed, WP_Error if not.
 * @param float          $new_price  The new price.
 * @param string         $gateway    Gateway slug.
 * @param RCP_Membership $membership Membership object.
 *
 * @since 3.5
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function rcp_gateway_process_change_subscription_price( $changed, $new_price, $gateway, $membership ) {

	// Don't mess with a good thing.
	if ( true === $changed ) {
		return $changed;
	}

	rcp_log( sprintf( 'Changing subscription price in %s gateway for membership ID #%d. New price: %s.', $gateway, $membership->get_id(), rcp_currency_filter( $new_price ) ) );

	// If gateway is empty, bail.
	if ( empty( $gateway ) ) {
		return new WP_Error( 'invalid_gateway', __( 'Invalid or unknown payment gateway.', 'rcp' ) );
	}

	$payment_gateway = rcp_get_gateway_class( $gateway );

	if ( empty( $payment_gateway ) ) {
		return new WP_Error( 'invalid_gateway_class', __( 'Invalid gateway class.', 'rcp' ) );
	}

	// Gateway doesn't support price changes.
	if ( ! $payment_gateway->supports( 'price-changes' ) ) {
		return new WP_Error( 'price_changes_not_supported', __( 'Payment gateway doesn\'t support price changes.', 'rcp' ) );
	}

	return $payment_gateway->change_membership_subscription_price( $membership, $new_price );

}
add_filter( 'rcp_membership_change_gateway_price', 'rcp_gateway_process_change_subscription_price', 10, 4 );

/**
 * Process manual creation of a subscription
 *
 * This is done outside the registration form, such as when an admin is manually changing a membership's
 * associated membership level. The old subscription is cancelled and a new one is created.
 *
 * For this to work, gateways must declare support for `subscription-creation`.
 *
 * @param true|WP_Error  $created    True if successfully created, WP_Error if not.
 * @param bool           $charge_now True to process the first payment now. False to wait until the membership's expiration date.
 * @param string         $gateway    Gateway slug.
 * @param RCP_Membership $membership Membership object.
 *
 * @since 3.5
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function rcp_gateway_process_manual_subscription_creation( $created, $charge_now, $gateway, $membership ) {

	// Don't mess with a good thing.
	if ( true === $created ) {
		return $created;
	}

	// If gateway is empty, bail.
	if ( empty( $gateway ) ) {
		return new WP_Error( 'invalid_gateway', __( 'Invalid or unknown payment gateway.', 'rcp' ) );
	}

	$payment_gateway = rcp_get_gateway_class( $gateway );

	if ( empty( $payment_gateway ) ) {
		return new WP_Error( 'invalid_gateway_class', __( 'Invalid gateway class.', 'rcp' ) );
	}

	// Gateway doesn't support price changes.
	if ( ! $payment_gateway->supports( 'subscription-creation' ) ) {
		return new WP_Error( 'subscription_creation_not_supported', __( 'Payment gateway doesn\'t support dynamic subscription creation.', 'rcp' ) );
	}

	return $payment_gateway->create_subscription_for_membership( $membership, $charge_now );

}
add_filter( 'rcp_membership_created_gateway_subscription', 'rcp_gateway_process_manual_subscription_creation', 10, 4 );

/**
 * Update the next bill date for a subscription
 *
 * We can't actually change this directly so we do something hacky by setting the "trial_end" date instead.
 * This effectively changes the next bill date.
 * @link https://stripe.com/docs/billing/subscriptions/billing-cycle#changing
 *
 * @param true|WP_Error  $changed          True if successfully changed, WP_Error if not.
 * @param string         $new_renewal_date New renewal date in MySQL format.
 * @param string         $gateway          Payment gateway slug.
 * @param RCP_Membership $membership       Membership object.
 *
 * @return true|WP_Error True on success, WP_Error on failure.
 * @throws Exception
 */
function rcp_stripe_update_subscription_bill_date( $changed, $new_renewal_date, $gateway, $membership ) {

	// Don't mess with a good thing.
	if ( true === $changed ) {
		return $changed;
	}

	// If gateway is empty, bail.
	if ( empty( $gateway ) ) {
		return new WP_Error( 'invalid_gateway', __( 'Invalid or unknown payment gateway.', 'rcp' ) );
	}

	$payment_gateway = rcp_get_gateway_class( $gateway );

	if ( empty( $payment_gateway ) ) {
		return new WP_Error( 'invalid_gateway_class', __( 'Invalid gateway class.', 'rcp' ) );
	}

	// Gateway doesn't support next bill date changes.
	if ( ! $payment_gateway->supports( 'renewal-date-changes' ) ) {
		return new WP_Error( 'renewal_date_changes_not_supported', __( 'Payment gateway doesn\'t support renewal date changes.', 'rcp' ) );
	}

	return $payment_gateway->change_next_bill_date( $membership, $new_renewal_date );

}

add_filter( 'rcp_membership_change_next_bill_date', 'rcp_stripe_update_subscription_bill_date', 10, 4 );
