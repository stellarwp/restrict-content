<?php
/**
 * Discounts Schema Class.
 *
 * @package     RCP
 * @subpackage  Database\Schemas
 * @copyright   Copyright (c) 2019, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.3
 */

namespace RCP\Database\Schemas;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Schema;

/**
 * Discounts Schema Class.
 *
 * @since 3.3
 */
class Discounts extends Schema {

	/**
	 * Array of database column objects
	 *
	 * @since  3.3
	 * @access public
	 * @var array
	 */
	public $columns = array(

		// id
		array(
			'name'     => 'id',
			'type'     => 'bigint',
			'length'   => '9',
			'unsigned' => false,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true
		),

		// name
		array(
			'name'       => 'name',
			'type'       => 'tinytext',
			'default'    => '',
			'sortable'   => true,
			'searchable' => true
		),

		// description
		array(
			'name'       => 'description',
			'type'       => 'longtext',
			'default'    => '',
			'searchable' => true
		),

		// amount
		array(
			'name'    => 'amount',
			'type'    => 'tinytext',
			'default' => ''
		),

		// unit
		array(
			'name'    => 'unit',
			'type'    => 'tinytext',
			'default' => '%'
		),

		// code
		array(
			'name'       => 'code',
			'type'       => 'tinytext',
			'default'    => '',
			'sortable'   => true,
			'searchable' => true
		),

		// use_count
		array(
			'name'       => 'use_count',
			'type'       => 'mediumint',
			'default'    => '0',
			'sortable'   => true,
			'transition' => true
		),

		// max_uses
		array(
			'name'     => 'max_uses',
			'type'     => 'mediumint',
			'default'  => '0',
			'sortable' => true
		),

		// status
		array(
			'name'       => 'status',
			'type'       => 'tinytext',
			'default'    => 'disabled',
			'sortable'   => true,
			'transition' => true
		),

		// expiration
		array(
			'name'       => 'expiration',
			'type'       => 'datetime',
			'default'    => null,
			'allow_null' => true,
			'date_query' => true,
			'sortable'   => true,
		),

		// membership_level_ids
		array(
			'name'    => 'membership_level_ids',
			'type'    => 'text',
			'default' => ''
		),

		// one_time
		array(
			'name'    => 'one_time',
			'type'    => 'smallint',
			'default' => '0'
		),

		// date_created
		array(
			'name'       => 'date_created',
			'type'       => 'datetime',
			'default'    => '',
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
		),

		// date_modified
		array(
			'name'       => 'date_modified',
			'type'       => 'datetime',
			'default'    => '',
			'modified'   => true,
			'date_query' => true,
			'sortable'   => true,
		),

		// uuid
		array(
			'uuid' => true,
		)

	);

}
