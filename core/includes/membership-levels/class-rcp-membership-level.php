<?php
/**
 * Membership Level Object
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 */

namespace RCP;

/**
 * Class Membership_Level
 *
 * @package RCP
 */
class Membership_Level extends Base_Object {

	/**
	 * Membership level ID
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Name of the membership level
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
	 * Duration of a single billing cycle
	 *
	 * Combined with `duration_unit` to form a readable billing cycle.
	 * A `0` duration indicates the membership never expires.
	 *
	 * @var int
	 */
	protected $duration = 0;

	/**
	 * Duration unit of a single billing cycle
	 *
	 * @var string
	 */
	protected $duration_unit = 'month';

	/**
	 * Duration of the free trial period
	 *
	 * Combined with `trial_duration_unit` to form a readable duration.
	 * A `0` duration indicates there is no free trial.
	 *
	 * @var int
	 */
	protected $trial_duration = 0;

	/**
	 * Duration unit of the trial period
	 *
	 * @var string
	 */
	protected $trial_duration_unit = 'day';

	/**
	 * Base price of the membership level
	 *
	 * For the initial payment, this value is combined with the `fee`.
	 * For renewal payments, this is the entire price.
	 *
	 * @var string
	 */
	protected $price = '0';

	/**
	 * Extra fee charged on initial signup
	 *
	 * If this value is negative then it's treated as a discount.
	 *
	 * @var string
	 */
	protected $fee = '0';

	/**
	 * Maximum number of renewals allowed
	 *
	 * A `0` maximum means there is no limit and it will renew indefinitely or until cancelled.
	 *
	 * @var int
	 */
	protected $maximum_renewals = 0;

	/**
	 * Action to take after the final payment.
	 *
	 * @var string
	 */
	protected $after_final_payment = '';

	/**
	 * Determines order of the membership level on the signup page
	 *
	 * @var int
	 */
	protected $list_order = 0;

	/**
	 * Access level granted to customers who have this membership level
	 *
	 * Number between 0 and 10, with 10 being the highest/most access.
	 *
	 * @var int
	 */
	protected $level = 0;

	/**
	 * Status of the membership level
	 *
	 * Active membership levels appear in [register_form].
	 * Inactive membership levels do not appear automatically and must be explicitly declared via `id` attribute.
	 *
	 * @var string
	 */
	protected $status = 'inactive';

	/**
	 * WordPress user role to grant customers who sign up for this membership level
	 *
	 * @var string
	 */
	protected $role = 'subscriber';

	/**
	 * Date in UTC the membership level was created. Format: YYYY-MM-DD HH:MM:SS
	 *
	 * @var string
	 */
	protected $date_created = '';

	/**
	 * Date in UTC the membership level was last modified. Format: YYYY-MM-DD HH:MM:SS
	 *
	 * @var string
	 */
	protected $date_modified = '';

	/**
	 * Magic setter to allow empty checks on protected properties.
	 *
	 * @param string $key Property name.
	 *
	 * @since 3.4
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
	 * @since 3.4
	 * @return mixed|\WP_Error
	 */
	public function __get( $key ) {

		$key = sanitize_key( $key );

		if ( method_exists( $this, 'get_' . $key ) ) {
			return call_user_func( array( $this, 'get_' . $key ) );
		} elseif ( property_exists( $this, $key ) ) {
			return $this->{$key};
		} else {
			return new \WP_Error( 'invalid-property', sprintf( __( 'Can\'t get property %s', 'rcp' ), $key ) );
		}

	}

	/**
	 * Magic setter to set protected properties.
	 *
	 * @param string $key   Property name.
	 * @param mixed  $value Property value.
	 *
	 * @since 3.4
	 * @return void|false
	 */
	public function __set( $key, $value ) {

		$key = sanitize_key( $key );

		// Only real properties can be saved.
		$keys = array_keys( get_class_vars( get_called_class() ) );

		if ( ! in_array( $key, $keys, true ) ) {
			return false;
		}

		$this->{$key} = $value;

	}

	/**
	 * Returns the membership level ID
	 *
	 * @since 3.4
	 * @return int
	 */
	public function get_id() {
		return absint( $this->id );
	}

