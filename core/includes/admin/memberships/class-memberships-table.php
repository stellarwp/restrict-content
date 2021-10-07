<?php
/**
 * Memberships List Table
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro
 * @license   GPL2+
 * @since     3.0
 */

namespace RCP\Admin;

/**
 * Class Memberships_Table
 *
 * @since   3.0
 * @package RCP\Admin
 */
class Memberships_Table extends List_Table {

	/**
	 * Constructor.
	 *
	 * @since 3.0
	 * @see   WP_List_Table::__construct()
	 */
	public function __construct() {
		parent::__construct( [
			'singular' => 'Membership',
			'plural'   => 'Memberships',
			'ajax'     => false,
		] );

		$this->process_bulk_action();
		$this->get_counts();
	}

	/**
	 * Get the base URL for the memberships list table.
	 *
	 * @since 3.0
	 * @return string Base URL.
	 */
	public function get_base_url() {
		return rcp_get_memberships_admin_page();
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
			'customer'        => __( 'Customer', 'rcp' ),
			'object_id'       => __( 'Membership', 'rcp' ),
			'status'          => __( 'Status', 'rcp' ),
			'auto_renew'      => __( 'Recurring', 'rcp' ),
			'created_date'    => __( 'Created', 'rcp' ),
			'expiration_date' => __( 'Expiration', 'rcp' ),
		);

		/*
		 * Backwards compatibility: add an "extra" column if someone is hooking into the old action to add
		 * their own column. Everything gets bundled into one column because this is the only way we can realistically
		 * do it.
		 */
		if ( has_action( 'rcp_members_page_table_header' ) ) {
			$columns['custom'] = __( 'Extra', 'rcp' );
		}

