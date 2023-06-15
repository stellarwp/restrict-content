<?php
/**
 * Install Functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Install Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Runs on plugin install to create the database, options, and defaults.
 *
 * @param bool $network_wide Whether the plugin is being network activated.
 *
 * @return void
 */
function rcp_options_install( $network_wide = false ) {
	global $wpdb, $rcp_db_name, $rcp_db_version, $rcp_discounts_db_name, $rcp_discounts_db_version,
		   $rcp_payments_db_name, $rcp_payments_db_version;

	$rcp_options = get_option( 'rcp_settings', array() );

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	/**
	 * If the plugin is being network activated, create the tables
	 * on the shutdown hook. Otherwise do it now.
	 * @see https://github.com/restrictcontentpro/restrict-content-pro/issues/669
	 */
	if ( $network_wide ) {
		add_action( 'shutdown', 'rcp_create_tables' );
	} else {
		rcp_create_tables();
	}

	update_option( "rcp_db_version", $rcp_db_version );

	update_option( "rcp_discounts_db_version", $rcp_discounts_db_version );

	update_option( "rcp_payments_db_version", $rcp_payments_db_version );

	// Create RCP caps
	$caps = new RCP_Capabilities;
	$caps->add_caps();

	// Insert default notices.
	$reminders = new RCP_Reminders();
	$notices   = $reminders->get_notices();
	if ( empty( $notices ) ) {
		$notices[] = $reminders->get_default_notice( 'renewal' );
		$notices[] = $reminders->get_default_notice( 'expiration' );

		update_option( 'rcp_reminder_notices', $notices );
	}

	// Insert default email templates.
	if ( empty( $rcp_options ) ) {
		$rcp_options = rcp_create_default_email_templates();
	}

	update_option( 'rcp_settings', $rcp_options );

	// Initialize new RCP Settings.
	rcp_init_settings( RCP_PLUGIN_VERSION );
	// and option that allows us to make sure RCP is installed
	update_option( 'rcp_is_installed', '1' );

	if ( ! get_option( 'rcp_version' ) ) {
		update_option( 'rcp_version', RCP_PLUGIN_VERSION );
	}

	do_action( 'rcp_options_install' );
}
// run the install scripts upon plugin activation
register_activation_hook( RCP_PLUGIN_FILE, 'rcp_options_install' );

/**
 * Check if RCP is installed and if not, run installation
 *
 * @return void
 */
function rcp_check_if_installed() {

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	// this is mainly for network activated installs
	if( ! get_option( 'rcp_is_installed' ) ) {
		rcp_options_install();
	}
}
add_action( 'admin_init', 'rcp_check_if_installed' );

/**
 * Creates the Restrict Content Pro database tables.
 *
 * Note: This creates pre-3.0 tables only. The new tables in version 3.0 can be created like so:
 *
 * restrict_content_pro()->memberships_table->create()
 * restrict_content_pro()->customers_table->create()
 * restrict_content_pro()->queue_table->create()
 *
 * @since 2.7
 * @return void
 */
function rcp_create_tables() {

	// Create membership level meta table
	$sub_meta_table_name = rcp_get_level_meta_db_name();
	$sql = "CREATE TABLE {$sub_meta_table_name} (
		meta_id bigint(20) NOT NULL AUTO_INCREMENT,
		level_id bigint(20) NOT NULL DEFAULT '0',
		meta_key varchar(255) DEFAULT NULL,
		meta_value longtext,
		PRIMARY KEY meta_id (meta_id),
		KEY level_id (level_id),
		KEY meta_key (meta_key)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	@dbDelta( $sql );

	// create the RCP discounts database table
	if ( ! restrict_content_pro()->discounts_table->exists() ) {
		restrict_content_pro()->discounts_table->install();
	}

	// create the RCP payments database table
	$rcp_payments_db_name = rcp_get_payments_db_name();
	$sql = "CREATE TABLE {$rcp_payments_db_name} (
		id bigint(9) unsigned NOT NULL AUTO_INCREMENT,
		customer_id bigint(20) unsigned NOT NULL,
		membership_id bigint(20) unsigned NOT NULL,
		subscription varchar(200) NOT NULL,
		object_id bigint(9) unsigned NOT NULL,
		object_type varchar(20) NOT NULL DEFAULT 'subscription',
		date datetime NOT NULL,
		amount mediumtext NOT NULL,
		subtotal mediumtext NOT NULL,
		credits mediumtext NOT NULL,
		fees mediumtext NOT NULL,
		discount_amount mediumtext NOT NULL,
		discount_code tinytext NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		payment_type tinytext NOT NULL,
		transaction_type varchar(12) NOT NULL,
		subscription_key varchar(32) NOT NULL,
		transaction_id varchar(64) NOT NULL,
		status varchar(12) NOT NULL,
		gateway tinytext NOT NULL,
		PRIMARY KEY id (id),
		KEY subscription (subscription),
		KEY customer_id (customer_id),
		KEY membership_id (membership_id),
		KEY user_id (user_id),
		KEY transaction_type (transaction_type),
		KEY subscription_key (subscription_key),
		KEY transaction_id (transaction_id),
		KEY status (status)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	@dbDelta( $sql );

	// Create payment meta table
	$payment_meta_table_name = rcp_get_payment_meta_db_name();
	$sql = "CREATE TABLE {$payment_meta_table_name} (
		meta_id bigint(20) NOT NULL AUTO_INCREMENT,
		rcp_payment_id bigint(20) NOT NULL DEFAULT '0',
		meta_key varchar(255) DEFAULT NULL,
		meta_value longtext,
		PRIMARY KEY meta_id (meta_id),
		KEY rcp_payment_id (rcp_payment_id),
		KEY meta_key (meta_key)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	@dbDelta( $sql );

	do_action( 'rcp_create_tables' );
}

