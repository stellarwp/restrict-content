<?php
/**
 * Define the Freemius Integrations
 *
 * @since	3.5.23
 */
class RcpFreemius {

	public function __construct() {
		$this->setup();
	}

	// Create a helper function for easy SDK access.
	public function setup()
	{
		global $rcp_freemius;

		if (!isset($rcp_freemius)) {
			// Include Freemius SDK.
			require_once RCP_PLUGIN_DIR . 'core/integrations/freemius/start.php';

			$rcp_freemius = fs_dynamic_init(array(
				'id' => '10401',
				'slug' => 'rcp',
				'type' => 'plugin',
				'public_key' => 'pk_b2fbc7855865cb002c41403a3efaa',
				'is_premium' => false,
				'has_addons' => false,
				'has_paid_plans' => false,
				'is_org_compliant' => false,
				'menu' => array(
					'slug' => 'rcp-members',
					'first-path' => 'admin.php?page=restrict-content-pro-welcome',
					'account' => false,
					'contact' => false,
					'support' => false,
				),
			));
		}

		$rcp_freemius->add_action( 'connect_message', [ $this, 'filter_connect_message_on_update',], 10, 6 );
		$rcp_freemius->add_action( 'connect_message_on_update', [ $this, 'filter_connect_message_on_update',], 10, 6 );

		return $rcp_freemius;
	}

	public function initialize(){
		// Signal that SDK was initiated.
		do_action( 'rcp_freemius_loaded' );
	}

	public function filter_connect_message_on_update(
		$message, $user_first_name, $product_title, $user_login, $site_link, $freemius_link) {

		wp_enqueue_style('rcp-freemius', RCP_WEB_ROOT . 'core/includes/css/freemius.css');

		// Add the heading HTML.
		$plugin_name = 'Restrict Content';
		$title       = '<h3>' . sprintf( esc_html__( 'We hope you love %1$s', 'rcp' ), $plugin_name ) . '</h3>';
		$html        = '';

		// Add the introduction HTML.
		$html .= '<p>';
		$html .= sprintf( esc_html__( 'Hi, %1$s! This is an invitation to help our %2$s community. If you opt-in, some data about your usage of %2$s will be shared with our teams (so they can work their butts off to improve). We will also share some helpful info on membership site management, WordPress, and our products from time to time.', 'rcp' ), $user_first_name, $plugin_name );
		$html .= '</p>';

		$html .= '<p>';
		$html .= sprintf( esc_html__( 'And if you skip this, that\'s okay! %1$s will still work just fine.', 'rcp' ), $plugin_name );
		$html .= '</p>';

		// Add the "Powered by" HTML.
		$html .= '<div class="tribe-powered-by-freemius">' . esc_html__( 'Powered by', 'rcp' ) . '</div>';

		return $title . $html;
	}

	/**
	 * Get the plugin icon URL.
	 *
	 * @since  5.0.2
	 *
	 * @return string The plugin icon URL.
	 */
	public function get_plugin_icon_url() {
		return RCP_WEB_ROOT. 'core/includes/images/Full-Logo-1.svg';
	}
}

$freemius = new RcpFreemius();
$freemius->initialize();
