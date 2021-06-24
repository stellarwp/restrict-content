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

			<h2><?php _e( 'Restrict Content Settings', 'LION' ); ?></h2>

			<div class="rcp-settings-wrap">

				<div class="rcp-settings-content">

					<?php
					if ( ! isset( $_REQUEST['updated'] ) ) {
						$_REQUEST['updated'] = false;
					}
					if ( false !== $_REQUEST['updated'] ) {
						?>
						<div class="updated fade">
							<p><strong><?php _e( 'Options saved', 'LION' ); ?> )</strong></p>
						</div>
						<?php
					}
					?>

					<form method="post" action="options.php">

						<?php settings_fields( 'rc_settings_group' ); ?>

						<table class="form-table">
							<tr valign="top">
								<th colspan="2"><strong><?php _e( 'Short Code Messages', 'LION' ); ?></strong></th>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Restricted Message', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[shortcode_message]" class="large-text" name="rc_settings[shortcode_message]" type="text" value="<?php echo isset( $rc_options['shortcode_message'] ) ? esc_attr( $rc_options['shortcode_message'] ) : ''; ?>"/><br/>
									<label class="description" for="rc_settings[shortcode_message]"><?php _e( 'When using the [restrict ... ] .... [/restrict] Short Code, this is the message displayed when a user does not have the appropriate permissions.', 'LION' ); ?></label><br/>
									<small style="color: #666;"><?php _e( 'The <strong>{userlevel}</strong> tag will be automatically replaced with the permission level needed.', 'LION' ); ?></small>
								</td>
							</tr>
							<tr>
								<th colspan="2">
									<strong><?php _e( 'User Level Restriction Messages', 'LION' ); ?></strong></th>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Administrators', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[administrator_message]" class="large-text" name="rc_settings[administrator_message]" type="text" value="<?php echo isset( $rc_options['administrator_message'] ) ? esc_attr( $rc_options['administrator_message'] ) : ''; ?>"/><br/>
									<label class="description" for="rc_settings[administrator_message]"><?php _e( 'Message displayed when a user does not have permission to view Administrator restricted content', 'LION' ); ?></label><br/>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Editors', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[editor_message]" class="large-text" name="rc_settings[editor_message]" type="text" value="<?php echo isset( $rc_options['editor_message'] ) ? esc_attr( $rc_options['editor_message'] ) : ''; ?>"/><br/>
									<label class="description" for="rc_settings[editor_message]"><?php _e( 'Message displayed when a user does not have permission to view Editor restricted content', 'LION' ); ?></label><br/>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Authors', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[author_message]" class="large-text" name="rc_settings[author_message]" type="text" value="<?php echo isset( $rc_options['author_message'] ) ? esc_attr( $rc_options['author_message'] ) : ''; ?>"/><br/>
									<label class="description" for="rc_settings[author_message]"><?php _e( 'Message displayed when a user does not have permission to view Author restricted content', 'LION' ); ?></label><br/>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Contributors', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[contributor_message]" class="large-text" name="rc_settings[contributor_message]" type="text" value="<?php echo isset( $rc_options['contributor_message'] ) ? esc_attr( $rc_options['contributor_message'] ) : ''; ?>"/><br/>
									<label class="description" for="rc_settings[contributor_message]"><?php _e( 'Message displayed when a user does not have permission to view Contributor restricted content', 'LION' ); ?></label><br/>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Subscribers', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[subscriber_message]" class="large-text" name="rc_settings[subscriber_message]" type="text" value="<?php echo isset( $rc_options['subscriber_message'] ) ? esc_attr( $rc_options['subscriber_message'] ) : ''; ?>"/><br/>
									<label class="description" for="rc_settings[subscriber_message]"><?php _e( 'Message displayed when a user does not have permission to view Subscriber restricted content', 'LION' ); ?></label><br/>
								</td>
							</tr>
						</table>

						<!-- save the options -->
						<p class="submit">
							<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'rLION' ); ?>"/>
						</p>

					</form>

					<?php rc_pro_features(); ?>

				</div>

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

    <div class="rcp-pro-banner">
        <div class="rcp-pro-logo">
            <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/restrict-content-pro-logo.svg'); ?>" >
        </div>
        <div class="rcp-unlock-text">
            <h2><?php _e( 'Grow Your Sales with Premium Features and Add-ons in Restrict Content PRO', 'LION' ); ?></h2>
        </div>
        <div class="rcp-pro-button">
            <a href="https://restrictcontentpro.com/demo/"><?php _e( 'Try Before You Buy', 'LION' ); ?></a>
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
 * Register plugin settings
 *
 * @return void
 */
