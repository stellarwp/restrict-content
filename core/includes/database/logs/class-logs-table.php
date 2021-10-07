<?php
/**
 * Logs Table.
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
 * Setup the "rcp_logs" database table
 *
 * @since 3.4
 */
final class Logs extends Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'logs';

	/**
	 * @var string Database version
	 */
	protected $version = 202003051;

	/**
	 * @var array Array of upgrade versions and methods
	 */
	protected $upgrades = array();

	/**
	 * Setup the database schema
	 *
	 * @access protected
	 * @since  3.4
	 * @return void
	 */
	protected function set_schema() {
		$this->schema = "id int unsigned NOT NULL AUTO_INCREMENT,
			object_type varchar(20) NOT NULL,
			object_id bigint(20) UNSIGNED DEFAULT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			type varchar(20) NOT NULL,
			title varchar(200) NOT NULL,
			content longtext NOT NULL,
			is_error tinyint(1) NOT NULL DEFAULT 0,
			date_created datetime NOT NULL,
			date_modified datetime NOT NULL,
			uuid varchar(100) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			KEY object_id_object_type (object_id, object_type),
			KEY user_id (user_id),
			KEY type (type)";
	}

}
