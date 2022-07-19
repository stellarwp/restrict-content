<?php
/**
 * Registration Form - Total Details
 *
 * This template is loaded into register.php and register-single.php to display the total
 * membership cost, fees, and any recurring costs.
 * @link http://docs.restrictcontentpro.com/article/1597-registerform
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @since 3.4 Show discount information below fees if "Discount Signup Fees" is enabled.
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/Register/Total Details
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use RCP\Membership_Level;

if ( ! rcp_is_registration() ) {
	return;
}

$membership_level = rcp_get_membership_level( rcp_get_registration()->get_membership_level_id() );

if ( ! $membership_level instanceof Membership_Level ) {
	return;
}

global $rcp_options;
?>

<div class="rcp_registration_total_details rcp-table">

	<header>

		<div class="membership-amount">
			<div><p><?php _e( 'Membership', 'rcp' ); ?> <?php _e( 'Amount', 'rcp' ); ?></p></div>
		</div>

	</header>

	<main style="vertical-align: top;">

		<div class="membership-level-price">
			<div data-th="<?php esc_attr_e( 'Membership', 'rcp' ); ?>"><?php echo esc_html( $membership_level->get_name() ); ?></div>
			<div data-th="<?php esc_attr_e( 'Amount', 'rcp' ); ?>"><?php echo ! $membership_level->is_free() ? rcp_currency_filter( $membership_level->get_price() ) : __( 'free', 'rcp' ); ?></div>
		</div>

		<?php if ( ! $membership_level->is_free() ) : ?>
			<?php if ( rcp_get_registration()->get_fees() || rcp_get_registration()->get_discounts() ) : ?>
				<div>
					<div><?php _e( 'Discounts and Fees', 'rcp' ); ?></div>
			</div>

				<?php
				/**
				 * Discounts
				 *
				 * We show discount information here if discounts *do not* apply to signup fees.
				 */
				?>
				<?php if ( empty( $rcp_options['discount_fees'] ) ) : ?>
					<?php if ( rcp_get_registration()->get_discounts() ) : foreach( rcp_get_registration()->get_discounts() as $code => $recuring ) : if ( ! $discount = rcp_get_discount_details_by_code( $code ) ) continue; ?>
						<div class="rcp-discount">
							<div data-th="<?php esc_attr_e( 'Discount', 'rcp' ); ?>"><?php echo esc_html( $discount->get_name() ); ?></div>
							<div data-th="<?php esc_attr_e( 'Discount Amount', 'rcp' ); ?>"><?php echo esc_html( rcp_discount_sign_filter( $discount->get_amount(), $discount->get_unit() ) ); ?></div>
				</div>
					<?php endforeach; endif; ?>
				<?php endif; ?>

				<?php // Fees ?>
				<?php if ( rcp_get_registration()->get_fees() ) : foreach( rcp_get_registration()->get_fees() as $fee ) :

					$sign          = ( $fee['amount'] < 0 ) ? '-' : '';
					$fee['amount'] = abs( $fee['amount'] );
				?>
					<div class="rcp-fee">
						<div data-th="<?php esc_attr_e( 'Fee', 'rcp' ); ?>"><?php echo esc_html( $fee['description'] ); ?></div>
						<div data-th="<?php esc_attr_e( 'Fee Amount', 'rcp' ); ?>"><?php echo esc_html( $sign . rcp_currency_filter( $fee['amount'] ) ); ?></div>
				</div>
				<?php endforeach; endif; ?>

				<?php
				/**
				 * Discounts
				 *
				 * We show discount information here if discounts *do* apply to signup fees.
				 */
				?>
				<?php if ( ! empty( $rcp_options['discount_fees'] ) ) : ?>
					<?php if ( rcp_get_registration()->get_discounts() ) : foreach( rcp_get_registration()->get_discounts() as $code => $recuring ) : if ( ! $discount = rcp_get_discount_details_by_code( $code ) ) continue; ?>
						<div class="rcp-discount">
							<div data-th="<?php esc_attr_e( 'Discount', 'rcp' ); ?>"><?php echo esc_html( $discount->get_name() ); ?></div>
							<div data-th="<?php esc_attr_e( 'Discount Amount', 'rcp' ); ?>"><?php echo esc_html( rcp_discount_sign_filter( $discount->get_amount(), $discount->unit ) ); ?></div>
				</div>
					<?php endforeach; endif; ?>
				<?php endif; ?>

			<?php endif; ?>
		<?php endif; ?>

	</main>

	<footer>

		<div class="rcp-total">
			<div scope="row"><?php _e( 'Total Today', 'rcp' ); ?></div>
			<div data-th="<?php esc_attr_e( 'Total Today', 'rcp' ); ?>"><?php rcp_registration_total(); ?></div>
		</div>

		<?php if ( rcp_registration_is_recurring() ) : ?>
			<?php
			if ( 1 === $membership_level->get_duration() ) {
				$label = sprintf( __( 'Total Recurring Per %s', 'rcp' ), rcp_filter_duration_unit( $membership_level->get_duration_unit(), 1 ) );
			} else {
				$label = sprintf( __( 'Total Recurring Every %s %s', 'rcp' ), $membership_level->get_duration(), rcp_filter_duration_unit( $membership_level->get_duration_unit(), $membership_level->get_duration() ) );
			}

			if ( $membership_level->get_maximum_renewals() > 0 ) {
				$label = sprintf(
					__( '%d Additional Payments Every %s %s', 'rcp' ),
					$membership_level->get_maximum_renewals(),
					$membership_level->get_duration(),
					rcp_filter_duration_unit( $membership_level->get_duration_unit(), $membership_level->get_duration() )
				);
			}
			?>
			<div class="rcp-recurring-total">
				<div scope="row"><?php echo $label; ?></div>
				<div data-th="<?php echo esc_attr( $label ); ?>"><?php rcp_registration_recurring_total(); ?></div>
			</div>
		<?php endif; ?>

		<?php
		/**
		 * Insert content at the end of the table footer.
		 *
		 * @since 3.3
		 */
		do_action( 'rcp_register_total_details_footer_bottom' );
		?>

	</footer>
		</div>
