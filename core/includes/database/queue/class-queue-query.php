<?php
/**
 * Queue Query Class.
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
 * Class used for querying the job queue.
 *
 * @since 3.0
 *
 * @see   \RCP\Database\Queries\Queue::__construct() for accepted arguments.
 */
class Queue extends Query {

	/** Table Properties ******************************************************/

	/**
	 * Name of the database table to query.
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $table_name = 'queue';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $table_alias = 'q';

	/**
	 * Name of class used to setup the database schema
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $table_schema = '\\RCP\\Database\\Schemas\\Queue';

	/** Item ******************************************************************/

	/**
	 * Name for a single item
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $item_name = 'job';

	/**
	 * Plural version for a group of items.
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $item_name_plural = 'jobs';

	/**
	 * Callback function for turning IDs into objects
	 *
	 * @since  3.0
	 * @access public
	 * @var mixed
	 */
	protected $item_shape = '\\RCP\\Utils\\Batch\\Job';

	/**
	 * Group to cache queries and queried items in.
	 *
	 * @since  3.0
	 * @access public
	 * @var string
	 */
	protected $cache_group = 'queue';

	/**
	 * Sets up the queue query, based on the query vars passed.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param string|array $query                   {
	 *                                              Optional. Array or query string of membership query parameters.
	 *                                              Default empty.
	 *
	 * @type int           $id                      A job ID to only return that job. Default empty.
	 * @type array         $id__in                  Array of job IDs to include. Default empty.
	 * @type array         $id__not_in              Array of job IDs to exclude. Default empty.
	 * @type int           $queue                   A queue name to only return jobs in this queue. Default empty.
	 * @type array         $queue__in               Array of queues to include. Default empty.
	 * @type array         $queue__not_in           Array of queues to exclude. Default empty.
	 * @type string        $name                    A job name. Default empty.
	 * @type string        $status                  A job status to only return jobs with this status. Default empty.
	 * @type array         $status__in              Array of statuses to include. Default empty.
	 * @type array         $status__not_in          Array of statuses to exclude. Default empty.
	 * @type bool          $count                   Whether to return a job count (true) or array of job objects.
	 *                                              Default false.
	 * @type string        $fields                  Item fields to return. Accepts any column known names
	 *                                              or empty (returns an array of complete job objects). Default
	 *                                              empty.
	 * @type int           $number                  Limit number of jobs to retrieve. Default 100.
	 * @type int $offset                            Number of jobs to offset the query. Used to build LIMIT clause.
	 *                                              Default 0.
	 * @type bool          $no_found_rows           Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
	 * @type string|array  $orderby                 Accepts 'id', 'name', and 'status'. Also accepts false, an empty
	 *                                              array, or 'none' to disable `ORDER BY` clause. Default 'id'.
	 * @type string        $order                   How to order results. Accepts 'ASC', 'DESC'. Default 'DESC'.
	 * @type string        $search                  Search term(s) to retrieve matching jobs for. Default empty.
	 * @type bool          $update_cache            Whether to prime the cache for found memberships. Default false.
	 * }
	 */
	public function __construct( $query = array() ) {
		parent::__construct( $query );
	}

}