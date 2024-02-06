<?php
/**
 * Registration Form - Combined
 *
 * This template is used to display the registration form for a single
 * membership level with [register_form id="3"] and the "non single:
 * register form with [register_form]
 *
 * @link https://restrictcontentpro.com/knowledgebase/register_form
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/Register/Single
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

global $rcp_options, $rcp_level, $post, $rcp_levels_db, $rcp_register_form_atts;

$discount = ! empty( $_REQUEST['discount'] ) ? sanitize_text_field( $_REQUEST['discount'] ) : '';

// We only have $level only used on "register-single.php"
$level = rcp_get_membership_level( $rcp_level );
?>

<h3 class="rcp_header">
	<?php if ( ! is_user_logged_in() ) {
		echo apply_filters( 'rcp_registration_header_logged_out', $rcp_register_form_atts['logged_out_header'] );
	} else {
		echo apply_filters( 'rcp_registration_header_logged_in', $rcp_register_form_atts['logged_in_header'] );
	}
	?>
</h3>

<?php
// show any error messages after form submission
rcp_show_error_messages( 'register' ); ?>

<form id="rcp_registration_form" class="rcp_form" method="POST" action="<?php echo esc_url( rcp_get_current_url() ); ?>">

	<?php if ( $level ) { ?>
		<div class="rcp_description"><?php echo wpautop( wptexturize( $level->get_description() ) ); ?></div>
	<?php } ?>

	<?php if ( ! is_user_logged_in() ) { ?>
		<div class="rcp_login_link">
			<p><?php printf( __( '<a href="%s">Log in</a> to renew or change an existing membership.', 'rcp' ), esc_url( rcp_get_login_url( rcp_get_current_url() ) ) ); ?></p>
		</div>

		<?php do_action( 'rcp_before_register_form_fields' ); ?>

		<fieldset class="rcp_user_fieldset">
			<p id="rcp_user_login_wrap">
				<label for="rcp_user_login"><?php echo apply_filters ( 'rcp_registration_username_label', __( 'Username', 'rcp' ) ); ?></label>
				<input name="rcp_user_login" id="rcp_user_login" class="required" type="text" <?php if( isset( $_POST['rcp_user_login'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_user_login'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_user_email_wrap">
				<label for="rcp_user_email"><?php echo apply_filters ( 'rcp_registration_email_label', __( 'Email', 'rcp' ) ); ?></label>
				<input name="rcp_user_email" id="rcp_user_email" class="required" type="text" <?php if( isset( $_POST['rcp_user_email'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_user_email'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_user_first_wrap" class="inline">
				<label for="rcp_user_first"><?php echo apply_filters ( 'rcp_registration_firstname_label', __( 'First Name', 'rcp' ) ); ?></label>
				<input name="rcp_user_first" id="rcp_user_first" type="text" <?php if( isset( $_POST['rcp_user_first'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_user_first'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_user_last_wrap" class="inline-block">
				<label for="rcp_user_last"><?php echo apply_filters ( 'rcp_registration_lastname_label', __( 'Last Name', 'rcp' ) ); ?></label>
				<input name="rcp_user_last" id="rcp_user_last" type="text" <?php if( isset( $_POST['rcp_user_last'] ) ) { echo 'value="' . esc_attr( $_POST['rcp_user_last'] ) . '"'; } ?>/>
			</p>
			<p id="rcp_password_wrap" class="inline">
				<label for="rcp_password"><?php echo apply_filters ( 'rcp_registration_password_label', __( 'Password', 'rcp' ) ); ?></label>
				<input name="rcp_user_pass" id="rcp_password" class="required" type="password"/>
			</p>
			<p id="rcp_password_again_wrap" class="inline-block">
				<label for="rcp_password_again"><?php echo apply_filters ( 'rcp_registration_password_again_label', __( 'Password Again', 'rcp' ) ); ?></label>
				<input name="rcp_user_pass_confirm" id="rcp_password_again" class="required" type="password"/>
			</p>

			<?php do_action( 'rcp_after_password_registration_field' ); ?>

		</fieldset>
	<?php } ?>

	<?php do_action( 'rcp_before_subscription_form_fields' ); ?>

	<?php // with if ( ! $level ) I'm checking if register.php is active ?>
	<?php if ( ! $level ) { ?>
		<fieldset class="rcp_subscription_fieldset">
		<?php
		$levels = rcp_get_membership_levels( array( 'status' => 'active', 'number' => 100 ) );
		$i      = 0;
		if( $levels ) : ?>
			<?php if ( count( $levels ) > 1 ) : ?>
				<p class="rcp_subscription_message"><?php echo apply_filters ( 'rcp_registration_choose_subscription', __( 'Choose your membership level', 'rcp' ) ); ?></p>
			<?php endif; ?>
			<ul id="rcp_subscription_levels">
				<?php foreach( $levels as $key => $each_level ) : ?>
					<?php if( rcp_show_subscription_level( $each_level->get_id() ) ) : ?>
					<li class="rcp_subscription_level rcp_subscription_level_<?php echo esc_attr( $each_level->get_id() ); ?>">
						<input type="radio" id="rcp_subscription_level_<?php echo esc_attr( $each_level->get_id() ); ?>" class="required rcp_level" <?php if ( $i == 0 || ( isset( $_GET['level'] ) && $_GET['level'] == $each_level->get_id() ) ) { echo 'checked="checked"'; } ?> name="rcp_level" rel="<?php echo esc_attr( $each_level->get_price() ); ?>" value="<?php echo esc_attr( $each_level->get_id() ); ?>" <?php if( $each_level->is_lifetime() ) { echo 'data-duration="forever"'; } if ( $each_level->has_trial() ) { echo 'data-has-trial="true"'; } ?>/>
						<label for="rcp_subscription_level_<?php echo esc_attr( $each_level->get_id() ); ?>">
							<span class="rcp_subscription_level_name"><?php echo esc_html( $each_level->get_name() ); ?></span><span class="rcp_separator">&nbsp;-&nbsp;</span><span class="rcp_price" rel="<?php echo esc_attr( $each_level->get_price() ); ?>"><?php echo ! $each_level->is_free() ? rcp_currency_filter( $each_level->get_price() ) : __( 'free', 'rcp' ); ?></span><span class="rcp_separator">&nbsp;-&nbsp;</span>
							<span class="rcp_level_duration"><?php echo ! $each_level->is_lifetime() ? $each_level->get_duration() . '&nbsp;' . rcp_filter_duration_unit( $each_level->get_duration_unit(), $each_level->get_duration() ) : __( 'unlimited', 'rcp' ); ?></span>
							<?php if ( $each_level->get_maximum_renewals() > 0 ) : ?>
								<span class="rcp_separator">&nbsp;-&nbsp;</span>
								<span class="rcp_level_bill_times"><?php printf( __( '%d total payments', 'rcp' ), $each_level->get_maximum_renewals() + 1 ); ?></span>
							<?php endif; ?>
							<div class="rcp_level_description"> <?php echo $each_level->get_description(); ?></div>
						</label>
					</li>
					<?php $i++; endif; ?>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><strong><?php _e( 'You have not created any membership levels yet', 'rcp' ); ?></strong></p>
		<?php endif; ?>
		</fieldset>
	<?php
	}

	if ( ! function_exists( 'rcp_should_show_discounts' ) ) :
		function rcp_should_show_discounts( $level ) {
			// This one is easy. No discounts? Stop here.
			if ( ! rcp_has_discounts() ) {
				return false;
			}

			// On "register-single.php" and it's free.
			if ( $level && $level->is_free() ) {
				return false;
			}

			return true;
		}
	endif;

	$show_discounts = rcp_should_show_discounts( $level );

	if ( $show_discounts ) :
	?>
		<fieldset class="rcp_discounts_fieldset">
			<p id="rcp_discount_code_wrap">
				<label for="rcp_discount_code">
					<?php _e( 'Discount Code', 'rcp' ); ?>
					<span class="rcp_discount_valid" style="display: none;"> - <?php _e( 'Valid', 'rcp' ); ?></span>
					<span class="rcp_discount_invalid" style="display: none;"> - <?php _e( 'Invalid', 'rcp' ); ?></span>
				</label>
				<span class="rcp_discount_code_field_wrap">
					<input type="text" id="rcp_discount_code" name="rcp_discount" class="rcp_discount_code" value="<?php echo esc_attr( $discount ); ?>"/>
					<button class="rcp_button" id="rcp_apply_discount"><?php _e( 'Apply', 'rcp' ); ?></button>
				</span>
			</p>
		</fieldset>
	<?php
	endif;

	// checking if $levels is a thing, so it can be passed along to add_actions
	$do_we_have_levels = isset( $levels );

	if ( $do_we_have_levels ) { 
		do_action( 'rcp_after_register_form_fields', $levels );
	} else {
		do_action( 'rcp_after_register_form_fields' );
	}

	// There is no conditional on register.php
	// So we return true immediately if so
	// If $level is a thing, we check if it's free, and return false
	// Don't need gateways on free stuff
	if ( ! function_exists( 'rcp_should_show_gateway_fields' ) ) :
		function rcp_should_show_gateway_fields( $level ) {
			if ( ! $level ) {
				return true;
			}

			if ( $level->is_free() ) {
				return false;
			}

			// We have a $level, but it ain't free
			return true;
		}
	endif;

	$should_show_gateway_fields = rcp_should_show_gateway_fields( $level );

	if ( $should_show_gateway_fields ) :
	?>
		<div class="rcp_gateway_fields">
			<?php
			$gateways = rcp_get_enabled_payment_gateways();
			if( count( $gateways ) > 1 ) :
				$display = rcp_has_paid_levels() ? '' : ' style="display: none;"';
				$i = 1;
				?>
				<fieldset class="rcp_gateways_fieldset">
					<legend><?php _e( 'Choose Your Payment Method', 'rcp' ); ?></legend>
					<p id="rcp_payment_gateways"<?php echo $display; ?>>
						<?php foreach( $gateways as $key => $gateway ) :
							$recurring = rcp_gateway_supports( $key, 'recurring' ) ? 'yes' : 'no';
							$trial    = rcp_gateway_supports( $key, 'trial' ) ? 'yes' : 'no'; ?>
							<label for="rcp_gateway_<?php echo esc_attr( $key ); ?>" class="rcp_gateway_option_label">
								<input id="rcp_gateway_<?php echo esc_attr( $key );?>" name="rcp_gateway" type="radio" class="rcp_gateway_option_input" value="<?php echo esc_attr( $key ); ?>" data-supports-recurring="<?php echo esc_attr( $recurring ); ?>" data-supports-trial="<?php echo esc_attr( $trial ); ?>" <?php checked( $i, 1 ); ?>>
								<?php echo esc_html( $gateway ); ?>
							</label>
						<?php
						$i++;
						endforeach; ?>
					</p>
				</fieldset>
			<?php else: ?>
				<?php foreach( $gateways as $key => $gateway ) :
					$recurring = rcp_gateway_supports( $key, 'recurring' ) ? 'yes' : 'no';
					$trial = rcp_gateway_supports( $key, 'trial' ) ? 'yes' : 'no';
					?>
					<input type="hidden" name="rcp_gateway" value="<?php echo esc_attr( $key ); ?>" data-supports-recurring="<?php echo esc_attr( $recurring ); ?>" data-supports-trial="<?php echo esc_attr( $trial ); ?>"/>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	<?php
	endif;
	?>

	<?php if ( ! empty( $rcp_options['enable_terms'] ) ) : ?>
		<fieldset class="rcp_agree_to_terms_fieldset">
			<p id="rcp_agree_to_terms_wrap">
				<input type="checkbox" id="rcp_agree_to_terms" name="rcp_agree_to_terms" value="1">
				<label for="rcp_agree_to_terms">
					<?php
					if ( ! empty( $rcp_options['terms_link'] ) ) {
						echo '<a href="' . esc_url( $rcp_options['terms_link'] ) . '" target="_blank">';
					}

					if ( ! empty( $rcp_options['terms_label'] ) ) {
						echo $rcp_options['terms_label'];
					} else {
						_e( 'I agree to the terms and conditions', 'rcp' );
					}

					if ( ! empty( $rcp_options['terms_link'] ) ) {
						echo '</a>';
					}
					?>
				</label>
			</p>
		</fieldset>
	<?php endif; ?>

	<?php if ( ! empty( $rcp_options['enable_privacy_policy'] ) ) : ?>
		<fieldset class="rcp_agree_to_privacy_policy_fieldset">
			<p id="rcp_agree_to_privacy_policy_wrap">
				<input type="checkbox" id="rcp_agree_to_privacy_policy" name="rcp_agree_to_privacy_policy" value="1">
				<label for="rcp_agree_to_privacy_policy">
					<?php
					if ( ! empty( $rcp_options['privacy_policy_link'] ) ) {
						echo '<a href="' . esc_url( $rcp_options['privacy_policy_link'] ) . '" target="_blank">';
					}

					if ( ! empty( $rcp_options['privacy_policy_label'] ) ) {
						echo $rcp_options['privacy_policy_label'];
					} else {
						_e( 'I agree to the privacy policy', 'rcp' );
					}

					if ( ! empty( $rcp_options['privacy_policy_link'] ) ) {
						echo '</a>';
					}
					?>
				</label>
			</p>
		</fieldset>
	<?php endif; ?>

	<?php
	if ( $do_we_have_levels ) { 
		do_action( 'rcp_before_registration_submit_field', $levels );
	} else {
		do_action( 'rcp_before_registration_submit_field' );
	}
	?>

	<?php if ( ! empty( $_GET['rcp_redirect'] ) ) : ?>
		<input type="hidden" name="rcp_redirect" value="<?php echo esc_url( $_GET[ 'rcp_redirect' ] ) ?>"/>
	<?php endif; ?>

	<p id="rcp_submit_wrap">
		<?php if ( $level ) { ?>
			<input type="hidden" name="rcp_level" class="rcp_level" value="<?php echo esc_attr( $level->get_id() ); ?>" rel="<?php echo esc_attr( $level->get_price() ); ?>" <?php if ( $level->has_trial() ) { echo 'data-has-trial="true"'; } ?> />
		<?php } ?>
		<input type="hidden" name="rcp_register_nonce" value="<?php echo wp_create_nonce('rcp-register-nonce' ); ?>"/>
		<input type="submit" name="rcp_submit_registration" id="rcp_submit" class="rcp-button" value="<?php esc_attr_e( apply_filters ( 'rcp_registration_register_button', __( 'Register', 'rcp' ) ) ); ?>"/>
	</p>
</form>
