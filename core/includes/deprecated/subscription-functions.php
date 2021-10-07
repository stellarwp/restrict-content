<?php
/**
 * Membership Level Functions
 *
 * Functions for getting non-member specific info about membership levels.
 *
 * @package     Restrict Content Pro
 * @subpackage  Subscription Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Gets an array of all available membership levels
 *
 * @deprecated 3.4 In favour of `rcp_get_membership_levels()`
 * @see        rcp_get_membership_levels()
 *
 * @param string $status The status of membership levels we want to retrieve: active, inactive, or all
 *
 * @return RCP\Membership_Level[]|false Array of objects if levels exist, false otherwise
 */
function rcp_get_subscription_levels( $status = 'all' ) {

	_deprecated_function( __FUNCTION__, '3.4', 'rcp_get_membership_levels' );

	$args = array(
		'number' => 999 // This function used to retrieve all results.
	);

	if ( in_array( $status, array( 'active', 'inactive' ) ) ) {
		$args['status'] = $status;
	}

	return rcp_get_membership_levels( $args );
}

/**
 * Gets all details of a specified membership level
 *
 * @deprecated 3.4 In favour of `rcp_get_membership_level()`
 * @see        rcp_get_membership_level()
 *
 * @param int $id The ID of the membership level to retrieve.
 *
 * @return object|false Object on success, false otherwise.
 */
function rcp_get_subscription_details( $id ) {

	_deprecated_function( __FUNCTION__, '3.4', 'rcp_get_membership_level' );

	return rcp_get_membership_level( $id );
}

/**
 * Gets all details of a specific membership level
 *
 * @deprecated 3.4 In favour of `rcp_get_membership_level_by()`
 * @see        rcp_get_membership_level_by()
 *
 * @param string $name The name of the membership level to retrieve.
 *
 * @return object|false Object on success, false otherwise.
 */
function rcp_get_subscription_details_by_name( $name ) {

	_deprecated_function( __FUNCTION__, '3.4', 'rcp_get_membership_level_by' );

	return rcp_get_membership_level_by( 'name', $name );
}
