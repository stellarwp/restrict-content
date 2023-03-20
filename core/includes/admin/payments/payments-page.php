<?php
/**
 * Payments Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Payments Page
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Renders the Restrict > Payments page
 *
 * @since  1.0
 * @return void
 */
function rcp_payments_page() {

	include_once RCP_PLUGIN_DIR . 'core/includes/admin/payments/class-payments-table.php';

	$table_class = new \RCP\Admin\Payments_Table();
	$table_class->prepare_items();

	$rcp_payments  = new RCP_Payments();
	$status = $table_class->get_request_var( 'status' );
	do_action( 'stellarwp/telemetry/restrict-content-pro/optin' );
	do_action( 'stellarwp/telemetry/restrict-content/optin' );
	?>

	<div class="wrap">

		<?php
		if( isset( $_GET['view'] ) && 'new-payment' == $_GET['view'] ) :
			include( 'new-payment.php' );
		elseif( isset( $_GET['view'] ) && 'edit-payment' == $_GET['view'] ) :
			include( 'edit-payment.php' );
		else : ?>
		<h1>
			<?php _e( 'Payments', 'rcp' ); ?>
			<a href="<?php echo admin_url( '/admin.php?page=rcp-payments&view=new-payment' ); ?>" class="add-new-h2">
				<?php _e( 'Create Payment', 'rcp' ); ?>
			</a>
		</h1>

		<?php if ( 'pending' === $status ) : ?>
			<div class="notice notice-large notice-warning">
				<?php printf( __( 'Pending payments are converted to Complete when finalized. Read more about pending payments <a href="%s">here</a>.', 'rcp' ), 'http://docs.restrictcontentpro.com/article/1903-pending-payments' ); ?>
			</div>
		<?php endif; ?>

			<p class="total"><strong><?php _e( 'Total Earnings', 'rcp' ); ?>: <?php echo rcp_currency_filter( $rcp_payments->get_earnings() ); ?></strong></p>

		<?php do_action('rcp_payments_page_top'); ?>

		<form id="rcp-payments-filter" method="GET" action="<?php echo esc_url( add_query_arg( 'page', 'rcp-payments', admin_url( 'admin.php' ) ) ); ?>">
			<input type="hidden" name="page" value="rcp-payments"/>
			<?php
			$table_class->views();
			$table_class->search_box( __( 'Search payments', 'rcp' ), 'rcp-payments' );
			$table_class->display();
			?>
		</form>

		<?php do_action( 'rcp_payments_page_bottom' ); ?>
		<?php endif; ?>
	</div><!--end wrap-->
	<?php
}
