<?php

use RCP\Membership_Level;

/**
 * RCP Payments class
 *
 * This class handles querying, inserting, updating, and removing payments
 * Also handles calculating earnings
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Payments
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

class RCP_Payments {

	/**
	 * Holds the name of our payments database table
	 *
	 * @access  public
	 * @since   1.5
	 */
	public $db_name;

	/**
	 * Holds the name of our payment meta database table
	 *
	 * @access  public
	 * @since   2.6
	 */
	public $meta_db_name;

	/**
	 * Holds the version number of our discounts database table
	 *
	 * @access  public
	 * @since   1.5
	 */
	public $db_version;

	/**
	 * Get things going.
	 *
	 * @return void
	 */
	function __construct() {

		$this->db_name      = rcp_get_payments_db_name();
		$this->meta_db_name = rcp_get_payment_meta_db_name();
		$this->db_version   = '1.5';

	}


	/**
	 * Add a payment to the database
	 *
	 * @access  public
	 * @param   array $payment_data Array All of the payment data, such as amount, date, user ID, etc
	 * @since   1.5
	 * @return  int|false ID of the newly created payment, or false on failure.
	 */
	public function insert( $payment_data = array() ) {

		global $wpdb;

		$defaults = array(
			'subscription'          => '',
			'object_id'             => 0,
			'object_type'           => 'subscription',
			'date'                  => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'amount'                => 0.00, // Total amount after fees/credits/discounts are added.
			'user_id'               => 0,
			'customer_id'           => 0,
			'membership_id'         => 0,
			'payment_type'          => '',
			'transaction_type'      => 'new',
			'subscription_key'      => '',
			'transaction_id'        => '',
			'status'                => 'complete',
			'gateway'               => '',
			'subtotal'              => 0.00, // Base price of the membership level.
			'credits'               => 0.00, // Proration credits.
			'fees'                  => 0.00, // Fees.
			'discount_amount'       => 0.00, // Discount amount from discount code.
			'discount_code'         => ''
		);

		$args = wp_parse_args( $payment_data, $defaults );

		if( ! empty( $args['transaction_id'] ) && $this->payment_exists( $args['transaction_id'] ) ) {
			return false;
		}

		// Backwards compatibility: make sure we store the subscription ID as well.
		if ( empty( $args['object_id'] ) && ! empty( $args['subscription'] ) ) {
			$membership_level = rcp_get_membership_level_by( 'name', $args['subscription'] );

			if ( $membership_level instanceof Membership_Level ) {
				$args['object_id'] = $membership_level->get_id();
			}
		}

		// Backwards compatibility: include customer ID.
		if ( empty( $args['customer_id'] ) && ! empty( $args['user_id'] ) ) {
			$customer = rcp_get_customer_by_user_id( $args['user_id'] );

			if ( ! empty( $customer ) ) {
				$args['customer_id'] = $customer->get_id();
			}
		}

		// Backwards compatibility: update pending payment instead of creating a new one.
		if ( ! empty( $args['user_id'] ) && 'complete' == $args['status'] ) {
			$last_pending_payment = get_user_meta( $args['user_id'], 'rcp_pending_payment_id', true );
			$pending_payment      = ! empty( $last_pending_payment ) ? $this->get_payment( $last_pending_payment ) : false;

			if ( ! empty( $pending_payment ) && $args['amount'] == $pending_payment->amount && $args['subscription'] == $pending_payment->subscription ) {
				$this->update( $pending_payment->id, $args );

				return $pending_payment->id;
			}
		}

		$add = $wpdb->insert( $this->db_name, $args, array( '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );

		// if insert was successful, return the payment ID
		if( $add ) {

			$payment_id = $wpdb->insert_id;

			// clear the payment caches
			delete_transient( 'rcp_earnings' );
			delete_transient( 'rcp_payments_count' );

			// Remove trialing status, if it exists
			delete_user_meta( $args['user_id'], 'rcp_is_trialing' );

			/**
			 * Triggers when the payment's status is changed. This is here to spoof a status
			 * change when a payment is first inserted.
			 *
			 * @see RCP_Payment::update() - Action is also run here when status is changed.
			 *
			 * @param string $new_status New status being set.
			 * @param int    $payment_id ID of the payment.
			 *
			 * @since 2.9
			 */
			do_action( 'rcp_update_payment_status', $args['status'], $payment_id );
			do_action( 'rcp_update_payment_status_' . $args['status'], $payment_id );

			if ( 'complete' == $args['status'] ) {
				/**
				 * Runs only when a new payment is inserted as "complete". This is to
				 * ensure backwards compatibility from before payments were inserted
				 * as "pending" before payment is taken.
				 *
				 * @deprecated 2.9 - Use rcp_create_payment to run actions whenever a payment is
				 *             inserted, regardless of status.
				 *
				 * @see RCP_Payment::update() - Action is also run here when status is updated to complete.
				 *
				 * @param int   $payment_id ID of the payment that was just inserted.
				 * @param array $args       Array of all payment information.
				 * @param float $amount     Amount the payment was for.
				 */
				do_action( 'rcp_insert_payment', $payment_id, $args, $args['amount'] );
			}

			/**
			 * Runs when a new payment is successfully inserted.
			 *
			 * @param int   $payment_id ID of the payment that was just inserted.
			 * @param array $args       Array of all payment information.
			 *
			 * @since 2.9
			 */
			do_action( 'rcp_create_payment', $payment_id, $args );

			rcp_log( sprintf( 'New payment inserted. ID: %d; User ID: %d; Amount: %.2f; Subscription: %s; Status: %s', $payment_id, $args['user_id'], $args['amount'], $args['subscription'], $args['status'] ) );

			return $payment_id;

		} else {
			rcp_log( 'Failed inserting new payment into database.', true );
		}

		return false;

	}


	/**
	 * Checks if a payment exists in the DB
	 *
	 * @param   string $transaction_id The transaction ID of the payment record.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  bool
	 */
	public function payment_exists( $transaction_id = '' ) {

		global $wpdb;

		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM " . $this->db_name . " WHERE `transaction_id`='%s' LIMIT 1;",
				$transaction_id
			)
		);

		return (bool) $found;

	}


	/**
	 * Update a payment in the datbase.
	 *
	 * @param   int   $payment_id   ID of the payment record to update.
	 * @param   array $payment_data Array of all payment data to update.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  int|false The number of rows updated, or false on error.
	 */
	public function update( $payment_id = 0, $payment_data = array() ) {

		global $wpdb;

		do_action( 'rcp_update_payment', $payment_id, $payment_data );

		rcp_log( sprintf( 'Updating payment #%d with new data: %s', $payment_id, var_export( $payment_data, true ) ) );

		if ( array_key_exists( 'status', $payment_data ) ) {
			delete_transient( md5( 'rcp_payments_count_' . serialize( array( 'user_id' => 0, 'status' => '', 's' => '' ) ) ) );
			delete_transient( md5( 'rcp_payments_count_' . serialize( array( 'user_id' => 0, 'status' => 'pending', 's' => '' ) ) ) );
			delete_transient( md5( 'rcp_payments_count_' . serialize( array( 'user_id' => 0, 'status' => 'complete', 's' => '' ) ) ) );
			delete_transient( md5( 'rcp_payments_count_' . serialize( array( 'user_id' => 0, 'status' => 'refunded', 's' => '' ) ) ) );
		}

		$updated = $wpdb->update( $this->db_name, $payment_data, array( 'id' => $payment_id ) );

		if ( $updated && array_key_exists( 'status', $payment_data ) ) {

			$payment = $this->get_payment( $payment_id );

			/**
			 * Triggers when the payment's status is changed.
			 *
			 * @param string $new_status   New status being set.
			 * @param int    $payment_id   ID of the payment.
			 *
			 * @since 2.9
			 */
			do_action( 'rcp_update_payment_status', $payment_data['status'], $payment_id );
			do_action( 'rcp_update_payment_status_' . $payment_data['status'], $payment_id );

			if ( 'complete' == $payment_data['status'] ) {
				$amount  = ! empty( $payment->amount ) ? $payment->amount : 0.00;

				/**
				 * Runs only when a payment is updated to "complete". This is to
				 * ensure backwards compatibility from before payments were inserted
				 * as "pending" before payment is taken.
				 *
				 * @deprecated 2.9 - Use rcp_create_payment to run actions whenever a payment is
				 *             inserted, regardless of status.
				 *
				 * @see RCP_Payments::insert() - Action is also run here.
				 *
				 * @param int   $payment_id ID of the payment that was just updated.
				 * @param array $args       Array of payment information that was just updated.
				 * @param float $amount     Amount the payment was for.
				 */
				do_action( 'rcp_insert_payment', $payment_id, (array) $payment, $amount );
			}

		}

		return $updated;
	}


	/**
	 * Delete a payment from the datbase.
	 *
	 * @param   int $payment_id ID of the payment to delete.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  void
	*/
	public function delete( $payment_id = 0 ) {

		global $wpdb;

		do_action( 'rcp_delete_payment', $payment_id );

		rcp_log( sprintf( 'Deleted payment #%d.', $payment_id ) );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db_name} WHERE `id` = '%d';", absint( $payment_id ) ) );

	}


	/**
	 * Retrieve a specific payment
	 *
	 * @param   int $payment_id ID of the payment to retrieve.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  object|null Database object or null if payment not found.
	 */
	public function get_payment( $payment_id = 0 ) {

		global $wpdb;

		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->db_name} WHERE id = %d", absint( $payment_id ) ) );

		if ( is_object( $payment ) ) {
			if ( empty( $payment->status ) ) {
				$payment->status = 'complete';
			}

			$payment = $this->backfill_payment_data( $payment );
		}

		return $payment;

	}

	/**
	 * Attempt to guess the payment gateway from the "Payment Type"
	 *
	 * @param int|object $_payment_id_or_object Payment ID or database object.
	 *
	 * @access private
	 * @since 2.9
	 * @return string|false
	 */
	private function get_payment_gateway( $_payment_id_or_object ) {

		if ( is_object( $_payment_id_or_object ) ) {
			$payment = $_payment_id_or_object;
		} elseif ( is_numeric( $_payment_id_or_object ) ) {
			$payment = $this->get_payment( $_payment_id_or_object );
		}

		if ( empty( $payment ) ) {
			return false;
		}

		$type    = strtolower( $payment->payment_type );
		$gateway = false;

		// If we already have a gateway in the DB, use that.
		if ( ! empty( $payment->gateway ) ) {
			return $payment->gateway;
		}

		// If "Payment Type" isn't set, we can't get the gateway.
		if ( empty( $type ) ) {
			return $gateway;
		}

		switch ( $type ) {

			case 'web_accept' :
			case 'paypal express one time' :
			case 'recurring_payment' :
			case 'subscr_payment' :
			case 'recurring_payment_profile_created' :
				$gateway = 'paypal';
				break;

			case 'credit card' :
			case 'credit card one time' :
				if ( false !== strpos( $payment->transaction_id, 'ch_' ) ) {
					$gateway = 'stripe';
				} elseif ( false !== strpos( $payment->transaction_id, 'anet_' ) ) {
					$gateway = 'authorizenet';
				} elseif ( is_numeric( $payment->transaction_id ) ) {
					$gateway = 'twocheckout';
				}
				break;

			case 'braintree credit card one time' :
			case 'braintree credit card initial payment' :
			case 'braintree credit card' :
				$gateway = 'braintree';
				break;

			case 'manual' :
				$gateway = 'manual';
				break;

		}

		return $gateway;

	}


	/**
	 * Retrieve a specific payment by a field
	 *
	 * @param   string $field Name of the field to check against.
	 * @param   mixed  $value Value of the field.
	 *
	 * @access  public
	 * @since   1.8.2
	 * @return  object
	 */
	public function get_payment_by( $field = 'id', $value = '' ) {

		global $wpdb;

		$query   = $wpdb->prepare( "SELECT * FROM {$this->db_name} WHERE {$field} = %s", sanitize_text_field( $value ) );
		$payment = $wpdb->get_row( $query );

		if( is_object( $payment ) && empty( $payment->status ) ) {
			$payment->status = 'complete';
		}

		return $payment;

	}


	/**
	 * Retrieve payments from the database
	 *
	 * @param   array $args Query arguments to override the defaults.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  array Array of objects.
	 */
	public function get_payments( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'           => 20,
			'offset'           => 0,
			'subscription'     => 0,
			'user_id'          => 0,
			'customer_id'      => 0,
			'membership_id'    => 0,
			'date'             => array(),
			'fields'           => false,
			'status'           => '',
			's'                => '',
			'order'            => 'DESC',
			'orderby'          => 'id',
			'object_type'      => '',
			'object_id'        => '',
			'transaction_type' => '',
			'gateway'          => ''
		);

		$args  = wp_parse_args( $args, $defaults );

		$where  = ' WHERE 1=1 ';
		$values = array();

		// payments for a specific membership level
		if( ! empty( $args['subscription'] ) ) {
			$where   .= " AND `subscription`= %s ";
			$values[] = $args['subscription'];
		}

		// payments for specific users
		if( ! empty( $args['user_id'] ) ) {

			if( is_array( $args['user_id'] ) ) {
				$user_ids = implode( ',', array_map( 'absint', $args['user_id'] ) );
			} else {
				$user_ids = intval( $args['user_id'] );
			}

			$where .= " AND `user_id` IN( {$user_ids} ) ";

		}

		// payments for specific customers
		if( ! empty( $args['customer_id'] ) ) {

			if( is_array( $args['customer_id'] ) ) {
				$customer_ids = implode( ',', array_map( 'absint', $args['customer_id'] ) );
			} else {
				$customer_ids = intval( $args['customer_id'] );
			}

			$where .= " AND `customer_id` IN( {$customer_ids} ) ";

		}

		// payments for specific memberships
		if( ! empty( $args['membership_id'] ) ) {

			if( is_array( $args['membership_id'] ) ) {
				$membership_ids = implode( ',', array_map( 'absint', $args['membership_id'] ) );
			} else {
				$membership_ids = intval( $args['membership_id'] );
			}

			$where .= " AND `membership_id` IN( {$membership_ids} ) ";

		}

		// payments for specific statuses
		if( ! empty( $args['status'] ) ) {

			if( is_array( $args['status'] ) ) {
				$status_count       = count( $args['status'] );
				$status_placeholder = array_fill( 0, $status_count, '%s' );
				$statuses           = implode( ', ', $status_placeholder );

				$where .= "AND `status` IN ( $statuses )";
				$values = $values + $args['status'];
			} else {
				$where   .= "AND `status` = %s";
				$values[] = $args['status'];
			}

		}

		// Setup the date query
		if( ! empty( $args['date'] ) && is_array( $args['date'] ) ) {

			if ( ! empty( $args['date']['start'] ) || ! empty( $args['date']['end'] ) ) {

				if ( ! empty( $args['date']['start'] ) ) {

					$start    = date( 'Y-m-d 00:00:00', strtotime( $args['date']['start'] ) );
					$where   .= " AND `date` >= %s";
					$values[] = $start;

				}

				if ( ! empty( $args['date']['end'] ) ) {

					$end    = date( 'Y-m-d 23:59:59', strtotime( $args['date']['end'] ) );
					$where   .= " AND `date` <= %s";
					$values[] = $end;

				}

			} else {

				$day        = ! empty( $args['date']['day'] ) ? absint( $args['date']['day'] ) : null;
				$month      = ! empty( $args['date']['month'] ) ? absint( $args['date']['month'] ) : null;
				$year       = ! empty( $args['date']['year'] ) ? absint( $args['date']['year'] ) : null;
				$date_where = '';

				$date_where .= ! is_null( $year ) ? $year . " = YEAR ( date ) " : '';

				if ( ! is_null( $month ) ) {
					$date_where = $month . " = MONTH ( date ) AND " . $date_where;
				}

				if ( ! is_null( $day ) ) {
					$date_where = $day . " = DAY ( date ) AND " . $date_where;
				}

				$where .= " AND (" . $date_where . ")";

			}
		}

		// Fields to return
		if( $args['fields'] ) {
			$fields = $args['fields'];
		} else {
			$fields = '*';
		}

		// Search
		if( ! empty( $args['s'] ) ) {

			// Search by email
			if( is_email( $args['s'] ) ) {

				$user = get_user_by( 'email', $args['s'] );

				if ( is_a( $user, 'WP_User' ) ) {
					$where   .= " AND `user_id` = %d";
					$values[] = $user->ID;
				}

			} else {

				// Search by subscription key
				if( strlen( $args['s'] ) == 32 ) {

					$where   .= " AND `subscription_key` = %s";
					$values[] = $args['s'];

				} elseif( rcp_get_membership_level_by( 'name', $args['s'] ) ) {

					// Matching membership level found so search for payments with this level
					$where   .= " AND `subscription` = %s";
					$values[] = $args['s'];
				} else {
					$where   .= " AND `transaction_id` = %s";
					$values[] = $args['s'];
				}
			}

		}

		if ( ! empty( $args['object_type'] ) ) {
			$where   .= " AND `object_type` = %s";
			$values[] = $args['object_type'];
		}

		if ( ! empty( $args['object_id'] ) ) {
			$where   .= " AND `object_id` = %d AND `object_id` != 0";
			$values[] = $args['object_id'];
		}

		if ( ! empty( $args['transaction_type'] ) ) {
			$where   .= " AND `transaction_type` = %s";
			$values[] = $args['transaction_type'];
		}

		if ( ! empty( $args['gateway'] ) ) {
			$where   .= " AND `gateway` = %s";
			$values[] = $args['gateway'];
		}

		if ( 'DESC' === strtoupper( $args['order'] ) ) {
			$order = 'DESC';
		} else {
			$order = 'ASC';
		}

		$columns = array(
			'id',
			'user_id',
			'subscription',
			'subscription_key',
			'transaction_id',
			'status',
			'date'
		);

		$orderby = array_key_exists( $args['orderby'], $columns ) ? $args['orderby'] : 'id';

		$values[] = absint( $args['offset'] );
		$values[] = absint( $args['number'] );

		$payments = $wpdb->get_results( $wpdb->prepare( "SELECT {$fields} FROM " . $this->db_name . " {$where} ORDER BY {$orderby} {$order} LIMIT %d,%d;", $values ) );

		foreach ( $payments as $key => $payment ) {

			if ( '*' === $fields ) {
				$payment = $this->backfill_payment_data( $payment );
			}

			$payments[ $key ] = $payment;

		}

		return $payments;

	}

	/**
	 * Backfills any missing payment data introduced in RCP 2.9+
	 * and updates the payment record accordingly.
	 *
	 * @access private
	 * @since 2.9
	 *
	 * @param stdClass $payment The payment object.
	 *
	 * @return stdClass The updated payment object.
	 */
	private function backfill_payment_data( $payment ) {

		$data_to_update = array();

		/** Backfill the membership level ID. */
		if ( empty( $payment->object_id ) && ! empty( $payment->subscription ) ) {

			$membership_level = rcp_get_membership_level_by( 'name', $payment->subscription );

			if ( $membership_level instanceof Membership_Level ) {
				$payment->object_id = $membership_level->get_id();
				$data_to_update['object_id'] = $membership_level->get_id();
			}

		}

		/** Backfill the gateway */
		if ( empty( $payment->gateway ) && ! empty( $payment->payment_type ) ) {

			$gateway = $this->get_payment_gateway( $payment );

			if ( ! empty( $gateway ) ) {
				$payment->gateway = $gateway;
				$data_to_update['gateway'] = $gateway;
			}

		}

		/** Backfill empty subtotal */
		if ( ! property_exists( $payment, 'subtotal' ) || '' === $payment->subtotal || null === $payment->subtotal ) {
			$payment->subtotal          = $payment->amount;
			$data_to_update['subtotal'] = $payment->amount;
		}

		/** Backfill empty credits */
		if ( ! property_exists( $payment, 'credits' ) || '' === $payment->credits || null === $payment->credits ) {
			$payment->credits          = 0;
			$data_to_update['credits'] = 0;
		}

		/** Backfill empty fees */
		if ( ! property_exists( $payment, 'fees' ) || '' === $payment->fees || null === $payment->fees ) {
			$payment->fees          = 0;
			$data_to_update['fees'] = 0;
		}

		/** Backfill empty discount_amount */
		if ( ! property_exists( $payment, 'discount_amount' ) || '' === $payment->discount_amount || null === $payment->discount_amount ) {
			$payment->discount_amount          = 0;
			$data_to_update['discount_amount'] = 0;
		}

		/** Backfill customer ID */
		if ( ! empty( $payment->user_id ) && empty( $payment->customer_id ) ) {
			$customer = rcp_get_customer_by_user_id( $payment->user_id );

			if ( ! empty( $customer ) ) {
				$data_to_update['customer_id'] = $customer->get_id();
			}
		}

		if ( ! empty( $data_to_update ) ) {
			$this->update( $payment->id, $data_to_update );
		}

		return $payment;
	}

	/**
	 * Count the total number of payments in the database
	 *
	 * @param   array $args Query arguments to override the defaults.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  int
	 */
	public function count( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'user_id'          => 0,
			'date'             => array(),
			'status'           => '',
			's'                => '',
			'object_id'        => '',
			'object_type'      => '',
			'transaction_type' => '',
			'gateway'          => ''
		);

		$args  = wp_parse_args( $args, $defaults );

		$where  = ' WHERE 1=1 ';
		$values = array();

		// Filter by user ID
		if( ! empty( $args['user_id'] ) ) {

			if( is_array( $args['user_id'] ) ) {
				$user_ids = implode( ',', array_map( 'absint', $args['user_id'] ) );
			} else {
				$user_ids = intval( $args['user_id'] );
			}

			$where .= " AND `user_id` IN( {$user_ids} ) ";

		}

		// Setup the date query
		if( ! empty( $args['date'] ) && is_array( $args['date'] ) ) {

			if ( ! empty( $args['date']['start'] ) || ! empty( $args['date']['end'] ) ) {

				if ( ! empty( $args['date']['start'] ) ) {

					$start    = date( 'Y-m-d 00:00:00', strtotime( $args['date']['start'] ) );
					$where   .= " AND `date` >= %s";
					$values[] = $start;

				}

				if ( ! empty( $args['date']['end'] ) ) {

					$end    = date( 'Y-m-d 23:59:59', strtotime( $args['date']['end'] ) );
					$where   .= " AND `date` <= %s";
					$values[] = $end;

				}

			} else {

				$day        = ! empty( $args['date']['day'] ) ? absint( $args['date']['day'] ) : null;
				$month      = ! empty( $args['date']['month'] ) ? absint( $args['date']['month'] ) : null;
				$year       = ! empty( $args['date']['year'] ) ? absint( $args['date']['year'] ) : null;
				$date_where = '';

				$date_where .= ! is_null( $year ) ? $year . " = YEAR ( date ) " : '';

				if ( ! is_null( $month ) ) {
					$date_where = $month . " = MONTH ( date ) AND " . $date_where;
				}

				if ( ! is_null( $day ) ) {
					$date_where = $day . " = DAY ( date ) AND " . $date_where;
				}

				$where .= " AND (" . $date_where . ")";

			}
		}

		// Filter by status
		if( ! empty( $args['status'] ) ) {

			if( is_array( $args['status'] ) ) {
				$status_count       = count( $args['status'] );
				$status_placeholder = array_fill( 0, $status_count, '%s' );
				$statuses           = implode( ', ', $status_placeholder );

				$where .= "AND `status` IN ( $statuses )";
				$values = $values + $args['status'];
			} else {
				$where   .= "AND `status` = %s";
				$values[] = $args['status'];
			}

		}

		// Search
		if( ! empty( $args['s'] ) ) {

			// Search by email
			if( is_email( $args['s'] ) ) {

				$user = get_user_by( 'email', $args['s'] );

				if ( is_a( $user, 'WP_User' ) ) {
					$where   .= " AND `user_id` = %d";
					$values[] = $user->ID;
				}

			} else {

				// Search by subscription key
				if( strlen( $args['s'] ) == 32 ) {

					$where   .= " AND `subscription_key` = %s";
					$values[] = $args['s'];

				} elseif( rcp_get_membership_level_by( 'name', $args['s'] ) ) {

					// Matching membership level found so search for payments with this level
					$where   .= " AND `subscription` = %s";
					$values[] = $args['s'];
				} else {
					$where   .= " AND `transaction_id` = %s";
					$values[] = $args['s'];
				}
			}

		}

		// Object type
		if ( ! empty( $args['object_type'] ) ) {
			$where   .= " AND `object_type` = %s";
			$values[] = $args['object_type'];
		}

		// Object ID
		if ( ! empty( $args['object_id'] ) ) {
			$where   .= " AND `object_id` = %d AND `object_id` != 0";
			$values[] = $args['object_id'];
		}

		// Transaction type (new, renewal, upgrade, downgrade)
		if ( ! empty( $args['transaction_type'] ) ) {
			$where   .= " AND `transaction_type` = %s";
			$values[] = $args['transaction_type'];
		}

		// Gateway
		if ( ! empty( $args['gateway'] ) ) {
			$where   .= " AND `gateway` = %s";
			$values[] = $args['gateway'];
		}

		$key   = md5( 'rcp_payments_count_' . serialize( $args ) );
		$count = get_transient( $key );

		if( $count === false ) {

			$query = "SELECT COUNT(ID) FROM " . $this->db_name . "{$where};";

			if ( ! empty( $values ) ) {
				$query = $wpdb->prepare( $query, $values );
			}

			$count = $wpdb->get_var( $query );
			set_transient( $key, $count, 10800 );
		}

		return absint( $count );

	}


	/**
	 * Calculate the total earnings of all payments in the database
	 *
	 * @param   array $args Query arguments to override the defaults.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  float
	 */
	public function get_earnings( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'earnings'     => 1, // Just for the cache key
			'subscription' => 0,
			'user_id'      => 0,
			'date'         => array()
		);

		$args = wp_parse_args( $args, $defaults );

		$cache_args = $args;
		$cache_args['user_id'] = is_array( $args['user_id'] ) ? implode( ',', $args['user_id'] ) : $args['user_id'];
		$cache_args['date']    = implode( ',', $args['date'] );
		$cache_key = md5( implode( ',', $cache_args ) );

		$where = ' WHERE 1=1 ';

		// payments for a specific membership level
		if( ! empty( $args['subscription'] ) ) {
			$where   .= " AND `subscription` = %s ";
			$values[] = $args['subscription'];
		}

		// payments for specific users
		if( ! empty( $args['user_id'] ) ) {

			if( is_array( $args['user_id'] ) )
				$user_ids = implode( ',', array_map( 'absint', $args['user_id'] ) );
			else
				$user_ids = intval( $args['user_id'] );

			$where .= " AND `user_id` IN( {$user_ids} ) ";

		}

		// Setup the date query
		if( ! empty( $args['date'] ) && is_array( $args['date'] ) ) {

			if ( ! empty( $args['date']['start'] ) || ! empty( $args['date']['end'] ) ) {

				if ( ! empty( $args['date']['start'] ) ) {

					$start    = date( 'Y-m-d 00:00:00', strtotime( $args['date']['start'] ) );
					$where   .= " AND `date` >= %s";
					$values[] = $start;

				}

				if ( ! empty( $args['date']['end'] ) ) {

					$end    = date( 'Y-m-d 23:59:59', strtotime( $args['date']['end'] ) );
					$where   .= " AND `date` <= %s";
					$values[] = $end;

				}

			} else {

				$day        = ! empty( $args['date']['day'] ) ? absint( $args['date']['day'] ) : null;
				$month      = ! empty( $args['date']['month'] ) ? absint( $args['date']['month'] ) : null;
				$year       = ! empty( $args['date']['year'] ) ? absint( $args['date']['year'] ) : null;
				$date_where = '';

				$date_where .= ! is_null( $year ) ? $year . " = YEAR ( date ) " : '';

				if ( ! is_null( $month ) ) {
					$date_where = $month . " = MONTH ( date ) AND " . $date_where;
				}

				if ( ! is_null( $day ) ) {
					$date_where = $day . " = DAY ( date ) AND " . $date_where;
				}

				$where .= " AND (" . $date_where . ")";

			}
		}

		// Exclude refunded payments
		$where .= " AND ( `status` = 'complete' OR `status` IS NULL OR `status` = '' )";

		$earnings = get_transient( $cache_key );

		if( empty( $earnings ) ) {
			$query = "SELECT SUM(amount) FROM " . $this->db_name . " {$where};";

			if ( ! empty( $values ) ) {
				$query = $wpdb->prepare( $query, $values );
			}

			$earnings = $wpdb->get_var( $query );

			set_transient( $cache_key, $earnings, 3600 );
		}

		$earnings = empty( $earnings ) ? 0 : $earnings;

		return round( $earnings, 2 );

	}


	/**
	 * Calculate the total refunds of all payments in the database
	 *
	 * @param   array $args Query arguments to override the defaults.
	 *
	 * @access  public
	 * @since   2.5
	 * @return  float
	 */
	public function get_refunds( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'refunds'      => 2, // Just for the cache key
			'subscription' => 0,
			'user_id'      => 0,
			'date'         => array()
		);

		$args = wp_parse_args( $args, $defaults );

		$cache_args = $args;
		$cache_args['date'] = implode( ',', $args['date'] );
		$cache_key = md5( implode( ',', $cache_args ) );

		$where = '';

		// refunds for a specific membership level
		if( ! empty( $args['subscription'] ) ) {
			$where .= "WHERE `subscription`= '{$args['subscription']}' ";
		}

		// refunds for specific users
		if( ! empty( $args['user_id'] ) ) {

			if( is_array( $args['user_id'] ) )
				$user_ids = implode( ',', $args['user_id'] );
			else
				$user_ids = intval( $args['user_id'] );

			if( ! empty( $args['subscription'] ) ) {
				$where .= "`user_id` IN( {$user_ids} ) ";
			} else {
				$where .= "WHERE `user_id` IN( {$user_ids} ) ";
			}

		}

		// Setup the date query
		if( ! empty( $args['date'] ) && is_array( $args['date'] ) ) {

			$day   = ! empty( $args['date']['day'] )   ? absint( $args['date']['day'] )   : null;
			$month = ! empty( $args['date']['month'] ) ? absint( $args['date']['month'] ) : null;
			$year  = ! empty( $args['date']['year'] )  ? absint( $args['date']['year'] )  : null;
			$date_where = '';

			$date_where .= ! is_null( $year )  ? $year . " = YEAR ( date ) " : '';

			if( ! is_null( $month ) ) {
				$date_where = $month  . " = MONTH ( date ) AND " . $date_where;
			}

			if( ! is_null( $day ) ) {
				$date_where = $day . " = DAY ( date ) AND " . $date_where;
			}

			if( ! empty( $args['user_id'] ) || ! empty( $args['subscription'] ) ) {
				$where .= "AND (" . $date_where . ") ";
			} else {
				$where .= "WHERE ( " . $date_where . " ) ";
			}
		}

		// Exclude refunded payments
		if( false !== strpos( $where, 'WHERE' ) ) {

			$where .= "AND ( `status` = 'refunded' )";

		} else {

			$where .= "WHERE ( `status` = 'refunded' )";

		}

		$refunds = get_transient( $cache_key );

		if( $refunds === false ) {
			$refunds = $wpdb->get_var( "SELECT SUM(amount) FROM " . $this->db_name . " {$where};" );
			set_transient( $cache_key, $refunds, 3600 );
		}

		return round( $refunds, 2 );

	}


	/**
	 * Retrieves the last payment made by a user
	 *
	 * @param   int $user_id ID of the user to check.
	 *
	 * @access  public
	 * @since   1.5
	 * @return  int|float|false Amount of last payment or false if none is found.
	*/
	public function last_payment_of_user( $user_id = 0 ) {
		global $wpdb;
		$query = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $this->db_name . " WHERE `user_id`='%d' ORDER BY id DESC LIMIT 1;", $user_id ) );
		if( $query )
			return $query[0]->amount;
		return false;
	}

	/**
	 * Retrieve payment meta field for a payment.
	 *
	 * @param   int    $payment_id    Payment ID.
	 * @param   string $meta_key      The meta key to retrieve.
	 * @param   bool   $single        Whether to return a single value.
	 *
	 * @access  public
	 * @since   2.6
	 * @return  mixed                 Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_meta( $payment_id = 0, $meta_key = '', $single = false ) {
		return get_metadata( 'rcp_payment', $payment_id, $meta_key, $single );
	}

	/**
	 * Add meta data field to a payment.
	 *
	 * @param   int    $payment_id    Payment ID.
	 * @param   string $meta_key      Metadata name.
	 * @param   mixed  $meta_value    Metadata value.
	 * @param   bool   $unique        Optional, default is false. Whether the same key should not be added.
	 *
	 * @access  public
	 * @since   2.6
	 * @since 3.5.10                  Removing default values for $payment_id and $meta_key
	 *
	 * @return  bool                  False for failure. True for success.
	 */
	public function add_meta( $payment_id, $meta_key, $meta_value, $unique = false ) {
		return add_metadata( 'rcp_payment', $payment_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update payment meta field based on Payment ID.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with the
	 * same key and Payment ID.
	 *
	 * If the meta field for the payment does not exist, it will be added.
	 *
	 * @param   int    $payment_id    Payment ID.
	 * @param   string $meta_key      Metadata key.
	 * @param   mixed  $meta_value    Metadata value.
	 * @param   mixed  $prev_value    Optional. Previous value to check before removing.
	 *
	 * @access  public
	 * @since   2.6
	 * @since   3.5.10                Removing default value from $payment_id and $meta_key
	 *
	 * @return  bool                  False on failure, true if success.
	 */
	public function update_meta( $payment_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_metadata( 'rcp_payment', $payment_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Remove metadata matching criteria from a payment.
	 *
	 * You can match based on the key, or key and value. Removing based on key and
	 * value, will keep from removing duplicate metadata with the same key. It also
	 * allows removing all metadata matching key, if needed.
	 *
	 * @param   int    $payment_id    Payment ID.
	 * @param   string $meta_key      Metadata name.
	 * @param   mixed  $meta_value    Optional. Metadata value.
	 *
	 * @access  public
	 * @since   2.6
	 * @return  bool                  False for failure. True for success.
	 */
	public function delete_meta( $payment_id = 0, $meta_key = '', $meta_value = '' ) {
		return delete_metadata( 'rcp_payment', $payment_id, $meta_key, $meta_value );
	}

}
