<?php
/**
 * Membership Levels Table.
 *
 * @package     RCP
 * @subpackage  Database\Tables
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.4
 */

namespace RCP\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Table;

/**
 * Setup the "wp_restrict_content_pro" database table
 *
 * @since 3.4
 */
final class Membership_Levels extends Table {

	/**
	 * We have to override this to strip out the `rcp_` prefix. This table doesn't have that.
	 *
	 * @var string Table prefix
	 */
	protected $prefix = '';

	/**
	 * @var string Table name
	 */
	protected $name = 'restrict_content_pro';

	/**
	 * @var string Database version
	 */
	protected $version = 202003093;

	/**
	 * @var array Array of upgrade versions and methods
	 */
	protected $upgrades = array(
		'202003091' => 202003091,
		'202003092' => 202003092,
		'202003093' => 202003093,
		'202008061' => 202008061,
		'202008062' => 202008062,
		'202009251' => 202009251,
		'202009252' => 202009252
	);

	/**
	 * Membership_Levels constructor.
	 *
	 * Get the table name from `rcp_get_levels_db_name()`. This is for backwards compatibility because that
	 * function contains a filter.
	 */
	public function __construct() {
		$this->table_name = str_replace( $this->get_db()->prefix, '', rcp_get_levels_db_name() );

		return parent::__construct();
	}

	/**
	 * Setup the database schema
	 *
	 * @access protected
	 * @since  3.4
	 * @return void
	 */
	protected function set_schema() {
		$this->schema = "id bigint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(200) NOT NULL,
			description longtext NOT NULL,
			duration smallint UNSIGNED NOT NULL DEFAULT 0,
			duration_unit tinytext NOT NULL,
			trial_duration smallint(6) UNSIGNED NOT NULL DEFAULT 0,
			trial_duration_unit tinytext NOT NULL,
			price tinytext NOT NULL,
			fee tinytext NOT NULL,
			maximum_renewals smallint UNSIGNED NOT NULL DEFAULT 0,
			after_final_payment tinytext NOT NULL,
			level mediumint UNSIGNED NOT NULL DEFAULT 0,
			role tinytext NOT NULL,
			status varchar(12) NOT NULL DEFAULT 'active',
			list_order mediumint UNSIGNED NOT NULL DEFAULT 0,
			date_created datetime NOT NULL,
			date_modified datetime NOT NULL,
			uuid varchar(100) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			KEY name (name(191)),
			KEY status (status)";
	}

	/**
	 * This method is overridden to allow us to change the generated database name. This is necessary due to
	 * a long-standing error where network enabled installs have an incorrect table name.
	 * This also allows us to apply our old filter to the table name.
	 *
	 *  @link https://github.com/restrictcontentpro/restrict-content-pro/issues/1478
	 *
	 * @since 3.4
	 */
	protected function set_db_interface() {

		// Get the database once, to avoid duplicate function calls
		$db = $this->get_db();

		// Bail if no database
		if ( empty( $db ) ) {
			return;
		}

		// Set variables for global tables
		if ( $this->is_global() ) {
			$site_id = 0;
			$tables  = 'ms_global_tables';

			// Set variables for per-site tables
		} else {
			$site_id = null;
			$tables  = 'tables';
		}

		/*
		 * Set the table prefix and prefix the table name
		 *
		 * We've overridden the table prefix for this table because of a long-standing error where network
		 * enabled installs have an incorrect table name.
		 *
		 * @link https://github.com/restrictcontentpro/restrict-content-pro/issues/1478
		 */
		$this->table_prefix  = ! $this->is_global() ? $db->get_blog_prefix( $site_id ) : '';

		// Get the prefixed table name
		$prefixed_table_name = rcp_get_levels_db_name();

		// Set the database interface
		$db->{$this->prefixed_name} = $this->table_name = $prefixed_table_name;

		// Create the array if it does not exist
		if ( ! isset( $db->{$tables} ) ) {
			$db->{$tables} = array();
		}

		// Add the table to the global table array
		$db->{$tables}[] = $this->prefixed_name;

		// Charset
		if ( ! empty( $db->charset ) ) {
			$this->charset_collation = "DEFAULT CHARACTER SET {$db->charset}";
		}

		// Collation
		if ( ! empty( $db->collate ) ) {
			$this->charset_collation .= " COLLATE {$db->collate}";
		}
	}

