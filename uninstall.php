<?php
/**
 * Uninstall Restrict Content Pro
 *
 * Deletes the following plugin data:
 *      - RCP post meta
 *      - RCP term meta
 *      - Pages created and used by RCP
 *      - Clears scheduled RCP cron events
 *      - Options added by RCP
 *      - RCP database tables
 *
 * @package     Restrict Content Pro
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.8
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load RCP file.
include_once 'restrict-content.php';

global $wpdb;
$rcp_options = get_option( 'rcp_settings' );

if( isset( $rcp_options['remove_data_on_uninstall'] ) ) {

	if ( class_exists( 'Restrict_Content_Pro' ) ) {
		// This is kind of stupid but it ensures that the capabilities file (class used below) gets loaded.
		Restrict_Content_Pro::instance( dirname( __FILE__ ) . '/restrict-content-pro.php' );
	}

	// Delete all post meta.
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'rcp\_%' OR meta_key = '_is_paid'" );

	// Delete all term meta.
	$wpdb->query( "DELETE FROM $wpdb->termmeta WHERE meta_key = 'rcp_restricted_meta'" );

	// Delete all user meta.
	$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'rcp\_%' OR meta_key LIKE '\_rcp\_%'" );

	// Remove custom capabilities.
	$caps = new RCP_Capabilities;
	$caps->remove_caps();

	// Delete the plugin pages.
	$rcp_pages = array( 'registration_page', 'redirect', 'account_page', 'edit_profile', 'update_card' );
	foreach( $rcp_pages as $page_option ) {
		$page_id = isset( $rcp_options[ $page_option ] ) ? $rcp_options[ $page_option ] : false;
		if( $page_id ) {
			wp_trash_post( $page_id );
		}
	}

	// Clear scheduled cron events.
	wp_clear_scheduled_hook( 'rcp_expired_users_check' );
	wp_clear_scheduled_hook( 'rcp_send_expiring_soon_notice' );
	wp_clear_scheduled_hook( 'rcp_check_member_counts' );

	// Remove all plugin settings.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'rcp\_%'" );

	/**
	 * Our table names can be unpredictable, since they run through
	 * helper functions that have filters. You can't use $wpdb::prepare()
	 * to prepare table names, so we run them through esc_sql here
	 * just to make sure they're safe.
	 */
	$table_payments     = esc_sql( rcp_get_payments_db_name() );
	$table_payment_meta = esc_sql( rcp_get_payment_meta_db_name() );
	$table_levels       = esc_sql( rcp_get_levels_db_name() );
	$table_level_meta   = esc_sql( rcp_get_level_meta_db_name() );

	$wpdb->query( "DROP TABLE IF EXISTS {$table_payments}" );
	$wpdb->query( "DROP TABLE IF EXISTS {$table_payment_meta}" );
	$wpdb->query( "DROP TABLE IF EXISTS {$table_levels}" );
	$wpdb->query( "DROP TABLE IF EXISTS {$table_level_meta}" );

	// Drop all other tables.
	foreach ( restrict_content_pro()->components as $component ) {
		$table = $component->get_interface( 'table' );

		if ( $table instanceof \RCP\Database\Table && $table->exists() ) {
			$table->uninstall();
		}

		// Drop associated meta table if there is one.
		$meta = $component->get_interface( 'meta' );
		if ( $meta instanceof \RCP\Database\Table && $meta->exists() ) {
			$meta->uninstall();
		}
	}

	// Delete database versions from options table.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wpdb\_rcp\_%'" );
	// Delete database version from options table.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'wpdb_restrict_content_pro_version'" );

}
