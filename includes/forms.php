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
 * Displays the login form fields
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

		echo rc_change_password_form( $args );

	} else {

		if ( ! is_user_logged_in() ) :

			rc_show_error_messages( 'login' ); ?>

			<form id="rc_login_form"  class="<?php esc_attr_e( $args['class'] ); ?>" method="POST" action="<?php echo esc_url( rc_get_current_url() ); ?>">
				<fieldset class="rc_login_data">

					<p>
						<label for="rc_user_login"><?php _e( 'Username', 'restrict-content' ); ?></label>
						<input name="rc_user_login" id="rc_user_login" class="required" type="text" />
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

/**
 * Displays the lost password form fields
 */
function rc_lost_password_form() {

	if ( ! is_user_logged_in() ) :

		rc_show_error_messages( 'lostpassword' ); ?>

		<form id="rc_lostpassword_form" class="rc_form" method="POST" action="<?php echo esc_url( add_query_arg( 'rc_action', 'lostpassword') ); ?>">
			<fieldset class="rc_lostpassword_data">
				<p>
					<label for="rc_user_login"><?php _e( 'Username or E-mail:', 'restrict-content' ); ?></label>
					<input name="rc_user_login" id="rc_user_login" class="required" type="text"/>
				</p>

				<p>
					<input type="hidden" name="rc_action" value="lostpassword"/>
					<input type="hidden" name="rc_redirect" value="<?php echo esc_url( rc_get_current_url() ); ?>"/>
					<input type="hidden" name="rc_lostpassword_nonce" value="<?php echo wp_create_nonce( 'rc-lostpassword-nonce' ); ?>"/>
					<input id="rc_lostpassword_submit" type="submit" value="<?php esc_attr_e( 'Request Password Reset', 'restrict-content' ); ?>"/>
				</p>
			</fieldset>
		</form>

		<?php else : ?>

			<div class="rc_logged_in">
				<a href="<?php echo wp_logout_url( home_url() ); ?>">
					<?php _e( 'Log out', 'restrict-content' ); ?>
				</a>
			</div>

		<?php endif;
}

/**
 * Processes the lost password form
 */
function rc_process_lost_password_form() {

	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['rc_action'] ) || 'lostpassword' !== $_POST['rc_action'] ) {
		return;
	}

	if ( ! isset( $_POST['rc_lostpassword_nonce'] ) || ! wp_verify_nonce( $_POST['rc_lostpassword_nonce'], 'rc-lostpassword-nonce' ) ) {
		return;
	}

	$errors = rc_send_password_reset_email();

	if ( ! is_wp_error( $errors ) ) {
		$redirect_to = esc_url($_POST['rc_redirect']) . '?rc_action=lostpassword_checkemail';
		wp_redirect( $redirect_to );
		exit();
	}
}
add_action( 'init', 'rc_process_lost_password_form' );

/**
 * Sends the password reset email.
 *
 * @return bool
 */
