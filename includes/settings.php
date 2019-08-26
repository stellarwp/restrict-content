<?php
/**
 * Settings Page
 *
 * @package     Restrict Content
 * @subpackage  Settings
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Render settings page
 *
 * @return void
 */
function rc_settings_page() {
	global $rc_options;

	?>
	<div class="wrap">
		<div id="upb-wrap" class="upb-help">
			<h2><?php _e( 'Restrict Content Settings', 'restrict-content' ); ?></h2>
			<?php
			if ( ! isset( $_REQUEST['updated'] ) ) {
				$_REQUEST['updated'] = false;
			}
			?>
			<?php if ( false !== $_REQUEST['updated'] ) : ?>
				<div class="updated fade">
					<p><strong><?php _e( 'Options saved', 'restrict-content' ); ?> )</strong></p>
				</div>
			<?php endif; ?>
			<form method="post" action="options.php">

				<?php settings_fields( 'rc_settings_group' ); ?>

				<table class="form-table">
					<tr valign="top">
						<th colspan="2"><strong><?php _e( 'Short Code Messages', 'restrict-content' ); ?></strong></th>
					</tr>
					<tr valign="top">
						<th><?php _e( 'Restricted Message', 'restrict-content' ); ?></th>
						<td>
							<input id="rc_settings[shortcode_message]" class="large-text" name="rc_settings[shortcode_message]" type="text" value="<?php echo isset( $rc_options['shortcode_message'] ) ? esc_attr( $rc_options['shortcode_message'] ) : ''; ?>"/><br/>
							<label class="description" for="rc_settings[shortcode_message]"><?php _e( 'When using the [restrict ... ] .... [/restrict] Short Code, this is the message displayed when a user does not have the appropriate permissions.', 'restrict-content' ); ?></label><br/>
							<small style="color: #666;"><?php _e( 'The <strong>{userlevel}</strong> tag will be automatically replaced with the permission level needed.', 'restrict-content' ); ?></small>
						</td>
					</tr>
					<tr>
						<th colspan="2">
							<strong><?php _e( 'User Level Restriction Messages', 'restrict-content' ); ?></strong></th>
					</tr>
					<tr valign="top">
						<th><?php _e( 'Administrators', 'restrict-content' ); ?></th>
						<td>
							<input id="rc_settings[administrator_message]" class="large-text" name="rc_settings[administrator_message]" type="text" value="<?php echo isset( $rc_options['administrator_message'] ) ? esc_attr( $rc_options['administrator_message'] ) : ''; ?>"/><br/>
							<label class="description" for="rc_settings[administrator_message]"><?php _e( 'Message displayed when a user does not have permission to view Administrator restricted content', 'restrict-content' ); ?></label><br/>
						</td>
					</tr>
					<tr valign="top">
						<th><?php _e( 'Editors', 'restrict-content' ); ?></th>
						<td>
							<input id="rc_settings[editor_message]" class="large-text" name="rc_settings[editor_message]" type="text" value="<?php echo isset( $rc_options['editor_message'] ) ? esc_attr( $rc_options['editor_message'] ) : ''; ?>"/><br/>
							<label class="description" for="rc_settings[editor_message]"><?php _e( 'Message displayed when a user does not have permission to view Editor restricted content', 'restrict-content' ); ?></label><br/>
						</td>
					</tr>
					<tr valign="top">
						<th><?php _e( 'Authors', 'restrict-content' ); ?></th>
						<td>
							<input id="rc_settings[author_message]" class="large-text" name="rc_settings[author_message]" type="text" value="<?php echo isset( $rc_options['author_message'] ) ? esc_attr( $rc_options['author_message'] ) : ''; ?>"/><br/>
							<label class="description" for="rc_settings[author_message]"><?php _e( 'Message displayed when a user does not have permission to view Author restricted content', 'restrict-content' ); ?></label><br/>
						</td>
					</tr>
					<tr valign="top">
						<th><?php _e( 'Contributors', 'restrict-content' ); ?></th>
						<td>
							<input id="rc_settings[contributor_message]" class="large-text" name="rc_settings[contributor_message]" type="text" value="<?php echo isset( $rc_options['contributor_message'] ) ? esc_attr( $rc_options['contributor_message'] ) : ''; ?>"/><br/>
							<label class="description" for="rc_settings[contributor_message]"><?php _e( 'Message displayed when a user does not have permission to view Contributor restricted content', 'restrict-content' ); ?></label><br/>
						</td>
					</tr>
					<tr valign="top">
						<th><?php _e( 'Subscribers', 'restrict-content' ); ?></th>
						<td>
							<input id="rc_settings[subscriber_message]" class="large-text" name="rc_settings[subscriber_message]" type="text" value="<?php echo isset( $rc_options['subscriber_message'] ) ? esc_attr( $rc_options['subscriber_message'] ) : ''; ?>"/><br/>
							<label class="description" for="rc_settings[subscriber_message]"><?php _e( 'Message displayed when a user does not have permission to view Subscriber restricted content', 'restrict-content' ); ?></label><br/>
						</td>
					</tr>
				</table>

				<hr>
				<h4><?php _e( 'Need more control?' ); ?></h4>
				<p><?php _e( "Take your membership site to the next level with Restrict Content Pro. With RCP, you can:", 'restrict-content' ); ?></p>
				<p>
					<ul class="rc-settings-list" style="list-style-type: disc; list-style-position: inside">
						<li><?php _e( 'Charge for access and add a recurring revenue stream to your business. PayPal, Stripe, 2Checkout, Authorize.net, Braintree, and Manual Payments are all supported.', 'restrict-content' ); ?></li>
						<li><?php _e( 'Send and receive emails when members sign up, renew, cancel, and expire.', 'restrict-content' ); ?></li>
						<li><?php _e( 'Generate invoices for subscription payments.', 'restrict-content' ); ?></li>
						<li><?php _e( 'Prevent account sharing.', 'restrict-content' ); ?></li>
						<li><?php _e( 'Subscribe members to your MailChimp, AWeber, ConvertKit, ActiveCampaign, Campaign Monitor, GetResponse, or MailPoet mailing lists', 'restrict-content' ); ?></li>
						<li><?php _e( 'Drip content on a schedule.', 'restrict-content' ); ?></li>
						<li><?php _e( 'Offer group accounts to businesses and other organizations.', 'restrict-content' ); ?></li>
						<li><?php _e( 'Sell websites using WordPress multisite.', 'restrict-content' ); ?></li>
						<li><?php _e( '... and a lot more!', 'restrict-content' ); ?></li>
					</ul>
					<?php printf(
						__( '<a href="%s" target="_blank" rel="noopener noreferrer">Find out more...</a>', 'restrict-content' ),
						'https://restrictcontentpro.com/?utm_campaign=restrict-content&utm_medium=admin&utm_source=settings&utm_content=main'
					); ?>
				</p>

				<!-- save the options -->
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'restrict-content' ); ?>"/>
				</p>


			</form>
		</div><!--end sf-wrap-->
	</div><!--end wrap-->

	<?php
}

/**
 * Register plugin settings
 *
 * @return void
 */
function rc_register_settings() {

	register_setting( 'rc_settings_group', 'rc_settings' );
}

add_action( 'admin_init', 'rc_register_settings' );

/**
 * Add link to settings page in menu
 *
 * @return void
 */
function rc_settings_menu() {

	add_submenu_page( 'options-general.php', __( 'Restrict Content Settings', 'restrict-content' ), __( 'Restrict Content', 'restrict-content' ), 'manage_options', 'restrict-content-settings', 'rc_settings_page' );
}

add_action( 'admin_menu', 'rc_settings_menu' );
