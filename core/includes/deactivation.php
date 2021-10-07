<?php
/**
 * Deactivation routines
 *
 * @package     Restrict Content Pro
 * @subpackage  Deactivation Routines
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.5.4
 */

/**
 * Clears the plugin's scheduled cron jobs.
 *
 * @since 2.5.4
 * @return void
 */
function rcp_clear_cron_jobs() {
	wp_clear_scheduled_hook( 'rcp_expired_users_check' );
	wp_clear_scheduled_hook( 'rcp_send_expiring_soon_notice' );
}
register_deactivation_hook( RCP_PLUGIN_FILE, 'rcp_clear_cron_jobs' );