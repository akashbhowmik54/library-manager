<?php
/**
 * Plugin Name: Library Manager
 * Description: Custom WordPress plugin to manage books using a custom DB table, REST API, and React admin UI.
 * Version: 1.0.0
 * Author: Akash Kumar Bhowmik
 * Text Domain: library-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'LM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once LM_PLUGIN_DIR . 'includes/class-library-activator.php';

require_once LM_PLUGIN_DIR . 'includes/class-library-db.php';
require_once LM_PLUGIN_DIR . 'includes/class-library-rest.php';
require_once LM_PLUGIN_DIR . 'includes/class-library-admin.php';

register_activation_hook( __FILE__, [ 'Library_Activator', 'activate' ] );

add_action( 'plugins_loaded', 'lm_initialize_plugin' );

function lm_initialize_plugin() {
	Library_DB::init();
	Library_REST::init();
	Library_Admin::init();
}
