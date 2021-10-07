<?php
/**
 * Screen Options
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Screen Options
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Setup screen options
 *
 * @return void
 */
function rcp_screen_options() {

	global $rcp_members_page;
	global $rcp_customers_page;
	global $rcp_subscriptions_page;
	global $rcp_payments_page;

	$screen = get_current_screen();

	if(!is_object($screen))
		return;

	switch($screen->id) :

		case $rcp_members_page :
			$args = array(
				'label' => __('Memberships per page', 'rcp'),
				'default' => 10,
				'option' => 'rcp_members_per_page'
			);
			add_screen_option( 'per_page', $args );
			break;

		case $rcp_customers_page :
			$args = array(
				'label' => __( 'Customers per page', 'rcp' ),
				'default' => 10,
				'option' => 'rcp_customers_per_page'
			);
			add_screen_option( 'per_page', $args );
			break;

		case $rcp_payments_page :
			$args = array(
				'label' => __('Payments per page', 'rcp'),
				'default' => 10,
				'option' => 'rcp_payments_per_page'
			);
			add_screen_option( 'per_page', $args );
			break;

		case $rcp_subscriptions_page :
			$args = array(
				'label'   => __( 'Membership Levels per page', 'rcp' ),
				'default' => 10,
				'option'  => 'rcp_membership_levels_per_page'
			);
			add_screen_option( 'per_page', $args );
			break;

	endswitch;
}

/**
 * Filters option for number of rows when listing members/payments
 *
 * @param bool|int $status Screen option value. Default false to skip.
 * @param string   $option The option name.
 * @param int      $value  The number of rows to use.
 *
 * @return int|bool
 */
function rcp_set_screen_option($status, $option, $value) {
	if ( 'rcp_members_per_page' == $option ) {
		return $value;
	}
	if ( 'rcp_customers_per_page' == $option ) {
		return $value;
	}
	if ( 'rcp_payments_per_page' == $option ) {
		return $value;
	}
	if ( 'rcp_membership_levels_per_page' == $option ) {
		return $value;
	}

	return $status;
}
add_filter('set-screen-option', 'rcp_set_screen_option', 10, 3);
