<?php
/**
 * Cron Functions
 *
 * Schedules events.
 *
 * @package     Restrict Content Pro
 * @subpackage  Cron Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Set up the following cron job events:
 *
 * Daily: Expired users check
 * @see rcp_check_for_expired_users()
 *
 * Daily: Send expiring soon notices
 * @see rcp_check_for_soon_to_expire_users()
 *
 * Daily: Check and update member counts
 * @see rcp_check_member_counts()
 *
 * @return void
 */
function rcp_setup_cron_jobs() {

	if ( ! wp_next_scheduled( 'rcp_expired_users_check' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'daily', 'rcp_expired_users_check' );
	}

	if ( ! wp_next_scheduled( 'rcp_send_expiring_soon_notice' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'daily', 'rcp_send_expiring_soon_notice' );
	}

	if ( ! wp_next_scheduled( 'rcp_check_member_counts' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'daily', 'rcp_check_member_counts' );
	}

	if ( ! wp_next_scheduled( 'rcp_mark_abandoned_payments' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'daily', 'rcp_mark_abandoned_payments' );
	}
}
add_action('wp', 'rcp_setup_cron_jobs');

/**
 * Check for expired users
 *
 * Runs each day and checks for expired users. If their account has expired, their status
 * is updated to "expired" and, based on settings, they may receive an email.
 *
 * @see rcp_email_on_expiration()
 *
 * @return void
 */
function rcp_check_for_expired_users() {

	rcp_log( 'Starting rcp_check_for_expired_users() cron job.', true );

	$args = array(
		'expiration_date_query' => array(
			'after'  => date( 'Y-m-d H:i:s', strtotime( '-1 month' ) ),
			'before' => current_time( 'mysql' )
		),
		'status' => array( 'active', 'cancelled' ),
		'number' => 99999
	);

	/**
	 * Filters the query arguments.
	 *
	 * @param array $args
	 *
	 * @since 3.1.1
	 */
	$args = apply_filters( 'rcp_check_for_expired_memberships_query_args', $args );

	$expired_memberships = rcp_get_memberships( $args );

	/**
	 * Filters the array of memberships found via the query.
	 *
	 * @param array $expired_memberships Array of RCP_Membership objects.
	 */
	$expired_memberships = apply_filters( 'rcp_check_for_expired_users_members_filter', $expired_memberships );

	if( $expired_memberships ) {
		foreach( $expired_memberships as $key => $membership ) {

			/**
			 * @var RCP_Membership $membership
			 */

			$expiration_date = $membership->get_expiration_time();
			if( $expiration_date && current_time( 'timestamp' ) > $expiration_date ) {
				rcp_log( sprintf( 'Expiring membership #%d via cron job.', $membership->get_id() ) );
				$membership->set_status( 'expired' );
			}
		}
	} else {
		rcp_log( 'No expired memberships found.' );
	}
}
//add_action( 'admin_init', 'rcp_check_for_expired_users' );
add_action( 'rcp_expired_users_check', 'rcp_check_for_expired_users' );

/**
 * Check for soon-to-expire users
 *
 * Runs each day and checks for members that are soon to expire. Based on settings, each
 * member gets sent an expiry notice email.
 *
 * @uses RCP_Reminders
 *
 * @return void
 */
function rcp_check_for_soon_to_expire_users() {

	rcp_log( 'Starting rcp_check_for_soon_to_expire_users() cron job.', true );

	$reminders = new RCP_Reminders();
	$reminders->send_reminders();

}
add_action( 'rcp_send_expiring_soon_notice', 'rcp_check_for_soon_to_expire_users' );

/**
 * Counts the active members on a membership level to ensure counts are accurate.
 *
 * Runs once per day
 *
 * @since 2.6
 *
 * @return void
 */
function rcp_check_member_counts() {

	rcp_log( 'Starting rcp_check_member_counts() cron job.', true );

	$entries_today = rcp_get_membership_count_entries( array(
		'number'             => 1,
		'date_created_query' => array(
			'year'  => date( 'Y' ),
			'month' => date( 'm' ),
			'day'   => date( 'd' )
		)
	) );

	// Bail if we've already added entries today.
	if ( ! empty( $entries_today ) ) {
		rcp_log( 'Exiting rcp_check_member_counts() - counts have already been added today.', true );

		return;
	}

	$levels = rcp_get_membership_levels( array( 'number' => 999 ) );

	if( ! $levels ) {
		return;
	}

	$statuses = array( 'active', 'pending', 'cancelled', 'expired', 'free' );

	foreach( $levels as $level ) {

		$counts = array(
			'active'    => 0,
			'pending'   => 0,
			'cancelled' => 0,
			'expired'   => 0
		);

		foreach( $statuses as $status ) {
			$key = $level->get_id() . '_' . $status . '_member_count';

			// Change "free" to "active".
			if ( 'free' == $status ) {
				$status = 'active';
			}

			$count = rcp_count_memberships( array(
				'status'    => $status,
				'object_id' => $level->get_id()
			) );

			rcp_update_membership_level_meta( $level->get_id(), $key, $count );

			$counts[ $status ] = $count;

		}

		rcp_add_membership_count_entry( array(
			'level_id'        => $level->get_id(),
			'active_count'    => $counts['active'],
			'pending_count'   => $counts['pending'],
			'cancelled_count' => $counts['cancelled'],
			'expired_count'   => $counts['expired']
		) );

	}
}
add_action( 'rcp_check_member_counts', 'rcp_check_member_counts' );

/**
 * Find pending payments that are more than a week old and mark them as abandoned.
 *
 * @since 2.9
 * @return void
 */
function rcp_mark_abandoned_payments() {

	rcp_log( 'Starting rcp_mark_abandoned_payments() cron job.', true );

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	$args = array(
		'fields' => 'id',
		'number' => 9999,
		'status' => 'pending',
		'date'   => array(
			'end' => date( 'Y-m-d', strtotime( '-7 days', current_time( 'timestamp' ) ) )
		)
	);

	$payments = $rcp_payments_db->get_payments( $args );

	if ( $payments ) {
		foreach ( $payments as $payment ) {
			$rcp_payments_db->update( $payment->id, array( 'status' => 'abandoned' ) );
		}
	}

}
add_action( 'rcp_mark_abandoned_payments', 'rcp_mark_abandoned_payments' );
