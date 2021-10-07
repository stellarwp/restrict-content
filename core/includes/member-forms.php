<?php
/**
 * Member Forms
 *
 * @package     Restrict Content Pro
 * @subpackage  Member Forms
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
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
 * @return string HTML content.
 */
function rcp_login_form_fields( $args = array() ) {

	global $rcp_login_form_args;

	// parse the arguments passed
	$defaults = array(
		'redirect' => rcp_get_current_url(),
		'class'    => 'rcp_form'
	);

	$rcp_login_form_args = wp_parse_args( $args, $defaults );

	if( ! empty( $_GET['redirect'] ) ) {
		$rcp_login_form_args['redirect'] = urldecode( $_GET['redirect'] );
	}

	ob_start();

	if ( isset($_REQUEST['rcp_action']) && $_REQUEST['rcp_action'] === "lostpassword") {
		echo rcp_lostpassword_form_fields();
	} elseif ( isset($_REQUEST['rcp_action']) && $_REQUEST['rcp_action'] === "lostpassword_checkemail") {
		echo rcp_lostpassword_checkemail_message();
	} elseif ( isset($_REQUEST['rcp_action']) && ( $_REQUEST['rcp_action'] === "lostpassword_reset" || $_REQUEST['rcp_action'] === "reset-password" )) {
		echo rcp_change_password_form();
	} else {
		do_action( 'rcp_before_login_form' );

		rcp_get_template_part( 'login' );

		do_action( 'rcp_after_login_form' );
	}

	return ob_get_clean();
}

/**
 * Display lost password form fields
 *
 * @since       2.3
 * @return      string HTML content
 */
function rcp_lostpassword_form_fields() {

	ob_start();

	do_action( 'rcp_before_lostpassword_form' );

	rcp_get_template_part( 'lostpassword' );

	do_action( 'rcp_after_lostpassword_form' );

	return ob_get_clean();
}

/**
 * Display lost password check email message
 *
 * @since       2.3
 * @return      string HTML content
 */
function rcp_lostpassword_checkemail_message() {

	ob_start();

	rcp_get_template_part( 'lostpassword_checkemail' );

	return ob_get_clean();
}

/**
 * Display registration form fields
 *
 * @param null|int $id   ID of the subscription, to use register-single.php, or null to use register.php.
 * @param array    $atts Attributes passed to the shortcode.
 *
 * @return string
 */
function rcp_registration_form_fields( $id = null, $atts = array() ) {

	global $rcp_level, $rcp_register_form_atts;

	$rcp_level              = $id;
	$rcp_register_form_atts = $atts;

	ob_start();

	do_action( 'rcp_before_register_form', $id, $atts );

	if( ! is_null( $id ) ) {

		if ( rcp_show_subscription_level( $id ) ) {

			if ( rcp_locate_template( array( 'register-single-' . $id . '.php' ), false ) ) {

				rcp_get_template_part( 'register', 'single-' . $id );

			} else {

				rcp_get_template_part( 'register', 'single' );

			}

		} else {

			echo $rcp_register_form_atts['registered_message'];

		}

	} else {

		rcp_get_template_part( 'register' );

	}

	do_action( 'rcp_after_register_form', $id, $atts );

	return ob_get_clean();
}

/**
 * Display change password form fields
 *
 * @param array $args Arguments to override the defaults.
 *
 * @return string
 */
function rcp_change_password_form( $args = array() ) {

	global $rcp_password_form_args;

	// parse the arguments passed
	$defaults = array (
 		'redirect' => rcp_get_current_url(),
	);
	$rcp_password_form_args = wp_parse_args( $args, $defaults );

	ob_start();
	do_action( 'rcp_before_password_form' );
	rcp_get_template_part( 'change-password' );
	do_action( 'rcp_after_password_form' );
	return ob_get_clean();
}

/**
 * Display auto renew checkbox, if set to allow the user to decide.
 *
 * @param array $levels
 *
 * @return void
 */
function rcp_add_auto_renew( $levels = array() ) {
	if( '3' == rcp_get_auto_renew_behavior() ) :
		global $rcp_options;
		?>
		<p id="rcp_auto_renew_wrap">
			<input name="rcp_auto_renew" id="rcp_auto_renew" type="checkbox" <?php echo isset( $rcp_options['auto_renew_checked_on'] ) ? 'checked="checked"' : ''; ?>/>
			<label for="rcp_auto_renew"><?php echo apply_filters ( 'rcp_registration_auto_renew', __( 'Auto Renew', 'rcp' ) ); ?></label>
		</p>
	<?php endif;
}
add_action( 'rcp_before_registration_submit_field', 'rcp_add_auto_renew' );