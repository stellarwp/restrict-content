<?php
/**
 * Deprecated Functions
 *
 * These are kept here for backwards compatibility with extensions that might be using them
 *
 * @package     Restrict Content Pro
 * @subpackage  Deprecated Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

/**
 * Retrieve all payments from database
 *
 * @deprecated  1.5
 * @access      private
 * @param       int $offset The number to skip
 * @param       int $number The number to retrieve
 *
 * @return      array
*/
function rcp_get_payments( $offset = 0, $number = 20 ) {
	global $wpdb, $rcp_payments_db_name;
	if( $number > 0 ) {
		$payments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->escape( $rcp_payments_db_name ) . " ORDER BY id DESC LIMIT %d,%d;", absint( $offset ), absint( $number ) ) );
	} else {
		// when retrieving all payments, the query is cached
		$payments = get_transient( 'rcp_payments' );
		if( $payments === false ) {
			$payments = $wpdb->get_results( "SELECT * FROM " . $wpdb->escape( $rcp_payments_db_name ) . " ORDER BY id DESC;" ); // this is to get all payments
			set_transient( 'rcp_payments', $payments, 10800 );
		}
	}
	return $payments;
}


/**
 * Retrieve the total number of payments in the database
 *
 * @deprecated  1.5
 * @access      private
 * @return      int
*/
function rcp_count_payments() {
	global $wpdb, $rcp_payments_db_name;
	$count = get_transient( 'rcp_payments_count' );
	if( $count === false ) {
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM " . $rcp_payments_db_name . ";" );
		set_transient( 'rcp_payments_count', $count, 10800 );
	}
	return $count;
}


/**
 * Retrieve total site earnings
 *
 * @deprecated  1.5
 * @access      private
 * @return      float
*/
function rcp_get_earnings() {
	global $wpdb, $rcp_payments_db_name;
	$payments = get_transient( 'rcp_earnings' );
	if( $payments === false ) {
		$payments = $wpdb->get_results( "SELECT amount FROM " . $rcp_payments_db_name . ";" );
		// cache the payments query
		set_transient( 'rcp_earnings', $payments, 10800 );
	}
	$total = (float) 0.00;
	if( $payments ) :
		foreach( $payments as $payment ) :
			$total = $total + $payment->amount;
		endforeach;
	endif;
	return $total;
}


/**
 * Insert a payment into the database
 *
 * @deprecated  1.5
 * @access      private
 * @param       array $payment_data The data to store
 *
 * @return      INT the ID of the new payment, or false if insertion fails
*/
function rcp_insert_payment( $payment_data = array() ) {
	global $rcp_payments_db;
	return $rcp_payments_db->insert( $payment_data );
}


/**
 * Check if a payment already exists
 *
 * @deprecated  1.5
 * @access      private
 * @param       $type string The type of payment (web_accept, subscr_payment, Credit Card, etc)
 * @param       $date string/date The date of tpaen
 * @param       $subscriptionkey string The subscription key the payment is connected to
 * @return      bool
*/
function rcp_check_for_existing_payment( $type, $date, $subscription_key ) {

	global $wpdb, $rcp_payments_db_name;

	if( $wpdb->get_results( $wpdb->prepare("SELECT id FROM " . $rcp_payments_db_name . " WHERE `date`='%s' AND `subscription_key`='%s' AND `payment_type`='%s';", $date, $subscription_key, $type ) ) )
		return true; // this payment already exists

	return false; // this payment doesn't exist
}


/**
 * Retrieves the amount for the lat payment made by a user
 *
 * @access      private
 * @param       int $user_id The ID of the user to retrieve a payment amount for
 * @return      float
*/
function rcp_get_users_last_payment_amount( $user_id = 0 ) {
	global $wpdb, $rcp_payments_db_name;
	$query = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $rcp_payments_db_name . " WHERE `user_id`='%d' ORDER BY id DESC LIMIT 1;", $user_id ) );
	return $query[0]->amount;
}


/**
 * Calculates a new expiration
 *
 * @deprecated  2.4
 * @access      private
 * @param       $expiration_object
 * @return      string
 */
function rcp_calc_member_expiration( $expiration_object ) {

	$member_expires = 'none';

	if( $expiration_object->duration > 0 ) {

		$current_time       = current_time( 'timestamp' );
		$last_day           = cal_days_in_month( CAL_GREGORIAN, date( 'n', $current_time ), date( 'Y', $current_time ) );

		$expiration_unit 	= $expiration_object->duration_unit;
		$expiration_length 	= $expiration_object->duration;
		$member_expires 	= date( 'Y-m-d H:i:s', strtotime( '+' . $expiration_length . ' ' . $expiration_unit . ' 23:59:59', current_time( 'timestamp' ) ) );

		if( date( 'j', $current_time ) == $last_day && 'day' != $expiration_unit ) {
			$member_expires = date( 'Y-m-d H:i:s', strtotime( $member_expires . ' +2 days', current_time( 'timestamp' ) ) );
		}

	}

	return apply_filters( 'rcp_calc_member_expiration', $member_expires, $expiration_object );
}

/**
 * Generate URL to download a PDF invoice
 *
 * @since 2.0
 * @deprecated 2.6 Use rcp_get_invoice_url() instead.
 * @return string
*/
function rcp_get_pdf_download_url( $payment_id = 0 ) {
	return rcp_get_invoice_url( $payment_id );
}

/**
 * User level checks
 *
 * @deprecated 2.7
 * @return void
 */
function rcp_user_level_checks() {
	if ( current_user_can( 'read' ) ) {
		if ( current_user_can( 'edit_posts' ) ) {
			if ( current_user_can( 'upload_files' ) ) {
				if ( current_user_can( 'moderate_comments' ) ) {
					if ( current_user_can( 'switch_themes' ) ) {
						//do nothing here for admin
					} else {
						add_filter( 'the_content', 'rcp_display_message_to_editors' );
					}
				} else {
					add_filter( 'the_content', 'rcp_display_message_authors' );
				}
			} else {
				add_filter( 'the_content', 'rcp_display_message_to_contributors' );
			}
		} else {
			add_filter( 'the_content', 'rcp_display_message_to_subscribers' );
		}
	} else {
		add_filter( 'the_content', 'rcp_display_message_to_non_loggged_in_users' );
	}
}
// add_action( 'loop_start', 'rcp_user_level_checks' );

/**
 * Display message to editors
 *
 * @deprecated 2.7
 *
 * @param string $content
 *
 * @return string
 */
function rcp_display_message_to_editors( $content ) {
	global $rcp_options, $post, $user_ID;

	$message = $rcp_options['free_message'];
	$paid_message = $rcp_options['paid_message'];
	if ( rcp_is_paid_content( $post->ID ) ) {
		$message = $paid_message;
	}

	$user_level = get_post_meta( $post->ID, 'rcp_user_level', true );
	$access_level = get_post_meta( $post->ID, 'rcp_access_level', true );

	$has_access = false;
	if ( rcp_user_has_access( $user_ID, $access_level ) ) {
		$has_access = true;
	}

	if ( $user_level == 'Administrator' && $has_access ) {
		return rcp_format_teaser( $message );
	}
	return $content;
}

/**
 * Display message to authors
 *
 * @deprecated 2.7
 *
 * @param string $content
 *
 * @return string
 */
function rcp_display_message_authors( $content ) {
	global $rcp_options, $post, $user_ID;

	$message = $rcp_options['free_message'];
	$paid_message = $rcp_options['paid_message'];
	if ( rcp_is_paid_content( $post->ID ) ) {
		$message = $paid_message;
	}

	$user_level = get_post_meta( $post->ID, 'rcp_user_level', true );
	$access_level = get_post_meta( $post->ID, 'rcp_access_level', true );

	$has_access = false;
	if ( rcp_user_has_access( $user_ID, $access_level ) ) {
		$has_access = true;
	}

	if ( ( $user_level == 'Administrator' || $user_level == 'Editor' )  && $has_access ) {
		return rcp_format_teaser( $message );
	}
	// return the content unfilitered
	return $content;
}

/**
 * Display message to contributors
 *
 * @deprecated 2.7
 *
 * @param string $content
 *
 * @return string
 */
