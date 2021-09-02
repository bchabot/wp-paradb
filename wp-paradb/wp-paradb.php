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
 * @since             0.0.0
 * @package           wp-paradb
 *
 * @wordpress-plugin
 * Plugin Name:       WordPress Paranormal Database
 * Plugin URI:        https://github.com/bchabot/wp-paradb
 * Description:       Plugin to add reporting, archiving, and search functionality for paranormal investigation
 * Version:           0.0.0
 * Author:            Brian Chabot <bchabot@gmail.com>
 * Author URI:        https://brianchabot.org/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       wpparadb
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGIN_NAME_VERSION', '0.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_wpparadb() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpparadb-activator.php';
	wpparadb_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_wpparadb() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpparadb-deactivator.php';
	wpparadb_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpparadb' );
register_deactivation_hook( __FILE__, 'deactivate_wpparadb' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpparadb.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.0.0
 */
function run_wp_paradb() {

	$plugin = new wpparadb();
	$plugin->run();

}
run_wpparadb();
