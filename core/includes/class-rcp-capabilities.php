<?php
/**
 * Roles and Capabilities
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
*/

/**
 * Affiliate_WP_Roles Class
 *
 * This class handles the role creation and assignment of capabilities for those roles.
 *
 * @since 2.0
 */
class RCP_Capabilities {

	/**
	 * Get things going
	 *
	 * @since 2.0
	 */
	public function __construct() { /* Do nothing here */ }

	/**
	 * Add new capabilities
	 *
	 * @access public
	 * @since  2.0
	 * @global obj $wp_roles
	 * @return void
	 */
	public function add_caps() {
		global $wp_roles;

		if ( class_exists('WP_Roles') )
			if ( ! isset( $wp_roles ) )
				$wp_roles = new WP_Roles();

		if ( is_object( $wp_roles ) ) {

			$wp_roles->add_cap( 'administrator', 'rcp_view_members' );
			$wp_roles->add_cap( 'administrator', 'rcp_manage_members' );
			$wp_roles->add_cap( 'administrator', 'rcp_view_levels' );
			$wp_roles->add_cap( 'administrator', 'rcp_manage_levels' );
			$wp_roles->add_cap( 'administrator', 'rcp_view_discounts' );
			$wp_roles->add_cap( 'administrator', 'rcp_manage_discounts' );
			$wp_roles->add_cap( 'administrator', 'rcp_view_payments' );
			$wp_roles->add_cap( 'administrator', 'rcp_manage_payments' );
			$wp_roles->add_cap( 'administrator', 'rcp_manage_settings' );
			$wp_roles->add_cap( 'administrator', 'rcp_export_data' );
			$wp_roles->add_cap( 'administrator', 'rcp_view_help' );
		}
	}


	/**
	 * Remove core post type capabilities (called on uninstall)
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function remove_caps() {
		if ( class_exists( 'WP_Roles' ) )
			if ( ! isset( $wp_roles ) )
				$wp_roles = new WP_Roles();

		if ( is_object( $wp_roles ) ) {

			/** Site Administrator Capabilities */
			$wp_roles->remove_cap( 'administrator', 'rcp_view_members' );
			$wp_roles->remove_cap( 'administrator', 'rcp_manage_members' );
			$wp_roles->remove_cap( 'administrator', 'rcp_view_levels' );
			$wp_roles->remove_cap( 'administrator', 'rcp_manage_levels' );
			$wp_roles->remove_cap( 'administrator', 'rcp_view_discounts' );
			$wp_roles->remove_cap( 'administrator', 'rcp_manage_discounts' );
			$wp_roles->remove_cap( 'administrator', 'rcp_view_payments' );
			$wp_roles->remove_cap( 'administrator', 'rcp_manage_payments' );
			$wp_roles->remove_cap( 'administrator', 'rcp_manage_settings' );
			$wp_roles->remove_cap( 'administrator', 'rcp_export_data' );
			$wp_roles->remove_cap( 'administrator', 'rcp_view_help' );

		}
	}
}