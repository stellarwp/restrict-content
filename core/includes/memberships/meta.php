<?php
/**
 * Membership Meta Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add meta data field to a membership.
 *
 * @param int    $membership_id Membership ID.
 * @param string $meta_key      Meta data name.
 * @param mixed  $meta_value    Meta data value. Must be serializable if non-scalar.
 * @param bool   $unique        Optional. Whether the same key should not be added. Default false.
 *
 * @since 3.0
 * @return false|int
 */
function rcp_add_membership_meta( $membership_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'rcp_membership', $membership_id, $meta_key, $meta_value, $unique );
}

/**
 * Remove meta data matching criteria from a membership.
 *
 * You can match based on the key, or key and value. Removing based on key and value, will keep from removing duplicate
 * meta data with the same key. It also allows removing all meta data matching key, if needed.
 *
 * @param int    $membership_id Membership ID.
 * @param string $meta_key      Meta data name.
 * @param mixed  $meta_value    Meta data value. Must be serializable if non-scalar. Default empty.
 *
 * @since 3.0
 * @return false|int
 */
function rcp_delete_membership_meta( $membership_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'rcp_membership', $membership_id, $meta_key, $meta_value );
}

/**
 * Retrieve membership meta field for a membership.
 *
 * @param  int   $membership_id Membership ID.
 * @param string $key           Optional. The meta key to retrieve. By default, returns data for all keys. Default
 *                              empty.
 * @param bool   $single        Optional, default is false. If true, return only the first value of the specified
 *                              meta_key. This parameter has no effect if meta_key is not specified.
 *
 * @since 3.0
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
function rcp_get_membership_meta( $membership_id, $key = '', $single = false ) {
	return get_metadata( 'rcp_membership', $membership_id, $key, $single );
}

/**
 * Update membership meta field based on membership ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and membership ID.
 *
 * If the meta field for the membership does not exist, it will be added.
 *
 * @param int    $membership_id Membership ID.
 * @param string $meta_key      Meta data key.
 * @param mixed  $meta_value    Meta data value. Must be serializable if non-scalar.
 * @param mixed  $prev_value    Optional. Previous value to check before removing. Default empty.
 *
 * @since 3.0
 * @return int|false Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function rcp_update_membership_meta( $membership_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'rcp_membership', $membership_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Delete everything from membership meta matching meta key.
 *
 * @param string $meta_key Key to search for when deleting.
 *
 * @since 3.0
 * @return bool Whether the membership meta key was deleted from the database.
 */
function rcp_delete_membership_meta_by_key( $meta_key ) {
	return delete_metadata( 'rcp_membership', null, $meta_key, '', true );
}