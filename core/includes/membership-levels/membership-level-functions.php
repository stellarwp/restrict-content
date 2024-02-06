<?php
/**
 * Membership Level Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 */

use RCP\Membership_Level;

/**
 * Retrieves a membership level by its ID
 *
 * @param int $level_id
 *
 * @since 3.4
 * @return RCP\Membership_Level|false
 */
function rcp_get_membership_level( $level_id ) {

	$query            = new RCP\Database\Queries\Membership_Level();
	$membership_level = $query->get_item( $level_id );

	/**
	 * Filters the membership level being returned.
	 *
	 * @param RCP\Membership_Level $membership_level
	 */
	return apply_filters( 'rcp_get_level', $membership_level );

}

/**
 * Retrieves a membership level by a column/value pair
 *
 * @param string $column_name  Name of the column to search in.
 * @param mixed  $column_value Value of the row.
 *
 * @since 3.4
 * @return RCP\Membership_Level|false
 */
function rcp_get_membership_level_by( $column_name, $column_value ) {

	$query            = new RCP\Database\Queries\Membership_Level();
	$membership_level = $query->get_item_by( $column_name, $column_value );

	/**
	 * Filters the membership level being returned.
	 *
	 * @param RCP\Membership_Level $membership_level
	 */
	return apply_filters( 'rcp_get_level', $membership_level );

}

/**
 * Queries for membership levels
 *
 * @param array       $args                {
 *                                         Optional. Array of query parameters. Default empty.
 *
 * @type int          $id                  Filter by membership level ID. Default empty.
 * @type array        $id__in              Array of membership level IDs to include. Default empty.
 * @type array        $id__not_in          Array of membership level IDs to exclude. Default empty.
 * @type string       $name                Filter by name. Default empty.
 * @type int          $duration            Filter by duration. Default empty.
 * @type string       $duration_unit       Filter by duration unit. Default empty.
 * @type int          $trial_duration      Filter by trial duration. Default empty.
 * @type string       $trial_duration_unit Filter by trial duration unit. Default empty.
 * @type string|float $price               Filter by price. Default empty.
 * @type string|float $fee                 Filter by signup fee. Default empty.
 * @type int          $maximum_renewals    Filter by maximum renewals. Default empty.
 * @type string       $after_final_payment Filter by after final payment action. Default empty.
 * @type int          $list_order          Filter by list order. Default empty.
 * @type int          $level               Filter by access level. Default empty.
 * @type string       $status              Filter by status. Default empty.
 * @type array        $status__not_in      Array of statuses to exclude. Default empty.
 * @type string       $role                Filter by user role. Default empty.
 * @type array        $role__in            Array of roles to include. Default empty.
 * @type array        $role__not_in        Array of roles to exclude. Default empty.
 * @type array        $date_created_query  Date query for filtering by date created. See WP_Date_Query. Default empty.
 * @type bool         $count               Whether to return the count of records (true) or membership level objects
 *                                         (false). Default false.
 * @type int          $number              Maximum number of results to return. Default 20.
 * @type int          $offset              Number of records to offset the query. Used to build LIMIT clause. Default 0.
 * @type bool         $no_found_rows       Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
 * @type string|array $orderby             Accepts `id`, `name`, `duration`, `trial_duration`, `maximum_renewals`,
 *                                         `list_order`, `level`, `status`, `date_created`, and `date_modified`.
 *                                         Also accepts false, an empty array, or `none` to disable `ORDER BY` clause.
 *                                         Default `id`.
 * @type string       $order               How to order results. Accepts `ASC` and `DESC`. Default `DESC`.
 * @type string       $search              Search term(s). Default empty.
 * @type bool         $update_cache        Whether to prime the cache. Default false.
 * }
 *
 * @since 3.4
 * @return RCP\Membership_Level[] Array of RCP\Membership_Level objects.
 */
