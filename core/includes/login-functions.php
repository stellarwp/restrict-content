<?php
/**
 * Login Functions
 *
 * Processes the login forms and also the login process during registration
 *
 * @package     Restrict Content Pro
 * @subpackage  Login Functions
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieves the login URl with an optional redirect
 *
 * @param string $redirect URL to redirect to after login (optional).
 *
 * @since  2.1
 * @return string
 */
function rcp_get_login_url( $redirect = '' ) {

	global $rcp_options;

	if( isset( $rcp_options['hijack_login_url'] ) && ! empty( $rcp_options['login_redirect'] ) ) {

		$url = add_query_arg( 'redirect', urlencode( $redirect ), get_permalink( absint( $rcp_options['login_redirect'] ) ) );

	} else {

		$url = wp_login_url( $redirect );

	}

	return apply_filters( 'rcp_login_url', $url, $redirect );

}

/**
 * Process the login form
 *
 * @uses rcp_login_user_in()
 *
 * @since  1.0
 * @return void
 */
function rcp_process_login_form() {

	if( ! isset( $_POST['rcp_action'] ) || 'login' != $_POST['rcp_action'] ) {
		return;
	}

	if( ! isset( $_POST['rcp_login_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_login_nonce'], 'rcp-login-nonce' ) ) {
		return;
	}

	if( is_email( $_POST['rcp_user_login'] ) && ! username_exists( $_POST['rcp_user_login'] ) ) {

		$user = get_user_by( 'email', $_POST['rcp_user_login'] );

	} else {

		// this returns the user ID and other info from the user name
		$user = get_user_by( 'login', $_POST['rcp_user_login'] );

	}

	/**
	 * Executes before error checks are performed.
	 *
	 * @param array $_POST Posted data.
	 */
	do_action( 'rcp_before_form_errors', $_POST );

	if( ! $user ) {
		// if the user name doesn't exist
		rcp_errors()->add( 'empty_username', __( 'Invalid username or email', 'rcp' ), 'login' );
	}

	if( ! isset( $_POST['rcp_user_pass'] ) || $_POST['rcp_user_pass'] == '') {
		// if no password was entered
		rcp_errors()->add( 'empty_password', __( 'Please enter a password', 'rcp' ), 'login' );
	}

	/**
	 * Third party plugins can use this action to add additional error checks and messages.
	 *
	 * @param array $_POST Posted data.
	 */
	do_action( 'rcp_login_form_errors', $_POST );

	// Retrieve all error messages. At this point these are only the errors added via the above hook.
	$errors = rcp_errors()->get_error_messages();

	// Exit early if we have errors from third party plugins.
	if ( ! empty( $errors ) ) {
		return;
	}

	// Now we can attempt the login.
	$user = wp_signon( array(
		'user_login'    => $user->user_login,
		'user_password' => $_POST['rcp_user_pass'],
		'remember'      => isset( $_POST['rcp_user_remember'] )
	) );

	// Add error message if the login failed.
	if ( is_wp_error( $user ) ) {
		rcp_errors()->add( $user->get_error_code(), $user->get_error_message(), 'login' );
	}

	// Refresh error messages.
	$errors = rcp_errors()->get_error_messages();

	// Redirect if the login was successful.
	if( empty( $errors ) ) {

		$redirect = ! empty( $_POST['rcp_redirect'] ) ? $_POST['rcp_redirect'] : home_url();

		// redirect the user back to the page they were previously on
		wp_safe_redirect( apply_filters( 'rcp_login_redirect_url', esc_url_raw( $redirect ), $user ) ); exit;

	} else {

		// Page will refresh with errors shown.

	}
}
add_action('init', 'rcp_process_login_form');

/**
 * Process the password reset. adapted from wp-login.php
 *
 * @since  2.3
 * @return void
 */
function rcp_process_lostpassword_reset() {

	if( ! isset( $_GET['rcp_action'] ) || 'lostpassword_reset' != $_GET['rcp_action'] ) {
		return;
	}

	nocache_headers();

	list( $rp_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
	$rp_cookie = apply_filters( 'rcp_resetpass_cookie_name', 'rcp-resetpass-' . COOKIEHASH );

	// store reset key and login name in cookie & remove from URL
	if ( isset( $_GET['key'] ) ) {
		$value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['key'] ) );
		setcookie( $rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
		wp_safe_redirect( remove_query_arg( array( 'key', 'login' ) ) );
		exit;
	}

	// check if the reset key and login name are valid
	if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
		list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
		$user = check_password_reset_key( $rp_key, $rp_login );
	} else {
		$user = false;
	}

	if ( ! $user || is_wp_error( $user ) ) {
		setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
		if ( $user && $user->get_error_code() === 'expired_key' ) {
			rcp_errors()->add( 'expired_key', __('Your password reset link has expired.', 'rcp'), 'password' );
		} else {
			rcp_errors()->add( 'invalid_key', __('Your password reset link appears to be invalid.', 'rcp'), 'password' );
		}
	}

}

add_action('init', 'rcp_process_lostpassword_reset');

/**
 * Process the lost password form
 *
 * @uses rcp_retrieve_password()
 *
 * @since  2.3
 * @return void
 */
