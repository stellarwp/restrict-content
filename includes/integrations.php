<?php
/**
 * Integrations with other plugins.
 *
 * @package     Restrict Content
 * @subpackage  Plugin Integrations
 * @copyright   Copyright (c) 2017, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Registers the Access Control tab on the WooCommerce product edit page.
 *
 * @since 2.2
 * @param $tabs array
 *
 * @return array
 */
function rc_add_woocommerce_product_data_tab( $tabs ) {

	$tabs['access'] = array(
		'label'  => __( 'Access Control', 'restrict-content' ),
		'target' => 'rc_access_control',
		'class'  => array(),
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'rc_add_woocommerce_product_data_tab' );

/**
 * Renders the HTML for the Access Control tab on the WooCommerce product edit page.
 *
 * @since 2.2
 */
function rc_add_woocommerce_product_data_tab_html() {

	?>

	<div id="rc_access_control" class="panel woocommerce_options_panel">
		<div class="options_group">
			<p>
				<strong><?php _e( 'Unlock product access control features with Restrict Content Pro', 'restrict-content' ); ?></strong>
			</p>
			<p>
				<?php printf(
					__( 'Restrict Content Pro enables you to control access to who can view and/or purchase your products, and optionally you can offer member-only discounts to paid subscribers. <a href="%s" target="_blank" rel="noopener noreferrer">Learn more...</a>', 'restrict-content' ),
					'https://restrictcontentpro.com/tour/features/woocommerce-integration/?utm_source=Restrict%20Content&utm_medium=plugin&utm_campaign=RCIntegrations&utm_content=WooCommerce%20Access%20Control'
				); ?>
			</p>
		</div>
	</div>

	<?php
}
add_action( 'woocommerce_product_data_panels', 'rc_add_woocommerce_product_data_tab_html' );