<?php
/**
 * Query Filters
 *
 * @package     Restrict Content Pro
 * @subpackage  Query Filters
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Hides all premium posts from non active subscribers
 *
 * @param WP_Query $query
 *
 * @return void
 */
function rcp_hide_premium_posts( $query ) {

	if ( ! $query->is_main_query() ) {
		return;
	}

	global $rcp_options, $user_ID;

	$suppress_filters = isset( $query->query_vars['suppress_filters'] );

	if ( ! isset( $rcp_options['hide_premium'] ) || is_singular() || $suppress_filters || user_can( $user_ID, 'manage_options' ) ) {
		return;
	}

	$hide_restricted_content = false;

	$customer = rcp_get_customer(); // current customer

	// If this isn't a valid customer - hide it.
	if ( empty( $customer ) ) {
		$hide_restricted_content = true;
	} else {
		$memberships = rcp_count_memberships( array(
			'customer_id' => $customer->get_id(),
			'status'      => array( 'active', 'cancelled' )
		) );

		if ( empty( $memberships ) ) {
			$hide_restricted_content = true;
		}
	}

	// If this customer doesn't have any active memberships - hide it.

	if( $hide_restricted_content ) {
		$premium_ids              = rcp_get_restricted_post_ids();
		$term_restricted_post_ids = rcp_get_post_ids_assigned_to_restricted_terms();
		$post_ids                 = array_unique( array_merge( $premium_ids, $term_restricted_post_ids ) );

		if( $post_ids ) {
			$existing_not_in = is_array( $query->get( 'post__not_in' ) ) ? $query->get( 'post__not_in' ) : array();
			$post_ids        = array_unique( array_merge( $post_ids, $existing_not_in ) );

			$query->set( 'post__not_in', $post_ids );
		}
	}
}
add_action( 'pre_get_posts', 'rcp_hide_premium_posts', 99999 );
