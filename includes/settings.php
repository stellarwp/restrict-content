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

	// Find out if we're displaying a sidebar. If so, add a class.
	$maybe_display_promo = rc_maybe_display_promotion();
	$wrapper_class       = ( true === $maybe_display_promo )
		? ' rc-has-sidebar'
		: '';

	?>
	<div class="wrap">
		<div id="upb-wrap" class="upb-help">

			<h2><?php _e( 'Restrict Content Settings', 'restrict-content' ); ?></h2>

			<div class="rc-settings-wrap<?php echo esc_attr( $wrapper_class ); ?>">

				<div class="rc-settings-content">

					<?php
					if ( ! isset( $_REQUEST['updated'] ) ) {
						$_REQUEST['updated'] = false;
					}
					if ( false !== $_REQUEST['updated'] ) {
						?>
						<div class="updated fade">
							<p><strong><?php _e( 'Options saved', 'restrict-content' ); ?> )</strong></p>
						</div>
						<?php
					}
					?>

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

						<?php
						// Only show the RCP features if no sidebar is displaying
						if ( false === $maybe_display_promo ) {
							rc_pro_features();
						}
						?>

						<!-- save the options -->
						<p class="submit">
							<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'restrict-content' ); ?>"/>
						</p>

					</form>

				</div>

				<?php
				// Display a sidebar element when necessary
				if ( true === $maybe_display_promo ) {
					rc_display_sidebar();
				}
				?>

			</div>

		</div><!--end sf-wrap-->

	</div><!--end wrap-->

	<?php
}

/**
 * Features list for Restrict Content Pro
 */
function rc_pro_features() {
	?>

	<div class="rc-pro-features">

		<hr>
		<h4><?php _e( 'Need more control?' ); ?></h4>
		<p><?php _e( "Take your membership site to the next level with Restrict Content Pro. With RCP, you can:", 'restrict-content' ); ?></p>
		<p>
		<ul class="rc-settings-list" style="list-style-type: disc; list-style-position: inside">
			<li><?php _e( 'Charge for access and add a recurring revenue stream to your business. PayPal, Stripe, 2Checkout, Authorize.net, Braintree, and Manual Payments are all supported.', 'restrict-content' ); ?></li>
			<li><?php _e( 'Send and receive emails when members sign up, renew, cancel, and expire.', 'restrict-content' ); ?></li>
			<li><?php _e( 'Generate invoices for subscription payments.', 'restrict-content' ); ?></li>
			<li><?php _e( 'Prevent account sharing.', 'restrict-content' ); ?></li>
			<li><?php _e( 'Subscribe members to your Mailchimp, AWeber, ConvertKit, ActiveCampaign, Campaign Monitor, GetResponse, or MailPoet mailing lists.', 'restrict-content' ); ?></li>
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

	</div>

	<?php
}

/**
 * Display a sidebar element
 */
function rc_display_sidebar() {
	$coupon_code = 'BFCM2019';
	$args        = array(
		'utm_source'   => 'settings',
		'utm_medium'   => 'wp-admin',
		'utm_campaign' => 'bfcm2019',
		'utm_content'  => 'sidebar-promo',
	);
	$url         = add_query_arg( $args, 'https://restrictcontentpro.com/pricing/' );
	?>

	<div class="rc-settings-sidebar">

		<div class="rc-settings-sidebar-content">

			<div class="rc-sidebar-header-section">
				<img class="rc-bcfm-header" src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/bfcm-header.svg' ); ?>">
			</div>

			<div class="rc-sidebar-description-section">
				<p class="rc-sidebar-description"><?php _e( 'Save 25% on all Restrict Content Pro purchases <strong>this week</strong>, including renewals and upgrades!', 'restrict-content' ); ?></p>
			</div>

			<div class="rc-sidebar-coupon-section">
				<label for="rc-coupon-code"><?php _e( 'Use code at checkout:', 'restrict-content' ); ?></label>
				<input id="rc-coupon-code" type="text" value="<?php echo $coupon_code; ?>" readonly>
				<p class="rc-coupon-note"><?php _e( 'Sale ends 23:59 PM December 6th CST. Save 25% on <a href="https://sandhillsdev.com/projects/" target="_blank">our other plugins</a>.', 'restrict-content' ); ?></p>
			</div>

			<div class="rc-sidebar-footer-section">
				<a class="rc-cta-button" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php _e( 'Upgrade Now!', 'restrict-content' ); ?></a>
			</div>

		</div>

		<div class="rc-sidebar-logo-section">
			<div class="rc-logo-wrap">
				<img class="rc-logo" src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/restrict-content-pro-logo-light.svg' ); ?>">
			</div>
		</div>

	</div>

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
