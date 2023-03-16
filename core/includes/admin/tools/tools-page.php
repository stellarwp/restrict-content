<?php
/**
 * Tools Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Tools
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Displays the Tools page
 *
 * @since 2.5
 * @return void
 */
function rcp_tools_page() {
	if( ! current_user_can( 'rcp_manage_settings' ) ) {
		return;
	}

	do_action( 'stellarwp/telemetry/restrict-content-pro/optin' );
	do_action( 'stellarwp/telemetry/restrict-content/optin' );

	$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'system_info';
    ?>

	<div class="wrap">
		<h1><?php
			if( defined('IS_PRO') && IS_PRO ) {
				_e( 'Restrict Content Pro Tools', 'rcp' );
			}
			else {
				_e( 'Restrict Content Tools', 'rcp' );
			}
		?></h1>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach( rcp_get_tools_tabs() as $tab_id => $tab_name ) {
				$tab_url = add_query_arg( array(
					'tab' => $tab_id
				) );

				$tab_url = remove_query_arg( array(
					'rcp_message'
				), $tab_url );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';
				echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab' . $active . '">' . esc_html( $tab_name ) . '</a>';
			}
			?>
		</h2>

		<?php do_action( 'rcp_tools_tab_subnav_' . $active_tab ); ?>

		<div class="metabox-holder">
			<?php do_action( 'rcp_tools_tab_' . $active_tab ); ?>
		</div>
	</div>
<?php
}

/**
 * Retrieve tools tabs
 *
 * @since 2.9
 * @return array
 */
function rcp_get_tools_tabs() {

	$tabs = array(
		'system_info' => __( 'System Info', 'rcp' ),
		'debug'       => __( 'Debugging', 'rcp' )
	);

	if ( current_user_can( 'rcp_manage_settings' ) ) {
		$tabs['import'] = __( 'Import', 'rcp' );
	}

	if ( rcp_current_user_can_export() ) {
		$tabs['export'] = __( 'Export', 'rcp' );
	}

	if ( ! empty( $_GET['tab'] ) && 'batch' === $_GET['tab'] ) {
		$tabs['batch'] = __( 'Batch Processing', 'rcp' );
	}

	return apply_filters( 'rcp_tools_tabs', $tabs );

}

/**
 * Display system information tab
 *
 * @since 2.9
 * @return void
 */
function rcp_tools_display_system_info() {

	include RCP_PLUGIN_DIR . 'core/includes/admin/tools/system-info.php';
	?>
	<form action="<?php echo esc_url( admin_url( 'admin.php?page=rcp-tools' ) ); ?>" method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus(); this.select()" id="rcp-system-info-textarea" name="rcp-sysinfo" title="To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac)."><?php echo rcp_tools_system_info_report(); ?></textarea>
		<p class="submit">
			<input type="hidden" name="rcp-action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'rcp-download-sysinfo', false ); ?>
		</p>
	</form>
	<?php

}
add_action( 'rcp_tools_tab_system_info', 'rcp_tools_display_system_info' );

/**
 * Listens for system info download requests and delivers the file
 *
 * @since 2.5
 * @return void
 */
function rcp_tools_sysinfo_download() {

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		return;
	}

	if ( ! isset( $_POST['rcp-download-sysinfo'] ) ) {
		return;
	}

	nocache_headers();

	header( 'Content-Type: text/plain' );
	header( 'Content-Disposition: attachment; filename="rcp-system-info.txt"' );

	echo wp_strip_all_tags( $_POST['rcp-sysinfo'] );
	exit;
}
add_action( 'admin_init', 'rcp_tools_sysinfo_download' );

/**
 * Display debug log
 *
 * @since 2.9
 * @return void
 */
function rcp_tools_display_debug() {

	$logs = new RCP_Logging();
	?>
	<div class="postbox">
		<h3><?php _e( 'Debug Log', 'rcp' ); ?></h3>
		<div class="inside">
			<form id="rcp-debug-log" method="post">
				<p><label for="rcp-debug-log-contents"><?php _e( 'Any Restrict Content Pro errors that occur will be logged to this file.', 'rcp' ); ?></label></p>
				<textarea id="rcp-debug-log-contents" name="rcp-debug-log-contents" class="large-text" rows="15"><?php echo esc_textarea( $logs->get_log() ); ?></textarea>
				<p class="submit">
					<input type="hidden" name="rcp-action" value="submit_debug_log">
					<?php
					wp_nonce_field( 'rcp_submit_debug_log', 'rcp_debug_log_nonce' );
					submit_button( __( 'Download Debug Log', 'rcp' ), 'primary', 'rcp_download_debug_log', false );
					submit_button( __( 'Clear Log', 'rcp' ), 'secondary', 'rcp_clear_debug_log', false );
					?>
				</p>
			</form>
		</div>
	</div>
	<?php

}
add_action( 'rcp_tools_tab_debug', 'rcp_tools_display_debug' );

