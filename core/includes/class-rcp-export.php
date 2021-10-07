<?php
/**
 * Export Class
 *
 * This is the base class for all export methods. Each data export type (subscribers, payments, etc) extend this class
 *
 * @package     Restrict Content Pro
 * @subpackage  Export Class
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class RCP_Export {

	/**
	 * Our export type. Used for export-type specific filters / actions
	 *
	 * @access      public
	 * @var         string
	 * @since       1.5
	 */
	public $export_type = 'default';

	/**
	 * Can we export?
	 *
	 * @access      public
	 * @since       1.5
	 * @return      bool
	 */
	public function can_export() {
		return (bool) apply_filters( 'rcp_export_capability', current_user_can( 'rcp_export_data' ) );
	}

	/**
	 * Set the export headers
	 *
	 * @access      public
	 * @since       1.5
	 * @return      void
	 */
	public function headers() {
		ignore_user_abort( true );

		if ( ! rcp_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) )
			set_time_limit( 0 );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=rcp-export-' . $this->export_type . '-' . date( 'm-d-Y' ) . '.csv' );
		header( "Expires: 0" );
	}

	/**
	 * Set the CSV columns
	 *
	 * @access      public
	 * @since       1.5
	 * @return      array
	 */
	public function csv_cols() {
		$cols = array(
			'id'   => __( 'ID',   'rcp' ),
			'date' => __( 'Date', 'rcp' )
		);
		return $cols;
	}

	/**
	 * Retrieve CSV columns
	 *
	 * @access      public
	 * @since       1.5
	 * @return      array
	 */
	public function get_csv_cols() {
		$cols = $this->csv_cols();
		return apply_filters( 'rcp_export_csv_cols_' . $this->export_type, $cols );
	}

	/**
	 * Output the CSV columns
	 *
	 * @access      public
	 * @since       1.5
	 * @return      void
	 */
	public function csv_cols_out() {
		$cols = $this->get_csv_cols();
		$i = 1;
		foreach( $cols as $col_id => $column ) {
			echo '"' . $column . '"';
			echo $i == count( $cols ) ? '' : ',';
			$i++;
		}
		echo "\r\n";
	}

	/**
	 * Get the data being exported
	 *
	 * @access      public
	 * @since       1.5
	 * @return      array $data
	 */
	public function get_data() {
		// Just a sample data array
		$data = array(
			0 => array(
				'id'   => '',
				'data' => date( 'F j, Y' )
			),
			1 => array(
				'id'   => '',
				'data' => date( 'F j, Y' )
			)
		);

		$data = apply_filters( 'rcp_export_get_data', $data );
		$data = apply_filters( 'rcp_export_get_data_' . $this->export_type, $data );

		return $data;
	}

	/**
	 * Output the CSV rows
	 *
	 * @access      public
	 * @since       1.5
	 * @return      void
	 */
	public function csv_rows_out() {
		$data = $this->get_data();

		$cols = $this->get_csv_cols();

		// Output each row
		foreach ( $data as $row ) {
			$i = 1;
			foreach ( $row as $col_id => $column ) {
				// Make sure the column is valid
				if ( array_key_exists( $col_id, $cols ) ) {
					echo '"' . $this->esc_field( $column ) . '"';
					echo $i == count( $cols ) + 1 ? '' : ',';
				}

				$i++;
			}
			echo "\r\n";
		}
	}

	/**
	 * Escape a string to be used in a CSV context
	 *
	 * @param string $field
	 *
	 * @access      public
	 * @since       2.6.4
	 * @return      string
	 */
	public function esc_field( $field ) {

		$field = addslashes( preg_replace( "/\"/","'", $field ) );

		return $field;
	}

	/**
	 * Perform the export
	 *
	 * @access      public
	 * @since       1.5
	 * @return      void
	 */
	public function export() {

		if( ! $this->can_export() )
			wp_die( __( 'You do not have permission to export data.', 'rcp' ), __( 'Error', 'rcp' ) );

		// Set headers
		$this->headers();

		// Output CSV columns (headers)
		$this->csv_cols_out();

		// Output CSV rows
		$this->csv_rows_out();

		exit;
	}
}