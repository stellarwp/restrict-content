<?php
/**
 * Log Object
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 * @since     3.4
 */

namespace RCP\Logs;

use RCP\Base_Object;

/**
 * Class Log
 *
 * @package RCP\Logs
 */
class Log extends Base_Object {

	/**
	 * Log ID
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Object type - one of:
	 *
	 *        - customers
	 *        - memberships
	 *        - payments
	 *
	 * @var string
	 */
	protected $object_type = '';

	/**
	 * ID of the associated object or `null` if not associated with an object
	 *
	 * @var null|int
	 */
	protected $object_id = null;

	/**
	 * ID of the associated user or `null` if not associated with a user
	 *
	 * @var null|int
	 */
	protected $user_id = null;

	/**
	 * Type of log/event
	 *
	 * @var string
	 */
	protected $type = '';

	/**
	 * Title of the log
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Additional log content
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * Will be `1` if the log is an error, otherwise `0`
	 *
	 * @var int
	 */
	protected $is_error = 0;

	/**
	 * Date the log was created, in MySQL format
	 *
	 * @var string
	 */
	protected $date_created = '';

	/**
	 * Date the log was last modified, in MySQL format
	 *
	 * @var string
	 */
	protected $date_modified = '';

	/**
	 * UUID
	 *
	 * @var string
	 */
	protected $uuid = '';

}
