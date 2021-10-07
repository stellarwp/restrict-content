<?php
/**
 * RCP Member class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Member
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
 */

/**
 * Class RCP_Member
 *
 * @deprecated 3.0 Use RCP_Customer instead.
 * @see        RCP_Customer
 */
class RCP_Member extends WP_User {

	/**
	 * New customer object for this user.
	 *
	 * @var RCP_Customer $customer
	 *
	 * @access private
	 * @since 3.0
	 */
	private $customer = false;

	/**
	 * New membership object for this user.
	 *
	 * @var RCP_Membership|false $membership
	 *
	 * @access private
	 * @since 3.0
	 */
	private $membership = false;

	/**
	 * Get the new RCP_Customer object for this user.
	 *
	 * @param bool $create Whether or not to create a new customer if one doesn't already exist.
	 *
	 * @access private
	 * @since 3.0
	 * @return RCP_Customer|false
	 */
	private function get_customer( $create = false ) {

		if ( ! is_object( $this->customer ) ) {
			$this->customer = rcp_get_customer_by_user_id( $this->ID );

			if ( empty( $this->customer ) && ! empty( $create ) ) {
				// Create a new customer.
				$customer_id = rcp_add_customer( array( 'user_id' => $this->ID ) );

				if ( ! empty( $customer_id ) ) {
					$this->customer = rcp_get_customer( $customer_id );
				}
			}
		}

		return $this->customer;

	}

	/**
	 * Get this user's membership. This returns ONE membership only - the first one this user had. This is done for
	 * backwards compatibility purposes for when we introduce multiple memberships but functions still expect one
	 * result and not an array.
	 *
	 * @param bool $create Whether or not to create a new membership if one doesn't already exist. We set this to `true`
	 *                     in all the SET functions. Otherwise it's `false`.
	 *
	 * @access private
	 * @since 3.0
	 * @return RCP_Membership|false
	 */
	private function get_membership( $create = false ) {

		if ( ! is_object( $this->membership ) ) {
			$customer = $this->get_customer( $create );

			if ( is_object( $customer ) ) {
				$this->membership = rcp_get_customer_single_membership( $customer->get_id() );

				if ( empty( $this->membership ) && ! empty( $create ) ) {
					$membership_id = rcp_add_membership( array( 'customer_id' => $customer->get_id() ) );

					if ( ! empty( $membership_id ) ) {
						$this->membership = rcp_get_membership( $membership_id );
					}
				}
			}
		}

		return $this->membership;

	}