function rcp_get_membership_levels( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'number' => apply_filters( 'rcp_get_membership_levels_default_number', 20 ),
		'orderby' => apply_filters( 'rcp_get_membership_levels_default_orderby', 'list_order' ),
		'order' => apply_filters( 'rcp_get_membership_levels_default_order', 'ASC')
	) );

	$query             = new RCP\Database\Queries\Membership_Level();
	$membership_levels = $query->query( $args );

	/**
	 * Filters the membership levels.
	 *
	 * @param RCP\Membership_Level[] $membership_levels
	 */
	return apply_filters( 'rcp_get_levels', $membership_levels );

}

/**
 * Counts the membership level records
 *
 * @param array $args Array of query arguments to override the defaults.
 *
 * @see   rcp_get_membership_levels() for accepted arguments.
 *
 * @since 3.4
 * @return int
 */
function rcp_count_membership_levels( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'count' => true
	) );

	$query = new RCP\Database\Queries\Membership_Level( $args );

	return absint( $query->found_items );

}

/**
 * Adds a new membership level
 *
 * @param array    $args                {
 *                                      Array of membership level data.
 *
 * @type string    $name                Name of the membership level.
 * @type string    $description         Optional. Membership level description.
 * @type int       $duration            Duration of the billing cycle. Default `0`.
 * @type string    $duration_unit       Billing cycle duration unit. One of: `day`, `month`, `year`. Default `month`.
 * @type int       $trial_duration      Duration of the free trial. Default `0`, which indicates no trial.
 * @type string    $trial_duration_unit Trial duration unit. One of: `day`, `month`, `year`. Default `day`.
 * @type int|float $price               Price of the membership level. Default `0` (free).
 * @type int|float $fee                 Fee to charge at signup. Can be a negative number to indicate a discount.
 *                                      Default `0`.
 * @type int       $maximum_renewals    Maximum number of renewals to process. Default `0`, which indicates no limit.
 * @type string    $after_final_payment Action to take after the final payment is received. One of: `lifetime`,
 *                                      `expire_immediately`, or `expire_term_end`. Default empty.
 * @type int       $list_order          Order the level should appear in the list. Lower numbers are shown first.
 *                                      Default `0`.
 * @type int       $level               Access level to grant. Number from 0 to 10. Default `0`.
 * @type string    $status              Status of the membership level. Either `active` or `inactive`. Default
 *                                      `inactive`.
 * @type string    $role                User role to grant to members. Default `subscriber`.
 * }
 *
 * @since 3.4
 * @return int|WP_Error ID of the new membership level on success, WP_Error on failure.
 */
