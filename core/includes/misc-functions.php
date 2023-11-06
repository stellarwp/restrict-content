<?php
/**
 * Misc. Functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Misc Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Determines if we are in sandbox mode
 *
 * @access public
 * @since 2.6.4
 * @return bool True if we are in sandbox mode
 */
function rcp_is_sandbox() {

	global $rcp_options;

	$is_sandbox = ( defined( 'RCP_GATEWAY_SANDBOX_MODE' ) && RCP_GATEWAY_SANDBOX_MODE ) ? true : isset( $rcp_options['sandbox'] );

	/**
	 * Filters whether or not sandbox mode is enabled.
	 *
	 * @param bool $is_sandbox
	 */
	return (bool) apply_filters( 'rcp_is_sandbox', $is_sandbox );

}

/**
 * Checks whether the post is Paid Only.
 *
 * @param int $post_id ID of the post to check.
 *
 * @access private
 * @return bool True if the post is paid only, false if not.
 */
function rcp_is_paid_content( $post_id ) {
	if ( $post_id == '' || ! is_int( $post_id ) ) {
		$post_id = get_the_ID();
	}

	$return                 = false;
	$post_type_restrictions = rcp_get_post_type_restrictions( get_post_type( $post_id ) );

	if ( ! empty( $post_type_restrictions ) ) {

		// Check post type restrictions.
		if ( array_key_exists( 'is_paid', $post_type_restrictions ) ) {
			$return = true;
		}
	} else {

		// Check regular post.
		$is_paid = get_post_meta( $post_id, '_is_paid', true );
		if ( $is_paid ) {
			// this post is for paid users only
			$return = true;
		}
	}

	return (bool) apply_filters( 'rcp_is_paid_content', $return, $post_id );
}


/**
 * Retrieve a list of all Paid Only posts.
 *
 * @access public
 * @return array Lists all paid only posts.
 */
function rcp_get_paid_posts() {
	$args     = array(
		'meta_key'       => '_is_paid',
		'meta_value'     => 1,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'post_type'      => 'any',
		'fields'         => 'ids',
	);
	$paid_ids = get_posts( $args );
	if ( $paid_ids ) {
		return $paid_ids;
	}

	return array();
}


/**
 * Apply the currency sign to a price.
 *
 * @param float $price Price to add the currency sign to.
 *
 * @access public
 * @return string List of currency signs.
 */
function rcp_currency_filter( $price ) {
	global $rcp_options;

	$formatted_price = ! empty( $price ) ? rcp_format_amount( $price ) : $price; // Add decimals and format thousands separator.
	$currency        = rcp_get_currency();
	$position        = isset( $rcp_options['currency_position'] ) ? $rcp_options['currency_position'] : 'before';

	if ( $position == 'before' ) :
		$formatted = rcp_get_currency_symbol( $currency ) . $formatted_price;
		return apply_filters( 'rcp_' . strtolower( $currency ) . '_currency_filter_before', $formatted, $currency, $price );
	else :
		$formatted = $formatted_price . rcp_get_currency_symbol( $currency );
		return apply_filters( 'rcp_' . strtolower( $currency ) . '_currency_filter_after', $formatted, $currency, $price );
	endif;
}

/**
 * Return the symbol for a specific currency
 *
 * @param bool $currency
 *
 * @since 2.9.5
 * @return string
 */
function rcp_get_currency_symbol( $currency = false ) {

	global $rcp_options;

	$position = isset( $rcp_options['currency_position'] ) ? $rcp_options['currency_position'] : 'before';

	if ( empty( $currency ) ) {
		$currency = rcp_get_currency();
	}

	$supported_currencies = rcp_get_currencies();
	if ( ! array_key_exists( $currency, $supported_currencies ) ) {
		$currency = rcp_get_currency();
	}

	switch ( $currency ) {
		case 'USD':
			$symbol = '&#36;';
			break;
		case 'EUR':
			$symbol = '&#8364;';
			break;
		case 'GBP':
			$symbol = '&#163;';
			break;
		case 'AUD':
			$symbol = '&#36;';
			break;
		case 'BRL':
			$symbol = '&#82;&#36;';
			break;
		case 'CAD':
			$symbol = '&#36;';
			break;
		case 'CHF':
			$symbol = '&#67;&#72;&#70;';
			break;
		case 'CZK':
			$symbol = '&#75;&#269;';
			break;
		case 'DKK':
			$symbol = '&#107;&#114;';
			break;
		case 'HKD':
			$symbol = '&#36;';
			break;
		case 'HUF':
			$symbol = '&#70;&#116;';
			break;
		case 'ILS':
			$symbol = '&#8362;';
			break;
		case 'IRR':
			$symbol = '&#65020;';
			break;
		case 'JPY':
			$symbol = '&#165;';
			break;
		case 'MXN':
			$symbol = '&#36;';
			break;
		case 'MYR':
			$symbol = '&#82;&#77;';
			break;
		case 'NOK':
			$symbol = 'after' == $position ? '&nbsp;&#107;&#114;' : '&#107;&#114;&nbsp;';
			break;
		case 'NZD':
			$symbol = '&#36;';
			break;
		case 'PHP':
			$symbol = '&#8369;';
			break;
		case 'PLN':
			$symbol = 'after' == $position ? '&nbsp;&#122;&#322;' : '&#122;&#322;&nbsp;';
			break;
		case 'RUB':
			$symbol = '&#1088;&#1091;&#1073;';
			break;
		case 'SEK':
			$symbol = '&#107;&#114;';
			break;
		case 'SGD':
			$symbol = '&#36;';
			break;
		case 'THB':
			$symbol = '&#3647;';
			break;
		case 'TRY':
			$symbol = '&#8356;';
			break;
		case 'TWD':
			$symbol = '&#78;&#84;&#36;';
			break;
		default:
			$symbol = $currency;
	}

	return apply_filters( 'rcp_' . strtolower( $currency ) . '_symbol', $symbol, $currency );

}


/**
 * Get the currency list.
 *
 * @access private
 * @return array List of currencies.
 */
