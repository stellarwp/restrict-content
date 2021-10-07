<?php
/**
 * Log Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 * @since     3.4
 */

namespace RCP\Logs;

use RCP\Database\Queries\Log as Query;

/**
 * Get a single log by its ID
 *
 * @param int $log_id
 *
 * @since 3.4
 * @return Log|false
 */
function get_log( $log_id ) {

	$query = new Query();

	return $query->get_item( $log_id );

}

/**
 * Query for logs
 *
 * @param array $args
 *
 * @see   Query::__construct() for accepted arguments.
 *
 * @since 3.4
 * @return Log[] Array of `Log` objects.
 */
function get_logs( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'number' => 20
	) );

	$query = new Query();

	return $query->query( $args );

}

/**
 * Count the number of logs
 *
 * @param array $args
 *
 * @see   Query::__construct() for accepted arguments.
 *
 * @return int Number of logs.
 */
function count_logs( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'count' => true
	) );

	$query = new Query( $args );

	return absint( $query->found_items );

}

/**
 * Add a new log
 *
 * @param array   $args          {
 *                               Array of log data. Default empty.
 *
 * @type string   $object_type   Optional. Type of object. E.g. `customer`, `membership`, `payment`.
 * @type int|null $object_id     Optional. ID of the associated object type. E.g. if the `object_type` is `membership`
 *                               and you pass through an `object_id` of `25` then this log refers to membership ID 25.
 *                               Set as `null` if this log doesn't refer to an individual object. Default null.
 * @type int|null $user_id       Optional. ID of the user associated with this log. Set as `null` if this log doesn't
 *                               refer to any individual user. Default null.
 * @type string   $type          Required. Log type. Default empty.
 * @type string   $title         Optional. Short log title. Default empty.
 * @type string   $content       Optional. Long form log content. Default empty.
 * @type int      $is_error      Optional. Set to `1` if this log is for an error. Set to `0` if this log is just
 *                               informational. Default `0`.
 * @type string   $date_created  Optional. Date the log was created. Format: YYYY-MM-DD HH:MM:SS. Default is current
 *                               time.
 * @type string   $date_modified Optional. Date the log was last modified. Format: YYYY-MM-DD HH:MM:SS. Default is
 *                               current time.
 *                    }
 *
 * @since 3.4
 * @return bool
 */
function add_log( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'object_type' => '',
		'object_id'   => null,
		'user_id'     => null,
		'type'        => '',
		'title'       => '',
		'content'     => '',
		'is_error'    => 0,
	) );

	$query = new Query();

	return $query->add_item( $args );

}
