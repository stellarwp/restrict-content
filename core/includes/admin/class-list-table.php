<?php
/**
 * List Table Base Class
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

namespace RCP\Admin;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class List_Table
 *
 * @since   3.0
 * @package RCP\Admin
 */
abstract class List_Table extends \WP_List_Table {

	/**
	 * Number of results to show per page.
	 *
	 * @since 3.0
	 * @var   int
	 */
	public $per_page = 30;

	/**
	 * Counts.
	 *
	 * @since 3.0
	 * @var   array
	 */
	public $counts = array(
		'total' => 0
	);

	/**
	 * List_Table constructor.
	 *
	 * @param $args
	 *
	 * @since 3.0.3
	 */
	public function __construct( $args ) {
		parent::__construct( $args );

		$this->set_per_page();

		add_filter( 'removable_query_args', array( $this, 'removable_query_args' ) );
	}

	/**
	 * Set number of results per page
	 *
	 * This uses the screen options setting if available. Otherwise it defaults to 30.
	 *
	 * @since  3.0.3
	 * @access protected
	 */
	protected function set_per_page() {

		$per_page      = 30;
		$user_id       = get_current_user_id();
		$screen        = get_current_screen();
		$screen_option = $screen->get_option( 'per_page', 'option' );

		if ( ! empty( $screen_option ) ) {
			$per_page = get_user_meta( $user_id, $screen_option, true );

			if ( empty( $per_page ) || $per_page < 1 ) {
				$per_page = $screen->get_option( 'per_page', 'default' );
			}
		}

		$this->per_page = $per_page;

	}

	/**
	 * Get a request var, or return the default if not set.
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @since 3.0
	 * @return mixed Un-sanitized request var
	 */
	public function get_request_var( $var = '', $default = false ) {
		return isset( $_REQUEST[$var] )
			? $_REQUEST[$var]
			: $default;
	}

	/**
	 * Get a status request var, if set.
	 *
	 * @param mixed $default
	 *
	 * @since 3.0
	 * @return string
	 */
	protected function get_status( $default = '' ) {
		return sanitize_key( $this->get_request_var( 'status', $default ) );
	}

	/**
	 * Retrieve the current page number.
	 *
	 * @since 3.0
	 * @return int Current page number.
	 */
	protected function get_paged() {
		return absint( $this->get_request_var( 'paged', 1 ) );
	}

	/**
	 * Retrieve the offset to use for the query.
	 *
	 * @since 3.0
	 * @return int Offset.
	 */
	protected function get_offset() {
		$offset = ( $this->get_paged() - 1 ) * $this->per_page;

		return absint( $offset );
	}

	/**
	 * Retrieve the current page number.
	 *
	 * @since 3.0
	 * @return int Current page number.
	 */
	protected function get_search() {
		return rawurldecode( trim( $this->get_request_var( 's', '' ) ) );
	}

	/**
	 * Generate the table navigation above or below the table.
	 *
	 * We're overriding this to turn off the referer param in `wp_nonce_field()`.
	 *
	 * @param string $which
	 *
	 * @since 3.1
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'], '_wpnonce', false );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ) : ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
			<?php
			endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear"/>
		</div>
		<?php
	}

	/**
	 * Show the search field.
	 *
	 * @param string $text     Label for the search box
	 * @param string $input_id ID of the search box
	 *
	 * @since 3.0
	 */
	public function search_box( $text, $input_id ) {

		// Bail if no items and no search
		if ( ! $this->get_search() && ! $this->has_items() ) {
			return;
		}

		$orderby  = $this->get_request_var( 'orderby' );
		$order    = $this->get_request_var( 'order' );
		$input_id = $input_id . '-search-input';

		if ( ! empty( $orderby ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $orderby ) . '" />';
		}

		if ( ! empty( $order ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $order ) . '" />';
		}

		?>

		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( esc_html( $text ), 'button', false, false, array( 'ID' => 'search-submit' ) ); ?>
		</p>

		<?php
	}

	/**
	 * Get the base URL for this list table.
	 *
	 * @since 3.0
	 * @return string Base URL.
	 */
	public function get_base_url() {
		return admin_url();
	}

	/**
	 * Retrieve the view types
	 *
	 * @since 3.0
	 * @return array $views All the views available
	 */
	public function get_views() {

		// Get the current status
		$current = $this->get_status();

		// Args to remove
		$remove = array( 'rcp_message', 'status', 'paged', '_wpnonce' );

		// Base URL
		$url = remove_query_arg( $remove, $this->get_base_url() );

		// Is all selected?
		$class = in_array( $current, array( '', 'all' ), true )
			? ' class="current"'
			: '';

		// All
		$count = '&nbsp;<span class="count">(' . esc_attr( $this->counts['total'] ) . ')</span>';
		$label = __( 'All', 'rcp' ) . $count;
		$views = array(
			'all' => sprintf( '<a href="%s"%s>%s</a>', $url, $class, $label ),
		);

		// Remove total from counts array
		$counts = $this->counts;
		unset( $counts['total'] );

		// Loop through statuses.
		if ( ! empty( $counts ) ) {
			foreach ( $counts as $status => $count ) {
				$count_url = add_query_arg( array(
					'status' => urlencode( $status ),
					'paged'  => false,
				), $url );

				$class = ( $current === $status )
					? ' class="current"'
					: '';

				$count = '&nbsp;<span class="count">(' . absint( $this->counts[$status] ) . ')</span>';

				$label          = rcp_get_status_label( $status ) . $count;
				$views[$status] = sprintf( '<a href="%s"%s>%s</a>', $count_url, $class, $label );
			}
		}

		return $views;
	}

	/**
	 * Remove "action" query arg. This prevents the bulk action admin notice from persisting across page views.
	 *
	 * @param array $query_args
	 *
	 * @since 3.1
	 * @return array
	 */
	public function removable_query_args( $query_args ) {
		$query_args[] = 'action';

		return $query_args;
	}

}
