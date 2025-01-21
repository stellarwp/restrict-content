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
 * @param WP_Query $query The WP_Query instance.
 *
 * @return void
 */
function rcp_hide_premium_posts( $query ) {

	if ( ! $query->is_main_query() ) {
		return;
	}

	if ( is_admin() ) {
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

		$no_membership = empty( $memberships );

		if ( $no_membership ) {
			$hide_restricted_content = true;
		}

		// If this is a search query and the user doesn't have a membership, hide the restricted content.
		$is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;

		if ( $no_membership && $query->is_search() ) {
			$hide_restricted_content = true;
		} elseif ( $no_membership && $is_rest_request && isset( $query->query_vars['s'] ) ) {
			$hide_restricted_content = true;
		}
	}

	// If this customer doesn't have any active memberships - hide it.

	if ( $hide_restricted_content ) {
		$premium_ids              = rcp_get_restricted_post_ids();
		$term_restricted_post_ids = rcp_get_post_ids_assigned_to_restricted_terms();
		$post_ids                 = array_unique( array_merge( $premium_ids, $term_restricted_post_ids ) );

		if ( $post_ids ) {
			$existing_not_in = is_array( $query->get( 'post__not_in' ) ) ? $query->get( 'post__not_in' ) : array();
			$post_ids        = array_unique( array_merge( $post_ids, $existing_not_in ) );

			$query->set( 'post__not_in', $post_ids );
		}
	}
}
add_action( 'pre_get_posts', 'rcp_hide_premium_posts', 99999 );

/**
 * Hides all premium posts from non active subscribers in REST API.
 *
 * @since 3.5.45
 *
 * @param array<string,mixed> $args The query arguments.
 *
 * @return array<string,mixed>
 */
function rcp_hide_premium_posts_from_rest_api( $args ) {
	$customer = rcp_get_customer();

	$hide_restricted_content = false;

	// If this isn't a valid customer - hide it.
	if ( empty( $customer ) ) {
		$hide_restricted_content = true;
	} else {
		$memberships = rcp_count_memberships(
			[
				'customer_id' => $customer->get_id(),
				'status'      => [
					'active',
					'cancelled',
				],
			]
		);

		if ( empty( $memberships ) ) {
			$hide_restricted_content = true;
		}
	}

	if ( ! $hide_restricted_content ) {
		return $args;
	}

	if ( ! isset( $args['post__not_in'] ) ) {
		$args['post__not_in'] = [];
	}

	$args['post__not_in'] = array_unique(
		array_merge(
			(array) $args['post__not_in'],
			rcp_get_restricted_post_ids()
		)
	);

	return $args;
}

/**
 * Hides all premium posts from non active subscribers in REST API.
 *
 * @since 3.5.45
 *
 * @return void
 */
function rcp_hide_premium_content_from_rest_api() {
	global $rcp_options;

	if ( ! isset( $rcp_options['hide_premium'] ) ) {
		return;
	}

	$user = wp_get_current_user();

	if ( user_can( $user, 'manage_options' ) ) {
		return;
	}

	$post_types = get_post_types( [ 'public' => true ], 'names' );

	if ( ! empty( $post_types ) ) {
		foreach ( $post_types as $post_type ) {
			add_filter( 'rest_' . $post_type . '_query', 'rcp_hide_premium_posts_from_rest_api', 10 );
		}
	}

	add_filter( 'rest_post_search_query', 'rcp_hide_premium_posts_from_rest_api', 10 );
}

add_action( 'rest_api_init', 'rcp_hide_premium_content_from_rest_api' );
