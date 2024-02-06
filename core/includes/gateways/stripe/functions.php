<?php
/**
 * Stripe Functions
 *
 * @package     Restrict Content Pro
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
 * @param RCP_Membership $membership
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

		exit;

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

	wp_redirect( add_query_arg( 'card', 'updated' ) ); exit;

}
add_action( 'rcp_update_membership_billing_card', 'rcp_stripe_update_membership_billing_card' );

/**
 * Return the multiplier for the currency. Most currencies are multiplied by 100. Zere decimal
 * currencies should not be multiplied so use 1.
 *
 * @param string $currency
 *
 * @since 2.5
 * @return int
 */
function rcp_stripe_get_currency_multiplier( $currency = '' ) {
	$multiplier = ( rcp_is_zero_decimal_currency( $currency ) ) ? 1 : 100;

	return apply_filters( 'rcp_stripe_get_currency_multiplier', $multiplier, $currency );
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

	$payment_id = ! empty( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;

	if ( empty( $payment_id ) ) {
		wp_send_json_error( __( 'Missing payment ID.', 'rcp' ) );
		exit;
	}

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	$payment = $rcp_payments_db->get_payment( $payment_id );

	if ( empty( $payment ) ) {
		wp_send_json_error( __( 'Invalid payment.', 'rcp' ) );
		exit;
	}

	$gateway = new RCP_Payment_Gateway_Stripe();

	// Set some of the expected properties.
	$gateway->payment       = $payment;
	$gateway->user_id       = $payment->user_id;
	$gateway->membership    = rcp_get_membership( absint( $payment->membership_id ) );
	$gateway->error_message = ! empty( $_POST['message'] ) ? sanitize_text_field( $_POST['message'] ) : __( 'Unknown error', 'rcp' );

	do_action( 'rcp_registration_failed', $gateway );

	$error = array(
		'message' => $gateway->error_message,
		'type'    => 'other',
		'param'   => false,
		'code'    => 'other'
	);

	do_action( 'rcp_stripe_signup_payment_failed', $error, $gateway );

	wp_send_json_success();
	exit;

}

add_action( 'wp_ajax_rcp_stripe_handle_initial_payment_failure', 'rcp_stripe_handle_initial_payment_failure' );
add_action( 'wp_ajax_nopriv_rcp_stripe_handle_initial_payment_failure', 'rcp_stripe_handle_initial_payment_failure' );

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
	exit;

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

	exit;

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
