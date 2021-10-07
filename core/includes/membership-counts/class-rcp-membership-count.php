<?php
/**
 * Membership Count Object
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Ashley Gibson
 * @license   GPL2+
 */

class RCP_Membership_Count extends \RCP\Base_Object {

	/**
	 * Entry ID
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * ID of the membership level.
	 *
	 * @var int
	 */
	protected $level_id = 0;

	/**
	 * Number of active memberships.
	 *
	 * @var int
	 */
	protected $active_count = 0;

	/**
	 * Number of pending memberships.
	 *
	 * @var int
	 */
	protected $pending_count = 0;

	/**
	 * Number of cancelled memberships.
	 *
	 * @var int
	 */
	protected $cancelled_count = 0;

	/**
	 * Number of expired memberships.
	 *
	 * @var int
	 */
	protected $expired_count = 0;

	/**
	 * Date this data was counted.
	 *
	 * @var string
	 */
	protected $date_created = '';

	/**
	 * Get the entry ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return absint( $this->id );
	}

	/**
	 * Get the ID of the membership level.
	 *
	 * @return int
	 */
	public function get_level_id() {
		return absint( $this->level_id );
	}

	/**
	 * Get the number of active memberships on this date.
	 *
	 * @return int
	 */
	public function get_active_count() {
		return absint( $this->active_count );
	}

	/**
	 * Get the number of pending memberships on this date.
	 *
	 * @return int
	 */
	public function get_pending_count() {
		return absint( $this->pending_count );
	}

	/**
	 * Get the number of cancelled memberships on this date.
	 *
	 * @return int
	 */
	public function get_cancelled_count() {
		return absint( $this->cancelled_count );
	}

	/**
	 * Get the number of expired memberships on this date.
	 *
	 * @return int
	 */
	public function get_expired_count() {
		return absint( $this->expired_count );
	}

	/**
	 * Get the date this record was created.
	 *
	 * @return string
	 */
	public function get_date_created() {
		return $this->date_created;
	}

}
