<?php
/**
 * Scripts
 *
 * @package     Restrict Content Pro
 * @subpackage  Scripts
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Load admin scripts
 *
 * @param string $hook Page hook.
 *
 * @return void
 */
function rcp_admin_scripts( $hook ) {

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	global $rcp_options, $rcp_members_page, $rcp_customers_page, $rcp_discounts_page, $rcp_reports_page, $rcp_tools_page;

	$is_rcp_page = rcp_is_rcp_admin_page();

	if ( $rcp_customers_page == $hook ) {
		// Load the password show/hide feature and strength meter.
		wp_enqueue_script( 'user-profile' );
	}

	if ( $rcp_discounts_page == $hook ) {
		wp_enqueue_script( 'jquery-ui-timepicker', RCP_PLUGIN_URL . 'core/includes/libraries/js/jquery-ui-timepicker-addon' . $suffix . '.js', array( 'jquery-ui-datepicker', 'jquery-ui-slider' ), '1.6.3' );
	}

	if ( $is_rcp_page ) {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-tooltip' );
		wp_enqueue_script( 'rcp-admin-scripts', RCP_PLUGIN_URL . 'core/includes/js/admin-scripts' . $suffix . '.js', array( 'jquery' ), RCP_PLUGIN_VERSION, true );
	}

	if ( $rcp_reports_page == $hook ) {
		wp_enqueue_script( 'jquery-flot', RCP_PLUGIN_URL . 'core/includes/libraries/js/jquery.flot.min.js', array(), '0.7', true );

		// For now only load Chart.js and new scripts file on Membership Counts tab.
		if ( ! empty( $_GET['tab'] ) && 'membership_counts' === $_GET['tab'] ) {
			wp_enqueue_script( 'chart-js', RCP_PLUGIN_URL . 'core/includes/libraries/js/chart.min.js', array(), '2.8.0', true );
			wp_enqueue_script( 'rcp-admin-reports', RCP_PLUGIN_URL . 'core/includes/js/admin-reports' . $suffix . '.js', array( 'jquery', 'chart-js' ), RCP_PLUGIN_VERSION, true );
		}
	}

	if ( $is_rcp_page ) {
		$membership = ! empty( $_GET['membership_id'] ) ? rcp_get_membership( absint( $_GET['membership_id'] ) ) : false;

		$change_level_message = __( 'Are you sure you want to change the membership level?', 'rcp' );
		if ( ! empty( $membership ) && rcp_gateway_supports( $membership->get_gateway(), 'subscription-creation' ) ) {
			$change_level_message = __( 'Are you sure you want to change the membership level? The subscription price at the payment gateway will be updated to match the price and duration of the new membership level.', 'rcp' );
		} elseif ( ! empty( $membership ) && $membership->is_recurring() ) {
			$change_level_message = __( 'Are you sure you want to change the membership level? The subscription will be cancelled at the payment gateway and this customer will not be automatically billed again.', 'rcp' );
		}

		wp_localize_script(
			'rcp-admin-scripts',
			'rcp_vars',
			array(
				'action_cancel'           => __( 'Cancel', 'rcp' ),
				'action_edit'             => __( 'Edit', 'rcp' ),
				'rcp_member_nonce'        => wp_create_nonce( 'rcp_member_nonce' ),
				'cancel_user'             => __( 'Are you sure you wish to cancel this member\'s subscription?', 'rcp' ),
				'delete_customer'         => __( 'Are you sure you want to delete this customer? This action is irreversible. All their memberships will be cancelled. Proceed?', 'rcp' ),
				'delete_membership'       => __( 'Are you sure you want to delete this membership? This action is irreversible. Proceed?', 'rcp' ),
				'delete_subscription'     => __( 'If you delete this subscription, all members registered with this level will be canceled. Proceed?', 'rcp' ),
				'delete_payment'          => __( 'Are you sure you want to delete this payment? This action is irreversible. Proceed?', 'rcp' ),
				'delete_discount'         => __( 'Are you sure you want to delete this discount? This action is irreversible. Proceed?', 'rcp' ),
				'delete_reminder'         => __( 'Are you sure you want to delete this reminder email? This action is irreversible. Proceed?', 'rcp' ),
				'expire_membership'       => __( 'Are you sure you want to expire this membership? The customer will lose access immediately.', 'rcp' ),
				'change_membership_level' => $change_level_message,
				'missing_username'        => __( 'You must choose a username', 'rcp' ),
				'currency_sign'           => rcp_currency_filter( '' ),
				'currency_pos'            => isset( $rcp_options['currency_position'] ) ? $rcp_options['currency_position'] : 'before',
				'use_as_logo'             => __( 'Use as Logo', 'rcp' ),
				'choose_logo'             => __( 'Choose a Logo', 'rcp' ),
				'can_cancel_member'       => ( $hook == $rcp_members_page && isset( $_GET['edit_member'] ) && rcp_can_member_cancel( absint( $_GET['edit_member'] ) ) ),
				'cancel_subscription'     => __( 'Cancel subscription at gateway', 'rcp' ),
				'currencies'              => json_encode( rcp_get_currencies() ),
				'downgrade_redirect'      => admin_url() . '?page=restrict-content-settings',
			)
		);
	}

	if ( $rcp_tools_page === $hook ) {
		wp_enqueue_script( 'rcp-batch', RCP_PLUGIN_URL . 'core/includes/batch/batch.js', array( 'jquery' ), RCP_PLUGIN_VERSION );
		wp_localize_script(
			'rcp-batch',
			'rcp_batch_vars',
			array(
				'batch_nonce' => wp_create_nonce( 'rcp_batch_nonce' ),
				'i18n'        => array(
					'job_fail'  => __( 'Job failed to complete successfully.', 'rcp' ),
					'job_retry' => __( 'Try again.', 'rcp' ),
				),
			)
		);
		wp_enqueue_script( 'rcp-csv-import', RCP_PLUGIN_URL . 'core/includes/js/admin-csv-import' . $suffix . '.js', array( 'jquery', 'jquery-form', 'rcp-batch' ), RCP_PLUGIN_VERSION );
		wp_localize_script(
			'rcp-csv-import',
			'rcp_csv_import_vars',
			array(
				'unsupported_browser' => __( 'Unfortunately your browser is not compatible with this kind of file upload. Please upgrade your browser.', 'rcp' ),
			)
		);
	}

	// RCP Admin Notices Script Inclusion and Localization - Notice Dismissal
	if ( ! get_option( 'dismissed-rcp-plugin-migration-notice', false ) ) {
		wp_enqueue_script( 'restrict-content-pro-admin-notices', RCP_PLUGIN_URL . 'core/includes/js/restrict-content-pro-admin-notices' . $suffix . '.js', array( 'jquery' ), RCP_PLUGIN_VERSION );
		wp_localize_script(
			'restrict-content-pro-admin-notices',
			'rcp_admin_notices_vars',
			array(
				'rcp_dismissed_nonce' => wp_create_nonce( 'rcp_dismissed_nonce' ),
			)
		);
	}

	// RCP Admin Notices Script Inclusion and Localization - Notice Dismissal
	if ( ! get_option( 'dismissed-restrict-content-upgrade-notice', false ) ) {
		wp_enqueue_script( 'restrict-content-pro-admin-notices', RCP_PLUGIN_URL . 'core/includes/js/restrict-content-pro-admin-notices.js', array( 'jquery' ), RCP_PLUGIN_VERSION );
		wp_localize_script(
			'restrict-content-pro-admin-notices',
			'rcp_admin_notices_vars',
			array(
				'rcp_dismissed_nonce' => wp_create_nonce( 'rcp_dismissed_nonce' ),
			)
		);
	}

	// RCP Black Friday Notice Script Inclusion and Localization - Notice Dismissal
	if ( ! get_option( 'dismissed-restrict-content-bfcm-notice', false ) ) {
		wp_enqueue_script( 'restrict-content-pro-admin-notices', RCP_PLUGIN_URL . 'core/includes/js/restrict-content-pro-admin-notices.js', array( 'jquery' ), RCP_PLUGIN_VERSION );
		wp_localize_script(
			'restrict-content-pro-admin-notices',
			'rcp_admin_notices_vars',
			array(
				'rcp_dismissed_nonce' => wp_create_nonce( 'rcp_dismissed_nonce' ),
			)
		);
	}

}
add_action( 'admin_enqueue_scripts', 'rcp_admin_scripts' );

