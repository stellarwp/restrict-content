<?php
/**
 * Customer Object
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customer class.
 */
#[\AllowDynamicProperties]
class RCP_Customer {

	/**
	 * Customer ID.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Corresponding user ID number.
	 *
	 * @var int
	 */
	protected $user_id = 0;

	/**
	 * Date this person became a customer.
	 *
	 * @var string
	 */
	protected $date_registered = '';

	/**
	 * Email verification status - either `none`, `pending`, or `verified`.
	 *
	 * @var string
	 */
	protected $email_verification = 'none';

	/**
	 * Date this customer last logged in.
	 *
	 * @var string
	 */
	protected $last_login = '';

	/**
	 * Whether or not the customer has trialed before.
	 *
	 * @var null|int
	 */
	protected $has_trialed = null;

	/**
	 * Serialized array of all known IP addresses for this customer.
	 *
	 * @var string
	 */
	protected $ips = '';

	/**
	 * Notes about this customer.
	 *
	 * @var string
	 */
	protected $notes = '';

	/**
	 * Deprecated RCP_Member object for backwards compatibility.
	 *
	 * @var RCP_Member $member
	 */
	protected $member;

	/**
	 * RCP_Customer constructor.
	 *
	 * @param object $customer_object Customer object row from the database.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function __construct( $customer_object = null ) {

		if ( ! is_object( $customer_object ) ) {
			return;
		}

		$this->setup_customer( $customer_object );

	}

	/**
	 * Setup properties.
	 *
	 * @param object $customer_object Row from the database.
	 *
	 * @access private
	 * @since  3.0
	 * @return bool
	 */
	private function setup_customer( $customer_object ) {

		if ( ! is_object( $customer_object ) ) {
			return false;
		}

		$vars = get_object_vars( $customer_object );

		foreach ( $vars as $key => $value ) {
			switch ( $key ) {
				case 'date_registered' :
				case 'last_login' :
					if ( '0000-00-00 00:00:00' === $value || is_null( $value ) ) {
						$value = '';
					}
					break;

				case 'ips' :
					$value = maybe_unserialize( $value );
					break;
			}

			$this->{$key} = $value;
		}

		if ( empty( $this->id ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Update the customer data in the database.
	 *
	 * @param array $data               {
	 *                                  Array of arguments.
	 *
	 * @type int    $user_id            Optional. ID of the corresponding user account.
	 * @type string $date_registered    Optional. Date this customer registered in MySQL format.
	 * @type string $email_verification Optional. Email verification status: `none`, `pending`, `verified`.
	 * @type string $last_login         Optional. Date this customer last logged into their user account.
	 * @type array  $ips                Optional. Array of all known IP addresses for this customer.
	 * @type string $notes              Optional. Customer notes.
	 *                    }
	 *
	 * @access public
	 * @since  3.0
	 * @return bool True if update was successful, false on failure.
	 */
	public function update( $data = array() ) {

		// Remove "notes" for our log because it's annoying.
		$log_data = $data;
		if ( ! empty( $log_data['notes'] ) ) {
			unset( $log_data['notes'] );
		}
		if ( ! empty( $log_data ) ) {
			rcp_log( sprintf( 'Updating customer #%d. New data: %s.', $this->get_id(), var_export( $log_data, true ) ) );
		}

		if ( ! empty( $data['ips'] ) ) {
			$data['ips'] = maybe_serialize( $data['ips'] );
		}

		if ( ! empty( $data['email_verification'] ) && ! in_array( $data['email_verification'], array( 'none', 'pending', 'verified' ) ) ) {
			unset( $data['email_verification'] );
		}

		$customers = new \RCP\Database\Queries\Customer();

		$updated = $customers->update_item( $this->get_id(), $data );

		if ( $updated ) {
			foreach ( $data as $key => $value ) {
				if ( 'ips' === $key ) {
					$this->{$key} = maybe_unserialize( $value );
				} else {
					$this->{$key} = $value;
				}
			}

			return true;
		}

		return false;

	}

	/**
	 * Get the ID of the customer.
	 *
	 * @access public
	 * @since  3.0
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get the ID of the corresponding user account.
	 *
	 * @access public
	 * @since  3.0
	 * @return int
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Returns the deprecated member object for this customer. This is for backwards compatibility only.
	 *
	 * @access public
	 * @since  3.0
	 * @return RCP_Member
	 */
	public function get_member() {

		if ( ! is_object( $this->member ) ) {
			$this->member = new RCP_Member( $this->get_user_id() );
		}

		return $this->member;

	}

	/**
	 * Returns the date the customer registered.
	 *
	 * @param bool $formatted Whether or not to format the returned date.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_date_registered( $formatted = true ) {

		$date_registered = $this->date_registered;

		if ( $formatted && ! empty( $date_registered ) ) {
			$date_registered = date_i18n( get_option( 'date_format' ), strtotime( $date_registered, current_time( 'timestamp' ) ) );
		}

		return $date_registered;

	}

	/**
	 * Returns the email verification status. Will be one of the following:
	 *        `verified` - Email verification was required and they completed it.
	 *        `pending`  - Email verification is required and they've not done it yet.
	 *        `none`     - Email verification was not required for their registration.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_email_verification_status() {
		return $this->email_verification;
	}

	/**
	 * Returns the date the customer last logged in.
	 *
	 * @param bool $formatted Whether or not to format the returned date.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_last_login( $formatted = true ) {

		$last_login = $this->last_login;

		if ( $formatted && ! empty( $last_login ) ) {
			$last_login = date_i18n( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), strtotime( $last_login, current_time( 'timestamp' ) ) );
		}

		return $last_login;

	}

	/**
	 * Returns an array of all known IPs for this customer.
	 *
	 * @see    rcp_log_ip_and_last_login_date()
	 *
	 * @access public
	 * @since  3.0
	 * @return array
	 */
	function get_ips() {

		$ips = is_array( $this->ips ) ? $this->ips : array();

		return $ips;
	}

	/**
	 * Add an IP address to this customer's known IPs.
	 *
	 * @param string $ip IP to add.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	function add_ip( $ip ) {

		$ips = $this->get_ips();

		if ( ! in_array( $ip, $ips ) ) {
			$ips[] = sanitize_text_field( $ip );
		}

		$this->update( array( 'ips' => $ips ) );

	}

	/**
	 * Determines whether or not this customer has ever used a trial.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function has_trialed() {

		$has_trialed = $this->has_trialed;

		if ( ! is_null( $has_trialed ) ) {

			/*
			 * As of RCP 3.1.2, we now store as `has_trialed` flag in the DB.
			 * If this is set, the value will be `1` for yes, they have trialed; or `0` for no they have not.
			 */
			$has_trialed = (bool) $has_trialed;

		} else {

			/*
			 * If `has_trialed` is `null` then that means we haven't set it yet post-upgrade. So let's backfill
			 * the value by querying memberships directly.
			 */
			$memberships = $this->get_memberships( array(
				'status__not_in' => array( 'pending' ), // exclude "pending"
				'disabled'       => '' // include both enabled and disabled memberships
			) );

			$has_trialed = false;

			if ( is_array( $memberships ) ) {
				foreach ( $memberships as $membership ) {
					if ( $membership->get_trial_end_date() ) {
						$has_trialed = true;
						break;
					}
				}
			}

			// Backwards compatibility - check user meta. We have to do this manually because get_user_meta() is routed here.
			if ( ! $has_trialed ) {
				global $wpdb;

				$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key = 'rcp_has_trialed' AND user_id = %d", $this->get_user_id() ) );

				if ( ! empty( $results ) ) {
					$has_trialed = true;
				}
			}

			$this->update( array(
				'has_trialed' => $has_trialed
			) );

		}

		if ( has_filter( 'rcp_has_used_trial' ) ) {
			/**
			 * @deprecated 3.0 Use `rcp_customer_has_trialed` instead.
			 */
			$has_trialed = apply_filters( 'rcp_has_used_trial', $has_trialed, $this->get_user_id() );
		}

		if ( has_filter( 'rcp_member_has_trialed' ) ) {
			/**
			 * @deprecated 3.0 Use `rcp_customer_has_trialed` instead.
			 */
			$has_trialed = apply_filters( 'rcp_member_has_trialed', $has_trialed, $this->get_user_id() );
		}

		/**
		 * Filters whether or not this customer has used a free trial.
		 *
		 * @param bool         $has_trialed Whether or not they've used a free trial.
		 * @param int          $customer_id Customer ID number.
		 * @param RCP_Customer $this        Customer object.
		 *
		 * @since 3.0
		 */
		$has_trialed = apply_filters( 'rcp_customer_has_trialed', $has_trialed, $this->get_id(), $this );

		return $has_trialed;

	}

	/**
	 * Get customer notes.
	 *
	 * @access public
	 * @since  3.0
	 * @return string
	 */
	public function get_notes() {

		$notes = $this->notes;

		if ( has_filter( 'rcp_member_get_notes' ) ) {
			/**
			 * Filters the customer notes.
			 *
			 * @deprecated 3.0 Use `rcp_customer_get_notes` instead.
			 *
			 * @param string     $notes   Notes.
			 * @param int        $user_id ID of the user account.
			 * @param RCP_Member $member  Deprecated member object.
			 */
			$notes = apply_filters( 'rcp_member_get_notes', $notes, $this->get_member()->ID, $this->get_member() );
		}

		/**
		 * Filters the customer notes.
		 *
		 * @param string       $notes       Notes.
		 * @param int          $customer_id Customer ID number.
		 * @param RCP_Customer $this        Customer object.
		 *
		 * @since 3.0
		 */
		$notes = apply_filters( 'rcp_customer_get_notes', $notes, $this->get_id(), $this );

		return $notes;

	}

	/**
	 * Add a new customer note.
	 *
	 * @param string $note New note to add.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function add_note( $note = '' ) {

		$notes = $this->get_notes();

		if ( empty( $notes ) ) {
			$notes = '';
		}

		if ( has_filter( 'rcp_member_pre_add_note' ) ) {
			/**
			 * Filters the note being added.
			 *
			 * @deprecated 3.0 Use `rcp_customer_pre_add_note` instead.
			 */
			$note = apply_filters( 'rcp_member_pre_add_note', $note, $this->get_member()->ID, $this->get_member() );
		}

