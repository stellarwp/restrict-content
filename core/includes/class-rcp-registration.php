<?php

use RCP\Membership_Level;

/**
 * RCP Registration Class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Registration
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.5
 */

class RCP_Registration {

	/**
	 * Store the membership level ID for the registration
	 *
	 * @since 2.5
	 * @var int
	 */
	protected $subscription = 0;

	/**
	 * Membership level object for the registration
	 *
	 * @since 3.4
	 * @var Membership_Level|false
	 */
	protected $membership_level = false;

	/**
	 * Type of registration: new, renewal, upgrade
	 *
	 * @since 3.1
	 * @var string
	 */
	protected $registration_type = 'new';

	/**
	 * Current membership, if this is a renewal or upgrade
	 *
	 * @since 3.1
	 * @var RCP_Membership|false
	 */
	protected $membership = false;

	/**
	 * Payment object from the database if we're recovering a pending/abandoned/failed payment.
	 *
	 * @since 3.2.2
	 * @var object|bool
	 */
	protected $recovered_payment = false;

	/**
	 * Store the discounts for the registration
	 *
	 * @since 2.5
	 * @var array
	 */
	protected $discounts = array();

	/**
	 * Store the fees/credits for the registration. Credits are negative fees.
	 *
	 * @since 2.5
	 * @var array
	 */
	protected $fees = array();

	/**
	 * Get things started.
	 *
	 * @param int         $level_id ID of the membership level for this registration.
	 * @param null|string $discount Discount code to apply to this registration.
	 *
	 * @return void
	 */
	public function __construct( $level_id = 0, $discount = null ) {

		if ( $level_id ) {
			$this->set_subscription( $level_id );
		}

		$this->set_registration_type();
		$this->maybe_recover_payment();
		$this->maybe_add_signup_fee();

		if ( $level_id && $discount ) {
			$this->add_discount( strtolower( $discount ) );
		}

		do_action( 'rcp_registration_init', $this );
	}

	/**
	 * Set the subscription for this registration
	 *
	 * @since 2.5
	 *
	 * @param $membership_level
	 *
	 * @return bool
	 */
	public function set_subscription( $membership_level ) {
		$this->membership_level = rcp_get_membership_level( $membership_level );

		if ( ! $this->membership_level instanceof Membership_Level ) {
			return false;
		}

		$this->subscription = $membership_level;

		return true;
	}

	/**
	 * Add a signup fee if this is not a "renewal".
	 *
	 * @since 3.1
	 * @return void
	 */
	protected function maybe_add_signup_fee() {

		if ( ! $this->membership_level instanceof Membership_Level ) {
			return;
		}

		if ( empty( $this->membership_level->get_fee() ) ) {
			return;
		}

		$add_signup_fee = 'renewal' != $this->get_registration_type();

		/**
		 * Filters whether or not the signup fee should be applied.
		 *
		 * @param bool             $add_signup_fee   Whether or not to add the signup fee.
		 * @param Membership_Level $membership_level Membership level object.
		 * @param RCP_Registration $this             Registration object.
		 *
		 * @since 3.1
		 */
		$add_signup_fee = apply_filters( 'rcp_apply_signup_fee_to_registration', $add_signup_fee, $this->membership_level, $this );

		if ( ! $add_signup_fee ) {
			return;
		}

		$description = ( $this->membership_level->get_fee() > 0 ) ? __( 'Signup Fee', 'rcp' ) : __( 'Signup Credit', 'rcp' );
		$this->add_fee( $this->membership_level->get_fee(), $description );

	}

	/**
	 * Get registration subscription
	 *
	 * @deprecated 3.0 Use `RCP_Registration::get_membership_level_id()` instead.
	 * @see        RCP_Registration::get_membership_level_id()
	 *
	 * @since      2.5
	 * @return int
	 */
	public function get_subscription() {
		_deprecated_function( __METHOD__, '3.0', __CLASS__ . ':get_membership_level_id' );

		return $this->get_membership_level_id();
	}

