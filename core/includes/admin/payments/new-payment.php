<?php
/**
 * New Payment Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/New Payment
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
?>

<h1>
	<?php _e( 'Create Payment', 'rcp' ); ?> -
	<a href="<?php echo admin_url( '/admin.php?page=rcp-payments' ); ?>" class="button-secondary">
		<?php _e( 'Cancel', 'rcp' ); ?>
	</a>
</h1>
<form id="rcp-add-payment" action="" method="post">
	<table class="form-table">
		<tbody>
			<?php do_action( 'rcp_add_payment_before' ); ?>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-user-id"><?php _e( 'User', 'rcp' ); ?></label>
				</th>
				<td>
					<input type="text" name="user" autocomplete="off" id="rcp-user" class="regular-text rcp-user-search"/>
					<img class="rcp-ajax waiting" src="<?php echo admin_url('images/wpspin_light.gif'); ?>" style="display: none;"/>
					<div id="rcp_user_search_results"></div>
					<p class="description"><?php _e('Begin typing the user name to add a payment record for.', 'rcp'); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-membership-level"><?php _e( 'Membership Level', 'rcp' ); ?></label>
				</th>
				<td>
					<select id="rcp-membership-level" name="membership_level_id">
						<?php foreach ( rcp_get_membership_levels( array( 'number' => 999 ) ) as $membership_level ) : ?>
							<option value="<?php echo esc_attr( $membership_level->get_id() ); ?>"><?php echo esc_html( $membership_level->get_name() ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php _e( 'The membership level this payment is for.', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-amount"><?php _e( 'Amount', 'rcp' ); ?></label>
				</th>
				<td>
					<input type="text" name="amount" id="rcp-amount" pattern="^[+\-]?[0-9]{1,3}(?:,?[0-9]{3})*(\.[0-9]{2})?$" title="<?php _e( 'Please enter a payment amount in the format of 1.99', 'rcp' ); ?>" min="0.00" value=""/>
					<p class="description"><?php _e( 'The amount of this payment', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-date"><?php _e( 'Payment Date', 'rcp' ); ?></label>
				</th>
				<td>
					<input name="date" id="rcp-date" type="text" class="rcp-datepicker" value=""/>
					<p class="description"><?php _e( 'Enter the date for this payment in the format of yyyy-mm-dd', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-date"><?php _e( 'Transaction ID', 'rcp' ); ?></label>
				</th>
				<td>
					<input name="transaction-id" id="rcp-transaction-id" type="text" class="regular-text" value=""/>
					<p class="description"><?php _e( 'Enter the transaction ID for this payment, if any', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-status"><?php _e( 'Status', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="status" id="rcp-status">
						<option value="pending"><?php _e( 'Pending', 'rcp' ); ?></option>
						<option value="complete"><?php _e( 'Complete', 'rcp' ); ?></option>
						<option value="failed"><?php _e( 'Failed', 'rcp' ); ?></option>
						<option value="refunded"><?php _e( 'Refunded', 'rcp' ); ?></option>
						<option value="abandoned"><?php _e( 'Abandoned', 'rcp' ); ?></option>
					</select>
					<p class="description"><?php _e( 'The status of this payment.', 'rcp' ); ?></p>
				</td>
			</tr>
			<?php do_action( 'rcp_add_payment_after' ); ?>
		</tbody>
	</table>
	<p class="submit">
		<input type="hidden" name="rcp-action" value="add-payment"/>
		<input type="submit" value="<?php _e( 'Create Payment', 'rcp' ); ?>" class="button-primary"/>
	</p>
	<?php wp_nonce_field( 'rcp_add_payment_nonce', 'rcp_add_payment_nonce' ); ?>
</form>
