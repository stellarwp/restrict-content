<?php

/**
 * Debug Logging class
 *
 * @package    restrict-content-pro
 * @subpackage Classes/Logging
 * @copyright  Copyright (c) 2017, Restrict Content Pro
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.9
 */
class RCP_Logging {

	/**
	 * Full path to the file
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $file = '';

	/**
	 * Get things started
	 *
	 * @param array $args Arguments to override the defaults.
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function __construct( $args = array() ) {

		$upload_dir = wp_upload_dir();

		$defaults = array(
			'file' => trailingslashit( $upload_dir['basedir'] ) . 'rcp-debug.log'
		);

		$args = wp_parse_args( $args, $defaults );

		$this->file = $args['file'];

	}

	/**
	 * Checks if the file is writable
	 *
	 * @access public
	 * @since  2.0
	 * @return bool
	 */
	public function is_writable() {
		return is_writable( $this->file );
	}

	/**
	 * Retrieve the log data
	 *
	 * @access public
	 * @since  2.9
	 * @return string
	 */
	public function get_log() {
		return $this->get_file();
	}

	/**
	 * Log message to file
	 *
	 * @param string $message Message to log.
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function log( $message = '' ) {
		$message = current_time( 'Y-n-d H:i:s' ) . ' - ' . $message . "\r\n";
		$this->write_to_log( $message );
	}

	/**
	 * Retrieve the file
	 *
	 * @access protected
	 * @since  2.9
	 * @return string
	 */
	protected function get_file() {

		$file = '';

		if ( @file_exists( $this->file ) ) {

			$file = @file_get_contents( $this->file );

		}

		return $file;
	}

	/**
	 * Write the log message
	 *
	 * If the file doesn't exist yet, it's also created.
	 *
	 * @param string $message Message to log.
	 *
	 * @access protected
	 * @since  2.9
	 * @return void
	 */
	protected function write_to_log( $message = '' ) {

		if ( ! @file_exists( $this->file ) ) {
			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );
		}

		if ( ! $this->is_writable() ) {
			return;
		}

		$file = $this->get_file();
		$file .= $message;
		@file_put_contents( $this->file, $file );

	}

	/**
	 * Clear the log
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function clear_log() {

		if ( ! $this->is_writable() ) {
			return;
		}

		@unlink( $this->file );

	}

}