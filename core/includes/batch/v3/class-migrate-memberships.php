<?php
/**
 * Migrate Memberships
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

use RCP\Membership_Level;
use RCP\Utils\Batch\Abstract_Job_Callback;

/**
 * Class Migrate_Memberships_v3
 *
 * Migrates membership data to the custom memberships table.
 *
 * @package RCP\Utils\Batch
 * @since   3.0
 */
final class RCP_Batch_Callback_Migrate_Memberships_v3 extends Abstract_Job_Callback {

	/**
	 * @var array $memberships The membership data from usermeta
	 */
	private $memberships;

	/**
	 * @var bool Whether or not RCP is network activated and using global tables.
	 */
	private $network_active = false;

	/**
	 * @inheritdoc
	 */
	public function execute() {

		rcp_log( sprintf( 'Batch Member Migration: Initiating RCP_Batch_Callback_Migrate_Memberships_v3. Current step: %d; current count: %d; total count: %d.', $this->get_job()->get_step(), $this->get_job()->get_current_count(), $this->get_job()->get_total_count() ), true );

		if ( $this->start_time === null ) {
			$this->start_time = time();
		}

		if ( $this->time_exceeded() || $this->get_job()->is_completed() ) {
			return $this;
		}

		parent::execute();

		if ( 0 === $this->get_job()->get_step() ) {
			$this->get_job()->clear_errors();
		}

		if (
			is_multisite() &&
			is_plugin_active_for_network( plugin_basename( RCP_PLUGIN_FILE ) ) &&
			( ! defined( 'RCP_NETWORK_SEPARATE_SITES' ) || ! RCP_NETWORK_SEPARATE_SITES )
		) {
			$this->network_active = true;
		}

		$this->disable_membership_actions();

		$this->memberships = $this->get_batch();

		if ( empty( $this->memberships ) || $this->get_job()->is_completed() ) {
			$this->finish();
			return $this;
		}

		$this->process_batch();

		$current_step = $this->get_job()->get_step();
		$current_step++;
		$this->get_job()->set_step( $current_step );
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function finish() {
		// Update membership counts.
		rcp_check_member_counts();

		// Set job to complete.
		$this->get_job()->set_status( 'complete' );

		$errors = $this->get_job()->get_errors();

		rcp_log( sprintf( 'Batch Member Migration: Job complete. Errors: %s', var_export( $errors, true ) ), true );
	}

	/**
	 * Disable all membership-related hooks during the migration.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function disable_membership_actions() {

		// Disable activation sequence, as memberships were already activated.
		remove_action( 'rcp_new_membership_added', 'rcp_activate_membership_on_insert', 10 );

		// Prevent status-related emails from being sent.
		remove_action( 'rcp_membership_post_activate', 'rcp_email_on_membership_activation', 10 );
		remove_action( 'rcp_membership_post_cancel', 'rcp_email_on_membership_cancellation', 10 );
		remove_action( 'rcp_transition_membership_status_expired', 'rcp_email_on_membership_expiration', 10 );

	}

	/**
	 * Count the total number of results and save the value.
	 *
	 * @since 3.1
	 * @return int
	 */
	public function get_total_count() {

		global $wpdb;

		$usermeta_table = $wpdb->usermeta;
		$total_count    = $this->get_job()->get_total_count();

		if ( empty( $total_count ) ) {
			if ( is_multisite() && ! $this->network_active ) {
				$blog_id          = get_current_blog_id();
				$capabilities_key = 1 == $blog_id ? $wpdb->base_prefix . 'capabilities' : $wpdb->base_prefix . $blog_id . '_capabilities';
				$total_count      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$usermeta_table} m1, {$usermeta_table} m2 WHERE m1.user_id = m2.user_id AND m1.meta_key = 'rcp_status' AND m2.meta_key = %s", $capabilities_key ) );
			} else {
				$total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$usermeta_table} WHERE meta_key = 'rcp_status'" );
			}
			$this->get_job()->set_total_count( $total_count );

			rcp_log( sprintf( 'Batch Member Migration: Total count: %d.', $total_count ), true );
		}

