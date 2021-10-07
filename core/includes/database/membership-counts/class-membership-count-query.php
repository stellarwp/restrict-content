<?php
/**
 * Membership Count Query Class.
 *
 * @package     RCP
 * @subpackage  Database\Queries
 * @copyright   Copyright (c) 2019, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.3
 */

namespace RCP\Database\Queries;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use RCP\Database\Query;

/**
 * Class used for querying memberships.
 *
 * @since 3.3
 *
 * @see   \RCP\Database\Queries\Membership_Count::__construct() for accepted arguments.
 */
class Membership_Count extends Query {

	/** Table Properties ******************************************************/

	/**
	 * Name of the database table to query.
	 *
	 * @since  3.3
	 * @access public
	 * @var string
	 */
	protected $table_name = 'membership_counts';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * @since  3.3
	 * @access public
	 * @var string
	 */
	protected $table_alias = 'mc';

	/**
	 * Name of class used to setup the database schema
	 *
	 * @since  3.3
	 * @access public
	 * @var string
	 */
	protected $table_schema = '\\RCP\\Database\\Schemas\\Membership_Counts';

	/** Item ******************************************************************/

	/**
	 * Name for a single item
	 *
	 * @since  3.3
	 * @access public
	 * @var string
	 */
	protected $item_name = 'membership_count';

	/**
	 * Plural version for a group of items.
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $item_name_plural = 'membership_counts';

	/**
	 * Callback function for turning IDs into objects
	 *
	 * @since  3.3
	 * @access public
	 * @var mixed
	 */
	protected $item_shape = '\\RCP_Membership_Count';

	/**
	 * Group to cache queries and queried items in.
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $cache_group = 'membership_counts';

	/**
	 * Sets up the membership count query, based on the query vars passed.
	 *
	 * @since  3.3
	 * @access public
	 *
	 * @param string|array $query                   {
	 *                                              Optional. Array or query string of membership count query
	 *                                              parameters. Default empty.
	 *
	 * @type int           $id                      An entry ID to only return that entry. Default empty.
	 * @type array         $id__in                  Array of entry IDs to include. Default empty.
	 * @type array         $id__not_in              Array of entry IDs to exclude. Default empty.
	 * @type int           $level_id                A level ID to only return this level's entries. Default
	 *                                              empty.
	 * @type array         $level_id__in            Array of level IDs to include. Default empty.
	 * @type array         $level_id__not_in        Array of level IDs to exclude. Default empty.
	 * @type array         $date_query              Query all datetime columns together. See WP_Date_Query.
	 * @type array         $created_date_query      Date query clauses to limit entries by. See WP_Date_Query.
	 *                                              Default null.
	 * @type bool          $count                   Whether to return an entry count (true) or array of entry
	 *                                              objects. Default false.
	 * @type string        $fields                  Item fields to return. Accepts any column known names
	 *                                              or empty (returns an array of complete entry objects). Default
	 *                                              empty.
	 * @type int           $number                  Limit number of entries to retrieve. Default 100.
	 * @type int           $offset                  Number of entries to offset the query. Used to build LIMIT
	 *                                              clause. Default 0.
	 * @type bool          $no_found_rows           Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
	 * @type string|array  $orderby                 Accepts 'id', 'level_id', 'active_count', 'pending_count',
	 *                                              `cancelled_count`, `expired_count`, and `date_created`.
	 *                                              Also accepts false, an empty array, or 'none' to disable
	 *                                              `ORDER BY` clause. Default 'id'.
	 * @type string        $order                   How to order results. Accepts 'ASC', 'DESC'. Default 'DESC'.
	 * @type string        $search                  Search term(s) to retrieve matching entries for. Default empty.
	 * @type bool          $update_cache            Whether to prime the cache for found entries. Default false.
	 * }
	 */
	public function __construct( $query = array() ) {
		parent::__construct( $query );
	}

}