	/**
	 * Get the ID number of the membership level this registration is for.
	 *
	 * @access public
	 * @since  3.0
	 * @return int ID of the membership level.
	 */
	public function get_membership_level_id() {
		return $this->subscription;
	}

	/**
	 * Set registration type
	 *
	 * This is based on the following query strings:
	 *
	 *        - $_REQUEST['registration_type'] - Will either be "renewal" or "upgrade". If empty, we assume "new".
	 *        - $_REQUEST['membership_id'] - This must be provided for renewals and upgrades so we know which membership
	 *                                       to work with.
	 *
	 * @since 3.1
	 * @return void
	 */
	public function set_registration_type() {

		$this->registration_type = 'new'; // default;

		if ( ! empty( $_REQUEST['registration_type'] ) && 'new' != $_REQUEST['registration_type'] && ! empty( $_REQUEST['membership_id'] ) ) {

			/**
			 * The `registration_type` query arg is set, it's NOT `new`, and we have a membership ID.
			 */
			$membership = rcp_get_membership( absint( $_REQUEST['membership_id'] ) );

			if ( ! empty( $membership ) && $membership->get_user_id() == get_current_user_id() ) {
				$this->membership        = $membership;
				$this->registration_type = sanitize_text_field( $_REQUEST['registration_type'] );
			}

		} elseif ( ! rcp_multiple_memberships_enabled() && $this->get_membership_level_id() ) {

			/**
			 * Multiple memberships not enabled, and we have a selected membership level ID on the form.
			 * We determine if it's a renewal or upgrade based on the user's current membership level and
			 * the one they've selected on the registration form.
			 */

			$customer            = rcp_get_customer();
			$previous_membership = ! empty( $customer ) ? rcp_get_customer_single_membership( $customer->get_id() ) : false;

			if ( ! empty( $previous_membership ) ) {
				$this->membership = $previous_membership;

				if ( $this->membership->get_object_id() == $this->get_membership_level_id() ) {
					$this->registration_type = 'renewal';
				} else {
					$this->registration_type = 'upgrade';
				}
			}

		}

		if ( 'upgrade' == $this->registration_type && is_object( $this->membership ) && $this->membership_level instanceof Membership_Level ) {
			/**
			 * If we have the type listed as an "upgrade", we'll run a few extra checks to determine
			 * if we should change this to "downgrade".
			 */
			// Figure out if this is a downgrade instead.
			$previous_membership_level = rcp_get_membership_level( $this->membership->get_object_id() );

			// If the previous membership level is invalid (maybe it's been deleted), then treat this as a new registration.
			if ( ! $previous_membership_level instanceof Membership_Level ) {
				rcp_log( sprintf( 'Previous membership level (#%d) is invalid. Treating this as a new registration.', $this->membership->get_object_id() ) );

				$this->registration_type = 'new';

				return;
			}

			$days_in_old_cycle = rcp_get_days_in_cycle( $previous_membership_level->get_duration_unit(), $previous_membership_level->get_duration() );
			$days_in_new_cycle = rcp_get_days_in_cycle( $this->membership_level->get_duration_unit(), $this->membership_level->get_duration() );

			$old_price_per_day = $days_in_old_cycle > 0 ? $previous_membership_level->get_price() / $days_in_old_cycle : $previous_membership_level->get_price();
			$new_price_per_day = $days_in_new_cycle > 0 ? $this->get_recurring_total( true, false ) / $days_in_new_cycle : $this->get_recurring_total( true, false );

			rcp_log( sprintf( 'Old price per day: %s (ID #%d); New price per day: %s (ID #%d)', $old_price_per_day, $previous_membership_level->get_id(), $new_price_per_day, $this->membership_level->get_id() ) );

			if ( $old_price_per_day > $new_price_per_day ) {
				$this->registration_type = 'downgrade';
			}
		}

	}

	/**
	 * Get the registration type
	 *
	 * @since 3.1
	 * @return string
	 */
	public function get_registration_type() {
		return $this->registration_type;
	}

