<?php
/**
 * Define the StellarWP Telemetry Integrations
 *
 * @since   3.5.27
 * @package RCP
 */

use RCP\StellarWP\Telemetry\Opt_In\Status;
use RCP\StellarWP\Telemetry\Config;
/**
 * Define the StellarWP Telemetry Integrations and configurations.
 *
 * @since   3.5.27
 */
class RCP_Telemetry {

	/**
	 * The Container Interface.
	 *
	 * @since 3.5.27
	 * @var RCP\Container $container The RCP Container.
	 * @access protected
	 */
	protected $container;

	/**
	 * Initialize variables.
	 *
	 * @since 3.5.27
	 */
	public function __construct() {
		$this->container = Config::get_container();
	}

	/**
	 * Set up the actions and filters. We avoid adding the hooks in the constructor since it happens before some
	 * dependencies are declare.
	 *
	 * @since 3.5.27
	 * @return void
	 */
	public function init() {
		// Add Filters.
		add_filter( 'stellarwp/telemetry/restrict-content-pro/optin_args', [ $this, 'telemetry_messages' ] );
		add_filter( 'stellarwp/telemetry/restrict-content-pro/exit_interview_args', [ $this, 'exit_interview' ] );
		add_filter( 'plugin_action_links', [ $this, 'add_opt_in_link' ], 10, 2 );
		add_filter( 'admin_init', [ $this, 'update_opt_in_get_status' ] );
		add_filter( 'admin_init', [ $this, 'update_opt_in_post_status' ] );
		add_filter( 'admin_init', [ $this, 'check_interview_selection' ] );
	}

	/**
	 * We customize the Telemetry Labels for RCP.
	 *
	 * @since 3.5.27
	 * @param array $_args The Telemetry Labels.
	 * @return array The modified labels.
	 */
	public function telemetry_messages( $_args ) {
		$_args['plugin_logo']        = RCP_WEB_ROOT . 'core/includes/images/Full-Logo-1.svg';
		$_args['plugin_logo_width']  = '300';
		$_args['plugin_logo_height'] = '50';
		$_args['permissions_url']    = 'https://restrictcontentpro.com/telemetry-tracking/';
		$_args['tos_url']            = 'https://restrictcontentpro.com/terms-of-service/';
		$_args['privacy_url']        = 'https://stellarwp.com/privacy-policy/';
		$_args['heading']            = __( 'We hope you love Restrict Content Pro.', 'rcp' );

		if ( ! $this->check_freemius_status() ) {
			// translators: %s: The user name.
			$_args['intro'] = sprintf( __( "Hi, %s! This is an invitation to help our Restrict Content Pro community. If you opt-in, some data about your usage of Restrict Content Pro will be shared with our teams (so they can work their butts off to improve). We will also share some helpful info on membership site management, WordPress, and our products from time to time. And if you skip this, that's okay! Restrict Content Pro will still work just fine.", 'rcp' ), wp_get_current_user()->display_name );
		} else {
			// translators: %s: The user name.
			$_args['intro'] = sprintf( __( "Hello, %s! We just wanted to let you know that we've replaced Freemius with our own Telemetry feature. This new Telemetry removes the middle man (Freemius) and as a result is much more privacy-friendly. Rather than sending helpful information to Freemius, who then sends it to us, the information is now sent directly to us. Click 'Allow & Continue' to continue sharing this helpful information using our new Telemetry feature.", 'rcp' ), wp_get_current_user()->display_name );
		}

		return $_args;
	}
	/**
	 * Add Opt-In links to plugin actions.
	 *
	 * @param  array  $plugin_actions The Plugin Actions for each plugin.
	 * @param  string $plugin_file The main plugin file name.
	 * @since  1.0
	 * @return array The additional plugin actions.
	 */
	public function add_opt_in_link( $plugin_actions, $plugin_file ) {

		$new_actions   = array();
		$opt_in_status = $this->container->get( Status::class )->is_active();

		if ( $opt_in_status && ( basename( RCP_ROOT ) . '/restrict-content-pro.php' === $plugin_file ) ) {
			$new_actions['rcp_opt_out'] = sprintf(
				// translators: %s: The admin URL.
				__( '<a href="%1$s" alt="%2$s">Opt-Out</a>', 'rcp' ),
				// translators: %s: The Opt-Out alt text.
				esc_url( admin_url( 'plugins.php?opt-in-status=0' ) ),
				__( 'Change to Opt Out Status', 'rcp' )
			);
		} elseif ( ! $opt_in_status && ( basename( RCP_ROOT ) . '/restrict-content-pro.php' === $plugin_file ) ) {
			$new_actions['rcp_opt_in'] = sprintf(
				// translators: %s: The admin URL.
				__( '<a href="%1$s" alt="%2$s">Opt-In</a>', 'rcp' ),
				esc_url( admin_url( 'plugins.php?opt-in-status=1' ) ),
				// translators: %s: The Opt-Out alt text.
				__( 'Change to Opt In Status', 'rcp' )
			);
		}

		return array_merge( $new_actions, $plugin_actions );
	}

