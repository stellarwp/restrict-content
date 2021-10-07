<?php
/**
 * Membership Query Class.
 *
 * @package     RCP
 * @subpackage  Database\Queries
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

namespace RCP\Database\Queries;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Query;

/**
 * Class used for querying memberships.
 *
 * @since 3.0
 *
 * @see   \RCP\Database\Queries\Membership::__construct() for accepted arguments.
 */
class Membership extends Query {

	/** Table Properties ******************************************************/

	/**
	 * Name of the database table to query.
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $table_name = 'memberships';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $table_alias = 'm';

	/**
	 * Name of class used to setup the database schema
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $table_schema = '\\RCP\\Database\\Schemas\\Memberships';

	/** Item ******************************************************************/

	/**
	 * Name for a single item
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $item_name = 'membership';

	/**
	 * Plural version for a group of items.
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $item_name_plural = 'memberships';

	/**
	 * Callback function for turning IDs into objects
	 *
	 * @since  3.0
	 * @access public
	 * @var mixed
	 */
	protected $item_shape = '\\RCP_Membership';

	/**
	 * Group to cache queries and queried items in.
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $cache_group = 'memberships';

	/**
	 * Sets up the membership query, based on the query vars passed.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param string|array $query                   {
	 *                                              Optional. Array or query string of membership query parameters.
	 *                                              Default empty.
	 *
	 * @type int           $id                      A membership ID to only return that membership. Default empty.
	 * @type array         $id__in                  Array of membership IDs to include. Default empty.
	 * @type array         $id__not_in              Array of membership IDs to exclude. Default empty.
	 * @type int           $customer_id             A customer ID to only return this customer's memberships. Default
	 *                                              empty.
	 * @type array         $customer_id__in         Array of customer IDs to include. Default empty.
	 * @type array         $customer_id__not_in     Array of customer IDs to exclude. Default empty.
	 * @type string        $object_id               An object ID to only return memberships for this object. Default
	 *                                              empty.
	 * @type array         $object_id__in           Array of object IDs to include. Default empty.
	 * @type array         $object_id__not_in       Array of object IDs to exclude. Default empty.
	 * @type string        $object_type             An object type to only return this type. Default empty.
	 * @type array         $object_type__in         Array of object types to include. Default empty.
	 * @type array         $object_type__not_in     Array of object types to exclude. Default empty.
	 * @type string        $currency                A currency to only show memberships using this currency. Default
	 *                                              empty.
	 * @type array         $date_query              Query all datetime columns together. See WP_Date_Query.
	 * @type array         $created_date_query      Date query clauses to limit memberships by. See WP_Date_Query.
	 *                                              Default null.
	 * @type array         $activated_date_query    Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type array         $trial_end_date_query    Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type array         $renewed_date_query      Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type array         $cancellation_date_query Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type array         $expiration_date_query   Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type array         $date_modified_query     Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type int           $auto_renew              Auto renewal status. Default null.
	 * @type string        $status                  A status to filter by. Default null for all statuses.
	 * @type array         $status__in              Array of statuses to include. Default empty.
	 * @type array         $status__not_in          Array of statuses to exclude. Default empty.
	 * @type string        $gateway_customer_id     A gateway customer ID to search for. Default empty.
	 * @type string        $gateway_subscription_id A gateway subscription ID to search for. Default empty.
	 * @type string        $gateway                 A gateway to filter by. Default empty.
	 * @type string        $subscription_key        A subscription key to filter by. Default empty.
	 * @type int           $upgraded_from           A membership ID this membership upgraded from. Default empty.
	 * @type int           $disabled                Whether or not to show disabled memberships. Set to `0` to only
	 *                                              include enabled memberships, set to `1` to only include disabled
	 *                                              memberships, set to empty string to include both. Default empty.
	 * @type bool          $count                   Whether to return a membership count (true) or array of membership
	 *                                              objects. Default false.
	 * @type string        $fields                  Item fields to return. Accepts any column known names
	 *                                              or empty (returns an array of complete membership objects). Default
	 *                                              empty.
	 * @type int           $number                  Limit number of memberships to retrieve. Default 100.
	 * @type int           $offset                  Number of memberships to offset the query. Used to build LIMIT
	 *                                              clause. Default 0.
	 * @type bool          $no_found_rows           Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
	 * @type string|array  $orderby                 Accepts 'id', 'object_id', 'object_type', 'currency',
	 *                                              `initial_amount`, `recurring_amount`, `created_date`,
	 *                                              `trial_end_date`, `cancellation_date`, `expiration_date`,
	 *                                              `times_billed`, `maximum_renewals`, `status`,
	 *                                              `gateway_customer_id`, `gateway_subscription_id`, and
	 *                                              `subscription_key`. Also accepts false, an empty array, or 'none'
	 *                                              to disable `ORDER BY` clause. Default 'id'.
	 * @type string        $order                   How to order results. Accepts 'ASC', 'DESC'. Default 'DESC'.
	 * @type string        $search                  Search term(s) to retrieve matching memberships for. Default empty.
	 * @type bool          $update_cache            Whether to prime the cache for found memberships. Default false.
	 * }
	 */
	public function __construct( $query = array() ) {
		parent::__construct( $query );
	}

}
