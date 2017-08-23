<?php
/**
 * Plugin Name: Restrict Content
 * Plugin URL: http://pippinsplugins.com/restricted-content-plugin-free/
 * Description: Restrict Content to registered users only. This is a simple plugin that will allow you to easily restrict complete posts / pages to logged in users only.
 * Version: 2.1.3
 * Author: Pippin Williamson
 * Author URI: http://pippinsplugins.com
 * Contributors: mordauk
 * Tags: Restrict content, member only, registered, logged in, restricted access, restrict access, limiit access, read-only, read only
 */


/*******************************************
* global variables
*******************************************/

// load the plugin options
$rc_options = get_option( 'rc_settings' );

if( ! defined('RC_PLUGIN_DIR' ) ) {
	define('RC_PLUGIN_DIR', dirname(__FILE__));
}

/**
 * Load textdomain
 *
 * @return void
 */
function rc_textdomain() {

	// Set filter for plugin's languages directory
	$rc_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$rc_lang_dir = apply_filters( 'rc_languages_directory', $rc_lang_dir );

	// Load the translations
	load_plugin_textdomain( 'restrict-content', false, $rc_lang_dir );
}
add_action( 'init', 'rc_textdomain' );



/*******************************************
* file includes
*******************************************/
require_once  RC_PLUGIN_DIR . '/includes/misc-functions.php';
require_once  RC_PLUGIN_DIR . '/includes/forms.php';
include(RC_PLUGIN_DIR . '/includes/settings.php');
include(RC_PLUGIN_DIR . '/includes/shortcodes.php');
include(RC_PLUGIN_DIR . '/includes/metabox.php');
include(RC_PLUGIN_DIR . '/includes/display-functions.php');
include(RC_PLUGIN_DIR . '/includes/feed-functions.php');
include(RC_PLUGIN_DIR . '/includes/user-checks.php');
