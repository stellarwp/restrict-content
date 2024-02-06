<?php
/**
 * Membership Functions
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
 * Get a single membership by its ID.
 *
 * @param int $membership_id
 *
 * @since 3.0
 * @return RCP_Membership|false
 */
function rcp_get_membership( $membership_id ) {

	$memberships = new RCP\Database\Queries\Membership();

	return $memberships->get_item( $membership_id );

}

/**
 * Get a membership by a field/value pair.
 *
 * @param string $field Column to search in.
 * @param string $value Value of the row.
 *
 * @since 3.0
 * @return RCP_Membership|false
 */
function rcp_get_membership_by( $field = '', $value = '' ) {

	/*
	 * We don't use \RCP\Database\Queries\Membership::get_item_by() because that may return
	 * disabled memberships, and thus have unexpected results. Using rcp_get_memberships()
	 * allows us to check enabled memberships only more easily.
	 */
	$memberships = rcp_get_memberships( array(
		$field   => $value,
		'number' => 1
	) );

	if ( empty( $memberships ) ) {
		return false;
	}

	return reset( $memberships );

}

/**
 * Returns an array of all memberships.
 *
 * @param array       $args                     {
 *                                              Optional. Array or query string of membership query parameters.
 *                                              Default empty.
 *
 * @type int          $id                       A membership ID to only return that membership. Default empty.
 * @type array        $id__in                   Array of membership IDs to include. Default empty.
 * @type array        $id__not_in               Array of membership IDs to exclude. Default empty.
 * @type int          $customer_id              A customer ID to only return this customer's memberships. Default
 *                                              empty.
 * @type array        $customer_id__in          Array of customer IDs to include. Default empty.
 * @type array        $customer_id__not_in      Array of customer IDs to exclude. Default empty.
 * @type string       $object_id                An object ID to only return memberships for this object. Default
 *                                              empty.
 * @type array        $object_id__in            Array of object IDs to include. Default empty.
 * @type array        $object_id__not_in        Array of object IDs to exclude. Default empty.
 * @type string       $object_type              An object type to only return this type. Default empty.
 * @type array        $object_type__in          Array of object types to include. Default empty.
 * @type array        $object_type__not_in      Array of object types to exclude. Default empty.
 * @type string       $currency                 A currency to only show memberships using this currency. Default
 *                                              empty.
 * @type array        $date_query               Query all datetime columns together. See WP_Date_Query.
 * @type array        $created_date_query       Date query clauses to limit memberships by. See WP_Date_Query.
 *                                              Default null.
 * @type array        $trial_end_date_query     Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type array        $renewed_date_query       Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type array        $cancellation_date_query  Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type array        $expiration_date_query    Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type array        $date_modified_query      Date query clauses to limit by. See WP_Date_Query. Default null.
 * @type int          $auto_renew               Auto renewal status. Default null.
 * @type string       $status                   A status to filter by. Default null for all statuses.
 * @type array        $status__in               Array of statuses to include. Default empty.
 * @type array        $status__not_in           Array of statuses to exclude. Default empty.
 * @type string       $gateway_customer_id      A gateway customer ID to search for. Default empty.
 * @type string       $gateway_subscription_id  A gateway subscription ID to search for. Default empty.
 * @type string       $gateway                  A gateway to filter by. Default empty.
 * @type string       $subscription_key         A subscription key to filter by. Default empty.
 * @type int          $upgraded_from            A membership ID this membership upgraded from. Default empty.
 * @type int          $disabled                 Whether or not to show disabled memberships. Set to `0` to only
 *                                              include enabled memberships, set to `1` to only include disabled
 *                                              memberships, set to empty string to include both. Default 0.
 * @type bool         $count                    Whether to return a membership count (true) or array of membership
 *                                              objects. Default false.
 * @type string       $fields                   Item fields to return. Accepts any column known names
 *                                              or empty (returns an array of complete membership objects). Default
 *                                              empty.
 * @type int          $number                   Limit number of memberships to retrieve. Default 20.
 * @type int          $offset                   Number of memberships to offset the query. Used to build LIMIT
 *                                              clause. Default 0.
 * @type bool         $no_found_rows            Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
 * @type string|array $orderby                  Accepts 'id', 'object_id', 'object_type', 'currency',
 *                                              `initial_amount`, `recurring_amount`, `created_date`,
 *                                              `trial_end_date`, `cancellation_date`, `expiration_date`,
 *                                              `times_billed`, `maximum_renewals`, `status`,
 *                                              `gateway_customer_id`, `gateway_subscription_id`, and
 *                                              `subscription_key`. Also accepts false, an empty array, or 'none'
 *                                              to disable `ORDER BY` clause. Default 'id'.
 * @type string       $order                    How to order results. Accepts 'ASC', 'DESC'. Default 'DESC'.
 * @type string       $search                   Search term(s) to retrieve matching memberships for. Default empty.
 * @type bool         $update_cache             Whether to prime the cache for found memberships. Default false.
 * }
 *
 * @since 3.0
 * @return RCP_Membership[] Array of RCP_Membership objects.
 */
