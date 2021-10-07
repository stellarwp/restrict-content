<?php
/**
 * Add/Edit Membership Reminder
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Reminders/Subscription Reminders View
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */

$reminder_type = isset( $_GET['rcp_reminder_type'] ) ? $_GET['rcp_reminder_type'] : 'renewal';
$notices       = new RCP_Reminders();
$notice_id     = isset( $_GET['notice'] ) ? absint( $_GET['notice'] ) : 0;
$new_notice    = ! isset( $_GET['notice'] );
$default       = array(
	'type'        => $reminder_type,
	'subject'     => '',
	'send_period' => 'today',
	'message'     => '',
	'enabled'     => false,
	'levels'      => array()
);
$notice        = ! $new_notice ? $notices->get_notice( $notice_id ) : $default;
?>
<div class="wrap">
	<h1>
		<?php echo $notice_id ? __( 'Edit Reminder Notice', 'rcp' ) : __( 'Add Reminder Notice', 'rcp' ); ?> -
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rcp-settings#emails' ) ); ?>" class="add-new-h2"><?php _e( 'Go Back', 'rcp' ); ?></a>
	</h1>

	<?php if ( empty( $notice ) ) : ?>
		<div class="error settings-error">
			<p><?php _e( 'Error: Invalid notice ID.', 'rcp' ); ?></p>
		</div>
		<?php
		echo '</div>'; // close .wrap

		return;

	endif; ?>

	<form id="rcp-edit-reminder-notice" method="POST">
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row" valign="top">
					<label for="rcp-notice-type"><?php _e( 'Notice Type', 'rcp' ); ?></label>
				</th>
				<td>
					<select id="rcp-notice-type" name="rcp_reminder_type">
						<?php foreach ( $notices->get_notice_types() as $type => $label ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $type, $notice['type'] ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<p class="description"><?php _e( 'Is this a renewal notice or an expiration notice?', 'rcp' ); ?></p>
					<p class="description">
						<?php _e( '<strong>Expiration notices</strong> are sent to "active" and "cancelled" memberships that <strong>do not</strong> have auto renew enabled. They can be used to inform customers that their memberships will not be automatically renewed and they will need to do a manual renewal to retain access to their content.', 'rcp' ); ?> <br>
						<?php _e( '<strong>Reminder notices</strong> are sent to "active" memberships that <strong>do</strong> have auto renew enabled. They can be used to inform customers that their memberships will be automatically renewed and give them a chance to cancel if they do not wish to continue.', 'rcp' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="rcp-notice-subject"><?php _e( 'Email Subject', 'rcp' ); ?></label>
				</th>
				<td>
					<input type="text" name="rcp_reminder_subject" id="rcp-notice-subject" class="regular-text" value="<?php echo esc_attr( stripslashes( $notice['subject'] ) ); ?>"/>

					<p class="description"><?php _e( 'The subject line of the reminder notice email', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="rcp-notice-period"><?php _e( 'Email Period', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="rcp_reminder_period" id="rcp-notice-period">
						<?php foreach ( $notices->get_notice_periods() as $period => $label ) : ?>
							<option value="<?php echo esc_attr( $period ); ?>"<?php selected( $period, $notice['send_period'] ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<p class="description"><?php _e( 'When should this email be sent?', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="rcp-notice-levels"><?php _e( 'Membership Levels', 'rcp' ); ?></label>
				</th>
				<td>
					<?php
					$levels = rcp_get_membership_levels( array( 'number' => 999 ) );
					if ( $levels ) : ?>
						<?php
						$current = ! empty( $notice['levels'] ) && is_array( $notice['levels'] ) ? $notice['levels'] : array();
						foreach ( $levels as $level ) :
							// Don't bother showing levels that never expire.
							if ( $level->is_lifetime() ) {
								continue;
							}
							?>
							<input type="checkbox" id="rcp-notice-levels-<?php echo esc_attr( $level->get_id() ); ?>" name="rcp_reminder_levels[]" value="<?php echo esc_attr( $level->get_id() ); ?>" <?php checked( true, in_array( $level->get_id(), $current ) ); ?>>
							<label for="rcp-notice-levels-<?php echo esc_attr( $level->get_id() ); ?>"><?php echo esc_html( $level->get_name() ); ?></label>
							<br>
						<?php
						endforeach;
					endif; ?>
					<p class="description"><?php _e( 'The membership levels this reminder will be sent for. Leave blank for all levels.', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="rcp-notice-enabled"><?php _e( 'Enable Notice', 'rcp' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="rcp-notice-enabled" name="rcp_reminder_enabled" value="1" <?php checked( ! empty( $notice['enabled'] ), true ); ?>>
					<span><?php _e( 'Check to enable sending the email. If unchecked, the email won\'t be sent to members.', 'rcp' ); ?></span>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="rcp-notice-message"><?php _e( 'Email Message', 'rcp' ); ?></label>
				</th>
				<td>
					<?php wp_editor( wpautop( wp_kses_post( wptexturize( $notice['message'] ) ) ), 'rcp-notice-message', array( 'textarea_name' => 'rcp_reminder_message' ) ); ?>
					<p class="description"><?php _e( 'The email message to be sent with the reminder notice. The following template tags can be used in the message:', 'rcp' ); ?></p>
					<?php echo rcp_get_emails_tags_list(); ?>
				</td>
			</tr>
			</tbody>
		</table>
		<p class="submit">
			<input type="hidden" name="rcp-action" value="add_edit_reminder_notice"/>
			<input type="hidden" name="notice-id" value="<?php echo ! $new_notice ? esc_attr( $notice_id ) : ''; ?>"/>
			<?php wp_nonce_field( 'rcp_add_edit_reminder', 'rcp_add_edit_reminder_nonce' ); ?>
			<input type="submit" value="<?php echo ! $new_notice ? esc_attr__( 'Edit Reminder Notice', 'rcp' ) : esc_attr__( 'Add Reminder Notice', 'rcp' ); ?>" class="button-primary"/>
		</p>
	</form>
</div>
