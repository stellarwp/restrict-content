<?php
/**
 * Member Functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Member Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Retrieves the total member counts for a status
 *
 * This retrieves the count for each membership level and them sums the results.
 *
 * Use rcp_count_members() to retrieve a count based on level, status, recurring, and search terms.
 *
 * @access public
 * @since  2.6
 * @return int
 */
function rcp_get_member_count( $status = 'active' ) {

	$levels = rcp_get_membership_levels( array( 'number' => 999 ) );

	if( ! $levels ) {
		return 0;
	}

	$total = 0;
	foreach( $levels as $level ) {

		$total += (int) rcp_get_subscription_member_count( $level->get_id(), $status );

	}

	return $total;

}

/**
 * Retrieves the total number of members by subscription status
 *
 * @uses   rcp_count_members()
 *
 * @return array Array of all counts.
 */
function rcp_count_all_members() {
	$counts = array(
		'active' 	=> rcp_count_members('', 'active'),
		'pending' 	=> rcp_count_members('', 'pending'),
		'expired' 	=> rcp_count_members('', 'expired'),
		'cancelled' => rcp_count_members('', 'cancelled'),
		'free' 		=> rcp_count_members('', 'free')
	);
	return $counts;
}

/**
 * Wrapper function for RCP_Member->can_access()
 *
 * Returns true if user can access the current content
 *
 * @param int $user_id
 * @param int $post_id
 *
 * @since  2.1
 * @return bool
 */
function rcp_user_can_access( $user_id = 0, $post_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$customer = rcp_get_customer_by_user_id( $user_id );

	if( empty( $post_id ) ) {
		global $post;

		// If we can't find a global $post object, assume the user can access the page.
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return true;
		}

		$post_id = $post->ID;
	}

	// If there is no customer then it depends on whether or not the post has restrictions.
	if ( empty( $customer ) ) {
		$member = new RCP_Member( $user_id ); // need for backwards compat in the filter

		if ( user_can( $user_id, 'manage_options' ) ) {
			// Admins always get access.
			$can_access = true;
		} else {
			$can_access = ! rcp_is_restricted_content( $post_id );
		}

		// We need to apply the filter here so that it can be used on logged-out users, like in IP Restriction.
		$can_access = apply_filters( 'rcp_member_can_access', $can_access, $member->ID, $post_id, $member );
	} else {
		$can_access = $customer->can_access( $post_id );
	}

	/*
	 * If they can't access, let's do some more digging. This is to ensure that if a post is
	 * just restricted to a certain role, a user with that role can access the content even
	 * if they don't have a membership.
	 */
	if ( is_user_logged_in() && ! $can_access ) {
		$restrictions = rcp_get_post_restrictions( $post_id );
		if ( empty( $restrictions['membership_levels'] ) && empty( $restrictions['access_level'] ) && ! empty( $restrictions['user_level'] ) ) {
			foreach ( $restrictions['user_level'] as $role ) {
				if ( user_can( $user_id, $role ) ) {
					$can_access = true;
					break;
				}
			}
		}
	}

	return $can_access;
}

/**
 * Returns true if the user meets all the requirements to access the term.
 *
 * @param int $user_id ID of the user to check.
 * @param int $term_id ID of the term to check.
 *
 * @since 3.0.2
 * @return bool
 */
function rcp_user_can_access_term( $user_id, $term_id ) {

	// If the user is an admin, they can access.
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}

	$restrictions = rcp_get_term_restrictions( $term_id );

	// There are no restrictions - bail.
	if ( empty( $restrictions ) ) {
		return true;
	}

	$customer = rcp_get_customer_by_user_id( $user_id );

	// If there's no customer record, there's no way they can access.
	if ( empty( $customer ) ) {
		return false;
	}

	// Assume they can access, then we can try to prove otherwise.
	$can_access = true;

	// Check "paid only".
	if ( ! empty( $restrictions['paid_only'] ) && ! rcp_user_has_paid_membership( $user_id ) )  {
		$can_access = false;
	}

	// Check access level.
	if ( ! empty( $restrictions['access_level'] ) && ! rcp_user_has_access( $user_id, $restrictions['access_level'] ) ) {
		$can_access = false;
	}

	// Check selected membership level IDs.
	if ( ! empty( $restrictions['subscriptions'] ) && is_array( $restrictions['subscriptions'] ) && ! count( array_intersect( rcp_get_customer_membership_level_ids( $customer->get_id() ), $restrictions['subscriptions'] ) ) ) {
		$can_access = false;
	}

	return $can_access;

}


