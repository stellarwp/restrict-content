<?php
/**
 * Content Filters
 *
 * Filters for hiding restricted post and page content.
 *
 * @package     Restrict Content Pro
 * @subpackage  Content Filters
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Filter the content based upon the "Restrict this content" metabox configuration.
 *
 * @param string $content Existing post content.
 *
 * @return string Newly modified post content (possibly with teaser).
 */
function rcp_filter_restricted_content( $content ) {
	global $post, $rcp_options;

	if (
		$post
		&& ! rcp_user_can_access( get_current_user_id(), $post->ID )
	) {
		$message = rcp_get_restricted_content_message();
		return rcp_format_teaser( $message );
	}

	return $content;
}

add_filter( 'the_content', 'rcp_filter_restricted_content' , 100 );

/**
 * Remove comments from posts/pages if user does not have access
 *
 * @since 2.6
 * @param string $template Path to template file to load
 *
 * @return string Path to template file to load
 */
function rcp_hide_comments( $template ) {

	$post_id = get_the_ID();

	if( ! empty( $post_id ) ) {

		if( ! rcp_user_can_access( get_current_user_id(), $post_id ) ) {

			$template = rcp_get_template_part( 'comments', 'no-access', false );

		}

	}

	return $template;
}
add_filter( 'comments_template', 'rcp_hide_comments', 9999999 );

/**
 * Format the teaser message. Default excerpt length is 50 words.
 *
 * @uses  rcp_excerpt_by_id()
 *
 * @param string $message Message to add to the end of the excerpt.
 *
 * @return string Formatted teaser with message appended.
 */
function rcp_format_teaser( $message ) {
	global $post, $rcp_options;

	$show_excerpt = isset( $rcp_options['content_excerpts'] ) ? $rcp_options['content_excerpts'] : 'individual';

	if ( 'always' == $show_excerpt || ( 'individual' == $show_excerpt && get_post_meta( $post->ID, 'rcp_show_excerpt', true ) ) ) {
		$excerpt_length = apply_filters( 'rcp_filter_excerpt_length', 100 );
		$excerpt        = rcp_excerpt_by_id( $post, $excerpt_length );
		$message        = apply_filters( 'rcp_restricted_message', $message );
		$message        = $excerpt . $message;
	} else {
		$message = apply_filters( 'rcp_restricted_message', $message );
	}

	return $message;
}

/**
 * Wrap the restricted message in paragraph tags and allow for shortcodes to be used.
 *
 * @param string $message Restricted content message.
 *
 * @return string
 */
function rcp_restricted_message_filter( $message ) {
	return do_shortcode( wpautop( $message ) );
}
add_filter( 'rcp_restricted_message', 'rcp_restricted_message_filter', 10, 1 );

/**
 * Display pending email verification message when trying to access restricted content
 *
 * @param string $message The original message.
 *
 * @return string Modified or original message based on verification status.
 */
function rcp_restricted_message_pending_verification( $message ) {

	global $rcp_load_css;

	if ( rcp_is_pending_verification() ) {
		$rcp_load_css = true;

		$message = '<div class="rcp_message error"><p class="rcp_error rcp_pending_member"><span>' . __( 'Your account is pending email verification.', 'rcp' ) . '</span></p></div>';
	}

	return $message;

}
add_filter( 'rcp_restricted_message', 'rcp_restricted_message_pending_verification', 9999 );

/**
 * Spoof password required during REST API requests in order to hide comments
 * associated with restricted posts.
 *
 * @param bool    $required Whether or not the post requires a password.
 * @param WP_Post $post     The post being checked.
 *
 * @since  2.7.4
 * @return bool
 */
function rcp_post_password_required_rest_api( $required, $post ) {

	if (
		$post !== null
		&& DEFINED( 'REST_REQUEST' )
		&& REST_REQUEST
		&& rcp_is_restricted_content( $post->ID )
		&& ! rcp_user_can_access( get_current_user_id(), $post->ID )
	) {
		$required = true;
	}

	return $required;
}

add_filter( 'post_password_required', 'rcp_post_password_required_rest_api', 10, 2 );
