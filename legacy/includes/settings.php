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
									<input
                                            id="rc_settings[shortcode_message]"
                                            class="large-text"
                                            name="rc_settings[shortcode_message]"
                                            type="text"
                                            value="<?php echo ! empty( $rc_options['shortcode_message'] ) ? esc_attr( $rc_options['shortcode_message'] ) : __( 'You do not have access to this post.', 'LION' ); ?>"/><br/>
									<label class="description" for="rc_settings[shortcode_message]"><?php _e( 'When using the [restrict ... ] .... [/restrict] Short Code, this is the message displayed when a user does not have the appropriate permissions.', 'LION' ); ?></label><br/>
									<small style="color: #666;"><?php _e( 'The <strong>{userlevel}</strong> tag will be automatically replaced with the permission level needed.', 'LION' ); ?></small>
								</td>
							</tr>
							<tr>
								<th colspan="2">
									<strong><?php _e( 'User Level Restriction Messages', 'LION' ); ?></strong>
                                </th>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Administrators', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[administrator_message]" class="large-text" name="rc_settings[administrator_message]" type="text" value="<?php echo ! empty( $rc_options['administrator_message'] ) ? esc_attr( $rc_options['administrator_message'] ) : __( 'This content is for Administrator Users.', 'LION' ); ?>"/><br/>
									<label class="description" for="rc_settings[administrator_message]"><?php _e( 'Message displayed when a user does not have permission to view Administrator restricted content', 'LION' ); ?></label><br/>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Editors', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[editor_message]" class="large-text" name="rc_settings[editor_message]" type="text" value="<?php echo ! empty( $rc_options['editor_message'] ) ? esc_attr( $rc_options['editor_message'] ) : __( 'This content is for Editor Users.', 'LION' ); ?>"/><br/>
									<label class="description" for="rc_settings[editor_message]"><?php _e( 'Message displayed when a user does not have permission to view Editor restricted content', 'LION' ); ?></label><br/>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Authors', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[author_message]" class="large-text" name="rc_settings[author_message]" type="text" value="<?php echo ! empty( $rc_options['author_message'] ) ? esc_attr( $rc_options['author_message'] ) : __( 'This content is for Author Users.', 'LION' ); ?>"/><br/>
									<label class="description" for="rc_settings[author_message]"><?php _e( 'Message displayed when a user does not have permission to view Author restricted content', 'LION' ); ?></label><br/>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Contributors', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[contributor_message]" class="large-text" name="rc_settings[contributor_message]" type="text" value="<?php echo ! empty( $rc_options['contributor_message'] ) ? esc_attr( $rc_options['contributor_message'] ) : __( 'This content is for Contributor Users.', 'LION' ); ?>"/><br/>
									<label class="description" for="rc_settings[contributor_message]"><?php _e( 'Message displayed when a user does not have permission to view Contributor restricted content', 'LION' ); ?></label><br/>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Subscribers', 'LION' ); ?></th>
								<td>
									<input id="rc_settings[subscriber_message]" class="large-text" name="rc_settings[subscriber_message]" type="text" value="<?php echo ! empty( $rc_options['subscriber_message'] ) ? esc_attr( $rc_options['subscriber_message'] ) : __( 'This content is for Subscribers Users.', 'LION' ); ?>"/><br/>
									<label class="description" for="rc_settings[subscriber_message]"><?php _e( 'Message displayed when a user does not have permission to view Subscriber restricted content', 'LION' ); ?></label><br/>
								</td>
							</tr>
                            <tr>
                                <th><?php _e( 'Restrict Content Welcome', 'LION' ); ?></th>
                                <td>
                                    <label><a href="admin.php?page=restrict-content-welcome"><?php _e( 'View the Restrict Content Welcome page again for some helpful tips and links!', 'LION' ) ?></label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Switch Restrict Content Version', 'LION' ); ?></th>
                                <td>
                                    <input
                                            type="hidden"
                                            name="rcp_settings_nonce"
                                            id="rcp_settings_nonce"
                                            value="<?php echo wp_create_nonce( 'rc_process_legacy_nonce' ); ?>"
                                    />
                                    <input
                                            type="button"
                                            id="restrict_content_legacy_switch"
                                            class="button-primary btn-success"
                                            value="<?php _e( 'Use the new version of Restrict Content?', 'LION' ); ?>"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Why Upgrade?', 'LION' ); ?></th>
                                <td><?php
                                    printf(
                                        __( 'Restrict Content 3 includes a robust new suite of features, including creating memberships and collectings payments. After upgrading, your content will remain restricted, and the associated restricted content messages will update to the new default restriction message. <a href="%s">Learn More</a>', 'LION' ),
                                        'https://help.ithemes.com/hc/en-us/articles/4411587693211'
                                    )
                                ?></td>
                            </tr>
						</table>

						<!-- save the options -->
						<p class="submit">
							<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'LION' ); ?>"/>
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
            <img src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/restrict_content_logo_white.svg' ); ?>" >
        </div>
        <div class="rcp-unlock-text">
            <h2><?php _e( 'Get powerful new membership capabilities and content access features', 'LION' ); ?></h2>
        </div>
        <div class="rcp-pro-button">
            <a><?php _e( 'Update now to 3.0 (Free!)', 'LION' ); ?></a>
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
	$rc_help_page = add_submenu_page( 'restrict-content-settings', __( 'Help', 'LION' ), __( 'Help', 'LION' ), 'manage_options', 'rcp-need-help', 'rc_need_help_page' );
	$rc_welcome_page = add_submenu_page( null, __( 'Welcome', 'LION'), __( 'Welcome', 'LION' ), 'manage_options', 'restrict-content-welcome', 'rc_welcome_page' );
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

