<?php
/**
 * Membership Levels Schema Class.
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
 * Membership Levels Schema Class.
 *
 * @since 3.4
 */
class Membership_Levels extends Schema {

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
			'length'   => '9',
			'unsigned' => true,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true
		),

		// name
		array(
			'name'       => 'name',
			'type'       => 'varchar',
			'length'     => '200',
			'default'    => '',
			'sortable'   => true,
			'searchable' => true,
			'validate'   => 'sanitize_text_field'
		),

		// description
		array(
			'name'       => 'description',
			'type'       => 'longtext',
			'default'    => '',
			'searchable' => true,
			'validate'   => 'wp_kses_post'
		),

		// duration
		array(
			'name'     => 'duration',
			'type'     => 'smallint',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
			'validate' => 'absint'
		),

		// duration_unit
		array(
			'name'     => 'duration_unit',
			'type'     => 'tinytext',
			'default'  => '',
			'validate' => '_rcp_sanitize_duration_unit'
		),

		// trial_duration
		array(
			'name'     => 'trial_duration',
			'type'     => 'smallint',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
			'validate' => 'absint'
		),

		// trial_duration_unit
		array(
			'name'     => 'trial_duration_unit',
			'type'     => 'tinytext',
			'default'  => '',
			'validate' => '_rcp_sanitize_duration_unit'
		),

		// price
		array(
			'name'     => 'price',
			'type'     => 'tinytext',
			'default'  => '',
			'validate' => 'sanitize_text_field'
		),

		// fee
		array(
			'name'     => 'fee',
			'type'     => 'tinytext',
			'default'  => '',
			'validate' => 'sanitize_text_field'
		),

		// maximum_renewals
		array(
			'name'     => 'maximum_renewals',
			'type'     => 'smallint',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
			'validate' => 'absint'
		),

		// after_final_payment
		array(
			'name'     => 'after_final_payment',
			'type'     => 'tinytext',
			'default'  => '',
			'validate' => 'sanitize_text_field'
		),

		// list_order
		array(
			'name'     => 'list_order',
			'type'     => 'mediumint',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
			'validate' => 'absint'
		),

		// level
		array(
			'name'     => 'level',
			'type'     => 'mediumint',
			'unsigned' => true,
			'default'  => 0,
			'sortable' => true,
			'validate' => 'absint'
		),

		// status
		array(
			'name'       => 'status',
			'type'       => 'varchar',
			'length'     => '12',
			'default'    => '',
			'sortable'   => true,
			'transition' => true,
			'validate'   => 'sanitize_text_field'
		),

		// role
		array(
			'name'     => 'role',
			'type'     => 'tinytext',
			'default'  => '',
			'validate' => 'sanitize_text_field'
		),

		// date_created
		array(
			'name'       => 'date_created',
			'type'       => 'datetime',
			'default'    => '', // Defaults to current datetime in query class.
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
		),

		// date_modified
		array(
			'name'       => 'date_modified',
			'type'       => 'datetime',
			'default'    => '', // Defaults to current datetime in query class.
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
