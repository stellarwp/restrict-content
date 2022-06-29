<?php
/**
 * Export Actions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 * @since     3.4
 */

namespace RCP\Admin\Export;

use RCP\Utils\Batch;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add an export job to the queue
 *
 * @since 3.4
 */
function add_export_job() {

	if ( empty( $_POST['rcp_batch_export_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_batch_export_nonce'], 'rcp_batch_export' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! rcp_current_user_can_export() ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_POST['exporter'] ) ) {
		wp_die( __( 'Missing exporter.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$exporter = rcp_get_csv_exporter( $_POST['exporter'] );

	if ( empty( $exporter ) ) {
		wp_die( __( 'This is not a registered exporter.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	// Sanitize export settings.
	if ( ! empty( $_POST['export_settings'] ) && is_array( $_POST['export_settings'] ) ) {
		$settings = array_map( 'sanitize_text_field', $_POST['export_settings'] );
	} else {
		$settings = array();
	}

	/**
	 * Use this hook to include the job callback class file.
	 *
	 * @param string $exporter ['callback'] Name of the class.
	 */
	do_action( 'rcp_batch_processing_class_include', $exporter['callback'] );

	$job_name = sprintf( __( 'CSV %s Export - %s', 'rcp' ), $exporter['name'], date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );

	$job_id = Batch\add_batch_job( array(
		'name'     => $job_name,
		'callback' => ( $exporter['callback'] ),
		'queue'    => 'rcp_csv_export',
		'data'     => array(
			'filepath' => tempnam( get_temp_dir(), 'rcp-' ),
			'settings' => $settings
		)
	) );

	if ( is_wp_error( $job_id ) ) {
		wp_die( $job_id->get_error_message(), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
	}

	$job = Batch\get_job( $job_id );

	if ( ! $job instanceof Batch\Job ) {
		wp_die( __( 'Unable to retrieve job.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 500 ) );
	}

	$redirect_url = add_query_arg( array(
		'rcp-job-id'        => absint( $job->get_id() ),
		'rcp-job-autostart' => 1
	), admin_url( 'admin.php?page=rcp-tools&tab=batch' ) );

	wp_safe_redirect( esc_url_raw( $redirect_url ) );
	exit;

}

add_action( 'rcp_action_add_export_job', __NAMESPACE__ . '\add_export_job' );

/**
 * Include batch CSV export class files.
 *
 * @param string $callback Export class name.
 *
 * @since 3.4
 * @return void
 */
function include_batch_export_files( $callback ) {

	$exporter = rcp_get_csv_exporter_by_callback( $callback );

	if ( empty( $exporter ) ) {
		return;
	}

	// Include the main file.
	require_once RCP_PLUGIN_DIR . 'core/includes/batch/csv-exports/class-batch-csv-export-base.php';

	// Include the file specifically for this importer.
	if ( file_exists( $exporter['callback_file'] ) ) {
		require_once $exporter['callback_file'];
	}

}

add_action( 'rcp_batch_processing_class_include', __NAMESPACE__ . '\include_batch_export_files' );

/**
 * Add extra settings to the Memberships Export
 *
 * @param array $exporter
 *
 * @since 3.4
 */
function memberships_settings( $exporter ) {

	$levels = rcp_get_membership_levels( array( 'number' => 999 ) );
	?>
	<table class="form-table">
		<tbody>
		<?php if ( $levels ) : ?>
			<tr>
				<th>
					<label for="rcp-membership-level"><?php _e( 'Memberships', 'rcp' ); ?></label>
				</th>
				<td>
					<select id="rcp-membership-level" name="export_settings[level_id]">
						<option value=""><?php _e( 'All Levels', 'rcp' ); ?></option>
						<?php foreach ( $levels as $level ) : ?>
							<option value="<?php echo esc_attr( $level->get_id() ); ?>"><?php echo esc_html( $level->get_name() ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php _e( 'Choose a membership level to only export memberships from that level.', 'rcp' ); ?></p>
				</td>
			</tr>
		<?php endif; ?>
		<tr>
			<th>
				<label for="rcp-membership-status"><?php _e( 'Membership Status', 'rcp' ); ?></label>
			</th>
			<td>
				<select id="rcp-membership-status" name="export_settings[status]">
					<option value="all"><?php _e( 'All', 'rcp' ); ?></option>
					<option value="active"><?php _e( 'Active', 'rcp' ); ?></option>
					<option value="pending"><?php _e( 'Pending', 'rcp' ); ?></option>
					<option value="expired"><?php _e( 'Expired', 'rcp' ); ?></option>
					<option value="cancelled"><?php _e( 'Cancelled', 'rcp' ); ?></option>
				</select>
				<p class="description"><?php _e( 'Choose a status to only export memberships with that status.', 'rcp' ); ?></p>
			</td>
		</tr>
		<tr>
			<th>
				<label for="rcp-membership-number"><?php _e( 'Maximum Number', 'rcp' ); ?></label>
			</th>
			<td>
				<input type="number" id="rcp-membership-number" name="export_settings[number]" class="small-text">
				<p class="description"><?php _e( 'Maximum number of memberships to export. Leave blank to export all.', 'rcp' ); ?></p>
			</td>
		</tr>
		</tbody>
	</table>
	<?php

}

add_action( 'rcp_csv_exporter_settings_memberships', __NAMESPACE__ . '\memberships_settings' );

/**
 * Add extra settings to the Payments Export
 *
 * @param array $exporter
 *
 * @since 3.4
 */
function payments_settings( $exporter ) {

	$current = date( 'Y' );
	$year    = $current;
	$end     = $current - 5;
	?>
	<table class="form-table">
		<tbody>
		<tr>
			<th>
				<?php _e( 'Payment Dates', 'rcp' ); ?>
			</th>
			<td>
				<label for="rcp-year" class="screen-reader-text"><?php _e( 'Select a year to only export payments made in that year', 'rcp' ); ?></label>
				<select id="rcp-year" name="export_settings[year]">
					<option value="0"><?php _e( 'All years', 'rcp' ); ?></option>
					<?php while ( $year >= $end ) : ?>
						<option value="<?php echo esc_attr( $year ); ?>"><?php echo esc_html( $year ); ?></option>
						<?php $year--; endwhile; ?>
				</select>

				<label for="rcp-month" class="screen-reader-text"><?php _e( 'Select a month to only export payments made in that month', 'rcp' ); ?></label>
				<select id="rcp-month" name="export_settings[month]">
					<option value="0"><?php _e( 'All months', 'rcp' ); ?></option>
					<?php for ( $i = 1; $i <= 12; $i++ ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( rcp_get_month_name( $i ) ); ?></option>
					<?php endfor; ?>
				</select>
			</td>
		</tr>
		</tbody>
	</table>
	<?php

}

add_action( 'rcp_csv_exporter_settings_payments', __NAMESPACE__ . '\payments_settings' );

/**
 * Downloads the export file and deletes the temp file from the system.
 *
 * @since 3.4
 */
function download_export_file() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp_download_export_file' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! rcp_current_user_can_export() ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( empty( $_GET['export_id'] ) ) {
		wp_die( __( 'Missing export ID.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$job = Batch\get_job( absint( $_GET['export_id'] ) );

	if ( ! $job instanceof Batch\Job || 'rcp_csv_export' !== $job->get_queue() ) {
		wp_die( __( 'Invalid export.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$data           = $job->get_data();
	$file_path      = ! empty( $data['filepath'] ) ? $data['filepath'] : false;
	$export_details = rcp_get_csv_exporter_by_callback( $job->get_callback() );

	if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
		wp_die( __( 'Export file not found.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	ignore_user_abort( true );

	if ( ! rcp_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		set_time_limit( 0 );
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="rcp-' . $export_details['key'] . '-export-' . date( 'Y-m-d' ) . '.csv"' );

	/**
	 * We need to append a BOM to the export so that Microsoft Excel knows
	 * that the file is in Unicode.
	 */
	echo "\xEF\xBB\xBF";

	readfile( $file_path );

	@unlink( $file_path );

	die();

}

add_action( 'rcp_action_download_export_file', __NAMESPACE__ . '\download_export_file' );

/**
 * Redirects the old separate export page to the tools page. Export now lives there in an Export tab.
 *
 * @since 3.4
 */
function redirect_old_export_page() {
	if ( ! is_admin() || empty( $_GET['page'] ) || 'rcp-export' !== $_GET['page'] ) {
		return;
	}

	wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=rcp-tools&tab=export' ) ) );
	exit;
}

add_action( 'init', __NAMESPACE__ . '\redirect_old_export_page', 1 );
