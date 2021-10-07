<?php
/**
 * Export Memberships
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 */

namespace RCP\Batch\CSV_Exports;

use RCP_Membership;

/**
 * Class Memberships
 *
 * @package RCP\Batch\CSV_Exports
 */
class Memberships extends Base {

	/**
	 * @inheritDoc
	 */
	public function get_columns() {

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

		/**
		 * Filters the columns to export.
		 *
		 * @param array $cols
		 *
		 * @since 1.5
		 */
		return apply_filters( 'rcp_export_csv_cols_members', $cols );

	}

	/**
	 * Builds and returns an array of query args to use in count and get functions.
	 *
	 * @return array
	 */
	private function get_query_args() {
		$number = $this->get_amount_per_step();
		if ( ! empty( $this->settings['number'] ) && $number > $this->settings['number'] ) {
			$number = $this->settings['number'];
		}

		$args = array(
			'number' => $number,
			'offset' => $this->offset
		);

		if ( ! empty( $this->settings['level_id'] ) ) {
			$args['object_id'] = absint( $this->settings['level_id'] );
		}

		if ( ! empty( $this->settings['status'] ) && 'all' !== $this->settings['status'] ) {
			$args['status'] = strtolower( $this->settings['status'] );
		}

		return $args;
	}

	/**
	 * @inheritDoc
	 */
	public function get_batch() {

		// Bail with no results if a "Maximum Number" has been specified and we've exceeded that.
		if ( ! empty( $this->settings['number'] ) && ( $this->get_amount_per_step() * $this->offset ) > $this->settings['number'] ) {
			return array();
		}

		$memberships = rcp_get_memberships( $this->get_query_args() );
		$batch       = array();

		foreach ( $memberships as $membership ) {

			$user      = get_userdata( $membership->get_user_id() );
			$discounts = get_user_meta( $membership->get_user_id(), 'rcp_user_discounts', true );
			if ( ! empty( $discounts ) && is_array( $discounts ) && ! $discounts instanceof \stdClass ) {
				foreach ( $discounts as $key => $code ) {
					if ( ! is_string( $code ) ) {
						unset( $discounts[ $key ] );
					}
				}
				$discounts = implode( ' ', $discounts );
			}

			$membership_data = array(
				'id'                    => $membership->get_id(),
				'user_id'               => $membership->get_customer()->get_user_id(),
				'user_login'            => $user->user_login,
				'user_email'            => $user->user_email,
				'first_name'            => $user->first_name,
				'last_name'             => $user->last_name,
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

			if ( has_filter( 'rcp_export_members_get_data_row' ) ) {
				$membership_data = apply_filters_deprecated( 'rcp_export_members_get_data_row', array(
					$membership_data,
					new \RCP_Member( $user->ID )
				), '3.0', 'rcp_export_memberships_get_data_row' );
			}

			/**
			 * Filters the data row.
			 *
			 * @param array          $membership_data Membership data for this row.
			 * @param RCP_Membership $membership      Membership object.
			 *
			 * @since 3.0
			 */
			$batch[] = apply_filters( 'rcp_export_memberships_get_data_row', $membership_data, $membership );

		}

		$batch = apply_filters_deprecated( 'rcp_export_get_data', array( $batch ), '3.4' );
		$batch = apply_filters_deprecated( 'rcp_export_get_data_members', array( $batch ), '3.4' );

		return $batch;

	}

	/**
	 * Counts the total number of expected results.
	 *
	 * @return int
	 */
	public function get_total() {
		return rcp_count_memberships( $this->get_query_args() );
	}
}
