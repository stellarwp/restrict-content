<?php
/**
 * Help Menus Setup (old)
 *
 * For WP version less than 3.3
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Help Menus Setup
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Setup help tabs
 *
 * @param $contextual_help
 * @param $screen_id
 * @param $screen
 *
 * @return string
 */
function rcp_help_tabs_old($contextual_help, $screen_id, $screen) {

	global $rcp_members_page;
	global $rcp_subscriptions_page;
	global $rcp_discounts_page;
	global $rcp_payments_page;
	global $rcp_settings_page;

	// replace edit with the base of the page you're adding the help info to

	switch($screen->base) :

		case $rcp_members_page:
			$contextual_help = '<h3>' . __('General', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_members_tab_content('general');

			$contextual_help .= '<h3>' . __('Adding Subscriptions', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_members_tab_content('adding_subs');

			$contextual_help .= '<h3>' . __('Member Details', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_members_tab_content('member_details');

			$contextual_help .= '<h3>' . __('Editing Members', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_members_tab_content('editing_member');

			return $contextual_help;
		break;

		case $rcp_subscriptions_page:
			$contextual_help = '<h3>' . __('General', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_subscriptions_tab_content('general');

			$contextual_help .= '<h3>' . __('Adding Subscriptions', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_subscriptions_tab_content('adding_subscriptions');

			$contextual_help .= '<h3>' . __('Editing Subscriptions', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_subscriptions_tab_content('editing_subscriptions');

			$contextual_help .= '<h3>' . __('Deleting Subscriptions', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_subscriptions_tab_content('deleting_subscriptions');

			return $contextual_help;
		break;

		case $rcp_discounts_page:
			$contextual_help = '<h3>' . __('General', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_discounts_tab_content('general');

			$contextual_help .= '<h3>' . __('Adding Discounts', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_discounts_tab_content('adding_discounts');

			$contextual_help .= '<h3>' . __('Editing Discounts', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_discounts_tab_content('editing_discounts');

			$contextual_help .= '<h3>' . __('Using Discounts', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_discounts_tab_content('using_discounts');

			return $contextual_help;
		break;

		case $rcp_payments_page:
			$contextual_help = '<h3>' . __('General', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_payments_tab_content('general');

			return $contextual_help;
		break;

		case $rcp_settings_page:
			$contextual_help = '<h3>' . __('General', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_settings_tab_content('general');

			$contextual_help .= '<h3>' . __('Messages', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_settings_tab_content('messages');

			$contextual_help .= '<h3>' . __('PayPal', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_settings_tab_content('paypal');

			$contextual_help .= '<h3>' . __('Signup Forms', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_settings_tab_content('signup_forms');

			$contextual_help .= '<h3>' . __('Emails', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_settings_tab_content('emails');

			$contextual_help .= '<h3>' . __('Misc', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_settings_tab_content('misc');

			$contextual_help .= '<h3>' . __('Logging', 'rcp') . '</h3>';
			$contextual_help .= rcp_render_settings_tab_content('logging');

			return $contextual_help;
		break;

		default:
			// show the default WP help tab content
			return $contextual_help;
		break;
	endswitch;

}
add_action('contextual_help', 'rcp_help_tabs_old', 100, 3);
