<?php
/**
 * Discount
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.3
 */

use \RCP\Base_Object;

/**
 * Class RCP_Discount
 * @since 3.3
 */
class RCP_Discount extends Base_Object {

	/**
	 * Discount ID
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Discount name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Description
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Discount amount
	 * This will either be a dollar amount or percentage.
	 *
	 * @var int|float
	 */
	protected $amount = 0;

	/**
	 * Will either be `%` (percentage) or `flat` (fixed dollar amount)
	 *
	 * @var string
	 */
	protected $unit = '%';

	/**
	 * Discount code
	 *
	 * @var string
	 */
	protected $code = '';

	/**
	 * Number of times the discount code has been used
	 *
	 * @var int
	 */
	protected $use_count = 0;

	/**
	 * Maximum number of times this code can be used. `0` for unlimited.
	 *
	 * @var int
	 */
	protected $max_uses = 0;

	/**
	 * Status of the discount - either `active` or `disabled`. Only active discounts can be used.
	 *
	 * @var string
	 */
	protected $status = 'disabled';

	/**
	 * Date the discount code expires. Empty for never expires.
	 *
	 * @var string
	 */
	protected $expiration = '';

	/**
	 * Serialized array of membership level IDs this discount code is locked to.
	 * If set, this discount code can only be used on specific membership levels.
	 *
	 * @var string
	 */
	protected $membership_level_ids = '';

	/**
	 * Whether the discount is recurring (`0`) or is only applied towards the first payment (`1`).
	 *
	 * @var int
	 */
	protected $one_time = 0;

	/**
	 * Magic setter to allow empty checks on protected properties.
	 *
	 * @param string $key Property name.
	 *
	 * @since 3.3
	 * @return bool True if the property is set, false if not.
	 */
	public function __isset( $key ) {
		if ( property_exists( $this, $key ) ) {
			return false === empty( $this->{$key} );
		} else {
			return false;
		}
	}

	/**
	 * Magic getter to retrieve protected properties.
	 *
	 * @param string $key Property name.
	 *
	 * @since 3.3
	 * @return mixed|WP_Error
	 */
	public function __get( $key ) {

		$key = sanitize_key( $key );

		if ( method_exists( $this, 'get_' . $key ) ) {
			return call_user_func( array( $this, 'get_' . $key ) );
		} elseif ( property_exists( $this, $key ) ) {
			return $this->{$key};
		} else {
			return new WP_Error( 'invalid-property', sprintf( __( 'Can\'t get property %s', 'rcp' ), $key ) );
		}

	}

	/**
	 * Get the discount code ID number
	 *
	 * @since 3.3
	 * @return int
	 */
	public function get_id() {
		return absint( $this->id );
	}

	/**
	 * Get the name of the discount
	 *
	 * @since 3.3
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the discount description
	 *
	 * @since 3.3
	 * @return string
	 */
	public function get_description() {
		return stripslashes( $this->description );
	}

	/**
	 * Get the amount the discount is for
	 *
	 * @since 3.3
	 * @return float|int
	 */
	public function get_amount() {
		return $this->amount;
	}

	/**
	 * Get the discount unit
	 *
	 * @since 3.3
	 * @return string
	 */
	public function get_unit() {
		return 'flat' == $this->unit ? 'flat' : '%';
	}

	/**
	 * Get the discount code
	 *
	 * This is the value actually entered on checkout.
	 *
	 * @since 3.3
	 * @return string
	 */
	public function get_code() {
		return strtolower( $this->code );
	}

	/**
	 * Get the number of times the discount code has been used
	 *
	 * @since 3.3
	 * @return int
	 */
	public function get_use_count() {
		return absint( $this->use_count );
	}

	/**
	 * Increase the use count by 1
	 *
	 * @since 3.3
	 * @return void
	 */
	public function increment_use_count() {
		$this->use_count = $this->get_use_count() + 1;

		rcp_update_discount( $this->get_id(), array(
			'use_count' => $this->get_use_count()
		) );
	}

