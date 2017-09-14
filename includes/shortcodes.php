<?php
/**
 * Shortcodes
 *
 * @package     Restrict Content
 * @subpackage  Shortcodes
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Restrict content to a user level
 *
 * @param array       $atts    Shortcode attributes.
 * @param string|null $content Shortcode content.
 *
 * @return string
 */
function restrict_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts( array(
		'userlevel' => 'none',
	), $atts, 'restrict' );

	global $rc_options;

	if ( $atts['userlevel'] == 'admin' && current_user_can( 'switch_themes' ) ) {
		return do_shortcode( $content );
	}
	if ( $atts['userlevel'] == 'editor' && current_user_can( 'moderate_comments' ) ) {
		return do_shortcode( $content );
	}
	if ( $atts['userlevel'] == 'author' && current_user_can( 'upload_files' ) ) {
		return do_shortcode( $content );
	}
	if ( $atts['userlevel'] == 'contributor' && current_user_can( 'edit_posts' ) ) {
		return do_shortcode( $content );
	}
	if ( $atts['userlevel'] == 'subscriber' && current_user_can( 'read' ) ) {
		return do_shortcode( $content );
	}
	if ( $atts['userlevel'] == 'none' && is_user_logged_in() ) {
		return do_shortcode( $content );
	} else {
		return '<span style="color: red;">' . str_replace( '{userlevel}', $atts['userlevel'], $rc_options['shortcode_message'] ) . '</span>';
	}
}

add_shortcode( 'restrict', 'restrict_shortcode' );

/**
 * Displays content to users who are not logged in
 *
 * @param array       $atts    Shortcode attributes.
 * @param string|null $content Shortcode content.
 *
 * @return string|void
 */
function rc_not_logged_in( $atts, $content = null ) {
	if ( ! is_user_logged_in() ) {
		return do_shortcode( $content );
	}
}

add_shortcode( 'not_logged_in', 'rc_not_logged_in' );

/**
 * Adds the [login_form] shortcode
 *
 * @since 2.2
 * @param array $atts Shortcode attributes
 *
 * @return string
 */
function rc_login_form_shortcode( $atts ) {

	$atts = shortcode_atts( array(
		'redirect' => rc_get_current_url(),
		'class'    => 'rc_form'
	), $atts, 'login_form' );

	return rc_login_form_fields( $atts );
}
add_shortcode( 'login_form', 'rc_login_form_shortcode' );

/**
 * Adds the [register_form] shortcode
 *
 * @since 2.2
 * @param array $atts Shortcode attributes
 *
 * @return string
 */
function rc_register_form_shortcode( $atts ) {

	$atts = shortcode_atts( array(
		'redirect'           => rc_get_current_url(),
		'class'              => 'rc_form',
		'registered_message' => __( 'You are already registered.', 'restrict-content' ),
		'logged_out_header'  => __( 'Register New Account', 'restrict-content' )
	), $atts, 'register_form' );

	return rc_register_form_fields( $atts );
}
add_shortcode( 'register_form', 'rc_register_form_shortcode' );