function rcp_get_memberships( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'number'   => 20,
		'disabled' => 0
	) );

	$memberships = new RCP\Database\Queries\Membership();

	return $memberships->query( $args );

}

/**
 * Count the number of memberships.
 *
 * @param array $args Array of query arguments to override the defaults.
 *
 * @see   rcp_get_memberships() for accepted arguments.
 *
 * @since 3.0
 * @return int
 */
function rcp_count_memberships( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'count'    => true,
		'disabled' => 0
	) );

	$memberships = new RCP\Database\Queries\Membership( $args );

	return absint( $memberships->found_items );

}

/**
 * Query for and return array of membership counts, keyed by status.
 *
 * @param array $args Query arguments to override the defaults.
 *
 * @since 3.0
 * @return array
 */
function rcp_get_membership_counts( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'count'    => true,
		'disabled' => 0,
		'groupby'  => 'status'
	) );

	$memberships = new RCP\Database\Queries\Membership( $args );

	return rcp_format_counts( $memberships, $args['groupby'] );

}

/**
 * Add a new membership.
 *
 * @param array $data                      {
 *                                         Array of arguments.
 *
 * @type int    $customer_id               Required. ID of the customer.
 * @type int    $object_id                 Optional. Object ID. Typically the membership level ID.
 * @type string $object_type               Optional. Object type. Default is `membership`.
 * @type string $currency                  Optional. Currency used for the membership. Default is the return value of
 *                                                   `rcp_get_currency()`.
 * @type string $initial_amount            Optional. Amount charged on initial registration.
 * @type string $recurring_amount          Optional. Amount charged on renewals.
 * @type string $created_date              Optional. Date the membership was created. Default is now.
 * @type string $trial_end_date            Optional. Date the integrated free trial ends. Leave blank for no trial.
 * @type string $renewed_date              Optional. Date the membership was last renewed. Leave blank for none.
 * @type string $cancellation_date         Optional. Date this membership was cancelled.
 * @type string $expiration_date           Optional. Expiration date. Leave blank to auto calculate.
 * @type bool   $auto_renew                Optional. Whether or not this membership automatically renews. Default is
 *                                                   false.
 * @type int    $times_billed              Optional. Number of times this membership has been billed for so far.
 *                                                   Default is 0.
 * @type int    $maximum_renewals          Optional. Maximum number of renewals to process. Default is 0 for
 *                                                   indefinite.
 * @type string $status                    Optional. Membership status. Default is `pending`.
 * @type string $gateway_customer_id       Optional. Customer ID at the payment gateway.
 * @type string $gateway_subscription_id   Optional. Subscription ID at the payment gateway.
 * @type string $gateway                   Optional. Payment gateway used for this membership.
 * @type string $signup_method             Optional. Method used to signup. Default is `live`.
 * @type string $subscription_key          Optional. Subscription key.
 * @type string $notes                     Optional. Membership notes.
 * @type int    $upgraded_from             Optional. ID of the membership this one was upgraded from.
 * @type string $date_modified             Optional. Date the membership was last modified.
 * @type int    $disabled                  Optional. Whether or not this membership is disabled
 *                                                   (`0` = not disabled; `1` = disabled). Default is `0`.
 * }
 *
 * @since 3.0
 * @return int|false ID of the new membership, or false on failure.
 */