function rcp_display_message_to_contributors( $content ) {
	global $rcp_options, $post, $user_ID;

	$message = $rcp_options['free_message'];
	$paid_message = $rcp_options['paid_message'];
	if ( rcp_is_paid_content( $post->ID ) ) {
		$message = $paid_message;
	}

	$user_level = get_post_meta( $post->ID, 'rcp_user_level', true );
	$access_level = get_post_meta( $post->ID, 'rcp_access_level', true );

	$has_access = false;
	if ( rcp_user_has_access( $user_ID, $access_level ) ) {
		$has_access = true;
	}

	if ( ( $user_level == 'Administrator' || $user_level == 'Editor' || $user_level == 'Author' ) && $has_access ) {
		return rcp_format_teaser( $message );
	}
	// return the content unfilitered
	return $content;
}

/**
 * Display message to subscribers
 *
 * @deprecated 2.7
 *
 * @param string $content
 *
 * @return string
 */
function rcp_display_message_to_subscribers( $content ) {
	global $rcp_options, $post, $user_ID;

	$message      = isset( $rcp_options['free_message'] ) ? $rcp_options['free_message'] : '';
 	$paid_message = isset( $rcp_options['paid_message'] ) ? $rcp_options['paid_message'] : '';

	if ( rcp_is_paid_content( $post->ID ) ) {
		$message = $paid_message;
	}

	$user_level = get_post_meta( $post->ID, 'rcp_user_level', true );
	$access_level = get_post_meta( $post->ID, 'rcp_access_level', true );

	$has_access = false;
	if ( rcp_user_has_access( $user_ID, $access_level ) ) {
		$has_access = true;
	}
	if ( $user_level == 'Administrator' || $user_level == 'Editor' || $user_level == 'Author' || $user_level == 'Contributor' || !$has_access ) {
		return rcp_format_teaser( $message );
	}
	// return the content unfilitered
	return $content;
}

/**
 * Display error message to non-logged in users
 *
 * @deprecated 2.7
 *
 * @param string $content
 *
 * @return string
 */
function rcp_display_message_to_non_loggged_in_users( $content ) {
	global $rcp_options, $post, $user_ID;

	$message      = isset( $rcp_options['free_message'] ) ? $rcp_options['free_message'] : '';
	$paid_message = isset( $rcp_options['paid_message'] ) ? $rcp_options['paid_message'] : '';

	if ( rcp_is_paid_content( $post->ID ) ) {
		$message = $paid_message;
	}

	$user_level   = get_post_meta( $post->ID, 'rcp_user_level', true );
	$access_level = get_post_meta( $post->ID, 'rcp_access_level', true );
	$has_access   = false;

	if ( rcp_user_has_access( $user_ID, $access_level ) ) {
		$has_access = true;
	}

	if ( ! is_user_logged_in() && ( $user_level == 'Administrator' || $user_level == 'Editor' || $user_level == 'Author' || $user_level == 'Contributor' || $user_level == 'Subscriber' ) && $has_access ) {
		return rcp_format_teaser( $message );
	}

	// return the content unfilitered
	return $content;
}

/**
 * Parses email template tags
 *
 * @deprecated 2.7
*/
function rcp_filter_email_tags( $message, $user_id, $display_name ) {

	$user = get_userdata( $user_id );

	$site_name = stripslashes_deep( html_entity_decode( get_bloginfo('name'), ENT_COMPAT, 'UTF-8' ) );

	$rcp_payments = new RCP_Payments();

	$message = str_replace( '%blogname%', $site_name, $message );
	$message = str_replace( '%username%', $user->user_login, $message );
	$message = str_replace( '%useremail%', $user->user_email, $message );
	$message = str_replace( '%firstname%', html_entity_decode( $user->first_name, ENT_COMPAT, 'UTF-8' ), $message );
	$message = str_replace( '%lastname%', html_entity_decode( $user->last_name, ENT_COMPAT, 'UTF-8' ), $message );
	$message = str_replace( '%displayname%', html_entity_decode( $display_name, ENT_COMPAT, 'UTF-8' ), $message );
	$message = str_replace( '%expiration%', rcp_get_expiration_date( $user_id ), $message );
	$message = str_replace( '%subscription_name%', html_entity_decode( rcp_get_subscription($user_id), ENT_COMPAT, 'UTF-8' ), $message );
	$message = str_replace( '%subscription_key%', rcp_get_subscription_key( $user_id ), $message );
	$message = str_replace( '%amount%', html_entity_decode( rcp_currency_filter( $rcp_payments->last_payment_of_user( $user_id ) ), ENT_COMPAT, 'UTF-8' ), $message );

	return apply_filters( 'rcp_email_tags', $message, $user_id );
}

/**
 * reverse of strstr()
 *
 * @deprecated 2.7.2
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return string
 */
function rcp_rstrstr( $haystack, $needle ) {
	return substr( $haystack, 0, strpos( $haystack, $needle ) );
}

/**
 * Filter restricted content based on category restrictions
 *
 * @deprecated 2.7 This is now covered by rcp_filter_restricted_content()
 *
 * @access      public
 * @since       2.0
 * @return      $content
 */
function rcp_filter_restricted_category_content( $content ) {
	global $post, $rcp_options;

	$restrictions = array();

	foreach( rcp_get_restricted_taxonomies() as $taxonomy ) {
		$restriction = rcp_is_post_taxonomy_restricted( $post->ID, $taxonomy );

		// -1 means that the taxonomy terms are unrestricted
		if ( -1 === $restriction ) {
			continue;
		}

		// true or false. Whether or not the user has access to the restricted taxonomy terms
		$restrictions[] = $restriction;

	}

	if ( empty( $restrictions ) ) {
		return $content;
	}

	$restricted = ( apply_filters( 'rcp_restricted_taxonomy_match_all', false ) ) ? false !== array_search( true, $restrictions ) : false === array_search( false, $restrictions );

	if ( $restricted ) {

		$message = ! empty( $rcp_options['paid_message'] ) ? $rcp_options['paid_message'] : __( 'You need to have an active subscription to view this content.', 'rcp' );

		return rcp_format_teaser( $message );

	}

	return $content;

}
// add_filter( 'the_content', 'rcp_filter_restricted_category_content', 101 );

/**
 * Disallow access to restricted content if the user is pending email verification.
 *
 * @deprecated 2.9 Moved to RCP_Member::can_access()
 *
 * @param bool       $can_access Whether or not the user can access the post.
 * @param int        $user_id    ID of the user being checked.
 * @param int        $post_id    ID of the post being checked.
 * @param RCP_Member $member     Member object.
 *
 * @return bool
 */
function rcp_disallow_access_pending_verification( $can_access, $user_id, $post_id, $member ) {

	if ( rcp_is_restricted_content( $post_id ) && $member->is_pending_verification() ) {
		return false;
	}

	return $can_access;

}
//add_filter( 'rcp_member_can_access', 'rcp_disallow_access_pending_verification', 10, 4 );

/**
 * Retrieve the renewal reminder periods
 *
 * @deprecated 2.9 Use RCP_Reminders::get_notice_periods() instead.
 * @see RCP_Reminders::get_notice_periods()
 *
 * @since       1.6
 * @access      public
 * @return      array
 */
function rcp_get_renewal_reminder_periods() {
	$periods = array(
		'none'      => __( 'None, reminders disabled', 'rcp' ),
		'+1 day'    => __( 'One day before expiration', 'rcp' ),
		'+2 days'   => __( 'Two days before expiration', 'rcp' ),
		'+3 days'   => __( 'Three days before expiration', 'rcp' ),
		'+4 days'   => __( 'Four days before expiration', 'rcp' ),
		'+5 days'   => __( 'Five days before expiration', 'rcp' ),
		'+6 days'   => __( 'Six days before expiration', 'rcp' ),
		'+1 week'   => __( 'One week before expiration', 'rcp' ),
		'+2 weeks'  => __( 'Two weeks before expiration', 'rcp' ),
		'+3 weeks'  => __( 'Three weeks before expiration', 'rcp' ),
		'+1 month'  => __( 'One month before expiration', 'rcp' ),
		'+2 months' => __( 'Two months before expiration', 'rcp' ),
		'+3 months' => __( 'Three months before expiration', 'rcp' ),
	);
	return apply_filters( 'rcp_renewal_reminder_periods', $periods );
}


/**
 * Retrieve the renewal reminder period that is enabled
 *
 * @deprecated 2.9 Multiple periods are now available in new reminders feature.
 * @see RCP_Reminders
 *
 * @since       1.6
 * @access      public
 * @return      string
 */
