<?php
/**
 * Import Payments
 *
 * @since     3.4
 * @copyright Copyright (c) 2020, Restrict Content Pro team
 * @license   GPL2+
 * @package   restrict-content-pro
 */

use RCP\Membership_Level;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RCP_Batch_Callback_Import_Payments
 *
 * @since 3.4
 */
class RCP_Batch_Callback_Import_Payments extends RCP_Batch_Callback_CSV_Import_Base {

	/**
	 * Process batch
	 *
	 * @since 3.4
	 */
	public function process_batch() {

		parent::process_batch();

		// Disable notification emails.
		remove_action( 'rcp_update_payment_status_complete', 'rcp_email_payment_received', 100 );
		remove_action( 'rcp_process_manual_signup', 'rcp_email_admin_on_manual_payment', 10 );

		$processed = [];

		if ( empty( $this->rows ) || $this->time_exceeded() ) {
			$this->get_job()->adjust_current_count( count( $processed ) );

			return;
		}

		$payments = new RCP_Payments();

		foreach ( $this->rows as $row_number => $row ) {

			/**
			 * Parse user account.
			 */
			$user = $this->get_user( $row );

			if ( is_wp_error( $user ) ) {
				$this->get_job()->add_error( sprintf( __( 'Skipping row #%d. Error code: %s; Error message: %s', 'rcp' ), $row_number, $user->get_error_code(), $user->get_error_message() ) );

				continue;
			}

			/**
			 * Gather payment information.
			 */
			$payment_data = $this->get_payment_data( $row );

			if ( is_wp_error( $payment_data ) ) {
				$this->get_job()->add_error( sprintf( __( 'Skipping row #%d. Error code: %s; Error message: %s', 'rcp' ), $row_number, $payment_data->get_error_code(), $payment_data->get_error_message() ) );

				continue;
			}

			// Add user ID.
			$payment_data['user_id'] = absint( $user->ID );

			// If we don't have a customer record at this point, make one.
			if ( empty( $payment_data['customer_id'] ) ) {
				$customer = rcp_get_customer_by_user_id( $user->ID );
				if ( empty( $customer ) ) {
					$customer_id = rcp_add_customer( array(
						'user_id' => absint( $user->ID )
					) );

					if ( ! empty( $customer_id ) ) {
						$customer = rcp_get_customer( $customer_id );
					}
				}

				if ( empty( $customer ) ) {
					$this->get_job()->add_error( sprintf( __( 'Skipping row #%d. Error creating or retrieving customer record for user #%d.', 'rcp' ), $row_number, $user->ID ) );

					continue;
				} else {
					$payment_data['customer_id'] = $customer->get_id();
				}
			}

			$result = $payments->insert( $payment_data );

			if ( empty( $result ) ) {
				$this->get_job()->add_error( sprintf( __( 'Skipping row #%d. Error creating payment record.', 'rcp' ), $row_number ) );

				continue;
			}

			$processed[] = $row_number;

			unset( $this->rows[ $row_number ] );

		}

		$this->get_job()->adjust_current_count( count( $processed ) );

		if ( $this->get_job()->is_completed() ) {
			$this->finish();
		}

	}

	/**
	 * Parse existing user from the row or create a new one.
	 *
	 * @param array $row
	 *
	 * @since 3.4
	 * @return WP_User|WP_Error
	 */
	private function get_user( $row ) {

		$user = false;

		// Get user by email.
		if ( ! empty( $this->field_map['user_email'] ) && ! empty( $row[ $this->field_map['user_email'] ] ) ) {
			$email = $row[ $this->field_map['user_email'] ];
			$user  = get_user_by( 'email', $email );
		}

		// Get user by login.
		if ( empty( $user ) && ! empty( $this->field_map['user_login'] ) && ! empty( $row[ $this->field_map['user_login'] ] ) ) {
			$user_login = $row[ $this->field_map['user_login'] ];
			$user       = get_user_by( 'login', $user_login );
		}

		if ( empty( $user ) ) {
			$user = new WP_Error( 'user_error', __( 'Error retrieving user account.', 'rcp' ) );
		}

		return $user;

	}

