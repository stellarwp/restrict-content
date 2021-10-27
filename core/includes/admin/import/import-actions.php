<?php
/**
 * Import Actions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.1
 */

use RCP\Utils\Batch;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upload the CSV import file and create the batch processing job.
 *
 * @since 3.1
 * @return void
 */
function rcp_upload_csv_import_file_ajax() {

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: You do not have permission to perform this action.', 'rcp' )
		) );
	}

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	}

	if ( ! wp_verify_nonce( $_REQUEST['rcp_ajax_import_nonce'], 'rcp_ajax_import' ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: Nonce verification failed', 'rcp' )
		) );
	}

	if ( empty( $_POST['importer'] ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: Missing importer key.', 'rcp' ),
			'request' => $_REQUEST
		) );
	}

	$importer_details = rcp_get_csv_importer( $_POST['importer'] );

	if ( empty( $importer_details ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: No such registered importer.', 'rcp' ),
			'request' => $_REQUEST
		) );
	}

	if ( empty( $_FILES['import_file'] ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: Missing import file. Please provide an import file.', 'rcp' ),
			'request' => $_REQUEST
		) );
	}

	$accepted_mime_types = array(
		'text/csv',
		'text/comma-separated-values',
		'text/plain',
		'text/anytext',
		'text/*',
		'text/plain',
		'text/anytext',
		'text/*',
		'application/csv',
		'application/excel',
		'application/vnd.ms-excel',
		'application/vnd.msexcel',
	);

	$file_type_data = wp_check_filetype_and_ext( $_FILES['import_file']['tmp_name'], $_FILES['import_file']['name'] );
	$file_type      = ! empty( $file_type_data['type'] ) ? strtolower( $file_type_data['type'] ) : strtolower( $_FILES['import_file']['type'] );

	if ( empty( $file_type ) || ! in_array( $file_type, $accepted_mime_types ) ) {
		wp_send_json_error( array(
			'message' => sprintf( __( 'Error: The file you uploaded does not appear to be a CSV file. File type: %s', 'rcp' ), esc_html( $file_type ) ),
			'request' => $_REQUEST
		) );
	}

	if ( ! file_exists( $_FILES['import_file']['tmp_name'] ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: Something went wrong during the upload process, please try again.', 'rcp' ),
			'request' => $_REQUEST
		) );
	}

	// Let WordPress import the file. We will remove it after import is complete
	$import_file = wp_handle_upload( $_FILES['import_file'], array( 'test_form' => false ) );

	// Sanitize import settings.
	if ( ! empty( $_POST['import_settings'] ) && is_array( $_POST['import_settings'] ) ) {
		$settings = array_map( 'sanitize_text_field', $_POST['import_settings'] );
	} else {
		$settings = array();
	}

	if ( $import_file && empty( $import_file['error'] ) ) {

		/**
		 * Use this hook to include the job callback class file.
		 *
		 * @param string $importer_details ['callback'] Name of the class file.
		 */
		do_action( 'rcp_batch_processing_class_include', $importer_details['callback'] );

		switch ( $importer_details['key'] ) {
			case 'memberships' :
				$job_name    = __( 'CSV Memberships Import', 'rcp' );
				$description = __( 'Import memberships from file: %s', 'rcp' );
				break;

			case 'payments' :
				$job_name    = __( 'CSV Payments Import', 'rcp' );
				$description = __( 'Import payments from file: %s', 'rcp' );
				break;

			default :
				$job_name    = sprintf( __( 'CSV %s Import', 'rcp' ), $importer_details['name'] );
				$description = __( 'Import data from file: %s', 'rcp' );
				break;
		}

		// Ensure we get a unique job name.
		$job_check = Batch\get_jobs( array( 'name' => $job_name, 'queue' => 'rcp_csv_import' ) );

		if ( $job_check ) {
			$suffix = 2;

			do {
				$alt_name  = sprintf( '%s #%d', $job_name, $suffix );
				$job_check = Batch\get_jobs( array( 'name' => $alt_name, 'queue' => 'rcp_csv_import' ) );
				$suffix++;
			} while ( $job_check );

			$job_name = $alt_name;
		}

		$job_id = Batch\add_batch_job( array(
			'name'        => $job_name,
			'description' => sprintf( $description, basename( $import_file['file'] ) ),
			'callback'    => sanitize_text_field( $importer_details['callback'] ),
			'queue'       => 'rcp_csv_import',
			'data'        => array(
				'file_path' => sanitize_text_field( _wp_relative_upload_path( $import_file['file'] ) ),
				'settings'  => $settings
			)
		) );

		if ( is_wp_error( $job_id ) ) {
			wp_send_json_error( array(
				'message' => sprintf( __( 'Error: %s', 'rcp' ), $job_id->get_error_message() )
			) );
		} elseif ( empty( $job_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Error: Batch job creation failed.', 'rcp' )
			) );
		}

		$job = Batch\get_job( $job_id );

		if ( empty( $job ) || ! class_exists( $job->get_callback() ) ) {
			wp_send_json_error( array(
				'message' => __( 'Error: Unable to get batch job callback.', 'rcp' )
			) );
		}

		/**
		 * @var RCP_Batch_Callback_CSV_Import_Base $importer
		 */
		$importer = $job->get_callback_object();

		wp_send_json_success( array(
			'job_id'    => $job_id,
			'first_row' => $importer->get_first_row(),
			'columns'   => $importer->get_columns(),
			'nonce'     => wp_create_nonce( 'rcp_ajax_import' )
		) );

	} else {

		/**
		 * Error generated by _wp_handle_upload()
		 * @see _wp_handle_upload() in wp-admin/includes/file.php
		 */

		wp_send_json_error( array( 'message' => $import_file['error'] ) );
	}

	exit;

}
add_action( 'rcp_action_upload_import_file', 'rcp_upload_csv_import_file_ajax' );

