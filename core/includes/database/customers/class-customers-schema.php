<?php
/**
 * Customers Schema Class.
 *
 * @package     RCP
 * @subpackage  Database\Schemas
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

namespace RCP\Database\Schemas;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Schema;

/**
 * Customers Schema Class.
 *
 * @since 3.0
 */
class Customers extends Schema {

	/**
	 * Array of database column objects
	 *
	 * @since  3.0
	 * @access public
	 * @var array
	 */
	public $columns = array(

		// id
		array(
			'name'     => 'id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true
		),

		// user_id
		array(
			'name'     => 'user_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => '0'
		),

		// date_registered
		array(
			'name'       => 'date_registered',
			'type'       => 'datetime',
			'default'    => '',
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
		),

		// email_verification
		array(
			'name'       => 'email_verification',
			'type'       => 'enum(\'verified\', \'pending\', \'none\')',
			'default'    => 'none',
			'transition' => true
		),

		// last_login
		array(
			'name'       => 'last_login',
			'type'       => 'datetime',
			'default'    => null,
			'allow_null' => true,
			'date_query' => true,
			'sortable'   => true,
		),

		// has_trialed
		array(
			'name'       => 'has_trialed',
			'type'       => 'smallint',
			'length'     => '',
			'unsigned'   => true,
			'default'    => null,
			'allow_null' => true,
			'transition' => true
		),

		// ips
		array(
			'name'       => 'ips',
			'type'       => 'longtext',
			'default'    => '',
			'searchable' => true
		),

		// notes
		array(
			'name'    => 'notes',
			'type'    => 'longtext',
			'default' => ''
		),

		// uuid
		array(
			'uuid' => true,
		)

	);

}
