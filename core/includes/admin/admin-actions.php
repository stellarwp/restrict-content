<?php
/**
 * Admin Actions
 *
 * @package     restrict-content-pro
 * @subpackage  Admin/Admin Actions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Process all RCP actions sent via POST and GET
 *
 * @since 2.9
 * @return void
 */
function rcp_process_actions() {
	if ( isset( $_POST['rcp-action'] ) ) {
		do_action( 'rcp_action_' . $_POST['rcp-action'], $_POST );
	}

	if ( isset( $_GET['rcp-action'] ) ) {
		do_action( 'rcp_action_' . $_GET['rcp-action'], $_GET );
	}
}
add_action( 'admin_init', 'rcp_process_actions' );

/**
 * Denotes the various RCP pages as such in the pages list table.
 *
 * @param array $post_states An array of post display states.
 * @param WP_Post $post The current post object.
 * @return array An array of post display states.
 */
function rcp_display_post_states( $post_states, $post ) {
	$rcp_options = get_option( 'rcp_settings' );
	$pages       = array(
		'registration_page' => __( 'Registration Page', 'rcp' ),
		'redirect'          => __( 'Registration Success Page', 'rcp' ),
		'account_page'      => __( 'Account Page', 'rcp' ),
		'edit_profile'      => __( 'Edit Profile Page', 'rcp' ),
		'update_card'       => __( 'Update Card Page', 'rcp' )
	);

	foreach( $pages as $key => $value ) {
		if( isset( $rcp_options[$key] ) && ( $post->ID === (int) $rcp_options[$key] ) ) {
			$post_states['rcp_'.$key] = $pages[$key];
		}
	}

	return $post_states;
}

add_filter( 'display_post_states', 'rcp_display_post_states', 10, 2 );