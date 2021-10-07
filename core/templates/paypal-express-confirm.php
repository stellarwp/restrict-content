<?php
/**
 * PayPal Express Confirmation
 *
 * This template is loaded while processing a PayPal Express payment. The customer is
 * asked to confirm the subscription details.
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/PayPal Express Confirmation
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use RCP\Membership_Level;

global $rcp_checkout_details;

$payments         = new RCP_Payments();
$customer         = rcp_get_customer(); // current customer
$membership_id    = ! empty( $rcp_checkout_details['membership_id'] ) ? absint( $rcp_checkout_details['membership_id'] ) : 0;
$payment_id       = ! empty( $membership_id ) ? rcp_get_membership_meta( $membership_id, 'pending_payment_id', true ) : false;
$payment          = $payments->get_payment( $payment_id );
$membership_level = rcp_get_membership_level( $payment->object_id );
?>
<div class="rcp-confirm-details" id="billing_info">
	<h3><?php _e( 'Please confirm your payment', 'rcp' ); ?></h3>
	<p><strong><?php echo isset( $rcp_checkout_details['FIRSTNAME'] ) ? esc_html( $rcp_checkout_details['FIRSTNAME'] ) : ''; ?> <?php echo isset( $rcp_checkout_details['LASTNAME'] ) ? esc_html( $rcp_checkout_details['LASTNAME'] ) : ''; ?></strong><br />
	<?php _e( 'PayPal Status:', 'rcp' ); ?> <?php echo isset( $rcp_checkout_details['PAYERSTATUS'] ) ? esc_html( $rcp_checkout_details['PAYERSTATUS'] ) : ''; ?><br />
	<?php _e( 'Email:', 'rcp' ); ?> <?php echo isset( $rcp_checkout_details['EMAIL'] ) ? esc_html( $rcp_checkout_details['EMAIL'] ) : ''; ?></p>
</div>
<table id="order_summary" class="rcp-table">
	<thead>
		<tr>
			<th><?php _e( 'Description', 'rcp' ); ?></th>
			<th><?php _e( 'Amount', 'rcp' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-th="<?php esc_attr_e( 'Subscription', 'rcp' ); ?>" class="rcp-ppe-confirm-name"><?php echo isset( $rcp_checkout_details['DESC'] ) ? esc_html( $rcp_checkout_details['DESC'] ) : esc_html( $payment->subscription ); ?></td>
			<td data-th="<?php esc_attr_e( 'Subtotal', 'rcp' ); ?>" class="rcp-ppe-confirm-price"><?php echo $membership_level instanceof Membership_Level ? rcp_currency_filter( $membership_level->get_price() ) : ''; ?></td>
		</tr>
	</tbody>
	<tfoot>
		<?php if ( ! empty( $payment->discount_amount ) ) : ?>
			<tr>
				<th scope="row" class="rcp-ppe-confirm-name"><?php _e( 'Discount:', 'rcp' ); ?></th>
				<td data-th="<?php esc_attr_e( 'Discount', 'rcp' ); ?>" class="rcp-ppe-confirm-price"><?php echo rcp_currency_filter( -1 * abs( $payment->discount_amount ) ); ?></td>
			</tr>
		<?php endif; ?>
		<?php if ( ! empty( $payment->fees ) ) : ?>
			<tr>
				<th scope="row" class="rcp-ppe-confirm-name"><?php _e( 'Fees:', 'rcp' ); ?></th>
				<td data-th="<?php esc_attr_e( 'Fees', 'rcp' ); ?>" class="rcp-ppe-confirm-price"><?php echo rcp_currency_filter( $payment->fees ); ?></td>
			</tr>
		<?php endif; ?>
		<?php if ( ! empty( $payment->credits ) ) : ?>
			<tr>
				<th scope="row" class="rcp-ppe-confirm-name"><?php _e( 'Credits:', 'rcp' ); ?></th>
				<td data-th="<?php esc_attr_e( 'Credits', 'rcp' ); ?>" class="rcp-ppe-confirm-price"><?php echo rcp_currency_filter( -1 * abs( $payment->credits ) ); ?></td>
			</tr>
		<?php endif; ?>
		<tr>
			<th scope="row" class="rcp-ppe-confirm-name"><?php _e( 'Total Today:', 'rcp' ); ?></th>
			<td data-th="<?php esc_attr_e( 'Total Today', 'rcp' ); ?>" class="rcp-ppe-confirm-price"><?php echo rcp_currency_filter( $payment->amount ); ?></td>
		</tr>
		<?php if ( ! empty( $_GET['rcp-recurring'] ) ) : ?>
			<?php
			if ( $membership_level->get_duration() == 1 ) {
				$recurring_heading = sprintf( __( 'Total Recurring Per %s:', 'rcp' ), rcp_filter_duration_unit( $membership_level->get_duration_unit(), 1 ) );
			} else {
				$recurring_heading = sprintf( __( 'Total Recurring Every %s %s:', 'rcp' ), $membership_level->get_duration(), rcp_filter_duration_unit( $membership_level->get_duration_unit(), $membership_level->get_duration() ) );
			}
			?>
			<tr>
				<th scope="row" class="rcp-ppe-confirm-name"><?php echo $recurring_heading; ?></th>
				<td data-th="<?php echo esc_attr( $recurring_heading ); ?>" class="rcp-ppe-confirm-price"><?php echo rcp_currency_filter( $rcp_checkout_details['PAYMENTREQUEST_0_AMT'] ); // @todo ?></td>
			</tr>
		<?php endif; ?>
	</tfoot>
</table>

<form id="rcp-paypal-express-confirm-form" action="<?php echo esc_url( add_query_arg( 'rcp-confirm', 'paypal_express' ) ); ?>" method="post">
	<input type="hidden" name="confirmation" value="yes" />
	<input type="hidden" name="token" value="<?php echo esc_attr( $_GET['token'] ); ?>" />
	<input type="hidden" name="payer_id" value="<?php echo esc_attr( $_GET['PayerID'] ); ?>" />
	<input type="hidden" name="rcp_ppe_confirm_nonce" value="<?php echo wp_create_nonce( 'rcp-ppe-confirm-nonce' ); ?>"/>
	<input type="submit" class="rcp-button" value="<?php esc_attr_e( 'Confirm', 'rcp' ); ?>" />
</form>