	/**
	 * Get the existing membership object that is being renewed or upgraded
	 *
	 * @since 3.1
	 * @return RCP_Membership|false
	 */
	public function get_membership() {
		return $this->membership;
	}

	/**
	 * Determine whether or not the level being registered for has a trial that the current user is eligible
	 * for. This will return false if there is a trial but the user is not eligible for it.
	 *
	 * @access public
	 * @since  3.0.6
	 * @return bool
	 */
	public function is_trial() {

		if ( ! $this->membership_level instanceof Membership_Level ) {
			return false;
		}

		if ( ! $this->membership_level->has_trial() ) {
			return false;
		}

		// There is a trial, but let's check eligibility.

		$customer = rcp_get_customer_by_user_id();

		// No customer, which means they're brand new, which means they're eligible.
		if ( empty( $customer ) ) {
			return true;
		}

		return ! $customer->has_trialed();

	}

	/**
	 * Attempt to recover an existing pending / abandoned / failed payment.
	 *
	 * First we look in the request for `rcp_registration_payment_id`. If that doesn't exist then we try to recover
	 * automatically. This will only work if the current membership (located via `set_registration_type()`)
	 * has a pending payment ID. This will be the case if someone was attempting to sign up or manually renew,
	 * didn't complete payment, then immediately reattempted.
	 *
	 * If a payment is located, then this payment is used for the registration instead of creating a new one.
	 * This will also used the existing membership record associated with this payment.
	 *
	 * Requirements:
	 *      - The payment status is `pending`, `abandoned`, or `failed`.
	 *      - Transaction ID is empty.
	 *      - There is an associated membership record (it's okay if it's disabled, it just needs to exist).
	 *
	 * @link  https://github.com/restrictcontentpro/restrict-content-pro/issues/2230
	 *
	 * @since 3.2.2
	 * @return void
	 */
	protected function maybe_recover_payment() {

		// First get payment ID from request. This will be set when explicitly clicking a "Complete Payment" link.
		$pending_payment_id = ! empty( $_REQUEST['rcp_registration_payment_id'] ) ? absint( $_REQUEST['rcp_registration_payment_id'] ) : 0;

		/*
		 * Or, try to get a pending payment ID from the current membership's meta.
		 * This will be set if trying to renew the current membership a second time when the first time failed.
		 */
		if ( empty( $pending_payment_id ) && ! empty( $this->membership ) ) {
			$pending_payment_id = rcp_get_membership_meta( $this->membership->get_id(), 'pending_payment_id', true );
		}

		if ( empty( $pending_payment_id ) ) {
			return;
		}

		/**
		 * @var RCP_Payments $rcp_payments_db
		 */
		global $rcp_payments_db;

		$payment = $rcp_payments_db->get_payment( $pending_payment_id );

		// We can't use this if there's already a transaction ID.
		if ( ! empty( $payment->transaction_id ) ) {
			return;
		}

		// We can't use this if there's a user ID mismatch.
		if ( $payment->user_id != get_current_user_id() ) {
			return;
		}

		// We can't use this if there's no associated membership.
		if ( empty( $payment->membership_id ) || ! $membership = rcp_get_membership( $payment->membership_id ) ) {
			return;
		}

		// We can't use this if the membership level ID being signed up for is now different from the recovered payment level ID.
		if ( $this->get_membership_level_id() && $payment->object_id != $this->get_membership_level_id() ) {
			return;
		}

		$this->recovered_payment = $payment;

		// Use the same `transaction_type` as the recovered payment.
		$this->registration_type = $this->recovered_payment->transaction_type;

		rcp_log( sprintf( 'Using recovered payment #%d for registration. Transaction type: %s.', $this->recovered_payment->id, $this->recovered_payment->transaction_type ) );

	}

	/**
	 * Get the recovered payment object.
	 *
	 * @since 3.2.3
	 * @return object|false Payment object if set, false if not.
	 */
	public function get_recovered_payment() {
		return $this->recovered_payment;
	}

