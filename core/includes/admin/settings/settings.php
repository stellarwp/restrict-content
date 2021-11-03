<?php
/**
 * Settings
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Register the plugins settings
 *
 * @return void
 */
function rcp_register_settings() {
	// create whitelist of options
	register_setting( 'rcp_settings_group', 'rcp_settings', 'rcp_sanitize_settings' );
}
add_action( 'admin_init', 'rcp_register_settings' );

/**
 * Render the settings page
 *
 * @return void
 */
function rcp_settings_page() {
	global $rcp_options;

	$defaults = array(
		'currency_position'     => 'before',
		'currency'              => 'USD',
		'registration_page'     => 0,
		'redirect'              => 0,
		'redirect_from_premium' => 0,
		'login_redirect'        => 0,
		'email_header_img'      => '',
		'email_header_text'     => __( 'Hello', 'rcp' )
	);

	$rcp_options = wp_parse_args( $rcp_options, $defaults );

	?>
	<div id="rcp-settings-wrap" class="wrap">
		<?php
		if ( ! isset( $_REQUEST['updated'] ) )
			$_REQUEST['updated'] = false;
		?>

		<h1><?php _e( 'Restrict Content Pro', 'rcp' ); ?></h1>

		<?php if( ! empty( $_GET['rcp_gateway_connect_error'] ) ): ?>
		<div class="notice error">
			<p><?php printf( __( 'There was an error processing your gateway connection request. Code: %s. Message: %s. Please <a href="%s">try again</a>.', 'rcp' ), esc_html( urldecode( $_GET['rcp_gateway_connect_error'] ) ), esc_html( urldecode( $_GET['rcp_gateway_connect_error_description'] ) ), esc_url( admin_url( 'admin.php?page=rcp-settings#payments' ) ) ); ?></p>
		</div>
		<?php return; endif; ?>

		<h2 class="nav-tab-wrapper">
			<a href="#general" id="general-tab" class="nav-tab"><?php _e( 'General', 'rcp' ); ?></a>
			<a href="#payments" id="payments-tab" class="nav-tab"><?php _e( 'Payments', 'rcp' ); ?></a>
			<?php do_action( 'restrict_content_pro_add_admin_settings_email_tab' ); ?>
			<a href="#invoices" id="invoices-tab" class="nav-tab"><?php _e( 'Invoices', 'rcp' ); ?></a>
			<a href="#misc" id="misc-tab" class="nav-tab"><?php _e( 'Misc', 'rcp' ); ?></a>
		</h2>
		<?php if ( false !== $_REQUEST['updated'] ) : ?>
		<div class="updated fade"><p><strong><?php _e( 'Options saved', 'rcp' ); ?></strong></p></div>
		<?php endif; ?>
		<form method="post" action="options.php" class="rcp_options_form">

			<?php settings_fields( 'rcp_settings_group' ); ?>

			<?php $pages = get_pages(); ?>


			<div id="tab_container">

				<div class="tab_content" id="general">
					<table class="form-table">
						<tr valign="top">
							<th colspan=2>
								<h3><?php _e( 'General', 'rcp' ); ?></h3>
							</th>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[registration_page]"><?php _e( 'Registration Page', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp_settings[registration_page]" name="rcp_settings[registration_page]">
									<?php
									if($pages) :
										foreach ( $pages as $page ) {
										  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['registration_page'], false) . '>';
											$option .= $page->post_title;
											$option .= ' (ID: ' . $page->ID . ')';
											$option .= '</option>';
											echo $option;
										}
									else :
										echo '<option>' . __('No pages found', 'rcp' ) . '</option>';
									endif;
									?>
								</select>
								<?php if ( ! empty( $rcp_options['registration_page'] ) ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $rcp_options['registration_page'] ) ); ?>" class="button-secondary"><?php _e( 'Edit Page', 'rcp' ); ?></a>
									<a href="<?php echo esc_url( get_permalink( $rcp_options['registration_page'] ) ); ?>" class="button-secondary"><?php _e( 'View Page', 'rcp' ); ?></a>
								<?php endif; ?>
								<p class="description"><?php printf( __( 'Choose the primary registration page. This must contain the [register_form] short code. Additional registration forms may be added to other pages with [register_form id="x"]. <a href="%s" target="_blank">See documentation</a>.', 'rcp' ), 'http://docs.restrictcontentpro.com/article/1597-registerform' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[redirect]"><?php _e( 'Success Page', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp_settings[redirect]" name="rcp_settings[redirect]">
									<?php
									if($pages) :
										foreach ( $pages as $page ) {
										  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['redirect'], false) . '>';
											$option .= $page->post_title;
											$option .= ' (ID: ' . $page->ID . ')';
											$option .= '</option>';
											echo $option;
										}
									else :
										echo '<option>' . __('No pages found', 'rcp' ) . '</option>';
									endif;
									?>
								</select>
								<?php if ( ! empty( $rcp_options['redirect'] ) ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $rcp_options['redirect'] ) ); ?>" class="button-secondary"><?php _e( 'Edit Page', 'rcp' ); ?></a>
									<a href="<?php echo esc_url( get_permalink( $rcp_options['redirect'] ) ); ?>" class="button-secondary"><?php _e( 'View Page', 'rcp' ); ?></a>
								<?php endif; ?>
								<p class="description"><?php _e( 'This is the page users are redirected to after a successful registration.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[account_page]"><?php _e( 'Account Page', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp_settings[account_page]" name="rcp_settings[account_page]">
									<?php
									if($pages) :
										$rcp_options['account_page'] = isset( $rcp_options['account_page'] ) ? absint( $rcp_options['account_page'] ) : 0;
										foreach ( $pages as $page ) {
										  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['account_page'], false) . '>';
											$option .= $page->post_title;
											$option .= ' (ID: ' . $page->ID . ')';
											$option .= '</option>';
											echo $option;
										}
									else :
										echo '<option>' . __('No pages found', 'rcp' ) . '</option>';
									endif;
									?>
								</select>
								<?php if ( ! empty( $rcp_options['account_page'] ) ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $rcp_options['account_page'] ) ); ?>" class="button-secondary"><?php _e( 'Edit Page', 'rcp' ); ?></a>
									<a href="<?php echo esc_url( get_permalink( $rcp_options['account_page'] ) ); ?>" class="button-secondary"><?php _e( 'View Page', 'rcp' ); ?></a>
								<?php endif; ?>
								<p class="description"><?php printf( __( 'This page displays the account and membership information for members. Contains <a href="%s" target="_blank">[subscription_details] short code</a>.', 'rcp' ), 'http://docs.restrictcontentpro.com/article/1600-subscriptiondetails' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[edit_profile]"><?php _e( 'Edit Profile Page', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp_settings[edit_profile]" name="rcp_settings[edit_profile]">
									<?php
									if($pages) :
										$rcp_options['edit_profile'] = isset( $rcp_options['edit_profile'] ) ? absint( $rcp_options['edit_profile'] ) : 0;
										foreach ( $pages as $page ) {
										  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['edit_profile'], false) . '>';
											$option .= $page->post_title;
											$option .= ' (ID: ' . $page->ID . ')';
											$option .= '</option>';
											echo $option;
										}
									else :
										echo '<option>' . __('No pages found', 'rcp' ) . '</option>';
									endif;
									?>
								</select>
								<?php if ( ! empty( $rcp_options['edit_profile'] ) ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $rcp_options['edit_profile'] ) ); ?>" class="button-secondary"><?php _e( 'Edit Page', 'rcp' ); ?></a>
									<a href="<?php echo esc_url( get_permalink( $rcp_options['edit_profile'] ) ); ?>" class="button-secondary"><?php _e( 'View Page', 'rcp' ); ?></a>
								<?php endif; ?>
								<p class="description"><?php printf( __( 'This page displays a profile edit form for logged-in members. Contains <a href="%s" target="_blank">[rcp_profile_editor] shortcode.', 'rcp' ), 'http://docs.restrictcontentpro.com/article/1602-rcpprofileeditor' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[update_card]"><?php _e( 'Update Billing Card Page', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp_settings[update_card]" name="rcp_settings[update_card]">
									<?php
									if($pages) :
										$rcp_options['update_card'] = isset( $rcp_options['update_card'] ) ? absint( $rcp_options['update_card'] ) : 0;
										foreach ( $pages as $page ) {
										  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['update_card'], false) . '>';
											$option .= $page->post_title;
											$option .= ' (ID: ' . $page->ID . ')';
											$option .= '</option>';
											echo $option;
										}
									else :
										echo '<option>' . __('No pages found', 'rcp' ) . '</option>';
									endif;
									?>
								</select>
								<?php if ( ! empty( $rcp_options['update_card'] ) ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $rcp_options['update_card'] ) ); ?>" class="button-secondary"><?php _e( 'Edit Page', 'rcp' ); ?></a>
									<a href="<?php echo esc_url( get_permalink( $rcp_options['update_card'] ) ); ?>" class="button-secondary"><?php _e( 'View Page', 'rcp' ); ?></a>
								<?php endif; ?>
								<p class="description"><?php printf( __( 'This page displays an update billing card form for logged-in members with recurring subscriptions. Contains <a href="%s" target="_blank">[rcp_update_card] short code</a>.', 'rcp' ), 'http://docs.restrictcontentpro.com/article/1608-rcpupdatecard' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<?php _e( 'Multiple Memberships', 'rcp' ); ?>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[multiple_memberships]" id="rcp_settings_multiple_memberships" <?php checked( isset( $rcp_options['multiple_memberships'] ) ); ?>/>
								<label for="rcp_settings_multiple_memberships"><?php _e( 'Check this to allow customers to sign up for multiple memberships at a time. If unchecked, each customer will only be able to hold one active membership at a time.', 'rcp' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings_auto_renew"><?php _e( 'Auto Renew', 'rcp' ); ?></label>
							</th>
							<td>
								<select name="rcp_settings[auto_renew]" id="rcp_settings_auto_renew">
									<option value="1"<?php selected( '1', rcp_get_auto_renew_behavior() ); ?>><?php _e( 'Always auto renew', 'rcp' ); ?></option>
									<option value="2"<?php selected( '2', rcp_get_auto_renew_behavior() ); ?>><?php _e( 'Never auto renew', 'rcp' ); ?></option>
									<option value="3"<?php selected( '3', rcp_get_auto_renew_behavior() ); ?>><?php _e( 'Let customer choose whether to auto renew', 'rcp' ); ?></option>
								</select>
								<p class="description"><?php _e( 'Select the auto renew behavior you would like membership levels to have.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top"<?php echo ( '3' != rcp_get_auto_renew_behavior() ) ? ' style="display: none;"' : ''; ?>>
							<th>
								<label for="rcp_settings[auto_renew_checked_on]">&nbsp;&mdash;&nbsp;<?php _e( 'Default to Auto Renew', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[auto_renew_checked_on]" id="rcp_settings[auto_renew_checked_on]" <?php checked( true, isset( $rcp_options['auto_renew_checked_on'] ) ); ?>/>
								<span><?php _e( 'Check this to have the auto renew checkbox enabled by default during registration. Customers will be able to change this.', 'rcp' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[restriction_message]"><?php _e( 'Restricted Content Message', 'rcp' ); ?></label>
							</th>
							<td>
								<?php
								$restriction_message = isset( $rcp_options['restriction_message'] ) ? $rcp_options['restriction_message'] : '';
								if ( empty( $restriction_message ) && ! empty( $rcp_options['paid_message'] ) ) {
									$restriction_message = $rcp_options['paid_message'];
								} elseif ( empty( $restriction_message ) && ! empty( $rcp_options['free_message'] ) ) {
									$restriction_message = $rcp_options['free_message'];
								}
								wp_editor( $restriction_message, 'rcp_settings_restriction_message', array( 'textarea_name' => 'rcp_settings[restriction_message]', 'teeny' => true ) ); ?>
								<p class="description"><?php _e( 'This is the message shown to users who do not have permission to view content.', 'rcp' ); ?></p>
							</td>
						</tr>
						<?php do_action( 'rcp_messages_settings', $rcp_options ); ?>
					</table>
					<?php do_action( 'rcp_general_settings', $rcp_options ); ?>

				</div><!--end #general-->

				<div class="tab_content" id="payments">
					<table class="form-table">
						<tr>
							<th>
								<label for="rcp_settings[currency]"><?php _e( 'Currency', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp_settings[currency]" name="rcp_settings[currency]">
									<?php
									$currencies = rcp_get_currencies();
									foreach($currencies as $key => $currency) {
										echo '<option value="' . esc_attr( $key ) . '" ' . selected($key, $rcp_options['currency'], false) . '>' . $currency . '</option>';
									}
									?>
								</select>
								<p class="description"><?php _e( 'Choose your currency.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[currency_position]"><?php _e( 'Currency Position', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp_settings[currency_position]" name="rcp_settings[currency_position]">
									<option value="before" <?php selected('before', $rcp_options['currency_position']); ?>><?php _e( 'Before - $10', 'rcp' ); ?></option>
									<option value="after" <?php selected('after', $rcp_options['currency_position']); ?>><?php _e( 'After - 10$', 'rcp' ); ?></option>
								</select>
								<p class="description"><?php _e( 'Show the currency sign before or after the price?', 'rcp' ); ?></p>
							</td>
						</tr>
						<?php if ( count( rcp_get_payment_gateways() ) > 1 ): ?>
							<tr valign="top">
								<th>
									<h3><?php _e( 'Gateways', 'rcp' ); ?></h3>
								</th>
								<td>
									<?php _e( 'Check each of the payment gateways you would like to enable. Configure the selected gateways below.', 'rcp' ); ?>
								</td>
							</tr>
							<tr valign="top">
								<th><span><?php _e( 'Enabled Gateways', 'rcp' ); ?></span></th>
								<td>
									<?php
									$gateways = rcp_get_payment_gateways();

									foreach( $gateways as $key => $gateway ) :

										$label = $gateway;

										if( is_array( $gateway ) ) {
											$label = $gateway['admin_label'];
										}

										if ( $key == 'twocheckout' && checked( true, isset( $rcp_options[ 'gateways' ][ $key ] ), false ) == '') {

										} else {
											echo '<input name="rcp_settings[gateways][' . $key . ']" id="rcp_settings[gateways][' . $key . ']" type="checkbox" value="1" ' . checked( true, isset( $rcp_options['gateways'][ $key ] ), false) . '/>&nbsp;';
											echo '<label for="rcp_settings[gateways][' . $key . ']">' . $label . '</label><br/>';
										}

									endforeach;
									?>
								</td>
							</tr>
						<?php endif; ?>

						<?php do_action( 'restrict_content_pro_output_payment_gateway_inputs', $rcp_options ); ?>

					</table>
					<?php do_action( 'rcp_payments_settings', $rcp_options ); ?>

				</div><!--end #payments-->

				<?php do_action( 'restrict_content_pro_add_admin_setting_email_inputs', $rcp_options ); ?>

				<div class="tab_content" id="invoices">
					<table class="form-table">
						<tr valign="top">
							<th>
								<label for="rcp_settings[invoice_logo]"><?php _e( 'Invoice Logo', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text rcp-upload-field" id="rcp_settings[invoice_logo]" style="width: 300px;" name="rcp_settings[invoice_logo]" value="<?php if( isset( $rcp_options['invoice_logo'] ) ) { echo $rcp_options['invoice_logo']; } ?>"/>
								<button class="button-secondary rcp-upload"><?php _e( 'Choose Logo', 'rcp' ); ?></button>
								<p class="description"><?php _e( 'Upload a logo to display on the invoices.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[invoice_company]"><?php _e( 'Company Name', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="rcp_settings[invoice_company]" style="width: 300px;" name="rcp_settings[invoice_company]" value="<?php if( isset( $rcp_options['invoice_company'] ) ) { echo $rcp_options['invoice_company']; } ?>"/>
								<p class="description"><?php _e( 'Enter the company name that will be shown on the invoice.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[invoice_name]"><?php _e( 'Name', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="rcp_settings[invoice_name]" style="width: 300px;" name="rcp_settings[invoice_name]" value="<?php if( isset( $rcp_options['invoice_name'] ) ) { echo $rcp_options['invoice_name']; } ?>"/>
								<p class="description"><?php _e( 'Enter the personal name that will be shown on the invoice.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[invoice_address]"><?php _e( 'Address Line 1', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="rcp_settings[invoice_address]" style="width: 300px;" name="rcp_settings[invoice_address]" value="<?php if( isset( $rcp_options['invoice_address'] ) ) { echo $rcp_options['invoice_address']; } ?>"/>
								<p class="description"><?php _e( 'Enter the first address line that will appear on the invoice.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[invoice_address_2]"><?php _e( 'Address Line 2', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="rcp_settings[invoice_address_2]" style="width: 300px;" name="rcp_settings[invoice_address_2]" value="<?php if( isset( $rcp_options['invoice_address_2'] ) ) { echo $rcp_options['invoice_address_2']; } ?>"/>
								<p class="description"><?php _e( 'Enter the second address line that will appear on the invoice.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[invoice_city_state_zip]"><?php _e( 'City, State, and Zip', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="rcp_settings[invoice_city_state_zip]" style="width: 300px;" name="rcp_settings[invoice_city_state_zip]" value="<?php if( isset( $rcp_options['invoice_city_state_zip'] ) ) { echo $rcp_options['invoice_city_state_zip']; } ?>"/>
								<p class="description"><?php _e( 'Enter the city, state and zip/postal code that will appear on the invoice.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[invoice_email]"><?php _e( 'Email', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="rcp_settings[invoice_email]" style="width: 300px;" name="rcp_settings[invoice_email]" value="<?php if( isset( $rcp_options['invoice_email'] ) ) { echo $rcp_options['invoice_email']; } ?>"/>
								<p class="description"><?php _e( 'Enter the email address that will appear on the invoice.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[invoice_header]"><?php _e( 'Header Text', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="rcp_settings[invoice_header]" style="width: 300px;" name="rcp_settings[invoice_header]" value="<?php if( isset( $rcp_options['invoice_header'] ) ) { echo $rcp_options['invoice_header']; } ?>"/>
								<p class="description"><?php _e( 'Enter the message you would like to be shown on the header of the invoice.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings_invoice_notes"><?php _e( 'Notes', 'rcp' ); ?></label>
							</th>
							<td>
								<?php
								$invoice_notes = isset( $rcp_options['invoice_notes'] ) ? $rcp_options['invoice_notes'] : '';
								wp_editor( $invoice_notes, 'rcp_settings_invoice_notes', array( 'textarea_name' => 'rcp_settings[invoice_notes]', 'teeny' => true ) );
								?>
								<p class="description"><?php _e( 'Enter additional notes you would like displayed below the invoice totals.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[invoice_footer]"><?php _e( 'Footer Text', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="rcp_settings[invoice_footer]" style="width: 300px;" name="rcp_settings[invoice_footer]" value="<?php if( isset( $rcp_options['invoice_footer'] ) ) { echo $rcp_options['invoice_footer']; } ?>"/>
								<p class="description"><?php _e( 'Enter the message you would like to be shown on the footer of the invoice.', 'rcp' ); ?></p>
							</td>
						</tr>
					</table>
					<?php do_action( 'rcp_invoice_settings', $rcp_options ); ?>
				</div><!--end #invoices-->

				<div class="tab_content" id="misc">
					<table class="form-table">
						<tr valign="top">
							<th>
								<label for="rcp_settings[hide_premium]"><?php _e( 'Hide Restricted Posts', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[hide_premium]" id="rcp_settings[hide_premium]" <?php if( isset( $rcp_options['hide_premium'] ) ) checked('1', $rcp_options['hide_premium']); ?>/>
								<span class="description"><?php _e( 'Check this to hide all restricted posts from queries when the user does not have access.', 'rcp' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[redirect]">&nbsp;&mdash;&nbsp;<?php _e( 'Redirect Page', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp_settings[redirect_from_premium]" name="rcp_settings[redirect_from_premium]">
									<?php
									if($pages) :
										foreach ( $pages as $page ) {
										  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['redirect_from_premium'], false) . '>';
											$option .= $page->post_title;
											$option .= '</option>';
											echo $option;
										}
									else :
										echo '<option>' . __('No pages found', 'rcp' ) . '</option>';
									endif;
									?>
								</select>
								<?php if ( ! empty( $rcp_options['redirect_from_premium'] ) ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $rcp_options['redirect_from_premium'] ) ); ?>" class="button-secondary"><?php _e( 'Edit Page', 'rcp' ); ?></a>
									<a href="<?php echo esc_url( get_permalink( $rcp_options['redirect_from_premium'] ) ); ?>" class="button-secondary"><?php _e( 'View Page', 'rcp' ); ?></a>
								<?php endif; ?>
								<p class="description"><?php _e( 'This is the page non-subscribed users are redirected to when attempting to access a premium post or page.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[hijack_login_url]"><?php _e( 'Redirect Default Login URL', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[hijack_login_url]" id="rcp_settings[hijack_login_url]" <?php if( isset( $rcp_options['hijack_login_url'] ) ) checked('1', $rcp_options['hijack_login_url']); ?>/>
								<span class="description"><?php _e( 'Check this to force the default login URL to redirect to the page specified below.', 'rcp' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[redirect]">&nbsp;&mdash;&nbsp;<?php _e( 'Login Page', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp_settings[login_redirect]" name="rcp_settings[login_redirect]">
									<?php
									if($pages) :
										foreach ( $pages as $page ) {
										  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['login_redirect'], false) . '>';
											$option .= $page->post_title;
											$option .= '</option>';
											echo $option;
										}
									else :
										echo '<option>' . __('No pages found', 'rcp' ) . '</option>';
									endif;
									?>
								</select>
								<?php if ( ! empty( $rcp_options['login_redirect'] ) ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $rcp_options['login_redirect'] ) ); ?>" class="button-secondary"><?php _e( 'Edit Page', 'rcp' ); ?></a>
									<a href="<?php echo esc_url( get_permalink( $rcp_options['login_redirect'] ) ); ?>" class="button-secondary"><?php _e( 'View Page', 'rcp' ); ?></a>
								<?php endif; ?>
								<p class="description"><?php _e( 'This is the page the default login URL redirects to, if the option above is checked. This page must contain the [login_form] shortcode.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[auto_add_users]"><?php _e( 'Auto Add Users to Level', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[auto_add_users]" id="rcp_settings[auto_add_users]" <?php if( isset( $rcp_options['auto_add_users'] ) ) checked('1', $rcp_options['auto_add_users']); ?>/>
								<span class="description"><?php _e( 'Check this to automatically add new WordPress users to a membership level. This only needs to be turned on if you\'re adding users manually or through some means other than the registration form. This does not automatically take payment so it\'s best used for free levels.', 'rcp' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[auto_add_users_level]">&nbsp;&mdash;&nbsp;<?php _e( 'Membership Level', 'rcp' ); ?></label>
							</th>
							<td>
								<select id="rcp_settings[auto_add_users_level]" name="rcp_settings[auto_add_users_level]">
									<?php
									$selected_level = isset( $rcp_options['auto_add_users_level'] ) ? $rcp_options['auto_add_users_level'] : '';
									foreach( rcp_get_membership_levels( array( 'number' => 999 ) ) as $key => $level ) :
										echo '<option value="' . esc_attr( absint( $level->get_id() ) ) . '"' . selected( $level->get_id(), $selected_level, false ) . '>' . esc_html( $level->get_name() ) . '</option>';
									endforeach;
									?>
								</select>
								<p class="description"><?php _e( 'New WordPress users will be automatically added to this membership level if the above option is checked.', 'rcp' ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[content_excerpts]"><?php _e( 'Content Excerpts', 'rcp' ); ?></label>
							</th>
							<td>
								<?php $excerpts = isset( $rcp_options['content_excerpts'] ) ? $rcp_options['content_excerpts'] : 'individual'; ?>
								<select id="rcp_settings[content_excerpts]" name="rcp_settings[content_excerpts]">
									<option value="always" <?php selected( $excerpts, 'all' ); ?>><?php _e( 'Always show excerpts', 'rcp' ); ?></option>
									<option value="never" <?php selected( $excerpts, 'never' ); ?>><?php _e( 'Never show excerpts', 'rcp' ); ?></option>
									<option value="individual" <?php selected( $excerpts, 'individual' ); ?>><?php _e( 'Decide for each post individually', 'rcp' ); ?></option>
								</select>
								<p class="description"><?php _e( 'Whether or not to show excerpts to members without access to the content.', 'rcp' ); ?></p>
							</td>
						</tr>

						<?php

						/**
						 * Action to add Discount Signup Fees
						 */
						do_action('restrict_content_pro_discount_signup_fees_admin');

						?>

						<?php

						/**
						 * Action to add the maximum number of simultaneous connections per member
						 */
						do_action('restrict_content_pro_max_connections_per_member_admin');

						?>
						<tr valign="top">
							<th>
								<label for="rcp_settings[disable_toolbar]"><?php _e( 'Disable WordPress Toolbar', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[disable_toolbar]" id="rcp_settings[disable_toolbar]"<?php checked( true, isset( $rcp_options['disable_toolbar'] ) ); ?>/>
								<span class="description"><?php _e( 'Check this if you\'d like to disable the WordPress toolbar for members. Note: will not disable the toolbar for users with the edit_posts capability (e.g. authors, editors, & admins).', 'rcp' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[disable_css]"><?php _e( 'Disable Form CSS', 'rcp' ); ?></label><br/>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[disable_css]" id="rcp_settings[disable_css]" <?php if( isset( $rcp_options['disable_css'] ) ) checked('1', $rcp_options['disable_css']); ?>/>
								<span class="description"><?php _e( 'Check this to disable all included form styling.', 'rcp' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[enable_terms]"><?php _e( 'Agree to Terms', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[enable_terms]" id="rcp_settings[enable_terms]" <?php if ( isset( $rcp_options['enable_terms'] ) ) checked('1', $rcp_options['enable_terms'] ); ?>/>
								<span class="description"><?php _e( 'Check this to add an "Agree to Terms" checkbox to the registration form.', 'rcp' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[terms_label]">&nbsp;&mdash;&nbsp;<?php _e( 'Agree to Terms Label', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" id="rcp_settings[terms_label]" style="width: 300px;" name="rcp_settings[terms_label]" value="<?php if( isset( $rcp_options['terms_label'] ) ) echo esc_attr( $rcp_options['terms_label'] ); ?>" />
								<p class="description"><?php _e( 'Label shown next to the agree to terms checkbox.', 'rcp' ); ?></p>
							<td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[terms_link]">&nbsp;&mdash;&nbsp;<?php _e( 'Terms Link', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" id="rcp_settings[terms_link]" style="width: 300px;" name="rcp_settings[terms_link]" value="<?php if( isset( $rcp_options['terms_link'] ) ) echo esc_attr( $rcp_options['terms_link'] ); ?>" placeholder="https://" />
								<p class="description"><?php _e( 'Optional - the URL to your terms page. If set, the terms label will link to this URL.', 'rcp' ); ?></p>
							<td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[enable_privacy_policy]"><?php _e( 'Agree to Privacy Policy', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[enable_privacy_policy]" id="rcp_settings[enable_privacy_policy]" <?php if ( isset( $rcp_options['enable_privacy_policy'] ) ) checked('1', $rcp_options['enable_privacy_policy'] ); ?>/>
								<span class="description"><?php _e( 'Check this to add an "Agree to Privacy Policy" checkbox to the registration form.', 'rcp' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[privacy_policy_label]">&nbsp;&mdash;&nbsp;<?php _e( 'Agree to Privacy Policy Label', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" id="rcp_settings[privacy_policy_label]" style="width: 300px;" name="rcp_settings[privacy_policy_label]" value="<?php if( isset( $rcp_options['privacy_policy_label'] ) ) echo esc_attr( $rcp_options['privacy_policy_label'] ); ?>" />
								<p class="description"><?php _e( 'Label shown next to the agree to privacy policy checkbox.', 'rcp' ); ?></p>
							<td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[privacy_policy_link]">&nbsp;&mdash;&nbsp;<?php _e( 'Privacy Policy Link', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="text" id="rcp_settings[privacy_policy_link]" style="width: 300px;" name="rcp_settings[privacy_policy_link]" value="<?php if( isset( $rcp_options['privacy_policy_link'] ) ) echo esc_attr( $rcp_options['privacy_policy_link'] ); ?>" placeholder="https://" />
								<p class="description"><?php _e( 'Optional - the URL to your privacy policy page. If set, the privacy policy label will link to this URL.', 'rcp' ); ?></p>
							<td>
						</tr>

						<?
						/**
						* Action to add the maximum number of simultaneous connections per member
						*/
						do_action( 'restrict_content_pro_add_recaptcha_fields', $rcp_options );
						?>

						<tr valign="top">
							<th>
								<label for="rcp_settings[debug_mode]"><?php _e( 'Enable Debug Mode', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[debug_mode]" id="rcp_settings[debug_mode]" <?php checked( true, ! empty( $rcp_options['debug_mode'] ) ); ?>/>
								<span class="description"><?php printf( __( 'Turn on error logging to help identify issues. Logs are kept in <a href="%s">Restrict > Tools</a>.', 'rcp' ), esc_url( admin_url( 'admin.php?page=rcp-tools' ) ) ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th>
								<label for="rcp_settings[remove_data_on_uninstall]"><?php _e( 'Remove Data on Uninstall', 'rcp' ); ?></label>
							</th>
							<td>
								<input type="checkbox" value="1" name="rcp_settings[remove_data_on_uninstall]" id="rcp_settings[remove_data_on_uninstall]" <?php checked( true, ! empty( $rcp_options['remove_data_on_uninstall'] ) ); ?>/>
								<span class="description"><?php _e( 'Remove all saved data for Restrict Content Pro when the plugin is uninstalled.', 'rcp' ); ?></span>
							</td>
						</tr>
					</table>
					<?php do_action( 'rcp_misc_settings', $rcp_options ); ?>
				</div><!--end #misc-->

			</div><!--end #tab_container-->

			<!-- save the options -->
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'rcp' ); ?>" />
			</p>

		</form>
	</div><!--end wrap-->

	<?php
}

/**
 * Sanitize settings.
 *
 * @param array $data
 *
 * @return array Sanitized data.
 */
function rcp_sanitize_settings( $data ) {

	// Trim API key fields.
	$api_key_fields = array(
		'stripe_test_secret', 'stripe_test_publishable',
		'stripe_live_secret', 'stripe_live_publishable',
		'twocheckout_test_private', 'twocheckout_test_publishable',
		'twocheckout_live_private', 'twocheckout_live_publishable'
	);

	foreach ( $api_key_fields as $field ) {
		if ( ! empty( $data[ $field ] ) ) {
			$data[ $field ] = trim( $data[ $field ] );
		}
	}

	delete_transient( 'rcp_login_redirect_invalid' );

	// Make sure the [login_form] short code is on the redirect page. Users get locked out if it is not
	if( isset( $data['hijack_login_url'] ) ) {

		$page_id = absint( $data['login_redirect'] );
		$page    = get_post( $page_id );

		if( ! $page || 'page' != $page->post_type ) {
			unset( $data['hijack_login_url'] );
		}

		if(
			// Check for various login form short codes
			false === strpos( $page->post_content, '[login_form' ) &&
			false === strpos( $page->post_content, '[edd_login' ) &&
			false === strpos( $page->post_content, '[subscription_details' ) &&
			false === strpos( $page->post_content, '[login' )
		) {
			unset( $data['hijack_login_url'] );
			set_transient( 'rcp_login_redirect_invalid', 1, MINUTE_IN_SECONDS );
		}

	}

	// Sanitize email bodies
	$email_bodies = array( 'active_email', 'cancelled_email', 'expired_email', 'renew_notice_email', 'free_email', 'trial_email', 'payment_received_email' );
	foreach ( $email_bodies as $email_body ) {
		if ( ! empty( $data[$email_body] ) ) {
			$data[$email_body] = wp_kses_post( $data[$email_body] );
		}
	}

	do_action( 'rcp_save_settings', $data );

	return apply_filters( 'rcp_save_settings', $data );
}

/**
 * Retrieves site data (plugin versions, etc.) to be sent along with the license check.
 *
 * @since 2.9
 * @return array
 */
function rcp_get_site_tracking_data() {

	global $rcp_options;

	/**
	 * @var RCP_Levels $rcp_levels_db
	 */
	global $rcp_levels_db;

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	$data = array();

	$theme_data = wp_get_theme();
	$theme      = $theme_data->Name . ' ' . $theme_data->Version;

	$data['php_version']  = phpversion();
	$data['rcp_version']  = RCP_PLUGIN_VERSION;
	$data['wp_version']   = get_bloginfo( 'version' );
	$data['server']       = isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '';
	$data['install_date'] = get_post_field( 'post_date', $rcp_options['registration_page'] );
	$data['multisite']    = is_multisite();
	$data['url']          = home_url();
	$data['theme']        = $theme;

	// Retrieve current plugin information
	if( ! function_exists( 'get_plugins' ) ) {
		include ABSPATH . '/wp-admin/includes/plugin.php';
	}

	$plugins        = array_keys( get_plugins() );
	$active_plugins = get_option( 'active_plugins', array() );

	foreach ( $plugins as $key => $plugin ) {
		if ( in_array( $plugin, $active_plugins ) ) {
			// Remove active plugins from list so we can show active and inactive separately
			unset( $plugins[ $key ] );
		}
	}

	$enabled_gateways = array();
	$gateways         = new RCP_Payment_Gateways;

	foreach( $gateways->enabled_gateways  as $key => $gateway ) {
		if( is_array( $gateway ) ) {
			$enabled_gateways[ $key ] = $gateway['admin_label'];
		}
	}

	$plugins_str = implode( ',', array_keys( get_plugins() ) );
	$upgraded    = strpos( $plugins_str, 'restrictcontent.php' );

	$data['active_plugins']      = $active_plugins;
	$data['inactive_plugins']    = $plugins;
	$data['locale']              = get_locale();
	$data['auto_renew']          = $rcp_options['auto_renew'];
	$data['currency']            = $rcp_options['currency'];
	$data['gateways']            = $enabled_gateways;
	$data['active_members']      = rcp_get_member_count( 'active' );
	$data['free_members']        = rcp_get_member_count( 'free' );
	$data['expired_members']     = rcp_get_member_count( 'expired' );
	$data['cancelled_members']   = rcp_get_member_count( 'cancelled' );
	$data['subscription_levels'] = $rcp_levels_db->count();
	$data['payments']            = $rcp_payments_db->count();
	$data['upgraded_to_pro']     = ! empty( $upgraded );

	return $data;

}


/**
 * Set rcp_manage_settings as the cap required to save RCP settings pages
 *
 * @since 2.0
 * @return string capability required
 */
function rcp_set_settings_cap() {
	return 'rcp_manage_settings';
}
add_filter( 'option_page_capability_rcp_settings_group', 'rcp_set_settings_cap' );

/**
 * Send a test email
 *
 * @return void
 */
function rcp_process_send_test_email() {

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		wp_die( __( 'You do not have permission to send test emails', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp_send_test_email' ) ) {
		wp_die( __( 'Nonce verification failed', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 401 ) );
	}

	if ( empty( $_GET['email'] ) ) {
		wp_die( __( 'No email template was provided', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$current_user = wp_get_current_user();

	rcp_log( sprintf( 'Sending test email template %s to user ID #%d.', sanitize_text_field( $_GET['email'] ), $current_user->ID ) );

	global $rcp_options;

	$subject = '';
	$message = '';

	switch( $_GET['email'] ) {
		case 'active' :
			$subject = $rcp_options['active_subject'];
			$message = $rcp_options['active_email'];
			break;
		case 'active_admin' :
			$subject = $rcp_options['active_subject_admin'];
			$message = $rcp_options['active_email_admin'];
			break;
		case 'cancelled' :
			$subject = $rcp_options['cancelled_subject'];
			$message = $rcp_options['cancelled_email'];
			break;
		case 'cancelled_admin' :
			$subject = $rcp_options['cancelled_subject_admin'];
			$message = $rcp_options['cancelled_email_admin'];
			break;
		case 'expired' :
			$subject = $rcp_options['expired_subject'];
			$message = $rcp_options['expired_email'];
			break;
		case 'expired_admin' :
			$subject = $rcp_options['expired_subject_admin'];
			$message = $rcp_options['expired_email_admin'];
			break;
		case 'free' :
			$subject = $rcp_options['free_subject'];
			$message = $rcp_options['free_email'];
			break;
		case 'free_admin' :
			$subject = $rcp_options['free_subject_admin'];
			$message = $rcp_options['free_email_admin'];
			break;
		case 'trial' :
			$subject = $rcp_options['trial_subject'];
			$message = $rcp_options['trial_email'];
			break;
		case 'trial_admin' :
			$subject = $rcp_options['trial_subject_admin'];
			$message = $rcp_options['trial_email_admin'];
			break;
		case 'payment_received' :
			$subject = $rcp_options['payment_received_subject'];
			$message = $rcp_options['payment_received_email'];
			break;
		case 'payment_received_admin' :
			$subject = $rcp_options['payment_received_subject_admin'];
			$message = $rcp_options['payment_received_email_admin'];
			break;
		case 'renewal_payment_failed' :
			$subject = $rcp_options['renewal_payment_failed_subject'];
			$message = $rcp_options['renewal_payment_failed_email'];
			break;
		case 'renewal_payment_failed_admin' :
			$subject = $rcp_options['renewal_payment_failed_subject_admin'];
			$message = $rcp_options['renewal_payment_failed_email_admin'];
			break;
	}

	if ( empty( $subject ) || empty( $message ) ) {
		wp_die( __( 'Test email not sent: email subject or message is blank.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$emails            = new RCP_Emails();
	$emails->member_id = $current_user->ID;

	$sent = $emails->send( $current_user->user_email, $subject, $message );

	if ( $sent ) {
		wp_safe_redirect( admin_url( 'admin.php?page=rcp-settings&rcp_message=test_email_sent#emails' ) );
	} else {
		wp_safe_redirect( admin_url( 'admin.php?page=rcp-settings&rcp_message=test_email_not_sent#emails' ) );
	}
	exit;

}

add_action( 'rcp_action_send_test_email', 'rcp_process_send_test_email' );

/**
 * Listens for Stripe Connect completion requests and saves the Stripe API keys.
 *
 * @since 2.9.11
 */
function rcp_process_gateway_connect_completion() {

	if( ! isset( $_GET['rcp_gateway_connect_completion'] ) || 'stripe_connect' !== $_GET['rcp_gateway_connect_completion'] || ! isset( $_GET['state'] ) ) {
		return;
	}

	if( ! current_user_can( 'rcp_manage_settings' ) ) {
		return;
	}

	if( headers_sent() ) {
		return;
	}

	$rcp_credentials_url = add_query_arg( array(
		'live_mode'         => urlencode( (int) ! rcp_is_sandbox() ),
		'state'             => urlencode( sanitize_text_field( $_GET['state'] ) ),
		'customer_site_url' => urlencode( admin_url( 'admin.php?page=rcp-settings' ) ),
	), 'https://restrictcontentpro.com/?rcp_gateway_connect_credentials=stripe_connect' );

	$response = wp_remote_get( esc_url_raw( $rcp_credentials_url ) );
	if( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		$message = '<p>' . sprintf( __( 'There was an error getting your Stripe credentials. Please <a href="%s">try again</a>. If you continue to have this problem, please contact support.', 'rcp' ), esc_url( admin_url( 'admin.php?page=rcp-settings#payments' ) ) ) . '</p>';
		wp_die( $message );
	}

	$response = json_decode( $response['body'], true );
	$data = $response['data'];

	global $rcp_options;

	if( rcp_is_sandbox() ) {
		$rcp_options['stripe_test_publishable'] = sanitize_text_field( $data['publishable_key'] );
		$rcp_options['stripe_test_secret'] = sanitize_text_field( $data['secret_key'] );
	} else {
		$rcp_options['stripe_live_publishable'] = sanitize_text_field( $data['publishable_key'] );
		$rcp_options['stripe_live_secret'] = sanitize_text_field( $data['secret_key'] );
	}
	update_option( 'rcp_settings', $rcp_options );
	update_option( 'rcp_stripe_connect_account_id', sanitize_text_field( $data['stripe_user_id'] ), false );
	wp_redirect( esc_url_raw( admin_url( 'admin.php?page=rcp-settings#payments' ) ) );
	exit;

}
add_action( 'admin_init', 'rcp_process_gateway_connect_completion' );
