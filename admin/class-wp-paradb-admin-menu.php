<?php
/**
 * Admin menu and pages
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
 * Handle admin menu registration
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/admin
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Admin_Menu {

	/**
	 * Register admin menus
	 *
	 * @since    1.0.0
	 */
	public static function register_menus() {
		// Main ParaDB menu.
		add_menu_page(
			__( 'ParaDB', 'wp-paradb' ),
			__( 'ParaDB', 'wp-paradb' ),
			'paradb_view_cases',
			'wp-paradb',
			array( __CLASS__, 'dashboard_page' ),
			'dashicons-search',
			30
		);

		// Dashboard submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Dashboard', 'wp-paradb' ),
			__( 'Dashboard', 'wp-paradb' ),
			'paradb_view_cases',
			'wp-paradb',
			array( __CLASS__, 'dashboard_page' )
		);

		// Cases submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Cases', 'wp-paradb' ),
			__( 'Cases', 'wp-paradb' ),
			'paradb_view_cases',
			'wp-paradb-cases',
			array( __CLASS__, 'cases_page' )
		);

		// Add/Edit Case submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Add New Case', 'wp-paradb' ),
			__( 'Add New Case', 'wp-paradb' ),
			'paradb_create_cases',
			'wp-paradb-case-edit',
			array( __CLASS__, 'case_edit_page' )
		);

		// Reports submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Reports', 'wp-paradb' ),
			__( 'Reports', 'wp-paradb' ),
			'paradb_view_cases',
			'wp-paradb-reports',
			array( __CLASS__, 'reports_page' )
		);

		// Clients submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Clients', 'wp-paradb' ),
			__( 'Clients', 'wp-paradb' ),
			'paradb_view_clients',
			'wp-paradb-clients',
			array( __CLASS__, 'clients_page' )
		);

		// Evidence submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Evidence', 'wp-paradb' ),
			__( 'Evidence', 'wp-paradb' ),
			'paradb_view_cases',
			'wp-paradb-evidence',
			array( __CLASS__, 'evidence_page' )
		);

		// Witness Accounts submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Witness Accounts', 'wp-paradb' ),
			__( 'Witness Accounts', 'wp-paradb' ),
			'paradb_manage_witnesses',
			'wp-paradb-witnesses',
			array( __CLASS__, 'witnesses_page' )
		);

		// Taxonomies submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Taxonomies', 'wp-paradb' ),
			__( 'Taxonomies', 'wp-paradb' ),
			'paradb_manage_settings',
			'wp-paradb-taxonomies',
			array( __CLASS__, 'taxonomies_page' )
		);

		// Settings submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Settings', 'wp-paradb' ),
			__( 'Settings', 'wp-paradb' ),
			'paradb_manage_settings',
			'wp-paradb-settings',
			array( __CLASS__, 'settings_page' )
		);
	}

	/**
	 * Dashboard page
	 *
	 * @since    1.0.0
	 */
	public static function dashboard_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-dashboard.php';
	}

	/**
	 * Cases list page
	 *
	 * @since    1.0.0
	 */
	public static function cases_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-cases.php';
	}

	/**
	 * Case edit page
	 *
	 * @since    1.0.0
	 */
	public static function case_edit_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-case-edit.php';
	}

	/**
	 * Reports page
	 *
	 * @since    1.0.0
	 */
	public static function reports_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-reports.php';
	}

	/**
	 * Clients page
	 *
	 * @since    1.0.0
	 */
	public static function clients_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-clients.php';
	}

	/**
	 * Evidence page
	 *
	 * @since    1.0.0
	 */
	public static function evidence_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-evidence.php';
	}

	/**
	 * Witness accounts page
	 *
	 * @since    1.0.0
	 */
	public static function witnesses_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-witnesses.php';
	}


	/**
	 * Taxonomies page
	 *
	 * @since    1.0.0
	 */
	public static function taxonomies_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-taxonomies.php';
	}	

	/**
	 * Settings page
	 *
	 * @since    1.0.0
	 */
	public static function settings_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-settings.php';
	}
}