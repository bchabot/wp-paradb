<?php
/**
 * The plugin bootstrap file
 *
 * @wordpress-plugin
 * Plugin Name:       WordPress Paranormal Database
 * Plugin URI:        https://github.com/bchabot/wp-paradb
 * Description:       A comprehensive system for recording, archiving, and sharing paranormal witness reports, investigations, and research.
 * Version:           0.0.6
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Brian Chabot
 * Author URI:        https://brianchabot.org/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wp-paradb
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 */
define( 'WP_PARADB_VERSION', '0.0.6' );

/**
 * Plugin paths.
 */
define( 'WP_PARADB_PLUGIN_FILE', __FILE__ );
define( 'WP_PARADB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_PARADB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_PARADB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_wp_paradb() {
	require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activator.php';
	WP_ParaDB_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wp_paradb() {
	require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-deactivator.php';
	WP_ParaDB_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_paradb' );
register_deactivation_hook( __FILE__, 'deactivate_wp_paradb' );

/**
 * The core plugin class.
 */
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb.php';

/**
 * Initialize the plugin.
 *
 * @since    1.0.0
 */
function run_wp_paradb() {
	$plugin = new WP_ParaDB();
	$plugin->run();
}

run_wp_paradb();