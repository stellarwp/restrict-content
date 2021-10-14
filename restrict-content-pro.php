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
 * Class RCP_Requirements_Check
 *
 * @since 3.0
 */
final class RCP_Requirements_Check {

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
        if ( get_option( 'rc_settings' ) && ! get_option( 'restrict_content_pro_use_legacy_restrict_content' ) ) {
            add_option( 'restrict_content_pro_use_legacy_restrict_content', true);
        }

        // If restrict_content_pro_use_legacy_restrict_content then load old Restrict Content
        if ( get_option( 'restrict_content_pro_use_legacy_restrict_content' ) ) {
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
new RCP_Requirements_Check();
