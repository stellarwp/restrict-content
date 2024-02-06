<?php
/**
 * Restrict Content Pro Base Class
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 */

use RCP\Database\Tables\Customers;
use RCP\Database\Tables\Discounts;
use RCP\Database\Tables\Membership_Counts;
use RCP\Database\Tables\Membership_Meta;
use RCP\Database\Tables\Memberships;
use RCP\Database\Tables\Queue;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Restrict_Content_Pro' ) ) :


	/**
	 * Class Restrict_Content_Pro
	 *
	 * @since 3.0
	 */
	final class Restrict_Content_Pro {
		const VERSION = '3.5.40';

		/**
		 * Stores the base slug for the extension.
		 *
		 * @since 3.5.18
		 *
		 * @var string
		 */
		const FILE = RCP_PLUGIN_FILE;

		/**
		 * @var Restrict_Content_Pro The one true Restrict_Content_Pro
		 *
		 * @since 3.0
		 */
		private static $instance;

		/**
		 * RCP loader file.
		 *
		 * @since 3.0
		 * @var string
		 */
		private $file = '';

		/**
		 * @var Customers
		 */
		public $customers_table;

		/**
		 * @var Discounts
		 */
		public $discounts_table;

		/**
		 * @var Memberships
		 */
		public $memberships_table;

		/**
		 * @var Membership_Meta
		 */
		public $membership_meta_table;

		/**
		 * @var Queue
		 */
		public $queue_table;

		/**
		 * @var Membership_Counts
		 */
		public $membership_counts_table;

		/**
		 * @var \RCP\Component[]
		 */
		public $components;

		/**
		 * @var boolean
		 */
		private $is_pro;

		/**
		 * Main Restrict_Content_Pro Instance.
		 *
		 * Insures that only one instance of Restrict_Content_Pro exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since     3.0
		 *
		 * @static
		 * @staticvar array $instance
		 *
		 * @uses      Restrict_Content_Pro::setup_constants() Setup constants.
		 * @uses      Restrict_Content_Pro::setup_files() Setup required files.
		 * @see       restrict_content_pro()
		 *
		 * @param string $file Main plugin file path.
		 *
		 * @return Restrict_Content_Pro The one true Restrict_Content_Pro
		 */
		public static function instance( $file = '' ) {

			// Return if already instantiated
			if ( self::is_instantiated() ) {
				return self::$instance;
			}

			// Setup the singleton
			self::setup_instance( $file );

			// Bootstrap
			self::$instance->setup_constants();
			self::$instance->setup_files();
			self::$instance->setup_globals();
			self::$instance->setup_application();

			// Backwards compat globals
			self::$instance->backcompat_globals();

			// Return the instance
			return self::$instance;

		}

		/**
		 * Throw error on object clone.
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since  3.0
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'rcp' ), '3.0' );
		}

		/**
		 * Disable un-serializing of the class.
		 *
		 * @since  3.0
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'rcp' ), '3.0' );
		}

		/**
		 * Return whether the main loading class has been instantiated or not.
		 *
		 * @access private
		 * @since  3.0
		 * @return boolean True if instantiated. False if not.
		 */
		private static function is_instantiated() {

			// Return true if instance is correct class
			if ( ! empty( self::$instance ) && ( self::$instance instanceof Restrict_Content_Pro ) ) {
				return true;
			}

			// Return false if not instantiated correctly
			return false;
		}

		/**
		 * Setup the singleton instance
		 *
		 * @param string $file Path to main plugin file.
		 *
		 * @access private
		 * @since  3.0
		 */
		private static function setup_instance( $file = '' ) {
			self::$instance       = new Restrict_Content_Pro();
			self::$instance->file = $file;
		}

		/**
		 * Setup plugin constants.
		 *
		 * @access private
		 * @since  3.0
		 * @return void
		 */
		private function setup_constants() {

			if ( ! defined( 'RCP_PLUGIN_VERSION' ) ) {
				define( 'RCP_PLUGIN_VERSION', self::VERSION );
			}

			if ( ! defined( 'RCP_PLUGIN_FILE' ) ) {
				define( 'RCP_PLUGIN_FILE', $this->file );
			}

			if ( ! defined( 'RCP_PLUGIN_DIR' ) ) {
				define( 'RCP_PLUGIN_DIR', plugin_dir_path( RCP_PLUGIN_FILE ) );
			}

			if ( ! defined( 'RCP_PLUGIN_URL' ) ) {
				define( 'RCP_PLUGIN_URL', plugin_dir_url( RCP_PLUGIN_FILE ) );
			}

			if ( ! defined( 'CAL_GREGORIAN' ) ) {
				define( 'CAL_GREGORIAN', 1 );
			}

		}

		/**
		 * Setup globals
		 *
		 * @access private
		 * @since  3.0.1
		 * @return void
		 */
		private function setup_globals() {
			$GLOBALS['rcp_options'] = get_option( 'rcp_settings', array() );
		}

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since  3.0
		 * @return void
		 */
		private function setup_files() {
			$this->include_files();

			// Admin
			if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				$this->include_admin();
			} else {
				$this->include_frontend();
			}
		}


		/**
		 * Setup the rest of the application
		 *
		 * @since 3.0
		 */
		private function setup_application() {

			rcp_setup_components();

			self::$instance->customers_table         = $this->components['customers']->get_interface( 'table' );
			self::$instance->discounts_table         = $this->components['discounts']->get_interface( 'table' );
			self::$instance->memberships_table       = $this->components['memberships']->get_interface( 'table' );
			self::$instance->membership_meta_table   = $this->components['memberships']->get_interface( 'meta' );
			self::$instance->queue_table             = $this->components['queue']->get_interface( 'table' );
			self::$instance->membership_counts_table = $this->components['membership_counts']->get_interface( 'table' );

		}

		/** Includes **************************************************************/

		/**
		 * Include global files
		 *
		 * @access public
		 * @since  3.0
		 */
		private function include_files() {

			// Core.
			require_once RCP_PLUGIN_DIR . 'core/includes/class-rcp-helper-cast.php';

			// Components
			require_once RCP_PLUGIN_DIR . 'core/includes/class-rcp-base-object.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/component-functions.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/class-rcp-component.php';

			// Database
			require_once RCP_PLUGIN_DIR . 'core/includes/database/engine/class-base.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/engine/class-table.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/engine/class-compare.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/engine/class-date.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/engine/class-query.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/engine/class-column.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/engine/class-row.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/engine/class-schema.php';

			// Tables
			require_once RCP_PLUGIN_DIR . 'core/includes/database/customers/class-customers-table.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/discounts/class-discounts-table.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/logs/class-logs-table.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/membership-counts/class-membership-counts-table.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/membership-levels/class-membership-levels-table.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/memberships/class-memberships-table.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/memberships/class-membership-meta-table.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/queue/class-queue-table.php';

			// Queries
			require_once RCP_PLUGIN_DIR . 'core/includes/database/customers/class-customer-query.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/discounts/class-discount-query.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/logs/class-log-query.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/membership-counts/class-membership-count-query.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/membership-levels/class-membership-level-query.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/memberships/class-membership-query.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/queue/class-queue-query.php';

			// Schemas
			require_once RCP_PLUGIN_DIR . 'core/includes/database/customers/class-customers-schema.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/discounts/class-discounts-schema.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/logs/class-logs-schema.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/membership-counts/class-membership-counts-schema.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/membership-levels/class-membership-levels-schema.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/memberships/class-memberships-schema.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/database/queue/class-queue-schema.php';

			// Payment Gateway
			require_once( RCP_PLUGIN_DIR . 'core/includes/gateways/class-rcp-payment-gateway.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/gateways/class-rcp-payment-gateway-manual.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/gateways/class-rcp-payment-gateways.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/gateways/gateway-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/gateways/gateway-functions.php' );
			rcp_load_gateway_files();

			// Membership Levels
			require_once( RCP_PLUGIN_DIR . 'core/includes/membership-levels/class-rcp-membership-level.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/membership-levels/membership-level-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/levels/level-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/levels/meta.php' );

			// Memberships
			require_once( RCP_PLUGIN_DIR . 'core/includes/memberships/class-rcp-membership.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/memberships/membership-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/memberships/membership-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/memberships/meta.php' );

			// Membership Counts
			require_once( RCP_PLUGIN_DIR . 'core/includes/membership-counts/class-rcp-membership-count.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/membership-counts/membership-count-functions.php' );

			// Member
			require_once( RCP_PLUGIN_DIR . 'core/includes/member-forms.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/member-functions.php' );

			// Discounts
			require_once( RCP_PLUGIN_DIR . 'core/includes/discounts/class-rcp-discount.php');
			require_once( RCP_PLUGIN_DIR . 'core/includes/discounts/discount-functions.php');

			// @todo can this be improved?
			require( RCP_PLUGIN_DIR . 'core/includes/install.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/class-rcp-capabilities.php' );
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				require_once( RCP_PLUGIN_DIR . 'core/includes/class-rcp-cli.php' );
				require_once( RCP_PLUGIN_DIR . 'core/includes/membership-levels/membership-level-commands.php' );
			}
			require_once( RCP_PLUGIN_DIR . 'core/includes/class-rcp-emails.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/class-rcp-integrations.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/class-rcp-levels.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/class-rcp-logging.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/class-rcp-member.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/class-rcp-payments.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/class-rcp-registration.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/class-rcp-reminders.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/scripts.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/ajax-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/captcha-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/cron-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/compat/class-base.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/compat/class-member.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/customers/class-rcp-customer.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/customers/customer-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/customers/customer-functions.php' );

			require_once( RCP_PLUGIN_DIR . 'core/includes/email-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/invoice-functions.php' );

			require_once( RCP_PLUGIN_DIR . 'core/includes/login-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/logs/class-log.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/logs/log-functions.php' );

			require_once( RCP_PLUGIN_DIR . 'core/includes/payments/meta.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/misc-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/payments/payment-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/payments/payment-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/registration-functions.php' );

			require_once( RCP_PLUGIN_DIR . 'core/includes/error-tracking.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/shortcodes.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/template-functions.php' );

			// Batch
			require_once RCP_PLUGIN_DIR . 'core/includes/batch/interface-job-callback.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/batch/abstract-job-callback.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/batch/batch-functions.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/batch/class-job.php';

			// Deprecated
			require_once( RCP_PLUGIN_DIR . 'core/includes/deprecated/class-rcp-discounts.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/deprecated/subscription-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/deprecated/functions.php' );
			// @todo remove
			if ( ! class_exists( 'WP_Logging' ) ) {
				require_once( RCP_PLUGIN_DIR . 'core/includes/deprecated/class-wp-logging.php' );
			}

			// Stripe
			require_once( RCP_PLUGIN_DIR . 'core/includes/gateways/class-rcp-payment-gateway-stripe.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/gateways/stripe/functions.php' );

			// @todo load this only when needed
			require_once RCP_PLUGIN_DIR . 'core/includes/batch/v3/class-migrate-memberships.php';

			// block functions
			require_once RCP_PLUGIN_DIR . 'core/includes/block-functions.php';

			// Integrations.
			require_once RCP_PLUGIN_DIR . 'core/includes/integrations/class-rcp-telemetry.php';
			require_once RCP_PLUGIN_DIR . 'core/includes/class-rcp-telemetry-info.php';

			if ( file_exists( RCP_PLUGIN_DIR . 'pro/class-restrict-content-pro.php') ) {
				require_once( RCP_PLUGIN_DIR . 'pro/class-restrict-content-pro.php' );
				include_pro_files();
			}

		}

		/**
		 * Setup administration
		 *
		 * @since 3.0
		 */
		private function include_admin() {

			global $rcp_options;

			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/upgrades.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/class-list-table.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/class-rcp-upgrades.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/admin-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/admin-pages.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/admin-notices.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/admin-ajax-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/class-rcp-add-on-updater.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/customers/customer-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/customers/customers-page.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/screen-options.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/members/member-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/members/members-page.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/memberships/membership-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/reminders/subscription-reminders.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/settings/settings.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/subscriptions/subscription-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/subscriptions/subscription-levels.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/payments/payment-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/payments/payments-page.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/export.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/export/export-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/tools/tools-page.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/import/import-actions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/import/import-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/help/help-menus.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/metabox.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/add-ons.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/terms.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/post-types/restrict-post-type.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/user-page-columns.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/export-functions.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/deactivation.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/admin/deprecated-admin-functions.php' );

			// batch processing
			require_once RCP_PLUGIN_DIR . 'core/includes/admin/batch/ajax-actions.php';

			if ( file_exists( RCP_PLUGIN_DIR . 'pro/class-restrict-content-pro.php') ) {
				require_once( RCP_PLUGIN_DIR . 'pro/class-restrict-content-pro.php' );
				include_pro_admin_files();
			}

		}

		/**
		 * Setup front-end specific code
		 *
		 * @since 3.0
		 */
		private function include_frontend() {

			require_once( RCP_PLUGIN_DIR . 'core/includes/content-filters.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/query-filters.php' );
			require_once( RCP_PLUGIN_DIR . 'core/includes/redirects.php' );

		}

		/**
		 * Backwards compatibility for old global values
		 *
		 * @since 3.0
		 */
		private function backcompat_globals() {

			global $wpdb, $rcp_payments_db, $rcp_levels_db, $rcp_discounts_db;

			// the plugin base directory
			global $rcp_base_dir; // not used any more, but just in case someone else is
			$rcp_base_dir = dirname( __FILE__ );

			global $rcp_db_name;
			$rcp_db_name = rcp_get_levels_db_name();

			global $rcp_db_version;
			$rcp_db_version = '1.6';

			global $rcp_discounts_db_name;
			$rcp_discounts_db_name = rcp_get_discounts_db_name();

			global $rcp_discounts_db_version;
			$rcp_discounts_db_version = '1.2';

			global $rcp_payments_db_name;
			$rcp_payments_db_name = rcp_get_payments_db_name();

			global $rcp_payments_db_version;
			$rcp_payments_db_version = '1.5';

			/* database table query globals */

			$rcp_payments_db       = new RCP_Payments;
			$rcp_levels_db         = new RCP_Levels;
			$rcp_discounts_db      = new RCP_Discounts;
			$wpdb->levelmeta       = $rcp_levels_db->meta_db_name;
			$wpdb->rcp_paymentmeta = $rcp_payments_db->meta_db_name;

			/* settings page globals */
			global $rcp_members_page;
			global $rcp_subscriptions_page;
			global $rcp_discounts_page;
			global $rcp_payments_page;
			global $rcp_settings_page;
			global $rcp_reports_page;
			global $rcp_export_page;
			global $rcp_help_page;

		}

		/**
		 * Check if the current instance is PRO. This does not fully determine if the actual code is PRO.
		 * The main purpose of this function is for labels.
		 *
		 * @since 3.5.28
		 * @return bool
		 */
		public function is_pro() {
			if( false === has_action('admin_menu','include_pro_pages') ) {
				return false;
			}
			return true;
		}

	}

endif; // End if class_exists check.

/**
 * Returns the instance of Restrict_Content_Pro.
 *
 * The main function responsible for returning the one true Restrict_Content_Pro
 * instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $rcp = restrict_content_pro(); ?>
 *
 * @since 3.0
 * @return Restrict_Content_Pro The one true Restrict_Content_Pro instance.
 */
function restrict_content_pro() {
	return Restrict_Content_Pro::instance();
}
