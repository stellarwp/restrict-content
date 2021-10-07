<?php
/**
 * Backwards Compatibility Handler for Members
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

namespace RCP\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Member
 *
 * @package RCP\Compat
 *
 * @since   3.0
 */
class Member extends Base {

	/**
	 * User meta keys used in RCP.
	 *
	 * @var array
	 */
	protected $meta_keys = array(
		'rcp_expiration',
		'rcp_has_trialed',
		'rcp_is_trialing',
		'rcp_joined_date_', // @todo
		'rcp_merchant_subscription_id',
		'rcp_notes',
		'rcp_payment_profile_id',
		// 'rcp_pending_email_verification', // still using this
		// 'rcp_pending_subscription_level', // we're still using this for backwards compat
		'rcp_recurring',
		'rcp_signup_method',
		'rcp_status',
		'rcp_subscription_level',
		'rcp_subscription_key',
	);

	/**
	 * Backwards compatibility hooks for members.
	 *
	 * @access protected
	 * @since  3.0
	 * @return void
	 */
	protected function hooks() {

		add_filter( 'get_user_metadata', array( $this, 'get_user_meta' ), 99, 4 );
		add_filter( 'update_user_metadata', array( $this, 'update_user_meta' ), 99, 5 );
		add_filter( 'add_user_metadata', array( $this, 'update_user_meta' ), 99, 5 );
		add_action( 'pre_get_users', array( $this, 'pre_get_users' ), 99 );

	}

	/**
	 * Backwards compatibility filters for get_user_meta() calls on users.
	 *
	 * @param mixed  $value     The value get_post_meta would return if we don't filter.
	 * @param int    $object_id The object ID user meta was requested for.
	 * @param string $meta_key  The meta key requested.
	 * @param bool   $single    If a single value or an array of the value is requested.
	 *
	 * @access public
	 * @since  3.0
	 * @return mixed
	 */
	public function get_user_meta( $value, $object_id, $meta_key, $single ) {

		if ( 'get_user_metadata' !== current_filter() ) {
			$message = __( 'This function is not meant to be called directly. It is only here for backwards compatibility purposes.', 'rcp' );
			_doing_it_wrong( __FUNCTION__, esc_html( $message ), 'RCP 3.0' );
		}

		if ( ! in_array( $meta_key, $this->meta_keys ) ) {
			return $value;
		}

		$customer = rcp_get_customer_by_user_id( $object_id );

		// No customer found - bail.
		if ( empty( $customer ) ) {
			return $value;
		}

		$membership = rcp_get_customer_single_membership( $customer->get_id() );

		// No membership found - bail.
		if ( empty( $membership ) ) {
			return $value;
		}

		switch ( $meta_key ) {
			case 'rcp_expiration' :
				$value = $membership->get_expiration_date( false );
				break;
			case 'rcp_has_trialed' :
				$value = $customer->has_trialed();
				break;
			case 'rcp_is_trialing' :
				$value = $membership->is_trialing();
				break;
			case 'rcp_merchant_subscription_id' :
				$value = $membership->get_gateway_subscription_id();
				break;
			case 'rcp_notes' :
				$value = $customer->get_notes();
				break;
			case 'rcp_payment_profile_id' :
				$value = $membership->get_gateway_customer_id();
				break;
			case 'rcp_pending_email_verification' :
				$value = $customer->is_pending_verification();
				break;
			case 'rcp_recurring' :
				$value = $membership->is_recurring();
				break;
			case 'rcp_signup_method' :
				$value = $membership->get_signup_method();
				break;
			case 'rcp_status' :
				$value = $membership->get_status();
				break;
			case 'rcp_subscription_level' :
				$value = $membership->get_object_id();
				break;
			case 'rcp_subscription_key' :
				$value = '';
				$membership->get_subscription_key();
				break;
		}

		if ( $this->show_notices ) {
			$message = __( 'All user meta has been <strong>deprecated</strong> since Restrict Content Pro 3.0! Use the <code>RCP_Membership</code> class instead.', 'rcp' );
			_doing_it_wrong( 'get_user_meta()', $message, 'RCP 3.0' );
			if ( $this->show_backtrace ) {
				$backtrace = debug_backtrace();
				trigger_error( print_r( $backtrace, 1 ) );
			}
		}

		return $value;

	}

