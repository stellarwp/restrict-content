<?php
/**
 * Card Update Form
 *
 * This form is displayed with the [rcp_update_card] shortcode.
 *
 * @link https://restrictcontentpro.com/knowledgebase/rcp_update_card
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/Card Update Form
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @var RCP_Membership $rcp_membership
 */
global $rcp_membership;

$customer = $rcp_membership->get_customer();
$gateway  = rcp_get_gateway_class( $rcp_membership->get_gateway() );

/*
 * If the customer has more than one membership, display a navigation menu that:
 *
 *     1) Clarifies which membership they're currently updating the details for.
 *     2) Allows the customer to quickly switch to a different membership.
 */
if ( $customer instanceof RCP_Customer && count( $customer->get_memberships() ) > 1 ) {
	$membership_list = array();
	$current_page    = remove_query_arg( 'membership_id' );

	foreach ( $customer->get_memberships() as $membership ) {
		if ( $membership->get_id() === $rcp_membership->get_id() ) {
			$membership_list[] = $membership->get_membership_level_name();
			continue;
		}

		$update_card_url = add_query_arg( 'membership_id', urlencode( $membership->get_id() ), $current_page );

		$membership_list[] = '<a href="' . esc_url( $update_card_url ) . '">' . $membership->get_membership_level_name() . '</a>';
	}

	?>
	<p class="rcp-update-payment-method-membership-menu">
		<?php echo implode( ' | ', $membership_list ); ?>
	</p>
	<?php
}
?>
<form id="rcp_update_card_form" class="rcp_form" action="" method="POST">

	<?php $cards = $rcp_membership->get_card_details(); ?>

	<?php if( ! empty( $cards ) ) : ?>
		<h3><?php _e( 'Your Cards', 'rcp' ); ?></h3>
		<ul class="rcp-gateway-saved-payment-methods">
			<?php foreach( $cards as $card ) : ?>
				<li>
					<label for="<?php echo ! empty( $card['id'] ) ? esc_attr( $card['id'] ) : ''; ?>">
						<input type="radio" id="<?php echo ! empty( $card['id'] ) ? esc_attr( $card['id'] ) : ''; ?>" name="rcp_gateway_existing_payment_method" value="<?php echo ! empty( $card['id'] ) ? esc_attr( $card['id'] ) : ''; ?>" <?php checked( ! empty( $card['current'] ) ); ?> />
						<span class="rcp-gateway-saved-card-brand"><?php echo esc_html( ucfirst( $card['type'] ) ); ?></span>
						<span class="rcp-gateway-saved-card-ending-label"><?php _e( 'ending in', 'rcp' ); ?></span>
						<span class="rcp-gateway-saved-card-last-4"><?php echo esc_html( $card['last4'] ); ?></span>
						<span class="rcp-gateway-saved-payment-method-sep">&mdash; </span>
						<span class="rcp-gateway-saved-card-expires-label"><?php _e( 'expires', 'rcp' ); ?></span>
						<span class="rcp-gateway-saved-card-expiration"><?php printf( '%s / %s', esc_html( $card['exp_month'] ), esc_html( $card['exp_year'] ) ); ?></span>
						<?php if ( ! empty( $card['current'] ) ) : ?>
							<span class="rcp-gateway-saved-payment-method-sep">&mdash; </span>
							<span class="rcp-gateway-saved-card-current"><?php _e( 'current', 'rcp' ); ?></span>
						<?php endif; ?>
						<?php
						/**
						 * Add extra information / actions for an individual card.
						 *
						 * @param array          $card           Array of card details.
						 * @param RCP_Membership $rcp_membership Current membership object.
						 *
						 * @since 3.3
						 */
						do_action( 'rcp_update_billing_card_list_item', $card, $rcp_membership );
						?>
					</label>
				</li>
			<?php endforeach; ?>
	<?php else : ?>
		<ul class="rcp-gateway-saved-payment-methods" style="display:none;">
	<?php endif; ?>

			<li class="rcp-gateway-add-payment-method-wrap">
				<label for="rcp-gateway-add-payment-method">
					<input type="radio" id="rcp-gateway-add-payment-method" name="rcp_gateway_existing_payment_method" value="new" <?php checked( empty( $cards ) ); ?> />
					<?php _e( 'Add New Card', 'rcp' ); ?>
				</label>
			</li>
		</ul>
	<?php
	/**
	 * Load update billing card fields via the payment gateway class
	 *
	 * @see RCP_Payment_Gateway::update_card_fields()
	 */
	if ( ! empty( $gateway ) ) {
		$gateway->update_card_fields();
	}
	?>

	<p id="rcp_submit_wrap">
		<input type="hidden" name="rcp_membership_id" value="<?php echo esc_attr( $rcp_membership->get_id() ); ?>"/>
		<input type="hidden" name="rcp_update_card_nonce" value="<?php echo wp_create_nonce( 'rcp-update-card-nonce' ); ?>"/>
		<input type="submit" name="rcp_submit_card_update" id="rcp_submit" class="rcp-button" value="<?php esc_attr_e( 'Update Payment Method', 'rcp' ); ?>"/>
	</p>
</form>