function rcp_process_lostpassword_form() {

	if( ! isset( $_POST['rcp_action'] ) || 'lostpassword' != $_POST['rcp_action'] ) {
		return;
	}

	if( ! isset( $_POST['rcp_lostpassword_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_lostpassword_nonce'], 'rcp-lostpassword-nonce' ) ) {
		return;
	}

	$errors = rcp_retrieve_password();

	if ( ! is_wp_error( $errors ) ) {
		$redirect_to = esc_url($_POST['rcp_redirect']) . '?rcp_action=lostpassword_checkemail';
		wp_redirect( $redirect_to );
		exit();
	}
}
add_action('init', 'rcp_process_lostpassword_form');

/**
 * Send password reset email to user. Adapted from wp-login.php
 *
 * @since  2.3
 * @return WP_Error|bool True if successful.
 */
function rcp_retrieve_password() {
	global $wpdb, $wp_hasher, $wp_db_version;

	if ( empty( $_POST['rcp_user_login'] ) ) {
		rcp_errors()->add( 'empty_username', __( 'Enter a username or e-mail address.', 'rcp' ), 'lostpassword' );
	} elseif ( strpos( $_POST['rcp_user_login'], '@' ) ) {
		$user_data = get_user_by( 'email', trim( $_POST['rcp_user_login'] ) );
		if ( empty( $user_data ) ) {
			rcp_errors()->add( 'invalid_email', __( 'There is no user registered with that email address.', 'rcp' ), 'lostpassword' );
		}
	} else {
		$login = trim($_POST['rcp_user_login']);
		$user_data = get_user_by('login', $login);
	}

	if ( rcp_errors()->get_error_code() ) {
		return rcp_errors();
	}

	if ( empty( $user_data ) ) {
		rcp_errors()->add('invalidcombo', __('Invalid username or e-mail.', 'rcp' ), 'lostpassword');
		return rcp_errors();
	}

	// Redefining user_login ensures we return the right case in the email.
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;

	/**
	 * Fires when a user is initializing the reset password flow.
	 *
	 * @since 3.4.3
	 *
	 * @param array   $_POST     The form $_POST data.
	 * @param WP_User $user_data The user whose password reset is being requested.
	 */
	do_action( 'rcp_retrieve_password_form_errors', $_POST, $user_data );

	if ( rcp_errors()->get_error_code() ) {
		return rcp_errors();
	}

	$key = get_password_reset_key( $user_data );

	if ( is_wp_error( $key ) ) {
		rcp_errors()->add( $key->get_error_code(), $key->get_error_message() );

		return rcp_errors();
	}

	$message = __('Someone requested that the password be reset for the following account:', 'rcp') . "\r\n\r\n";
	$message .= network_home_url( '/' ) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s', 'rcp'), $user_login) . "\r\n\r\n";
	$message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'rcp') . "\r\n\r\n";
	$message .= __('To reset your password, visit the following address:', 'rcp') . "\r\n\r\n";
	$message .= esc_url_raw( add_query_arg( array( 'rcp_action' => 'lostpassword_reset', 'key' => $key, 'login' => rawurlencode( $user_login ) ), $_POST['rcp_redirect'] ) ) . "\r\n";

	if ( is_multisite() ) {

		$blogname = $GLOBALS['current_site']->site_name;

	} else {
		/*
		 * The blogname option is escaped with esc_html on the way into the database
		 * in sanitize_option we want to reverse this for the plain text arena of emails.
		 */
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	}

	$title   = sprintf( __('[%s] Password Reset', 'rcp'), $blogname );
	$title   = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );
	$message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

	$emails = new RCP_Emails;
	$emails->member_id = $user_data->ID;
	$sent = $emails->send( $user_email, wp_specialchars_decode( $title ), $message );

	if ( $message && ! $sent ) {
		wp_die( __('The e-mail could not be sent.', 'rcp' ) . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.', 'rcp' ) );
	}

	return true;
}

/**
 * Return the user who is initiating the password reset, or false if not performing a reset
 *
 * @param       string $rp_cookie Password reset cookie name
 * @since       2.3
 * @return      WP_User|false User object if reset key and login name exist and are valid, false if not
 */
function rcp_get_user_resetting_password( $rp_cookie ) {

	// check if the reset key and login name are valid
	if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
		list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
		$user = check_password_reset_key( $rp_key, $rp_login );
	} else {
		$user = false;
	}

	if ( is_wp_error( $user ) ) {
		$user = false;
	}

	return $user;
}

/**
 * When a customer logs in, record their login date and IP address.
 *
 * @param string  $user_login User login.
 * @param WP_User $user       User object.
 *
 * @since 3.0
 * @return void
 */
function rcp_log_ip_and_last_login_date( $user_login, $user ) {

	$customer = rcp_get_customer_by_user_id( $user->ID );

	if ( empty( $customer ) ) {
		return;
	}

	$customer->add_ip( rcp_get_ip() );

	$customer->update( array(
		'last_login' => current_time( 'mysql' )
	) );

}
add_action( 'wp_login', 'rcp_log_ip_and_last_login_date', 10, 2 );