/**
 * Sets the URL of the Restrict > Help page
 *
 * @access      public
 * @since       2.5
 * @return      void
 */
function rcp_admin_help_url() {
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#adminmenu .toplevel_page_rcp-members .wp-submenu-wrap a[href="admin.php?page=rcp-help"]').prop('href', 'http://restrictcontentpro.com/knowledgebase/').prop('target', '_blank');
	});
	</script>
	<?php
}
add_action( 'admin_head', 'rcp_admin_help_url' );

/**
 * Load admin stylesheets
 *
 * @param string $hook Page hook.
 *
 * @return void
 */
function rcp_admin_styles( $hook ) {
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	if ( rcp_is_rcp_admin_page() ) {
		wp_enqueue_style( 'datepicker', RCP_PLUGIN_URL . 'core/includes/libraries/css/datepicker' . $suffix . '.css', array(), '1.4.2' );
		wp_enqueue_style( 'rcp-admin', RCP_PLUGIN_URL . 'core/includes/css/admin-styles' . $suffix . '.css', array(), RCP_PLUGIN_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'rcp_admin_styles' );


/**
 * Register form CSS
 *
 * @return void
 */
function rcp_register_css() {
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	wp_register_style( 'rcp-form-css', RCP_PLUGIN_URL . 'core/includes/css/forms' . $suffix . '.css', array(), RCP_PLUGIN_VERSION );
}
add_action( 'init', 'rcp_register_css' );

/**
 * Register front-end scripts
 *
 * @return void
 */
function rcp_register_scripts() {

	global $rcp_options;

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	wp_register_script( 'es6-promise', RCP_PLUGIN_URL . 'core/includes/libraries/js/es6-promise.auto.min.js', array(), '3.5.0' );
	wp_register_script( 'rcp-register', RCP_PLUGIN_URL . 'core/includes/js/register' . $suffix . '.js', array( 'jquery' ), RCP_PLUGIN_VERSION );
	wp_register_script( 'jquery-blockui', RCP_PLUGIN_URL . 'core/includes/libraries/js/jquery.blockUI.js', array( 'jquery' ), RCP_PLUGIN_VERSION );
	wp_register_script( 'rcp-account', RCP_PLUGIN_URL . 'core/includes/js/account' . $suffix . '.js', array( 'jquery' ), RCP_PLUGIN_VERSION, true );
	wp_register_script( 'rcp-general', RCP_PLUGIN_URL . 'core/includes/js/general' . $suffix . '.js', array(), RCP_PLUGIN_VERSION, true );

	wp_localize_script(
		'rcp-account',
		'rcpAccountVars',
		array(
			// translators: %s: The expiration date.
			'confirmEnableAutoRenew'  => __( 'Are you sure you want to enable auto renew? You will automatically be charged on %s.', 'rcp' ),
			'confirmDisableAutoRenew' => __( 'Are you sure you want to disable auto renew?', 'rcp' ),
		)
	);

	if ( 3 === rcp_get_recaptcha_version() ) {
		wp_register_script( 'recaptcha-v3', add_query_arg( 'render', urlencode( $rcp_options['recaptcha_public_key'] ), 'https://www.google.com/recaptcha/api.js' ), array(), RCP_PLUGIN_VERSION );
	} else {
		wp_register_script( 'recaptcha-v2', 'https://www.google.com/recaptcha/api.js', array(), RCP_PLUGIN_VERSION );
	}

}
add_action( 'init', 'rcp_register_scripts' );

/**
 * Load form CSS
 *
 * @return void
 */
function rcp_print_css() {
	global $rcp_load_css, $rcp_options;

	// this variable is set to TRUE if the short code is used on a page/post
	if ( ! $rcp_load_css || ( isset( $rcp_options['disable_css'] ) && $rcp_options['disable_css'] ) ) {
		return; // this means that neither short code is present, so we get out of here
	}

	wp_print_styles( 'rcp-form-css' );
}
add_action( 'wp_footer', 'rcp_print_css' );

/**
 * Loads scripts for the account page
 *
 * @since 3.4
 */
function rcp_load_account_scripts() {
	global $post, $rcp_options;

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$load_scripts = false;

	if ( ! empty( $rcp_options['account_page'] ) && $rcp_options['account_page'] == $post->ID ) {
		$load_scripts = true;
	}

	// Check for the presence of the shortcode.
	if ( ! $load_scripts && has_shortcode( $post->post_content, 'subscription_details' ) ) {
		$load_scripts = true;
	}

	if ( $load_scripts ) {
		wp_enqueue_script( 'rcp-account' );
	}
}
add_action( 'wp_enqueue_scripts', 'rcp_load_account_scripts' );

/**
 * Load form scripts
 *
 * @return void
 */
function rcp_print_scripts() {
	global $rcp_load_scripts, $rcp_options;

	// this variable is set to TRUE if the short code is used on a page/post
	if ( ! $rcp_load_scripts ) {
		return; // this means that neither short code is present, so we get out of here
	}

	wp_localize_script(
		'rcp-register',
		'rcp_script_options',
		array(
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'register'           => apply_filters( 'rcp_registration_register_button', __( 'Register', 'rcp' ) ),
			'pleasewait'         => __( 'Please Wait . . . ', 'rcp' ),
			'pay_now'            => __( 'Submit Payment', 'rcp' ),
			'user_has_trialed'   => is_user_logged_in() && rcp_has_used_trial(),
			'trial_levels'       => rcp_get_trial_level_ids(),
			'auto_renew_default' => isset( $rcp_options['auto_renew_checked_on'] ),
			'recaptcha_enabled'  => rcp_is_recaptcha_enabled(),
			'recaptcha_version'  => rcp_get_recaptcha_version(),
			'error_occurred'     => esc_html__( 'An unexpected error has occurred. Please try again or contact support if the issue persists.', 'rcp' ),
			'enter_card_details' => esc_html__( 'Please enter your card details.', 'rcp' ),
			'invalid_cardholder' => esc_html__( 'The card holder name you have entered is invalid', 'rcp' ),
		)
	);

	wp_print_scripts( 'es6-promise' );
	wp_print_scripts( 'rcp-register' );
	wp_print_scripts( 'jquery-blockui' );
	wp_print_scripts( 'rcp-general' );
	if ( rcp_is_recaptcha_enabled() ) {
		wp_print_scripts( 'recaptcha-v' . rcp_get_recaptcha_version() );
	}

}
add_action( 'wp_footer', 'rcp_print_scripts' );

/**
 * Dismisses notices that were created by RCP.
 *
 * @return void
 */
function rcp_ajax_dismissed_notice_handler() {
	// Verify nonce.
	if ( empty( $_POST['rcp_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_nonce'], 'rcp_dismissed_nonce' ) ) {
		return;
	}

	// Name not in $_POST? Bail.
	if ( ! array_key_exists( 'name', $_POST ) ) {
		return;
	}

	// Check user permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$name = sanitize_text_field( $_POST['name'] );

	if ( $name === 'rcp-plugin-migration-notice' ) {
		update_option( 'dismissed-' . $name, true );
	} elseif ( $name === 'restrict-content-upgrade-notice' ) {
		update_option( 'dismissed-' . $name, true );
	} elseif ( $name === 'restrict-content-bfcm-notice' ) {
		update_option( 'dismissed-' . $name, true );
	}
}

add_action( 'wp_ajax_rcp_ajax_dismissed_notice_handler', 'rcp_ajax_dismissed_notice_handler' );

