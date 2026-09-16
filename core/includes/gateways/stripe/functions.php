<?php
/**
 * Stripe Functions
 *
 * @package     Kadence Memberships Pro
 * @subpackage  Gateways/Stripe/Functions
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use RCP\Membership_Level;

/**
 * Generate an idempotency key.
 *
 * @since 3.5.0
 *
 * @param array $args Arguments used to create or update the current object.
 * @param string $context The context in which the key was generated.
 * @return string
 */
function rcp_stripe_generate_idempotency_key( $args, $context = 'new' ) {
	$idempotency_key = md5( json_encode( $args ) );

	/**
	 * Filters the idempotency_key value sent with the Stripe charge options.
	 *
	 * @since 3.5.0
	 *
	 * @param string $idempotency_key Value of the idempotency key.
	 * @param array  $args            Arguments used to help generate the key.
	 * @param string $context         Context under which the idempotency key is generated.
	 */
	$idempotency_key = apply_filters(
		'rcp_stripe_generate_idempotency_key',
		$idempotency_key,
		$args,
		$context
	);

	return $idempotency_key;
}

/**
 * Determines if a membership is a Stripe subscription.
 *
 * @param int|RCP_Membership $membership_object_or_id Membership ID or object.
 *
 * @since 3.0
 * @return bool
 */
function rcp_is_stripe_membership( $membership_object_or_id ) {

	if ( ! is_object( $membership_object_or_id ) ) {
		$membership = rcp_get_membership( $membership_object_or_id );
	} else {
		$membership = $membership_object_or_id;
	}

	$is_stripe = false;

	if ( ! empty( $membership ) && $membership->get_id() > 0 ) {
		$subscription_id = $membership->get_gateway_customer_id();

		if ( false !== strpos( $subscription_id, 'cus_' ) ) {
			$is_stripe = true;
		}
	}

	/**
	 * Filters whether or not the membership is a Stripe subscription.
	 *
	 * @param bool           $is_stripe
	 * @param RCP_Membership $membership
	 *
	 * @since 3.0
	 */
	return (bool) apply_filters( 'rcp_is_stripe_membership', $is_stripe, $membership );

}

/**
 * Add JS to the update card form
 *
 * @access      private
 * @since       2.1
 * @return      void
 */
