<?php
/**
 * Edit Membership
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

use RCP\Membership_Level;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $_GET['membership_id'] ) || ! is_numeric( $_GET['membership_id'] ) ) {
	wp_die( __( 'Something went wrong.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
}

$membership_id = $_GET['membership_id'];
$membership    = rcp_get_membership( $membership_id );

if ( empty( $membership ) ) {
	wp_die( __( 'Something went wrong.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
}

// Prevent editing disabled memberships.
if ( $membership->is_disabled() ) {
	wp_die( __( 'Invalid membership.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
}

$customer         = $membership->get_customer();
$user             = $customer instanceof RCP_Customer ? get_userdata( $customer->get_user_id() ) : false;
$membership_level = rcp_get_membership_level( $membership->get_object_id() );
$created_date     = date( 'Y-m-d H:i:s', strtotime( $membership->get_created_date( false ), current_time( 'timestamp' ) ) );
$expiration_date  = date( 'Y-m-d', strtotime( $membership->get_expiration_date( false ), current_time( 'timestamp' ) ) );

// Action URLs.
$cancel_url = wp_nonce_url( rcp_get_memberships_admin_page( array(
	'membership_id' => $membership->get_id(),
	'rcp-action'    => 'cancel_membership'
) ), 'cancel_membership' );
$expire_url = wp_nonce_url( rcp_get_memberships_admin_page( array(
	'membership_id' => $membership->get_id(),
	'rcp-action'    => 'expire_membership'
) ), 'expire_membership' );

// If this is a payment plan then override the cancellation URL to take them to a second confirmation screen.
if ( $membership->has_payment_plan() ) {
	$cancel_url = rcp_get_memberships_admin_page( array(
		'membership_id' => $membership->get_id(),
		'view'          => 'cancel-confirmation',
	) );
}

// Payments
$payments = $membership->get_payments( array( 'number' => 5 ) );
?>
<div class="wrap">
	<h1><?php _e( 'Membership Details', 'rcp' ); ?></h1>

	<div id="rcp-item-card-wrapper">
		<div class="rcp-info-wrapper rcp-item-section rcp-membership-card-wrapper">
			<form id="rcp-edit-membership-info" method="POST">
				<div class="rcp-item-info">
					<table class="widefat striped">
						<tbody>
						<tr>
							<th scope="row" class="row-title">
								<label for="tablecell"><?php _e( 'ID:', 'rcp' ); ?></label>
							</th>
							<td>
								<?php echo $membership->get_id(); ?>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="tablecell"><?php _e( 'Customer:', 'rcp' ); ?></label>
							</th>
							<td>
								<?php if ( $customer instanceof RCP_Customer && $user instanceof WP_User ) : ?>
									<a href="<?php echo esc_url( rcp_get_customers_admin_page( array( 'customer_id' => $customer->get_id(), 'view' => 'edit' ) ) ); ?>"><?php echo ! empty( $user->display_name ) ? esc_html( $user->display_name ) : esc_html( $user->user_login ); ?></a>
								<?php else : ?>
									<?php _e( '(Unknown)', 'rcp' ); ?>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-membership-level"><?php _e( 'Membership Level:', 'rcp' ); ?></label>
							</th>
							<td>
								<span class="rcp-current-membership-level"><?php echo $membership->get_membership_level_name(); ?></span>
								<select name="object_id" id="rcp-membership-level" class="hidden">
									<?php foreach ( rcp_get_membership_levels( array( 'number' => 999 ) ) as $level ) : ?>
										<option value="<?php echo esc_attr( $level->get_id() ); ?>" <?php selected( $level->get_id(), $membership->get_object_id() ); ?>><?php echo esc_html( $level->get_name() ); ?></option>
									<?php endforeach; ?>
								</select>
								<input type="submit" name="rcp_change_membership_level" class="button hidden" id="rcp-change-membership-level-button" title="<?php echo $membership->is_recurring() ? esc_attr__( 'Warning: The subscription will be cancelled at the payment gateway.', 'rcp' ) : ''; ?>" value="<?php esc_attr_e( 'Change Level', 'rcp' ); ?>">
								<span>&nbsp;&ndash;&nbsp;</span>
								<a href="#" id="rcp-edit-membership-level"><?php _e( 'Edit', 'rcp' ); ?></a>
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
										echo '<option value="' . esc_attr( $status ) . '"' . selected( $status, $membership->get_status(), false ) . '>' . rcp_get_status_label( $status ) . '</option>';
									endforeach;
									?>
								</select>
								<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Active memberships have access to restricted content. Members with a status of Cancelled may continue to access restricted content until the expiration date on their account is reached. When a user reaches his or her expiration date, their status is updated to Expired.', 'rcp' ); ?>"></span>
								<span id="rcp-membership-status-action-buttons">
									<?php if ( 'cancelled' != $membership->get_status() ) :
										$title_text = $membership->can_cancel() ? __( 'Cancel membership and recurring billing. The customer will retain access until they reach the expiration date.', 'rcp' ) : __( 'Membership cannot be cancelled as it does not have a recurring subscription.', 'rcp' );
										?>
										<a class="button rcp_cancel" id="rcp-cancel-membership-button" href="<?php echo esc_url( $cancel_url ) ?>" title="<?php echo esc_attr( $title_text ); ?>" <?php echo ! $membership->can_cancel() ? ' disabled="disabled"' : ''; ?>><?php _e( 'Cancel', 'rcp' ); ?></a>
									<?php endif; ?>
									<?php if ( $membership->is_active() ) : ?>
										<a class="button" id="rcp-expire-membership-button" href="<?php echo esc_url( $expire_url ); ?>" title="<?php esc_attr_e( 'Revoke the customer\'s access immediately', 'rcp' ); ?>"><?php _e( 'Expire', 'rcp' ); ?></a>
									<?php endif; ?>
								</span>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label><?php _e( 'Billing Cycle:', 'rcp' ); ?></label>
							</th>
							<td>
								<?php if ( $membership->can_change_gateway_price() ) : ?>
									<label for="rcp-initial-amount" class="screen-reader-text"><?php _e( 'Initial Amount', 'rcp' ); ?></label>
									<?php echo rcp_get_currency_symbol(); ?>
									<input type="text" id="rcp-initial-amount" name="initial_amount" placeholder="0.00" style="width: 80px;" value="<?php echo esc_attr( $membership->get_initial_amount() ); ?>" readonly/>
									<span id="rcp-billing-cycle-recurring">
									&nbsp;<?php _e( 'initially, then', 'rcp' ); ?>&nbsp;
									<label for="rcp-recurring-amount" class="screen-reader-text"><?php _e( 'Recurring Amount', 'rcp' ); ?></label>
									<?php echo rcp_get_currency_symbol(); ?>
									<input type="text" id="rcp-recurring-amount" name="recurring_amount" placeholder="0.00" style="width: 80px;" value="<?php echo esc_attr( $membership->get_recurring_amount() ); ?>"/>
									<?php
									$duration_unit = lcfirst( rcp_filter_duration_unit( $membership_level->duration_unit, $membership_level->duration ) );
									printf( _n( 'every %2$s', '%d %s', $membership_level->duration, 'rcp' ), $membership_level->duration, $duration_unit );
									?>
								</span>
								<?php else :
									echo $membership->get_formatted_billing_cycle();
								endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label><?php _e( 'Times Billed:', 'rcp' ); ?></label>
							</th>
							<td>
								<?php
								if ( $membership_level instanceof Membership_Level ) {
									if ( 0 == $membership->get_maximum_renewals() && ! $membership_level->is_lifetime() && ! $membership_level->is_free() ) {
										printf( __( '%d / Until Cancelled', 'rcp' ), $membership->get_times_billed() );
									} else {
										$renewals = $membership_level->is_free() ? 1 : $membership->get_maximum_renewals() + 1;

										printf( __( '%d / %d', 'rcp' ), $membership->get_times_billed(), $renewals );
									}
								} else {
									_e( 'Unknown (membership level not found)', 'rcp' );
								}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-membership-created"><?php _e( 'Date Created:', 'rcp' ); ?></label>
							</th>
							<td>
								<span class="rcp-membership-created"><?php echo $membership->get_created_date(); ?></span>
								<input type="text" id="rcp-membership-created" name="created_date" class="rcp-datepicker rcp-membership-created hidden" value="<?php echo esc_attr( $created_date ); ?>"/>
								<span>&nbsp;&ndash;&nbsp;</span>
								<a href="#" id="rcp-edit-membership-created"><?php _e( 'Edit', 'rcp' ); ?></a>
							</td>
						</tr>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-membership-expiration">
									<?php echo $membership->is_trialing() ? __( 'Trialling Until:', 'rcp' ) : __( 'Expiration Date:', 'rcp' ); ?>
								</label>
							</th>
							<td>
								<span class="rcp-membership-expiration"><?php echo ( 'none' == $membership->get_expiration_date() ) ? __( 'Never Expires', 'rcp' ) : $membership->get_expiration_date(); ?></span>
								<input type="text" id="rcp-membership-expiration" name="expiration_date" class="rcp-datepicker rcp-membership-expiration hidden" value="<?php echo esc_attr( $expiration_date ); ?>"/>
								<span class="rcp-membership-expiration-none-wrap hidden">
									<input type="checkbox" id="rcp-membership-expiration-none" name="expiration_date_none" value="1" <?php checked( 'none' == $membership->get_expiration_date() ); ?> />
									<label for="rcp-membership-expiration-none"><?php _e( 'Never expires', 'rcp' ); ?></label>
								</span>
								<span>&nbsp;&ndash;&nbsp;</span>
								<a href="#" id="rcp-edit-membership-expiration"><?php _e( 'Edit', 'rcp' ); ?></a>
							</td>
						</tr>
						<?php if ( 'cancelled' == $membership->get_status() ) : ?>
							<tr>
								<th scope="row" class="row-title">
									<label for="rcp-membership-cancellation-date"><?php _e( 'Cancellation Date:', 'rcp' ); ?></label>
								</th>
								<td>
									<?php
									$cancellation_date = $membership->get_cancellation_date();

									if ( empty( $cancellation_date ) ) {
										_e( 'Unknown', 'rcp' );
									} else {
										echo $cancellation_date;
									}
									?>
								</td>
							</tr>
						<?php endif; ?>
						<tr>
							<th scope="row" class="row-title">
								<label for="rcp-recurring"><?php _e( 'Auto Renew:', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" name="auto_renew" id="rcp-recurring" value="1" <?php checked( $membership->is_recurring() ); ?>/>
								<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( 'If checked, this member has a recurring subscription. Only customers with recurring memberships will be given the option to cancel their membership on their subscription details page.', 'rcp' ); ?>"></span>
							</td>
						</tr>
						<?php if ( 'free' != $membership->get_gateway() ) : ?>
							<tr>
								<th scope="row" class="row-title">
									<label for="rcp-payment-method"><?php _e( 'Payment Method:', 'rcp' ); ?></label>
								</th>
								<td>
									<?php
									$gateways     = rcp_get_payment_gateways();
									$gateway_used = $membership->get_gateway();
									?>
									<select id="rcp-payment-method" name="gateway">
										<?php
										foreach ( $gateways as $gateway_key => $gateway ) {
											?>
											<option value="<?php echo esc_attr( $gateway_key ); ?>" <?php selected( $gateway_key, $gateway_used ); ?>><?php echo esc_html( $gateway['admin_label'] ); ?></option>
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
									<span class="rcp-membership-gateway-customer-id">
										<?php
										$gateway_customer_id = $membership->get_gateway_customer_id();

										if ( ! empty( $gateway_customer_id ) ) : ?>
											<a href="<?php echo esc_url( rcp_get_gateway_customer_id_url( $membership->get_gateway(), $gateway_customer_id ) ); ?>" target="_blank">
											<?php echo esc_html( $gateway_customer_id ) ?>
										</a>
										<?php endif; ?>
									</span>
									<input type="text" id="rcp-membership-gateway-customer-id" name="gateway_customer_id" class="hidden" value="<?php echo esc_attr( $membership->get_gateway_customer_id() ); ?>"/>
									<span>&nbsp;&ndash;&nbsp;</span>
									<a href="#" id="rcp-edit-membership-gateway-customer-id"><?php _e( 'Edit', 'rcp' ); ?></a>
								</td>
							</tr>
							<tr>
								<th scope="row" class="row-title">
									<label for="rcp-membership-gateway-subscription-id"><?php _e( 'Gateway Subscription ID:', 'rcp' ); ?></label>
								</th>
								<td>
									<span class="rcp-membership-gateway-subscription-id">
										<?php
										$gateway_subscription_id = $membership->get_gateway_subscription_id();

										if ( ! empty( $gateway_subscription_id ) ) : ?>
											<a href="<?php echo esc_url( rcp_get_gateway_subscription_id_url( $membership->get_gateway(), $gateway_subscription_id ) ); ?>" target="_blank">
											<?php echo esc_html( $gateway_subscription_id ) ?>
										</a>
										<?php endif; ?>
									</span>
									<input type="text" id="rcp-membership-gateway-subscription-id" name="gateway_subscription_id" class="hidden" value="<?php echo esc_attr( $membership->get_gateway_subscription_id() ); ?>"/>
									<span>&nbsp;&ndash;&nbsp;</span>
									<a href="#" id="rcp-edit-membership-gateway-subscription-id"><?php _e( 'Edit', 'rcp' ); ?></a>
								</td>
							</tr>
						<?php endif; ?>
						<tr>
							<th scope="row" class="row-title">
								<?php _e( 'Subscription Key:', 'rcp' ); ?>
							</th>
							<td>
								<?php echo esc_html( $membership->get_subscription_key() ); ?>
							</td>
						</tr>
						<?php if ( $membership->was_upgrade() ) : ?>
							<tr>
								<th scope="row" class="row-title">
									<label><?php _e( 'Changed From:', 'rcp' ); ?></label>
								</th>
								<td>
									<?php
									$previous_membership = rcp_get_membership( $membership->get_upgraded_from() );

									if ( ! empty( $previous_membership ) ) {
										echo $previous_membership->get_membership_level_name();
									}
									?>
								</td>
							</tr>
						<?php endif; ?>
						<?php
						/**
						 * Used for adding additional content to the end of the membership table.
						 *
						 * @param RCP_Membership $membership
						 *
						 * @since 3.0
						 */
						do_action( 'rcp_edit_membership_after', $membership );
						?>
						</tbody>
					</table>
				</div>

				<div id="rcp-membership-notices">
					<?php if ( ! $membership->can_change_next_bill_date() ) : ?>
						<div class="notice notice-info inline hidden" id="rcp-membership-expiration-update-notice">
							<p><?php _e( 'Changing the expiration date will not affect when renewal payments are processed.', 'rcp' ); ?></p>
						</div>
					<?php else : ?>
						<div class="notice notice-success inline hidden" id="rcp-membership-expiration-update-notice">
							<p><?php _e( 'The renewal date in the payment gateway will be updated to match the new expiration date.', 'rcp' ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( $membership->can_change_gateway_price() ) : ?>
						<div class="notice notice-success inline hidden" id="rcp-membership-recurring-price-update-notice">
							<p><?php _e( 'The renewal price in the payment gateway will be updated.', 'rcp' ); ?></p>
						</div>
					<?php endif; ?>
					<div class="notice notice-info inline hidden" id="rcp-membership-recurring-update-notice">
						<?php if ( $membership->can_toggle_auto_renew() ) : ?>
							<p id="rcp-membership-recurring-enable-auto-renew">
								<strong><?php _e( 'Enabling Auto Renew:', 'rcp' ); ?></strong> <?php _e( 'A new subscription will be created at the payment gateway.', 'rcp' ); ?>
								<?php
								if ( $membership->get_expiration_time() >= current_time( 'timestamp' ) ) {
									printf( __( 'The customer will be automatically billed %s on: %s.', 'rcp' ), $membership->get_recurring_amount( true ), $membership->get_expiration_date( true ) );
								} else {
									printf( __( 'The customer will be charged %s immediately.', 'rcp' ), $membership->get_recurring_amount( true ) );
								}
								?>
							</p>

						<p id="rcp-membership-recurring-disable-auto-renew">
							<strong><?php _e( 'Disabling Auto Renew:', 'rcp' ); ?></strong> <?php _e( 'The subscription at the payment gateway will be cancelled, but the membership status will not change.', 'rcp' ); ?>
						</p>
						<?php else : ?>
							<p><?php _e( 'Changing the recurring indicator will not set up or remove a subscription with the gateway. This checkbox is for updating RCP records only.', 'rcp' ); ?></p>
						<?php endif; ?>
					</div>
					<div class="notice notice-warning inline hidden" id="rcp-membership-gateway-subscription-id-update-notice">
						<p><?php _e( 'Changing the gateway subscription ID can result in renewals not being processed. Do this with caution.', 'rcp' ); ?></p>
					</div>
				</div>
				<div id="rcp-item-edit-actions" class="edit-item">
					<input type="hidden" name="rcp-action" value="edit_membership"/>
					<input type="hidden" name="membership_id" value="<?php echo esc_attr( $membership->get_id() ); ?>"/>
					<?php wp_nonce_field( 'rcp_edit_membership', 'rcp_edit_membership_nonce' ); ?>
					<input type="submit" name="rcp_update_membership" id="rcp_update_membership" class="button button-primary" value="<?php _e( 'Update Membership', 'rcp' ); ?>"/>
					&nbsp;<input type="submit" name="rcp_delete_membership" class="rcp-delete-membership button" value="<?php _e( 'Delete Membership', 'rcp' ); ?>"/>
				</div>
			</form>
		</div>

		<?php do_action( 'rcp_membership_details_before_payments', $membership ); ?>

		<div id="rcp-membership-payments-wrapper" class="rcp-item-section">
			<h3><?php _e( 'Payments:', 'rcp' ); ?></h3>
			<table class="wp-list-table widefat striped payments">
				<thead>
				<tr>
					<th class="column-primary"><?php _e( 'ID', 'rcp' ); ?></th>
					<th><?php _e( 'Date', 'rcp' ); ?></th>
					<th><?php _e( 'Amount', 'rcp' ); ?></th>
					<th><?php _e( 'Status', 'rcp' ); ?></th>
					<th><?php _e( 'Transaction ID', 'rcp' ); ?></th>
					<th><?php _e( 'Invoice', 'rcp' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( ! empty( $payments ) ) : ?>
					<?php foreach ( $payments as $payment ) : ?>
						<tr>
							<td class="column-primary" data-colname="<?php esc_attr_e( 'ID', 'rcp' ); ?>">
								<a href="<?php echo esc_url( add_query_arg( 'payment_id', urlencode( $payment->id ), admin_url( 'admin.php?page=rcp-payments&view=edit-payment' ) ) ); ?>"><?php echo $payment->id; ?></a>
								<button type="button" class="toggle-row">
									<span class="screen-reader-text"><?php _e( 'Show more details', 'rcp' ); ?></span>
								</button>
							</td>
							<td data-colname="<?php esc_attr_e( 'Date', 'rcp' ); ?>"><?php echo $payment->date; ?></td>
							<td data-colname="<?php esc_attr_e( 'Amount', 'rcp' ); ?>"><?php echo rcp_currency_filter( $payment->amount ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Status', 'rcp' ); ?>"><?php echo rcp_get_status_label( $payment->status ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Transaction ID', 'rcp' ); ?>"><?php echo rcp_get_merchant_transaction_id_link( $payment ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Invoice', 'rcp' ); ?>">
								<a href="<?php echo esc_url( rcp_get_invoice_url( $payment->id ) ); ?>"><?php _e( 'View Invoice', 'rcp' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ( 5 === count( $payments ) ) : ?>
						<tr>
							<td colspan="6">
								<a href="<?php echo esc_url( add_query_arg( 'membership_id', urlencode( $membership->get_id() ), admin_url( 'admin.php?page=rcp-payments' ) ) ); ?>"><?php _e( 'View all payments', 'rcp' ); ?></a>
							</td>
						</tr>
					<?php endif; ?>
				<?php else : ?>
					<tr>
						<td colspan="6"><?php _e( 'No payments found.', 'rcp' ); ?></td>
					</tr>
				<?php endif; ?>
				</tbody>
				<?php if ( current_user_can( 'rcp_manage_payments' ) ) : ?>
					<tfoot>
					<tr class="alternate">
						<td colspan="6">
							<form id="rcp-membership-add-renewal" method="POST">
								<p>
									<?php _e( 'Use this form to manually record a renewal payment.', 'rcp' ); ?>
									<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Note: this does not initiate a charge in your merchant processor. This should only be used for recording a missed payment or one that was manually collected.', 'rcp' ); ?>"></span>
								</p>
								<p>
									<label for="rcp-payment-amount" class="rcp-payment-field-label"><?php _e( 'Amount:', 'rcp' ); ?></label>
									<input type="text" id="rcp-payment-amount" class="regular-text" style="width: 100px; padding: 3px;" name="amount" value="<?php echo esc_attr( $membership->get_recurring_amount() ); ?>" placeholder="0.00">
								</p>
								<p>
									<label for="rcp-payment-transaction-id" class="rcp-payment-field-label"><?php _e( 'Transaction ID:', 'rcp' ); ?></label>
									<input type="text" id="rcp-payment-transaction-id" class="regular-text" style="width: 100px; padding: 3px;" name="transaction_id" value="" placeholder="">
								</p>
								<p>
									<label for="rcp-payment-status" class="rcp-payment-field-label"><?php _e( 'Status', 'rcp' ); ?></label>
									<select id="rcp-payment-status" name="status">
										<option value="pending"><?php _e( 'Pending', 'rcp' ); ?></option>
										<option value="complete" selected="selected"><?php _e( 'Complete', 'rcp' ); ?></option>
										<option value="failed"><?php _e( 'Failed', 'rcp' ); ?></option>
										<option value="refunded"><?php _e( 'Refunded', 'rcp' ); ?></option>
										<option value="abandoned"><?php _e( 'Abandoned', 'rcp' ); ?></option>
									</select>
								</p>

								<input type="hidden" name="rcp-action" value="add_membership_payment"/>
								<input type="hidden" name="membership_id" value="<?php echo esc_attr( $membership->get_id() ); ?>"/>
								<?php wp_nonce_field( 'rcp_add_membership_payment', 'rcp_add_membership_payment_nonce' ); ?>
								<?php if ( $membership->can_renew() ) : ?>
									<input type="submit" name="renew_and_add_payment" class="button alignright" style="margin-left: 8px;" value="<?php esc_attr_e( 'Record Payment and Renew Membership', 'rcp' ); ?>"/>
								<?php endif; ?>
								<input type="submit" name="add_payment_only" class="button alignright" value="<?php esc_attr_e( 'Record Payment Only', 'rcp' ); ?>"/>
							</form>
						</td>
					</tr>
					</tfoot>
				<?php endif; ?>
			</table>
		</div>

		<?php do_action( 'rcp_membership_details_after_payments', $membership ); ?>

		<div id="rcp-membership-notes-wrapper" class="rcp-item-section">
			<h3><?php _e( 'Notes:', 'rcp' ); ?></h3>
			<div id="rcp-membership-notes" class="rcp-item-notes">
				<?php echo wpautop( $membership->get_notes() ); ?>
			</div>
			<form id="rcp-edit-membership-notes" method="POST">
				<label for="rcp-add-membership-note" class="screen-reader-text"><?php _e( 'Add Note', 'rcp' ); ?></label>
				<textarea id="rcp-add-membership-note" class="rcp-add-item-note" name="new_note" placeholder="<?php esc_attr_e( 'Add a note...', 'rcp' ); ?>"></textarea>
				<div class="edit-item">
					<input type="hidden" name="rcp-action" value="add_membership_note"/>
					<input type="hidden" name="membership_id" value="<?php echo esc_attr( $membership->get_id() ); ?>"/>
					<?php wp_nonce_field( 'rcp_add_membership_note', 'rcp_add_membership_note_nonce' ); ?>
					<input type="submit" class="button" value="<?php esc_attr_e( 'Add Note', 'rcp' ); ?>"/>
				</div>
			</form>
		</div>
	</div>
</div>