/**
 * Checks if a user is currently trialing
 *
 * @param int $user_id ID of the user to check, or 0 for the current user.
 *
 * @access      public
 * @since       1.5
 * @return      bool
 */
function rcp_is_trialing( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( empty( $customer ) ) {
		return false;
	}

	foreach ( $customer->get_memberships() as $membership ) {
		if ( $membership->is_trialing() ) {
			return true;
		}
	}

	return false;

}

/**
 * Prints payment history for the specific user in a formatted table
 *
 * @param int $user_id ID of the user to get the history for.
 *
 * @since  2.5
 * @return string
 */
function rcp_print_user_payments_formatted( $user_id ) {

	$payments = new RCP_Payments;
	$user_payments = $payments->get_payments( array( 'user_id' => $user_id ) );
	$payments_list = '';

	if ( ! $user_payments ) {
		return $payments_list;
	}

	$i = 0;

	ob_start();
	?>

	<table class="wp-list-table widefat posts rcp-table rcp_payment_details">

		<thead>
			<tr>
				<th scope="col" class="column-primary"><?php _e( 'ID', 'rcp' ); ?></th>
				<th scope="col"><?php _e( 'Date', 'rcp' ); ?></th>
				<th scope="col"><?php _e( 'Subscription', 'rcp' ); ?></th>
				<th scope="col"><?php _e( 'Payment Type', 'rcp' ); ?></th>
				<th scope="col"><?php _e( 'Transaction ID', 'rcp' ); ?></th>
				<th scope="col"><?php _e( 'Amount', 'rcp' ); ?></th>
				<th scope="col"><?php _e( 'Status', 'rcp' ); ?></th>
				<th scope="col"><?php _e( 'Invoice', 'rcp' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach( $user_payments as $payment ) : ?>

				<tr class="rcp_row<?php echo rcp_is_odd( $i ) ? ' alternate' : ''; ?>">
					<td class="column-primary" data-colname="<?php esc_attr_e( 'ID', 'rcp' ); ?>">
						<a href="<?php echo esc_url( add_query_arg( array( 'payment_id' => urlencode( $payment->id ), 'view' => 'edit-payment' ), admin_url( 'admin.php?page=rcp-payments' ) ) ); ?>" class="rcp-edit-payment"><?php echo esc_html( $payment->id ); ?></a>
						<button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e( 'Show more details', 'rcp' ); ?></span></button>
					</td>
					<td data-colname="<?php esc_attr_e( 'Date', 'rcp' ); ?>"><?php echo esc_html( $payment->date ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Subscription', 'rcp' ); ?>"><?php echo esc_html( $payment->subscription ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Payment Type', 'rcp' ); ?>"><?php echo esc_html( $payment->payment_type ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Transaction ID', 'rcp' ); ?>"><?php echo rcp_get_merchant_transaction_id_link( $payment ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Amount', 'rcp' ); ?>"><?php echo ( '' == $payment->amount ) ? esc_html( rcp_currency_filter( $payment->amount2 ) ) : esc_html( rcp_currency_filter( $payment->amount ) ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Status', 'rcp' ); ?>"><?php echo rcp_get_payment_status_label( $payment ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Invoice', 'rcp' ); ?>"><a href="<?php echo esc_url( rcp_get_invoice_url( $payment->id ) ); ?>" target="_blank"><?php _e( 'View Invoice', 'rcp' ); ?></a></td>
				</tr>

			<?php
			$i++;
			endforeach; ?>
		</tbody>

	</table>

	<?php
	return apply_filters( 'rcp_print_user_payments_formatted', ob_get_clean(), $user_id );
}

/**
 * Returns the slug of the role for the specified user.
 *
 * @param int $user_id The ID of the user to get the role of
 *
 * @return int|string
 */
function rcp_get_user_role( $user_id ) {

	global $wpdb;

	$user = new WP_User( $user_id );
	$capabilities = $user->{$wpdb->prefix . 'capabilities'};

	if ( !isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	$user_role = '';

	if( ! empty( $capabilities ) ) {
		foreach ( $wp_roles->role_names as $role => $name ) {

			if ( array_key_exists( $role, $capabilities ) ) {
				$user_role = $role;
			}
		}
	}

	return $user_role;
}

/**
 * Returns the name of the role for the specified user.
 *
 * @param int $user_id The ID of the user to get the role of
 *
 * @since 3.0
 * @return string
 */
function rcp_get_user_role_name( $user_id ) {

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	$role_slug = rcp_get_user_role( $user_id );
	$role_name = '';

	if ( empty( $role_slug ) ) {
		return $role_name;
	}

	if ( array_key_exists( $role_slug, $wp_roles->role_names ) ) {
		$role_name = $wp_roles->role_names[ $role_slug ];
	}

	return $role_name;
}


/**
 * Determine if it's possible to upgrade a user's subscription
 *
 * @deprecated 3.0 Use `RCP_Membership::upgrade_possible()` instead.
 * @see RCP_Membership::upgrade_possible()
 *
 * @param int $user_id The ID of the user to check, or 0 for the current user.
 *
 * @since  1.5
 * @return bool
*/

function rcp_subscription_upgrade_possible( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( empty( $customer ) ) {
		return (bool) apply_filters( 'rcp_can_upgrade_subscription', false, $user_id );
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	if ( empty( $membership ) ) {
		return (bool) apply_filters( 'rcp_can_upgrade_subscription', false, $user_id );
	}

	return $membership->upgrade_possible();
}

/**
 * Does this user have an upgrade path?
 *
 * @uses  rcp_get_upgrade_paths()
 *
 * @param int $user_id The ID of the user to check, or 0 for the current user.
 *
 * @since  2.5
 * @return bool True if an upgrade path is available, false if not.
 */
function rcp_has_upgrade_path( $user_id = 0 ) {
	return apply_filters( 'rcp_has_upgrade_path', ( bool ) rcp_get_upgrade_paths( $user_id ), $user_id );
}

/**
 * Get membership levels to which this user can upgrade
 *
 * @param int $user_id The ID of the user to check, or 0 for the current user.
 *
 * @since 2.5
 * @return array Array of subscriptions.
 */
function rcp_get_upgrade_paths( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$membership_levels = rcp_get_membership_levels( array( 'status' => 'active' ) );
	$customer          = rcp_get_customer_by_user_id( $user_id );

	if ( empty( $customer ) ) {
		return apply_filters( 'rcp_get_upgrade_paths', $membership_levels, $user_id );
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	if ( empty( $membership ) ) {
		return apply_filters( 'rcp_get_upgrade_paths', $membership_levels, $user_id );
	}

	$upgrade_paths = $membership->upgrade_possible() ? $membership->get_upgrade_paths() : array();

	/*
	 * If the current membership can be renewed, add it back to the list. This is for backwards
	 * compatibility because it used to be in `RCP_Membership::get_upgrade_paths()`.
	 */
	if ( $membership->can_renew() ) {
		$upgrade_paths[] = rcp_get_membership_level( $membership->get_object_id() );
	}

	// Sort by "list_order" first, then "id".
	if ( function_exists( 'wp_list_sort' ) ) {
		$upgrade_paths = wp_list_sort( $upgrade_paths, array(
			'list_order' => 'ASC',
			'id'         => 'ASC'
		) );
	}

	return $upgrade_paths;
}

/**
 * Process Profile Updater Form
 *
 * Processes the profile updater form by updating the necessary fields
 *
 * @access  private
 * @since   1.5
 * @return  void
*/
function rcp_process_profile_editor_updates() {

	// Profile field change request
	if ( empty( $_POST['rcp_action'] ) || $_POST['rcp_action'] !== 'edit_user_profile' || !is_user_logged_in() )
		return false;


	// Nonce security
	if ( ! wp_verify_nonce( $_POST['rcp_profile_editor_nonce'], 'rcp-profile-editor-nonce' ) )
		return false;

	$user_id      = get_current_user_id();
	$old_data     = get_userdata( $user_id );

	$display_name = ! empty( $_POST['rcp_display_name'] ) ? sanitize_text_field( $_POST['rcp_display_name'] ) : '';
	$first_name   = ! empty( $_POST['rcp_first_name'] )   ? sanitize_text_field( $_POST['rcp_first_name'] )   : '';
	$last_name    = ! empty( $_POST['rcp_last_name'] )    ? sanitize_text_field( $_POST['rcp_last_name'] )    : '';
	$email        = ! empty( $_POST['rcp_email'] )        ? sanitize_text_field( $_POST['rcp_email'] )        : '';

	$userdata = array(
		'ID'           => $user_id,
		'first_name'   => $first_name,
		'last_name'    => $last_name,
		'display_name' => $display_name,
		'user_email'   => $email
	);

	// Empty email
	if ( empty( $email ) || ! is_email( $email ) ) {
		rcp_errors()->add( 'empty_email', __( 'Please enter a valid email address', 'rcp' ) );
	}

	// Make sure the new email doesn't belong to another user
	if( $email != $old_data->user_email && email_exists( $email ) ) {
		rcp_errors()->add( 'email_exists', __( 'The email you entered belongs to another user. Please use another.', 'rcp' ) );
	}

	// New password
	if ( ! empty( $_POST['rcp_new_user_pass1'] ) ) {
		if ( $_POST['rcp_new_user_pass1'] !== $_POST['rcp_new_user_pass2'] ) {
			rcp_errors()->add( 'password_mismatch', __( 'The passwords you entered do not match. Please try again.', 'rcp' ) );
		} else {
			$userdata['user_pass'] = $_POST['rcp_new_user_pass1'];
		}
	}

	do_action( 'rcp_edit_profile_form_errors', $_POST, $user_id );

	// retrieve all error messages, if any
	$errors = rcp_errors()->get_error_messages();

	// only create the user if there are no errors
	if( empty( $errors ) ) {

		// Update the user
		$updated = wp_update_user( $userdata );
		$updated = apply_filters( 'rcp_edit_profile_update_user', $updated, $user_id, $_POST );

		if( $updated ) {
			do_action( 'rcp_user_profile_updated', $user_id, $userdata, $old_data );

			wp_safe_redirect( add_query_arg( 'rcp-message', 'profile-updated', sanitize_text_field( $_POST['rcp_redirect'] ) ) );

			exit;
		} else {
			rcp_errors()->add( 'not_updated', __( 'There was an error updating your profile. Please try again.', 'rcp' ) );
		}
	}
}
add_action( 'init', 'rcp_process_profile_editor_updates' );

/**
 * Change a user password
 *
 * @access  public
 * @since   1.0
 * @return  void
 */
function rcp_change_password() {
	// reset a users password
	if( isset( $_POST['rcp_action'] ) && $_POST['rcp_action'] == 'reset-password' ) {

		global $user_ID;

		list( $rp_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$rp_cookie = apply_filters( 'rcp_resetpass_cookie_name', 'rcp-resetpass-' . COOKIEHASH );

		$user = rcp_get_user_resetting_password( $rp_cookie );

		if( !is_user_logged_in() && !$user) {
			return;
		}

		if( wp_verify_nonce( $_POST['rcp_password_nonce'], 'rcp-password-nonce' ) ) {

			do_action( 'rcp_before_password_form_errors', $_POST );

			if( $_POST['rcp_user_pass'] == '' || $_POST['rcp_user_pass_confirm'] == '' ) {
				// password(s) field empty
				rcp_errors()->add( 'password_empty', __( 'Please enter a password, and confirm it', 'rcp' ), 'password' );
			}
			if( $_POST['rcp_user_pass'] != $_POST['rcp_user_pass_confirm'] ) {
				// passwords do not match
				rcp_errors()->add( 'password_mismatch', __( 'Passwords do not match', 'rcp' ), 'password' );
			}

			do_action( 'rcp_password_form_errors', $_POST );

			// retrieve all error messages, if any
			$errors = rcp_errors()->get_error_messages();

			if( empty( $errors ) ) {
				// change the password here
				$user_data = array(
					'ID' 		=> (is_user_logged_in()) ? $user_ID : $user->ID,
					'user_pass' => $_POST['rcp_user_pass']
				);
				wp_update_user( $user_data );
				// remove cookie with password reset info
				setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
				// send password change email here (if WP doesn't)
				wp_safe_redirect( add_query_arg( 'password-reset', 'true', $_POST['rcp_redirect'] ) );
				exit;
			}
		}
	}
}
add_action( 'init', 'rcp_change_password' );

/**
 * Updates member payment profile ID meta keys with old versions from pre 2.1 gateways
 *
 * @param string     $profile_id
 * @param int        $user_id
 * @param RCP_Member $member_object
 *
 * @access  public
 * @since   2.1
 * @return  string   The profile ID.
 */
function rcp_backfill_payment_profile_ids( $profile_id, $user_id, $member_object ) {

	if( empty( $profile_id ) ) {

		// Check for Stripe
		$profile_id = get_user_meta( $user_id, '_rcp_stripe_user_id', true );

		if( ! empty( $profile_id ) ) {

			$member_object->set_payment_profile_id( $profile_id );

		} else {

			// Check for PayPal
			$profile_id = get_user_meta( $user_id, 'rcp_recurring_payment_id', true );

			if( ! empty( $profile_id ) ) {

				$member_object->set_payment_profile_id( $profile_id );

			}

		}

	}

	return $profile_id;
}
add_filter( 'rcp_member_get_payment_profile_id', 'rcp_backfill_payment_profile_ids', 10, 3 );

/**
 * Retrieves the user account ID from a payment profile ID
 *
 * @param   string $profile_id Profile ID.
 *
 * @access  public
 * @since   2.1
 * @return  int|false User ID if found, false if not.
 */
function rcp_get_member_id_from_profile_id( $profile_id = '' ) {

	global $wpdb;

	$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'rcp_payment_profile_id' AND meta_value = %s LIMIT 1", $profile_id ) );

	if ( $user_id != NULL ) {
		return $user_id;
	}

	return false;
}

/**
 * Determines if a member can renew their subscription
 *
 * @deprecated 3.0 Use `RCP_Membership::can_renew()` instead.
 * @see RCP_Membership::can_renew()
 *
 * @param int $user_id The user ID to check, or 0 for the current user.
 *
 * @access public
 * @since  2.3
 * @return bool True if the user can renew, false if not.
 */
function rcp_can_member_renew( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( empty( $customer ) ) {
		return apply_filters( 'rcp_member_can_renew', false, $user_id );
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	if ( empty( $membership ) ) {
		return apply_filters( 'rcp_member_can_renew', false, $user_id );
	}

	return $membership->can_renew();
}

/**
 * Determines if a member can update the credit / debit card attached to their account
 *
 * @deprecated 3.0 Use `rcp_can_update_membership_billing_card()` instead.
 * @see rcp_can_update_membership_billing_card()
 *
 * @param int $user_id The ID of the user to check, or 0 for the current user.
 *
 * @access  public
 * @since   2.1
 * @return  bool
 */
function rcp_member_can_update_billing_card( $user_id = 0 ) {

	global $rcp_options;

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret = false;

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( ! empty( $customer ) ) {
		$membership = rcp_get_customer_single_membership( $customer->get_id() );

		if ( ! empty( $membership ) && rcp_can_update_membership_billing_card( $membership->get_id() ) ) {
			$ret = true;
		}
	}

	return apply_filters( 'rcp_member_can_update_billing_card', $ret, $user_id );
}

/**
 * Wrapper for RCP_Member->get_switch_to_url()
 *
 * @param int $user_id ID of the user to get the switch to URL for.
 *
 * @access public
 * @since  2.1
 * @return string|false The URL if available, false if not.
 */
function rcp_get_switch_to_url( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		return;
	}

	$member = new RCP_Member( $user_id );
	return $member->get_switch_to_url();

}

/**
 * Validate a potential username
 *
 * @param       string $username The username to validate
 *
 * @access      public
 * @since       2.2
 * @return      bool
 */
function rcp_validate_username( $username = '' ) {
	$sanitized = sanitize_user( $username, false );
	$valid = ( strtolower( $sanitized ) == strtolower( $username ) );
	return (bool) apply_filters( 'rcp_validate_username', $valid, $username );
}

/**
 * Disable toolbar for non-admins if option is enabled
 *
 * @since 2.7
 *
 * @return void
 */
function rcp_maybe_disable_toolbar() {

	global $rcp_options;

	if ( isset( $rcp_options['disable_toolbar'] ) && ! current_user_can( 'edit_posts' ) ) {
		add_filter( 'show_admin_bar', '__return_false' );
	}
}
add_action( 'init', 'rcp_maybe_disable_toolbar', 9999 );

/**
 * Determines if a member is pending email verification.
 *
 * @param int $user_id ID of the user to check.
 *
 * @return bool
 */
function rcp_is_pending_verification( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( empty( $customer ) ) {
		return false;
	}

	return $customer->is_pending_verification();

}

/**
 * Generate email verification link for a user
 *
 * @param int $user_id ID of the user to create the link for.
 *
 * @since  2.8.2
 * @return string|false Verification link on success, false on failure.
 */
function rcp_generate_verification_link( $user_id ) {

	if ( ! $user = get_user_by( 'id', $user_id ) ) {
		return false;
	}

	// The user should already be pending.
	if ( ! rcp_is_pending_verification( $user_id ) ) {
		return false;
	}

	$verify_link = add_query_arg( array(
		'rcp-verify-key' => urlencode( get_user_meta( $user_id, 'rcp_pending_email_verification', true ) ),
		'rcp-user'       => urlencode( $user->ID )
	), trailingslashit( home_url() ) );

	return apply_filters( 'rcp_email_verification_link', $verify_link, $user );

}

/**
 * Confirm email verification and redirect to Edit Profile page
 *
 * @since  2.8.2
 * @return void
 */
function rcp_confirm_email_verification() {

	if ( empty( $_GET['rcp-verify-key'] ) || empty( $_GET['rcp-user'] ) ) {
		return;
	}

	if ( ! $user = get_user_by( 'id', rawurldecode( $_GET['rcp-user'] ) ) ) {
		return;
	}

	if ( ! rcp_is_pending_verification( $user->ID ) ) {
		return;
	}

	if ( rawurldecode( $_GET['rcp-verify-key'] ) != get_user_meta( $user->ID, 'rcp_pending_email_verification', true ) ) {
		return;
	}

	$member   = new RCP_Member( $user ); // for backwards compatibility
	$customer = rcp_get_customer_by_user_id( $user->ID );

	if ( empty( $customer ) ) {
		return;
	}

	$customer->verify_email();

	global $rcp_options;

	$account_page = $rcp_options['account_page'];
	if ( ! $redirect = add_query_arg( array( 'rcp-message' => 'email-verified' ), get_post_permalink( $account_page ) ) ) {
		return;
	}

	wp_safe_redirect( apply_filters( 'rcp_verification_redirect_url', $redirect, $member ) );
	exit;

}
add_action( 'template_redirect', 'rcp_confirm_email_verification' );

/**
 * Process re-send verification email from the Edit Profile page
 *
 * @since  2.8.2
 * @return void
 */
function rcp_resend_email_verification() {

	// Profile field change request
	if ( empty( $_GET['rcp_action'] ) || $_GET['rcp_action'] !== 'resend_verification' || ! is_user_logged_in() ) {
		return;
	}

	// Nonce security
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp-verification-nonce' ) ) {
		return;
	}

	$customer = rcp_get_customer_by_user_id();

	if ( empty( $customer ) ) {
		return;
	}

	// Not pending verification.
	if ( ! $customer->is_pending_verification() ) {
		return;
	}

	rcp_send_email_verification( $customer->get_user_id() );

	// Redirect back to `redirect` query arg or Edit Profile page with success message.
	if ( isset( $_GET['redirect'] ) ) {
		$redirect = urldecode( $_GET['redirect'] );
	} else {
		global $rcp_options;

		$account_page = $rcp_options['account_page'];
		$redirect     = get_permalink( $account_page );
	}

	if ( empty( $redirect ) ) {
		return;
	}

	$redirect = add_query_arg( array( 'rcp-message' => 'verification-resent' ), $redirect );

	wp_safe_redirect( esc_url_raw( $redirect ) );
	exit;

}
add_action( 'init', 'rcp_resend_email_verification' );

/**
 * Add a note to the member when a recurring charge fails.
 *
 * @param RCP_Member          $member
 * @param RCP_Payment_Gateway $gateway
 *
 * @since  2.7.4
 * @return void
 */
function rcp_add_recurring_payment_failure_note( $member, $gateway ) {

	$membership = $gateway->membership;

	$gateway_classes = wp_list_pluck( rcp_get_payment_gateways(), 'class' );
	$gateway_name    = array_search( get_class( $gateway ), $gateway_classes );

	$note = sprintf( __( 'Recurring charge failed in %s.', 'rcp' ), ucwords( $gateway_name ) );

	if ( ! empty( $gateway->webhook_event_id ) ) {
		$note .= sprintf( __( ' Event ID: %s', 'rcp' ), $gateway->webhook_event_id );
	}

	$membership->add_note( $note );

	rcp_log( sprintf( 'Recurring payment failed for membership #%d. Gateway: %s; Membership Level: %s; Expiration Date: %s', $membership->get_id(), ucwords( $gateway_name ), $member->get_subscription_name(), $membership->get_expiration_date() ) );

}
add_action( 'rcp_recurring_payment_failed', 'rcp_add_recurring_payment_failure_note', 10, 2 );

/**
 * Adds a note to the member when a subscription is started, renewed, or changed.
 *
 * @deprecated 3.0
 *
 * @param string     $subscription_id The member's new subscription ID.
 * @param int        $member_id       The member ID.
 * @param RCP_Member $member          The RCP_Member object.
 *
 * @since 2.8.2
 * @return void
 */
function rcp_add_subscription_change_note( $subscription_id, $member_id, $member ) {

	$subscription_id          = (int) $subscription_id;
	$existing_subscription_id = (int) $member->get_subscription_id();

	if ( empty( $existing_subscription_id ) ) {
		$member->add_note( sprintf( __( '%s subscription started.', 'rcp' ), rcp_get_subscription_name( $subscription_id ) ) );
		return;
	}

	if ( $existing_subscription_id === $subscription_id ) {
		$member->add_note( sprintf( __( '%s subscription renewed.', 'rcp' ), rcp_get_subscription_name( $subscription_id ) ) );
		return;
	}

	if ( $existing_subscription_id !== $subscription_id ) {
		$member->add_note( sprintf( __( 'Subscription changed from %s to %s.', 'rcp' ), rcp_get_subscription_name( $existing_subscription_id ), rcp_get_subscription_name( $subscription_id ) ) );
		return;
	}

}
//add_action( 'rcp_member_pre_set_subscription_id', 'rcp_add_subscription_change_note', 10, 3 );
