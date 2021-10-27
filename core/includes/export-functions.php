<?php
/**
 * Export Functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Export Functions
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Get an array of the available CSV exporters and their settings.
 *
 * @since 3.4
 * @return array
 */
function rcp_get_csv_exporters() {

	$exporters = array(
		/**
		 * Memberships
		 */
		'memberships' => array(
			// Name used in titles and labels.
			'name'          => __( 'Memberships', 'rcp' ),
			// Unique identifier. Same as the array key above.
			'key'           => 'memberships',
			// Description printed on the export page.
			'description'   => __( 'Download membership data as a CSV file. This is useful for tasks such as importing batch users into MailChimp, or other systems.', 'rcp' ),
			// Batch processor callback class name.
			'callback'      => '\\RCP\\Batch\\CSV_Exports\\Memberships',
			// Path to the above class file.
			'callback_file' => RCP_PLUGIN_DIR . 'core/includes/batch/csv-exports/class-export-memberships.php',
		),
		/**
		 * Payments
		 */
		'payments'    => array(
			'name'          => __( 'Payments', 'rcp' ),
			'key'           => 'payments',
			'description'   => __( 'Download payment data as a CSV file. Use this file for your own record keeping or tracking.', 'rcp' ),
			'callback'      => '\\RCP\\Batch\\CSV_Exports\\Payments',
			'callback_file' => RCP_PLUGIN_DIR . 'core/includes/batch/csv-exports/class-export-payments.php',
		)
	);

	/**
	 * Filters the available CSV exporters. Use this filter to add support
	 * for a custom importer.
	 *
	 * @param array $exporters
	 *
	 * @since 3.4
	 */
	return apply_filters( 'rcp_csv_exporters', $exporters );

}

/**
 * Get details about a specific exporter by key.
 *
 * @param string $key
 *
 * @since 3.4
 * @return array|false Array of exporter details on success, false on failure.
 */
function rcp_get_csv_exporter( $key ) {

	$exporters = rcp_get_csv_exporters();

	if ( ! array_key_exists( $key, $exporters ) ) {
		return false;
	}

	return $exporters[ $key ];

}

/**
 * Get details about a specific exporter by callback class name.
 *
 * @param string $callback Batch processor callback class name.
 *
 * @since 3.4
 * @return array|false Array of exporter details on success, false on failure.
 */
function rcp_get_csv_exporter_by_callback( $callback ) {

	$exporters = rcp_get_csv_exporters();

	if ( empty( $exporters ) ) {
		return false;
	}

	foreach ( $exporters as $exporter ) {
		if ( $callback === $exporter['callback'] ) {
			return $exporter;
		}
	}

	return false;

}

/**
 * Determines whether or not the current user has export permissions.
 *
 * @since 3.4
 * @return bool
 */
function rcp_current_user_can_export() {
	return (bool) apply_filters( 'rcp_export_capability', current_user_can( 'rcp_export_data' ) );
}