function rcp_add_membership_level( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'name'                => '',
		'description'         => '',
		'duration'            => 0,
		'duration_unit'       => 'month',
		'trial_duration'      => 0,
		'trial_duration_unit' => 'day',
		'price'               => 0,
		'fee'                 => 0,
		'maximum_renewals'    => 0,
		'after_final_payment' => '',
		'list_order'          => 0,
		'level'               => 0,
		'status'              => 'inactive',
		'role'                => 'subscriber'
	) );

	/**
	 * Triggers before the membership level is inserted into the database.
	 *
	 * @param array $args Array of arguments to use in creation.
	 */
	do_action( 'rcp_pre_add_subscription', $args );

	/**
	 * Filters the arguments used to create the membership level.
	 *
	 * @param array $args
	 */
	$args = apply_filters( 'rcp_add_subscription_args', $args );

	foreach ( array( 'price', 'fee' ) as $key ) {
		if ( empty( $args[ $key ] ) ) {
			$args[ $key ] = '0';
		}
		$args[ $key ] = str_replace( ',', '', $args[ $key ] );
	}

	/*
	 * Sanitize payment plan settings.
	 * If `maximum_renewals` is `0` then there is no payment plan.
	 */
	if ( empty( $args['maximum_renewals'] ) ) {
		$args['maximum_renewals']    = 0;
		$args['after_final_payment'] = '';
	}

	if ( $args['duration'] == 0 && $args['maximum_renewals'] > 0 ) {
		rcp_log(
			sprintf(
				'Failed inserting membership level: invalid duration ( %s ) and maximum_renewal ( %s ) combination',
				$args['duration'],
				$args['maximum_renewals']
			),
			true
		);

		return new WP_Error('invalid_maximum_renewals', __( 'Invalid Maximum Renewals: A one-time payment cannot include a payment plan.', 'rcp'  ) );
	}

	/*
	 * Validate price value.
	 */
	if ( false === filter_var( $args['price'], FILTER_VALIDATE_FLOAT ) || $args['price'] < 0 ) {
		rcp_log( sprintf( 'Failed inserting membership level: invalid price ( %s ).', $args['price'] ), true );

		return new WP_Error( 'invalid_level_price', __( 'Invalid price: the membership level price must be a valid positive number.', 'rcp' ) );
	}

	/*
	 * Validate signup fee value.
	 */
	if ( false === filter_var( $args['fee'], FILTER_VALIDATE_FLOAT ) ) {
		rcp_log( sprintf( 'Failed inserting membership level: invalid fee ( %s ).', $args['fee'] ), true );

		return new WP_Error( 'invalid_level_fee', __( 'Invalid fee: the membership level fee must be a valid number.', 'rcp' ) );
	}

	/*
	 * Validate the trial settings.
	 * If a trial is enabled, the level's regular price and duration must be > 0.
	 */
	if ( $args['trial_duration'] > 0 ) {
		if ( $args['price'] <= 0 || $args['duration'] <= 0 ) {
			rcp_log( sprintf( 'Failed inserting membership level: invalid settings for free trial. Price: %f; Duration: %d', $args['price'], $args['duration'] ), true );

			return new WP_Error( 'invalid_level_trial', __( 'Invalid trial: a membership level with a trial must have a price and duration greater than zero.', 'rcp' ) );
		}
	}

	$query               = new RCP\Database\Queries\Membership_Level();
	$membership_level_id = $query->add_item( $args );

	if ( ! $membership_level_id ) {
		rcp_log( sprintf( 'Failed inserting new membership level into database. Args: %s', var_export( $args, true ) ), true );

		return new WP_Error( 'level_not_added', __( 'An unexpected error occurred while trying to add the membership level.', 'rcp' ) );
	}

	/**
	 * Triggers after a new membership level is successfully added.
	 *
	 * @param int   $membership_level_id ID of the newly created membership level.
	 * @param array $args                Arguments used in creation.
	 */
	do_action( 'rcp_add_subscription', $membership_level_id, $args );

	rcp_log( sprintf( 'Successfully added new membership level #%d. Args: %s', $membership_level_id, var_export( $args, true ) ) );

	return absint( $membership_level_id );

}

/**
 * Updates an existing membership level
 *
 * @param int   $level_id ID of the membership level to update.
 * @param array $args     Fields and values to update.
 *
 * @since 3.4
 * @return true|WP_Error True if the update was successful, WP_Error on failure.
 */