	/**
	 * Listen for calls to update_user_meta() for members and see if we need to filter them.
	 *
	 * @param null|bool $check      Whether to allow updating metadata for the given type.
	 * @param int       $object_id  Object ID.
	 * @param string    $meta_key   Meta key.
	 * @param mixed     $meta_value Meta value. Must be serializable if non-scalar.
	 * @param mixed     $prev_value Optional. If specified, only update existing metadata entries with the specified
	 *                              value. Otherwise, update all entries.
	 *
	 * @access public
	 * @since  3.0
	 * @return mixed  Returns 'null' if no action should be taken and WordPress core can continue, or non-null to avoid
	 *                usermeta.
	 */
	public function update_user_meta( $check, $object_id, $meta_key, $meta_value, $prev_value ) {

		if ( ! in_array( $meta_key, $this->meta_keys ) ) {
			return $check;
		}

		if ( 'update_user_metadata' !== current_filter() ) {
			$message = __( 'This function is not meant to be called directly. It is only here for backwards compatibility purposes.', 'rcp' );
			_doing_it_wrong( __FUNCTION__, esc_html( $message ), 'RCP 3.0' );
		}

		$customer = rcp_get_customer_by_user_id( $object_id );

		// No customer found - let's create one.
		if ( empty( $customer ) ) {
			$customer_id = rcp_add_customer( array(
				'user_id' => absint( $object_id )
			) );

			// If creation failed - bail.
			if ( empty( $customer_id ) ) {
				return $check;
			}

			$customer = rcp_get_customer( $customer_id );
		}

		$membership = rcp_get_customer_single_membership( $customer->get_id() );

		$class = $column = '';

		switch ( $meta_key ) {
			case 'rcp_expiration' :
				$column = 'expiration_date';
				$class  = 'membership';
				break;
			case 'rcp_merchant_subscription_id' :
				$column = 'gateway_subscription_id';
				$class  = 'membership';
				break;
			case 'rcp_notes' :
				$column = 'notes';
				$class  = 'customer';
				break;
			case 'rcp_payment_profile_id' :
				$column = 'gateway_customer_id';
				$class  = 'membership';
				break;
			case 'rcp_pending_email_verification' :
				$column = 'email_verification';
				$class  = 'customer';

				if ( ! empty( $meta_value ) ) {
					$meta_value = 'pending';
				} else {
					$meta_value = 'verified';
				}
				break;
			case 'rcp_recurring' :
				$column = 'auto_renew';
				$class  = 'membership';

				$meta_value = ! empty( $meta_value );
				break;
			case 'rcp_signup_method' :
				$column = 'signup_method';
				$class  = 'membership';
				break;
			case 'rcp_status' :
				$column = 'status';
				$class  = 'membership';
				break;
			case 'rcp_subscription_level' :
				$column = 'object_id';
				$class  = 'membership';
				break;
			case 'rcp_subscription_key' :
				$column = 'subscription_key';
				$class  = 'membership';
				break;
		}

		if ( 'membership' === $class ) {

			// Update or create membership.

			if ( ! empty( $membership ) ) {
				$check = $membership->update( array( $column => $meta_value ) );
			} else {
				$membership_id = rcp_add_membership( array(
					'customer_id' => $customer->get_id(),
					'user_id'     => $customer->get_user_id(),
					$column       => $meta_value
				) );

				$check = ! empty( $membership_id );
			}

		} elseif ( 'customer' === $class ) {

			$check = rcp_update_customer( $customer->get_id(), array( $column => $meta_value ) );

		}

		if ( $this->show_notices ) {
			$message = __( 'All user meta has been <strong>deprecated</strong> since Restrict Content Pro 3.0! Use the <code>RCP_Membership</code> class instead.', 'rcp' );
			_doing_it_wrong( 'update_user_meta()', $message, 'RCP 3.0' );
			if ( $this->show_backtrace ) {
				$backtrace = debug_backtrace();
				trigger_error( print_r( $backtrace, 1 ) );
			}
		}

		return $check;

	}

	/**
	 * Cleans up a meta query to put all RCP keys in a simplified, consistent format.
	 *
	 * @param array $queries
	 *
	 * @access public
	 * @since  3.0
	 * @return array
	 */
	protected function clean_meta_query( $queries ) {

		$clean_queries = array();

		if ( ! is_array( $queries ) ) {
			return $clean_queries;
		}

		foreach ( $queries as $key => $query ) {
			if ( 'relation' === $key ) {
				$relation = $query;

			} elseif ( ! is_array( $query ) ) {
				continue;

				// First-order clause.
			} elseif ( isset( $query['key'] ) || isset( $query['value'] ) ) {
				if ( isset( $query['value'] ) && array() === $query['value'] ) {
					unset( $query['value'] );
				}

				if ( in_array( $query['key'], $this->meta_keys ) ) {
					$clean_queries[$key] = $query;
				}

				// Otherwise, it's a nested query, so we recurse.
			} else {
				$cleaned_query = $this->clean_meta_query( $query );

				if ( ! empty( $cleaned_query ) ) {
					$clean_queries = $clean_queries + $cleaned_query;
				}
			}
		}

		return $clean_queries;

	}

