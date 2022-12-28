<?php
/**
 * Export Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2020, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Render the export page
 *
 * @deprecated 3.4
 *
 * @return void
 */
function rcp_export_page() {
	_deprecated_function( __FUNCTION__, '3.4' );

	global $rcp_options, $rcp_db_name, $wpdb;
	$current_page = admin_url( '/admin.php?page=rcp-export' );
	?>
	<div class="wrap">
		<h1><?php _e( 'Export', 'rcp' ); ?></h1>

		<?php do_action( 'rcp_export_page_top' ); ?>

		<h2><?php _e( 'Memberships Export', 'rcp' ); ?></h2>
		<p><?php _e( 'Download membership data as a CSV file. This is useful for tasks such as importing batch users into MailChimp, or other systems.', 'rcp' ); ?></p>
		<form id="rcp_export" action="<?php echo esc_attr( $current_page ); ?>" method="post">
			<p>
				<select name="rcp-subscription" id="rcp-subscription">
					<option value="0"><?php _e( 'All', 'rcp' ); ?></option>
					<?php
					$levels = rcp_get_membership_levels( array( 'number' => 999 ) );
					if ( $levels ) :
						foreach ( $levels as $key => $level ) :
							?>
						<option value="<?php echo absint( $level->get_id() ); ?>"><?php echo esc_html( $level->get_name() ); ?></option>
							<?php
						endforeach;
					endif;
					?>
				</select>
				<label for="rcp-subscription"><?php _e( 'Choose the subscription to export memberships from', 'rcp' ); ?></label><br/>
				<select name="rcp-status" id="rcp-status">
					<option value="active"><?php _e( 'Active', 'rcp' ); ?></option>
					<option value="pending"><?php _e( 'Pending', 'rcp' ); ?></option>
					<option value="expired"><?php _e( 'Expired', 'rcp' ); ?></option>
					<option value="cancelled"><?php _e( 'Cancelled', 'rcp' ); ?></option>
				</select>
				<label for="rcp-status"><?php _e( 'Choose the status to export', 'rcp' ); ?></label><br/>
				<input type="number" id="rcp-number" name="rcp-number" class="small-text" value="500" />
				<label for="rcp-number"><?php _e( 'Maximum number of memberships to export', 'rcp' ); ?><br/>
				<input type="number" id="rcp-offset" name="rcp-offset" class="small-text" value="0" />
				<label for="rcp-offset"><?php _e( 'The number of memberships to skip', 'rcp' ); ?>
			</p>
			<p><?php _e( 'If you need to export a large number of memberships, export them in batches using the max and offset options', 'rcp' ); ?></p>
			<input type="hidden" name="rcp-action" value="export-members"/>
			<input type="submit" class="button-secondary" value="<?php _e( 'Download Memberships CSV', 'rcp' ); ?>"/>
		</form>

		<!-- payments export -->
		<h2><?php _e( 'Payments Export', 'rcp' ); ?></h2>
		<p><?php _e( 'Download payment data as a CSV file. Use this file for your own record keeping or tracking.', 'rcp' ); ?></p>
		<form id="rcp_export" action="<?php echo esc_url( $current_page ); ?>" method="post">
			<p>
				<select name="rcp-year" id="rcp-year">
					<option value="0"><?php _e( 'All years', 'rcp' ); ?>
					<?php
					$current = date( 'Y' );
					$year    = $current;
					$end     = $current - 5;
					while ( $year >= $end ) :
						// phpcs:disable
						?>
						<option value="<?php echo $year; ?>"><?php echo $year; ?></option>
						<?php
						$year--;
					endwhile;
						// phpcs:enable
					?>
				</select>
				<select name="rcp-month" id="rcp-month">
					<option value="0"><?php _e( 'All months', 'rcp' ); ?>
					<?php for ( $i = 1; $i <= 12; $i++ ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_attr( rcp_get_month_name( $i ) ); ?></option>
					<?php endfor; ?>
				</select>
			</p>
			<p>
				<input type="submit" class="button-secondary" value="<?php _e( 'Download Payments CSV', 'rcp' ); ?>"/>
				<input type="hidden" name="rcp-action" value="export-payments"/>
			</p>
		</form>

		<?php do_action( 'rcp_export_page_bottom' ); ?>

	</div><!--end wrap-->
	<?php
}
