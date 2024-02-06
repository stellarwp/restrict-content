<?php
/**
 * Membership Object
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

use RCP\Membership_Level;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Membership class.
 */
#[\AllowDynamicProperties]
class RCP_Membership {

	/**
	 * Membership ID.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * ID of the customer this membership belongs to.
	 *
	 * @var int
	 */
	protected $customer_id = 0;

	/**
	 * ID of the user this membership belongs to.
	 *
	 * @var int|null
	 */
	protected $user_id = null;

	/**
	 * Corresponding customer object.
	 *
	 * @var RCP_Customer
	 */
	protected $customer;

	/**
	 * Member object. Used for backwards compatibility filter support.
	 *
	 * @var RCP_Member
	 */
	protected $member;

	/**
	 * Corresponding object ID (such as a membership level ID).
	 *
	 * @var int
	 */
	protected $object_id = 0;

	/**
	 * Object type.
	 *
	 * @var string
	 */
	protected $object_type = 'membership';

	/**
	 * Currency used for membership payments.
	 *
	 * @var string
	 */
	protected $currency = 'USD';

	/**
	 * Initial payment amount.
	 *
	 * @var int|float
	 */
	protected $initial_amount = 0;

	/**
	 * Recurring amount.
	 *
	 * @var int|float
	 */
	protected $recurring_amount = 0;

	/**
	 * Date the membership was created.
	 *
	 * @var string
	 */
	protected $created_date = '';

	/**
	 * Date the membership was activated
	 *
	 * @var string
	 * @since 3.3.1
	 */
	protected $activated_date = '';

	/**
	 * Last day of the trial. If no trial then this will be blank.
	 *
	 * @var string
	 */
	protected $trial_end_date = '';

	/**
	 * Date the membership was last renewed.
	 *
	 * @var string
	 */
	protected $renewed_date = '';

	/**
	 * Date the membership was cancelled. If it hasn't been cancelled this will be blank.
	 *
	 * @var string
	 */
	protected $cancellation_date = '';

	/**
	 * Date the membership expires or is next due for a renewal. If this is a lifetime membership then this will be
	 * `null`.
	 *
	 * @var string|null
	 */
	protected $expiration_date = null;

	/**
	 * Date the payment plan was completed, or `null` if it hasn't been.
	 *
	 * @var string|null
	 */
	protected $payment_plan_completed_date = null;

	/**
	 * Number of times this membership has been billed for, including the first payment.
	 *
	 * @var int
	 */
	protected $times_billed = 1;

	/**
	 * Maximum number of times to renew this membership. Default is `0` for unlimited.
	 *
	 * @var int
	 */
	protected $maximum_renewals = 0;

	/**
	 * Status of this membership: `active`, `cancelled`, `expired`, or `pending`.
	 *
	 * @var string
	 */
	protected $status = '';

	/**
	 * Whether or not this membership automatically renews.
	 *
	 * @var int
	 */
	protected $auto_renew = 0;

	/**
	 * Customer ID number with the gateway. This is a user profile ID - not a subscription ID. For example, if using
	 * Stripe then this ID begins with "cus_". Not all gateways have this.
	 *
	 * @var string
	 */
	protected $gateway_customer_id = '';

	/**
	 * ID of the subscription with the payment gateway. If using Stripe then this ID begins with "sub_".
	 *
	 * @var string
	 */
	protected $gateway_subscription_id = '';

	/**
	 * Payment gateway used for billing.
	 *
	 * @var string
	 */
	protected $gateway = '';

	/**
	 * Method used to create this membership. Options include: `live` (via the registration form), `manual` (manually
	 * added by a site admin), and `imported`.
	 *
	 * @var string
	 */
	protected $signup_method = '';

	/**
	 * Subscription key.
	 *
	 * @var string
	 */
	protected $subscription_key = '';

	/**
	 * Membership notes.
	 *
	 * @var string
	 */
	protected $notes = '';

	/**
	 * ID of the membership this one upgraded from.
	 *
	 * @var int
	 */
	protected $upgraded_from = 0;

	/**
	 * Date this membership was last modified.
	 *
	 * @var string
	 */
	protected $date_modified = '';

	/**
	 * Whether this membership is disabled (`0` = not disabled; `1` = disabled).
	 *
	 * @var int
	 */
	protected $disabled = 0;

