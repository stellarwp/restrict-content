<?php
/**
 * Discounts Table.
 *
 * @package     RCP
 * @subpackage  Database\Tables
 * @copyright   Copyright (c) 2019, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.3
 */

namespace RCP\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Table;

/**
 * Setup the "rcp_discounts" database table
 *
 * @since 3.3
 */
final class Discounts extends Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'discounts';

	/**
	 * @var string Database version
	 */
	protected $version = 202003313;

	/**
	 * @var array Array of upgrade versions and methods
	 */
	protected $upgrades = array(
		'201907101' => 201907101,
		'201907102' => 201907102,
		'201907103' => 201907103,
		'201907104' => 201907104,
		'202002101' => 202002101,
		'202003311' => 202003311,
		'202003312' => 202003312,
		'202003313' => 202003313
	);

	/**
	 * Discounts constructor.
	 *
	 * @access public
	 * @since  3.3
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Setup the database schema
	 *
	 * @access protected
	 * @since  3.3
	 * @return void
	 */
	protected function set_schema() {
		$this->schema = "id bigint(9) NOT NULL AUTO_INCREMENT,
			name tinytext NOT NULL DEFAULT '',
			description longtext NOT NULL default '',
			amount tinytext NOT NULL,
			unit tinytext NOT NULL DEFAULT '',
			code tinytext NOT NULL DEFAULT '',
			use_count mediumint NOT NULL DEFAULT '0',
			max_uses mediumint NOT NULL DEFAULT '0',
			status tinytext NOT NULL,
			expiration datetime DEFAULT NULL,
			membership_level_ids text NOT NULL DEFAULT '',
			one_time smallint NOT NULL DEFAULT 0,
			date_created datetime NOT NULL,
			date_modified datetime NOT NULL,
			uuid varchar(100) NOT NULL DEFAULT '',
			PRIMARY KEY (id)";
	}

	/**
	 * If the old `rcp_discounts_db_version` option exists, copy that value to our new version key.
	 * This will ensure new upgrades are processed on old installs.
	 *
	 * @since 3.3
	 */
	public function maybe_upgrade() {

		if ( false !== get_option( 'rcp_discounts_db_version' ) ) {
			update_option( $this->db_version_key, get_option( 'rcp_discounts_db_version' ) );

			delete_option( 'rcp_discounts_db_version' );
		}

		return parent::maybe_upgrade();
	}

	/**
	 * Upgrade to 201907101
	 *      - Update `expiration` type to `datetime`.
	 *
	 * @since 3.3
	 * @return bool
	 */
	protected function __201907101() {

		$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY expiration datetime DEFAULT NULL" );

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 201907101 successful.', $this->get_table_name() ) );
		} else {
			rcp_log( sprintf( '%s table upgrade to 201907101 failure.', $this->get_table_name() ) );
		}

		return $success;

	}

	/**
	 * Upgrade to 201907102
	 *      - Add `date_created` column.
	 *
	 * @since 3.3
	 * @return bool
	 */
	protected function __201907102() {

		// Look for column
		$result = $this->column_exists( 'date_created' );

		// Add column if it doesn't exist.
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN date_created datetime NOT NULL;" );
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 201907102 successful.', $this->get_table_name() ) );
		} else {
			rcp_log( sprintf( '%s table upgrade to 201907102 failure.', $this->get_table_name() ) );
		}

		return $success;

	}

	/**
	 * Upgrade to 201907103
	 *      - Add `date_modified` column.
	 *
	 * @since 3.3
	 * @return bool
	 */
	protected function __201907103() {

		// Look for column
		$result = $this->column_exists( 'date_modified' );

		// Add column if it doesn't exist.
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN date_modified datetime NOT NULL;" );
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 201907103 successful.', $this->get_table_name() ) );
		} else {
			rcp_log( sprintf( '%s table upgrade to 201907103 failure.', $this->get_table_name() ) );
		}

		return $success;

	}

	/**
	 * Upgrade to 201907104
	 *      - Add `uuid` column.
	 *
	 * @since 3.3
	 * @return bool
	 */
	protected function __201907104() {

		// Look for column
		$result = $this->column_exists( 'uuid' );

		// Add column if it doesn't exist.
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN uuid varchar(100) NOT NULL DEFAULT '';" );
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 201907104 successful.', $this->get_table_name() ) );
		} else {
			rcp_log( sprintf( '%s table upgrade to 201907104 failure.', $this->get_table_name() ) );
		}

		return $success;

	}

	/**
	 * Upgrade to 202002101
	 *      - Add `membership_level_ids` column if it doesn't already exist. Then migrate data from old
	 *        `subscription_id` column to new column. This change used to exist in RCP_Upgrades::v30_upgrades()
	 *        but was moved here.
	 *
	 *      - Add `one_time` column if it doesn't already exist. This column was actually added in a previous
	 *        version of RCP, but there appear to be some issues with customers missing it.
	 *
	 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/2698
	 *
	 * @since 3.3.7
	 * @return bool
	 */
	protected function __202002101() {

		/**
		 * membership_level_ids
		 */

		// Look for column
		$result = $this->column_exists( 'membership_level_ids' );

		$has_subscription_id_col = $this->column_exists( 'subscription_id' );

		// Add column if it doesn't exist.
		if ( false === $result ) {
			$after  = $has_subscription_id_col ? 'subscription_id' : 'expiration';
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN membership_level_ids TEXT NOT NULL AFTER {$after};" );
		}

		$success = $this->is_success( $result );

		if ( ! $success ) {
			rcp_log( sprintf( '%s table upgrade to 202002101 failure - error while adding `membership_level_ids` column.', $this->get_table_name() ) );

			return $success;
		}

		if ( $has_subscription_id_col ) {
			rcp_log( sprintf( 'Adding membership_level_ids column in %s was successful. Now migrating data.', $this->get_table_name() ), true );

			// Doing a direct query here to ensure we can get `subscription_id`.
			$discounts = $this->get_db()->get_results( "SELECT id, subscription_id FROM {$this->get_table_name()} WHERE subscription_id != ''" );

			if ( ! empty( $discounts ) ) {
				// Prevent discount sync with Stripe - we don't need it here.
				remove_action( 'rcp_edit_discount', 'rcp_stripe_update_discount', 10 );

				foreach ( $discounts as $discount ) {
					$membership_level_ids = empty( $discount->subscription_id ) ? array() : array( absint( $discount->subscription_id ) );

					rcp_update_discount( absint( $discount->id ), array(
						'membership_level_ids' => $membership_level_ids
					) );
				}

				rcp_log( sprintf( 'Successfully updated membership_level_ids column for %d discount codes.', count( $discounts ) ), true );
			} else {
				rcp_log( 'No discount codes to upgrade.', true );
			}

			// Delete old `subscription_id` column.
			rcp_log( sprintf( 'Dropping old subscription_id column from %s.', $this->get_table_name() ), true );
			$this->get_db()->query( "ALTER TABLE {$this->get_table_name()} DROP COLUMN subscription_id" );
		}

		/**
		 * one_time
		 */

		// Look for column
		$result = $this->column_exists( 'one_time' );

		// Add column if it doesn't exist.
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN one_time smallint NOT NULL DEFAULT 0 AFTER membership_level_ids;" );

			global $rcp_options;

			if ( $this->is_success( $result ) && ! empty( $rcp_options['one_time_discounts'] ) ) {
				rcp_log( 'Setting all discounts to one time.', true );

				$this->get_db()->query( "UPDATE {$this->table_name} SET one_time = 1" );
			}
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202002101 successful.', $this->get_table_name() ) );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202002101 failure - error while adding `one_time` column.', $this->get_table_name() ) );
		}

		return $success;

	}

	/**
	 * Upgrade to version 202003311
	 * - Change default `expiration` value to `null`
	 */
	protected function __202003311() {

		$result  = $this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY expiration datetime DEFAULT NULL" );
		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202003311 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202003311 failure.', $this->get_table_name() ), true );
		}

		return $success;

	}

	/**
	 * Upgrade to version 202003312
	 * - Remove default `date_created` value
	 */
	protected function __202003312() {

		$result  = $this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY date_created datetime NOT NULL" );
		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202003312 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202003312 failure.', $this->get_table_name() ), true );
		}

		return $success;

	}

	/**
	 * Upgrade to version 202003313
	 * - Remove default `date_modified` value
	 */
	protected function __202003313() {

		$result  = $this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY date_modified datetime NOT NULL" );
		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202003313 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202003313 failure.', $this->get_table_name() ), true );
		}

		return $success;

	}

}
