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
 *
 * @since 2.2
 */
function rc_register_plugin_styles() {

	wp_register_style( 'rc-forms', trailingslashit( plugins_url() ) . 'restrict-content/includes/css/rc-forms.css', array(), '20170828' );
	wp_register_script( 'rc-register', trailingslashit( plugins_url() ) . 'restrict-content/includes/js/rc-register.js', array( 'jquery' ), '20170828' );

	if ( ! is_singular() ) {
		return;
	}

	global $post;

	if ( has_shortcode( $post->post_content, 'login_form' ) || has_shortcode( $post->post_content, 'register_form' ) ) {
		wp_enqueue_style( 'rc-forms' );
		wp_enqueue_script( 'rc-register' );
		wp_localize_script(
			'rc-register',
			'rc_register_options',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'rc-register-nonce' ),
				'strings' => array(
					'please_wait' => __( 'Please wait...', 'restrict-content' ),
					'register' => __( 'Register', 'restrict-content' ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'rc_register_plugin_styles' );