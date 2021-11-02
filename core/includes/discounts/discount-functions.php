<?php
/**
 * Discount Functions
 *
 * Functions for getting non-member specific info about discount codes.
 *
 * @package     Restrict Content Pro
 * @subpackage  Discount Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use \RCP\Database\Queries\Discount;

/**
 * Get a single discount by ID
 *
 * @param int $discount_id
 *
 * @since 3.3
 * @return RCP_Discount|false
 */
function rcp_get_discount( $discount_id ) {

	$query = new Discount();

	return $query->get_item( $discount_id );

}

/**
 * Get a single discount by a column/value pair.
 *
 * @param string $column Column name.
 * @param mixed  $value  Column value.
 *
 * @since 3.3
 * @return RCP_Discount|false
 */
function rcp_get_discount_by( $column, $value ) {

	if ( 'code' == $column ) {
		$value = strtolower( $value );
	}

	$query = new Discount();

	return $query->get_item_by( $column, $value );

}

/**
 * Retrieves all discount codes
 *
 * @param string|array $query                   {
 *                                              Optional. Array or query string of discount query parameters.
 *                                              Default empty.
 *
 * @type int           $id                      A discount ID to only return that discount. Default empty.
 * @type array         $id__in                  Array of discount IDs to include. Default empty.
 * @type array         $id__not_in              Array of discount IDs to exclude. Default empty.
 * @type string        $name                    Specific discount name to retrieve. Default empty.
 * @type string        $amount                  Retrieve discounts for this amount. Default empty.
 * @type string        $unit                    Retrieve discounts with this unit. Default empty.
 * @type string        $code                    Specific discount code to retrieve. Default empty.
 * @type string        $status                  Status to filter by. Default empty.
 * @type int           $one_time                Set to `1` to only retrieve one-time discounts. Set to `0` to only
 *                                              retrieve recurring discounts. Omit to retrieve all. Default empty.
 * @type array         $date_query              Query all datetime columns together. See WP_Date_Query.
 * @type array         $expiration_query        Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type array         $date_created_query      Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type array         $date_modified_query     Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type bool          $count                   Whether to return a discount count (true) or array of discount
 *                                              objects. Default false.
 * @type string        $fields                  Item fields to return. Accepts any column known names
 *                                              or empty (returns an array of complete discount objects). Default
 *                                              empty.
 * @type int           $number                  Limit number of discount to retrieve. Default 100.
 * @type int           $offset                  Number of discount to offset the query. Used to build LIMIT
 *                                              clause. Default 0.
 * @type bool          $no_found_rows           Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
 * @type string|array  $orderby                 Accepts 'id', 'name', 'code', 'use_count', 'max_uses', 'status',
 *                                              'expiration', 'date_created', and 'date_modified'. Also accepts
 *                                              false, an empty array, or 'none' to disable `ORDER BY` clause.
 *                                              Default 'id'.
 * @type string        $order                   How to order results. Accepts 'ASC', 'DESC'. Default 'DESC'.
 * @type string        $search                  Search term(s) to retrieve matching discounts for. Default empty.
 * @type bool          $update_cache            Whether to prime the cache for found discounts. Default false.
 * }
 *
 * @return RCP_Discount[] Array of RCP_Discount objects.
 */
function rcp_get_discounts( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'number' => 20
	) );

	// Backwards compat: if setting status to "all", unset it.
	if ( ! empty( $args['status'] ) && 'all' == $args['status'] ) {
		unset( $args['status'] );
	}

	$query = new Discount();

	return $query->query( $args );

}

/**
 * Count the number of discounts
 *
 * @param string|array $query                   {
 *                                              Optional. Array or query string of discount query parameters.
 *                                              Default empty.
 *
 * @type int           $id                      A discount ID to only return that discount. Default empty.
 * @type array         $id__in                  Array of discount IDs to include. Default empty.
 * @type array         $id__not_in              Array of discount IDs to exclude. Default empty.
 * @type string        $name                    Specific discount name to retrieve. Default empty.
 * @type string        $amount                  Retrieve discounts for this amount. Default empty.
 * @type string        $unit                    Retrieve discounts with this unit. Default empty.
 * @type string        $code                    Specific discount code to retrieve. Default empty.
 * @type string        $status                  Status to filter by. Default empty.
 * @type int           $one_time                Set to `1` to only retrieve one-time discounts. Set to `0` to only
 *                                              retrieve recurring discounts. Omit to retrieve all. Default empty.
 * @type array         $date_query              Query all datetime columns together. See WP_Date_Query.
 * @type array         $expiration_query        Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type array         $date_created_query      Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type array         $date_modified_query     Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type string        $search                  Search term(s) to retrieve matching discounts for. Default empty.
 * @type bool          $update_cache            Whether to prime the cache for found discounts. Default false.
 * }
 *
 * @since 3.3
 * @return int
 */
