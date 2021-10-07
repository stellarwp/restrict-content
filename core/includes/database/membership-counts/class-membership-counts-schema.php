<?php
/**
 * Membership Counts Schema Class.
 *
 * @package     RCP
 * @subpackage  Database\Schemas
 * @copyright   Copyright (c) 2019, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.3
 */

namespace RCP\Database\Schemas;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Schema;

/**
 * Membership Counts Schema Class.
 *
 * @since 3.3
 */
class Membership_Counts extends Schema {

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
			'length'   => '20',
			'unsigned' => true,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true
		),

		// level_id
		array(
			'name'     => 'level_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'sortable' => true,
			'default'  => 0
		),

		// active_count
		array(
			'name'     => 'active_count',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'sortable' => true,
			'default'  => 0
		),

		// pending_count
		array(
			'name'     => 'pending_count',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'sortable' => true,
			'default'  => 0
		),

		// cancelled_count
		array(
			'name'     => 'cancelled_count',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'sortable' => true,
			'default'  => 0
		),

		// expired_count
		array(
			'name'     => 'expired_count',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'sortable' => true,
			'default'  => 0
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

		// uuid
		array(
			'uuid' => true,
		)

	);

}
