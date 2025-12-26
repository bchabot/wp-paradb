<?php
/**
 * Field log management functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.4.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle field log operations
 *
 * @since      1.4.0
 */
class WP_ParaDB_Field_Log_Handler {

	/**
	 * Create a new field log entry
	 *
	 * @since    1.4.0
	 */
	public static function create_log( $data ) {
		global $wpdb;

		if ( empty( $data['case_id'] ) || empty( $data['log_content'] ) ) {
			return new WP_Error( 'missing_fields', __( 'Case ID and log content are required.', 'wp-paradb' ) );
		}

		$log_data = array(
			'case_id'         => absint( $data['case_id'] ),
			'activity_id'     => isset( $data['activity_id'] ) ? absint( $data['activity_id'] ) : null,
			'investigator_id' => get_current_user_id(),
			'log_content'     => wp_kses_post( $data['log_content'] ),
			'latitude'        => isset( $data['latitude'] ) ? floatval( $data['latitude'] ) : null,
			'longitude'       => isset( $data['longitude'] ) ? floatval( $data['longitude'] ) : null,
			'file_url'        => isset( $data['file_url'] ) ? esc_url_raw( $data['file_url'] ) : null,
			'date_created'    => current_time( 'mysql' ),
		);

		$result = $wpdb->insert(
			$wpdb->prefix . 'paradb_field_logs',
			$log_data,
			array( '%d', '%d', '%d', '%s', '%f', '%f', '%s', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_insert_error', __( 'Failed to create log entry.', 'wp-paradb' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get logs for a case or activity
	 *
	 * @since    1.4.0
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'case_id'     => 0,
			'activity_id' => 0,
			'limit'       => 100,
			'offset'      => 0,
			'order'       => 'DESC', // Requirement says alphabetical, but logs are usually chronological. I'll provide order param.
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		if ( $args['case_id'] > 0 ) {
			$where[] = 'case_id = %d';
			$where_values[] = $args['case_id'];
		}

		if ( $args['activity_id'] > 0 ) {
			$where[] = 'activity_id = %d';
			$where_values[] = $args['activity_id'];
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT * FROM {$wpdb->prefix}paradb_field_logs WHERE {$where_clause} ORDER BY date_created {$args['order']} LIMIT %d OFFSET %d";
		$where_values[] = $args['limit'];
		$where_values[] = $args['offset'];

		return $wpdb->get_results( $wpdb->prepare( $query, $where_values ) );
	}
}