	/**
	 * Modify `WP_User_Query` to catch out searches including RCP meta. If RCP meta is present then we do a Memberships
	 * query for the same arguments, get the corresponding user IDs for those memberships, and include those in the
	 * `WP_User_Query` here instead of the meta.
	 *
	 * @param \WP_User_Query $user_query
	 *
	 * @access public
	 * @since  3.0
	 * @return void
	 */
	public function pre_get_users( $user_query ) {

		$meta_query = new \WP_Meta_Query();
		$meta_query->parse_query_vars( $user_query->query_vars );

		$rcp_meta = $this->clean_meta_query( $meta_query->queries );
		$user_ids = array();

		// No RCP meta here! Exit!
		if ( empty( $rcp_meta ) ) {
			return;
		}

		$membership_args = array( 'number' => 9999 );
		$paid            = false;

		// Set up the membership query args.
		foreach ( $rcp_meta as $query_key => $meta ) {
			switch ( $meta['key'] ) {
				case 'rcp_expiration' :
					$membership_args['expiration_date'] = $meta['value'];
					unset( $user_query->query_vars['meta_query'][$query_key] );
					break;
				case 'rcp_has_trialed' :
					$membership_args['trial_end_date'] = array(
						'after' => '0000-00-00 00:00:00'
					);
					unset( $user_query->query_vars['meta_query'][$query_key] );
					break;
				case 'rcp_is_trialing' :
					$membership_args['trial_end_date'] = array(
						'after' => current_time( 'mysql' )
					);
					unset( $user_query->query_vars['meta_query'][$query_key] );
					break;
				case 'rcp_join_date_' :
					// @todo
					break;
				case 'rcp_merchant_subscription_id' :
				case 'rcp_payment_profile_id' :
					$membership_args['gateway_id'] = $meta['value'];
					unset( $user_query->query_vars['meta_query'][$query_key] );
					break;
				case 'rcp_notes' :
					// @todo
					break;
				case 'rcp_pending_email_verification' :
					// @todo
					break;
				case 'rcp_recurring' :
					$membership_args['auto_renew'] = $meta['value'];
					unset( $user_query->query_vars['meta_query'][$query_key] );
					break;
				case 'rcp_signup_method' :
					$membership_args['signup_method'] = $meta['value'];
					unset( $user_query->query_vars['meta_query'][$query_key] );
					break;
				case 'rcp_status' :
					$status = $meta['value'];
					if ( is_array( $status ) ) {
						if ( ! empty( $meta['compare'] ) && in_array( $meta['compare'], array( '!=', 'NOT IN' ) ) ) {
							$membership_args['status__not_in'] = $status;
						} else {
							if ( in_array( 'active', $status ) ) {
								$paid = true;
							}

							$membership_args['status__in'] = $status;
						}
					} else {
						if ( ! empty( $meta['compare'] ) && in_array( $meta['compare'], array( '!=', 'NOT IN' ) ) ) {
							$membership_args['status__not_in'] = array( $status );
						} else {
							if ( 'active' == $status ) {
								$paid = true;
							}
							$membership_args['status'] = $status;
						}
					}
					unset( $user_query->query_vars['meta_query'][$query_key] );
					break;
				case 'rcp_subscription_level' :
					$membership_args['object_id'] = $meta['value'];
					unset( $user_query->query_vars['meta_query'][$query_key] );
					break;
				case 'rcp_subscription_key' :
					$membership_args['subscription_key'] = $meta['value'];
					unset( $user_query->query_vars['meta_query'][$query_key] );
					break;
			}
		}

		if ( empty( $membership_args ) ) {
			return;
		}

		$memberships = rcp_get_memberships( $membership_args );

		if ( empty( $memberships ) ) {
			// @todo
			return;
		}

		foreach ( $memberships as $membership ) {
			/**
			 * @var \RCP_Membership $membership
			 */
			$customer_id = $membership->get_customer_id();
			$customer    = rcp_get_customer( $customer_id );
			$user_id     = ! empty( $customer ) ? $customer->get_user_id() : 0;

			if ( ! empty( $user_id ) && ( ! $paid || ( $paid && $membership->is_paid() ) ) ) {
				$user_ids[] = absint( $user_id );
			}
		}

		if ( ! empty( $user_ids ) ) {
			$user_query->set( 'include', $user_ids );
		}

	}

}

new Member();