/**
 * Create pages for Restrict Content Pro
 *
 * @since 3.0.3
 * @return void
 */
function rcp_create_pages() {

	// Bail if pages have already been created.
	if( get_option( 'rcp_install_pages_created' ) ) {
		return;
	}

	global $rcp_options, $wpdb;

	$author_id = current_user_can( 'edit_others_pages' ) ? get_current_user_id() : 1;

	// Checks if the purchase page option exists
	if ( ! isset( $rcp_options['registration_page'] ) ) {

		// Register Page
		$register = wp_insert_post(
			array(
				'post_title'     => __( 'Register', 'rcp' ),
				'post_content'   => '[register_form]',
				'post_status'    => 'publish',
				'post_author'    => $author_id,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);

		// Welcome (Success) Page
		$success = wp_insert_post(
			array(
				'post_title'     => __( 'Welcome', 'rcp' ),
				'post_content'   => __( 'Welcome! This is your success page where members are redirected after completing their registration.', 'rcp' ),
				'post_status'    => 'publish',
				'post_author'    => $author_id,
				'post_parent'    => $register,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);

		// Store our page IDs
		$rcp_options['registration_page'] = $register;
		$rcp_options['redirect']  = $success;

	}

	// Checks if the account page option exists
	if ( empty( $rcp_options['account_page'] ) ) {

		$account = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'page' AND post_content LIKE '%[subscription_details%' LIMIT 1;" );

		if( empty( $account ) ) {

			// Account Page
			$account = wp_insert_post(
				array(
					'post_title'     => __( 'Your Membership', 'rcp' ),
					'post_content'   => '[subscription_details]',
					'post_status'    => 'publish',
					'post_author'    => $author_id,
					'post_parent'    => $rcp_options['registration_page'],
					'post_type'      => 'page',
					'comment_status' => 'closed'
				)
			);

		}

		// Store our page IDs
		$rcp_options['account_page'] = $account;

	}

	// Checks if the profile editor page option exists
	if ( empty( $rcp_options['edit_profile'] ) ) {

		$profile = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'page' AND post_content LIKE '%[rcp_profile_editor%' LIMIT 1;" );

		if( empty( $profile ) ) {

			// Profile editor Page
			$profile = wp_insert_post(
				array(
					'post_title'     => __( 'Edit Your Profile', 'rcp' ),
					'post_content'   => '[rcp_profile_editor]',
					'post_status'    => 'publish',
					'post_author'    => $author_id,
					'post_parent'    => $rcp_options['registration_page'],
					'post_type'      => 'page',
					'comment_status' => 'closed'
				)
			);

		}

		// Store our page IDs
		$rcp_options['edit_profile'] = $profile;

	}

	// Checks if the update billing card page option exists
	if ( empty( $rcp_options['update_card'] ) ) {

		$update_card = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'page' AND post_content LIKE '%[rcp_update_card%' LIMIT 1;" );

		if( empty( $update_card ) ) {

			// update_card editor Page
			$update_card = wp_insert_post(
				array(
					'post_title'     => __( 'Update Billing Card', 'rcp' ),
					'post_content'   => '[rcp_update_card]',
					'post_status'    => 'publish',
					'post_author'    => $author_id,
					'post_parent'    => $rcp_options['registration_page'],
					'post_type'      => 'page',
					'comment_status' => 'closed'
				)
			);

		}

		// Store our page IDs
		$rcp_options['update_card'] = $update_card;

	}

	update_option( 'rcp_settings', $rcp_options );

	update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );

}

add_action( 'admin_init', 'rcp_create_pages' );

/**
 * Create default email templates.
 *
 * @since 3.1
 * @return array
 */
function rcp_create_default_email_templates() {

	$templates = array();

	$site_name = stripslashes_deep( html_entity_decode( get_bloginfo('name'), ENT_COMPAT, 'UTF-8' ) );

	// Email verification
	$templates['verification_subject'] = __( 'Please verify your email address', 'rcp' );
	$verification_email                = sprintf( __( 'Hi %s,', 'rcp' ), '%displayname%' ) . "\n\n";
	$verification_email               .= __( 'Please click here to verify your email address:', 'rcp' ) . "\n\n";
	$verification_email               .= '%verificationlink%';
	$templates['verification_email']   = $verification_email;

	// Active Membership Email (member)
	$templates['active_subject'] = sprintf( __( 'Your %s membership has been activated', 'rcp' ), $site_name );
	$active_email                = __( 'Hi %displayname%,', 'rcp' ) . "\n\n";
	$active_email               .= sprintf( __( 'Your %s membership has been activated.', 'rcp' ), '%subscription_name%' );
	$templates['active_email']   = $active_email;

	// Active Membership Email (admin)
	$templates['active_subject_admin'] = sprintf( __( 'New membership on %s', 'rcp' ), $site_name );
	$active_admin_email                = __( 'Hello', 'rcp' ) . "\n\n";
	$active_admin_email               .= sprintf( __( '%s (%s) is now a member of %s', 'rcp' ), '%displayname%', '%username%', $site_name ). ".\n\n";
	$active_admin_email               .= sprintf( __( 'Membership level: %s', 'rcp' ), '%subscription_name%' ) . "\n\n";
	$active_admin_email               .= __( 'Thank you', 'rcp' );
	$templates['active_email_admin']   = $active_admin_email;

	// Free Membership Email (member)
	$templates['free_subject'] = sprintf( __( 'Your %s membership has been activated', 'rcp' ), $site_name );
	$free_email                = __( 'Hi %displayname%,', 'rcp' ) . "\n\n";
	$free_email               .= sprintf( __( 'Your %s membership has been activated.', 'rcp' ), '%subscription_name%' );
	$templates['free_email']   = $free_email;

	// Free Membership Email (admin)
	$templates['free_subject_admin'] = sprintf( __( 'New free membership on %s', 'rcp' ), $site_name );
	$free_admin_email                = __( 'Hello', 'rcp' ) . "\n\n";
	$free_admin_email               .= sprintf( __( '%s (%s) is now a member of %s', 'rcp' ), '%displayname%', '%username%', $site_name ). ".\n\n";
	$free_admin_email               .= sprintf( __( 'Membership level: %s', 'rcp' ), '%subscription_name%' ) . "\n\n";
	$free_admin_email               .= __( 'Thank you', 'rcp' );
	$templates['free_email_admin']   = $free_admin_email;

	// Trial Membership Email (member)
	$templates['trial_subject'] = sprintf( __( 'Your %s membership has been activated', 'rcp' ), $site_name );
	$trial_email                = __( 'Hi %displayname%,', 'rcp' ) . "\n\n";
	$trial_email               .= sprintf( __( 'Your %s membership has been activated.', 'rcp' ), '%subscription_name%' );
	$templates['trial_email']   = $trial_email;

	// Trial Membership Email (admin)
	$templates['trial_subject_admin'] = sprintf( __( 'New trial membership on %s', 'rcp' ), $site_name );
	$trial_admin_email                = __( 'Hello', 'rcp' ) . "\n\n";
	$trial_admin_email               .= sprintf( __( '%s (%s) is now a member of %s', 'rcp' ), '%displayname%', '%username%', $site_name ). ".\n\n";
	$trial_admin_email               .= sprintf( __( 'Membership level: %s', 'rcp' ), '%subscription_name%' ) . "\n\n";
	$trial_admin_email               .= __( 'Thank you', 'rcp' );
	$templates['trial_email_admin']   = $trial_admin_email;

	// Cancelled Membership Email (member)
	$templates['cancelled_subject'] = sprintf( __( 'Your %s membership has been cancelled', 'rcp' ), $site_name );
	$cancelled_email                = __( 'Hi %displayname%,', 'rcp' ) . "\n\n";
	$cancelled_email               .= sprintf( __( 'Your %s membership has been cancelled. You will retain access to content until %s.', 'rcp' ), '%subscription_name%', '%expiration%' );
	$templates['cancelled_email']   = $cancelled_email;

	// Cancelled Membership Email (admin)
	$templates['cancelled_subject_admin'] = sprintf( __( 'Cancelled membership on %s', 'rcp' ), $site_name );
	$cancelled_admin_email                = __( 'Hello', 'rcp' ) . "\n\n";
	$cancelled_admin_email               .= sprintf( __( '%s (%s) has cancelled their membership to %s', 'rcp' ), '%displayname%', '%username%', $site_name ). ".\n\n";
	$cancelled_admin_email               .= sprintf( __( 'Their membership level was: %s', 'rcp' ), '%subscription_name%' ) . "\n\n";
	$cancelled_admin_email               .= sprintf( __( 'They will retain access until: %s', 'rcp' ), '%expiration%' ) . "\n\n";
	$cancelled_admin_email               .= __( 'Thank you', 'rcp' );
	$templates['cancelled_email_admin']   = $cancelled_admin_email;

	// Expired Membership Email (member)
	$templates['expired_subject'] = sprintf( __( 'Your %s membership has expired', 'rcp' ), $site_name );
	$expired_email                = __( 'Hi %displayname%,', 'rcp' ) . "\n\n";
	$expired_email               .= sprintf( __( 'Your %s membership has expired.', 'rcp' ), '%subscription_name%' );
	$templates['expired_email']   = $expired_email;

	// Expired Membership Email (admin)
	$templates['expired_subject_admin'] = sprintf( __( 'Expired membership on %s', 'rcp' ), $site_name );
	$expired_admin_email                = __( 'Hello', 'rcp' ) . "\n\n";
	$expired_admin_email               .= sprintf( __( '%s\'s (%s) membership has expired.', 'rcp' ), '%displayname%', '%username%' ). ".\n\n";
	$expired_admin_email               .= sprintf( __( 'Their membership level was: %s', 'rcp' ), '%subscription_name%' ) . "\n\n";
	$expired_admin_email               .= __( 'Thank you', 'rcp' );
	$templates['expired_email_admin']   = $expired_admin_email;

	// Payment Received Email (member)
	$templates['payment_received_subject'] = sprintf( __( 'Your %s payment has been received', 'rcp' ), $site_name );
	$payment_received_email                = __( 'Hi %displayname%,', 'rcp' ) . "\n\n";
	$payment_received_email               .= sprintf( __( 'Your %s payment has been received.', 'rcp' ), '%subscription_name%' ) . "\n\n";
	$payment_received_email               .= sprintf( __( 'Payment Amount: ', 'rcp' ), '%amount%' ) . "\n\n";
	$payment_received_email               .= sprintf( __( 'Invoice: ', 'rcp' ), '%invoice_url%' );
	$templates['payment_received_email']   = $payment_received_email;

	// Renewal Payment Failed Email (member)
	$templates['renewal_payment_failed_subject'] = sprintf( __( 'Your %s payment could not be processed', 'rcp' ), $site_name );
	$renewal_payment_failed_email                = __( 'Hi %displayname%,', 'rcp' ) . "\n\n";
	$renewal_payment_failed_email               .= sprintf( __( 'Your %s renewal payment could not be processed. Please log in and update your billing information so we can reattempt payment.', 'rcp' ), '%subscription_name%' ) . "\n\n";
	$renewal_payment_failed_email               .= wp_login_url( home_url() );
	$templates['renewal_payment_failed_email']   = $renewal_payment_failed_email;

	// Renewal Payment Failed (admin)
	$templates['renewal_payment_failed_subject_admin'] = sprintf( __( 'Renewal payment failed on %s', 'rcp' ), $site_name );
	$renewal_payment_failed_admin_email                = __( 'Hello', 'rcp' ) . "\n\n";
	$renewal_payment_failed_admin_email               .= sprintf( __( '%s\'s (%s) renewal payment failed to be processed.', 'rcp' ), '%displayname%', '%username%' );
	$renewal_payment_failed_admin_email               .= __( 'Thank you', 'rcp' );
	$templates['renewal_payment_failed_email_admin']   = $renewal_payment_failed_admin_email;

	return $templates;

}

/**
 * Initialize new settings or update existing settings.
 *
 * The goal is to handle versioning for any new or existing versions.
 *
 * @param string $_rcp_version The RCP versions.
 * @return void It doesn't return information it just set up information.
 */
function rcp_init_settings( $_rcp_version ) {
	// Get current setting if exists.
	$all_rcp_options = get_option('rcp_settings');
	// Add new settings for version 3.5.25
	if ( version_compare($_rcp_version, '3.5.25', '>=' ) )  {
		// Check if options exits.
		if (!array_key_exists('stripe_webhooks', $all_rcp_options)) {
			$all_rcp_options['stripe_webhooks'] = get_stripe_webhooks();
			update_option('rcp_settings', $all_rcp_options);
		}
	}
}
