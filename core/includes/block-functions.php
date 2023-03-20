<?php
/**
 * Block Functions
 *
 * Handles creation of Block Category, Registering blocks, and providing data to blocks
 *
 * @package Restrict Content Pro
 * @copyright Copyright (c) 2021, Restrict Content Pro
 * @license http://opensource.org/license/gpl-2.1.php GNU Public License
 */

/**
 * Add the RCP category to the blocks menu
 *
 * @since 3.5.8 Add the Restrict Content Pro Category
 */
function rcp_add_restrict_content_pro_block_category( $categories ) {
	$categories[] = array (
		'slug' 	=> 'restrict-content-pro',
		'icon' 	=> 'lock',
		'title' => __( 'Restrict Content Pro', 'rcp' ),
	);
	return $categories;
}

if ( rcp_wp_version_compare( '5.8', '<' ) ) {
	add_filter( 'block_categories', 'rcp_add_restrict_content_pro_block_category' );
} else {
	add_filter( 'block_categories_all', 'rcp_add_restrict_content_pro_block_category' );
}

/**
 * Add the global rcp pages to the script for the content upgrade redirect block
 *
 * @since 3.5.8
 */
function rcp_enqueue_block_editor_assets_for_content_upgrade_redirect() {
	$content_redirect_upgrade_editor_script_handle = generate_block_asset_handle(
		'restrict-content-pro/content-upgrade-redirect',
		'editorScript'
	);
	global $rcp_options;

	$script = 'var rcp_default_registration_page = ' . wp_json_encode( get_permalink( $rcp_options['registration_page'] ) ) . '; ';
	$script .= 'var rcp_default_account_page = ' . wp_json_encode( get_permalink( $rcp_options['account_page'] ) ) . '; ';
	wp_add_inline_script( $content_redirect_upgrade_editor_script_handle, $script );
}
add_action( 'enqueue_block_editor_assets', 'rcp_enqueue_block_editor_assets_for_content_upgrade_redirect' );

/**
 * Registers the RCP blocks so that they can be used in the block editor
 *
 * @since 3.5.8
 */
function rcp_register_blocks_init() {
	// Register the registration redirect block using blocks.json
	register_block_type_from_metadata(
		RCP_PLUGIN_DIR . '/core/src/blocks/content-upgrade-redirect/',
		array ()
	);
}
add_action( 'init', 'rcp_register_blocks_init' );
