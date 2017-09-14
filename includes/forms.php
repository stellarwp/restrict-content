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
 * @since 2.2
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

	if ( isset( $_REQUEST['rc_action'] ) && $_REQUEST['rc_action'] === "lostpassword" ) {

		echo rc_lost_password_form();

	} elseif ( isset( $_REQUEST['rc_action'] ) && $_REQUEST['rc_action'] === "lostpassword_checkemail" ) {

		echo rc_lost_password_check_email_message();

	} elseif ( isset( $_REQUEST['rc_action'] ) && ( $_REQUEST['rc_action'] === "lostpassword_reset" || $_REQUEST['rc_action'] === "reset-password" ) ) {

		echo rc_change_password_form( $args );

	} else {

		if ( ! is_user_logged_in() ) :

			rc_show_error_messages( 'login' );

			if ( isset( $_GET['password-reset'] ) && $_GET['password-reset'] == 'true' ) : ?>
				<div class="rc-message">
					<p class="rc-success"><?php _e( 'Password changed successfully. Please log in with your new password.', 'restrict-content' ); ?></p>
				</div>
			<?php endif; ?>

			<form id="rc_login_form"  class="<?php echo esc_attr( $args['class'] ); ?>" method="POST" action="<?php echo esc_url( rc_get_current_url() ); ?>">
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
 *
 * @since 2.2
 */
function rc_process_login_form() {

	if ( ! isset( $_POST['rc_action'] ) || 'login' !== $_POST['rc_action'] ) {
		return;
	}

	if ( ! isset( $_POST['rc_login_nonce'] ) || ! wp_verify_nonce( $_POST['rc_login_nonce'], 'rc-login-nonce' ) ) {
		return;
	}

	if ( is_email( $_POST['rc_user_login'] ) && ! username_exists( $_POST['rc_user_login'] ) ) {

		$user = get_user_by( 'email', sanitize_email( $_POST['rc_user_login'] ) );

	} else {

		$user = get_user_by( 'login', sanitize_user( $_POST['rc_user_login'] ) );

	}

	if ( ! $user ) {
		rc_errors()->add( 'empty_username', __( 'Invalid username or email', 'restrict-content' ), 'login' );
	}

	if ( empty( $_POST['rc_user_pass'] ) ) {
		rc_errors()->add( 'empty_password', __( 'Please enter a password', 'restrict-content' ), 'login' );
	}

	if ( $user && ! wp_check_password( $_POST['rc_user_pass'], $user->user_pass, $user->ID ) ) {
		rc_errors()->add( 'empty_password', __( 'Incorrect password', 'restrict-content' ), 'login' );
	}

	$errors = rc_errors()->get_error_messages();

	if ( empty( $errors ) ) {

		$redirect = ! empty( $_POST['rc_redirect'] ) ? $_POST['rc_redirect'] : home_url();

		wp_signon( array(
			'user_login'    => $user->user_login,
			'user_password' => $_POST['rc_user_pass'],
			'remember'      => isset( $_POST['rc_user_remember'] )
		) );

		wp_safe_redirect( esc_url_raw( $redirect ) );

		exit;
	}
}
add_action( 'init', 'rc_process_login_form' );

/**
 * Displays the lost password form fields
 *
 * @since 2.2
 */
function rc_lost_password_form() {

	if ( ! is_user_logged_in() ) :

		if ( isset( $_GET['rc_key_error'] ) && 'invalidkey' === $_GET['rc_key_error'] ) {

			rc_errors()->add( 'password_expired_key', __( 'Your password reset key is invalid. Please try again.', 'restrict-content' ), 'lostpassword' );

		} elseif ( isset( $_GET['rc_key_error'] ) && 'expiredkey' === $_GET['rc_key_error'] ) {

			rc_errors()->add( 'password_expired_key', __( 'Your password reset key has expired. Please try again.', 'restrict-content' ), 'lostpassword' );

		}

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
 *
 * @since 2.2
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
		$redirect_to = esc_url( $_POST['rc_redirect'] ) . '?rc_action=lostpassword_checkemail';
		wp_redirect( $redirect_to );
		exit();
	}
}
add_action( 'init', 'rc_process_lost_password_form' );

/**
 * Sends the password reset email.
 *
 * @since 2.2
 *
 * @return bool
 */