function rcp_get_currencies() {
	$currencies = array(
		'USD' => __( 'US Dollars (&#36;)', 'rcp' ),
		'EUR' => __( 'Euros (&#8364;)', 'rcp' ),
		'GBP' => __( 'Pounds Sterling (&#163;)', 'rcp' ),
		'AUD' => __( 'Australian Dollars (&#36;)', 'rcp' ),
		'BRL' => __( 'Brazilian Real (&#82;&#36;)', 'rcp' ),
		'CAD' => __( 'Canadian Dollars (&#36;)', 'rcp' ),
		'CZK' => __( 'Czech Koruna (&#75;&#269;)', 'rcp' ),
		'DKK' => __( 'Danish Krone (&#107;&#114;)', 'rcp' ),
		'HKD' => __( 'Hong Kong Dollar (&#36;)', 'rcp' ),
		'HUF' => __( 'Hungarian Forint (&#70;&#116;)', 'rcp' ),
		'IRR' => __( 'Iranian Rial (&#65020;)', 'rcp' ),
		'ILS' => __( 'Israeli Shekel (&#8362;)', 'rcp' ),
		'JPY' => __( 'Japanese Yen (&#165;)', 'rcp' ),
		'MYR' => __( 'Malaysian Ringgits (&#82;&#77;)', 'rcp' ),
		'MXN' => __( 'Mexican Peso (&#36;)', 'rcp' ),
		'NZD' => __( 'New Zealand Dollar (&#36;)', 'rcp' ),
		'NOK' => __( 'Norwegian Krone (&#107;&#114;)', 'rcp' ),
		'PHP' => __( 'Philippine Pesos (&#8369;)', 'rcp' ),
		'PLN' => __( 'Polish Zloty (&#122;&#322;)', 'rcp' ),
		'RUB' => __( 'Russian Rubles (&#1088;&#1091;&#1073;)', 'rcp' ),
		'SGD' => __( 'Singapore Dollar (&#36;)', 'rcp' ),
		'SEK' => __( 'Swedish Krona (&#107;&#114;)', 'rcp' ),
		'CHF' => __( 'Swiss Franc (&#67;&#72;&#70;)', 'rcp' ),
		'TWD' => __( 'Taiwan New Dollars (&#78;&#84;&#36;)', 'rcp' ),
		'THB' => __( 'Thai Baht (&#3647;)', 'rcp' ),
		'TRY' => __( 'Turkish Lira (&#8356;)', 'rcp' ),
	);
	return apply_filters( 'rcp_currencies', $currencies );
}

/**
 * Is odd?
 *
 * Checks if a number is odd.
 *
 * @param int $int Number to check.
 *
 * @access private
 * @return bool
 */
function rcp_is_odd( $int ) {
	return $int & 1;
}


/**
 * Gets the excerpt of a specific post ID or object.
 *
 * @param object/int $post The ID or object of the post to get the excerpt of.
 * @param int        $length The length of the excerpt in words.
 * @param string     $tags The allowed HTML tags. These will not be stripped out.
 * @param string     $extra Text to append to the end of the excerpt.
 *
 * @return string Post excerpt.
 */
function rcp_excerpt_by_id( $post, $length = 50, $tags = '<a><em><strong><blockquote><ul><ol><li><p>', $extra = ' . . .' ) {

	if ( is_int( $post ) ) {
		// get the post object of the passed ID
		$post = get_post( $post );
	} elseif ( ! is_object( $post ) ) {
		return false;
	}
	$more = false;
	if ( has_excerpt( $post->ID ) ) {
		$the_excerpt = $post->post_excerpt;
	} elseif ( strstr( $post->post_content, '<!--more-->' ) ) {
		$more        = true;
		$length      = strpos( $post->post_content, '<!--more-->' );
		$the_excerpt = $post->post_content;
	} else {
		$the_excerpt = $post->post_content;
	}

	$tags = apply_filters( 'rcp_excerpt_tags', $tags );

	if ( $more ) {
		$the_excerpt = strip_shortcodes( strip_tags( stripslashes( substr( $the_excerpt, 0, $length ) ), $tags ) );
	} else {
		$the_excerpt   = strip_shortcodes( strip_tags( stripslashes( $the_excerpt ), $tags ) );
		$the_excerpt   = preg_split( '/\b/', $the_excerpt, $length * 2 + 1 );
		$excerpt_waste = array_pop( $the_excerpt );
		$the_excerpt   = implode( $the_excerpt );
		$the_excerpt  .= $extra;
	}

	$the_excerpt = wpautop( $the_excerpt );

	/**
	 * Filters the post excerpt.
	 *
	 * @param string  $the_excerpt Generated post excerpt.
	 * @param WP_Post $post        Post object.
	 * @param int     $length      Desired length of the excerpt in words.
	 * @param string  $tags        The allowed HTML tags. These will not be stripped out.
	 * @param string  $extra       Text to append to the end of the excerpt.
	 *
	 * @since 2.9.3
	 */
	return apply_filters( 'rcp_post_excerpt', $the_excerpt, $post, $length, $tags, $extra );
}


/**
 * Get current URL.
 *
 * Returns the URL to the current page, including detection for https.
 *
 * @access private
 * @return string
 */
function rcp_get_current_url() {
	global $post;

	if ( is_singular() ) :

		$current_url = get_permalink( $post->ID );

	else :

		global $wp;

		if ( get_option( 'permalink_structure' ) ) {

			$base = trailingslashit( home_url( $wp->request ) );

		} else {

			$base = add_query_arg( $wp->query_string, '', trailingslashit( home_url( $wp->request ) ) );
			$base = remove_query_arg( array( 'post_type', 'name' ), $base );

		}

		$scheme      = is_ssl() ? 'https' : 'http';
		$current_url = set_url_scheme( $base, $scheme );

	endif;

	return apply_filters( 'rcp_current_url', $current_url );
}


/**
 * Check if a "Maximum number of simultaneous connections per member" has been set.
 *
 * @access private
 * @since  1.4
 * @return bool
 */
function rcp_no_account_sharing() {
	return intval( rcp_no_account_sharing_number() ) > 0;
}


/**
 * Returns the "Maximum number of simultaneous connections per member".
 *
 * @access private
 * @since  2.7
 * @return int
 */
function rcp_no_account_sharing_number() {
	global $rcp_options;

	$sharing_number = 0;
	if ( isset( $rcp_options['no_login_sharing'] ) ) {
		$sharing_number = $rcp_options['no_login_sharing'];
	}

	return apply_filters( 'rcp_no_account_sharing', $sharing_number );
}


/**
 * Stores cookie value in a transient when a user logs in.
 *
 * Transient IDs are based on the user ID so that we can track the number of
 * users logged into the same account. Admins are excluded from this.
 *
 * @param string $logged_in_cookie The logged-in cookie.
 * @param int    $expire           The time the login grace period expires as a UNIX timestamp.
 *                                 Default is 12 hours past the cookie's expiration time.
 * @param int    $expiration       The time when the logged-in authentication cookie expires as a UNIX timestamp.
 *                                 Default is 14 days from now.
 * @param int    $user_id          User ID.
 * @param string $status           Authentication scheme. Default 'logged_in'.
 *
 * @access private
 * @since  1.5
 * @return void
 */
