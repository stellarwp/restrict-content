<?php
/**
 * Logs Schema Class.
 *
 * @package     RCP
 * @subpackage  Database\Schemas
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.4
 */

namespace RCP\Database\Schemas;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Schema;

/**
 * Logs Schema Class.
 *
 * @since 3.4
 */
class Logs extends Schema {

	/**
	 * Array of database column objects
	 *
	 * @since  3.4
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

		// object_type
		array(
			'name'      => 'object_type',
			'type'      => 'varchar',
			'length'    => '20',
			'default'   => '',
			'sortable'  => true,
			'cache_key' => true,
		),

		// object_id
		array(
			'name'       => 'object_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => null,
			'allow_null' => true,
			'sortable'   => true,
			'cache_key'  => true,
		),

		// user_id
		array(
			'name'       => 'user_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => null,
			'allow_null' => true,
			'sortable'   => true,
			'cache_key'  => true,
		),

		// type
		array(
			'name'     => 'type',
			'type'     => 'varchar',
			'length'   => '20',
			'default'  => '',
			'sortable' => true
		),

		// title
		array(
			'name'       => 'title',
			'type'       => 'varchar',
			'length'     => '200',
			'default'    => '',
			'searchable' => true,
			'sortable'   => true,
			'in'         => false,
			'not_in'     => false
		),

		// content
		array(
			'name'       => 'content',
			'type'       => 'longtext',
			'default'    => '',
			'searchable' => true,
			'in'         => false,
			'not_in'     => false
		),

		// is_error
		array(
			'name'     => 'is_error',
			'type'     => 'tinyint',
			'length'   => '1',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
		),

		// date_created
		array(
			'name'       => 'date_created',
			'type'       => 'datetime',
			'default'    => '',
			'created'    => true,
			'date_query' => true,
			'sortable'   => true
		),

		// date_modified
		array(
			'name'       => 'date_modified',
			'type'       => 'datetime',
			'default'    => '',
			'modified'   => true,
			'date_query' => true,
			'sortable'   => true
		),

		// uuid
		array(
			'uuid' => true,
		)

	);

}
