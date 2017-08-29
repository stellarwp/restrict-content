<?php
/**
 * Scripts and Styles
 *
 * @package    Restrict Content
 * @subpackage Scripts
 * @copyright  Copyright (c) 2017, Sandhills Development, LLC
 * @license    GPLv2
 */

/**
 * Registers and enqueues the form CSS file.
 */
function rc_register_plugin_styles() {

	wp_register_style( 'rc-forms', trailingslashit( plugins_url() ) . 'restrict-content/includes/css/rc-forms.css', array(), '20170828' );

	if ( ! is_singular() ) {
		return;
	}

	global $post;

	if ( has_shortcode( $post->post_content, 'login_form' ) || has_shortcode( $post->post_content, 'register_form' ) ) {
		wp_enqueue_style( 'rc-forms' );
	}
}
add_action( 'wp_enqueue_scripts', 'rc_register_plugin_styles' );