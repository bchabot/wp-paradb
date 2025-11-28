<?php
/**
 * Fired during plugin deactivation
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
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Clean up temporary data and flush rewrite rules.
	 * Note: This does not remove user data - that's handled in uninstall.php
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear any cached data.
		wp_cache_flush();

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Clear any scheduled events.
		wp_clear_scheduled_hook( 'wp_paradb_daily_cleanup' );
	}
}
