<?php
/**
 * Subscription Reminders Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Reminders/Subscription Reminders
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */

use RCP\Membership_Level;

/**
 * Removes the Subscription Reminder menu link
 *
 * @since 2.9
 * @return void
 */
function rcp_hide_reminder_page() {
	remove_submenu_page( 'rcp-members', 'rcp-reminder' );
}

/**
 * Renders the add/edit subscription reminder notice screen
 *
 * @since 2.9
 * @return void
 */
function rcp_subscription_reminder_page() {

	include RCP_PLUGIN_DIR . 'core/includes/admin/reminders/subscription-reminder-view.php';

}

/**
 * Display subscription reminder table
 *
 * @param string $type Type to display (expiration or renewal).
 *
 * @since 2.9
 * @return void
 */
function rcp_subscription_reminder_table( $type = 'expiration' ) {

	$reminders  = new RCP_Reminders();
	$notices    = $reminders->get_notices( $type );
	$type_label = ( 'expiration' == $type ) ? __( 'Expiration', 'rcp' ) : __( 'Renewal', 'rcp' );
	?>
	<table id="rcp-<?php echo esc_attr( $type ); ?>-reminders" class="wp-list-table widefat fixed posts rcp-email-reminders">
		<thead>
		<tr>
			<th scope="col" class="rcp-reminder-subject-col"><?php _e( 'Subject', 'rcp' ); ?></th>
			<th scope="col" class="rcp-reminder-period-col"><?php _e( 'Send Period', 'rcp' ); ?></th>
			<th scope="col" class="rcp-reminder-status"><?php _e( 'Status', 'rcp' ); ?></th>
			<th scope="col" class="rcp-reminder-levels"><?php _e( 'Level(s)', 'rcp' ); ?></th>
			<th scope="col" class="rcp-reminder-action-col"><?php _e( 'Actions', 'rcp' ); ?></th>
		</tr>
		</thead>
		<?php if ( ! empty( $notices ) ) : $i = 1; ?>
			<?php foreach ( $notices as $key => $notice ) : $notice = $reminders->get_notice( $key ); ?>
				<tr<?php echo ( 0 == $i % 2 ) ? ' class="alternate"' : ''; ?>>
					<td><?php echo esc_html( stripslashes( $notice['subject'] ) ); ?></td>
					<td><?php echo esc_html( $reminders->get_notice_period_label( $key ) ); ?></td>
					<td><?php echo ! empty( $notice['enabled'] ) ? __( 'Enabled', 'rcp' ) : __( 'Disabled', 'rcp' ); ?></td>
					<td>
						<?php
						$levels = ! empty( $notice['levels'] ) && is_array( $notice['levels'] ) ? $notice['levels'] : array();
						if ( ! empty( $levels ) && count( $levels ) > 1 ) {
							esc_html_e( 'Multiple Levels', 'rcp' );
						} elseif ( is_array( $levels ) && count( $levels ) == 1 ) {
							$this_level = rcp_get_membership_level( $levels );
							echo $this_level instanceof Membership_Level ? esc_html( $this_level->get_name() ) : '';
						} else {
							esc_html_e( 'All Levels', 'rcp' );
						}
						?>
					</td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rcp-reminder&rcp-action=edit_subscription_reminder&notice=' . $key ) ); ?>" class="rcp-edit-reminder-notice" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Edit', 'rcp' ); ?></a>&nbsp;|
						<a href="<?php echo esc_url( add_query_arg( array( 'rcp_preview_email' => urlencode( $key ) ), home_url() ) ); ?>" class="rcp-preview-reminder-notice" target="_blank"><?php _e( 'Preview', 'rcp' ); ?></a> |
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rcp-reminder&rcp-action=send_test_reminder&notice-id=' . $key ), 'rcp_send_test_reminder' ) ); ?>" class="rcp-send-test-reminder-notice"><?php _e( 'Send Test Email', 'rcp' ); ?></a>&nbsp;|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rcp-reminder&rcp-action=delete_subscription_reminder&notice-id=' . $key ), 'rcp_delete_reminder_notice' ) ); ?>" class="rcp-delete rcp-delete-reminder"><?php _e( 'Delete', 'rcp' ); ?></a>
					</td>
				</tr>
				<?php $i ++; endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="3"><?php _e( 'No reminders set.', 'rcp' ); ?></td>
			</tr>
		<?php endif; ?>
	</table>
	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rcp-reminder&rcp-action=add_subscription_reminder&rcp_reminder_type=' . urlencode( $type ) ) ); ?>" class="button-secondary" id="rcp-add-renewal-notice"><?php printf( __( 'Add %s Reminder', 'rcp' ), $type_label ); ?></a>
	</p>
	<?php

}