function rcp_set_user_logged_in_status( $logged_in_cookie, $expire, $expiration, $user_id, $status = 'logged_in' ) {

	if ( ! rcp_no_account_sharing() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( ! empty( $user_id ) && ! user_can( $user_id, 'manage_options' ) ) :

		$data = get_transient( 'rcp_user_logged_in_' . $user_id );

		if ( false === $data ) {
			$data = array();
		}

		$data[] = $logged_in_cookie;

		set_transient( 'rcp_user_logged_in_' . $user_id, $data, MONTH_IN_SECONDS );

	endif;
}
add_action( 'set_logged_in_cookie', 'rcp_set_user_logged_in_status', 10, 5 );


/**
 * Removes the current user's auth cookie from the rcp_user_logged_in_# transient when logging out.
 *
 * @access private
 * @since  1.5
 * @return void
 */
function rcp_clear_auth_cookie() {

	if ( ! rcp_no_account_sharing() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! isset( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
		return;
	}

	$user_id = get_current_user_id();

	// Admins are excluded from this so we don't need to check them.
	if ( user_can( $user_id, 'manage_options' ) ) {
		return;
	}

	$already_logged_in = get_transient( 'rcp_user_logged_in_' . $user_id );

	if ( is_serialized( $already_logged_in ) ) {
		preg_match( '/[oO]\s*:\s*\d+\s*:\s*"\s*(?!(?i)(stdClass))/', $already_logged_in, $matches );
		if ( ! empty( $matches ) ) {
			$already_logged_in = false;
		}
	}

	if ( $already_logged_in !== false ) :

		$data = maybe_unserialize( $already_logged_in );

		$key = array_search( $_COOKIE[ LOGGED_IN_COOKIE ], $data );
		if ( false !== $key ) {
			unset( $data[ $key ] );
			$data = array_values( $data );
			set_transient( 'rcp_user_logged_in_' . $user_id, $data, MONTH_IN_SECONDS );
		}

	endif;

}
add_action( 'clear_auth_cookie', 'rcp_clear_auth_cookie' );


/**
 * Checks if a user is allowed to be logged-in.
 *
 * The transient related to the user is retrieved and the first cookie in the transient
 * is compared to the LOGGED_IN_COOKIE of the current user.
 *
 * The first cookie in the transient is the oldest, so it is the one that gets logged out.
 *
 * We only log a user out if there are more than X users logged into the same account and
 * if it is not an administrator account.
 *
 * @access private
 * @since  1.5
 * @return void
 */
function rcp_can_user_be_logged_in() {
	if ( is_user_logged_in() && rcp_no_account_sharing() ) {

		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! isset( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			return;
		}

		$user_id = get_current_user_id();

		// Admins are excluded from this.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return;
		}

		$already_logged_in = get_transient( 'rcp_user_logged_in_' . $user_id );

		if ( is_serialized( $already_logged_in ) ) {
			preg_match( '/[oO]\s*:\s*\d+\s*:\s*"\s*(?!(?i)(stdClass))/', $already_logged_in, $matches );
			if ( ! empty( $matches ) ) {
				$already_logged_in = false;
			}
		}

		if ( $already_logged_in !== false ) {

			$data = maybe_unserialize( $already_logged_in );

			// remove the oldest logged in users
			$prev_data_count = count( $data );
			while ( count( $data ) > intval( rcp_no_account_sharing_number() ) ) {
				unset( $data[0] );
				$data = array_values( $data );
			}

			// save modified data
			if ( count( $data ) != $prev_data_count ) {
				set_transient( 'rcp_user_logged_in_' . $user_id, $data, MONTH_IN_SECONDS );
			}

			if ( ! in_array( $_COOKIE[ LOGGED_IN_COOKIE ], $data ) ) {

				// Log the user out - this is one of the oldest user logged into this account
				wp_logout();
				wp_safe_redirect( trailingslashit( get_bloginfo( 'wpurl' ) ) . 'wp-login.php?loggedout=true' );
			}
		}
	}
}
add_action( 'init', 'rcp_can_user_be_logged_in' );


/**
 * Retrieve a list of the allowed HTML tags.
 *
 * This is used for filtering HTML in membership level descriptions and other places.
 *
 * @access public
 * @since  1.5
 * @return array
 */
function rcp_allowed_html_tags() {
	$tags = array(
		'p'      => array(
			'class' => array(),
		),
		'span'   => array(
			'class' => array(),
		),
		'a'      => array(
			'href'  => array(),
			'title' => array(),
			'class' => array(),
		),
		'strong' => array(),
		'em'     => array(),
		'br'     => array(),
		'img'    => array(
			'src'   => array(),
			'title' => array(),
			'alt'   => array(),
		),
		'div'    => array(
			'class' => array(),
		),
		'ul'     => array(
			'class' => array(),
		),
		'li'     => array(
			'class' => array(),
		),
	);

	return apply_filters( 'rcp_allowed_html_tags', $tags );
}


/**
 * Checks whether function is disabled.
 *
 * @param  string $function Name of the function.
 *
 * @access public
 * @since  1.5
 * @return bool Whether or not function is disabled.
 */
function rcp_is_func_disabled( $function ) {
	$disabled = explode( ',', ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}


/**
 * Converts the month number to the month name
 *
 * @param  int $n Month number.
 *
 * @access public
 * @since  1.8
 * @return string The name of the month.
 */
if ( ! function_exists( 'rcp_get_month_name' ) ) {
	function rcp_get_month_name( $n ) {
		$timestamp = mktime( 0, 0, 0, $n, 1, 2005 );

		return date_i18n( 'F', $timestamp );
	}
}

/**
 * Retrieve timezone.
 *
 * @since  1.8
 * @return string $timezone The timezone ID.
 */
function rcp_get_timezone_id() {

	// if site timezone string exists, return it
	if ( $timezone = get_option( 'timezone_string' ) ) {
		return $timezone;
	}

	// get UTC offset, if it isn't set return UTC
	if ( ! ( $utc_offset = 3600 * get_option( 'gmt_offset', 0 ) ) ) {
		return 'UTC';
	}

	// attempt to guess the timezone string from the UTC offset
	$timezone = timezone_name_from_abbr( '', $utc_offset );

	// last try, guess timezone string manually
	if ( $timezone === false ) {

		$is_dst = date( 'I' );

		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset ) {
					return $city['timezone_id'];
				}
			}
		}
	}

	// fallback
	return 'UTC';
}

/**
 * Get the number of days in a particular month.
 *
 * @param int $calendar Calendar to use for calculation.
 * @param int $month    Month in the selected calendar.
 * @param int $year     Year in the selected calendar.
 *
 * @since  2.0.9
 * @return string $timezone The timezone ID.
 */
if ( ! function_exists( 'cal_days_in_month' ) ) {
	// Fallback in case the calendar extension is not loaded in PHP
	// Only supports Gregorian calendar
	function cal_days_in_month( $calendar, $month, $year ) {
		return date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
	}
}

/**
 * Retrieves the payment status label for a payment.
 *
 * @param int|object $payment Payment ID or database object.
 *
 * @since  2.1
 * @return string
 */
function rcp_get_payment_status_label( $payment ) {

	if ( is_numeric( $payment ) ) {
		$payments = new RCP_Payments();
		$payment  = $payments->get_payment( $payment );
	}

	if ( ! $payment ) {
		return '';
	}

	$status = ! empty( $payment->status ) ? $payment->status : 'complete';
	$label  = rcp_get_status_label( $status );

	return apply_filters( 'rcp_payment_status_label', $label, $status, $payment );

}

/**
 * Get User IP.
 *
 * Returns the IP address of the current visitor.
 *
 * @since  1.3
 * @return string $ip User's IP address.
 */
