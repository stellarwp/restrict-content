<?php
/**
 * Members Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Members Page
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Render members table
 *
 * @return void
 */
function rcp_members_page() {

	do_action( 'stellarwp/telemetry/restrict-content-pro/optin' );
	do_action( 'stellarwp/telemetry/restrict-content/optin' );

	if ( ! empty( $_GET['view'] ) && 'edit' == $_GET['view'] && ! empty( $_GET['membership_id'] ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/admin/memberships/edit-membership.php';
	} elseif ( ! empty( $_GET['view'] ) && 'add' == $_GET['view'] ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/admin/memberships/add-membership.php';
	} elseif ( ! empty( $_GET['view'] ) && 'cancel-confirmation' == $_GET['view'] ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/admin/memberships/cancel-membership.php';
	} else {
		// List all memberships.
		rcp_memberships_list();
	}
	return;
}

/**
 * Display the list of memberships.
 *
 * @since 3.0
 * @return void
 */
function rcp_memberships_list() {

	include_once RCP_PLUGIN_DIR . 'core/includes/admin/memberships/class-memberships-table.php';

	$table_class = new \RCP\Admin\Memberships_Table();
	$table_class->prepare_items();

	?>
	<div class="wrap">
		<h1>
			<?php _e( 'Memberships', 'rcp' ); ?>
			<a href="<?php echo esc_url( rcp_get_memberships_admin_page( array( 'view' => 'add' ) ) ); ?>" class="page-title-action"><?php _e( 'Add New', 'rcp' ); ?></a>
		</h1>

		<?php do_action( 'rcp_members_above_table' ); ?>

		<form id="rcp-memberships-filter" method="GET" action="<?php echo esc_url( add_query_arg( 'page', 'rcp-members', admin_url( 'admin.php' ) ) ); ?>">
			<input type="hidden" name="page" value="rcp-members"/>
			<?php
			$table_class->views();
			$table_class->search_box( __( 'Search memberships', 'rcp' ), 'rcp-memberships' );
			$table_class->display();
			?>
		</form>

		<?php do_action( 'rcp_members_below_table' ); ?>
	</div>
	<?php

}
