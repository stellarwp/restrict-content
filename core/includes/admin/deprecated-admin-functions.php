<?php
/**
 * Deprecated Admin Functions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 * @since     3.4
 */

/**
 * Process member export
 *
 * @deprecated 3.4
 *
 * @return void
 */
function rcp_export_members() {
	if ( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'export-members' ) {

		_deprecated_function( __FUNCTION__, '3.4' );

		include RCP_PLUGIN_DIR . 'core/includes/deprecated/class-rcp-export.php';
		include RCP_PLUGIN_DIR . 'core/includes/deprecated/class-rcp-export-members.php';

		$export = new RCP_Members_Export;
		$export->export();
	}
}
//add_action( 'admin_init', 'rcp_export_members' );

/**
 * Process payment export
 *
 * @return void
 */
function rcp_export_payments() {
	if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'export-payments' ) {

		include RCP_PLUGIN_DIR . 'core/includes/class-rcp-export.php';
		include RCP_PLUGIN_DIR . 'core/includes/class-rcp-export-payments.php';

		$export = new RCP_Payments_Export;
		$export->export();
	}
}
//add_action( 'admin_init', 'rcp_export_payments' );