function rcp_get_ip() {

	$ip = false;

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		// check ip from share internet
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// to check ip is pass from proxy
		// can include more than 1 ip, first is the public one
		$ip = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
		$ip = $ip[0];
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	// Fix potential CSV returned from $_SERVER variables
	$ip_array = explode( ',', $ip );
	$user_ip  = ! empty( $ip_array[0] ) ? $ip_array[0] : '127.0.0.1';
	$user_ip  = filter_var( wp_unslash( trim( $user_ip ) ), FILTER_VALIDATE_IP );

	/**
	 * Filters the current user's IP address.
	 *
	 * @param int $user_ip
	 */
	return apply_filters( 'rcp_get_ip', $user_ip );
}

/**
 * Retrieve the membership levels a post/page is restricted to
 *
 * @param int $post_id The ID of the post to retrieve levels for
 *
 * @since  1.6
 * @return array|false
 */
function rcp_get_content_subscription_levels( $post_id = 0 ) {
	$levels = get_post_meta( $post_id, 'rcp_subscription_level', true );

	if ( 'all' == $levels ) {
		// This is for backwards compatibility from when RCP didn't allow content to be restricted to multiple levels
		return false;
	}

	if ( 'any' !== $levels && 'any-paid' !== $levels && ! empty( $levels ) && ! is_array( $levels ) ) {
		$levels = array( $levels );
	}
	return apply_filters( 'rcp_get_content_subscription_levels', $levels, $post_id );
}

/**
 * Checks to see if content is restricted in any way.
 *
 * @param  int $post_id The post ID to check for restrictions.
 *
 * @since  2.5
 * @return bool True if the content is restricted, false if not.
 */
function rcp_is_restricted_content( $post_id ) {

	if ( empty( $post_id ) || ! is_numeric( $post_id ) ) {
		return false;
	}

	$post_id = absint( $post_id );

	// Check post type restrictions.
	$restricted = rcp_is_restricted_post_type( get_post_type( $post_id ) );

	// Check post restrictions.
	if ( ! $restricted ) {
		$restricted = rcp_has_post_restrictions( $post_id );
	}

	// Check if the post is restricted via a term.
	if ( ! $restricted ) {
		$restricted = rcp_has_term_restrictions( $post_id );
	}

	return apply_filters( 'rcp_is_restricted_content', $restricted, $post_id );

}

/**
 * Get an array of restrictions on a given post.
 *
 * If the post's post type is restricted, then the global post type restrictions are returned.
 * Otherwise, it will be an array of requirements from the individual post.
 * Terms are not checked at this time, but may be in the future.
 *
 * @param int $post_id
 *
 * @since 3.0.4
 * @return array
 */
function rcp_get_post_restrictions( $post_id ) {

	// Set up defaults.
	$restrictions = array(
		'membership_levels' => '', // Can be a string "any-paid", "any", or array of level IDs.
		'access_level'      => 0,
		'user_level'        => array(),
	);

	$post_type_restrictions = rcp_get_post_type_restrictions( get_post_type( $post_id ) );

	if ( empty( $post_type_restrictions ) ) {
		$membership_levels = rcp_get_content_subscription_levels( $post_id );
		$access_level      = get_post_meta( $post_id, 'rcp_access_level', true );
		$user_level        = get_post_meta( $post_id, 'rcp_user_level', true );
	} else {
		$membership_levels = array_key_exists( 'subscription_level', $post_type_restrictions ) ? $post_type_restrictions['subscription_level'] : false;
		$access_level      = array_key_exists( 'access_level', $post_type_restrictions ) ? $post_type_restrictions['access_level'] : false;
		$user_level        = array_key_exists( 'user_level', $post_type_restrictions ) ? $post_type_restrictions['user_level'] : false;
	}

	// Check that user level is an array for backwards compatibility.
	if ( ! empty( $user_level ) && ! is_array( $user_level ) ) {
		$user_level = array( $user_level );
	}

	if ( ! empty( $membership_levels ) ) {
		$restrictions['membership_levels'] = $membership_levels;
	}

	if ( ! empty( $access_level ) ) {
		$restrictions['access_level'] = $access_level;
	}

	if ( ! empty( $user_level ) && 'all' != strtolower( $user_level[0] ) && 'None' != strtolower( $user_level[0] ) ) {
		$restrictions['user_level'] = $user_level;
	}

	// @todo check terms

	return $restrictions;

}

/**
 * Checks to see if a given post has any restrictions. This checks post
 * restrictions only via the Edit Post meta box.
 *
 * @param int $post_id The post ID to check for restrictions.
 *
 * @since 2.8.2
 * @return bool True if the post has restrictions.
 */
function rcp_has_post_restrictions( $post_id ) {

	if ( empty( $post_id ) || ! is_numeric( $post_id ) ) {
		return false;
	}

	$restricted = false;

	$post_id = absint( $post_id );

	if ( ! $restricted && get_post_meta( $post_id, '_is_paid', true ) ) {
		$restricted = true;
	}

	if ( ! $restricted && rcp_get_content_subscription_levels( $post_id ) ) {
		$restricted = true;
	}

	if ( ! $restricted ) {
		$rcp_user_level = get_post_meta( $post_id, 'rcp_user_level', true );
		if ( ! empty( $rcp_user_level ) && ! is_array( $rcp_user_level ) ) {
			$rcp_user_level = array( $rcp_user_level );
		}
		if ( ! empty( $rcp_user_level ) && 'all' !== strtolower( $rcp_user_level[0] ) && 'None' !== strtolower( $rcp_user_level[0] ) ) {
			$restricted = true;
		}
	}

	if ( ! $restricted ) {
		$rcp_access_level = get_post_meta( $post_id, 'rcp_access_level', true );
		if ( ! empty( $rcp_access_level ) && 'None' !== $rcp_access_level ) {
			$restricted = true;
		}
	}

	return (bool) apply_filters( 'rcp_has_post_restrictions', $restricted, $post_id );

}

/**
 * Checks if a given post is assigned to any terms that have restrictions.
 *
 * This does not check if the current user meets the requirements, it just checks if any
 * restrictions are in place.
 *
 * @uses rcp_get_term_restrictions()
 *
 * @param int $post_id ID of the post to check.
 *
 * @since 3.0.6
 * @return bool
 */
function rcp_has_term_restrictions( $post_id ) {

	if ( empty( $post_id ) || ! is_numeric( $post_id ) ) {
		return false;
	}

	$restricted = false;
	$term_ids   = rcp_get_connected_term_ids( $post_id );

	// Post doesn't have any terms - bail.
	if ( empty( $term_ids ) ) {
		return $restricted;
	}

	foreach ( $term_ids as $term_id ) {
		if ( rcp_get_term_restrictions( $term_id ) ) {
			$restricted = true;
			break;
		}
	}

	return $restricted;

}

/**
 * Get taxonomies that can be restricted
 *
 * @param string $output The type of output to return in the array. Accepts either taxonomy 'names'
 *                       or 'objects'. Default 'names'.
 *
 * @since 2.5
 * @return array
 */
function rcp_get_restricted_taxonomies( $output = 'names' ) {
	return apply_filters(
		'rcp_get_restricted_taxonomies',
		get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			$output
		)
	);
}

/**
 * Get restrictions for the provided term_id
 *
 * @param int $term_id
 *
 * @since 2.5
 * @return array
 */
