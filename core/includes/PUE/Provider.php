<?php
/**
 * Handles the update functionality of the Events Virtual plugin.
 *
 * @since   TBD
 *
 * @package Tribe\Events\Virtual
 */

namespace RCP\PUE;

use Restrict_Content_Pro;
use Tribe__PUE__Checker;

/**
 * Class PUE
 *
 * @since   TBD
 *
 * @package RCP
 */
class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * The slug used for PUE.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private static $pue_slug = 'restrict-content-pro';

	/**
	 * Plugin update URL.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $update_url = 'http://pue.theeventscalendar.com/';

	/**
	 * The PUE checker instance.
	 *
	 * @since TBD
	 *
	 * @var Tribe__PUE__Checker
	 */
	private $pue_instance;

	/**
	 * Registers the filters required by the Plugin Update Engine.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
		$this->container->singleton( PUE::class, PUE::class );

		add_action( 'tribe_helper_activation_complete', [ $this, 'load_plugin_update_engine' ] );

		if ( ! is_multisite() || is_super_admin() ) {
			add_filter( 'plugin_action_links', [ $this, 'filter_plugin_action_links' ], 100, 4 );
		}

		add_filter( 'network_admin_plugin_action_links', [ $this, 'filter_plugin_action_links' ], 100, 4 );

		if ( did_action( 'tribe_helper_activation_complete' ) ) {
			$this->load_plugin_update_engine();
		}

		register_uninstall_hook( Restrict_Content_Pro::FILE, [ 'tribe_events_virtual_uninstall' ] );
	}

	/**
	 * If the PUE Checker class exists, go ahead and create a new instance to handle
	 * update checks for this plugin.
	 *
	 * @since TBD
	 */
	public function load_plugin_update_engine() {
		/**
		 * Filters whether Events Virtual PUE component should manage the plugin updates or not.
		 *
		 * @since TBD
		 *
		 * @param bool   $pue_enabled Whether Events Virtual PUE component should manage the plugin updates or not.
		 * @param string $pue_slug    The Events Virtual plugin slug used to register it in the Plugin Update Engine.
		 */
		$pue_enabled = apply_filters( 'tribe_enable_pue', true, static::get_slug() );

		if ( ! ( $pue_enabled && class_exists( 'Tribe__PUE__Checker' ) ) ) {
			return;
		}

		$this->pue_instance = new Tribe__PUE__Checker(
			$this->update_url,
			static::get_slug(),
			[],
			plugin_basename( Restrict_Content_Pro::FILE )
		);
	}

	/**
	 * Get the PUE slug for this plugin.
	 *
	 * @since TBD
	 *
	 * @return string PUE slug.
	 */
	public static function get_slug() {
		return static::$pue_slug;
	}

	/**
	 * Filter the plugin action links to remove the Licenses link that iThemes Licensing adds.
	 *
	 * @since TBD
	 *
	 * @param array<mixed> $actions
	 * @param string $plugin_file
	 * @return array<mixed>
	 */
	public function filter_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		if ( ! preg_match( '/restrict-content-pro/', $plugin_file ) ) {
			return $actions;
		}

		if ( ! PUE::has_embedded_license() ) {
			return $actions;
		}

		$actions = array_filter( $actions, static function( $val ) {
			return ! preg_match( '/page\=ithemes-licensing/', $val );
		} );

		return $actions;
	}
}
