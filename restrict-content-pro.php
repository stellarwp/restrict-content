<?php
/**
 * Plugin Name: Restrict Content
 * Plugin URI: https://restrictcontentpro.com
 * Description: Set up a complete membership system for your WordPress site and deliver premium content to your members. Unlimited membership packages, membership management, discount codes, registration / login forms, and more.
 * Version: 3.0
 * Author: iThemes
 * Author URI: https://ithemes.com/
 * Text Domain: rcp
 * Domain Path: languages
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class RC_Requirements_Check
 *
 * @since 3.0
 */
final class RC_Requirements_Check {

	/**
	 * Plugin file
	 *
	 * @since 3.0
	 * @var string
	 */
	private $file = '';

	/**
	 * Plugin basename
	 *
	 * @since 3.0
	 * @var string
	 */
	private $base = '';

	/**
	 * Requirements array
	 *Yeah
	 * @var array
	 * @since 3.0
	 */
	private $requirements = array(

		// PHP
		'php' => array(
			'minimum' => '5.6.0',
			'name'    => 'PHP',
			'exists'  => true,
			'current' => false,
			'checked' => false,
			'met'     => false
		),

		// WordPress
		'wp' => array(
			'minimum' => '4.4.0',
			'name'    => 'WordPress',
			'exists'  => true,
			'current' => false,
			'checked' => false,
			'met'     => false
		)
	);

	/**
	 * Setup plugin requirements
	 *
	 * @since 3.0
	 */
	public function __construct() {
		// Setup file & base
		$this->file = __FILE__;
		$this->base = plugin_basename( $this->file );

		// Always load translations
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Load or quit
		$this->met()
			? $this->load()
			: $this->quit();
	}

	/**
	 * Quit without loading
	 *
	 * @since 3.0
	 */
	private function quit() {
		add_action( 'admin_head',                        array( $this, 'admin_head'        ) );
		add_filter( "plugin_action_links_{$this->base}", array( $this, 'plugin_row_links'  ) );
		add_action( "after_plugin_row_{$this->base}",    array( $this, 'plugin_row_notice' ) );
	}

	/** Specific Methods ******************************************************/

	/**
	 * Load normally
	 *
	 * @since 3.0
	 */
	private function load() {
        // If we find the rc_settings option then they were definitely using the old version
        $use_legacy_version = false;
        if ( option_exists( 'restrict_content_pro_use_legacy_restrict_content' ) ) {
            $use_legacy_version = get_option( 'restrict_content_pro_use_legacy_restrict_content' );
        }
        // If restrict_content_pro_use_legacy_restrict_content then load old Restrict Content
        if ( $use_legacy_version ) {
            require_once dirname( $this->file ) . '/restrict-content/restrictcontent.php';
        } else {
            // Maybe include the bundled bootstrapper
            if ( ! class_exists( 'Restrict_Content_Pro' ) ) {
                require_once dirname( $this->file ) . '/core/includes/class-restrict-content.php';
            }

            // Maybe hook-in the bootstrapper
            if ( class_exists( 'Restrict_Content_Pro' ) ) {

                // Bootstrap to plugins_loaded before priority 10 to make sure
                // add-ons are loaded after us.
                add_action( 'plugins_loaded', array( $this, 'bootstrap' ), 4 );

                // Register the activation hook
                register_activation_hook( $this->file, array( $this, 'install' ) );
            }
        }
	}

	/**
	 * Install, usually on an activation hook.
	 *
	 * @since 3.0
	 */
	public function install() {
		// Bootstrap to include all of the necessary files
		$this->bootstrap();

		// Network wide?
		$network_wide = ! empty( $_GET['networkwide'] )
			? (bool) $_GET['networkwide']
			: false;

		// Call the installer directly during the activation hook
		rcp_options_install( $network_wide );
	}

	/**
	 * Bootstrap everything.
	 *
	 * @since 3.0
	 */
	public function bootstrap() {
		Restrict_Content_Pro::instance( $this->file );
	}

