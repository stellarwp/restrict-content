<?php
/**
 * Stripe Payment Gateway
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Gateways/Stripe
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
 */

use RCP\Membership_Level;

/**
 * Class RCP_Payment_Gateway_Stripe. This Class takes care of the Stripe integration.
 */
class RCP_Payment_Gateway_Stripe extends RCP_Payment_Gateway {

	protected $secret_key;
	protected $publishable_key;

	/**
	 * Get things going
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function init() {

		global $rcp_options;

		$this->supports[] = 'one-time';
		$this->supports[] = 'recurring';
		$this->supports[] = 'fees';
		$this->supports[] = 'gateway-submits-form';
		$this->supports[] = 'trial';
		$this->supports[] = 'price-changes';
		$this->supports[] = 'renewal-date-changes';
		$this->supports[] = 'subscription-creation'; // Creating subscriptions outside the registration form
		$this->supports[] = 'ajax-payment';
		$this->supports[] = 'card-updates';
		$this->supports[] = 'off-site-subscription-creation';
		$this->supports[] = 'expiration-extension-on-renewals'; // @link https://github.com/restrictcontentpro/restrict-content-pro/issues/1259

		if ( $this->test_mode ) {

			$this->secret_key      = isset( $rcp_options['stripe_test_secret'] ) ? trim( $rcp_options['stripe_test_secret'] ) : '';
			$this->publishable_key = isset( $rcp_options['stripe_test_publishable'] ) ? trim( $rcp_options['stripe_test_publishable'] ) : '';

		} else {

			$this->secret_key      = isset( $rcp_options['stripe_live_secret'] ) ? trim( $rcp_options['stripe_live_secret'] ) : '';
			$this->publishable_key = isset( $rcp_options['stripe_live_publishable'] ) ? trim( $rcp_options['stripe_live_publishable'] ) : '';

		}

		if ( ! class_exists( 'Stripe\Stripe' ) ) {
			require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
		}

		\Stripe\Stripe::setApiKey( $this->secret_key );

		\Stripe\Stripe::setApiVersion( '2020-08-27' );

		if ( method_exists( '\Stripe\Stripe', 'setAppInfo' ) ) {
			\Stripe\Stripe::setAppInfo( 'WordPress Restrict Content Pro', RCP_PLUGIN_VERSION, esc_url( site_url() ), 'pp_partner_DxPqC5fdD9vjrf' );
		}
	}

	/**
	 * Create a payment intent or setup intent.
	 *
	 * @since 3.2
	 * @return array|WP_Error
	 */
	public function process_ajax_signup() {

		/**
		 * @var RCP_Payments $rcp_payments_db
		 */
		global $rcp_payments_db, $rcp_options;

		$intent          = null;
		$stripe_customer = $this->get_or_create_customer( $this->membership->get_customer_id(), $this->membership->get_user_id() );

		if ( is_wp_error( $stripe_customer ) ) {
			return new WP_Error( $stripe_customer->get_error_code(), sprintf( __( 'Error creating Stripe customer: %s', 'rcp' ), $stripe_customer->get_error_message() ) );
		}

		$this->membership->set_gateway_customer_id( sanitize_text_field( $stripe_customer->id ) );

		$intent_args = array(
			'customer'    => $stripe_customer->id,
			'description' => sanitize_text_field( $this->membership->get_membership_level_name() ),
			'metadata'    => array(
				'email'                => $this->email,
				'user_id'              => absint( $this->user_id ),
				'level_id'             => absint( $this->membership->get_object_id() ),
				'level'                => sanitize_text_field( $this->membership->get_membership_level_name() ),
				'key'                  => sanitize_text_field( $this->membership->get_subscription_key() ),
				'membership_id'        => absint( $this->membership->get_id() ),
				'rcp_payment_id'       => absint( $this->payment->id ),
				'rcp_subscription_key' => $this->membership->get_subscription_key(),
			),
		);

		// Maybe add saved card.
		if ( ! empty( $_POST['rcp_gateway_existing_payment_method'] ) && 'new' !== $_POST['rcp_gateway_existing_payment_method'] ) {
			$intent_args['payment_method'] = sanitize_text_field( $_POST['rcp_gateway_existing_payment_method'] );
		}

		$intent_options         = array();
		$stripe_connect_user_id = get_option( 'rcp_stripe_connect_account_id', false );
		$payment_intent_id      = $rcp_payments_db->get_meta( $this->payment->id, 'stripe_payment_intent_id', true );
		$existing_intent        = false;

		if ( ! empty( $stripe_connect_user_id ) ) {
			$options['stripe_account'] = $stripe_connect_user_id;
		}

		try {
			if ( ! empty( $payment_intent_id ) && 'pi_' === substr( $payment_intent_id, 0, 3 ) ) {
				$existing_intent = \Stripe\PaymentIntent::retrieve( $payment_intent_id );
			} elseif ( ! empty( $payment_intent_id ) && 'seti_' === substr( $payment_intent_id, 0, 5 ) ) {
				$existing_intent = \Stripe\SetupIntent::retrieve( $payment_intent_id );
			}

			// We can't update canceled intents.
			if ( ! empty( $existing_intent ) && 'canceled' === $existing_intent->status ) {
				$existing_intent = false;
			}

			if ( ! empty( $this->initial_amount ) ) {
				// Create a payment intent.
				$intent_args = wp_parse_args(
					$intent_args,
					array(
						'amount'              => $this->initial_amount * rcp_stripe_get_currency_multiplier(),
						'confirmation_method' => 'automatic',
						'confirm'             => false,
						'currency'            => strtolower( rcp_get_currency() ),
						'setup_future_usage'  => 'off_session',
					)
				);

				/**
				 * @deprecated 3.2 In favour of `rcp_stripe_create_payment_intent_args`.
				 *
				 * @param array                      $args
				 * @param RCP_Payment_Gateway_Stripe $this
				 */
				$old_charge_args = apply_filters_deprecated(
					'rcp_stripe_charge_create_args',
					array(
						$intent_args,
						$this,
					),
					'3.2',
					'rcp_stripe_create_payment_intent_args'
				);

				// Grab a few compatible arguments from the old charges filter.
				$compatible_keys = array( 'amount', 'currency', 'customer', 'description', 'metadata' );
				foreach ( $compatible_keys as $compatible_key ) {
					$intent_args[ $compatible_key ] = ! empty( $old_charge_args[ $compatible_key ] ) ? $old_charge_args[ $compatible_key ] : $intent_args[ $compatible_key ];
				}

				if ( ! empty( $rcp_options['statement_descriptor'] ) ) {
					$intent_args['statement_descriptor'] = $rcp_options['statement_descriptor'];
				}

				if ( ! empty( $rcp_options['statement_descriptor_suffix'] ) ) {
					$intent_args['statement_descriptor_suffix'] = $rcp_options['statement_descriptor_suffix'];
				}

				/**
				 * Filters the payment intent arguments.
				 * This replaces the old `rcp_stripe_charge_create_args` filter.
				 *
				 * @since 3.2
				 */
				$intent_args = apply_filters( 'rcp_stripe_create_payment_intent_args', $intent_args, $this );

				if ( ! empty( $existing_intent ) && 'payment_intent' === $existing_intent->object ) {
					$idempotency_args                  = $intent_args;
					$idempotency_args['update']        = true;
					$intent_options['idempotency_key'] = rcp_stripe_generate_idempotency_key( $idempotency_args );

					// Unset some options we can't update.
					$unset_args = array( 'confirmation_method', 'confirm' );
					foreach ( $unset_args as $unset_arg ) {
						if ( isset( $intent_args[ $unset_arg ] ) ) {
							unset( $intent_args[ $unset_arg ] );
						}
					}

					$intent = \Stripe\PaymentIntent::update( $existing_intent->id, $intent_args, $intent_options );
				} else {
					$intent_options['idempotency_key'] = rcp_stripe_generate_idempotency_key( $intent_args );
					$intent                            = \Stripe\PaymentIntent::create( $intent_args, $intent_options );
				}
			} else {
				// Create a setup intent.
				$intent_args = wp_parse_args(
					$intent_args,
					array(
						'usage' => 'off_session',
					)
				);

				if ( empty( $existing_intent ) || 'setup_intent' !== $existing_intent->object ) {
					$intent_options['idempotency_key'] = rcp_stripe_generate_idempotency_key( $intent_args );
					$intent                            = \Stripe\SetupIntent::create( $intent_args, $intent_options );
				}
			}
		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			return $this->get_stripe_error( $e );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}

		// Store the payment intent ID with the payment.
		$rcp_payments_db->update_meta( $this->payment->id, 'stripe_payment_intent_id', sanitize_text_field( $intent->id ) );

		// Add the client secret to the JSON success data.
		return array(
			'stripe_client_secret' => sanitize_text_field( $intent->client_secret ),
			'stripe_intent_type'   => sanitize_text_field( $intent->object ),
		);

	}