function rcp_get_renewal_reminder_period() {
	global $rcp_options;
	$period = isset( $rcp_options['renewal_reminder_period'] ) ? $rcp_options['renewal_reminder_period'] : 'none';
	return apply_filters( 'rcp_get_renewal_reminder_period', $period );
}

/**
 * Log Types.
 *
 * Sets up the valid log types for WP_Logging.
 *
 * @deprecated 2.9 Using new RCP_Logging class instead.
 *
 * @param array $types Existing log types.
 *
 * @access private
 * @since  1.3.4
 * @return array
 */
function rcp_log_types( $types ) {

	$types = array(
		'gateway_error'
	);
	return $types;

}
add_filter( 'wp_log_types', 'rcp_log_types' );

/**
 * Filter content in RSS feeds.
 *
 * @deprecated 2.9 The "hide from feed" meta field was removed and content is already filtered for RSS
 *                 feeds in rcp_filter_restricted_content().
 * @see        rcp_filter_restricted_content()
 *
 * @param string $content
 *
 * @return string
 */
function rcp_filter_feed_posts( $content ) {
	global $rcp_options;

	if( ! is_feed() )
		return $content;

	$hide_from_feed = get_post_meta( get_the_ID(), 'rcp_hide_from_feed', true );
	if ( $hide_from_feed == 'on' ) {
		if( rcp_is_paid_content( get_the_ID() ) ) {
			return rcp_format_teaser( $rcp_options['paid_message'] );
		} else {
			return rcp_format_teaser( $rcp_options['free_message'] );
		}
	}
	return do_shortcode( $content );

}
//add_action( 'the_excerpt', 'rcp_filter_feed_posts' );
//add_action( 'the_content', 'rcp_filter_feed_posts' );

/**
 * Prints payment history for the specified user
 *
 * @deprecated 2.9.1 Use rcp_print_user_payments_formatted() instead.
 * @see rcp_print_user_payments_formatted()
 *
 * @param $user_id
 *
 * @return string
 */
function rcp_print_user_payments( $user_id ) {
	$payments = new RCP_Payments;
	$user_payments = $payments->get_payments( array( 'user_id' => $user_id ) );
	$payments_list = '';
	if( $user_payments ) :
		foreach( $user_payments as $payment ) :
			$transaction_id = ! empty( $payment->transaction_id ) ? $payment->transaction_id : '';
			$payments_list .= '<ul class="rcp_payment_details">';
			$payments_list .= '<li>' . __( 'Date', 'rcp' ) . ': ' . $payment->date . '</li>';
			$payments_list .= '<li>' . __( 'Subscription', 'rcp' ) . ': ' . $payment->subscription . '</li>';
			$payments_list .= '<li>' . __( 'Payment Type', 'rcp' ) . ': ' . $payment->payment_type . '</li>';
			$payments_list .= '<li>' . __( 'Subscription Key', 'rcp' ) . ': ' . $payment->subscription_key . '</li>';
			$payments_list .= '<li>' . __( 'Transaction ID', 'rcp' ) . ': ' . $transaction_id . '</li>';
			if( $payment->amount != '' ) {
				$payments_list .= '<li>' . __( 'Amount', 'rcp' ) . ': ' . rcp_currency_filter( $payment->amount ) . '</li>';
			} else {
				$payments_list .= '<li>' . __( 'Amount', 'rcp' ) . ': ' . rcp_currency_filter( $payment->amount2 ) . '</li>';
			}
			$payments_list .= '</ul>';
		endforeach;
	else :
		$payments_list = '<p class="rcp-no-payments">' . __( 'No payments recorded', 'rcp' ) . '</p>';
	endif;
	return $payments_list;
}

/**
 * Log a user in
 *
 * @deprecated 3.0 Deprecated in favor of using wp_signon() from WordPress core.
 *
 * @param int    $user_id    ID of the user to login.
 * @param string $user_login Login name of the user.
 * @param bool   $remember   Whether or not to remember the user.
 *
 * @since  1.0
 * @return void
 */
function rcp_login_user_in( $user_id, $user_login, $remember = false ) {
	$user = get_userdata( $user_id );
	if( ! $user )
		return;
	wp_set_auth_cookie( $user_id, $remember );
	wp_set_current_user( $user_id, $user_login );
	do_action( 'wp_login', $user_login, $user );
}

/**
 * The default length for excerpts.
 *
 * @deprecated 2.9.14
 *
 * @param int $excerpt_length Number of words to show in the excerpt.
 *
 * @access private
 * @return string
 */
function rcp_excerpt_length( $excerpt_length ) {
	// the number of words to show in the excerpt
	return 100;
}
//add_filter( 'rcp_filter_excerpt_length', 'rcp_excerpt_length' );

/**
 * Check PayPal return price after applying discount.
 *
 * @deprecated 3.0
 *
 * @param float $price
 * @param float $amount
 * @param float $amount2
 * @param int $user_id
 *
 * @return bool
 */
function rcp_check_paypal_return_price_after_discount( $price, $amount, $amount2, $user_id ) {
	// get an array of all discount codes this user has used
	$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );
	if( !is_array( $user_discounts ) || $user_discounts == '' ) {
		// this user has never used a discount code
		return false;
	}
	foreach( $user_discounts as $discount_code ) {
		if( !rcp_validate_discount( $discount_code ) ) {
			// discount code is inactive
			return false;
		}
		$code_details = rcp_get_discount_details_by_code( $discount_code );
		$discounted_price = rcp_get_discounted_price( $price, $code_details->amount, $code_details->unit );
		if( $discounted_price == $amount || $discounted_price == $amount2 ) {
			return true;
		}
	}
	return false;
}

/**
 * Retrieves the member's ID from their payment processor's subscription ID
 *
 * @deprecated 3.0 Use `rcp_get_membership_by` instead.
 * @see rcp_get_membership_by()
 *
 * @param   string $subscription_id
 *
 * @since   2.8
 * @return  int|false User ID if found, false if not.
 */
function rcp_get_member_id_from_subscription_id( $subscription_id = '' ) {

	global $wpdb;

	$customer_table    = rcp_get_customers_db_name();
	$memberships_table = rcp_get_memberships_db_name();

	$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$customer_table} c INNER JOIN {$memberships_table} m ON c.id = m.customer_id WHERE gateway_subscription_id = %s LIMIT 1", $subscription_id ) );

	if ( $user_id != NULL ) {
		return $user_id;
	}

	return false;
}

/**
 * Get the prorate amount for this member
 *
 * @deprecated 3.0 Use `rcp_get_membership_prorate_credit` instead.
 * @see rcp_get_membership_prorate_credit()
 *
 * @param int $user_id
 *
 * @since 2.5
 * @return int
 */
function rcp_get_member_prorate_credit( $user_id = 0 ) {
	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );

	return $member->get_prorate_credit_amount();
}

/**
 * Gets the cancellation URL for a member
 *
 * @deprecated 3.0 Use `rcp_get_membership_cancel_url` instead.
 * @see rcp_get_membership_cancel_url()
 *
 * @param int $user_id The user ID to get the link for, or 0 for the current user.
 *
 * @access  public
 * @since   2.1
 * @return  string Cancellation URL.
 */
function rcp_get_member_cancel_url( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$url        = '';
	$customer   = rcp_get_customer_by_user_id( $user_id );
	$membership = ! empty( $customer ) ? rcp_get_customer_single_membership( $customer->get_id() ) : false;

	if( ! empty( $membership ) && $membership->is_recurring() ) {

		$url = wp_nonce_url( add_query_arg( array( 'rcp-action' => 'cancel', 'membership-id' => $membership->get_id() ) ), 'rcp-cancel-nonce' );

	}

	return apply_filters( 'rcp_member_cancel_url', $url, $user_id );
}

/**
 * Process a member cancellation request
 *
 * @deprecated 3.0 Use `rcp_process_membership_cancellation` instead.
 * @see rcp_process_membership_cancellation()
 *
 * @access  public
 * @since   2.1
 * @return  void
 */
