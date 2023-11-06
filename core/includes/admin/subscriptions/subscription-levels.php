<?php
/**
 * Membership Levels Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Membership Levels
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use RCP\Admin\Membership_Levels_Table;

/**
 * Render membership levels page
 *
 * @return void
 */
function rcp_member_levels_page() {

	include_once RCP_PLUGIN_DIR . 'core/includes/admin/subscriptions/class-membership-levels-table.php';

	$table_class = new Membership_Levels_Table();
	$table_class->prepare_items();
	do_action( 'stellarwp/telemetry/restrict-content-pro/optin' );
	do_action( 'stellarwp/telemetry/restrict-content/optin' );
	?>
	<div class="wrap">
		<?php if(isset($_GET['edit_subscription'])) :
			include('edit-subscription.php');
		else : ?>
			<h1><?php _e('Membership Levels', 'rcp'); ?></h1>

			<form id="rcp-memberships-filter" method="GET" action="<?php echo esc_url( add_query_arg( 'page', 'rcp-member-levels', admin_url( 'admin.php' ) ) ); ?>">
				<input type="hidden" name="page" value="rcp-member-levels"/>
				<?php
				$table_class->views();
				$table_class->search_box( __( 'Search membership levels', 'rcp' ), 'rcp-membership-levels' );
				$table_class->display();
				?>
			</form>

			<?php do_action('rcp_levels_below_table'); ?>
			<?php if( current_user_can( 'rcp_manage_levels' ) ) : ?>
				<h2><?php _e('Add New Level', 'rcp'); ?></h2>
				<form id="rcp-member-levels" action="" method="post">
					<table class="form-table">
						<tbody>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-name"><?php _e('Name', 'rcp'); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-name" name="name" value=""/>
									<p class="description"><?php _e('The name of the membership level.', 'rcp'); ?></p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-description"><?php _e('Description', 'rcp'); ?></label>
								</th>
								<td>
									<textarea id="rcp-description" name="description"></textarea>
									<p class="description"><?php _e('Membership level description. This is shown on the registration form.', 'rcp'); ?></p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-level"><?php _e('Access Level', 'rcp'); ?></label>
								</th>
								<td>
									<select id="rcp-level" name="level">
										<?php
										$access_levels = rcp_get_access_levels();
										foreach( $access_levels as $access ) {
											echo '<option value="' . $access . '">' . $access . '</option>';
										}
										?>
									</select>
									<p class="description">
										<?php _e('Level of access this membership gives. Leave None for default or you are unsure what this is.', 'rcp'); ?>
										<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>Access Level</strong>: refers to a tiered system where a member\'s ability to view content is determined by the access level assigned to their account. A member with an access level of 5 can view content assigned to access levels of 5 and lower, whereas a member with an access level of 4 can only view content assigned to levels of 4 and lower.', 'rcp' ); ?>"></span>
									</p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-duration"><?php _e('Duration', 'rcp'); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-duration" name="duration" value="0"/>
									<select name="duration_unit" id="rcp-duration-unit">
										<option value="day"><?php _e('Day(s)', 'rcp'); ?></option>
										<option value="month"><?php _e('Month(s)', 'rcp'); ?></option>
										<option value="year"><?php _e('Year(s)', 'rcp'); ?></option>
									</select>
									<p class="description">
										<?php _e('Length of time for this membership level. Enter 0 for unlimited.', 'rcp'); ?>
										<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>Example</strong>: setting this to 1 month would make memberships last 1 month, after which they will renew automatically or be marked as expired.', 'rcp' ); ?>"></span>
									</p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-maximum-renewals-setting"><?php _e( 'Maximum Renewals', 'rcp' ); ?></label>
								</th>
								<td>
									<select name="maximum_renewals_setting" id="rcp-maximum-renewals-setting">
										<option value="forever"><?php _e( 'Until Cancelled', 'rcp' ); ?></option>
										<option value="specific"><?php _e( 'Specific Number', 'rcp' ); ?></option>
									</select>
									<label for="rcp-maximum-renewals" class="screen-reader-text"><?php _e( 'Enter the maximum number of renewals', 'rcp' ); ?></label>
									<input type="number" id="rcp-maximum-renewals" name="maximum_renewals" value="0" style="display:none;" min="0" oninput="validatePositiveNumber(this)"/>
									<p class="description">
										<?php _e( 'Number of renewals to process after the first payment. Must be greater than zero.', 'rcp' ); ?>
										<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( '<strong>Until Cancelled</strong>: will continue billing the member indefinitely, or until they cancel their membership. <br/><br/><strong>Specific Number</strong> will allow you to enter the number of additional times you wish to bill the customer after their first payment. If you enter "3", the member will be billed once immediately when they sign up, then 3 more times after that. Then billing will stop automatically.', 'rcp' ); ?>"></span>
									</p>
								</td>
							</tr>
							<tr class="form-field" style="display: none;">
								<th scope="row" valign="top">
									<label for="rcp-after-final-payment"><?php _e( 'After Final Payment', 'rcp' ); ?></label>
								</th>
								<td>
									<select name="after_final_payment" id="rcp-after-final-payment">
										<option value="lifetime"><?php _e( 'Grant Lifetime Access', 'rcp' ); ?></option>
										<option value="expire_immediately"><?php _e( 'End Membership Immediately', 'rcp' ); ?></option>
										<option value="expire_term_end"><?php _e( 'End Membership at End of Billing Period', 'rcp' ); ?></option>
									</select>
									<p class="description">
										<?php _e( 'Action to take after the final payment has been received.', 'rcp'); ?>
										<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( '<strong>Grant Lifetime Access</strong>: will update the member\'s expiration date to "none" to give them lifetime access to restricted content. <br/><br/><strong>End Membership Immediately</strong>: will make the user\'s membership expire immediately after the final payment is received and they will lose access to restricted content. <br/><br/><strong>End Membership at End of Billing Period</strong>: will allow the user to complete one more period after the final payment, after which their membership will expire. For example, if the membership duration is set to 1 month, the user will make their final payment then have access for 1 more month after that before expiring.', 'rcp' ); ?>"></span>
									</p>
								</td>
							</tr>
							<?php

								/**
							 	* Action to add the free trial input fields
							 	*/
								do_action( 'rcp_new_subscription_after_set_trial_duration' );

							?>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-price"><?php _e('Price', 'rcp'); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-price" name="price" value="0" pattern="^(\d+\.\d{1,2})|(\d+)$"/>
									<p class="description">
										<?php _e('The price of this membership level. Enter 0 for free.', 'rcp'); ?>
										<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( 'This price refers to the amount paid per duration period. For example, if duration period is set to 1 month, this would be the amount charged each month.', 'rcp' ); ?>"></span>
									</p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-fee"><?php _e('Signup Fee', 'rcp'); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-fee" name="fee" value="0"/>
									<p class="description"><?php _e('Optional signup fee to charge subscribers for the first billing cycle. Enter a negative number to give a discount on the first payment.', 'rcp'); ?></p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-status"><?php _e('Status', 'rcp'); ?></label>
								</th>
								<td>
									<select name="status" id="rcp-status">
										<option value="active"><?php _e('Active', 'rcp'); ?></option>
										<option value="inactive"><?php _e('Inactive', 'rcp'); ?></option>
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
										<?php wp_dropdown_roles( 'subscriber' ); ?>
									</select>
									<p class="description"><?php _e( 'The user role given to the member after signing up.', 'rcp' ); ?></p>
								</td>
							</tr>
							<?php do_action( 'rcp_add_subscription_form' ); ?>
						</tbody>
					</table>
					<p class="submit">
						<input type="hidden" name="rcp-action" value="add-level"/>
						<input type="submit" value="<?php _e('Add Membership Level', 'rcp'); ?>" class="button-primary"/>
					</p>
					<?php wp_nonce_field( 'rcp_add_level_nonce', 'rcp_add_level_nonce' ); ?>
				</form>
			<?php endif; ?>
		<?php endif; ?>
	</div><!--end wrap-->

	<?php
}
