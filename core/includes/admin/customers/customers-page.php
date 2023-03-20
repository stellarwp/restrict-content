<?php
/**
 * Customers Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Customers Page
 * @copyright   Copyright (c) 2018, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

/**
 * Render customers table
 *
 * @return void
 */
function rcp_customers_page() {

	do_action( 'stellarwp/telemetry/restrict-content-pro/optin' );
	do_action( 'stellarwp/telemetry/restrict-content/optin' );

	if ( ! empty( $_GET['view'] ) && 'edit' == $_GET['view'] && ! empty( $_GET['customer_id'] ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/admin/customers/edit-customer.php';
	} elseif ( ! empty( $_GET['view'] ) && 'add' == $_GET['view'] ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/admin/customers/add-customer.php';
	} else {
		// List all customers.
		rcp_customers_list();
	}
	return;
}

/**
 * Display the list of customers.
 *
 * @since 3.0
 * @return void
 */
function rcp_customers_list() {

	include_once RCP_PLUGIN_DIR . 'core/includes/admin/customers/class-customers-table.php';

	$table_class = new \RCP\Admin\Customers_Table();
	$table_class->prepare_items();

	?>
	<div class="wrap">
		<h1>
			<?php _e( 'Customers', 'rcp' ); ?>
			<a href="<?php echo esc_url( rcp_get_customers_admin_page( array( 'view' => 'add' ) ) ); ?>" class="page-title-action"><?php _e( 'Add New', 'rcp' ); ?></a>
		</h1>

		<form id="rcp-customers-filter" method="GET" action="<?php echo esc_url( rcp_get_customers_admin_page() ); ?>">
			<input type="hidden" name="page" value="rcp-customers"/>
			<?php
			$table_class->views();
			$table_class->search_box( __( 'Search customers', 'rcp' ), 'rcp-customers' );
			$table_class->display();
			?>
		</form>
	</div>
	<?php

}
