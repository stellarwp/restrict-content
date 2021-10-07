<?php
/**
 * User Page Columns
 *
 * Functions for adding extra columns to the Users > All Users table.
 *
 * @package     Restrict Content Pro
 * @subpackage  User Page Columns
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Add user column to display membership status.
 *
 * @param array $columns
 *
 * @return array
 */
function rcp_add_user_columns( $columns ) {
	$columns['rcp_subscription'] = rcp_multiple_memberships_enabled() ? __( 'Membership(s)', 'rcp' ) : __( 'Membership', 'rcp' );

    return $columns;
}
add_filter( 'manage_users_columns', 'rcp_add_user_columns' );

/**
 * Display user column value
 *
 * @param string $value       Column value.
 * @param string $column_name Name of the current column.
 * @param int    $user_id     ID of the user.
 *
 * @return string
 */
function rcp_show_user_columns( $value, $column_name, $user_id ) {
	if ( 'rcp_subscription' !== $column_name ) {
		return $value;
	}

	$customer    = rcp_get_customer_by_user_id( $user_id );
	$memberships = is_object( $customer ) ? $customer->get_memberships() : array();
	$admin_page  = rcp_get_memberships_admin_page();
	$user        = get_userdata( $user_id );

	$memberships_html = array();

	if ( ! empty( $memberships ) ) {
		foreach ( $memberships as $membership ) {
			/**
			 * @var RCP_Membership $membership
			 */

			$edit_page = add_query_arg( array(
				'membership_id' => urlencode( $membership->get_id() ),
				'view'          => 'edit'
			), $admin_page );

			$memberships_html[] = sprintf( '%s (%s) - <a href="%s">%s</a>', esc_html( $membership->get_membership_level_name() ), esc_html( $membership->get_status() ), esc_url( $edit_page ), __( 'Edit', 'rcp' ) );
		}
	}

	if ( empty( $memberships ) || rcp_multiple_memberships_enabled() ) {
		$add_membership_page = add_query_arg( array(
			'view' => 'add',
			'email' => urlencode( $user->user_email )
		), $admin_page );

		$memberships_html[] = '<a href="' . esc_url( $add_membership_page ) . '">' . __( 'Add Membership', 'rcp' ) . '</a>';
	}

	$value = implode( '<br />', $memberships_html );

	return $value;
}
add_filter( 'manage_users_custom_column',  'rcp_show_user_columns', 100, 3 );

/**
 * Add bulk "Add RCP Membership" button to Users > All Users table.
 *
 * @since 3.1
 * @return void
 */
function rcp_bulk_add_membership_select() {

	// Bail if current user cannot manage members.
	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		return;
	}

	$levels = rcp_get_membership_levels( array( 'number' => 999 ) );

	if ( empty( $levels ) ) {
		return;
	}
	?>
	<div style="float: right; margin: 0 4px;">
		<label class="screen-reader-shortcut" for="rcp-bulk-add-membership"><?php _e( 'Add RCP Membership', 'rcp' ); ?></label>
		<select id="rcp-bulk-add-membership" name="rcp_bulk_membership[]">
			<option value=""><?php _e( 'Add RCP Membership', 'rcp' ); ?></option>
			<?php foreach ( $levels as $level ) : ?>
				<option value="<?php echo esc_attr( $level->get_id() ); ?>"><?php echo esc_html( $level->get_name() ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php submit_button( __( 'Add', 'rcp' ), 'secondary', 'rcp_add_bulk_membership', false ); ?>
	</div>
	<?php

}
add_action( 'restrict_manage_users', 'rcp_bulk_add_membership_select' );

/**
 * Process adding bulk memberships to selected users.
 *
 * @since 3.1
 * @return void
 */
function rcp_process_bulk_add_memberships() {

	// Bail if we're not doing RCP bulk action.
	if ( empty( $_REQUEST['users'] ) || empty( $_REQUEST['rcp_bulk_membership'] ) ) {
		return;
	}

	$user_ids = array_map( 'absint', $_REQUEST['users'] );
	$level_id = absint( current( array_filter( $_REQUEST['rcp_bulk_membership'] ) ) );

	if ( empty( $user_ids ) || ! is_array( $user_ids ) || empty( $level_id ) ) {
		return;
	}

	check_admin_referer( 'bulk-users' );

	// Bail if current user cannot manage members.
	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	$current_user = wp_get_current_user();

	rcp_log( sprintf( 'User %s (ID #%d) has started bulk adding memberships for %d users.', $current_user->display_name, $current_user->ID, count( $user_ids ) ) );

	foreach ( $user_ids as $user_id ) {

		$customer = rcp_get_customer_by_user_id( $user_id );

		if ( empty( $customer ) ) {
			// Create a new customer.
			$customer_id = rcp_add_customer( array(
				'user_id' => $user_id
			) );

			if ( ! empty( $customer_id ) ) {
				$customer = rcp_get_customer( $customer_id );
			}
		}

		// Bail if we don't have a customer record.
		if ( empty( $customer ) ) {
			rcp_log( sprintf( 'Membership bulk add - error creating customer record for user #%d.', $user_id ), true );

			continue;
		}

		$membership_id = $customer->add_membership( array(
			'object_id'     => $level_id,
			'status'        => 'active',
			'signup_method' => 'manual'
		) );

		if ( empty( $membership_id ) ) {
			rcp_log( sprintf( 'Membership bulk add - error creating membership record for user #%d.', $user_id ), true );

			continue;
		}

		$membership = rcp_get_membership( $membership_id );

		if ( $membership ) {
			$membership->add_note( sprintf( __( 'Membership created via bulk action by %s (user ID #%d).', 'rcp' ), $current_user->user_login, $current_user->ID ) );
		}

	}

	wp_safe_redirect( add_query_arg( 'rcp_message', 'membership_added', admin_url( 'users.php' ) ) );
	exit;

}
add_action( 'admin_init', 'rcp_process_bulk_add_memberships', 1 );
