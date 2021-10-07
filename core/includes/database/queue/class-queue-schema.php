<?php
/**
 * Queue Schema Class.
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
 * Queue Schema Class.
 *
 * @since 3.0
 */
class Queue extends Schema {

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

		// queue
		array(
			'name'    => 'queue',
			'type'    => 'varchar',
			'length'  => '20',
			'default' => 'rcp_core'
		),

		// name
		array(
			'name'       => 'name',
			'type'       => 'varchar',
			'length'     => '50',
			'default'    => '',
			'searchable' => true,
			'sortable'   => true,
		),

		// description
		array(
			'name'    => 'description',
			'type'    => 'varchar',
			'length'  => '256',
			'default' => '',
		),

		// callback
		array(
			'name'    => 'callback',
			'type'    => 'varchar',
			'length'  => '512',
			'default' => '',
		),

		// total_count
		array(
			'name'     => 'total_count',
			'type'     => 'bigint',
			'length'   => 20,
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true
		),

		// current_count
		array(
			'name'     => 'current_count',
			'type'     => 'bigint',
			'length'   => 20,
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true
		),

		// step
		array(
			'name'     => 'step',
			'type'     => 'bigint',
			'length'   => 20,
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true
		),

		// status
		array(
			'name'     => 'status',
			'type'     => 'varchar',
			'length'   => '20',
			'default'  => 'incomplete',
			'sortable' => true
		),

		// data
		array(
			'name'     => 'data',
			'type'     => 'longtext',
			'default'  => ''
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

		// date_completed
		array(
			'name'       => 'date_completed',
			'type'       => 'datetime',
			'default'    => null,
			'allow_null' => true,
			'date_query' => true,
			'sortable'   => true
		),

		// uuid
		array(
			'uuid' => true,
		)

	);

}
