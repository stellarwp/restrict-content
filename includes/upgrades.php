<?php
/**
 * Upgrade routines
 *
 * @package     Restrict Content
 * @subpackage  Upgrades
 * @copyright   Copyright (c) 2017, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

function rc_init_upgrades() {

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$version = preg_replace( '/[^0-9.].*/', '', get_option( 'rc_version' ) );

	if ( empty( $version ) ) {
		rc_v22_upgrades();
	}

}
add_action( 'admin_init', 'rc_init_upgrades', 0 );

/**
 * Updates the rcUserLevel post meta key name to match
 * the one used by Restrict Content Pro.
 *
 * @since 2.2
 */
function rc_v22_upgrades() {

	global $wpdb;

	$update = $wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = 'rcp_user_level' WHERE meta_key = 'rcUserLevel'"  );

	if ( false === $update ) {
		return;
	}

	update_option( 'rc_user_level_post_meta_updated', true );
	update_option( 'rc_version', RC_PLUGIN_VERSION );
}