function rcp_process_member_cancellation() {

	if( ! isset( $_GET['rcp-action'] ) || $_GET['rcp-action'] !== 'cancel' ) {
		return;
	}

	if( ! is_user_logged_in() ) {
		return;
	}

	if( wp_verify_nonce( $_GET['_wpnonce'], 'rcp-cancel-nonce' ) ) {

		global $rcp_options;

		$success  = rcp_cancel_member_payment_profile( get_current_user_id() );
		$redirect = remove_query_arg( array( 'rcp-action', '_wpnonce', 'member-id' ), rcp_get_current_url() );

		if( ! $success && rcp_is_paypal_subscriber() ) {
			// No profile ID stored, so redirect to PayPal to cancel manually
			$redirect = 'https://www.paypal.com/cgi-bin/customerprofileweb?cmd=_manage-paylist';
		}

		if( $success ) {

			do_action( 'rcp_process_member_cancellation', get_current_user_id() );

			$redirect = add_query_arg( 'profile', 'cancelled', $redirect );

		}

		wp_redirect( $redirect ); exit;

	}
}
//add_action( 'template_redirect', 'rcp_process_member_cancellation' );

/**
 * Cancel a member's payment profile
 *
 * @deprecated 3.0 Use `rcp_cancel_membership_payment_profile()` instead.
 * @see rcp_cancel_membership_payment_profile()
 *
 * @param int  $member_id  ID of the member to cancel.
 * @param bool $set_status Whether or not to update the status to 'cancelled'.
 *
 * @access  public
 * @since   2.1
 * @return  bool Whether or not the cancellation was successful.
 */
function rcp_cancel_member_payment_profile( $member_id = 0, $set_status = true ) {

	$customer = rcp_get_customer_by_user_id( $member_id );

	if ( empty( $customer ) ) {
		return false;
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );
	$success    = ! empty( $membership ) ? $membership->cancel_payment_profile( $set_status ) : false;

	return ! is_wp_error( $success );
}

/**
 * Determines if a member can cancel their subscription on site
 *
 * @deprecated 3.0 Use `rcp_can_membership_be_cancelled()` instead.
 * @see rcp_can_membership_be_cancelled()
 *
 * @param int $user_id The user ID to check, or 0 for the current user.
 *
 * @access  public
 * @since   2.1
 * @return  bool True if the member can cancel, false if not.
 */
function rcp_can_member_cancel( $user_id = 0 ) {

	$member = new RCP_Member( $user_id );

	return $member->can_cancel();

}

/**
 * Inserts a new note for a user
 *
 * @deprecated 3.0 Use `rcp_add_customer_note()` instead.
 * @see rcp_add_customer_note();
 *
 * @param int    $user_id ID of the user to add a note to.
 * @param string $note    Note to add.
 *
 * @since  2.0
 * @return void
 */
function rcp_add_member_note( $user_id = 0, $note = '' ) {
	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( empty( $customer ) ) {
		return;
	}

	$customer->add_note( $note );
}

/**
 * Gets a user's subscription level ID
 *
 * @deprecated 3.0 Use `rcp_get_customer_membership_level_ids()` instead.
 * @see rcp_get_customer_membership_level_ids()
 *
 * @param int $user_id The ID of the user to return the subscription level of, or 0 for current user.
 *
 * @return int|false The ID of the user's subscription level or false if none.
 */
function rcp_get_subscription_id( $user_id = 0 ) {

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );

	return $member->get_subscription_id();

}

/**
 * Gets a user's subscription level name
 *
 * @deprecated 3.0 Use `rcp_get_customer_membership_level_names` instead.
 * @see rcp_get_customer_membership_level_names()
 *
 * @param int $user_id The ID of the user to return the subscription level of, or 0 for current user.
 *
 * @return string The name of the user's subscription level
 */
function rcp_get_subscription( $user_id = 0 ) {

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );

	return $member->get_subscription_name();

}

/**
 * Checks whether a user has a recurring subscription
 *
 * @deprecated 3.0 Use `rcp_membership_is_recurring()` instead.
 * @see rcp_membership_is_recurring()
 *
 * @param int $user_id The ID of the user to return the subscription level of, or 0 for current user.
 *
 * @return bool True if the user is recurring, false otherwise
 */
function rcp_is_recurring( $user_id = 0 ) {

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );

	return $member->is_recurring();

}

/**
 * Checks whether a user is expired
 *
 * @deprecated 3.0 Use `rcp_membership_is_expired()` instead.
 * @see rcp_membership_is_expired()
 *
 * @param int $user_id The ID of the user to return the subscription level of, or 0 for current user.
 *
 * @return bool True if the user is expired, false otherwise
 */
function rcp_is_expired( $user_id = 0 ) {

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );

	return $member->is_expired();

}

/**
 * Checks whether a user has an active subscription
 *
 * @deprecated 3.0 Use `rcp_user_has_paid_membership()` or `rcp_user_has_active_membership()` instead.
 * @see rcp_user_has_paid_membership()
 * @see rcp_user_has_active_membership()
 *
 * @param int $user_id The ID of the user to return the subscription level of, or 0 for current user.
 *
 * @return bool True if the user has an active, paid subscription (or is trialing), false otherwise
 */
function rcp_is_active( $user_id = 0 ) {

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	return rcp_user_has_paid_membership( $user_id );

}

/**
 * Just a wrapper function for rcp_is_active()
 *
 * @deprecated 3.0
 *
 * @param int $user_id - the ID of the user to return the subscription level of, or 0 for current user.
 *
 * @return bool True if the user has an active, paid subscription (or is trialing), false otherwise
 */
function rcp_is_paid_user( $user_id = 0) {

	$ret = false;

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	if( rcp_is_active( $user_id ) ) {
		$ret = true;
	}
	return apply_filters( 'rcp_is_paid_user', $ret, $user_id );
}

/**
 * Returns an array of all members, based on subscription status
 *
 * @deprecated 3.0 Use either `rcp_get_memberships()` or `rcp_get_customers()`
 * @see rcp_get_memberships()
 * @see rcp_get_customers()
 *
 * @param string $status       The subscription status of users to retrieve
 * @param int    $subscription The subscription ID to retrieve users from
 * @param int    $offset       The number of users to skip, used for pagination
 * @param int    $number       The total users to retrieve, used for pagination
 * @param string $order        The order in which to display users: ASC / DESC
 * @param string $recurring    Retrieve recurring (or non recurring) only
 * @param string $search       Seach parameter
 *
 * @return array|bool          Array of users or false if none.
 */
function rcp_get_members( $status = 'active', $subscription = null, $offset = 0, $number = 999999, $order = 'DESC', $recurring = null, $search = '' ) {

	global $wpdb;

	$where = ' WHERE m.disabled = 0 ';

	$users_table       = $wpdb->users;
	$customers_table   = rcp_get_customers_db_name();
	$memberships_table = rcp_get_memberships_db_name();

	// Membership status.
	if ( ! empty( $status ) ) {
		// Membership status.
		if ( 'active' == $status ) {
			$where .= " AND ( m.initial_amount > 0 OR m.recurring_amount > 0 ) ";
		} elseif ( 'free' == $status ) {
			$status = 'active';

			$where .= " AND m.initial_amount = 0 AND m.recurring_amount = 0 ";
		}

		$where    .= ' AND m.status = %s ';
		$values[] = sanitize_text_field( $status );
	}

	// Subscription.
	if ( ! empty( $subscription ) ) {
		$where    .= ' AND m.object_id = %d ';
		$values[] = absint( $subscription );
	}

	// Recurring.
	if ( ! empty( $recurring ) ) {
		if ( 1 == $recurring ) {
			// Find non-recurring.
			$where .= ' AND m.auto_renew = 0 ';
		} else {
			// Find recurring.
			$where .= ' AND m.auto_renew = 1 ';
		}
	}

	// Search.
	if ( ! empty( $search ) ) {
		if( false !== strpos( $search, 'first_name:' ) ) {
			$where   .= ' AND ( umeta.meta_key = \'first_name\' AND umeta.meta_value LIKE %s ) ';
			$values[] = sanitize_text_field( trim( str_replace( 'first_name:', '', $search ) ) );
		} elseif( false !== strpos( $search, 'last_name:' ) ) {
			$where   .= ' AND ( umeta.meta_key = \'last_name\' AND umeta.meta_value LIKE %s ) ';
			$values[] = sanitize_text_field( trim( str_replace( 'last_name:', '', $search ) ) );
		} elseif( false !== strpos( $search, 'payment_profile:' ) ) {
			$where .= ' AND ( m.gateway_subscription_id LIKE %s OR m.gateway_customer_id LIKE %s ) ';
			$values[] = sanitize_text_field( trim( str_replace( 'payment_profile:', '', $search ) ) );
			$values[] = sanitize_text_field( trim( str_replace( 'payment_profile:', '', $search ) ) );
		} else {
			// Search username/email.
			$where .= ' AND ( u.user_login LIKE %s OR u.user_email LIKE %s ) ';
			$values[] = '%' . $wpdb->esc_like( sanitize_text_field( trim( $search ) ) ) . '%';
			$values[] = '%' . $wpdb->esc_like( sanitize_text_field( trim( $search ) ) ) . '%';
		}
	}

	$values[] = absint( $offset );
	$values[] = absint( $number );

	$join = '';

	if ( false !== strpos( $where, 'umeta' ) ) {
		$join .=  " INNER JOIN {$wpdb->usermeta} umeta ON u.ID = umeta.user_id ";
	}

	$query = $wpdb->prepare(
		"SELECT * FROM {$users_table} u
			INNER JOIN {$customers_table} c ON u.ID = c.user_id
			INNER JOIN {$memberships_table} m ON c.id = m.customer_id
			{$join}
			{$where}
			ORDER BY u.ID {$order}
			LIMIT %d, %d",
		$values
	);

	$results = $wpdb->get_results( $query );

	if ( empty( $results ) ) {
		return false;
	}

	$users = array();

	// Set up WP_User objects.
	foreach ( $results as $user ) {
		$users[] = new WP_User( $user );
	}

	return $users;
}

