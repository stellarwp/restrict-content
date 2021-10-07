<?php
/**
 * Export Members Class
 *
 * Export members to a CSV
 *
 * @package     Restrict Content Pro
 * @subpackage  Export Class
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class RCP_Members_Export
 *
 * @deprecated 3.4
 */
class RCP_Members_Export extends RCP_Export {

	/**
	 * Our export type. Used for export-type specific filters / actions
	 *
	 * @access      public
	 * @var         string
	 * @since       1.5
	 */
	public $export_type = 'members';

	/**
	 * Set the CSV columns
	 *
	 * @access      public
	 * @since       1.5
	 * @return      array
	 */
	public function csv_cols() {
		$cols = array(
			'id'                    => __( 'Membership ID', 'rcp' ),
			'user_id'               => __( 'User ID', 'rcp' ),
			'user_login'            => __( 'User Login', 'rcp' ),
			'user_email'            => __( 'User Email', 'rcp' ),
			'first_name'            => __( 'First Name', 'rcp' ),
			'last_name'             => __( 'Last Name', 'rcp' ),
			'subscription'          => __( 'Membership Level ID', 'rcp' ),
			'membership_level_name' => __( 'Membership Level Name', 'rcp' ),
			'subscription_key'      => __( 'Subscription Key', 'rcp' ),
			'created_date'          => __( 'Created Date', 'rcp' ),
			'expiration'            => __( 'Expiration Date', 'rcp' ),
			'status'                => __( 'Status', 'rcp' ),
			'times_billed'          => __( 'Times Billed', 'rcp' ),
			'discount_codes'        => __( 'Discount Codes', 'rcp' ),
			'gateway'               => __( 'Gateway', 'rcp' ),
			'gateway_customer_id'   => __( 'Gateway Customer ID', 'rcp' ),
			'profile_id'            => __( 'Gateway Subscription ID', 'rcp' ),
			'is_recurring'          => __( 'Auto Renew', 'rcp' )
		);
		return $cols;
	}

	/**
	 * Get the data being exported
	 *
	 * @access      public
	 * @since       1.5
	 * @return      array
	 */
	public function get_data() {
		global $wpdb;

		$data = array();

		$subscription = isset( $_POST['rcp-subscription'] ) ? absint( $_POST['rcp-subscription'] )        : null;
		$status       = isset( $_POST['rcp-status'] )       ? sanitize_text_field( $_POST['rcp-status'] ) : 'active';
		$offset       = isset( $_POST['rcp-offset'] )       ? absint( $_POST['rcp-offset'] )              : null;
		$number       = isset( $_POST['rcp-number'] )       ? absint( $_POST['rcp-number'] )              : null;

		$args = array(
			'status' => $status,
			'number' => $number,
			'offset' => $offset
		);

		if ( ! empty( $subscription ) ) {
			$args['object_id'] = $subscription;
		}

		$memberships  = rcp_get_memberships( $args );

		if( $memberships ) :
			foreach ( $memberships as $membership ) {

				/**
				 * @var RCP_Membership $membership
				 */

				$member = new RCP_Member( $membership->get_user_id() ); // for backwards compatibility

				$discounts = get_user_meta( $membership->get_user_id(), 'rcp_user_discounts', true );
				if( ! empty( $discounts ) && is_array( $discounts ) && ! $discounts instanceof stdClass ) {
					foreach( $discounts as $key => $code ) {
						if( ! is_string( $code ) ) {
							unset( $discounts[ $key ] );
						}
					}
					$discounts = implode( ' ', $discounts );
				}

				$membership_data = array(
					'id'                    => $membership->get_id(),
					'user_id'               => $membership->get_user_id(),
					'user_login'            => $member->user_login,
					'user_email'            => $member->user_email,
					'first_name'            => $member->first_name,
					'last_name'             => $member->last_name,
					'subscription'          => $membership->get_object_id(),
					'membership_level_name' => $membership->get_membership_level_name(),
					'subscription_key'      => $membership->get_subscription_key(),
					'created_date'          => $membership->get_created_date( false ),
					'expiration'            => $membership->get_expiration_date( false ),
					'status'                => $membership->get_status(),
					'times_billed'          => $membership->get_times_billed(),
					'discount_codes'        => $discounts,
					'gateway'               => $membership->get_gateway(),
					'gateway_customer_id'   => $membership->get_gateway_customer_id(),
					'profile_id'            => $membership->get_gateway_subscription_id(),
					'is_recurring'          => $membership->is_recurring()
				);

				/**
				 * @deprecated 3.0 Use `rcp_export_memberships_get_data_row` instead.
				 */
				$membership_data = apply_filters( 'rcp_export_members_get_data_row', $membership_data, $member );

				/**
				 * Filters the data row.
				 *
				 * @param array          $membership_data Membership data for this row.
				 * @param RCP_Membership $membership      Membership object.
				 *
				 * @since 3.0
				 */
				$data[] = apply_filters( 'rcp_export_memberships_get_data_row', $membership_data, $membership );

			}
		endif;

		$data = apply_filters( 'rcp_export_get_data', $data );
		$data = apply_filters( 'rcp_export_get_data_' . $this->export_type, $data );

		return $data;
	}
}
