<?php
/**
 * Upgrades
 *
 * @package     Kadence Memberships Pro
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2017, Kadence Memberships Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Check if upgrade is needed
 *
 * @return bool
 */
function rcp_check_if_upgrade_needed() {
	global $rcp_db_version, $rcp_discounts_db_version, $rcp_payments_db_version;

	if ( version_compare( RCP_PLUGIN_VERSION, '2.6', '>=' ) && version_compare( get_option( 'rcp_version' ), '2.5', '>=' ) ) {

		// New RCP_Upgrades class was introduced with 2.6, see https://github.com/restrictcontentpro/restrict-content-pro/issues/511.

		return false;
	}

	if ( version_compare( $rcp_db_version, get_option( 'rcp_db_version' ), '>' ) ) {
		return true;
	}
	if ( version_compare( $rcp_discounts_db_version, get_option( 'rcp_discounts_db_version' ), '>' ) ) {
		return true;
	}
	if ( version_compare( $rcp_payments_db_version, get_option( 'rcp_payments_db_version' ), '>' ) ) {
		return true;
	}
	if ( ! get_option( 'rcp_version' ) || version_compare( get_option( 'rcp_version' ), '2.3', '<' ) ) {
		return true;
	}
	return false;
}
add_action( 'admin_init', 'rcp_check_if_upgrade_needed' );

/**
 * Run upgrade
 *
 * @since 1.1.7.2
 * @since 4.0.10 Hardened the upgrade request handling.
 *
 * @uses rcp_check_if_upgrade_needed()
 *
 * @return void
 */
function rcp_run_upgrade() {
	if (
		isset( $_GET['rcp-action'], $_GET['_wpnonce'] )
		&& 'upgrade' === $_GET['rcp-action']
		&& current_user_can( 'rcp_manage_settings' )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rcp_upgrade' )
		&& rcp_check_if_upgrade_needed()
	) {
		rcp_options_upgrade();
		wp_safe_redirect( admin_url() );
		exit;
	}
}
add_action( 'admin_init', 'rcp_run_upgrade' );

/**
 * Options upgrade
 *
 * @param bool $network_wide Whether the plugin is being network activated.
 *
 * @return void
 */
function rcp_options_upgrade( $network_wide = false ) {

	/**
	 * If the plugin is being network activated, do the upgrades
	 * on the shutdown hook. Otherwise do it now.
	 *
	 * @see https://github.com/restrictcontentpro/restrict-content-pro/issues/669
	 */
	if ( $network_wide ) {
		add_action( 'shutdown', 'rcp_legacy_db_upgrades' );
	} else {
		rcp_legacy_db_upgrades();
	}
}
register_activation_hook( RCP_PLUGIN_FILE, 'rcp_options_upgrade' );

/**
 * Runs the legacy database upgrade routines.
 *
 * @since 2.7
 * @return void
 */
function rcp_legacy_db_upgrades() {

	global $wpdb, $rcp_db_version, $rcp_discounts_db_version, $rcp_payments_db_version;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Legacy schema changes on the plugin's own tables.

	// Upgrade the discount codes table.
	$rcp_discounts_db_name = rcp_get_discounts_db_name();

	if ( ! $wpdb->query( 'SHOW COLUMNS FROM `' . $rcp_discounts_db_name . "` LIKE 'max_uses'" ) ) {
		$wpdb->query( 'ALTER TABLE `' . $rcp_discounts_db_name . '` ADD `max_uses` mediumint' );
		update_option( 'rcp_discounts_db_version', $rcp_discounts_db_version );
	}
	if ( ! $wpdb->query( 'SHOW COLUMNS FROM `' . $rcp_discounts_db_name . "` LIKE 'expiration'" ) ) {
		$wpdb->query( 'ALTER TABLE `' . $rcp_discounts_db_name . '` ADD `expiration` mediumtext' );
		update_option( 'rcp_discounts_db_version', $rcp_discounts_db_version );
	}
	if ( ! $wpdb->query( 'SHOW COLUMNS FROM `' . $rcp_discounts_db_name . "` LIKE 'subscription_id'" ) ) {
		$wpdb->query( 'ALTER TABLE `' . $rcp_discounts_db_name . '` ADD `subscription_id` mediumint' );
		update_option( 'rcp_discounts_db_version', $rcp_discounts_db_version );
	}

	// Upgrade the membership levels table.
	$rcp_db_name = rcp_get_levels_db_name();

	if ( ! get_option( 'rcp_db_version' ) ) {
		update_option( 'rcp_db_version', $rcp_db_version );
	}

	if ( ! $wpdb->query( 'SHOW COLUMNS FROM `' . $rcp_db_name . "` LIKE 'level'" ) ) {
		$wpdb->query( 'ALTER TABLE `' . $rcp_db_name . '` ADD `level` mediumtext' );
		update_option( 'rcp_db_version', $rcp_db_version );
	}
	if ( ! $wpdb->query( 'SHOW COLUMNS FROM `' . $rcp_db_name . "` LIKE 'status'" ) ) {
		$wpdb->query( 'ALTER TABLE `' . $rcp_db_name . '` ADD `status` tinytext' );
		update_option( 'rcp_db_version', $rcp_db_version );
	}
	if ( ! $wpdb->query( 'SHOW COLUMNS FROM `' . $rcp_db_name . "` LIKE 'fee'" ) ) {
		$wpdb->query( 'ALTER TABLE `' . $rcp_db_name . '` ADD `fee` tinytext' );
		update_option( 'rcp_db_version', $rcp_db_version );
	}
	if ( ! $wpdb->query( 'SHOW COLUMNS FROM `' . $rcp_db_name . "` LIKE 'role'" ) ) {
		$wpdb->query( 'ALTER TABLE `' . $rcp_db_name . '` ADD `role` tinytext' );
		update_option( 'rcp_db_version', $rcp_db_version );
	}
	if ( version_compare( get_option( 'rcp_db_version' ), '1.3', '<' ) ) {
		$wpdb->query( 'ALTER TABLE ' . $rcp_db_name . ' MODIFY `duration` smallint' );
		update_option( 'rcp_db_version', $rcp_db_version );
	}

	// Upgrade the payments table.
	$rcp_payments_db_name = rcp_get_payments_db_name();

	if ( '1.0' === get_option( 'rcp_payments_db_version' ) ) {
		$wpdb->query( 'ALTER TABLE ' . $rcp_payments_db_name . ' MODIFY `amount` mediumtext' );
		update_option( 'rcp_payments_db_version', $rcp_payments_db_version );
	}
	if ( ! $wpdb->query( 'SHOW COLUMNS FROM `' . $rcp_payments_db_name . "` LIKE 'transaction_id'" ) ) {
		$wpdb->query( 'ALTER TABLE `' . $rcp_payments_db_name . '` ADD `transaction_id` tinytext' );
		update_option( 'rcp_payments_db_version', $rcp_payments_db_version );
	}
	if ( ! $wpdb->query( 'SHOW COLUMNS FROM `' . $rcp_payments_db_name . "` LIKE 'status'" ) ) {
		$wpdb->query( 'ALTER TABLE `' . $rcp_payments_db_name . '` ADD `status` varchar(200)' );
		update_option( 'rcp_payments_db_version', $rcp_payments_db_version );
	}

	// phpcs:enable

	// 2.3 upgrades for account pages.
	if ( ! get_option( 'rcp_version' ) || version_compare( get_option( 'rcp_version' ), '2.3', '<' ) ) {
		// Update or create plugin pages.
		rcp_options_install();
	}
}
