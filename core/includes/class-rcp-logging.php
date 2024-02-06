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

/**
 * Debug Logging class
 *
 * @since 2.9
 */
class RCP_Logging {
	/**
	 * Legacy log filename.
	 *
	 * @since 3.5.39
	 *
	 * @var string
	 */
	private const LEGACY_LOG_FILENAME = 'rcp-debug.log';

	/**
	 * Default log directory.
	 *
	 * @since 3.5.39
	 *
	 * @var string
	 */
	private const DEFAULT_LOG_DIR = 'rcp' . DIRECTORY_SEPARATOR . 'debug';

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
		$defaults = array(
			'file' => $this->get_default_log_file_path(),
		);

		$args = wp_parse_args( $args, $defaults );

		$this->file = $args['file'];
	}

	/**
	 * Returns the default log file path.
	 *
	 * @since 3.5.39
	 *
	 * @return string
	 */
	private function get_default_log_file_path(): string {
		$upload_dir = wp_upload_dir();

		$option = RCP_Helper_Cast::to_string( get_option( 'rcp_debug_log_filename' ) );

		$filename = basename( $option, '.log' );
		if ( empty( $filename ) ) {
			$filename = uniqid() . '.log';
			update_option( 'rcp_debug_log_filename', $filename );
		} else {
			$filename .= '.log';
		}

		$log_dir = trailingslashit( $upload_dir['basedir'] ) . self::DEFAULT_LOG_DIR;
		rcp_create_protected_directory( $log_dir );

		return $log_dir . DIRECTORY_SEPARATOR . $filename;
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
		if ( ! file_exists( $this->file ) ) {
			file_put_contents( $this->file, $this->migrate_legacy_log_content() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			@chmod( $this->file, 0664 );
		}

		if ( ! $this->is_writable() ) {
			return;
		}

		$file  = $this->get_file();
		$file .= $message;
		@file_put_contents( $this->file, $file );
	}

	/**
	 * Returns the legacy log content and deletes the file.
	 *
	 * @since 3.5.39
	 *
	 * @return string
	 */
	private function migrate_legacy_log_content(): string {
		$upload_dir = wp_upload_dir();

		$legacy_log_file = trailingslashit( $upload_dir['basedir'] ) . self::LEGACY_LOG_FILENAME;

		if ( ! file_exists( $legacy_log_file ) ) {
			return '';
		}

		$content = file_get_contents( $legacy_log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		@unlink( $legacy_log_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- legacy file.

		return (string) $content;
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