function rcp_update_membership_level( $level_id, $args = array() ) {

	$level = rcp_get_membership_level( $level_id );

	if ( ! $level instanceof RCP\Membership_Level ) {
		return new WP_Error( 'invalid_level', __( 'Invalid membership level.', 'rcp' ) );
	}

	$args = wp_parse_args( $args, $level->export_vars() );

	/**
	 * Triggers before a membership level is updated.
	 *
	 * @param int   $level_id ID of the membership level being edited.
	 * @param array $args     New membership level settings.
	 */
	do_action( 'rcp_pre_edit_subscription_level', $level_id, $args );

	foreach ( array( 'price', 'fee' ) as $key ) {
		if ( empty( $args[ $key ] ) ) {
			$args[ $key ] = '0';
		}
		$args[ $key ] = str_replace( ',', '', $args[ $key ] );
	}

	/*
	 * Sanitize payment plan settings.
	 * If `maximum_renewals` is `0` then there is no payment plan.
	 */
	if ( empty( $args['maximum_renewals'] ) ) {
		$args['maximum_renewals']    = 0;
		$args['after_final_payment'] = '';
	}

	if ( $args['duration'] == 0 && $args['maximum_renewals'] > 0 ) {
		rcp_log(
			sprintf(
				'Failed updating membership level: invalid duration ( %s ) and maximum_renewal ( %s ) combination',
				$args['duration'],
				$args['maximum_renewals']
			),
			true
		);

		return new WP_Error('invalid_maximum_renewals', __( 'Invalid Maximum Renewals: A one-time payment cannot include a payment plan.', 'rcp' ) );
	}

	/*
	 * Validate price value.
	 */
	if ( false === filter_var( $args['price'], FILTER_VALIDATE_FLOAT ) || $args['price'] < 0 ) {
		rcp_log( sprintf( 'Failed updating membership level #%d: invalid price ( %s ).', $level_id, $args['price'] ), true );

		return new WP_Error( 'invalid_level_price', __( 'Invalid price: the membership level price must be a valid positive number.', 'rcp' ) );
	}

	/*
	 * Validate signup fee value.
	 */
	if ( false === filter_var( $args['fee'], FILTER_VALIDATE_FLOAT ) ) {
		rcp_log( sprintf( 'Failed updating membership level #%d: invalid fee ( %s ).', $level_id, $args['fee'] ), true );

		return new WP_Error( 'invalid_level_fee', __( 'Invalid fee: the membership level fee must be a valid number.', 'rcp' ) );
	}

	/*
	 * Validate the trial settings.
	 * If a trial is enabled, the level's regular price and duration must be > 0.
	 */
	if ( $args['trial_duration'] > 0 ) {
		if ( $args['price'] <= 0 || $args['duration'] <= 0 ) {
			rcp_log( sprintf( 'Failed updating membership level #%d: invalid settings for free trial. Price: %f; Duration: %d', $level_id, $args['price'], $args['duration'] ), true );

			return new WP_Error( 'invalid_level_trial', __( 'Invalid trial: a membership level with a trial must have a price and duration greater than zero.', 'rcp' ) );
		}
	}

	$query   = new RCP\Database\Queries\Membership_Level();
	$updated = $query->update_item( $level_id, $args );

	/**
	 * Triggers after the membership level has been updated.
	 *
	 * @param int   $level_id ID of the level that was edited.
	 * @param array $args     New membership level settings.
	 */
	do_action( 'rcp_edit_subscription_level', $level_id, $args );

	if ( ! $updated ) {
		rcp_log( sprintf( 'Failed updating membership level #%d. Args: %s', absint( $level_id ), var_export( $args, true ) ), true );

		return new WP_Error( 'level_not_added', __( 'An unexpected error occurred while trying to update the membership level.', 'rcp' ) );
	}

	rcp_log( sprintf( 'Successfully updated membership level #%d. Args: %s', absint( $level_id ), var_export( $args, true ) ) );

	return true;

}

/**
 * Permanently deletes a membership level
 *
 * @todo  Also delete membership level meta.
 *
 * @param int $level_id ID of the membership level to delete.
 *
 * @since 3.4
 * @return bool
 */
function rcp_delete_membership_level( $level_id ) {

	$query   = new RCP\Database\Queries\Membership_Level();
	$deleted = $query->delete_item( $level_id );

	if ( ! $deleted ) {
		return false;
	}

	/**
	 * Triggers after a membership level has been deleted.
	 *
	 * @param int $level_id ID of the level that was deleted.
	 */
	do_action( 'rcp_remove_level', absint( $level_id ) );

	rcp_log( sprintf( 'Deleted membership level ID #%d.', $level_id ) );

	return true;

}

/**
 * Gets the name of a specified membership level
 *
 * @param int $id The ID of the membership level to retrieve
 *
 * @return string Name of membership level, or error message on failure
 */
function rcp_get_subscription_name( $id ) {

	$level = rcp_get_membership_level( $id );

	if ( ! $level instanceof RCP\Membership_Level ) {
		return '';
	}

	return $level->get_name();
}