	/**
	 * Decrease the use count by 1
	 *
	 * @since 3.3
	 * @return void
	 */
	public function decrement_use_count() {
		$this->use_count = $this->get_use_count() - 1;

		if ( $this->use_count < 0 ) {
			$this->use_count = 0;
		}

		rcp_update_discount( $this->get_id(), array(
			'use_count' => $this->get_use_count()
		) );
	}

	/**
	 * Get the maximum number of times the discount code can be used
	 *
	 * @since 3.3
	 * @return int
	 */
	public function get_max_uses() {
		return absint( $this->max_uses );
	}

	/**
	 * Get the status of the discount code - either `active` or `disabled`.
	 * Only active discount codes can be used.
	 *
	 * @since 3.3
	 * @return string
	 */
	public function get_status() {
		return 'active' == $this->status ? 'active' : 'disabled';
	}

	/**
	 * Get the date the discount expires. Empty if it never expires.
	 *
	 * @since 3.3
	 * @return string
	 */
	public function get_expiration() {
		return $this->expiration;
	}

	/**
	 * Get the IDs of membership levels this discount code can be used on.
	 * If empty, the discount can be used for any membership level.
	 *
	 * @since 3.3
	 * @return array
	 */
	public function get_membership_level_ids() {
		$ids = maybe_unserialize( $this->membership_level_ids );

		if ( is_array( $ids ) && ! empty( $ids ) ) {
			$ids = array_map( 'absint', $ids );
		} else {
			$ids = array();
		}

		return $ids;
	}

	/**
	 * Determines whether the discount is locked to certain membership levels.
	 *
	 * @since 3.3
	 * @return bool
	 */
	public function has_membership_level_ids() {

		$level_ids = $this->get_membership_level_ids();

		return ! empty( $level_ids ) && is_array( $level_ids ) && count( $level_ids );

	}

	/**
	 * Returns `true` if the discount code is only applied to the first payment.
	 * Returns `false` if the discount code is applied to all payments in a subscription.
	 *
	 * @since 3.3
	 * @return bool
	 */
	public function is_one_time() {
		return ! empty( $this->one_time );
	}

	/**
	 * Determines whether the discount code is expired.
	 *
	 * @since 3.3
	 * @return bool
	 */
	public function is_expired() {

		$is_expired = false;
		$expiration = $this->get_expiration();

		if ( ! empty( $expiration ) && strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $expiration, current_time( 'timestamp' ) ) ) {
			$is_expired = true;
		}

