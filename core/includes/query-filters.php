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
		// Use database-level filtering instead of post__not_in for better performance.
		add_filter( 'posts_where', 'rcp_filter_premium_posts_where', 10, 2 );
	}
}
add_action( 'pre_get_posts', 'rcp_hide_premium_posts', 99999 );

/**
 * Filters the WHERE clause to exclude premium posts at the database level.
 * This is more efficient than using post__not_in for sites with many posts.
 *
 * @since 3.5.47
 *
 * @param string   $where The WHERE clause of the query.
 * @param WP_Query $query The WP_Query instance.
 *
 * @return string Modified WHERE clause.
 */
function rcp_filter_premium_posts_where( $where, $query ) {
	global $wpdb;

	// Add JOIN for meta tables if not already present.
	if (
		! strpos( $where, 'INNER JOIN' )
		&& ! strpos( $where, 'LEFT JOIN' )
	) {
		$query->set(
			'meta_query',
			[
				'relation' => 'OR',
				[
					'key'   => '_is_paid',
					'value' => '1',
				],
				[
					'key' => 'rcp_subscription_level',
				],
				[
					'key'     => 'rcp_user_level',
					'value'   => 'All',
					'compare' => '!=',
				],
				[
					'key'     => 'rcp_access_level',
					'value'   => 'None',
					'compare' => '!=',
				],
			]
		);
	}

	// Add WHERE clause to exclude posts with premium restrictions.
	$where .= " AND NOT EXISTS (
		SELECT 1 FROM {$wpdb->postmeta} pm
		WHERE pm.post_id = {$wpdb->posts}.ID
		AND (
			(pm.meta_key = '_is_paid' AND pm.meta_value = '1') OR
			(pm.meta_key = 'rcp_subscription_level' AND pm.meta_value != '') OR
			(pm.meta_key = 'rcp_user_level' AND pm.meta_value != 'All') OR
			(pm.meta_key = 'rcp_access_level' AND pm.meta_value != 'None')
		)
	)";

	// Also exclude posts that are assigned to restricted taxonomy terms.
	$where .= " AND NOT EXISTS (
		SELECT 1 FROM {$wpdb->term_relationships} tr
		INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
		INNER JOIN {$wpdb->termmeta} tm ON tt.term_id = tm.term_id
		WHERE tr.object_id = {$wpdb->posts}.ID
		AND tm.meta_key = 'rcp_restricted_meta'
		AND tm.meta_value != ''
		AND tm.meta_value NOT LIKE '%s:13:\"access_level\";s:4:\"None\"%'
	)";

	// Remove this filter to prevent it from affecting other queries.
	remove_filter( 'posts_where', 'rcp_filter_premium_posts_where', 10 );

	return $where;
}

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

	// Use database-level filtering instead of post__not_in for better performance.
	// Handle both post-level meta restrictions and term-based restrictions.
	$args['meta_query'] = [
		'relation' => 'AND',
		[
			'relation' => 'OR',
			[
				'key'     => '_is_paid',
				'value'   => '1',
				'compare' => '!=',
			],
			[
				'key'     => '_is_paid',
				'compare' => 'NOT EXISTS',
			],
		],
		[
			'relation' => 'OR',
			[
				'key'     => 'rcp_subscription_level',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => 'rcp_subscription_level',
				'value'   => '',
				'compare' => '=',
			],
		],
		[
			'relation' => 'OR',
			[
				'key'     => 'rcp_user_level',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => 'rcp_user_level',
				'value'   => 'All',
				'compare' => '=',
			],
		],
		[
			'relation' => 'OR',
			[
				'key'     => 'rcp_access_level',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => 'rcp_access_level',
				'value'   => 'None',
				'compare' => '=',
			],
		],
	];

	return $args;
}

/**
 * Filters REST API queries to exclude posts assigned to restricted taxonomy terms.
 * This handles the term-based restrictions that can't be handled by meta_query alone.
 *
 * @since 3.5.47
 * @deprecated 3.5.52
 *
 * @param array<string, mixed> $args    The query arguments.
 * @param WP_REST_Request      $request The REST request object.
 *
 * @return array<string,mixed> Modified query arguments.
 */
function rcp_filter_rest_api_restricted_posts( $args, $request ) {
	_deprecated_function( __FUNCTION__, '3.5.52' );

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