/**
 * Handles submit actions for the debug log
 *
 * @since 2.9
 * @return void
 */
function rcp_submit_debug_log() {

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		return;
	}

	if ( ! isset( $_POST['rcp_debug_log_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_debug_log_nonce'], 'rcp_submit_debug_log' ) ) {
		return;
	}

	if ( isset( $_POST['rcp_download_debug_log'] ) ) {

		// Download debug log
		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="rcp-debug-log.txt"' );

		echo wp_strip_all_tags( $_REQUEST['rcp-debug-log-contents'] );
		exit;

	} elseif ( isset( $_POST['rcp_clear_debug_log'] ) ) {

		// Clear debug log.
		$logs = new RCP_Logging();
		$logs->clear_log();

		wp_safe_redirect( admin_url( 'admin.php?page=rcp-tools&tab=debug') );
		exit;

	}

}
add_action( 'admin_init', 'rcp_submit_debug_log' );

/**
 * Displays the batch processing tab on the Tools page.
 *
 * @since 3.0
 */
function rcp_batch_processing_page() {

	$job_id     = ! empty( $_GET['rcp-job-id'] ) ? absint( $_GET['rcp-job-id'] ) : 0;
	$queue_name = ! empty( $_GET['rcp-queue'] ) ? sanitize_key( $_GET['rcp-queue'] ) : 'rcp_core';
	$autostart  = ! empty( $_GET['rcp-job-autostart'] );
	$jobs       = array();

	if ( ! empty( $job_id ) ) {
		$job = \RCP\Utils\Batch\get_job( $job_id );

		if ( ! empty( $job ) ) {
			$jobs = array( $job );
		}
	} elseif( ! empty( $queue_name ) ) {
		$jobs = \RCP\Utils\Batch\get_jobs( array(
			'queue'  => $queue_name,
			'status' => 'incomplete'
		) );
	}
	?>

	<div class="wrap">

		<?php

		if( empty( $jobs ) ) {
			echo '<p>' . __( 'A valid job queue was not provided.', 'rcp' ) . '</p></div>';
			return;
		}

		/**
		 * @var \RCP\Utils\Batch\Job $job
		 */
		foreach( $jobs as $key => $job ) {
			if( $job->is_completed() ) {
				echo '<p>' . sprintf( __( '%s has already been completed.', 'rcp' ), $job->get_name() ) . '</p></div>';
				continue;
			} ?>
			<table id="rcp-batch-processing-job-<?php echo esc_attr( $job->get_id() ); ?>" class="wp-list-table widefat fixed posts rcp-batch-processing-job-table">
				<thead>
				<tr>
					<th><?php echo ! empty( $job ) ? $job->get_name() : ''; ?></th>
					<th><?php _e( 'Progress', 'rcp' ); ?></th>
					<th><?php _e( 'Actions', 'rcp' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td>
						<?php echo esc_html( $job->get_description() ); ?> <br/>
						<?php printf( __( 'WP-CLI command: %s', 'rcp' ), '<code>wp rcp batch --id=' . $job->get_id() . '</code>' ); ?>
					</td>
					<td>
						<span class="rcp-batch-processing-job-progress-bar"><span style="width: <?php echo esc_attr( $job->get_percent_complete() ); ?>%;"></span></span>
						<span class="rcp-batch-processing-job-progress-text description"><?php printf( __( '%s%% complete', 'rcp' ), '<span class="rcp-batch-processing-job-percent-complete">' . $job->get_percent_complete() . '</span>' ); ?></span>
					</td>
					<td>
						<form class="rcp-batch-form">
							<?php if ( 1 === count( $jobs ) && $autostart ) : ?>
								<input type="hidden" id="rcp-job-autostart" value="1" />
							<?php endif; ?>
							<input type="hidden" name="rcp-job-step" class="rcp-batch-processing-job-step" value="<?php echo esc_attr( $job->get_step() ); ?>" />
							<input type="hidden" name="rcp-job-id" class="rcp-batch-processing-job-id" value="<?php echo esc_attr( $job->get_id() ); ?>" />
							<input type="submit" value="<?php echo $job->get_percent_complete() > 0 ? esc_attr( 'Continue Processing', 'rcp' ) : esc_attr( 'Start Processing', 'rcp' ); ?>" class="button-primary"/>
							<span class="spinner"></span>
							<span class="rcp-batch-processing-message"></span>
						</form>
					</td>
				</tr>
				<tr id="rcp-batch-processing-errors-job-<?php echo esc_attr( $job->get_id() ); ?>" class="rcp-batch-processing-errors" style="display: none;">
					<td colspan="3"></td>
				</tr>
				</tbody>
			</table>
			<?php
		}
		?>

	</div>
	<?php
}
add_action( 'rcp_tools_tab_batch', 'rcp_batch_processing_page' );

/**
 * Display importer subnavigation. This allows you to switch between importers.
 *
 * @since 3.1
 * @return void
 */
function rcp_tools_display_import_subnav() {

	$importers = rcp_get_csv_importers();

	if ( empty( $importers ) ) {
		return;
	}

	// If we only have one importer, don't bother showing the subnav.
	if ( 1 === count( $importers ) ) {
		return;
	}

	$current = ( isset( $_GET['rcp-csv-importer'] ) && array_key_exists( $_GET['rcp-csv-importer'], $importers ) ) ? urldecode( $_GET['rcp-csv-importer'] ) : '';

	if ( empty( $current ) ) {
		$first_importer = $importers;
		reset( $first_importer );
		$current = key( $first_importer );
	}
	?>
	<ul class="subsubsub rcp-sub-nav">
		<li>
			<?php foreach ( $importers as $importer_key => $importer_details ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'rcp-csv-importer', urlencode( $importer_key ), admin_url( 'admin.php?page=rcp-tools&tab=import' ) ) ); ?>"<?php echo ( $importer_key === $current ) ? ' class="current"' : ''; ?>><?php echo esc_html( $importer_details['name'] ); ?></a>
			<?php endforeach; ?>
		</li>
	</ul>
	<?php

}