	/**
	 * Plugin specific URL for an external requirements page.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_url() {
		return 'https://docs.restrictcontentpro.com/article/2077-minimum-requirements';
	}

	/**
	 * Plugin specific text to quickly explain what's wrong.
	 *
	 * @since 3.0
	 * @return void
	 */
	private function unmet_requirements_text() {
		esc_html_e( 'This plugin is not fully active.', 'rcp' );
	}

	/**
	 * Plugin specific text to describe a single unmet requirement.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_description_text() {
		return esc_html__( 'Requires %s (%s), but (%s) is installed.', 'rcp' );
	}

	/**
	 * Plugin specific text to describe a single missing requirement.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_missing_text() {
		return esc_html__( 'Requires %s (%s), but it appears to be missing.', 'rcp' );
	}

	/**
	 * Plugin specific text used to link to an external requirements page.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_link() {
		return esc_html__( 'Requirements', 'rcp' );
	}

	/**
	 * Plugin specific aria label text to describe the requirements link.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_label() {
		return esc_html__( 'Restrict Content Pro Requirements', 'rcp' );
	}

	/**
	 * Plugin specific text used in CSS to identify attribute IDs and classes.
	 *
	 * @since 3.0
	 * @return string
	 */
	private function unmet_requirements_name() {
		return 'rcp-requirements';
	}

	/** Agnostic Methods ******************************************************/

	/**
	 * Plugin agnostic method to output the additional plugin row
	 *
	 * @since 3.0
	 */
	public function plugin_row_notice() {
		?><tr class="active <?php echo esc_attr( $this->unmet_requirements_name() ); ?>-row">
		<th class="check-column">
			<span class="dashicons dashicons-warning"></span>
		</th>
		<td class="column-primary">
			<?php $this->unmet_requirements_text(); ?>
		</td>
		<td class="column-description">
			<?php $this->unmet_requirements_description(); ?>
		</td>
		</tr><?php
	}

	/**
	 * Plugin agnostic method used to output all unmet requirement information
	 *
	 * @since 3.0
	 */
	private function unmet_requirements_description() {
		foreach ( $this->requirements as $properties ) {
			if ( empty( $properties['met'] ) ) {
				$this->unmet_requirement_description( $properties );
			}
		}
	}

	/**
	 * Plugin agnostic method to output specific unmet requirement information
	 *
	 * @since 3.0
	 * @param array $requirement
	 */
	private function unmet_requirement_description( $requirement = array() ) {

		// Requirement exists, but is out of date
		if ( ! empty( $requirement['exists'] ) ) {
			$text = sprintf(
				$this->unmet_requirements_description_text(),
				'<strong>' . esc_html( $requirement['name']    ) . '</strong>',
				'<strong>' . esc_html( $requirement['minimum'] ) . '</strong>',
				'<strong>' . esc_html( $requirement['current'] ) . '</strong>'
			);

			// Requirement could not be found
		} else {
			$text = sprintf(
				$this->unmet_requirements_missing_text(),
				'<strong>' . esc_html( $requirement['name']    ) . '</strong>',
				'<strong>' . esc_html( $requirement['minimum'] ) . '</strong>'
			);
		}

		// Output the description
		echo '<p>' . $text . '</p>';
	}

	/**
	 * Plugin agnostic method to output unmet requirements styling
	 *
	 * @since 3.0
	 */
	public function admin_head() {

		// Get the requirements row name
		$name = $this->unmet_requirements_name(); ?>

		<style id="<?php echo esc_attr( $name ); ?>">
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th,
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] td,
			.plugins .<?php echo esc_html( $name ); ?>-row th,
			.plugins .<?php echo esc_html( $name ); ?>-row td {
				background: #fff5f5;
			}
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th {
				box-shadow: none;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row th span {
				margin-left: 6px;
				color: #dc3232;
			}
			.plugins tr[data-plugin="<?php echo esc_html( $this->base ); ?>"] th,
			.plugins .<?php echo esc_html( $name ); ?>-row th.check-column {
				border-left: 4px solid #dc3232 !important;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row .column-description p {
				margin: 0;
				padding: 0;
			}
			.plugins .<?php echo esc_html( $name ); ?>-row .column-description p:not(:last-of-type) {
				margin-bottom: 8px;
			}
		</style>
		<?php
	}

