<?php
/**
 * Customers Table.
 *
 * @package     RCP
 * @subpackage  Database\Tables
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

namespace RCP\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Table;

/**
 * Setup the "rcp_customers" database table
 *
 * @since 3.0
 */
final class Customers extends Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'customers';

	/**
	 * @var string Database version
	 */
	protected $version = 202003311;

	/**
	 * @var array Upgrades to perform
	 */
	protected $upgrades = array(
		'201908151' => 201908151,
		'202003311' => 202003311
	);

	/**
	 * Customers constructor.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Setup the database schema
	 *
	 * @access protected
	 * @since  3.0
	 * @return void
	 */
	protected function set_schema() {
		$this->schema = "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT '0',
			date_registered datetime NOT NULL,
			email_verification enum('verified', 'pending', 'none') DEFAULT 'none',
			last_login datetime DEFAULT NULL,
			has_trialed smallint unsigned DEFAULT NULL,
			ips longtext NOT NULL DEFAULT '',
			notes longtext NOT NULL DEFAULT '',
			uuid varchar(100) NOT NULL default '',
			PRIMARY KEY (id),
			KEY user_id (user_id)";
	}

	/**
	 * Upgrade to version 201908151
	 * - Add `has_trialed` column.
	 *
	 * @since 3.1.2
	 * @return bool
	 */
	protected function __201908151() {

		// Look for column
		$result = $this->column_exists( 'has_trialed' );

		// Maybe add column
		if ( false === $result ) {
			$result = $this->get_db()->query( "
				ALTER TABLE {$this->table_name} ADD COLUMN `has_trialed` smallint unsigned DEFAULT NULL AFTER `last_login`;
			" );
		}

		// Return success/fail
		$success = $this->is_success( $result );

		rcp_log( sprintf( 'Upgrading customers table to version 201908151. Result: %s', var_export( $success, true ) ), true );

		return $success;

	}

	/**
	 * Upgrade to version 202003311
	 * - Change default `last_login` value to `null`
	 */
	protected function __202003311() {

		$result  = $this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY last_login datetime DEFAULT NULL" );
		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202003311 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202003311 failure.', $this->get_table_name() ), true );
		}

		return $success;

	}


}