function rcp_get_term_restrictions( $term_id ) {

	// fallback to older method of handling term meta if term meta does not exist
	if ( ( ! function_exists( 'get_term_meta' ) ) || ! $restrictions = get_term_meta( $term_id, 'rcp_restricted_meta', true ) ) {
		$restrictions = get_option( "rcp_category_meta_$term_id" );
	}

	if ( ! empty( $restrictions['access_level'] ) && 'none' === strtolower( $restrictions['access_level'] ) ) {
		unset( $restrictions['access_level'] );
	}

	return apply_filters( 'rcp_get_term_restrictions', $restrictions, $term_id );
}

/**
 * Returns an array of all restricted post types (keys) and their restriction
 * settings (values).
 *
 * @since 2.9
 * @return array
 */
function rcp_get_restricted_post_types() {
	return get_option( 'rcp_restricted_post_types', array() );
}

/**
 * Get restrictions for a specific post type.
 *
 * @param string $post_type The post type to check.
 *
 * @since 2.9
 * @return array Array of restriction settings.
 */
function rcp_get_post_type_restrictions( $post_type ) {
	$restricted_post_types = rcp_get_restricted_post_types();

	if ( empty( $post_type ) || empty( $restricted_post_types ) ) {
		return array();
	}

	return array_key_exists( $post_type, $restricted_post_types ) ? $restricted_post_types[ $post_type ] : array();
}

/**
 * Checks to see if a given post type has global restrictions applied.
 *
 * @param string $post_type The post type to check.
 *
 * @since 2.9
 * @return bool True if the post type is restricted in some way.
 */
function rcp_is_restricted_post_type( $post_type ) {
	$restrictions = rcp_get_post_type_restrictions( $post_type );

	return ! empty( $restrictions );
}

/**
 * Check the provided taxonomy along with the given post id to see if any restrictions are found
 *
 * @since      2.5
 * @param int      $post_id ID of the post to check.
 * @param string   $taxonomy
 * @param null|int $user_id User ID or leave as null to use currently logged in user.
 *
 * @return int|bool true if tax is restricted, false if user can access, -1 if unrestricted or invalid
 */
function rcp_is_post_taxonomy_restricted( $post_id, $taxonomy, $user_id = null ) {

	$restricted = -1;

	if ( current_user_can( 'edit_post', $post_id ) ) {
		return $restricted;
	}

	// make sure this post supports the supplied taxonomy
	$post_taxonomies = get_post_taxonomies( $post_id );
	if ( ! in_array( $taxonomy, (array) $post_taxonomies ) ) {
		return $restricted;
	}

	$terms = get_the_terms( $post_id, $taxonomy );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return $restricted;
	}

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$customer = rcp_get_customer_by_user_id( $user_id );
	$is_paid  = is_object( $customer ) ? $customer->has_paid_membership() : false;

	// Loop through the categories and determine if one has restriction options
	foreach ( $terms as $term ) {

		$term_meta = rcp_get_term_restrictions( $term->term_id );

		if ( empty( $term_meta['paid_only'] ) && empty( $term_meta['subscriptions'] ) && ( empty( $term_meta['access_level'] ) || 'None' == $term_meta['access_level'] ) ) {
			continue;
		}

		$restricted = true;

		/** Check that the user has a paid subscription */
		$paid_only = ! empty( $term_meta['paid_only'] );
		if ( $paid_only && $is_paid ) {
			$restricted = false;
			break;
		}

		/** If restricted to one or more membership levels, make sure that the user is a member of one of the levels */
		$subscriptions = ! empty( $term_meta['subscriptions'] ) ? array_map( 'absint', $term_meta['subscriptions'] ) : false;
		if ( $subscriptions && $customer && count( array_intersect( rcp_get_customer_membership_level_ids( $customer->get_id() ), $subscriptions ) ) ) {
			$restricted = false;
			break;
		}

		/** If restricted to one or more access levels, make sure that the user is a member of one of the levls */
		$access_level = ! empty( $term_meta['access_level'] ) ? absint( $term_meta['access_level'] ) : 0;
		if ( $access_level > 0 && $customer && $customer->has_access_level( $access_level ) ) {
			$restricted = false;
			break;
		}
	}

	return apply_filters( 'rcp_is_post_taxonomy_restricted', $restricted, $taxonomy, $post_id, $user_id );
}

/**
 * Get RCP Currency.
 *
 * @since  2.5
 * @return string
 */
function rcp_get_currency() {
	global $rcp_options;
	$currency = isset( $rcp_options['currency'] ) ? strtoupper( $rcp_options['currency'] ) : 'USD';
	return apply_filters( 'rcp_get_currency', $currency );
}

/**
 * Determines if a given currency code matches the currency selected in the settings.
 *
 * @param string $currency_code Currency code to check.
 *
 * @since  2.7.2
 * @return bool
 */
function rcp_is_valid_currency( $currency_code ) {
	$valid = strtolower( $currency_code ) == strtolower( rcp_get_currency() );

	return (bool) apply_filters( 'rcp_is_valid_currency', $valid, $currency_code );
}

/**
 * Determines if a given currency code matches the currency associated with a membership.
 *
 * @param $currency_code
 * @param $membership
 *
 * @since 3.5.6
 * @return bool
 */
function rcp_is_valid_membership_currency( $currency_code, $membership ) {
	$valid = strtolower( $currency_code ) == strtolower( $membership->get_currency() );

	return (bool) apply_filters( 'rcp_is_valid_membership_currency', $valid, $currency_code, $membership );
}

/**
 * Determines if RCP is using a zero-decimal currency.
 *
 * @param  string $currency
 *
 * @access public
 * @since  2.5
 * @return bool True if currency set to a zero-decimal currency.
 */
function rcp_is_zero_decimal_currency( $currency = '' ) {

	if ( ! $currency ) {
		$currency = strtoupper( rcp_get_currency() );
	}

	$zero_dec_currencies = array(
		'BIF',
		'CLP',
		'DJF',
		'GNF',
		'JPY',
		'KMF',
		'KRW',
		'MGA',
		'PYG',
		'RWF',
		'VND',
		'VUV',
		'XAF',
		'XOF',
		'XPF',
	);

	return apply_filters( 'rcp_is_zero_decimal_currency', in_array( $currency, $zero_dec_currencies ) );

}

/**
 * Sets the number of decimal places based on the currency.
 *
 * @param  int $decimals The number of decimal places. Default is 2.
 *
 * @since  2.5.2
 * @return int The number of decimal places.
 */
function rcp_currency_decimal_filter( $decimals = 2 ) {

	$currency = rcp_get_currency();

	if ( rcp_is_zero_decimal_currency( $currency ) ) {
		$decimals = 0;
	}

	return apply_filters( 'rcp_currency_decimal_filter', $decimals, $currency );
}

/**
 * Formats the payment amount for display to enforce decimal places and thousands separator.
 *
 * @param float|int $amount
 *
 * @since 2.9.5
 * @return float
 */
