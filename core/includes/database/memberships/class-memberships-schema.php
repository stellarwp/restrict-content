<?php
/**
 * Memberships Schema Class.
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
 * Memberships Schema Class.
 *
 * @since 3.0
 */
class Memberships extends Schema {

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

		// customer_id
		array(
			'name'     => 'customer_id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => '0'
		),

		// user_id
		array(
			'name'       => 'user_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => null,
			'allow_null' => true
		),

		// object_id
		array(
			'name'       => 'object_id',
			'type'       => 'bigint',
			'length'     => '20',
			'unsigned'   => true,
			'default'    => '0',
			'sortable'   => true,
			'transition' => true
		),

		// object_type
		array(
			'name'     => 'object_type',
			'type'     => 'varchar',
			'length'   => '20',
			'default'  => '',
			'sortable' => true,
		),

		// currency
		array(
			'name'     => 'currency',
			'type'     => 'varchar',
			'length'   => '20',
			'default'  => 'USD',
			'sortable' => true
		),

		// initial_amount
		array(
			'name'     => 'initial_amount',
			'type'     => 'mediumtext',
			'default'  => '',
			'sortable' => true
		),

		// recurring_amount
		array(
			'name'     => 'recurring_amount',
			'type'     => 'mediumtext',
			'default'  => '',
			'sortable' => true
		),

		// created_date
		array(
			'name'       => 'created_date',
			'type'       => 'datetime',
			'default'    => '',
			'created'    => true,
			'date_query' => true,
			'sortable'   => true
		),

		// activated_date
		array(
			'name'       => 'activated_date',
			'type'       => 'datetime',
			'default'    => null,
			'allow_null' => true,
			'date_query' => true,
			'sortable'   => true
		),

		// trial_end_date
		array(
			'name'       => 'trial_end_date',
			'type'       => 'datetime',
			'default'    => null,
			'allow_null' => true,
			'date_query' => true,
			'sortable'   => true
		),

		// renewed_date
		array(
			'name'       => 'renewed_date',
			'type'       => 'datetime',
			'default'    => null,
			'allow_null' => true,
			'date_query' => true,
			'sortable'   => true
		),

		// cancellation_date
		array(
			'name'       => 'cancellation_date',
			'type'       => 'datetime',
			'default'    => null,
			'allow_null' => true,
			'date_query' => true,
			'sortable'   => true
		),

		// expiration_date
		array(
			'name'       => 'expiration_date',
			'type'       => 'datetime',
			'default'    => null,
			'allow_null' => true,
			'date_query' => true,
			'sortable'   => true,
			'transition' => true
		),

		// payment_plan_completed_date
		array(
			'name'       => 'payment_plan_completed_date',
			'type'       => 'datetime',
			'default'    => null,
			'allow_null' => true,
			'date_query' => true,
			'sortable'   => true,
			'transition' => true
		),

		// auto_renew
		array(
			'name'       => 'auto_renew',
			'type'       => 'smallint',
			'unsigned'   => true,
			'default'    => '0',
			'transition' => true
		),

		// times_billed
		array(
			'name'       => 'times_billed',
			'type'       => 'smallint',
			'unsigned'   => true,
			'default'    => '0',
			'sortable'   => true,
			'transition' => true
		),

		// maximum_renewals
		array(
			'name'     => 'maximum_renewals',
			'type'     => 'smallint',
			'unsigned' => true,
			'default'  => '0',
			'sortable' => true
		),

		// status
		array(
			'name'       => 'status',
			'type'       => 'varchar',
			'length'     => '12',
			'default'    => 'pending',
			'sortable'   => true,
			'transition' => true
		),

		// gateway_customer_id
		array(
			'name'       => 'gateway_customer_id',
			'type'       => 'tinytext',
			'default'    => '',
			'searchable' => true,
			'sortable'   => true,
			'transition' => true
		),

		// gateway_subscription_id
		array(
			'name'       => 'gateway_subscription_id',
			'type'       => 'tinytext',
			'default'    => '',
			'searchable' => true,
			'sortable'   => true,
			'transition' => true
		),

		// gateway
		array(
			'name'    => 'gateway',
			'type'    => 'tinytext',
			'default' => '',
		),

		// signup_method
		array(
			'name'    => 'signup_method',
			'type'    => 'tinytext',
			'default' => '',
		),

		// subscription_key
		array(
			'name'       => 'subscription_key',
			'type'       => 'varchar',
			'length'     => '32',
			'default'    => '',
			'searchable' => true,
			'sortable'   => true
		),

		// notes
		array(
			'name'    => 'notes',
			'type'    => 'longtext',
			'default' => ''
		),

		// upgraded_from
		array(
			'name'     => 'upgraded_from',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'default'  => ''
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

		// disabled
		array(
			'name'     => 'disabled',
			'type'     => 'smallint',
			'unsigned' => true,
			'default'  => '',
			'pattern'  => '%d'
		),

		// uuid
		array(
			'uuid' => true,
		)

	);

}