function rc_send_password_reset_email() {

	if ( empty( $_POST['rc_user_login'] ) ) {

		rc_errors()->add( 'empty_username', __( 'Enter a username or email address.', 'restrict-content' ), 'lostpassword' );
		return false;

	}

	$user_login = sanitize_text_field( $_POST['rc_user_login'] );

	if ( is_email( $user_login ) ) {

		$user_data = get_user_by( 'email', trim( $user_login ) );

		if ( empty( $user_data ) ) {
			rc_errors()->add( 'invalid_email', __( 'There is no user registered with that email address.', 'restrict-content' ), 'lostpassword' );
		}

	} else {

		$user_data = get_user_by( 'login', trim( $user_login ) );

	}

	if ( rc_errors()->get_error_code() ) {
		return false;
	}

	if ( ! $user_data ) {
		rc_errors()->add( 'invalidcombo', __( 'Invalid username or e-mail.', 'restrict-content' ), 'lostpassword' );
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
 *
 * @since 2.2
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
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
				<?php _e( 'Log out', 'restrict-content' ); ?>
			</a>
		</div>

	<?php endif;
}

/**
 * Displays the change password form fields.
 *
 * @since 2.2
 * @param array $args Arguments to override the defaults.
 *
 * @return string
 */
function rc_change_password_form( $args = array() ) {

	$args = wp_parse_args( $args, array( 'redirect' => rc_get_current_url() ) );

	rc_show_error_messages( 'password' ); ?>

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
				' ',
				time() - YEAR_IN_SECONDS,
				current( explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) ) ),
				COOKIE_DOMAIN,
				is_ssl(),
				true
			);

			wp_password_change_notification( $user );

			wp_safe_redirect( add_query_arg( 'password-reset', 'true', $_POST['rc_redirect'] ) );
			exit;
		}

	} elseif ( is_wp_error( $user ) ) {

		if ( 'expired_key' === $user->get_error_code() ) {
			wp_safe_redirect( esc_url( $_POST['rc_redirect'] ) . '?rc_action=lostpassword&rc_key_error=expiredkey' );
		} else {
			wp_safe_redirect( esc_url( $_POST['rc_redirect'] ) . '?rc_action=lostpassword&rc_key_error=invalidkey' );
		}

	}
}
add_action( 'init', 'rc_process_change_password_form' );

/**
 * Displays the register form fields.
 *
 * @since 2.2
 * @param array $args
 *
 * @return string
 */
function rc_register_form_fields( array $args = array() ) {

	$args = wp_parse_args(
		$args,
		array(
			'redirect'           => rc_get_current_url(),
			'class'              => 'rc_form',
			'registered_message' => __( 'You are already registered.', 'restrict-content' ),
			'logged_out_header'  => __( 'Register New Account', 'restrict-content' )
		)
	);

	if ( ! empty( $_GET['redirect'] ) ) {
		$args['redirect'] = urldecode( $_GET['redirect'] );
	}

	ob_start();

	$header = ! is_user_logged_in() ? $args['logged_out_header'] : $args['registered_message'] ?>

	<h3 class="rc_header">
		<?php echo esc_html( $header ); ?>
	</h3>

	<?php rc_show_error_messages( 'register' );

	if ( ! is_user_logged_in() ) :

	?>

		<form id="rc_registration_form" class="<?php echo esc_attr( $args['class'] ); ?>" method="post" action="<?php echo esc_url( rc_get_current_url() ); ?>">
			<fieldset class="rc_user_fieldset">
				<p id="rc_user_login_wrap">
					<label for="rc_user_login"><?php _e( 'Username', 'restrict-content' ); ?></label>
					<input name="rc_user_login" id="rc_user_login" class="required" type="text" <?php if( isset( $_POST['rc_user_login'] ) ) { echo 'value="' . esc_attr( $_POST['rc_user_login'] ) . '"'; } ?> required/>
				</p>
				<p id="rc_user_email_wrap">
					<label for="rc_user_email"><?php _e( 'Email', 'restrict-content' ); ?></label>
					<input name="rc_user_email" id="rc_user_email" class="required" type="text" <?php if( isset( $_POST['rc_user_email'] ) ) { echo 'value="' . esc_attr( $_POST['rc_user_email'] ) . '"'; } ?> required/>
				</p>
				<p id="rc_user_first_wrap">
					<label for="rc_user_first"><?php _e( 'First Name', 'restrict-content' ); ?></label>
					<input name="rc_user_first" id="rc_user_first" type="text" <?php if( isset( $_POST['rc_user_first'] ) ) { echo 'value="' . esc_attr( $_POST['rc_user_first'] ) . '"'; } ?>/>
				</p>
				<p id="rc_user_last_wrap">
					<label for="rc_user_last"><?php _e( 'Last Name', 'restrict-content' ); ?></label>
					<input name="rc_user_last" id="rc_user_last" type="text" <?php if( isset( $_POST['rc_user_last'] ) ) { echo 'value="' . esc_attr( $_POST['rc_user_last'] ) . '"'; } ?>/>
				</p>
				<p id="rc_password_wrap">
					<label for="rc_password"><?php _e( 'Password', 'restrict-content' ); ?></label>
					<input name="rc_user_pass" id="rc_password" class="required" type="password" required/>
				</p>
				<p id="rc_password_again_wrap">
					<label for="rc_password_again"><?php _e( 'Password Again', 'restrict-content' ); ?></label>
					<input name="rc_user_pass_confirm" id="rc_password_again" class="required" type="password" required/>
				</p>
			</fieldset>

			<p id="rc_submit_wrap">
				<input type="hidden" name="rc_register_nonce" id="rc_register_nonce" value="<?php echo wp_create_nonce( 'rc-register-nonce' ); ?>"/>
				<input type="hidden" name="rc_redirect" id="rc_redirect" value="<?php echo esc_url( $args['redirect'] ); ?>"/>
				<input type="submit" name="rc_submit_registration" id="rc_submit_registration" value="<?php esc_attr_e( 'Register', 'restrict-content' ); ?>"/>
			</p>
		</form>
		<?php
	endif;
	return ob_get_clean();
}