function rcp_format_amount( $amount ) {

	global $wp_locale;

	$thousands_sep = ! empty( $wp_locale->number_format['thousands_sep'] ) ? $wp_locale->number_format['thousands_sep'] : ',';
	$decimal_sep   = ! empty( $wp_locale->number_format['decimal_point'] ) ? $wp_locale->number_format['decimal_point'] : '.';

	// Format the amount
	if ( $decimal_sep === ',' && false !== ( $sep_found = strpos( $amount, $decimal_sep ) ) ) {
		$whole  = substr( $amount, 0, $sep_found );
		$part   = substr( $amount, $sep_found + 1, ( strlen( $amount ) - 1 ) );
		$amount = $whole . '.' . $part;
	}

	// Strip , from the amount (if set as the thousands separator)
	if ( $thousands_sep === ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
		$amount = str_replace( ',', '', $amount );
	}

	// Strip ' ' from the amount (if set as the thousands separator)
	if ( $thousands_sep === ' ' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
		$amount = str_replace( ' ', '', $amount );
	}

	if ( empty( $amount ) ) {
		$amount = 0;
	}

	$new_amount = number_format_i18n( $amount, rcp_currency_decimal_filter() );

	return $new_amount;
}

/**
 * Gets the taxonomy term ids connected to the specified post ID.
 *
 * @param  int $post_id The post ID.
 *
 * @since 2.7
 * @return array An array of taxonomy term IDs connected to the post.
 */
function rcp_get_connected_term_ids( $post_id = 0 ) {
	$taxonomies = array_values( get_taxonomies( array( 'public' => true ) ) );
	$terms      = wp_get_object_terms( $post_id, $taxonomies, array( 'fields' => 'ids' ) );

	return $terms;
}

/**
 * Gets all post IDs that are assigned to restricted taxonomy terms.
 *
 * @since 2.7
 * @return array An array of post IDs assigned to restricted taxonomy terms.
 */
function rcp_get_post_ids_assigned_to_restricted_terms() {

	global $wpdb;

	if ( false === ( $post_ids = get_transient( 'rcp_post_ids_assigned_to_restricted_terms' ) ) ) {
		$post_ids = array();

		/**
		 * Get all terms with the 'rcp_restricted_meta' key.
		 */
		$terms = get_terms(
			array_values( get_taxonomies( array( 'public' => true ) ) ),
			array(
				'hide_empty' => false,
				'meta_query' => array(
					array(
						'key' => 'rcp_restricted_meta',
					),
				),
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			set_transient( 'rcp_post_ids_assigned_to_restricted_terms', array(), DAY_IN_SECONDS );
			return array();
		}

		foreach ( $terms as $term ) {

			/**
			 * For legacy reasons, we need to check for empty meta
			 * and for meta with just an access_level of 'None'
			 * and ignore them.
			 */
			$meta = get_term_meta( $term->term_id, 'rcp_restricted_meta', true );

			if ( empty( $meta ) ) {
				// Remove the legacy metadata
				delete_term_meta( $term->term_id, 'rcp_restricted_meta' );
				continue;
			}

			if ( 1 === count( $meta ) && array_key_exists( 'access_level', $meta ) && 'None' === $meta['access_level'] ) {
				// Remove the legacy metadata
				delete_term_meta( $term->term_id, 'rcp_restricted_meta' );
				continue;
			}

			$p_ids = $wpdb->get_results( $wpdb->prepare( "SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d", absint( $term->term_taxonomy_id ) ), ARRAY_A );
			foreach ( $p_ids as $p_id ) {
				if ( ! in_array( $p_id['object_id'], $post_ids ) ) {
					$post_ids[] = $p_id['object_id'];
				}
			}
		}

		set_transient( 'rcp_post_ids_assigned_to_restricted_terms', $post_ids, DAY_IN_SECONDS );
	}

	return $post_ids;
}

/**
 * Gets a list of post IDs with post-level restrictions defined.
 *
 * @since 2.7
 * @return array An array of post IDs.
 */
function rcp_get_restricted_post_ids() {

	if ( false === ( $post_ids = get_transient( 'rcp_restricted_post_ids' ) ) ) {

		$post_ids = get_posts(
			array(
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_type'      => 'any',
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'   => '_is_paid',
						'value' => 1,
					),
					array(
						'key' => 'rcp_subscription_level',
					),
					array(
						'key'     => 'rcp_user_level',
						'value'   => 'All',
						'compare' => '!=',
					),
					array(
						'key'     => 'rcp_access_level',
						'value'   => 'None',
						'compare' => '!=',
					),
				),
			)
		);

		set_transient( 'rcp_restricted_post_ids', $post_ids, DAY_IN_SECONDS );
	}

	return $post_ids;
}

/**
 * Clears the transient that holds the post IDs with post-level restrictions defined.
 *
 * @param int $post_id
 *
 * @since 2.7
 */
function rcp_delete_transient_restricted_post_ids( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	delete_transient( 'rcp_restricted_post_ids' );
	delete_transient( 'rcp_post_ids_assigned_to_restricted_terms' );
}
add_action( 'save_post', 'rcp_delete_transient_restricted_post_ids' );
add_action( 'wp_trash_post', 'rcp_delete_transient_restricted_post_ids' );
add_action( 'untrash_post', 'rcp_delete_transient_restricted_post_ids' );

/**
 * Clears the transient that holds the post IDs that are assigned to restricted taxonomy terms.
 *
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 *
 * @return void
 */
function rcp_delete_transient_post_ids_assigned_to_restricted_terms( $term_id, $tt_id, $taxonomy ) {
	delete_transient( 'rcp_post_ids_assigned_to_restricted_terms' );
}
add_action( 'edited_term', 'rcp_delete_transient_post_ids_assigned_to_restricted_terms', 10, 3 );

/**
 * Log a message to the debug file if debug mode is enabled.
 *
 * @param string $message Message to log.
 * @param bool   $force   Whether to force log a message, even if debugging is disabled.
 *
 * @since 2.9
 * @return void
 */
function rcp_log( $message = '', $force = false ) {
	global $rcp_options;

	if ( empty( $rcp_options['debug_mode'] ) && ! $force ) {
		return;
	}

	$logs = new RCP_Logging();
	$logs->log( $message );
}

if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
}

/**
 * Get the name of the membership levels database table.
 *
 * @return string
 */
function rcp_get_levels_db_name() {
	global $wpdb;

	$prefix = is_plugin_active_for_network( plugin_basename( RCP_PLUGIN_FILE ) ) ? '' : $wpdb->prefix;

	if ( defined( 'RCP_NETWORK_SEPARATE_SITES' ) && RCP_NETWORK_SEPARATE_SITES ) {
		$prefix = $wpdb->prefix;
	}

	return apply_filters( 'rcp_levels_db_name', $prefix . 'restrict_content_pro' );
}

/**
 * Get the name of the membership level meta database table.
 *
 * @return string
 */
