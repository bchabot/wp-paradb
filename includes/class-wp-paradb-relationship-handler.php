<?php
/**
 * Relationship management functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.3.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/includes
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle object relationship operations
 *
 * @since      1.3.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Relationship_Handler {

	/**
	 * Create a new relationship
	 *
	 * @since    1.3.0
	 */
	public static function create_relationship( $data ) {
		global $wpdb;

		if ( empty( $data['from_id'] ) || empty( $data['from_type'] ) || empty( $data['to_id'] ) || empty( $data['to_type'] ) || empty( $data['relationship_type'] ) ) {
			return new WP_Error( 'missing_fields', __( 'Missing required fields for relationship.', 'wp-paradb' ) );
		}

		$relationship_data = array(
			'from_id'           => absint( $data['from_id'] ),
			'from_type'         => sanitize_text_field( $data['from_type'] ),
			'to_id'             => absint( $data['to_id'] ),
			'to_type'           => sanitize_text_field( $data['to_type'] ),
			'relationship_type' => sanitize_text_field( $data['relationship_type'] ),
			'notes'             => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : null,
			'created_by'        => get_current_user_id(),
			'date_created'      => current_time( 'mysql' ),
		);

		$result = $wpdb->insert(
			$wpdb->prefix . 'paradb_relationships',
			$relationship_data,
			array( '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_insert_error', __( 'Failed to create relationship.', 'wp-paradb' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get relationships for an object
	 *
	 * @since    1.3.0
	 */
	public static function get_relationships( $object_id, $object_type ) {
		global $wpdb;

		$object_id = absint( $object_id );
		$object_type = sanitize_text_field( $object_type );

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}relationships 
			 WHERE (from_id = %d AND from_type = %s) 
			 OR (to_id = %d AND to_type = %s)
			 ORDER BY date_created DESC",
			$object_id, $object_type, $object_id, $object_type
		) );
	}

	/**
	 * Delete a relationship
	 *
	 * @since    1.3.0
	 */
	public static function delete_relationship( $relationship_id ) {
		global $wpdb;
		return $wpdb->delete(
			$wpdb->prefix . 'paradb_relationships',
			array( 'relationship_id' => absint( $relationship_id ) ),
			array( '%d' )
		);
	}

	/**
	 * Get human-readable object label
	 *
	 * @since    1.3.0
	 */
	public static function get_object_label( $id, $type ) {
		$id = absint( $id );
		switch ( $type ) {
			case 'case':
				require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
				$case = WP_ParaDB_Case_Handler::get_case( $id );
				return $case ? $case->case_number . ' - ' . $case->case_name : __( 'Unknown Case', 'wp-paradb' );
			case 'activity':
				require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';
				$activity = WP_ParaDB_Activity_Handler::get_activity( $id );
				return $activity ? $activity->activity_title : __( 'Unknown Activity', 'wp-paradb' );
			case 'report':
				require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-report-handler.php';
				$report = WP_ParaDB_Report_Handler::get_report( $id );
				return $report ? $report->report_title : __( 'Unknown Report', 'wp-paradb' );
			case 'location':
				require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-location-handler.php';
				$loc = WP_ParaDB_Location_Handler::get_location( $id );
				return $loc ? $loc->location_name : __( 'Unknown Location', 'wp-paradb' );
			case 'witness':
				require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-witness-handler.php';
				$witness = WP_ParaDB_Witness_Handler::get_witness_account( $id );
				return $witness ? ( $witness->account_name ? $witness->account_name : __( 'Anonymous Witness', 'wp-paradb' ) ) : __( 'Unknown Witness', 'wp-paradb' );
			case 'evidence':
				require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-evidence-handler.php';
				$evidence = WP_ParaDB_Evidence_Handler::get_evidence( $id );
				return $evidence ? ( $evidence->title ? $evidence->title : $evidence->file_name ) : __( 'Unknown Evidence', 'wp-paradb' );
		}
		return $type . ' #' . $id;
	}
}
