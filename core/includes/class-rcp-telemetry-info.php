<?php
/**
 * Collects RCP information and add it to the site health with telemetry.
 *
 * @since   3.5.28
 * @package RCP
 */

/**
 * Collects RCP information and add it to the site health with telemetry.
 *
 * @since   3.5.28
 */
class RCP_Telemetry_Info {

	/**
	 * RCP Options from Database.
	 *
	 * @var array $rcp_options The options.
	 */
	private $rcp_options = [];

	/**
	 * The constructor that initializes the RCP settings
	 *
	 * @since 3.5.28
	 */
	public function __construct() {
		$this->rcp_options = get_option( 'rcp_settings' );
	}

	/**
	 * The version RCP was updated from.
	 *
	 * @since 3.5.28
	 *
	 * @return string The version.
	 */
	public function rcp_last_updated() : string {
		return get_option( 'rcp_version_upgraded_on', '(unknown date)' );
	}
	/**
	 * Get the total Membership Levels.
	 *
	 * @since 3.5.28
	 *
	 * @return int 0 if not found, the total otherwise.
	 */
	public function total_membership_levels() : int {
		$levels = rcp_get_membership_levels( array( 'number' => 999 ) );
		if ( is_array( $levels ) ) {
			return count( $levels );
		} else {
			return 0;
		}
	}
	/**
	 * Get the total Paid Membership Levels.
	 *
	 * @since 3.5.28
	 *
	 * @return int 0 if not found, the total otherwise.
	 */
	public function total_paid_membership_levels() : int {
		$paid_membership = rcp_get_paid_levels();
		if ( is_array( $paid_membership ) ) {
			return count( $paid_membership );
		} else {
			return 0;
		}
	}
	/**
	 * Get the total Free Membership Levels.
	 *
	 * @since 3.5.28
	 *
	 * @return int 0 if not found, the total otherwise.
	 */
	public function total_free_membership_levels(): int {
		$free_levels = rcp_get_membership_levels(
			array(
				'price__in' => array( 0 ),
				'status'    => 'active',
				'number'    => 9999,
			)
		);

		if ( is_array( $free_levels ) ) {
			return count( $free_levels );
		} else {
			return 0;
		}
	}

	/**
	 * One-Time membership are those that the duration is set to 0.
	 *
	 * @since 3.5.28
	 * @link https://help.ithemes.com/hc/en-us/articles/1260807007649-Can-I-Create-a-One-Time-Payment-
	 *
	 * @return int 0 if not found, the total otherwise.
	 */
	public function total_one_time_membership_levels() : int {
		$one_time_levels = rcp_get_membership_levels(
			array(
				'duration' => 0,
				'status'   => 'active',
				'number'   => 9999,
			)
		);

		if ( is_array( $one_time_levels ) ) {
			return count( $one_time_levels );
		} else {
			return 0;
		}
	}
	/**
	 * Get the total Recurring Membership Levels.
	 *
	 * @since 3.5.28
	 *
	 * @return int 0 if not found, the total otherwise.
	 */
	public function total_recurring_membership_levels() : int {
		global $wpdb;
		// Array of values that will be use by the prepared method.
		$data = array(
			'status'   => 'active',
			'duration' => 0,
		);

		$sql = "SELECT * FROM {$wpdb->prefix}restrict_content_pro WHERE status = %s AND duration > %d LIMIT 9999";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$prepare = $wpdb->prepare( $sql, $data );
		// Execute the query with the prepared statements.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$recurring_levels = $wpdb->get_results( $prepare, ARRAY_A );

		if ( null !== $recurring_levels ) {
			return count( $recurring_levels );
		} else {
			return 0;
		}
	}
	/**
	 * Get the total Paying customers.
	 *
	 * @since 3.5.28
	 *
	 * @return int 0 if not found, the total otherwise.
	 */
	public function total_paying_customers() : int {
		global $wpdb;
		// Array of values that will be use by the prepared method.
		$data = array(
			'status'   => 'active',
			'disabled' => 0,
			'gateway'  => 'free',
		);

		$sql = "SELECT * FROM {$wpdb->prefix}rcp_memberships WHERE status = %s AND disabled = %d AND gateway <> %s";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$prepare = $wpdb->prepare( $sql, $data );
		// Execute the query with the prepared statements.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$paying_customers = $wpdb->get_results( $prepare, ARRAY_A );

		if ( null !== $paying_customers ) {
			return count( $paying_customers );
		} else {
			return 0;
		}
	}
	/**
	 * Get the total Free customers.
	 * Customers who have always had free memberships and have never ever paid for anything. Customers who have expired or canceled memberships should not show up here. Gold-star Free users only.
	 *
	 * @since 3.5.28
	 *
	 * @return int 0 if not found, the total otherwise.
	 */
	public function total_free_customers() : int {
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}rcp_memberships m INNER JOIN {$wpdb->prefix}restrict_content_pro r
				ON m.object_id = r.id
				WHERE m.status = 'active' AND m.disabled = 0 AND m.gateway IN ('free','manual')
				AND r.price = 0 AND m.customer_id NOT IN (
					SELECT customer_id FROM {$wpdb->prefix}rcp_memberships WHERE status = 'active' AND gateway NOT IN ('free','manual') and disabled = 0 GROUP BY customer_id
				)";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$paying_customers = $wpdb->get_results( $sql, ARRAY_A );

