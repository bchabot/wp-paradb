<?php
/**
 * Data maintenance and backup functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.5.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle data backup, restore, and maintenance
 *
 * @since      1.5.0
 */
class WP_ParaDB_Maintenance_Handler {

	/**
	 * Export all plugin data to JSON
	 *
	 * @since    1.5.0
	 */
	public static function export_data() {
		global $wpdb;

		$tables = array(
			'cases',
			'reports',
			'activities',
			'clients',
			'evidence',
			'case_notes',
			'case_team',
			'locations',
			'relationships',
			'field_logs',
			'witness_accounts',
		);

		$data = array(
			'version' => WP_PARADB_VERSION,
			'exported_at' => current_time( 'mysql' ),
			'settings' => get_option( 'wp_paradb_settings', array() ),
			'options' => get_option( 'wp_paradb_options', array() ),
			'taxonomies' => get_option( 'wp_paradb_taxonomies', array() ),
			'tables' => array(),
		);

		foreach ( $tables as $table ) {
			$table_name = $wpdb->prefix . 'paradb_' . $table;
			$data['tables'][$table] = $wpdb->get_results( "SELECT * FROM {$table_name}", ARRAY_A );
		}

		return wp_json_encode( $data );
	}

	/**
	 * Import plugin data from JSON
	 *
	 * @since    1.5.0
	 */
	public static function import_data( $json_data ) {
		global $wpdb;

		$data = json_decode( $json_data, true );
		if ( ! $data || ! isset( $data['tables'] ) ) {
			return new WP_Error( 'invalid_data', __( 'Invalid backup data format.', 'wp-paradb' ) );
		}

		// Restore Settings/Options
		if ( isset( $data['settings'] ) ) update_option( 'wp_paradb_settings', $data['settings'] );
		if ( isset( $data['options'] ) ) update_option( 'wp_paradb_options', $data['options'] );
		if ( isset( $data['taxonomies'] ) ) update_option( 'wp_paradb_taxonomies', $data['taxonomies'] );

		// Restore Tables
		foreach ( $data['tables'] as $table => $rows ) {
			$table_name = $wpdb->prefix . 'paradb_' . $table;
			
			// Clear existing table data
			$wpdb->query( "TRUNCATE TABLE {$table_name}" );

			if ( empty( $rows ) ) continue;

			foreach ( $rows as $row ) {
				$wpdb->insert( $table_name, $row );
			}
		}

		return true;
	}

	/**
	 * Reset all data and settings to factory defaults
	 *
	 * @since    1.5.0
	 */
	public static function reset_all() {
		// Drop and recreate tables
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-database.php';
		WP_ParaDB_Database::drop_tables();
		WP_ParaDB_Database::create_tables();

		// Reset settings and options
		delete_option( 'wp_paradb_settings' );
		delete_option( 'wp_paradb_options' );
		delete_option( 'wp_paradb_taxonomies' );

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activator.php';
		// Activator will set default options and taxonomies
		WP_ParaDB_Activator::activate();

		return true;
	}
}
