<?php
/**
 * Membership Count Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Sandhills Development, LLC
 * @license   GPL2+
 * @since     3.3
 */

/**
 * Query for membership count records
 *
 * @param string|array $query                   {
 *                                              Optional. Array or query string of membership count query
 *                                              parameters. Default empty.
 *
 * @type int           $id                      An entry ID to only return that entry. Default empty.
 * @type array         $id__in                  Array of entry IDs to include. Default empty.
 * @type array         $id__not_in              Array of entry IDs to exclude. Default empty.
 * @type int           $level_id                A level ID to only return this level's entries. Default
 *                                              empty.
 * @type array         $level_id__in            Array of level IDs to include. Default empty.
 * @type array         $level_id__not_in        Array of level IDs to exclude. Default empty.
 * @type array         $date_query              Query all datetime columns together. See WP_Date_Query.
 * @type array         $created_date_query      Date query clauses to limit entries by. See WP_Date_Query.
 *                                              Default null.
 * @type bool          $count                   Whether to return an entry count (true) or array of entry
 *                                              objects. Default false.
 * @type string        $fields                  Item fields to return. Accepts any column known names
 *                                              or empty (returns an array of complete entry objects). Default
 *                                              empty.
 * @type int           $number                  Limit number of entries to retrieve. Default 100.
 * @type int           $offset                  Number of entries to offset the query. Used to build LIMIT
 *                                              clause. Default 0.
 * @type bool          $no_found_rows           Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
 * @type string|array  $orderby                 Accepts 'id', 'level_id', 'active_count', 'pending_count',
 *                                              `cancelled_count`, `expired_count`, and `date_created`.
 *                                              Also accepts false, an empty array, or 'none' to disable
 *                                              `ORDER BY` clause. Default 'id'.
 * @type string        $order                   How to order results. Accepts 'ASC', 'DESC'. Default 'DESC'.
 * @type string        $search                  Search term(s) to retrieve matching entries for. Default empty.
 * @type bool          $update_cache            Whether to prime the cache for found entries. Default false.
 * }
 *
 * @since 3.3
 */
function rcp_get_membership_count_entries( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'number' => 20
	) );

	$query = new RCP\Database\Queries\Membership_Count();

	return $query->query( $args );

}

/**
 * Add a new membership count entry.
 *
 * @param array $args            {
 *
 * @type int    $level_id        ID of the corresponding membership level.
 * @type int    $active_count    Number of active memberships on this date.
 * @type int    $pending_count   Number of pending memberships on this date.
 * @type int    $cancelled_count Number of cancelled memberships on this date.
 * @type int    $expired_count   Number of expired memberships on this date.
 * }
 *
 * @since 3.3
 * @return int|WP_Error ID of the new entry on success, WP_Error on failure.
 */
function rcp_add_membership_count_entry( $args = array() ) {

	$defaults = array(
		'level_id'        => 0,
		'active_count'    => 0,
		'pending_count'   => 0,
		'cancelled_count' => 0,
		'expired_count'   => 0
	);

	$args = wp_parse_args( $args, $defaults );

	// Level ID is required.
	if ( empty( $args['level_id'] ) ) {
		return new WP_Error( 'missing_level_id', __( 'You must specify a membership level ID.', 'rcp' ) );
	}

	$query    = new RCP\Database\Queries\Membership_Count();
	$entry_id = $query->add_item( $args );

	if ( empty( $entry_id ) ) {
		return new WP_Error( 'database_error', __( 'Failed to add a new membership count entry.', 'rcp' ) );
	}

	return absint( $entry_id );

}
