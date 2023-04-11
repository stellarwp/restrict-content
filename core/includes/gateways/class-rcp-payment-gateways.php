<?php
/**
 * Payment Gateways Class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Payment Gateways
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateways {

	public $available_gateways;

	public $enabled_gateways;

	/**
	 * Get things going
	 *
	 * @since 2.1
	 */
	public function __construct() {

		$this->available_gateways = $this->get_gateways();
		$this->enabled_gateways   = $this->get_enabled_gateways();

	}

	/**
	 * Retrieve a gateway by ID
	 *
	 * @since 2.1
	 * @return array|false
	 */
	public function get_gateway( $id = '' ) {

		if( isset( $this->available_gateways[ $id ] ) ) {

			return $this->available_gateways[ $id ];

		}

		return false;
	}

	/**
	 * Retrieve all registered gateways
	 *
	 * @since 2.1
	 * @return array
	 */
	private function get_gateways() {

		$gateways = array(
				'manual' => array(
						'label'        => __( 'Manual Payment', 'rcp' ),
						'admin_label'  => __( 'Manual Payment', 'rcp' ),
						'class'        => 'RCP_Payment_Gateway_Manual'
				),
				'stripe' => array(
						'label' => __('Credit / Debit Card', 'rcp'),
						'admin_label' => __('Stripe', 'rcp'),
						'class' => 'RCP_Payment_Gateway_Stripe',
						'test_card' => array(
								'number' => '4242424242424242',
								'cvc' => '123',
								'zip' => '45814',
								'link' => 'https://stripe.com/docs/testing#cards'
						)
				),
		);

		return apply_filters( 'rcp_payment_gateways', $gateways );

	}

	/**
	 * Retrieve all enabled gateways
	 *
	 * @since 2.1
	 * @return array
	 */
	private function get_enabled_gateways() {

		global $rcp_options;

		$enabled = array();
		$saved   = isset( $rcp_options['gateways'] ) ? array_map( 'trim', $rcp_options['gateways'] ) : array();

		if ( ! empty( $saved ) && is_array( $saved ) && array_key_exists( 'stripe_checkout', $saved ) ) {
			unset( $saved['stripe_checkout'] );
			if ( ! in_array( 'stripe', $saved ) ) {
				// Add normal Stripe if it's not already activated.
				$saved['stripe'] = 1;
			}
		}

		if( ! empty( $saved ) ) {

			foreach( $this->available_gateways as $key => $gateway ) {

				if( isset( $saved[ $key ] ) && $saved[ $key ] == 1 ) {

					$enabled[ $key ] = $gateway;

				}
			}

		}

		/**
		 * TODO: If PayPay is activated as default then the settings should be actiavted before sending the result.

		if( empty( $enabled ) ) {

			$enabled[ 'paypal'] = __( 'PayPal', 'rcp' );

		}
		 **/


		return apply_filters( 'rcp_enabled_payment_gateways', $enabled, $this->available_gateways );

	}

	/**
	 * Determine if a gateway is enabled
	 *
	 * @param string $id ID of the gateway to check.
	 *
	 * @since 2.1
	 * @return bool
	 */
	public function is_gateway_enabled( $id = '' ) {
		return isset( $this->enabled_gateways[ $id ] );
	}

	/**
	 * Load the fields for a gateway
	 *
	 * @since 2.1
	 * @return void
	 */
	public function load_fields() {

		if( ! empty( $_POST['rcp_gateway'] ) ) {

			$fields = $this->get_gateway_fields( $_POST['rcp_gateway'] );

			if ( ! empty( $fields ) ) {
				wp_send_json_success( array( 'success' => true, 'fields' => $fields ) );
			} else {
				wp_send_json_error( array( 'success' => false ) );
			}

		}
	}

	/**
	 * Returns the fields for a specific gateway.
	 *
	 * @param string $gateway Gateway slug.
	 *
	 * @return string|false
	 */
	public function get_gateway_fields( $gateway ) {

		$gateway_name = sanitize_text_field( $gateway );
		$gateway     = $this->get_gateway( sanitize_text_field( $gateway ) );
		$gateway_obj = false;

		if( isset( $gateway['class'] ) ) {
			$gateway_obj = new $gateway['class'];
		}

		if( ! is_object( $gateway_obj ) ) {
			return false;
		}

		/**
		 * @var RCP_Payment_Gateway $gateway_obj
		 */

		$fields = $gateway_obj->fields();

		// Add test card number.
		$show_test_card = rcp_is_sandbox() && ! empty( $gateway['test_card']['number'] );
		/**
		 * Filters whether or not the test card details should be shown.
		 *
		 * @param bool $show_test_card Whether or not the test card information should be shown.
		 * @param array $gateway Gateway details.
		 */
		$show_test_card = apply_filters( 'rcp_show_test_card_on_registration', $show_test_card, $gateway );
		if ( $show_test_card ) {
			ob_start();
			?>
			<div id="rcp-sandbox-gateway-test-cards">
				<p><?php printf( __( '<strong>Test mode is enabled.</strong> You can use the following card details for %s test transactions:', 'rcp' ), $gateway['admin_label'] ); ?></p>
				<ul>
					<li><?php printf( __( 'Number: %s', 'rcp' ), $gateway['test_card']['number'] ); ?></li>
					<?php if ( ! empty( $gateway['test_card']['cvc'] ) ) : ?>
						<li><?php printf( __( 'CVC: %s', 'rcp' ), $gateway['test_card']['cvc'] ); ?></li>
					<?php endif; ?>
					<li><?php _e( 'Expiration: any future date', 'rcp' ); ?></li>
					<?php if ( ! empty( $gateway['test_card']['zip'] ) ) : ?>
						<li><?php printf( __( 'Zip: %s', 'rcp' ), $gateway['test_card']['zip'] ); ?></li>
					<?php endif; ?>
				</ul>
				<?php if ( ! empty( $gateway['test_card']['link'] ) ) : ?>
					<p><?php printf( __( 'For more test card numbers visit the <a href="%s" target="_blank">%s documentation page</a>.', 'rcp' ), esc_url( $gateway['test_card']['link'] ), $gateway['admin_label'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php
			if( 'braintree' == $gateway_name ) {
				do_action( 'rcp_braintree_additional_fields' );
			}
			$fields = ob_get_clean() . $fields;
		}

		return $fields;

	}

}