function rcp_count_discounts( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'count' => true
	) );

	// Backwards compat: if setting status to "all", unset it.
	if ( ! empty( $args['status'] ) && 'all' == $args['status'] ) {
		unset( $args['status'] );
	}

	$query = new Discount( $args );

	return absint( $query->found_items );

}

/**
 * Add a new discount
 *
 * @param array $args        {
 *
 * @type string $name        Required. Name of the discount.
 * @type string $description Optional. Discount description.
 * }
 *
 * @since 3.3
 * @return int|WP_Error ID of the new discount on success, WP_Error on failure.
 */
function rcp_add_discount( $args ) {

	$args = wp_parse_args( $args, array(
		'name'                 => '',
		'description'          => '',
		'amount'               => '0.00',
		'unit'                 => '%',
		'code'                 => '',
		'use_count'            => 0,
		'max_uses'             => 0,
		'status'               => 'disabled',
		'expiration'           => null,
		'membership_level_ids' => array(),
		'one_time'             => 0
	) );

	// Backwards compat: convert `subscription_id` to `membership_level_ids`.
	if ( ! empty( $args['subscription_id'] ) && empty( $args['membership_level_ids'] ) ) {
		$args['membership_level_ids'] = array( absint( $args['subscription_id'] ) );
	}

	// If `membership_level_ids` is empty, let's set it to an empty string.
	if ( empty( $args['membership_level_ids'] ) ) {
		$args['membership_level_ids'] = '';
	}

	$args['membership_level_ids'] = maybe_serialize( $args['membership_level_ids'] );

	// Ensure `unit` is either `%` or `flat`.
	if ( ! in_array( $args['unit'], array( '%', 'flat' ) ) ) {
		$args['unit'] = 'flat';
	}

	// Sanitize amount - it is required and should be a number.
	$amount = rcp_sanitize_discount_amount( $args['amount'], $args['unit'] );
	if ( is_wp_error( $amount ) ) {
		return $amount;
	} else {
		$args['amount'] = $amount;
	}

	// Status should either be "active" or "disabled".
	$args['status'] = in_array( $args['status'], array( 'active', 'disabled' ) ) ? $args['status'] : 'disabled';

	// Code is required.
	if ( empty( $args['code'] ) ) {
		return new WP_Error( 'code_missing', __( 'Please enter a discount code.', 'rcp' ) );
	}

	// Code already exists -- they must be unique.
	if ( rcp_get_discount_by( 'code', $args['code'] ) ) {
		return new WP_Error( 'discount_code_already_exists', sprintf( __( 'A discount with the code %s already exists.', 'rcp' ), '<code>' . esc_html( $args['code'] ) . '</code>' ) );
	}

	// Validate percentages. They must be between 0 and 100.
	if ( '%' == $args['unit'] && ! filter_var( $args['amount'], FILTER_VALIDATE_INT, array(
			'options' => array(
				'min_range' => 1,
				'max_range' => 100
			)
		) ) ) {
		return new WP_Error( 'invalid_percent', __( 'Percentage discounts must be whole numbers between 1 and 100.', 'rcp' ) );
	}

	$args['amount'] = filter_var( $args['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );

	/**
	 * Triggers before a discount code is added.
	 *
	 * @param array $args Arguments used to create the discount.
	 */
	do_action( 'rcp_pre_add_discount', $args );

	$query       = new Discount();
	$discount_id = $query->add_item( $args );

	if ( ! $discount_id ) {
		return new WP_Error( 'insert_failure', __( 'Failed to insert new discount code.', 'rcp' ) );
	}

	/**
	 * Triggers after a new discount is added.
	 *
	 * @param array $args        Arguments used to create the discount.
	 * @param int   $discount_id ID of the newly created discount.
	 */
	do_action( 'rcp_add_discount', $args, $discount_id );

	return $discount_id;

}

/**
 * Update a discount code
 *
 * @param int   $discount_id ID of the discount to update.
 * @param array $args        Data to update.
 *
 * @since 3.3
 * @return true|WP_Error
 */
function rcp_update_discount( $discount_id, $args ) {

	$discount = rcp_get_discount( $discount_id );

	if ( empty( $discount ) ) {
		return new WP_Error( 'invalid_discount', __( 'Invalid discount ID.', 'rcp' ) );
	}

	// Codes must be lowercase.
	if ( ! empty( $args['code'] ) ) {
		$args['code'] = strtolower( $args['code'] );
	}

	// Unit must be "flat" or "%".
	if ( ! empty( $args['unit'] ) && ! in_array( $args['unit'], array( '%', 'flat' ) ) ) {
		$args['unit'] = '%';
	}

	// Sanitize amount - it is required and should be a number.
	if ( ! empty( $args['amount'] ) ) {
		$unit   = ! empty( $args['unit'] ) ? $args['unit'] : $discount->get_unit();
		$amount = rcp_sanitize_discount_amount( $args['amount'], $unit );
		if ( is_wp_error( $amount ) ) {
			return $amount;
		} else {
			$args['amount'] = $amount;
		}
	}

	if ( isset( $args['membership_level_ids'] ) ) {
		$args['membership_level_ids'] = maybe_serialize( $args['membership_level_ids'] );
	}

	/**
	 * Triggers before a discount is updated.
	 *
	 * @param int   $discount_id ID of the discount being updated.
	 * @param array $args        Array of arguments to update.
	 */
	do_action( 'rcp_pre_edit_discount', absint( $discount_id ), $args );

	$query = new Discount();

	$updated = $query->update_item( $discount_id, $args );

	/**
	 * Triggers after the discount is updated.
	 *
	 * @param int   $discount_id ID of the discount that was updated.
	 * @param array $args        Array of data that was updated.
	 */
	do_action( 'rcp_edit_discount', absint( $discount_id ), $args );

	if ( ! $updated ) {
		return new WP_Error( 'update_failed', __( 'Failed to update discount code.', 'rcp' ) );
	}

	return true;

}

/**
 * Delete a discount code
 *
 * @param int $discount_id ID of the discount to delete.
 *
 * @since 3.3
 * @return bool
 */
function rcp_delete_discount( $discount_id ) {

	/**
	 * Triggers before the discount is deleted.
	 *
	 * @param int $discount_id ID of the discount being deleted.
	 */
	do_action( 'rcp_delete_discount', $discount_id );

	$query = new Discount();

	return $query->delete_item( $discount_id );

}

/**
 * Check if we have any active discounts
 *
 * @return bool
 */
function rcp_has_discounts() {

	$discounts = rcp_count_discounts( array(
		'status' => 'active'
	) );

	return ! empty( $discounts );
}

/**
 * Returns the DB object for a given discount code.
 *
 * @deprecated 3.3 In favour of `rcp_get_discount()`
 * @see        rcp_get_discount()
 *
 * @param int $id The ID number of the discount to retrieve data for.
 *
 * @return RCP_Discount|false
 */
function rcp_get_discount_details( $id ) {
	_deprecated_function( __FUNCTION__, '3.3', 'rcp_get_discount' );

	return rcp_get_discount( $id );
}

/**
 * Returns a discount code based on the code provided.
 *
 * @param string $code The discount code to retrieve all information for.
 *
 * @return RCP_Discount|false
 */
function rcp_get_discount_details_by_code( $code ) {
	return rcp_get_discount_by( 'code', $code );
}

/**
 * Check whether a given discount code is valid.
 *
 * @param string $code            The discount code to validate.
 * @param int    $subscription_id ID of the membership level you want to use the code for.
 *
 * @return bool
 */
function rcp_validate_discount( $code, $subscription_id = 0 ) {

	$discount = rcp_get_discount_details_by_code( $code );

	if ( ! empty( $discount ) ) {
		return $discount->is_valid( $subscription_id );
	}

	return apply_filters( 'rcp_is_discount_valid', false, $discount, $subscription_id );
}

/**
 * Get the status of a discount code
 *
 * @param int $code_id The discount code ID.
 *
 * @return string|bool Status name on success, false on failure.
 */
function rcp_get_discount_status( $code_id ) {

	$discount = rcp_get_discount( $code_id );

	if ( ! empty( $discount ) ) {
		return $discount->get_status();
	}

	return false;
}

/**
 * Checks whether a discount code has any uses left.
 *
 * @param int $code_id The ID of the discount code to check.
 *
 * @return bool True if uses left, false otherwise.
 */
function rcp_discount_has_uses_left( $code_id ) {

	$discount = rcp_get_discount( $code_id );

	if ( ! empty( $discount ) ) {
		return ! $discount->is_maxed_out();
	}

	return false;

}

/**
 * Checks whether a discount code has not expired.
 *
 * @param int $code_id The ID of the discount code to check.
 *
 * @return bool True if not expired, false if expired.
 */
function rcp_is_discount_not_expired( $code_id ) {

	$discount = rcp_get_discount( $code_id );

	if ( ! empty( $discount ) ) {
		return ! $discount->is_expired();
	}

	return false;

}

/**
 * Calculates a subscription price after applying a discount.
 *
 * @param float  $base_price The original subscription price.
 * @param float  $amount     The discount amount.
 * @param string $type       The kind of discount, either '%' or 'flat'.
 * @param bool   $format     Whether or not to run `number_format()` on the result.
 *
 * @return string
 */
function rcp_get_discounted_price( $base_price, $amount, $type, $format = true ) {

	if( $type == '%' ) {
		$discounted_price = $base_price - ( $base_price * ( $amount / 100 ) );
	} elseif($type == 'flat') {
		$discounted_price = $base_price - $amount;
	}

	if ( ! $format ) {
		return $discounted_price;
	}

	return number_format( (float) $discounted_price, 2 );
}

/**
 * Stores a discount code in a user's history.
 *
 * @param string $code            The discount code to store.
 * @param int    $user_id         The ID of the user to store the discount for.
 * @param object $discount_object The object containing all info about the discount.
 *
 * @return void
 */
function rcp_store_discount_use_for_user( $code, $user_id, $discount_object ) {

	$discount = rcp_get_discount_by( 'code', $code );

	if ( empty( $discount ) ) {
		return;
	}

	$discount->store_for_user( $user_id );

}

/**
 * Checks whether a user has used a particular discount code.
 * This is used to prevent users from spamming discount codes.
 *
 * @param int    $user_id The ID of the user to check.
 * @param string $code    The discount code to check against the user ID.
 *
 * @return bool
 */
function rcp_user_has_used_discount( $user_id, $code ) {

	$ret = false;

	if( ! empty( $code ) ) {

		$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );

		if( ! empty( $user_discounts ) ) {
			if( in_array( $code, $user_discounts ) ) {
				$ret = true;
			}
		}
	}

	return apply_filters( 'rcp_user_has_used_discount', $ret, $user_id, $code );
}

/**
 * Increase the usage count of a discount code.
 *
 * @param int $code_id The ID of the discount.
 *
 * @return void
 */
function rcp_increase_code_use( $code_id ) {

	$discount = rcp_get_discount( $code_id );

	if ( ! empty( $discount ) ) {
		$discount->increment_use_count();
	}
}

/**
 * Returns the number of times a discount code has been used.
 *
 * @param int|string $code The ID or code of the discount.
 *
 * @return int|string The number of times the discount code has been used or the string 'None'.
 */
function rcp_count_discount_code_uses( $code ) {
	if( is_int( $code ) ) {
		// discount ID has been given
		$discount = rcp_get_discount( absint( $code ) );
	} else {
		// discount code has been given
		$discount = rcp_get_discount_by( 'code', $code );
	}

	$count = ! empty( $discount ) ? $discount->get_use_count() : 0;

	return $count ? $count : __( 'None', 'rcp' );
}

/**
 * Returns a formatted discount amount with a '%' sign appended (percentage-based) or with the
 * currency sign added to the amount (flat discount rate).
 *
 * @param float  $amount Discount amount.
 * @param string $type   Discount amount - either '%' or 'flat'.
 *
 * @return string
 */
function rcp_discount_sign_filter( $amount, $type ) {
	$discount = '';
	if( $type == '%' ) {
		$discount = $amount . '%';
	} elseif( $type == 'flat' ) {
		$discount = rcp_currency_filter( $amount );
	}
	return $discount;
}

/**
 * Sanitizes a discount amount
 *
 * @param int|float $amount The discount amount.
 * @param string    $unit   The discount unit - either `%` or `flat`.
 *
 * @since 3.3
 * @return float|int|WP_Error
 */
function rcp_sanitize_discount_amount( $amount, $unit ) {

	if ( empty( $amount ) || ! is_numeric( $amount ) ) {
		return new WP_Error( 'amount_missing', __( 'Please enter a discount amount containing numbers only.', 'rcp' ) );
	}

	if ( ! isset( $unit ) || ! in_array( $unit, array( '%', 'flat' ) ) ) {
		$unit = 'flat';
	}

	if ( '%' === $unit && ! filter_var( $amount, FILTER_VALIDATE_INT, array(
			'options' => array(
				'min_range' => 1,
				'max_range' => 100
			)
		) ) ) {
		return new WP_Error( 'invalid_percent', __( 'Percentage discounts must be whole numbers between 1 and 100.', 'rcp' ) );
	}

	return filter_var( $amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );

}