function rcp_stripe_update_card_form_js() {
	global $rcp_options, $rcp_membership;

	if ( ! rcp_is_gateway_enabled( 'stripe' ) ) {
		return;
	}

	if ( ! rcp_is_stripe_membership( $rcp_membership->get_id() ) ) {
		return;
	}

	if ( rcp_is_sandbox() ) {
		$key = trim( $rcp_options['stripe_test_publishable'] );
	} else {
		$key = trim( $rcp_options['stripe_live_publishable'] );
	}

	if ( empty( $key ) ) {
		return;
	}

	if ( rcp_is_sandbox() ) {
		$secret_key = trim( $rcp_options['stripe_test_secret'] );
	} else {
		$secret_key = trim( $rcp_options['stripe_live_secret'] );
	}

	if( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// Shared Stripe functionality.
	rcp_stripe_enqueue_scripts(
		array(
			'keys'   => array(
				'publishable' => $key,
			),
			'errors' => rcp_stripe_get_localized_error_messages()
		)
	);

	// Custom profile form handling.
	wp_enqueue_script(
		'rcp-stripe-profile',
		RCP_PLUGIN_URL . 'core/includes/gateways/stripe/js/profile' . $suffix . '.js',
		array(
			'jquery',
			'rcp-stripe'
		),
		RCP_PLUGIN_VERSION,
		true
	);

	wp_localize_script( 'rcp-stripe-profile', 'rcp_stripe_script_options', array(
		'ajaxurl'             => admin_url( 'admin-ajax.php' ),
		'confirm_delete_card' => esc_html__( 'Are you sure you want to delete this payment method?', 'rcp' ),
		'enter_card_name'     => __( 'Please enter a card holder name', 'rcp' ),
		'pleasewait'          => __( 'Please Wait . . . ', 'rcp' ),
		'nonce'                => wp_create_nonce( 'rcp_stripe_create_setup_intent_for_saved_card' ),
	) );

	try {
		$subscription_id = $rcp_membership->get_gateway_subscription_id();

		if ( ! empty( $subscription_id ) ) {
			$subscription = \Stripe\Subscription::retrieve( $subscription_id );

			if ( 'past_due' === $subscription->status ) {
				$invoice        = \Stripe\Invoice::retrieve( $subscription->latest_invoice );
				$payment_intent = \Stripe\PaymentIntent::retrieve( $invoice->payment_intent );

				if ( in_array( $payment_intent->status, array( 'requires_action', 'requires_payment_method' ) ) ) {
					?>
					<p class="rcp_error">
						<span><?php printf( __( 'You have an overdue invoice for %s. Please update your card details to complete your payment.', 'rcp' ), rcp_currency_filter( $invoice->amount_due / rcp_stripe_get_currency_multiplier() ) ); ?></span>
					</p>
					<?php
				}
			}
		}
	} catch ( Exception $e ) {

	}
}
add_action( 'rcp_before_update_billing_card_form', 'rcp_stripe_update_card_form_js' );

/**
 * Update the billing card for a given membership.
 *
 * @param RCP_Membership $membership Membership object.
 *
 * @since 3.0
 * @return void
 */
function rcp_stripe_update_membership_billing_card( $membership ) {

	if ( ! is_a( $membership, 'RCP_Membership' ) ) {
		return;
	}

	if ( ! rcp_is_stripe_membership( $membership ) ) {
		return;
	}

	if( empty( $_POST['stripe_payment_intent_id'] ) ) {
		wp_die( __( 'Missing Stripe setup intent.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$customer_id     = $membership->get_gateway_customer_id();
	$subscription_id = $membership->get_gateway_subscription_id();

	global $rcp_options;

	if ( rcp_is_sandbox() ) {
		$secret_key = trim( $rcp_options['stripe_test_secret'] );
	} else {
		$secret_key = trim( $rcp_options['stripe_live_secret'] );
	}

	if( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	try {
		$subscription = \Stripe\Subscription::retrieve( $subscription_id );
	} catch ( \Exception $e ) {
		$subscription = false;
	}

	try {

		if ( ! empty( $_POST['stripe_payment_intent_object'] ) && 'payment_intent' === $_POST['stripe_payment_intent_object'] ) {
			$intent = \Stripe\PaymentIntent::retrieve( sanitize_text_field( $_POST['stripe_payment_intent_id'] ) );
		} else {
			$intent = \Stripe\SetupIntent::retrieve( sanitize_text_field( $_POST['stripe_payment_intent_id'] ) );
		}

		// Maybe attach the payment method to the customer.
		$payment_method = \Stripe\PaymentMethod::retrieve( $intent->payment_method );

		if ( empty( $payment_method->customer ) ) {
			$payment_method->attach( array(
				'customer' => $customer_id
			) );
		}

		// Set as default payment method.
		if ( ! empty( $subscription ) && 'canceled' !== $subscription->status ) {
			\Stripe\Subscription::update( $subscription->id, array(
				'default_payment_method' => $payment_method->id
			) );
		} else {
			\Stripe\Customer::update( $customer_id, array(
				'invoice_settings' => array(
					'default_payment_method' => $payment_method->id
				)
			) );
		}

		// Attempt to pay any overdue invoices.
		try {
			if ( ! empty( $subscription ) ) {

				if ( 'past_due' === $subscription->status ) {
					$invoices = \Stripe\Invoice::all( array(
						'status'       => 'open',
						'subscription' => $subscription_id,
						'limit'        => 7
					) );

					$has_paid_invoice = false;

					foreach ( $invoices as $invoice ) {
						if ( true === $has_paid_invoice ) {
							$invoice->voidInvoice();
						} else {
							$paid_invoice = $invoice->pay( array(
								'off_session' => true
							) );

							if ( 'paid' === $paid_invoice->status ) {
								$has_paid_invoice = true;
							}
						}
					}
				}
			}
		} catch ( \Exception $e ) {
			// This is a "soft" error. We don't need to show the customer any error messages.
			rcp_log( sprintf( 'Error while paying overdue invoices for Stripe subscription %s; Membership ID: %d; Message: %s.', $subscription_id, $membership->get_id(), $e->getMessage() ), true );
		}

	} catch ( \Stripe\Error\Card $e ) {

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

		return;

	} catch (\Stripe\Error\InvalidRequest $e) {

		// Invalid parameters were supplied to Stripe's API
		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

	} catch (\Stripe\Error\Authentication $e) {

		// Authentication with Stripe's API failed
		// (maybe you changed API keys recently)

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

	} catch (\Stripe\Exception\ApiConnectionException $e) {

		// Network communication with Stripe failed

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

	} catch (\Stripe\Exception\ApiErrorException $e) {

		// Display a very generic error to the user

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

	} catch (Exception $e) {

		// Something else happened, completely unrelated to Stripe

		$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
		$error .= print_r( $e, true );

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

	}

	wp_redirect( add_query_arg( 'card', 'updated' ) );
	return;
}
add_action( 'rcp_update_membership_billing_card', 'rcp_stripe_update_membership_billing_card' );

/**
 * Return the multiplier for the currency. Most currencies are multiplied by 100. Zere decimal
 * currencies should not be multiplied so use 1.
 *
 * @param string $currency Currency code. Defaults to the store currency.
 *
 * @since 2.5
 * @return int
 */
function rcp_stripe_get_currency_multiplier( $currency = '' ) {
	if ( rcp_is_zero_decimal_currency( $currency ) ) {
		$multiplier = 1;
	} elseif ( rcp_stripe_is_three_decimal_currency( $currency ) ) {
		$multiplier = 1000;
	} else {
		$multiplier = 100;
	}

	return apply_filters( 'rcp_stripe_get_currency_multiplier', $multiplier, $currency );
}

/**
 * Determines if a currency is one Stripe bills in thousandths.
 *
 * Stripe sends and expects these amounts x1000 rather than x100. Without this they are
 * recorded ten times too large.
 *
 * @link https://docs.stripe.com/currencies#three-decimal
 *
 * @since 4.0.6
 *
 * @param string $currency Currency code. Defaults to the store currency.
 *
 * @return bool
 */
function rcp_stripe_is_three_decimal_currency( $currency = '' ) {
	$currency = $currency ? strtoupper( $currency ) : strtoupper( rcp_get_currency() );

	$three_decimal_currencies = array(
		'BHD',
		'JOD',
		'KWD',
		'OMR',
		'TND',
	);

	return apply_filters( 'rcp_stripe_is_three_decimal_currency', in_array( $currency, $three_decimal_currencies, true ), $currency );
}

/**
 * Get a membership's saved card details.
 *
 * @param array          $card_details
 * @param int            $membership_id
 * @param RCP_Membership $membership
 *
 * @since 3.2
 * @return array
 */
function rcp_stripe_get_membership_card_details( $card_details, $membership_id, $membership ) {

	if ( ! rcp_is_stripe_membership( $membership ) ) {
		return $card_details;
	}

	global $rcp_options;

	if ( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
	}

	if ( rcp_is_sandbox() ) {
		$secret_key = trim( $rcp_options['stripe_test_secret'] );
	} else {
		$secret_key = trim( $rcp_options['stripe_live_secret'] );
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	$all_customer_payment_methods = rcp_stripe_get_user_saved_payment_methods( $membership->get_user_id() );

	if ( ! empty( $all_customer_payment_methods ) ) {
		foreach ( $all_customer_payment_methods as $payment_method ) {
			/**
			 * @var \Stripe\PaymentMethod $payment_method
			 */
			if ( 'card' === $payment_method->type ) {
				$card_details[ $payment_method->id ] = array(
					'id'        => $payment_method->id,
					'name'      => $payment_method->billing_details->name,
					'type'      => $payment_method->card->brand,
					'zip'       => $payment_method->billing_details->address->postal_code,
					'exp_month' => $payment_method->card->exp_month,
					'exp_year'  => $payment_method->card->exp_year,
					'last4'     => $payment_method->card->last4,
					'current'   => false
				);
			}
		}
	}

	// RCP 3.2+ uses payment methods now.
	try {
		$payment_method_id = $payment_method = false;
		$subscription_id   = $membership->get_gateway_subscription_id();

		if ( ! empty( $subscription_id ) ) {
			// Get payment method attached to subscription.
			$subscription      = \Stripe\Subscription::retrieve( $membership->get_gateway_subscription_id() );
			$payment_method_id = $subscription->default_payment_method;
		}

		if ( empty( $payment_method_id ) && $membership->get_gateway_customer_id() ) {
			// Get customer's default payment method.
			$customer          = \Stripe\Customer::retrieve( $membership->get_gateway_customer_id() );
			$payment_method_id = $customer->invoice_settings->default_payment_method;
		}

		if ( ! empty( $payment_method_id ) ) {
			$payment_method = \Stripe\PaymentMethod::retrieve( $payment_method_id );
		}
	} catch ( Exception $e ) {
		$payment_method = false;
	}

	if ( ! empty( $payment_method ) ) {
		if ( ! empty( $card_details[ $payment_method->id ] ) ) {
			$card_details[ $payment_method->id ]['current'] = true;
		} else {
			$card_details[ $payment_method->id ] = array(
				'id'        => $payment_method->id,
				'name'      => $payment_method->billing_details->name,
				'type'      => $payment_method->card->brand,
				'zip'       => $payment_method->billing_details->address->postal_code,
				'exp_month' => $payment_method->card->exp_month,
				'exp_year'  => $payment_method->card->exp_year,
				'last4'     => $payment_method->card->last4,
				'current'   => false
			);
		}
	} elseif ( $membership->get_gateway_customer_id() ) {
		// Try default source instead. This will have been saved pre-3.2.
		try {
			$customer = ! empty( $customer ) ? $customer : \Stripe\Customer::retrieve( $membership->get_gateway_customer_id() );
			$source = \Stripe\Source::retrieve( $customer->default_source );

			if ( empty( $source ) ) {
				throw new Exception( 'Source not found' );
			} else {
				if ( ! empty( $card_details[ $source->id ] ) ) {
					$card_details[ $source->id ]['current'] = true;
				} else {
					$card_details[ $source->id ] = array(
							'id'        => $source->id,
							'name'      => $source->name,
							'type'      => $source->brand,
							'zip'       => $source->address_zip,
							'exp_month' => $source->exp_month,
							'exp_year'  => $source->exp_year,
							'last4'     => $source->last4,
							'current'   => true
					);
				}
			}
		} catch ( Exception $e ) {
		}
	}

	return $card_details;

}

add_filter( 'rcp_membership_get_card_details', 'rcp_stripe_get_membership_card_details', 10, 3 );

/**
 * Get the saved Stripe payment methods for a given user ID.
 *
 * @param int $user_id ID of the user to get the payment methods for. Use 0 for currently logged in user.
 *
 * @since 3.3
 * @return \Stripe\PaymentMethod[]|array
 */
function rcp_stripe_get_user_saved_payment_methods( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	static $existing_payment_methods;

	if ( ! is_null( $existing_payment_methods ) && array_key_exists( $user_id, $existing_payment_methods ) ) {
		// Payment methods have already been retrieved for this user -- return them now.
		return $existing_payment_methods[ $user_id ];
	}

	$customer_payment_methods = array();

	$customer = rcp_get_customer_by_user_id( $user_id );

	try {

		if ( empty( $customer ) ) {
			throw new Exception( __( 'User is not a customer.', 'rcp' ) );
		}
		$stripe_customer_id = rcp_get_customer_gateway_id( $customer->get_id(), array(
			'stripe',
			'stripe_checkout'
		) );

		if ( empty( $stripe_customer_id ) ) {
			throw new Exception( __( 'User is not a Stripe customer.', 'rcp' ) );
		}

		global $rcp_options;

		if ( ! class_exists( 'Stripe\Stripe' ) ) {
			require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
		}

		if ( rcp_is_sandbox() ) {
			$secret_key = isset( $rcp_options['stripe_test_secret'] ) ? trim( $rcp_options['stripe_test_secret'] ) : '';
		} else {
			$secret_key = isset( $rcp_options['stripe_live_secret'] ) ? trim( $rcp_options['stripe_live_secret'] ) : '';
		}

		if ( empty( $secret_key ) ) {
			throw new Exception( __( 'Missing Stripe secret key.', 'rcp' ) );
		}

		\Stripe\Stripe::setApiKey( $secret_key );

		$payment_methods = \Stripe\PaymentMethod::all( array(
			'customer' => $stripe_customer_id,
			'type'     => 'card'
		) );

		if ( empty( $payment_methods ) ) {
			throw new Exception( __( 'User does not have any saved payment methods.', 'rcp' ) );
		}

		foreach ( $payment_methods->data as $payment_method ) {
			/**
			 * @var \Stripe\PaymentMethod $payment_method
			 */
			$customer_payment_methods[ $payment_method->id ] = $payment_method;
		}

	} catch ( Exception $e ) { }

	$existing_payment_methods[ $user_id ] = $customer_payment_methods;

	return $existing_payment_methods[ $user_id ];

}

/**
 * Sends a new user notification email when using the [register_form_stripe] shortcode.
 *
 * @param int                        $user_id ID of the user.
 * @param RCP_Payment_Gateway_Stripe $gateway Stripe gateway object.
 *
 * @since 2.7
 * @return void
 */
function rcp_stripe_checkout_new_user_notification( $user_id, $gateway ) {

	if ( 'stripe' === $gateway->subscription_data['post_data']['rcp_gateway'] && ! empty( $gateway->subscription_data['post_data']['rcp_stripe_checkout'] ) && $gateway->subscription_data['new_user'] ) {

		/**
		 * After the password reset key is generated and before the email body is created,
		 * add our filter to replace the URLs in the email body.
		 */
		add_action( 'retrieve_password_key', function() {

			add_filter( 'wp_mail', function( $args ) {

				global $rcp_options;

				if ( ! empty( $rcp_options['hijack_login_url'] ) && ! empty( $rcp_options['login_redirect'] ) ) {

					// Rewrite the password reset link
					$args['message'] = str_replace( trailingslashit( network_site_url() ) . 'wp-login.php?action=rp', get_permalink( $rcp_options['login_redirect'] ) . '?rcp_action=lostpassword_reset', $args['message'] );

				}

				return $args;

			});

		});

		wp_new_user_notification( $user_id, null, 'user' );

	}

}
add_action( 'rcp_stripe_signup', 'rcp_stripe_checkout_new_user_notification', 10, 2 );

/**
 * Cancel a Stripe membership by its subscription ID.
 *
 * @param string $payment_profile_id
 *
 * @since 3.0
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function rcp_stripe_cancel_membership( $payment_profile_id ) {

	global $rcp_options;

	if ( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
	}

	if ( rcp_is_sandbox() ) {
		$secret_key = trim( $rcp_options['stripe_test_secret'] );
	} else {
		$secret_key = trim( $rcp_options['stripe_live_secret'] );
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	try {
		$sub = \Stripe\Subscription::retrieve( $payment_profile_id );
		$sub->cancel();

		$success = true;
	} catch ( \Stripe\Error\InvalidRequest $e ) {

		// Invalid parameters were supplied to Stripe's API
		$body = $e->getJsonBody();
		$err  = $body['error'];

		rcp_log( sprintf( 'Failed to cancel Stripe payment profile %s. Error code: %s; Error Message: %s.', $payment_profile_id, $err['code'], $err['message'] ) );

		$success = new WP_Error( $err['code'], $err['message'] );

	} catch ( \Stripe\Error\Authentication $e ) {

		// Authentication with Stripe's API failed
		// (maybe you changed API keys recently)

		$body = $e->getJsonBody();
		$err  = $body['error'];

		rcp_log( sprintf( 'Failed to cancel Stripe payment profile %s. Error code: %s; Error Message: %s.', $payment_profile_id, $err['code'], $err['message'] ) );

		$success = new WP_Error( $err['code'], $err['message'] );

	} catch ( \Stripe\Error\ApiConnection $e ) {

		// Network communication with Stripe failed

		$body = $e->getJsonBody();
		$err  = $body['error'];

		rcp_log( sprintf( 'Failed to cancel Stripe payment profile %s. Error code: %s; Error Message: %s.', $payment_profile_id, $err['code'], $err['message'] ) );

		$success = new WP_Error( $err['code'], $err['message'] );

	} catch ( \Stripe\Exception\ApiErrorException $e ) {

		// Display a very generic error to the user

		$body = $e->getJsonBody();
		$err  = $body['error'];

		rcp_log( sprintf( 'Failed to cancel Stripe payment profile %s. Error code: %s; Error Message: %s.', $payment_profile_id, $err['code'], $err['message'] ) );

		$success = new WP_Error( $err['code'], $err['message'] );

	} catch ( Exception $e ) {

		// Something else happened, completely unrelated to Stripe

		rcp_log( sprintf( 'Failed to cancel Stripe payment profile f%s. Error: %s.', $payment_profile_id, $e ) );

		$success = new WP_Error( 'unknown_error', $e );

	}

	return $success;

}

/**
 * Enqueue shared scripts.
 *
 * @since 3.1.0
 */
function rcp_stripe_enqueue_scripts( $localize = array() ) {
	// Stripe API.
	wp_enqueue_script(
		'stripe-js-v3',
		'https://js.stripe.com/v3/',
		array(),
		'3'
	);

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	wp_enqueue_script(
		'rcp-stripe',
		RCP_PLUGIN_URL . 'core/includes/gateways/stripe/js/stripe' . $suffix . '.js',
		array(
			'stripe-js-v3'
		),
		RCP_PLUGIN_VERSION
	);

	$localize = wp_parse_args(
		array(
			'formatting'     => array(
				'currencyMultiplier' => rcp_stripe_get_currency_multiplier(),
			),
			'elementsConfig' => null,
		),
		$localize
	);

	/**
	 * Filter the data made available to the Stripe scripts.
	 *
	 * @since 3.1.0
	 *
	 * @param array $localize Localization data.
	 */
	$localize = apply_filters( 'rcp_stripe_scripts', $localize );

	wp_localize_script(
		'rcp-stripe',
		'rcpStripe',
		$localize
	);
}

/**
 * When an initial charge fails we need to manually trigger the `rcp_registration_failed` action.
 * This is because our charges happen outside the main gateway class.
 *
 * @since 3.2
 * @return void
 */
function rcp_stripe_handle_initial_payment_failure() {

	// Verify nonce for CSRF protection.
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'rcp_process_stripe_payment' ) ) {
		wp_send_json_error( __( 'Security verification failed.', 'rcp' ) );
	}

	$payment_id = ! empty( $_POST['payment_id'] ) ? absint( wp_unslash( $_POST['payment_id'] ) ) : 0;

	if ( empty( $payment_id ) ) {
		wp_send_json_error( __( 'Missing payment ID.', 'rcp' ) );
	}

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	$payment = $rcp_payments_db->get_payment( $payment_id );

	if ( empty( $payment ) || ! is_object( $payment ) ) {
		wp_send_json_error( __( 'Invalid payment.', 'rcp' ) );
	}

	// Security check: Verify user ownership of the payment.
	$current_user_id = get_current_user_id();
	if ( empty( $current_user_id ) || absint( $payment->user_id ) !== $current_user_id ) {
		wp_send_json_error( __( 'You do not have permission to perform this action.', 'rcp' ) );
	}

	// Only allow marking payments as failed if they are in pending status.
	if ( 'pending' !== strtolower( $payment->status ) ) {
		wp_send_json_error( __( 'This payment cannot be marked as failed.', 'rcp' ) );
	}

	// Verify the membership belongs to the current user.
	if ( ! empty( $payment->membership_id ) ) {
		$membership = rcp_get_membership( absint( $payment->membership_id ) );
		if ( empty( $membership ) || absint( $membership->get_customer()->get_user_id() ) !== $current_user_id ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'rcp' ) );
		}
	}

	/**
	 * Fires before processing a payment failure.
	 *
	 * Can be used to implement additional security checks like rate limiting.
	 *
	 * @since 3.5.55
	 *
	 * @param object $payment Payment object.
	 * @param int    $user_id Current user ID.
	 */
	do_action( 'rcp_before_stripe_handle_payment_failure', $payment, $current_user_id );

	$gateway = new RCP_Payment_Gateway_Stripe();

	// Set some of the expected properties.
	$gateway->payment       = $payment;
	$gateway->user_id       = $payment->user_id;
	$gateway->membership    = rcp_get_membership( absint( $payment->membership_id ) );
	$gateway->error_message = ! empty( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : __( 'Unknown error', 'rcp' );

	do_action( 'rcp_registration_failed', $gateway );

	$error = array(
		'message' => $gateway->error_message,
		'type'    => 'other',
		'param'   => false,
		'code'    => 'other'
	);

	do_action( 'rcp_stripe_signup_payment_failed', $error, $gateway );

	wp_send_json_success();

}

add_action( 'wp_ajax_rcp_stripe_handle_initial_payment_failure', 'rcp_stripe_handle_initial_payment_failure' );
add_action( 'wp_ajax_nopriv_rcp_stripe_handle_initial_payment_failure', 'rcp_stripe_handle_initial_payment_failure' );

/**
 * Issue a payment nonce for the current user.
 *
 * Registration creates the account and logs the visitor in mid flow, so the nonce printed with
 * the page was minted for the logged out visitor and no longer verifies. Nonces cannot be
 * regenerated in the request that sets the auth cookie either, because the session token is not
 * readable until the browser sends it back.
 *
 * @since 4.0.6
 *
 * @return void
 */
function rcp_stripe_generate_payment_nonce() {

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to perform this action.', 'rcp' ) );
	}

	wp_send_json_success( wp_create_nonce( 'rcp_process_stripe_payment' ) );
}

add_action( 'wp_ajax_rcp_stripe_generate_payment_nonce', 'rcp_stripe_generate_payment_nonce' );

/**
 * Create a setup intent while saving a new billing card.
 *
 * This is slightly different from `rcp_stripe_create_payment_intent()` because we get the Stripe customer
 * ID from the posted membership_id and attach that to the payment intent.
 *
 * @since 3.2
 * @return void
 */
function rcp_stripe_create_setup_intent_for_saved_card() {
	check_ajax_referer( 'rcp_stripe_create_setup_intent_for_saved_card', 'nonce' );

	// Check if the user is at least a registered user.
	if ( ! current_user_can( 'read' ) ) {
		wp_send_json_error( __( 'You are not authorized to perform this action.', 'rcp' ) );
	}

	global $rcp_options;

	if ( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
	}

	if ( rcp_is_sandbox() ) {
		$secret_key = trim( $rcp_options['stripe_test_secret'] );
	} else {
		$secret_key = trim( $rcp_options['stripe_live_secret'] );
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	$membership = ! empty( $_POST['membership_id'] ) ? rcp_get_membership( absint( $_POST['membership_id'] ) ) : false;

	if ( empty( $membership ) ) {
		wp_send_json_error( __( 'Missing membership ID.', 'rcp' ) );
	}

	$subscription_id = $membership->get_gateway_subscription_id();
	$customer_id     = $membership->get_gateway_customer_id();

	$create_setup_intent = true;
	$intent              = false;

	try {
		if ( ! empty( $subscription_id ) ) {
			/*
			 * See if the customer has a "past_due" subscription that requires a new payment method or
			 * requires action on an existing payment method. If so, let's use the payment intent that
			 * Stripe has already created.
			 */
			$subscription = \Stripe\Subscription::retrieve( $subscription_id );

			if ( 'past_due' === $subscription->status ) {
				$invoices = \Stripe\Invoice::all( array(
					'status'       => 'open',
					'subscription' => $subscription_id
				) );

				if ( $invoices ) {
					foreach ( $invoices as $invoice ) {
						/*
						 * We loop through all open invoices until we get a payment intent with the expected status.
						 * We do this because Stripe may not actually have a PI for all invoices if there are multiple
						 * unpaid ones that have built up.
						 */
						if ( empty( $invoice->payment_intent ) ) {
							continue;
						}

						$payment_intent = \Stripe\PaymentIntent::retrieve( $invoice->payment_intent );

						/*
						 * If we can't access the `client_secret`, then RCP won't be able to complete this payment
						 * intent due to Stripe limitations. We'll need a setup intent instead.
						 *
						 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/2658
						 */
						if ( empty( $payment_intent->client_secret ) ) {
							continue;
						}

						$intent_statuses = array(
							'requires_action',
							'requires_source_action',
							'requires_payment_method',
							'requires_source'
						);

						if ( in_array( $payment_intent->status, $intent_statuses ) ) {
							// Use this existing payment intent.
							$create_setup_intent = false;
							$intent              = $payment_intent;

							if ( ! empty( $_POST['payment_method_id'] ) && 'new' !== $_POST['payment_method_id'] && $_POST['payment_method_id'] != $intent->payment_method ) {
								\Stripe\PaymentIntent::update( $intent->id, array(
									'payment_method' => sanitize_text_field( $_POST['payment_method_id'] )
								) );
							}

							break;
						}
					}
				}
			}
		}
	} catch ( Exception $e ) { }

	if ( $create_setup_intent ) {
		if ( empty( $customer_id ) || false === strpos( $customer_id, 'cus_' ) ) {
			wp_send_json_error( __( 'Invalid Stripe customer ID.', 'rcp' ) );
		}

		try {
			/*
			 * The customer is just generically updating their card details, so we can creaste a new
			 * setup intent.
			 */
			$intent_options         = array();
			$stripe_connect_user_id = get_option( 'rcp_stripe_connect_account_id', false );

			if ( ! empty( $stripe_connect_user_id ) ) {
				$options['stripe_account'] = $stripe_connect_user_id;
			}

			$intent_args = array(
				'usage'    => 'off_session',
				'customer' => $customer_id
			);

			if ( ! empty( $_POST['payment_method_id'] ) && 'new' !== $_POST['payment_method_id'] ) {
				$intent_args['payment_method'] = sanitize_text_field( $_POST['payment_method_id'] );
			}

			$intent = \Stripe\SetupIntent::create( $intent_args, $intent_options );
		} catch ( Exception $e ) {
			$intent = false;
		}
	}

	if ( ! empty( $intent ) ) {
		wp_send_json_success( array(
			'success'                      => true,
			'payment_intent_client_secret' => $intent->client_secret,
			'payment_intent_id'            => $intent->id,
			'payment_intent_object'        => $intent->object
		) );
	}

	wp_send_json_error( __( 'Error creating setup intent.', 'rcp' ) );

}

add_action( 'wp_ajax_rcp_stripe_create_setup_intent_for_saved_card', 'rcp_stripe_create_setup_intent_for_saved_card' );
add_action( 'wp_ajax_nopriv_rcp_stripe_create_setup_intent_for_saved_card', 'rcp_stripe_create_setup_intent_for_saved_card' );

/**
 * Add a "Delete" link for each card.
 *
 * @param array          $card       Array of card details.
 * @param RCP_Membership $membership Membership object.
 *
 * @since 3.3
 * @return void
 */
function rcp_stripe_maybe_add_delete_card_link( $card, $membership ) {

	// We need an ID to delete.
	if ( empty( $card['id'] ) ) {
		return;
	}

	/**
	 * Whether or not cards can be deleted.
	 *
	 * @param bool           $can_delete
	 * @param array          $card
	 * @param RCP_Membership $membership
	 */
	$can_delete = apply_filters( 'rcp_can_delete_saved_card', true, $card, $membership );

	if ( ! $can_delete ) {
		return;
	}
	?>
	<span class="rcp-gateway-saved-payment-method-sep">&mdash; </span>
	<span class="rcp-gateway-saved-card-delete">
		<a href="#" data-id="<?php echo esc_attr( $card['id'] ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'rcp_delete_stripe_card' ) ); ?>"><?php _e( 'Delete', 'rcp' ); ?></a>
	</span>
	<?php

}

add_action( 'rcp_update_billing_card_list_item', 'rcp_stripe_maybe_add_delete_card_link', 10, 2 );

/**
 * Delete a saved payment method
 *
 * @since 3.3
 * @return void
 */
function rcp_stripe_delete_saved_payment_method() {

	check_ajax_referer( 'rcp_delete_stripe_card', 'nonce' );

	if ( empty( $_POST['payment_method_id'] ) ) {
		wp_send_json_error( __( 'Missing payment method ID.', 'rcp' ) );
	}

	$payment_method_id  = $_POST['payment_method_id'];
	$customer           = rcp_get_customer_by_user_id( get_current_user_id() );
	$stripe_customer_id = ! empty( $customer ) ? rcp_get_customer_gateway_id( $customer->get_id(), array( 'stripe', 'stripe_checkout' ) ) : false;

	if ( empty( $stripe_customer_id ) ) {
		wp_send_json_error( __( 'Invalid or unknown Stripe customer ID.', 'rcp' ) );
	}

	global $rcp_options;

	if ( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
	}

	if ( rcp_is_sandbox() ) {
		$secret_key = trim( $rcp_options['stripe_test_secret'] );
	} else {
		$secret_key = trim( $rcp_options['stripe_live_secret'] );
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	try {
		if ( 'pm_' === substr( $payment_method_id, 0, 3 ) ) {
			// Delete a payment method (RCP 3.2+)
			$payment_method = \Stripe\PaymentMethod::retrieve( sanitize_text_field( $payment_method_id ) );

			if ( $payment_method->customer != $stripe_customer_id ) {
				wp_send_json_error( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			// Stripe currently only supports `detach()`, it does not support `delete()` for payment methods.
			$payment_method->detach();
		} else {
			// Delete a card (pre-3.2)
			\Stripe\Customer::deleteSource( $stripe_customer_id, sanitize_text_field( $payment_method_id ) );
		}

		wp_send_json_success();
	} catch( \Stripe\Exception\ApiErrorException $e ) {
		$body  = $e->getJsonBody();
		$error = $body['error'];

		wp_send_json_error( $error['message'] );
	} catch ( Exception $e ) {
		wp_send_json_error( __( 'An unknown error occurred.', 'rcp' ) );
	}

	return;
}
add_action( 'wp_ajax_rcp_stripe_delete_saved_payment_method', 'rcp_stripe_delete_saved_payment_method' );

/**
 * [register_form_stripe] always enforces auto renew unless not allowed.
 * This mostly makes it so if auto renew is set to "let the customer choose"
 * then we actually always auto renew because [register_form_stripe] doesn't
 * offer the choice.
 *
 * @param bool $auto_renew
 *
 * @since 3.2
 * @return bool
 */
function rcp_stripe_modal_always_recurring( $auto_renew ) {

	if ( $auto_renew || empty( $_POST['rcp_gateway'] ) || 'stripe' !== $_POST['rcp_gateway'] || empty( $_POST['rcp_stripe_checkout'] ) || empty( $_POST['rcp_level'] ) ) {
		return $auto_renew;
	}

	$membership_level = rcp_get_membership_level( absint( $_POST['rcp_level'] ) );

	if ( ! $membership_level instanceof Membership_Level ) {
		return $auto_renew;
	}

	if ( $membership_level->is_lifetime() || $membership_level->is_free() ) {
		return $auto_renew;
	}

	if ( '2' === rcp_get_auto_renew_behavior() ) {
		return $auto_renew;
	}

	return true;

}
add_filter( 'rcp_registration_is_recurring', 'rcp_stripe_modal_always_recurring' );

/**
 * Get localized versions of Stripe's error messages
 *
 * @since 3.2.3
 * @return array
 */
function rcp_stripe_get_localized_error_messages() {

	$messages = array(
		'api_key_expired'                            => __( 'Payment gateway connection error.', 'rcp' ),
		'card_declined'                              => __( 'The card has been declined.', 'rcp' ),
		'email_invalid'                              => __( 'Invalid email address. Please enter a valid email address and try again.', 'rcp' ),
		'expired_card'                               => __( 'This card has expired. Please try again with a different payment method.', 'rcp' ),
		'incorrect_address'                          => __( 'The supplied billing address is incorrect. Please check the card\'s address or try again with a different card.', 'rcp' ),
		'incorrect_cvc'                              => __( 'The card\'s security code is incorrect. Please check the security code or try again with a different card.', 'rcp' ),
		'incorrect_number'                           => __( 'The card number is incorrect. Please check the card number or try again with a different card.', 'rcp' ),
		'invalid_number'                             => __( 'The card number is incorrect. Please check the card number or try again with a different card.', 'rcp' ),
		'incorrect_zip'                              => __( 'The card\'s postal code is incorrect. Please check the postal code or try again with a different card.', 'rcp' ),
		'postal_code_invalid'                        => __( 'The card\'s postal code is incorrect. Please check the postal code or try again with a different card.', 'rcp' ),
		'invalid_cvc'                                => __( 'The card\'s security code is invalid. Please check the security code or try again with a different card.', 'rcp' ),
		'invalid_expiry_month'                       => __( 'The card\'s expiration month is incorrect.', 'rcp' ),
		'invalid_expiry_year'                        => __( 'The card\'s expiration year is incorrect.', 'rcp' ),
		'payment_intent_authentication_failure'      => __( 'Authentication failure.', 'rcp' ),
		'payment_intent_incompatible_payment_method' => __( 'This payment method is invalid.', 'rcp' ),
		'payment_intent_payment_attempt_failed'      => __( 'Payment attempt failed.', 'rcp' ),
		'setup_intent_authentication_failure'        => __( 'Setup attempt failed.', 'rcp' )
	);

	/**
	 * Filters the localized error messages.
	 *
	 * @param array $messages
	 *
	 * @since 3.2.3
	 */
	return apply_filters( 'rcp_stripe_error_messages', $messages );

}

/**
 * Cancel Stripe subscriptions after deleting a Membership Level.
 *
 * @since 3.5.40
 *
 * @param int            $membership_id ID of the membership.
 * @param RCP_Membership $membership    Membership object.
 *
 * @return void
 */
function rcp_stripe_cancel_subscriptions_after_deleting_level( $membership_id, $membership ): void {
	$gateways = [
		'stripe',
		'stripe_checkout',
	];

	// Bail if this membership doesn't use Stripe.
	if ( ! in_array( $membership->get_gateway(), $gateways, true ) ) {
		return;
	}

	$id = $membership->get_gateway_subscription_id();

	// Stop if this membership doesn't have a gateway subscription ID.
	if ( empty( $id ) ) {
		return;
	}

	rcp_stripe_cancel_membership( $id );
}

add_action( 'rcp_membership_pre_cancel', 'rcp_stripe_cancel_subscriptions_after_deleting_level', 10, 2 );

/**
 * Normalize a Stripe ID that may be either a string or an expanded object.
 *
 * @since 4.0.6
 *
 * @param mixed $value String ID, expanded StripeObject, or null.
 *
 * @return string The ID, or an empty string.
 */
function rcp_stripe_normalize_id( $value ): string {
	if ( is_string( $value ) ) {
		return $value;
	}

	if ( is_object( $value ) && ! empty( $value->id ) && is_string( $value->id ) ) {
		return $value->id;
	}

	return '';
}

/**
 * Safely read a key from a Stripe object or array.
 *
 * Property syntax must be avoided for optional keys: \Stripe\StripeObject::__get() writes to
 * the error log when the key is absent, which would mean a log line on every renewal.
 *
 * @since 4.0.6
 *
 * @param mixed  $source Stripe object, array, or anything else.
 * @param string $key    Key to read.
 *
 * @return mixed The value, or null if it is absent or the source is not readable.
 */
function rcp_stripe_object_get( $source, string $key ) {
	if ( is_array( $source ) ) {
		return array_key_exists( $key, $source ) ? $source[ $key ] : null;
	}

	if ( $source instanceof \ArrayAccess ) {
		return $source->offsetExists( $key ) ? $source->offsetGet( $key ) : null;
	}

	return null;
}

/**
 * Read an ID held under a key on a Stripe object.
 *
 * @since 4.0.6
 *
 * @param mixed  $source Stripe object or array.
 * @param string $key    Key holding a string ID or an expanded object.
 *
 * @return string
 */
function rcp_stripe_object_get_id( $source, string $key ): string {
	return rcp_stripe_normalize_id( rcp_stripe_object_get( $source, $key ) );
}

/**
 * Read a numeric amount held under a key on a Stripe object.
 *
 * @since 4.0.6
 *
 * @param mixed  $source Stripe object or array.
 * @param string $key    Key holding an amount in the currency's smallest unit.
 *
 * @return float The amount, or 0 if absent or not numeric.
 */
function rcp_stripe_object_get_amount( $source, string $key ): float {
	$value = rcp_stripe_object_get( $source, $key );

	return is_numeric( $value ) ? (float) $value : 0.0;
}

/**
 * Determine whether a paid invoice represents a recurring renewal.
 *
 * `subscription_create` must be excluded. RCP takes the initial payment with a standalone
 * PaymentIntent and creates the subscription with a future billing anchor, so signup emits a
 * separate invoice. Treating it as a renewal can complete a still-pending initial payment
 * with an amount of 0. Where such an invoice carries a real charge, `charge.succeeded`
 * already handles it.
 *
 * @since 4.0.6
 *
 * @param mixed $invoice Invoice object from the webhook.
 *
 * @return bool
 */
function rcp_stripe_invoice_is_renewal( $invoice ): bool {
	$default = array(
		'subscription_cycle', // A subscription advancing into a new period.
		'subscription',       // Legacy value on older invoices.
	);

	/**
	 * Filters the invoice `billing_reason` values treated as recurring renewals.
	 *
	 * @since 4.0.6
	 *
	 * @param string[] $billing_reasons Allowed billing reasons.
	 * @param mixed    $invoice         The invoice being evaluated.
	 */
	$allowed = apply_filters( 'rcp_stripe_invoice_renewal_billing_reasons', $default, $invoice );
	$allowed = array_filter( (array) $allowed, 'is_string' );

	if ( empty( $allowed ) ) {
		$allowed = $default;
	}

	$billing_reason = rcp_stripe_object_get( $invoice, 'billing_reason' );

	/*
	 * Stripe has set a billing reason on every invoice since 2018. If one is missing the
	 * rendering is unusual, so only treat it as a renewal when the invoice belongs to a
	 * subscription. Failing open here would let signup invoices renew memberships.
	 */
	if ( ! is_string( $billing_reason ) || '' === $billing_reason ) {
		return '' !== rcp_stripe_object_get_id( $invoice, 'subscription' );
	}

	return in_array( $billing_reason, $allowed, true );
}

/**
 * Get the charge ID belonging to a PaymentIntent.
 *
 * Makes one API call and never throws. An empty return tells the caller to defer to the
 * `charge.succeeded` webhook; letting the exception escape would return HTTP 500 to Stripe,
 * which triggers retries and eventually disables the endpoint.
 *
 * @since 4.0.6
 *
 * @param string $payment_intent_id PaymentIntent ID.
 *
 * @return string Charge ID, or an empty string if it could not be resolved.
 */
function rcp_stripe_get_payment_intent_charge_id( string $payment_intent_id ): string {
	if ( empty( $payment_intent_id ) ) {
		return '';
	}

	/**
	 * Short circuits resolving a PaymentIntent's charge ID, skipping the API call.
	 *
	 * @since 4.0.6
	 *
	 * @param string|null $charge_id         Charge ID to return, or null to query the API.
	 * @param string      $payment_intent_id The PaymentIntent being resolved.
	 */
	$pre = apply_filters( 'rcp_stripe_pre_get_payment_intent_charge_id', null, $payment_intent_id );

	if ( null !== $pre ) {
		return (string) $pre;
	}

	try {
		$intent = \Stripe\PaymentIntent::retrieve( $payment_intent_id );
	} catch ( Exception $e ) {
		rcp_log( sprintf( 'Stripe Gateway: could not retrieve PaymentIntent %s. %s', $payment_intent_id, $e->getMessage() ), true );

		return '';
	}

	return rcp_stripe_get_payment_intent_charge_id_from_object( $intent );
}

/**
 * Extract the charge ID from a PaymentIntent object.
 *
 * The gateway pins Stripe API version 2020-08-27, which exposes an expanded `charges` list.
 * `latest_charge` is read as well so this keeps working if the pinned version is raised to
 * 2022-11-15 or later, where `charges` was removed.
 *
 * @since 4.0.6
 *
 * @param mixed $intent PaymentIntent object.
 *
 * @return string Charge ID, or an empty string.
 */
function rcp_stripe_get_payment_intent_charge_id_from_object( $intent ): string {
	$charges = rcp_stripe_object_get( rcp_stripe_object_get( $intent, 'charges' ), 'data' );

	if ( is_array( $charges ) && ! empty( $charges ) ) {
		$charge_id = rcp_stripe_normalize_id( reset( $charges ) );

		if ( '' !== $charge_id ) {
			return $charge_id;
		}
	}

	return rcp_stripe_object_get_id( $intent, 'latest_charge' );
}

/**
 * Determine the transaction ID to record for a paid invoice.
 *
 * Stripe delivers both `charge.succeeded` and `invoice.payment_succeeded` for a single
 * renewal and RCP processes both. The only idempotency guard is
 * RCP_Payments::payment_exists() on the transaction ID, so this must return the same value
 * the `charge.succeeded` handler records, which is the charge ID. `charge.refunded` also
 * looks payments up by charge ID.
 *
 * @since 4.0.6
 *
 * @param mixed $invoice Invoice object.
 *
 * @return string One of:
 *                - The charge ID, whenever the invoice has an associated charge.
 *                - The invoice ID, when Stripe recorded neither a charge nor a PaymentIntent.
 *                  Nothing was charged, so no `charge.succeeded` will ever fire, and this is
 *                  the only way a credit balance or zero amount renewal gets recorded.
 *                - An empty string, when a payment exists but its charge could not be
 *                  resolved. The caller must do nothing and let `charge.succeeded` handle the
 *                  renewal rather than record an ID that will not match.
 */
function rcp_stripe_get_invoice_transaction_id( $invoice ): string {
	$charge_id = rcp_stripe_object_get_id( $invoice, 'charge' );

	if ( '' !== $charge_id ) {
		return $charge_id;
	}

	$intent_id = rcp_stripe_object_get_id( $invoice, 'payment_intent' );

	if ( '' !== $intent_id ) {
		// A PaymentIntent exists, so a charge exists or is about to. Never guess an ID here.
		return rcp_stripe_get_payment_intent_charge_id( $intent_id );
	}

	return rcp_stripe_object_get_id( $invoice, 'id' );
}

/**
 * Build the option name used for a Stripe webhook processing lock.
 *
 * @since 4.0.6
 *
 * @param string $key Lock key, usually a transaction ID.
 *
 * @return string
 */
function rcp_stripe_get_webhook_lock_name( string $key ): string {
	return 'rcp_stripe_webhook_lock_' . md5( $key );
}

/**
 * Attempt to acquire an exclusive lock for processing a Stripe webhook.
 *
 * Both webhooks for a renewal arrive at once and the caller makes an API call before
 * inserting the payment, so without this both requests can pass the duplicate check.
 *
 * Uses `INSERT IGNORE` against the options table, whose `option_name` column carries a UNIQUE
 * key, so exactly one caller can win. Raw SQL keeps the options cache from masking the
 * result. wp_cache_add() is not a substitute: it is only atomic with a persistent object
 * cache and silently becomes a no-op without one.
 *
 * @since 4.0.6
 *
 * @param string $key     Lock key, usually a transaction ID.
 * @param string $token   Caller token, written as the value so only the owner can release it.
 * @param int    $timeout Seconds after which an existing lock is considered abandoned.
 *
 * @return bool True if the lock was acquired.
 */
function rcp_stripe_acquire_webhook_lock( string $key, string $token, int $timeout = 300 ): bool {
	global $wpdb;

	if ( '' === $key || '' === $token ) {
		return false;
	}

	$lock_name = rcp_stripe_get_webhook_lock_name( $key );
	$value     = time() . ':' . $token;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES ( %s, %s, 'no' )",
			$lock_name,
			$value
		)
	);

	$inserted = (int) $wpdb->rows_affected;

	if ( 1 === $inserted ) {
		return true;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$existing = $wpdb->get_var( $wpdb->prepare( "SELECT `option_value` FROM `$wpdb->options` WHERE `option_name` = %s LIMIT 1", $lock_name ) );

	if ( null === $existing ) {
		return false;
	}

	$started = (int) strtok( (string) $existing, ':' );

	if ( ( $started + $timeout ) > time() ) {
		return false;
	}

	/*
	 * The holder abandoned the lock. Take it over conditionally on the value just read, so
	 * that only one of several waiting callers succeeds.
	 */
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE `$wpdb->options` SET `option_value` = %s WHERE `option_name` = %s AND `option_value` = %s",
			$value,
			$lock_name,
			$existing
		)
	);

	$stolen = (int) $wpdb->rows_affected;

	return 1 === $stolen;
}

/**
 * Release a Stripe webhook processing lock.
 *
 * Scoped to the caller's token. A request that stalled past the timeout and had its lock
 * taken over must not delete the new owner's row.
 *
 * @since 4.0.6
 *
 * @param string $key   Lock key, usually a transaction ID.
 * @param string $token The token the lock was acquired with.
 *
 * @return void
 */
function rcp_stripe_release_webhook_lock( string $key, string $token ): void {
	global $wpdb;

	if ( '' === $key || '' === $token ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM `$wpdb->options` WHERE `option_name` = %s AND `option_value` LIKE %s",
			rcp_stripe_get_webhook_lock_name( $key ),
			'%:' . $wpdb->esc_like( $token )
		)
	);
}

/**
 * Delete Stripe webhook locks left behind by requests that never reached shutdown.
 *
 * A lock is released on `shutdown`, so one only survives when the process dies outright, for
 * example on a fatal error or a hard timeout. Those rows are harmless because
 * rcp_stripe_acquire_webhook_lock() takes over anything past its timeout, but nothing would
 * ever remove them.
 *
 * @since 4.0.6
 *
 * @param int $max_age Seconds after which a lock is considered garbage.
 *
 * @return int Number of locks deleted.
 */
function rcp_stripe_cleanup_webhook_locks( int $max_age = DAY_IN_SECONDS ): int {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM `$wpdb->options`
			 WHERE `option_name` LIKE %s
			 AND CAST( SUBSTRING_INDEX( `option_value`, ':', 1 ) AS UNSIGNED ) < %d",
			$wpdb->esc_like( 'rcp_stripe_webhook_lock_' ) . '%',
			time() - $max_age
		)
	);

	$deleted = (int) $wpdb->rows_affected;

	if ( $deleted > 0 ) {
		rcp_log( sprintf( 'Stripe webhook: cleaned up %d abandoned webhook locks.', $deleted ) );
	}

	return $deleted;
}

/**
 * Cron callback for the webhook lock sweep.
 *
 * WordPress hands a callback an empty string when the action fires with no arguments, so the
 * typed function above cannot be hooked directly.
 *
 * @since 4.0.6
 *
 * @return void
 */
function rcp_stripe_run_webhook_lock_cleanup(): void {
	rcp_stripe_cleanup_webhook_locks();
}

add_action( 'rcp_stripe_cleanup_webhook_locks', 'rcp_stripe_run_webhook_lock_cleanup' );

/**
 * Read the currency code from a Stripe object.
 *
 * Returning an empty string leaves rcp_stripe_get_currency_multiplier() on the store
 * currency, which is the behaviour that applied before the currency was passed through.
 *
 * @since 4.0.6
 *
 * @param mixed $source Stripe object or array.
 *
 * @return string Currency code, or an empty string.
 */
function rcp_stripe_object_get_currency( $source ): string {
	$currency = rcp_stripe_object_get( $source, 'currency' );

	return is_string( $currency ) ? $currency : '';
}