function rcp_add_membership( $data = array() ) {

	$defaults = array(
		'customer_id'      => 0,
		'user_id'          => null,
		'object_id'        => 0,
		'object_type'      => 'membership',
		'currency'         => rcp_get_currency(),
		'initial_amount'   => false,
		'recurring_amount' => false,
		'created_date'     => current_time( 'mysql' ),
		'expiration_date'  => '',
		'auto_renew'       => false,
		'times_billed'     => 0,
		'maximum_renewals' => 0,
		'status'           => 'pending',
		'signup_method'    => 'live',
		'disabled'         => 0
	);

	$data = wp_parse_args( $data, $defaults );

	// Customer ID is required.
	if ( empty( $data['customer_id'] ) ) {
		rcp_log( sprintf( 'Failed to add new membership - customer ID is missing. Data: %s', var_export( $data, true ) ) );

		return false;
	}

	$membership_level = rcp_get_membership_level( $data['object_id'] );

	if ( ! $membership_level instanceof Membership_Level ) {
		return false;
	}

	$customer         = rcp_get_customer( $data['customer_id'] );
	$has_trial        = $membership_level->has_trial() || ( $membership_level->is_free() && ! $membership_level->is_lifetime() );
	$set_trial        = ( $has_trial && ! $customer->has_trialed() );
	$upgraded_from    = ! empty( $data['upgraded_from'] ) ? $data['upgraded_from'] : 0;
	$expiration_date  = ! empty( $data['object_id'] ) ? rcp_calculate_subscription_expiration( $data['object_id'], $set_trial, $upgraded_from ) : '';

	// Populate user_id if not provided.
	if ( empty( $data['user_id'] ) && $customer instanceof RCP_Customer ) {
		$data['user_id'] = $customer->get_user_id();
	}

	// Convert "free" status to "active".
	if ( ! empty( $data['status'] ) && 'free' === $data['status'] ) {
		$data['status'] = 'active';
	}

	if ( has_filter( 'rcp_member_calculated_expiration' ) ) {
		/**
		 * @deprecated 3.0 Use `rcp_membership_calculated_expiration_date` instead.
		 */
		$expiration_date = apply_filters( 'rcp_member_calculated_expiration', $expiration_date, $customer->get_user_id(), $customer->get_member() );
	}

	// Auto calculate expiration.
	if ( empty( $data['expiration_date'] ) && ! empty( $expiration_date ) ) {
		$data['expiration_date'] = $expiration_date;
	}

	if ( 'none' == $data['expiration_date'] ) {
		$data['expiration_date'] = null;
	}

	// Auto calculate trial end date.
	if ( $set_trial && empty( $data['trial_end_date'] ) && ! empty( $expiration_date ) ) {
		$data['trial_end_date'] = $expiration_date;
	}

	// If amounts were not provided, attempt to guess them from the membership level.
	if ( false === $data['initial_amount'] ) {
		$data['initial_amount'] = $membership_level->get_fee() + $membership_level->get_price();
	}
	if ( false === $data['recurring_amount'] ) {
		$data['recurring_amount'] = $membership_level->get_price();
	}

	// Make sure all empty amounts are `0.00` for consistency.
	if ( empty( $data['initial_amount'] ) || '0' == $data['initial_amount'] ) {
		$data['initial_amount'] = '0.00';
	}
	if ( empty( $data['recurring_amount'] ) || '0' == $data['recurring_amount'] ) {
		$data['recurring_amount'] = '0.00';
	}

	// Change auto renew to an integer.
	$data['auto_renew'] = ! empty( $data['auto_renew'] ) ? 1 : 0;

	// Remove "notes" for our log because it's annoying.
	$log_data = $data;
	if ( isset( $log_data['notes'] ) ) {
		unset( $log_data['notes'] );
	}
	rcp_log( sprintf( 'Adding new membership. Data: %s', var_export( $log_data, true ) ) );

	$memberships = new RCP\Database\Queries\Membership();

	$membership_id = $memberships->add_item( $data );

	if ( $membership_id ) {
		/**
		 * Triggers after a new membership is added.
		 *
		 * @param int   $membership_id ID of the membership that was just added.
		 * @param array $data          Membership data.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_new_membership_added', $membership_id, $data );

		return $membership_id;
	}

	return false;

}

/**
 * Update a membership record in the database.
 *
 * @param int   $membership_id             ID of the membership to update.
 * @param array $data                      {
 *                                         Array of data to update. Accepts:
 *
 * @type int    $customer_id               Required. ID of the customer.
 * @type int    $object_id                 Optional. Object ID. Typically the membership level ID.
 * @type string $object_type               Optional. Object type. Default is `membership`.
 * @type string $currency                  Optional. Currency used for the membership. Default is the return value of
 *                                                   `rcp_get_currency()`.
 * @type string $initial_amount            Optional. Amount charged on initial registration.
 * @type string $recurring_amount          Optional. Amount charged on renewals.
 * @type string $created_date              Optional. Date the membership was created. Default is now.
 * @type string $trial_end_date            Optional. Date the integrated free trial ends. Leave blank for no trial.
 * @type string $renewed_date              Optional. Date the membership was last renewed.
 * @type string $cancellation_date         Optional. Date this membership was cancelled.
 * @type string $expiration_date           Optional. Expiration date. Leave blank to auto calculate.
 * @type bool   $auto_renew                Optional. Whether or not this membership automatically renews. Default is
 *                                                   false.
 * @type int    $times_billed              Optional. Number of times this membership has been billed for so far.
 *                                                   Default is 0.
 * @type int    $maximum_renewals          Optional. Maximum number of renewals to process. Default is 0 for
 *                                                   indefinite.
 * @type string $status                    Optional. Membership status. Default is `pending`.
 * @type string $gateway_customer_id       Optional. Customer ID at the payment gateway.
 * @type string $gateway_subscription_id   Optional. Subscription ID at the payment gateway.
 * @type string $gateway                   Optional. Payment gateway used for this membership.
 * @type string $signup_method             Optional. Method used to signup. Default is `live`.
 * @type string $subscription_key          Optional. Subscription key.
 * @type string $notes                     Optional. Membership notes.
 * @type int    $upgraded_from             Optional. ID of the membership this one was upgraded from.
 * @type int    $disabled                  Optional. Whether or not this membership is disabled
 *                                                   (`0` = not disabled; `1` = disabled). Default is `0`.
 * }
 *
 * @since 3.0
 * @return bool True on success, false on failure.
 */
function rcp_update_membership( $membership_id, $data = array() ) {

	$membership = rcp_get_membership( $membership_id );

	return $membership->update( $data );

}

/**
 * Delete a membership record
 *
 * This will rarely want to be done. Most memberships should probably be disabled instead.
 *
 * @see   RCP_Membership::disable()
 *
 * @param int $membership_id ID of the membership to delete.
 *
 * @since 3.0
 * @return bool
 */
function rcp_delete_membership( $membership_id ) {

	//@todo other stuff to clean up

	$memberships = new \RCP\Database\Queries\Membership();

	return $memberships->delete_item( $membership_id );

}

/**
 * Get the prorate credit amount for a membership.
 *
 * @param int $membership_id ID of the membership to check.
 *
 * @since 3.0
 * @return float|int
 */
function rcp_get_membership_prorate_credit( $membership_id ) {

	$membership = rcp_get_membership( $membership_id );

	return $membership->get_prorate_credit_amount();

}

/**
 * Determines whether or not a membership can have the billing card updated.
 *
 * @param int|RCP_Membership $membership_id_or_object ID of the membership or membership object.
 *
 * @since 3.0
 * @return bool
 */
function rcp_can_update_membership_billing_card( $membership_id_or_object ) {

	if ( ! is_object( $membership_id_or_object ) ) {
		$membership = rcp_get_membership( $membership_id_or_object );
	} else {
		$membership = $membership_id_or_object;
	}

	if ( empty( $membership ) ) {
		return false;
	}

	return $membership->can_update_billing_card();

}

/**
 * Get the renewal URL for a membership.
 *
 * @param int $membership_id
 *
 * @since 3.1
 * @return string
 */
function rcp_get_membership_renewal_url( $membership_id ) {

	$url = add_query_arg( array(
		'registration_type' => 'renewal',
		'membership_id'     => urlencode( $membership_id )
	), rcp_get_registration_page_url() );

	return $url;

}

/**
 * Get the upgrade URL for a membership.
 *
 * @param int $membership_id
 *
 * @since 3.1
 * @return string
 */
function rcp_get_membership_upgrade_url( $membership_id ) {

	$url = add_query_arg( array(
		'registration_type' => 'upgrade',
		'membership_id'     => urlencode( $membership_id )
	),rcp_get_registration_page_url() );

	return $url;

}

/**
 * Get the cancellation URL for a membership.
 *
 * @param int $membership_id ID of the membership to get the cancel URL for.
 *
 * @since 3.0
 * @return string
 */
function rcp_get_membership_cancel_url( $membership_id ) {

	$url        = '';
	$membership = rcp_get_membership( $membership_id );

	if ( $membership->is_recurring() ) {

		$url = wp_nonce_url( add_query_arg( array( 'rcp-action' => 'cancel', 'membership-id' => urlencode( $membership_id ) ) ), 'rcp-cancel-nonce' );

	}

	/**
	 * @deprecated 3.0 Use `rcp_membership_cancel_url` instead.
	 */
	$url = apply_filters( 'rcp_member_cancel_url', $url, $membership->get_user_id() );

	/**
	 * Filters the membership cancel URL.
	 *
	 * @param string         $url           Cancellation URL.
	 * @param int            $membership_id ID of the membership.
	 * @param RCP_Membership $membership    Membership object.
	 *
	 * @since 3.0
	 */
	$url = apply_filters( 'rcp_membership_cancel_url', $url, $membership_id, $membership );

	return $url;

}

/**
 * Cancel membership payment profile at the payment gateway.
 *
 * @param int  $membership_id ID of the membership.
 * @param bool $set_status    Whether or not to set the membership status to "cancelled".
 *
 * @since 3.0
 * @return true|WP_Error
 */
function rcp_cancel_membership_payment_profile( $membership_id, $set_status = true ) {

	$membership = rcp_get_membership( $membership_id );

	return $membership->cancel_payment_profile( $set_status );

}

/**
 * Determines if a membership can be cancelled on site.
 *
 * @param int $membership_id ID of the membership.
 *
 * @since 3.0
 * @return bool
 */
function rcp_can_membership_be_cancelled( $membership_id ) {

	$membership = rcp_get_membership( $membership_id );

	return $membership->can_cancel();

}

/**
 * Determines if a membership is recurring.
 *
 * @param int $membership_id ID of the membership to check.
 *
 * @since 3.0
 * @return bool
 */
function rcp_membership_is_recurring( $membership_id ) {

	$membership = rcp_get_membership( $membership_id );

	return $membership->is_recurring();

}

/**
 * Determines if a membership is expired.
 *
 * @param int $membership_id ID of the membership to check.
 *
 * @since 3.0
 * @return bool
 */
function rcp_membership_is_expired( $membership_id ) {

	$membership = rcp_get_membership( $membership_id );

	return $membership->is_expired();

}

/**
 * Determines if a membership is active.
 *
 * @param int $membership_id ID of the membership to check.
 *
 * @since 3.0
 * @return bool
 */
function rcp_membership_is_active( $membership_id ) {

	$membership = rcp_get_membership( $membership_id );

	return $membership->is_active();

}

/**
 * Determines whether a membership grants access to a specific access level.
 *
 * @param int $membership_id ID of the membership to check.
 * @param int $access_level  Access level to compare against.
 *
 * @since 3.0
 * @return bool
 */
function rcp_membership_has_access_level( $membership_id, $access_level = 0 ) {

	$membership = rcp_get_membership( $membership_id );

	return $membership->has_access_level( $access_level );

}

/**
 * Whether or not customers can have multiple memberships at a time.
 *
 * @since 3.0
 * @return bool
 */
function rcp_multiple_memberships_enabled() {
	global $rcp_options;

	return isset( $rcp_options['multiple_memberships'] );
}

/**
 * Prints or returns a membership status in a nice format that is localized.
 *
 * @param int  $membership_id ID of the membership.
 * @param bool $echo          Whether to print (TRUE) or return (FALSE) the status.
 *
 * @since 3.0
 * @return string
 */
function rcp_print_membership_status( $membership_id, $echo = true ) {

	$membership = rcp_get_membership( $membership_id );
	$status     = $membership->get_status();

	$print_status = rcp_get_status_label( $status );

	if ( $echo ) {
		echo $print_status;
	}

	return $print_status;

}
