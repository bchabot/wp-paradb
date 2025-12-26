<?php
/**
 * Report management functionality
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
 * Handle report operations
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Report_Handler {

	/**
	 * Create a new report
	 *
	 * @since    1.0.0
	 * @param    array    $data    Report data.
	 * @return   int|WP_Error      Report ID on success, WP_Error on failure.
	 */
	public static function create_report( $data ) {
		global $wpdb;

		// Validate required fields.
		if ( empty( $data['case_id'] ) || empty( $data['report_title'] ) || empty( $data['report_content'] ) ) {
			return new WP_Error( 'missing_required_fields', __( 'Case ID, report title, and content are required.', 'wp-paradb' ) );
		}

		// Prepare report data.
		$report_data = array(
			'case_id'            => absint( $data['case_id'] ),
			'activity_id'        => isset( $data['activity_id'] ) ? absint( $data['activity_id'] ) : null,
			'report_title'       => sanitize_text_field( $data['report_title'] ),
			'report_type'        => isset( $data['report_type'] ) ? sanitize_text_field( $data['report_type'] ) : 'report',
			'report_date'        => isset( $data['report_date'] ) ? sanitize_text_field( $data['report_date'] ) : current_time( 'mysql' ),
			                        'report_content'     => wp_kses_post( $data['report_content'] ),
			                        'report_summary'     => isset( $data['report_summary'] ) ? sanitize_textarea_field( $data['report_summary'] ) : null,
			                        'is_published'       => isset( $data['is_published'] ) ? absint( $data['is_published'] ) : 0,
			                        'investigator_id'    => get_current_user_id(),
			                        'date_created'       => current_time( 'mysql' ),
			                );
			
			                // Format types for database.
			                $format = array(
			                        '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s',
			                );
		// Insert into database.
		$result = $wpdb->insert(
			$wpdb->prefix . 'paradb_reports',
			$report_data,
			$format
		);

		if ( false === $result ) {
			return new WP_Error( 'db_insert_error', __( 'Failed to create report.', 'wp-paradb' ) );
		}

		$report_id = $wpdb->insert_id;

		do_action( 'wp_paradb_report_created', $report_id, $report_data );

		return $report_id;
	}

	/**
	 * Update an existing report
	 *
	 * @since    1.0.0
	 * @param    int      $report_id    Report ID.
	 * @param    array    $data         Updated report data.
	 * @return   bool|WP_Error          True on success, WP_Error on failure.
	 */
	public static function update_report( $report_id, $data ) {
		global $wpdb;

		$report_id = absint( $report_id );

		if ( 0 === $report_id ) {
			return new WP_Error( 'invalid_report_id', __( 'Invalid report ID.', 'wp-paradb' ) );
		}

		// Check if report exists.
		$report = self::get_report( $report_id );
		if ( ! $report ) {
			return new WP_Error( 'report_not_found', __( 'Report not found.', 'wp-paradb' ) );
		}

		// Prepare update data.
		$update_data = array();
		$format = array();

		                $allowed_fields = array(
		                        'activity_id', 'report_title', 'report_type', 'report_date', 'report_content', 'report_summary', 'is_published',
		                );
		
		                foreach ( $allowed_fields as $field ) {
		                        if ( isset( $data[ $field ] ) ) {
		                                if ( in_array( $field, array( 'report_content' ), true ) ) {
		                                        $update_data[ $field ] = wp_kses_post( $data[ $field ] );
		                                        $format[] = '%s';
		                                } elseif ( 'report_summary' === $field ) {
		                                        $update_data[ $field ] = sanitize_textarea_field( $data[ $field ] );
		                                        $format[] = '%s';
		                                } elseif ( in_array( $field, array( 'activity_id', 'is_published' ), true ) ) {
		                                        $update_data[ $field ] = absint( $data[ $field ] );
		                                        $format[] = '%d';
		                                } else {
		                                        $update_data[ $field ] = sanitize_text_field( $data[ $field ] );
		                                        $format[] = '%s';
		                                }
		                        }
		                }
		$update_data['date_modified'] = current_time( 'mysql' );
		$format[] = '%s';

		// Update database.
		$result = $wpdb->update(
			$wpdb->prefix . 'paradb_reports',
			$update_data,
			array( 'report_id' => $report_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_update_error', __( 'Failed to update report.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_report_updated', $report_id, $update_data );

		return true;
	}

	/**
	 * Get a report by ID
	 *
	 * @since    1.0.0
	 * @param    int    $report_id    Report ID.
	 * @return   object|null          Report object or null if not found.
	 */
	public static function get_report( $report_id ) {
		global $wpdb;

		$report_id = absint( $report_id );

		if ( 0 === $report_id ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}paradb_reports WHERE report_id = %d",
			$report_id
		) );
	}

	/**
	 * Get reports with filters
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Array of report objects.
	 */
	public static function get_reports( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'case_id'         => 0,
			'activity_id'     => 0,
			'investigator_id' => 0,
			'report_type'     => '',
			'search'          => '',
			'orderby'         => 'report_date',
			'order'           => 'DESC',
			'limit'           => 20,
			'offset'          => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		// Filter by case.
		if ( $args['case_id'] > 0 ) {
			$where[] = 'case_id = %d';
			$where_values[] = $args['case_id'];
		}

		// Filter by activity.
		if ( $args['activity_id'] > 0 ) {
			$where[] = 'activity_id = %d';
			$where_values[] = $args['activity_id'];
		}

		// Filter by investigator.
		if ( $args['investigator_id'] > 0 ) {
			$where[] = 'investigator_id = %d';
			$where_values[] = $args['investigator_id'];
		}

		// Filter by report type.
		if ( ! empty( $args['report_type'] ) ) {
			$where[] = 'report_type = %s';
			$where_values[] = $args['report_type'];
		}

		// Search.
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(report_title LIKE %s OR report_content LIKE %s OR report_summary LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = implode( ' AND ', $where );

		// Build query.
		$query = "SELECT * FROM {$wpdb->prefix}paradb_reports WHERE {$where_clause}";

		// Add ordering.
		$allowed_orderby = array( 'report_id', 'report_title', 'report_date', 'date_created', 'report_type' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'report_date';
		$order = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$query .= " ORDER BY {$orderby} {$order}";

		// Add limit.
		$query .= " LIMIT %d OFFSET %d";
		$where_values[] = absint( $args['limit'] );
		$where_values[] = absint( $args['offset'] );

		// Prepare and execute query.
		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Delete a report
	 *
	 * @since    1.0.0
	 * @param    int    $report_id    Report ID.
	 * @return   bool|WP_Error        True on success, WP_Error on failure.
	 */
	public static function delete_report( $report_id ) {
		global $wpdb;

		$report_id = absint( $report_id );

		if ( 0 === $report_id ) {
			return new WP_Error( 'invalid_report_id', __( 'Invalid report ID.', 'wp-paradb' ) );
		}

		// Delete related evidence references.
		$wpdb->update(
			$wpdb->prefix . 'paradb_evidence',
			array( 'report_id' => null ),
			array( 'report_id' => $report_id ),
			array( '%d' ),
			array( '%d' )
		);

		// Delete report.
		$result = $wpdb->delete(
			$wpdb->prefix . 'paradb_reports',
			array( 'report_id' => $report_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_delete_error', __( 'Failed to delete report.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_report_deleted', $report_id );

		return true;
	}

	/**
	 * Get report count for a case
	 *
	 * @since    1.0.0
	 * @param    int    $case_id    Case ID.
	 * @return   int                Report count.
	 */
	public static function get_case_report_count( $case_id ) {
		global $wpdb;

		return absint( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}paradb_reports WHERE case_id = %d",
			absint( $case_id )
		) ) );
	}
}