<?php
/**
 * CAPTCHA Functions
 *
 * Adds CAPTCHA to the registration form and validates the submission.
 *
 * @package     Restrict Content Pro
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/license/gpl-2.1.php GNU Public License
 */

/**
 * Whether or not reCATPCHA is enabled. The setting must be checked on and
 * all keys entered for this to return true.
 *
 * @since 2.9
 * @return bool
 */
function rcp_is_recaptcha_enabled() {

	global $rcp_options;

	$enabled = ( ! empty( $rcp_options['enable_recaptcha'] ) && ! empty( $rcp_options['recaptcha_public_key'] ) && ! empty( $rcp_options['recaptcha_private_key'] ) );

	if ( ! $enabled ) {
		return false;
	}

	/**
	 * Filters whether reCAPTCHA is enabled.
	 *
	 * This can only be used to disable reCAPTCHA if it is presently enabled.
	 *
	 * @since 3.4.3
	 *
	 * @param bool $enabled True if reCAPTCHA is enabled.
	 */
	return apply_filters( 'rcp_is_recaptcha_enabled', $enabled );
}

/**
 * Get the reCAPTCHA version
 *
 * @since 3.3
 * @return int Either 2 (v2) or 3 (v3)
 */
function rcp_get_recaptcha_version() {

	global $rcp_options;

	return ! empty( $rcp_options['recaptcha_version'] ) ? absint( $rcp_options['recaptcha_version'] ) : 2;

}

/**
 * Add reCAPTCHA to the registration form if it's enabled.
 *
 * @return void
 */
function rcp_show_captcha() {
	global $rcp_options;
	// reCaptcha
	if ( rcp_is_recaptcha_enabled() ) {
		if ( 3 === rcp_get_recaptcha_version() ) {
			/**
			 * v3 markup
			 */
			?>
			<div id="rcp_recaptcha" data-sitekey="<?php echo esc_attr( $rcp_options['recaptcha_public_key'] ); ?>">
				<input type="hidden" id="rcp_recaptcha_token" name="g-recaptcha-response" />
				<input type="hidden" name="g-recaptcha-remoteip" value=<?php echo esc_attr( rcp_get_ip() ); ?> />
			</div>
			<?php
		} else {
			/**
			 * v2 markup
			 */
			?>
			<div id="rcp_recaptcha" data-callback="rcp_validate_recaptcha" class="g-recaptcha" data-sitekey="<?php echo esc_attr( $rcp_options['recaptcha_public_key'] ); ?>"></div>
			<input type="hidden" name="g-recaptcha-remoteip" value=<?php echo esc_attr( rcp_get_ip() ); ?> /><br/>
			<?php
		}
	}
}
add_action( 'rcp_before_registration_submit_field', 'rcp_show_captcha', 100 );
add_action( 'rcp_before_stripe_checkout_submit_field', 'rcp_show_captcha', 100 );

/**
 * Validate reCAPTCHA during form submission and throw an error if invalid.
 *
 * @param array $data Data passed through the registration form.
 *
 * @return void
 */
function rcp_validate_captcha( $data ) {

	global $rcp_options;

	if( ! rcp_is_recaptcha_enabled() ) {
		return;
	}

	if ( empty( $data['g-recaptcha-response'] ) || empty( $data['g-recaptcha-remoteip'] ) ) {
		rcp_errors()->add( 'invalid_recaptcha', __( 'Registration failed', 'rcp' ), 'register' );
		return;
	}

	// Skip on validation step (to ensure we only do this once).
	if ( ! empty( $data['validate_only'] ) ) {
		return;
	}

	$verify = wp_safe_remote_post(
		'https://www.google.com/recaptcha/api/siteverify',
		array(
			'body' => array(
				'secret'   => trim( $rcp_options['recaptcha_private_key'] ),
				'response' => $data['g-recaptcha-response'],
				'remoteip' => $data['g-recaptcha-remoteip']
			)
		)
	);

	$verify = json_decode( wp_remote_retrieve_body( $verify ) );

	if ( 2 === rcp_get_recaptcha_version() ) {
		/**
		 * Validate reCAPTCHA v2 response
		 */
		if ( empty( $verify->success ) || true !== $verify->success ) {
			rcp_errors()->add( 'invalid_recaptcha', __( 'Registration failed', 'rcp' ), 'register' );
		}

		return;
	} else {
		/**
		 * Validate reCAPTCHA v3 response
		 */

		/**
		 * Filters the score threshold. Scores lower than this will be rejected.
		 */
		$score_threshold = apply_filters( 'rcp_recaptcha_score_threshold', 0.5 );
		$score           = ! empty( $verify->score ) ? $verify->score : 0;

		rcp_log( sprintf( 'reCAPTCHA score: %s / %s', $score, $score_threshold ) );

		if ( empty( $verify->success ) || true !== $verify->success || empty( $verify->action ) || 'register' !== $verify->action || $score < $score_threshold ) {
			rcp_errors()->add( 'invalid_recaptcha', __( 'Registration failed', 'rcp' ), 'register' );
			if ( isset( $verify->{'error-codes'} ) ) {
				rcp_log( sprintf( 'reCAPTCHA errors: %s', var_export( $verify->{'error-codes'}, true ) ), true );
			}
		}
	}

}
add_action( 'rcp_form_errors', 'rcp_validate_captcha' );
