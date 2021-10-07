<?php
/**
 * Payments List Table
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro
 * @license   GPL2+
 * @since     3.1
 */

namespace RCP\Admin;

/**
 * Class Payments_Table
 *
 * @since   3.1
 * @package RCP\Admin
 */
class Payments_Table extends List_Table {

	/**
	 * Constructor.
	 *
	 * @since 3.1
	 * @see   WP_List_Table::__construct()
	 */
	public function __construct() {
		parent::__construct( [
			'singular' => 'Payment',
			'plural'   => 'Payments',
			'ajax'     => false,
		] );

		$this->process_bulk_action();
		$this->get_counts();
	}

	/**
	 * Get the base URL for the payments list table.
	 *
	 * @since 3.1
	 * @return string Base URL.
	 */
	public function get_base_url() {

		$args = array(
			'page' => 'rcp-payments'
		);

		$payments_page = add_query_arg( $args, admin_url( 'admin.php' ) );

		return $payments_page;

	}

	/**
	 * Retrieve the table columns.
	 *
	 * @since 3.1
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'id'             => __( 'ID', 'rcp' ),
			'customer'       => __( 'Customer', 'rcp' ),
			'membership'     => __( 'Membership', 'rcp' ),
			'date'           => __( 'Date', 'rcp' ),
			'amount'         => __( 'Amount', 'rcp' ),
			'type'           => __( 'Type', 'rcp' ),
			'gateway'        => __( 'Gateway', 'rcp' ),
			'transaction_id' => __( 'Transaction ID', 'rcp' ),
			'status'         => __( 'Status', 'rcp' )
		);

		/*
		 * Backwards compatibility: add an "extra" column if someone is hooking into the old action to add
		 * their own column. Everything gets bundled into one column because this is the only way we can realistically
		 * do it.
		 */
		if ( has_action( 'rcp_payments_page_table_header' ) ) {
			$columns['custom'] = __( 'Extra', 'rcp' );
		}

		/**
		 * Filters the table columns.
		 *
		 * @param array $columns
		 *
		 * @since 3.1
		 */
		$columns = apply_filters( 'rcp_payments_list_table_columns', $columns );

