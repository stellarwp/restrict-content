<?php
/**
 * Change Password Form
 *
 * This form is for changing an account password.
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/Change Password Form
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

global $rcp_password_form_args; ?>

<?php
rcp_show_error_messages( 'password' );

// Bail if the reset link is invalid. This prevents the change password fields from showing.
$errors = rcp_errors();
if ( rcp_errors()->get_error_data( 'invalid_key' ) === 'password' ) {
	return;
}
?>

<?php if( isset( $_GET['password-reset']) && $_GET['password-reset'] == 'true') { ?>
	<div class="rcp_message success">
		<span><?php _e( 'Password changed successfully', 'rcp' ); ?></span>
	</div>
<?php } ?>
<form id="rcp_password_form"  class="rcp_form" method="POST" action="<?php echo esc_url( rcp_get_current_url() ); ?>">
	<fieldset class="rcp_change_password_fieldset">
		<p>
			<label for="rcp_user_pass"><?php echo apply_filters( 'rcp_registration_new_password_label', __( 'New Password', 'rcp' ) ); ?></label>
			<input name="rcp_user_pass" id="rcp_user_pass" class="required" type="password"/>
		</p>
		<p>
			<label for="rcp_user_pass_confirm"><?php echo apply_filters( 'rcp_registration_confirm_password_label', __( 'Password Confirm', 'rcp' ) ); ?></label>
			<input name="rcp_user_pass_confirm" id="rcp_user_pass_confirm" class="required" type="password"/>
		</p>
		<p>
			<input type="hidden" name="rcp_action" value="reset-password"/>
			<input type="hidden" name="rcp_redirect" value="<?php echo esc_url( $rcp_password_form_args['redirect'] ); ?>"/>
			<input type="hidden" name="rcp_password_nonce" value="<?php echo wp_create_nonce('rcp-password-nonce' ); ?>"/>
			<input id="rcp_password_submit" class="rcp-button" type="submit" value="<?php esc_attr_e( apply_filters( 'rcp_registration_change_password_button', __( 'Change Password', 'rcp' ) ) ); ?>"/>
		</p>
	</fieldset>
</form>