<?php
/**
 * Import Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get an array of the available CSV importers and their settings.
 *
 * @since 3.1
 * @return array
 */
function rcp_get_csv_importers() {

	$importers = array(
		/**
		 * Memberships
		 */
		'memberships' => array(
			// Name used in titles and labels.
			'name'          => __( 'Memberships', 'rcp' ),
			// Unique identifier. Same as the array key above.
			'key'           => 'memberships',
			// Description printed on the import page.
			'description'   => sprintf( __( 'Use this tool to import user memberships into Restrict Content Pro. See <a href="%s">this article</a> for further instructions and a sample CSV file.', 'rcp' ), 'https://docs.restrictcontentpro.com/article/1579-import-members-from-a-csv-file' ),
			// Batch processor callback class name.
			'callback'      => 'RCP_Batch_Callback_Import_Memberships',
			// Path to the above class file.
			'callback_file' => RCP_PLUGIN_DIR . 'core/includes/batch/csv-imports/class-import-memberships.php',
			/*
			 * Array of supported columns. The key is what you're looking for in the batch
			 * processor callback. The value is the expected name of the column header. The
			 * value is just used to auto-select a column; it doesn't actually affect functionality.
			 */
			'columns'       => array(
				'user_email'              => __( 'User Email', 'rcp' ),
				'first_name'              => __( 'First Name', 'rcp' ),
				'last_name'               => __( 'Last Name', 'rcp' ),
				'user_login'              => __( 'User Login', 'rcp' ),
				'user_password'           => __( 'User Password', 'rcp' ),
				'membership_level_name'   => __( 'Membership Level Name', 'rcp' ),
				'status'                  => __( 'Status', 'rcp' ),
				'created_date'            => __( 'Created Date', 'rcp' ),
				'expiration_date'         => __( 'Expiration Date', 'rcp' ),
				'auto_renew'              => __( 'Auto Renew', 'rcp' ),
				'times_billed'            => __( 'Times Billed', 'rcp' ),
				'gateway'                 => __( 'Gateway', 'rcp' ),
				'gateway_customer_id'     => __( 'Gateway Customer ID', 'rcp' ),
				'gateway_subscription_id' => __( 'Gateway Subscription ID', 'rcp' ),
				'subscription_key'        => __( 'Subscription Key', 'rcp' )
			)
		),
		/**
		 * Payments
		 */
		'payments'    => array(
			'name'          => __( 'Payments', 'rcp' ),
			'key'           => 'payments',
			'description'   => sprintf( __( 'Use this tool to import user payments into Restrict Content Pro. See <a href="%s">this article</a> for further instructions and a sample CSV file.', 'rcp' ), 'https://docs.restrictcontentpro.com/article/2265-importing-payments' ),
			'callback'      => 'RCP_Batch_Callback_Import_Payments',
			'callback_file' => RCP_PLUGIN_DIR . 'core/includes/batch/csv-imports/class-import-payments.php',
			'columns'       => array(
				'status'           => __( 'Status', 'rcp' ),
				'subscription'     => __( 'Membership Level Name', 'rcp' ),
				'object_id'        => __( 'Membership Level ID', 'rcp' ),
				'amount'           => __( 'Total Amount', 'rcp' ),
				'subtotal'         => __( 'Subtotal', 'rcp' ),
				'credits'          => __( 'Credits', 'rcp' ),
				'fees'             => __( 'Fees', 'rcp' ),
				'discount_amount'  => __( 'Discount Amount', 'rcp' ),
				'discount_code'    => __( 'Discount Code', 'rcp' ),
				'user_login'       => __( 'User Login', 'rcp' ),
				'user_email'       => __( 'User Email', 'rcp' ),
				'membership_id'    => __( 'Membership ID', 'rcp' ),
				'gateway'          => __( 'Gateway', 'rcp' ),
				'transaction_id'   => __( 'Transaction ID', 'rcp' ),
				'transaction_type' => __( 'Transaction Type', 'rcp' ),
				'date'             => __( 'Date', 'rcp' ),
			)
		)
	);

	/**
	 * Filters the available CSV importers. Use this filter to add support
	 * for a custom importer.
	 *
	 * @param array $importers
	 *
	 * @since 3.1
	 */
	return apply_filters( 'rcp_csv_importers', $importers );

}

/**
 * Get details about a specific importer by key.
 *
 * @param string $key
 *
 * @since 3.1
 * @return array|false Array of importer details on success, false on failure.
 */
function rcp_get_csv_importer( $key ) {

	$importers = rcp_get_csv_importers();

	if ( ! array_key_exists( $key, $importers ) ) {
		return false;
	}

	return $importers[$key];

}

/**
 * Get details about a specific importer by callback class name.
 *
 * @param string $callback Batch processor callback class name.
 *
 * @return array|false Array of importer details on success, false on failure.
 */
function rcp_get_csv_importer_by_callback( $callback ) {

	$importers = rcp_get_csv_importers();

	if ( empty( $importers ) ) {
		return false;
	}

	foreach ( $importers as $importer ) {
		if ( $callback === $importer['callback'] ) {
			return $importer;
		}
	}

	return false;

}