/**
 * Add or edit reminder notice
 *
 * @since 2.9
 * @return void
 */
function rcp_process_add_edit_reminder_notice() {

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		wp_die( __( 'You do not have permission to add reminder notices', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $_POST['rcp_add_edit_reminder_nonce'], 'rcp_add_edit_reminder' ) ) {
		wp_die( __( 'Nonce verification failed', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	$notice_id = $_POST['notice-id']; // We're adding a new notice if this is an empty string.
	$subject   = isset( $_POST['rcp_reminder_subject'] ) ? sanitize_text_field( $_POST['rcp_reminder_subject'] ) : __( 'Your Subscription is About to Renew', 'rcp' );
	$period    = isset( $_POST['rcp_reminder_period'] ) ? sanitize_text_field( $_POST['rcp_reminder_period'] ) : '+1month';
	$message   = isset( $_POST['rcp_reminder_message'] ) ? wp_kses( stripslashes( $_POST['rcp_reminder_message'] ), wp_kses_allowed_html( 'post' ) ) : false;
	$type      = isset( $_POST['rcp_reminder_type'] ) ? sanitize_text_field( $_POST['rcp_reminder_type'] ) : 'renewal';
	$enabled   = isset( $_POST['rcp_reminder_enabled'] );
	$levels    = isset( $_POST['rcp_reminder_levels'] ) && is_array( $_POST['rcp_reminder_levels'] ) ? array_map( 'absint', $_POST['rcp_reminder_levels'] ) : '';

	// Disable message if subject and/or message are empty.
	if ( $enabled && ( empty( $message ) || empty( $subject ) ) ) {
		$enabled = false;
	}

	$reminders = new RCP_Reminders();
	$notices   = $reminders->get_notices();
	$settings  = array(
		'subject'     => $subject,
		'message'     => $message,
		'send_period' => $period,
		'type'        => $type,
		'enabled'     => $enabled,
		'levels'      => $levels
	);

	if ( '' != $notice_id ) {
		$notices[ absint( $notice_id ) ] = $settings;
		$redirect_url                    = admin_url( 'admin.php?page=rcp-settings&rcp_message=reminder_updated#emails' );
	} else {
		$notices[]    = $settings;
		$redirect_url = admin_url( 'admin.php?page=rcp-settings&rcp_message=reminder_added#emails' );
	}

	update_option( 'rcp_reminder_notices', $notices );

	wp_safe_redirect( $redirect_url );
	exit;

}

add_action( 'rcp_action_add_edit_reminder_notice', 'rcp_process_add_edit_reminder_notice' );

/**
 * Delete a reminder notice
 *
 * @since 2.9
 * @return void
 */
function rcp_process_delete_reminder_notice() {

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		wp_die( __( 'You do not have permission to delete reminder notices', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp_delete_reminder_notice' ) ) {
		wp_die( __( 'Nonce verification failed', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 401 ) );
	}

	if ( empty( $_GET['notice-id'] ) && 0 !== (int) $_GET['notice-id'] ) {
		wp_die( __( 'No reminder notice ID was provided', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$reminders = new RCP_Reminders();
	$notices   = $reminders->get_notices();
	unset( $notices[ absint( $_GET['notice-id'] ) ] );

	update_option( 'rcp_reminder_notices', $notices );

	wp_safe_redirect( admin_url( 'admin.php?page=rcp-settings&rcp_message=reminder_deleted#emails' ) );
	exit;

}

add_action( 'rcp_action_delete_subscription_reminder', 'rcp_process_delete_reminder_notice' );

/**
 * Send a test reminder notice
 *
 * @since 2.9
 * @return void
 */
function rcp_process_send_test_reminder_notice() {

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		wp_die( __( 'You do not have permission to send test reminder notices', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp_send_test_reminder' ) ) {
		wp_die( __( 'Nonce verification failed', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 401 ) );
	}

	if ( empty( $_GET['notice-id'] ) && 0 !== (int) $_GET['notice-id'] ) {
		wp_die( __( 'No reminder notice ID was provided', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$reminders = new RCP_Reminders();
	$reminders->send_test_notice( absint( $_GET['notice-id'] ) );

	wp_safe_redirect( admin_url( 'admin.php?page=rcp-settings&rcp_message=test_reminder_sent#emails' ) );
	exit;

}

add_action( 'rcp_action_send_test_reminder', 'rcp_process_send_test_reminder_notice' );