/**
 * Processes the registration form and creates the user's account.
 *
 * @since 2.2
 */
function rc_process_registration_form() {

	if ( ! isset( $_POST['rc_register_nonce'] ) || ! wp_verify_nonce( $_POST['rc_register_nonce'], 'rc-register-nonce' ) ) {
		return;
	}

	$user_data = rc_validate_user_data( $_POST );

	$errors = rc_errors()->get_error_messages();

	if ( ! empty( $errors ) ) {

		wp_send_json_error( array(
			'success' => false,
			'errors' => rc_get_error_messages_html( 'register' ),
		) );

		return;

	}

	if ( $user_data['need_new'] ) {

		$display_name = $user_data['first_name'] . ' ' . $user_data['last_name'];
		$display_name = ! empty( $display_name ) ? $display_name : $user_data['login'];

		$user_data['id'] = wp_insert_user( array(
			'user_login'    => $user_data['login'],
			'user_pass'     => $user_data['password'],
			'user_email'    => $user_data['email'],
			'first_name'    => $user_data['first_name'],
			'last_name'     => $user_data['last_name'],
			'display_name'  => $display_name,
			'user_register' => date( 'Y-m-d H:i:s' ),
		) );
	}

	if ( empty( $user_data['id'] ) ) {
		return;
	}

	wp_signon( array(
		'user_login' => $user_data['login'],
		'user_password' => $user_data['password']
	) );

	$redirect_url = ! empty( $_POST['rc_redirect'] ) ? esc_url( $_POST['rc_redirect'] ) : esc_url( home_url() );

	wp_send_json_success( array(
		'success'  => true,
		'user'     => $user_data,
		'redirect' => $redirect_url
	) );

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'init', 'rc_process_registration_form' );
add_action( 'wp_ajax_rc_process_registration_form', 'rc_process_registration_form' );
add_action( 'wp_ajax_nopriv_rc_process_registration_form', 'rc_process_registration_form' );

/**
 * Validates the user data for registration.
 *
 * @since 2.2
 * @param array $data
 *
 * @return array
 */
function rc_validate_user_data( array $data ) {

	$user = array();

	$data = array_map( 'trim', $data );

	if ( ! is_user_logged_in() ) {

		$user['need_new'] = true;
		$user['id'] = 0;
		$user['login'] = ! empty( $data['rc_user_login'] ) ? sanitize_text_field( $data['rc_user_login'] ) : false;
		$user['email'] = ! empty( $data['rc_user_email'] ) ? sanitize_email( $data['rc_user_email'] ) : false;
		$user['first_name'] = ! empty( $data['rc_user_first'] ) ? sanitize_text_field( $data['rc_user_first'] ) : false;
		$user['last_name'] = ! empty( $data['rc_user_last'] ) ? sanitize_text_field( $data['rc_user_last'] ) : false;
		$user['password'] = ! empty( $data['rc_user_pass'] ) ? sanitize_text_field( $data['rc_user_pass'] ) : false;
		$user['password_confirm'] = ! empty( $data['rc_user_pass_confirm'] ) ? sanitize_text_field( $data['rc_user_pass_confirm'] ) : false;

	} else {

		$user_data = get_userdata( get_current_user_id() );

		$user['need_new'] = false;
		$user['id'] = $user_data->ID;
		$user['login'] = $user_data->user_login;
		$user['email'] = $user_data->user_email;
	}

	if ( $user['need_new'] ) {

		if ( empty( $user['login'] ) ) {
			rc_errors()->add( 'username_empty', __( 'Please enter a username', 'restrict-content' ), 'register' );
		}

		if ( username_exists( $user['login'] ) ) {
			rc_errors()->add( 'username_unavailable', __( 'Username already taken', 'restrict-content' ), 'register' );
		}

		$sanitized_username = sanitize_user( $user['login'], false );
		if ( strtolower( $sanitized_username ) !== strtolower( $user['login'] ) ) {
			rc_errors()->add( 'username_invalid', __( 'Invalid username', 'restrict-content' ), 'register' );
		}

		if ( ! is_email( $user['email'] ) ) {
			rc_errors()->add( 'email_invalid', __( 'Invalid email', 'restrict-content' ), 'register' );
		}

		if ( email_exists( $user['email'] ) ) {
			rc_errors()->add( 'email_used', __( 'Email already registered', 'restrict-content' ), 'register' );
		}

		if ( empty( $user['password'] ) ) {
			rc_errors()->add( 'password_empty', __( 'Please enter a password', 'restrict-content' ), 'register' );
		}

		if ( $user['password'] !== $user['password_confirm'] ) {
			rc_errors()->add( 'password_mismatch', __( 'Passwords do not match', 'restrict-content' ), 'register' );
		}
	}

	return $user;
}