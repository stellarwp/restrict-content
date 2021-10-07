<?php
/**
 * Membership Level Meta Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add meta data field to a membership level.
 *
 * @param int    $level_id   Membership level ID.
 * @param string $meta_key   Meta data name.
 * @param mixed  $meta_value Meta data value. Must be serializable if non-scalar.
 * @param bool   $unique     Optional. Whether the same key should not be added. Default false.
 *
 * @since 3.1
 * @return false|int
 */
function rcp_add_membership_level_meta( $level_id, $meta_key, $meta_value, $unique = false ) {
	/**
	 * @var RCP_Levels $rcp_levels_db
	 */
	global $rcp_levels_db;

	return $rcp_levels_db->add_meta( $level_id, $meta_key, $meta_value, $unique );
}

/**
 * Remove meta data matching criteria from a membership level.
 *
 * You can match based on the key, or key and value. Removing based on key and value, will keep from removing duplicate
 * meta data with the same key. It also allows removing all meta data matching key, if needed.
 *
 * @param int    $level_id   Membership level ID.
 * @param string $meta_key   Meta data name.
 * @param mixed  $meta_value Meta data value. Must be serializable if non-scalar. Default empty.
 *
 * @since 3.1
 * @return false|int
 */
function rcp_delete_membership_level_meta( $level_id, $meta_key, $meta_value = '' ) {
	/**
	 * @var RCP_Levels $rcp_levels_db
	 */
	global $rcp_levels_db;

	return $rcp_levels_db->delete_meta( $level_id, $meta_key, $meta_value );
}

/**
 * Retrieve membership level meta field for a level.
 *
 * @param  int   $level_id   Membership level ID.
 * @param string $key        Optional. The meta key to retrieve. By default, returns data for all keys. Default
 *                           empty.
 * @param bool   $single     Optional, default is false. If true, return only the first value of the specified
 *                           meta_key. This parameter has no effect if meta_key is not specified.
 *
 * @since 3.1
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
function rcp_get_membership_level_meta( $level_id, $key = '', $single = false ) {
	/**
	 * @var RCP_Levels $rcp_levels_db
	 */
	global $rcp_levels_db;

	return $rcp_levels_db->get_meta( $level_id, $key, $single );
}

/**
 * Update membership level meta field based on level ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and level ID.
 *
 * If the meta field for the level does not exist, it will be added.
 *
 * @param int    $level_id   Membership level ID.
 * @param string $meta_key   Meta data key.
 * @param mixed  $meta_value Meta data value. Must be serializable if non-scalar.
 * @param mixed  $prev_value Optional. Previous value to check before removing. Default empty.
 *
 * @since 3.1
 * @return int|false Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function rcp_update_membership_level_meta( $level_id, $meta_key, $meta_value, $prev_value = '' ) {
	/**
	 * @var RCP_Levels $rcp_levels_db
	 */
	global $rcp_levels_db;

	return $rcp_levels_db->update_meta( $level_id, $meta_key, $meta_value, $prev_value );
}