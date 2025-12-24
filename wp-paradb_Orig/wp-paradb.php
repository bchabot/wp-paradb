<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 *
 * @wordpress-plugin
 * Plugin Name:       WordPress Paranormal Database
 * Plugin URI:        https://github.com/bchabot/wp-paradb
 * Description:       A standardized, easy-to-use way of recording, archiving, and sharing paranormal witness reports, recorded anomalies, experiments, and investigations.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Brian Chabot
 * Author URI:        https://brianchabot.org/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wp-paradb
 * Domain Path:       /languages
 * Network:           false
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'WP_PARADB_VERSION', '1.0.0' );

/**
 * Plugin file path.
 */
define( 'WP_PARADB_PLUGIN_FILE', __FILE__ );

/**
 * Plugin directory path.
 */
define( 'WP_PARADB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'WP_PARADB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'WP_PARADB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-paradb-activator.php
 */
function activate_wp_paradb() {
	require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activator.php';
	WP_ParaDB_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-paradb-deactivator.php
 */
function deactivate_wp_paradb() {
	require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-deactivator.php';
	WP_ParaDB_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_paradb' );
register_deactivation_hook( __FILE__, 'deactivate_wp_paradb' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_paradb() {
	$plugin = new WP_ParaDB();
	$plugin->run();
}

// Initialize the plugin.
run_wp_paradb();