		$columns = apply_filters( 'rcp_memberships_list_table_columns', $columns );

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
			'object_id'       => array( 'object_id', false ),
			'status'          => array( 'status', false ),
			'auto_renew'      => array( 'auto_renew', false ),
			'created_date'    => array( 'created_date', false ),
			'expiration_date' => array( 'expiration_date', false )
		);
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 3.0
	 * @return string
	 */
	protected function get_primary_column_name() {
		return 'customer';
	}

	/**
	 * This function renders any other columns in the list table.
	 *
	 * @param \RCP_Membership $membership  Membership object.
	 * @param string          $column_name The name of the column
	 *
	 * @since 3.0
	 * @return string Column Name
	 */
	public function column_default( $membership, $column_name ) {

		$value = '';

		/*
		 * Backwards compatibility: show content of custom columns from old action hook.
		 */
		if ( 'custom' == $column_name && has_action( 'rcp_members_page_table_column' ) ) {
			$customer = $membership->get_customer();
			$user_id  = $customer instanceof \RCP_Customer ? $customer->get_user_id() : 0;
			ob_start();
			do_action( 'rcp_members_page_table_column', $user_id );
			$column_content = ob_get_clean();

			$value = wp_strip_all_tags( $column_content );
		}

		/**
		 * Filters the column value.
		 *
		 * @param string          $value      Column value.
		 * @param \RCP_Membership $membership Membership object.
		 *
		 * @since 3.0
		 */
		$value = apply_filters( 'rcp_memberships_list_table_column_' . $column_name, $value, $membership );

		return $value;

	}

	/**
	 * Render the checkbox column.
	 *
	 * @param \RCP_Membership $membership
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_cb( $membership ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'membership_id',
			$membership->get_id()
		);
	}

	/**
	 * Render the "Customer" column.
	 *
	 * @param \RCP_Membership $membership
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_customer( $membership ) {

		$membership_id       = $membership->get_id();
		$customer            = $membership->get_customer();
		$user_id             = $customer instanceof \RCP_Customer ? $customer->get_user_id() : 0;
		$user                = ! empty( $user_id ) ? get_userdata( $user_id ) : false;

		if ( $user instanceof \WP_User ) {
			$display_name = ! empty( $user->display_name ) ? $user->display_name : $user->user_login;
		} else {
			$display_name = __( '(Unknown)', 'rcp' );
		}

		$edit_membership_url = rcp_get_memberships_admin_page( array(
			'membership_id' => absint( $membership_id ),
			'view'          => 'edit'
		) );
		$edit_customer_url   = $customer instanceof \RCP_Customer ? rcp_get_customers_admin_page( array(
			'customer_id' => $customer->get_id(),
			'view'        => 'edit'
		) ) : '';
		$cancel_url          = wp_nonce_url( add_query_arg( array(
			'rcp-action'    => 'cancel_membership',
			'membership_id' => urlencode( $membership_id )
		), $this->get_base_url() ), 'cancel_membership' );

		$actions = array(
			'edit_membership' => '<a href="' . esc_url( $edit_membership_url ) . '">' . __( 'Edit Membership', 'rcp' ) . '</a>',
		);

		// Only add Edit Customer link if we have a customer.
		if ( ! empty( $edit_customer_url ) ) {
			$actions['edit_customer'] = '<a href="' . esc_url( $edit_customer_url ) . '">' . __( 'Edit Customer', 'rcp' ) . '</a>';
		}

		if ( $membership->can_cancel() ) {
			$actions['cancel'] = '<a href="' . esc_url( $cancel_url ) . '" class="rcp_cancel">' . __( 'Cancel', 'rcp' ) . '</a>';
		}

		// Membership ID goes last.
		$actions['membership_id'] = '<span class="rcp-id-col">' . sprintf( __( 'ID: %d', 'rcp' ), $membership_id ) . '</span>';

		ob_start();
		/**
		 * @deprecated 3.0 Use `rcp_memberships_list_table_row_actions` instead.
		 */
		do_action( 'rcp_member_row_actions', $user_id );
		$custom_row_actions = ob_get_clean();
		if ( $custom_row_actions ) {
			$actions['custom_row_actions'] = $custom_row_actions;
		}

		/**
		 * Filters the row actions.
		 *
		 * @param array           $actions    Default actions.
		 * @param \RCP_Membership $membership Membership object.
		 *
		 * @since 3.0
		 */
		$actions = apply_filters( 'rcp_memberships_list_table_row_actions', $actions, $membership );

		return '<strong><a class="row-title" href="' . esc_url( $edit_membership_url ) . '">' . esc_html( $display_name ) . '</a></strong>' . $this->row_actions( $actions );

	}

	/**
	 * Render the "Membership" column.
	 *
	 * @param \RCP_Membership $membership
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_object_id( $membership ) {
		return $membership->get_membership_level_name();
	}

	/**
	 * Render the "Status" column.
	 *
	 * @param \RCP_Membership $membership
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_status( $membership ) {
		return rcp_print_membership_status( $membership->get_id(), false );
	}

	/**
	 * Render the "Recurring" column.
	 *
	 * @param \RCP_Membership $membership
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_auto_renew( $membership ) {
		return $membership->is_recurring() ? __( 'Yes', 'rcp' ) : __( 'No', 'rcp' );
	}

	/**
	 * Render the "Created" column.
	 *
	 * @param \RCP_Membership $membership
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_created_date( $membership ) {
		return $membership->get_created_date();
	}

	/**
	 * Render the "Expiration" column.
	 *
	 * @param \RCP_Membership $membership
	 *
	 * @since 3.0
	 * @return string
	 */
	public function column_expiration_date( $membership ) {
		return $membership->get_expiration_date();
	}

	/**
	 * Message to be displayed when there are no memberships.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No memberships found.', 'rcp' );
	}

	/**
	 * Retrieve the bulk actions.
	 *
	 * @since 3.0
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'activate' => __( 'Activate', 'rcp' ),
			'expire'   => __( 'Expire', 'rcp' ),
			'cancel'   => __( 'Cancel', 'rcp' ),
			'delete'   => __( 'Permanently Delete', 'rcp' )
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

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-memberships' ) ) {
			return;
		}

		$ids = wp_parse_id_list( (array) $this->get_request_var( 'membership_id', false ) );

		// Bail if no IDs
		if ( empty( $ids ) ) {
			return;
		}

		$current_user = wp_get_current_user();

		foreach ( $ids as $id ) {
			$membership = rcp_get_membership( absint( $id ) );

			if ( empty( $membership ) ) {
				continue;
			}

			switch ( $this->current_action() ) {
				case 'activate':
					$membership->activate();
					break;

				case 'expire':
					$membership->add_note( sprintf( __( 'Membership expired via bulk action by user %s (#%d).', 'rcp' ), $current_user->user_login, $current_user->ID ) );
					$membership->expire();
					break;

				case 'cancel':
					$membership->add_note( sprintf( __( 'Membership cancelled via bulk action by user %s (#%d).', 'rcp' ), $current_user->user_login, $current_user->ID ) );
					if ( $membership->can_cancel() ) {
						$membership->cancel_payment_profile();
					} else {
						$membership->cancel();
					}
					break;

				case 'delete':
					$membership->disable(); // we don't truly delete
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
			case 'activate' :
				$message = __( 'Membership(s) activated.', 'rcp' );
				break;

			case 'expire' :
				$message = __( 'Membership(s) expired.', 'rcp' );
				break;

			case 'cancel' :
				$message = __( 'Membership(s) cancelled.', 'rcp' );
				break;

			case 'delete' :
				$message = __( 'Membership(s) deleted.', 'rcp' );
				break;
		}

		if ( empty( $message ) ) {
			return;
		}

		echo '<div class="updated"><p>' . $message . '</p></div>';

	}

	/**
	 * Retrieve the membership counts.
	 *
	 * @since 3.0
	 * @return void
	 */
	public function get_counts() {
		$this->counts = rcp_get_membership_counts();
	}

	/**
	 * Retrieve memberships data.
	 *
	 * @param bool $count Whether or not to get membership objects (false) or just count the total number (true).
	 *
	 * @since 3.0
	 * @return array|int
	 */
	public function memberships_data( $count = false ) {
		$search_type = $this->get_request_var( 'search_type', '' );

		$args = array(
			'number'    => $this->per_page,
			'offset'    => $this->get_offset(),
			'orderby'   => sanitize_text_field( $this->get_request_var( 'orderby', 'date_modified' ) ),
			'order'     => sanitize_text_field( $this->get_request_var( 'order', 'DESC' ) ),
			'object_id' => sanitize_text_field( $this->get_request_var( 'object_id', '' ) ),
			'status'    => $this->get_status(),
		);

		/*
		 * Filter by customer ID
		 */
		$customer_id = $this->get_request_var( 'customer_id' );
		if ( ! empty( $customer_id ) ) {
			$args['customer_id'] = absint( $customer_id );
		}

		$search = $this->get_search();
		if ( ! empty( $search ) ) {
			if ( 'user' == $search_type ) {
				/*
				 * Search by user account
				 * This process sucks because our query class doesn't do joins.
				 */

				// First we have to search for user accounts.
				$user_ids = get_users( array(
					'number' => -1,
					'search' => '*' . $search . '*',
					'fields' => 'ids'
				) );

				// No user results - bail.
				if ( empty( $user_ids ) ) {
					return $count ? 0 : array();
				}

				// Now get all customers based on these accounts.
				$customer_ids = rcp_get_customers( array(
					'number'      => 999,
					'user_id__in' => $user_ids,
					'fields'      => 'ID'
				) );

				// No customer results - bail.
				if ( empty( $customer_ids ) ) {
					return $count ? 0 : array();
				}

				// Finally, include these customer IDs in the memberships query.
				$args['customer_id__in'] = $customer_ids;

			} else {
				/*
				 * Search by any membership field.
				 */
				$args['search'] = $search;
			}
		}

		if ( $count ) {
			return rcp_count_memberships( $args );
		}

		return rcp_get_memberships( $args );
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
		$this->items           = $this->memberships_data();

		$total = $this->memberships_data( true );

		// Setup pagination
		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $total / $this->per_page )
		) );
	}

	/**
	 * Display extra table nav. This includes the membership level filter.
	 *
	 * @param string $which
	 *
	 * @since 3.0
	 */
	protected function extra_tablenav( $which ) {

		if ( 'top' !== $which ) {
			return;
		}

		$level_id = $this->get_request_var( 'object_id', '' );
		$levels   = rcp_get_membership_levels( array( 'number' => 999 ) );

		if ( empty( $levels ) ) {
			return;
		}
		?>
		<div class="alignleft actions">
			<label for="rcp-memberships-level-filter" class="screen-reader-text"><?php _e( 'Filter by membership level', 'rcp' ); ?></label>
			<select id="rcp-memberships-level-filter" name="object_id">
				<option value="" <?php selected( $level_id, '' ); ?>><?php _e( 'All Membership Levels', 'rcp' ); ?></option>
				<?php foreach ( $levels as $level ) : ?>
					<option value="<?php echo esc_attr( $level->get_id() ); ?>" <?php selected( $level_id, $level->get_id() ); ?>><?php echo esc_html( $level->get_name() ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php if ( $this->get_status() ) : ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( $this->get_status() ); ?>" />
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
	 * @since 3.0
	 */
	public function search_box( $text, $input_id ) {

		// Bail if no items and no search
		if ( ! $this->get_search() && ! $this->has_items() ) {
			return;
		}

		$orderby     = $this->get_request_var( 'orderby' );
		$order       = $this->get_request_var( 'order' );
		$search_type = $this->get_request_var( 'search_type', 'user' );
		$input_id    = $input_id . '-search-input';

		if ( ! empty( $orderby ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $orderby ) . '" />';
		}

		if ( ! empty( $order ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $order ) . '" />';
		}

		?>

		<p class="search-box">
			<label class="screen-reader-text" for="rcp-memberships-search-type"><?php esc_html_e( 'Choose a field to search', 'rcp' ); ?></label>
			<select id="rcp-memberships-search-type" name="search_type" style="float:left;">
				<option value="user" <?php selected( $search_type, 'user' ); ?>><?php _e( 'User Account (name, email, login)', 'rcp' ); ?></option>
				<option value="gateway_id" <?php selected( $search_type, 'gateway_id' ); ?>><?php _e( 'Gateway Subscription ID', 'rcp' ); ?></option>
			</select>
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( esc_html( $text ), 'button', false, false, array( 'ID' => 'search-submit' ) ); ?>
		</p>

		<?php
	}

}