/**
 * Gets the duration of a membership
 *
 * @param int $id The ID of the membership level to retrieve
 *
 * @return object|false Length an unit (m/d/y) of membership, or false on failure.
 */
function rcp_get_subscription_length( $id ) {

	$level = rcp_get_membership_level( $id );

	if ( ! $level instanceof RCP\Membership_Level ) {
		return false;
	}

	$details                = new stdClass();
	$details->duration      = $level->get_duration();
	$details->duration_unit = $level->get_duration_unit();

	return $details;
}

/**
 * Gets the price of a membership level
 *
 * @param int $id The ID of the membership level to retrieve
 *
 * @return float|false Price of membership level, false on failure
 */
function rcp_get_subscription_price( $id ) {

	$level = rcp_get_membership_level( $id );

	if ( ! $level instanceof RCP\Membership_Level ) {
		return false;
	}

	return $level->get_price();
}

/**
 * Gets the signup fee of a membership level
 *
 * @param int $id The ID of the membership level to retrieve
 *
 * @return float|false Signup fee if any, false otherwise
 */
function rcp_get_subscription_fee( $id ) {

	$level = rcp_get_membership_level( $id );

	if ( ! $level instanceof RCP\Membership_Level ) {
		return false;
	}

	return $level->get_fee();

}

/**
 * Gets the description of a membership level
 *
 * @param int $id The ID of the membership level to retrieve
 *
 * @return string Level description.
 */
function rcp_get_subscription_description( $id ) {

	$level = rcp_get_membership_level( $id );

	if ( ! $level instanceof RCP\Membership_Level ) {
		return '';
	}

	return $level->get_description();
}

/**
 * Gets the access level of a membership package
 *
 * @param int $id The ID of the membership level to retrieve
 *
 * @return int|false The numerical access level the membership gives, or false if none.
 */
function rcp_get_subscription_access_level( $id ) {

	$level = rcp_get_membership_level( $id );

	if ( ! $level instanceof RCP\Membership_Level ) {
		return false;
	}

	return $level->get_access_level();

}

/**
 * Sanitizes a duration unit
 *
 * @param string $unit
 *
 * @since 3.4
 * @return string
 */
function _rcp_sanitize_duration_unit( $unit ) {
	return in_array( $unit, array( 'day', 'month', 'year' ) ) ? $unit : 'day';
}

/**
 * Gets the day of expiration of a membership from the current day
 *
 * @since 3.5.40 Introduces the $upgraded_from parameter.
 *
 * @param int  $id            The ID of the membership level to retrieve.
 * @param bool $set_trial     Whether or not to use the trial duration for calculations.
 * @param int  $upgraded_from ID of the membership the user upgraded from.
 *
 * @return string MySQL formatted date of expiration.
 */
