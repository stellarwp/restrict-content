<?php
/**
 * Membership Level Query Class.
 *
 * @package     RCP
 * @subpackage  Database\Queries
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.4
 */

namespace RCP\Database\Queries;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Query;

/**
 * Class used for querying membership levels.
 *
 * @since 3.4
 *
 * @see   \RCP\Database\Queries\Membership_Level::__construct() for accepted arguments.
 */
class Membership_Level extends Query {

	/**
	 * We have to override this to strip out the `rcp_` prefix. This table doesn't have that.
	 *
	 * @var string Table prefix
	 */
	protected $prefix = '';

	/** Table Properties ******************************************************/

	/**
	 * Name of the database table to query.
	 *
	 * @since  3.4
	 * @access public
	 * @var string
	 */
	protected $table_name = 'restrict_content_pro';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * @since  3.4
	 * @access public
	 * @var string
	 */
	protected $table_alias = 'ml';

	/**
	 * Name of class used to setup the database schema
	 *
	 * @since  3.4
	 * @access public
	 * @var string
	 */
	protected $table_schema = '\\RCP\\Database\\Schemas\\Membership_Levels';

	/** Item ******************************************************************/

	/**
	 * Name for a single item
	 *
	 * @since  3.4
	 * @access public
	 * @var string
	 */
	protected $item_name = 'membership_level';

	/**
	 * Plural version for a group of items.
	 *
	 * @since  3.4
	 * @access public
	 * @var string
	 */
	protected $item_name_plural = 'membership_levels';

	/**
	 * Callback function for turning IDs into objects
	 *
	 * @since  3.4
	 * @access public
	 * @var mixed
	 */
	protected $item_shape = '\\RCP\\Membership_Level';

	/**
	 * Group to cache queries and queried items in.
	 *
	 * @since  3.4
	 * @access public
	 * @var string
	 */
	protected $cache_group = 'membership_levels';

	/**
	 * Sets up the membership level query, based on the query vars passed.
	 *
	 * @since  3.4
	 * @access public
	 *
	 * @param string|array $query                   {
	 *                                              Optional. Array or query string of level query parameters.
	 *                                              Default empty.
	 *
	 * @type int           $id                      A level ID to only return that level. Default empty.
	 * @type array         $id__in                  Array of level IDs to include. Default empty.
	 * @type array         $id__not_in              Array of level IDs to exclude. Default empty.
	 * @type string        $name                    Specific level name to retrieve. Default empty.
	 * @type string        $status                  Status to filter by. Default empty.
	 * @type array         $date_query              Query all datetime columns together. See WP_Date_Query.
	 * @type array         $date_created_query      Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type array         $date_modified_query     Date query clauses to limit by. See WP_Date_Query. Default null.
	 * @type bool          $count                   Whether to return a level count (true) or array of level
	 *                                              objects. Default false.
	 * @type string        $fields                  Item fields to return. Accepts any column known names
	 *                                              or empty (returns an array of complete level objects). Default
	 *                                              empty.
	 * @type int           $number                  Limit number of levels to retrieve. Default 100.
	 * @type int           $offset                  Number of levels to offset the query. Used to build LIMIT
	 *                                              clause. Default 0.
	 * @type bool          $no_found_rows           Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
	 * @type string|array  $orderby                 Accepts 'id', 'name', 'duration', 'trial_duration',
	 *                                              'maximum_renewals', 'list_order', 'level', 'status',
	 *                                              'date_created', and 'date_modified'. Also accepts
	 *                                              false, an empty array, or 'none' to disable `ORDER BY` clause.
	 *                                              Default 'id'.
	 * @type string        $order                   How to order results. Accepts 'ASC', 'DESC'. Default 'DESC'.
	 * @type string        $search                  Search term(s) to retrieve matching levels for. Default empty.
	 * @type bool          $update_cache            Whether to prime the cache for found levels. Default false.
	 * }
	 */
	public function __construct( $query = array() ) {
		parent::__construct( $query );
	}

}