add_action( 'rcp_tools_tab_subnav_import', 'rcp_tools_display_import_subnav' );

/**
 * Display import tab
 *
 * @since 3.1
 * @return void
 */
function rcp_tools_display_import() {

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		return;
	}

	$importers = rcp_get_csv_importers();

	$current = ( isset( $_GET['rcp-csv-importer'] ) && array_key_exists( $_GET['rcp-csv-importer'], $importers ) ) ? urldecode( $_GET['rcp-csv-importer'] ) : '';

	if ( empty( $current ) ) {
		// If current one is not specified via query arg, default to the first one in the list.
		$first_importer = $importers;
		reset( $first_importer );
		$current = key( $first_importer );
	}

	$importer = rcp_get_csv_importer( $current );
	?>
	<div class="postbox">
		<h3><?php printf( __( 'Import %s', 'rcp' ), esc_html( $importer['name'] ) ); ?></h3>
		<div class="inside">
			<?php if ( ! empty( $importer['description'] ) ) : ?>
				<p><?php echo wp_kses_post( $importer['description'] ); ?></p>
			<?php endif; ?>
			<form id="rcp-import-memberships" class="rcp-import-form" action="<?php echo esc_url( add_query_arg( 'rcp-action', 'upload_import_file', admin_url() ) ); ?>" method="POST" enctype="multipart/form-data">

				<table class="form-table rcp-import-file-wrap">
					<tbody>
					<tr class="rcp-import-file">
						<th>
							<label for="rcp-<?php echo sanitize_html_class( $importer['key'] ); ?>-import-file"><?php _e( 'CSV File', 'rcp' ); ?></label>
						</th>
						<td>
							<p>
								<input id="rcp-<?php echo sanitize_html_class( $importer['key'] ); ?>-import-file" name="import_file" type="file"/>
							</p>
						</td>
					</tr>
					<?php
					/**
					 * Use this action hook to insert additional importer settings. All input names
					 * should be an array with the key `import_settings`. Example:
					 *
					 * `import_settings[object_id]`
					 * `import_settings[status]`
					 */
					do_action( 'rcp_csv_importer_settings_' . $importer['key'], $importer );
					?>
					</tbody>
					<tfoot>
					<tr>
						<td colspan="2">
							<p id="rcp-import-csv-errors" style="display:none"></p>

							<p id="rcp-import-csv-button-wrap" class="submit">
								<input type="submit" value="<?php _e( 'Upload CSV', 'rcp' ); ?>" class="button-secondary"/>
								<span class="spinner"></span>
							</p>
						</td>
					</tr>
					</tfoot>
				</table>

				<div id="rcp-import-<?php echo sanitize_html_class( $importer['key'] ); ?>-options" class="rcp-import-options" style="display:none;">

					<p>
						<?php printf( __( 'Each column from your CSV file needs to be mapped to its corresponding Restrict Content Pro field. Select the column that should be mapped to each field below. Any columns not needed can be ignored.', 'rcp' ) ); ?>
					</p>

					<table class="widefat striped" width="100%" cellpadding="0" cellspacing="0">
						<thead>
						<tr>
							<th><strong><?php _e( 'RCP Field', 'rcp' ); ?></strong></th>
							<th><strong><?php _e( 'CSV Column', 'rcp' ); ?></strong></th>
							<th><strong><?php _e( 'Data Preview', 'rcp' ); ?></strong></th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ( $importer['columns'] as $column_key => $column_value ) : ?>
							<tr>
								<td><?php echo esc_html( $column_value ); ?></td>
								<td>
									<select name="rcp_import_field[<?php echo sanitize_html_class( $column_key ); ?>]" class="rcp-import-csv-column" data-field="<?php echo esc_attr( $column_value ); ?>">
										<option value=""><?php _e( '- Ignore this field -', 'rcp' ); ?></option>
									</select>
								</td>
								<td class="rcp-import-preview-field"><?php _e( '- select field to preview data -', 'rcp' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<p class="submit">
						<button class="rcp-import-proceed button-primary"><?php _e( 'Process Import', 'rcp' ); ?></button>
						<span class="spinner"></span>
					</p>

				</div>

				<?php wp_nonce_field( 'rcp_ajax_import', 'rcp_ajax_import_nonce' ); ?>
				<input type="hidden" name="importer" value="<?php echo esc_attr( $importer['key'] ); ?>" />
				<input type="hidden" name="rcp-action" value="upload_import_file" />
			</form>
		</div>
	</div>
	<?php

}