function rcp_calculate_subscription_expiration( $id, $set_trial = false, $upgraded_from = 0 ) {
	$membership_level = rcp_get_membership_level( $id );
	$expiration_date  = 'none';

	if ( ! $membership_level instanceof Membership_Level ) {
		return $expiration_date;
	}

	if ( ! $membership_level->is_lifetime() ) {

		$current_time = current_time( 'timestamp' );

		if ( $set_trial && $membership_level->has_trial() ) {
			$expiration_unit   = $membership_level->get_trial_duration_unit();
			$expiration_length = $membership_level->get_trial_duration();
		} else {
			$expiration_unit   = $membership_level->get_duration_unit();
			$expiration_length = $membership_level->get_duration();
		}

		// If the user is upgrading from a different membership level, we need to account for proration.
		if (
			$upgraded_from > 0
			&& ! $membership_level->is_free()
		) {
			$previous_membership = rcp_get_membership( $upgraded_from );

			$prorate_credit = ! empty( $previous_membership ) ? $previous_membership->get_prorate_credit_amount() : 0;

			// Get prorate credit in units based on the new membership level price.
			$total_credits         = $prorate_credit - $membership_level->get_fee();
			$prorate_credit_length = floor( $total_credits / $membership_level->get_price() );

			// Add prorate credit to the expiration length.
			if ( $prorate_credit_length > 0 ) {
				$expiration_length = $expiration_length + $prorate_credit_length;
			}
		}

		$expiration_timestamp = strtotime( '+' . $expiration_length . ' ' . $expiration_unit . ' 23:59:59', $current_time );
		$expiration_date      = date( 'Y-m-d H:i:s', $expiration_timestamp );

		$extension_days = array( '29', '30', '31' );

		if ( in_array( date( 'j', $expiration_timestamp ), $extension_days ) && 'month' === $expiration_unit ) {
			/*
			 * Here we extend the expiration date by 1-3 days in order to account for "walking" payment dates in PayPal.
			 *
			 * See https://github.com/pippinsplugins/restrict-content-pro/issues/239
			 */

			$month = date( 'n', $expiration_timestamp );

			if ( $month < 12 ) {
				$month += 1;
				$year  = date( 'Y', $expiration_timestamp );
			} else {
				$month = 1;
				$year  = date( 'Y', $expiration_timestamp ) + 1;
			}

			$timestamp       = mktime( 0, 0, 0, $month, 1, $year );
			$expiration_date = date( 'Y-m-d 23:59:59', $timestamp );
		}

	}

	/**
	 * Filters the calculate expiration date for a membership level.
	 *
	 * @param string $expiration_date  Calculated date in MySQL format, or `none` if no expiration.
	 * @param object $membership_level Membership level object.
	 * @param bool   $set_trial        Whether or not to set a trial.
	 *
	 * @since 3.0
	 */
	$expiration_date = apply_filters( 'rcp_calculate_membership_level_expiration', $expiration_date, $membership_level, $set_trial );

	return $expiration_date;
}

/**
 * Get the number of days in a billing cycle.
 *
 * Taken from WooCommerce.
 *
 * @param string $duration_unit Unit: day, month, or year.
 * @param int    $duration      Cycle duration.
 *
 * @since 3.0.4
 * @return int The number of days in a billing cycle.
 */
function rcp_get_days_in_cycle( $duration_unit, $duration ) {

	$days_in_cycle = 0;

	switch ( $duration_unit ) {
		case 'day' :
			$days_in_cycle = $duration;
			break;
		case 'week' :
			$days_in_cycle = $duration * 7;
			break;
		case 'month' :
			$days_in_cycle = $duration * 30.4375; // Average days per month over 4 year period
			break;
		case 'year' :
			$days_in_cycle = $duration * 365.25; // Average days per year over 4 year period
			break;
	}

	return $days_in_cycle;

}

/**
 * Retrieve the number of active subscribers on a membership level
 *
 * @param int    $id     ID of the membership level to check.
 * @param string $status Membership status to check. Default is 'active'.
 *
 * @since  2.6
 * @return int Number of subscribers.
 */
function rcp_get_subscription_member_count( $id, $status = 'active' ) {

	$key   = $id . '_' . $status . '_member_count';
	$count = rcp_get_membership_level_meta( $id, $key, true );

	if ( '' === $count ) {
		if ( in_array( $status, array( 'active', 'free' ) ) ) {
			// If "active" or "free", use deprecated method to ensure paid vs free separation.
			$count = rcp_count_members( $id, $status );
		} else {
			$count = rcp_count_memberships( array(
				'object_id' => $id,
				'status'    => $status
			) );
		}

		rcp_update_membership_level_meta( $id, $key, (int) $count );

	}

	$count = (int) max( $count, 0 );

	return apply_filters( 'rcp_get_subscription_member_count', $count, $id, $status );
}

/**
 * Increments the number of active subscribers on a membership level
 *
 * @param int    $id     ID of the membership level to increment the count of.
 * @param string $status Membership status to increment count for. Default is 'active'.
 *
 * @since  2.6
 * @return void
 */
