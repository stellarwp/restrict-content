<?php
/**
 * Membership Counts Table.
 *
 * @package     RCP
 * @subpackage  Database\Tables
 * @copyright   Copyright (c) 2019, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.3
 */

namespace RCP\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Table;

/**
 * Setup the "rcp_membership_counts" database table
 *
 * @since 3.3
 */
final class Membership_Counts extends Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'membership_counts';

	/**
	 * @var string Database version
	 */
	protected $version = 202003311;

	/**
	 * @var array Array of upgrade versions and methods
	 */
	protected $upgrades = array(
		'202003311' => 202003311
	);

	/**
	 * Membership_Counts constructor.
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
		$this->schema = "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			level_id bigint(20) unsigned NOT NULL DEFAULT 0,
			active_count bigint(20) unsigned NOT NULL DEFAULT 0,
			pending_count bigint(20) unsigned NOT NULL DEFAULT 0,
			cancelled_count bigint(20) unsigned NOT NULL DEFAULT 0,
			expired_count bigint(20) unsigned NOT NULL DEFAULT 0,
			date_created datetime NOT NULL,
			uuid varchar(100) NOT NULL default '',
			PRIMARY KEY (id),
			KEY date_created (date_created)";
	}

	/**
	 * Upgrade to version 202003311
	 * - Remove default `date_created` value
	 */
	protected function __202003311() {

		$result  = $this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY date_created datetime NOT NULL" );
		$success = $this->is_success( $result );

		if ( $success ) {
			rcp_log( sprintf( '%s table upgrade to 202003311 successful.', $this->get_table_name() ), true );
		} else {
			rcp_log( sprintf( '%s table upgrade to 202003311 failure.', $this->get_table_name() ), true );
		}

		return $success;

	}

}
