<?php
/**
 * Meta Box
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Meta Box
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Get metabox fields
 *
 * @return array
 */
function rcp_get_metabox_fields() {

	//custom meta boxes
	$rcp_prefix = 'rcp_';

	$rcp_meta_box  = array(
		'id'       => 'rcp_meta_box',
		'title'    => __( 'Restrict this content', 'rcp' ),
		'context'  => 'normal',
		'priority' => apply_filters( 'rcp_metabox_priority', 'high' ),
		'fields'   => array() // No longer used
	);

	return apply_filters( 'rcp_metabox_fields', $rcp_meta_box );
}

/**
 * Add meta box to supported post types
 *
 * @uses rcp_get_metabox_fields()
 * @uses rcp_get_metabox_post_types()
 *
 * @return void
 */
function rcp_add_meta_boxes() {
	$rcp_meta_box = rcp_get_metabox_fields();

	foreach ( rcp_get_metabox_post_types() as $post_type ) {
		add_meta_box( $rcp_meta_box['id'], $rcp_meta_box['title'], 'rcp_render_meta_box', $post_type, $rcp_meta_box['context'], $rcp_meta_box['priority'] );
	}
}
add_action( 'add_meta_boxes', 'rcp_add_meta_boxes' );

/**
 * Returns all post types the meta box should be added to.
 *
 * @since 2.9
 * @return array
 */
function rcp_get_metabox_post_types() {
	$post_types   = get_post_types( array( 'public' => true, 'show_ui' => true ) );
	$post_types   = (array) apply_filters( 'rcp_metabox_post_types', $post_types );
	$exclude      = apply_filters( 'rcp_metabox_excluded_post_types', array( 'forum', 'topic', 'reply', 'product', 'attachment' ) );

	return array_diff( $post_types, $exclude );
}


/**
 * Callback function to show fields in meta box
 *
 * @return void
 */
function rcp_render_meta_box() {
	global $post;

	$rcp_meta_box = rcp_get_metabox_fields();

	// Use nonce for verification
	echo '<input type="hidden" name="rcp_meta_box" value="'. wp_create_nonce( basename( __FILE__ ) ) . '" />';

	do_action( 'rcp_metabox_fields_before' );

	include RCP_PLUGIN_DIR . 'core/includes/admin/metabox-view.php';

	do_action( 'rcp_metabox_fields_after' );

}

/**
 * Save data from meta box
 *
 * @param int $post_id ID of the post being saved.
 *
 * @return void
 */
function rcp_save_meta_data( $post_id ) {

	// verify nonce
	if ( ! isset( $_POST['rcp_meta_box'] ) || ! wp_verify_nonce( $_POST['rcp_meta_box'], basename( __FILE__ ) ) ) {
		return;
	}

	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// check permissions
	if ( 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {

		return;

	}

	$is_paid     = false;
	$restrict_by = ! empty( $_POST['rcp_restrict_by'] ) ? sanitize_text_field( $_POST['rcp_restrict_by'] ) : 'unrestricted';

	switch( $restrict_by ) {

		case 'unrestricted' :

			delete_post_meta( $post_id, 'rcp_access_level' );
			delete_post_meta( $post_id, 'rcp_subscription_level' );
			delete_post_meta( $post_id, 'rcp_user_level' );

			break;


		case 'subscription-level' :

			$level_set = sanitize_text_field( $_POST['rcp_subscription_level_any_set'] );

			switch( $level_set ) {

				case 'any' :

					update_post_meta( $post_id, 'rcp_subscription_level', 'any' );

					break;

				case 'any-paid' :

					$is_paid = true;
					update_post_meta( $post_id, 'rcp_subscription_level', 'any-paid' );

					break;

				case 'specific' :

					$is_paid = true;

					$levels = array_map( 'absint', $_POST[ 'rcp_subscription_level' ] );

					foreach( $levels as $level ) {

						$price = rcp_get_subscription_price( $level );
						if( empty( $price ) ) {
							$is_paid = false;
							break;
						}

					}

					update_post_meta( $post_id, 'rcp_subscription_level', $levels );

					break;

			}

			// Remove unneeded fields
			delete_post_meta( $post_id, 'rcp_access_level' );

			break;


		case 'access-level' :

			update_post_meta( $post_id, 'rcp_access_level', absint( $_POST[ 'rcp_access_level' ] ) );

			// Remove unneeded fields
			delete_post_meta( $post_id, 'rcp_subscription_level' );

			break;

		case 'registered-users' :

			// Remove unneeded fields
			delete_post_meta( $post_id, 'rcp_access_level' );

			// Remove unneeded fields
			delete_post_meta( $post_id, 'rcp_subscription_level' );

			break;

	}

	global $rcp_options;

	$content_excerpts  = isset( $rcp_options['content_excerpts'] ) ? $rcp_options['content_excerpts'] : 'individual';
	$show_excerpt      = isset( $_POST['rcp_show_excerpt'] );
	$user_role         = ! empty( $_POST['rcp_user_level'] ) ? $_POST[ 'rcp_user_level' ] : 'all';

	if ( ! is_array( $user_role ) ) {
		$user_role = array( $user_role );
	}
	$user_role = array_map( 'sanitize_text_field', $user_role );

	if ( 'individual' === $content_excerpts && $show_excerpt ) {
		update_post_meta( $post_id, 'rcp_show_excerpt', $show_excerpt );
	} else {
		delete_post_meta( $post_id, 'rcp_show_excerpt' );
	}

	if ( ! empty( $_POST['rcp_restrict_by'] ) && 'unrestricted' !== $_POST['rcp_restrict_by'] ) {
		update_post_meta( $post_id, 'rcp_user_level', $user_role );
	}

	if ( $is_paid ) {
		update_post_meta( $post_id, '_is_paid', $is_paid );
	} else {
		delete_post_meta( $post_id, '_is_paid' );
	}

	do_action( 'rcp_save_post_meta', $post_id );

}
add_action( 'save_post', 'rcp_save_meta_data' );