	/**
	 * Process registration
	 *
	 * @access public
	 * @since  2.1
	 *
	 * @throws Exception If there was an error with the Stripe code.
	 *
	 * @return void
	 */
	public function process_signup() {

		/**
		 * @var RCP_Payments $rcp_payments_db
		 */
		global $rcp_payments_db;

		$payment_intent_id = $rcp_payments_db->get_meta( $this->payment->id, 'stripe_payment_intent_id', true );

		if ( empty( $payment_intent_id ) ) {
			$this->handle_processing_error( new WP_Error( 'missing_stripe_payment_intent', __( 'Missing Stripe payment intent, please try again or contact support if the issue persists.', 'rcp' ) ) );
		}

		try {

			if ( ! empty( $payment_intent_id ) && 'pi_' === substr( $payment_intent_id, 0, 3 ) ) {
				$payment_intent = \Stripe\PaymentIntent::retrieve( $payment_intent_id );
			} elseif ( ! empty( $payment_intent_id ) && 'seti_' === substr( $payment_intent_id, 0, 5 ) ) {
				$payment_intent = \Stripe\SetupIntent::retrieve( $payment_intent_id );
			}
		} catch ( Exception $e ) {
			$this->handle_processing_error( $e );
		}

		/**
		 * Set up Stripe customer record and attach it to the membership.
		 */
		$customer = $this->get_or_create_customer( $this->membership->get_customer_id(), $this->user_id, $payment_intent->customer );

		/**
		 * Update customer record to set description and metadata.
		 */
		try {
			$description = sprintf(
				// translators: %1$d The user ID. %2$d The email. %3$d The subscription name.
				__( 'User ID: %1$d - User Email: %2$s - Membership: %3$s', 'rcp' ),
				$this->user_id,
				$this->email,
				$this->subscription_name
			);

			if ( strlen( $description ) > 350 ) {
				$description = substr( $description, 0, 350 );
			}

			\Stripe\Customer::update(
				$customer->id,
				array(
					'description' => sanitize_text_field( $description ),
					'metadata'    => array(
						'user_id'      => absint( $this->user_id ),
						'email'        => sanitize_text_field( $this->email ),
						'subscription' => sanitize_text_field( $this->subscription_name ),
						'customer_id'  => absint( $this->customer->get_id() ),
					),
				)
			);
		} catch ( Exception $e ) {
			$error = sprintf( 'Stripe Gateway: Failed to update Stripe customer metadata while activating membership #%d. Message: %s', $this->membership->get_id(), $e->getMessage() );
			rcp_log( $error, true );
			$this->membership->add_note( $error );
		}

		/**
		 * Initial payment / authorization was successful. Let's get some post-payment stuff done.
		 */

		$member = new RCP_Member( $this->user_id ); // for backwards compatibility only

		/**
		 * Complete the payment record if we have a confirmed payment. This activates the membership.
		 * We also attempt to get the transaction ID from the payment intent charge.
		 *
		 * If the charge isn't actually complete now, the webhook will pick it up later.
		 */
		$payment_data = array(
			'payment_type'   => 'Credit Card',
			'transaction_id' => '',
		);

		if ( 'payment_intent' == $payment_intent->object && ! empty( $payment_intent->charges->data[0]['id'] ) && 'succeeded' === $payment_intent->charges->data[0]['status'] ) {
			// Set the transaction ID from the charge.
			$payment_data['transaction_id'] = sanitize_text_field( $payment_intent->charges->data[0]['id'] );
			$payment_data['status']         = 'complete';
		} elseif ( 'setup_intent' == $payment_intent->object && empty( $this->initial_amount ) ) {
			// We'll get the transaction ID from the subscription later.
			$payment_data['status'] = 'complete';
		} else {
			rcp_log( sprintf( 'Stripe Gateway: payment not immediately verified for intent ID %s - waiting on webhook.', $payment_intent->id ) );
		}

		$rcp_payments_db->update( $this->payment->id, $payment_data );

		/**
		 * Get the current gateway subscription ID, wipe that value from the membership record, then cancel the
		 * subscription. in Stripe.
		 *
		 * We do this to account for cases where someone might be able to renew but they still have an "active"
		 * subscription in Stripe. One example might be if their previous subscription becomes past-due (but not
		 * cancelled) due to the renewal payment failing. Instead of the customer updating their payment method,
		 * they've manually renewed.
		 *
		 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/2530
		 */
		$current_sub_id = $this->membership->get_gateway_subscription_id();
		if ( ! empty( $current_sub_id ) && false !== strpos( $this->membership->get_gateway(), 'stripe' ) && 'sub_' === substr( $current_sub_id, 0, 4 ) ) {
			try {
				$subscription = \Stripe\Subscription::retrieve( $current_sub_id );

				$this->membership->update(
					array(
						'gateway_subscription_id' => '',
					)
				);

				$subscription->cancel();
			} catch ( Exception $e ) {
				rcp_log( sprintf( 'Stripe Gateway: Subscription cleanup failed for user #%d. Subscription ID: %s. Message: %s', $this->user_id, $current_sub_id, $e->getMessage() ), true );
			}
		}

		/*
		 * We need to refresh our local membership variable because updating the payment record above will have modified it.
		 * This feels hacky AF but I'm doing it for now.
		 */
		$this->membership = rcp_get_membership( $this->membership->get_id() );

		/**
		 * Triggers when a payment is processed by the gateway.
		 *
		 * @param RCP_Member                 $member     Deprecated member object.
		 * @param int                        $payment_id ID of the payment that was just processed.
		 * @param RCP_Payment_Gateway_Stripe $this       Gateway object.
		 */
		do_action( 'rcp_gateway_payment_processed', $member, $this->payment->id, $this );

		/**
		 * Clean up any past due or unpaid subscription.
		 *
		 * We only do this if multiple memberships is not enabled, otherwise we can't be
		 * completely sure which ones we need to keep.
		 */
		if ( ! rcp_multiple_memberships_enabled() ) {
			try {
				// Set up array of subscriptions we cancel below so we don't try to cancel the same one twice.
				$cancelled_subscriptions = array();

				$subscriptions = \Stripe\Subscription::all(
					array(
						'customer' => $customer->id,
						'expand'   => array( 'data.plan.product' ),
					)
				);

				foreach ( $subscriptions->data as $subscription ) {

					// Cancel subscriptions with the RCP metadata present and matching member ID.
					if ( ! empty( $subscription->metadata ) && ! empty( $subscription->metadata['rcp_subscription_level_id'] ) && $this->user_id == $subscription->metadata['rcp_member_id'] ) {
						$subscription->cancel();
						$cancelled_subscriptions[] = $subscription->id;
						rcp_log( sprintf( 'Stripe Gateway: Cancelled Stripe subscription %s.', $subscription->id ) );
						continue;
					}

					/*
					 * This handles subscriptions from before metadata was added. We check the plan name against the
					 * RCP membership level database. If the Stripe plan name matches a sub level name then we cancel it.
					 */
					if ( ! empty( $subscription->plan->product->name ) ) {

						$level = rcp_get_membership_level_by( 'name', $subscription->plan->product->name );

						// Cancel if this plan name matches an RCP membership level.
						if ( $level instanceof Membership_Level ) {
							$subscription->cancel();
							$cancelled_subscriptions[] = $subscription->id;
							rcp_log( sprintf( 'Stripe Gateway: Cancelled Stripe subscription %s.', $subscription->id ) );
						}
					}
				}
			} catch ( Exception $e ) {
				rcp_log( sprintf( 'Stripe Gateway: Subscription cleanup failed for user #%d. Message: %s', $this->user_id, $e->getMessage() ), true );
			}
		}

		/**
		 * Attach the payment method to the customer so we can use it again.
		 */
		try {
			$payment_method = \Stripe\PaymentMethod::retrieve( $payment_intent->payment_method );

			if ( empty( $payment_method->customer ) ) {
				$payment_method->attach(
					array(
						'customer' => $customer->id,
					)
				);
			}

			\Stripe\Customer::update(
				$customer->id,
				array(
					'invoice_settings' => array(
						'default_payment_method' => $payment_intent->payment_method,
					),
				)
			);

			/*
			 * Dedupe payment methods.
			 * If someone re-registers with the same card details they've used in the past, Stripe
			 * will actually create a whole new payment method object with the same fingerprint.
			 * This could result in the same card being added to the customer's payment methods in
			 * Stripe, which is kind of annoying. So we dedupe them to make sure one customer only
			 * has each payment method listed once. Hopefully Stripe will handle this automatically
			 * in the future.
			 *
			 * @link https://github.com/stripe/stripe-payments-demo/issues/45
			 */
			$customer_payment_methods = \Stripe\PaymentMethod::all(
				array(
					'customer' => $customer->id,
					'type'     => 'card',
				)
			);
			if ( ! empty( $customer_payment_methods->data ) ) {
				foreach ( $customer_payment_methods->data as $existing_method ) {
					// Detach if the fingerprint matches but payment method ID is different.
					if ( $existing_method->card->fingerprint === $payment_method->card->fingerprint && $existing_method->id != $payment_method->id ) {
						$existing_method->detach();
					}
				}
			}
		} catch ( Exception $e ) {
			$error = sprintf( 'Stripe Gateway: Failed to attach payment method to customer while activating membership #%d. Message: %s', $this->membership->get_id(), $e->getMessage() );
			rcp_log( $error, true );
			$this->membership->add_note( $error );
		}

		if ( $this->auto_renew ) {

			/**
			 * Set up a recurring subscription in Stripe with a delayed start date.
			 *
			 * All start dates are delayed one cycle because we use a one-time payment for the first charge.
			 */

			// Retrieve or create the plan.
			$plan_id = $this->maybe_create_plan(
				array(
					'name'           => $this->subscription_name,
					'price'          => $this->amount,
					'interval'       => $this->length_unit,
					'interval_count' => $this->length,
				)
			);

			try {

				if ( is_wp_error( $plan_id ) ) {
					throw new Exception( $plan_id->get_error_message() );
				}

				// Set up base subscription args.
				if ( ! empty( $this->subscription_start_date ) ) {
					// Use subscription start date if provided. This will be a free trial.
					rcp_log( sprintf( 'Stripe Gateway: Using subscription start date for subscription: %s', $this->subscription_start_date ) );
					$start_date = strtotime( $this->subscription_start_date, current_time( 'timestamp' ) );
				} else {
					// Otherwise, use the calculated expiration date of the membership, modified to current time instead of 23:59.

					$base_date = $this->membership->get_expiration_date( false );
					rcp_log( sprintf( 'Stripe Gateway: Using newly calculated expiration for subscription start date: %s', $base_date ) );

					$timezone     = get_option( 'timezone_string' );
					$timezone     = ! empty( $timezone ) ? $timezone : 'UTC';
					$datetime     = new DateTime( $base_date, new DateTimeZone( $timezone ) );
					$current_time = getdate();
					$datetime->setTime( $current_time['hours'], $current_time['minutes'], $current_time['seconds'] );
					$start_date = $datetime->getTimestamp() - HOUR_IN_SECONDS; // Reduce by 60 seconds to account for inaccurate server times.
				}

				$sub_args = array(
					'customer'               => $customer->id,
					'default_payment_method' => $payment_method->id,
					'plan'                   => $plan_id,
					'proration_behavior'     => 'none',
					'metadata'               => array(
						'rcp_subscription_level_id' => $this->subscription_id,
						'rcp_member_id'             => $this->user_id,
						'rcp_customer_id'           => $this->customer->get_id(),
						'rcp_membership_id'         => $this->membership->get_id(),
						'rcp_initial_payment_id'    => $this->payment->id,
					),
				);

				/*
				 * Now determine if we use `trial_end` or `billing_cycle_anchor` to schedule the start of the
				 * subscription.
				 *
				 * If this is an actual trial, then we use `trial_end`.
				 *
				 * Otherwise, billing cycle anchor is preferable because that works with Stripe MRR.
				 * However, the anchor date cannot be further in the future than a normal billing cycle duration.
				 * If that's the case, then we have to use trial end instead.
				 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/2503
				 */

				$stripe_max_anchor = $this->get_stripe_max_billing_cycle_anchor( $this->length, $this->length_unit, 'now' );

				if ( $this->is_trial() || $start_date > $stripe_max_anchor->getTimestamp() ) {
					$sub_args['trial_end'] = $start_date;
					rcp_log( sprintf( 'Stripe Gateway: Creating subscription with %s start date via trial_end.', $start_date ) );
				} else {
					$sub_args['billing_cycle_anchor'] = $start_date;
					$sub_args['proration_behavior']   = 'none';
					rcp_log( sprintf( 'Stripe Gateway: Creating subscription with %s start date via billing_cycle_anchor.', $start_date ) );
				}

				$set_anchor = isset( $sub_args['billing_cycle_anchor'] );

				$sub_options = array(
					'idempotency_key' => rcp_stripe_generate_idempotency_key( $sub_args ),
				);

				$stripe_connect_user_id = get_option( 'rcp_stripe_connect_account_id', false );

				if ( ! empty( $stripe_connect_user_id ) ) {
					$sub_options['stripe_account'] = $stripe_connect_user_id;
				}

				/**
				 * Filters the Stripe subscription arguments.
				 *
				 * @param array                      $sub_args
				 * @param RCP_Payment_Gateway_Stripe $this
				 */
				$sub_args = apply_filters( 'rcp_stripe_create_subscription_args', $sub_args, $this );

				/*
				 * If we have a `billing_cycle_anchor` AND a `trial_end`, then we need to unset whichever one
				 * we set, and leave the customer's custom one in tact.
				 *
				 * This is done to account for people who filter the arguments to customize the next bill
				 * date. If `trial_end` is used in conjunction with `billing_cycle_anchor` then it will create
				 * unexpected results and the next bill date will not be what they want.
				 *
				 * This may not be completely perfect but it's the best way to try to account for any errors.
				 */
				if ( ! empty( $sub_args['trial_end'] ) && ! empty( $sub_args['billing_cycle_anchor'] ) ) {
					// If we set an anchor, remove that, because this means the customer has set their own `trial_end`.
					if ( $set_anchor ) {
						unset( $sub_args['billing_cycle_anchor'] );
					} else {
						// We set a trial, which means the customer has set their own `billing_cycle_anchor`.
						unset( $sub_args['trial_end'] );
					}
				}

				// Stripe API no longer returns the subscriptions as a part of the customer, so this needs to be overhauled
				// to get the subscription or create the subscriptions.
				$subscription = \Stripe\Subscription::create( $sub_args, $sub_options );

				// Link the Stripe subscription to the RCP membership.
				$this->membership->set_gateway_subscription_id( $subscription->id );

				// If this all started with a Setup Intent then let's use the subscription ID as the transaction ID.
				if ( 'setup_intent' === $payment_intent->object ) {
					$rcp_payments_db->update(
						$this->payment->id,
						array(
							'transaction_id' => sanitize_text_field( $subscription->id ),
						)
					);
				}
			} catch ( Exception $e ) {
				$error = sprintf( 'Stripe Gateway: Failed to create subscription for membership #%d. Message: %s', $this->membership->get_id(), $e->getMessage() );
				rcp_log( $error, true );
				$this->membership->add_note( $error );
				$this->membership->set_recurring( false );
			}
		}

		/**
		 * Triggers after a successful Stripe signup.
		 *
		 * @param int                        $user_id ID of the user account.
		 * @param RCP_Payment_Gateway_Stripe $this    Gateway object.
		 */
		do_action( 'rcp_stripe_signup', $this->user_id, $this );

		// Redirect to the success page.
		wp_redirect( $this->return_url );
		exit;

	}

