<?php
/**
 * Help Menus Setup
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Help Menus Setup
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Setup help tabs
 */
function rcp_help_tabs() {
	global $rcp_members_page;
	global $rcp_customers_page;
	global $rcp_subscriptions_page;
	global $rcp_discounts_page;
	global $rcp_payments_page;
	global $rcp_settings_page;

	$screen = get_current_screen();

	if(!is_object($screen))
		return;

	switch($screen->id) :

		case $rcp_members_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_members_tab_content( 'general' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'adding_subs',
					'title' => __( 'Adding Memberships', 'rcp' ),
					'content' => rcp_render_members_tab_content( 'adding_subs' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'member_details',
					'title' => __( 'Membership Details', 'rcp' ),
					'content' => rcp_render_members_tab_content( 'member_details' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'editing_member',
					'title' => __( 'Editing Memberships', 'rcp' ),
					'content' => rcp_render_members_tab_content( 'editing_member' )
				)
			);
		break;

		case $rcp_customers_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_customers_tab_content( 'general' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'customer_details',
					'title' => __( 'Customer Details', 'rcp' ),
					'content' => rcp_render_customers_tab_content( 'customer_details' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'editing_customers',
					'title' => __( 'Editing Customers', 'rcp' ),
					'content' => rcp_render_customers_tab_content( 'editing_customers' )
				)
			);
		break;

		case $rcp_subscriptions_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_subscriptions_tab_content( 'general' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'adding_subscriptions',
					'title' => __( 'Adding Levels', 'rcp' ),
					'content' => rcp_render_subscriptions_tab_content( 'adding_subscriptions' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'editing_subscriptions',
					'title' => __( 'Editing Levels', 'rcp' ),
					'content' => rcp_render_subscriptions_tab_content( 'editing_subscriptions' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'deleting_subscriptions',
					'title' => __( 'Deleting Levels', 'rcp' ),
					'content' => rcp_render_subscriptions_tab_content( 'deleting_subscriptions' )
				)
			);
		break;

		case $rcp_discounts_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_discounts_tab_content( 'general' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'adding_discounts',
					'title' => __( 'Adding Discounts', 'rcp' ),
					'content' => rcp_render_discounts_tab_content( 'adding_discounts' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'editing_discounts',
					'title' => __( 'Editing Discounts', 'rcp' ),
					'content' => rcp_render_discounts_tab_content( 'editing_discounts' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'using_discounts',
					'title' => __( 'Using Discounts', 'rcp' ),
					'content' => rcp_render_discounts_tab_content( 'using_discounts' )
				)
			);
		break;

		case $rcp_payments_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_payments_tab_content( 'general' )
				)
			);
		break;

		case $rcp_settings_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'general' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'pages',
					'title' => __( 'Pages', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'pages' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'messages',
					'title' => __( 'Messages', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'messages' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'payments',
					'title' => __( 'Payments', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'payments' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'emails',
					'title' => __( 'Emails', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'emails' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'invoices',
					'title' => __( 'Invoices', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'invoices' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'misc',
					'title' => __( 'Misc', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'misc' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'logging',
					'title' => __( 'Logging', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'logging' )
				)
			);
			break;

	default:
		break;

	endswitch;
}
add_action('admin_menu', 'rcp_help_tabs', 100);
