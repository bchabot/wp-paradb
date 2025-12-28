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

		// Activities submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Activities', 'wp-paradb' ),
			__( 'Activities', 'wp-paradb' ),
			'paradb_view_cases',
			'wp-paradb-activities',
			array( __CLASS__, 'activities_page' )
		);

		// Field Logs submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Field Logs', 'wp-paradb' ),
			__( 'Field Logs', 'wp-paradb' ),
			'paradb_view_cases',
			'wp-paradb-logs',
			array( __CLASS__, 'logs_page' )
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

		// Locations submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Locations', 'wp-paradb' ),
			__( 'Locations', 'wp-paradb' ),
			'paradb_view_cases',
			'wp-paradb-locations',
			array( __CLASS__, 'locations_page' )
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
			__( 'ParaDB Settings', 'wp-paradb' ),
			__( 'Settings', 'wp-paradb' ),
			'paradb_manage_settings',
			'wp-paradb-settings',
			array( 'WP_ParaDB_Admin_Settings', 'render_settings_page' )
		);

		// Documentation submenu.
		add_submenu_page(
			'wp-paradb',
			__( 'Documentation', 'wp-paradb' ),
			__( 'Documentation', 'wp-paradb' ),
			'paradb_view_cases',
			'wp-paradb-docs',
			array( __CLASS__, 'docs_page' )
		);

		// Mobile Log Chat (Hidden from menu but accessible via URL).
		add_submenu_page(
			null, // No parent means it's hidden from the menu.
			__( 'Log My Actions', 'wp-paradb' ),
			__( 'Log My Actions', 'wp-paradb' ),
			'paradb_view_cases',
			'wp-paradb-log-chat',
			array( __CLASS__, 'log_chat_page' )
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
	 * Documentation page
	 *
	 * @since    1.0.0
	 */
	public static function docs_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-docs.php';
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
	 * Activities page
	 *
	 * @since    1.0.0
	 */
	public static function activities_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-activities.php';
	}

	/**
	 * Field Logs page
	 *
	 * @since    1.6.0
	 */
	public static function logs_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-logs.php';
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
	 * Locations page
	 *
	 * @since    1.2.0
	 */
	public static function locations_page() {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-locations.php';
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
	 * Log Chat page
	 *
	 * @since    1.6.0
	 */
	public static function log_chat_page() {
		$activity_id = isset( $_GET['activity_id'] ) ? absint( $_GET['activity_id'] ) : 0;
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
		
		$activity = WP_ParaDB_Activity_Handler::get_activity( $activity_id );
		$case_id = $activity ? $activity->case_id : 0;
		$user_id = get_current_user_id();

		if ( ! current_user_can( 'paradb_view_cases' ) && ! WP_ParaDB_Case_Handler::is_user_on_team( $case_id, $user_id ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'wp-paradb' ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-log-chat.php';
	}
}