		if ( null !== $paying_customers ) {
			return count( $paying_customers );
		} else {
			return 0;
		}
	}
	/**
	 * Get the total No Membership customers.
	 * Customers with expired or canceled memberships. Customers who also have a free membership alongside their canceled or expired ones will show up here.
	 *
	 * @since 3.5.28
	 *
	 * @return int 0 if not found, the total otherwise.
	 */
	public function total_no_membership_customers() : int {
		global $wpdb;

		$sql = "SELECT m.customer_id FROM {$wpdb->prefix}rcp_memberships m
				INNER JOIN {$wpdb->prefix}rcp_customers c ON m.customer_id = c.id
				WHERE status IN ('expired','cancelled') OR m.customer_id IN (
					SELECT m.customer_id FROM {$wpdb->prefix}rcp_memberships m INNER JOIN {$wpdb->prefix}restrict_content_pro r
					ON m.object_id = r.id
					WHERE m.status = 'active' AND m.disabled = 0 AND m.gateway IN ('free','manual')
					AND r.price = 0 AND m.customer_id NOT IN (
						SELECT customer_id FROM {$wpdb->prefix}rcp_memberships WHERE status = 'active' AND gateway NOT IN ('free','manual') and disabled = 0 GROUP BY customer_id
					)
				)
				GROUP BY m.customer_id;";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$no_membership_customers = $wpdb->get_results( $sql, ARRAY_A );

		if ( null !== $no_membership_customers ) {
			return count( $no_membership_customers );
		} else {
			return 0;
		}
	}
	/**
	 * Get the Multiple Membership status.
	 *
	 * @since 3.5.28
	 *
	 * @return bool True if enabled, False otherwise.
	 */
	public function is_multiple_memberships() : bool {
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		return isset( $this->rcp_options['multiple_memberships'] ) && $this->rcp_options['multiple_memberships'] == '1';
	}
	/**
	 * Get the Monthly Revenue. This always returns the revenue of the current month.
	 *
	 * @since 3.5.28
	 *
	 * @return float The revenue.
	 */
	public function monthly_revenue(): float {
		$payments_db   = new RCP_Payments();
		$earnings      = (float) 0.00; // Total earnings for time period shown.
		$current_month = gmdate( 'm' );
		$current_year  = gmdate( 'Y' );
		// Args that will be used to query the Payments table.
		$args['date'] = [
			'month' => $current_month,
			'year'  => $current_year,
		];

		$payments  = $payments_db->get_earnings( $args );
		$earnings += $payments;
		return $earnings;
	}
	/**
	 * Get the active memberships and builds an array with the number of memberships and the name for each gateway.
	 *
	 * @since 3.5.28
	 *
	 * @return array The array with the gateways' info.
	 */
	public function payment_gateways() : array {
		$payment_gateways = new RCP_Payment_Gateways();
		$gateways         = [
			'total'            => 0,
			'enabled_gateways' => [],
		];
		$enabled_gateways = [];

		if ( is_array( $payment_gateways->enabled_gateways ) ) {
			$gateways['total'] = count( $payment_gateways->enabled_gateways );
			foreach ( $payment_gateways->enabled_gateways as $gateway ) {
				$enabled_gateways[] = $gateway['admin_label'];
			}
			$gateways['enabled_gateways'] = $enabled_gateways;
		}
		return $gateways;
	}
	/**
	 * Get the active RCP add-ons.
	 *
	 * @since 3.5.28
	 *
	 * @return array The array with the active add-ons.
	 */
	public function active_addons() : array {
		// Get plugins that have an update.
		$plugins          = get_plugins();
		$active_plugins   = get_option( 'active_plugins', array() );
		$active_addons    = [];
		$rcp_found_addons = [];
		$rcp_addons       = rcp_get_addons_list();

		foreach ( $active_plugins as $plugin ) {
			$plugin_path = explode( '/', $plugin );
			$plugin_slug = $plugin_path[0];
			if ( array_key_exists( $plugin_slug, $rcp_addons ) ) {
				$rcp_found_addons[] = $plugin_slug;
			}
		}

		foreach ( $rcp_found_addons as $addon ) {
			$active_addons[] = [
				'add_on' => $addon,
				'name'   => $rcp_addons[ $addon ],
			];
		}

		return $active_addons;
	}
	/**
	 * Get the inactive RCP add-ons.
	 *
	 * @since 3.5.28
	 *
	 * @return array The array with the inactive add-ons.
	 */
	public function deactivated_addons() : array {
		// Get plugins that have an update.
		$plugins          = get_plugins();
		$active_plugins   = get_option( 'active_plugins', array() );
		$inactive_plugins = [];
		$inactive_addons  = [];
		$rcp_found_addons = [];
		$rcp_addons       = rcp_get_addons_list();

		foreach ( $plugins as $plugin_path => $plugin ) {
			$a = $plugin;
			if ( ! in_array( $plugin_path, $active_plugins, true ) ) {
				$inactive_plugins[] = $plugin_path;
			}
		}

		foreach ( $inactive_plugins as $plugin ) {
			$plugin_path = explode( '/', $plugin );
			$plugin_slug = $plugin_path[0];
			if ( array_key_exists( $plugin_slug, $rcp_addons ) ) {
				$rcp_found_addons[] = $plugin_slug;
			}
		}

		foreach ( $rcp_found_addons as $addon ) {
			$inactive_addons[] = [
				'add_on' => $addon,
				'name'   => $rcp_addons[ $addon ],
			];
		}

		return $inactive_addons;
	}
}
