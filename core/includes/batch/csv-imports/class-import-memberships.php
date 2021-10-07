<?php
/**
 * Import Memberships
 *
 * @since     3.1
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 * @package   restrict-content-pro
 */

use RCP\Membership_Level;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RCP_Batch_Callback_Import_Memberships
 *
 * @since 3.1
 */
class RCP_Batch_Callback_Import_Memberships extends RCP_Batch_Callback_CSV_Import_Base {

	/**
	 * Process batch
	 */
	public function process_batch() {

		parent::process_batch();

		// Maybe disable notification emails.
		if ( ! empty( $this->settings['disable_notification_emails'] ) ) {
			remove_action( 'rcp_membership_post_activate', 'rcp_email_on_membership_activation', 10 );
			remove_action( 'rcp_membership_post_cancel', 'rcp_email_on_membership_cancellation', 10 );
			remove_action( 'rcp_transition_membership_status_expired', 'rcp_email_on_membership_expiration', 10 );
		}

		$processed = [];

		if ( empty( $this->rows ) || $this->time_exceeded() ) {
			$this->get_job()->adjust_current_count( count( $processed ) );
			return;
		}

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
			 * Retrieve or create customer record.
			 */
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
			}

			/**
			 * Gather membership information.
			 */
			$membership_data = $this->get_membership_data( $row );

			if ( is_wp_error( $membership_data ) ) {
				$this->get_job()->add_error( sprintf( __( 'Skipping row #%d (user ID #%d). Error code: %s; Error message: %s', 'rcp' ), $row_number, $user->ID, $membership_data->get_error_code(), $membership_data->get_error_message() ) );

				continue;
			}

			$membership_data['customer_id'] = $customer->get_id();

			$existing_membership = rcp_get_customer_single_membership( $customer->get_id() );

			$new = false;

			/*
			 * Add a new membership if:
			 * 		- Multiple memberships is enabled and they've opted to add new records; or
			 * 		- The customer doesn't have any membership records yet; or
			 * 		- The customer does have an existing membership, but it's for a different level.
			 */
			if (
				( rcp_multiple_memberships_enabled() && ! empty( $this->settings['existing_customers'] ) && 'new' == $this->settings['existing_customers'] )
				||
				empty( $existing_membership )
				||
				( ! empty( $existing_membership ) && $existing_membership->get_object_id() != $membership_data['object_id'] )
			) {
				$new = true;
			}

			if ( $new ) {

				/**
				 * Insert new membership records.
				 */
				$membership_id = $customer->add_membership( $membership_data );

				if ( empty( $membership_id ) ) {
					$this->get_job()->add_error( sprintf( __( 'Error creating membership record for row #%d (user ID #%d).', 'rcp' ), $row_number, $user->ID ) );

					continue;
				}

			} else {

				/**
				 * Update existing membership.
				 */
				$membership_id = $existing_membership->get_id();
				$updated       = $existing_membership->update( $membership_data );

				if ( empty( $updated ) ) {
					$this->get_job()->add_error( sprintf( __( 'Error updating membership record #%d for row #%d.', 'rcp' ), $existing_membership->get_id(), $row_number ) );

					continue;
				}

			}

			$new_membership = rcp_get_membership( $membership_id );

			/**
			 * Deprecated action hook from old CSV User Import add-on.
			 */
			do_action_deprecated( 'rcp_user_import_user_added', array(
				$user->ID,
				$user,
				$new_membership->get_object_id(),
				$new_membership->get_status(),
				$new_membership->get_expiration_date( false ),
				$row
			), '3.1.2', 'rcp_csv_import_membership_processed' );

			/**
			 * Triggers after a membership record has been imported / updated.
			 *
			 * @param RCP_Membership $new_membership The membership that was imported or updated.
			 * @param WP_User        $user           User object associated with the new membership.
			 * @param array          $row            Entire CSV row for this membership.
			 */
			do_action( 'rcp_csv_import_membership_processed', $new_membership, $user, $row );

			$processed[] = $row_number;