function rcp_get_level_meta_db_name() {
	global $wpdb;

	$prefix = is_plugin_active_for_network( plugin_basename( RCP_PLUGIN_FILE ) ) ? '' : $wpdb->prefix;

	if ( defined( 'RCP_NETWORK_SEPARATE_SITES' ) && RCP_NETWORK_SEPARATE_SITES ) {
		$prefix = $wpdb->prefix;
	}

	return apply_filters( 'rcp_level_meta_db_name', $prefix . 'rcp_subscription_level_meta' );
}

/**
 * Get the name of the discount codes database table.
 *
 * @since 3.2 Removed `rcp_discounts_db_name` filter.
 *
 * @return string
 */
function rcp_get_discounts_db_name() {
	return restrict_content_pro()->discounts_table->get_table_name();
}

/**
 * Get the name of the payments database table.
 *
 * @return string
 */
function rcp_get_payments_db_name() {
	global $wpdb;

	$prefix = is_plugin_active_for_network( plugin_basename( RCP_PLUGIN_FILE ) ) ? '' : $wpdb->prefix;

	if ( defined( 'RCP_NETWORK_SEPARATE_SITES' ) && RCP_NETWORK_SEPARATE_SITES ) {
		$prefix = $wpdb->prefix;
	}

	return apply_filters( 'rcp_payments_db_name', $prefix . 'rcp_payments' );
}

/**
 * Get the name of the payment meta database table.
 *
 * @return string
 */
function rcp_get_payment_meta_db_name() {
	global $wpdb;

	$prefix = is_plugin_active_for_network( plugin_basename( RCP_PLUGIN_FILE ) ) ? '' : $wpdb->prefix;

	if ( defined( 'RCP_NETWORK_SEPARATE_SITES' ) && RCP_NETWORK_SEPARATE_SITES ) {
		$prefix = $wpdb->prefix;
	}

	return apply_filters( 'rcp_payment_meta_db_name', $prefix . 'rcp_payment_meta' );
}

/**
 * Get the name of the customers database table.
 *
 * @since 3.0
 * @return string
 */
function rcp_get_customers_db_name() {
	return restrict_content_pro()->customers_table->get_table_name();
}

/**
 * Get the name of the memberships database table.
 *
 * @since 3.0
 * @return string
 */
function rcp_get_memberships_db_name() {
	return restrict_content_pro()->memberships_table->get_table_name();
}

/**
 * Get the name of the batch processing queue table.
 *
 * @since 3.0
 * @return string
 */
function rcp_get_queue_db_name() {
	return restrict_content_pro()->queue_table->get_table_name();
}

/**
 * Format an array of count objects, using the $groupby key.
 *
 * @param array  $counts
 * @param string $groupby
 *
 * @since 3.0
 * @return array
 */
function rcp_format_counts( $counts = array(), $groupby = '' ) {

	// Default array
	$c = array(
		'total' => 0,
	);

	// Loop through counts and shape return value
	if ( ! empty( $counts->items ) ) {

		// Loop through statuses
		foreach ( $counts->items as $count ) {
			$c[ $count[ $groupby ] ] = absint( $count['count'] );
		}

		// Total
		$c['total'] = array_sum( $c );
	}

	// Return array of counts
	return $c;

}

/**
 * Returns a translation-ready status label for display.
 *
 * @param string $status
 *
 * @since 3.0
 * @return string
 */
function rcp_get_status_label( $status = '' ) {

	static $labels = null;

	// Array of status labels
	if ( null === $labels ) {
		$labels = array(

			// General
			'active'    => __( 'Active', 'rcp' ),
			'inactive'  => __( 'Inactive', 'rcp' ),
			'pending'   => __( 'Pending', 'rcp' ),

			// Memberships
			'cancelled' => __( 'Cancelled', 'rcp' ),
			'expired'   => __( 'Expired', 'rcp' ),
			'free'      => __( 'Free', 'rcp' ), // deprecated

			// Payments
			'abandoned' => __( 'Abandoned', 'rcp' ),
			'complete'  => __( 'Complete', 'rcp' ),
			'failed'    => __( 'Failed', 'rcp' ),
			'refunded'  => __( 'Refunded', 'rcp' ),
			'new'       => __( 'New', 'rcp' ),
			'renewal'   => __( 'Renewal', 'rcp' ),
			'upgrade'   => __( 'Upgrade', 'rcp' ),
			'downgrade' => __( 'Downgrade', 'rcp' ),

			// Discount Codes
			'disabled'  => __( 'Disabled', 'rcp' ),
		);
	}

	// Return the label if set, or uppercase the first letter if not
	$final_label = isset( $labels[ $status ] )
		? $labels[ $status ]
		: ucwords( $status );

	return $final_label;

}

/**
 * Get restricted content message
 *
 * @param bool $paid Whether or not this is paid content. For backwards compatibility only.
 *
 * @since 3.0
 * @return string
 */
function rcp_get_restricted_content_message( $paid = false ) {
	global $post, $rcp_options;

	// If we have a "new" global restricted message, use that and be done
	if ( ! empty( $rcp_options['restriction_message'] ) ) {
		return apply_filters( 'rcp_restricted_content_message', $rcp_options['restriction_message'] );
	}

	$message = __( 'This content is restricted to subscribers', 'rcp' );

	if ( ! empty( $rcp_options['free_message'] ) ) {
		$message = $rcp_options['free_message'];
	}

	if ( ! empty( $rcp_options['paid_message'] ) && ( rcp_is_paid_content( $post->ID ) || rcp_has_term_restrictions( $post->ID ) || $paid ) ) {
		$message = $rcp_options['paid_message'];
	}

	return apply_filters( 'rcp_restricted_content_message', $message );
}

/**
 * Compares the WordPress version with the given version.
 *
 * @param string $version   The version to compare with.
 * @param string $operator  The operator.
 * @param bool   $allow_dev Whether to treat dev versions as stable.
 *
 * @since 3.5.10
 *
 * @return bool
 */
function rcp_wp_version_compare( $version, $operator, $allow_dev = true ) {
	global $wp_version;
	if ( $allow_dev ) {
		list( $wp_version ) = explode( '-', $wp_version );
	}
	return version_compare( $wp_version, $version, $operator );
}

/**
 * Checks if the WordPress version is at least the given version.
 *
 * @param string $version   The version to check WP for.
 * @param bool   $allow_dev Whether to treat dev versions as stable.
 *
 * @since 3.5.10
 *
 * @return bool
 */
function rcp_is_wp_version_at_least( $version, $allow_dev = true ) {
	return rcp_wp_version_compare( $version, '>=', $allow_dev );
}

/**
 * Sanitized the text field using WordPress functions. This is useful to use it in many fields and prevent
 * PHPCS asking to sanitized the field, plush the field will be sanitized.
 *
 * @since 3.5.25
 *
 * @param string $_text The text to sanitized.
 * @return string The sanitized string.
 */
function rcp_sanitize_request_field( $_text ) {
	return sanitize_text_field( wp_unslash( $_text ) );
}
/**
 * Returns an associative array with the list of RCP Add-Ons.
 *
 * @since 3.5.28
 */