	/**
	 * Attempt to guess the maximum `billing_cycle_anchor` Stripe will allow us to set, given a signup date
	 * and billing cycle interval.
	 *
	 * @param int    $interval      Billing cycle interval.
	 * @param string $interval_unit Billing cycle interval unit.
	 * @param string $signup_date   Signup date that can be parsed by `strtotime()`. Will almost always be
	 *                              `now`, but can be overridden for help in unit tests.
	 *
	 * @since 3.3.7
	 * @return DateTime
	 */
	public function get_stripe_max_billing_cycle_anchor( $interval, $interval_unit, $signup_date = 'now' ) {

		try {
			$signup_date = new DateTimeImmutable( $signup_date );
		} catch ( Exception $e ) {
			$signup_date = new DateTimeImmutable();
		}

		$stripe_max_anchor = $signup_date->modify( sprintf( '+%d %s', $interval, $interval_unit ) );

		$proposed_next_bill_date = new DateTime();
		$proposed_next_bill_date->setTimestamp( $signup_date->getTimestamp() );

		// Set to first day of the month so we're not dealing with mismatching days of the month.
		$proposed_next_bill_date->setDate( $proposed_next_bill_date->format( 'Y' ), $proposed_next_bill_date->format( 'm' ), 1 );
		// Now we can safely add 1 interval and still be in the expected month.
		$proposed_next_bill_date->modify( sprintf( '+ %d %s', $interval, $interval_unit ) );

		/*
		 * If the day of the month in the signup date exceeds the total number of days in the proposed month,
		 * set the anchor to the last day of the proposed month - wahtever that is.
		 */
		if ( date( 'j', $signup_date->getTimestamp() ) > date( 't', $proposed_next_bill_date->getTimestamp() ) ) {
			try {
				$stripe_max_anchor = new DateTime( date( 'Y-m-t H:i:s', $proposed_next_bill_date->getTimestamp() ) );
			} catch ( Exception $e ) {

			}
		}

		return $stripe_max_anchor;

	}

	/**
	 * Get or create Stripe Customer
	 *
	 * Get the Stripe customer record based on the user registering. This scans their
	 * `gateway_customer_id` fields for a Stripe customer ID and retrieves that if found.
	 *
	 * If no Stripe customer ID is located, a new customer record is created.
	 *
	 * @param int    $rcp_customer_id RCP customer ID - used for checking the gateway customer ID field.
	 * @param int    $user_id         WordPress user ID number.
	 * @param string $customer_id     Stripe customer ID - if you already have one.
	 *
	 * @since 3.2
	 * @return \Stripe\Customer|WP_Error
	 */
	public function get_or_create_customer( $rcp_customer_id = 0, $user_id = 0, $customer_id = '' ) {

		$customer_exists = false;
		$user_id         = ! empty( $user_id ) ? $user_id : $this->user_id;
		$user            = get_userdata( $user_id );
		$rcp_customer_id = ! empty( $rcp_customer_id ) ? $rcp_customer_id : $this->membership->get_customer()->get_id();
		$customer_id     = ! empty( $customer_id ) ? $customer_id : rcp_get_customer_gateway_id( $rcp_customer_id, array( 'stripe', 'stripe_checkout' ) );

		if ( $customer_id ) {

			$customer_exists = true;

			try {

				// Update the customer to ensure their card data is up to date
				$customer = \Stripe\Customer::retrieve( $customer_id );

				if ( isset( $customer->deleted ) && $customer->deleted ) {

					// This customer was deleted
					$customer_exists = false;

				}

				// No customer found
			} catch ( Exception $e ) {

				$customer_exists = false;

			}
		}

		if ( empty( $customer_exists ) ) {

			try {

				$customer_args = array(
					'email' => $user->user_email,
					'name'  => sanitize_text_field( trim( $user->first_name . ' ' . $user->last_name ) ),
				);

				/**
				 * Filters the customer creation arguments.
				 *
				 * @param array                      $customer_args Arguments used to create the Stripe customer.
				 * @param RCP_Payment_Gateway_Stripe $this          Gatway object.
				 */
				$customer_args = apply_filters( 'rcp_stripe_customer_create_args', $customer_args, $this );

				$customer = \Stripe\Customer::create( $customer_args );

			} catch ( Exception $e ) {

				return new WP_Error( $e->getCode(), $e->getMessage() );

			}
		}

		return $customer;

	}