			unset( $this->rows[$row_number] );

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
	 * @since 3.1
	 * @return WP_User|WP_Error
	 */
	private function get_user( $row ) {

		$user = false;

		$user_login = $email = $first_name = $last_name = $password = '';

		// Get user by email.
		if ( ! empty( $this->field_map['user_email'] ) && ! empty( $row[$this->field_map['user_email']] ) ) {
			$email = $row[$this->field_map['user_email']];
			$user  = get_user_by( 'email', $email );
		}

		// Get user by login.
		if ( empty( $user ) && ! empty( $this->field_map['user_login'] ) && ! empty( $row[$this->field_map['user_login']] ) ) {
			$user_login = $row[$this->field_map['user_login']];
			$user       = get_user_by( 'login', $user_login );
		}

		// First name.
		if ( ! empty( $this->field_map['first_name'] ) && ! empty( $row[$this->field_map['first_name']] ) ) {
			$first_name = $row[$this->field_map['first_name']];
		}

		// Last name.
		if ( ! empty( $this->field_map['last_name'] ) && ! empty( $row[$this->field_map['last_name']] ) ) {
			$last_name = $row[$this->field_map['last_name']];
		}

		// Password
		if ( ! empty( $this->field_map['user_password'] ) && ! empty( $row[$this->field_map['user_password']] ) ) {
			$password = $row[$this->field_map['user_password']];
		} else {
			$password = '';
		}

		if ( ! empty( $user ) ) {

			/**
			 * Check to see if we have a data mismatch.
			 * For example: if the email in the CSV file doesn't match the email we have for the fetched user.
			 */
			if ( ! empty( $email ) && $email != $user->user_email ) {
				return new WP_Error( 'email_mismatch', sprintf( __( 'Email address provided in the CSV does not match the user email on record for user ID #%d.', 'rcp' ), $user->ID ) );
			}

			if ( ! empty( $user_login ) && $user_login != $user->user_login ) {
				return new WP_Error( 'user_login_mismatch', sprintf( __( 'User login provided in the CSV does not match the user login on record for user ID #%d.', 'rcp' ), $user->ID ) );
			}

			/**
			 * Update existing account with new data.
			 */

			$data_to_update = array();

			if ( ! empty( $password ) ) {
				$data_to_update['user_pass'] = $password;
			}

			if ( ! empty( $first_name ) ) {
				$data_to_update['first_name'] = sanitize_text_field( $first_name );
			}

			if ( ! empty( $last_name ) ) {
				$data_to_update['last_name'] = sanitize_text_field( $last_name );
			}

			if ( ! empty( $data_to_update ) ) {
				$data_to_update['ID'] = $user->ID;

				// Don't update the password of the current user.
				if ( $user->ID == get_current_user_id() && isset( $data_to_update['user_pass'] ) ) {
					unset( $data_to_update['user_pass'] );
				}

				wp_update_user( $data_to_update );
			}

		} else {

			/**
			 * Create new account.
			 */
			$user_data = array(
				'user_login' => ! empty( $user_login ) ? sanitize_text_field( $user_login ) : sanitize_text_field( $email ),
				'user_email' => sanitize_text_field( $email ),
				'first_name' => sanitize_text_field( $first_name ),
				'last_name'  => sanitize_text_field( $last_name ),
				'user_pass'  => ! empty( $password ) ? $password : wp_generate_password( 24 )
			);

			$user_id = wp_insert_user( $user_data );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			if ( ! empty( $user_id ) && ! is_wp_error( $user_id ) ) {
				$user = get_user_by( 'ID', $user_id );

				if ( ! empty( $this->settings['send_set_password_emails'] ) ) {
					wp_new_user_notification( $user->ID, null, 'user' );
				}
			}

		}

		if ( empty( $user ) ) {
			$user = new WP_Error( 'user_error', __( 'Error creating or retrieving user account.', 'rcp' ) );
		}

		return $user;

	}