	/**
	 * Add discount to the registration
	 *
	 * @since      2.5
	 *
	 * @param      $code
	 * @param bool $recurring
	 *
	 * @return bool
	 */
	public function add_discount( $code, $recurring = true ) {
		if ( ! rcp_validate_discount( $code, $this->subscription ) ) {
			return false;
		}

		$this->discounts[ $code ] = $recurring;
		return true;
	}

	/**
	 * Get registration discounts
	 *
	 * @since 2.5
	 * @return array|bool
	 */
	public function get_discounts() {
		if ( empty( $this->discounts ) ) {
				return false;
		}

		return $this->discounts;
	}

	/**
	 * Add fee to the registration. Use negative fee for credit.
	 *
	 * @since      2.5
	 *
	 * @param float $amount
	 * @param null  $description
	 * @param bool  $recurring
	 * @param bool  $proration
	 *
	 * @return bool
	 */
	public function add_fee( $amount, $description = null, $recurring = false, $proration = false ) {

		$fee = array(
			'amount'      => floatval( $amount ),
			'description' => sanitize_text_field( $description ),
			'recurring'   => (bool) $recurring,
			'proration'   => (bool) $proration,
		);

		$id = md5( serialize( $fee ) );

		if ( isset( $this->fees[ $id ] ) ) {
			return false;
		}

		$this->fees[ $id ] = apply_filters( 'rcp_registration_add_fee', $fee, $this );

		return true;
	}

	/**
	 * Get registration fees
	 *
	 * @since 2.5
	 * @return array|bool
	 */
	public function get_fees() {
		if ( empty( $this->fees ) ) {
			return false;
		}

		return $this->fees;
	}

	/**
	 * Get the total number of fees
	 *
	 * @since 2.5
	 *
	 * @param null $total
	 * @param bool $only_recurring | set to only get fees that are recurring
	 *
	 * @return float
	 */
	public function get_total_fees( $total = null, $only_recurring = false ) {

		if ( ! $this->get_fees() ) {
			return 0;
		}

		$fees = 0;

		foreach ( $this->get_fees() as $fee ) {
			if ( $only_recurring && ! $fee['recurring'] ) {
				continue;
			}

			$fees += $fee['amount'];
		}

		// if total is present, make sure that any negative fees are not
		// greater than the total.
		if ( $total && ( $fees + $total ) < 0 ) {
			$fees = -1 * $total;
		}

		return apply_filters( 'rcp_registration_get_total_fees', (float) $fees, $total, $only_recurring, $this );

	}

	/**
	 * Get the signup fees
	 *
	 * @since 2.5
	 *
	 * @return float
	 */
	public function get_signup_fees() {

		if ( ! $this->get_fees() ) {
			return 0;
		}

		$fees = 0;

		foreach ( $this->get_fees() as $fee ) {

			if ( $fee['proration'] ) {
				continue;
			}

			if ( $fee['recurring'] ) {
				continue;
			}

			$fees += $fee['amount'];
		}

		return apply_filters( 'rcp_registration_get_signup_fees', (float) $fees, $this );

	}

	/**
	 * Get the total proration amount
	 *
	 * @since 2.5
	 *
	 * @return float
	 */
	public function get_proration_credits() {

		if ( ! $this->get_fees() ) {
			return 0;
		}

		$proration = 0;

		foreach ( $this->get_fees() as $fee ) {

			if ( ! $fee['proration'] ) {
				continue;
			}

			$proration += $fee['amount'];

		}

		return apply_filters( 'rcp_registration_get_proration_fees', (float) $proration, $this );

	}

