<?php
/**
 * Redirects
 *
 * @package     Restrict Content Pro
 * @subpackage  Redirects
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Redirect non-subscribed users away from a restricted post
 * If the redirect page is premium, users are sent to the home page
 *
 * @return void
 */
function rcp_redirect_from_premium_post() {
	global $rcp_options, $user_ID, $post, $wp_query;
	if ( empty( $rcp_options['hide_premium'] ) || current_user_can( 'manage_options' ) ) {
		return;
	}

	$member   = new RCP_Member( $user_ID ); // for backwards compatibility
	$customer = rcp_get_customer(); // currently logged in customer
	$redirect = false;

	if( is_singular() && ! rcp_user_can_access( get_current_user_id(), $post->ID ) ) {
		// Singular posts / pages.
		$redirect = true;
	} elseif( is_post_type_archive() && $wp_query->have_posts() && rcp_is_restricted_post_type( get_post_type() ) && ! rcp_user_can_access( get_current_user_id(), get_the_ID() ) ) {
		// Post type archives where the whole post type is restricted.
		$redirect = true;
	} elseif ( ( is_category() || is_tag() || is_tax() ) && get_queried_object_id() && ! rcp_user_can_access_term( get_current_user_id(), get_queried_object_id() ) ) {
		// Taxonomy archives where the term is restricted.
		$redirect = true;
	}

	// Bail if we don't need to redirect.
	if ( ! $redirect ) {
		return;
	}

	// Figure out the redirect URL.
	$redirect_page_id = $rcp_options['redirect_from_premium'];

	// Bail if we're on the specified redirect page... oops!
	if ( ! empty( $redirect_page_id ) && is_singular() && $redirect_page_id == $post->ID ) {
		return;
	}

	if ( ! empty( $redirect_page_id ) ) {
		$redirect_url = get_permalink( $rcp_options['redirect_from_premium'] );
	} else {
		$redirect_url = ! is_front_page() ? home_url() : false;
	}

	/**
	 * Filters the URL to redirect unauthorized users to.
	 *
	 * @param string       $redirect_url URL to redirect unauthorized users to.
	 * @param RCP_Member   $member       Deprecated member object.
	 * @param WP_Post      $post         Global post object.
	 * @param RCP_Customer $customer     Current customer object.
	 */
	$redirect_url = apply_filters( 'rcp_restricted_post_redirect_url', $redirect_url, $member, $post, $customer );

	if ( empty( $redirect_url ) ) {
		return;
	}

	wp_redirect( esc_url_raw( $redirect_url ) );
	exit;
}
add_action( 'template_redirect', 'rcp_redirect_from_premium_post', 999 );

/**
 * Hijack the default WP login URL and redirect users to custom login page
 *
 * @param string $login_url
 *
 * @return string
 */
function rcp_hijack_login_url( $login_url ) {
	global $rcp_options;
	if( isset( $rcp_options['hijack_login_url'], $rcp_options['login_redirect'] ) ) {

		$do_hijack = empty( $_REQUEST['interim-login'] );

		/**
		 * Filters whether the login URL should be hijacked.
		 *
		 * @since 3.4.3
		 *
		 * @param bool $do_hijack Should the hijacking occur.
		 */
		$do_hijack = apply_filters( 'rcp_do_login_hijack', $do_hijack );

		if ( $do_hijack ) {
			$login_url = get_permalink( $rcp_options['login_redirect'] );
		}
	}
	return $login_url;
}
add_filter( 'login_url', 'rcp_hijack_login_url' );


/**
 * Redirects users to the custom login page when access wp-login.php
 *
 * @return void
 */
function rcp_redirect_from_wp_login() {
	global $rcp_options;

	if( isset( $rcp_options['hijack_login_url'], $rcp_options['login_redirect'] ) ) {
		$do_hijack = empty( $_REQUEST['interim-login'] );

		// This filter is documented in includes/redirects.php
		$do_hijack = apply_filters( 'rcp_do_login_hijack', $do_hijack );

		if ( ! $do_hijack ) {
			return;
		}

		if ( ! empty( $_GET['redirect_to'] ) ) {
			$login_url = add_query_arg( 'redirect', urlencode( $_GET['redirect_to'] ), get_permalink( $rcp_options['login_redirect'] ) );
		} else {
			$login_url = get_permalink( $rcp_options['login_redirect'] );
		}

		wp_redirect( esc_url_raw( $login_url ) ); exit;
	}
}
add_action( 'login_form_login', 'rcp_redirect_from_wp_login' );

/**
 * If "Redirect Default Login URL" is enabled then we filter the lost password URL to use
 * the designated login page instead.
 *
 * @param string $lostpassword_url
 * @param string $redirect
 *
 * @since 3.2.2
 * @return string
 */
function rcp_filter_lostpassword_url( $lostpassword_url, $redirect ) {

	global $rcp_options;

	if ( empty( $rcp_options['hijack_login_url'] ) || empty( $rcp_options['login_redirect'] ) ) {
		return $lostpassword_url;
	}

	$lostpassword_url = add_query_arg( 'rcp_action', 'lostpassword', get_permalink( $rcp_options['login_redirect'] ) );

	return $lostpassword_url;

}
add_filter( 'lostpassword_url', 'rcp_filter_lostpassword_url', 10, 2 );