	/**
	 * Get membership data from the row
	 *
	 * @param array $row
	 *
	 * @return array|WP_Error
	 */
	private function get_membership_data( $row ) {

		$data = array();

		// Membership level.
		if ( ! empty( $this->field_map['membership_level_name'] ) && ! empty( $row[$this->field_map['membership_level_name']] ) ) {
			$membership_level = rcp_get_membership_level_by( 'name', $row[$this->field_map['membership_level_name']] );

			if ( $membership_level instanceof Membership_Level ) {
				$data['object_id'] = $membership_level->get_id();
			}
		} elseif ( ! empty( $this->settings['object_id'] ) ) {
			// Get value from settings UI.
			$data['object_id'] = absint( $this->settings['object_id'] );
		}

		if ( empty( $data['object_id'] ) ) {
			return new WP_Error( 'missing_membership_level', __( 'Unable to determine membership level.', 'rcp' ) );
		}

		// Membership status.
		if ( ! empty( $this->field_map['status'] ) && ! empty( $row[$this->field_map['status']] ) ) {
			$status = sanitize_text_field( strtolower( $row[$this->field_map['status']] ) );

			if ( in_array( $status, array( 'active', 'cancelled', 'expired', 'pending' ) ) ) {
				$data['status'] = $status;
			}
		} elseif ( ! empty( $this->settings['status'] ) ) {
			// Get value from settings UI.
			$data['status'] = sanitize_text_field( $this->settings['status'] );
		}

		// Created Date
		if ( ! empty( $this->field_map['created_date'] ) && ! empty( $row[$this->field_map['created_date']] ) ) {
			$data['created_date'] = date( 'Y-m-d H:i:s', strtotime( $row[$this->field_map['created_date']], current_time( 'timestamp' ) ) );
		}

		// Expiration date.
		$expiration_date = $this->settings['expiration_date'];
		if ( ! empty( $this->field_map['expiration_date'] ) && ! empty( $row[$this->field_map['expiration_date']] ) ) {
			if ( 'none' == strtolower( $row[$this->field_map['expiration_date']] ) ) {
				$expiration_date = 'none';
			} else {
				$expiration_date = $row[$this->field_map['expiration_date']];
			}
		}
		if ( ! empty( $expiration_date ) && 'none' !== strtolower( $expiration_date ) ) {
			$data['expiration_date'] = date( 'Y-m-d 23:59:59', strtotime( $expiration_date, current_time( 'timestamp' ) ) );
		} elseif ( 'none' === strtolower( $expiration_date ) ) {
			$data['expiration_date'] = 'none';
		}

		// Auto Renew
		if ( ! empty( $this->field_map['auto_renew'] ) && ! empty( $row[$this->field_map['auto_renew']] ) ) {
			$data['auto_renew'] = 1;
		} else {
			$data['auto_renew'] = 0;
		}

		// Times Billed
		if ( ! empty( $this->field_map['times_billed'] ) && ! empty( $row[$this->field_map['times_billed']] ) ) {
			$data['times_billed'] = absint( $row[$this->field_map['times_billed']] );
		}

		// Gateway
		if ( ! empty( $this->field_map['gateway'] ) && ! empty( $row[$this->field_map['gateway']] ) ) {
			$gateway_slug = strtolower( $row[$this->field_map['gateway']] );

			if ( array_key_exists( $gateway_slug, rcp_get_payment_gateways() ) ) {
				$data['gateway'] = sanitize_text_field( $gateway_slug );
			}
		}

		// We can loop through all the rest of the data.
		$fields = array(
			'gateway_customer_id',
			'gateway_subscription_id',
			'subscription_key'
		);

		foreach ( $fields as $field ) {
			if ( ! empty( $this->field_map[$field] ) && ! empty( $row[$this->field_map[$field]] ) ) {
				$data[$field] = sanitize_text_field( $row[$this->field_map[$field]] );
			}
		}

		// If gateway value doesn't exist, but we have a gateway_customer_id or gateway_subscription_id, then attempt to guess it.
		if ( empty( $data['gateway'] ) && ( ! empty( $data['gateway_customer_id'] ) || ! empty( $data['gateway_subscription_id'] ) ) ) {
			$data['gateway'] = rcp_get_gateway_slug_from_gateway_ids( $data );
		}

		return $data;

	}

}
