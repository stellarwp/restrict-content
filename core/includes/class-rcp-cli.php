<?php
/**
 * RCP WP-CLI
 *
 * This class provides an integration point with the WP-CLI plugin allowing
 * access to RCP from the command line.
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! class_exists( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command( 'rcp', 'RCP_CLI' );

class RCP_CLI extends WP_CLI_Command {

	/**
	 * RCP_CLI constructor.
	 */
	public function __construct() {

	}

	/**
	 * Run batch processing jobs
	 *
	 * ## OPTIONS
	 *
	 * --id=<job_id>: The ID of the job to run.
	 * --name=<job_name>: The name of the job to run.
	 * --step=<step_number>: A specific step to start on. Default is 0 (beginning).
	 * --force=<boolean>: If the job should be run even if it has been run already.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function batch( $args, $assoc_args ) {

		$job_id   = ! empty( $assoc_args['id'] ) ? $assoc_args['id'] : false;
		$job_name = ! empty( $assoc_args['name'] ) ? $assoc_args['name'] : false;
		$step     = ! empty( $assoc_args['step'] ) ? absint( $assoc_args['step'] ) : 0;
		$force    = isset( $assoc_args['force'] ) ? true : false;
		$start    = time();

		$field = 'id';
		$value = absint( $job_id );

		if ( empty( $value ) ) {
			$field = 'name';
			$value = sanitize_text_field( $job_name );
		}

		if ( empty( $value ) ) {
			WP_CLI::error( __( 'You must specify a job ID with the --id argument or a job name with the --name argument.', 'rcp' ) );
		}

		$job = \RCP\Utils\Batch\get_job_by( $field, $value );

		if ( empty( $job ) ) {
			WP_CLI::error( __( 'Invalid job.', 'rcp' ) );
		}

		if ( $job->is_completed() && ! $force ) {
			WP_CLI::error( sprintf( __( 'The %s job has already been completed. To run it again anyway, use the --force argument.', 'rcp' ), esc_html( $job->get_name() ) ) );
		}

		if ( ! empty( $step ) ) {
			$job->set_step( $step );
		}

		if ( $force ) {
			$job->set_step( 0 );
			$job->set_current_count( 0 );
			$job->set_total_count( 0 );
			$job->set_status( 'incomplete' );
		}

		$total_count = $job->get_callback_object()->get_total_count();
		$per_step    = $job->get_callback_object()->get_amount_per_step();
		$total_steps = round( $total_count / $per_step );
		$progress    = \WP_CLI\Utils\make_progress_bar( sprintf( __( 'Processing Batch Job: %s', 'rcp' ), $job->get_name() ), $total_steps );

		while ( ! $job->is_completed() ) {

			$job->process_batch();
			$progress->tick();

		}

		$progress->finish();

		WP_CLI::log( sprintf( __( 'Job completed in %d seconds.', 'rcp' ), time() - $start ) );
		WP_CLI::log( __( 'Old Records: ', 'rcp' ) . $job->get_total_count() );
		WP_CLI::log( __( 'New Records: ', 'rcp' ) . $job->get_current_count() );

		if ( $job->has_errors() ) {
			WP_CLI::error_multi_line( $job->get_errors() );
		}

	}

	/**
	 * Get or create membership records.
	 *
	 * ## OPTIONS
	 *
	 * --id=<membership_id>: A specific membership ID to retrieve.
	 * --email=<customer_email>: Retrieve the memberships for this customer email.
	 * --create=<number>: The number of arbitrary membership records to create.
	 * --range=<number>: The range (in number of days) to use for the creation period in new memberships. Default 30.
	 * --status=<membership_status>: Status to use for created memberships (active, pending, expired, or cancelled).
	 *                               Default is a random selection.
	 * --level=<membership_level_id>: ID of the membership level to assign. Default is a random one.
	 *
	 * ## EXAMPLES
	 *
	 * wp rcp memberships --id=3
	 * wp rcp memberships --email=support@restrictcontentpro.com
	 * wp rcp memberships --create=100 --range=365 --status=active
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function memberships( $args, $assoc_args ) {

		$membership_id = ! empty( $assoc_args['id'] ) ? absint( $assoc_args['id'] ) : 0;
		$email         = ! empty( $assoc_args['email'] ) ? $assoc_args['email'] : false;
		$create        = ! empty( $assoc_args['create'] ) ? absint( $assoc_args['create'] ) : false;
		$range         = ! empty( $assoc_args['range'] ) ? absint( $assoc_args['range'] ) : 30;
		$status        = ! empty( $assoc_args['status'] ) ? $assoc_args['status'] : false;
		$level_id      = ! empty( $assoc_args['level'] ) ? absint( $assoc_args['level'] ) : 0;

		if ( $create ) {

			/**
			 * Create new membership records.
			 */

			global $wpdb;

			// Disable email notifications.
			remove_action( 'rcp_membership_post_activate', 'rcp_email_on_membership_activation', 10 );
			remove_action( 'rcp_membership_post_cancel', 'rcp_email_on_membership_cancellation', 10 );
			remove_action( 'rcp_transition_membership_status_expired', 'rcp_email_on_membership_expiration', 10 );
			remove_action( 'rcp_update_payment_status_complete', 'rcp_email_payment_received', 100 );

			// Get all the active membership level IDs.
			$level_ids = rcp_get_membership_levels( array( 'status' => 'active', 'fields' => 'id' ) );
			if ( empty( $level_ids ) ) {
				WP_CLI::error( __( 'You must have at least one active membership level.', 'rcp' ) );
			}

			$progress = \WP_CLI\Utils\make_progress_bar( 'Creating memberships', $create );

			for ( $i = 0; $i < $create; $i++ ) {

				if ( ! $email ) {
					// Create a fake email if one wasn't provided.
					$this_email = 'customer-' . uniqid() . '@test.com';
				} else {
					$this_email = $email;
				}

				// Choose a fake date to use for the created date.
				$oldest_time  = strtotime( '-' . $range . ' days', current_time( 'timestamp' ) );
				$newest_time  = current_time( 'timestamp' );
				$timestamp    = rand( $oldest_time, $newest_time );
				$created_date = date( 'Y-m-d H:i:s', $timestamp );

				$user = get_user_by( 'email', $this_email );

				// Create a new user account if one does not exist.
				if ( empty( $user ) ) {
					$fname      = $this->get_fname();
					$lname      = $this->get_lname();
					$domain     = $this->get_domain();
					$tld        = $this->get_tld();
					$this_email = $fname . '.' . $lname . '@' . $domain . '.' . $tld;

					$user_id = wp_insert_user( array(
						'user_login'      => $this_email,
						'user_pass'       => wp_generate_password(),
						'user_email'      => $this_email,
						'first_name'      => $fname,
						'last_name'       => $lname,
						'display_name'    => sprintf( '%s %s', $fname, $lname ),
						'user_registered' => $created_date
					) );

					// Add user meta to designate this as a generated customer so we can deleted it later.
					add_user_meta( $user_id, 'rcp_generated_via_cli', 1 );
				} else {
					$user_id = $user->ID;
				}

				$customer = rcp_get_customer_by_user_id( $user_id );

				// Create a new customer record if one does not exist.
				if ( empty( $customer ) ) {
					$customer_id = rcp_add_customer( array(
						'user_id'         => absint( $user_id ),
						'date_registered' => $created_date
					) );
				} else {
					$customer_id = $customer->get_id();
				}

				// Now add the membership.

				/*
				 * An array of available statuses, each mapped to a number.
				 */
				$statuses = array(
					1 => 'active', // active
					2 => 'pending', // pending
					3 => 'expired', // expired
					4 => 'cancelled', // cancelled
				);

				// Choose a random status if omitted.
				if ( empty( $status ) || ! in_array( $status, $statuses ) ) {
					$this_status = $statuses[rand( 1, 4 )];
				} else {
					$this_status = $status;
				}

				$membership_args = array(
					'customer_id'      => absint( $customer_id ),
					'user_id'          => $user_id,
					'object_id'        => ! empty( $level_id ) ? $level_id : $level_ids[array_rand( $level_ids )], // specified or random membership level ID
					'status'           => $this_status,
					'created_date'     => $created_date,
					'gateway'          => 'manual',
					'subscription_key' => rcp_generate_subscription_key()
				);

				switch ( $membership_args['status'] ) {

					case 'expired' :
						// Set the expiration date to a random date between the start date and yesterday.
						$oldest_time                        = strtotime( $created_date, current_time( 'timestamp' ) );
						$newest_time                        = strtotime( '-1 day', current_time( 'timestamp' ) );
						$timestamp                          = rand( $oldest_time, $newest_time );
						$membership_args['expiration_date'] = date( 'Y-m-d H:i:s', $timestamp );
						break;

					case 'cancelled' :
						// Set the cancellation date to a random date between the start date and yesterday.
						// Set the expiration date to a random date between the start date and yesterday.
						$oldest_time                          = strtotime( $created_date, current_time( 'timestamp' ) );
						$newest_time                          = strtotime( '-1 day', current_time( 'timestamp' ) );
						$timestamp                            = rand( $oldest_time, $newest_time );
						$membership_args['cancellation_date'] = date( 'Y-m-d H:i:s', $timestamp );
						break;

				}

				$membership_id = rcp_add_membership( $membership_args );

				// Add membership meta to designate this as a generated record so we can deleted it later.
				rcp_add_membership_meta( $membership_id, 'rcp_generated_via_cli', 1 );

				$membership = rcp_get_membership( $membership_id );

				// Generate a transaction ID.
				$auth_key       = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
				$transaction_id = strtolower( md5( $membership_args['subscription_key'] . date( 'Y-m-d H:i:s' ) . $auth_key . uniqid( 'rcp', true ) ) );

				// Create a corresponding payment record.
				$payment_args = array(
					'subscription'     => rcp_get_subscription_name( $membership_args['object_id'] ),
					'object_id'        => $membership_args['object_id'],
					'date'             => $membership_args['created_date'],
					'amount'           => $membership->get_initial_amount(),
					'subtotal'         => $membership->get_initial_amount(),
					'user_id'          => $user_id,
					'subscription_key' => $membership_args['subscription_key'],
					'transaction_id'   => $transaction_id,
					'status'           => 'pending' == $membership_args['status'] ? 'pending' : 'complete',
					'gateway'          => 'manual',
					'customer_id'      => $customer_id,
					'membership_id'    => $membership_id
				);

				$rcp_payments = new RCP_Payments();
				$payment_id   = $rcp_payments->insert( $payment_args );

				// Add payment meta to designate this as a generated record so we can delete it later.
				$rcp_payments->add_meta( $payment_id, 'rcp_generated_via_cli', 1 );

				$progress->tick();

			}

			$progress->finish();

			WP_CLI::success( sprintf( _n( 'Created %d membership record', 'Created %d membership records', $create, 'rcp' ), $create ) );

		} elseif ( $membership_id ) {

			/**
			 * Get an individual membership record.
			 */
			$membership = rcp_get_membership( $membership_id );

			if ( empty( $membership ) ) {
				WP_CLI::error( __( 'Invalid membership.', 'rcp' ) );
			}

			$this->print_membership_details( $membership );

		} elseif ( $email ) {

			/**
			 * Get all the memberships associated with a specific user email.
			 */
			$user = get_user_by( 'email', $email );

			if ( empty( $user ) ) {
				WP_CLI::error( __( 'No customer found with this email address.', 'rcp' ) );
			}

			$customer = rcp_get_customer_by_user_id( $user->ID );

			if ( empty( $customer ) ) {
				WP_CLI::error( __( 'No customer found with this email address.', 'rcp' ) );
			}

			$memberships = $customer->get_memberships();

			if ( empty( $memberships ) ) {
				WP_CLI::error( __( 'This customer does not have any memberships.', 'rcp' ) );
			}

			foreach ( $memberships as $membership ) {
				$this->print_membership_details( $membership );
			}

		}

	}

	/**
	 * Get a customer record
	 *
	 * ## OPTIONS
	 *
	 * --id=<customer_id>: A specific customer ID to retrieve.
	 * --email=<customer_email>: A specific customer to retrieve, by their email address.
	 *
	 * ## EXAMPLES
	 *
	 * wp rcp customers --id=3
	 * wp rcp customers --email=support@restrictcontentpro.com
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function customers( $args, $assoc_args ) {

		$customer_id = ! empty( $assoc_args['id'] ) ? absint( $assoc_args['id'] ) : 0;
		$email       = ! empty( $assoc_args['email'] ) ? $assoc_args['email'] : false;
		$customer    = false;
		$user        = false;

		if ( $customer_id ) {
			$customer = rcp_get_customer( $customer_id );
		} elseif ( $email ) {
			$user = get_user_by( 'email', $email );

			if ( empty( $user ) ) {
				WP_CLI::error( __( 'No customer with this email address.', 'rcp' ) );
			}

			$customer = rcp_get_customer_by_user_id( $user->ID );
		}

		if ( empty( $customer ) ) {
			WP_CLI::error( __( 'Invalid customer.', 'rcp' ) );
		}

		if ( empty( $user ) ) {
			$user = get_userdata( $customer->get_user_id() );
		}

		WP_CLI::log( WP_CLI::colorize( '%G' . sprintf( __( 'Customer #%d ( %s )', 'rcp' ), $customer->get_id(), $user->user_email ) . '%N' ) );
		WP_CLI::log( sprintf( __( 'User ID: %d', 'rcp' ), $customer->get_user_id() ) );
		WP_CLI::log( sprintf( __( 'Username: %s', 'rcp' ), $user->user_login ) );
		WP_CLI::log( sprintf( __( 'First Name: %s', 'rcp' ), $user->first_name ) );
		WP_CLI::log( sprintf( __( 'Last Name: %s', 'rcp' ), $user->last_name ) );
		WP_CLI::log( sprintf( __( 'Registration Date: %s', 'rcp' ), $customer->get_date_registered( false ) ) );
		WP_CLI::log( sprintf( __( 'Last Login Date: %s', 'rcp' ), $customer->get_last_login( false ) ) );
		$email_verification = $customer->is_pending_verification() ? __( 'yes', 'rcp' ) : __( 'no', 'rcp' );
		WP_CLI::log( sprintf( __( 'Pending Email Verification: %s', 'rcp' ), $email_verification ) );
		WP_CLI::log( sprintf( __( 'Active Memberships: %d', 'rcp' ), count( $customer->get_memberships( array( 'status' => array( 'active', 'cancelled' ) ) ) ) ) );
		WP_CLI::log( sprintf( __( 'Number of Payments: %d', 'rcp' ), count( $customer->get_payments() ) ) );
		WP_CLI::log( sprintf( __( 'Lifetime Value: %s', 'rcp' ), $customer->get_lifetime_value() ) );


	}

	/**
	 * Delete all data generated by the CLI.
	 *
	 * ## OPTIONS
	 *
	 * --skip-memberships=<boolean>: Whether to skip deleting generated memberships.
	 * --skip-customers=<boolean>: Whether to skip deleting generated customers.
	 * --skip-payments=<boolean>: Whether to skip deleting generated payments.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function delete_generated_data( $args, $assoc_args ) {

		global $wpdb;

		$skip_memberships = ! empty( $assoc_args['skip-memberships'] );
		$skip_customers   = ! empty( $assoc_args['skip-customers'] );
		$skip_payments    = ! empty( $assoc_args['skip-payments'] );

		/*
		 * Payments
		 */
		if ( ! $skip_payments ) {
			$paymentmeta_table_name = rcp_get_payment_meta_db_name();
			$payments               = new RCP_Payments();
			$generated_payments     = $wpdb->get_results( "SELECT * FROM {$paymentmeta_table_name} WHERE meta_key = 'rcp_generated_via_cli'" );

			if ( $generated_payments ) {
				$number_generated_payments = count( $generated_payments );
				$payment_progress          = \WP_CLI\Utils\make_progress_bar( sprintf( _n( 'Deleting %d generated payment', 'Deleting %d generated payments', $number_generated_payments, 'rcp' ), $number_generated_payments ), $number_generated_payments );

				foreach ( $generated_payments as $generated_payment ) {
					$payments->delete( $generated_payment->rcp_payment_id );
					$payments->delete_meta( $generated_payment->rcp_payment_id, 'rcp_generated_via_cli' );
					$payment_progress->tick();
				}
				$payment_progress->finish();
			} else {
				WP_CLI::log( __( 'No payments to delete.', 'rcp' ) );
			}
		}

		/*
		 * Memberships
		 */
		if ( ! $skip_memberships ) {
			$memberships = rcp_get_memberships( array(
				'number'     => 99999999,
				'meta_query' => array(
					array(
						'key'   => 'rcp_generated_via_cli',
						'value' => 1
					)
				)
			) );
			if ( $memberships ) {
				$number_generated_memberships = count( $memberships );

				$membership_progress = \WP_CLI\Utils\make_progress_bar( sprintf( _n( 'Deleting %d generated membership', 'Deleting %d generated memberships', $number_generated_memberships, 'rcp' ), $number_generated_memberships ), $number_generated_memberships );

				foreach ( $memberships as $membership ) {
					if ( rcp_get_membership_meta( $membership->get_id(), 'rcp_generated_via_cli', true ) ) {
						rcp_delete_membership_meta( $membership->get_id(), 'rcp_generated_via_cli' );
						rcp_delete_membership( $membership->get_id() );
					}
					$membership_progress->tick();
				}

				$membership_progress->finish();
			} else {
				WP_CLI::log( __( 'No memberships to delete.', 'rcp' ) );
			}
		}

		/*
		 * Customers (user accounts, actually)
		 */
		if ( ! $skip_customers ) {
			$customers = get_users( array(
				'fields'     => 'ID',
				'meta_key'   => 'rcp_generated_via_cli',
				'meta_value' => 1
			) );

			if ( $customers ) {
				$number_generated_customers = count( $customers );

				$customer_progress = \WP_CLI\Utils\make_progress_bar( sprintf( _n( 'Deleting %d generated customer', 'Deleting %d generated customers', $number_generated_customers, 'rcp' ), $number_generated_customers ), $number_generated_customers );

				foreach ( $customers as $customer_id ) {
					wp_delete_user( $customer_id );
					$customer_progress->tick();
				}

				$customer_progress->finish();
			} else {
				WP_CLI::log( __( 'No customers to delete.', 'rcp' ) );
			}
		}

	}

	/**
	 * Print details about a membership.
	 *
	 * @param RCP_Membership $membership
	 */
	protected function print_membership_details( $membership ) {

		$user_id = $membership->get_user_id();
		$user    = get_userdata( $user_id );

		WP_CLI::log( WP_CLI::colorize( '%G' . sprintf( __( 'Membership #%d ( %s )', 'rcp' ), $membership->get_id(), $user->user_email ) . '%N' ) );
		WP_CLI::log( sprintf( __( 'Customer ID: %d', 'rcp' ), $membership->get_customer_id() ) );
		WP_CLI::log( sprintf( __( 'Created Date: %d', 'rcp' ), $membership->get_created_date( false ) ) );
		WP_CLI::log( sprintf( __( 'Membership Status: %s', 'rcp' ), rcp_print_membership_status( $membership->get_id(), false ) ) );
		WP_CLI::log( sprintf( __( 'Membership Level: %s', 'rcp' ), $membership->get_membership_level_name() ) );
		WP_CLI::log( sprintf( __( 'Billing Cycle: %s', 'rcp' ), html_entity_decode( $membership->get_formatted_billing_cycle() ) ) );
		$maximum_renewals = 0 == $membership->get_maximum_renewals() ? __( 'until cancelled', 'rcp' ) : $membership->get_maximum_renewals();
		WP_CLI::log( sprintf( __( 'Times Billed: %s', 'rcp' ), sprintf( '%d / %s', $membership->get_times_billed(), $maximum_renewals ) ) );
		$auto_renew = $membership->is_recurring() ? __( 'yes', 'rcp' ) : __( 'no', 'rcp' );
		WP_CLI::log( sprintf( __( 'Auto Renew: %s', 'rcp' ), $auto_renew ) );
		WP_CLI::log( sprintf( __( 'Expiration Date: %s', 'rcp' ), $membership->get_expiration_date( false ) ) );
		if ( $membership->get_renewed_date( false ) ) {
			WP_CLI::log( sprintf( __( 'Last Renewed: %s', 'rcp' ), $membership->get_renewed_date( false ) ) );
		}
		if ( 'cancelled' == $membership->get_status() ) {
			WP_CLI::log( sprintf( __( 'Cancellation Date: %s', 'rcp' ), $membership->get_cancellation_date( false ) ) );
		}
		WP_CLI::log( sprintf( __( 'Gateway: %s', 'rcp' ), $membership->get_gateway() ) );

		WP_CLI::log( '' );

	}

	/**
	 * Get a random first name
	 *
	 * @return string
	 */
	protected function get_fname() {
		$names = array(
			'Ilse',
			'Emelda',
			'Aurelio',
			'Chiquita',
			'Cheryl',
			'Norbert',
			'Neville',
			'Wendie',
			'Clint',
			'Synthia',
			'Tobi',
			'Nakita',
			'Marisa',
			'Maybelle',
			'Onie',
			'Donnette',
			'Henry',
			'Sheryll',
			'Leighann',
			'Wilson',
		);

		return $names[rand( 0, ( count( $names ) - 1 ) )];
	}

	/**
	 * Get a random last name
	 *
	 * @return string
	 */
	protected function get_lname() {
		$names = array(
			'Warner',
			'Roush',
			'Lenahan',
			'Theiss',
			'Sack',
			'Troutt',
			'Vanderburg',
			'Lisi',
			'Lemons',
			'Christon',
			'Kogut',
			'Broad',
			'Wernick',
			'Horstmann',
			'Schoenfeld',
			'Dolloff',
			'Murph',
			'Shipp',
			'Hursey',
			'Jacobi',
		);

		return $names[rand( 0, ( count( $names ) - 1 ) )];
	}

	/**
	 * Get domain
	 *
	 * Used for generating a random email address.
	 *
	 * @return string
	 */
	protected function get_domain() {
		$domains = array(
			'example',
			'edd',
			'rcp',
			'affwp',
		);

		return $domains[rand( 0, ( count( $domains ) - 1 ) )];
	}

	/**
	 * Get TLD
	 *
	 * Used for generating a random email address.
	 *
	 * @return string
	 */
	protected function get_tld() {
		$tlds = array(
			'local',
			'test',
			'example',
			'localhost',
			'invalid',
		);

		return $tlds[rand( 0, ( count( $tlds ) - 1 ) )];
	}

}
