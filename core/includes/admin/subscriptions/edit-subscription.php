<?php
/**
 * Edit Membership Level Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Edit Membership Level
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use RCP\Membership_Level;

$level = rcp_get_membership_level( absint( urldecode( $_GET['edit_subscription'] ) ) );

if ( ! $level instanceof Membership_Level ) {
	wp_die( __( 'Invalid membership level.', 'rcp' ) );
}
?>
<h1>
	<?php printf( __( 'Edit Membership Level: %s', 'rcp' ), esc_html( $level->get_name() ) ); ?>
	<a href="<?php echo admin_url( '/admin.php?page=rcp-member-levels' ); ?>" class="add-new-h2">
		<?php _e( 'Cancel', 'rcp' ); ?>
	</a>
</h1>
<form id="rcp-edit-subscription" action="" method="post">
	<table class="form-table">
		<tbody>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rcp-name"><?php _e( 'Name', 'rcp' ); ?></label>
				</th>
				<td>
					<input name="name" id="rcp-name" type="text" value="<?php echo esc_attr( $level->get_name() ); ?>"/>
					<p class="description"><?php _e( 'The name of this membership level. This is shown on the registration page.', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rcp-description"><?php _e( 'Description', 'rcp' ); ?></label>
				</th>
				<td>
					<textarea name="description" id="rcp-description"><?php echo esc_textarea( $level->get_description() ); ?></textarea>
					<p class="description"><?php _e( 'The description of this membership level. This is shown on the registration page.', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rcp-level"><?php _e( 'Access Level', 'rcp' ); ?></label>
				</th>
				<td>
					<select id="rcp-level" name="level">
						<?php
						foreach( rcp_get_access_levels() as $access ) {
							echo '<option value="' . absint( $access ) . '" ' . selected( $access, $level->get_access_level(), false ) . '">' . esc_html( $access ) . '</option>';
						}
						?>
					</select>
					<p class="description"><?php _e( 'Level of access this membership gives.', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rcp-duration"><?php _e( 'Duration', 'rcp' ); ?></label>
				</th>
				<td>
					<input type="text" id="rcp-duration" name="duration" value="<?php echo absint( $level->get_duration() ); ?>"/>
					<select name="duration_unit" id="rcp-duration-unit">
						<option value="day" <?php selected( $level->get_duration_unit(), 'day' ); ?>><?php _e( 'Day(s)', 'rcp' ); ?></option>
						<option value="month" <?php selected( $level->get_duration_unit(), 'month' ); ?>><?php _e( 'Month(s)', 'rcp' ); ?></option>
						<option value="year" <?php selected( $level->get_duration_unit(), 'year' ); ?>><?php _e( 'Year(s)', 'rcp' ); ?></option>
					</select>
					<p class="description"><?php _e( 'Length of time for this membership level. Enter 0 for unlimited.', 'rcp' ); ?></p>
				</td>
			</tr>
			<?php
				/**
				 * Action to add the free trial input fields
				 * @param Membership_Level $level
				 */
				do_action( 'rcp_edit_subscription_after_set_trial_duration', $level );
			?>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rcp-maximum-renewals-setting"><?php _e( 'Maximum Renewals', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="maximum_renewals_setting" id="rcp-maximum-renewals-setting">
						<option value="forever" <?php selected( ! $level->get_maximum_renewals() ); ?>><?php _e( 'Until Cancelled', 'rcp' ); ?></option>
						<option value="specific" <?php selected( $level->get_maximum_renewals() > 0 ); ?>><?php _e( 'Specific Number', 'rcp' ); ?></option>
					</select>
					<label for="rcp-maximum-renewals" class="screen-reader-text"><?php _e( 'Enter the maximum number of renewals', 'rcp' ); ?></label>
					<input type="number" id="rcp-maximum-renewals" name="maximum_renewals" value="<?php echo esc_attr( $level->get_maximum_renewals() ); ?>"<?php echo empty( $level->get_maximum_renewals() ) ? ' style="display: none;"' : ''; ?> min="0" oninput="validatePositiveNumber(this)"/>
					<p class="description">
						<?php _e( 'Number of renewals to process after the first payment. Must be greater than zero.', 'rcp' ); ?>
						<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( '<strong>Until Cancelled</strong>: will continue billing the member indefinitely, or until they cancel their membership. <br/><br/><strong>Specific Number</strong> will allow you to enter the number of additional times you wish to bill the customer after their first payment. If you enter "3", the member will be billed once immediately when they sign up, then 3 more times after that. Then billing will stop automatically.', 'rcp' ); ?>"></span>
					</p>
				</td>
			</tr>
			<tr class="form-field"<?php echo empty( $level->get_maximum_renewals() ) ? ' style="display: none;"' : ''; ?>>
				<th scope="row" valign="top">
					<label for="rcp-after-final-payment"><?php _e( 'After Final Payment', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="after_final_payment" id="rcp-after-final-payment">
						<option value="lifetime" <?php selected( $level->get_after_final_payment(), 'lifetime' ); ?>><?php _e( 'Grant Lifetime Access', 'rcp' ); ?></option>
						<option value="expire_immediately" <?php selected( $level->get_after_final_payment(), 'expire_immediately' ); ?>><?php _e( 'End Membership Immediately', 'rcp' ); ?></option>
						<option value="expire_term_end" <?php selected( $level->get_after_final_payment(), 'expire_term_end' ); ?>><?php _e( 'End Membership at End of Billing Period', 'rcp' ); ?></option>
					</select>
					<p class="description">
						<?php _e( 'Action to take after the final payment has been received.', 'rcp'); ?>
						<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( '<strong>Grant Lifetime Access</strong>: will update the member\'s expiration date to "none" to give them lifetime access to restricted content. <br/><br/><strong>End Membership Immediately</strong>: will make the user\'s membership expire immediately after the final payment is received and they will lose access to restricted content. <br/><br/><strong>End Membership at End of Billing Period</strong>: will allow the user to complete one more period after the final payment, after which their membership will expire. For example, if the membership duration is set to 1 month, the user will make their final payment then have access for 1 more month after that before expiring.', 'rcp' ); ?>"></span>
					</p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rcp-price"><?php _e( 'Price', 'rcp' ); ?></label>
				</th>
				<td>
					<input type="text" id="rcp-price" name="price" value="<?php echo esc_attr( $level->get_price() ); ?>" pattern="^(\d+\.\d{1,2})|(\d+)$"/>
					<p class="description"><?php _e( 'The price of this membership level. Enter 0 for free.', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rcp-fee"><?php _e( 'Signup Fee', 'rcp' ); ?></label>
				</th>
				<td>
					<input type="text" id="rcp-fee" name="fee" value="<?php echo esc_attr( $level->get_fee() ); ?>"/>
					<p class="description"><?php _e( 'Optional signup fee to charge subscribers for the first billing cycle. Enter a negative number to give a discount on the first payment.', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rcp-status"><?php _e( 'Status', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="status" id="rcp-status">
						<option value="active" <?php selected( $level->get_status(), 'active' ); ?>><?php _e( 'Active', 'rcp' ); ?></option>
						<option value="inactive" <?php selected( $level->get_status(), 'inactive' ); ?>><?php _e( 'Inactive', 'rcp' ); ?></option>
					</select>
					<p class="description">
						<?php
						printf(
							__('Inactive membership levels do not appear on the %s shortcode page. Learn more about membership level statuses in <a href="%s" target="_blank">our documentation article</a>.', 'rcp'),
							'[register_form]',
							esc_url( 'https://docs.restrictcontentpro.com/article/2257-active-vs-inactive-membership-levels' )
						);
						?>
					</p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rcp-role"><?php _e( 'User Role', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="role" id="rcp-role">
						<?php wp_dropdown_roles( $level->get_role() ); ?>
					</select>
					<p class="description"><?php _e( 'The user role given to the member after signing up.', 'rcp' ); ?></p>
				</td>
			</tr>
			<?php do_action( 'rcp_edit_subscription_form', $level ); ?>
		</tbody>
	</table>
	<p class="submit">
		<input type="hidden" name="rcp-action" value="edit-subscription"/>
		<input type="hidden" name="subscription_id" value="<?php echo absint( urldecode( $_GET['edit_subscription'] ) ); ?>"/>
		<input type="submit" value="<?php _e( 'Update Membership Level', 'rcp' ); ?>" class="button-primary"/>
	</p>
	<?php wp_nonce_field( 'rcp_edit_level_nonce', 'rcp_edit_level_nonce' ); ?>
</form>