/**
 * Counts the number of members by subscription level and status
 *
 * @deprecated 3.0 Use `rcp_count_memberships()` or `rcp_count_customers()` instead.
 * @see rcp_count_memberships()
 * @see rcp_count_customers()
 *
 * @param int    $level ID of the subscription level to count the members of.
 * @param string $status The status to count.
 * @param string $recurring    Retrieve recurring (or non recurring) only
 * @param string $search       Seach parameter
 *
 * @return int The number of members for the specified subscription level and status
 */
function rcp_count_members( $level = '', $status = 'active', $recurring = null, $search = '' ) {

	global $wpdb;

	$where = ' WHERE m.disabled = 0 ';

	$users_table       = $wpdb->users;
	$customers_table   = rcp_get_customers_db_name();
	$memberships_table = rcp_get_memberships_db_name();

	// Membership status.
	if ( 'active' == $status ) {
		$where .= " AND ( m.initial_amount > 0 OR m.recurring_amount > 0 ) ";
	} elseif ( 'free' == $status ) {
		$status = 'active';

		$where .= " AND m.initial_amount = 0 AND m.recurring_amount = 0 ";
	}
	$where   .= ' AND m.status = %s ';
	$values[] = sanitize_text_field( $status );

	// Subscription.
	if ( ! empty( $level ) ) {
		$where    .= ' AND m.object_id = %d ';
		$values[] = absint( $level );
	}

	// Recurring.
	if ( ! empty( $recurring ) ) {
		if ( 1 == $recurring ) {
			// Find non-recurring.
			$where .= ' AND m.auto_renew = 0 ';
		} else {
			// Find recurring.
			$where .= ' AND m.auto_renew = 1 ';
		}
	}

	// Search.
	if ( ! empty( $search ) ) {
		if( false !== strpos( $search, 'first_name:' ) ) {
			$where   .= ' AND ( umeta.meta_key = \'first_name\' AND umeta.meta_value LIKE %s ) ';
			$values[] = sanitize_text_field( trim( str_replace( 'first_name:', '', $search ) ) );
		} elseif( false !== strpos( $search, 'last_name:' ) ) {
			$where   .= ' AND ( umeta.meta_key = \'last_name\' AND umeta.meta_value LIKE %s ) ';
			$values[] = sanitize_text_field( trim( str_replace( 'last_name:', '', $search ) ) );
		} elseif( false !== strpos( $search, 'payment_profile:' ) ) {
			$where .= ' AND ( m.gateway_subscription_id LIKE %s OR m.gateway_customer_id LIKE %s ) ';
			$values[] = sanitize_text_field( trim( str_replace( 'payment_profile:', '', $search ) ) );
			$values[] = sanitize_text_field( trim( str_replace( 'payment_profile:', '', $search ) ) );
		} else {
			// Search username/email.
			$where .= ' AND ( u.user_login LIKE %s OR u.user_email LIKE %s ) ';
			$values[] = '%' . $wpdb->esc_like( sanitize_text_field( trim( $search ) ) ) . '%';
			$values[] = '%' . $wpdb->esc_like( sanitize_text_field( trim( $search ) ) ) . '%';
		}
	}

	$join = '';

	if ( false !== strpos( $where, 'umeta' ) ) {
		$join .=  " INNER JOIN {$wpdb->usermeta} umeta ON u.ID = umeta.user_id ";
	}

	$query = $wpdb->prepare(
		"SELECT COUNT(*) FROM {$users_table} u
			INNER JOIN {$customers_table} c ON u.ID = c.user_id
			INNER JOIN {$memberships_table} m ON c.id = m.customer_id
			{$join}
			{$where}",
		$values
	);

	$results = $wpdb->get_var( $query );

	return absint( $results );

}

/**
 * Gets all members of a particular subscription level
 *
 * @deprecated 3.0 Use `rcp_get_memberships` instead.
 * @see rcp_get_memberships()
 *
 * @param int          $id     The ID of the subscription level to retrieve users for.
 * @param string|array $fields String or array of the user fields to retrieve.
 *
 * @return array An array of user objects
 */
function rcp_get_members_of_subscription( $id = 1, $fields = 'ID') {
	return rcp_get_members( '', $id );
}

/**
 * Gets the date of a user's expiration in a nice format
 *
 * @deprecated 3.0 Use RCP_Membership::get_expiration_date() instead.
 * @see RCP_Membership::get_expiration_date()
 *
 * @param int $user_id The ID of the user to return the subscription level of, or 0 for the current user.
 *
 * @return string The date of the user's expiration, in the format specified in settings
 */
function rcp_get_expiration_date( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->get_expiration_date( true, false );
}

/**
 * Sets the users expiration date
 *
 * @deprecated 3.0 Use RCP_Membership::set_expiration_date() instead.
 * @see RCP_Membership::set_expiration_date()
 *
 * @param int    $user_id The ID of the user to return the subscription level of, or 0 for the current user.
 * @param string $date    The expiration date in YYYY-MM-DD H:i:s
 *
 * @since 2.0
 * @return string The date of the user's expiration, in the format specified in settings
 */
function rcp_set_expiration_date( $user_id = 0, $new_date = '' ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->set_expiration_date( $new_date );
}

/**
 * Gets the date of a user's expiration in a unix time stamp
 *
 * @deprecated 3.0 Use RCP_Membership::get_expiration_time() instead.
 * @see RCP_Membership::get_expiration_time()
 *
 * @param int $user_id The ID of the user to return the subscription level of
 *
 * @return int|false Timestamp of expiration of false if no expiration
 */
function rcp_get_expiration_timestamp( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );

	return $member->get_expiration_time();

}

/**
 * Gets the status of a user's subscription. If a user is expired, this will update their status to "expired".
 *
 * @deprecated 3.0 Use RCP_Membership::get_status() instead.
 * @see RCP_Membership::get_status()
 *
 * @param int $user_id The ID of the user to return the subscription level of, or 0 for the current user.
 *
 * @return string The status of the user's subscription
 */
function rcp_get_status( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->get_status();
}

/**
 * Gets a user's subscription status in a nice format that is localized
 *
 * @deprecated 3.0 Use `rcp_print_membership_status()` instead.
 * @see rcp_print_membership_status()
 *
 * @param int $user_id The ID of the user to return the subscription level of, or 0 for the current user.
 *
 * @return string The user's subscription status
 */
function rcp_print_status( $user_id = 0, $echo = true  ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$status_slug = '';

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( ! empty( $customer ) ) {
		$membership = rcp_get_customer_single_membership( $customer->get_id() );

		if ( ! empty( $membership ) ) {
			$status_slug = $membership->get_status();
		}
	}

	if ( empty( $status_slug ) ) {
		$status_slug = rcp_get_status( $user_id );
	}

	$print_status = rcp_get_status_label( $status_slug );

	if ( $echo ) {
		echo $print_status;
	}

	return $print_status;
}

/**
 * Sets a user's status to the specified status
 *
 * @deprecated 3.0 Use RCP_Membership::set_status() instead.
 * @see RCP_Membership::set_status()
 *
 * @param int    $user_id    The ID of the user to return the subscription level of
 * @param string $new_status The status to set the user to
 *
 * @return bool True on a successful status change, false otherwise
 */