	/**
	 * Get payment data from the row
	 *
	 * @param array $row
	 *
	 * @since 3.4
	 * @return array|WP_Error
	 */
	private function get_payment_data( $row ) {

		$data = array();

		// Date.
		if ( ! empty( $this->field_map['date'] ) && ! empty( $row[ $this->field_map['date'] ] ) ) {
			$date = $row[ $this->field_map['membership_level_name'] ];

			// Convert it to our desired format.
			$data['date'] = date( 'Y-m-d H:i:s', strtotime( $date, current_time( 'timestamp' ) ) );
		}

		// Membership & object ID
		if ( ! empty( $this->field_map['membership_id'] ) && ! empty( $row[ $this->field_map['membership_id'] ] ) ) {
			$membership = rcp_get_membership( absint( $row[ $this->field_map['membership_id'] ] ) );

			if ( ! empty( $membership ) ) {
				$data['membership_id'] = $membership->get_id();
				$data['customer_id']   = $membership->get_customer_id();
				$data['object_id']     = $membership->get_object_id();
				$data['subscription']  = sanitize_text_field( $membership->get_membership_level_name() );
			}
		}
		// Get object ID from explicit value.
		if ( empty( $data['object_id'] ) && ! empty( $this->field_map['object_id'] ) && ! empty( $row[ $this->field_map['object_id'] ] ) ) {
			$membership_level = rcp_get_membership_level( $row[ $this->field_map['object_id'] ] );

			if ( $membership_level instanceof Membership_Level ) {
				$data['object_id']    = $membership_level->get_id();
				$data['subscription'] = sanitize_text_field( $membership_level->get_name() );
			}
		}
		// Get object ID from membership level name.
		if ( empty( $data['subscription'] ) && ! empty( $this->field_map['subscription'] ) && ! empty( $row[ $this->field_map['subscription'] ] ) ) {
			$membership_level = rcp_get_membership_level_by( 'name', $row[ $this->field_map['subscription'] ] );

			if ( $membership_level instanceof Membership_Level ) {
				$data['object_id']    = $membership_level->get_id();
				$data['subscription'] = sanitize_text_field( $membership_level->get_name() );
			} else {
				$data['subscription'] = sanitize_text_field( $row[ $this->field_map['subscription'] ] );
			}
		}

		// Gateway
		if ( ! empty( $this->field_map['gateway'] ) && ! empty( $row[ $this->field_map['gateway'] ] ) ) {
			$gateway_slug = strtolower( $row[ $this->field_map['gateway'] ] );

			if ( array_key_exists( $gateway_slug, rcp_get_payment_gateways() ) ) {
				$data['gateway'] = sanitize_text_field( $gateway_slug );
			}
		}

		// Transaction Type
		if ( ! empty( $this->field_map['transaction_type'] ) && ! empty( $row[ $this->field_map['transaction_type'] ] ) ) {
			$transaction_type = strtolower( $row[ $this->field_map['transaction_type'] ] );
			$all_types        = array(
				'new',
				'renewal',
				'upgrade',
				'downgrade'
			);

			if ( in_array( $transaction_type, $all_types ) ) {
				$data['transaction_type'] = sanitize_text_field( $transaction_type );
			}
		}

		// We can loop through all the rest of the data.
		$fields = array(
			'amount',
			'subtotal',
			'credits',
			'fees',
			'discount_amount',
			'discount_code',
			'transaction_id'
		);

		foreach ( $fields as $field ) {
			if ( ! empty( $this->field_map[ $field ] ) && ! empty( $row[ $this->field_map[ $field ] ] ) ) {
				$data[ $field ] = sanitize_text_field( $row[ $this->field_map[ $field ] ] );
			}
		}

		return $data;

	}

}
