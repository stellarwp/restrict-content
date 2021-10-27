<?php
/**
 * RCP Integrations class
 *
 * This class handles loading integration files.
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Integrations
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

class RCP_Integrations {

	/**
	 * Load plugin integrations.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		add_action( 'plugins_loaded', array( $this, 'load' ) );

	}

	/**
	 * Get a list of available integrations.
	 *
	 * @access public
	 * @return array
	 */
	public function get_integrations() {

		return apply_filters( 'rcp_integrations', array(
			'woocommerce'            => 'WooCommerce',
			'google-authenticator'   => 'Google Authenticator',
			'wp-approve-user'        => 'WP Approve User',
			'easy-digital-downloads' => 'Easy Digital Downloads'
		) );

	}

	/**
	 * Load integration files.
	 *
	 * @access public
	 * @return void
	 */
	public function load() {

		do_action( 'rcp_integrations_load' );

		foreach( $this->get_integrations() as $filename => $integration ) {

			if( file_exists( RCP_PLUGIN_DIR . 'core/includes/integrations/class-rcp-' . $filename . '.php' ) ) {
				require_once RCP_PLUGIN_DIR . 'core/includes/integrations/class-rcp-' . $filename . '.php';
			}

		}

		do_action( 'rcp_integrations_loaded' );

	}

}
new RCP_Integrations;
