<?php
/**
 * Customers List Table
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro
 * @license   GPL2+
 * @since     3.0
 */

namespace RCP\Admin;

/**
 * Class Customers_Table
 *
 * @since   3.0
 * @package RCP\Admin
 */
class Customers_Table extends List_Table {

	/**
	 * Constructor.
	 *
	 * @since 3.0
	 * @see   WP_List_Table::__construct()
	 */
	public function __construct() {
		parent::__construct( [
			'singular' => 'Customer',
			'plural'   => 'Customers',
			'ajax'     => false,
		] );

		$this->process_bulk_action();
		$this->get_counts();
	}

	/**
	 * Get the base URL for the customers list table.
	 *
	 * @since 3.0
	 * @return string Base URL.
	 */
	public function get_base_url() {
		return rcp_get_customers_admin_page();
	}

	/**
	 * Retrieve the table columns.
	 *
	 * @since 3.0
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'name'            => __( 'Name', 'rcp' ),
			'email'           => __( 'Email', 'rcp' ),
			'date_registered' => __( 'Date Registered', 'rcp' ),
			'last_login'      => __( 'Last Login', 'rcp' ),
		);

		/*
		 * Backwards compatibility: add an "extra" column if someone is hooking into the old action to add
		 * their own column. Everything gets bundled into one column because this is the only way we can realistically
		 * do it.
		 */
		if ( has_action( 'rcp_members_page_table_header' ) ) {
			$columns['custom'] = __( 'Extra', 'rcp' );
		}

		$columns = apply_filters( 'rcp_customers_list_table_columns', $columns );

