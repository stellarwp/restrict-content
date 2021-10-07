<?php
/**
 * Cancel Membership
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $_GET['membership_id'] ) || ! is_numeric( $_GET['membership_id'] ) ) {
	wp_die( __( 'Something went wrong.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
}

$membership_id = absint( $_GET['membership_id'] );
$membership    = rcp_get_membership( $membership_id );

if ( empty( $membership ) ) {
	wp_die( __( 'Something went wrong.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
}

// Prevent editing disabled memberships.
if ( $membership->is_disabled() ) {
	wp_die( __( 'Invalid membership.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
}

// Prevent cancelling memberships that cannot be cancelled.
if ( ! $membership->can_cancel() ) {
	wp_die( __( 'This membership cannot be cancelled.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
}

$cancel_url = wp_nonce_url( rcp_get_memberships_admin_page( array(
	'membership_id' => $membership->get_id(),
	'rcp-action'    => 'cancel_membership'
) ), 'cancel_membership' );

$edit_url = rcp_get_memberships_admin_page( array(
	'membership_id' => $membership->get_id(),
	'view'          => 'edit'
) );
?>
<div class="wrap">
	<h1><?php _e( 'Confirm Membership Cancellation', 'rcp' ) ?></h1>
	<p><?php printf( __( 'Are you sure you wish to cancel <a href="%s">membership #%d</a>? This will stop automatic billing. The customer will retain access to restricted content until the membership expiration date, %s.', 'rcp' ), esc_url( $edit_url ), $membership->get_id(), $membership->get_expiration_date() ); ?></p>
	<?php if ( $membership->has_payment_plan() && ! $membership->at_maximum_renewals() ) : ?>
		<div class="notice notice-error inline">
			<p><?php printf( __( 'Note: This membership is a payment plan still in progress; it has only completed %d / %d payments. If you cancel this membership, future installment payments will not be processed automatically.', 'rcp' ), $membership->get_times_billed(), $membership->get_maximum_renewals() + 1 ); ?></p>
		</div>
	<?php endif; ?>
	<p>
		<a href="<?php echo esc_url( $cancel_url ); ?>" class="button button-primary"><?php _e( 'Cancel Membership', 'rcp' ); ?></a>
	</p>
</div>
