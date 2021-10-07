<?php
/**
 * RCP Discounts class
 *
 * This class handles querying, inserting, updating, and removing discounts
 * Also includes other discount helper functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Discounts
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.5
 */

/**
 * Class RCP_Discounts
 *
 * @deprecated 3.3
 */
class RCP_Discounts {

	/**
	 * Holds the name of our discounts database table
	 *
	 * @access  public
	 * @since   1.5
	 */
	public $db_name;


	/**
	 * Holds the version number of our discounts database table
	 *
	 * @access  public
	 * @since   1.5
	 */
	public $db_version;


	/**
	 * Get things started
	 *
	 * @since   1.5
	 * @return  void
	 */
	function __construct() {

		$this->db_name    = rcp_get_discounts_db_name();
		$this->db_version = '1.3';

	}


	/**
	 * Retrieve discounts from the database
	 *
	 * @deprecated 3.3 In favour of rcp_get_discounts()
	 * @see        rcp_get_discounts()
	 *
	 * @param array $args Query arguments.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  array|false Array of discounts or false if none.
	 */
	public function get_discounts( $args = array() ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_get_discounts' );

		$defaults = array(
			'status' => 'all',
			'number' => null,
			'offset' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$discounts = rcp_get_discounts( $args );

		return ! empty( $discounts ) ? $discounts : false;

	}

	/**
	 * Count the total number of discount codes in the database
	 *
	 * @deprecated 3.3 In favour of rcp_count_discounts()
	 * @see        rcp_count_discounts()
	 *
	 * @param array $args Query arguments to override the defaults.
	 *
	 * @access public
	 * @return int
	 */
	public function count( $args = array() ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_count_discounts' );

		$defaults = array(
			'status' => 'all'
		);

		$args = wp_parse_args( $args, $defaults );

		return rcp_count_discounts( $args );

	}


	/**
	 * Retrieve a specific discount from the database
	 *
	 * @deprecated 3.3 In favour of rcp_get_discount()
	 * @see        rcp_get_discount()
	 *
	 * @param  int $discount_id ID of the discount to retrieve.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  object|null Database row or null on failure.
	 */
	public function get_discount( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_get_discount' );

		return rcp_get_discount( $discount_id );

	}


	/**
	 * Retrieve a specific discount from the database by field
	 *
	 * @deprecated 3.3 In favour of rcp_get_discount_by()
	 * @see        rcp_get_discount_by()
	 *
	 * @param string $field Name of the field to check.
	 * @param string $value Value of the field.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  object|null Database row or null on failure.
	 */
	public function get_by( $field = 'code', $value = '' ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_get_discount_by' );

		return rcp_get_discount_by( $field, $value );

	}


	/**
	 * Get the status of a discount
	 *
	 * @deprecated 3.3 In favour of rcp_get_discount_status()
	 * @see        rcp_get_discount_status()
	 *
	 * @param  int $discount_id ID of the discount.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  string|false Discount status or false on failure.
	 */
	public function get_status( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_get_discount_status' );

		return rcp_get_discount_status( $discount_id );

	}


	/**
	 * Get the amount of a discount
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::get_amount()
	 * @see        RCP_Discount::get_amount()
	 *
	 * @param  int $discount_id ID of the discount.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  int|float
	 */
	public function get_amount( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::get_amount' );

		$discount = rcp_get_discount( $discount_id );

		if ( $discount ) {
			return $discount->get_amount();
		} else {
			return 0;
		}

	}


	/**
	 * Get the number of times a discount has been used
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::get_use_count()
	 * @see        RCP_Discount::get_use_count()
	 *
	 * @param  int $discount_id ID of the discount.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  int
	 */
	public function get_uses( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::get_use_count' );

		$discount = rcp_get_discount( $discount_id );

		if ( $discount ) {
			return $discount->get_use_count();
		} else {
			return 0;
		}

	}


	/**
	 * Get the maximum number of times a discount can be used
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::get_max_uses()
	 * @see        RCP_Discount::get_max_uses()
	 *
	 * @param  int $discount_id ID of the discount.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  int
	 */
	public function get_max_uses( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::get_max_uses' );

		$discount = rcp_get_discount( $discount_id );

		if ( $discount ) {
			return $discount->get_max_uses();
		} else {
			return 0;
		}

	}


	/**
	 * Get the associated membership level for a discount
	 *
	 * @deprecated 3.0 Use `get_membership_level_ids()` instead.
	 * @see RCP_Discounts::get_membership_level_ids()
	 *
	 * @param  int $discount_id ID of the discount.
	 *
	 * @access  public
	 * @since   1.6
	 * @return  int
	 */
	public function get_subscription_id( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.0', 'RCP_Discount::get_membership_level_ids' );

		$ids = $this->get_membership_level_ids( $discount_id );

		if ( empty( $ids ) || ! isset( $ids[0] ) ) {
			return 0;
		}

		return $ids[0];

	}


	/**
	 * Get the associated membership level(s) for a discount.
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::get_membership_level_ids()
	 * @see        RCP_Discount::get_membership_level_ids()
	 *
	 * @param int $discount_id ID of the discount.
	 *
	 * @access public
	 * @since 3.0
	 * @return array
	 */
	public function get_membership_level_ids( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::get_membership_level_ids' );

		$discount = rcp_get_discount( $discount_id );

		if ( empty( $discount ) ) {
			return array();
		}

		return $discount->get_membership_level_ids();

	}


	/**
	 * Checks whether a discount code has a membership level associated
	 *
	 * @deprecated 3.0 Use `has_membership_level_ids()` instead.
	 * @see RCP_Discounts::has_membership_level_ids()
	 *
	 * @param  int $discount_id ID of the discount.
	 *
	 * @access  public
	 * @since   1.6
	 * @return  bool
	 */
	public function has_subscription_id( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.0', 'RCP_Discount::has_membership_level_ids' );

		return $this->get_subscription_id( $discount_id ) > 0;

	}


	/**
	 * Checks whether a discount code has at least one associated membership level.
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::has_membership_level_ids()
	 * @see        RCP_Discount::has_membership_level_ids()
	 *
	 * @param int $discount_id ID of the discount code.
	 *
	 * @access public
	 * @since 3.0
	 * @return bool
	 */
	public function has_membership_level_ids( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::has_membership_level_ids' );

		$membership_level_ids = $this->get_membership_level_ids( $discount_id );

		return ( count( $membership_level_ids ) > 0 );

	}


	/**
	 * Increase the use count of a discount by 1
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::increment_use_count()
	 * @see        RCP_Discount::increment_use_count()
	 *
	 * @param  int $discount_id ID of the discount.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  void
	 */
	public function increase_uses( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::increment_use_count' );

		$discount = rcp_get_discount( $discount_id );

		if ( ! empty( $discount ) ) {
			$discount->increment_use_count();
		}

	}

	/**
	 * Decrease the use count of a discount by 1
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::decrement_use_count()
	 * @see        RCP_Discount::decrement_use_count()
	 *
	 * @param  int $discount_id ID of the discount.
	 *
	 * @access  public
	 * @since   2.8
	 * @return  void
	 */
	public function decrease_uses( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::decrement_use_count' );

		$discount = rcp_get_discount( $discount_id );

		if ( ! empty( $discount ) ) {
			$discount->decrement_use_count();
		}

	}


	/**
	 * Get the expiration date of a discount
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::get_expiration()
	 * @see        RCP_Discount::get_expiration()
	 *
	 * @param  int $discount_id ID of the discount.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  string|false Expiration date, or false if it never expires.
	 */
	public function get_expiration( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::get_expiration' );

		$discount = rcp_get_discount( $discount_id );

		if ( ! empty( $discount ) ) {
			return $discount->get_expiration();
		}

		return false;

	}


	/**
	 * Get the discount type
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::get_unit()
	 * @see        RCP_Discount::get_unit()
	 *
	 * @param  int $discount_id ID of the discount.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  string|false
	 */
	public function get_type( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::get_unit' );

		$discount = rcp_get_discount( $discount_id );

		if ( ! empty( $discount ) ) {
			return $discount->get_unit();
		}

		return false;

	}


	/**
	 * Store a discount in the database
	 *
	 * @deprecated 3.3 In favour of rcp_add_discount()
	 * @see        rcp_add_discount()
	 *
	 * @param  array $args Arguments for the discount code.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  int|WP_Error|false ID of the newly created discount code or WP_Error/false on failure.
	 */
	public function insert( $args = array() ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_add_discount' );

		$defaults = array(
			'name'                 => '',
			'description'          => '',
			'amount'               => '0.00',
			'status'               => 'inactive',
			'unit'                 => '%',
			'code'                 => '',
			'expiration'           => '',
			'max_uses' 	           => 0,
			'use_count'            => '0',
			'membership_level_ids' => array(),
			'one_time'             => 0
		);

		$args = wp_parse_args( $args, $defaults );

		$discount_id = rcp_add_discount( $args );

		return $discount_id;
	}


	/**
	 * Update an existing discount
	 *
	 * @deprecated 3.3 In favour of rcp_update_discount()
	 * @see        rcp_update_discount()
	 *
	 * @param  int   $discount_id ID of the discount to update.
	 * @param  array $args        Array of fields/values to update.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  bool Whether or not the update was successful.
	 */
	public function update( $discount_id = 0, $args = array() ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_update_discount' );

		$result = rcp_update_discount( $discount_id, $args );

		if ( is_wp_error( $result ) ) {
			return false;
		}

		return $result;

	}


	/**
	 * Delete a discount code
	 *
	 * @deprecated 3.3 In favour of rcp_delete_discount()
	 * @see        rcp_delete_discount()
	 *
	 * @param  int $discount_id ID of the discount to delete.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  void
	 */
	public function delete( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_delete_discount' );

		rcp_delete_discount( $discount_id );

	}


	/**
	 * Check if a discount is maxed out
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::is_maxed_out()
	 * @see        RCP_Discount::is_maxed_out()
	 *
	 * @param  int $discount_id ID of the discount to check.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  bool
	 */
	public function is_maxed_out( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::is_maxed_out' );

		$discount = rcp_get_discount( $discount_id );

		if ( ! empty( $discount ) ) {
			return $discount->is_maxed_out();
		}

		return false;

	}


	/**
	 * Check if a discount is expired
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::is_expired()
	 * @see        RCP_Discount::is_expired()
	 *
	 * @param  int $discount_id ID of the discount to check.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  bool
	 */
	public function is_expired( $discount_id = 0 ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::is_expired' );

		$discount = rcp_get_discount( $discount_id );

		if ( ! empty( $discount ) ) {
			return $discount->is_expired();
		}

		return false;

	}


	/**
	 * Add a discount to a user's history
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::store_for_user()
	 * @see        RCP_Discount::store_for_user()
	 *
	 * @param  int    $user_id ID of the user to add the discount to.
	 * @param  string $discount_code Discount code to add.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  void
	 */
	public function add_to_user( $user_id = 0, $discount_code = '' ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::store_for_user' );

		$discount = rcp_get_discount_by( 'code', $discount_code );

		if ( empty( $discount ) ) {
			return;
		}

		$discount->store_for_user( $user_id );

	}

	/**
	 * Remove a discount from a user's history
	 *
	 * @deprecated 3.3 In favour of RCP_Discount::remove_from_user()
	 * @see        RCP_Discount::remove_from_user()
	 *
	 * @param  int    $user_id ID of the user to remove the discount from.
	 * @param  string $discount_code Discount code to remove.
	 *
	 * @access  public
	 * @since   2.8
	 * @return  bool Whether or not the discount was removed.
	 */
	public function remove_from_user( $user_id, $discount_code = '' ) {

		_deprecated_function( __METHOD__, '3.3', 'RCP_Discount::remove_from_user' );

		$discount = rcp_get_discount_by( 'code', $discount_code );

		if ( empty( $discount ) ) {
			return false;
		}

		return $discount->remove_from_user( $user_id );

	}


	/**
	 * Check if a user has used a discount
	 *
	 * @deprecated 3.3 In favour of rcp_user_has_used_discount()
	 * @see        rcp_user_has_used_discount()
	 *
	 * @param  int    $user_id ID of the user to check.
	 * @param  string $discount_code Discount code to check.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  bool
	 */
	public function user_has_used( $user_id = 0, $discount_code = '' ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_user_has_used_discount' );

		return rcp_user_has_used_discount( $user_id, $discount_code );

	}


	/**
	 * Format the discount code
	 *
	 * @deprecated 3.3 In favour of rcp_discount_sign_filter()
	 * @see        rcp_discount_sign_filter()
	 *
	 * @param int|float $amount Discount amount.
	 * @param string    $type   Type of discount - either '%' or 'flat'.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  string
	 */
	public function format_discount( $amount = '', $type = '' ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_discount_sign_filter' );

		return rcp_discount_sign_filter( $amount, $type );

	}


	/**
	 * Calculate the discounted price
	 *
	 * @deprecated 3.3 In favour of rcp_get_discounted_price()
	 * @see        rcp_get_discounted_price()
	 *
	 * @param int|float $base_price      Full price without the discount.
	 * @param int|float $discount_amount Amount of the discount code.
	 * @param string    $type            Type of discount - either '%' or 'flat'.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  int|float
	 */
	public function calc_discounted_price( $base_price = '', $discount_amount = '', $type = '%' ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_get_discounted_price' );

		return rcp_get_discounted_price( $base_price, $discount_amount, $type, false );

	}


	/**
	 * Sanitizes the discount amount
	 *
	 * @deprecated 3.3 In favour of rcp_sanitize_discount_amount()
	 * @see        rcp_sanitize_discount_amount()
	 *
	 * @param int|float $amount The discount amount.
	 * @param string    $type   The discount type - either '%' or 'flat'.
	 *
	 * @access public
	 * @since  2.4.9
	 * @return mixed|array|WP_Error
	 */
	public function format_amount( $amount, $type ) {

		_deprecated_function( __METHOD__, '3.3', 'rcp_sanitize_discount_amount' );

		return rcp_sanitize_discount_amount( $amount, $type );
	}


}