	/**
	 * Get Stripe error from exception
	 *
	 * This converts the exception into a WP_Error object with a localized error message.
	 *
	 * @param \Stripe\Exception\ApiErrorException $e
	 *
	 * @since 3.2
	 * @return WP_Error
	 */
	protected function get_stripe_error( $e ) {

		$wp_error = new WP_Error();

		if ( method_exists( $e, 'getJsonBody' ) ) {
			$body  = $e->getJsonBody();
			$error = $body['error'];
			$wp_error->add( $error['code'], $this->get_localized_error_message( $error['code'], $e->getMessage() ) );
		} else {
			$wp_error->add( 'unknown_error', __( 'An unknown error has occurred.', 'rcp' ) );
		}

		return $wp_error;

	}

	/**
	 * Localize common Stripe error messages so they're available for translation.
	 *
	 * @link https://stripe.com/docs/error-codes
	 *
	 * @param string $error_code    Stripe error code.
	 * @param string $error_message Original Stripe error message. This will be returned if we don't have a localized version of
	 *                              the error code.
	 *
	 * @since 3.2
	 * @return string
	 */
	protected function get_localized_error_message( $error_code, $error_message = '' ) {

		$errors = rcp_stripe_get_localized_error_messages();

		if ( ! empty( $errors[ $error_code ] ) ) {
			return $errors[ $error_code ];
		} else {
			// translators: %1$s The error code. %2$s The error message.
			return sprintf( __( 'An error has occurred (code: %1$s; message: %2$s).', 'rcp' ), $error_code, $error_message );
		}

	}