	/**
	 * Plugin agnostic method to add the "Requirements" link to row actions
	 *
	 * @since 3.0
	 * @param array $links
	 * @return array
	 */
	public function plugin_row_links( $links = array() ) {

		// Add the Requirements link
		$links['requirements'] =
			'<a href="' . esc_url( $this->unmet_requirements_url() ) . '" aria-label="' . esc_attr( $this->unmet_requirements_label() ) . '">'
			. esc_html( $this->unmet_requirements_link() )
			. '</a>';

		// Return links with Requirements link
		return $links;
	}

	/** Checkers **************************************************************/

	/**
	 * Plugin specific requirements checker
	 *
	 * @since 3.0
	 */
	private function check() {

		// Loop through requirements
		foreach ( $this->requirements as $dependency => $properties ) {

			// Which dependency are we checking?
			switch ( $dependency ) {

				// PHP
				case 'php' :
					$version = phpversion();
					break;

				// WP
				case 'wp' :
					$version = get_bloginfo( 'version' );
					break;

				// Unknown
				default :
					$version = false;
					break;
			}

			// Merge to original array
			if ( ! empty( $version ) ) {
				$this->requirements[ $dependency ] = array_merge( $this->requirements[ $dependency ], array(
					'current' => $version,
					'checked' => true,
					'met'     => version_compare( $version, $properties['minimum'], '>=' )
				) );
			}
		}
	}

	/**
	 * Have all requirements been met?
	 *
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function met() {

		// Run the check
		$this->check();

		// Default to true (any false below wins)
		$retval  = true;
		$to_meet = wp_list_pluck( $this->requirements, 'met' );

		// Look for unmet dependencies, and exit if so
		foreach ( $to_meet as $met ) {
			if ( empty( $met ) ) {
				$retval = false;
				continue;
			}
		}

		// Return
		return $retval;
	}

	/** Translations **********************************************************/

	/**
	 * Plugin specific text-domain loader.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function load_textdomain() {

		// Set filter for plugin's languages directory
		$rcp_lang_dir = dirname( $this->base ) . '/languages/';
		$rcp_lang_dir = apply_filters( 'rcp_languages_directory', $rcp_lang_dir );


		// Traditional WordPress plugin locale filter

		$get_locale = get_locale();

		if ( version_compare( get_bloginfo( 'version' ), '4.7', '>=' ) ) {

			$get_locale = get_user_locale();
		}

		/**
		 * Defines the plugin language locale used in RCP.
		 *
		 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
		 *                  otherwise uses `get_locale()`.
		 */
		$locale        = apply_filters( 'plugin_locale',  $get_locale, 'rcp' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'rcp', $locale );

		// Setup paths to current locale file
		$mofile_local  = $rcp_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/rcp/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/rcp folder
			load_textdomain( 'rcp', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/rcp/languages/ folder
			load_textdomain( 'rcp', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'rcp', false, $rcp_lang_dir );
		}

	}

}

// Invoke the checker
new RC_Requirements_Check();

function option_exists($name, $site_wide=false){
    global $wpdb; return $wpdb->query("SELECT * FROM ". ($site_wide ? $wpdb->base_prefix : $wpdb->prefix). "options WHERE option_name ='$name' LIMIT 1");
}

/**
 * Process the switch between Legacy Restrict Content and Restrict Content 3.0
 *
 * @since 3.0
 */