		return $total_count;
	}

	/**
	 * Retrieves the batch of membership records from usermeta.
	 *
	 * @since 3.0
	 * @return array Membership data from usermeta.
	 */
	private function get_batch() {
		if ( ! empty( $this->memberships ) ) {
			return $this->memberships;
		}

		global $wpdb;

		$usermeta_table = $wpdb->usermeta;

		$this->get_total_count();

		if ( is_multisite() && ! $this->network_active ) {
			$blog_id          = get_current_blog_id();
			$capabilities_key = 1 == $blog_id ? $wpdb->base_prefix . 'capabilities' : $wpdb->base_prefix . $blog_id . '_capabilities';
			$results          = $wpdb->get_results( $wpdb->prepare( "SELECT m1.* FROM {$usermeta_table} m1, {$usermeta_table} m2 WHERE m1.user_id = m2.user_id AND m1.meta_key = 'rcp_status' AND m2.meta_key = %s LIMIT %d OFFSET %d", $capabilities_key, $this->size, $this->offset ) );
		} else {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$usermeta_table} WHERE meta_key = 'rcp_status' LIMIT %d OFFSET %d", $this->size, $this->offset ) );
		}

		rcp_log( sprintf( 'Batch Member Migration: %d results found in query for LIMIT %d OFFSET %d.', count( $results ), $this->size, $this->offset ) );

		if ( empty( $results ) ) {
			return [];
		}

		return $results;
	}

	/**
	 * Processing the batch of memberships.
	 */
	private function process_batch() {

		global $rcp_options;

		$rcp_options['debug_mode'] = false;

		global $wpdb;

		$rcp_payments = new \RCP_Payments();

		$processed = [];

		if ( empty( $this->memberships ) || $this->time_exceeded() ) {
			$this->get_job()->adjust_current_count( count( $processed ) );
			return;
		}

		foreach ( $this->memberships as $key => $membership ) {

			$meta_items = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE user_id = %d", $membership->user_id ) );
			$user_meta  = array();

			foreach ( $meta_items as $meta_item ) {
				$user_meta[$meta_item->meta_key] = maybe_unserialize( $meta_item->meta_value );
			}

			$user                = get_userdata( $membership->user_id );
			$membership_level_id = 0;
			$pending_payment     = false;

			if ( empty( $user ) ) {
				$this->get_job()->add_error( sprintf( __( 'Skipped user #%d - unable to locate user account. Possible orphaned user meta.', 'rcp' ), $membership->user_id ) );

				continue;
			}

			if ( ! empty( $user_meta['rcp_subscription_level'] ) ) {
				$membership_level_id = $user_meta['rcp_subscription_level'];
			} elseif ( ! empty( $user_meta['rcp_pending_payment_id'] ) ) {
				$pending_payment     = $rcp_payments->get_payment( $user_meta['rcp_pending_payment_id'] );
				$membership_level_id = ! empty( $pending_payment->object_id ) ? $pending_payment->object_id : 0;
			}

			if ( empty( $membership_level_id ) ) {
				$this->get_job()->add_error( sprintf( __( 'Skipped user #%d - unable to determine membership level.', 'rcp' ), $user->ID ) );

				continue;
			}

			$membership_level = rcp_get_membership_level( $membership_level_id );

			if ( ! $membership_level instanceof Membership_Level ) {
				$this->get_job()->add_error( sprintf( __( 'Skipped user #%d - unable to get membership level details for ID #%d.', 'rcp' ), $user->ID, $membership_level_id ) );

				continue;
			}

			/**
			 * First retrieve or create a new customer record.
			 */

			// Check email verification status.
			if ( ! empty( $user_meta['rcp_pending_email_verification'] ) ) {
				// Pending verification.
				$email_verification = 'pending';
			} elseif ( ! empty( $user_meta['rcp_email_verified'] ) ) {
				// Already verified.
				$email_verification = 'verified';
			} else {
				// Verification not required.
				$email_verification = 'none';
			}

			$existing_customer = rcp_get_customer_by_user_id( $user->ID );

			if ( ! $existing_customer instanceof RCP_Customer ) {
				// Create a new customer.
				$customer_data = array(
					'user_id'            => $user->ID,
					'date_registered'    => $user->user_registered,
					'email_verification' => $email_verification,
					'last_login'         => '',
					'ips'                => '',
					'notes'              => ''
				);

				$customer_id = rcp_add_customer( $customer_data );
				$customer    = rcp_get_customer( $customer_id );
			} else {
				// Update customer if their email verification status has changed.
				if ( $email_verification != $existing_customer->get_email_verification_status() ) {
					$existing_customer->update( array(
						'email_verification' => $email_verification
					) );
				}

				$customer = $existing_customer;
			}

			if ( ! isset( $customer ) || ! $customer instanceof RCP_Customer ) {
				$this->get_job()->add_error( sprintf( __( 'Error inserting customer record for user #%d.', 'rcp' ), $user->ID ) );

				continue;
			}

			/**
			 * Disable all the customer's memberships. This ensures we don't wind up with any duplicates if
			 * this migration is run more than once.
			 */
			$customer->disable_memberships();

			/**
			 * Update all this user's payments to add the new customer ID.
			 */
			$wpdb->update(
				rcp_get_payments_db_name(),
				array( 'customer_id' => absint( $customer->get_id() ) ),
				array( 'user_id' => $user->ID ),
				array( '%d' ),
				array( '%d' )
			);

			/**
			 * Get the first payment of this membership, if it exists.
			 */

			$first_membership_payment = $rcp_payments->get_payments( array(
				'user_id'      => $membership->user_id,
				'number'       => 1,
				'order'        => 'ASC',
				'subscription' => $membership_level->get_name(),
			) );

			if ( ! empty( $first_membership_payment ) ) {
				$first_membership_payment = reset( $first_membership_payment );
			}

			$last_membership_payment = $rcp_payments->get_payments( array(
				'user_id'      => $membership->user_id,
				'number'       => 1,
				'order'        => 'DESC',
				'subscription' => $membership_level->get_name()
			) );

			if ( ! empty( $last_membership_payment ) ) {
				$last_membership_payment = reset( $last_membership_payment );
			}

			$times_billed = $rcp_payments->count( [
				'user_id'   => $membership->user_id,
				'status'    => 'complete',
				'object_id' => $membership_level_id
			] );

			$gateway_info = $this->get_gateway( $user_meta, $last_membership_payment );

			if ( $pending_payment ) {
				$expiration_date = rcp_calculate_subscription_expiration( $membership_level_id );
			} else {
				$expiration_date = ! empty( $user_meta['rcp_expiration'] ) ? $user_meta['rcp_expiration'] : 'none';
			}

			if ( empty( $expiration_date ) || strtolower( $expiration_date ) === 'none' ) {
				$expiration_date = null;
			}

			if ( ! empty( $expiration_date ) ) {
				$date            = new \DateTime( $expiration_date, new \DateTimeZone( 'UTC' ) );
				$expiration_date = $date->format( 'Y-m-d H:i:s' );
			}

			// Determine the user's status.
			$status           = 'free' == $user_meta['rcp_status'] ? 'active' : $user_meta['rcp_status'];
			$allowed_statuses = array( 'active', 'expired', 'cancelled', 'pending' );

			if ( ! in_array( $status, $allowed_statuses ) ) {
				if ( strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $expiration_date, current_time( 'timestamp' ) ) ) {
					$status = 'expired';
				} else {
					$status = 'active';
				}
			}

			// Explicitly set amounts to `0.00` if empty.
			$initial_amount = ! empty( $first_membership_payment->amount ) ? $first_membership_payment->amount : ( $membership_level->get_price() + $membership_level->get_fee() );
			if ( empty( $initial_amount ) ) {
				$initial_amount = 0.00;
			}

			$recurring_amount = $membership_level->get_price();
			if ( empty( $recurring_amount ) ) {
				$recurring_amount = 0.00;
			}

			// Determine the user's join date.
			$join_date_meta_key = 'rcp_joined_date_' . $membership_level_id;
			$join_date          = ! empty( $user_meta[$join_date_meta_key] ) ? $user_meta[$join_date_meta_key] : false;
			if ( empty( $join_date ) && ! empty( $first_membership_payment->date ) ) {
				$join_date = $first_membership_payment->date;
			} elseif ( empty( $join_date ) ) {
				$join_date = current_time( 'mysql' );
			}

			// See if we have a renewal date.
			$renewal_date_meta_key = 'rcp_renewed_date_' . $membership_level_id;
			$renewal_date          = ! empty( $user_meta[$renewal_date_meta_key] ) ? $user_meta[$renewal_date_meta_key] : '';

			// Get the subscription key.
			if ( $pending_payment ) {
				$subscription_key = $pending_payment->subscription_key;
			} elseif ( ! empty( $user_meta['rcp_subscription_key'] ) ) {
				$subscription_key = $user_meta['rcp_subscription_key'];
			} else {
				$subscription_key = '';
			}

			$data = array(
				'customer_id'             => $customer->get_id(),
				'user_id'                 => $user->ID,
				'object_id'               => $membership_level_id,
				'object_type'             => 'membership',
				'initial_amount'          => $initial_amount,
				'recurring_amount'        => $recurring_amount,
				'created_date'            => $join_date,
				'trial_end_date'          => ! empty( $user_meta['rcp_is_trialing'] ) ? $expiration_date : '',
				'renewal_date'            => $renewal_date,
				'cancellation_date'       => '',
				'expiration_date'         => $expiration_date,
				'auto_renew'              => ( ! empty( $user_meta['rcp_recurring'] ) && 'no' != $user_meta['rcp_recurring'] ) ? 1 : 0,
				'times_billed'            => $times_billed,
				'maximum_renewals'        => 0,
				'status'                  => $status,
				'gateway'                 => $gateway_info['gateway'],
				'gateway_customer_id'     => $gateway_info['gateway_customer_id'],
				'gateway_subscription_id' => $gateway_info['gateway_subscription_id'],
				'signup_method'           => ! empty( $user_meta['rcp_signup_method'] ) ? $user_meta['rcp_signup_method'] : 'live',
				'subscription_key'        => $subscription_key,
				'notes'                   => ! empty( $user_meta['rcp_notes'] ) ? $user_meta['rcp_notes'] : '',
			);

			$membership_id = rcp_add_membership( $data );

			if ( empty( $membership_id ) ) {
				$this->get_job()->add_error( sprintf( __( 'Error inserting membership record for user #%d.', 'rcp' ), $user->ID ) );

				continue;
			}

			/**
			 * Update all this user's payments to add this membership ID.
			 */
			$wpdb->update(
				rcp_get_payments_db_name(),
				array( 'membership_id' => absint( $membership_id ) ),
				array(
					'user_id'      => $membership->user_id,
					'subscription' => $membership_level->get_name()
				),
				array( '%d' ),
				array( '%d', '%s' )
			);

			$processed[] = $membership_id;

			unset( $this->memberships[$key] );
		}

		$this->get_job()->adjust_current_count( count( $processed ) );

		if ( $this->get_job()->is_completed() ) {
			$this->finish();
		}
	}

	/**
	 * Determine the payment gateway used for membership.
	 *
	 * @param array  $user_meta Array of user meta.
	 * @param object $payment   Payment object.
	 *
	 * @access private
	 * @since  3.0
	 * @return array
	 */
	private function get_gateway( $user_meta, $payment ) {

		$profile_id      = ! empty( $user_meta['rcp_payment_profile_id'] ) ? $user_meta['rcp_payment_profile_id'] : '';
		$merchant_sub_id = ! empty( $user_meta['rcp_merchant_subscription_id'] ) ? $user_meta['rcp_merchant_subscription_id'] : '';

		$gateway_info = array(
			'gateway'                 => '',
			'gateway_customer_id'     => '',
			'gateway_subscription_id' => ''
		);

		if ( 'free' == $user_meta['rcp_status'] ) {
			return $gateway_info;
		}

		// Check for Stripe.
		if ( false !== strpos( $profile_id, 'cus_' ) ) {
			$gateway_info['gateway']                 = 'stripe';
			$gateway_info['gateway_customer_id']     = $profile_id;
			$gateway_info['gateway_subscription_id'] = $merchant_sub_id;

			return $gateway_info;
		}

		// Check for 2Checkout.
		if ( false !== strpos( $profile_id, '2co_' ) ) {
			$gateway_info['gateway']                 = 'twocheckout';
			$gateway_info['gateway_subscription_id'] = $profile_id;

			return $gateway_info;
		}

		// Check for Authorize.net.
		if ( false !== strpos( $profile_id, 'anet_' ) ) {
			$gateway_info['gateway']                 = 'authorizenet';
			$gateway_info['gateway_subscription_id'] = $profile_id;

			return $gateway_info;
		}

		// Check for Braintree.
		if ( false !== strpos( $profile_id, 'bt_' ) ) {
			$gateway_info['gateway']                 = 'braintree';
			$gateway_info['gateway_customer_id']     = $profile_id;
			$gateway_info['gateway_subscription_id'] = $merchant_sub_id;

			return $gateway_info;
		}

		// Check for PayPal.
		if ( false !== strpos( $profile_id, 'I-' ) ) {
			$gateway_info['gateway']                 = 'paypal';
			$gateway_info['gateway_subscription_id'] = $profile_id;

			// Determine which PayPal gateway was used from last payment.
			if ( ! empty( $payment->gateway ) ) {
				$gateway_info['gateway'] = $payment->gateway;
			}

			return $gateway_info;
		}

		// Check for third party gateways.
		if ( ! empty( $payment->gateway ) && 'free' != $payment->gateway ) {
			$gateway_info['gateway'] = $payment->gateway;
		}

		$gateway_info['gateway_subscription_id'] = $profile_id;

		return $gateway_info;

	}
}