function rc_send_password_reset_email() {

	if ( empty( $_POST['rc_user_login'] ) ) {

		rc_errors()->add( 'empty_username', __( 'Enter a username or email address.', 'restrict-content' ), 'lostpassword' );
		return false;

	} elseif ( is_email( $_POST['rc_user_login'] ) ) {

		$user_data = get_user_by( 'email', trim( $_POST['rc_user_login'] ) );

		if ( empty( $user_data ) ) {
			rc_errors()->add( 'invalid_email', __( 'There is no user registered with that email address.', 'restrict-content' ), 'lostpassword' );
		}

	} else {

		$login = trim( $_POST['rc_user_login'] );

		$user_data = get_user_by( 'login', $login );

	}

	if ( rc_errors()->get_error_code() ) {
		return false;
	}

	if ( ! $user_data ) {
		rc_errors()->add( 'invalidcombo', __('Invalid username or e-mail.', 'restrict-content' ), 'lostpassword' );
		return false;
	}

	if ( is_multisite() && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
		rc_errors()->add( 'invalidcombo', __('Invalid username or e-mail.', 'restrict-content' ), 'lostpassword' );
		return false;
	}

	$user_login = $user_data->user_login;

	do_action( 'retrieve_password', $user_login );

	$key = get_password_reset_key( $user_data );

	$message = __( 'Someone requested that the password be reset for the following account:', 'restrict-content' ) . "\r\n\r\n";
	$message .= network_home_url( '/' ) . "\r\n\r\n";
	$message .= sprintf( __( 'Username: %s', 'restrict-content' ), $user_login ) . "\r\n\r\n";
	$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.', 'restrict-content' ) . "\r\n\r\n";
	$message .= __( 'To reset your password, visit the following address:', 'restrict-content' ) . "\r\n\r\n";
	$message .= esc_url_raw( add_query_arg( array( 'rc_action' => 'lostpassword_reset', 'key' => $key, 'login' => rawurlencode( $user_login ) ), $_POST['rc_redirect'] ) ) . "\r\n";

	if ( is_multisite() ) {

		$blogname = $GLOBALS['current_site']->site_name;

	} else {
		/*
		 * The blogname option is escaped with esc_html on the way into the database
		 * in sanitize_option we want to reverse this for the plain text arena of emails.
		 */
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	$sent = wp_mail(
		stripslashes( $user_data->user_email ),
		sprintf( __( '[%s] Password Reset', 'restrict-content' ), $blogname ),
		$message
	);

	if ( ! $sent ) {
		wp_die( __( 'The email could not be sent. If this problem persists, please contact support.', 'restrict-content' ) );
	}

	return true;
}

/**
 * Displays the check email message on the lost password form.
 */
function rc_lost_password_check_email_message() {

	if ( ! is_user_logged_in() ) : ?>

		<div class="rc-message">
			<p class="rc-info">
				<?php esc_html_e( 'Check your e-mail for the confirmation link.', 'restrict-content' ); ?>
			</p>
		</div>

	<?php else : ?>

		<div class="rc_logged_in">
			<a href="<?php echo wp_logout_url( home_url() ); ?>">
				<?php _e( 'Log out', 'restrict-content' ); ?>
			</a>
		</div>

	<?php endif;
}

/**
 * Displays the change password form fields.
 *
 * @param array $args Arguments to override the defaults.
 *
 * @return string
 */
function rc_change_password_form( $args = array() ) {

	$args = wp_parse_args( $args, array( 'redirect' => rc_get_current_url() ) );

	rc_show_error_messages( 'password' );

	if ( isset( $_GET['password-reset'] ) && $_GET['password-reset'] == 'true' ) : ?>
		<div class="rc_message success">
			<span><?php _e( 'Password changed successfully', 'restrict-content' ); ?></span>
		</div>
	<?php endif; ?>

	<form id="rc_password_form"  class="rc_form" method="POST" action="<?php echo esc_url( rc_get_current_url() ); ?>">
		<fieldset class="rc_change_password_fieldset">
			<p>
				<label for="rc_user_pass"><?php esc_html_e( 'New Password', 'restrict-content' ); ?></label>
				<input name="rc_user_pass" id="rc_user_pass" class="required" type="password"/>
			</p>
			<p>
				<label for="rc_user_pass_confirm"><?php esc_html_e( 'Password Confirm', 'restrict-content' ); ?></label>
				<input name="rc_user_pass_confirm" id="rc_user_pass_confirm" class="required" type="password"/>
			</p>
			<p>
				<?php
				$key = ! empty( $_GET['key'] ) ? $_GET['key'] : false;
				$login = ! empty( $_GET['login'] ) ? $_GET['login'] : false;
				?>
				<input type="hidden" name="rc_action" value="reset-password"/>
				<input type="hidden" name="rc_password_reset_key" value="<?php echo esc_attr( $key ); ?>"/>
				<input type="hidden" name="rc_password_reset_login" value="<?php echo esc_attr( $login ); ?>"/>
				<input type="hidden" name="rc_redirect" value="<?php echo esc_url( $args['redirect'] ); ?>"/>
				<input type="hidden" name="rc_password_nonce" value="<?php echo wp_create_nonce('rc-password-nonce' ); ?>"/>
				<input id="rc_password_submit" type="submit" value="<?php esc_attr_e( 'Change Password', 'restrict-content' ); ?>"/>
			</p>
		</fieldset>
	</form>

	<?php
}

/**
 * Changes a user's password on the password reset form.
 */
function rc_process_change_password_form() {

	if ( ! isset( $_POST['rc_action'] ) || 'reset-password' !== $_POST['rc_action'] ) {
		return;
	}

	if ( ! isset( $_POST['rc_password_nonce'] ) || ! wp_verify_nonce( $_POST['rc_password_nonce'], 'rc-password-nonce' ) ) {
		return;
	}

	$user = check_password_reset_key( sanitize_text_field( $_POST['rc_password_reset_key'] ), sanitize_text_field( $_POST['rc_password_reset_login'] ) );

	if ( $user instanceof WP_User ) {

		if ( ! isset( $_POST['rc_user_pass'] ) || ! isset( $_POST['rc_user_pass_confirm'] ) ) {
			rc_errors()->add( 'password_empty', __( 'Please enter a password and confirm it.', 'restrict-content' ), 'password' );
		}

		if ( $_POST['rc_user_pass'] !== $_POST['rc_user_pass_confirm'] ) {
			rc_errors()->add( 'password_mismatch', __( 'Passwords do not match.', 'restrict-content' ), 'password' );
		}

		$errors = rc_errors()->get_error_messages();

		if ( empty( $errors ) ) {

			wp_set_password( $_POST['rc_user_pass'], $user->ID );

			setcookie(
				'wp-resetpass-' . COOKIEHASH,
				'',
				0,
				current( explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) ) ),
				COOKIE_DOMAIN,
				is_ssl(),
				true
			);

			wp_password_change_notification( $user );

			wp_safe_redirect( add_query_arg( 'password-reset', 'true', $_POST['rc_redirect'] ) );
			exit;
		}

	}
}
add_action( 'init', 'rc_process_change_password_form' );