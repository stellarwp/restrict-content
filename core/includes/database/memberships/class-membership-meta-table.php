<?php
/**
 * Membership Meta Table.
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
 * Setup the "rcp_membershipmeta" database table
 *
 * @since 3.0
 */
final class Membership_Meta extends Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'membershipmeta';

	/**
	 * @var string Database version
	 */
	protected $version = 201901291;

	/**
	 * @var array Array of upgrade versions and methods
	 */
	protected $upgrades = array();

	/**
	 * Membership Meta constructor.
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
		$max_index_length = 191;
		$this->schema = "meta_id bigint(20) unsigned NOT NULL auto_increment,
		rcp_membership_id bigint(20) unsigned NOT NULL default '0',
		meta_key varchar(255) DEFAULT NULL,
		meta_value longtext DEFAULT NULL,
		PRIMARY KEY (meta_id),
		KEY rcp_membership_id (rcp_membership_id),
		KEY meta_key (meta_key({$max_index_length}))";
	}

}