function rc_process_legacy_switch() {

    if ( ! isset( $_POST['rc_process_legacy_nonce'] ) || ! wp_verify_nonce( $_POST['rc_process_legacy_nonce'], 'rc_process_legacy_nonce' ) ) {
        wp_send_json_error( array(
            'success' => false,
            'errors' => 'invalid nonce',
        ) );
        return;
    }

    if ( option_exists( 'restrict_content_pro_use_legacy_restrict_content' ) ) {
        if ( get_option( 'restrict_content_pro_use_legacy_restrict_content' ) == true ) {
            $redirectUrl = admin_url( 'admin.php?page=rcp-members' );
            update_option( 'restrict_content_pro_use_legacy_restrict_content', false );
            wp_send_json_success( array(
                'success'  => true,
                'data'     => array(
                    'redirect' => $redirectUrl
                ),
            ) );
        } else {
            $redirectUrl = admin_url( 'admin.php?page=restrict-content-settings' );
            update_option( 'restrict_content_pro_use_legacy_restrict_content', true );
            wp_send_json_success( array(
                'success'  => true,
                'data'     => array(
                    'redirect' => $redirectUrl
                )
            ) );
        }
    } else {
        $redirectUrl = admin_url( 'admin.php?page=restrict-content-settings' );
        update_option( 'restrict_content_pro_use_legacy_restrict_content', true );
        wp_send_json_success( array(
            'success'  => true,
            'data'     => array(
                'redirect' => $redirectUrl
            )
        ) );
    }
}
add_action( 'wp_ajax_rc_process_legacy_switch', 'rc_process_legacy_switch' );

function restrict_content_add_legacy_button_to_pro() {
    ?>
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
                class="button-primary"
                value="<?php _e( 'Downgrade to Legacy Restrict Content?', 'LION' ); ?>"
        />
    </td>
    <?php
}
add_action( 'rcp_misc_settings', 'restrict_content_add_legacy_button_to_pro' );

/**
 * Register admin menus
 *
 * @since 3.0
 * @return void
 */
function register_menus() {
    global $restrict_content_pro_why_go_pro, $restrict_content_pro_help_page, $restrict_content_pro_welcome_page;

    $restrict_content_pro_why_go_pro    = add_submenu_page( 'rcp-members', __( 'Why Go Pro', 'LION' ), __( 'Why go Pro', 'LION' ), 'manage_options', 'rcp-why-go-pro', 'rc_why_go_pro_page_redesign' );
    $restrict_content_pro_help_page     = add_submenu_page( 'rcp-members', __( 'Help', 'LION' ), __( 'Help', 'LION' ), 'manage_options', 'rcp-need-help', 'rc_need_help_page_redesign' );
    $restrict_content_pro_welcome_page  = add_submenu_page( null, __( 'Welcome', 'LION'), __( 'Welcome', 'LION' ), 'manage_options', 'restrict-content-welcome', 'rc_welcome_page_redesign' );
}
add_action( 'admin_menu', 'register_menus', 100 );

/**
 * Load admin styles
 */
function rc_admin_styles_primary( $hook_suffix ) {

    if ( get_option( 'restrict_content_pro_use_legacy_restrict_content' ) == false ) {
        // Only load admin CSS on Restrict Content Settings page
        if (
            'toplevel_page_restrict-content-settings' == $hook_suffix ||
            'restrict_page_rcp-why-go-pro' == $hook_suffix
        ) {
            wp_enqueue_style('rcp-settings', trailingslashit(plugins_url()) . 'restrict-content/restrict-content/includes/assets/css/rc-settings.css', array(), RCP_PLUGIN_VERSION);
            wp_enqueue_script('rcp-admin-settings-functionality', trailingslashit(plugins_url()) . 'restrict-content/restrict-content/includes/assets/js/rc-settings.js', array(), RCP_PLUGIN_VERSION);
            wp_localize_script(
                'rcp-admin-settings-functionality',
                'rcp_admin_settings_options',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'rc_process_legacy_nonce' => wp_create_nonce('rcp-settings-nonce')
                )
            );
        }

        if ('admin_page_restrict-content-welcome' == $hook_suffix || 'restrict_page_rcp-need-help' == $hook_suffix) {
            wp_enqueue_style('rcp-settings', trailingslashit(plugins_url()) . 'restrict-content/restrict-content/includes/assets/css/rc-settings.css', array(), RCP_PLUGIN_VERSION);
            wp_enqueue_style('rcp-wp-overrides', trailingslashit(plugins_url()) . 'restrict-content/restrict-content/includes/assets/css/rc-wp-overrides.css', array(), RCP_PLUGIN_VERSION);
            wp_enqueue_script('rcp-admin-settings', trailingslashit(plugins_url()) . 'restrict-content/restrict-content/includes/assets/js/rc-admin.js', array(), RCP_PLUGIN_VERSION);
        }

        wp_enqueue_style('rcp-metabox', trailingslashit(plugins_url()) . 'restrict-content/restrict-content/includes/assets/css/rc-metabox.css', array(), RCP_PLUGIN_VERSION);
    }
}
add_action( 'admin_enqueue_scripts', 'rc_admin_styles_primary' );