	/**
	 * Handle Stripe processing error
	 *
	 * @param \Stripe\Exception\ApiErrorException|Exception|WP_Error $e Stripe error exception.
	 *
	 * @access protected
	 * @since  2.5
	 *
	 * @return void
	 */
	protected function handle_processing_error( $e ) {

		if ( method_exists( $e, 'getJsonBody' ) ) {

			$body                = $e->getJsonBody();
			$err                 = $body['error'];
			$error_code          = ! empty( $err['code'] ) ? $err['code'] : false;
			$this->error_message = $this->get_localized_error_message( $error_code, $e->getMessage() );

		} else {

			$error_code          = is_wp_error( $e ) ? $e->get_error_code() : $e->getCode();
			$this->error_message = is_wp_error( $e ) ? $e->get_error_message() : $e->getMessage();

			// $err is here for backwards compat for the rcp_stripe_signup_payment_failed hook below.
			$err = array(
				'message' => $this->error_message,
				'type'    => 'other',
				'param'   => false,
				'code'    => 'other',
			);

		}

		do_action( 'rcp_registration_failed', $this );
		do_action( 'rcp_stripe_signup_payment_failed', $err, $this );

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';

		if ( ! empty( $error_code ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $error_code ) . '</p>';
		}

		if ( method_exists( $e, 'getHttpStatus' ) ) {
			$error .= '<p>Status: ' . $e->getHttpStatus() . '</p>';
		}

		$error .= '<p>Message: ' . $this->error_message . '</p>';

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );
	}

	/**
	 * Process webhooks. This is the list of current webhooks handle by RC:
	 *
	 * - customer.subscription.created
	 * - customer.subscription.deleted
	 * - charge.succeeded
	 * - charge.refunded
	 * - invoice.payment_succeeded
	 * - invoice.payment_failed
	 *
	 * @access public
	 * @return void
	 */
	public function process_webhooks() {

		if ( ! isset( $_GET['listener'] )
			 || strtolower( sanitize_text_field( wp_unslash( $_GET['listener'] ) ) ) != 'stripe' ) {
			return;
		}

		rcp_log( 'Starting to process Stripe webhook.' );

		// Ensure listener URL is not cached by W3TC
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		// retrieve the request's body and parse it as JSON
		$body       = @file_get_contents( 'php://input' );
		$event_data = json_decode( $body );
		$expiration = '';

		// for extra security, retrieve from the Stripe API
		if ( ! isset( $event_data->id ) ) {
			rcp_log( 'Exiting Stripe webhook - no event ID found.' );

			wp_send_json_error(
				[
					'message' => __( 'Exiting Stripe webhook - no event ID found.', 'rcp' ),
				],
				401
			);
		}

		if ( ! isset( $event_data->object ) || $event_data->object !== 'event' ) {
			$event_object = $event_data->object ?? 'empty';
			rcp_log( 'Exiting Stripe webhook - Stripe object is an invalid event type. "' . $event_object->object . '"' . ' was provided.' );

			wp_send_json_error(
				[
					'message' => __( 'Exiting Stripe webhook - Stripe object is an invalid event type.', 'rcp' ),
				],
				401
			);
		}

		$rcp_payments = new RCP_Payments();

		$event_id = $event_data->id;

		try {

			\Stripe\Stripe::setMaxNetworkRetries( 3 );
			$event         = \Stripe\Event::retrieve( $event_id );
			$payment_event = $event->data->object;
			$membership    = false;

			rcp_log( sprintf( 'Event ID: %s; Event Type: %s', $event->id, $event->type ) );

			// Check for valid webhooks in the current RCP settings.
			if ( ! validate_stripe_webhook( $event->type ) ) {
				rcp_log( sprintf( 'Exiting Stripe webhook - Unregistered Stripe webhook. The webhook "%s" is not currently handled.', $event->type ), true );

				wp_send_json_success(
					[
						'message' => sprintf(
							// translators: %s is the event type.
							__( 'Exiting Stripe webhook - Unregistered Stripe webhook. The webhook "%s" is not currently handled.', 'rcp' ),
							esc_html( $event->type )
						),
					],
					200
				);
			}

			if ( empty( $payment_event->customer ) ) {
				rcp_log( 'Exiting Stripe webhook - no customer attached to event.' );

				wp_send_json_success(
					[
						'message' => __( 'Exiting Stripe webhook - no customer attached to event.', 'rcp' ),
					],
					200
				);
			}

			$invoice = $customer = $subscription = false;

			// Try to get an invoice object from the payment event.
			if ( ! empty( $payment_event->object ) && 'invoice' === $payment_event->object ) {
				$invoice = $payment_event;
			} elseif ( ! empty( $payment_event->invoice ) ) {
				$invoice = \Stripe\Invoice::retrieve( $payment_event->invoice );
			}

			// Now try to get a subscription from the invoice.
			if ( $invoice instanceof \Stripe\Invoice && ! empty( $invoice->subscription ) ) {
				$subscription = \Stripe\Subscription::retrieve( $invoice->subscription );
			}

			// We can also get the subscription by the object ID in some circumstances.
			if ( empty( $subscription ) && false !== strpos( $payment_event->id, 'sub_' ) ) {
				$subscription = \Stripe\Subscription::retrieve( $payment_event->id );
			}

			// Retrieve the membership by subscription ID.
			if ( ! empty( $subscription ) ) {
				$membership = rcp_get_membership_by( 'gateway_subscription_id', $subscription->id );

				// Retrieve the membership by rcp_membership_id subscription meta.
				if ( empty( $membership ) && ! empty( $subscription->metadata->rcp_membership_id ) ) {
					$membership = rcp_get_membership( absint( $subscription->metadata->rcp_membership_id ) );
				}
			}

			// Retrieve the membership by payment meta (one-time charges only).
			if ( ! empty( $payment_event->metadata->rcp_subscription_key ) ) {
				rcp_log( sprintf( 'Stripe Webhook: Getting membership by subscription key: %s', $payment_event->metadata->rcp_subscription_key ) );
				$membership = rcp_get_membership_by( 'subscription_key', $payment_event->metadata->rcp_subscription_key );
			}

			if ( empty( $membership ) ) {

				// Grab the customer ID from the old meta keys
				global $wpdb;
				$user_id  = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_rcp_stripe_user_id' AND meta_value = %s LIMIT 1", $payment_event->customer ) );
				$customer = rcp_get_customer_by_user_id( $user_id );

				if ( ! empty( $customer ) ) {
					/*
					 * We can only use this function if:
					 * 		- Multiple memberships is disabled; or
					 * 		- The customer only has one membership anyway.
					 */
					if ( ! rcp_multiple_memberships_enabled() || 1 === count( $customer->get_memberships() ) ) {
						$membership = rcp_get_customer_single_membership( $customer->get_id() );
					}
				}
			}

			/**
			 * Filters the membership record associated with this webhook.
			 *
			 * This filter was introduced due to conflicts that may arise when the same Stripe customer may
			 * be used on different sites.
			 *
			 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/2606
			 *
			 * @param RCP_Membership|false       $membership
			 * @param RCP_Payment_Gateway_Stripe $this
			 * @param \Stripe\Event              $event
			 *
			 * @since 3.3.5
			 */
			$membership = apply_filters( 'rcp_stripe_webhook_membership', $membership, $this, $event );

			if ( ! $membership instanceof RCP_Membership ) {
				rcp_log( sprintf( 'Exiting Stripe webhook - membership not found. Customer ID: %s.', $payment_event->customer ), true );

				wp_send_json_error(
					[
						// translators: %s is the event type.
						'message' => sprintf( __( 'Exiting Stripe webhook - membership not found. Customer ID: %s.', 'rcp' ), esc_html( $payment_event->customer ) ),
					],
					200
				);
			}

			$this->membership = $membership;
			$customer         = $membership->get_customer();
			$member           = new RCP_Member( $customer->get_user_id() ); // for backwards compatibility.

			rcp_log( sprintf( 'Processing webhook for membership #%d.', $membership->get_id() ) );

			$subscription_level_id = $membership->get_object_id();

			if ( ! $subscription_level_id ) {
				rcp_log( 'Exiting Stripe webhook - no membership level ID for membership.', true );

				wp_send_json_success(
					[
						'message' => __( 'Exiting Stripe webhook - no membership level ID for membership.', 'rcp' ),
					],
					200
				);
			}

			if ( $event->type == 'customer.subscription.created' ) {
				do_action( 'rcp_webhook_recurring_payment_profile_created', $member, $this );
			}

			if ( $event->type == 'charge.succeeded' || $event->type == 'invoice.payment_succeeded' ) {

				rcp_log( sprintf( 'Processing Stripe %s webhook.', $event->type ) );

				// setup payment data
				$payment_data = array(
					// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'date'           => date( 'Y-m-d H:i:s', $event->created ),
					'payment_type'   => 'Credit Card',
					'user_id'        => $member->ID,
					'customer_id'    => $customer->get_id(),
					'membership_id'  => $membership->get_id(),
					'amount'         => '',
					'transaction_id' => '',
					'object_id'      => $subscription_level_id,
					'status'         => 'complete',
					'gateway'        => 'stripe',
				);

				if ( $event->type == 'charge.succeeded' ) {

					// Successful one-time payment.
					if ( empty( $payment_event->invoice ) ) {

						$payment_data['amount']         = $payment_event->amount / rcp_stripe_get_currency_multiplier();
						$payment_data['transaction_id'] = $payment_event->id;

						// Successful subscription payment.
					} else {

						$payment_data['amount']         = $invoice->amount_due / rcp_stripe_get_currency_multiplier();
						$payment_data['transaction_id'] = $payment_event->id;

						if ( ! empty( $payment_event->discount ) ) {
							$payment_data['discount_code'] = $payment_event->discount->coupon_id;
						}
					}
				}

				if ( ! empty( $payment_data['transaction_id'] ) && ! $rcp_payments->payment_exists( $payment_data['transaction_id'] ) ) {

					if ( ! empty( $subscription ) ) {

						$membership->set_recurring();
						$membership->set_gateway_subscription_id( $subscription->id );

						/*
						 * Set the new expiration date.
						 * We use the `current_period_end` as our base and force the time to be 23:59:59 that day.
						 * However, this must be at least two hours after `current_period_end` to ensure there's
						 * plenty of time between the next invoice being generated and actually being paid/finalized.
						 * Stripe usually does this within 1 hour, but we're using 2 to be on the safe side and
						 * account for delays.
						 *
						 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/2671
						 */
						$renewal_date = new DateTime();
						$renewal_date->setTimestamp( $subscription->current_period_end );
						$renewal_date->setTime( 23, 59, 59 );

						// Estimated charge date is 2 hours after `current_period_end`.
						$stripe_estimated_charge_timestamp = $subscription->current_period_end + ( 2 * HOUR_IN_SECONDS );

						if ( $stripe_estimated_charge_timestamp > $renewal_date->getTimestamp() ) {
							$renewal_date->setTimestamp( $stripe_estimated_charge_timestamp );
						}

						$expiration = $renewal_date->format( 'Y-m-d H:i:s' );

						/*
						 * Check to see if the current date is occurring before today and
						 * updating the next_bill_date in Stripe
						 */
						$current_expiration_date = $membership->get_expiration_date( false );
						$timezone                = get_option( 'timezone_string' );
						$timezone                = ! empty( $timezone ) ? $timezone : 'UTC';
						$current_expiration_date = new DateTime( $current_expiration_date, new DateTimeZone( $timezone ) );

						if ( $current_expiration_date->getTimestamp() < time() ) {
							$this->update_subscription(
								$subscription->id,
								array(
									'next_bill_date' => $expiration,
								)
							);
						}
					}

					$pending_payment_id = rcp_get_membership_meta( $this->membership->get_id(), 'pending_payment_id', true );
					if ( ! empty( $pending_payment_id ) ) {

						// Completing a pending payment. Account activation is handled in rcp_complete_registration()
						$rcp_payments->update( $pending_payment_id, $payment_data );
						$payment_id = $pending_payment_id;

					} else {

						// Inserting a new payment and renewing.
						$membership->renew( $membership->is_recurring(), 'active', $expiration );

						// These must be retrieved after the status is set to active in order for upgrades to work properly
						$payment_data['subscription']     = $membership->get_membership_level_name();
						$payment_data['subscription_key'] = $membership->get_subscription_key();
						$payment_data['subtotal']         = $payment_data['amount'];
						$payment_data['transaction_type'] = 'renewal';
						$payment_id                       = $rcp_payments->insert( $payment_data );

						if ( $membership->is_recurring() ) {
							do_action( 'rcp_webhook_recurring_payment_processed', $member, $payment_id, $this );
						}
					}

					do_action( 'rcp_gateway_payment_processed', $member, $payment_id, $this );
					do_action( 'rcp_stripe_charge_succeeded', $customer->get_user_id(), $payment_data, $event );

					wp_send_json_success(
						[
							'message' => __( 'rcp_stripe_charge_succeeded action fired successfully', 'rcp' ),
						],
						200
					);

				} elseif ( ! empty( $payment_data['transaction_id'] ) && $rcp_payments->payment_exists( $payment_data['transaction_id'] ) ) {

					do_action( 'rcp_ipn_duplicate_payment', $payment_data['transaction_id'], $member, $this );
					rcp_log( 'Duplicate payment found. Transaction ID: ' . $payment_data['transaction_id'] );

					wp_send_json_success(
						[
							// translators: %s The transaction id.
							'message' => sprintf( __( 'Duplicate payment found. Transaction ID %s', 'rcp' ), $payment_data['transaction_id'] ),
						],
						200
					);
				}
			}

			// failed payment
			if ( $event->type == 'invoice.payment_failed' ) {

				rcp_log( 'Processing Stripe invoice.payment_failed webhook.' );

				$this->webhook_event_id = $event->id;

				// Make sure this invoice is tied to a subscription and is the user's current subscription.
				if ( ! empty( $event->data->object->subscription ) && $event->data->object->subscription == $membership->get_gateway_subscription_id() ) {
					do_action( 'rcp_recurring_payment_failed', $member, $this );
				} else {
					rcp_log( sprintf( 'Stripe subscription ID %s doesn\'t match membership\'s merchant subscription ID %s. Skipping rcp_recurring_payment_failed hook.', $event->data->object->subscription, $member->get_merchant_subscription_id() ), true );
				}

				do_action( 'rcp_stripe_charge_failed', $payment_event, $event, $member );

				wp_send_json_success(
					[
						'message' => __( 'rcp_stripe_charge_failed action fired successfully', 'rcp' ),
					],
					200
				);
			}

			// Cancelled / failed subscription
			if ( $event->type == 'customer.subscription.deleted' ) {

				rcp_log( 'Processing Stripe customer.subscription.deleted webhook.' );

				if ( $payment_event->id == $membership->get_gateway_subscription_id() ) {

					// Bail if auto-renew was toggled within the last 5 minutes.
					$toggle = rcp_get_membership_meta( $membership->get_id(), 'auto_renew_toggled_off', true );
					if ( ! empty( $toggle ) && strtotime( $toggle ) >= strtotime( '-5 minutes' ) ) {
						rcp_log( sprintf( 'Membership #%d just disabled auto renew - not cancelling.', $membership->get_id() ) );

						wp_send_json_success(
							[
								'message' => sprintf(
									// translators: %d is the membership ID.
									__( 'Membership #%d just disabled auto renew - not cancelling.', 'rcp' ),
									esc_html( $membership->get_id() )
								),
							],
							200
						);
					}

					// If this is a completed payment plan, we can skip any cancellation actions. This is handled in renewals.
					if ( $membership->has_payment_plan() && $membership->at_maximum_renewals() ) {
						rcp_log( sprintf( 'Membership #%d has completed its payment plan - not cancelling.', $membership->get_id() ) );

						wp_send_json_success(
							[
								'message' => sprintf(
									// translators: %d is the membership ID.
									__( 'Membership #%d has completed its payment plan - not cancelling.', 'rcp' ),
									esc_html( $membership->get_id() )
								),
							],
							200
						);
					}

					if ( $membership->is_active() ) {
						$membership->cancel();
						$membership->add_note( __( 'Membership cancelled via Stripe webhook.', 'rcp' ) );
					} else {
						rcp_log( sprintf( 'Membership #%d is not active - not cancelling account.', $membership->get_id() ) );
					}

					do_action( 'rcp_webhook_cancel', $member, $this );

					wp_send_json_success(
						[
							'message' => __( 'Membership cancelled successfully', 'rcp' ),
						],
						200
					);

				} else {
					rcp_log( sprintf( 'Payment event ID (%s) doesn\'t match membership\'s merchant subscription ID (%s).', $payment_event->id, $membership->get_gateway_subscription_id() ), true );
				}
			}

			if ( $event->type == 'charge.refunded' ) {

				rcp_log( sprintf( 'Processing charge.refunded webhook - event Id %s.', $event_id ) );

				$payment = $rcp_payments->get_payment_by( 'transaction_id', sanitize_text_field( $payment_event->id ) );

				if ( empty( $payment ) ) {
					rcp_log( sprintf( 'No payment found with transaction Id #%s.', $payment_event->id ) );
				} else {
					rcp_log( sprintf( 'Updating status of payment #%d ( transaction ID %s) to "refunded".', $payment->id, $payment_event->id ) );

					$rcp_payments->update(
						$payment->id,
						array(
							'status' => 'refunded',
						)
					);

					if ( $membership->is_active() ) {
						$membership->expire();
						$membership->add_note( __( 'Membership expired via Stripe webhook', 'rcp' ) );
					}
				}
			}

			do_action( 'rcp_stripe_' . $event->type, $payment_event, $event );

		} catch ( Exception $e ) {
			// something failed
			rcp_log( sprintf( 'Exiting Stripe webhook due to PHP exception: %s.', $e->getMessage() ), true );

			wp_send_json_error(
				[
					'message' => sprintf(
						// translators: %s is the error message.
						__( 'Exiting Stripe webhook due to PHP exception: %s.', 'rcp' ),
						esc_html( $e->getMessage() )
					),
				],
				500
			);
		}

		rcp_log( 'Restrict Content reached the end of webhooks processing.', true );

		wp_send_json_success(
			[
				'message' => __( 'Restrict Content completed processing webhooks.', 'rcp' ),
			],
			200
		);

	}

	/**
	 * Add credit card fields
	 *
	 * @since 2.1
	 * @return string
	 */
	public function fields() {
		ob_start();

		$saved_payment_methods = rcp_stripe_get_user_saved_payment_methods( get_current_user_id() );

		if ( ! empty( $saved_payment_methods ) ) {
			$i = 0;
			?>
			<ul class="rcp-gateway-saved-payment-methods">
				<?php
				foreach ( $saved_payment_methods as $payment_method ) {
					/**
					 * @var \Stripe\PaymentMethod $payment_method
					 */
					$classes = array(
						'rcp-gateway-saved-payment-method',
						'rcp-gateway-saved-payment-method-type-' . $payment_method->type,
					);

					if ( 'card' === $payment_method->type ) {
						$classes[] = 'rcp-gateway-saved-payment-method-card-brand-' . $payment_method->card->brand;
					}

					$classes = array_map( 'sanitize_html_class', $classes );
					?>
					<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
						<label for="<?php echo esc_attr( $payment_method->id ); ?>">
							<input type="radio" id="<?php echo esc_attr( $payment_method->id ); ?>" name="rcp_gateway_existing_payment_method" value="<?php echo esc_attr( $payment_method->id ); ?>" <?php checked( 0 === $i ); ?> />
							<?php if ( 'card' === $payment_method->type ) : ?>
								<span class="rcp-gateway-saved-card-brand"><?php echo esc_html( $payment_method->card->brand ); ?></span>
								<span class="rcp-gateway-saved-card-ending-label"><?php _e( 'ending in', 'rcp' ); ?></span>
								<span class="rcp-gateway-saved-card-last-4"><?php echo esc_html( $payment_method->card->last4 ); ?></span>
								<span class="rcp-gateway-saved-payment-method-sep">&mdash; </span>
								<span class="rcp-gateway-saved-card-expires-label"><?php _e( 'expires', 'rcp' ); ?></span>
								<span class="rcp-gateway-saved-card-expiration"><?php printf( '%s / %s', esc_html( $payment_method->card->exp_month ), esc_html( $payment_method->card->exp_year ) ); ?></span>

								<?php
								// Check if the card is expired.
								$current  = strtotime( date( 'm/Y' ) );
								$exp_date = strtotime( $payment_method->card->exp_month . '/' . $payment_method->card->exp_year );

								if ( $exp_date < $current ) {
									?>
									<span class="rcp-gateway-saved-card-is-expired">
									<?php _e( 'Expired', 'rcp' ); ?>
								</span>
									<?php
								}
								?>
							<?php endif; ?>
						</label>
					</li>
					<?php
					$i ++;
				}
				?>
				<li class="rcp-gateway-add-payment-method-wrap">
					<label for="rcp-gateway-add-payment-method">
						<input type="radio" id="rcp-gateway-add-payment-method" name="rcp_gateway_existing_payment_method" value="new" />
						<?php _e( 'Add New Card', 'rcp' ); ?>
					</label>
				</li>
			</ul>
			<?php
		}
		?>

		<div class="rcp-gateway-new-card-fields"<?php echo ! empty( $saved_payment_methods ) ? ' style="display: none;"' : ''; ?>>
			<fieldset class="rcp_card_fieldset">
				<div id="rcp_card_name_wrap">
					<label for="rcp-card-name"><?php _e( 'Name on Card', 'rcp' ); ?></label>
					<input type="text" size="20" name="rcp_card_name" id="rcp-card-name" class="rcp_card_name card-name"/>
				</div>

				<div id="rcp_card_wrap">
					<label for="rcp-card-element"><?php esc_html_e( 'Credit Card', 'rcp' ); ?></label>
					<div id="rcp-card-element"></div>
				</div>
			</fieldset>
		</div>

		<div id="rcp-card-element-errors"></div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Load fields for the Update Billing Card form
	 *
	 * @access public
	 * @since 3.3
	 * @return void
	 */
	public function update_card_fields() {
		?>
		<div class="rcp-gateway-new-card-fields">
			<fieldset id="rcp-card-name-wrapper" class="rcp_card_fieldset">
				<p id="rcp_card_name_wrap">
					<label for="rcp-update-card-name"><?php _e( 'Name on Card', 'rcp' ); ?></label>
					<input type="text" size="20" id="rcp-update-card-name" name="rcp_card_name" class="rcp_card_name card-name" />
				</p>
			</fieldset>

			<fieldset id="rcp-card-wrapper" class="rcp_card_fieldset">
				<div id="rcp_card_wrap">
					<div id="rcp-card-element"></div>
				</div>
			</fieldset>
		</div>
		<div id="rcp-card-element-errors"></div>
		<?php
	}

	/**
	 * Validate additional fields during registration submission
	 *
	 * @since  2.1
	 * @return void
	 */
	public function validate_fields() {

		global $rcp_options;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['rcp_card_name'] ) && ( empty( $_POST['rcp_gateway_existing_payment_method'] ) || 'new' === $_POST['rcp_gateway_existing_payment_method'] ) ) {
			rcp_errors()->add( 'missing_card_name', __( 'The card holder name you have entered is invalid', 'rcp' ), 'register' );
		}

		if ( $this->test_mode && ( empty( $rcp_options['stripe_test_secret'] ) || empty( $rcp_options['stripe_test_publishable'] ) ) ) {
			rcp_errors()->add( 'missing_stripe_test_keys', __( 'Missing Stripe test keys. Please enter your test keys to use Stripe in Sandbox Mode.', 'rcp' ), 'register' );
		}

		if ( ! $this->test_mode && ( empty( $rcp_options['stripe_live_secret'] ) || empty( $rcp_options['stripe_live_publishable'] ) ) ) {
			rcp_errors()->add( 'missing_stripe_live_keys', __( 'Missing Stripe live keys. Please enter your live keys to use Stripe in Live Mode.', 'rcp' ), 'register' );
		}

	}

	/**
	 * Load Stripe JS
	 *
	 * @since 2.1
	 * @return void
	 */
	public function scripts() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Shared Stripe functionality.
		rcp_stripe_enqueue_scripts(
			array(
				'keys'   => array(
					'publishable' => $this->publishable_key,
				),
				'errors' => rcp_stripe_get_localized_error_messages(),
			)
		);

		// Custom registration form handling.
		// Does not set `rcp-register` as a dependency because those are printed in `wp_footer`
		wp_enqueue_script(
			'rcp-stripe-register',
			RCP_PLUGIN_URL . 'core/includes/gateways/stripe/js/register' . $suffix . '.js',
			array(
				'jquery',
				'rcp-stripe',
			),
			RCP_PLUGIN_VERSION
		);
	}

	/**
	 * Create plan in Stripe
	 *
	 * @deprecated 3.2 In favour of `maybe_create_plan()`
	 * @see RCP_Payment_Gateway_Stripe::maybe_create_plan()
	 *
	 * @param int $plan_id ID number of the membership level.
	 *
	 * @since 2.1
	 * @return bool|string - plan_id if successful, false if not
	 */
	private function create_plan( $plan_id = 0 ) {
		global $rcp_options;

		// get all membership level info for this plan
		$membership_level = rcp_get_membership_level( $plan_id );

		if ( ! $membership_level instanceof Membership_Level ) {
			return false;
		}

		$price          = round( $membership_level->get_price() * rcp_stripe_get_currency_multiplier(), 0 );
		$interval       = $membership_level->get_duration_unit();
		$interval_count = $membership_level->get_duration();
		$name           = $membership_level->get_name();
		$plan_id        = $this->generate_plan_id( $membership_level );
		$currency       = strtolower( rcp_get_currency() );

		try {

			$product_args = array(
				'name' => $name,
				'type' => 'service',
			);

			$product = \Stripe\Product::create(
				$product_args,
				array(
					'idempotency_key' => rcp_stripe_generate_idempotency_key( $product_args ),
				)
			);

			$plan_args = array(
				'amount'         => $price,
				'interval'       => $interval,
				'interval_count' => $interval_count,
				'currency'       => $currency,
				'id'             => $plan_id,
				'product'        => $product->id,
			);

			$plan = \Stripe\Plan::create(
				$plan_args,
				array(
					'idempotency_key' => rcp_stripe_generate_idempotency_key( $plan_args ),
				)
			);

			// plan successfully created
			return $plan->id;

		} catch ( Exception $e ) {

			$this->handle_processing_error( $e );
		}

	}

	/**
	 * Determine if a plan exists for a given membership level
	 *
	 * @deprecated 3.2 In favour of `maybe_create_plan()`
	 * @see RCP_Payment_Gateway_Stripe::maybe_create_plan()
	 *
	 * @param int $membership_level_id The ID number of the membership level to check
	 *
	 * @since 2.1
	 * @return bool|string false if the plan doesn't exist, plan id if it does
	 */
	private function plan_exists( $membership_level_id ) {

		$membership_level = rcp_get_membership_level( $membership_level_id );

		if ( ! $membership_level instanceof Membership_Level ) {
			return false;
		}

		// fallback to old plan id if the new plan id does not exist
		$old_plan_id = strtolower( str_replace( ' ', '', $membership_level->get_name() ) );
		$new_plan_id = $this->generate_plan_id( $membership_level );

		/**
		 * Filters the ID of the plan to check for. If this exists, the new subscription will
		 * use this plan.
		 *
		 * @param string           $new_plan_id ID of the Stripe plan to check for.
		 * @param Membership_Level $plan        Membership level object.
		 */
		$new_plan_id = apply_filters( 'rcp_stripe_existing_plan_id', $new_plan_id, $membership_level );

		// check if the plan new plan id structure exists
		try {

			$plan = \Stripe\Plan::retrieve( $new_plan_id );
			return $plan->id;

		} catch ( Exception $e ) {
			// translators: %s The new plan ID.
			rcp_log( sprintf( __( 'New Plan ID doesn\'t exist. New Plan ID: %s', 'rcp' ), $new_plan_id ) );
		}

		try {
			// fall back to the old plan id structure and verify that the plan metadata also matches
			$stripe_plan = \Stripe\Plan::retrieve( $old_plan_id );

			if ( (int) $stripe_plan->amount !== (int) $membership_level->get_price() * 100 ) {
				return false;
			}

			if ( $stripe_plan->interval !== $membership_level->get_duration_unit() ) {
				return false;
			}

			if ( $stripe_plan->interval_count !== intval( $membership_level->get_duration() ) ) {
				return false;
			}

			return $old_plan_id;

		} catch ( Exception $e ) {
			return false;
		}

	}

	/**
	 * Checks to see if a plan exists with the provided arguments, and if so, returns the ID of
	 * that plan. If not, a new plan is created.
	 *
	 * This method differs from create_plan() and plan_exists() because it doesn't expect
	 * a membership level ID number. This allows for the creation of plans that may not be
	 * exactly based on a membership level's parameters.
	 *
	 * @param array $args           {
	 *                              Array of arguments.
	 *
	 * @type string $name           Required. Name of the plan.
	 * @type float  $price          Required. Price each interval.
	 * @type string $interval       Optional. Billing interval (i.e ."day", "month", "year"). Default is "month".
	 * @type int    $interval_count Optional. Interval count. Default is "1".
	 * @type string $currency       Optional. Currency. Defaults to site currency.
	 * @type string $id             Optional. Plan ID. Automatically generated based on arguments.
	 *                    }
	 *
	 * @since 3.2
	 * @return string|WP_Error Plan ID on success or WP_Error on failure.
	 */
	public function maybe_create_plan( $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'name'           => '',
				'price'          => 0.00,
				'interval'       => 'month',
				'interval_count' => 1,
				'currency'       => strtolower( rcp_get_currency() ),
				'id'             => '',
			)
		);

		// Name and price are required.
		if ( empty( $args['name'] ) || empty( $args['price'] ) ) {
			return new WP_Error( 'missing_name_price', __( 'Missing plan name or price.', 'rcp' ) );
		}

		/*
		 * Create a new object that looks like a membership level object.
		 * We do this because generate_plan_id() expects a membership level object but we
		 * don't actually have one.
		 */
		if ( empty( $args['id'] ) ) {
			$plan_level = new Membership_Level(
				array(
					'name'          => $args['name'],
					'price'         => $args['price'],
					'duration'      => $args['interval_count'],
					'duration_unit' => $args['interval'],
				)
			);

			$plan_id = $this->generate_plan_id( $plan_level );
		} else {
			$plan_id = $args['id'];
		}

		if ( empty( $plan_id ) ) {
			return new WP_Error( 'empty_plan_id', __( 'Empty plan ID.', 'rcp' ) );
		}

		// Convert price to Stripe format.
		$price = round( $args['price'] * rcp_stripe_get_currency_multiplier(), 0 );

		// First check to see if a plan exists with this ID. If so, return that.

		try {

			$membership_level = isset( $plan_level ) ? $plan_level : new stdClass();

			/**
			 * Filters the ID of the plan to check for. If this exists, the new subscription will
			 * use this plan.
			 *
			 * @param string $plan_id          ID of the Stripe plan to check for.
			 * @param object $membership_level Membership level object.
			 */
			$existing_plan_id = apply_filters( 'rcp_stripe_existing_plan_id', $plan_id, $membership_level );

			$plan = \Stripe\Plan::retrieve( $existing_plan_id );

			return $plan->id;

		} catch ( Exception $e ) {
		}

		// Otherwise, create a new plan.

		try {

			$product = \Stripe\Product::create(
				array(
					'name' => $args['name'],
					'type' => 'service',
				)
			);

			$plan = \Stripe\Plan::create(
				array(
					'amount'         => $price,
					'interval'       => $args['interval'],
					'interval_count' => $args['interval_count'],
					'currency'       => $args['currency'],
					'id'             => $plan_id,
					'product'        => $product->id,
				)
			);

			// plan successfully created
			return $plan->id;

		} catch ( Exception $e ) {

			rcp_log( sprintf( 'Error creating Stripe plan. Code: %s; Message: %s', $e->getCode(), $e->getMessage() ) );

			return new WP_Error( 'stripe_exception', sprintf( 'Error creating Stripe plan. Code: %s; Message: %s', $e->getCode(), $e->getMessage() ) );
		}

	}

	/**
	 * Generate a Stripe plan ID string based on a membership level
	 *
	 * The plan name is set to {levelname}-{price}-{duration}{duration unit}
	 * Strip out invalid characters such as '@', '.', and '()'.
	 * Similar to WP core's sanitize_html_class() & sanitize_key() functions.
	 *
	 * @param Membership_Level $membership_level
	 *
	 * @since 3.0.3
	 * @return string
	 */
	private function generate_plan_id( $membership_level ) {

		$level_name = strtolower( str_replace( ' ', '', sanitize_title_with_dashes( $membership_level->get_name() ) ) );
		$plan_id    = sprintf( '%s-%s-%s', $level_name, $membership_level->get_price(), $membership_level->get_duration() . $membership_level->get_duration_unit() );
		$plan_id    = preg_replace( '/[^a-z0-9_\-]/', '-', $plan_id );

		return $plan_id;

	}

	/**
	 * Creates a subscription for a membership.
	 *
	 * @param RCP_Membership $membership The Membership object.
	 *
	 * @return true|WP_Error
	 */
	public function create_off_site_subscription( $membership ) {

		$membership_level = rcp_get_membership_level( $membership->get_object_id() );

		if ( ! $membership_level instanceof Membership_Level ) {
			return new WP_Error( 'invalid_membership_level', __( 'Membership level not found.', 'rcp' ) );
		}

		// Retrieve or create the plan.
		$plan_id = $this->maybe_create_plan(
			array(
				'name'           => $membership->get_membership_level_name(),
				'price'          => $membership->get_recurring_amount(),
				'interval'       => $membership_level->get_duration_unit(),
				'interval_count' => $membership_level->get_duration(),
			)
		);

		if ( is_wp_error( $plan_id ) ) {
			return $plan_id;
		}

		try {

			$customer = \Stripe\Customer::retrieve( $membership->get_gateway_customer_id() );

			$sub_args = array(
				'customer'           => $customer->id,
				'plan'               => $plan_id,
				'proration_behavior' => 'none',
				'metadata'           => array(
					'rcp_subscription_level_id' => $membership->get_object_id(),
					'rcp_member_id'             => $membership->get_user_id(),
					'rcp_customer_id'           => $membership->get_customer_id(),
					'rcp_membership_id'         => $membership->get_id(),
				),
			);

			if ( ! empty( $customer->invoice_settings->default_payment_method ) ) {
				$sub_args['default_payment_method'] = $customer->invoice_settings->default_payment_method;
			}

			// Calculate the subscription start date.
			$base_date    = $membership->get_expiration_date( false );
			$timezone     = get_option( 'timezone_string' );
			$timezone     = ! empty( $timezone ) ? $timezone : 'UTC';
			$datetime     = new DateTime( $base_date, new DateTimeZone( $timezone ) );
			$current_time = getdate();
			$datetime->setTime( $current_time['hours'], $current_time['minutes'], $current_time['seconds'] );
			$start_date = $datetime->getTimestamp() - HOUR_IN_SECONDS; // Reduce by 60 seconds to account for inaccurate server times.

			if ( $start_date > time() ) {
				/*
				 * Now determine if we use `trial_end` or `billing_cycle_anchor` to schedule the start of the
				 * subscription.
				 *
				 * Billing cycle anchor is preferable because that works with Stripe MRR.
				 * However, the anchor date cannot be further in the future than a normal billing cycle duration.
				 * If that's the case, then we have to use trial end instead.
				 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/2503
				 */
				$stripe_max_anchor = $this->get_stripe_max_billing_cycle_anchor( $membership_level->get_duration(), $membership_level->get_duration_unit(), 'now' );

				if ( $start_date > $stripe_max_anchor->getTimestamp() ) {
					$sub_args['trial_end'] = $start_date;
					rcp_log( sprintf( 'Stripe Gateway: Creating subscription with %s start date via trial_end.', $start_date ) );
				} else {
					$sub_args['billing_cycle_anchor'] = $start_date;
					$sub_args['proration_behavior']   = 'none';
					rcp_log( sprintf( 'Stripe Gateway: Creating subscription with %s start date via billing_cycle_anchor.', $start_date ) );
				}
			} else {
				rcp_log( 'Stripe Gateway: Creating subscription immediately.' );
			}

			$sub_options = array(
				'idempotency_key' => rcp_stripe_generate_idempotency_key( $sub_args ),
			);

			$stripe_connect_user_id = get_option( 'rcp_stripe_connect_account_id', false );

			if ( ! empty( $stripe_connect_user_id ) ) {
				$sub_options['stripe_account'] = $stripe_connect_user_id;
			}

			// Create the subscription.
			$subscription = \Stripe\Subscription::create( $sub_args, $sub_options );

			// Link the Stripe subscription to the RCP membership.
			$membership->set_gateway_subscription_id( $subscription->id );

			return true;

		} catch ( \Exception $e ) {
			return new WP_Error( 'subscription_creation_failed', $e->getMessage() );
		}
	}

	/**
	 * Change the price of a membership's subscription in Stripe.
	 *
	 * @param RCP_Membership $membership The membership object.
	 * @param float          $new_price The new price.
	 *
	 * @since 3.2
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function change_membership_subscription_price( $membership, $new_price ) {

		$subscription_id  = $membership->get_gateway_subscription_id();
		$membership_level = rcp_get_subscription_details( $membership->get_object_id() );

		if ( empty( $membership_level ) ) {
			return new WP_Error( 'invalid_membership_level', __( 'Unable to get membership level details', 'rcp' ) );
		}

		// Create a plan in Stripe.
		$plan_id = $this->maybe_create_plan(
			array(
				'name'           => $membership_level->name,
				'price'          => $new_price,
				'interval'       => $membership_level->duration_unit,
				'interval_count' => $membership_level->duration,
			)
		);

		if ( empty( $plan_id ) ) {
			return new WP_Error( 'plan_creation_failed', __( 'Failed to create Stripe plan', 'rcp' ) );
		}

		// Now update the subscription to change the plan.
		try {

			$subscription = \Stripe\Subscription::retrieve( $subscription_id );

			$args = array(
				'proration_behavior' => 'none',
				'items'              => array(
					array(
						'id'   => $subscription->items->data[0]->id,
						'plan' => $plan_id,
					),
				),
			);

			return $this->update_subscription( $subscription_id, $args );

		} catch ( Exception $e ) {

			rcp_log( sprintf( 'Error creating new subscription for membership. Code: %s; Message: %s', $e->getCode(), $e->getMessage() ) );

			return new WP_Error( $e->getCode(), $e->getMessage() );

		}

	}

	/**
	 * Create a Stripe subscription for a given membership object.
	 *
	 * If successful, the membership record is updated accordingly:
	 *      - Expiration date synced with next bill date in Stripe.
	 *      - Auto renew enabled.
	 *      - Gateway set to "stripe".
	 *      - Gateway customer ID is set to Stripe customer ID.
	 *      - Gateway subscription ID is set to Stripe subscription ID.
	 *
	 * @param RCP_Membership $membership Membership object.
	 * @param bool           $charge_now True if the customer should be charged immediately. False if the first payment should be
	 *                                   the date of the membership expiration.
	 *
	 * @since 3.2
	 * @return string|WP_Error New subscription ID on success, WP_Error on failure.
	 */
	public function create_subscription_for_membership( $membership, $charge_now = false ) {

		$args = array();

		// If we're not billing today, set a trial.
		if ( ! $charge_now ) {
			$expiration_date = $membership->get_expiration_date( false );

			try {
				$next_bill_date = new DateTime( $expiration_date );

				/*
				 * Modify the next bill date time to 00:00:00. This is because the time is currently
				 * 23:59:59, and if we leave it at that then the expiration date will be Jan 1st but
				 * Stripe won't bill until Jan 2nd. So we modify the time to ensure the billing happens
				 * before RCP expires the membership.
				 */
				$next_bill_date->setTime( 0, 0, 0 );

				$args['trial_end'] = $next_bill_date->format( 'U' );
			} catch ( Exception $e ) {
				rcp_log( __( 'Error creating date for next billing date', 'rcp' ) );
			}
		}

		$customer_id = $membership->get_gateway_customer_id();

		if ( empty( $customer_id ) || false === strpos( $customer_id, 'cus_' ) ) {
			$customer_id = rcp_get_customer_gateway_id(
				$membership->get_customer()->get_id(),
				array(
					'stripe',
					'stripe_checkout',
				)
			);
		}

		if ( empty( $customer_id ) || false === strpos( $customer_id, 'cus_' ) ) {
			return new WP_Error( 'missing_customer_id', __( 'Unable to determine Stripe customer ID', 'rcp' ) );
		}

		// Get or create a plan ID.
		if ( ! $plan_id = $this->plan_exists( $membership->get_object_id() ) ) {
			// create the plan if it doesn't exist
			$plan_id = $this->create_plan( $membership->get_object_id() );
		}

		try {

			$customer = \Stripe\Customer::retrieve( $customer_id );

			if ( isset( $customer->deleted ) && $customer->deleted ) {
				// This customer was deleted
				return new WP_Error( 'deleted_customer', __( 'Customer deleted in Stripe', 'rcp' ) );
			}

			$sub_args = wp_parse_args(
				$args,
				array(
					'customer'           => $customer->id,
					'plan'               => $plan_id,
					'proration_behavior' => 'none',
					'metadata'           => array(
						'rcp_subscription_level_id' => $membership->get_object_id(),
						'rcp_member_id'             => $membership->get_customer()->get_user_id(),
						'rcp_customer_id'           => $membership->get_customer()->get_id(),
						'rcp_membership_id'         => $membership->get_id(),
					),
				)
			);

			$sub_options            = array();
			$stripe_connect_user_id = get_option( 'rcp_stripe_connect_account_id', false );

			if ( ! empty( $stripe_connect_user_id ) ) {
				$sub_options['stripe_account'] = $stripe_connect_user_id;
			}

			$subscription = \Stripe\Subscription::create( $sub_args, $sub_options );

			$membership->update(
				array(
					'expiration_date'         => gmdate( 'Y-m-d 23:59:59', $subscription->current_period_end ),
					'gateway'                 => 'stripe',
					'gateway_customer_id'     => $customer_id,
					'gateway_subscription_id' => $subscription->id,
				)
			);

			return $subscription->id;

		} catch ( Exception $e ) {

			rcp_log( sprintf( 'Error creating new subscription for membership. Code: %s; Message: %s', $e->getCode(), $e->getMessage() ) );

			return new WP_Error( $e->getCode(), $e->getMessage() );

		}

	}

	/**
	 * Change the next bill date for an existing membership.
	 *
	 * @param RCP_Membership $membership Membership object.
	 * @param string         $next_bill_date Desired next bill date in MySQL format.
	 *
	 * @since 3.2
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function change_next_bill_date( $membership, $next_bill_date ) {

		try {
			$next_bill_date = new DateTime( $next_bill_date );

			/*
			 * Modify the next bill date time to 00:00:00. This is because the time is currently
			 * 23:59:59, and if we leave it at that then the expiration date will be Jan 1st but
			 * Stripe won't bill until Jan 2nd. So we modify the time to ensure the billing happens
			 * before RCP expires the membership.
			 */
			$next_bill_date->setTime( 0, 0, 0 );

			$result = $this->update_subscription(
				$membership->get_gateway_subscription_id(),
				array(
					'trial_end' => $next_bill_date->format( 'U' ),
				)
			);

			return $result;
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}

	}

	/**
	 * Update a subscription in Stripe
	 *
	 * @param string $subscription_id ID of the subscription to update.
	 * @param array  $args            Data to update.
	 *
	 * @since 3.2
	 * @return true|WP_Error
	 */
	public function update_subscription( $subscription_id, $args ) {

		try {

			\Stripe\Subscription::update( $subscription_id, $args );

			return true;

		} catch ( Exception $e ) {

			rcp_log( sprintf( 'Error creating new subscription for membership. Code: %s; Message: %s', $e->getCode(), $e->getMessage() ) );

			return new WP_Error( $e->getCode(), $e->getMessage() );
		}

	}
}
