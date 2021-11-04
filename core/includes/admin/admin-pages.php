<?php
/**
 * Admin Pages
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Pages
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Create admin menus and sub-menus
 *
 * @return void
 */
function rcp_settings_menu() {
	global $rcp_members_page, $rcp_customers_page, $rcp_subscriptions_page, $rcp_payments_page,
	$rcp_settings_page, $rcp_export_page, $rcp_help_page, $rcp_tools_page,
   	$rcp_add_ons_page, $rcp_reminders_page;

	// add settings page
	add_menu_page( __( 'Restrict Content Pro Settings', 'rcp' ), __( 'Restrict', 'rcp' ), 'rcp_view_members', 'rcp-members', 'rcp_members_page', 'dashicons-lock' );
	$rcp_members_page       = add_submenu_page( 'rcp-members', __( 'Memberships', 'rcp' ), __( 'Memberships', 'rcp' ), 'rcp_view_members', 'rcp-members', 'rcp_members_page', 1 );
	$rcp_customers_page     = add_submenu_page( 'rcp-members', __( 'Customers', 'rcp' ), __( 'Customers', 'rcp' ), 'rcp_view_members', 'rcp-customers', 'rcp_customers_page', 2 );
	$rcp_subscriptions_page = add_submenu_page( 'rcp-members', __( 'Membership Levels', 'rcp' ), __( 'Membership Levels', 'rcp' ), 'rcp_view_levels', 'rcp-member-levels', 'rcp_member_levels_page', 3 );
	$rcp_payments_page      = add_submenu_page( 'rcp-members', __( 'Payments', 'rcp' ), __( 'Payments', 'rcp' ), 'rcp_view_payments', 'rcp-payments', 'rcp_payments_page', 5 );
	$rcp_settings_page      = add_submenu_page( 'rcp-members', __( 'Restrict Content Pro Settings', 'rcp' ), __( 'Settings', 'rcp' ),'rcp_manage_settings', 'rcp-settings', 'rcp_settings_page', 7 );
	$rcp_tools_page         = add_submenu_page( 'rcp-members', __( 'Tools', 'rcp' ), __( 'Tools', 'rcp' ), 'rcp_manage_settings', 'rcp-tools', 'rcp_tools_page',  8 );
	// Removing Legacy Help Page from Restrict Content Pro and Restrict Content
//	$rcp_help_page          = add_submenu_page( 'rcp-members', __( 'Help', 'rcp' ), __( 'Help', 'rcp' ), 'rcp_view_help', 'rcp-help', '__return_null', 9 );
//	$rcp_add_ons_page       = add_submenu_page( 'rcp-members', __( 'Add-ons', 'rcp' ), __( 'Add-ons', 'rcp' ), 'rcp_view_members', 'rcp-add-ons', 'rcp_add_ons_admin', 10 );
	$rcp_reminders_page     = add_submenu_page( 'rcp-members', __( 'Subscription Reminder', 'rcp' ), __( 'Subscription Reminder', 'rcp' ), 'rcp_manage_settings', 'rcp-reminder', 'rcp_subscription_reminder_page', 11 );

	// Backwards compatibility - link the old export page to the tools page.
	$rcp_export_page = $rcp_tools_page;

	// Remove the reminders page from the menu.
	add_action( 'admin_head', 'rcp_hide_reminder_page' );

	// Add "Restrict" submenu under each post type.
	foreach ( rcp_get_metabox_post_types() as $post_type ) {
		$post_type_details = get_post_type_object( $post_type );
		$url               = ( 'post' == $post_type ) ? 'edit.php' : 'edit.php?post_type=' . $post_type;
		$slug              = ( 'post' == $post_type ) ? 'rcp-restrict-post-type' : 'rcp-restrict-post-type-' . $post_type;
		$capability        = isset( $post_type_details->cap->edit_posts ) ? $post_type_details->cap->edit_posts : 'edit_posts';
		add_submenu_page( $url, __( 'Restrict Access', 'rcp' ), __( 'Restrict Access', 'rcp' ), $capability, $slug, 'rcp_restrict_post_type_page' );
	}

	if ( get_bloginfo('version') >= 3.3 ) {
		// load each of the help tabs
		add_action( "load-$rcp_members_page", "rcp_help_tabs" );
		add_action( "load-$rcp_customers_page", "rcp_help_tabs" );
		add_action( "load-$rcp_subscriptions_page", "rcp_help_tabs" );
		add_action( "load-$rcp_settings_page", "rcp_help_tabs" );
	}
	add_action( "load-$rcp_members_page", "rcp_screen_options" );
	add_action( "load-$rcp_customers_page", "rcp_screen_options" );
	add_action( "load-$rcp_subscriptions_page", "rcp_screen_options" );
	add_action( "load-$rcp_payments_page", "rcp_screen_options" );
	add_action( "load-$rcp_settings_page", "rcp_screen_options" );
	add_action( "load-$rcp_tools_page", "rcp_screen_options" );
}
add_action( 'admin_menu', 'rcp_settings_menu', 10, 2 );

/**
 * Determines whether or not the current page is an RCP admin page.
 *
 * @since 3.3.7
 * @return bool
 */
function rcp_is_rcp_admin_page() {

	$screen = get_current_screen();

	global $rcp_members_page, $rcp_customers_page, $rcp_subscriptions_page, $rcp_discounts_page, $rcp_payments_page, $rcp_reports_page, $rcp_settings_page, $rcp_help_page, $rcp_tools_page;
	$pages = array( $rcp_members_page, $rcp_customers_page, $rcp_subscriptions_page, $rcp_discounts_page, $rcp_payments_page, $rcp_reports_page, $rcp_settings_page, $rcp_tools_page, $rcp_help_page );

	// Include post types that support restrictions.
	if ( 'post' === $screen->base && ! empty( $screen->post_type ) && in_array( $screen->post_type, rcp_get_metabox_post_types() ) ) {
		$pages[] = $screen->id;
	}

	if( false !== strpos( $screen->id, 'rcp-restrict-post-type' ) ) {
		$pages[] = $screen->id;
	}

	$is_admin = in_array( $screen->id, $pages );

	/**
	 * Filters whether or not the current page is an RCP admin page.
	 *
	 * @param bool      $is_admin
	 * @param WP_Screen $screen
	 *
	 * @since 3.3.7
	 */
	return apply_filters( 'rcp_is_rcp_admin_page', $is_admin, $screen );

}

/**
 * Returns the URL to the memberships page.
 *
 * @param array $args Query args to add.
 *
 * @since 3.0
 * @return string
 */
function rcp_get_memberships_admin_page( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'page' => 'rcp-members'
	) );

	$sanitized_args = array();

	foreach ($args as $key => $value) {
		$sanitized_key   = urlencode( $key );
		$sanitized_value = urlencode( $value );

		$sanitized_args[ $sanitized_key ] = $sanitized_value;
	}

	$memberships_page = add_query_arg( $sanitized_args, admin_url(  'admin.php'  ) );

	return $memberships_page;

}

/**
 * Returns the URL to the customers page.
 *
 * @param array $args Query args to add.
 *
 * @since 3.0
 * @return string
 */
function rcp_get_customers_admin_page( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'page' => 'rcp-customers'
	) );

	$customers_page = add_query_arg( $args, admin_url(  'admin.php'  ) );

	return $customers_page;

}
