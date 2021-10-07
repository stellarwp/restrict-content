<?php
/**
 * class-batch-csv-export-base.php
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 */

namespace RCP\Batch\CSV_Exports;

use RCP\Utils\Batch\Abstract_Job_Callback;
use RCP\Utils\Batch\Job;

/**
 * Class Base
 *
 * @package RCP\Batch\CSV_Exports
 */
abstract class Base extends Abstract_Job_Callback {

	/**
	 * Type of export being performed (`memberships`, `payments`, etc.)
	 *
	 * @var string
	 */
	protected $export_type;

	/**
	 * File the export data is stored in.
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * Settings from the UI form
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Base constructor.
	 *
	 * @param Job $job
	 */
	public function __construct( $job ) {

		parent::__construct( $job );

		$exporter          = rcp_get_csv_exporter_by_callback( $this->get_job()->get_callback() );
		$this->export_type = ! empty( $exporter['key'] ) ? $exporter['key'] : '';

		$job_data = $this->get_job()->get_data();

		// Set up the file path.
		$this->file = ! empty( $job_data['filepath'] ) ? $job_data['filepath'] : false;

		if ( empty( $this->file ) ) {
			$this->file = tempnam( get_temp_dir(), 'rcp-' );
			$this->get_job()->add_data( array(
				'filepath' => tempnam( get_temp_dir(), 'rcp-' )
			) );
		}

		// Get settings from the job data.
		$this->settings = ! empty( $job_data['settings'] ) ? $job_data['settings'] : array();

	}

	/**
	 * Returns the column headers for the export.
	 *
	 * @return array
	 */
	abstract public function get_columns();

	/**
	 * Returns the total number of records to be exported.
	 *
	 * @return int
	 */
	abstract public function get_total();

	/**
	 * @return $this
	 */
	public function execute() {

		rcp_log( sprintf( 'Batch Processing: Initiating RCP Batch Export %s. Current step: %d; current count: %d; total count: %d.', $this->export_type, $this->get_job()->get_step(), $this->get_job()->get_current_count(), $this->get_job()->get_total_count() ), true );

		if ( $this->start_time === null ) {
			$this->start_time = time();
		}

		if ( ! rcp_current_user_can_export() ) {
			$this->get_job()->add_error( __( 'You do not have permission to perform this action.', 'rcp' ) );

			return $this;
		}

		if ( $this->time_exceeded() || $this->get_job()->is_completed() ) {
			return $this;
		}

		parent::execute();

		// On the first step, clear errors, clean up the file, add headers, calculate total count.
		if ( 0 === $this->get_job()->get_step() ) {
			$this->get_job()->clear_errors();
			$this->process_headers();
			$this->get_job()->set_total_count( $this->get_total() );
		}

		// Get the data for this batch.
		$batch = $this->get_batch();

		// If we don't have any, we're done.
		if ( empty( $batch ) ) {
			$this->finish();

			return $this;
		}

		// Otherwise, stash this batch and continue.
		$this->process_batch( $batch );
		$this->get_job()->adjust_current_count( count( $batch ) );

		$current_step = $this->get_job()->get_step();
		$current_step++;
		$this->get_job()->set_step( $current_step );

		return $this;

	}

	/**
	 * Retrieves the batch data from the database.
	 *
	 * @return array
	 */
	abstract public function get_batch();

	/**
	 * @inheritDoc
	 */
	public function finish() {
		// Set job to complete.
		$this->get_job()->set_status( 'complete' );

		$errors = $this->get_job()->get_errors();

		rcp_log( sprintf( 'Batch CSV Export: Job complete. Errors: %s', var_export( $errors, true ) ), true );
	}

	/**
	 * Processes the CSV column headers and writes the resulting string to the file.
	 *
	 * @since 3.4
	 */
	protected function process_headers() {

		$row_data = '';
		$columns  = $this->get_columns();
		$i        = 1;

		foreach ( $columns as $column_key => $column_name ) {
			$row_data .= sprintf( '"%s"', addslashes( $column_name ) );
			$row_data .= $i === count( $columns ) ? '' : ',';
			$i++;
		}

		$row_data .= "\r\n";

		$this->stash_batch( $row_data, false );

	}

	/**
	 * Processes the current batch data to format each row and stash the resulting string in the file.
	 *
	 * @param array $batch Current batch of data.
	 *
	 * @since 3.4
	 */
	protected function process_batch( $batch ) {

		$row_data = '';
		$columns  = $this->get_columns();

		if ( empty( $batch ) || ! is_array( $batch ) ) {
			return;
		}

		foreach ( $batch as $row ) {
			$i = 1;

			foreach ( $columns as $column_key => $column_name ) {
				if ( array_key_exists( $column_key, $row ) ) {
					$row_data .= sprintf( '"%s"', addslashes( preg_replace( "/\"/", "'", $row[ $column_key ] ) ) );
				}
				$row_data .= $i === count( $columns ) ? '' : ',';
				$i++;
			}

			$row_data .= "\r\n";
		}

		$this->stash_batch( $row_data );

	}

	/**
	 * Retrieves the CSV file contents.
	 *
	 * @since 3.4
	 * @return string
	 */
	protected function get_file() {

		$file = '';

		if ( @file_exists( $this->file ) ) {
			$file = @file_get_contents( $this->file );
		} else {
			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );
		}

		return $file;

	}

	/**
	 * Appends the formatted string data to the file and saves the new contents.
	 *
	 * @param string $batch  Batch data formatted as a comma-separated string.
	 * @param bool   $append Whether to append the new data (true) or override (false).
	 *
	 * @since 3.4
	 */
	protected function stash_batch( $batch, $append = true ) {

		$file = $this->get_file();

		if ( $append ) {
			$file .= $batch;
		} else {
			$file = $batch;
		}

		@file_put_contents( $this->file, $file );

	}

	/**
	 * Message to display upon job completion.
	 *
	 * @since 3.4
	 * @return string
	 */
	public function get_complete_message() {
		$download_url = wp_nonce_url( add_query_arg( array(
			'rcp-action' => 'download_export_file',
			'export_id'  => urlencode( $this->get_job()->get_id() )
		), admin_url() ), 'rcp_download_export_file' );

		return '<a href="' . esc_url( $download_url ) . '">' . __( 'Download Export File', 'rcp' ) . '</a>';
	}

}