function rc_welcome_page() {
    $current_user = wp_get_current_user();

	$rc_welcome_try_free_meta_nonce = wp_create_nonce( 'rc_welcome_try_free_meta_nonce' );
    ?>
    <div class="restrict-content-welcome-header">
        <img class="restrict-content-logo" src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/restrict_content_logo.svg' ); ?>" >
    </div>
    <div class="restrict-content-welcome-top-container">
        <div class="restrict-content-welcome-left-container">
            <h1 class="restrict-content-welcome-user">
                <?php
                    printf( __( 'Welcome %s!', 'LION' ),
                        $current_user->first_name ?: $current_user->display_name
                    );
                ?>
            </h1>
            <div class="restrict-content-inner-container">
                <div class="restrict-content-welcome-body-container">
                    <div class="restrict-content-welcome-body restrict-content-container-section">
                        <h2 class="restrict-content-thanks-header"><?php _e( 'Thanks For Installing Restrict Content!', 'LION' ); ?></h2>
                        <p class="restrict-content-thanks-message"><?php _e( 'Restrict Content is a simple WordPress membership plugin that gives you full control over who can and cannot view content on your WordPress site.', 'LION' ); ?></p>
                        <p class="restrict-content-thanks-message"><?php _e( 'Restrict access to your website based on user role so that your posts, pages, media, and custom post types become viewable by logged-in members only.', 'LION' ); ?></p>
                    </div>
                    <div class="restrict-content-welcome-standing-rex">
                        <img src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/restrict-content-pro-rex-standing.png' ); ?>" >
                    </div>
                </div>
                <div class="restrict-content-welcome-body-container">
                    <div class="restrict-content-how-to-body restrict-content-container-section">
                        <h2><?php _e( 'How To Use Restrict Content', 'LION' ); ?></h2>
                        <p class="restrict-content-how-to-message"><?php _e( "To restrict an entire post or page, simply select the user level you'd like to restrict the post or page to from the drop down menu added just below the post/page editor.", 'LION' ); ?></p>
                        <p class="restrict-content-how-to-message"><?php _e( 'Accepted user-level values are:', 'LION' ); ?></p>
                        <p>
                            * <?php _e( 'admin', 'LION' ); ?><br>
                            * <?php _e( 'editor', 'LION' ); ?><br>
                            * <?php _e( 'author', 'LION' ); ?><br>
                            * <?php _e( 'subscriber', 'LION' ); ?><br>
                            * <?php _e( 'contributor', 'LION' ); ?>
                        </p>
                        <p class="restrict-content-how-to-message"><?php _e( "To restrict just a section of content within a post or page or to display registration forms, you can use shortcodes:", 'LION' ); ?></p>
                        <ul>
                            <li><?php _e( 'Limit access to content with a shortcode. <span class="restrict-content-example-text">Example: [restrict] This content is limited to logged in users. [/restrict]</span>', 'LION' ); ?></li>
                            <li><?php _e( 'Limit access to content based on user role. <span class="restrict-content-example-text">Example [restrict userlevel="editor"] Only editors and higher can see this contents.[/restrict]</span>', 'LION' ); ?></li>
                            <li><?php _e( 'Limit access to content if user is not logged in. <span class="restrict-content-example-text">Example: [not_logged_in] This content is only shown to non-logged-in users.[/not_logged_in]</span>', 'LION' ); ?></li>
                            <li><?php _e( 'Display a registration form for new accounts on any page of you website with <span class="restrict-content-example-text">[register_form].</span>', 'LION' ); ?></li>
                            <li><?php _e( 'Display a login form for existing users on any page of your website with <span class="restrict-content-example-text">[login_form]</span>.', 'LION' ); ?></li>
                        </ul>
                    </div>
                </div>
                <div class="restrict-content-welcome-body-container">
                    <div class="restrict-content-helpful-resources restrict-content-container-section">
                        <h2><?php _e( 'Helpful Resources', 'LION' ); ?></h2>
                        <div class="restrict-content-resource-container">
                            <h3><?php _e( 'Getting Started with Restrict Content', 'LION' ); ?></h3>
                            <p><?php _e( 'Learn how to start restricting content on your website.', 'LION' ); ?></p>
                        </div>
                        <div class="restrict-content-resource-container">
                            <h3><?php _e( 'Help Center', 'LION' ); ?></h3>
                            <p>
				                <?php
				                printf(
					                __( 'Our <a href="%s">Help Center</a> will help you become a Restrict Content & Restrict Content Pro expert.', 'LION' ),
					                'https://help.ithemes.com'
				                );
				                ?>
                            </p>
                        </div>
                        <div class="restrict-content-resource-container">
                            <h3><?php _e( 'Need More Control Over Your Content & Memberships?', 'LION' ); ?></h3>
                            <p><?php _e( 'Check out Restrict Content Pro and our suite of add-ons for building awesome membership websites.', 'LION' ); ?> <br><a href="https://restrictcontentpro.com/add-ons/">https://restrictcontentpro.com/add-ons/</a></p>
                        </div>
                        <div class="restrict-content-resource-container">
                            <h3><?php _e( 'Introduction to Restrict Content Pro', 'LION' ); ?></h3>
                            <p><?php _e( 'Get a full overview of Restrict Content Pro and dive into several of its key features.', 'LION' ) ?><br><a href="https://training.ithemes.com/webinar/introduction-to-restrict-content-pro/">https://training.ithemes.com/webinar/introduction-to-restrict-content-pro/</a></p>
                        </div>
                        <div class="restrict-content-resource-container">
                            <h3><?php _e( 'Try Restrict Content Pro for Free', 'LION' ); ?></h3>
                            <p><?php _e( 'Give Restrict Content Pro a spin, along with the full suite of add-ons, before buying a subscription.', 'LION' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="restrict-content-welcome-right-container">
            <div class="restrict-content-welcome-advertisement">
                <div class="logo">
                    <img class="restrict-content-welcome-advertisement-logo" src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/restrict-content-pro-logo-vertical-blue-black.svg' ); ?>" >
                </div>
                <div class="restrict-content-welcome-try-for-free">
                    <p><?php _e( 'Try a Demo!', 'LION' ); ?></p>
                </div>
                <div class="restrict-content-welcome-advertisement-content">
                    <p><?php _e( 'Lock away your exclusive content. Give access to valued members.', 'LION' ); ?></p>
                    <p class="rcp-highlight"><?php _e( 'A Full-Featured Powerful Membership Solution for WordPress.', 'LION' ); ?></p>
                    <p><?php _e( 'Give Restrict Content Pro a spin, along with the full suite of add-ons. Enter your email and we’ll automatically send you a link to a personal WordPress demo site, no strings attached!', 'LION' ); ?></p>
                </div>
                <div class="restrict-content-welcome-advertisement-form">
                    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="restrict_content_try_free">
                        <input type="hidden" name="action" value="restrict_content_try_free">
                        <input type="hidden" name="rc_welcome_try_free_meta_nonce" value="<?php echo $rc_welcome_try_free_meta_nonce; ?>" >
                        <input type="hidden" name="source_page" value="welcome_page">
                        <input type="email" name="try_email_address" id="try_email_address" placeholder="Email Address">
                        <input type="submit" class="restrict-content-welcome-button" value="<?php _e( 'Try Now, Free!', 'LION' ); ?>">
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}

add_action( 'in_admin_header', function() {
    if ( ! empty( $_GET['page'] ) && $_GET['page'] === 'rcp-why-go-pro' ) {
	    remove_all_actions( 'all_admin_notices' );
	    remove_all_actions( 'network_admin_notices' );
	    remove_all_actions( 'admin_notices' );
    }
});

function rc_need_help_page() {
    ?>
    <div class="restrict-content-welcome-header">
        <img class="restrict-content-logo" src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/restrict_content_logo.svg' ); ?>" >
    </div>
    <div class="restrict-content-welcome-top-container">
        <div class="restrict-content-welcome-left-container">
            <h1 class="restrict-content-welcome-user"><?php _e( 'Need Help?', 'LION' ); ?></h1>
            <p>
                <?php
                    printf(
                        __('Are you new to Restrict Content? Check out the Getting Started with <a href="%s">Restrict Content guide.</a>', 'LION' ),
	                    'https://help.ithemes.com/hc/en-us/articles/4402387794587-Getting-Started-with-Restrict-Content'
                    );
                ?>
            </p>
            <div class="restrict-content-inner-container">
                <a class="restrict-content-section-link" href="https://help.ithemes.com">
                    <div class="restrict-content-help-section">
                        <div class="restrict-content-help-section-icon">
                            <div id="restrict-content-help-center" class="restrict-content-help-section-trouble-shooting-image"></div>
                        </div>
                        <div class="restrict-content-help-section-content">
                            <h3><?php _e( 'Help Center', 'LION' ); ?></h3>
                            <p><?php _e( 'Our Help Center is filled with articles to help you learn more about using Restrict Content and Restrict Content Pro.', 'LION' ); ?></p>
                        </div>
                        <img class="restrict-content-help-section-arrow hidden" style="display: none;" src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/purple-arrow-right.svg' ); ?>" >
                    </div>
                </a>
                <a class="restrict-content-section-link" href="https://help.ithemes.com/hc/en-us/articles/115003073433-Checking-for-a-Conflict">
                    <div id="restrict-content-troubleshooting-link" class="restrict-content-help-section">
                        <div class="restrict-content-help-section-icon">
                            <div id="restrict-content-trouble-shooting" class="restrict-content-help-section-trouble-shooting-image"></div>
                        </div>
                        <div class="restrict-content-help-section-content">
                            <h3><?php _e( 'Troubleshooting', 'LION' ); ?></h3>
                            <p><?php _e( 'If you run into any errors or things aren’t working as expected, the first step in troubleshooting is to check for a plugin or theme conflict.', 'LION' ); ?></p>
                        </div>
                        <img class="restrict-content-help-section-arrow hidden" style="display: none;" src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/purple-arrow-right.svg' ); ?>" >
                    </div>
                </a>
                <a class="restrict-content-section-link" href="https://wordpress.org/support/plugin/restrict-content/">
                    <div id="restrict-content-support-link" class="restrict-content-help-section">
                        <div class="restrict-content-help-section-icon">
                            <div id="restrict-content-support-forum" class="restrict-content-help-section-trouble-shooting-image"></div>
                        </div>
                        <div class="restrict-content-help-section-content">
                            <h3><?php _e( 'Support Forum', 'LION' ); ?></h3>
                            <p><?php _e( 'If you are still having trouble after checking for a conflict, feel free to start a new thread on the Restrict Content support forum.', 'LION' ); ?></p>
                        </div>
                        <img class="restrict-content-help-section-arrow hidden" style="display:none;" src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/purple-arrow-right.svg' ); ?>" >
                    </div>
                </a>
                <div class="restrict-content-premium-support">
                    <div class="premium-support-content">
                        <h3><?php _e( 'Get Premium Support', 'LION' ); ?></h3>
                        <p>
                            <?php
                                printf(
                                    __( 'Purchase any <a href="%s">Restrict Content Pro subscription</a> and get access to our ticketed support system. Our team of experts is ready to help!', 'LION' ),
                                    'https://restrictcontentpro.com/pricing/'
                                );
                            ?>
                        </p>
                    </div>
                    <img class="restrict-content-premium-support-logo" src="<?php echo esc_url( RC_PLUGIN_URL . 'includes/assets/images/flying_rex.png' ); ?>" >
                </div>
            </div>
        </div>
        <div class="restrict-content-welcome-right-container">
            <div class="restrict-content-welcome-advertisement">
                <div class="logo">
                    <img class="restrict-content-welcome-advertisement-logo" src="<?php echo esc_url( RC_PLUGIN_URL . '/includes/assets/images/restrict-content-pro-logo-vertical-blue-black.svg' ); ?>" >
                </div>
                <div class="restrict-content-welcome-try-for-free">
                    <p><?php _e( 'Try a Demo!', 'LION' ); ?></p>
                </div>
                <div class="restrict-content-welcome-advertisement-content">
                    <p><?php _e( 'Lock away your exclusive content. Give access to valued members.', 'LION' ); ?></p>
                    <p class="rcp-highlight"><?php _e( 'A Full-Featured Powerful Membership Solution for WordPress.', 'LION' ); ?></p>
                    <p><?php _e( 'Give Restrict Content Pro a spin, along with the full suite of add-ons. Enter your email and we’ll automatically send you a link to a personal WordPress demo site, no strings attached!', 'LION' ); ?></p>
                </div>
                <div class="restrict-content-welcome-advertisement-form">
                    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="restrict_content_try_free">
                        <input type="hidden" name="action" value="restrict_content_try_free">
                        <input type="hidden" name="rc_welcome_try_free_meta_nonce" value="<?php echo wp_create_nonce( 'rc_welcome_try_free_meta_nonce' ); ?>" >
                        <input type="hidden" name="source_page" value="help_page">
                        <input type="email" name="try_email_address" id="try_email_address" placeholder="Email Address">
                        <input type="submit" class="restrict-content-welcome-button" value="<?php _e( 'Try Now, Free!', 'LION' ); ?>">
                    </form>
                </div>
            </div>
            <div class="restrict-content-unlock-premium-features">
                <h3><?php _e( 'Unlock Premium Features', 'LION' ); ?></h3>
                <p><?php _e( 'Go beyond the basics with premium features & support.', 'LION' ); ?></p>
                <div class="tabs">
                    <div class="tablist" role="tablist" aria-label="<?php esc_attr_e( 'Pricing Plans', 'LION' ); ?>">

                        <button role="tab" aria-selected="true" aria-controls="1sitetab" id="1site">
                            <?php _e( '1 Site', 'LION' ); ?>
                        </button>
                        <button role="tab" aria-selected="false" aria-controls="10sitetab" id="10site" tabindex="-1">
                            <?php _e( '10 Sites', 'LION' ); ?>
                        </button>
                        <button role="tab" aria-selected="false" aria-controls="unlimitedtab" id="unlimited" tabindex="-1">
                            <?php _e( 'Unlimited', 'LION' ); ?>
                        </button>
                    </div>
                    <div class="tabpanel" tabindex="0" role="tabpanel" id="1sitetab" aria-labelledby="1site">
                        <h4><?php _e( '$99', 'LION' ); ?></h4>
                        <p><?php _e( 'Includes updates & support for one year.', 'LION' ); ?></p>
                    </div>
                    <div class="tabpanel" tabindex="0" role="tabpanel" id="10sitetab" aria-labelledby="10site" hidden="">
                        <h4><?php _e( '$149', 'LION' ); ?></h4>
                        <p><?php _e( 'Includes updates & support for one year.', 'LION' ); ?></p>
                    </div>
                    <div class="tabpanel" tabindex="0" role="tabpanel" id="unlimitedtab" aria-labelledby="unlimited" hidden="">
                        <h4><?php _e( '$249', 'LION' ); ?></h4>
                        <p><?php _e( 'Includes updates & support for one year.', 'LION' ); ?></p>
                    </div>
                </div>
                <a href="https://restrictcontentpro.com/pricing/" class="go-pro-now"><?php _e( 'Go Pro Now', 'LION' ); ?></a>
                <p class="whats-included"><a href="https://restrictcontentpro.com/why-go-pro/"><?php _e( "What's included with Pro?", 'LION' ); ?></a></p>
            </div>
        </div>
    </div>
    <?php
}

function rc_screen_options() {

}

function restrict_content_admin_try_free_success() {
	if ( ! empty( $_GET['message'] ) && ! empty( $_GET['page'] ) && $_GET['page'] === 'rcp-need-help') {
		if ( $_GET['message'] === 'success' ) {
			?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Email Sent Successfully.', 'LION' ); ?></p>
            </div>
			<?php
		} else if ( $_GET['message'] === 'failed' ) {
			?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e( 'Unable to send email.', 'LION' ); ?></p>
            </div>
			<?php
		}
	} else if ( ! empty( $_GET['message'] ) && ! empty( $_GET['page'] ) && $_GET['page'] === 'restrict-content-welcome' ) {
		if ( $_GET['message'] === 'success' ) {
			?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Email Sent Successfully.', 'LION' ); ?></p>
            </div>
			<?php
		} else if ( $_GET['message'] === 'failed' ) {
			?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e( 'Unable to send email.', 'LION' ); ?></p>
            </div>
			<?php
		}
	}
}
add_action( 'admin_notices', 'restrict_content_admin_try_free_success' );

function restrict_content_admin_try_free () {

	if( isset( $_POST['rc_welcome_try_free_meta_nonce'] ) && wp_verify_nonce( $_POST['rc_welcome_try_free_meta_nonce'], 'rc_welcome_try_free_meta_nonce') ) {

        $body = array(
            'template_name' => 'rcp-demo-delivery',
            'email' => $_POST['try_email_address']
        );

		$fields = array(
			'method'        => 'POST',
			'body'  => json_encode( $body )
		);

        $response = wp_remote_request( 'https://api.ithemes.com/email/send', $fields );

        if ( ! is_wp_error( $response ) && $_POST['source_page'] === 'welcome_page' ) {
	        wp_redirect( add_query_arg( 'message', 'success', admin_url( 'admin.php?page=restrict-content-welcome' ) ) );
        } else if ( ! is_wp_error( $response ) && $_POST['source_page'] === 'help_page' ) {
	        wp_redirect( add_query_arg( 'message', 'success', admin_url( 'admin.php?page=rcp-need-help' ) ) );
        } else {
	        wp_redirect( add_query_arg( 'message', 'failed', admin_url( 'admin.php?page=rcp-need-help' ) ) );
        }
    }
}

add_action( 'admin_post_restrict_content_try_free', 'restrict_content_admin_try_free' );
