<?php
/**
 * Ajax Actions
 *
 * Process the front-end ajax actions.
 *
 * @package     Restrict Content Pro
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/license/gpl-2.1.php GNU Public License
 */

/**
 * Check whether a discount code is valid. Used during registration to validate a discount code on the fly.
 *
 * @return void
 */
function rcp_validate_discount_with_ajax() {
	if( isset( $_POST['code'] ) ) {

		$return          = array();
		$return['valid'] = false;
		$return['full']  = false;
		$subscription_id = isset( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;

		rcp_setup_registration( $subscription_id, $_POST['code'] );

		if( rcp_validate_discount( wp_unslash( $_POST['code'] ), $subscription_id ) ) {

			$code_details = rcp_get_discount_details_by_code( sanitize_text_field( wp_unslash( $_POST['code'] ) ) );

			if( ( ! rcp_registration_is_recurring() && rcp_get_registration()->get_recurring_total() == 0.00 ) && rcp_get_registration()->get_total() == 0.00 ) {

				// this is a 100% discount
				$return['full']   = true;

			}

			$return['valid']  = true;
			$return['amount'] = rcp_discount_sign_filter( $code_details->get_amount(), $code_details->get_unit() );

		}

		wp_send_json( $return );
	}
	die();
}
add_action( 'wp_ajax_validate_discount', 'rcp_validate_discount_with_ajax' );
add_action( 'wp_ajax_nopriv_validate_discount', 'rcp_validate_discount_with_ajax' );

/**
 * Calls the load_fields() method for gateways when a gateway selection is made
 *
 * @since  2.1
 * @return void
 */
function rcp_load_gateway_fields() {

	$gateways = new RCP_Payment_Gateways;
	$gateways->load_fields();
	die();
}
add_action( 'wp_ajax_rcp_load_gateway_fields', 'rcp_load_gateway_fields' );
add_action( 'wp_ajax_nopriv_rcp_load_gateway_fields', 'rcp_load_gateway_fields' );

/**
 * Setup the registration details
 *
 * @since  2.5
 * @return void
 */
function rcp_calc_total_ajax() {
	$return = array(
		'valid' => false,
		'total' => __( 'No available membership levels for your account.', 'rcp' ),
	);

	if ( ! rcp_is_registration() ) {
		wp_send_json( $return );
	}

	ob_start();

	rcp_get_template_part( 'register-total-details' );

	$return['total'] = ob_get_clean();

	wp_send_json( $return );
}
add_action( 'wp_ajax_rcp_calc_discount', 'rcp_calc_total_ajax' );
add_action( 'wp_ajax_nopriv_rcp_calc_discount', 'rcp_calc_total_ajax' );

/**
 * Validates the entire registration state
 *
 * @param array $args
 *
 * @since 3.0.6
 * @return array|bool
 */
function rcp_validate_registration_state( $args = array() ) {

	if( empty( $args ) ) {
		return false;
	}

	/**
	 * @type int    $level_id - ID of the membership level.
	 * @type string $gateway - Payment gateway slug.
	 * @type string $gateway_fields - HTML for the payment gateway fields.
	 * @type bool   $show_gateway_fields - Whether or not the gateway fields should be displayed.
	 * @type string $discount_code - Discount code to apply to the registration.
	 * @type bool   $discount_valid - Whether or not the discount is valid.
	 * @type float  $discount_amount - The dollar amount discounted.
	 * @type bool   $full_discount - Whether or not this is a paid level that has been discounted down to $0 due to discount code, negative fees, or credits.
	 * @type bool   $is_free - Similar to $full_discount, but just for discount codes. Deprecated.
	 * @type bool   $lifetime - Whether or not this is a lifetime membership. Deprecated.
	 * @type bool   $level_has_trial - Whether or not the chosen membership level has a trial.
	 * @type float  $initial_total - Total amount due today.
	 * @type float  $recurring_total - Total recurring amount.
	 * @type bool   $recurring - Whether or not recurring is checked on.
	 * @type bool   $recurring_available - Whether or not the recurring checkbox should even be displayed.
	 * @type string $total_details_html - The HTML for the total details table.
	 * @type string $event_type - JavaScript event type.
	 */
	$return = array(
		'level_id'            => ! empty( $args['rcp_level'] ) ? absint( $args['rcp_level'] ) : 0,
		'gateway'             => ! empty( $args['rcp_gateway'] ) ? sanitize_text_field( $args['rcp_gateway'] ) : false,
		'gateway_fields'      => false,
		'show_gateway_fields' => true,
		'discount_code'       => ! empty( $args['discount_code'] ) ? sanitize_text_field( $args['discount_code'] ) : false,
		'discount_valid'      => false,
		'discount_amount'     => false,
		'full_discount'       => false,
		'is_free'             => ! empty( $args['is_free'] ) && 'true' == $args['is_free'], // Deprecated
		'lifetime'            => ! empty( $args['lifetime'] ) && 'true' == $args['lifetime'], // Deprecated
		'level_has_trial'     => ! empty( $args['level_has_trial'] ) && 'true' == $args['level_has_trial'],
		'trial_eligible'      => true,
		'initial_total'       => 0.00,
		'recurring_total'     => 0.00,
		'recurring'           => ! empty( $args['rcp_auto_renew'] ) && 'true' == $args['rcp_auto_renew'],
		'recurring_available' => false,
		'total_details_html'  => __( 'No available membership levels for your account.', 'rcp' ),
		'event_type'          => ! empty( $args['event_type'] ) ? sanitize_text_field( $args['event_type'] ) : false
	);

	rcp_setup_registration( $return['level_id'], $return['discount_code'] );

	/**
	 * 100% discount
	 * If auto renew is DISABLED, this checks if the total due today is $0.
	 * If auto renew is ENABLED, this checks if the total today and recurring total are both $0.
	 * This may be $0 due to discounts, fees, or credits.
	 */
	if ( ( ! rcp_registration_is_recurring() && rcp_get_registration()->get_total() == 0.00 )
		|| ( rcp_registration_is_recurring() && ( rcp_get_registration()->get_total() + rcp_get_registration()->get_recurring_total() ) == 0.00 )
	) {
		// this is a 100% discount
		$return['full_discount'] = true;
	}

	/** Discount */
	if( ! empty( $return['discount_code'] ) ) {
		if( rcp_validate_discount( $return['discount_code'], $return['level_id'] ) ) {
			$code_details              = rcp_get_discount_details_by_code( $return['discount_code'] );
			$return['discount_valid']  = true;
			$return['discount_amount'] = rcp_discount_sign_filter( $code_details->get_amount(), $code_details->get_unit() );
		}
	}

	/** Totals */
	if ( rcp_is_registration() ) {
		$return['initial_total']   = rcp_get_registration()->get_total();
		$return['recurring_total'] = rcp_get_registration()->get_recurring_total();
		$return['recurring']       = rcp_registration_is_recurring();

		ob_start();

		rcp_get_template_part( 'register-total-details' );

		$return['total_details_html'] = ob_get_clean();
	}

	/**
	 * Recurring
	 * Determine whether or not it's allowed. This is largely a copy of rcp_registration_is_recurring()
	 * except it doesn't take the user's selection into account. It's just whether or not the checkbox should
	 * be displayed to the user at all.
	 */
	// Only proceed if "never auto renew" is NOT selected in settings.
	if ( '2' != rcp_get_auto_renew_behavior() ) {
		$return['recurring_available'] = true; // default to allowed

		// Does the gateway actually support it?
		$return['recurring_available'] = rcp_gateway_supports( $return['gateway'], 'recurring' );

		// No auto renew for lifetime or free memberships.
		if ( ! rcp_get_registration_recurring_total() > 0 ) {
			$return['recurring_available'] = false;
		}

		// If the total today is $0 and the gateway does not support free trials, disable auto renew.
		if ( empty( $return['initial_total'] ) && ! rcp_gateway_supports( $return['gateway'], 'trial' ) ) {
			$return['recurring_available'] = false;
			$return['recurring']           = false;
		}
	}

	/**
	 * Free trial
	 * Force auto renew on and disable option if this is a free trial.
	 */
	$customer = rcp_get_customer(); // current customer
	if ( $customer && $customer->has_trialed() ) {
		$return['trial_eligible'] = false;
	}

	if ( $return['level_has_trial'] && $return['trial_eligible'] ) {
		$return['recurring'] = rcp_gateway_supports( $return['gateway'], 'trial' );
	}

	/** Gateway fields */
	// Load the fields.
	if( ! empty( $return['gateway'] ) ) {
		$gateways = new RCP_Payment_Gateways;
		$fields = $gateways->get_gateway_fields( $return['gateway'] );
		if( ! empty( $fields ) ) {
			$return['gateway_fields'] = $fields;
		}
	}
	// Determine whether or not the template should display the fields.
	if ( ( empty( $return['initial_total'] ) && ! $return['recurring'] ) || ( empty( $return['initial_total'] ) && empty( $return['recurring_total'] ) ) ) {
		// Membership is free.
		$return['show_gateway_fields'] = false;
	}

	return $return;

}

/**
 * Ajax callback for validating the registration state.
 * @uses rcp_validate_registration_state()
 *
 * @since 3.0.6
 * @return void
 */
function rcp_validate_registration_state_ajax() {
	wp_send_json_success( rcp_validate_registration_state( wp_unslash( $_POST ) ) );
	exit;
}
add_action( 'wp_ajax_rcp_validate_registration_state', 'rcp_validate_registration_state_ajax' );
add_action( 'wp_ajax_nopriv_rcp_validate_registration_state', 'rcp_validate_registration_state_ajax' );

add_action( 'wp_ajax_rcp_braintree_3ds_validation_fields', 'rcp_braintree_3ds_validation_fields' );
add_action( 'wp_ajax_nopriv_rcp_braintree_3ds_validation_fields', 'rcp_braintree_3ds_validation_fields' );
