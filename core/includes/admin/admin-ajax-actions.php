<?php
/**
 * Admin Ajax Actions
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Ajax Actions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use RCP\Membership_Level;

/**
 * Retrieves the expiration date of a membership level
 *
 * @uses rcp_calculate_subscription_expiration()
 *
 * @return void
 */
function rcp_ajax_get_subscription_expiration() {
	if ( current_user_can( 'rcp_view_members' ) ) {
		if( isset( $_POST['subscription_level'] ) ) {
			$level_id = absint( $_POST['subscription_level'] );
			$expiration = rcp_calculate_subscription_expiration( $level_id );
			echo $expiration;
		}
	}

	die();
}
add_action( 'wp_ajax_rcp_get_subscription_expiration', 'rcp_ajax_get_subscription_expiration' );

/**
 * Processes the ajax re-ordering request
 *
 * @return void
 */
function rcp_update_subscription_order() {
	if ( current_user_can( 'rcp_view_levels' ) ) {
		if( isset( $_POST['recordsArray'] ) ) {
			global $wpdb, $rcp_db_name;
			$subscription_levels = $_POST['recordsArray'];
			$counter = 1;
			foreach ( $subscription_levels as $level ) {
				$new_order = $wpdb->update(
					$rcp_db_name,
					array('list_order' 	=> $counter ),
					array('id' 			=> $level),
					array('%d')
				);
				$counter++;
			}
			// clear the cache
			delete_transient('rcp_subscription_levels');
		}
	}
	die();
}
add_action( 'wp_ajax_update-subscription-order', 'rcp_update_subscription_order' );

/**
 * Retrieves a list of users via live search
 *
 * @return void
 */
function rcp_search_users() {

	if ( current_user_can( 'rcp_view_members' ) ) {
		if( wp_verify_nonce( $_POST['rcp_nonce'], 'rcp_member_nonce' ) ) {

			$search_query = trim( $_POST['user_name'] );
			$return_field = ! empty( $_POST['return_field'] ) ? sanitize_text_field( $_POST['return_field'] ) : 'user_login';

			$found_users = get_users( array(
					'number' => 9999,
					'search' => $search_query . '*'
				)
			);

			if( $found_users ) {
				$user_list = '<ul>';
				foreach( $found_users as $user ) {
					$user_list .= '<li><a href="#" data-login="' . esc_attr( $user->$return_field ) . '">' . esc_html( $user->$return_field ) . '</a></li>';
				}
				$user_list .= '</ul>';

				echo json_encode( array( 'results' => $user_list, 'id' => 'found' ) );

			} else {
				echo json_encode( array( 'msg' => '<ul><li>' . __( 'No users found', 'rcp' ) . '</li></ul>', 'results' => 'none', 'id' => 'fail' ) );
			}

		}
	}

	die();
}
add_action( 'wp_ajax_rcp_search_users', 'rcp_search_users' );

/**
 * Retrieve the initial and recurring amounts for a membership level.
 *
 * @since 3.0
 * @return void
 */
function rcp_get_membership_billing_cycle_ajax() {

	if ( current_user_can( 'rcp_view_levels' ) ) {
		check_ajax_referer( 'rcp_member_nonce', 'rcp_nonce' );

		$object_id = $_POST['object_id'];

		if ( empty( $object_id ) ) {
			wp_send_json_error();
		}

		$cycle = array(
			'initial_amount'   => 0.00,
			'recurring_amount' => 0.00
		);

		$membership_level = rcp_get_membership_level( $object_id );

		if ( ! $membership_level instanceof Membership_Level ) {
			wp_send_json_error();
		}

		if ( $membership_level->is_free() ) {
			$cycle = 'free';
		} else {
			$cycle['initial_amount']   = $membership_level->get_price() + $membership_level->get_fee();
			$cycle['recurring_amount'] = $membership_level->is_lifetime() ? 0 : $membership_level->get_price();
		}

		wp_send_json_success( $cycle );
	}

	exit;

}
add_action( 'wp_ajax_rcp_get_membership_billing_cycle', 'rcp_get_membership_billing_cycle_ajax' );
