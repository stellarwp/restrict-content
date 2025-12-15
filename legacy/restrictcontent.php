<?php

/*******************************************
* global variables
*******************************************/

// load the plugin options
global $rc_options;

if( false === $rc_options ) {
    $options = array (
        'shortcode_message' => 'You do not have access to this post.',
        'administrator_message' => 'This content is for Administrator Users.',
        'editor_message' => 'This content is for Editor Users.',
        'author_message' => 'This content is for Author Users.',
        'contributor_message' => 'This content is for Contributor Users.',
        'subscriber_message' => 'This content is for Subscribers Users',
    );

   update_option( 'rc_settings', $options );
}

if ( ! defined( 'RC_PLUGIN_VERSION' ) ) {
	define( 'RC_PLUGIN_VERSION', '3.2.16' );
}

if ( ! defined( 'RC_PLUGIN_DIR' ) ) {
	define( 'RC_PLUGIN_DIR', dirname(__FILE__) );
}

if ( ! defined( 'RC_PLUGIN_URL' ) ) {
	define( 'RC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
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
require_once  RC_PLUGIN_DIR . '/includes/scripts.php';
require_once  RC_PLUGIN_DIR . '/includes/upgrades.php';
require_once  RC_PLUGIN_DIR . '/includes/integrations.php';
include(RC_PLUGIN_DIR . '/includes/settings.php');
include(RC_PLUGIN_DIR . '/includes/shortcodes.php');
include(RC_PLUGIN_DIR . '/includes/metabox.php');
include(RC_PLUGIN_DIR . '/includes/display-functions.php');
include(RC_PLUGIN_DIR . '/includes/feed-functions.php');
include(RC_PLUGIN_DIR . '/includes/user-checks.php');


if ( is_admin() && file_exists( RC_PLUGIN_DIR . '/lib/icon-fonts/load.php' ) ) {
	require( RC_PLUGIN_DIR . "/lib/icon-fonts/load.php" );
}

register_activation_hook( __FILE__, function() {
	if ( current_user_can( 'manage_options' ) ) {
		add_option( 'Restrict_Content_Plugin_Activated', 'restrict-content' );
	}
} );

add_action( 'admin_init', 'restrict_content_plugin_activation' );

function restrict_content_plugin_activation() {
	if ( is_admin() && get_option( 'Restrict_Content_Plugin_Activated' ) === 'restrict-content' ) {
		delete_option('Restrict_Content_Plugin_Activated' );
		wp_safe_redirect( admin_url( 'admin.php?page=restrict-content-welcome' ) );
		die();
	}
}