	/**
	 * Retrieves the status of the member
	 *
	 * @access  public
	 * @since   2.1
	 * @return  string
	 */
	public function get_status() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			if ( ! $membership->is_paid() && $membership->is_active() ) {
				// Backwards compatible support for "free" status.
				$status = 'free';
			} else {
				// All other statuses.
				$status = $membership->get_status();
			}
		} else {
			$status = apply_filters( 'rcp_member_get_status', 'free', $this->ID, $this );
		}

		return $status;

	}

	/**
	 * Sets the status of a member
	 *
	 * @param  string $new_status New status to set.
	 *
	 * @access  public
	 * @since   2.1
	 * @return  bool Whether or not the status was updated.
	 */
	public function set_status( $new_status = '' ) {

		$membership = $this->get_membership( true );

		if ( ! empty( $membership ) ) {
			return $membership->set_status( $new_status );
		}

		return false;

	}

	/**
	 * Retrieves the expiration date of the member
	 *
	 * @param  bool $formatted Whether or not the returned value should be formatted.
	 * @param  bool $pending   Whether or not to check the pending expiration date.
	 *
	 * @access  public
	 * @since   2.1
	 * @return  string
	 */
	public function get_expiration_date( $formatted = true, $pending = true ) {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$expiration = $membership->get_expiration_date( $formatted );
		} else {
			$expiration = apply_filters( 'rcp_member_get_expiration_date', false, $this->ID, $this, $formatted, $pending );
		}

		return $expiration;

	}

	/**
	 * Retrieves the expiration date of the member as a timestamp
	 *
	 * @access  public
	 * @since   2.1
	 * @return  int|false
	 */
	public function get_expiration_time() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$timestamp = $membership->get_expiration_time();
		} else {
			$timestamp = apply_filters( 'rcp_member_get_expiration_time', false, $this->ID, $this );
		}

		return $timestamp;

	}

	/**
	 * Sets the expiration date for a member
	 *
	 * Should be passed as a MYSQL date string.
	 *
	 * @param   string $new_date New date as a MySQL date string.
	 *
	 * @access  public
	 * @since   2.1
	 * @return  bool Whether or not the expiration date was updated.
	 */
	public function set_expiration_date( $new_date = '' ) {

		$membership = $this->get_membership( true );

		if ( ! empty( $membership ) ) {
			return $membership->set_expiration_date( $new_date );
		}

		return false;

	}

	/**
	 * Calculates the new expiration date for a member
	 *
	 * @param   bool $force_now Whether or not to force an update.
	 * @param   bool $trial     Whether or not this is for a free trial.
	 *
	 * @access  public
	 * @since   2.4
	 * @return  String Date in Y-m-d H:i:s format or "none" if is a lifetime member
	 */
	public function calculate_expiration( $force_now = false, $trial = false ) {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			return $membership->calculate_expiration( $force_now, $trial );
		}

		return false;

	}

	/**
	 * Sets the joined date for a member
	 *
	 * @param  string $date            Join date in MySQL date format.
	 * @param  int    $subscription_id ID of the subscription level.
	 *
	 * @access  public
	 * @since   2.6
	 * @return  int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	public function set_joined_date( $date = '', $subscription_id = 0 ) {

		$membership = $this->get_membership( true );

		if ( empty( $membership ) ) {
			return false;
		}

		if( empty( $date ) ) {
			$date = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
		}

		$ret = rcp_update_membership( $membership->get_id(), array( 'created_date' => $date ) );

		/**
		 * @deprecated 3.0
		 */
		do_action( 'rcp_set_joined_date', $this->ID, $date, $this );

		return $ret;

	}

	/**
	 * Retrieves the joined date for a subscription
	 *
	 * @param   int $subscription_id ID of the subscription level.
	 *
	 * @access  public
	 * @since   2.6
	 * @return  string Joined date
	 */
	public function get_joined_date( $subscription_id = 0 ) {

		$date       = false;
		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$date = $membership->get_created_date( false );
		}

		return apply_filters( 'rcp_get_joined_date', $date, $this->ID, $subscription_id, $this );

	}

	/**
	 * Sets the renewed date for a member
	 *
	 * @param   string $date Renewed date in MySQL format.
	 *
	 * @access  public
	 * @since   2.6
	 * @return  int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	public function set_renewed_date( $date = '' ) {

		if( get_user_meta( $this->ID, '_rcp_new_subscription', true ) ) {
			return; // This is a new subscription so do not set anything
		}

		if( empty( $date ) ) {
			$date = date( 'Y-m-d H:i:s' );
		}

		$membership = $this->get_membership(false );

		if ( empty( $membership ) ) {
			$ret = false;
		} else {
			$ret = $membership->set_renewed_date( $date );
		}

		do_action( 'rcp_set_renewed_date', $this->ID, $date, $this );

		return $ret;

	}

	/**
	 * Retrieves the renewed date for a subscription
	 *
	 * @param   int $subscription_id ID of the subscription level.
	 *
	 * @access  public
	 * @since   2.6
	 * @return  string Renewed date
	 */
	public function get_renewed_date( $subscription_id = 0 ) {

		if( empty( $subscription_id ) ) {
			$subscription_id = $this->get_subscription_id();
		}

		$date = get_user_meta( $this->ID, 'rcp_renewed_date_' . $this->get_subscription_id(), true );

		$customer = $this->get_customer();

		// If we don't have a customer, bail now.
		if ( empty( $customer ) ) {
			return apply_filters( 'rcp_get_renewed_date', $date, $this->ID, $subscription_id, $this );
		}

		// Get membership with this subscription ID.
		$memberships = $customer->get_memberships( array(
			'object_id' => $subscription_id
		) );

		// If no memberships found - bail.
		if ( empty( $memberships ) || empty( $memberships[0] ) ) {
			return apply_filters( 'rcp_get_renewed_date', $date, $this->ID, $subscription_id, $this );
		}

		/**
		 * @var RCP_Membership $membership
		 */
		$membership = $memberships[0];
		$date       = $membership->get_renewed_date( false );

		return apply_filters( 'rcp_get_renewed_date', $date, $this->ID, $subscription_id, $this );

	}

	/**
	 * Renews a member's membership by updating status and expiration date
	 *
	 * Does NOT handle payment processing for the renewal. This should be called after receiving a renewal payment.
	 *
	 * @param   bool   $recurring  Whether or not the membership is recurring.
	 * @param   string $status     Membership status.
	 * @param   string $expiration Membership expiration date in MySQL format.
	 *
	 * @access  public
	 * @since   2.1
	 * @return  void|false
	 */
	public function renew( $recurring = false, $status = 'active', $expiration = '' ) {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$membership->renew( $recurring, $status, $expiration );
		}

	}

	/**
	 * Sets a member's membership as cancelled by updating status
	 *
	 * Does NOT handle actual cancellation of subscription payments, that is done in rcp_process_member_cancellation(). This should be called after a member is successfully cancelled.
	 *
	 * @access  public
	 * @since   2.1
	 * @return  void
	 */
	public function cancel() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$membership->cancel();
		}

	}

	/**
	 * Determines if the member can cancel their subscription on site
	 *
	 * @access  public
	 * @since   2.7.2
	 * @return  bool True if the member can cancel, false if not.
	 */
	public function can_cancel() {

		$membership = $this->get_membership();
		$can_cancel = false;

		if ( ! empty( $membership ) ) {
			$can_cancel = $membership->can_cancel();
		} else {
			$can_cancel = apply_filters( 'rcp_member_can_cancel', $can_cancel, $this->ID );
		}

		return $can_cancel;

	}

	/**
	 * Cancel the member's payment profile
	 *
	 * @param bool $set_status Whether or not to update the status to 'cancelled'.
	 *
	 * @access  public
	 * @since   2.7.2
	 * @return  bool Whether or not the cancellation was successful.
	 */
	public function cancel_payment_profile( $set_status = true ) {

		$membership = $this->get_membership();

		if ( empty( $membership ) ) {
			return false;
		}

		$success = $membership->cancel_payment_profile( $set_status );

		if ( is_wp_error( $success ) ) {
			return false;
		}

		return $success;

	}

	/**
	 * Retrieves the profile ID of the member.
	 *
	 * This is used by payment gateways to store customer IDs and other identifiers for payment profiles
	 *
	 * @access  public
	 * @since   2.1
	 * @return  string
	 */
	public function get_payment_profile_id() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$profile_id = $membership->get_gateway_customer_id();
		} else {
			$profile_id = apply_filters( 'rcp_member_get_payment_profile_id', false, $this->ID, $this );
		}

		return $profile_id;

	}

	/**
	 * Sets the payment profile ID for a member
	 *
	 * This is used by payment gateways to store customer IDs and other identifiers for payment profiles.
	 *
	 * @param  string $profile_id Payment profile ID.
	 *
	 * @access  public
	 * @since   2.1
	 * @return  void
	 */
	public function set_payment_profile_id( $profile_id = '' ) {

		$membership = $this->get_membership( true );

		if ( ! empty( $membership ) ) {
			$membership->set_gateway_customer_id( $profile_id );
		}

	}

	/**
	 * Retrieves the subscription ID of the member from the merchant processor.
	 *
	 * This is used by payment gateways to retrieve the ID of the subscription.
	 *
	 * @access  public
	 * @since   2.5
	 * @return  string
	 */
	public function get_merchant_subscription_id() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$subscription_id = $membership->get_gateway_subscription_id();
		} else {
			$subscription_id = apply_filters( 'rcp_member_get_merchant_subscription_id', false, $this->ID, $this );
		}

		return $subscription_id;

	}

	/**
	 * Sets the payment profile ID for a member
	 *
	 * This is used by payment gateways to store the ID of the subscription.
	 *
	 * @param  string $subscription_id
	 *
	 * @access  public
	 * @since   2.5
	 * @return  void
	 */
	public function set_merchant_subscription_id( $subscription_id = '' ) {

		$membership = $this->get_membership( true );

		if ( ! empty( $membership ) ) {
			$membership->set_gateway_subscription_id( $subscription_id );
		}

	}

	/**
	 * Retrieves the subscription ID of the member
	 *
	 * @access  public
	 * @since   2.1
	 * @return  int|false
	 */
	public function get_subscription_id() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$subscription_id = $membership->get_object_id();
		} else {
			$subscription_id = apply_filters( 'rcp_member_get_subscription_id', false, $this->ID, $this );
		}

		return $subscription_id;

	}

	/**
	 * Set member's subscription ID
	 *
	 * @param int $subscription_id ID of the subscription level to set.
	 *
	 * @access public
	 * @since  2.7.4
	 * @return void
	 */
	public function set_subscription_id( $subscription_id ) {

		$membership = $this->get_membership( true );

		if ( ! empty( $membership ) ) {
			$membership->set_object_id( $subscription_id );
		}

	}

	/**
	 * Retrieves the pending subscription ID of the member
	 *
	 * @access  public
	 * @since   2.4.12
	 * @return  int|false
	 */
	public function get_pending_subscription_id() {

		/**
		 * @var RCP_Payments $rcp_payments_db
		 */
		global $rcp_payments_db;

		$pending_level_id = get_user_meta( $this->ID, 'rcp_pending_subscription_level', true );
		$pending_payment  = $this->get_pending_payment_id();

		if ( ! empty( $pending_payment ) ) {
			$payment          = $rcp_payments_db->get_payment( absint( $pending_payment ) );
			$pending_level_id = $payment->object_id;
		}

		return $pending_level_id;

	}

	/**
	 * Retrieves the subscription key of the member
	 *
	 * @access  public
	 * @since   2.1
	 * @return  string
	 */
	public function get_subscription_key() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$subscription_key = $membership->get_subscription_key();
		} else {
			$subscription_key = apply_filters( 'rcp_member_get_subscription_key', false, $this->ID, $this );
		}

		return $subscription_key;

	}

	/**
	 * Set member's subscription key
	 *
	 * @param string $subscription_key Key to set. Automatically generated if omitted.
	 *
	 * @access public
	 * @since  2.7.4
	 * @return void
	 */
	public function set_subscription_key( $subscription_key = '' ) {

		$membership = $this->get_membership( true );

		if ( ! empty( $membership ) ) {
			$this->membership->set_subscription_key( $subscription_key );
		}

	}

	/**
	 * Retrieves the pending subscription key of the member
	 *
	 * @access  public
	 * @since   2.4.12
	 * @return  string
	 */
	public function get_pending_subscription_key() {

		/**
		 * @var RCP_Payments $rcp_payments_db
		 */
		global $rcp_payments_db;

		$pending_key      = get_user_meta( $this->ID, 'rcp_pending_subscription_key', true );
		$pending_payment  = $this->get_pending_payment_id();

		if ( ! empty( $pending_payment ) ) {
			$payment     = $rcp_payments_db->get_payment( absint( $pending_payment ) );
			$pending_key = $payment->subscription_key;
		}

		return $pending_key;

	}

	/**
	 * Retrieves the current subscription name of the member
	 *
	 * @uses    rcp_get_subscription_name()
	 *
	 * @access  public
	 * @since   2.1
	 * @return  string
	 */
	public function get_subscription_name() {

		$sub_name = rcp_get_subscription_name( $this->get_subscription_id() );

		return apply_filters( 'rcp_member_get_subscription_name', $sub_name, $this->ID, $this );

	}

	/**
	 * Retrieves the pending subscription name of the member.
	 *
	 * @uses    rcp_get_subscription_name()
	 *
	 * @access  public
	 * @since   2.4.12
	 * @return  string
	 */
	public function get_pending_subscription_name() {

		$sub_name = rcp_get_subscription_name( $this->get_pending_subscription_id() );

		return apply_filters( 'rcp_member_get_subscription_name', $sub_name, $this->ID, $this );

	}

	/**
	 * Retrieves all payments belonging to the member
	 *
	 * @access  public
	 * @since   2.1
	 * @return  array|null Array of objects.
	 */
	public function get_payments() {

		$customer = $this->get_customer();

		if ( ! empty( $customer ) ) {
			return $customer->get_payments();
		}

		return null;
	}

	/**
	 * Retrieves the ID number of the currently pending payment.
	 *
	 * @access public
	 * @since 2.9
	 * @return int|bool ID of the pending payment or false if none.
	 */
	public function get_pending_payment_id() {

		$customer = $this->get_customer();

		if ( ! empty( $customer ) ) {
			return $customer->get_pending_payment_id();
		}

		return false;

	}

	/**
	 * Retrieves the notes on a member
	 *
	 * @access  public
	 * @since   2.1
	 * @return  string
	 */
	public function get_notes() {

		$customer = $this->get_customer();

		if ( ! empty( $customer ) ) {
			$notes = $customer->get_notes();
		} else {
			$notes = apply_filters( 'rcp_member_get_notes', false, $this->ID, $this );
		}

		return $notes;

	}

	/**
	 * Adds a new note to a member
	 *
	 * @param   string $note Note to add to the member.
	 *
	 * @access  public
	 * @since   2.1
	 * @return  true
	 */
	public function add_note( $note = '' ) {

		$customer = $this->get_customer();

		if ( ! empty( $customer ) ) {
			return $customer->add_note( $note );
		}

		return false;

	}

	/**
	 * Determines if a member has an active subscription, or is cancelled but has not reached EOT
	 *
	 * @access  public
	 * @since   2.1
	 * @return  bool
	 */
	public function is_active() {

		$ret = false;

		if( user_can( $this->ID, 'manage_options' ) ) {
			$ret = true;
		} else {
			$membership = $this->get_membership();

			if ( ! empty( $membership ) ) {
				$ret = $membership->is_active() && $membership->is_paid();
			}
		}

		return apply_filters( 'rcp_is_active', $ret, $this->ID, $this );

	}

	/**
	 * Determines if a member has a recurring subscription
	 *
	 * @access  public
	 * @since   2.1
	 * @return  bool
	 */
	public function is_recurring() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			return $membership->is_recurring();
		}

		return false;
	}

	/**
	 * Sets whether a member is recurring
	 *
	 * @param   bool $yes True if recurring, false if not.
	 *
	 * @access  public
	 * @since   2.1
	 * @return  void
	 */
	public function set_recurring( $yes = true ) {

		$membership = $this->get_membership( true );

		if ( ! empty( $membership ) ) {
			$membership->set_recurring( $yes );
		}

	}

	/**
	 * Determines if the member is expired
	 *
	 * @access  public
	 * @since   2.1
	 * @return  bool
	 */
	public function is_expired() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$is_expired = $membership->is_expired();
		} else {
			$is_expired = apply_filters( 'rcp_member_is_expired', false, $this->ID, $this );
		}

		return $is_expired;

	}

	/**
	 * Determines if the member is currently trailing
	 *
	 * @access  public
	 * @since   2.1
	 * @return  bool
	 */
	public function is_trialing() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$ret = $membership->is_trialing();
		} else {
			$ret = false;
			$ret = apply_filters( 'rcp_is_trialing', $ret, $this->ID );
			$ret = apply_filters( 'rcp_member_is_trialing', $ret, $this->ID, $this );
		}

		return $ret;

	}

	/**
	 * Determines if the member has used a trial
	 *
	 * @access  public
	 * @since   2.1
	 * @return  bool
	 */
	public function has_trialed() {

		$customer = $this->get_customer();

		if ( ! empty( $customer ) ) {
			return $customer->has_trialed();
		}

		return false;

	}

	/**
	 * Determines if a member is pending email verification.
	 *
	 * @access public
	 * @return bool
	 */
	public function is_pending_verification() {

		$customer = $this->get_customer();

		if ( ! empty( $customer ) ) {
			$is_pending = $customer->is_pending_verification();
		} else {
			$is_pending = apply_filters( 'rcp_is_pending_email_verification', false, $this->ID, $this );
		}

		return (bool) $is_pending;

	}

	/**
	 * Confirm email verification
	 *
	 * @access public
	 * @return void
	 */
	public function verify_email() {

		$customer = $this->get_customer();

		if ( ! empty( $customer ) ) {
			$customer->verify_email();
		}

	}

	/**
	 * Determines if the member can access specified content
	 *
	 * @param   int $post_id ID of the post to check the permissions on.
	 *
	 * @access  public
	 * @since   2.1
	 * @return  bool
	 */
	public function can_access( $post_id = 0 ) {

		// Admins always get access.
		if( user_can( $this->ID, 'manage_options' ) ) {
			return apply_filters( 'rcp_member_can_access', true, $this->ID, $post_id, $this );
		}

		// If the post is unrestricted, everyone gets access.
		if( ! rcp_is_restricted_content( $post_id ) ) {
			return apply_filters( 'rcp_member_can_access', true, $this->ID, $post_id, $this );
		}

		/*
		 * From this point on we assume the post has some kind of restrictions added.
		 */

		$can_access = false;
		$customer   = $this->get_customer();

		// If there's no customer, there's no membership, and thus they don't have access.
		if ( empty( $customer ) ) {
			return apply_filters( 'rcp_member_can_access', $can_access, $this->ID, $post_id, $this );
		}

		return $customer->can_access( $post_id );

	}

	/**
	 * Gets the URL to switch to the user
	 * if the User Switching plugin is active
	 *
	 * @access public
	 * @since 2.1
	 * @return string|false
	 */
	public function get_switch_to_url() {

		if( ! class_exists( 'user_switching' ) ) {
			return false;
		}

		$link = user_switching::maybe_switch_url( $this );
		if ( $link ) {
			$link = add_query_arg( 'redirect_to', urlencode( home_url() ), $link );
			return $link;
		} else {
			return false;
		}
	}

	/**
	 * Get the prorate credit amount for the user's remaining subscription
	 *
	 * @since 2.5
	 * @return int|float
	 */
	public function get_prorate_credit_amount() {

		$membership = $this->get_membership();

		if ( empty( $membership ) ) {
			return 0;
		}

		return $membership->get_prorate_credit_amount();

	}

	/**
	 * Get details about the member's card on file
	 *
	 * @since 2.5
	 * @return array
	 */
	public function get_card_details() {

		$membership = $this->get_membership();

		if ( ! empty( $membership ) ) {
			$card_details = $membership->get_card_details();
		} else {
			$card_details = apply_filters( 'rcp_get_card_details', array(), $this->ID, $this );
		}

		return $card_details;

	}

	/**
	 * Determines if the customer just upgraded
	 *
	 * @since 2.5
	 * @return int|false - Timestamp reflecting the date/time of the latest upgrade, or false.
	 */
	public function just_upgraded() {

		$upgraded = get_user_meta( $this->ID, '_rcp_just_upgraded', true );

		if( ! empty( $upgraded ) ) {

			$limit = strtotime( '-5 minutes', current_time( 'timestamp' ) );

			if( $limit > $upgraded ) {

				$upgraded = false;

			}

		}

		return apply_filters( 'rcp_member_just_upgraded', $upgraded, $this->ID, $this );
	}

}