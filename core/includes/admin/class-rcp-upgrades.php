<?php
/**
 * Upgrade class
 *
 * This class handles database upgrade routines between versions
 *
 * @package     Restrict Content Pro
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.6
 */
class RCP_Upgrades {

	private $version = '';
	private $upgraded = false;

	/**
	 * RCP_Upgrades constructor.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->version = preg_replace( '/[^0-9.].*/', '', get_option( 'rcp_version' ) );

		add_action( 'admin_init', array( $this, 'init' ), -9999 );

		// This uses a lower priority because we need it to run before BerlinDB's upgrade checks.
		add_action( 'admin_init', array( $this, 'maybe_set_db_upgrade_flags' ), -999999 );

	}

	/**
	 * Trigger updates and maybe update the RCP version number
	 *
	 * @access public
	 * @return void
	 */
	public function init() {

		$this->v26_upgrades();
		$this->v27_upgrades();
		$this->v29_upgrades();
		$this->v30_upgrades();
		$this->v304_upgrades();
		$this->v31_upgrades();
		$this->v32_upgrades();
		$this->v35_upgrades();

		// If upgrades have occurred or the DB version is different from the version constant
		if ( $this->upgraded || $this->version <> RCP_PLUGIN_VERSION ) {
			rcp_log( sprintf( 'RCP upgraded from version %s to %s.', $this->version, RCP_PLUGIN_VERSION ), true );
			update_option( 'rcp_version_upgraded_from', $this->version );
			update_option( 'rcp_version', RCP_PLUGIN_VERSION );
			update_option( 'rcp_version_upgraded_on', current_time( 'mysql', true ) );
		}

	}

	/**
	 * Maybe set BerlinDB upgrade flags
	 *
	 * This has to run before BerlinDB checks for table upgrades/installations. We might need to set a flag
	 * when moving an "old" database table to BerlinDB's API so we can ensure BerlinDB runs through table
	 * upgrades on existing installs.
	 *
	 * @access private
	 * @return void
	 */
	public function maybe_set_db_upgrade_flags() {
		if ( version_compare( $this->version, '3.4', '<' ) ) {
			update_option( 'rcp_membership_level_db', 1 );
		}
	}

	/**
	 * Process 2.6 upgrades
	 *
	 * @access private
	 * @return void
	 */
	private function v26_upgrades() {

		if( version_compare( $this->version, '2.6', '<' ) ) {
			rcp_log( 'Performing version 2.6 upgrades: options install.', true );
			@rcp_options_install();
		}
	}

	/**
	 * Process 2.7 upgrades
	 *
	 * @access private
	 * @return void
	 */
	private function v27_upgrades() {

		if( version_compare( $this->version, '2.7', '<' ) ) {

			rcp_log( 'Performing version 2.7 upgrades: options install and updating discounts database.', true );

			global $wpdb, $rcp_discounts_db_name;

			$wpdb->query( "UPDATE $rcp_discounts_db_name SET code = LOWER(code)" );

			@rcp_options_install();

			$this->upgraded = true;
		}
	}

	/**
	 * Process 2.9 upgrades
	 *
	 * @access private
	 * @since 2.9
	 * @return void
	 */
	private function v29_upgrades() {

		if( version_compare( $this->version, '2.9', '<' ) ) {

			global $rcp_options;

			// Migrate expiring soon email to new reminders.
			$period           = rcp_get_renewal_reminder_period();
			$subject          = isset( $rcp_options['renewal_subject'] ) ? $rcp_options['renewal_subject'] : '';
			$message          = isset( $rcp_options['renew_notice_email'] ) ? $rcp_options['renew_notice_email'] : '';
			$reminders        = new RCP_Reminders();
			$reminders_to_add = array();

			if ( 'none' != $period && ! empty( $subject ) && ! empty( $message ) ) {
				$allowed_periods = $reminders->get_notice_periods();
				$period          = str_replace( ' ', '', $period );

				$new_notice = array(
					'subject'     => sanitize_text_field( $subject ),
					'message'     => wp_kses( $message, wp_kses_allowed_html( 'post' ) ),
					'send_period' => array_key_exists( $period, $allowed_periods ) ? $period : '+1month',
					'type'        => 'expiration',
					'enabled'     => true
				);

				$reminders_to_add[] = $new_notice;
			}

			// Insert default renewal notice.
			$renewal_notices = $reminders->get_notices( 'renewal' );
			if ( empty( $renewal_notices ) ) {
				$reminders_to_add[] = $reminders->get_default_notice( 'renewal' );
			}

			// Update notices.
			if ( ! empty( $reminders_to_add ) ) {
				update_option( 'rcp_reminder_notices', $reminders_to_add );
			}

			@rcp_options_install();

			$this->upgraded = true;

		}

	}

	/**
	 * Process 3.0 upgrades.
	 * Renames the payment_id column to rcp_payment_id in the payment meta table.
	 *
	 * @since 3.0
	 */
	private function v30_upgrades() {

		if( version_compare( $this->version, '3.0', '<' ) ) {

			global $wpdb;

			/**
			 * Run options install to add new tables, add payment plan settings to subscription level table, etc.
			 */
			@rcp_options_install();

			/**
			 * Rename "payment_id" column in payment meta table to "rcp_payment_id".
			 */
			$payment_meta_table_name = rcp_get_payment_meta_db_name();

			rcp_log( sprintf( 'Performing version 3.0 upgrade: Renaming payment_id column to rcp_payment_id in the %s table.', $payment_meta_table_name ), true );

			$payment_meta_cols = $wpdb->get_col( "DESC " . $payment_meta_table_name, 0 );
			$column_renamed    = in_array( 'rcp_payment_id', $payment_meta_cols );

			// Only attempt to rename the column if it hasn't already been done.
			if ( ! $column_renamed ) {
				$updated = $wpdb->query( "ALTER TABLE {$payment_meta_table_name} CHANGE payment_id rcp_payment_id BIGINT(20) NOT NULL DEFAULT '0';" );

				if ( false === $updated ) {
					rcp_log( sprintf( 'Error renaming the payment_id column in %s.', $payment_meta_table_name ), true );
					return;
				} else {
					rcp_log( sprintf( 'Renaming payment_id to rcp_payment_id in %s was successful.', $payment_meta_table_name ), true );
				}
			} else {
				rcp_log( sprintf( 'payment_id column already renamed to rcp_payment_id in %s.', $payment_meta_table_name ), true );
			}

			/**
			 * Upgrade discounts table.
			 *
			 * Discounts migration moved to BerlinDB
			 * @see \RCP\Database\Tables\Discounts::__202002101()
			 */

			/**
			 * Change column types in rcp_payments.
			 */
			$payment_table_name = rcp_get_payments_db_name();

			rcp_log( sprintf( 'Performing version 3.0 upgrade: Changing column types in %s table.', $payment_table_name ), true );

			$wpdb->query( "ALTER TABLE {$payment_table_name} MODIFY id bigint(9) unsigned NOT NULL AUTO_INCREMENT" );
			$wpdb->query( "ALTER TABLE {$payment_table_name} MODIFY object_id bigint(9) unsigned NOT NULL" );
			$wpdb->query( "ALTER TABLE {$payment_table_name} MODIFY user_id bigint(20) unsigned NOT NULL" );

			/**
			 * Add batch processing job for migrating memberships to custom table.
			 */
			$registered = \RCP\Utils\Batch\add_batch_job( array(
				'name'        => 'Memberships Migration',
				'description' => __( 'Migrate members and their memberships from user meta to a custom table.', 'rcp' ),
				'callback'    => 'RCP_Batch_Callback_Migrate_Memberships_v3'
			) );

			if ( is_wp_error( $registered ) ) {
				rcp_log( sprintf( 'Batch: Error adding memberships migration job: %s', $registered->get_error_message() ), true );
			} else {
				rcp_log( 'Batch: Successfully initiated memberships migration job.', true );
			}

			$this->upgraded = true;
		}
	}

	/**
	 * Process 3.0.4 upgrades.
	 *
	 * @access private
	 * @return void
	 */
	private function v304_upgrades() {

		if( version_compare( $this->version, '3.0.4', '<' ) ) {
			rcp_log( 'Performing version 3.0.4 upgrades: options install.', true );
			@rcp_options_install();
		}

	}

	/**
	 * Process 3.1 upgrades.
	 *
	 * @access private
	 * @return void
	 */
	private function v31_upgrades() {

		if( version_compare( $this->version, '3.1', '<' ) ) {
			rcp_log( 'Performing version 3.1 upgrades: options install.', true );
			@rcp_options_install();

			/**
			 * Database upgrade to add `one_time` column to discounts table has been moved to:
			 * @see \RCP\Database\Tables\Discounts::__202002101()
			 */
		}

	}

	/**
	 * Process 3.2 upgrades.
	 *      - Remove Stripe Checkout gateway.
	 *
	 * @access private
	 * @return void
	 */
	private function v32_upgrades() {

		if( version_compare( $this->version, '3.2', '<' ) ) {
			global $rcp_options;

			$enabled_gateways = isset( $rcp_options['gateways'] ) ? array_map( 'trim', $rcp_options['gateways'] ) : array();

			if ( ! empty( $enabled_gateways ) && is_array( $enabled_gateways ) && array_key_exists( 'stripe_checkout', $enabled_gateways ) ) {
				rcp_log( 'Performing version 3.2 upgrades: removing Stripe Checkout gateway.', true );
				unset( $enabled_gateways[ 'stripe_checkout' ] );

				// If Stripe doesn't exist, add it.
				if ( ! array_key_exists( 'stripe', $enabled_gateways ) ) {
					rcp_log( 'Performing version 3.2 upgrades: adding Stripe Elements gateway.', true );
					$enabled_gateways['stripe'] = 1;
				}

				$rcp_options['gateways'] = $enabled_gateways;

				update_option( 'rcp_settings', $rcp_options );
			}
		}

	}

	/**
	 * Process 3.5 upgrades.
	 *      - Remove License Key Option
	 *
	 * @access private
	 * @return void
	 */
	private function v35_upgrades() {

		if ( version_compare( $this->version, '3.5', '<' ) ) {
			global $rcp_options;

			// Setting license_key to empty string before we eventually remove completely.
			$rcp_options['license_key'] = '';

			update_option( 'rcp_settings', $rcp_options );
		}
	}

}
new RCP_Upgrades;