function rcp_increment_subscription_member_count( $id, $status = 'active' ) {

	$key   = $id . '_' . $status . '_member_count';
	$count = rcp_get_subscription_member_count( $id, $status );
	$count += 1;

	rcp_update_membership_level_meta( $id, $key, (int) $count );

	do_action( 'rcp_increment_subscription_member_count', $id, $count, $status );
}

/**
 * Decrements the number of active subscribers on a membership level
 *
 * @param int    $id     ID of the membership level to decrement the count of.
 * @param string $status Membership status to decrement count for. Default is 'active'.
 *
 * @since  2.6
 * @return void
 */
function rcp_decrement_subscription_member_count( $id, $status = 'active' ) {

	$key   = $id . '_' . $status . '_member_count';
	$count = rcp_get_subscription_member_count( $id, $status );
	$count -= 1;
	$count = max( $count, 0 );

	rcp_update_membership_level_meta( $id, $key, (int) $count );

	do_action( 'rcp_decrement_subscription_member_count', $id, $count, $status );
}

/**
 * Get a formatted duration unit name for membership lengths
 *
 * @param string $unit   The duration unit to return a formatted string for.
 * @param int    $length The duration of the membership level.
 *
 * @return string A formatted unit display. Example "days" becomes "Days". Return is localized.
 */
function rcp_filter_duration_unit( $unit, $length ) {
	$new_unit = '';
	switch ( $unit ) :
		case 'day' :
			if ( $length > 1 ) {
				$new_unit = __( 'Days', 'rcp' );
			} else {
				$new_unit = __( 'Day', 'rcp' );
			}
			break;
		case 'month' :
			if ( $length > 1 ) {
				$new_unit = __( 'Months', 'rcp' );
			} else {
				$new_unit = __( 'Month', 'rcp' );
			}
			break;
		case 'year' :
			if ( $length > 1 ) {
				$new_unit = __( 'Years', 'rcp' );
			} else {
				$new_unit = __( 'Year', 'rcp' );
			}
			break;
	endswitch;
	return $new_unit;
}

/**
 * Checks to see if there are any paid membership levels created
 *
 * @since 1.1.0
 * @return bool True if paid levels exist, false if only free.
 */
function rcp_has_paid_levels() {
	return ( bool ) rcp_get_paid_levels();
}

/**
 * Return the paid levels
 *
 * @since 2.5
 * @return RCP\Membership_Level[]
 */
function rcp_get_paid_levels() {

	$paid_levels = rcp_get_membership_levels( array(
		'price__not_in' => array( 0 ),
		'status'        => 'active',
		'number'        => 9999
	) );

	/**
	 * Filters the paid membership levels.
	 *
	 * @param RCP\Membership_Level[] $paid_levels
	 */
	return apply_filters( 'rcp_get_paid_levels', $paid_levels );

}

/**
 * Retrieves available access levels
 *
 * @since 1.3.2
 * @return array
 */
function rcp_get_access_levels() {
	$levels = array(
		0  => 'None',
		1  => '1',
		2  => '2',
		3  => '3',
		4  => '4',
		5  => '5',
		6  => '6',
		7  => '7',
		8  => '8',
		9  => '9',
		10 => '10'
	);

	/**
	 * Filters the available access levels.
	 *
	 * @param array $levels
	 */
	return apply_filters( 'rcp_access_levels', $levels );
}

/**
 * Generates a new subscription key
 *
 * @since 1.3.2
 * @return string
 */
function rcp_generate_subscription_key() {
	return apply_filters( 'rcp_subscription_key', urlencode( strtolower( md5( uniqid() ) ) ) );
}

/**
 * Determines if a membership level should be shown
 *
 * @param int $level_id ID of the membership level to check.
 * @param int $user_id  ID of the user, or 0 to use currently logged in user.
 *
 * @since 1.3.2.3
 * @return bool
 */