function rc_why_go_pro_page_redesign() {
    ?>
    <div class="wrap">
        <div class="rcp-why-go-pro-wrap">
            <img class="restrict-content-logo" src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/restrict_content_logo.svg' ); ?>" >
            <div class="rcp-go-pro-color-container">
                <div class="rcp-why-go-pro-inner-wrapper">
                    <div class="rcp-top-header">
                        <h1>
                            <?php _e( 'Why Go Pro?', 'LION' ); ?></h1>
                        <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/flying_rex.png' ); ?>" >
                    </div>
                    <h2><?php _e( 'Grow Your Sales with Premium Features and Add-ons in Restrict Content PRO', 'LION' ); ?></h2>
                    <div class="rcp-pro-features-container">
                        <!-- LIMIT NUMBER OF CONNECTIONS FEATURE -->
                        <a href="https://restrictcontentpro.com/pricing/">
                            <div class="rcp-limit-number-of-connections feature">
                                <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/memb-levels.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Limit Number of Connections', 'LION' ); ?></h3>
                                    <p><?php _e( 'Prevent password sharing by limiting the number of simultaneous connections for each member.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- REMOVE STRIPE FEE FEATURE -->
                        <a href="https://restrictcontentpro.com/tour/payment-gateways/">
                            <div class="rcp-remove-stripe-fee feature">
                                <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/collect-payments.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Remove Stripe Fee', 'LION' ); ?></h3>
                                    <p><?php _e( "Remove the 2% fee for processing Stripe payments by upgrading to Restrict Content Pro.", 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- PRO EMAILS FEATURE -->
                        <a href="https://restrictcontentpro.com/tour/features/member-emails/">
                            <div class="rcp-pro-emails feature">
                                <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/customer-dash.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Pro Emails', 'LION' ); ?></h3>
                                    <p><?php _e( 'Unlock email personalization and automatically member expiration & renewal email reminders.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- MARKETING INTEGRATION FEATURE -->
                        <a href="https://restrictcontentpro.com/add-ons/pro/">
                            <div class="rcp-marketing-integration feature">
                                <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/mkt-integration.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Marketing Integration', 'LION' ); ?></h3>
                                    <p><?php _e( 'Subscribe members to your Mailchimp, AWeber, ConvertKit, etc., mailing lists.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- GROUP ACCOUNTS FEATURE -->
                        <a href="https://restrictcontentpro.com/downloads/group-accounts/">
                            <div class="rcp-group-accounts feature">
                                <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/group-acct.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Group Accounts', 'LION' ); ?></h3>
                                    <p><?php _e( 'Sell enterprise or group memberships with multiple sub accounts.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- DRIP CONTENT FEATURE -->
                        <a href="https://restrictcontentpro.com/downloads/drip-content/">
                            <div class="rcp-drip-content feature">
                                <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/drip-content.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Drip Content', 'LION' ); ?></h3>
                                    <p><?php _e( 'Time-release content to new members based on their start date.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- OFFER DISCOUNTS FEATURE -->
                        <a href="https://restrictcontentpro.com/tour/features/discount-codes/">
                            <div class="rcp-offer-discounts feature">
                                <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/offer-discounts.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Offer Discounts', 'LION' ); ?></h3>
                                    <p><?php _e( 'Attract new customers with special promotional codes that give them a discount on the purchase of a membership.', 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- RESTRICT PAST CONTENT FEATURE -->
                        <a href="https://restrictcontentpro.com/downloads/restrict-past-content/">
                            <div class="rcp-restrict-past-content feature">
                                <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/restrict-content.svg' ); ?>" >
                                <div class="feature-text">
                                    <h3><?php _e( 'Restrict Past Content', 'LION' ); ?></h3>
                                    <p><?php _e( "Restrict content published before a member's join date.", 'LION' ); ?></p>
                                </div>
                            </div>
                        </a>
                        <!-- PREMIUM SUPPORT FEATURE -->
                        <div class="rcp-premium-support feature">
                            <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/premium-support.svg' ); ?>" >
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

function rc_need_help_page_redesign() {
    $rc_welcome_try_free_meta_nonce = wp_create_nonce( 'rc_welcome_try_free_meta_nonce' );

    ?>
    <div class="restrict-content-welcome-header">
        <img class="restrict-content-logo" src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/restrict_content_logo.svg' ); ?>" >
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
                        <img class="restrict-content-help-section-arrow hidden" style="display: none;" src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/purple-arrow-right.svg' ); ?>" >
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
                        <img class="restrict-content-help-section-arrow hidden" style="display: none;" src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/purple-arrow-right.svg' ); ?>" >
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
                        <img class="restrict-content-help-section-arrow hidden" style="display:none;" src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/purple-arrow-right.svg' ); ?>" >
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
                    <img class="restrict-content-premium-support-logo" src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/flying_rex.png' ); ?>" >
                </div>
            </div>
        </div>
        <div class="restrict-content-welcome-right-container">
            <div class="restrict-content-welcome-advertisement">
                <div class="logo">
                    <img class="restrict-content-welcome-advertisement-logo" src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/restrict-content-pro-logo-vertical-blue-black.svg' ); ?>" >
                </div>
                <div class="restrict-content-welcome-try-for-free">
                    <p><?php _e( 'Try For Free!', 'LION' ); ?></p>
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
                        <h4><?php _e( '$80', 'LION' ); ?></h4>
                        <p><?php _e( 'restrict-content/includes updates & support for one year.', 'LION' ); ?></p>
                    </div>
                    <div class="tabpanel" tabindex="0" role="tabpanel" id="10sitetab" aria-labelledby="10site" hidden="">
                        <h4><?php _e( '$100', 'LION' ); ?></h4>
                        <p><?php _e( 'restrict-content/includes updates & support for one year.', 'LION' ); ?></p>
                    </div>
                    <div class="tabpanel" tabindex="0" role="tabpanel" id="unlimitedtab" aria-labelledby="unlimited" hidden="">
                        <h4><?php _e( '$200', 'LION' ); ?></h4>
                        <p><?php _e( 'restrict-content/includes updates & support for one year.', 'LION' ); ?></p>
                    </div>
                </div>
                <a href="https://restrictcontentpro.com/pricing/" class="go-pro-now"><?php _e( 'Go Pro Now', 'LION' ); ?></a>
                <p class="whats-included"><a href="https://restrictcontentpro.com/add-ons/"><?php _e( "What's included with Pro?", 'LION' ); ?></a></p>
            </div>
        </div>
    </div>
    <?php
}

function rc_welcome_page_redesign() {
    $current_user = wp_get_current_user();

    $rc_welcome_try_free_meta_nonce = wp_create_nonce( 'rc_welcome_try_free_meta_nonce' );
    ?>
    <div class="restrict-content-welcome-header">
        <img class="restrict-content-logo" src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/restrict_content_logo.svg' ); ?>" >
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
                        <p class="restrict-content-thanks-message"><?php _e( 'Start your membership site and create multiple Membership Levels and collect payments with Stripe.', 'LION' ); ?></p>
                    </div>
                    <div class="restrict-content-welcome-standing-rex">
                        <img src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/restrict-content-pro-rex-standing.png' ); ?>" >
                    </div>
                </div>
                <div class="restrict-content-welcome-body-container">
                    <div class="restrict-content-how-to-body restrict-content-container-section">
                        <h2><?php _e( 'Collect Payments with Stripe', 'LION' ); ?></h2>
                        <p class="restrict-content-how-to-message"><?php _e( "Install the free Restrict Content Stripe add-on to start accepting credit and debit card payments.", 'LION' ); ?></p>
                        <p class="restrict-content-how-to-message"><?php _e( 'Stripe is an excellent payment gateway with a simple setup process and exceptional reliability.', 'LION' ); ?></p>
                        <p class="restrict-content-how-to-message"><?php _e( "Placeholder text for Stripe add-on sign-up link.", 'LION' ); ?></p>
                 
                    </div>
                </div>
                <div class="restrict-content-welcome-body-container">
                    <div class="restrict-content-helpful-resources restrict-content-container-section">
                        <h2><?php _e( 'Helpful Resources', 'LION' ); ?></h2>
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
                    </div>
                </div>
            </div>
        </div>
        <div class="restrict-content-welcome-right-container">
            <div class="restrict-content-welcome-advertisement">
                <div class="logo">
                    <img class="restrict-content-welcome-advertisement-logo" src="<?php echo esc_url( RCP_PLUGIN_URL . 'restrict-content/includes/assets/images/restrict-content-pro-logo-vertical-blue-black.svg' ); ?>" >
                </div>
                <div class="restrict-content-welcome-try-for-free">
                    <p><?php _e( 'Try For Free!', 'LION' ); ?></p>
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

/**
 * This function is used to add the application fee amount to Stripe purchases
 *
 * @param array $intent_args
 * @param RCP_Payment_Gateway_Stripe $rcp_stripe
 *
 * @since 3.0
 *
 * @return array
 */
function restrict_content_add_application_fee(array $intent_args, RCP_Payment_Gateway_Stripe $rcp_stripe ): array
{
    $intent_args['application_fee_amount'] = restrict_content_stripe_get_application_fee_amount( $intent_args['amount'] );

    return $intent_args;
}
add_filter( 'rcp_stripe_create_payment_intent_args', 'restrict_content_add_application_fee', 10, 2 );

/**
 * This function is used to calculate application fee amount.
 *
 * @param int $amount Donation amount.
 *
 * @since 2.5.0
 *
 * @return int
 */
function restrict_content_stripe_get_application_fee_amount( $amount ): int
{
    return round( $amount * restrict_content_stripe_get_application_fee_percentage() / 100, 0 );
}

/**
 * This function is used to get application fee percentage.
 *
 * Note: This function is for internal purpose only.
 *
 * @since 2.5.0
 *
 * @return int
 */
function restrict_content_stripe_get_application_fee_percentage(): int
{
    return 2;
}

register_activation_hook( __FILE__, function() {
    if ( current_user_can( 'manage_options' ) ) {
        add_option( 'Restrict_Content_Plugin_Activated', 'restrict-content' );
    }
} );

function restrict_content_plugin_activation_redirect() {
    if ( is_admin() && get_option( 'Restrict_Content_Plugin_Activated' ) === 'restrict-content' ) {
        delete_option('Restrict_Content_Plugin_Activated' );
        wp_safe_redirect( admin_url( 'admin.php?page=restrict-content-welcome' ) );
        die();
    }
}
add_action( 'admin_init', 'restrict_content_plugin_activation_redirect' );

function restrict_content_add_stripe_fee_notice() {
    ?>
    <p>Note: The Restrict Content Stripe payment gateway integration includes an additional 2% processing fee. You can remove the processing fee by upgrading to Restrict Content Pro.</p>
    <?php
}
add_action( 'rcp_after_stripe_help_box_admin', 'restrict_content_add_stripe_fee_notice' );

function restrict_content_add_stripe_marketing_email_capture() {
    if ( get_option( 'restrict_content_shown_stripe_marketing' ) == false ) :
        $rc_stripe_marketing_nonce = wp_create_nonce( 'restrict_content_shown_stripe_marketing' );
    ?>
    <tr>
        <th id="rcp_stripe_marketing_container" class="rcp_stripe_help_box" colspan=2 style="display: none;">
            <div id="rcp_stripe_marketing_container_inner_container" class="rcp_stripe_help_box_inner_container">
                <div class="rcp_stripe_help_box_content">
                    <h2><?php _e( 'Subscribe and Setup', 'LION' ); ?></h2>
                    <p><?php _e( 'Subscribe to get tips on using Restrict Content to grow your business and learn about new features.', 'LION' ); ?></p>
                    <input id="stripe_mailing_list" name="stripe_mailing_list_email" type="email" placeholder="Email Address">
                    <input type="checkbox" value="1" name="rc_accept_privacy_policy" id="rc_accept_privacy_policy" class="rc_accept_privacy_policy" <?php checked( true, isset( $rcp_options['disable_active_email'] ) ); ?>/>
                    <span><?php _e( 'Accept Privacy Policy', 'rcp' ); ?></span>
                    <input type="hidden" name="restrict_content_shown_stripe_marketing" id="restrict_content_shown_stripe_marketing" value="<?php echo $rc_stripe_marketing_nonce; ?>" >
                    <input type="hidden" name="action" value="restrict_content_add_to_stripe_mailing_list">
                    <button id="restrict-content-stripe-marketing-submit" class="restrict-content-welcome-button">
                        <?php _e( 'Subscribe and Setup', 'LION' ); ?>
                    </button>
                    <p class="small"><a href="#payments" id="skip_stripe_marketing_setup"><?php _e( 'Skip, setup payment gateway', 'LION' ); ?></a></p>
                </div>
            </div>
        </th>
    </tr>
    <?php
    endif;
    // Set option so that the marketing is not shown again after this point.
    // update_option( 'restrict_content_shown_stripe_marketing', TRUE );
}
add_action( 'rcp_payments_settings', 'restrict_content_add_stripe_marketing_email_capture' );

/**
 * Load admin styles
 */
function restrict_content_add_stripe_marketing_logic( $hook_suffix ) {
    if ( 'restrict_page_rcp-settings' == $hook_suffix && get_option( 'restrict_content_shown_stripe_marketing' ) == false ) {
        wp_enqueue_script(
                'restrict-content-stripe-marketing',
                trailingslashit( plugins_url() ) . 'restrict-content/core/includes/js/restrict-content-stripe-marketing.js',
                array(),
                RCP_PLUGIN_VERSION
        );
        wp_localize_script(
            'restrict-content-stripe-marketing',
            'rcp_admin_stripe_marketing',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
            )
        );
    }
}
add_action( 'admin_enqueue_scripts', 'restrict_content_add_stripe_marketing_logic' );

function restrict_content_submit_data_to_stripe_mailing_list() {
    if( isset( $_POST['restrict_content_shown_stripe_marketing'] ) && wp_verify_nonce( $_POST['restrict_content_shown_stripe_marketing'], 'restrict_content_shown_stripe_marketing' ) ) {

        $body = array(
            'account' => 'rcp',
            'list_id' => 'ebb8b55cda',
            'tags'    => ['RC-Stripe-Activation'],
            'email'   => $_POST['stripe_mailing_list_email']
        );

        $fields = array(
            'body'   => json_encode( $body )
        );

        $response = wp_remote_post( 'https://api-dev.ithemes.com/newsletter/subscribe', $fields );

        if ( ! is_wp_error( $response ) ) {
            return $response;
        } else {
            rcp_log( json_encode( $response ), true );
        }
    }
}
add_action( 'wp_ajax_restrict_content_add_to_stripe_mailing_list', 'restrict_content_submit_data_to_stripe_mailing_list' );

/**
 * Deactivates the plugin if Restrict Content Pro is activated.
 *
 * @since 2.2.1
 */
function rc_deactivate_plugin() {
    if ( is_plugin_active('restrict-content-pro/restrict-content-pro.php') ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}
add_action( 'admin_init', 'rc_deactivate_plugin' );