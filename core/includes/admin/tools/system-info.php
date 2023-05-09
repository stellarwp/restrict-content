<?php
/**
 * System Info
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/System Info
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use RCP\Membership_Level;

/**
 * Displays the system info report
 *
 * @since 2.5
 * @return string $return The compiled system info report.
 */
function rcp_tools_system_info_report() {

	global $rcp_options, $wpdb;
	$restrict_content_obj = restrict_content_pro();

	// Get theme info
	$theme_data = wp_get_theme();
	$theme      = $theme_data->Name . ' ' . $theme_data->Version;

	$return  = '### Begin System Info ###' . "\n\n";

	// Start with the basics...
	$return .= '-- Site Info' . "\n\n";
	$return .= 'Site URL:                 ' . site_url() . "\n";
	$return .= 'Home URL:                 ' . home_url() . "\n";
	$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

	$return = apply_filters( 'rcp_system_info_after_site_info', $return );

	// WordPress configuration
	$return .= "\n" . '-- WordPress Configuration' . "\n\n";
	$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
	$return .= 'Language:                 ' . get_locale() . "\n";
	$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$return .= 'Active Theme:             ' . $theme . "\n";
	$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if frontpage is set to 'page'
	if( get_option( 'show_on_front' ) === 'page' ) {
		$front_page_id = get_option( 'page_on_front' );
		$blog_page_id = get_option( 'page_for_posts' );

		$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
		$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
	}

	$return .= 'ABSPATH:                  ' . ABSPATH . "\n";
	$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
	$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
	$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
	$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";
	$return .= 'Date Format:              ' . sprintf( '%s (%s)', get_option( 'date_format' ), date( get_option( 'date_format' ), current_time( 'timestamp' ) ) ) . "\n";
	$return .= 'Time Format:              ' . sprintf( '%s (%s)', get_option( 'time_format' ), date( get_option( 'time_format' ), current_time( 'timestamp' ) ) ) . "\n";
	$return .= 'Timezone:                 ' . get_option( 'timezone_string' ) . "\n";
	$return .= 'Membership:               ' . ( get_option( 'users_can_register' ) ? 'Enabled' : 'Disabled' ) . "\n";
	$return .= 'Default Role:             ' . get_option( 'default_role' ) . "\n";

	$return = apply_filters( 'rcp_system_info_after_wordpress_config', $return );

	// RCP Config
	$auto_renew_options         = array( 1 => 'Always auto renew', 2 => 'Never auto renew', 3 => 'Let customer choose whether to auto renew' );

	$restrict_content_version = ! $restrict_content_obj->is_pro() ? RCF_VERSION : RCP_PLUGIN_VERSION;

	$return .= "\n" . '-- RCP Configuration' . "\n\n";
	$return .= 'Version:                          ' . $restrict_content_version . "\n";
	$return .= 'Upgraded on:                      ' . sprintf( '%s from %s', get_option( 'rcp_version_upgraded_on', '(unknown date)' ), get_option( 'rcp_version_upgraded_from', 0 ) ) . "\n";
	$return .= 'License Key:                      ' . ( ! empty( $rcp_options['license_key'] ) ? $rcp_options['license_key'] . "\n" : "Not set\n" );
	$return .= 'Multiple Memberships:             ' . ( rcp_multiple_memberships_enabled() ? "Enabled\n" : "Disabled\n" );
	$return .= 'Auto Renew:                       ' . ( ! empty( $rcp_options['auto_renew'] ) && array_key_exists( $rcp_options['auto_renew'], $auto_renew_options ) ? $auto_renew_options[$rcp_options['auto_renew']] . "\n" : "Invalid Configuration\n" );
	$return .= 'Currency:                         ' . ( ! empty( $rcp_options['currency'] ) ? $rcp_options['currency'] . "\n" : "Invalid Configuration\n" );
	$return .= 'Currency Position:                ' . ( ! empty( $rcp_options['currency_position'] ) ? $rcp_options['currency_position'] . "\n" : "Invalid Configuration\n" );
	$return .= 'Sandbox Mode:                     ' . ( rcp_is_sandbox() ? "True" . "\n" : "False\n" );

	$return = apply_filters( 'rcp_system_info_after_rcp_config', $return );

	// RCP pages
	$return .= "\n" . '-- RCP Page Configuration' . "\n\n";
	$return .= 'Registration Page:                ' . ( ! empty( $rcp_options['registration_page'] ) ? get_permalink( $rcp_options['registration_page'] ) . "\n" : "Unset\n" );
	$return .= 'Success Page:                     ' . ( ! empty( $rcp_options['redirect'] ) ? get_permalink( $rcp_options['redirect'] ) . "\n" : "Unset\n" );
	$return .= 'Account Page:                     ' . ( ! empty( $rcp_options['account_page'] ) ? get_permalink( $rcp_options['account_page'] ) . "\n" : "Unset\n" );
	$return .= 'Edit Profile Page:                ' . ( ! empty( $rcp_options['edit_profile'] ) ? get_permalink( $rcp_options['edit_profile'] ) . "\n" : "Unset\n" );
	$return .= 'Update Billing Card Page:         ' . ( ! empty( $rcp_options['update_card'] ) ? get_permalink( $rcp_options['update_card'] ) . "\n" : "Unset\n" );

	$return = apply_filters( 'rcp_system_info_after_rcp_pages', $return );

	// RCP gateways
	$return .= "\n" . '-- RCP Enabled Gateways' . "\n\n";

	$gateways = new RCP_Payment_Gateways;
	if( $gateways->enabled_gateways ) {
		foreach( $gateways->enabled_gateways as $key => $gateway ) {
			if ( ! is_array( $gateway ) ) {
				$return .= $key . "\n";
				continue;
			}

			$test_api = 'Not Set';
			$live_api = 'Not Set';
			$additional_gateway_info = '';

			switch ( $key ) {

				case 'manual' :
					$test_api = $live_api = 'n/a';
					break;

				case 'paypal' :
				case 'paypal_express' :
				case 'paypal_pro' :
					if ( ! empty( $rcp_options['test_paypal_api_username'] ) && ! empty( $rcp_options['test_paypal_api_password'] ) && ! empty( $rcp_options['test_paypal_api_signature'] ) ) {
						$test_api = 'Set';
					}
					if ( ! empty( $rcp_options['live_paypal_api_username'] ) && ! empty( $rcp_options['live_paypal_api_password'] ) && ! empty( $rcp_options['live_paypal_api_signature'] ) ) {
						$live_api = 'Set';
					}
					break;

				case 'stripe' :
				case 'stripe_checkout' :
					if ( ! empty( $rcp_options['stripe_test_secret'] ) && ! empty( $rcp_options['stripe_test_publishable'] ) ) {
						$test_api = 'Set';
					}
					if ( ! empty( $rcp_options['stripe_live_secret'] ) && ! empty( $rcp_options['stripe_live_publishable'] ) ) {
						$live_api = 'Set';
					}
					$additional_gateway_info .= '   Statement Descriptor:           ' . ( ! empty( $rcp_options['statement_descriptor'] ) ? $rcp_options['statement_descriptor'] . "\n" : "\n" );
					$additional_gateway_info .= '   Statement Suffix:               ' . ( ! empty( $rcp_options['statement_descriptor_suffix'] ) ? $rcp_options['statement_descriptor_suffix'] . "\n" : "\n" );
					break;

				case 'twocheckout' :
					if ( ! empty( $rcp_options['twocheckout_test_private'] ) && ! empty( $rcp_options['twocheckout_test_publishable'] ) && ! empty( $rcp_options['twocheckout_test_seller_id'] ) ) {
						$test_api = 'Set';
					}
					if ( ! empty( $rcp_options['twocheckout_live_private'] ) && ! empty( $rcp_options['twocheckout_live_publishable'] ) && ! empty( $rcp_options['twocheckout_live_seller_id'] ) ) {
						$live_api = 'Set';
					}
					break;

				case 'authorizenet' :
					if ( ! empty( $rcp_options['authorize_test_api_login'] ) && ! empty( $rcp_options['authorize_test_txn_key'] ) ) {
						$test_api = 'Set';
					}
					if ( ! empty( $rcp_options['authorize_api_login'] ) && ! empty( $rcp_options['authorize_txn_key'] ) ) {
						$live_api = 'Set';
					}
					break;

				case 'braintree' :
					if ( ! empty( $rcp_options['braintree_sandbox_merchantId'] ) && ! empty( $rcp_options['braintree_sandbox_publicKey'] ) && ! empty( $rcp_options['braintree_sandbox_privateKey'] ) && ! empty( $rcp_options['braintree_sandbox_encryptionKey'] ) ) {
						$test_api = 'Set';
					}
					if ( ! empty( $rcp_options['braintree_live_merchantId'] ) && ! empty( $rcp_options['braintree_live_publicKey'] ) && ! empty( $rcp_options['braintree_live_privateKey'] ) && ! empty( $rcp_options['braintree_live_encryptionKey'] ) ) {
						$live_api = 'Set';
					}
					break;

			}

			$return .= str_pad( $gateway['admin_label'] . ' (' . $key . '): ', 35 ) . sprintf( 'Test API Keys: %s; Live API Keys: %s', $test_api, $live_api ) . "\n";
			$return .= $additional_gateway_info;
		}
	} else {
		$return .= 'None' . "\n";
	}

	$return = apply_filters( 'rcp_system_info_after_rcp_gateways', $return );

	// RCP membership levels
	$return .= "\n" . '-- RCP Membership Levels' . "\n\n";
	$levels = rcp_get_membership_levels( array( 'number' => 999 ) );
	if ( ! empty( $levels ) ) {
		foreach ( $levels as $level ) {
			$return .= str_pad( $level->get_name() . ':', 40 ) . sprintf(
					'%s; ID: %d; Price: %s; Fee: %s; Duration: %s %s; Trial: %s %s; Access Level: %s; Role: %s; Maximum Renewals: %d (%s)',
					$level->get_status(),
					$level->get_id(),
					$level->get_price(),
					$level->get_fee(),
					$level->get_duration(),
					$level->get_duration_unit(),
					$level->get_trial_duration(),
					$level->get_trial_duration_unit(),
					$level->get_access_level(),
					$level->get_role(),
					$level->get_maximum_renewals(),
					$level->get_after_final_payment()
				) . "\n";
		}
	}

	$return = apply_filters( 'rcp_system_info_after_rcp_subscription_levels', $return );

	// RCP Misc Settings
	$auto_add_level = ! empty( $rcp_options['auto_add_users'] ) ? rcp_get_membership_level( $rcp_options['auto_add_users'] ) : false;
	$return .= "\n" . '-- RCP Misc Settings' . "\n\n";
	$return .= 'Hide Restricted Posts:            ' . ( ! empty( $rcp_options['hide_premium'] ) ? "True\n" : "False\n" );
	$return .= 'Redirect Page:                    ' . ( ! empty( $rcp_options['redirect_from_premium'] ) ? get_permalink( $rcp_options['redirect_from_premium'] ) . "\n" : "Unset\n" );
	$return .= 'Redirect Default Login URL        ' . ( ! empty( $rcp_options['hijack_login_url'] ) ? "True\n" : "False\n" );
	$return .= 'Login Page:                       ' . ( ! empty( $rcp_options['login_redirect'] ) ? get_permalink( $rcp_options['login_redirect'] ) . "\n" : "Unset\n" );
	$return .= 'Auto Add Users To Membership:     ' . ( $auto_add_level instanceof Membership_Level ? $auto_add_level->get_name() . ' (ID #' . $auto_add_level->get_id() . ")\n" : "None\n" );
	$return .= 'Content Excerpts:                 ' . ( ! empty( $rcp_options['content_excerpts'] ) ? ucwords( $rcp_options['content_excerpts'] ) : 'Individual' ) . "\n";
	$return .= 'Discount Signup Fees              ' . ( ! empty( $rcp_options['discount_fees'] ) ? "Yes\n" : "No\n" );
	$return .= 'Prevent Account Sharing:          ' . ( ! empty( $rcp_options['no_login_sharing'] ) ? "True\n" : "False\n" );
	$return .= 'Disable WordPress Toolbar         ' . ( ! empty( $rcp_options['disable_toolbar'] ) ? "True\n" : "False\n" );
	$return .= 'Disable Form CSS:                 ' . ( ! empty( $rcp_options['disable_css'] ) ? "True\n" : "False\n" );
	$return .= 'Enable reCaptcha:                 ' . ( ! empty( $rcp_options['enable_recaptcha'] ) ? "True\n" : "False\n" );
	$return .= 'reCaptcha Site Key:               ' . ( ! empty( $rcp_options['recaptcha_public_key'] ) ? "Set\n" : "Unset\n" );
	$return .= 'reCaptcha Secret Key:             ' . ( ! empty( $rcp_options['recaptcha_private_key'] ) ? "Set\n" : "Unset\n" );
	$return .= 'Enable Debug Mode:                ' . ( ! empty( $rcp_options['debug_mode'] ) ? "True\n" : "False\n" );
	$return .= 'Remove Data on Uninstall:         ' . ( ! empty( $rcp_options['remove_data_on_uninstall'] ) ? "True\n" : "False\n" );
	$return .= 'Switch Free Subscriptions:        ' . ( ! empty( $rcp_options['disable_trial_free_subs'] ) ? "True\n" : "False\n" );
	$return .= 'Opt Into Beta:                    ' . ( ! empty( $rcp_options['show_beta_updates'] ) ? "True\n" : "False\n" );
	$return .= 'Proration:                        ' . ( ! apply_filters( 'rcp_disable_prorate_credit', false, get_current_user_id() ) ? "Enabled\n" : "Disabled\n" );

	$return = apply_filters( 'rcp_system_info_after_rcp_misc_settings', $return );

	// RCP Email Settings
	$email_verification = isset( $rcp_options['email_verification'] ) ? ucwords( $rcp_options['email_verification'] ) : 'Off';
	$return .= "\n" . '-- RCP Email Settings' . "\n\n";
	$return .= 'RCP_DISABLE_EMAILS Constant:      ' . sprintf( 'Status: %s', ( defined( 'RCP_DISABLE_EMAILS' ) ) ? ( RCP_DISABLE_EMAILS ? 'True' : 'False' ) : 'Not Set' ) . "\n";
	$return .= 'Email Verification:               ' . sprintf( 'Status: %s; Subject: %s; Body: %s', $email_verification, ( ! empty( $rcp_options['verification_subject'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['verification_email'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Active Subscription (member):     ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_active_email'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['active_subject'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['active_email'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Active Subscription (admin):      ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_active_email_admin'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['active_subject_admin'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['active_email_admin'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Cancelled Subscription (member):  ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_cancelled_email'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['cancelled_subject'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['cancelled_email'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Cancelled Subscription (admin):   ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_cancelled_email_admin'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['cancelled_subject_admin'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['cancelled_email_admin'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Expired Subscription (member):    ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_expired_email'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['expired_subject'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['expired_email'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Expired Subscription (admin):     ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_expired_email_admin'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['expired_subject_admin'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['expired_email_admin'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Free Subscription (member):       ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_free_email'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['free_subject'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['free_email'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Free Subscription (admin):        ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_free_email_admin'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['free_subject_admin'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['free_email_admin'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Trial Subscription (member):      ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_trial_email'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['trial_subject'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['trial_email'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Trial Subscription (admin):       ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_trial_email_admin'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['trial_subject_admin'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['trial_email_admin'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Payment Received (member):        ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_payment_received_email'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['payment_received_subject'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['payment_received_email'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'Renewal Payment Failed (member):  ' . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $rcp_options['disable_renewal_payment_failed_email'] ) ? 'Disabled' : 'Enabled' ), ( ! empty( $rcp_options['renewal_payment_failed_subject'] ) ? 'Set' : 'Not Set' ), ( ! empty( $rcp_options['renewal_payment_failed_email'] ) ? 'Set' : 'Not Set' ) ) . "\n";
	$return .= 'New User Notifications:           ' . ( ! empty( $rcp_options['disable_new_user_notices'] ) ? 'Disabled' : 'Enabled' ) . "\n";

	$return = apply_filters( 'rcp_system_info_after_rcp_email_settings', $return );

	// RCP Email Reminder Settings
	$return    .= "\n" . '-- RCP Email Reminders' . "\n\n";
	$reminders = new RCP_Reminders();
	$notices   = $reminders->get_notices();
	if ( ! empty( $notices ) ) {
		foreach ( $notices as $notice ) {
			$return .= str_pad( ucwords( $notice['type'] ) . ' ' . $notice['send_period'], 26 ) . sprintf( 'Status: %s; Subject: %s; Body: %s', ( ! empty( $notice['enabled'] ) ? 'Enabled' : 'Disabled' ), ( ! empty( $notice['subject'] ) ? 'Set' : 'Not Set' ), ( ! empty( $notice['message'] ) ? 'Set' : 'Not Set' ) ) . "\n";
		}
	} else {
		$return .= "None\n";
	}

	$return = apply_filters( 'rcp_system_info_after_rcp_reminder_settings', $return );

	// Database
	$return .= "\n" . '-- Database' . "\n\n";
	foreach ( restrict_content_pro()->components as $component_key => $component ) {
		$table = $component->get_interface( 'table' );
		if ( $table instanceof RCP\Database\Table ) {
			$needs_upgrade = $table->needs_upgrade() ? ' - NEEDS UPGRADE' : '';

			$return .= str_pad( sprintf( '%s version:', $component_key ), 40 ) . $table->get_version() . $needs_upgrade . "\n";
		}

		$integrity = 'unknown';
		$schema    = $component->get_interface( 'schema' );
		if ( $schema instanceof RCP\Database\Schema && $table instanceof RCP\Database\Table ) {
			$integrity = 'VALID';
			$missing = array();

			foreach ( $schema->columns as $column ) {
				if ( ! $column instanceof RCP\Database\Column ) {
					continue;
				}

				if ( ! $table->column_exists( $column->name ) ) {
					$missing[] = $column->name;
				}
			}

			if ( ! empty( $missing ) ) {
				$integrity = 'MISSING COLUMNS: ' . implode( ', ', $missing );
			}
		}

		$return .= str_pad( sprintf( '%s schema:', $component_key ), 40 ) . $integrity . "\n";
	}

	// RCP Templates
	$files       = array();
	$directories = array( get_stylesheet_directory() . '/rcp/' );

	if ( is_child_theme() ) {
		$directories[] = get_template_directory() . '/rcp/';
	}

	foreach ( $directories as $dir ) {
		if ( is_dir( $dir ) && ( count( glob( "$dir/*" ) ) !== 0 ) ) {
			foreach ( glob( $dir . '/*' ) as $file ) {
				if ( ! array_key_exists( basename( $file ), $files ) ) {
					$files[ basename( $file ) ] = 'Filename:                 ' . basename( $file );
				}
			}
		}
	}

	if ( ! empty( $files ) ) {
		$return .= "\n" . '-- RCP Template Overrides' . "\n\n";
		$return .= implode( "\n", $files ) . "\n";
		$return = apply_filters( 'rcp_system_info_after_rcp_templates', $return );
	}

	// Get plugins that have an update
	$updates = get_plugin_updates();

	// Must-use plugins
	// NOTE: MU plugins can't show updates!
	$muplugins = get_mu_plugins();
	if( count( $muplugins ) > 0 ) {
		$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

		foreach( $muplugins as $plugin => $plugin_data ) {
			$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
		}

		$return = apply_filters( 'rcp_system_info_after_wordpress_mu_plugins', $return );
	}

	// WordPress active plugins
	$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

	$plugins = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach( $plugins as $plugin_path => $plugin ) {
		if( !in_array( $plugin_path, $active_plugins ) )
			continue;

		$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	$return = apply_filters( 'rcp_system_info_after_wordpress_active_plugins', $return );

	// WordPress inactive plugins
	$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach( $plugins as $plugin_path => $plugin ) {
		if( in_array( $plugin_path, $active_plugins ) )
			continue;

		$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	$return = apply_filters( 'rcp_system_info_after_wordpress_inactive_plugins', $return );

	if( is_multisite() ) {
		// WordPress Multisite active plugins
		$return .= "\n" . '-- Network Active Plugins' . "\n\n";

		$plugins = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if( !array_key_exists( $plugin_base, $active_plugins ) )
				continue;

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
			$plugin  = get_plugin_data( $plugin_path );
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		$return = apply_filters( 'rcp_system_info_after_wordpress_network_active_plugins', $return );
	}

	// Server configuration (really just versioning)
	$return .= "\n" . '-- Webserver Configuration' . "\n\n";
	$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
	$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

	$return = apply_filters( 'rcp_system_info_after_server_config', $return );

	// PHP configuration
	$return .= "\n" . '-- PHP Configuration' . "\n\n";
	$return .= 'Safe Mode:                ' . ( ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled' . "\n" );
	$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
	$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
	$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
	$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
	$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

	$return = apply_filters( 'rcp_system_info_after_php_config', $return );

	// PHP extensions and such
	$return .= "\n" . '-- PHP Extensions' . "\n\n";
	$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

	$return = apply_filters( 'rcp_system_info_after_php_extensions', $return );

	$return .= "\n" . '-- Miscellaneous' . "\n\n";
	$return .= 'System Info Generated:    ' . current_time( 'mysql', true ) . ' (GMT)';

	$return .= "\n\n" . '### End System Info ###';

	return $return;
}
