<?php
/**
 * Job Callback Interface
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 */

namespace RCP\Utils\Batch;

/**
 * Interface Job_Callback_Interface
 *
 * @package RCP\Utils\Batch
 * @since   3.0
 */
interface Job_Callback_Interface {

	/**
	 * Execute the job
	 *
	 * @access public
	 * @since  3.0
	 * @return mixed
	 */
	public function execute();


	/**
	 * Runs any tasks required to finish a job.
	 *
	 * @access public
	 * @since  3.0
	 * @return mixed|void
	 */
	public function finish();

}