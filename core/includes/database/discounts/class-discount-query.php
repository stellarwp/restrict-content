<?php
/**
 * Discount Query Class.
 *
 * @package     RCP
 * @subpackage  Database\Queries
 * @copyright   Copyright (c) 2019, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.3
 */

namespace RCP\Database\Queries;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Query;

/**
 * Class used for querying discounts.
 *
 * @since 3.3
 *
 * @see   \RCP\Database\Queries\Discount::__construct() for accepted arguments.
 */
class Discount extends Query {

	/** Table Properties ******************************************************/

	/**
	 * Name of the database table to query.
	 *
	 * @since  3.3
	 * @access public
	 * @var string
	 */
	protected $table_name = 'discounts';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * @since  3.3
	 * @access public
	 * @var string
	 */
	protected $table_alias = 'd';

	/**
	 * Name of class used to setup the database schema
	 *
	 * @since  3.3
	 * @access public
	 * @var string
	 */
	protected $table_schema = '\\RCP\\Database\\Schemas\\Discounts';

	/** Item ******************************************************************/

	/**
	 * Name for a single item
	 *
	 * @since  3.3
	 * @access public
	 * @var string
	 */
	protected $item_name = 'discount';

	/**
	 * Plural version for a group of items.
	 *
	 * @since  3.3
	 * @access public
	 * @var string
	 */
	protected $item_name_plural = 'discounts';

	/**
	 * Callback function for turning IDs into objects
	 *
	 * @since  3.3
	 * @access public
	 * @var mixed
	 */
	protected $item_shape = '\\RCP_Discount';

	/**
	 * Group to cache queries and queried items in.
	 *
	 * @since  3.3
	 * @access public
	 * @var string
	 */
	protected $cache_group = 'discounts';

	/**
	 * Sets up the discount query, based on the query vars passed.
	 *
	 * @since  3.3
	 * @access public
	 *
	 * @param string|array $query                   {
	 *                                              Optional. Array or query string of discount query parameters.
	 *                                              Default empty.
	 *
	 * @type int           $id                      A discount ID to only return that discount. Default empty.
	 * @type array         $id__in                  Array of discount IDs to include. Default empty.
	 * @type array         $id__not_in              Array of discount IDs to exclude. Default empty.
	 * @type string        $name                    Specific discount name to retrieve. Default empty.
	 * @type string        $amount                  Retrieve discounts for this amount. Default empty.
	 * @type string        $unit                    Retrieve discounts with this unit. Default empty.
	 * @type string        $code                    Specific discount code to retrieve. Default empty.
	 * @type string        $status                  Status to filter by. Default empty.
	 * @type int           $one_time                Set to `1` to only retrieve one-time discounts. Set to `0` to only
	 *                                              retrieve recurring discounts. Omit to retrieve all. Default empty.
	 * @type array         $date_query              Query all datetime columns together. See WP_Date_Query.
	 * @type array         $expiration_query        Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type array         $date_created_query      Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type array         $date_modified_query     Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type bool          $count                   Whether to return a discount count (true) or array of discount
	 *                                              objects. Default false.
	 * @type string        $fields                  Item fields to return. Accepts any column known names
	 *                                              or empty (returns an array of complete discount objects). Default
	 *                                              empty.
	 * @type int           $number                  Limit number of discount to retrieve. Default 100.
	 * @type int           $offset                  Number of discount to offset the query. Used to build LIMIT
	 *                                              clause. Default 0.
	 * @type bool          $no_found_rows           Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
	 * @type string|array  $orderby                 Accepts 'id', 'name', 'code', 'use_count', 'max_uses', 'status',
	 *                                              'expiration', 'date_created', and 'date_modified'. Also accepts
	 *                                              false, an empty array, or 'none' to disable `ORDER BY` clause.
	 *                                              Default 'id'.
	 * @type string        $order                   How to order results. Accepts 'ASC', 'DESC'. Default 'DESC'.
	 * @type string        $search                  Search term(s) to retrieve matching discounts for. Default empty.
	 * @type bool          $update_cache            Whether to prime the cache for found discounts. Default false.
	 * }
	 */
	public function __construct( $query = array() ) {
		parent::__construct( $query );
	}

}