	/**
	 * Update the Opt-In status. This captures the link that was trigger in the Plugins Actions Page.
	 *
	 * @since 3.5.27
	 * @return void
	 */
	public function update_opt_in_get_status() {
		// Bail early if we're not saving the Opt-In Status field.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['opt-in-status'] ) ) {
			return;
		}

		$value = (int) filter_input( INPUT_GET, 'opt-in-status' );
		$this->container->get( Status::class )->set_status( $value );

		if ( $value ) {
			$redirect = add_query_arg( 'rcp_message', 'opt_in_message', esc_url( admin_url( 'plugins.php' ) ) );
		} else {
			$redirect = add_query_arg( 'rcp_message', 'opt_out_message', esc_url( admin_url( 'plugins.php' ) ) );
		}
		wp_safe_redirect( $redirect );
		exit;
	}
	/**
	 * Update the Opt-In status that is selected in the RCP Settings Misc page.
	 *
	 * @since 3.5.27
	 * @return void
	 */
	public function update_opt_in_post_status() {
		// Bail early if we're not saving the Opt-In Status field.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['opt-in-status-settings'] ) ) {
			return;
		}

		$value = (int) filter_input( INPUT_POST, 'opt-in-status-settings' );
		$this->container->get( Status::class )->set_status( $value );
	}
	/**
	 * Sets the logo and labels for the exit interview.
	 *
	 * @since 3.5.27
	 * @param array $_args The exit interview labels.
	 * @return array The custom labels.
	 */
	public function exit_interview( $_args ) {
		$_args['plugin_logo']        = RCP_WEB_ROOT . 'core/includes/images/Full-Logo-1.svg';
		$_args['plugin_logo_width']  = '300';
		$_args['plugin_logo_height'] = '50';
		$_args['plugin_logo_alt']    = 'Restrict Content Pro Logo';

		return $_args;
	}

	/**
	 *
	 * Check for specific freemius valules related to RCP and delete them, the updates the Freemius options.
	 *
	 * @return bool True if settings got deleted.
	 */
	private function wipe_rcp_freemius() {
		$fs_accounts = get_option( 'fs_accounts' );

		if ( isset( $fs_accounts['id_slug_type_path_map']['10401'] ) ) {
			unset( $fs_accounts['id_slug_type_path_map']['10401'] );
		} else {
			return false;
		}

		if ( isset( $fs_accounts['plugin_data']['rcp'] ) ) {
			unset( $fs_accounts['plugin_data']['rcp'] );
		}

		if ( isset( $fs_accounts['file_slug_map']['restrict-content-pro/restrict-content-pro.php'] ) ) {
			unset( $fs_accounts['file_slug_map']['restrict-content-pro/restrict-content-pro.php'] );
		}

		if ( isset( $fs_accounts['plugins']['rcp'] ) ) {
			unset( $fs_accounts['plugins']['rcp'] );
		}

		if ( isset( $fs_accounts['plans']['rcp'] ) ) {
			unset( $fs_accounts['plans']['rcp'] );
		}

		if ( isset( $fs_accounts['sites']['rcp'] ) ) {
			unset( $fs_accounts['sites']['rcp'] );
		}

		update_option( 'fs_accounts', $fs_accounts );
		return true;
	}

	/**
	 * Checks if Freemius is active for RCP.
	 *
	 * @return bool True if freemius was activated for RCP.
	 */
	private function check_freemius_status() {
		$fs_accounts = get_option( 'fs_accounts' );

		if ( isset( $fs_accounts['plugin_data']['rcp'] ) ) {
			if ( isset( $fs_accounts['plugin_data']['rcp']['activation_timestamp'] ) ) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Check if the user opt-in so that we can wipe the Freemius RCP data.
	 *
	 * @return void
	 */
	public function check_interview_selection() {
		// We're not attempting an action.
		if ( empty( $_POST['_wpnonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'stellarwp-telemetry' ) ) {
			return;
		}

		// We're not attempting a telemetry action.
		if ( isset( $_POST['action'] ) && 'stellarwp-telemetry' !== $_POST['action'] ) {
			return;
		}

		// The user did not respond to the opt-in modal.
		if ( ! isset( $_POST['optin-agreed'] ) ) {
			return;
		}

		// User agreed to opt-in to Telemetry.
		if ( 'true' === $_POST['optin-agreed'] ) {
			$fs_status = $this->check_freemius_status();

			if ( $fs_status ) {
				$this->wipe_rcp_freemius();
			}
		}
	}
}