/**
 * Process CSV import
 *
 * This handles saving the field mapping.
 *
 * @since 3.1
 * @return void
 */
function rcp_process_csv_import() {

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: You do not have permission to perform this action.', 'rcp' ),
			'request' => $_REQUEST
		) );
	}

	if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'rcp_ajax_import' ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: Nonce verification failed', 'rcp' ),
			'request' => $_REQUEST
		) );
	}

	if ( empty( $_REQUEST['job_id'] ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: Missing job ID.', 'rcp' ),
			'request' => $_REQUEST
		) );
	}

	$job = Batch\get_job( absint( $_REQUEST['job_id'] ) );

	if ( empty( $job ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: Unable to get batch job.', 'rcp' )
		) );
	}

	$importer = rcp_get_csv_importer_by_callback( $job->get_callback() );

	if ( empty( $importer ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: Unable to get importer information.', 'rcp' )
		) );
	}

	/**
	 * Use this hook to include the job callback class file.
	 *
	 * @param string $job_callback Name of the class file.
	 */
	do_action( 'rcp_batch_processing_class_include', $job->get_callback() );

	if ( ! class_exists( $job->get_callback() ) ) {
		wp_send_json_error( array(
			'message' => __( 'Error: Missing job callback.', 'rcp' )
		) );
	}

	// Map the fields to the database columns.
	parse_str( $_REQUEST['mapping'], $map );

	$job->add_data( array(
		'field_map' => array_map( 'sanitize_text_field', $map['rcp_import_field'] )
	) );

	$redirect_url = add_query_arg( array(
		'rcp-job-id'        => absint( $job->get_id() ),
		'rcp-job-autostart' => 1
	), admin_url( 'admin.php?page=rcp-tools&tab=batch' ) );

	wp_send_json_success( esc_url_raw( $redirect_url ) );

	exit;

}

add_action( 'wp_ajax_rcp_process_csv_import', 'rcp_process_csv_import' );

/**
 * Include batch CSV import class files.
 *
 * @param string $callback Import class name.
 *
 * @since 3.1
 * @return void
 */
function rcp_include_batch_import_files( $callback ) {

	$importer = rcp_get_csv_importer_by_callback( $callback );

	if ( empty( $importer ) ) {
		return;
	}

	// Include the main file.
	require_once RCP_PLUGIN_DIR . 'core/includes/batch/csv-imports/class-batch-csv-import-base.php';

	// Include the file specifically for this importer.
	if ( file_exists( $importer['callback_file'] ) ) {
		require_once $importer['callback_file'];
	}

}
add_action( 'rcp_batch_processing_class_include', 'rcp_include_batch_import_files' );