		return $columns;
	}

	/**
	 * Retrieve the sortable columns.
	 *
	 * // @todo At some point we'll add amount, type, and gateway
	 *
	 * @since 3.1
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'id'             => array( 'id', false ),
			'membership'     => array( 'membership', false ),
			'date'           => array( 'date', false ),
			'transaction_id' => array( 'transaction_id', false ),
			'status'         => array( 'status', false ),
		);
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 3.1
	 * @return string
	 */
	protected function get_primary_column_name() {
		return 'id';
	}

	/**
	 * This function renders any other columns in the list table.
	 *
	 * @param object $payment     Payment object.
	 * @param string $column_name The name of the column
	 *
	 * @since 3.1
	 * @return string Column Name
	 */
	public function column_default( $payment, $column_name ) {

		$value   = '';
		$gateway = ! empty( $payment->gateway ) ? $payment->gateway : '';

		switch ( $column_name ) {

			case 'customer' :
				$user = get_userdata( $payment->user_id );

				if ( ! empty( $user ) ) {
					$value = ! empty( $user->display_name ) ? esc_html( $user->display_name ) : esc_html( $user->user_login );
				} else {
					$value =  sprintf( __( 'User #%d (deleted)', 'rcp' ), $payment->user_id );
				}
				break;

			case 'membership' :
				$value = esc_html( $payment->subscription );
				break;

			case 'date' :
				$value = esc_html( $payment->date );
				break;

			case 'amount' :
				$value = rcp_currency_filter( $payment->amount );
				break;

			case 'type' :
				if ( ! empty( $payment->transaction_type ) ) {
					$value = esc_html( rcp_get_status_label( $payment->transaction_type ) );
				} elseif ( 'manual' != $gateway ) {
					// Prevent "manual" from duplicating twice (here and gateway column).
					$value = esc_html( $payment->payment_type );
				}
				break;

			case 'gateway' :
				if ( ! empty( $gateway ) ) {
					if ( 'free' == $gateway ) {
						$value = __( 'None', 'rcp' );
					} else {
						$value = rcp_get_payment_gateway_details( $gateway, 'admin_label' );
					}
				} else {
					$value = __( 'Unknown', 'rcp' );
				}
				break;

			case 'transaction_id' :
				$value = rcp_get_merchant_transaction_id_link( $payment );
				break;

			case 'status' :
				$value = rcp_get_payment_status_label( $payment );
				break;

		}

		/*
		 * Backwards compatibility: show content of custom columns from old action hook.
		 */
		if ( 'custom' == $column_name && has_action( 'rcp_payments_page_table_column' ) ) {
			ob_start();
			do_action( 'rcp_payments_page_table_column', $payment->id );
			$column_content = ob_get_clean();

			$value = wp_strip_all_tags( $column_content );
		}

		/**
		 * Filters the column value.
		 *
		 * @param string $value   Column value.
		 * @param object $payment Payment object.
		 *
		 * @since 3.1
		 */
		$value = apply_filters( 'rcp_payments_list_table_column_' . $column_name, $value, $payment );

		return $value;

	}

	/**
	 * Render the checkbox column.
	 *
	 * @param object $payment
	 *
	 * @since 3.1
	 * @return string
	 */
	public function column_cb( $payment ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'payment_id',
			$payment->id
		);
	}

	/**
	 * Render the main ID column.
	 *
	 * @param object $payment
	 *
	 * @since 3.1
	 * @return string
	 */
	public function column_id( $payment ) {

		$edit_url              = add_query_arg( array(
			'payment_id' => absint( $payment->id ),
			'view'       => 'edit-payment'
		), $this->get_base_url() );
		$invoice_url           = rcp_get_invoice_url( $payment->id );
		$customer_url          = rcp_get_customers_admin_page( array(
			'customer_id' => ! empty( $payment->customer_id ) ? urlencode( $payment->customer_id ) : 0,
			'view'        => 'edit'
		) );
		$customer_payments_url = add_query_arg( 'user_id', urlencode( $payment->user_id ), $this->get_base_url() );
		$delete_url            = wp_nonce_url( add_query_arg( array(
			'payment_id' => urlencode( $payment->id ),
			'rcp-action' => 'delete_payment'
		), $this->get_base_url() ), 'rcp_delete_payment_nonce' );

		// Link to edit payment.
		$actions = array(
			'edit' => '<a href="' . esc_url( $edit_url ) . '" title="' . esc_attr__( 'Edit payment', 'rcp' ) . '">' . __( 'Edit', 'rcp' ) . '</a>'
		);

		// Link to view invoice.
		$actions['invoice'] = '<a href="' . esc_url( $invoice_url ) . '" title="' . esc_attr__( 'View invoice', 'rcp' ) . '">' . __( 'Invoice', 'rcp' ) . '</a>';

		// Link to view customer profile.
		if ( ! empty( $payment->customer_id ) ) {
			$actions['customer'] = '<a href="' . esc_url( $customer_url ) . '" title="' . esc_attr__( 'View customer details' ) . '">' . __( 'Customer Details', 'rcp' ) . '</a>';
		}

		/*
		 * Link to view all payments by this customer.
		 * Only display if we're not already viewing all payments for this customer.
		 */
		if ( $this->get_request_var( 'user_id' ) != $payment->user_id ) {
			$actions['customer_payments'] = '<a href="' . esc_url( $customer_payments_url ) . '" title="' . esc_attr__( 'View all payments by this customer', 'rcp' ) . '">' . __( 'Customer Payments', 'rcp' ) . '</a>';
		}

		// Link to delete this payment.
		$actions['delete'] = '<span class="trash"><a href="' . esc_url( $delete_url ) . '" title="' . esc_attr__( 'Delete payment', 'rcp' ) . '" class="rcp-delete-payment">' . __( 'Delete', 'rcp' ) . '</a></span>';

		// Display the payment ID number.
		$actions['payment_id'] = '<span class="id rcp-id-col">' . sprintf( __( 'ID: %d', 'rcp' ), $payment->id ) . '</span>';

		ob_start();
		/**
		 * @deprecated 3.1 Use `rcp_payments_list_table_row_actions` instead.
		 */
		do_action( 'rcp_payments_page_table_row_actions', $payment );
		$custom_row_actions = ob_get_clean();
		if ( $custom_row_actions ) {
			$actions['custom_row_actions'] = $custom_row_actions;
		}

		/**
		 * Filters the row actions.
		 *
		 * @param array  $actions Default actions.
		 * @param object $payment Payment object.
		 *
		 * @since 3.1
		 */
		$actions = apply_filters( 'rcp_payments_list_table_row_actions', $actions, $payment );

		$final = '<strong><a href="' . esc_url( $edit_url ) . '" title="' . esc_attr__( 'Edit payment', 'rcp' ) . '">' . esc_html( $payment->id ) . '</a></strong>';

		if ( current_user_can( 'rcp_manage_payments' ) ) {
			$final .= $this->row_actions( $actions );
		}

		return $final;

	}

	/**
	 * Message to be displayed when there are no payments.
	 *
	 * @since 3.1
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No payments found.', 'rcp' );
	}

	/**
	 * Retrieve the bulk actions.
	 *
	 * @since 3.1
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Permanently Delete', 'rcp' )
		);
	}

	/**
	 * Process bulk actions.
	 *
	 * @since 3.1
	 * @return void
	 */
	public function process_bulk_action() {

		// Bail if a nonce was not supplied.
		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-payments' ) ) {
			return;
		}

		$ids = wp_parse_id_list( (array) $this->get_request_var( 'payment_id', false ) );

		// Bail if no IDs
		if ( empty( $ids ) ) {
			return;
		}

		$payments = new \RCP_Payments();

		foreach ( $ids as $payment_id ) {
			switch ( $this->current_action() ) {
				case 'delete':
					$payments->delete( $payment_id );
					break;
			}
		}

		$this->show_admin_notice( $this->current_action(), count( $ids ) );

	}

	/**
	 * Show admin notice for bulk actions.
	 *
	 * @param string $action The action to show the notice for.
	 * @param int    $number Number of items that were processed.
	 *
	 * @access private
	 * @since  3.1
	 * @return void
	 */
	private function show_admin_notice( $action, $number = 1 ) {

		$message = '';

		switch ( $action ) {
			case 'delete' :
				$message = _n( '1 payment deleted.', sprintf( '%d payments deleted.', $number ), $number, 'rcp' );
				break;
		}

		if ( empty( $message ) ) {
			return;
		}

		echo '<div class="updated"><p>' . $message . '</p></div>';

	}

	/**
	 * Retrieve the payment counts.
	 *
	 * @since 3.1
	 * @return void
	 */
	public function get_counts() {

		$payments = new \RCP_Payments();

		$this->counts = array(
			'total'     => $payments->count(),
			'complete'  => $payments->count( array( 'status' => 'complete' ) ),
			'pending'   => $payments->count( array( 'status' => 'pending' ) ),
			'refunded'  => $payments->count( array( 'status' => 'refunded' ) ),
			'failed'    => $payments->count( array( 'status' => 'failed' ) ),
			'abandoned' => $payments->count( array( 'status' => 'abandoned' ) ),
		);

	}

	/**
	 * Retrieve payments data.
	 *
	 * @param bool $count Whether or not to get payment objects (false) or just count the total number (true).
	 *
	 * @since 3.1
	 * @return array|int
	 */
	public function payments_data( $count = false ) {

		$payments = new \RCP_Payments();

		$args = array(
			'number'  => $this->per_page,
			'offset'  => $this->get_offset(),
			'orderby' => sanitize_text_field( $this->get_request_var( 'orderby', 'id' ) ),
			'order'   => sanitize_text_field( $this->get_request_var( 'order', 'DESC' ) ),
			'status'  => $this->get_status(),
		);

		// Search
		$search_type = $this->get_request_var( 'search_type', 'transaction_id' );
		$search      = $this->get_search();
		if ( ! empty( $search ) ) {
			if ( 'user' == $search_type ) {
				// First we have to search for user accounts.
				$user_ids = get_users( array(
					'number' => 1,
					'search' => '*' . $search . '*',
					'fields' => 'ids'
				) );

				// No user results - bail.
				if ( empty( $user_ids ) ) {
					return $count ? 0 : array();
				}

				// Set the first result as the user_id arg.
				$args['user_id'] = $user_ids[0];
			} else {
				$args['s'] = $this->get_search();
			}
		}

		// User ID
		$user_id = $this->get_request_var( 'user_id' );
		if ( ! empty( $user_id ) ) {
			$args['user_id'] = absint( $user_id );
		}

		// Transaction type
		$trans_type = $this->get_request_var( 'transaction_type' );
		if ( ! empty( $trans_type ) ) {
			$args['transaction_type'] = sanitize_text_field( $trans_type );
		}

		// Membership level ID
		$object_id = $this->get_request_var( 'object_id' );
		if ( ! empty( $object_id ) ) {
			$args['object_id'] = absint( $object_id );
		}

		// Gateway
		$gateway = $this->get_request_var( 'gateway' );
		if ( ! empty( $gateway ) ) {
			$args['gateway'] = sanitize_text_field( $gateway );
		}

		// Start Date
		$start_date = $this->get_request_var( 'start-date' );
		if ( ! empty( $start_date ) ) {
			$args['date']['start'] = sanitize_text_field( $start_date );
		}

		// End Date
		$end_date = $this->get_request_var( 'end-date' );
		if ( ! empty( $end_date ) ) {
			$args['date']['end'] = sanitize_text_field( $end_date );
		}

		if ( $count ) {
			return $payments->count( $args );
		}

		return $payments->get_payments( $args );

	}

	/**
	 * Setup the final data for the table.
	 *
	 * @since 3.1
	 * @return void
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->payments_data();

		$total = $this->payments_data( true );

		// Setup pagination
		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $total / $this->per_page )
		) );
	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag.
	 *
	 * We override this so we can add the "rcp-payments" class for backwards compatibility.
	 *
	 * @since 3.1
	 * @return array List of CSS classes for the table tag.
	 */
	public function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', $this->_args['plural'], 'rcp-payments' );
	}

	/**
	 * Display extra table nav. This includes the transaction type filter.
	 *
	 * @param string $which
	 *
	 * @since 3.1
	 */
	protected function extra_tablenav( $which ) {

		if ( 'top' !== $which ) {
			return;
		}

		$start_date = isset( $_GET['start-date'] )  ? sanitize_text_field( $_GET['start-date'] ) : null;
		$end_date   = isset( $_GET['end-date'] )    ? sanitize_text_field( $_GET['end-date'] )   : null;

		$gateway  = $this->get_request_var( 'gateway', '' );
		$gateways = rcp_get_payment_gateways();

		$transaction_type = $this->get_request_var( 'transaction_type', '' );
		$types            = array(
			'new',
			'renewal',
			'upgrade',
			'downgrade'
		);

		$level_id = $this->get_request_var( 'object_id', '' );
		$levels   = rcp_get_membership_levels( array( 'number' => 999 ) );
		?>
		<div class="alignleft actions">
			<label for="rcp-payments-start-date"><?php _e( 'Start Date', 'rcp' ); ?></label>
			<input type="text" id="rcp-payments-start-date" name="start-date" class="rcp-datepicker" value="<?php echo esc_attr( $start_date ); ?>" placeholder="YYYY-mm-dd"/>
			<label for="rcp-payments-end-date"><?php _e( 'End Date', 'rcp' ); ?></label>
			<input type="text" id="rcp-payments-end-date" name="end-date" class="rcp-datepicker" value="<?php echo esc_attr( $end_date ); ?>" placeholder="YYYY-mm-dd"/>

			<?php if ( ! empty( $gateways ) ) : ?>
				<label for="rcp-payment-gateways-filter" class="screen-reader-text"><?php _e( 'Filter by gateway', 'rcp' ); ?></label>
				<select id="rcp-payment-gateways-filter" name="gateway">
					<option value="" <?php selected( $gateway, '' ); ?>><?php _e( 'All Gateways', 'rcp' ); ?></option>
					<?php foreach ( $gateways as $gateway_slug => $gateway_info ) : ?>
						<option value="<?php echo esc_attr( $gateway_slug ); ?>" <?php selected( $gateway, $gateway_slug ); ?>><?php echo $gateway_info['admin_label']; ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<label for="rcp-payment-transaction-types-filter" class="screen-reader-text"><?php _e( 'Filter by transaction type', 'rcp' ); ?></label>
			<select id="rcp-payment-transaction-types-filter" name="transaction_type">
				<option value="" <?php selected( $transaction_type, '' ); ?>><?php _e( 'All Types', 'rcp' ); ?></option>
				<?php foreach ( $types as $type ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $transaction_type, $type ); ?>><?php echo rcp_get_status_label( $type ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php if ( ! empty( $levels ) ) : ?>
				<label for="rcp-memberships-level-filter" class="screen-reader-text"><?php _e( 'Filter by membership level', 'rcp' ); ?></label>
				<select id="rcp-memberships-level-filter" name="object_id">
					<option value="" <?php selected( $level_id, '' ); ?>><?php _e( 'All Membership Levels', 'rcp' ); ?></option>
					<?php foreach ( $levels as $level ) : ?>
						<option value="<?php echo esc_attr( $level->get_id() ); ?>" <?php selected( $level_id, $level->get_id() ); ?>><?php echo esc_html( $level->get_name() ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<?php submit_button( __( 'Filter' ), '', 'filter_action', false ); ?>
		</div>
		<?php

	}

	/**
	 * Show the search field.
	 *
	 * @param string $text     Label for the search box
	 * @param string $input_id ID of the search box
	 *
	 * @since 3.1
	 */
	public function search_box( $text, $input_id ) {

		// Bail if no items and no search
		if ( ! $this->get_search() && ! $this->has_items() ) {
			return;
		}

		$orderby     = $this->get_request_var( 'orderby' );
		$order       = $this->get_request_var( 'order' );
		$search_type = $this->get_request_var( 'search_type', 'transaction_id' );
		$input_id    = $input_id . '-search-input';

		if ( ! empty( $orderby ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $orderby ) . '" />';
		}

		if ( ! empty( $order ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $order ) . '" />';
		}

		?>

		<p class="search-box">
			<label class="screen-reader-text" for="rcp-payments-search-type"><?php esc_html_e( 'Choose a field to search', 'rcp' ); ?></label>
			<select id="rcp-payments-search-type" name="search_type" style="float:left;">
				<option value="transaction_id" <?php selected( $search_type, 'transaction_id' ); ?>><?php _e( 'Transaction ID', 'rcp' ); ?></option>
				<option value="user" <?php selected( $search_type, 'user' ); ?>><?php _e( 'User Account (name, email, login)', 'rcp' ); ?></option>
			</select>
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( esc_html( $text ), 'button', false, false, array( 'ID' => 'search-submit' ) ); ?>
		</p>

		<?php
	}

}
