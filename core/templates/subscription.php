<?php
/**
 * Subscription Details
 *
 * This template displays the current user's membership details with [subscription_details]
 *
 * @link        http://docs.restrictcontentpro.com/article/1600-subscriptiondetails
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/Subscription
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use RCP\Membership_Level;

global $user_ID, $rcp_options;

$member = new RCP_Member( $user_ID );

$customer    = rcp_get_customer(); // currently logged in customer
$memberships = is_object( $customer ) ? $customer->get_memberships() : false;

do_action( 'rcp_subscription_details_top' );

if ( isset( $_GET['profile'] ) && 'cancelled' == $_GET['profile'] && ! empty( $_GET['membership_id'] ) ) :
	$cancelled_membership = rcp_get_membership( absint( $_GET['membership_id'] ) );
	?>
	<p class="rcp_success">
		<span><?php printf( __( 'Your %s subscription has been successfully cancelled. Your membership will expire on %s.', 'rcp' ), $cancelled_membership->get_membership_level_name(), $cancelled_membership->get_expiration_date() ); ?></span>
	</p>
<?php elseif ( isset( $_GET['cancellation_failure'] ) ) : ?>
	<p class="rcp_error"><span><?php echo esc_html( urldecode( $_GET['cancellation_failure'] ) ); ?> </span></p>
<?php endif;

$has_payment_plan = false;

if ( ! empty( $memberships ) ) {
	foreach ( $memberships as $membership ) {
		/**
		 * @var RCP_Membership $membership
		 */
		if ( $membership->is_recurring() && $membership->is_expired() && $membership->can_update_billing_card() ) : ?>
			<p class="rcp_error">
				<span>
					<?php
					printf( __( 'Your %s membership has expired. <a href="%s">Update your payment method</a> to reactivate and renew your membership.', 'rcp' ),
						$membership->get_membership_level_name(),
						esc_url( add_query_arg( 'membership_id', urlencode( $membership->get_id() ), get_permalink( $rcp_options['update_card'] ) ) )
					);
					?>
				</span>
			</p>
		<?php endif;

		if ( $membership->has_payment_plan() ) {
			$has_payment_plan = true;
		}
	}
}
?>
	<div class="rcp-table" id="rcp-account-overview">
		<header>
		<div class="rcp-account-overview">
			<span><?php _e( 'Membership', 'rcp' ); ?></span>
			<span><?php _e( 'Status', 'rcp' ); ?></span>
			<span><?php _e( 'Expiration / Renewal Date', 'rcp' ); ?></span>
			<?php if ( $has_payment_plan ) : ?>
				<span><?php _e( 'Times Billed', 'rcp' ); ?></span>
			<?php endif; ?>
			<span><?php _e( 'Actions', 'rcp' ); ?></span>
		</div>
		</header>
		<main>
		<?php if ( ! empty( $memberships ) ) : ?>
			<?php foreach ( $memberships as $membership ) : ?>
				<div class="rcp-account-overview">
					<span data-th="<?php esc_attr_e( 'Membership', 'rcp' ); ?>">
						<?php echo esc_html( $membership->get_membership_level_name() ); ?>
					</span>
					<span data-th="<?php esc_attr_e( 'Status', 'rcp' ); ?>">
						<?php rcp_print_membership_status( $membership->get_id() ); ?>
					</span>
					<span data-th="<?php esc_attr_e( 'Expiration / Renewal Date', 'rcp' ); ?>">
						<?php
						echo $membership->get_expiration_date();

						if ( $membership->is_recurring() && 'active' === $membership->get_status() ) {
							echo '<span class="rcp-membership-auto-renew-notice">' . __( '(renews automatically)', 'rcp' ) . '</span>';
						}

						if ( $membership->is_active() && $membership->can_toggle_auto_renew() ) {
							echo '<span class="rcp-auto-renew-toggle">';
							if ( $membership->is_recurring() ) {
								$toggle_off_url = wp_nonce_url( add_query_arg( array(
										'rcp-action' => 'disable_auto_renew',
										'membership-id' => urlencode( $membership->get_id() )
								) ), 'rcp_toggle_auto_renew_off' );

								echo '<a href="' . esc_url( $toggle_off_url ) . '" class="rcp-disable-auto-renew">' . __( 'Disable auto renew', 'rcp' ) . '</a>';
							} else {
								$toggle_on_url = wp_nonce_url( add_query_arg( array(
										'rcp-action'    => 'enable_auto_renew',
										'membership-id' => urlencode( $membership->get_id() )
								) ), 'rcp_toggle_auto_renew_on' );

								echo '<a href="' . esc_url( $toggle_on_url ) . '" class="rcp-enable-auto-renew" data-expiration="' . esc_attr( $membership->get_expiration_date( true ) ) . '">' . __( 'Enable auto renew', 'rcp' ) . '</a>';
							}
							echo '</span>';
						}
						?>
					</span>
					<?php
					if ( $has_payment_plan ) {
						?>
						<span data-th="<?php esc_attr_e( 'Times Billed', 'rcp' ); ?>">
							<?php
							$membership_level = rcp_get_membership_level( $membership->get_object_id() );

							if ( $membership_level instanceof Membership_Level ) {
								if ( 0 == $membership->get_maximum_renewals() && ! $membership_level->is_lifetime() && ! $membership_level->is_free() ) {
									printf( __( '%d / Until Cancelled', 'rcp' ), $membership->get_times_billed() );
								} else {
									$renewals = $membership_level->is_free() ? 1 : $membership->get_maximum_renewals() + 1;

									printf( __( '%d / %d', 'rcp' ), $membership->get_times_billed(), $renewals );
								}
							}
							?>
						</span>
						<?php
					}
					?>
					<span data-th="<?php esc_attr_e( 'Actions', 'rcp' ); ?>">
						<?php
						$links = array();

						if ( $membership->can_update_billing_card() ) {
							$links[] = '<a href="' . esc_url( add_query_arg( 'membership_id', urlencode( $membership->get_id() ), get_permalink( $rcp_options['update_card'] ) ) ) . '" title="' . esc_attr__( 'Update payment method', 'rcp' ) . '" class="rcp_sub_details_update_card">' . __( 'Update payment method', 'rcp' ) . '</a>';
						}

						if ( $membership->can_renew() ) {
							$links[] = apply_filters( 'rcp_subscription_details_action_renew', '<a href="' . esc_url( rcp_get_membership_renewal_url( $membership->get_id() ) ) . '" title="' . esc_attr__( 'Renew your membership', 'rcp' ) . '" class="rcp_sub_details_renew">' . __( 'Renew your membership', 'rcp' ) . '</a>', $user_ID );
						}

						if ( $membership->upgrade_possible() ) {
							$links[] = apply_filters( 'rcp_subscription_details_action_upgrade', '<a href="' . esc_url( rcp_get_membership_upgrade_url( $membership->get_id() ) ) . '" title="' . esc_attr__( 'Upgrade or change your membership', 'rcp' ) . '" class="rcp_sub_details_change_membership">' . __( 'Upgrade or change your membership', 'rcp' ) . '</a>', $user_ID );
						}

						if ( $membership->is_active() && $membership->can_cancel() && ! $membership->has_payment_plan() ) {
							$links[] = apply_filters( 'rcp_subscription_details_action_cancel', '<a href="' . esc_url( rcp_get_membership_cancel_url( $membership->get_id() ) ) . '" title="' . esc_attr__( 'Cancel your membership', 'rcp' ) . '" class="rcp_sub_details_cancel" id="rcp_cancel_membership_' . esc_attr( $membership->get_id() ) . '">' . __( 'Cancel your membership', 'rcp' ) . '</a>', $user_ID );
						}

						/**
						 * Filters the action links HTML.
						 *
						 * @param string         $actions    Formatted HTML links.
						 * @param array          $links      Array of links before they're imploded into an HTML string.
						 * @param int            $user_ID    ID of the current user.
						 * @param RCP_Membership $membership Current membership record being displayed.
						 */
						echo apply_filters( 'rcp_subscription_details_actions', implode( '<br/>', $links ), $links, $user_ID, $membership );

						/**
						 * Add custom HTML to the "Actions" column.
						 *
						 * @param array          $links      Existing links.
						 * @param RCP_Membership $membership Current membership record being displayed.
						 */
						do_action( 'rcp_subscription_details_action_links', $links, $membership );

						if ( $membership->is_active() && $membership->can_cancel() && ! $membership->has_payment_plan() ) {
							?>
							<script>
								// Adds a confirm dialog to the cancel link
								var cancel_link = document.querySelector( "#rcp_cancel_membership_<?php echo $membership->get_id(); ?>" );

								if ( cancel_link ) {

									cancel_link.addEventListener( "click", function ( event ) {
										event.preventDefault();

										var message = '<?php printf( __( "Are you sure you want to cancel your %s subscription? If you cancel, your membership will expire on %s.", "rcp" ), $membership->get_membership_level_name(), $membership->get_expiration_date() ); ?>';
										var confirmed = confirm( message );

										if ( true === confirmed ) {
											location.assign(  document.querySelector( "#rcp_cancel_membership_<?php echo $membership->get_id(); ?>" ).href );
										} else {
											return false;
										}
									} );

								}
							</script>
							<?php
						}
						?>
					</span>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div>
				<div data-th="<?php esc_attr_e( 'Membership', 'rcp' ); ?>" colspan="4"><?php _e( 'You do not have any memberships.', 'rcp' ); ?></div>
			</div>
		<?php endif; ?>
		</main>
		</div>
	<div class="rcp-table" id="rcp-payment-history">
		<header>
		<div class="rcp-payment-history">
			<span><?php _e( 'Invoice #', 'rcp' ); ?></span>
			<span><?php _e( 'Membership', 'rcp' ); ?></span>
			<span><?php _e( 'Amount', 'rcp' ); ?></span>
			<span><?php _e( 'Payment Status', 'rcp' ); ?></span>
			<span><?php _e( 'Date', 'rcp' ); ?></span>
			<span><?php _e( 'Actions', 'rcp' ); ?></span>
		</div>
		</header>
		<main>
		<?php
		$payments = is_object( $customer ) ? $customer->get_payments() : false;
		if ( $payments ) : ?>
			<?php foreach ( $payments as $payment ) : ?>
				<div class="rcp-payment-history">
					<span data-th="<?php esc_attr_e( 'Invoice #', 'rcp' ); ?>"><?php echo $payment->id; ?></span>
					<span data-th="<?php esc_attr_e( 'Membership', 'rcp' ); ?>"><?php echo esc_html( $payment->subscription ); ?></span>
					<span data-th="<?php esc_attr_e( 'Amount', 'rcp' ); ?>"><?php echo rcp_currency_filter( $payment->amount ); ?></span>
					<span data-th="<?php esc_attr_e( 'Payment Status', 'rcp' ); ?>"><?php echo rcp_get_payment_status_label( $payment ); ?></span>
					<span data-th="<?php esc_attr_e( 'Date', 'rcp' ); ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->date, current_time( 'timestamp' ) ) ); ?></span>
					<span data-th="<?php esc_attr_e( 'Actions', 'rcp' ); ?>">
						<?php if ( in_array( $payment->status, array( 'pending', 'abandoned', 'failed' ) ) && empty( $payment->transaction_id ) ) : ?>
							<a href="<?php echo esc_url( rcp_get_payment_recovery_url( $payment->id ) ); ?>">
								<?php echo 'failed' === $payment->status ? __( 'Retry Payment', 'rcp' ) : __( 'Complete Payment', 'rcp' ); ?>
							</a> <br/>
						<?php endif; ?>
						<a href="<?php echo esc_url( rcp_get_invoice_url( $payment->id ) ); ?>"><?php _e( 'View Receipt', 'rcp' ); ?></a>
					</span>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div>
				<div data-th="<?php _e( 'Membership', 'rcp' ); ?>" colspan="6"><?php _e( 'You have not made any payments.', 'rcp' ); ?></div>
			</div>
		<?php endif; ?>
		</main>
		</div>
<?php do_action( 'rcp_subscription_details_bottom' );
