<?php
/**
 * Card Update Form Fields
 *
 * This form is displayed with the [rcp_update_card] shortcode.
 *
 * @link https://restrictcontentpro.com/knowledgebase/rcp_update_card
 *
 * The shortcode loads the `card-update-form.php` template, which then loads this template
 * for individual fields. Note that some gateways may not use these fields and may load
 * their own.
 * @see RCP_Payment_Gateway::update_card_fields()
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/Card Update Form
 * @copyright   Copyright (c) 2019, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

?>
<fieldset id="rcp-card-name-wrapper" class="rcp_card_fieldset">
	<p id="rcp_card_name_wrap">
		<label for="rcp-update-card-name"><?php _e( 'Name on Card', 'rcp' ); ?></label>
		<input type="text" size="20" id="rcp-update-card-name" name="rcp_card_name" class="rcp_card_name card-name" />
	</p>
</fieldset>

<fieldset id="rcp-card-wrapper" class="rcp_card_fieldset">
	<p id="rcp_card_number_wrap">
		<label for="rcp-update-card-number"><?php _e( 'Card Number', 'rcp' ); ?></label>
		<input type="text" size="20" maxlength="20" id="rcp-update-card-number" name="rcp_card_number" class="rcp_card_number card-number" />
	</p>
	<p id="rcp_card_cvc_wrap">
		<label for="rcp-update-card-cvc"><?php _e( 'Card CVC', 'rcp' ); ?></label>
		<input type="text" size="4" maxlength="4" id="rcp-update-card-cvc" name="rcp_card_cvc" class="rcp_card_cvc card-cvc" />
	</p>
	<p id="rcp_card_zip_wrap">
		<label for="rcp-update-card-zip"><?php _e( 'Card ZIP or Postal Code', 'rcp' ); ?></label>
		<input type="text" size="10" id="rcp-update-card-zip" name="rcp_card_zip" class="rcp_card_zip card-zip" />
	</p>
	<p id="rcp_card_exp_wrap">
		<label for="rcp-update-card-expiration-month"><?php _e( 'Expiration (MM/YYYY)', 'rcp' ); ?></label>
		<select name="rcp_card_exp_month" id="rcp-update-card-expiration-month" class="rcp_card_exp_month card-expiry-month">
			<?php for( $i = 1; $i <= 12; $i++ ) : ?>
				<option value="<?php echo $i; ?>"><?php echo $i . ' - ' . rcp_get_month_name( $i ); ?></option>
			<?php endfor; ?>
		</select>
		<span class="rcp_expiry_separator"> / </span>
		<select name="rcp_card_exp_year" id="rcp-update-card-expiration-year" class="rcp_card_exp_year card-expiry-year">
			<?php
			$year = date( 'Y' );
			for( $i = $year; $i <= $year + 10; $i++ ) : ?>
				<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
			<?php endfor; ?>
		</select>
	</p>
</fieldset>