	/**
	 * Get the total discounts
	 *
	 * @since 2.5
	 *
	 * @param null $total
	 * @param bool $only_recurring | set to only get discounts that are recurring
	 *
	 * @return int|mixed
	 */
	public function get_total_discounts( $total = null, $only_recurring = false ) {

		global $rcp_options;

		if ( ! $registration_discounts = $this->get_discounts() ) {
			return 0;
		}

		if ( ! $total && $this->membership_level instanceof Membership_Level ) {
			$total = $this->membership_level->get_price();

			// If fees get discounted too, then we need to add those on to our total.
			if ( ! empty( $rcp_options['discount_fees'] ) ) {
				$total += $this->get_total_fees( null, $only_recurring );
			}
		}

		$original_total = $total;

		foreach ( $registration_discounts as $registration_discount => $recurring ) {

			if ( $only_recurring && ! $recurring ) {
				continue;
			}

			$discount_obj = rcp_get_discount_by( 'code', $registration_discount );

			if ( $only_recurring && is_object( $discount_obj ) && ! empty( $discount_obj->is_one_time() ) ) {
				continue;
			}

			if ( is_object( $discount_obj ) ) {
				// calculate the after-discount price
				$total = rcp_get_discounted_price( $total, $discount_obj->get_amount(), $discount_obj->get_unit(), false );
			}
		}

		// make sure the discount is not > 100%
		if ( 0 > $total ) {
			$total = 0;
		}

		$total = round( $total, rcp_currency_decimal_filter() );

		return apply_filters( 'rcp_registration_get_total_discounts', (float) ( $original_total - $total ), $original_total, $only_recurring, $this );

	}

	/**
	 * Get the registration total due today
	 *
	 * @param bool $discounts | Include discounts?
	 * @param bool $fees      | Include fees?
	 *
	 * @since 2.5
	 * @return float
	 */
	public function get_total( $discounts = true, $fees = true ) {

		global $rcp_options;

		if ( $this->is_trial() ) {
			return 0;
		}

		$total = $this->membership_level instanceof Membership_Level ? $this->membership_level->get_price() : 0;

		if ( $discounts && empty( $rcp_options['discount_fees'] ) ) {
			$total -= $this->get_total_discounts( $total );
		}

		if ( 0 > $total ) {
			$total = 0;
		}

		if ( $fees ) {
			$total += $this->get_signup_fees();
			$total += $this->get_proration_credits();
		}

		if ( $discounts && ! empty( $rcp_options['discount_fees'] ) ) {
			$total -= $this->get_total_discounts( $total );
		}

		if ( 0 > $total ) {
			$total = 0;
		}

		$total = round( $total, rcp_currency_decimal_filter() );

		/**
		 * Filter the "initial amount" total.
		 *
		 * @param float $total     Total amount due today.
		 * @param RCP_Registration Registration object.
		 * @param bool  $discounts Whether or not discounts are included in the value.
		 * @param bool  $fees      Whether or not fees are included in the value.
		 */
		return apply_filters( 'rcp_registration_get_total', floatval( $total ), $this, $discounts, $fees );

	}

	/**
	 * Get the registration recurring total
	 *
	 * @param bool $discounts | Include discounts?
	 * @param bool $fees      | Include fees?
	 *
	 * @since 2.5
	 * @return float
	 */
	public function get_recurring_total( $discounts = true, $fees = true ) {

		global $rcp_options;

		$total = ( $this->membership_level instanceof Membership_Level && ! $this->membership_level->is_lifetime() ) ? $this->membership_level->get_price() : 0;

		if ( $discounts && empty( $rcp_options['discount_fees'] ) ) {
			$total -= $this->get_total_discounts( $total, true );
		}

		if ( $fees ) {
			$total += $this->get_total_fees( $total, true );
		}

		if ( $discounts && ! empty( $rcp_options['discount_fees'] ) ) {
			$total -= $this->get_total_discounts( $total, true );
		}

		if ( 0 > $total ) {
			$total = 0;
		}

		$total = round( $total, rcp_currency_decimal_filter() );

		/**
		 * Filters the "recurring amount" total.
		 *
		 * @param float $total     Recurring amount.
		 * @param RCP_Registration Registration object.
		 * @param bool  $discounts Whether or not discounts are included in the value.
		 * @param bool  $fees      Whether or not fees are included in the value.
		 */
		return apply_filters( 'rcp_registration_get_recurring_total', floatval( $total ), $this, $discounts, $fees );

	}


}
