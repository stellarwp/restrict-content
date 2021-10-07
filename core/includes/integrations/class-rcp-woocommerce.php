<?php
/**
 * WooCommerce Integration
 *
 * @package     Restrict Content Pro
 * @subpackage  Integrations/WooCommerce
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.2
 */

class RCP_WooCommerce {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   2.2
	 */
	public function __construct() {

		if( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_filter( 'woocommerce_product_data_tabs', array( $this, 'data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'data_display' ) );
		add_action( 'save_post_product', array( $this, 'save_meta' ) );

		add_filter( 'woocommerce_is_purchasable', array( $this, 'is_purchasable' ), 999999, 2 );
		add_filter( 'woocommerce_product_is_visible', array( $this, 'is_visible' ), 999999, 2 );
		add_filter( 'wc_get_template_part', array( $this, 'hide_template' ), 999999, 3 );
	}

	/**
	 * Register the product settings tab
	 *
	 * @param array $tabs
	 *
	 * @access  public
	 * @since   2.2
	 * @return  array
	 */
	public function data_tab( $tabs ) {

		$tabs['access'] = array(
			'label'  => __( 'Access Control', 'rcp' ),
			'target' => 'rcp_access_control',
			'class'  => array(),
		);

		return $tabs;

	}

	/**
	 * Display product settings
	 *
	 * @access  public
	 * @since   2.2
	 * @return  void
	 */
	public function data_display() {
        ?>
		<div id="rcp_access_control" class="panel woocommerce_options_panel">

			<div class="options_group">
				<p><?php _e( 'Restrict purchasing of this product to:', 'rcp' ); ?></p>
				<?php

				woocommerce_wp_checkbox( array(
					'id'      => '_rcp_woo_active_to_purchase',
					'label'   => __( 'Active subscribers only?', 'rcp' ),
					'cbvalue' => 1
				) );

				$levels = (array) get_post_meta( get_the_ID(), '_rcp_woo_subscription_levels_to_purchase', true );
				foreach ( rcp_get_membership_levels( array( 'number' => 999 ) ) as $level ) {
					woocommerce_wp_checkbox( array(
						'name'    => '_rcp_woo_subscription_levels_to_purchase[]',
						'id'      => '_rcp_woo_subscription_level_' . $level->get_id(),
						'label'   => $level->get_name(),
						'value'   => in_array( $level->get_id(), $levels ) ? $level->get_id() : 0,
						'cbvalue' => $level->get_id()
					) );
				}

				woocommerce_wp_select( array(
					'id'      => '_rcp_woo_access_level_to_purchase',
					'label'   => __( 'Access level required?', 'rcp' ),
					'options' => rcp_get_access_levels()
				) );
				?>
			</div>

			<div class="options_group">
				<p><?php _e( 'Restrict viewing of this product to:', 'rcp' ); ?></p>
				<?php

				woocommerce_wp_checkbox( array(
					'id'      => '_rcp_woo_active_to_view',
					'label'   => __( 'Active subscribers only?', 'rcp' ),
					'cbvalue' => 1
				) );

				$levels = (array) get_post_meta( get_the_ID(), '_rcp_woo_subscription_levels_to_view', true );
				foreach ( rcp_get_membership_levels( array( 'number' => 999 ) ) as $level ) {
					woocommerce_wp_checkbox( array(
						'name'    => '_rcp_woo_subscription_levels_to_view[]',
						'id'      => '_rcp_woo_subscription_level_to_view_' . $level->get_id(),
						'label'   => $level->get_name(),
						'value'   => in_array( $level->get_id(), $levels ) ? $level->get_id() : 0,
						'cbvalue' => $level->get_id()
					) );
				}

				woocommerce_wp_select( array(
					'id'      => '_rcp_woo_access_level_to_view',
					'label'   => __( 'Access level required?', 'rcp' ),
					'options' => rcp_get_access_levels()
				) );
				?>
			</div>
			<input type="hidden" name="rcp_woocommerce_product_meta_box_nonce" value="<?php echo wp_create_nonce( 'rcp_woocommerce_product_meta_box_nonce' ); ?>" />
		</div>
		<?php
	}

	/**
	 * Saves product access settings
	 *
	 * @param int $post_id ID of the post being saved.
	 *
	 * @access  public
	 * @since   2.2
	 * @return  int|void
	 */
	public function save_meta( $post_id = 0 ) {

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! isset( $_POST['rcp_woocommerce_product_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_woocommerce_product_meta_box_nonce'], 'rcp_woocommerce_product_meta_box_nonce' ) ) {
			return;
		}

		// Don't save revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Check user permission
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if( isset( $_POST['_rcp_woo_active_to_purchase'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_active_to_purchase', 1 );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_active_to_purchase' );

		}

		if( isset( $_POST['_rcp_woo_access_level_to_purchase'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_access_level_to_purchase', sanitize_text_field( $_POST['_rcp_woo_access_level_to_purchase'] ) );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_access_level_to_purchase' );

		}

		if( isset( $_POST['_rcp_woo_subscription_levels_to_purchase'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_subscription_levels_to_purchase', array_map( 'absint', $_POST['_rcp_woo_subscription_levels_to_purchase'] ) );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_subscription_levels_to_purchase' );

		}

		if( isset( $_POST['_rcp_woo_active_to_view'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_active_to_view', 1 );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_active_to_view' );

		}

		if( isset( $_POST['_rcp_woo_access_level_to_view'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_access_level_to_view', sanitize_text_field( $_POST['_rcp_woo_access_level_to_view'] ) );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_access_level_to_view' );

		}

		if( isset( $_POST['_rcp_woo_subscription_levels_to_view'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_subscription_levels_to_view', array_map( 'absint', $_POST['_rcp_woo_subscription_levels_to_view'] ) );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_subscription_levels_to_view' );

		}

	}

	/**
	 * Restrict the ability to purchase a product
	 *
	 * @param bool       $ret
	 * @param WC_Product $product
	 *
	 * @access  public
	 * @since   2.2
	 * @return  bool
	 */
	public function is_purchasable( $ret, $product ) {

		if ( ! $ret ) {
			return $ret;
		}

		return rcp_user_can_purchase_woocommerce_product( get_current_user_id(), $product->get_id() );

	}

	/**
	 * Restrict the visibility of a product
	 *
	 * @param bool $ret
	 * @param int $product_id
	 *
	 * @access  public
	 * @since   2.2
	 * @return  bool
	 */
	public function is_visible( $ret, $product_id ) {

		if( ! $ret ) {
			return $ret;
		}

		if ( current_user_can( 'edit_post', $product_id ) ) {
			return true;
		}

		return rcp_user_can_view_woocommerce_product( get_current_user_id(), $product_id );

	}

	/**
	 * Loads the restricted content template if required.
	 *
	 * @param string $template
	 * @param string $slug
	 * @param string $name
	 *
	 * @access  public
	 * @since   2.5
	 * @return  string
	 */
	public function hide_template( $template, $slug, $name ) {

		$product_id = get_the_ID();

		if ( ! is_singular( 'product' ) ) {
			return $template;
		}

		if( 'content-single-product' !== $slug . '-' . $name ) {
			return $template;
		}

		if ( current_user_can( 'edit_post', $product_id ) ) {
			return $template;
		}

		if ( rcp_user_can_view_woocommerce_product( get_current_user_id(), $product_id ) ) {
			return $template;
		}

		return rcp_get_template_part( 'woocommerce', 'single-no-access', false );
	}

}
new RCP_WooCommerce;

/**
 * Determines whether or not a user is allowed to purchase a WooCommerce product.
 *
 * @param int $user_id    ID of the user to check.
 * @param int $product_id ID of the WooCommerce product.
 *
 * @since 3.0.6
 * @return bool
 */
function rcp_user_can_purchase_woocommerce_product( $user_id, $product_id ) {

	$customer     = rcp_get_customer_by_user_id( $user_id );
	$can_purchase = true;
	$active_only  = get_post_meta( $product_id, '_rcp_woo_active_to_purchase', true );
	$levels       = (array) get_post_meta( $product_id, '_rcp_woo_subscription_levels_to_purchase', true );
	$access_level = get_post_meta( $product_id, '_rcp_woo_access_level_to_purchase', true );

	$levels = array_filter( $levels );

	if ( ! empty( $active_only ) || ! empty( $levels ) || ! empty( $access_level ) ) {
		// Product has some kind of restrictions.
		if ( ! empty( $customer ) && $customer->is_pending_verification() ) {
			// Customer is pending email verification so they should not get access.
			$can_purchase = false;
		}
	}

	// Requires an active membership.
	if ( $active_only ) {
		if ( empty( $customer ) || ! $customer->has_active_membership() ) {
			$can_purchase = false;
		}
	}

	// Requires a specific membership level.
	if ( is_array( $levels ) && ! empty( $levels ) ) {
		if ( empty( $customer ) || ! count( array_intersect( rcp_get_customer_membership_level_ids( $customer->get_id() ), $levels ) ) ) {
			$can_purchase = false;
		}
	}

	if ( $access_level ) {
		if ( empty( $customer ) || ! $customer->has_access_level( $access_level  ) ) {
			$can_purchase = false;
		}
	}

	/**
	 * Filters whether or not the user has permission to purchase this product.
	 *
	 * @param bool $can_purchase Whether or not the user is allowed to purchase the product.
	 * @param int  $user_id      ID of the user being checked.
	 * @param int  $product_id   ID of the product being checked.
	 *
	 * @since 3.0.6
	 */
	return apply_filters( 'rcp_user_can_purchase_woocommerce_product', $can_purchase, $user_id, $product_id );

}

/**
 * Determines whether or not a user is allowed to view a WooCommerce product.
 *
 * @param int $user_id    ID of the user to check.
 * @param int $product_id ID of the WooCommerce product.
 *
 * @since 3.0.6
 * @return bool
 */
function rcp_user_can_view_woocommerce_product( $user_id, $product_id ) {

	$customer     = rcp_get_customer_by_user_id( $user_id );
	$can_view     = true;
	$active_only  = get_post_meta( $product_id, '_rcp_woo_active_to_view', true );
	$levels       = (array) get_post_meta( $product_id, '_rcp_woo_subscription_levels_to_view', true );
	$access_level = get_post_meta( $product_id, '_rcp_woo_access_level_to_view', true );

	$levels = array_filter( $levels );

	if ( ! empty( $active_only ) || ! empty( $levels ) || ! empty( $access_level ) ) {
		// Product has some kind of restrictions.
		if ( ! empty( $customer ) && $customer->is_pending_verification() ) {
			// Customer is pending email verification so they should not get access.
			$can_view = false;
		}
	}

	if ( $active_only ) {
		if ( empty( $customer ) || ! $customer->has_active_membership() ) {
			$can_view = false;
		}
	}

	if ( is_array( $levels ) && ! empty( $levels ) ) {
		if ( empty( $customer ) || ! count( array_intersect( rcp_get_customer_membership_level_ids( $customer->get_id() ), $levels ) ) ) {
			$can_view = false;
		}
	}

	if ( $access_level ) {
		if ( empty( $customer ) || ! $customer->has_access_level( $access_level ) ) {
			$can_view = false;
		}
	}

	if ( true === rcp_is_post_taxonomy_restricted( $product_id, 'product_cat', $user_id ) ) {
		$can_view = false;
	}

	if ( true === rcp_is_post_taxonomy_restricted( $product_id, 'product_tag', $user_id ) ) {
		$can_view = false;
	}

	/**
	 * Filters whether or not the user has permission to view this product.
	 *
	 * @param bool $can_view  Whether or not the user is allowed to view the product.
	 * @param int  $user_id    ID of the user being checked.
	 * @param int  $product_id ID of the product being checked.
	 *
	 * @since 3.0.6
	 */
	return apply_filters( 'rcp_user_can_view_woocommerce_product', $can_view, $user_id, $product_id );

}
