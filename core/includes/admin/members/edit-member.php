<?php
/**
 * Edit Member Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Edit Member
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if( isset( $_GET['edit_member'] ) ) {
	$member_id = absint( $_GET['edit_member'] );
} elseif( isset( $_GET['view_member'] ) ) {
	$member_id = absint( $_GET['view_member'] );
}
$member = new RCP_Member( $member_id );

$current_status        = $member->get_status();
$subscription_level_id = $member->get_subscription_id();
$expiration_date       = $member->get_expiration_date( false );

// If member is pending, get pending details.
if ( 'pending' == $current_status ) {

	$pending_subscription_id = $member->get_pending_subscription_id();

	if ( ! empty( $pending_subscription_id ) ) {
		$subscription_level_id = $pending_subscription_id;
	}

	if ( empty( $expiration_date ) ) {
		$expiration_date = $member->calculate_expiration( true );
	}
}
?>
<h1>
	<?php _e( 'Edit Member:', 'rcp' ); echo ' ' . $member->display_name; ?>
</h1>

<?php if( ! $member->exists() ) : ?>
	<div class="error settings-error">
		<p><?php _e( 'Error: Invalid member ID.', 'rcp' ); ?></p>
	</div>
	<?php return; ?>
<?php endif; ?>

<?php if( $switch_to_url = rcp_get_switch_to_url( $member->ID ) ) { ?>
	<a href="<?php echo esc_url( $switch_to_url ); ?>" class="rcp_switch"><?php _e('Switch to User', 'rcp'); ?></a>
<?php } ?>
<form id="rcp-edit-member" action="" method="post">
	<table class="form-table">
		<tbody>
			<?php do_action( 'rcp_edit_member_before', $member->ID ); ?>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-userlogin"><?php _e( 'User Login', 'rcp' ); ?></label>
				</th>
				<td>
					<input id="rcp-userlogin" type="text" value="<?php echo esc_attr( $member->user_login ); ?>" disabled="disabled"/>
					<p class="description"><?php _e( 'The member\'s login name. This cannot be changed.', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-email"><?php _e( 'User Email', 'rcp' ); ?></label>
				</th>
				<td>
					<input id="rcp-email" name="email" type="text" value="<?php echo esc_attr( $member->user_email ); ?>"/>
					<p class="description"><?php _e( 'The member\'s email address.', 'rcp' ); ?> <a href="<?php echo esc_url( add_query_arg( 'user_id', $member->ID, admin_url( 'user-edit.php' ) ) ); ?>" title="<?php _e( 'View User\'s Profile', 'rcp' ); ?>"><?php _e( 'Edit User Account', 'rcp' ); ?></a></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-status"><?php _e( 'Status', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="status" id="rcp-status">
						<?php
							$statuses = array( 'active', 'expired', 'cancelled', 'pending', 'free' );
							foreach( $statuses as $status ) :
								echo '<option value="' . esc_attr( $status ) .  '"' . selected( $status, rcp_get_status( $member->ID ), false ) . '>' . ucwords( $status ) . '</option>';
							endforeach;
						?>
					</select>
					<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( 'An Active status is required to access paid content. Members with a status of Cancelled may continue to access paid content until the expiration date on their account is reached.', 'rcp' ); ?>"></span>
					<?php if ( $member->is_pending_verification() ) : ?>
						<p class="description"><?php printf( __( '(Pending email verification. <a href="%s">Click to manually verify email.</a>)', 'rcp' ), esc_url( wp_nonce_url( add_query_arg( array( 'rcp-action' => 'verify_email', 'member_id' => $member->ID ), add_query_arg( 'edit_member', $member->ID, admin_url( 'admin.php?page=rcp-members' ) ) ), 'rcp-manually-verify-email-nonce' ) ) ); ?></p>
					<?php endif; ?>
					<p class="description"><?php _e( 'The status of this user\'s subscription', 'rcp' ); ?></p>
					<p id="rcp-revoke-access-wrap">
						<input type="checkbox" id="rcp-revoke-access" name="rcp-revoke-access" value="1">
						<label for="rcp-revoke-access"><?php _e( 'Revoke access now', 'rcp' ); ?></label>
						<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'If not enabled, the member will retain access until the end of their current term. If checked, the member\'s status will be changed to "expired" and access will be revoked immediately.', 'rcp' ); ?>"></span>
					</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-level"><?php _e( 'Subscription Level', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="level" id="rcp-level">
						<?php
							foreach( rcp_get_subscription_levels( 'all' ) as $key => $level ) :
								echo '<option value="' . esc_attr( absint( $level->id ) ) . '"' . selected( $level->id, $subscription_level_id, false ) . '>' . esc_html( $level->name ) . '</option>';
							endforeach;
						?>
					</select>
					<p class="description"><?php _e( 'Choose the subscription level for this user', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-key"><?php _e( 'Subscription Key', 'rcp' ); ?></label>
				</th>
				<td>
					<input id="rcp-key" type="text" value="<?php echo esc_attr( $member->get_subscription_key() ); ?>" disabled="disabled"/>
					<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( 'This key is used for reference purposes and may be shown on payment and subscription records in your merchant accounts.', 'rcp' ); ?>"></span>
					<p class="description"><?php _e( 'The member\'s subscription key. This cannot be changed.', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-expiration"><?php _e( 'Expiration date', 'rcp' ); ?></label>
				</th>
				<td>
					<?php
					if( ! empty( $expiration_date ) && 'none' != $expiration_date ) {
						$expiration_date = date( 'Y-m-d', strtotime( $expiration_date, current_time( 'timestamp' ) ) );
					}
					?>
					<input name="expiration" id="rcp-expiration" type="text" class="rcp-datepicker" value="<?php echo esc_attr( $expiration_date ); ?>"/>
					<label for="rcp-unlimited">
						<input name="unlimited" id="rcp-unlimited" type="checkbox"<?php checked( $expiration_date, 'none' ); ?>/>
						<span class="description"><?php _e( 'Never expires?', 'rcp' ); ?></span>
					</label>
					<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( 'This is the date the member will lose access to content if their membership is not renewed.', 'rcp' ); ?>"></span>
					<p class="description"><?php _e( 'Enter the expiration date for this user in the format of yyyy-mm-dd', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-payment-profile-id"><?php _e( 'Payment Profile ID', 'rcp' ); ?></label>
				</th>
				<td>
					<input name="payment-profile-id" id="rcp-payment-profile-id" type="text" value="<?php echo esc_attr( $member->get_payment_profile_id() ); ?>"/>
					<p class="description"><?php _e( 'This is the customer\'s payment profile ID in the payment processor', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<?php _e( 'Recurring', 'rcp' ); ?>
				</th>
				<td>
					<label for="rcp-recurring">
						<input name="recurring" id="rcp-recurring" type="checkbox" value="1" <?php checked( 1, rcp_is_recurring( $member->ID ) ); ?>/>
						<?php _e( 'Is this user\'s subscription recurring?', 'rcp' ); ?>
					</label>
					<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( 'If checked, this member has a recurring subscription. Only customers with recurring memberships will be given the option to cancel their membership on their subscription details page.', 'rcp' ); ?>"></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<?php _e( 'Trialing', 'rcp' ); ?>
				</th>
				<td>
					<label for="rcp-trialing">
						<input name="trialing" id="rcp-trialing" type="checkbox" value="1" <?php checked( 1, rcp_is_trialing( $member->ID ) ); ?>/>
						<?php _e( 'Does this user have a trial membership?', 'rcp' ); ?>
					</label>
					<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Members are limited to a single trial membership. Once a trial has been used, the member may not sign up for another trial membership.', 'rcp' ); ?>"></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<?php _e( 'Sign Up Method', 'rcp' ); ?>
				</th>
				<td>
					<?php $method = get_user_meta( $member->ID, 'rcp_signup_method', true ) ? get_user_meta( $member->ID, 'rcp_signup_method', true ) : 'live';?>
					<select name="signup_method" id="rcp-signup-method">
						<option value="live" <?php selected( $method, 'live' ); ?>><?php _e( 'User Signup', 'rcp' ); ?>
						<option value="manual" <?php selected( $method, 'manual' ); ?>><?php _e( 'Added by admin manually', 'rcp' ); ?>
						<option value="imported" <?php selected( $method, 'imported' ); ?>><?php _e( 'Imported', 'rcp' ); ?>
					</select>
					<p class="description"><?php _e( 'Was this a real signup or a membership given to the user', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-notes"><?php _e( 'User Notes', 'rcp' ); ?></label>
				</th>
				<td>
					<textarea name="notes" id="rcp-notes" class="large-text" rows="10" cols="50"><?php echo esc_textarea( get_user_meta( $member->ID, 'rcp_notes', true ) ); ?></textarea>
					<p class="description"><?php _e( 'Use this area to record notes about this user if needed', 'rcp' ); ?></p>
				</td>
			</tr>
			<?php if ( ! empty( $rcp_options['enable_terms'] ) ) : ?>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-terms-agreed"><?php _e( 'Agreed to Terms', 'rcp' ); ?></label>
				</th>
				<td>
					<?php
					$terms_agreed = get_user_meta( $member->ID, 'rcp_terms_agreed', true );
					if ( ! empty( $terms_agreed ) && is_array( $terms_agreed ) ) {
						foreach ( $terms_agreed as $terms_agreed_date ) {
							echo date_i18n( get_option( 'date_format' ) . ' H:i:s', $terms_agreed_date ) . '<br />';
						}
					} else {
						_e( 'None', 'rcp' );
					}
					?>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ( ! empty( $rcp_options['enable_privacy_policy'] ) ) : ?>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="rcp-privacy-policy-agreed"><?php _e( 'Agreed to Privacy Policy', 'rcp' ); ?></label>
					</th>
					<td>
						<?php
						$privacy_policy_agreed = get_user_meta( $member->ID, 'rcp_privacy_policy_agreed', true );
						if ( ! empty( $privacy_policy_agreed ) && is_array( $privacy_policy_agreed ) ) {
							foreach ( $privacy_policy_agreed as $privacy_policy_agreed_date ) {
								echo date_i18n( get_option( 'date_format' ) . ' H:i:s', $privacy_policy_agreed_date ) . '<br />';
							}
						} else {
							_e( 'None', 'rcp' );
						}
						?>
					</td>
				</tr>
			<?php endif; ?>
			<tr class="form-field">
				<th scope="row" valign="top">
					<?php _e( 'Discount codes used', 'rcp' ); ?>
				</th>
				<td>
					<?php
					$discounts = get_user_meta( $member->ID, 'rcp_user_discounts', true );
					if( $discounts ) {
						foreach( $discounts as $discount ) {
							if( is_string( $discount ) ) {
								echo $discount . '<br/>';
							}
						}
					} else {
						_e( 'None', 'rcp' );
					}
					?>
				</td>
			</tr>
			<?php do_action( 'rcp_edit_member_after', $member->ID ); ?>
		</tbody>
	</table>

	<h4><?php _e( 'Payments', 'rcp' ); ?></h4>
	<?php echo rcp_print_user_payments_formatted( $member->ID ); ?>

	<p class="submit">
		<input type="hidden" name="rcp-action" value="edit-member"/>
		<input type="hidden" name="user" value="<?php echo absint( urldecode( $_GET['edit_member'] ) ); ?>"/>
		<input type="submit" value="<?php _e( 'Update User Subscription', 'rcp' ); ?>" class="button-primary"/>
	</p>
	<?php wp_nonce_field( 'rcp_edit_member_nonce', 'rcp_edit_member_nonce' ); ?>
</form>
