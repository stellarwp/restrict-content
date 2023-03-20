<?php
/**
 * Admin Notices
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Notices
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Display admin notices
 *
 * @return void
 */
function rcp_admin_notices() {
	global $rcp_options;

	$message = ! empty( $_GET['rcp_message'] ) ? urldecode( $_GET['rcp_message'] ) : false;
	$class   = 'updated';
	$text    = '';

	if( current_user_can( 'rcp_manage_settings' ) ) {
		// only show notice if settings have never been saved
		if ( ! is_array( $rcp_options ) || empty( $rcp_options ) ) {
			echo '<div class="notice notice-info"><p><a href="' . admin_url( "admin.php?page=rcp-settings" ) . '">' . __( 'You should now configure your Restrict Content Pro settings', 'rcp' ) . '</a></p></div>';
		}

		if ( rcp_check_if_upgrade_needed() ) {
			echo '<div class="error"><p>' . __( 'The Restrict Content Pro database needs to be updated: ', 'rcp' ) . ' ' . '<a href="' . esc_url( add_query_arg( 'rcp-action', 'upgrade', admin_url() ) ) . '">' . __( 'upgrade now', 'rcp' ) . '</a></p></div>';
		}

		if ( isset( $_GET['rcp-db'] ) && $_GET['rcp-db'] == 'updated' ) {
			echo '<div class="updated fade"><p>' . __( 'The Restrict Content Pro database has been updated', 'rcp' ) . '</p></div>';
		}

		if ( false !== get_transient( 'rcp_login_redirect_invalid' ) ) {
			echo '<div class="error"><p>' . __( 'The page selected for log in redirect does not appear to contain a log in form. Please add [login_form] to the page then re-enable the log in redirect option.', 'rcp' ) . '</p></div>';
		}

		if ( ( ! empty( $rcp_options['paid_message'] ) || ! empty( $rcp_options['free_message'] ) ) && empty( $rcp_options['restriction_message'] ) && ! get_user_meta( get_current_user_id(), '_rcp_content_restriction_message_missing_dismissed', true ) ) {
			echo '<div class="notice notice-info">';
			echo '<p>' . __( 'The Restrict Content Pro "Free Content Message" and "Premium Content Message" settings have been merged into one "Restricted Content Message" setting field. Please visit the', 'rcp' ) . ' <a href="' . esc_url( admin_url( "admin.php?page=rcp-settings" ) ) . '">' . __( 'settings page', 'rcp' ) . '</a> ' . __( 'to confirm your new message.', 'rcp' ) . '</p>';
			echo '<p>' . __( 'For more information, view our version 3.0 release post on the Restrict Content Pro blog: ', 'rcp' ) . '<a href="https://restrictcontentpro.com/?p=102444#restriction-message" target="_blank">https://restrictcontentpro.com/blog</a></p>';
			echo '<p><a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'rcp_notice' => 'content_restriction_message_missing' ) ), 'rcp_dismiss_notice', 'rcp_dismiss_notice_nonce' ) ) . '">' . __( 'Dismiss Notice', 'rcp' ) . '</a></p>';
			echo '</div>';
		}

		$stripe_user_id = get_option( 'rcp_stripe_connect_account_id' );
		if( empty( $stripe_user_id ) && ( rcp_is_gateway_enabled( 'stripe' ) || rcp_is_gateway_enabled( 'stripe_checkout' ) ) && ! get_user_meta( get_current_user_id(), '_rcp_stripe_connect_dismissed', true ) ) {
			echo '<div class="notice notice-info">';
			echo '<p>' . sprintf( __( 'Restrict Content Pro now supports Stripe Connect for easier setup and improved security. <a href="%s">Click here</a> to learn more about connecting your Stripe account.', 'rcp' ), esc_url( admin_url( 'admin.php?page=rcp-settings#payments' ) ) ) . '</p>';
			echo '<p><a href="' . wp_nonce_url( add_query_arg( array( 'rcp_notice' => 'stripe_connect' ) ), 'rcp_dismiss_notice', 'rcp_dismiss_notice_nonce' ) . '">' . _x( 'Dismiss Notice', 'Stripe Connect', 'rcp' ) . '</a></p>';
			echo '</div>';
		}
	}

	if( current_user_can( 'activate_plugins' ) ) {
		if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
			echo '<div class="error">';
			echo '<p><strong>' . __( 'Restrict Content Pro is increasing its PHP version requirement', 'rcp' ) . '</strong></p>';
			echo '<p>' . sprintf( __( 'Restrict Content Pro will be increasing its PHP requirement to version 5.6 or higher in version 3.0. It looks like you\'re using version %s, which means you will need to upgrade your version of PHP before upgrading to Restrict Content Pro 3.0. Newer versions of PHP are both faster and more secure. The version you\'re using <a href="%s" target="_blank">no longer receives security updates</a>, which is another great reason to update.', 'rcp' ), PHP_VERSION, 'http://php.net/eol.php' ) . '</p>';
			echo '<p><strong>' . __( 'Which version should I upgrade to?', 'rcp' ) . '</strong></p>';
			echo '<p>' . __( 'In order to be compatible with future versions of Restrict Content Pro, you should update your PHP version to 5.6, 7.0, 7.1, or 7.2. On a normal WordPress site, switching to PHP 5.6 should never cause issues. We would however actually recommend you switch to PHP 7.1 or higher to receive the full speed and security benefits provided to more modern and fully supported versions of PHP. However, some plugins may not be fully compatible with PHP 7+, so more testing may be required.', 'rcp' ) . '</p>';
			echo '<p><strong>' . __( 'Need help upgrading? Ask your web host!', 'rcp' ) . '</strong></p>';
			echo '<p>' . sprintf( __( 'Many web hosts can give you instructions on how/where to upgrade your version of PHP through their control panel, or may even be able to do it for you. If they do not want to upgrade your PHP version then we would suggest you switch hosts. All of the <a href="%s" target="_blank">WordPress hosting partners</a> support PHP 7.0 and higher.', 'rcp' ), 'https://wordpress.org/hosting/' ) . '</p>';
			echo '</div>';
		}

		if ( function_exists( 'rcp_register_stripe_gateway' ) ) {
			$deactivate_url = add_query_arg( array( 's' => 'restrict+content+pro+-+stripe' ), admin_url( 'plugins.php' ) );
			echo '<div class="error"><p>' . sprintf( __( 'You are using an outdated version of the Stripe integration for Restrict Content Pro. Please <a href="%s">deactivate</a> the add-on version to configure the new version.', 'rcp' ), $deactivate_url ) . '</p></div>';
		}

		if ( function_exists( 'rcp_register_paypal_pro_express_gateway' ) ) {
			$deactivate_url = add_query_arg( array( 's' => 'restrict+content+pro+-+paypal+pro' ), admin_url( 'plugins.php' ) );
			echo '<div class="error"><p>' . sprintf( __( 'You are using an outdated version of the PayPal Pro / Express integration for Restrict Content Pro. Please <a href="%s">deactivate</a> the add-on version to configure the new version.', 'rcp' ), $deactivate_url ) . '</p></div>';
		}

		// Show notice about installing new Authorize.net add-on.
		if ( rcp_is_gateway_enabled( 'authorizenet' ) && ! defined( 'RCP_ANET_VERSION' ) ) {
			echo '<div class="error">';
			echo '<p><strong>' . __( 'ACTION REQUIRED: You need to update your Authorize.net payment gateway for Restrict Content Pro' ) . '</strong></p>';
			echo '<p>' . sprintf( __( 'The Authorize.net payment gateway has been removed from the main Restrict Content Pro plugin. To continue processing payments with this gateway you need to install the new Authorize.net add-on. Once installed, please follow the instructions to <a href="%s" target="_blank">enter your signature key</a> and <a href="%s" target="_blank">set up a webhook</a>.' ), 'https://docs.restrictcontentpro.com/article/1765-authorize-net#api-credentials', 'https://docs.restrictcontentpro.com/article/1765-authorize-net#webhook' ) . '</p>';
			echo '<p><a href="' . esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=rcp-authorize-net' ), 'install-plugin_rcp-authorize-net' ) ) . '" class="button button-primary">' . __( 'Install', 'rcp' ) . '</a></p>';
			echo '</div>';
		}

		// Show notice about ending support for Stripe Checkout.
		if ( rcp_is_gateway_enabled( 'stripe_checkout' ) ) {
			echo '<div class="error">';
			echo '<p><strong>' . __( 'Restrict Content Pro: Support for Stripe Checkout is ending in version 3.2' ) . '</strong></p>';
			echo '<p>' . sprintf( __( 'Stripe will not be updating the Stripe Checkout modal to comply with Strong Customer Authentication (SCA) and as a result, the Stripe Checkout gateway will be removed from Restrict Content Pro in version 3.2. You will automatically be switched over to our Stripe Elements gateway instead. This will affect the appearance of your registration form, but will not impact payment processing or renewals. <a href="%s" target="_blank">Click here to learn more.</a>' ), 'https://docs.restrictcontentpro.com/article/1552-stripe-checkout' ) . '</p>';
			echo '</div>';
		}
	}

	$screen = get_current_screen();

	// Message on settings page if email sending is disabled by RCP_DISABLE_EMAILS
	if( $screen->id === 'restrict_page_rcp-settings' && defined( 'RCP_DISABLE_EMAILS' ) && RCP_DISABLE_EMAILS ) {
		echo '<div class="notice notice-info"><p>' . sprintf( __( 'Restrict Content Pro will not send emails because the %s constant is active. Remove it from your code to re-enable email notifications. (Emails generated by other plugins or WordPress core are not affected.)', 'rcp' ), '<code>RCP_DISABLE_EMAILS</code>' ) . '</p></div>';
	}

	// Payment messages.
	if( current_user_can( 'rcp_manage_payments' ) ) {
		switch( $message ) {
			case 'payment_deleted' :

				$text = __( 'Payment deleted', 'rcp' );
				break;

			case 'payment_added' :

				$text = __( 'Payment added', 'rcp' );
				break;

			case 'payment_not_added' :

				$text = __( 'Payment creation failed', 'rcp' );
				$class = 'error';
				break;

			case 'payment_updated' :

				$text = __( 'Payment updated', 'rcp' );
				break;

			case 'payment_not_updated' :

				$text = __( 'Payment update failed', 'rcp' );
				break;
		}
	}

	// Upgrade messages.
	if( current_user_can( 'rcp_manage_settings' ) ) {
		switch( $message ) {
			case 'upgrade-complete' :

				$text =  __( 'Database upgrade complete', 'rcp' );
				break;
		}
	}

	// Member messages.
	if( current_user_can( 'rcp_manage_members' ) ) {
		switch( $message ) {
			case 'user_added' :

				$text = __( 'The user\'s membership has been added', 'rcp' );
				break;

			case 'user_not_added' :

				$text = __( 'The user\'s membership could not be added', 'rcp' );
				$class = 'error';
				break;

			case 'members_updated' :

				$text = __( 'Member accounts updated', 'rcp' );
				break;

			case 'member_cancelled' :

				$text = __( 'Member\'s payment profile cancelled successfully', 'rcp' );
				break;

			case 'member_cancelled_error' :
				$text = __( 'The member\'s payment profile could not be cancelled. Please see the member\'s user notes for details.', 'rcp' );
				$class = 'error';
				break;

			case 'verification_sent' :

				$text = __( 'Verification email sent successfully.', 'rcp' );
				break;

			case 'email_verified' :

				$text = __( 'The user\'s email has been verified successfully', 'rcp' );
				break;
		}
	}

	// Customer messages.
	if ( current_user_can( 'rcp_manage_members' ) ) {
		switch ( $message ) {
			case 'customer_added' :

				$text = __( 'Customer added', 'rcp' );
				break;

			case 'user_updated' :

				$text = __( 'Customer updated', 'rcp' );
				break;

			case 'customer_note_added' :

				$text = __( 'Note added', 'rcp' );
				break;

			case 'customer_deleted' :

				$text = __( 'Customer deleted', 'rcp' );
				break;
		}
	}

	// Membership messages.
	if ( current_user_can( 'rcp_manage_members' ) ) {
		switch( $message ) {
			case 'membership_added' :

				$text = __( 'Membership added', 'rcp' );
				break;

			case 'membership_updated' :

				$text = __( 'Membership updated', 'rcp' );
				break;

			case 'membership_level_changed' :

				$text = __( 'Membership level changed', 'rcp' );
				break;

			case 'membership_expired' :

				$text = __( 'Membership has been expired. The customer no longer has access to associated content.', 'rcp' );
				break;

			case 'membership_cancelled' :

				$text = __( 'Membership cancelled. The customer will retain access until they reach their expiration date.', 'rcp' );
				break;

			case 'membership_cancellation_failed' :

				$text = ! empty( $_GET['rcp_cancel_failure_message'] ) ? sprintf( __( 'Membership cancellation failed: %s', 'rcp' ), esc_html( urldecode( $_GET['rcp_cancel_failure_message'] ) ) ) : __( 'Membership cancellation failed', 'rcp' );
				$class = 'error';
				break;

			case 'membership_note_added' :

				$text = __( 'Note added', 'rcp' );
				break;

			case 'membership_deleted' :

				$text = __( 'Membership deleted', 'rcp' );
				break;

		}

		// Extra message for auto renew toggle.
		if ( ! empty( $_GET['rcp_auto_renew_toggle_error'] ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php _e( 'Failed to change auto renew settings.', 'rcp' ); ?></strong>
					<?php echo esc_html( urldecode( $_GET['rcp_auto_renew_toggle_error'] ) ); ?>
				</p>
			</div>
			<?php
		}
	}

	// Level messages.
	if( current_user_can( 'rcp_manage_levels' ) ) {
		switch( $message ) {
			case 'level_added' :

				$text = __( 'Membership level added', 'rcp' );
				break;

			case 'level_updated' :

				$text = __( 'Membership level updated', 'rcp' );
				break;

			case 'invalid_level_price' :

				$text  = __( 'Invalid price: the membership level price must be a valid positive number.', 'rcp' );
				$class = 'error';
				break;

			case 'invalid_level_fee' :

				$text  = __( 'Invalid fee: the membership level price must be a valid positive number.', 'rcp' );
				$class = 'error';
				break;

			case 'invalid_level_trial' :

				$text = sprintf( __( 'Invalid trial: a membership level with a trial must have a price and duration greater than zero. Please see <a href="%s">the documentation article on creating trials</a> for further instructions.', 'rcp' ), 'http://docs.restrictcontentpro.com/article/1764-creating-free-trials' );
				$class = 'error';
				break;

			case 'invalid_maximum_renewals' :

				$text = __( 'Invalid Maximum Renewals: A one-time payment cannot include a payment plan.', 'rcp' );
				$class = 'error';
				break;

			case 'level_not_added' :

				$text  = __( 'An unexpected error occurred while trying to add the membership level.', 'rcp' );
				$class = 'error';
				break;

			case 'level_not_updated' :

				$text = __( 'An unexpected error occurred while trying to update the membership level.', 'rcp' );
				$class = 'error';
				break;

			case 'level_missing_fields' :

				$text = __( 'Membership level fields are required', 'rcp' );
				$class = 'error';
				break;

			case 'level_deleted' :

				$text = __( 'Membership level deleted', 'rcp' );
				break;

			case 'level_activated' :

				$text = __( 'Membership level activated', 'rcp' );
				break;

			case 'level_deactivated' :

				$text = __( 'Membership level deactivated', 'rcp' );
				break;
		}
	}

	// Discount messages.
	if( current_user_can( 'rcp_manage_discounts' ) ) {
		switch ( $message ) {
			case 'discount_added' :

				$text = __( 'Discount code created', 'rcp' );
				break;

			case 'discount_not_added' :

				$text  = __( 'The discount code could not be created due to an error', 'rcp' );
				$class = 'error';
				break;

			case 'discount_code_already_exists' :

				if ( ! empty( $_GET['discount_code'] ) ) {
					$text = sprintf( __( 'A discount with the code %s already exists.', 'rcp' ), '<code>' . esc_html( urldecode( $_GET['discount_code'] ) ) . '</code>' );
				} else {
					$text = __( 'A discount with this code already exists.', 'rcp' );
				}
				$class = 'error';
				break;

			case 'discount_amount_missing' :

				$text  = __( 'Please enter a discount amount containing numbers only.', 'rcp' );
				$class = 'error';
				break;

			case 'discount_invalid_percent' :

				$text  = __( 'Percentage discounts must be whole numbers between 1 and 100.', 'rcp' );
				$class = 'error';
				break;

			case 'discount_deleted' :

				$text = __( 'Discount code successfully deleted', 'rcp' );
				break;

			case 'discount_activated' :

				$text = __( 'Discount code activated', 'rcp' );
				break;

			case 'discount_deactivated' :

				$text = __( 'Discount code deactivated', 'rcp' );
				break;
		}
	}

	// Post type restriction messages.
	if( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) {
		switch ( $message ) {
			case 'post-type-updated' :
				$text = __( 'Post type restrictions updated.', 'rcp' );
				break;
		}
	}

	// Telemetry Notices.
	if( current_user_can( 'rcp_manage_settings' ) ) {
		switch( $message ) {
			case 'opt_in_message' :
				$text = __('Telemetry status changed to Opt In', 'rcp');
				break;
			case 'opt_out_message' :
				$text = __('Telemetry status changed to Opt Out', 'rcp');
				break;
		}
	}
	// Membership reminder messages.
	if( current_user_can( 'rcp_manage_settings' ) ) {
		switch( $message ) {
			case 'reminder_added' :

				$text = __( 'Membership reminder added', 'rcp' );
				break;

			case 'reminder_updated' :

				$text = __( 'Membership reminder updated', 'rcp' );
				break;

			case 'reminder_deleted' :

				$text = __( 'Membership reminder deleted', 'rcp' );
				break;

			case 'test_reminder_sent' :

				$text = __( 'Test reminder sent successfully', 'rcp' );
				break;

			case 'test_email_sent' :

				$current_user = wp_get_current_user();
				$text         = sprintf( __( 'Test email sent successfully to %s', 'rcp' ), $current_user->user_email );
				break;

			case 'test_email_not_sent' :

				$text  = __( 'Test email failed to send.', 'rcp' );
				$class = 'error';
				break;
		}

		if( $message ) {
			echo '<div class="' . $class . '"><p>' . $text . '</p></div>';
		}
	}
}
add_action( 'admin_notices', 'rcp_admin_notices' );

/**
 * Dismiss an admin notice for current user
 *
 * @access      private
 * @return      void
*/
function rcp_dismiss_notices() {

	if( empty( $_GET['rcp_dismiss_notice_nonce'] ) || empty( $_GET['rcp_notice'] ) ) {
		return;
	}

	if( ! wp_verify_nonce( $_GET['rcp_dismiss_notice_nonce'], 'rcp_dismiss_notice') ) {
		wp_die( __( 'Security check failed', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	$notice = sanitize_key( $_GET['rcp_notice'] );

	update_user_meta( get_current_user_id(), "_rcp_{$notice}_dismissed", 1 );

	do_action( 'rcp_dismiss_notices', $notice );

	wp_redirect( remove_query_arg( array( 'rcp_notice' ) ) );
	exit;

}
add_action( 'admin_init', 'rcp_dismiss_notices' );
