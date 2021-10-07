<?php
/**
 * Add Customer
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $_GET['view'] ) || 'add' != $_GET['view'] ) {
	wp_die( __( 'Something went wrong.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
}

?>
<div class="wrap">
	<h1><?php _e( 'Customers Details', 'rcp' ); ?></h1>

	<div id="rcp-item-card-wrapper">
		<div class="rcp-info-wrapper rcp-item-section rcp-customer-card-wrapper">
			<form id="rcp-edit-customer-info" method="POST">
				<div class="rcp-item-info">
					<div id="rcp-new-customer-details">
						<table class="widefat striped">
							<tbody>
							<tr>
								<th scope="row" class="row-title">
									<label for="tablecell"><?php _e( 'User Account:', 'rcp' ); ?></label>
								</th>
								<td>
									<input type="radio" id="rcp-customer-account-new" name="rcp_customer_user_account" value="new" checked="checked"/> <label for="rcp-customer-account-new"><?php _e( 'New Account', 'rcp' ); ?></label>
									<input type="radio" id="rcp-customer-account-existing" name="rcp_customer_user_account" value="existing"/> <label for="rcp-customer-account-existing"><?php _e( 'Existing Account', 'rcp' ); ?></label> <br/>
								</td>
							</tr>
							<tr class="rcp-customer-new-user-field">
								<th scope="row" class="row-title">
									<label for="tablecell"><?php _e( 'Name:', 'rcp' ); ?></label>
								</th>
								<td>
									<label for="rcp-customer-first-name" class="screen-reader-text"><?php _e( 'First Name', 'rcp' ); ?></label>
									<input type="text" id="rcp-customer-first-name" name="first_name" value="" placeholder="<?php esc_attr_e( 'First name', 'rcp' ); ?>"/>
									<label for="rcp-customer-last-name" class="screen-reader-text"><?php _e( 'Last Name', 'rcp' ); ?></label>
									<input type="text" id="rcp-customer-last-name" name="last_name" value="" placeholder="<?php esc_attr_e( 'Last name', 'rcp' ); ?>"/>
								</td>
							</tr>
							<tr>
								<th scope="row" class="row-title">
									<label for="rcp-customer-email"><?php _e( 'Email:', 'rcp' ); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-customer-email" class="rcp-user-search" data-return-field="user_email" name="user_email" value=""/>
									<div id="rcp_user_search_results"></div>
								</td>
							</tr>
							<tr class="rcp-customer-new-user-field">
								<th scope="row" class="row-title">
									<label for="rcp-customer-username"><?php _e( 'Username:', 'rcp' ); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-customer-username" name="user_login" value=""/>
								</td>
							</tr>
							<tr class="user-pass1-wrap rcp-customer-new-user-field">
								<th scope="row" class="row-title">
									<label for="pass1"><?php _e( 'Password:', 'rcp' ); ?></label>
								</th>
								<td>
									<button type="button" class="button wp-generate-pw hide-if-no-js"><?php _e( 'Show password', 'rcp' ); ?></button>
									<div class="wp-pwd hide-if-js">
										<?php $initial_password = wp_generate_password( 24 ); ?>
										<span class="password-input-wrapper">
											<input type="password" name="user_password" id="pass1" autocomplete="off" data-reveal="1" data-pw="<?php echo esc_attr( $initial_password ); ?>" aria-describedby="pass-strength-result" />
										</span>
										<button type="button" class="button wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="<?php esc_attr_e( 'Hide password', 'rcp' ); ?>">
											<span class="dashicons dashicons-hidden"></span>
											<span class="text"><?php _e( 'Hide', 'rcp-group-accounts' ); ?></span>
										</button>
										<button type="button" class="button wp-cancel-pw hide-if-no-js" data-toggle="0" aria-label="<?php esc_attr_e( 'Cancel password change', 'rcp' ); ?>">
											<span class="text"><?php _e( 'Cancel', 'rcp-group-accounts' ); ?></span>
										</button>
										<div style="display:none" id="pass-strength-result" aria-live="polite"></div>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row" class="row-title">
									<label for="rcp-customer-since"><?php _e( 'Customer Since:', 'rcp' ); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-customer-since" name="date_registered" value="<?php echo esc_attr( current_time( 'mysql' ) ); ?>"/>
								</td>
							</tr>
							</tbody>
						</table>
					</div>
				</div>

				<div id="rcp-item-edit-actions" class="edit-item">
					<input type="hidden" name="rcp-action" value="add_customer"/>
					<?php wp_nonce_field( 'rcp_add_customer_nonce', 'rcp_add_customer_nonce' ); ?>
					<input type="submit" name="rcp_update_customer" id="rcp_update_customer" class="button button-primary" value="<?php _e( 'Add Customer', 'rcp' ); ?>"/>
				</div>
			</form>
		</div>
	</div>
</div>