		/**
		 * Filters the note being added.
		 *
		 * @param string       $note        New note to add.
		 * @param int          $customer_id ID of the customer.
		 * @param RCP_Customer $this        Customer object.
		 *
		 * @since 3.0
		 */
		$note = apply_filters( 'rcp_customer_pre_add_note', $note, $this->get_id(), $this );

		$notes .= "\n\n" . date_i18n( 'F j, Y H:i:s', current_time( 'timestamp' ) ) . ' - ' . $note;

		$this->update( array( 'notes' => $notes ) );

		if ( has_action( 'rcp_member_add_note' ) ) {
			/**
			 * Triggers after the new note is saved.
			 *
			 * @deprecated 3.0 Use `rcp_customer_add_note` instead.
			 */
			do_action( 'rcp_member_add_note', $note, $this->get_member()->ID, $this->get_member() );
		}

		/**
		 * Triggers after the new note is added.
		 *
		 * @param string       $note        New note that was just added.
		 * @param int          $customer_id ID of the customer.
		 * @param RCP_Customer $this        Customer object.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_customer_add_note', $note, $this->get_id(), $this );

		return true;

	}

	/**
	 * Determines whether or not this customer is pending email verification.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function is_pending_verification() {

		$is_pending = 'pending' === $this->email_verification;

		if ( has_filter( 'rcp_is_pending_email_verification' ) ) {
			/**
			 * Filters whether this customer is pending email verification.
			 *
			 * @deprecated 3.0
			 */
			$is_pending = apply_filters( 'rcp_is_pending_email_verification', $is_pending, $this->get_member()->ID, $this->get_member() );
		}

		return $is_pending;

	}

	/**
	 * Mark this customer as having successfully verified their email address.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function verify_email() {

		if ( has_action( 'rcp_member_pre_verify_email' ) ) {
			/**
			 * @deprecated 3.0 Use `rcp_customer_pre_verify_email` instead.
			 */
			do_action( 'rcp_member_pre_verify_email', $this->get_member()->ID, $this->get_member() );
		}

		/**
		 * Triggers before the customer's email is verified.
		 *
		 * @param int          $customer_id ID of the customer.
		 * @param RCP_Customer $this        Customer object.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_customer_pre_verify_email', $this->get_id(), $this );

		$updated = $this->update( array( 'email_verification' => 'verified' ) );

		if ( $updated ) {
			$this->add_note( __( 'Email successfully verified.', 'rcp' ) );

			RCP\Logs\add_log( array(
				'object_type' => 'customer',
				'object_id'   => $this->get_id(),
				'user_id'     => $this->get_user_id(),
				'type'        => 'email_verified',
				'title'       => __( 'Email address verified', 'rcp' )
			) );
		}

		if ( has_action( 'rcp_member_post_verify_email' ) ) {
			/**
			 * @deprecated 3.0 Use `rcp_customer_post_verify_email` instead.
			 */
			do_action( 'rcp_member_post_verify_email', $this->get_member()->ID, $this->get_member() );
		}

		/**
		 * Triggers after the customer's email is verified.
		 *
		 * @param int          $customer_id ID of the customer.
		 * @param RCP_Customer $this        Customer object.
		 *
		 * @since 3.0
		 */
		do_action( 'rcp_customer_post_verify_email', $this->get_id(), $this );

		return $updated;

	}

	/**
	 * Get all the memberships this customer has.
	 *
	 * @param array $args Query arguments. See RCP_Memberships_DB::get_memberships().
	 *
	 * @access public
	 * @since  3.0
	 * @return RCP_Membership[]
	 */
	public function get_memberships( $args = array() ) {

		if ( 0 === $this->get_id() ) {
			return array();
		}

		$defaults = array(
			'customer_id' => $this->get_id(),
			'number'      => 9999
		);

		$args = wp_parse_args( $args, $defaults );

		return rcp_get_memberships( $args );

	}

	/**
	 * Determines whether the customer has at least one active membership.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function has_active_membership() {

		$memberships = $this->get_memberships( array(
			'status' => array( 'active', 'cancelled' )
		) );

		if ( empty( $memberships ) ) {
			return false;
		}

		foreach ( $memberships as $membership ) {
			/**
			 * @var RCP_Membership $membership
			 */
			if ( $membership->is_active() ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Determines whether the customer has at least one paid (and active) membership.
	 *
	 * @param bool $include_trial Whether or not to count trial memberships as paid.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function has_paid_membership( $include_trial = true ) {

		$memberships = $this->get_memberships( array(
			'status' => array( 'active', 'cancelled' )
		) );

		if ( empty( $memberships ) ) {
			return false;
		}

		foreach ( $memberships as $membership ) {
			/**
			 * @var RCP_Membership $membership
			 */
			if ( $membership->is_active() && $membership->is_paid( $include_trial ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Add a new membership for this customer.
	 *
	 * @param array $args Membership arguments.
	 *
	 * @access public
	 * @since  3.0
	 * @return int|false ID of the new membership or false on failure.
	 */
	public function add_membership( $args = array() ) {

		$args['customer_id'] = $this->get_id();
		$args['user_id']     = $this->get_user_id();

		return rcp_add_membership( $args );

	}

	/**
	 * Disable all memberships. This does the following:
	 *
	 *        - Cancels payment profiles to stop recurring billing.
	 *        - Expires memberships so the customer loses access to associated content.
	 *        - Hides the memberships from the customer so they can no longer be renewed or cancelled.
	 *        - This is basically a way of deleting the memberships while still keeping them in the database.
	 *
	 * @param int|array $exclude Membership ID(s) to exclude from disabling.
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function disable_memberships( $exclude = 0 ) {

		rcp_log( sprintf( 'Disabling all memberships for customer #%d except: %s.', $this->get_id(), var_export( $exclude, true ) ) );

		$memberships = $this->get_memberships();

		// No memberships found - bail.
		if ( empty( $memberships ) ) {
			rcp_log( 'No memberships found - exiting' );

			return;
		}

		if ( ! empty( $exclude ) && ! is_array( $exclude ) ) {
			$exclude = array( $exclude );
		}

		foreach ( $memberships as $membership ) {
			/**
			 * @var RCP_Membership $membership
			 */
			if ( ! empty( $exclude ) && in_array( $membership->get_id(), $exclude ) ) {
				continue;
			}
			$membership->disable();
		}

	}

	/**
	 * Check to see if this customer can access a certain post.
	 *
	 * @param int $post_id ID of the post to check access for.
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function can_access( $post_id = 0 ) {

		// Admins always get access.
		if ( user_can( $this->user_id, 'manage_options' ) ) {
			return apply_filters( 'rcp_member_can_access', true, $this->get_member()->ID, $post_id, $this->get_member() );
		}

		// If the post is unrestricted, everyone gets access.
		if ( ! rcp_is_restricted_content( $post_id ) ) {
			return apply_filters( 'rcp_member_can_access', true, $this->get_member()->ID, $post_id, $this->get_member() );
		}

		/*
		 * From this point on we assume the post has some kind of restrictions added.
		 */

		// Invalid customer ID means they aren't a customer, and thus don't get access.
		if ( empty( $this->id ) ) {
			return apply_filters( 'rcp_member_can_access', false, $this->get_member()->ID, $post_id, $this->get_member() );
		}

		// If the user is pending email verification, they don't get access.
		if ( $this->is_pending_verification() ) {
			return apply_filters( 'rcp_member_can_access', false, $this->get_member()->ID, $post_id, $this->get_member() );
		}

		$can_access = false;

		foreach ( $this->get_memberships() as $membership ) {
			/**
			 * @var RCP_Membership $membership
			 */
			if ( $membership->can_access( $post_id ) ) {
				$can_access = true;
				break;
			}
		}

		return apply_filters( 'rcp_member_can_access', $can_access, $this->get_member()->ID, $post_id, $this->get_member() );

	}

	/**
	 * Determines whether the customer has a certain access level. This checks the access of each of their active
	 * memberships.
	 *
	 * @param int $access_level_needed
	 *
	 * @access public
	 * @since  3.0
	 * @return bool
	 */
	public function has_access_level( $access_level_needed = 0 ) {

		$memberships = $this->get_memberships( array(
			'status' => array( 'active', 'cancelled' )
		) );

		if ( empty( $memberships ) ) {
			return 0 === $access_level_needed;
		}

		foreach ( $memberships as $membership ) {
			/**
			 * @var RCP_Membership $membership
			 */
			if ( $membership->has_access_level( $access_level_needed ) ) {
				return true;
			}
		}

		return 0 === $access_level_needed;

	}

	/**
	 * Gets the URL to switch to the user if the User Switching plugin is active.
	 *
	 * @access public
	 * @since  3.0
	 * @return string|false Switch to URL on success, false on failure.
	 */
	public function get_switch_to_url() {

		if ( ! class_exists( 'user_switching' ) ) {
			return false;
		}

		$link = user_switching::maybe_switch_url( new WP_User( $this->get_user_id() ) );
		if ( $link ) {
			$link = add_query_arg( 'redirect_to', urlencode( home_url() ), $link );
			return $link;
		} else {
			return false;
		}

	}

	/**
	 * Get this customer's payments.
	 *
	 * @param array $args Query args to override the defaults.
	 *
	 * @access public
	 * @since  3.0
	 * @return array
	 */
	public function get_payments( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'user_id' => $this->get_user_id()
		) );

		$payments = new RCP_Payments;
		$payments = $payments->get_payments( $args );

		return apply_filters( 'rcp_member_get_payments', $payments, $this->get_member()->ID, $this->get_member() );

	}

	/**
	 * Get the ID of this customer's currently pending payment.
	 *
	 * @deprecated 3.1 Pending payment IDs are now stored in membership meta with the key `pending_payment_id`.
	 * @see rcp_get_membership_meta()
	 *
	 * @access public
	 * @since  3.0
	 * @return int|false ID of the payment or false if not found.
	 */
	public function get_pending_payment_id() {
		return get_user_meta( $this->get_user_id(), 'rcp_pending_payment_id', true );
	}

	/**
	 * Get the lifetime value of the customer
	 *
	 * @access public
	 * @since  3.0
	 * @return int|float
	 */
	public function get_lifetime_value() {

		global $wpdb;

		$table_name = rcp_get_payments_db_name();

		$query = $wpdb->prepare(
			"SELECT SUM( amount ) FROM {$table_name} WHERE customer_id = %d AND status = 'complete'",
			$this->get_id()
		);

		$results = $wpdb->get_var( $query );

		if ( empty( $results ) ) {
			$results = 0;
		}

		return $results;

	}

}
