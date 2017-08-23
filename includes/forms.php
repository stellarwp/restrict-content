<?php
/**
 * Forms
 *
 * @package    Restrict Content
 * @subpackage Forms
 * @copyright  Copyright (c) 2017, Sandhills Development, LLC
 * @license    GPLv2
 */

/**
 * Display login form fields
 *
 * Based on the requested action, this could be the lost password form,
 * lost password "check your email" message, change password form,
 * or the login form template.
 *
 * @param array $args Arguments to override the defaults (including redirect URL).
 *
 * @return string HTML content
 */
function rc_login_form_fields( array $args = array() ) {

	$defaults = array( 'redirect' => rc_get_current_url() );

	$args = wp_parse_args( $args, $defaults );

	if ( ! empty( $_GET['redirect'] ) ) {
		$args['redirect'] = urldecode( $_GET['redirect'] );
	}

	ob_start();

	if ( isset($_REQUEST['rc_action']) && $_REQUEST['rc_action'] === "lostpassword") {

		echo rc_lost_password_form();

	} elseif ( isset($_REQUEST['rc_action']) && $_REQUEST['rc_action'] === "lostpassword_checkemail") {

		echo rc_lost_password_check_email_message();

	} elseif ( isset($_REQUEST['rc_action']) && ( $_REQUEST['rc_action'] === "lostpassword_reset" || $_REQUEST['rc_action'] === "reset-password" ) ) {

		echo rc_change_password_form();

	} else {

		if ( ! is_user_logged_in() ) :

			rc_show_error_messages( 'login' ); ?>

			<form id="rc_login_form"  class="<?php esc_attr_e( $args['class'] ); ?>" method="POST" action="<?php echo esc_url( rc_get_current_url() ); ?>">
				<fieldset class="rc_login_data">

					<p>
						<label for="rc_user_login"><?php _e( 'Username', 'restrict-content' ); ?></label>
						<input name="rc_user_login" id="rc_user_login" class="required" type="text"/>
					</p>

					<p>
						<label for="rc_user_pass"><?php _e( 'Password', 'restrict-content' ); ?></label>
						<input name="rc_user_pass" id="rc_user_pass" class="required" type="password"/>
					</p>

					<p>
						<input type="checkbox" name="rc_user_remember" id="rc_user_remember" value="1"/>
						<label for="rc_user_remember"><?php _e( 'Remember me', 'restrict-content' ); ?></label>
					</p>

					<p class="rc_lost_password">
						<a href="<?php echo esc_url( add_query_arg( 'rc_action', 'lostpassword') ); ?>">
							<?php _e( 'Lost your password?', 'restrict-content' ); ?>
						</a>
					</p>

					<p>
						<input type="hidden" name="rc_action" value="login"/>
						<input type="hidden" name="rc_redirect" value="<?php echo esc_url( $args['redirect'] ); ?>"/>
						<input type="hidden" name="rc_login_nonce" value="<?php echo wp_create_nonce( 'rc-login-nonce' ); ?>"/>
						<input id="rc_login_submit" type="submit" value="<?php esc_attr_e( 'Login', 'restrict-content' ); ?>"/>
					</p>

				</fieldset>
			</form>

		<?php else : ?>

			<div class="rc_logged_in"><a href="<?php echo wp_logout_url( home_url() ); ?>"><?php _e( 'Log out', 'restrict-content' ); ?></a></div>

		<?php endif;

	}

	return ob_get_clean();
}

/**
 * Processes the login form
 */
function rc_process_login_form() {

	if ( ! isset( $_POST['rc_action'] ) || 'login' !== $_POST['rc_action'] ) {
		return;
	}

	if ( ! isset( $_POST['rc_login_nonce'] ) || ! wp_verify_nonce( $_POST['rc_login_nonce'], 'rc-login-nonce' ) ) {
		return;
	}

	if ( is_email( $_POST['rc_user_login'] ) && ! username_exists( $_POST['rc_user_login'] ) ) {

		$user = get_user_by( 'email', $_POST['rc_user_login'] );

	} else {

		$user = get_user_by( 'login', $_POST['rc_user_login'] );

	}

	if ( ! $user ) {
		rc_errors()->add( 'empty_username', __( 'Invalid username or email', 'restrict-content' ), 'login' );
	}

	if ( empty( $_POST['rc_user_pass'] ) ) {
		rc_errors()->add( 'empty_password', __( 'Please enter a password', 'restrict-content' ), 'login' );
	}

	if ( $user ) {
		// check the user's login with their password
		if ( ! wp_check_password( $_POST['rc_user_pass'], $user->user_pass, $user->ID ) ) {
			rc_errors()->add( 'empty_password', __( 'Incorrect password', 'restrict-content' ), 'login' );
		}
	}

	$errors = rc_errors()->get_error_messages();

	if ( empty( $errors ) ) {

		$redirect = ! empty( $_POST['rc_redirect'] ) ? $_POST['rc_redirect'] : home_url();

		wp_signon( array(
			'user_login' => $user->user_login,
			'user_password' => $_POST['rc_user_pass'],
			'remember' => isset( $_POST['rc_user_remember'] )
		) );

		wp_safe_redirect( esc_url_raw( $redirect ) );

		exit;
	}
}
add_action( 'init', 'rc_process_login_form' );