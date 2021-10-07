<?php
/**
 * Admin Add-Ons
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Add-Ons
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add-ons Page
 *
 * Renders the add-ons page content.
 *
 * @return void
 */
function rcp_add_ons_admin() {
	$add_ons_tabs = apply_filters( 'rcp_add_ons_tabs', array( 'pro' => 'Pro', 'official-free' => 'Official Free' ) );
	$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $add_ons_tabs ) ? $_GET['tab'] : 'pro';

	ob_start();
	?>
	<div class="wrap" id="rcp-add-ons">
		<h1>
			<?php _e( 'Add-ons for Restrict Content Pro', 'rcp' ); ?>
			<span>
				&nbsp;&nbsp;<a href="https://restrictcontentpro.com/add-ons/?utm_source=plugin-add-ons-page&utm_medium=plugin&utm_campaign=Restrict%20Content%20Pro%20Add-ons%20Page&utm_content=All%20Add-ons" class="button-primary" title="<?php _e( 'Browse all add-ons', 'rcp' ); ?>" target="_blank"><?php _e( 'Browse all add-ons', 'rcp' ); ?></a>
			</span>
		</h1>
		<p><?php _e( 'These add-ons <em><strong>add functionality</strong></em> to your Restrict Content Pro-powered site.', 'rcp' ); ?></p>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach( $add_ons_tabs as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab'              => urlencode( $tab_id )
				) );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
					echo esc_html( $tab_name );
				echo '</a>';
			}
			?>

		</h2>
		<div id="tab_container">

			<?php if ( 'pro' === $active_tab ) : ?>
				<p><?php printf( __( 'Pro add-ons are only available with a Professional or Ultimate license. If you already have one of these licenses, simply <a href="%s">log in to your account</a> to download any of these add-ons.', 'rcp' ), 'https://restrictcontentpro.com/account/?utm_source=plugin-add-ons-page&utm_medium=plugin&utm_campaign=Restrict%20Content%20Pro%20Add-ons%20Page&utm_content=Account' ); ?></p>
				<p><?php printf( __( 'If you have a Personal or Plus license, you can easily upgrade from your account page to <a href="%s">get access to all of these add-ons</a>!', 'rcp' ), 'https://restrictcontentpro.com/account/?utm_source=plugin-add-ons-page&utm_medium=plugin&utm_campaign=Restrict%20Content%20Pro%20Add-ons%20Page&utm_content=Account' ); ?></p>
			<?php else : ?>
				<p><?php _e( 'Our official free add-ons are available to all license holders!', 'rcp' ); ?></p>
			<?php endif; ?>

			<?php echo rcp_add_ons_get_feed( $active_tab ); ?>
			<div class="rcp-add-ons-footer">
				<a href="https://restrictcontentpro.com/add-ons/?utm_source=plugin-add-ons-page&utm_medium=plugin&utm_campaign=Restrict%20Content%20Pro%20Add-ons%20Page&utm_content=All%20Add-ons" class="button-primary" title="<?php _e( 'Browse all add-ons', 'rcp' ); ?>" target="_blank"><?php _e( 'Browse all add-ons', 'rcp' ); ?></a>
			</div>
		</div>
	</div>
	<?php
	echo ob_get_clean();
}

/**
 * Add-ons Get Feed
 *
 * Gets the add-ons page feed.
 *
 * @param string $tab Which type of add-ons to retrieve.
 *
 * @return string
 */
function rcp_add_ons_get_feed( $tab = 'pro' ) {

	$cache = get_transient( 'rcp_add_ons_feed_' . $tab );

	if ( false === $cache ) {
		$url = 'https://restrictcontentpro.com/?feed=feed-add-ons';

		if ( 'pro' !== $tab ) {
			$url = add_query_arg( array( 'display' => urlencode( $tab ) ), $url );
		}

		$feed = wp_remote_get( esc_url_raw( $url ) );

		if ( ! is_wp_error( $feed ) ) {

			if ( isset( $feed['body'] ) && strlen( $feed['body'] ) > 0 ) {
				$cache = wp_remote_retrieve_body( $feed );
				set_transient( 'rcp_add_ons_feed_' . $tab, $cache, HOUR_IN_SECONDS );
			}

		} else {
			$cache = '<div class="error"><p>' . __( 'There was an error retrieving the add-ons list from the server. Please try again later.', 'rcp' ) . '</div>';
		}

	}

	return $cache;

}