		return $columns;
	}

	/**
	 * Retrieve the sortable columns.
	 *
	 * @since 3.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'date_registered' => array( 'date_registered', false ),
			'last_login'      => array( 'last_login', false )
		);
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 3.0
	 * @return string
	 */
	protected function get_primary_column_name() {
		return 'name';
	}

	/**
	 * This function renders any other columns in the list table.
	 *
	 * @param \RCP_Customer $customer    Customer object.
	 * @param string        $column_name The name of the column
	 *
	 * @since 3.0
	 * @return string Column Name
	 */
	public function column_default( $customer, $column_name ) {

		$value = '';

		/*
		 * Backwards compatibility: show content of custom columns from old action hook.
		 */
		if ( 'custom' == $column_name && has_action( 'rcp_members_page_table_column' ) ) {
			ob_start();
			do_action( 'rcp_members_page_table_column', $customer->get_user_id() );
			$column_content = ob_get_clean();

			$value = wp_strip_all_tags( $column_content );
		}

		/**
		 * Filters the column value.
		 *
		 * @param string        $value    Column value.
		 * @param \RCP_Customer $customer Customer object.
		 *
		 * @since 3.0
		 */
		$value = apply_filters( 'rcp_customers_list_table_column_' . $column_name, $value, $customer );

		return $value;

	}

	/**
	 * Render the checkbox column.
	 *
	 * @param \RCP_Customer $customer
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_cb( $customer ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'customer_id',
			$customer->get_id()
		);
	}

	/**
	 * Render the "Name" column.
	 *
	 * @param \RCP_Customer $customer
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_name( $customer ) {

		$customer_id         = $customer->get_id();
		$user_id             = $customer->get_user_id();
		$user                = get_userdata( $user_id );
		$display_name        = ! empty( $user->display_name ) ? $user->display_name : $user->user_login;
		$edit_customer_url   = rcp_get_customers_admin_page( array(
			'customer_id' => $customer_id,
			'view'        => 'edit'
		) );
		$delete_customer_url = wp_nonce_url( rcp_get_customers_admin_page( array(
			'rcp-action'  => 'delete_customer',
			'customer_id' => $customer_id
		) ), 'rcp_delete_customer' );

		$actions = array(
			'edit_customer'   => '<a href="' . esc_url( $edit_customer_url ) . '">' . __( 'Edit Customer', 'rcp' ) . '</a>',
			'delete_customer' => '<span class="trash"><a href="' . esc_url( $delete_customer_url ) . '" class="rcp-delete-customer">' . __( 'Delete', 'rcp' ) . '</a></span>',
			'customer_id'     => '<span class="rcp-id-col">' . sprintf( __( 'ID: %d', 'rcp' ), $customer_id ) . '</span>'
		);

		ob_start();
		/**
		 * @deprecated 3.0 Use `rcp_customers_list_table_row_actions` instead.
		 */
		do_action( 'rcp_member_row_actions', $customer->get_user_id() );
		$custom_row_actions = ob_get_clean();
		if ( $custom_row_actions ) {
			$actions['custom_row_actions'] = $custom_row_actions;
		}

		/**
		 * Filters the row actions.
		 *
		 * @param array         $actions    Default actions.
		 * @param \RCP_Customer $membership Membership object.
		 *
		 * @since 3.0
		 */
		$actions = apply_filters( 'rcp_customers_list_table_row_actions', $actions, $customer );

		return '<strong><a class="row-title" href="' . esc_url( $edit_customer_url ) . '">' . esc_html( $display_name ) . '</a></strong>' . $this->row_actions( $actions );

	}

	/**
	 * Render the "Email" column.
	 *
	 * @param \RCP_Customer $customer
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_email( $customer ) {

		$user = get_userdata( $customer->get_user_id() );

		return esc_html( $user->user_email );

	}

	/**
	 * Render the "Date Registered" column.
	 *
	 * @param \RCP_Customer $customer
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_date_registered( $customer ) {
		return $customer->get_date_registered();
	}

	/**
	 * Render the "Last Login" column.
	 *
	 * @param \RCP_Customer $customer
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_last_login( $customer ) {
		$last_login = $customer->get_last_login();

		if ( empty( $last_login ) ) {
			$last_login = __( 'Unknown', 'rcp' );
		}

		return $last_login;
	}

	/**
	 * Message to be displayed when there are no customers.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No customers found.', 'rcp' );
	}

	/**
	 * Retrieve the bulk actions.
	 *
	 * @since 3.0
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
	 * @since 3.0
	 * @return void
	 */
	public function process_bulk_action() {

		// Bail if a nonce was not supplied.
		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-customers' ) ) {
			return;
		}

		$ids = wp_parse_id_list( (array) $this->get_request_var( 'customer_id', false ) );

		// Bail if no IDs
		if ( empty( $ids ) ) {
			return;
		}

		$current_user = wp_get_current_user();

		rcp_log( sprintf( '%s is performing the %s bulk action on customers: %s.', $current_user->user_login, $this->current_action(), implode( ', ', $ids ) ) );

		foreach ( $ids as $id ) {
			$customer = rcp_get_customer( absint( $id ) );

			if ( empty( $customer ) ) {
				continue;
			}

			switch ( $this->current_action() ) {
				case 'delete':
					rcp_delete_customer( $customer->get_id() );
					break;
			}
		}

		$this->show_admin_notice( $this->current_action() );

	}

	/**
	 * Show admin notice for bulk actions.
	 *
	 * @param string $action The action to show the notice for.
	 *
	 * @access private
	 * @since 3.0.8
	 * @return void
	 */
	private function show_admin_notice( $action ) {

		$message = '';

		switch ( $action ) {
			case 'delete' :
				$message = __( 'Customer(s) deleted.', 'rcp' );
				break;
		}

		if ( empty( $message ) ) {
			return;
		}

		echo '<div class="updated"><p>' . $message . '</p></div>';

	}

	/**
	 * Retrieve the customer counts.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function get_counts() {
		$this->counts = array(
			'total'   => rcp_count_customers(),
			'pending' => rcp_count_customers( array(
				'email_verification' => 'pending'
			) )
		);
	}

	/**
	 * Retrieve customers data.
	 *
	 * @param bool $count Whether or not to get customer objects (false) or just count the total number (true).
	 *
	 * @since 3.0
	 * @return array|int
	 */
	public function customers_data( $count = false ) {

		$args = array(
			'number'             => $this->per_page,
			'offset'             => $this->get_offset(),
			'orderby'            => sanitize_text_field( $this->get_request_var( 'orderby', 'id' ) ),
			'order'              => sanitize_text_field( $this->get_request_var( 'order', 'DESC' ) ),
			'email_verification' => $this->get_status(),
		);

		$search = $this->get_search();

		if ( ! empty( $search ) ) {
			/*
			 * Search by user account
			 * This process sucks because our query class doesn't do joins.
			 * @todo first name and last name
			 */

			// First we have to search for user accounts.
			$user_ids = get_users( array(
				'number' => -1,
				'search' => '*' . $this->get_search() . '*',
				'fields' => 'ids'
			) );

			// No user results - bail.
			if ( empty( $user_ids ) ) {
				return $count ? 0 : array();
			}

			// Finally, include these user IDs in the customers query.
			$args['user_id__in'] = $user_ids;

		}

		if ( $count ) {
			return rcp_count_customers( $args );
		}

		return rcp_get_customers( $args );
	}

	/**
	 * Setup the final data for the table.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->customers_data();

		$total = $this->customers_data( true );

		// Setup pagination
		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $total / $this->per_page )
		) );
	}

}
