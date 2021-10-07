<?php
/**
 * Manual Payment Gateway
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Gateways/Manual
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_Manual extends RCP_Payment_Gateway {

	/**
	 * Get things going
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function init() {

		$this->supports[]  = 'one-time';
		$this->supports[]  = 'fees';
		$this->supports[]  = 'expiration-extension-on-renewals'; // @link https://github.com/restrictcontentpro/restrict-content-pro/issues/1259

	}

	/**
	 * Process registration
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function process_signup() {

		/**
		 * @var RCP_Payments $rcp_payments_db
		 */
		global $rcp_payments_db;

		$member = new RCP_Member( $this->user_id );

		/**
		 * Subscription activation is handled when the pending payment is manually updated to "Complete".
		 * @see rcp_complete_registration()
		 */

		// Update payment record with transaction ID.
		$rcp_payments_db->update( $this->payment->id, array(
			'payment_type'   => 'manual',
			'transaction_id' => $this->generate_transaction_id()
		) );

		do_action( 'rcp_process_manual_signup', $member, $this->payment->id, $this );

		wp_redirect( $this->return_url ); exit;

	}

}