		/**
		 * Filters whether the discount is expired.
		 *
		 * @param bool         $is_expired  Whether or not the discount is expired.
		 * @param int          $discount_id ID of the discount.
		 * @param string       $expiration  Expiration date in MySQL format.
		 * @param RCP_Discount $this        Discount object.
		 */
		return (bool) apply_filters( 'rcp_is_discount_expired', $is_expired, $this->get_id(), $expiration, $this );

	}

	/**
	 * Determines whether the discount code has reached its maximum number of uses.
	 *
	 * @since 3.3
	 * @return bool
	 */
	public function is_maxed_out() {

		$is_maxed = false;
		$uses     = $this->get_use_count();
		$max      = $this->get_max_uses();

		if ( $max > 0 && $uses >= $max ) {
			$is_maxed = true;
		}

		/**
		 * Filters whether the discount code is maxed out.
		 *
		 * @param bool         $is_maxed    Whether the discount code is maxed out.
		 * @param int          $discount_id ID of the discount.
		 * @param int          $uses        Number of times the discount has been used.
		 * @param int          $max         Maximum number of times the discount can be used.
		 * @param RCP_Discount $this        Discount object.
		 */
		return (bool) apply_filters( 'rcp_is_discount_maxed_out', $is_maxed, $this->get_id(), $uses, $max, $this );

	}

	/**
	 * Determines whether the discount code can be used. This checks:
	 *      - Status must be `active`.
	 *      - Expiration date must be blank or in the future.
	 *      - Max uses must not be exceeded.
	 *      - Provided membership level ID is in the allowed levels.
	 *
	 * @param int $membership_level_id ID of the membership level you want to use the code for.
	 *
	 * @since 3.3
	 * @return bool
	 */
	public function is_valid( $membership_level_id = 0 ) {

		$is_valid = false;

		// The discount is valid if it's active, not expired, and not maxed out.
		if ( 'active' == $this->get_status() && ! $this->is_expired() && ! $this->is_maxed_out() ) {
			$is_valid = true;
		}

		// Now check membership level ID requirements.
		if ( $this->has_membership_level_ids() ) {
			if ( ! in_array( $membership_level_id, $this->get_membership_level_ids() ) ) {
				$is_valid = false;
			}
		}

		// One-time discounts cannot be applied to free trials.
		if ( $is_valid && rcp_is_registration() && $this->is_one_time() && rcp_get_registration()->is_trial() ) {
			$is_valid = false;
		}

		/**
		 * Filters whether or not the discount code is valid.
		 *
		 * @param bool         $is_valid            Whether or not the discount code is valid.
		 * @param RCP_Discount $this                Discount code object.
		 * @param int          $membership_level_id ID of the membership level being checked.
		 */
		return (bool) apply_filters( 'rcp_is_discount_valid', $is_valid, $this, $membership_level_id );

	}

	/**
	 * Stores this discount code in a user's history.
	 *
	 * @param int $user_id ID of the user to store the discount with.
	 *
	 * @since 3.3
	 * @return void
	 */
	public function store_for_user( $user_id ) {

		$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );

		if ( ! is_array( $user_discounts ) ) {
			$user_discounts = array();
		}

		$user_discounts[] = $this->get_code();

		/**
		 * Triggers before the discount code is stored for the user.
		 *
		 * @param string       $code    Discount code string.
		 * @param int          $user_id ID of the user the code is being stored for.
		 * @param RCP_Discount $this    Discount object.
		 */
		do_action( 'rcp_pre_store_discount_for_user', $this->get_code(), $user_id, $this );

		update_user_meta( $user_id, 'rcp_user_discounts', $user_discounts );

		/**
		 * Triggers after the discount code is stored for the user.
		 *
		 * @param string       $code    Discount code string.
		 * @param int          $user_id ID of the user the code is being stored for.
		 * @param RCP_Discount $this    Discount object.
		 */
		do_action( 'rcp_store_discount_for_user', $this->get_code(), $user_id, $this );

	}

	/**
	 * Removes this discount code from a user's history.
	 *
	 * @param int $user_id ID of the user to remove the discount code from.
	 *
	 * @since 3.3
	 * @return bool Whether or not the discount was successfully removed.
	 */
	public function remove_from_user( $user_id ) {

		$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );

		if ( ! is_array( $user_discounts ) ) {
			$user_discounts = array();
		}

		// Reverse the array to remove the last instance of the discount.
		$key = array_search( $this->get_code(), array_reverse( $user_discounts, true ) );

		if ( false !== $key ) {
			unset( $user_discounts[ $key ] );

			/**
			 * Triggers before the discount code is removed.
			 *
			 * @param string       $code    Discount code string.
			 * @param int          $user_id ID of the user the discount is being removed from.
			 * @param RCP_Discount $this    Discount object.
			 */
			do_action( 'rcp_pre_remove_discount_from_user', $this->get_code(), $user_id, $this );

			if ( empty( $user_discounts ) ) {
				delete_user_meta( $user_id, 'rcp_user_discounts' );
			} else {
				update_user_meta( $user_id, 'rcp_user_discounts', $user_discounts );
			}

			/**
			 * Triggers after the discount code is removed.
			 *
			 * @param string       $code    Discount code string.
			 * @param int          $user_id ID of the user the discount was removed from.
			 * @param RCP_Discount $this    Discount object.
			 */
			do_action( 'rcp_remove_discount_from_user', $this->get_code(), $user_id, $this );

			return true;
		}

		return false;

	}

}
