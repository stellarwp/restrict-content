<?php
/**
 * Membership Level Actions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Increment membership level count when a membership's status changes.
 *
 * @param string $old_status
 * @param string $new_status
 * @param int    $membership_id
 *
 * @since 3.0
 * @return void
 */
function rcp_increment_membership_level_count_on_status_change( $old_status, $new_status, $membership_id ) {

	$membership = rcp_get_membership( $membership_id );
	$level_id   = $membership->get_object_id();

	if ( $new_status === $old_status ) {
		return;
	}

	rcp_increment_subscription_member_count( $level_id, $new_status );

	// If this is a brand new membership, the old status will be "new", which doesn't actually exist.
	if ( ! empty( $old_status ) && 'new' != $old_status ) {
		rcp_decrement_subscription_member_count( $level_id, $old_status );
	}

}

add_action( 'rcp_transition_membership_status', 'rcp_increment_membership_level_count_on_status_change', 10, 3 );

/**
 * Decrement membership level count when a membership is disabled.
 *
 * @param int            $membership_id
 * @param RCP_Membership $membership
 *
 * @since 3.0
 * @return void
 */
function rcp_decrement_membership_level_count_on_disable( $membership_id, $membership ) {

	$level_id = $membership->get_object_id();
	$status   = $membership->get_status();

	rcp_decrement_subscription_member_count( $level_id, $status );

}

add_action( 'rcp_membership_post_disable', 'rcp_decrement_membership_level_count_on_disable', 10, 2 );