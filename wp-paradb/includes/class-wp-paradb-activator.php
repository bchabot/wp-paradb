<?php
/**
 * Fired during plugin activation
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/includes
 * @author            Brian Chabot <bchabot@gmail.com>
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Set up database tables, default options, and any other
	 * initialization tasks required for the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Check WordPress version compatibility.
		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'WordPress Paranormal Database requires WordPress 5.0 or higher.', 'wp-paradb' ) );
		}

		// Check PHP version compatibility.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'WordPress Paranormal Database requires PHP 7.4 or higher.', 'wp-paradb' ) );
		}

		// Set default options.
		self::set_default_options();

		// Create database tables if needed.
		self::create_tables();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Set default plugin options.
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		$default_options = array(
			'version' => WP_PARADB_VERSION,
		);

		add_option( 'wp_paradb_options', $default_options );
	}

	/**
	 * Create database tables.
	 *
	 * @since    1.0.0
	 */
	private static function create_tables() {
		// Database table creation logic would go here.
		// For now, this is a placeholder for future development.
	}
}