function rcp_get_addons_list(): array {
	return [
		'rcp-avatax'                       => 'Restrict Content Pro - AvaTax',
		'rcp-functionality'                => 'Restrict Content Pro - Custom Functionality',
		'rcp-authorize-net'                => 'Restrict Content Pro - Authorize.net',
		'rcp-ultimate-member'              => 'RCP Ultimate Member',
		'rcp-group-accounts'               => 'Restrict Content Pro - Group Accounts',
		'rcp-drip-content'                 => 'Restrict Content Pro - Drip Content',
		'rcp-mailchimp-pro'                => 'Restrict Content Pro - MailChimp Pro',
		'rcp-site-creation'                => 'Restrict Content Pro - Site Creation',
		'rcp-timelock'                     => 'Restrict Content Pro - Timelock',
		'rcp-edd-member-downloads'         => 'Restrict Content Pro - EDD Member Downloads',
		'rcp-strong-passwords'             => 'Restrict Content Pro - Enforce Strong Passwords',
		'rcp-edd-wallet'                   => 'Restrict Content Pro - Easy Digital Downloads Wallet',
		'rcp-restriction-timeouts'         => 'Restrict Content Pro - Restriction Timeouts',
		'rcp-limited-quantity-available'   => 'Restrict Content Pro - Limited Quantity Available',
		'rcp-mailchimp'                    => 'Restrict Content Pro - MailChimp',
		'rcp-gravity-forms'                => 'GF RCP connection',
		'rcp-woocommerce-member-discounts' => 'Restrict Content Pro - WooCommerce Member Discounts',
		'rcp-woocommerce-checkout'         => 'Restrict Content Pro - WooCommerce Checkout',
		'rcp-usage-tracking'               => 'Restrict Content Pro - Usage Tracking',
		'rcp-stripe-connect-proxy'         => 'Sandhills: Stripe Connect proxy for Restrict Content Pro',
		'rcp-site-child'                   => 'RCP Site child theme',
		'rcp-restrict-past-content'        => 'Restrict Content Pro - Restrict Past Content',
		'rcp-rest-api'                     => 'Restrict Content Pro - REST API',
		'rcp-per-level-emails'             => 'Restrict Content Pro - Per-Level Emails',
		'rcp-math-verification'            => 'Restrict Content Pro - Math Verification',
		'rcp-mailerlite'                   => 'Restrict Content Pro - MailerLite',
		'rcp-library'                      => 'Restrict Content Pro Code Snippet Library',
		'rcp-ip-restriction'               => 'Restrict Content Pro - IP Restriction',
		'rcp-honeypot'                     => 'Restrict Content Pro - Honeypot',
		'rcp-help-scout'                   => 'Restrict Content Pro - Help Scout',
		'rcp-hardset-expiration-dates'     => 'Restrict Content Pro - Hard-set Expiration Dates',
		'rcp-gifts-through-edd'            => 'Restrict Content Pro - Gift Memberships',
		'rcp-getresponse'                  => 'Restrict Content Pro - GetResponse',
		'rcp-forms'                        => 'Restrict Content Pro - Forms',
		'rcp-fatwpl'                       => 'Restrict Content Pro - Force Admins to wp-login.php',
		'rcp-dev-tools'                    => 'Restrict Content Pro - Dev Tools',
		'rcp-custom-redirects'             => 'Restrict Content Pro - Custom Redirects',
		'rcp-convertkit'                   => 'Restrict Content Pro - ConvertKit',
		'rcp-braintree'                    => 'Restrict Content Pro - Braintree Gateway',
		'rcp-aweber-pro'                   => 'Restrict Content Pro - AWeber Pro',
		'rcp-add-on-version-response'      => 'RCP Add-On Version Response',
		'rcp-activecampaign'               => 'Restrict Content Pro - ActiveCampaign',
		'rcp-view-limit'                   => 'Restrict Content Pro - View Limit',
		'rcp-upgrade-paths'                => 'RCP Upgrade Paths',
		'rcp-custom-renew'                 => 'RCP Custom Renew',
		'restrict-content-pro-buddypress'  => 'Restrict Content Pro - BuddyPress',
	];
}

/**
 * Determines whether or not the current page is an RCP admin page.
 *
 * @since 3.3.7
 * @return bool
 */
function rcp_is_rcp_admin_page() {
	$is_admin = false;
	$screen   = get_current_screen();

	if ( $screen ) {
		global $rcp_members_page, $rcp_customers_page, $rcp_subscriptions_page, $rcp_discounts_page, $rcp_payments_page, $rcp_reports_page, $rcp_settings_page, $rcp_help_page, $rcp_tools_page, $restrict_content_pro_welcome_page, $restrict_content_pro_help_page, $restrict_content_pro_addons;
		$pages = array( $rcp_members_page, $rcp_customers_page, $rcp_subscriptions_page, $rcp_discounts_page, $rcp_payments_page, $rcp_reports_page, $rcp_settings_page, $rcp_tools_page, $rcp_help_page, $restrict_content_pro_welcome_page, $restrict_content_pro_help_page, $restrict_content_pro_addons );

		// Include post types that support restrictions.
		if (
			'post' === $screen->base
			&& ! empty( $screen->post_type )
			&& in_array( $screen->post_type, rcp_get_metabox_post_types(), true )
		) {
			$pages[] = $screen->id;
		}

		if ( false !== strpos( $screen->id, 'rcp-restrict-post-type' ) ) {
			$pages[] = $screen->id;
		}

		$is_admin = in_array( $screen->id, $pages, true );
	}

	/**
	 * Filters whether or not the current page is an RCP admin page.
	 *
	 * @param bool           $is_admin Whether or not the current page is an RCP admin page.
	 * @param WP_Screen|null $screen   The current screen object.
	 *
	 * @since 3.3.7
	 */
	return apply_filters( 'rcp_is_rcp_admin_page', $is_admin, $screen );
}

/**
 * Creates a protected directory to store plugin files.
 *
 * @since 3.5.39
 *
 * @param string $dir_path The path to the directory to create.
 *
 * @return bool True if the directory was created and protected, false otherwise.
 */
function rcp_create_protected_directory( string $dir_path ): bool {
	try {
		// create the directory if it doesn't exist.

		if ( ! is_dir( $dir_path ) ) {
			wp_mkdir_p( $dir_path );
		}

		if ( ! is_writable( $dir_path ) ) {
			return false;
		}

		// create .htaccess file to protect the directory.

		$htaccess_path = trailingslashit( $dir_path ) . '.htaccess';

		if ( ! file_exists( $htaccess_path ) ) {
			$result = file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
				$htaccess_path,
				'Order Allow,Deny' . PHP_EOL . 'Deny from all' . PHP_EOL
			);

			if ( ! $result ) {
				return false;
			}
		}

		// create the index.php file to prevent directory listing.

		$index_path = trailingslashit( $dir_path ) . 'index.php';

		if ( ! file_exists( $index_path ) ) {
			$result = file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
				$index_path,
				'<?php' . PHP_EOL . '// Silence is golden.' . PHP_EOL
			);

			if ( ! $result ) {
				return false;
			}
		}
	} catch ( Throwable $th ) {
		WP_DEBUG && error_log( $th->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only if debug is enabled.
		return false;
	}

	return true;
}