/**
 * Memberships Importer: add additional settings to the import UI.
 *
 * @param array $importer Importer details.
 *
 * @since 3.1
 * @return void
 */
function rcp_csv_importer_memberships_settings( $importer ) {
	?>
	<tr>
		<th>
			<label for="rcp-membership-level"><?php _e( 'Membership Level', 'rcp' ); ?></label>
		</th>
		<td>
			<select id="rcp-membership-level" name="import_settings[object_id]">
				<?php
				$membership_levels = rcp_get_membership_levels( array( 'number' => 999 ) );
				echo '<option value="" selected>' . __( '- Use CSV Column -', 'rcp' ) . '</option>';
				foreach ( $membership_levels as $level ) {
					echo '<option value="' . esc_attr( absint( $level->get_id() ) ) . '">' . esc_html( $level->get_name() ) . '</option>';
				}
				?>
			</select>
			<p class="description"><?php _e( 'Select the membership level to add users to. Membership levels can also be specified in the CSV file.', 'rcp' ); ?></p>
		</td>
	</tr>
	<tr>
		<th>
			<label for="rcp-status"><?php _e( 'Status', 'rcp' ); ?></label>
		</th>
		<td>
			<select id="rcp-status" name="import_settings[status]">
				<option value="" selected><?php _e( '- Use CSV Column -', 'rcp' ); ?></option>
				<option value="active"><?php _e( 'Active', 'rcp' ); ?></option>
				<option value="pending"><?php _e( 'Pending', 'rcp' ); ?></option>
				<option value="cancelled"><?php _e( 'Cancelled', 'rcp' ); ?></option>
				<option value="expired"><?php _e( 'Expired', 'rcp' ); ?></option>
			</select>
			<p class="description"><?php _e( 'Select the status to set for all imported memberships. Statuses can also be set in the CSV file.', 'rcp_csvui' ); ?></p>
		</td>
	</tr>
	<tr>
		<th>
			<label for="rcp-expiration"><?php _e( 'Expiration', 'rcp' ); ?></label>
		</th>
		<td>
			<input type="text" id="rcp-expiration" name="import_settings[expiration_date]" value="" class="rcp-datepicker"/>
			<p class="description"><?php _e( 'Select the expiration date for all memberships. Leave blank if specified in the CSV file. If an expiration date is not provided in either place, it will be automatically calculated based on the selected membership level.', 'rcp' ); ?></p>
		</td>
	</tr>
	<?php if ( rcp_multiple_memberships_enabled() ) : ?>
		<tr>
			<th>
				<label for="rcp-existing-customers"><?php _e( 'Existing Customers', 'rcp' ); ?></label>
			</th>
			<td>
				<select id="rcp-existing-customers" name="import_settings[existing_customers]">
					<option value="new" selected><?php _e( 'Add Additional Memberships', 'rcp' ); ?></option>
					<option value="update"><?php _e( 'Update Existing Memberships', 'rcp' ); ?></option>
				</select>
				<p class="description"><?php _e( 'If importing a membership for a customer that already exists, you can either insert an additional membership record, or update/change the existing membership record.', 'rcp' ); ?></p>
			</td>
		</tr>
	<?php endif; ?>
	<tr>
		<th>
			<label for="rcp-membership-import-disable-notification-emails"><?php _e( 'Disable Notification Emails', 'rcp' ); ?></label>
		</th>
		<td>
			<input type="checkbox" id="rcp-membership-import-disable-notification-emails" name="import_settings[disable_notification_emails]" value="1"/>
			<span class="description"><?php _e( 'Check on to disable customer and admin notification emails during the import process.', 'rcp' ); ?></span>
		</td>
	</tr>
	<tr>
		<th>
			<label for="rcp-send-set-password-emails"><?php _e( 'Send "Set Password" Emails', 'rcp' ); ?></label>
		</th>
		<td>
			<input type="checkbox" id="rcp-send-set-password-emails" name="import_settings[send_set_password_emails]" value="1"/>
			<span class="description"><?php _e( 'If checked, new accounts will be sent an email inviting them to set a password. Existing accounts will not receive one.', 'rcp' ); ?></span>
		</td>
	</tr>
	<?php
}

add_action( 'rcp_csv_importer_settings_memberships', 'rcp_csv_importer_memberships_settings' );