	/**
	 * Ensure existing installs prior to 3.4 get upgraded.
	 *
	 * @since 3.4
	 */
	public function maybe_upgrade() {

		if ( false !== get_option( 'rcp_membership_level_db' ) ) {
			update_option( $this->db_version_key, 1 );

			delete_option( 'rcp_membership_level_db' );
		}

		return parent::maybe_upgrade();

	}

	/**
	 * Upgrade to 202003091
	 *      - Add `date_created` column.
	 *
	 * @since 3.4
	 * @return bool
	 */
	protected function __202003091() {

		// Look for column
		$result = $this->column_exists( 'date_created' );

		// Add column if it doesn't exist.
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN date_created datetime NOT NULL;" );
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202003091 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202003091 failure.', $this->get_table_name() ), true );
		}

		return $success;

	}

	/**
	 * Upgrade to 202003092
	 *      - Add `date_created` column.
	 *
	 * @since 3.4
	 * @return bool
	 */
	protected function __202003092() {

		// Look for column
		$result = $this->column_exists( 'date_modified' );

		// Add column if it doesn't exist.
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN date_modified datetime NOT NULL;" );
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202003092 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202003092 failure.', $this->get_table_name() ), true );
		}

		return $success;

	}

	/**
	 * Upgrade to 202003093
	 *      - Add `uuid` column.
	 *
	 * @since 3.4
	 * @return bool
	 */
	protected function __202003093() {

		// Look for column
		$result = $this->column_exists( 'uuid' );

		// Add column if it doesn't exist.
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN uuid varchar(100) NOT NULL DEFAULT '';" );
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202003093 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202003093 failure.', $this->get_table_name() ), true );
		}

		return $success;

	}

	/**
	 * Upgrade to 202008061
	 *
	 * This ensures that pre-BerlinDB columns exist.
	 *      - Add `maximum_renewals` column.
	 *
	 * @since 3.4
	 * @return bool
	 */
	protected function __202008061() {

		// Look for column
		$result = $this->column_exists( 'maximum_renewals' );

		// Add column if it doesn't exist.
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN maximum_renewals smallint UNSIGNED NOT NULL DEFAULT 0 AFTER fee;" );
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202008061 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202008061 failure.', $this->get_table_name() ), true );
		}

		return $success;

	}

	/**
	 * Upgrade to 202008062
	 *
	 * This ensures that pre-BerlinDB columns exist.
	 *      - Add `after_final_payment` column.
	 *
	 * @since 3.4
	 * @return bool
	 */
	protected function __202008062() {

		// Look for column
		$result = $this->column_exists( 'after_final_payment' );

		// Add column if it doesn't exist.
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN after_final_payment tinytext NOT NULL AFTER maximum_renewals;" );
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202008062 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202008062 failure.', $this->get_table_name() ), true );
		}

		return $success;

	}

	/**
	 * Upgrade to 202009251
	 *
	 * This ensures that the pre-BerlinDB columns exist.
	 *      - Add `trial_duration` column.
	 *
	 * @since 3.4.2
	 * @return bool
	 */
	protected function __202009251() {

		// Look for column
		$result = $this->column_exists( 'trial_duration' );

		// Add column if it doesn't exist
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN trial_duration smallint(6) UNSIGNED NOT NULL DEFAULT 0;" );
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202009251 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202009251 successful.', $this->get_table_name() ), true );
		}

		return $success;
	}

	/**
	 * Upgrade to 202009252
	 *
	 * This ensure that the pre-BerlinDB columns exist.
	 *      - Add `trial_duration_unit` column.
	 *
	 * @since 3.4.2
	 * @return bool
	 */
	protected function __202009252() {

		// Look for column
		$result = $this->column_exists( 'trial_duration_unit' );

		// Add column if it doesn't exist
		if ( false === $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN trial_duration_unit tinytext NOT NULL;" );
		}

		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202009252 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202009252 successful.', $this->get_table_name() ), true );
		}

		return $success;
	}

}