function rcp_set_status( $user_id = 0, $new_status = '' ) {

	if( empty( $user_id ) || empty( $new_status ) ) {
		return false;
	}

	$member = new RCP_Member( $user_id );
	return $member->set_status( $new_status );

}

/**
 * Gets the user's unique subscription key
 *
 * @deprecated 3.0 Use RCP_Membership::get_subscription_key() instead.
 * @see RCP_Membership::get_subscription_key()
 *
 * @param int $user_id The ID of the user to return the subscription level of, or 0 for the current user
 *
 * @return string/bool Key string if it is retrieved successfully, false on failure
 */
function rcp_get_subscription_key( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->get_subscription_key();
}

/**
 * Checks whether a user has trialed
 *
 * @deprecated 3.0 Use RCP_Customer::has_trialed() instead.
 * @see RCP_Customer::has_trialed()
 *
 * @param int $user_id The ID of the user to return the subscription level of, or 0 for the current user
 *
 * @return bool True if the user has trialed, false otherwise
 */
function rcp_has_used_trial( $user_id = 0) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->has_trialed();

}

/**
 * Retrieve the payments for a specific user
 *
 * @deprecated 3.0 Use RCP_Customer::get_payments() instead.
 * @see RCP_Customer::get_payments()
 *
 * @param int   $user_id The ID of the user to get payments for
 * @param array $args    Override the default query args.
 *
 * @since  1.5
 * @return array
 */
function rcp_get_user_payments( $user_id = 0, $args = array() ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) ) {
		return array();
	}

	$args = wp_parse_args( $args, array(
		'user_id' => $user_id
	) );

	$payments = new RCP_Payments;
	return $payments->get_payments( $args );
}

/**
 * Check WordPress version is at least $version.
 *
 * @deprecated 3.0
 *
 * @param  string  $version WP version string to compare.
 *
 * @return bool             Result of comparison check.
 */
function rcp_compare_wp_version( $version ) {
	return version_compare( get_bloginfo( 'version' ), $version, '>=' );
}

/**
 * Load plugin text domain for translations.
 *
 * @deprecated 3.0 Moved to RCP_Requirements_Check::load_textdomain()
 * @see RCP_Requirements_Check::load_textdomain()
 *
 * @return void
 */
function rcp_load_textdomain() {

	// Set filter for plugin's languages directory
	$rcp_lang_dir = dirname( plugin_basename( RCP_PLUGIN_FILE ) ) . '/languages/';
	$rcp_lang_dir = apply_filters( 'rcp_languages_directory', $rcp_lang_dir );


	// Traditional WordPress plugin locale filter

	$get_locale = get_locale();

	if ( rcp_compare_wp_version( 4.7 ) ) {

		$get_locale = get_user_locale();
	}

	/**
	 * Defines the plugin language locale used in RCP.
	 *
	 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
	 *                  otherwise uses `get_locale()`.
	 */
	$locale        = apply_filters( 'plugin_locale',  $get_locale, 'rcp' );
	$mofile        = sprintf( '%1$s-%2$s.mo', 'rcp', $locale );

	// Setup paths to current locale file
	$mofile_local  = $rcp_lang_dir . $mofile;
	$mofile_global = WP_LANG_DIR . '/rcp/' . $mofile;

	if ( file_exists( $mofile_global ) ) {
		// Look in global /wp-content/languages/rcp folder
		load_textdomain( 'rcp', $mofile_global );
	} elseif ( file_exists( $mofile_local ) ) {
		// Look in local /wp-content/plugins/rcp/languages/ folder
		load_textdomain( 'rcp', $mofile_local );
	} else {
		// Load the default language files
		load_plugin_textdomain( 'rcp', false, $rcp_lang_dir );
	}

}
//add_action( 'init', 'rcp_load_textdomain' );

/**
 * Register / set up our databases classes
 *
 * @deprecated 3.0 Moved to Restrict_Content_Pro::backcompat_globals()
 * @see Restrict_Content_Pro::backcompat_globals()
 *
 * @access  private
 * @since   2.6
 * @return  void
 */
function rcp_register_databases() {

	global $wpdb, $rcp_payments_db, $rcp_levels_db, $rcp_discounts_db;

	$rcp_payments_db       = new RCP_Payments;
	$rcp_levels_db         = new RCP_Levels;
	$rcp_discounts_db      = new RCP_Discounts;
	$wpdb->levelmeta       = $rcp_levels_db->meta_db_name;
	$wpdb->rcp_paymentmeta = $rcp_payments_db->meta_db_name;

}

// Set up database classes.
//add_action( 'plugins_loaded', 'rcp_register_databases', 11 );

/**
 * Adjust subscription member counts on status changes
 *
 * @deprecated 3.0 Use `rcp_increment_membership_level_count_on_status_change()` instead.
 * @see rcp_increment_membership_level_count_on_status_change()
 *
 * @param string     $status     User's membership status.
 * @param int        $user_id    ID of the user.
 * @param string     $old_status Previous membership status.
 * @param RCP_Member $member     Member object.
 *
 * @access      public
 * @since       2.6
 * @return      void
 */
function rcp_increment_subscription_member_count_on_status_change( $status, $user_id, $old_status, $member ) {

	$pending_sub_id = $member->get_pending_subscription_id();
	$old_sub_id     = get_user_meta( $user_id, '_rcp_old_subscription_id', true );
	$sub_id         = $member->get_subscription_id();

	if ( $old_sub_id && (int) $sub_id === (int) $old_sub_id && $status === $old_status ) {
		return;
	}

	if ( ! empty( $pending_sub_id ) ) {

		rcp_increment_subscription_member_count( $pending_sub_id, $status );

	} elseif ( $status !== $old_status ) {

		rcp_increment_subscription_member_count( $sub_id, $status );

	}

	if ( ! empty( $old_status ) && $old_status !== $status ) {
		rcp_decrement_subscription_member_count( $sub_id, $old_status );
	}

	if ( $old_sub_id ) {
		rcp_decrement_subscription_member_count( $old_sub_id, $old_status );
	}

}

//add_action( 'rcp_set_status', 'rcp_increment_subscription_member_count_on_status_change', 9, 4 );

/**
 * Send emails to members based on subscription status changes.
 *
 * @deprecated 3.0 Use `rcp_send_membership_email()` instead.
 * @see rcp_send_membership_email()
 *
 * @param int    $user_id ID of the user to send the email to.
 * @param string $status  User's status, to determine which email to send.
 *
 * @return void
 */
