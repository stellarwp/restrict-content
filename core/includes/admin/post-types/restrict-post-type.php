<?php
/**
 * Page for Restricting a Post Type
 *
 * @package     restrict-content-pro
 * @subpackage  Admin/Restrict Post Type
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     GPL2+
 * @since       2.9
 */

use RCP\Membership_Level;

/**
 * Render page for restricting an entire post type.
 *
 * @since 2.9
 * @return void
 */
function rcp_restrict_post_type_page() {

	$screen            = get_current_screen();
	$post_type         = ! empty( $screen->post_type ) ? $screen->post_type : 'post';
	$post_type_details = get_post_type_object( $post_type );
	?>
	<div class="wrap">
		<h1><?php printf( __( 'Restrict All %s', 'rcp' ), $post_type_details->labels->name ); ?></h1>

		<div class="metabox-holder">
			<div class="postbox">
				<div class="inside">
					<?php
					do_action( 'rcp_restrict_post_type_fields_before' );

					include RCP_PLUGIN_DIR . 'core/includes/admin/post-types/restrict-post-type-view.php';

					do_action( 'rcp_restrict_post_type_fields_after' );
					?>
				</div>
			</div>
		</div>
	</div>
	<?php

}

/**
 * Save post type restrictions
 *
 * @since 2.9
 * @return void
 */
function rcp_save_post_type_restrictions() {

	if ( ! isset( $_POST['rcp_save_post_type_restrictions_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_save_post_type_restrictions_nonce'], 'rcp_save_post_type_restrictions' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	$post_type = isset( $_POST['rcp_post_type'] ) ? $_POST['rcp_post_type'] : 'post';

	// Check permissions
	$post_type_details = get_post_type_object( $post_type );
	$capability        = isset( $post_type_details->cap->edit_posts ) ? $post_type_details->cap->edit_posts : 'edit_posts';
	if ( ! current_user_can( $capability ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	$is_paid                     = false;
	$restrict_by                 = sanitize_text_field( $_POST['rcp_restrict_by'] );
	$restricted_post_types       = rcp_get_restricted_post_types();
	$this_post_type_restrictions = rcp_get_post_type_restrictions( $post_type );

	if ( ! is_array( $this_post_type_restrictions ) ) {
		$this_post_type_restrictions = array();
	}

	$levels = rcp_get_membership_levels( array( 'number' => 999 ) );

	switch ( $restrict_by ) {

		case 'unrestricted' :

			unset( $this_post_type_restrictions['access_level'] );
			unset( $this_post_type_restrictions['subscription_level'] );
			unset( $this_post_type_restrictions['user_level'] );

			break;


		case 'subscription-level' :

			$level_set = sanitize_text_field( $_POST['rcp_subscription_level_any_set'] );

			switch ( $level_set ) {

				case 'any' :

					$this_post_type_restrictions['subscription_level'] = 'any';

					break;

				case 'any-paid' :

					$is_paid                                           = true;
					$this_post_type_restrictions['subscription_level'] = 'any-paid';

					break;

				case 'specific' :

					$levels = array_map( 'absint', $_POST['rcp_subscription_level'] );

					foreach ( $levels as $level_id ) {

						$level = rcp_get_membership_level( $level_id );

						if ( $level instanceof Membership_Level && ! $level->is_free() ) {
							$is_paid = true;
							break;
						}

					}

					$this_post_type_restrictions['subscription_level'] = $levels;

					break;

			}

			// Remove unneeded fields
			unset( $this_post_type_restrictions['access_level'] );

			break;


		case 'access-level' :

			$this_post_type_restrictions['access_level'] = absint( $_POST['rcp_access_level'] );

			foreach ( $levels as $level ) {

				if ( ! $level->is_free() ) {
					$is_paid = true;
					break;
				}

			}

			// Remove unneeded fields
			unset( $this_post_type_restrictions['subscription_level'] );

			break;

		case 'registered-users' :

			// Remove unneeded fields
			unset( $this_post_type_restrictions['access_level'] );

			// Remove unneeded fields
			unset( $this_post_type_restrictions['subscription_level'] );

			foreach ( $levels as $level ) {

				if ( ! $level->is_free() ) {
					$is_paid = true;
					break;
				}

			}

			break;

	}


	$user_role = ! empty( $_POST['rcp_user_level'] ) ? $_POST[ 'rcp_user_level' ] : 'all';

	if ( ! is_array( $user_role ) ) {
		$user_role = array( $user_role );
	}
	$user_role = array_map( 'sanitize_text_field', $user_role );

	if ( 'unrestricted' !== $_POST['rcp_restrict_by'] ) {
		$this_post_type_restrictions['user_level'] = $user_role;
	}

	if ( $is_paid ) {
		$this_post_type_restrictions['is_paid'] = $is_paid;
	} else {
		unset( $this_post_type_restrictions['is_paid'] );
	}

	// Save the restrictions.
	if ( ! empty( $this_post_type_restrictions ) ) {
		$restricted_post_types[ $post_type ] = $this_post_type_restrictions;
	} else {
		unset( $restricted_post_types[ $post_type ] );
	}

	if ( empty( $restricted_post_types ) ) {
		delete_option( 'rcp_restricted_post_types' );
	} else {
		update_option( 'rcp_restricted_post_types', $restricted_post_types );
	}

	do_action( 'rcp_save_post_type_restrictions', $post_type );

	$url = add_query_arg( array(
		'rcp_message' => 'post-type-updated'
	), rcp_get_restrict_post_type_page_url( $post_type ) );

	wp_safe_redirect( $url );
	exit;

}

add_action( 'rcp_action_save_post_type_restrictions', 'rcp_save_post_type_restrictions' );

/**
 * Returns the URL to the post type restriction page
 *
 * @param string $post_type
 *
 * @since 2.9
 * @return string
 */
function rcp_get_restrict_post_type_page_url( $post_type = '' ) {
	if ( empty( $post_type ) ) {
		$post_type = get_post_type();
	}

	if ( 'post' == $post_type ) {
		$restrict_url = add_query_arg( array( 'page' => 'rcp-restrict-post-type' ), admin_url( 'edit.php' ) );
	} else {
		$restrict_url = add_query_arg( array(
			'post_type' => urlencode( $post_type ),
			'page'      => urlencode( 'rcp-restrict-post-type-' . $post_type )
		), admin_url( 'edit.php' ) );
	}

	return $restrict_url;
}