add_action( 'rcp_tools_tab_import', 'rcp_tools_display_import' );

/**
 * Display exporter subnavigation. This allows you to switch between exporters.
 *
 * @since 3.4
 * @return void
 */
function rcp_tools_display_export_subnav() {

	$exporters = rcp_get_csv_exporters();

	if ( empty( $exporters ) ) {
		return;
	}

	// If we only have one exporter, don't bother showing the subnav.
	if ( 1 === count( $exporters ) ) {
		return;
	}

	$current = ( isset( $_GET['rcp-csv-exporter'] ) && array_key_exists( $_GET['rcp-csv-exporter'], $exporters ) ) ? urldecode( $_GET['rcp-csv-exporter'] ) : '';

	if ( empty( $current ) ) {
		$first_exporter = $exporters;
		reset( $first_exporter );
		$current = key( $first_exporter );
	}
	?>
	<ul class="subsubsub rcp-sub-nav">
		<li>
			<?php foreach ( $exporters as $exporter_key => $exporter_details ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'rcp-csv-exporter', urlencode( $exporter_key ), admin_url( 'admin.php?page=rcp-tools&tab=export' ) ) ); ?>"<?php echo ( $exporter_key === $current ) ? ' class="current"' : ''; ?>><?php echo esc_html( $exporter_details['name'] ); ?></a>
			<?php endforeach; ?>
		</li>
	</ul>
	<?php

}

add_action( 'rcp_tools_tab_subnav_export', 'rcp_tools_display_export_subnav' );

/**
 * Display export tab
 *
 * @since 3.4
 * @return void
 */
function rcp_tools_display_export() {

	if ( ! rcp_current_user_can_export() ) {
		return;
	}

	$exporters = rcp_get_csv_exporters();

	$current = ( isset( $_GET['rcp-csv-exporter'] ) && array_key_exists( $_GET['rcp-csv-exporter'], $exporters ) ) ? urldecode( $_GET['rcp-csv-exporter'] ) : '';

	if ( empty( $current ) ) {
		// If current one is not specified via query arg, default to the first one in the list.
		$first_exporter = $exporters;
		reset( $first_exporter );
		$current = key( $first_exporter );
	}

	$exporter = rcp_get_csv_exporter( $current );
	?>
	<div class="postbox">
		<h3><?php printf( __( 'Export %s', 'rcp' ), esc_html( $exporter['name'] ) ); ?></h3>
		<div class="inside">
			<?php if ( ! empty( $exporter['description'] ) ) : ?>
				<p><?php echo wp_kses_post( $exporter['description'] ); ?></p>
			<?php endif; ?>
			<form id="rcp-export-<?php echo esc_attr( sanitize_html_class( $exporter['key'] ) ); ?>" class="rcp-export-form" action="" method="POST">

				<?php
				/**
				 * Use this action hook to insert additional exporter settings. All input names
				 * should be an array with the key `export_settings`. Example:
				 *
				 * `export_settings[object_id]`
				 * `export_settings[status]`
				 *
				 * @param array $exporter Exporter settings.
				 *
				 * @since 3.4
				 */
				do_action( 'rcp_csv_exporter_settings_' . $exporter['key'], $exporter );

				wp_nonce_field( 'rcp_batch_export', 'rcp_batch_export_nonce' );
				?>
				<input type="hidden" name="exporter" value="<?php echo esc_attr( $exporter['key'] ); ?>"/>
				<input type="hidden" name="rcp-action" value="add_export_job"/>

				<p class="submit">
					<button class="rcp-export-proceed button-primary"><?php _e( 'Process Export', 'rcp' ); ?></button>
					<span class="spinner"></span>
				</p>

			</form>
		</div>
	</div>
	<?php

}

add_action( 'rcp_tools_tab_export', 'rcp_tools_display_export' );