	/**
	 * RCP_Membership constructor.
	 *
	 * @param object $membership_object Object from the database.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function __construct( $membership_object = null ) {

		if ( ! is_object( $membership_object ) ) {
			return;
		}

		$this->setup_membership( $membership_object );

	}

	/**
	 * Setup properties.
	 *
	 * @param object $membership_object
	 *
	 * @access private
	 * @since  3.0
	 * @return bool
	 */
	private function setup_membership( $membership_object ) {

		if ( ! is_object( $membership_object ) ) {
			return false;
		}

		$vars = get_object_vars( $membership_object );

		foreach ( $vars as $key => $value ) {
			switch ( $key ) {
				case 'created_date' :
				case 'activated_date' :
				case 'trial_end_date' :
				case 'renewed_date' :
				case 'cancellation_date' :
				case 'expiration_date' :
				case 'payment_plan_completed_date' :
				case 'date_modified' :
					if ( '0000-00-00 00:00:00' === $value || is_null( $value ) ) {
						$value = null;
					}
					break;
			}

			$this->{$key} = $value;
		}

		if ( empty( $this->id ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Update the membership data in the database.
	 *
	 * @param array $data
	 *
	 * @access public
	 * @since  3.0
	 * @return bool True if update was successful, false on failure.
	 */
	public function update( $data = array() ) {

		// Convert "free" status to "active".
		if ( ! empty( $data['status'] ) && 'free' === $data['status'] ) {
			$data['status'] = 'active';
		}

		// Remove "notes" for our log because it's annoying.
		$log_data = $data;
		if ( ! empty( $log_data['notes'] ) ) {
			unset( $log_data['notes'] );
		}
		if ( ! empty( $log_data ) ) {
			rcp_log( sprintf( 'Updating membership #%d. New data: %s.', $this->get_id(), var_export( $log_data, true ) ) );
		}

		// Expiration date.
		if ( ! empty( $data['expiration_date'] ) && 'none' == $data['expiration_date'] ) {
			$data['expiration_date'] = null;
		}

		$memberships = new \RCP\Database\Queries\Membership();

		$updated = $memberships->update_item( $this->get_id(), $data );

		if ( $updated ) {
			// If setting the status to "active", verify the user role is added.
			if ( ! empty( $data['status'] ) && 'active' === $data['status'] ) {
				$this->add_user_role();
			}

			foreach ( $data as $key => $value ) {
				// Record changes of these columns.
				$columns_to_note = array(
					'expiration_date',
					'auto_renew',
					'status',
					'gateway_customer_id',
					'gateway_subscription_id',
					'gateway',
					'recurring_amount'
				);
				if ( in_array( $key, $columns_to_note ) && $value != $this->{$key} ) {
					$column_name = ucwords( str_replace( '_', ' ', $key ) );

					$this->add_note( sprintf( __( '%s changed from %s to %s.', 'rcp' ), $column_name, $this->{$key}, $value ) );
				}

				switch ( $key ) {
					case 'created_date' :
					case 'trial_end_date' :
					case 'renewed_date' :
					case 'cancellation_date' :
					case 'expiration_date' :
					case 'payment_plan_completed_date' :
						if ( '0000-00-00 00:00:00' === $value ) {
							$value = null;
						}
						break;
				}

				$this->{$key} = $value;
			}

			return true;
		}

		return false;

	}

	/**
	 * Get the ID of the membership.
	 *
	 * @access public
	 * @since  3.0
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the ID of the corresponding customer.
	 *
	 * @access public
	 * @since  3.0
	 * @return int
	 */
	public function get_customer_id() {
		return $this->customer_id;
	}

	/**
	 * Get the RCP_Customer object for this customer.
	 *
	 * @access public
	 * @since  3.0
	 * @return RCP_Customer
	 */
	public function get_customer() {

		if ( ! is_object( $this->customer ) ) {
			$this->customer = rcp_get_customer( $this->get_customer_id() );
		}

		return $this->customer;

	}

	/**
	 * Get the ID of the corresponding user.
	 *
	 * @access public
	 * @since 3.3.4
	 * @return int|null
	 */
	public function get_user_id() {

		if ( ! is_null( $this->user_id ) ) {
			return absint( $this->user_id );
		}

		$customer = $this->get_customer();

		if ( $customer instanceof RCP_Customer ) {
			$user_id = $customer->get_user_id();

			if ( ! empty( $user_id ) ) {
				$this->update( array(
					'user_id' => absint( $user_id )
				) );
			}
		}

		return $this->user_id;

	}

	/**
	 * Get the deprecated RCP_Member object for this customer.
	 *
	 * @access private
	 * @since  3.0
	 * @return RCP_Member|false
	 */
	private function get_member() {

		if ( ! isset( $this->member ) ) {
			$customer = $this->get_customer();

			$this->member = $customer instanceof RCP_Customer ? $customer->get_member() : false;
		}

		return $this->member;

	}

	/**
	 * Get the corresponding object ID for this membership. This will probably be the membership level ID.
	 *
	 * @access public
	 * @since  3.0
	 * @return int|false Corresponding object ID.
	 */
	public function get_object_id() {
		$object_id = $this->object_id;

		if ( has_filter( 'rcp_member_get_subscription_id' ) ) {
			$member  = $this->get_member();
			$user_id = $member instanceof RCP_Member ? $member->ID : 0;

			$object_id = apply_filters( 'rcp_member_get_subscription_id', $object_id, $user_id, $member );
		}

		return $object_id;
	}

	/**
	 * Set the object (membership level) ID.
	 *
	 * @param int $object_id Object ID to set.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function set_object_id( $object_id ) {

		if ( has_action( 'rcp_member_pre_set_subscription_id' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_pre_set_object_id` instead.
			 */
			do_action( 'rcp_member_pre_set_subscription_id', $object_id, $this->get_user_id(), $member );
		}

		$this->update( array( 'object_id' => absint( $object_id ) ) );

		/**
		 * Action "rcp_transition_membership_object_id" will run.
		 *
		 * @see   \RCP\Database\Query::transition_item()
		 *
		 * @param string $old_value     Old object ID value.
		 * @param string $new_value     New object ID value.
		 * @param int    $membership_id ID of the membership.
		 *
		 * @since 3.0
		 */

		if ( has_action( 'rcp_member_post_set_subscription_id' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_transition_membership_object_id` instead.
			 */
			do_action( 'rcp_member_post_set_subscription_id', $object_id, $this->get_user_id(), $member );
		}

	}

	/**
	 * Get object type. This will probably be `membership`.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Get the name of this membership level.
	 *
	 * @access public
	 * @since  3.0
	 * @return string|false Name of the membership level or false if none.
	 */
	public function get_membership_level_name() {

		if ( 'membership' != $this->object_type ) {
			return false;
		}

		$level = rcp_get_membership_level( $this->get_object_id() );

		if ( ! $level instanceof Membership_Level ) {
			return false;
		}

		return $level->get_name();

	}

	/**
	 * Get the currency used for this membership's payments.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_currency() {
		return $this->currency;
	}

	/**
	 * Get the amount charged on initial registration.
	 *
	 * @param bool $formatted Whether or not to format the amount with the currency symbol.
	 *
	 * @access public
	 * @since  3.0
	 * @return string|int|float
	 */
	public function get_initial_amount( $formatted = false ) {

		$initial_amount = $this->initial_amount;

		if ( $formatted ) {
			$initial_amount = rcp_currency_filter( $initial_amount );
		}

		return $initial_amount;

	}

	/**
	 * Get the amount charged on renewals.
	 *
	 * @param bool $formatted Whether or not to format the amount with the currency symbol.
	 *
	 * @access public
	 * @since  3.0
	 * @return string|int|float
	 */
	public function get_recurring_amount( $formatted = false ) {

		$recurring_amount = $this->recurring_amount;

		if ( $formatted ) {
			$recurring_amount = rcp_currency_filter( $recurring_amount );
		}

		return $recurring_amount;

	}

	/**
	 * Retrieves the formatted billing cycle. Will be formatted like so:
	 *        $15 then $20 every 6 months
	 *
	 * @since 3.0
	 * @return string
	 */
	public function get_formatted_billing_cycle() {
		$billing_cycle_string = '';

		$membership_level = rcp_get_membership_level( $this->get_object_id() );

		if ( 0 == $this->get_initial_amount() && 0 == $this->get_recurring_amount() ) {
			$billing_cycle_string = __( 'Free', 'rcp' );

			return apply_filters( 'rcp_membership_formatted_billing_cycle', $billing_cycle_string, $this, $this->get_object_id() );
		}

		$initial_amount   = $this->get_initial_amount( true );
		$recurring_amount = $this->get_recurring_amount( true );

		if ( $membership_level instanceof Membership_Level && $membership_level->is_lifetime() ) {
			$billing_cycle_string = $initial_amount;

			return apply_filters( 'rcp_membership_formatted_billing_cycle', $billing_cycle_string, $this, $this->get_object_id() );
		}

		if ( $membership_level instanceof Membership_Level ) {
			$duration_unit = lcfirst( rcp_filter_duration_unit( $membership_level->get_duration_unit(), $membership_level->get_duration() ) );

			$billing_cycle_string = sprintf(
				_n( '%1$s initially, then %2$s every %4$s', '%s initially, then %s every %d %s', $membership_level->get_duration(), 'rcp' ),
				$initial_amount, $recurring_amount,
				$membership_level->get_duration(),
				$duration_unit
			);
		}

		/**
		 * Filters the billing cycle string.
		 *
		 * @param string         $billing_cycle_string Formatted billing cycle string.
		 * @param RCP_Membership $this                 Membership object.
		 * @param int            $membership_level_id  ID of the membership level.
		 *
		 * @since 3.0
		 */
		return apply_filters( 'rcp_membership_formatted_billing_cycle', $billing_cycle_string, $this, $this->get_object_id() );

	}

	/**
	 * Get the status of the membership
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_status() {

		$status = $this->status;

		if ( has_filter( 'rcp_member_get_status' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_get_status` instead.
			 */
			$status = apply_filters( 'rcp_member_get_status', $status, $this->get_user_id(), $member );
		}

		/**
		 * Filters the membership status.
		 *
		 * @param string         $status        Membership status.
		 * @param int            $membership_id ID of this membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$status = apply_filters( 'rcp_membership_get_status', $status, $this->get_id(), $this );

		return $status;
	}

	/**
	 * Sets the membership status.
	 *
	 * @param string $new_status
	 *
	 * @access public
	 * @since  3.0
	 * @return bool Whether or not the status was updated.
	 */
	public function set_status( $new_status ) {

		$set = false;

		$old_status = $this->get_status();

		if ( has_filter( 'rcp_set_status_value' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_set_membership_status_value` instead.
			 */
			$new_status = apply_filters( 'rcp_set_status_value', $new_status, $this->get_user_id(), $old_status, $member );
		}

		/**
		 * Filters the value of the status being set.
		 *
		 * @param string         $new_status    New status being set.
		 * @param string         $old_status    Old status from before this change.
		 * @param int            $membership_id ID of this membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$new_status = apply_filters( 'rcp_set_membership_status_value', $new_status, $old_status, $this->get_id(), $this );

		if ( ! empty( $new_status ) ) {

			$update_data = array( 'status' => $new_status );

			if ( 'cancelled' == $new_status ) {
				$update_data['cancellation_date'] = current_time( 'mysql' );
			}

			$this->update( $update_data );

			/**
			 * Action "rcp_transition_membership_status" will run.
			 *
			 * @see   \RCP\Database\Query::transition_item()
			 *
			 * @param string $old_status    Old membership status.
			 * @param string $new_status    New membership status.
			 * @param int    $membership_id ID of the membership.
			 *
			 * @since 3.0
			 */

			if ( 'expired' != $new_status ) {
				$user_id = $this->get_user_id();

				if ( ! empty( $user_id ) ) {
					delete_user_meta( $user_id, '_rcp_expired_email_sent' );
				}
			}

			if ( 'cancelled' == $new_status ) {
				$this->set_recurring( false );
			}

			$set = true;
		}

		return $set;

	}

	/**
	 * Get the expiration date.
	 *
	 * @param bool $formatted Whether or not the returned value should be formatted.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_expiration_date( $formatted = true ) {

		$expiration = $this->expiration_date;

		if ( empty( $expiration ) ) {
			$expiration = 'none';
		} elseif ( $formatted ) {
			$expiration = date_i18n( get_option( 'date_format' ), strtotime( $expiration, current_time( 'timestamp' ) ) );
		}

		if ( has_filter( 'rcp_member_get_expiration_date' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_get_expiration_date` instead.
			 */
			$expiration = apply_filters( 'rcp_member_get_expiration_date', $expiration, $this->get_user_id(), $member, $formatted, false );
		}

		/**
		 * Filter the expiration date.
		 *
		 * @param string         $expiration    Membership expiration date.
		 * @param bool           $formatted     Whether or not to format the date.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$expiration = apply_filters( 'rcp_membership_get_expiration_date', $expiration, $formatted, $this->get_id(), $this );

		return $expiration;

	}

	/**
	 * Get the expiration date as a timestamp.
	 *
	 * @access public
	 * @since  3.0
	 * @return int|false
	 */
	public function get_expiration_time() {

		$expiration = $this->get_expiration_date( false );
		$timestamp  = ( $expiration && 'none' != $expiration ) ? strtotime( $expiration, current_time( 'timestamp' ) ) : false;

		if ( has_filter( 'rcp_member_get_expiration_time' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_get_expiration_time` instead.
			 */
			$timestamp = apply_filters( 'rcp_member_get_expiration_time', $timestamp, $this->get_user_id(), $member );
		}

		/**
		 * Filters the expiration time.
		 *
		 * @param int|false      $timestamp     Expiration timestamp.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$timestamp = apply_filters( 'rcp_membership_get_expiration_time', $timestamp, $this->get_id(), $this );

		return $timestamp;

	}

	/**
	 * Sets the expiration date for a member
	 *
	 * Should be passed as a MYSQL date string.
	 *
	 * @param string $new_date New date as a MySQL date string.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool Whether or not the expiration date was updated.
	 */
	public function set_expiration_date( $new_date = '' ) {

		$ret      = false;
		$old_date = $this->get_expiration_date( false );

		// Return early if there's no change in expiration date
		if ( empty( $new_date ) || ( ! empty( $old_date ) && ( $old_date == $new_date ) ) ) {
			return $ret;
		}

		$updated = $this->update( array( 'expiration_date' => $new_date ) );

		/**
		 * Action "rcp_transition_membership_expiration_date" will run.
		 *
		 * @see   \RCP\Database\Query::transition_item()
		 *
		 * @param string $old_date      Old expiration date in MySQL format.
		 * @param string $new_date      New expiration date in MySQL format.
		 * @param int    $membership_id ID of the membership.
		 *
		 * @since 3.0
		 */

		if ( has_action( 'rcp_set_expiration_date' ) ) {
			/**
			 * @deprecated 3.0 Use `rcp_transition_membership_expiration_date` instead.
			 */
			do_action( 'rcp_set_expiration_date', $this->get_user_id(), $new_date, $old_date );
		}

		$ret = $updated;

		return $ret;

	}

	/**
	 * Calculate a new expiration date.
	 *
	 * @param bool $from_today Whether to calculate from today (`true`), or extend the existing expiration date
	 *                         (`false`).
	 * @param bool $trial      Whether or not this is for a free trial.
	 *
	 * @access public
	 * @since  3.0
	 * @return String Date in Y-m-d H:i:s format or "none" if is a lifetime membership.
	 */
	public function calculate_expiration( $from_today = false, $trial = false ) {

		// Get the member's current expiration date
		$expiration = $this->get_expiration_time();

		// Determine what date to use as the start for the new expiration calculation
		if ( ! $from_today && $expiration > current_time( 'timestamp' ) && $this->is_active() ) {

			$base_timestamp = $expiration;

		} else {

			$base_timestamp = current_time( 'timestamp' );

		}

		$membership_level_id = $this->get_object_id();
		$membership_level    = rcp_get_membership_level( $membership_level_id );

		if ( $membership_level instanceof Membership_Level && ! $membership_level->is_lifetime() ) {

			if ( $membership_level->has_trial() && $trial ) {
				$expire_timestamp = strtotime( '+' . $membership_level->get_trial_duration() . ' ' . $membership_level->get_trial_duration_unit() . ' 23:59:59', $base_timestamp );
			} else {
				$expire_timestamp = strtotime( '+' . $membership_level->get_duration() . ' ' . $membership_level->get_duration_unit() . ' 23:59:59', $base_timestamp );
			}

			$extension_days = array( '29', '30', '31' );

			if ( in_array( date( 'j', $expire_timestamp ), $extension_days ) && 'month' === $membership_level->get_duration_unit() ) {

				/*
				 * Here we extend the expiration date by 1-3 days in order to account for "walking" payment dates in PayPal.
				 *
				 * See https://github.com/pippinsplugins/restrict-content-pro/issues/239
				 */

				$month = date( 'n', $expire_timestamp );

				if ( $month < 12 ) {
					$month += 1;
					$year  = date( 'Y', $expire_timestamp );
				} else {
					$month = 1;
					$year  = date( 'Y', $expire_timestamp ) + 1;
				}

				$expire_timestamp = mktime( 0, 0, 0, $month, 1, $year );
			}

			$expiration = date( 'Y-m-d 23:59:59', $expire_timestamp );

		} else {

			$expiration = 'none';

		}

		if ( has_filter( 'rcp_member_calculated_expiration' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_calculated_expiration_date` instead.
			 */
			$expiration = apply_filters( 'rcp_member_calculated_expiration', $expiration, $this->get_user_id(), $member );
		}

		/**
		 * Filters the calculated expiration date.
		 *
		 * @param string         $expiration    Calculated expiration date in MySQL format.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$expiration = apply_filters( 'rcp_membership_calculated_expiration_date', $expiration, $this->get_id(), $this );

		return $expiration;

	}

	/**
	 * Get the date this membership was created.
	 *
	 * @param bool $formatted Whether or not the returned date should be formatted.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_created_date( $formatted = true ) {

		$created_date = $this->created_date;

		if ( $formatted ) {
			$created_date = date_i18n( get_option( 'date_format' ), strtotime( $created_date, current_time( 'timestamp' ) ) );
		}

		return $created_date;
	}

	/**
	 * Get the date this membership was activated
	 *
	 * @access public
	 * @since  3.3.1
	 * @return string
	 */
	public function get_activated_date() {

		// Backfill activated date with created date.
		if ( empty( $this->activated_date ) && $this->is_active() && $this->get_created_date( false ) ) {
			$this->update( array(
				'activated_date' => $this->get_created_date( false )
			) );
		}

		return $this->activated_date;

	}

	/**
	 * Get the last day of the free trial.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_trial_end_date() {
		return $this->trial_end_date;
	}

	/**
	 * Returns true if this membership is in its free trial period. Returns false if the free trial period is over or
	 * if there never was one.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function is_trialing() {
		global $rcp_options;
		$membership_level_id = $this->get_object_id();
		$membership_level    = rcp_get_membership_level( $membership_level_id );
		$trial_duration      = 0;

		if ( $membership_level instanceof Membership_Level ) {
			$trial_duration = $membership_level->get_trial_duration();
		}

		$free_subs_swap = isset($rcp_options['disable_trial_free_subs']) && (bool)$rcp_options['disable_trial_free_subs'];

		// Using the $free_subs_swap option will prevent a trailed user to show/hide membership.
		if( $free_subs_swap ) {
			$is_trialing = false;
		// There never was a free trial.
		} elseif ( empty( $this->trial_end_date ) ) {
			$is_trialing = false;
		} elseif ( strtotime( $this->trial_end_date, current_time( 'timestamp' ) ) > current_time( 'timestamp' ) && $trial_duration > 0 ) {
			// There was a free trial, and it is still ongoing.
			$is_trialing = true;
		} else {
			// There was a free trial but it's over now.
			$is_trialing = false;
		}

		// If the membership isn't active, it's not trialling.
		if ( ! $this->is_active() ) {
			$is_trialing = false;
		}

		if ( has_filter( 'rcp_is_trialing' ) ) {
			/**
			 * @deprecated 3.0 Use `rcp_membership_is_trialing` instead.
			 */
			$is_trialing = apply_filters( 'rcp_is_trialing', $is_trialing, $this->get_user_id() );
		}

		if ( has_filter( 'rcp_member_is_trialing' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_is_trialing` instead.
			 */
			$is_trialing = apply_filters( 'rcp_member_is_trialing', $is_trialing, $this->get_user_id(), $member );
		}

		/**
		 * Filters the trialing status.
		 *
		 * @param bool           $is_trialing   Whether or not this membership is in the trial period.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$is_trialing = apply_filters( 'rcp_membership_is_trialing', $is_trialing, $this->get_id(), $this );

		return $is_trialing;

	}

	/**
	 * Returns the date this membership was last renewed.
	 *
	 * @param bool $formatted Whether or not the returned date should be formatted.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_renewed_date( $formatted = true ) {

		$renewed_date = $this->renewed_date;

		if ( has_filter( 'rcp_get_renewed_date' ) ) {
			$member = $this->get_member();

			/**
			 * Filters the renewal date.
			 *
			 * @param string $renewed_date Date the membership was last renewed.
			 * @param int    $user_id      ID of the user account.
			 * @param int    $object_id    ID of the associated object.
			 * @param        $member       RCP_Member Deprecated member object.
			 */
			$renewed_date = apply_filters( 'rcp_get_renewed_date', $renewed_date, $this->get_user_id(), $this->get_object_id(), $member );
		}

		if ( $formatted && ! empty( $renewed_date ) ) {
			$renewed_date = date_i18n( get_option( 'date_format' ), strtotime( $renewed_date, current_time( 'timestamp' ) ) );
		}

		return $renewed_date;

	}

	/**
	 * Set the membership renewed date.
	 *
	 * @param string $date Date the membership was renewed. Leave blank to use current time.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function set_renewed_date( $date = '' ) {

		if ( empty( $date ) ) {
			$date = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
		}

		$updated = $this->update( array(
			'renewed_date' => $date
		) );

		if ( has_action( 'rcp_set_renewed_date' ) ) {
			$member = $this->get_member();

			do_action( 'rcp_set_renewed_date', $this->get_user_id(), $member );
		}

		return $updated;

	}

	/**
	 * Returns the date this membership was cancelled.
	 *
	 * @param bool $formatted Whether or not the returned date should be formatted.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_cancellation_date( $formatted = true ) {

		$cancellation_date = $this->cancellation_date;

		if ( $formatted && ! empty( $cancellation_date ) ) {
			$cancellation_date = date_i18n( get_option( 'date_format' ), strtotime( $cancellation_date, current_time( 'timestamp' ) ) );
		}

		return $cancellation_date;

	}

	/**
	 * Get the number of times this membership has been billed for.
	 *
	 * @access public
	 * @since  3.0
	 * @return int
	 */
	public function get_times_billed() {
		return (int) $this->times_billed;
	}

	/**
	 * Get the maximum number of renewals.
	 *
	 * @access public
	 * @since  3.0
	 * @return int
	 */
	public function get_maximum_renewals() {
		return $this->maximum_renewals;
	}

	/**
	 * Determines if this membership is active or not. A membership is active if it has the status "active" or if
	 * it's "cancelled" but has not yet reached EOT.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function is_active() {

		if ( $this->is_disabled() ) {

			$is_active = false;

		} else {

			if ( $this->is_expired() ) {
				$is_active = false;
			} else {
				$is_active = in_array( $this->get_status(), array( 'active', 'cancelled' ) );
			}

		}

		if ( has_filter( 'rcp_is_active' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_is_active` instead.
			 */
			$is_active = apply_filters( 'rcp_is_active', $is_active, $this->get_user_id(), $member );
		}

		/**
		 * Filters whether or not the membership is active.
		 *
		 * @param bool           $is_active     Whether or not the membership is active.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$is_active = apply_filters( 'rcp_membership_is_active', $is_active, $this->get_id(), $this );

		return $is_active;

	}

	/**
	 * Determines if this is a paid membership.
	 *
	 * @param bool $include_trial Whether or not to count trial memberships as paid.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function is_paid( $include_trial = true ) {

		// If the membership is trialing, we consider them paid.
		if ( $include_trial && $this->is_trialing() ) {
			return true;
		}

		if ( $this->recurring_amount > 0 || $this->initial_amount > 0 ) {
			return true;
		}

		// As a fallback, check the price of the membership level.
		$membership_level = rcp_get_membership_level( $this->get_object_id() );

		if ( $membership_level instanceof Membership_Level && ! $membership_level->is_free() ) {
			return true;
		}

		return false;

	}

	/**
	 * Determines whether or not this membership has expired.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function is_expired() {

		$is_expired = false;
		$expiration = $this->get_expiration_date( false );

		if ( $expiration && strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $expiration, current_time( 'timestamp' ) ) ) {
			$is_expired = true;
		}

		if ( $expiration == 'none' ) {
			$is_expired = false;
		}

		// If the expiration date is in the past but the status isn't "expired", let's update it now.
		// Note: "pending" memberships are not affected by this. They will stay on "pending".
		if ( $is_expired && ! in_array( $this->get_status(), array( 'expired', 'pending' ) ) ) {
			$this->set_status( 'expired' );
		}

		if ( has_filter( 'rcp_member_is_expired' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_is_expired` instead.
			 */
			$is_expired = apply_filters( 'rcp_member_is_expired', $is_expired, $this->get_user_id(), $member );
		}

		/**
		 * Filters whether or not the membership is expired.
		 *
		 * @param bool           $is_expired    If the membership is expired.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$is_expired = apply_filters( 'rcp_membership_is_expired', $is_expired, $this->get_id(), $this );

		return $is_expired;

	}

	/**
	 * Determines if this membership automatically renews or not.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function is_recurring() {

		$is_recurring = ! empty( $this->auto_renew );

		if ( has_filter( 'rcp_member_is_recurring' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_is_recurring` instead.
			 */
			$is_recurring = apply_filters( 'rcp_member_is_recurring', $is_recurring, $this->get_user_id(), $member );
		}

		/**
		 * Filters the auto renew status.
		 *
		 * @param bool           $is_recurring  Whether or not this membership is recurring.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$is_recurring = apply_filters( 'rcp_membership_is_recurring', $is_recurring, $this->get_id(), $this );

		return $is_recurring;

	}

	/**
	 * Sets whether this membership automatically renews or not.
	 *
	 * @param bool $is_recurring
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function set_recurring( $is_recurring = true ) {

		rcp_log( sprintf( 'Updating recurring status for membership #%d. Customer ID: %d; Previous: %s; New: %s', $this->get_id(), $this->get_customer_id(), var_export( $this->is_recurring(), true ), var_export( $is_recurring, true ) ) );

		$this->update( array( 'auto_renew' => (int) $is_recurring ) );

		/**
		 * Action "rcp_transition_membership_auto_renew" will run.
		 *
		 * @see   \RCP\Database\Query::transition_item()
		 *
		 * @param string $old_value     Old auto renew value.
		 * @param string $new_value     New auto renew value.
		 * @param int    $membership_id ID of the membership.
		 *
		 * @since 3.0
		 */

		if ( has_action( 'rcp_member_set_recurring' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_transition_membership_auto_renew` instead.
			 */
			do_action( 'rcp_member_set_recurring', $is_recurring, $this->get_user_id(), $member );
		}

	}

	/**
	 * Determines whether or not auto renew "toggling" is supported.
	 *
	 * If so, switching off auto renew cancels the subscription, and switching on auto renew automatically
	 * creates a new one.
	 *
	 * This requires that the global auto renew settings are set to "let the customer decide" and the gateway
	 * for this membership must support `off-site-subscription-creation`.
	 *
	 * @access public
	 * @since  3.4
	 * @return bool
	 */
	public function can_toggle_auto_renew() {

		$can_toggle = false;

		if (
			// Auto renew must be set to "let the customer decide".
			'3' == rcp_get_auto_renew_behavior() &&

			// We need a gateway subscription ID.
			$this->get_gateway_customer_id() &&

			// Recurring price must be > $0
			$this->get_recurring_amount() > 0 &&

			// Gateway has registered support for off-site subscription creation.
			$this->get_gateway() &&
			rcp_gateway_supports( $this->get_gateway(), 'off-site-subscription-creation' )
		) {
			$can_toggle = true;
		}

		/**
		 * Filters whether or not a membership is able to toggle auto renew.
		 *
		 * @param bool           $can_toggle Whether or not auto renew can be toggled off/on.
		 * @param RCP_Membership $this       Membership object.
		 *
		 * @since 3.4
		 */
		return apply_filters( 'rcp_membership_can_toggle_auto_renew', $can_toggle, $this );

	}

	/**
	 * Toggles auto renew off
	 *
	 * This cancels the recurring subscription at the payment gateway, but does not change the membership's status.
	 *
	 * @since 3.4
	 * @return true|WP_Error
	 */
	public function toggle_auto_renew_off() {

		if ( ! $this->can_toggle_auto_renew() ) {
			return new WP_Error( 'not_supported', __( 'Toggling auto renew is not supported.', 'rcp' ) );
		}

		// If auto renew isn't even on, then just return true.
		if ( ! $this->is_recurring() ) {
			return true;
		}

		/*
		 * Add some membership meta to help designate auto renew was just toggled off. We use this in the Stripe
		 * webhook to ensure we don't change the membership status to `cancelled` when we cancel the subscription.
		 * This is a little bit hacky and we should consider improving it at a later date.
		 */
		rcp_update_membership_meta( $this->get_id(), 'auto_renew_toggled_off', date( 'Y-m-d H:i:s' ) );

		// Cancel the subscription at the gateway.
		$cancelled = $this->cancel_payment_profile( false );

		if ( is_wp_error( $cancelled ) ) {
			return $cancelled;
		}

		// Adjust membership settings accordingly.
		$this->update( array(
			'auto_renew'              => 0,
			'gateway_subscription_id' => ''
		) );

		// Now we can delete that meta.
		rcp_delete_membership_meta( $this->get_id(), 'auto_renew_toggled_off' );

		return true;

	}

	/**
	 * Toggles auto renew on
	 *
	 * Creates a new subscription at the payment gateway.
	 *
	 * @since 3.4
	 * @return true|WP_Error
	 */
	public function toggle_auto_renew_on() {

		if ( ! $this->can_toggle_auto_renew() ) {
			return new WP_Error( 'not_supported', __( 'Toggling auto renew is not supported.', 'rcp' ) );
		}

		// If we already have a subscription, something is wrong.
		if ( $this->is_recurring() ) {
			return new WP_Error( 'invalid_membership_state', __( 'This membership is already recurring.', 'rcp' ) );
		}

		rcp_log( sprintf( 'Attempting to create gateway subscription for membership #%d. Gateway: %s.', $this->get_id(), $this->get_gateway() ) );

		$gateway_class = rcp_get_gateway_class( $this->get_gateway() );

		if ( ! $gateway_class instanceof RCP_Payment_Gateway || ! method_exists( $gateway_class, 'create_off_site_subscription' ) ) {
			$result = new WP_Error( 'invalid_gateway', __( 'This feature is not supported by the chosen payment method.', 'rcp' ) );
		} else {
			$result = call_user_func( array( $gateway_class, 'create_off_site_subscription' ), $this );
		}

		if ( is_wp_error( $result ) ) {
			rcp_log( sprintf( 'Error setting up gateway subscription. Code: %s; Message: %s', $result->get_error_code(), $result->get_error_message() ) );
		} else {
			rcp_log( sprintf( 'Successfully set up recurring subscription in %s.', $this->get_gateway() ) );
			$this->set_recurring( true );
		}

		return $result;

	}

	/**
	 * Get the payment gateway used for this membership.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_gateway() {

		$gateway = $this->gateway;

		if ( empty( $gateway ) ) {
			// If empty, attempt to guess it.
			$gateway = rcp_get_gateway_slug_from_gateway_ids( array(
				'gateway_customer_id'     => $this->get_gateway_customer_id(),
				'gateway_subscription_id' => $this->get_gateway_subscription_id()
			) );

			if ( ! empty( $gateway ) ) {
				$this->update( array(
					'gateway' => $gateway
				) );
			}
		}

		if ( 'stripe_checkout' === $this->gateway ) {
			$this->update( array(
				'gateway' => 'stripe'
			) );
		}

		return $gateway;

	}

	/**
	 * Get the gateway customer ID. With Stripe this begins with "cus_".
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_gateway_customer_id() {

		$customer_id = $this->gateway_customer_id;

		if ( has_filter( 'rcp_member_get_payment_profile_id' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_get_gateway_customer_id` instead.
			 */
			$customer_id = apply_filters( 'rcp_member_get_payment_profile_id', $customer_id, $this->get_user_id(), $member );
		}

		/**
		 * Filters the gateway customer ID.
		 *
		 * @param string         $customer_id   Gateway customer ID.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$customer_id = apply_filters( 'rcp_membership_get_gateway_customer_id', $customer_id, $this->get_id(), $this );

		return $customer_id;

	}

	/**
	 * Set the gateway customer ID.
	 *
	 * @param string $customer_id New gateway ID to set.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function set_gateway_customer_id( $customer_id = '' ) {

		$customer_id = trim( $customer_id );

		if ( has_action( 'rcp_member_pre_set_profile_payment_id' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_pre_set_gateway_customer_id` instead.
			 */
			do_action( 'rcp_member_pre_set_profile_payment_id', $this->get_user_id(), $customer_id, $member );
		}

		$this->update( array( 'gateway_customer_id' => $customer_id ) );

		/**
		 * Action "rcp_transition_membership_gateway_customer_id" will run.
		 *
		 * @see   \RCP\Database\Query::transition_item()
		 *
		 * @param string $old_value     Old customer ID value.
		 * @param string $new_value     New customer ID value.
		 * @param int    $membership_id ID of the membership.
		 *
		 * @since 3.0
		 */

		if ( has_action( 'rcp_member_post_set_profile_payment_id' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_transition_membership_gateway_customer_id` instead.
			 */
			do_action( 'rcp_member_post_set_profile_payment_id', $this->get_user_id(), $customer_id, $member );
		}

	}

	/**
	 * Get the gateway subscription ID. With Stripe this begins with "sub_".
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_gateway_subscription_id() {

		$subscription_id = $this->gateway_subscription_id;

		if ( has_filter( 'rcp_member_get_merchant_subscription_id' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_get_gateway_subscription_id` instead.
			 */
			$subscription_id = apply_filters( 'rcp_member_get_merchant_subscription_id', $subscription_id, $this->get_user_id(), $member );
		}

		/**
		 * Filters the gateway subscription ID.
		 *
		 * @param string         $subscription_id Gateway subscription ID.
		 * @param int            $membership_id   ID of the membership.
		 * @param RCP_Membership $this            Membership object.
		 *
		 * @since 3.0
		 */
		$subscription_id = apply_filters( 'rcp_membership_get_gateway_subscription_id', $subscription_id, $this->get_id(), $this );

		return $subscription_id;

	}

	/**
	 * Set the gateway subscription ID.
	 *
	 * @param string $subscription_id Subscription ID to set.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function set_gateway_subscription_id( $subscription_id = '' ) {

		$subscription_id = trim( $subscription_id );

		if ( has_action( 'rcp_member_pre_set_merchant_subscription_id' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_pre_set_gateway_subscription_id` instead.
			 */
			do_action( 'rcp_member_pre_set_merchant_subscription_id', $this->get_user_id(), $subscription_id, $member );
		}

		$this->update( array( 'gateway_subscription_id' => $subscription_id ) );

		/**
		 * Action "rcp_transition_membership_gateway_subscription_id" will run.
		 *
		 * @see   \RCP\Database\Query::transition_item()
		 *
		 * @param string $old_value     Old subscription ID value.
		 * @param string $new_value     New subscription ID value.
		 * @param int    $membership_id ID of the membership.
		 *
		 * @since 3.0
		 */

		if ( has_action( 'rcp_member_post_set_merchant_subscription_id' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_transition_membership_gateway_subscription_id` instead.
			 */
			do_action( 'rcp_member_post_set_merchant_subscription_id', $this->get_user_id(), $subscription_id, $member );
		}

	}

	/**
	 * Get the membership's subscription key.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_subscription_key() {

		$subscription_key = $this->subscription_key;

		if ( has_filter( 'rcp_member_get_subscription_key' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0
			 */
			apply_filters( 'rcp_member_get_subscription_key', $subscription_key, $this->get_user_id(), $member );
		}

		return $subscription_key;

	}

	/**
	 * Set the membership subscription key.
	 *
	 * @param string $subscription_key Key to set. Automatically generated if omitted.
	 *
	 * @access public
	 * @since  2.3
	 * @return void
	 */
	public function set_subscription_key( $subscription_key = '' ) {

		if ( empty( $subscription_key ) ) {
			$subscription_key = rcp_generate_subscription_key();
		}

		$subscription_key = trim( $subscription_key );

		if ( has_action( 'rcp_member_pre_set_subscription_key' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0
			 */
			do_action( 'rcp_member_pre_set_subscription_key', $subscription_key, $this->get_user_id(), $member );
		}

		$this->update( array( 'subscription_key', $subscription_key ) );

		if ( has_action( 'rcp_member_post_set_subscription_key' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0
			 */
			do_action( 'rcp_member_post_set_subscription_key', $subscription_key, $this->get_user_id(), $member );
		}

	}

	/**
	 * Determines whether or not this membership was upgraded to another one.
	 *
	 * @access public
	 * @since  3.0.4
	 * @return int|false ID of the membership this one was upgraded to, or false if it wasn't upgraded.
	 */
	public function was_upgraded() {

		$memberships = rcp_get_memberships( array(
			'upgraded_from' => $this->get_id(),
			'number'        => 1,
			'fields'        => 'id',
			'disabled'      => '' // include both enabled and disabled memberships
		) );

		if ( ! empty( $memberships ) ) {
			return reset( $memberships );
		}

		return false;

	}

	/**
	 * Retrieves the membership ID this one was upgraded from.
	 *
	 * @access public
	 * @since  3.0
	 * @return int
	 */
	public function get_upgraded_from() {
		return $this->upgraded_from;
	}

	/**
	 * Determines whether or not this membership was upgraded from another one.
	 *
	 * @uses   RCP_Membership::get_upgraded_from()
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function was_upgrade() {
		$upgraded_from = $this->get_upgraded_from();

		return ! empty( $upgraded_from );
	}

	/**
	 * Activate the membership.
	 *
	 * Use this method instead of set_status() when activating the membership for the first time. Only this method
	 * triggers the welcome email.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function activate() {

		rcp_log( sprintf( 'Activating membership #%d.', $this->get_id() ) );

		/**
		 * Triggers before the membership is activated.
		 *
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_membership_pre_activate', $this->get_id(), $this );

		$this->add_note( __( 'Membership activated.', 'rcp' ) );

		// Set the activation date.
		if ( empty( $this->get_activated_date() ) ) {
			$this->update( array(
				'activated_date' => current_time( 'mysql' )
			) );
		}

		if ( 'active' != $this->get_status() ) {
			$this->set_status( 'active' );
		}

		// Apply user role granted by this membership level.
		$this->add_user_role();

		/**
		 * Triggers after the membership is activated.
		 *
		 * This sends the activation email.
		 *
		 * @see   rcp_email_on_membership_activation()
		 *
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_membership_post_activate', $this->get_id(), $this );

	}

	/**
	 * Add the membership level's assigned user role to the customer's account.
	 *
	 * @access public
	 * @since  3.0.5
	 * @return void
	 */
	private function add_user_role() {

		$old_role         = get_option( 'default_role', 'subscriber' );
		$membership_level = rcp_get_membership_level( $this->get_object_id() );
		$role             = $membership_level instanceof Membership_Level ? $membership_level->get_role() : get_option( 'default_role', 'subscriber' );
		$user_id          = $this->get_user_id();
		$user             = ! empty( $user_id ) ? new WP_User( $user_id ) : false;

		if ( ! $user instanceof WP_User || in_array( $role, $user->roles ) ) {
			return;
		}

		rcp_log( sprintf( 'Removing old role %s, adding new role %s for membership #%d (user ID #%d).', $old_role, $role, $this->get_id(), $user->ID ) );

		// `administrator` role is never removed.
		if ( 'administrator' !== $old_role ) {
			$user->remove_role( $old_role );
		}

		$user->add_role( apply_filters( 'rcp_default_user_level', $role, $membership_level->get_id() ) );

	}

	/**
	 * Determines whether or not the membership can be renewed.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function can_renew() {

		$can_renew = true;

		if ( $this->is_recurring() && $this->is_active() && 'cancelled' != $this->get_status() ) {
			$can_renew = false;
		}

		if ( 'none' == $this->get_expiration_date( false ) && $this->is_active() ) {
			$can_renew = false;
		}

		if ( ! $this->is_paid() ) {
			$can_renew = false;
		}

		// Can't renew a completed payment plan.
		if ( $this->is_payment_plan_complete() ) {
			$can_renew = false;
		}

		// Can't reset if this membership level has been deactivated.
		if ( $can_renew ) {
			$membership_level = rcp_get_membership_level( $this->get_object_id() );

			/**
			 * Filters whether or not deactivated membership levels can be renewed.
			 *
			 * @param bool $can_renew_deactivated
			 *
			 * @since 3.1
			 */
			$can_renew_deactivated = apply_filters( 'rcp_can_renew_deactivated_membership_levels', false );

			if ( ! $membership_level instanceof Membership_Level || ( 'active' != $membership_level->get_status() && ! $can_renew_deactivated ) ) {
				$can_renew = false;
			}
		}

		/**
		 * @deprecated 3.0
		 */
		$can_renew = apply_filters( 'rcp_member_can_renew', $can_renew, $this->get_user_id() );

		/**
		 * Filters whether or not the membership can be renewed.
		 *
		 * @param bool           $can_renew     Whether the membership can be renewed.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		return apply_filters( 'rcp_membership_can_renew', $can_renew, $this->get_id(), $this );

	}

	/**
	 * Renews the membership by updating status and expiration date.
	 *
	 * Does NOT handle payment processing for the renewal. This should be called after receiving a renewal payment.
	 *
	 * @param bool   $recurring  Whether or not the membership is recurring.
	 * @param string $status     Membership status.
	 * @param string $expiration Membership expiration date in MySQL format.
	 *
	 * @access public
	 * @since  3.0
	 * @return true|false Whether or not the renewal was successful.
	 */
	public function renew( $recurring = false, $status = 'active', $expiration = '' ) {

		$membership_level_id = $this->get_object_id();

		rcp_log( sprintf( 'Starting membership renewal for membership #%d. Membership Level ID: %d; Current Expiration Date: %s', $this->id, $membership_level_id, $this->get_expiration_date() ) );

		if ( empty( $membership_level_id ) ) {
			return false;
		}

		/*
		 * If this membership had a trial period that's still in the future, update it to now to help clarify
		 * the trial has in fact ended. This is necessary for early manual renewals.
		 *
		 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/2737
		 */
		if ( $this->get_trial_end_date() && strtotime( $this->get_trial_end_date() ) > time() ) {
			$this->update( array(
				'trial_end_date' => date( 'Y-m-d H:i:s' )
			) );
		}

		// Bail if this has a payment plan and it's completed - prevents renewals from running after the fact.
		if ( $this->has_payment_plan() && $this->at_maximum_renewals() ) {
			return false;
		}

		$membership_level = rcp_get_membership_level( $membership_level_id );

		if ( ! $expiration ) {
			/*
			 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/1259
			 *
			 * Not all gateways currently support extending the expiration date another full billing cycle during
			 * renewals while auto renew is selected.
			 */
			$from_today = rcp_gateway_supports( $this->get_gateway(), 'expiration-extension-on-renewals' ) ? false : $this->is_recurring();

			$expiration = $this->calculate_expiration( $from_today );

			if ( has_filter( 'rcp_member_renewal_expiration' ) ) {
				/**
				 * @deprecated 3.0 Use `rcp_membership_renewal_expiration_date` instead.
				 */
				$expiration = apply_filters( 'rcp_member_renewal_expiration', $expiration, $membership_level, $this->get_user_id() );
			}

			/**
			 * Filters the calculated expiration date to be set after the renewal.
			 *
			 * @param string           $expiration       Calculated expiration date.
			 * @param Membership_Level $membership_level Membership level object.
			 * @param int              $membership_id    ID of the membership.
			 * @param RCP_Membership   $this             Membership object.
			 *
			 * @since 3.0
			 */
			$expiration = apply_filters( 'rcp_membership_renewal_expiration_date', $expiration, $membership_level, $this->get_id(), $this );
		}

		if ( has_action( 'rcp_member_pre_renew' ) ) {
			/**
			 * deprecated 3.0 Use `rcp_membership_pre_renew` instead.
			 */
			do_action( 'rcp_member_pre_renew', $this->get_user_id(), $expiration, $this->get_member() );
		}

		/**
		 * Triggers before the membership renewal.
		 *
		 * @param string         $expiration    New expiration date to be set.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_membership_pre_renew', $expiration, $this->get_id(), $this );

		$this->set_expiration_date( $expiration );

		if ( ! empty( $status ) ) {
			$this->set_status( $status );
		}

		$this->set_recurring( $recurring );

		// Set the renewal date.
		$this->set_renewed_date(); // Current time.

		$this->add_note( __( 'Membership renewed.', 'rcp' ) );

		if ( $this->get_user_id() ) {
			delete_user_meta( $this->get_user_id(), '_rcp_expired_email_sent' );
		}

		if ( has_action( 'rcp_member_post_renew' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_post_renew` instead.
			 */
			do_action( 'rcp_member_post_renew', $this->get_user_id(), $expiration, $member );
		}

		/**
		 * Triggers after the membership renewal.
		 *
		 * @param string         $expiration    New expiration date to be set.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_membership_post_renew', $expiration, $this->get_id(), $this );

		rcp_log( sprintf( 'Completed membership renewal for membership #%d. Membership Level ID: %d; New Expiration Date: %s; New Status: %s', $this->id, $membership_level_id, $expiration, $this->get_status() ) );

		RCP\Logs\add_log( array(
			'object_type' => 'membership',
			'object_id'   => $this->get_id(),
			'user_id'     => $this->get_user_id(),
			'type'        => 'membership_renewed',
			'title'       => sprintf( __( 'Membership renewed with new expiration date: %s', 'rcp' ), $expiration ),
		) );

		return true;

	}

	/**
	 * Determines whether or not an upgrade is possible for this membership.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function upgrade_possible() {

		$upgrade_possible = $this->has_upgrade_path();

		if ( has_filter( 'rcp_can_upgrade_subscription' ) ) {
			/**
			 * @deprecated 3.0
			 */
			$upgrade_possible = (bool) apply_filters( 'rcp_can_upgrade_subscription', $upgrade_possible, $this->get_user_id() );
		}

		return $upgrade_possible;

	}

	/**
	 * Determines whether or not this membership has an upgrade path.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function has_upgrade_path() {

		$has_upgrade_path = (bool) $this->get_upgrade_paths();

		if ( has_filter( 'rcp_has_upgrade_path' ) ) {
			/**
			 * @deprecated 3.0
			 */
			$has_upgrade_path = apply_filters( 'rcp_has_upgrade_path', $has_upgrade_path, $this->get_user_id() );
		}

		return $has_upgrade_path;

	}

	/**
	 * Returns the available upgrade paths for this membership.
	 * This will be all the available membership levels except the one this membership is already on.
	 *
	 * @access public
	 * @since  3.0
	 * @return array
	 */
	public function get_upgrade_paths() {

		$current_membership_level = $this->get_object_id();
		$membership_levels        = rcp_get_membership_levels( array(
			'status' => 'active'
		) );

		// Remove the user's current subscription from the list.
		foreach ( $membership_levels as $key => $membership_level ) {
			if ( $current_membership_level == $membership_level->get_id() ) {
				unset( $membership_levels[ $key ] );
			}
		}

		$membership_levels = array_values( $membership_levels );

		if ( has_filter( 'rcp_get_upgrade_paths' ) ) {
			/**
			 * @deprecated 3.0
			 */
			$membership_levels = apply_filters( 'rcp_get_upgrade_paths', $membership_levels, $this->get_user_id() );
		}

		/**
		 * Filters the available upgrade paths.
		 *
		 * @param array          $membership_levels Array of membership level IDs.
		 * @param int            $membership_id     ID of the membership.
		 * @param RCP_Membership $this              Membership object.
		 */
		return apply_filters( 'rcp_get_membership_upgrade_paths', $membership_levels, $this->get_id(), $this );

	}

	/**
	 * Determines if this membership has a payment plan.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function has_payment_plan() {
		return $this->get_maximum_renewals() > 0;
	}

	/**
	 * Determines whether the maximum number of renewals has been reached.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function at_maximum_renewals() {

		if ( ! $this->has_payment_plan() ) {
			return false;
		}

		$times_billed = $this->get_times_billed() - 1; // Subtract 1 to exclude initial payment.
		$renew_times  = $this->get_maximum_renewals();

		return $times_billed >= $renew_times;

	}

	/**
	 * Returns the date the payment plan was completed.
	 *
	 * @access public
	 * @since  3.0
	 * @return null|string
	 */
	public function get_payment_plan_completed_date() {
		return $this->payment_plan_completed_date;
	}

	/**
	 * Set the date the payment plan was completed.
	 *
	 * @param string $date Date to set. Leave blank for current date/time.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function set_payment_plan_completed_date( $date = '' ) {

		if ( empty( $date ) ) {
			$date = current_time( 'mysql' );
		}

		$this->update( array(
			'payment_plan_completed_date' => $date
		) );

	}

	/**
	 * Determines whether or not the payment plan completion routine has run.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function is_payment_plan_complete() {

		$completed_date = $this->get_payment_plan_completed_date();

		return ! empty( $completed_date );

	}

	/**
	 * Completes a payment plan by processing "after final payment" actions.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool Whether or not the completion was successful.
	 */
	public function complete_payment_plan() {

		// No payment plan or not ready to complete.
		if ( ! $this->has_payment_plan() || ! $this->at_maximum_renewals() ) {
			return false;
		}

		// Completion routine has already run.
		if ( $this->is_payment_plan_complete() ) {
			return false;
		}

		$membership_level  = rcp_get_membership_level( $this->get_object_id() );
		$after_plan_action = $membership_level instanceof Membership_Level ? $membership_level->get_after_final_payment() : '';
		$level_name        = $membership_level instanceof Membership_Level ? $membership_level->get_name() : '';

		rcp_log( sprintf( 'Membership #%d payment plan is complete. Cancelling payment profile at gateway.', $this->get_id() ) );

		/*
		 * Cancel subscription at payment gateway.
		 */
		if ( $this->can_cancel() ) {
			$this->cancel_payment_profile( false );
		}

		/*
		 * Perform completion actions.
		 */
		if ( 'expire_immediately' == $after_plan_action ) {

			// Expire now.
			rcp_log( sprintf( 'Membership #%d expiring new.', $this->get_id() ) );
			$this->expire();

			$this->add_note( sprintf( __( '%s payment plan completed. Membership expiring immediately.', 'rcp' ), $level_name ) );

		} elseif ( 'lifetime' == $after_plan_action ) {

			// Grant lifetime access.
			rcp_log( sprintf( 'Granting lifetime access for membership #%d.', $this->get_id() ) );
			$this->set_recurring( false );
			$this->set_expiration_date( 'none' );

			$this->add_note( sprintf( __( '%s payment plan completed. Granted lifetime membership access.', 'rcp' ), $level_name ) );

		} elseif ( 'expire_term_end' == $after_plan_action ) {

			// Expire at end of term (so, cancel now).
			rcp_log( sprintf( 'Cancelling membership #%d at term end.', $this->get_id() ) );
			$this->set_status( 'cancelled' );

			$this->add_note( sprintf( __( '%s payment plan completed. Membership will expire at end of current term.', 'rcp' ), $level_name ) );

		}

		/*
		 * Set completion date.
		 */
		$this->set_payment_plan_completed_date();

		return true;

	}

	/**
	 * Determines whether or not this membership has been disabled.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function is_disabled() {
		return ! empty( $this->disabled );
	}

	/**
	 * Disable the membership. This does the following:
	 *
	 *        - Cancels the payment profile to stop recurring billing.
	 *        - Disables the membership so the customer loses access to associated content.
	 *        - Removes the associated role from the user account.
	 *        - Hides the membership from the customer so it can no longer be renewed or cancelled.
	 *        - This is basically a way of deleting the membership while still keeping it in the database.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function disable() {

		rcp_log( sprintf( 'Disabling membership #%d.', $this->get_id() ) );

		/**
		 * Add a note.
		 */
		$this->add_note( __( 'Membership disabled.', 'rcp' ) );

		/**
		 * Disable the membership so the customer loses access.
		 */
		$this->update( array( 'disabled' => 1 ) );

		/**
		 * Cancel recurring payments.
		 */
		if ( $this->can_cancel() ) {
			$this->cancel_payment_profile( true );
		}

		/**
		 * Remove associated user role.
		 *
		 * Note: `administrator` role is never removed.
		 */
		$old_role         = get_option( 'default_role', 'subscriber' );
		$membership_level = rcp_get_membership_level( $this->get_object_id() );
		$old_role         = $membership_level instanceof Membership_Level ? $membership_level->get_role() : $old_role;
		$user_id          = $this->get_user_id();
		$user             = ! empty( $user_id ) ? new WP_User( $user_id ) : false;
		if ( $user instanceof WP_User && 'administrator' !== $old_role ) {
			$user->remove_role( $old_role );
		}

		/**
		 * Runs after the membership has been disabled.
		 *
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_membership_post_disable', $this->get_id(), $this );

	}

	/**
	 * Enable a membership. This does the following:
	 *
	 *        - The membership is re-granted access to associated content (provided membership is still active).
	 *        - The customer is able to view this membership again and renew if desired.
	 *        - The user role is reapplied to the account (provided membership is still active).
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function enable() {

		$this->update( array( 'disabled' => 0 ) );

		/**
		 * Add associated user role.
		 */
		if ( $this->is_active() ) {
			$this->add_user_role();
		}

	}

