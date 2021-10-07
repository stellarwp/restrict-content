<?php
/**
 * Add Membership
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $_GET['view'] ) || 'add' != $_GET['view'] ) {
	wp_die( __( 'Something went wrong.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
}
?>
<div class="wrap">
	<h1><?php _e( 'Add Membership', 'rcp' ); ?></h1>

	<div id="rcp-item-card-wrapper">
		<div class="rcp-info-wrapper rcp-item-section rcp-membership-card-wrapper">
			<form id="rcp-add-membership-info" method="POST">
				<div class="rcp-item-info">
					<table class="widefat striped">
						<tbody>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-customer-email"><?php _e( 'Customer:', 'rcp' ); ?></label>
							</th>
							<td>
								<?php if ( ! empty( $_GET['customer_id'] ) ) :
									$customer = rcp_get_customer( absint( $_GET['customer_id'] ) );

									if ( ! empty( $customer ) ) {
										$user = get_userdata( $customer->get_user_id() );
										?>
										<a href="<?php echo esc_url( rcp_get_customers_admin_page( array( 'customer_id' => $customer->get_id(), 'view' => 'edit' ) ) ); ?>"><?php echo esc_html( $user->display_name ); ?></a>
										<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer->get_id() ); ?>"/>
										<?php
									}
									?>

								<?php else : ?>
									<input type="text" id="rcp-customer-email" class="rcp-user-search" data-return-field="user_email" name="user_email" placeholder="<?php esc_attr_e( 'Email', 'rcp' ); ?>" value="<?php echo ! empty( $_GET['email'] ) ? esc_attr( rawurldecode( $_GET['email'] ) ) : ''; ?>"/>
									<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Enter the email of an existing customer. If a customer doesn\'t exist with this email, a new one will be created.', 'rcp' ); ?>"></span>
									<div id="rcp_user_search_results"></div>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-membership-level"><?php _e( 'Membership Level:', 'rcp' ); ?></label>
							</th>
							<td>
								<?php
								$levels = rcp_get_membership_levels( array( 'number' => 999 ) );

								if ( $levels ) {
									echo '<select id="rcp-membership-level" name="object_id">';
									foreach ( $levels as $level ) {
										echo '<option value="' . esc_attr( $level->get_id() ) . '">' . esc_html( $level->get_name() ) . '</option>';
									}
									echo '</select>';
								} else {
									_e( 'No levels found', 'rcp' );
								}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="rcp-status"><?php _e( 'Membership Status:', 'rcp' ); ?></label>
							</th>
							<td>
								<select name="status" id="rcp-status">
									<?php
									$statuses = array( 'active', 'expired', 'cancelled', 'pending' );
									foreach ( $statuses as $status ) :
										echo '<option value="' . esc_attr( $status ) . '">' . rcp_get_status_label( $status ) . '</option>';
									endforeach;
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label><?php _e( 'Billing Cycle:', 'rcp' ); ?></label>
							</th>
							<td>
								<label for="rcp-initial-amount" class="screen-reader-text"><?php _e( 'Initial Amount', 'rcp' ); ?></label>
								<?php echo rcp_get_currency_symbol(); ?>
								<input type="text" id="rcp-initial-amount" name="initial_amount" placeholder="0.00" style="width: 80px;" value="" readonly/> <!-- @todo make this editable -->
								<span id="rcp-billing-cycle-recurring">
									&nbsp;<?php _e( 'initially, then', 'rcp' ); ?>&nbsp;
									<label for="rcp-recurring-amount" class="screen-reader-text"><?php _e( 'Recurring Amount', 'rcp' ); ?></label>
									<?php echo rcp_get_currency_symbol(); ?>
									<input type="text" id="rcp-recurring-amount" name="recurring_amount" placeholder="0.00" style="width: 80px;" value="" readonly/> <!-- @todo make this editable -->
									&nbsp;<?php _e( 'for renewals', 'rcp' ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-times-billed"><?php _e( 'Times Billed:', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="number" id="rcp-times-billed" name="times_billed" min="0" step="1" value="0"/>
								<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Number of times this membership has been billed for so far.', 'rcp' ); ?>"></span>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-membership-created"><?php _e( 'Date Created:', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" id="rcp-membership-created" name="created_date" class="rcp-datepicker rcp-membership-created" value="<?php echo esc_attr( date( 'Y-m-d', current_time( 'timestamp' ) ) ); ?>"/>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-membership-expiration">
									<?php _e( 'Expiration Date:', 'rcp' ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="rcp-membership-expiration" name="expiration_date" class="rcp-datepicker rcp-membership-expiration" value=""/>
								<input type="checkbox" id="rcp-membership-expiration-none" name="expiration_date_none" value="1"/>
								<label for="rcp-membership-expiration-none"><?php _e( 'Never expires', 'rcp' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-recurring"><?php _e( 'Auto Renew:', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" name="auto_renew" id="rcp-recurring" value="1"/>
								<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Note: Checking this does not automatically create a recurring subscription. The customer should already have one created in the payment gateway.', 'rcp' ); ?>"></span>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-payment-method"><?php _e( 'Payment Method:', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp-payment-method" name="gateway">
									<?php
									$gateways = rcp_get_payment_gateways();

									foreach ( $gateways as $gateway_key => $gateway ) {
										?>
										<option value="<?php echo esc_attr( $gateway_key ); ?>" <?php selected( $gateway_key, 'manual' ); ?>><?php echo esc_html( $gateway['admin_label'] ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-membership-gateway-customer-id"><?php _e( 'Gateway Customer ID:', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" id="rcp-membership-gateway-customer-id" name="gateway_customer_id" value=""/>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-membership-gateway-subscription-id"><?php _e( 'Gateway Subscription ID:', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" id="rcp-membership-gateway-subscription-id" name="gateway_subscription_id" value=""/>
							</td>
						</tr>
						<?php
						/**
						 * Add additional fields to the Add Membership form.
						 *
						 * @since 3.0.5
						 */
						do_action( 'rcp_add_membership_after' );
						?>
						</tbody>
					</table>
				</div>

				<div id="rcp-item-edit-actions" class="edit-item">
					<input type="hidden" name="rcp-action" value="add_membership"/>
					<?php wp_nonce_field( 'rcp_add_membership', 'rcp_add_membership_nonce' ); ?>
					<input type="submit" class="button button-primary" value="<?php _e( 'Add Membership', 'rcp' ); ?>"/>
				</div>
			</form>
		</div>
	</div>
</div>
