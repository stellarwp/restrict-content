<?php
/**
 * class-export-payments.php
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Ashley Gibson
 * @license   GPL2+
 */

namespace RCP\Batch\CSV_Exports;

use RCP_Payments;

/**
 * Class Payments
 *
 * @package RCP\Batch\CSV_Exports
 */
class Payments extends Base {

	/**
	 * @inheritDoc
	 */
	public function get_columns() {

		$cols = array(
			'id'               => __( 'ID', 'rcp' ),
			'status'           => __( 'Status', 'rcp' ),
			'object_type'      => __( 'Purchase Type', 'rcp' ),
			'object_id'        => __( 'Membership Level ID', 'rcp' ),
			'subscription'     => __( 'Membership Level Name', 'rcp' ),
			'amount'           => __( 'Total Amount', 'rcp' ),
			'subtotal'         => __( 'Subtotal', 'rcp' ),
			'credits'          => __( 'Credits', 'rcp' ),
			'fees'             => __( 'Fees', 'rcp' ),
			'discount_amount'  => __( 'Discount Amount', 'rcp' ),
			'discount_code'    => __( 'Discount Code', 'rcp' ),
			'user_id'          => __( 'User ID', 'rcp' ),
			'user_login'       => __( 'User Login', 'rcp' ),
			'user_email'       => __( 'User Email', 'rcp' ),
			'customer_id'      => __( 'Customer ID', 'rcp' ),
			'membership_id'    => __( 'Membership ID', 'rcp' ),
			'payment_type'     => __( 'Payment Type', 'rcp' ),
			'gateway'          => __( 'Gateway', 'rcp' ),
			'subscription_key' => __( 'Subscription Key', 'rcp' ),
			'transaction_id'   => __( 'Transaction ID', 'rcp' ),
			'transaction_type' => __( 'Transaction Type', 'rcp' ),
			'date'             => __( 'Date', 'rcp' )
		);

		/**
		 * Filters the columns to export.
		 *
		 * @param array $cols
		 *
		 * @since 1.5
		 */
		return apply_filters( 'rcp_export_csv_cols_payments', $cols );

	}

	/**
	 * Builds and returns an array of query args to use in count and get functions.
	 *
	 * @return array
	 */
	private function get_query_args() {
		$args = array(
			'number' => $this->get_amount_per_step(),
			'offset' => $this->offset
		);

		if ( ! empty( $this->settings['year'] ) ) {
			$args['date']['year'] = absint( $this->settings['year'] );
		}

		if ( ! empty( $this->settings['month'] ) ) {
			$args['date']['month'] = absint( $this->settings['month'] );
		}

		return $args;
	}

	/**
	 * @inheritDoc
	 */
	public function get_batch() {

		$batch    = array();
		$rcp_db   = new RCP_Payments;
		$payments = $rcp_db->get_payments( $this->get_query_args() );

		foreach ( $payments as $payment ) {

			$user = get_userdata( $payment->user_id );

			$payment_data = array(
				'id'               => $payment->id,
				'status'           => $payment->status,
				'object_type'      => $payment->object_type,
				'object_id'        => $payment->object_id,
				'subscription'     => $payment->subscription,
				'amount'           => $payment->amount,
				'subtotal'         => $payment->subtotal,
				'credits'          => $payment->credits,
				'fees'             => $payment->fees,
				'discount_amount'  => $payment->discount_amount,
				'discount_code'    => $payment->discount_code,
				'user_id'          => $payment->user_id,
				'user_login'       => isset( $user->user_login ) ? $user->user_login : '',
				'user_email'       => isset( $user->user_email ) ? $user->user_email : '',
				'customer_id'      => ! empty( $payment->customer_id ) ? $payment->customer_id : '',
				'membership_id'    => ! empty( $payment->membership_id ) ? $payment->membership_id : '',
				'payment_type'     => $payment->payment_type,
				'gateway'          => $payment->gateway,
				'subscription_key' => $payment->subscription_key,
				'transaction_id'   => $payment->transaction_id,
				'transaction_type' => ! empty( $payment->transaction_type ) ? $payment->transaction_type : '',
				'date'             => $payment->date
			);

			/**
			 * Filters the payment information that's exported to this row.
			 *
			 * @param array  $payment_data
			 * @param object $payment
			 */
			$payment_data = apply_filters( 'rcp_export_payments_get_data_row', $payment_data, $payment );

			$batch[] = $payment_data;

		}

		$batch = apply_filters_deprecated( 'rcp_export_get_data', array( $batch ), '3.4' );
		$batch = apply_filters_deprecated( 'rcp_export_get_data_payments', array( $batch ), '3.4' );

		return $batch;

	}

	/**
	 * Counts the total number of expected results.
	 *
	 * @return int
	 */
	public function get_total() {
		$rcp_db = new RCP_Payments;

		return $rcp_db->count( $this->get_query_args() );
	}

}
