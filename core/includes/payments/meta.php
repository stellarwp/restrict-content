<?php
/**
 * Payment Meta Functions
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
 * Add meta data field to a payment.
 *
 * @param int    $payment_id Payment ID.
 * @param string $meta_key   Meta data name.
 * @param mixed  $meta_value Meta data value. Must be serializable if non-scalar.
 * @param bool   $unique     Optional. Whether the same key should not be added. Default false.
 *
 * @since 3.1
 * @return false|int
 */
function rcp_add_payment_meta( $payment_id, $meta_key, $meta_value, $unique = false ) {
	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	return $rcp_payments_db->add_meta( $payment_id, $meta_key, $meta_value, $unique );
}

/**
 * Remove meta data matching criteria from a payment.
 *
 * You can match based on the key, or key and value. Removing based on key and value, will keep from removing duplicate
 * meta data with the same key. It also allows removing all meta data matching key, if needed.
 *
 * @param int    $payment_id Payment ID.
 * @param string $meta_key   Meta data name.
 * @param mixed  $meta_value Meta data value. Must be serializable if non-scalar. Default empty.
 *
 * @since 3.1
 * @return false|int
 */
function rcp_delete_payment_meta( $payment_id, $meta_key, $meta_value = '' ) {
	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	return $rcp_payments_db->delete_meta( $payment_id, $meta_key, $meta_value );
}

/**
 * Retrieve payment meta field for a payment.
 *
 * @param  int   $payment_id Payment ID.
 * @param string $key        Optional. The meta key to retrieve. By default, returns data for all keys. Default
 *                           empty.
 * @param bool   $single     Optional, default is false. If true, return only the first value of the specified
 *                           meta_key. This parameter has no effect if meta_key is not specified.
 *
 * @since 3.1
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
function rcp_get_payment_meta( $payment_id, $key = '', $single = false ) {
	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	return $rcp_payments_db->get_meta( $payment_id, $key, $single );
}

/**
 * Update payment meta field based on payment ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and payment ID.
 *
 * If the meta field for the payment does not exist, it will be added.
 *
 * @param int    $payment_id Payment ID.
 * @param string $meta_key   Meta data key.
 * @param mixed  $meta_value Meta data value. Must be serializable if non-scalar.
 * @param mixed  $prev_value Optional. Previous value to check before removing. Default empty.
 *
 * @since 3.1
 * @return int|false Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function rcp_update_payment_meta( $payment_id, $meta_key, $meta_value, $prev_value = '' ) {
	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	return $rcp_payments_db->update_meta( $payment_id, $meta_key, $meta_value, $prev_value );
}