	/**
	 * Returns the name of the membership level
	 *
	 * @since 3.4
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Returns the membership level description
	 *
	 * @since 3.4
	 * @return string
	 */
	public function get_description() {
		/**
		 * Filters the membership level description.
		 *
		 * @param string $description
		 * @param int    $id
		 */
		return apply_filters( 'rcp_get_subscription_description', $this->description, $this->get_id() );
	}

	/**
	 * Returns the billing cycle duration
	 *
	 * @since 3.4
	 * @return int
	 */
	public function get_duration() {
		return absint( $this->duration );
	}

	/**
	 * Returns the billing cycle duration unit
	 *
	 * One of: day, month, year
	 *
	 * @since 3.4
	 * @return string
	 */
	public function get_duration_unit() {
		return $this->duration_unit;
	}

	/**
	 * Determines whether or not this is a lifetime membership
	 *
	 * @sihnce 3.4
	 * @return bool
	 */
	public function is_lifetime() {
		return 0 === $this->get_duration();
	}

	/**
	 * Returns the duration of the free trial
	 *
	 * If `0`, then there is no free trial configured.
	 *
	 * @since 3.4
	 * @return int
	 */
	public function get_trial_duration() {
		return absint( $this->trial_duration );
	}

	/**
	 * Returns the trial duration unit
	 *
	 * One of: day, month, year
	 *
	 * @since 3.4
	 * @return string
	 */
	public function get_trial_duration_unit() {
		return $this->trial_duration_unit;
	}

	/**
	 * Determines if the membership level has a built-in free trial
	 *
	 * @since 3.4
	 * @return bool
	 */
	public function has_trial() {
		return $this->get_trial_duration() > 0;
	}

	/**
	 * Returns the price of the membership level
	 *
	 * @since 3.4
	 * @return float
	 */
	public function get_price() {
		return apply_filters( 'rcp_membership_level_price', floatval( $this->price ), $this );
	}

	/**
	 * Determines whether or not the membership level is free
	 *
	 * @since 3.4
	 * @return bool
	 */
	public function is_free() {
		return 0 == $this->get_price();
	}

	/**
	 * Returns the sign up fee
	 *
	 * @since 3.4
	 * @return float
	 */
	public function get_fee() {
		return floatval( $this->fee );
	}

	/**
	 * Returns the maximum number of renewals allowed
	 *
	 * Used in payment plans.
	 *
	 * @since 3.4
	 * @return int
	 */
	public function get_maximum_renewals() {
		return absint( $this->maximum_renewals );
	}

	/**
	 * Returns the action to take after the final payment
	 *
	 * One of:
	 *        - lifetime - grant lifetime access to the membership
	 *        - expire_immediately - expire the membership immediately
	 *        - expire_term_end - expire at the end of the billing cycle
	 *
	 * Use in payment plans.
	 *
	 * @since 3.4
	 * @return string
	 */
	public function get_after_final_payment() {
		return $this->after_final_payment;
	}

	/**
	 * Returns the list order
	 *
	 * This controls where the membership level is displayed in the `[register_form]` shortcode list.
	 * Lower numbers will be shown at the top, higher numbers at the bottom.
	 *
	 * @since 3.4
	 * @return int
	 */
	public function get_list_order() {
		return intval( $this->list_order );
	}

	/**
	 * Returns the access level granted by this membership level
	 *
	 * Will be a number from 0 to 10
	 *
	 * @since 3.4
	 * @return int
	 */
	public function get_access_level() {
		return absint( $this->level );
	}

	/**
	 * Returns the status of the membership level
	 *
	 * One of: active, inactive
	 *
	 * @since 3.4
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Returns the user role granted by this membership level
	 *
	 * @since 3.4
	 * @return string
	 */
	public function get_role() {
		return ! empty( $this->role ) ? $this->role : 'subscriber';
	}

	/**
	 * Returns the date the membership level was created
	 *
	 * @since 3.4
	 * @return string
	 */
	public function get_date_created() {
		return $this->date_created;
	}

}
