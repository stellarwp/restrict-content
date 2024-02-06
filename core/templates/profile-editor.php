<?php
/**
 * Profile Editor Form
 *
 * This template is used to display the profile editor with [rcp_profile_editor]
 *
 * @link https://restrictcontentpro.com/knowledgebase/rcp_profile_editor
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/Profile Editor
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

global $rcp_load_css;
$current_user = wp_get_current_user();
$rcp_load_css = true;

if ( is_user_logged_in() ):
	$user_id      = get_current_user_id();
	$first_name   = get_user_meta( $user_id, 'first_name', true );
	$last_name    = get_user_meta( $user_id, 'last_name', true );
	$display_name = $current_user->display_name;

	do_action( 'rcp_profile_editor_messages', $current_user );
	rcp_show_error_messages(); ?>
	<form id="rcp_profile_editor_form" class="rcp_form" action="<?php echo rcp_get_current_url(); ?>" method="post">
		<fieldset>
			<?php do_action( 'rcp_profile_editor_before', $current_user->ID ); ?>
			<legend><?php _e( 'Change your Name', 'rcp' ); ?></legend>
			<p id="rcp_profile_first_name_wrap">
				<label for="rcp_first_name"><?php _e( 'First Name', 'rcp' ); ?></label>
				<input name="rcp_first_name" id="rcp_first_name" class="text rcp-input" type="text" value="<?php echo esc_attr( $first_name ); ?>" />
			</p>
			<p id="rcp_profile_last_name_wrap">
				<label for="rcp_last_name"><?php _e( 'Last Name', 'rcp' ); ?></label>
				<input name="rcp_last_name" id="rcp_last_name" class="text rcp-input" type="text" value="<?php echo esc_attr( $last_name ); ?>" />
			</p>
			<p id="rcp_profile_display_name_wrap">
				<label for="rcp_display_name"><?php _e( 'Display Name', 'rcp' ); ?></label>
				<select name="rcp_display_name">
					<?php if ( ! empty( $current_user->first_name ) ): ?>
					<option <?php selected( $display_name, $current_user->first_name ); ?> value="<?php echo esc_attr( $current_user->first_name ); ?>"><?php echo $current_user->first_name; ?></option>
					<?php endif; ?>
					<option <?php selected( $display_name, $current_user->user_nicename ); ?> value="<?php echo esc_attr( $current_user->user_nicename ); ?>"><?php echo $current_user->user_nicename; ?></option>
					<?php if ( ! empty( $current_user->last_name ) ): ?>
					<option <?php selected( $display_name, $current_user->last_name ); ?> value="<?php echo esc_attr( $current_user->last_name ); ?>"><?php echo $current_user->last_name; ?></option>
					<?php endif; ?>
					<?php if ( ! empty( $current_user->first_name ) && ! empty( $current_user->last_name ) ): ?>
					<option <?php selected( $display_name, $current_user->first_name . ' ' . $current_user->last_name ); ?> value="<?php echo esc_attr( $current_user->first_name ) . ' ' . esc_attr( $current_user->last_name ); ?>"><?php echo $current_user->first_name . ' ' . $current_user->last_name; ?></option>
					<option <?php selected( $display_name, $current_user->last_name . ' ' . $current_user->first_name ); ?> value="<?php echo esc_attr( $current_user->last_name ) . ' ' . esc_attr( $current_user->first_name ); ?>"><?php echo $current_user->last_name . ' ' . $current_user->first_name; ?></option>
					<?php endif; ?>
				</select>
			</p>
			<p>
				<label for="rcp_email"><?php _e( 'Email Address', 'rcp' ); ?></label>
				<input name="rcp_email" id="rcp_email" class="text rcp-input required" type="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" />
			</p>
			<?php do_action( 'rcp_profile_editor_after', $current_user->ID ); ?>
        </fieldset>
        <fieldset>
			<legend><?php _e( 'Change your Password', 'rcp' ); ?></legend>
			<p id="rcp_profile_password_wrap">
				<label for="rcp_user_pass"><?php _e( 'New Password', 'rcp' ); ?></label>
				<input name="rcp_new_user_pass1" id="rcp_new_user_pass1" class="password rcp-input" type="password"/>
			</p>
			<p id="rcp_profile_password_confirm_wrap">
				<label for="rcp_user_pass"><?php _e( 'Re-enter Password', 'rcp' ); ?></label>
				<input name="rcp_new_user_pass2" id="rcp_new_user_pass2" class="password rcp-input" type="password"/>
			</p>
        </fieldset>
        <fieldset>
			<p id="rcp_profile_submit_wrap">
				<input type="hidden" name="rcp_profile_editor_nonce" value="<?php echo wp_create_nonce( 'rcp-profile-editor-nonce' ); ?>"/>
				<input type="hidden" name="rcp_action" value="edit_user_profile" />
				<input type="hidden" name="rcp_redirect" value="<?php echo esc_url( rcp_get_current_url() ); ?>" />
				<input name="rcp_profile_editor_submit" id="rcp_profile_editor_submit" type="submit" class="rcp_submit rcp-button" value="<?php esc_attr_e( 'Save Changes', 'rcp' ); ?>"/>
			</p>
		</fieldset>
	</form><!-- #rcp_profile_editor_form -->
	<?php
else:
	echo rcp_login_form_fields();
endif;