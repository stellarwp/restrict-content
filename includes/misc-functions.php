<?php
/**
 * Get current URL.
 *
 * Returns the URL to the current page, including detection for https.
 *
 * @return string
 */
function rc_get_current_url() {

	global $post;

	if ( is_singular() ) :

		$current_url = get_permalink( $post->ID );

	else :

		global $wp;

		if( get_option( 'permalink_structure' ) ) {

			$base = trailingslashit( home_url( $wp->request ) );

		} else {

			$base = add_query_arg( $wp->query_string, '', trailingslashit( home_url( $wp->request ) ) );
			$base = remove_query_arg( array( 'post_type', 'name' ), $base );

		}

		$scheme      = is_ssl() ? 'https' : 'http';
		$current_url = set_url_scheme( $base, $scheme );

	endif;

	return $current_url;
}

/**
 * Stores error messages
 *
 * @return WP_Error
 */
function rc_errors() {
	static $wp_error;
	return isset( $wp_error ) ? $wp_error : ( $wp_error = new WP_Error() );
}

/**
 * Displays the HTML for error messages
 *
 * @param string $error_id
 *
 * @return void
 */
function rc_show_error_messages( $error_id = '' ) {
	if( rc_errors()->get_error_codes() ) {
		echo rc_get_error_messages_html( $error_id );
	}
}

/**
 * Retrieves the HTML for error messages
 *
 * @param string $error_id
 *
 * @return string
 */
function rc_get_error_messages_html( $error_id = '' ) {

	$html   = '';
	$errors = rc_errors()->get_error_codes();

	if( $errors ) {

		$html .= '<div class="rc-message error">';

		foreach( $errors as $code ) {

			if ( rc_errors()->get_error_data( $code ) == $error_id ) {

				$message = rc_errors()->get_error_message( $code );

				$html .= '<p class="rc-error ' . esc_attr( $code ) . '"><span>' . $message . '</span></p>';

			}

		}

		$html .= '</div>';

	}

	return $html;

}