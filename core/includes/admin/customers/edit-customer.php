<?php
/**
 * Edit Customer
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $_GET['customer_id'] ) || ! is_numeric( $_GET['customer_id'] ) ) {
	wp_die( __( 'Something went wrong.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
}

$customer_id = $_GET['customer_id'];
$customer    = rcp_get_customer( $customer_id );

if ( empty( $customer ) ) {
	wp_die( __( 'Something went wrong.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
}

global $rcp_options;

$user                  = get_userdata( $customer->get_user_id() );
$memberships           = $customer->get_memberships();
$payments              = $customer->get_payments( array( 'number' => 5 ) );
$payments_db           = new RCP_Payments();
$number_payments       = $payments_db->count( array( 'user_id' => $user->ID ) );
$terms_agreed          = get_user_meta( $user->ID, 'rcp_terms_agreed', true );
$privacy_policy_agreed = get_user_meta( $user->ID, 'rcp_privacy_policy_agreed', true );
$delete_customer_url   = wp_nonce_url( rcp_get_customers_admin_page( array(
	'rcp-action'  => 'delete_customer',
	'customer_id' => $customer->get_id()
) ), 'rcp_delete_customer' );
?>
<div class="wrap">
	<h1><?php _e( 'Customers Details', 'rcp' ); ?></h1>

	<div id="rcp-item-card-wrapper">
		<div class="rcp-info-wrapper rcp-item-section rcp-customer-card-wrapper">
			<form id="rcp-edit-customer-info" method="POST">
				<div class="rcp-item-info">
					<div id="rcp-customer-account">
						<p class="rcp-customer-avatar">
							<?php echo get_avatar( $user->user_email, 150 ); ?>
						</p>
						<p class="rcp-customer-account">
							<a href="<?php echo esc_url( add_query_arg( 'user_id', urlencode( $user->ID ), admin_url( 'user-edit.php' ) ) ); ?>"><?php echo esc_html( $user->user_login ); ?></a>
							<?php
							$switch_to_url = $customer->get_switch_to_url();
							if ( ! empty( $switch_to_url ) ) {
								?>
								<br/>
								<a href="<?php echo esc_url( $switch_to_url ); ?>"><?php _e( 'Switch to User', 'rcp' ); ?></a>
								<?php
							}
							?>
						</p>
						<p class="rcp-customer-lifetime-value">
							<?php printf( __( '%s Lifetime Value', 'rcp' ), rcp_currency_filter( $customer->get_lifetime_value() ) ); ?>
						</p>
					</div>

					<div id="rcp-customer-details">
						<table class="widefat striped">
							<tbody>
							<?php do_action( 'rcp_edit_member_before', $customer->get_user_id() ); ?>
							<tr>
								<th scope="row" class="row-title">
									<label for="tablecell"><?php _e( 'ID:', 'rcp' ); ?></label>
								</th>
								<td>
									<?php echo $customer->get_id(); ?>
								</td>
							</tr>
							<tr>
								<th scope="row" class="row-title">
									<label for="tablecell"><?php _e( 'Name:', 'rcp' ); ?></label>
								</th>
								<td>
									<label for="rcp-customer-first-name" class="screen-reader-text"><?php _e( 'First Name', 'rcp' ); ?></label>
									<input type="text" id="rcp-customer-first-name" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" placeholder="<?php esc_attr_e( 'First name', 'rcp' ); ?>"/>
									<label for="rcp-customer-last-name" class="screen-reader-text"><?php _e( 'Last Name', 'rcp' ); ?></label>
									<input type="text" id="rcp-customer-last-name" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" placeholder="<?php esc_attr_e( 'Last name', 'rcp' ); ?>"/>
								</td>
							</tr>
							<tr>
								<th scope="row" class="row-title">
									<label for="rcp-customer-email"><?php _e( 'Email:', 'rcp' ); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-customer-email" name="user_email" value="<?php echo esc_attr( $user->user_email ); ?>"/>
								</td>
							</tr>
							<tr>
								<th scope="row" class="row-title">
									<label for="tablecell"><?php _e( 'Customer Since:', 'rcp' ); ?></label>
								</th>
								<td>
									<?php echo $customer->get_date_registered(); ?>
								</td>
							</tr>
							<tr>
								<th scope="row" class="row-title">
									<label for="tablecell"><?php _e( 'Has Trialed:', 'rcp' ); ?></label>
								</th>
								<td>
									<?php echo $customer->has_trialed() ? __( 'Yes', 'rcp' ) : __( 'No', 'rcp' ); ?>
									<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Each customer can only sign up for one free trial.', 'rcp' ); ?>"></span>
								</td>
							</tr>
							<tr>
								<th scope="row" class="row-title">
									<label for="rcp-customer-date-registered"><?php _e( 'Last Login:', 'rcp' ); ?></label>
								</th>
								<td>
									<?php
									$last_login = $customer->get_last_login();
									if ( ! empty( $last_login ) ) {
										echo $last_login;
									} else {
										_e( 'Unknown', 'rcp' );
									}
									?>
								</td>
							</tr>
							<tr>
								<th scope="row" class="row-title">
									<label for="tablecell"><?php _e( 'Email Verification:', 'rcp' ); ?></label>
								</th>
								<td>
									<?php
									$verification_status = $customer->get_email_verification_status();

									switch ( $verification_status ) {
										case 'verified' :
											_e( 'Verified', 'rcp' );
											break;
										case 'pending' :
											_e( 'Pending', 'rcp' );

											$resend_url = rcp_get_customers_admin_page( array(
												'customer_id' => $customer->get_id(),
												'member_id'   => $customer->get_user_id(),
												'rcp-action'  => 'send_verification'
											) );

											$verify_url = rcp_get_customers_admin_page( array(
												'customer_id' => $customer->get_id(),
												'member_id'   => $customer->get_user_id(),
												'rcp-action'  => 'verify_email'
											) );
											echo '&nbsp;<a href="' . esc_url( wp_nonce_url( $resend_url, 'rcp-verification-nonce' ) ) . '" class="button" title="' . esc_attr__( 'Re-send the verification email to the customer', 'rcp' ) . '">' . __( 'Re-send Email', 'rcp' ) . '</a>&nbsp;<a href="' . esc_url( wp_nonce_url( $verify_url, 'rcp-manually-verify-email-nonce' ) ) . '" class="button" title="' . esc_attr__( 'Mark the customer as verified', 'rcp' ) . '">' . __( 'Mark Verified', 'rcp' ) . '</a>';
											break;
										default :
											_e( 'Not required', 'rcp' );
											break;
									}
									?>
								</td>
							</tr>
							<tr>
								<th scope="row" class="row-title">
									<label for="tablecell"><?php _e( 'IP Address:', 'rcp' ); ?></label>
								</th>
								<td>
									<?php
									$ips = $customer->get_ips();

									echo ! empty( $ips ) ? esc_html( implode( ', ', $ips ) ) : __( 'Unknown', 'rcp' );
									?>
								</td>
							</tr>
							<?php do_action( 'rcp_edit_member_after', $customer->get_user_id() ); ?>
							</tbody>
						</table>
					</div>
				</div>

				<div id="rcp-item-edit-actions" class="edit-item">
					<input type="hidden" name="rcp-action" value="edit_customer"/>
					<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer->get_id() ); ?>"/>
					<?php wp_nonce_field( 'rcp_edit_member_nonce', 'rcp_edit_member_nonce' ); ?>
					<input type="submit" name="rcp_update_customer" id="rcp_update_customer" class="button button-primary" value="<?php _e( 'Update Customer', 'rcp' ); ?>"/>
					&nbsp;<a href="<?php echo esc_url( $delete_customer_url ); ?>" class="rcp-delete-customer button"><?php _e( 'Delete Customer', 'rcp' ); ?></a>
				</div>
			</form>
		</div>

		<?php if ( ! empty( $rcp_options['enable_terms'] ) || ! empty( $rcp_options['enable_privacy_policy'] ) ) : ?>
			<div id="rcp-customer-agreements-wrapper" class="rcp-item-section">
				<h3><?php _e( 'Agreements', 'rcp' ); ?></h3>
				<div id="rcp-customer-agreements-terms-wrapper">
					<p><?php _e( 'Agreed to Terms:', 'rcp' ); ?></p>
					<?php
					if ( ! empty( $terms_agreed ) && is_array( $terms_agreed ) ) {
						echo '<ul>';
						foreach ( $terms_agreed as $terms_agreed_date ) {
							echo '<li>' . date_i18n( get_option( 'date_format' ) . ' H:i:s', $terms_agreed_date ) . '</li>';
						}
						echo '</ul>';
					} else {
						echo '<p>' . __( 'None', 'rcp' ) . '</p>';
					}
					?>
				</div>

				<div id="rcp-customer-agreements-privacy-policy-wrapper">
					<p><?php _e( 'Agreed to Privacy Policy:', 'rcp' ); ?></p>
					<?php
					if ( ! empty( $privacy_policy_agreed ) && is_array( $privacy_policy_agreed ) ) {
						echo '<ul>';
						foreach ( $privacy_policy_agreed as $privacy_policy_agreed_date ) {
							echo '<li>' . date_i18n( get_option( 'date_format' ) . ' H:i:s', $privacy_policy_agreed_date ) . '</li>';
						}
						echo '</ul>';
					} else {
						echo '<p>' . __( 'None', 'rcp' ) . '</p>';
					}
					?>
				</div>
			</div>
		<?php endif; ?>

		<div id="rcp-customer-memberships-wrapper" class="rcp-item-section">
			<h3><?php _e( 'Memberships:', 'rcp' ); ?></h3>
			<table class="wp-list-table widefat striped payments">
				<thead>
				<tr>
					<th class="column-primary"><?php _e( 'Membership', 'rcp' ); ?></th>
					<th><?php _e( 'Amount', 'rcp' ); ?></th>
					<th><?php _e( 'Status', 'rcp' ); ?></th>
					<th><?php _e( 'Actions', 'rcp' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( ! empty( $memberships ) ) : ?>
					<?php foreach ( $memberships as $membership ) :
						/**
						 * @var RCP_Membership $membership
						 */
						?>
						<tr>
							<td class="column-primary" data-colname="<?php esc_attr_e( 'Membership', 'rcp' ); ?>">
								<?php echo $membership->get_membership_level_name(); ?>
								<button type="button" class="toggle-row">
									<span class="screen-reader-text"><?php _e( 'Show more details', 'rcp' ); ?></span>
								</button>
							</td>
							<td data-colname="<?php esc_attr_e( 'Amount', 'rcp' ); ?>"><?php echo $membership->get_formatted_billing_cycle(); ?></td>
							<td data-colname="<?php esc_attr_e( 'Status', 'rcp' ); ?>"><?php echo rcp_get_status_label( $membership->get_status() ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Actions', 'rcp' ); ?>">
								<a href="<?php echo esc_url( rcp_get_memberships_admin_page( array( 'view' => 'edit', 'membership_id' => $membership->get_id() ) ) ); ?>"><?php _e( 'View Details', 'rcp' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="4"><?php printf( __( 'No memberships found. Would you like to <a href="%s">add one?</a>', 'rcp' ), esc_url( rcp_get_memberships_admin_page( array( 'view' => 'add', 'customer_id' => $customer->get_id() ) ) ) ); ?></td>
					</tr>
				<?php endif; ?>
				</tbody>
			</table>
			<?php if ( rcp_multiple_memberships_enabled() ) : ?>
				<div class="edit-item">
					<a href="<?php echo esc_url( rcp_get_memberships_admin_page( array( 'view' => 'add', 'customer_id' => $customer->get_id() ) ) ); ?>" class="button"><?php _e( 'Add Membership', 'rcp' ); ?></a>
				</div>
			<?php endif; ?>
		</div>

		<?php
		/**
		 * Insert content after the "Memberships" section.
		 *
		 * @param RCP_Customer $customer
		 *
		 * @since 3.1.1
		 */
		do_action( 'rcp_edit_customer_after_memberships_section', $customer );
		?>

		<div id="rcp-customer-recent-payments-wrapper" class="rcp-item-section">
			<h3><?php _e( 'Recent Payments:', 'rcp' ); ?></h3>
			<table class="wp-list-table widefat striped payments">
				<thead>
				<tr>
					<th class="column-primary"><?php _e( 'ID', 'rcp' ); ?></th>
					<th><?php _e( 'Date', 'rcp' ); ?></th>
					<th><?php _e( 'Amount', 'rcp' ); ?></th>
					<th><?php _e( 'Status', 'rcp' ); ?></th>
					<th><?php _e( 'Transaction ID', 'rcp' ); ?></th>
					<th><?php _e( 'Invoice', 'rcp' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( ! empty( $payments ) ) : ?>
					<?php foreach ( $payments as $payment ) : ?>
						<tr>
							<td class="column-primary" data-colname="<?php esc_attr_e( 'ID', 'rcp' ); ?>">
								<a href="<?php echo esc_url( add_query_arg( 'payment_id', urlencode( $payment->id ), admin_url( 'admin.php?page=rcp-payments&view=edit-payment' ) ) ); ?>"><?php echo $payment->id; ?></a>
								<button type="button" class="toggle-row">
									<span class="screen-reader-text"><?php _e( 'Show more details', 'rcp' ); ?></span>
								</button>
							</td>
							<td data-colname="<?php esc_attr_e( 'Date', 'rcp' ); ?>"><?php echo $payment->date; ?></td>
							<td data-colname="<?php esc_attr_e( 'Amount', 'rcp' ); ?>"><?php echo rcp_currency_filter( $payment->amount ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Status', 'rcp' ); ?>"><?php echo rcp_get_status_label( $payment->status ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Transaction ID', 'rcp' ); ?>"><?php echo rcp_get_merchant_transaction_id_link( $payment ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Invoice', 'rcp' ); ?>">
								<a href="<?php echo esc_url( rcp_get_invoice_url( $payment->id ) ); ?>"><?php _e( 'View Invoice', 'rcp' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ( $number_payments > 5 ) : ?>
						<tr>
							<td colspan="6">
								<a href="<?php echo esc_url( add_query_arg( 'user_id', urlencode( $customer->get_user_id() ), admin_url( 'admin.php?page=rcp-payments' ) ) ); ?>"><?php printf( __( 'View all %d payments', 'rcp' ), number_format_i18n( $number_payments ) ); ?></a>
							</td>
						</tr>
					<?php endif; ?>
				<?php else : ?>
					<tr>
						<td colspan="6"><?php _e( 'No payments found.', 'rcp' ); ?></td>
					</tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div id="rcp-customer-notes-wrapper" class="rcp-item-section">
			<h3><?php _e( 'Notes:', 'rcp' ); ?></h3>
			<div id="rcp-customer-notes" class="rcp-item-notes">
				<?php echo wpautop( $customer->get_notes() ); ?>
			</div>
			<form id="rcp-edit-customer-notes" method="POST">
				<label for="rcp-add-customer-note" class="screen-reader-text"><?php _e( 'Add Note', 'rcp' ); ?></label>
				<textarea id="rcp-add-customer-note" class="rcp-add-item-note" name="new_note" placeholder="<?php esc_attr_e( 'Add a note...', 'rcp' ); ?>"></textarea>
				<div class="edit-item">
					<input type="hidden" name="rcp-action" value="add_customer_note"/>
					<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer->get_id() ); ?>"/>
					<?php wp_nonce_field( 'rcp_add_customer_note', 'rcp_add_customer_note_nonce' ); ?>
					<input type="submit" class="button" value="<?php esc_attr_e( 'Add Note', 'rcp' ); ?>"/>
				</div>
			</form>
		</div>
	</div>
</div>
