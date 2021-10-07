<?php
/**
 * Batch CSV Import Base Class
 *
 * @since     3.1
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 * @package   restrict-content-pro
 */

use RCP\Utils\Batch\Abstract_Job_Callback;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RCP_Batch_Callback_CSV_Import_Base
 *
 * @since 3.1
 */
class RCP_Batch_Callback_CSV_Import_Base extends Abstract_Job_Callback {

	/**
	 * Current batch of rows
	 *
	 * @var array
	 */
	protected $rows;

	/**
	 * Path to the file being imported.
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * The parsed CSV file being imported.
	 *
	 * @var array
	 */
	protected $csv;

	/**
	 * Map of CSV column headers -> database fields
	 *
	 * @var array
	 */
	protected $field_map;

	/**
	 * Settings from the UI form
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * RCP_Batch_Callback_CSV_Import_Base constructor.
	 *
	 * @param \RCP\Utils\Batch\Job $job
	 */
	public function __construct( $job ) {

		parent::__construct( $job );

		$file = ! empty( $this->get_job()->get_data()['file_path'] ) ? $this->get_job()->get_data()['file_path'] : '';

		if ( ! empty( $file ) ) {
			$this->set_csv_file( $file );
		}

		$this->field_map = ! empty( $this->get_job()->get_data()['field_map'] ) ? $this->get_job()->get_data()['field_map'] : array();
		$this->settings  = ! empty( $this->get_job()->get_data()['settings'] ) ? $this->get_job()->get_data()['settings'] : array();

	}

	/**
	 * Set the CSV file path. This then also parses the CSV and populates that property.
	 *
	 * @param string $file
	 */
	public function set_csv_file( $file ) {
		$uploads    = wp_get_upload_dir();
		$this->file = path_join( $uploads['basedir'], $file );

		if ( empty( $this->file ) || ! file_exists( $this->file ) ) {
			return;
		}

		$csv = array_map( 'str_getcsv', file( $this->file ) );
		array_walk( $csv, function ( &$a ) use ( $csv ) {
			/*
			 * Make sure the two arrays have the same lengths.
			 * If not, we trim the larger array to match the smaller one.
			 */
			$min     = min( count( $csv[0] ), count( $a ) );
			$headers = array_slice( $csv[0], 0, $min );
			$values  = array_slice( $a, 0, $min );
			$a       = array_combine( $headers, $values );
		} );
		array_shift( $csv );

		$this->csv = $csv;
	}

	/**
	 * Get the column header names
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = array();

		if ( isset( $this->csv[0] ) && is_array( $this->csv[0] ) ) {
			$columns = array_keys( $this->csv[0] );
		}

		return $columns;

	}

	/**
	 * Get the first row of the CSV file
	 *
	 * This is used for showing an example of what the import will look like.
	 *
	 * @return array The first row after the header.
	 */
	public function get_first_row() {
		if ( ! is_array( $this->csv ) ) {
			return array();
		}

		return array_map( array( $this, 'trim_preview' ), current( $this->csv ) );
	}

	/**
	 * Map the CSV column headers to database fields
	 *
	 * @param array $fields
	 */
	public function map_fields( $fields ) {

		$this->field_map = array_map( 'sanitize_text_field', $fields );

		$this->get_job()->add_data( array(
			'field_map' => $this->field_map
		) );
	}

	/**
	 * Trims a column value for preview
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	protected function trim_preview( $string ) {

		if ( ! is_numeric( $string ) ) {

			$long   = strlen( $string ) >= 30;
			$string = substr( $string, 0, 30 );
			$string = $long ? $string . '...' : $string;

		}

		return $string;

	}

	/**
	 * @inheritdoc
	 */
	public function execute() {

		rcp_log( sprintf( 'Batch Processing: Initiating RCP_Batch_Callback_CSV_Import_Base. Current step: %d; current count: %d; total count: %d.', $this->get_job()->get_step(), $this->get_job()->get_current_count(), $this->get_job()->get_total_count() ), true );

		if ( $this->start_time === null ) {
			$this->start_time = time();
		}

		if ( $this->time_exceeded() || $this->get_job()->is_completed() ) {
			return $this;
		}

		parent::execute();

		if ( 0 === $this->get_job()->get_step() ) {
			$this->get_job()->clear_errors();
		}

		if ( ! file_exists( $this->file ) ) {
			$this->get_job()->add_error( __( 'Import file not found.', 'rcp' ) );
			$this->finish();

			return $this;
		}

		$this->rows = $this->get_batch();

		if ( empty( $this->rows ) || $this->get_job()->is_completed() ) {
			$this->finish();
			return $this;
		}

		$this->process_batch();

		$current_step = $this->get_job()->get_step();
		$current_step++;
		$this->get_job()->set_step( $current_step );

		return $this;

	}

	/**
	 * Get the next batch of CSV rows.
	 *
	 * @since 3.1
	 * @return array
	 */
	private function get_batch() {

		$rows = array();
		$i    = 1;

		if ( is_array( $this->csv ) ) {
			if ( empty( $this->get_job()->get_total_count() ) ) {
				$this->get_job()->set_total_count( count( $this->csv ) );

				rcp_log( sprintf( 'Batch CSV Import: Total count: %d.', count( $this->csv ) ), true );
			}

			foreach ( $this->csv as $row_number => $row ) {

				// Skip all rows until we pass our offset.
				if ( $row_number + 1 <= $this->offset ) {
					continue;
				}

				// Bail if we're done with this batch.
				if ( $i > $this->size ) {
					break;
				}

				/*
				 * The first real row number will be `0`, which isn't user friendly for most people. So we add `+2` to
				 * achieve the following:
				 *
				 * 1. First, start at `1` instead of `0`.
				 * 2. Account for the header, which should be row #1. The first actual value starts at #2.
				 *
				 * This just improves row number display during error logging.
				 */
				$adjusted_row_number = $row_number + 2;

				$rows[ $adjusted_row_number ] = $row;

				$i ++;

			}
		}

		rcp_log( sprintf( 'Batch CSV Import: %d results found in query for LIMIT %d OFFSET %d.', count( $rows ), $this->size, $this->offset ) );

		return $rows;

	}

	/**
	 * Overwrite this.
	 */
	public function process_batch() {

		global $rcp_options;

		// Disable debug mode.
		$rcp_options['debug_mode'] = false;

	}

	/**
	 * Complete the import
	 *        - Set status to "complete".
	 *        - Delete the CSV file.
	 *
	 * @return void
	 */
	public function finish() {

		// Set job to complete.
		$this->get_job()->set_status( 'complete' );

		$errors = $this->get_job()->get_errors();

		rcp_log( sprintf( 'Batch CSV Import: Job complete. Errors: %s', var_export( $errors, true ) ), true );

		// Delete the uploaded file.
		wp_delete_file( $this->file );

	}

}