function rc_settings_menu() {
	add_menu_page( __( 'Restrict Content Settings', 'LION' ), __( 'Restrict', 'LION'), 'manage_options', 'restrict-content-settings', 'rc_settings_page' );
	$rc_settings_page = add_submenu_page( 'restrict-content-settings', __( 'Settings', 'LION' ), __( 'Settings', 'LION' ), 'manage_options', 'restrict-content-settings', 'rc_settings_page' );
	$rc_why_go_pro_page = add_submenu_page( 'restrict-content-settings', __( 'Why Go Pro', 'LION' ), __( 'Why go Pro', 'LION' ), 'manage_options', 'rcp-why-go-pro', 'rc_why_go_pro_page' );
}
add_action( 'admin_menu', 'rc_settings_menu');

/**
 * Render Why Go Pro Page
 *
 * @return void
 */
function rc_why_go_pro_page() {
    ?>
    <div class="wrap">
        <div class="rcp-why-go-pro-wrap">
            <img class="restrict-content-logo" src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/restrict_content_logo.svg' ); ?>" >
            <div class="rcp-go-pro-color-container">
                <div class="rcp-why-go-pro-inner-wrapper">
                    <div class="rcp-top-header">
                        <h1>
                            <?php _e( 'Why Go Pro?', 'LION' ); ?></h1>
                        <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/flying_rex.png' ); ?>" >
                    </div>
                    <h2><?php _e( 'Grow Your Sales with Premium Features and Add-ons in Restrict Content PRO', 'LION' ); ?></h2>
                    <div class="rcp-pro-features-container">
                        <!-- MEMBERSHIP LEVELS FEATURE -->
                        <a href="https://restrictcontentpro.com/tour/features/subscription-levels/">
                            <div class="rcp-membership-levels feature">
                                <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/memb-levels.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Membership Levels', 'LION' ); ?></h3>
                                    <p><?php _e( 'Offer multiple membership levels with unique prices and restrictions.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- COLLECT PAYMENTS FEATURE -->
                        <a href="https://restrictcontentpro.com/tour/payment-gateways/">
                            <div class="rcp-collect-payments feature">
                                <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/collect-payments.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Collect Payments', 'LION' ); ?></h3>
                                    <p><?php _e( "Collect recurring payments is easy with Restrict Content Pro's simple payment gateway integrations.", 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- CUSTOMER DASHBOARD FEATURE -->
                        <a href="https://restrictcontentpro.com/tour/features/">
                            <div class="rcp-customer-dashboard feature">
                                <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/customer-dash.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Customer Dashboard', 'LION' ); ?></h3>
                                    <p><?php _e( 'Let your members easily view and manage their account details.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- MARKETING INTEGRATION FEATURE -->
                        <a href="https://restrictcontentpro.com/add-ons/pro/">
                            <div class="rcp-marketing-integration feature">
                                <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/mkt-integration.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Marketing Integration', 'LION' ); ?></h3>
                                    <p><?php _e( 'Subscribe members to your Mailchimp, AWeber, ConvertKit, etc., mailing lists.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- GROUP ACCOUNTS FEATURE -->
                        <a href="https://restrictcontentpro.com/downloads/group-accounts/">
                            <div class="rcp-group-accounts feature">
                                <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/group-acct.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Group Accounts', 'LION' ); ?></h3>
                                    <p><?php _e( 'Sell enterprise or group memberships with multiple sub accounts.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- DRIP CONTENT FEATURE -->
                        <a href="https://restrictcontentpro.com/downloads/drip-content/">
                            <div class="rcp-drip-content feature">
                                <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/drip-content.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Drip Content', 'LION' ); ?></h3>
                                    <p><?php _e( 'Time-release content to new members based on their start date.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- OFFER DISCOUNTS FEATURE -->
                        <a href="https://restrictcontentpro.com/tour/features/discount-codes/">
                            <div class="rcp-offer-discounts feature">
                                <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/offer-discounts.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Offer Discounts', 'LION' ); ?></h3>
                                    <p><?php _e( 'Attract new customers with special promotional codes that give them a discount on the purchase of a membership.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- RESTRICT PAST CONTENT FEATURE -->
                        <a href="https://restrictcontentpro.com/downloads/restrict-past-content/">
                            <div class="rcp-restrict-past-content feature">
                                <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/restrict-content.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Restrict Past Content', 'LION' ); ?></h3>
                                    <p><?php _e( "Restrict content published before a member's join date.", 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- PREMIUM SUPPORT FEATURE -->
                        <div class="rcp-premium-support feature">
                            <img src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/premium-support.svg' ); ?>" >
                            <div class="feature-text">
                                <h3><?php _e( 'Premium Support', 'LION' ); ?></h3>
                                <p><?php _e( 'Get help from our team of membership experts.', 'LION' ); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="rcp-why-go-pro-buttons-container">
                        <a class="try-before-you-buy" href="https://restrictcontentpro.com/demo/">
                            <?php _e( 'Try Before You Buy', 'LION' ); ?>
                        </a>
                        <a class="rcp-unlock-pro-features-add-ons" href="https://restrictcontentpro.com/pricing/">
                            <?php _e( 'Unlock Pro Features & Add-Ons', 'LION' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function rc_screen_options() {

}