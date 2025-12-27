<?php
/**
 * Admin settings functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/admin
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle admin settings page
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/admin
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Admin_Settings {

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// We don't register the menu here anymore, it's done in WP_ParaDB_Admin_Menu
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting( 'wp_paradb_settings', WP_ParaDB_Settings::OPTION_NAME );
	}

	/**
	 * Render settings page
	 *
	 * @since    1.0.0
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'paradb_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-paradb' ) );
		}

		// The actual content is in the partial
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-settings.php';
	}
}

// Initialize settings logic
new WP_ParaDB_Admin_Settings();