function rcp_email_subscription_status( $user_id, $status = 'active' ) {

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( empty( $customer ) ) {
		return;
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	if ( empty( $membership ) ) {
		return;
	}

	rcp_send_membership_email( $membership, $status );
}

/**
 * Triggers the expiration notice when an account is marked as expired.
 *
 * @deprecated 3.0 In favour of `rcp_email_on_membership_expiration()`.
 * @see rcp_email_on_membership_expiration()
 *
 * @param string $status  User's status.
 * @param int    $user_id ID of the user to email.
 *
 * @access  public
 * @since   2.0.9
 * @return  void
 */
function rcp_email_on_expiration( $status, $user_id ) {

	if( 'expired' == $status ) {

		// Send expiration email.
		rcp_email_subscription_status( $user_id, 'expired' );

	}

}
//add_action( 'rcp_set_status', 'rcp_email_on_expiration', 11, 2 );

/**
 * Triggers the activation notice when an account is marked as active.
 *
 * @deprecated 3.0 In favour of `rcp_email_on_membership_activation()`.
 * @see rcp_email_on_membership_activation()
 *
 * @param string $status  User's status.
 * @param int    $user_id ID of the user to email.
 *
 * @access  public
 * @since   2.1
 * @return  void
 */
function rcp_email_on_activation( $status, $user_id ) {

	if( 'active' == $status && get_user_meta( $user_id, '_rcp_new_subscription', true ) ) {

		// Send welcome email.
		rcp_email_subscription_status( $user_id, 'active' );

	}

}
//add_action( 'rcp_set_status', 'rcp_email_on_activation', 11, 2 );

/**
 * Triggers the free trial notice when an account is marked as active.
 *
 * @deprecated 3.0 In favour of `rcp_email_on_membership_activation()`.
 * @see rcp_email_on_membership_activation()
 *
 * @param string $status  User's status.
 * @param int    $user_id ID of the user to email.
 *
 * @access  public
 * @since   2.7.2
 * @return  void
 */
function rcp_email_on_free_trial( $status, $user_id ) {

	if( 'active' == $status && rcp_is_trialing( $user_id ) && get_user_meta( $user_id, '_rcp_new_subscription', true ) ) {

		// Send free trial welcome email.
		rcp_email_subscription_status( $user_id, 'trial' );

	}

}
//add_action( 'rcp_set_status', 'rcp_email_on_free_trial', 11, 2 );

/**
 * Triggers the free notice when an account is marked as free.
 *
 * @deprecated 3.0 In favour of `rcp_email_on_membership_activation()`.
 * @see rcp_email_on_membership_activation()
 *
 * @param int        $user_id    ID of the user to email.
 * @param string     $old_status Previous status before the update.
 * @param RCP_Member $member     Member object.
 *
 * @since  2.8.2
 * @return void
 */
function rcp_email_on_free_subscription( $user_id, $old_status, $member ) {

	rcp_email_subscription_status( $user_id, 'free' );

}
//add_action( 'rcp_set_status_free', 'rcp_email_on_free_subscription', 11, 3 );

/**
 * Triggers the cancellation notice when an account is marked as cancelled.
 *
 * @deprecated 3.0 In favour of `rcp_email_on_membership_cancellation()`.
 * @see rcp_email_on_membership_cancellation()
 *
 * @param string $status  User's status.
 * @param int    $user_id ID of the user to email.
 *
 * @access  public
 * @since   2.1
 * @return  void
 */
function rcp_email_on_cancellation( $status, $user_id ) {

	if( 'cancelled' == $status ) {

		// Send cancellation email.
		rcp_email_subscription_status( $user_id, 'cancelled' );

	}

}
//add_action( 'rcp_set_status', 'rcp_email_on_cancellation', 11, 2 );

/**
 * Removes the subscription-assigned role from a member when the member expires.
 *
 * @deprecated 3.0 In favour of `rcp_update_expired_membership_role()`.
 * @see rcp_update_expired_membership_role()
 *
 * @param string     $status     Status that was just set.
 * @param int        $member_id  ID of the member.
 * @param string     $old_status Previous status.
 * @param RCP_Member $member     Member object.
 *
 * @since  2.7
 * @return void
 */
function rcp_update_expired_member_role( $status, $member_id, $old_status, $member ) {

	if ( 'expired' !== $status ) {
		return;
	}

	$subscription = rcp_get_membership_level( $member->get_subscription_id() );

	$default_role = get_option( 'default_role', 'subscriber' );

	if ( $subscription instanceof \RCP\Membership_Level && $subscription->get_role() !== $default_role ) {
		$member->remove_role( $subscription->get_role() );
	}
}
//add_action( 'rcp_set_status', 'rcp_update_expired_member_role', 10, 4 );

/**
 * Determine if a member is a Stripe subscriber
 *
 * @deprecated 3.0 Use `rcp_is_stripe_membership()` instead.
 * @see rcp_is_stripe_membership()
 *
 * @param int $user_id The ID of the user to check
 *
 * @since       2.1
 * @access      public
 * @return      bool
 */
function rcp_is_stripe_subscriber( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret = false;

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( ! empty( $customer ) ) {
		$membership = rcp_get_customer_single_membership( $customer->get_id() );

		if ( ! empty( $membership ) ) {
			$ret = rcp_is_stripe_membership( $membership );
		}
	}

	return (bool) apply_filters( 'rcp_is_stripe_subscriber', $ret, $user_id );
}

/**
 * Process an update card form request
 *
 * @deprecated 3.0 Use `rcp_stripe_update_membership_billing_card()` instead.
 * @see rcp_stripe_update_membership_billing_card()
 *
 * @param int        $member_id  ID of the member.
 * @param RCP_Member $member_obj Member object.
 *
 * @access      private
 * @since       2.1
 * @return      void
 */
function rcp_stripe_update_billing_card( $member_id, $member_obj ) {

	if( empty( $member_id ) ) {
		return;
	}

	if( ! is_a( $member_obj, 'RCP_Member' ) ) {
		return;
	}

	$customer = rcp_get_customer_by_user_id( $member_id );

	if ( empty( $customer ) ) {
		return;
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	if ( empty( $membership ) ) {
		return;
	}

	rcp_stripe_update_membership_billing_card( $membership );

}
//add_action( 'rcp_update_billing_card', 'rcp_stripe_update_billing_card', 10, 2 );

/**
 * Create discount code in Stripe when one is created in RCP
 *
 * @deprecated 3.2 Coupons are no longer synced.
 *
 * @param array $args
 *
 * @access      private
 * @since       2.1
 * @return      void
 */
function rcp_stripe_create_discount( $args ) {

	if( ! is_admin() ) {
		return;
	}

	if( function_exists( 'rcp_stripe_add_discount' ) ) {
		return; // Old Stripe gateway is active
	}

	if( ! rcp_is_gateway_enabled( 'stripe' ) ) {
		return;
	}

	global $rcp_options;

	if( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
	}

	if ( rcp_is_sandbox() ) {
		$secret_key = isset( $rcp_options['stripe_test_secret'] ) ? trim( $rcp_options['stripe_test_secret'] ) : '';
	} else {
		$secret_key = isset( $rcp_options['stripe_live_secret'] ) ? trim( $rcp_options['stripe_live_secret'] ) : '';
	}

	if( empty( $secret_key ) ) {
		return;
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	try {

		if ( $args['unit'] == '%' ) {
			$coupon_args = array(
				"percent_off" => sanitize_text_field( $args['amount'] ),
				"duration"    => "forever",
				"id"          => sanitize_text_field( $args['code'] ),
				"name"        => sanitize_text_field( $args['name'] ),
				"currency"    => strtolower( rcp_get_currency() )
			);

		} else {
			$coupon_args = array(
				"amount_off" => sanitize_text_field( $args['amount'] ) * rcp_stripe_get_currency_multiplier(),
				"duration"   => "forever",
				"id"         => sanitize_text_field( $args['code'] ),
				"name"       => sanitize_text_field( $args['name'] ),
				"currency"   => strtolower( rcp_get_currency() )
			);
		}

		\Stripe\Coupon::create( $coupon_args );

	} catch ( \Stripe\Exception\CardException $e ) {

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		exit;

	} catch (\Stripe\Exception\InvalidRequestException $e) {

		// Invalid parameters were supplied to Stripe's API
		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	} catch (\Stripe\Exception\AuthenticationException $e) {

		// Authentication with Stripe's API failed
		// (maybe you changed API keys recently)

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	} catch (\Stripe\Exception\ApiConnectionException $e) {

		// Network communication with Stripe failed

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	} catch (\Stripe\Exception\ApiErrorException $e) {

		// Display a very generic error to the user

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	} catch (Exception $e) {

		// Something else happened, completely unrelated to Stripe

		$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
		$error .= print_r( $e, true );

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	}

}
//add_action( 'rcp_pre_add_discount', 'rcp_stripe_create_discount' );

/**
 * Update a discount in Stripe when a local code is updated
 *
 * @deprecated 3.2 Coupons are no longer synced.
 *
 * @param int $discount_id The id of the discount being updated
 * @param array $args The array of discount args
 *              array(
 *					'name',
 *					'description',
 *					'amount',
 *					'unit',
 *					'code',
 *					'status',
 *					'expiration',
 *					'max_uses',
 *					'subscription_id'
 *				)
 *
 * @access      private
 * @since       2.1
 * @return      void
 */
function rcp_stripe_update_discount( $discount_id, $args ) {

	if( ! is_admin() ) {
		return;
	}

	// bail if the discount id or args are empty
	if ( empty( $discount_id ) || empty( $args )  )
		return;

	if( function_exists( 'rcp_stripe_add_discount' ) ) {
		return; // Old Stripe gateway is active
	}

	if( ! rcp_is_gateway_enabled( 'stripe' ) ) {
		return;
	}

	global $rcp_options;

	if( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
	}

	if ( ! empty( $_REQUEST['deactivate_discount'] ) || ! empty( $_REQUEST['activate_discount'] ) ) {
		return;
	}

	if ( rcp_is_sandbox() ) {
		$secret_key = isset( $rcp_options['stripe_test_secret'] ) ? trim( $rcp_options['stripe_test_secret'] ) : '';
	} else {
		$secret_key = isset( $rcp_options['stripe_live_secret'] ) ? trim( $rcp_options['stripe_live_secret'] ) : '';
	}

	if( empty( $secret_key ) ) {
		return;
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	$discount_details = rcp_get_discount_details( $discount_id );
	$discount_name    = $discount_details->code;

	if ( ! rcp_stripe_does_coupon_exists( $discount_name ) ) {

		try {

			if ( $args['unit'] == '%' ) {
				$coupon_args = array(
					"percent_off" => sanitize_text_field( $args['amount'] ),
					"duration"    => "forever",
					"id"          => sanitize_text_field( $discount_name ),
					"name"        => sanitize_text_field( $args['name'] ),
					"currency"    => strtolower( rcp_get_currency() )
				);
			} else {
				$coupon_args = array(
					"amount_off" => sanitize_text_field( $args['amount'] ) * rcp_stripe_get_currency_multiplier(),
					"duration"   => "forever",
					"id"         => sanitize_text_field( $discount_name ),
					"name"       => sanitize_text_field( $args['name'] ),
					"currency"   => strtolower( rcp_get_currency() )
				);
			}

			\Stripe\Coupon::create( $coupon_args );

		} catch ( Exception $e ) {
			wp_die( '<pre>' . $e . '</pre>', __( 'Error', 'rcp' ) );
		}

	} else {

		// first delete the discount in Stripe
		try {
			$cpn = \Stripe\Coupon::retrieve( $discount_name );
			$cpn->delete();
		} catch ( Exception $e ) {
			wp_die( '<pre>' . $e . '</pre>', __( 'Error', 'rcp' ) );
		}

		// now add a new one. This is a fake "update"
		try {

			if ( $args['unit'] == '%' ) {
				$coupon_args = array(
					"percent_off" => sanitize_text_field( $args['amount'] ),
					"duration"    => "forever",
					"id"          => sanitize_text_field( $discount_name ),
					"name"        => sanitize_text_field( $args['name'] ),
					"currency"    => strtolower( rcp_get_currency() )
				);
			} else {
				$coupon_args = array(
					"amount_off" => sanitize_text_field( $args['amount'] ) * rcp_stripe_get_currency_multiplier(),
					"duration"   => "forever",
					"id"         => sanitize_text_field( $discount_name ),
					"name"       => sanitize_text_field( $args['name'] ),
					"currency"   => strtolower( rcp_get_currency() )
				);
			}

			\Stripe\Coupon::create( $coupon_args );

		} catch (\Stripe\Error\InvalidRequest $e) {

			// Invalid parameters were supplied to Stripe's API
			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (\Stripe\Error\Authentication $e) {

			// Authentication with Stripe's API failed
			// (maybe you changed API keys recently)

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (\Stripe\Error\ApiConnection $e) {

			// Network communication with Stripe failed

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (\Stripe\Exception\ApiErrorException $e) {

			// Display a very generic error to the user

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (Exception $e) {

			// Something else happened, completely unrelated to Stripe

			$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
			$error .= print_r( $e, true );

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		}
	}
}
//add_action( 'rcp_edit_discount', 'rcp_stripe_update_discount', 10, 2 );

/**
 * Check if a coupone exists in Stripe
 *
 * @deprecated 3.2
 *
 * @param string $code Discount code.
 *
 * @access      private
 * @since       2.1
 * @return      bool|void
 */
function rcp_stripe_does_coupon_exists( $code ) {
	global $rcp_options;

	if( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
	}

	if ( rcp_is_sandbox() ) {
		$secret_key = isset( $rcp_options['stripe_test_secret'] ) ? trim( $rcp_options['stripe_test_secret'] ) : '';
	} else {
		$secret_key = isset( $rcp_options['stripe_live_secret'] ) ? trim( $rcp_options['stripe_live_secret'] ) : '';
	}

	if( empty( $secret_key ) ) {
		return;
	}

	\Stripe\Stripe::setApiKey( $secret_key );
	try {
		\Stripe\Coupon::retrieve( $code );
		$exists = true;
	} catch ( Exception $e ) {
		$exists = false;
	}

	return $exists;
}

/**
 * Query Stripe API to get customer's card details
 *
 * @deprecated 3.2 In favour of `rcp_stripe_get_membership_card_details()`
 * @see        rcp_stripe_get_membership_card_details()
 *
 * @param array      $cards     Array of card information.
 * @param int        $member_id ID of the member.
 * @param RCP_Member $member    RCP member object.
 *
 * @since 2.5
 * @return array
 */
function rcp_stripe_get_card_details( $cards, $member_id, $member ) {

	global $rcp_options;

	if( ! rcp_is_stripe_subscriber( $member_id ) ) {
		return $cards;
	}

	if( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'core/includes/libraries/stripe/init.php';
	}

	if ( rcp_is_sandbox() ) {
		$secret_key = isset( $rcp_options['stripe_test_secret'] ) ? trim( $rcp_options['stripe_test_secret'] ) : '';
	} else {
		$secret_key = isset( $rcp_options['stripe_live_secret'] ) ? trim( $rcp_options['stripe_live_secret'] ) : '';
	}

	if( empty( $secret_key ) ) {
		return $cards;
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	try {

		$customer = \Stripe\Customer::retrieve( $member->get_payment_profile_id() );
		$default  = \Stripe\PaymentMethod::retrieve( $customer->invoice_settings->default_payment_method );

		if ( 'card' === $default->type ) {
			$cards['stripe']['name']      = $default->billing_details->name;
			$cards['stripe']['type']      = $default->card->brand;
			$cards['stripe']['zip']       = $default->billing_details->address->postal_code;
			$cards['stripe']['exp_month'] = $default->card->exp_month;
			$cards['stripe']['exp_year']  = $default->card->exp_year;
			$cards['stripe']['last4']     = $default->card->last4;
		}

	} catch ( Exception $e ) {

	}

	return $cards;

}

//add_filter( 'rcp_get_card_details', 'rcp_stripe_get_card_details', 10, 3 );

/**
 * Cancels a Braintree subscriber.
 *
 * @deprecated 3.0 Use `rcp_braintree_cancel_membership()` instead.
 * @see rcp_braintree_cancel_membership()
 *
 * @since 2.8
 * @param int $member_id The member ID to cancel.
 * @return bool|WP_Error
 */
function rcp_braintree_cancel_member( $member_id = 0 ) {

	$customer = rcp_get_customer_by_user_id( $member_id );

	if ( empty( $customer ) ) {
		return new WP_Error( 'rcp_braintree_error', __( 'Unable to find customer from member ID.', 'rcp' ) );
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	return rcp_braintree_cancel_membership( $membership->get_gateway_subscription_id() );

}

/**
 * Determines if a member is a Braintree customer.
 *
 * @deprecated 3.0 Use `rcp_is_braintree_membership()` instead.
 * @see rcp_is_braintree_membership()
 *
 * @since  2.8
 * @param  int  $member_id The ID of the user to check
 * @return bool True if the member is a Braintree customer, false if not.
 */
function rcp_is_braintree_subscriber( $member_id = 0 ) {

	if ( empty( $member_id ) ) {
		$member_id = get_current_user_id();
	}

	$ret = false;

	$customer = rcp_get_customer_by_user_id( $member_id );

	if ( ! empty( $customer ) ) {
		$membership = rcp_get_customer_single_membership( $customer->get_id() );

		if ( ! empty( $membership ) ) {
			$ret = rcp_is_braintree_membership( $membership );
		}
	}

	return (bool) apply_filters( 'rcp_is_braintree_subscriber', $ret, $member_id );
}

/**
 * Displays an admin notice if the PHP version requirement isn't met.
 *
 * @deprecated 3.3 RCP core now requires PHP 5.6+ anyway.
 *
 * @since 2.8
 * @return void
 */
function rcp_braintree_php_version_check() {

	if ( current_user_can( 'rcp_manage_settings' ) && version_compare( PHP_VERSION, '5.4', '<' ) && array_key_exists( 'braintree', rcp_get_enabled_payment_gateways() ) ) {
		echo '<div class="error"><p>' . __( 'The Braintree payment gateway in Restrict Content Pro requires PHP version 5.4 or later. Please contact your web host and request that your version be upgraded to 5.4 or later. Your site will be unable to take Braintree payments until PHP is upgraded.', 'rcp' ) . '</p></div>';
	}

}
add_action( 'admin_notices', 'rcp_braintree_php_version_check' );