function rcp_show_subscription_level( $level_id = 0, $user_id = 0 ) {

	global $rcp_register_form_atts, $rcp_options;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret = true;

	$membership_level = rcp_get_membership_level( $level_id );

	if ( ! $membership_level instanceof RCP\Membership_Level ) {
		return false;
	}

	$customer                  = rcp_get_customer_by_user_id( $user_id );
	$membership                = is_object( $customer ) ? rcp_get_customer_single_membership( $customer->get_id() ) : false;
	$customers_level_id        = ! empty( $membership ) ? $membership->get_object_id() : false;
	$used_trial                = is_object( $customer ) ? $customer->has_trialed() : false;
	$free_subs_swaps 	       = isset($rcp_options['disable_trial_free_subs']) && (bool)$rcp_options['disable_trial_free_subs'];
	$used_trial 		       = ! $free_subs_swaps && $used_trial;
	$can_renew 				   = false === $membership ? false : $membership->can_renew();
	$membership_level_price    =  $membership_level->get_price();
	$membership_level_duration = $membership_level->get_duration();

	if ( ! rcp_multiple_memberships_enabled() ) {
		/*
		 * Don't show if user is logged in and one of the following applies:
		 * 		- Membership level is a free trial and the customer has already trialed.
		 * 		- Membership level is free and the customer already has it.
		 * 		- Membership level doesn't expire (`0` duration) and the customer already has it.
		 * 		- Membership level contains a free trial, the customer has already trialed, the customer is currently on this level, and the membership status is "active".
		 * 		- Customer has a membership but is NOT on this membership level, their membership is active, but upgrades are disabled.
		 * 		- Customer is trying to renew their current membership but it cannot be renewed.
		 */
		if (
			is_user_logged_in()
			&&
			// The Membership has a free trial and the user already have it. Having a price means that it has a trial
			// time. The setting does not allow to have a trial time without a price greater than 0.
			( $membership_level_price > 0 && $level_id == $customers_level_id && ! $can_renew )
			||
			( 0 == $membership_level_price && $membership_level_duration > 0 && $used_trial )
			||
			( 0 == $membership_level_price && $customers_level_id == $level_id )
			||
			( 0 == $membership_level_duration && $customers_level_id == $level_id && ! empty( $membership ) && $membership->is_active() )
			||
			( ! empty( $membership ) && $membership->is_active() && ! $membership->upgrade_possible() && $customers_level_id != $level_id )
			||
			( $customers_level_id == $level_id && ! $can_renew )
		) {
			$ret = false;
		}
	} else {
		/*
		 * Don't show free trial if user has already used it.
		 */
		if ( 0 == $membership_level_price && $membership_level_duration > 0 && $used_trial ) {
			$ret = false;
		}
	}

	// If multiple levels are specified in shortcode, like [register_form ids="1,2"]
	if ( ! empty( $rcp_register_form_atts['ids'] ) ) {

		$levels_to_show = array_map( 'absint', explode( ',', $rcp_register_form_atts['ids'] ) );

		if ( ! in_array( $level_id, $levels_to_show ) ) {
			$ret = false;
		}

	}

	/**
	 * Filters whether or not a membership level should be shown.
	 *
	 * @param bool $ret
	 * @param int  $level_id
	 * @param int  $user_id
	 */
	return apply_filters( 'rcp_show_subscription_level', $ret, $level_id, $user_id );
}

/**
 * Gets the IDs of membership levels with trial periods.
 *
 * @since 2.7
 * @return array An array of numeric membership level IDs. An empty array if none are found.
 */
function rcp_get_trial_level_ids() {
	$ids = array();

	foreach( rcp_get_membership_levels( array( 'number' => 999 ) ) as $membership_level ) {
		if ( $membership_level->has_trial() ) {
			$ids[] = $membership_level->get_id();
		}
	}

	return $ids;

	/*
	 * @todo Eventually it would be nice to use the below method, but right now it conflicts with Hard-set Expiration Dates.
	 */
	return rcp_get_membership_levels( array(
		'trial_duration__not_in' => array( 0 ),
		'number'                 => 9999,
		'fields'                 => 'id'
	) );
}
