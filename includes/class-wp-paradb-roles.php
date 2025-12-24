<?php
/**
 * User roles and capabilities management
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/includes
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle user roles and capabilities
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Roles {

	/**
	 * Create custom roles and capabilities
	 *
	 * @since    1.0.0
	 */
	public static function create_roles() {
		// Remove roles first to ensure clean setup
		self::remove_roles();

		// Get standard capabilities
		$read_caps = array(
			'read' => true,
		);

		// Investigator capabilities - Can view and work on assigned cases
		$investigator_caps = array_merge(
			$read_caps,
			array(
				'paradb_view_cases'        => true,
				'paradb_view_own_cases'    => true,
				'paradb_edit_own_cases'    => true,
				'paradb_add_reports'       => true,
				'paradb_edit_own_reports'  => true,
				'paradb_view_clients'      => true,
				'paradb_upload_evidence'   => true,
				'paradb_add_notes'         => true,
			)
		);

		// Team Leader capabilities - Can manage cases and team members
		$team_leader_caps = array_merge(
			$investigator_caps,
			array(
				'paradb_create_cases'      => true,
				'paradb_edit_cases'        => true,
				'paradb_delete_own_cases'  => true,
				'paradb_assign_cases'      => true,
				'paradb_manage_team'       => true,
				'paradb_edit_reports'      => true,
				'paradb_delete_own_reports' => true,
				'paradb_add_clients'       => true,
				'paradb_edit_clients'      => true,
				'paradb_manage_evidence'   => true,
				'paradb_publish_cases'     => true,
			)
		);

		// Director capabilities - Full administrative control
		$director_caps = array_merge(
			$team_leader_caps,
			array(
				'paradb_delete_cases'      => true,
				'paradb_delete_reports'    => true,
				'paradb_delete_clients'    => true,
				'paradb_delete_evidence'   => true,
				'paradb_manage_settings'   => true,
				'paradb_view_all_cases'    => true,
				'paradb_export_data'       => true,
				'paradb_manage_witnesses'  => true,
			)
		);

		// Create roles
		add_role(
			'paradb_investigator',
			__( 'ParaDB Investigator', 'wp-paradb' ),
			$investigator_caps
		);

		add_role(
			'paradb_team_leader',
			__( 'ParaDB Team Leader', 'wp-paradb' ),
			$team_leader_caps
		);

		add_role(
			'paradb_director',
			__( 'ParaDB Director', 'wp-paradb' ),
			$director_caps
		);

		// Add capabilities to administrator role
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( $director_caps as $cap => $grant ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove custom roles
	 *
	 * @since    1.0.0
	 */
	public static function remove_roles() {
		remove_role( 'paradb_investigator' );
		remove_role( 'paradb_team_leader' );
		remove_role( 'paradb_director' );

		// Remove capabilities from administrator
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$caps = array(
				'paradb_view_cases',
				'paradb_view_own_cases',
				'paradb_edit_own_cases',
				'paradb_create_cases',
				'paradb_edit_cases',
				'paradb_delete_own_cases',
				'paradb_delete_cases',
				'paradb_assign_cases',
				'paradb_view_all_cases',
				'paradb_add_reports',
				'paradb_edit_own_reports',
				'paradb_edit_reports',
				'paradb_delete_own_reports',
				'paradb_delete_reports',
				'paradb_view_clients',
				'paradb_add_clients',
				'paradb_edit_clients',
				'paradb_delete_clients',
				'paradb_upload_evidence',
				'paradb_manage_evidence',
				'paradb_delete_evidence',
				'paradb_add_notes',
				'paradb_manage_team',
				'paradb_publish_cases',
				'paradb_manage_settings',
				'paradb_export_data',
				'paradb_manage_witnesses',
			);

			foreach ( $caps as $cap ) {
				$admin->remove_cap( $cap );
			}
		}
	}

	/**
	 * Check if user has specific capability
	 *
	 * @since    1.0.0
	 * @param    string    $capability    Capability to check.
	 * @param    int       $user_id       User ID (defaults to current user).
	 * @return   bool                     True if user has capability.
	 */
	public static function user_can( $capability, $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( 0 === $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		return $user->has_cap( $capability );
	}

	/**
	 * Check if user can access a specific case
	 *
	 * @since    1.0.0
	 * @param    int    $case_id    Case ID.
	 * @param    int    $user_id    User ID (defaults to current user).
	 * @return   bool               True if user can access the case.
	 */
	public static function user_can_access_case( $case_id, $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( 0 === $user_id ) {
			return false;
		}

		// Administrators and directors can access all cases
		if ( self::user_can( 'paradb_view_all_cases', $user_id ) ) {
			return true;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'paradb_cases';

		// Check if user created the case
		$created_by = $wpdb->get_var( $wpdb->prepare(
			"SELECT created_by FROM {$table} WHERE case_id = %d",
			$case_id
		) );

		if ( absint( $created_by ) === absint( $user_id ) ) {
			return true;
		}

		// Check if user is assigned to the case
		$team_table = $wpdb->prefix . 'paradb_case_team';
		$assigned = $wpdb->get_var( $wpdb->prepare(
			"SELECT assignment_id FROM {$team_table} WHERE case_id = %d AND user_id = %d",
			$case_id,
			$user_id
		) );

		return ! is_null( $assigned );
	}

	/**
	 * Get users by role
	 *
	 * @since    1.0.0
	 * @param    string    $role    Role name.
	 * @return   array              Array of user objects.
	 */
	public static function get_users_by_role( $role ) {
		return get_users( array(
			'role' => $role,
		) );
	}

	/**
	 * Get all ParaDB users
	 *
	 * @since    1.0.0
	 * @return   array    Array of user objects.
	 */
	public static function get_all_paradb_users() {
		return get_users( array(
			'role__in' => array(
				'paradb_investigator',
				'paradb_team_leader',
				'paradb_director',
				'administrator',
			),
		) );
	}
}