	/**
	 * Changes the membership status to "cancelled".
	 *
	 * Does NOT handle actual cancellation of subscription payments, that is done in rcp_process_member_cancellation().
	 * This should be called after a member is successfully cancelled.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function cancel() {

		if ( 'cancelled' === $this->get_status() ) {
			return; // Bail if already set to cancelled
		}

		if ( has_action( 'rcp_member_pre_cancel' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_pre_cancel` instead.
			 */
			do_action( 'rcp_member_pre_cancel', $this->get_user_id(), $member );
		}

		/**
		 * Triggers before the membership is cancelled.
		 *
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_membership_pre_cancel', $this->get_id(), $this );

		// Change status.
		$this->set_status( 'cancelled' );

		if ( has_action( 'rcp_member_post_cancel' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_post_cancel` instead.
			 */
			do_action( 'rcp_member_post_cancel', $this->get_user_id(), $member );
		}

		/**
		 * Triggers after the membership is cancelled.
		 *
		 * This triggers the cancellation email.
		 *
		 * @see   rcp_email_on_membership_cancellation()
		 *
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_membership_post_cancel', $this->get_id(), $this );

		RCP\Logs\add_log( array(
			'object_type' => 'membership',
			'object_id'   => $this->get_id(),
			'user_id'     => $this->get_user_id(),
			'type'        => 'membership_cancelled',
			'title'       => __( 'Membership cancelled', 'rcp' ),
		) );

	}

	/**
	 * Determines if the automatically recurring membership can be cancelled on site.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool True if the membership can be cancelled, false if not.
	 */
	public function can_cancel() {

		$can_cancel = false;
		$paypal_gateways = [
			'paypal',
			'paypal_express',
			'paypal_pro',
		];

		if ( $this->is_recurring() && 'active' == $this->get_status() && $this->is_paid() && ! $this->is_expired() ) {

			// Check if the membership is a Stripe customer
			if ( 'stripe' == $this->get_gateway() || 'stripe_checkout' == $this->get_gateway() ) {

				$can_cancel = true;

			} elseif ( in_array( $this->get_gateway(),$paypal_gateways ) ) {

				if ( rcp_is_paypal_membership( $this ) && rcp_has_paypal_api_access() ) {
					$can_cancel = true;
				}

			} elseif ( 'twocheckout' == $this->get_gateway() && defined( 'TWOCHECKOUT_ADMIN_USER' ) && defined( 'TWOCHECKOUT_ADMIN_PASSWORD' ) ) {

				$can_cancel = true;

			} elseif ( 'braintree' == $this->get_gateway() && rcp_has_braintree_api_access() ) {

				$can_cancel = true;

			}

		}

		if ( has_filter( 'rcp_member_can_cancel' ) ) {
			/**
			 * @deprecated 3.0 Use `rcp_membership_can_cancel` instead.
			 */
			$can_cancel = apply_filters( 'rcp_member_can_cancel', $can_cancel, $this->get_user_id() );
		}

		/**
		 * Filters whether or not the membership can be cancelled.
		 *
		 * @param bool           $can_cancel    Whether or not this membership can be cancelled.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$can_cancel = apply_filters( 'rcp_membership_can_cancel', $can_cancel, $this->get_id(), $this );

		if ( $can_cancel ) {
			rcp_log( sprintf( '"Can cancel" status for membership #%d: %s.', $this->get_id(), var_export( $can_cancel, true ) ) );
		} else {
			// If cancellation is not available, let's try to find out why.
			if ( ! $this->is_recurring() ) {
				$reason = 'membership not recurring';
			} elseif ( 'active' !== $this->get_status() ) {
				$reason = 'membership not active';
			} elseif ( ! $this->is_paid() ) {
				$reason = 'membership not paid';
			} else {
				$reason = 'unknown';
			}

			rcp_log( sprintf( '"Can cancel" status for membership #%d: %s. Reason: %s.', $this->get_id(), var_export( $can_cancel, true ), $reason ) );
		}

		return $can_cancel;

	}

	/**
	 * Cancel the payment profile at the gateway.
	 *
	 * @param bool $set_status Whether or not to update the membership status to "cancelled".
	 *
	 * @access public
	 * @since  3.0
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function cancel_payment_profile( $set_status = true ) {

		$success                 = new WP_Error;
		$gateway_subscription_id = $this->get_gateway_subscription_id();

		if ( ! $this->can_cancel() ) {
			rcp_log( sprintf( 'Unable to cancel payment profile for membership #%d.', $this->get_id() ) );

			return new WP_Error( 'ineligible_to_cancel', __( 'Membership is not eligible for payment profile cancellation.', 'rcp' ) );
		}

		if ( 'stripe' == $this->get_gateway() || 'stripe_checkout' == $this->get_gateway() ) {

			/**
			 * Cancel Stripe.
			 */

			if ( ! empty( $gateway_subscription_id ) ) {
				$cancelled = rcp_stripe_cancel_membership( $gateway_subscription_id );

				if ( is_wp_error( $cancelled ) ) {
					rcp_log( sprintf( 'Failed to cancel Stripe payment profile for membership #%d. Error code: %s; Error Message: %s.', $this->get_id(), $cancelled->get_error_code(), $cancelled->get_error_message() ) );

					$success = $cancelled;
				} else {
					$success = true;
				}
			} else {
				rcp_log( sprintf( 'Failed to cancel Stripe payment profile for membership #%d. Missing payment profile ID.', $this->get_id() ) );

				$success = new WP_Error( 'missing_payment_profile_id', __( 'Missing subscription ID.', 'rcp' ) );
			}

		} elseif ( false !== strpos( $this->get_gateway(), 'paypal' ) ) {

			/**
			 * Cancel PayPal.
			 */

			$cancelled = rcp_paypal_cancel_membership( $gateway_subscription_id );

			if ( is_wp_error( $cancelled ) ) {

				rcp_log( sprintf( 'Failed to cancel PayPal payment profile for membership #%d. Error code: %s; Error Message: %s.', $this->get_id(), $cancelled->get_error_code(), $cancelled->get_error_message() ) );

				$success = $cancelled;

			} else {
				$success = true;
			}

		} elseif ( 'twocheckout' == $this->get_gateway() ) {

			/**
			 * Cancel 2Checkout
			 */

			$cancelled = rcp_2checkout_cancel_membership( $gateway_subscription_id );

			if ( is_wp_error( $cancelled ) ) {

				rcp_log( sprintf( 'Failed to cancel 2Checkout payment profile for membership #%d. Error code: %s; Error Message: %s.', $this->get_id(), $cancelled->get_error_code(), $cancelled->get_error_message() ) );

				$success = $cancelled;

			} else {
				$success = true;
			}

		} elseif ( 'braintree' == $this->get_gateway() ) {

			/**
			 * Cancel Braintree
			 */

			$cancelled = rcp_braintree_cancel_membership( $gateway_subscription_id );

			if ( is_wp_error( $cancelled ) ) {

				rcp_log( sprintf( 'Failed to cancel Braintree payment profile for membership #%d. Error code: %s; Error Message: %s.', $this->get_id(), $cancelled->get_error_code(), $cancelled->get_error_message() ) );

				$success = $cancelled;

			} else {
				$success = true;
			}

		}

		/**
		 * Filters whether or not the cancellation was successful. If developing a third party gateway
		 * you'd use this filter to process cancellations, then return `true` on success or `WP_Error`
		 * on failure.
		 *
		 * @param true|WP_Error  $success                 Whether or not the cancellation was successful.
		 * @param string         $gateway                 Payment gateway for this membership.
		 * @param string         $gateway_subscription_id Gateway subscription ID.
		 * @param int            $membership_id           ID of the membership.
		 * @param RCP_Membership $this                    Membership object.
		 *
		 * @since 3.0
		 */
		$success = apply_filters( 'rcp_membership_payment_profile_cancelled', $success, $this->get_gateway(), $gateway_subscription_id, $this->get_id(), $this );

		if ( true === $success && $set_status ) {
			$this->cancel();
		}

		if ( true === $success ) {
			rcp_log( sprintf( 'Payment profile successfully cancelled for membership #%d.', $this->get_id() ) );
		} elseif ( is_wp_error( $success ) ) {
			$this->add_note( sprintf( __( 'Failed cancelling payment profile. Error code: %s; Error Message: %s.', 'rcp' ), $success->get_error_code(), $success->get_error_message() ) );
			rcp_log( sprintf( 'Failed cancelling payment profile for membership #%d.', $this->get_id() ) );
		}

		return $success;

	}

	/**
	 * Expire the membership. This sets the expiration date to yesterday.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function expire() {

		rcp_log( sprintf( 'Manually expiring membership #%d.', $this->get_id() ) );

		$this->set_status( 'expired' );
		$this->set_expiration_date( date( 'Y-m-d H:i:s', strtotime( '-1 day', current_time( 'timestamp' ) ) ) );

	}

	/**
	 * Get all the membership notes.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_notes() {
		return $this->notes;
	}

	/**
	 * Add a new membership note.
	 *
	 * @param string $note New note to add.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool Whether or not the note was successfully added.
	 */
	public function add_note( $note = '' ) {

		$notes = $this->get_notes();

		if ( empty( $notes ) ) {
			$notes = '';
		} else {
			$notes .= "\n\n";
		}

		$notes .= date_i18n( 'F j, Y H:i:s', current_time( 'timestamp' ) ) . ' - ' . $note;

		$this->update( array( 'notes' => $notes ) );

		/**
		 * Triggers after the new note is added
		 *
		 * @param string 		 $note 			New note that was just added.
		 * @param int 			 $membership_id ID of the membership
		 * @param RCP_Membership $this 			RCP_Membership Object
		 */
		do_action( 'rcp_membership_add_note', $note, $this->get_id(), $this );

		return true;

	}

	/**
	 * Get the signup method for this membership. Options include: live, manual, imported.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_signup_method() {
		return $this->signup_method;
	}

	/**
	 * Check to see if this membership has access to view a certain post.
	 *
	 * @param int $post_id Post ID to check access for.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function can_access( $post_id = 0 ) {

		// If the post is unrestricted, everyone gets access.
		if ( ! rcp_is_restricted_content( $post_id ) ) {
			return apply_filters( 'rcp_membership_can_access', true, $this->get_id(), $post_id, $this );
		}

		// If the membership isn't active then they don't get access.
		if ( $this->is_expired() || ! $this->is_active() ) {
			return apply_filters( 'rcp_membership_can_access', false, $this->get_id(), $post_id, $this );
		}

		$post_type_restrictions = rcp_get_post_type_restrictions( get_post_type( $post_id ) );
		$membership_level_id    = $this->get_object_id();

		// Post or post type restrictions.
		if ( empty( $post_type_restrictions ) ) {
			$membership_levels = rcp_get_content_subscription_levels( $post_id );
			$access_level      = get_post_meta( $post_id, 'rcp_access_level', true );
			$user_level        = get_post_meta( $post_id, 'rcp_user_level', true );
		} else {
			$membership_levels = array_key_exists( 'subscription_level', $post_type_restrictions ) ? $post_type_restrictions['subscription_level'] : false;
			$access_level      = array_key_exists( 'access_level', $post_type_restrictions ) ? $post_type_restrictions['access_level'] : false;
			$user_level        = array_key_exists( 'user_level', $post_type_restrictions ) ? $post_type_restrictions['user_level'] : false;
		}

		// Check that user level is an array for backwards compatibility.
		if ( ! empty( $user_level ) && ! is_array( $user_level ) ) {
			$user_level = array( $user_level );
		}

		// Assume it access until proven otherwise.
		$can_access = true;

		// Check membership level restrictions.
		if ( ! empty( $membership_levels ) ) {

			if ( is_string( $membership_levels ) ) {

				switch ( $membership_levels ) {

					case 'any' :
						$can_access = ! empty( $membership_level_id );
						break;

					case 'any-paid' :
						$can_access = $this->is_paid();
						break;
				}

			} else {

				$can_access = in_array( $membership_level_id, $membership_levels );

			}
		}

		// Check post access level restrictions.
		if ( ! $this->has_access_level( $access_level ) && $access_level > 0 ) {
			$can_access = false;
		}

		// Check post user role restrictions. User needs at least one of the selected roles.
		if ( $can_access && ! empty( $user_level ) && 'all' != strtolower( $user_level[0] ) ) {
			$user_id  = $this->get_user_id();

			if ( ! empty( $user_id ) ) {
				foreach ( $user_level as $role ) {
					if ( user_can( $user_id, strtolower( $role ) ) ) {
						$can_access = true;
						break;
					} else {
						$can_access = false;
					}
				}
			}
		}

		// Check term restrictions.
		$has_post_restrictions = rcp_has_post_restrictions( $post_id );

		// since no post-level restrictions, check to see if user is restricted via term
		if ( $can_access && ! $has_post_restrictions && rcp_has_term_restrictions( $post_id ) ) {

			$restricted = false;

			$terms = (array) rcp_get_connected_term_ids( $post_id );

			if ( ! empty( $terms ) ) {

				foreach ( $terms as $term_id ) {

					$restrictions = rcp_get_term_restrictions( $term_id );

					if ( empty( $restrictions['paid_only'] ) && empty( $restrictions['subscriptions'] ) && ( empty( $restrictions['access_level'] ) || 'None' == $restrictions['access_level'] ) ) {
						if ( count( $terms ) === 1 ) {
							break;
						}
						continue;
					}

					// If only the Paid Only box is checked, check for active, paid subscription and return early if so.
					if ( ! $restricted && ! empty( $restrictions['paid_only'] ) && empty( $restrictions['subscriptions'] ) && empty( $restrictions['access_level'] ) && ( ! $this->is_active() || ! $this->is_paid() ) ) {
						$restricted = true;
						break;
					}

					if ( ! $restricted && ! empty( $restrictions['subscriptions'] ) && ! in_array( $this->get_object_id(), $restrictions['subscriptions'] ) ) {
						$restricted = true;
						break;
					}

					if ( ! $restricted && ! empty( $restrictions['access_level'] ) && 'None' !== $restrictions['access_level'] ) {
						if ( $restrictions['access_level'] > 0 && ! $this->has_access_level( $restrictions['access_level'] ) ) {
							$restricted = true;
							break;
						}
					}
				}
			}

			if ( $restricted ) {
				$can_access = false;
			}

			// since user doesn't pass post-level restrictions, see if user is allowed via term
		} else if ( ! $can_access && $has_post_restrictions && rcp_has_term_restrictions( $post_id ) ) {

			$allowed = false;

			$terms = (array) rcp_get_connected_term_ids( $post_id );

			if ( ! empty( $terms ) ) {

				foreach ( $terms as $term_id ) {

					$restrictions = rcp_get_term_restrictions( $term_id );

					if ( empty( $restrictions['paid_only'] ) && empty( $restrictions['subscriptions'] ) && ( empty( $restrictions['access_level'] ) || 'None' == $restrictions['access_level'] ) ) {
						if ( count( $terms ) === 1 ) {
							break;
						}
						continue;
					}

					// If only the Paid Only box is checked, check for paid, active subscription and return early if so.
					if ( ! $allowed && ! empty( $restrictions['paid_only'] ) && empty( $restrictions['subscriptions'] ) && empty( $restrictions['access_level'] ) && $this->is_active() && $this->is_paid() ) {
						$allowed = true;
						break;
					}

					if ( ! $allowed && ! empty( $restrictions['subscriptions'] ) && in_array( $this->get_object_id(), $restrictions['subscriptions'] ) ) {
						$allowed = true;
						break;
					}

					if ( ! $allowed && ! empty( $restrictions['access_level'] ) && 'None' !== $restrictions['access_level'] ) {
						if ( $restrictions['access_level'] > 0 && $this->has_access_level( $restrictions['access_level'] ) ) {
							$allowed = true;
							break;
						}
					}
				}
			}

			if ( $allowed ) {
				$can_access = true;
			}
		}

		/**
		 * Filters whether or not this membership has access to the given post.
		 *
		 * @param bool           $can_access    True if it has access, false if not.
		 * @param int            $membership_id ID of the membership.
		 * @param int            $post_id       ID of the post to check.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		return apply_filters( 'rcp_membership_can_access', $can_access, $this->get_id(), $post_id, $this );

	}

	/**
	 * Determines if this membership has a specific access level or higher.
	 *
	 * @param int $access_level_needed Level to check.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function has_access_level( $access_level_needed = 0 ) {

		$membership_level = rcp_get_membership_level( $this->get_object_id() );

		if ( ! $membership_level instanceof Membership_Level ) {
			return false;
		}

		if ( ( $membership_level->get_access_level() >= $access_level_needed ) || $access_level_needed == 0 ) {
			// The membership has the access level or higher.
			return true;
		}

		// The membership does not have access level.
		return false;

	}

	/**
	 * Get the prorate credit amount for the customer's remaining membership.
	 *
	 * @access public
	 * @since  3.0
	 * @return int|float
	 */
	public function get_prorate_credit_amount() {

		// Make sure this is an active, paid membership.
		if ( ! $this->is_active() || ! $this->is_paid() || $this->is_trialing() ) {
			return apply_filters( 'rcp_membership_get_prorate_credit', 0, $this->get_id(), $this );
		}

		/**
		 * @deprecated 3.0 Use `rcp_membership_disable_prorate_credit` instead.
		 */
		if ( apply_filters( 'rcp_disable_prorate_credit', false, $this->get_member() ) ) {
			return 0;
		}

		/**
		 * Set to `true` to disable prorated credits.
		 *
		 * @param bool           $disable_credit
		 * @param RCP_Membership $this
		 *
		 * @since 3.0
		 */
		if ( apply_filters( 'rcp_membership_disable_prorate_credit', false, $this ) ) {
			return 0;
		}

		// Get the most recent payment.
		foreach ( $this->get_payments() as $pmt ) {
			if ( 'complete' != $pmt->status ) {
				continue;
			}

			$payment = $pmt;
			break;
		}

		if ( empty( $payment ) ) {
			return apply_filters( 'rcp_membership_get_prorate_credit', 0, $this->get_id(), $this );
		}

		if ( ! empty( $payment->object_id ) ) {
			$membership_level_id = absint( $payment->object_id );
			$membership_level    = rcp_get_membership_level( $membership_level_id );
		} else {
			$membership_level_id = rcp_get_membership_level_by( 'name', $payment->subscription );
			$membership_level    = $this->get_object_id();
		}

		// Make sure the membership payment matches the existing membership.
		if ( ! $membership_level instanceof Membership_Level || $membership_level->is_lifetime() || $membership_level->get_id() != $membership_level_id ) {
			return apply_filters( 'rcp_membership_get_prorate_credit', 0, $this->get_id(), $this );
		}

		$exp_date = $this->get_expiration_date( false );

		// If this is member does not have an expiration date, they don't get any credits.
		if ( 'none' == $exp_date ) {
			return apply_filters( 'rcp_membership_get_prorate_credit', 0, $this->get_id(), $this );
		}

		// Make sure we have a valid date.
		if ( ! $exp_date = strtotime( $exp_date ) ) {
			return apply_filters( 'rcp_membership_get_prorate_credit', 0, $this->get_id(), $this );
		}

		$exp_date_dt = date( 'Y-m-d', $exp_date ) . ' 23:59:59';
		$exp_date    = strtotime( $exp_date_dt, current_time( 'timestamp' ) );

		$time_remaining = $exp_date - current_time( 'timestamp' );

		// Calculate the start date based on the expiration date.
		if ( ! $start_date = strtotime( $exp_date_dt . ' -' . $membership_level->get_duration() . $membership_level->get_duration_unit(), current_time( 'timestamp' ) ) ) {
			return apply_filters( 'rcp_membership_get_prorate_credit', 0, $this->get_id(), $this );
		}

		$total_time = $exp_date - $start_date;

		if ( $time_remaining <= 0 ) {
			return apply_filters( 'rcp_membership_get_prorate_credit', 0, $this->get_id(), $this );
		}

		/*
		 * Calculate discount as percentage of membership remaining.
		 * Use the subtotal from their last payment as the base price. This is the amount without discounts/credits/fees applied.
		 * This was only added in version 2.9, so we use the full amount as a fallback in case the subtotal doesn't exist for the last payment.
		 */
		$payment_amount       = ! empty( $payment->subtotal ) ? abs( $payment->subtotal ) : abs( $payment->amount -= $membership_level->get_fee() );
		$percentage_remaining = $time_remaining / $total_time;

		// make sure we don't credit more than 100%
		if ( $percentage_remaining > 1 ) {
			$percentage_remaining = 1;
		}

		$discount = round( $payment_amount * $percentage_remaining, 2 );

		// Make sure they get a discount. This shouldn't ever run.
		if ( ! $discount > 0 ) {
			$discount = $payment_amount;
		}

		$discount = floatval( $discount );

		if ( has_filter( 'rcp_member_prorate_credit' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_get_prorate_credit` instead.
			 */
			$discount = apply_filters( 'rcp_member_prorate_credit', $discount, $this->get_user_id(), $member );
		}

		/**
		 * Filters the prorate credit amount.
		 *
		 * @param float          $discount      Discount amount.
		 * @param int            $membership_id Membership ID number.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$discount = apply_filters( 'rcp_membership_get_prorate_credit', $discount, $this->get_id(), $this );

		return $discount;

	}

	/**
	 * Get the payments associated with this membership.
	 *
	 * @param array $args Query arguments to override the defaults.
	 *
	 * @access public
	 * @since  3.0
	 * @return array
	 */
	public function get_payments( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'membership_id' => $this->get_id()
		) );

		/**
		 * @var RCP_Payments $rcp_payments_db
		 */
		global $rcp_payments_db;

		$payments = $rcp_payments_db->get_payments( $args );

		return $payments;

	}

	/**
	 * Returns the card details associated with this membership.
	 *
	 * @access public
	 * @since  3.0
	 * @return array
	 */
	public function get_card_details() {

		$card_details = array();

		if ( has_filter( 'rcp_get_card_details' ) ) {
			$member = $this->get_member();

			/**
			 * @deprecated 3.0 Use `rcp_membership_get_card_details` instead.
			 */
			$card_details = apply_filters( 'rcp_get_card_details', $card_details, $this->get_user_id(), $member );
		}

		/**
		 * Filters the card details on file. Each payment gateway hooks into this to retrieve the details from the
		 * payment gateway API.
		 *
		 * @param array          $card_details  Array of card details.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.0
		 */
		$card_details = apply_filters( 'rcp_membership_get_card_details', $card_details, $this->get_id(), $this );

		return $card_details;

	}

	/**
	 * Determines whether or not the billing card associated with this membership can be updated.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function can_update_billing_card() {

		$can_update = rcp_gateway_supports( $this->get_gateway(), 'card-updates' );

		if ( has_filter( 'rcp_member_can_update_billing_card' ) ) {
			/**
			 * @deprecated 3.0
			 */
			$can_update = apply_filters( 'rcp_member_can_update_billing_card', $can_update, $this->get_user_id() );
		}

		/**
		 * Filters whether or not the card details can be updated for this membership.
		 *
		 * @param bool           $can_update    Whether or not the billing card can be updated.
		 * @param int            $membership_id ID of the membership.
		 * @param RCP_Membership $this          Membership object.
		 *
		 * @since 3.1
		 */
		$can_update = apply_filters( 'rcp_membership_can_update_billing_card', $can_update, $this->get_id(), $this );

		return $can_update;

	}

	/**
	 * Increment the number of times this membership has been billed for.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function increment_times_billed() {

		$times_billed = $this->get_times_billed();
		$times_billed++;

		$this->update( array(
			'times_billed' => absint( $times_billed )
		) );

		/*
		 * Complete payment plan.
		 */
		if ( $this->has_payment_plan() && $this->at_maximum_renewals() && ! $this->is_payment_plan_complete() ) {
			$this->complete_payment_plan();
		}

	}

	/**
	 * Determines whether the recurring subscription price can be changed at the payment gateway.
	 *
	 * @access public
	 * @since 3.5
	 * @return bool
	 */
	public function can_change_gateway_price() {

		$can_change = rcp_gateway_supports( $this->get_gateway(), 'price-changes' );

		// We can't change it if there's no subscription at the payment gateway.
		if ( $can_change && ( ! $this->is_recurring() || ! $this->get_gateway_subscription_id() || ! $this->is_active() ) ) {
			$can_change = false;
		}

		return $can_change;

	}

	/**
	 * Changes the price of the subscription at the payment gateway.
	 *
	 * @param float $new_price
	 *
	 * @since 3.5
	 * @return true|WP_Error
	 */
	public function change_gateway_price( $new_price ) {

		if ( ! $this->can_change_gateway_price() ) {
			return new WP_Error( 'not_supported', __( 'Price change not supported for this membership', 'rcp' ) );
		}

		$changed = new WP_Error( 'error', __( 'Price not changed', 'rcp' ) );

		/**
		 * Filters whether the membership price was changed. Gateways hook into this to
		 * actually process the change.
		 *
		 * @param true|WP_Error  $changed True if successfully changed, WP_Error if not.
		 * @param float          $new     The new price.
		 * @param string         $gateway Gateway slug.
		 * @param RCP_Membership $this    Membership object.
		 *
		 * @since 3.5
		 */
		$changed = apply_filters( 'rcp_membership_change_gateway_price', $changed, $new_price, $this->get_gateway(), $this );

		if ( is_wp_error( $changed ) ) {
			rcp_log( sprintf( 'Error changing membership #%d gateway price. Code: %s; Message: %s', $this->get_id(), $changed->get_error_code(), $changed->get_error_message() ) );
		} else {
			rcp_log( sprintf( 'Successfully changed membership #%d gateway price from %s to %s.', $this->get_id(), $this->get_recurring_amount(), $new_price ) );
			$this->update( array(
				'recurring_amount' => $new_price
			) );
		}

		return $changed;

	}

	/**
	 * Determines if a recurring subscription can be created at the gateway for this membership.
	 *
	 * @since 3.5
	 * @return bool
	 */
	public function can_create_gateway_subscription() {

		$can_create = rcp_gateway_supports( $this->get_gateway(), 'subscription-creation' );

		if ( ! $can_create ) {
			return false;
		}

		// If we already have one, we shouldn't be creating another one...
		if ( $this->is_recurring() ) {
			return false;
		}

		return true;

	}

	/**
	 * Create a recurring subscription for this membership at the payment gateway.
	 *
	 * This function is called when creating a subscription outside the registration form.
	 *
	 * @param bool $charge_now True to do initial charge immediately, false if to delay until membership expiration date.
	 *
	 * @since 3.5
	 * @return true|WP_Error
	 */
	public function create_gateway_subscription( $charge_now = false ) {

		if ( ! $this->can_create_gateway_subscription() ) {
			return new WP_Error( 'not_supported', __( 'Dynamic subscription creation not supported for this membership.', 'rcp' ) );
		}

		$created = new WP_Error( 'error', __( 'Subscription not created.', 'rcp' ) );

		rcp_log( sprintf( 'Attempting to create recurring subscription in %s for membership #%d.', $this->get_gateway(), $this->get_id() ) );

		/**
		 * Filters whether the subscription was created. Gateways hook into this to
		 * actually process the change.
		 *
		 * @param true|WP_Error  $changed    True if successfully created, WP_Error if not.
		 * @param bool           $charge_now True to do initial charge immediately, false if to delay until membership expiration date.
		 * @param string         $gateway    Gateway slug.
		 * @param RCP_Membership $this       Membership object.
		 *
		 * @since 3.5
		 */
		$created = apply_filters( 'rcp_membership_created_gateway_subscription', $created, $charge_now, $this->get_gateway(), $this );

		if ( is_wp_error( $created ) ) {
			rcp_log( sprintf( 'Error creating gateway subscription for membership #%d. Code: %s; Message: %s', $this->get_id(), $created->get_error_code(), $created->get_error_message() ) );
		} else {
			rcp_log( sprintf( 'Successfully created recurring subscription in %s for membership #%d.', $this->get_gateway(), $this->get_id() ) );
			$this->set_recurring( true );
		}

		return $created;

	}

	/**
	 * Determines if the next bill date can be changed for recurring subscriptions.
	 *
	 * Requirements:
	 *      - Auto renew enabled.
	 *      - Gateway subscription ID filled out.
	 *      - Gateway supports `renewal-date-changes`
	 *
	 * @ssince 3.5
	 * @return bool
	 */
	public function can_change_next_bill_date() {

		if ( ! $this->is_recurring() ) {
			return false;
		}

		if ( empty( $this->get_gateway_subscription_id() ) ) {
			return false;
		}

		return rcp_gateway_supports( $this->get_gateway(), 'renewal-date-changes' );

	}

	/**
	 * Change the next bill date at the payment gateway for a recurring membership.
	 *
	 * @see   RCP_Membership::can_change_next_bill_date()
	 *
	 * @param string $new_date New next bill date in MySQL format.
	 *
	 * @since 3.5
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function change_next_bill_date( $new_date ) {

		if ( ! $this->can_change_next_bill_date() ) {
			return new WP_Error( 'not_supported', __( 'Next bill date changes not supported for this membership', 'rcp' ) );
		}

		$changed = new WP_Error( 'error', __( 'Next bill date not changed', 'rcp' ) );

		/**
		 * Filters whether the next bill date was changed. Gateways will hook into this to
		 * actually process the change.
		 *
		 * @param true|WP_Error  $changed  True if successfully changed, WP_Error if not.
		 * @param string         $new_date New next bill date in MySQL format.
		 * @param string         $gateway  Gateway slug.
		 * @param RCP_Membership $this     Membership object.
		 */
		$changed = apply_filters( 'rcp_membership_change_next_bill_date', $changed, $new_date, $this->get_gateway(), $this );

		if ( is_wp_error( $changed ) ) {
			rcp_log( sprintf( 'Error changing next bill date with payment gateway. Code: %s; Message: %s', $changed->get_error_code(), $changed->get_error_message() ) );
		} else {
			rcp_log( sprintf( 'Successfully changed membership #%d next bill date to %s.', $this->get_id(), $new_date ) );
		}

		return $changed;

	}

}
