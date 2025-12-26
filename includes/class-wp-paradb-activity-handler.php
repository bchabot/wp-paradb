<?php
/**
 * Activity management functionality
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
 * Handle activity operations
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Activity_Handler {

	/**
	 * Create a new activity
	 *
	 * @since    1.0.0
	 * @param    array    $data    Activity data.
	 * @return   int|WP_Error      Activity ID on success, WP_Error on failure.
	 */
	public static function create_activity( $data ) {
		global $wpdb;

		// Validate required fields.
		if ( empty( $data['case_id'] ) || empty( $data['activity_title'] ) || empty( $data['activity_content'] ) ) {
			return new WP_Error( 'missing_required_fields', __( 'Case ID, activity title, and content are required.', 'wp-paradb' ) );
		}

		// Prepare activity data.
		$activity_data = array(
			'case_id'            => absint( $data['case_id'] ),
			'activity_title'       => sanitize_text_field( $data['activity_title'] ),
			'activity_type'        => isset( $data['activity_type'] ) ? sanitize_text_field( $data['activity_type'] ) : 'investigation',
			'activity_date'        => isset( $data['activity_date'] ) ? sanitize_text_field( $data['activity_date'] ) : current_time( 'mysql' ),
			'activity_content'     => wp_kses_post( $data['activity_content'] ),
			'activity_summary'     => isset( $data['activity_summary'] ) ? sanitize_textarea_field( $data['activity_summary'] ) : null,
			'investigator_id'    => get_current_user_id(),
			'weather_conditions' => isset( $data['weather_conditions'] ) ? sanitize_text_field( $data['weather_conditions'] ) : null,
			'moon_phase'         => isset( $data['moon_phase'] ) ? sanitize_text_field( $data['moon_phase'] ) : null,
			'temperature'        => isset( $data['temperature'] ) ? sanitize_text_field( $data['temperature'] ) : null,
			'astrological_data'  => isset( $data['astrological_data'] ) ? sanitize_textarea_field( $data['astrological_data'] ) : null,
			'geomagnetic_data'   => isset( $data['geomagnetic_data'] ) ? sanitize_textarea_field( $data['geomagnetic_data'] ) : null,
			'equipment_used'     => isset( $data['equipment_used'] ) ? sanitize_textarea_field( $data['equipment_used'] ) : null,
			'evidence_collected' => isset( $data['evidence_collected'] ) ? sanitize_textarea_field( $data['evidence_collected'] ) : null,
			'phenomena_observed' => isset( $data['phenomena_observed'] ) ? sanitize_textarea_field( $data['phenomena_observed'] ) : null,
			'duration_minutes'   => isset( $data['duration_minutes'] ) ? absint( $data['duration_minutes'] ) : null,
			'participants'       => isset( $data['participants'] ) ? sanitize_textarea_field( $data['participants'] ) : null,
			'is_published'       => isset( $data['is_published'] ) ? absint( $data['is_published'] ) : 0,
			'date_created'       => current_time( 'mysql' ),
		);

		// Format types for database.
		$format = array(
			'%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s',
			'%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s',
		);		// Insert into database.
		$result = $wpdb->insert(
			$wpdb->prefix . 'paradb_activities',
			$activity_data,
			$format
		);

		if ( false === $result ) {
			return new WP_Error( 'db_insert_error', __( 'Failed to create activity.', 'wp-paradb' ) );
		}

		$activity_id = $wpdb->insert_id;

		do_action( 'wp_paradb_activity_created', $activity_id, $activity_data );

		return $activity_id;
	}

	/**
	 * Update an existing activity
	 *
	 * @since    1.0.0
	 * @param    int      $activity_id    Activity ID.
	 * @param    array    $data         Updated activity data.
	 * @return   bool|WP_Error          True on success, WP_Error on failure.
	 */
	public static function update_activity( $activity_id, $data ) {
		global $wpdb;

		$activity_id = absint( $activity_id );

		if ( 0 === $activity_id ) {
			return new WP_Error( 'invalid_activity_id', __( 'Invalid activity ID.', 'wp-paradb' ) );
		}

		// Check if activity exists.
		$activity = self::get_activity( $activity_id );
		if ( ! $activity ) {
			return new WP_Error( 'activity_not_found', __( 'Activity not found.', 'wp-paradb' ) );
		}

		// Prepare update data.
		$update_data = array();
		$format = array();

		$allowed_fields = array(
			'activity_title', 'activity_type', 'activity_date', 'activity_content', 'activity_summary',
			'weather_conditions', 'moon_phase', 'temperature', 'astrological_data', 'geomagnetic_data', 
			'equipment_used', 'evidence_collected', 'phenomena_observed', 'duration_minutes', 'participants', 'is_published',
		);

		foreach ( $allowed_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				if ( in_array( $field, array( 'activity_content' ), true ) ) {
					$update_data[ $field ] = wp_kses_post( $data[ $field ] );
					$format[] = '%s';
				} elseif ( in_array( $field, array( 'activity_summary', 'equipment_used', 'evidence_collected', 'phenomena_observed', 'participants', 'astrological_data', 'geomagnetic_data' ), true ) ) {
					$update_data[ $field ] = sanitize_textarea_field( $data[ $field ] );
					$format[] = '%s';
				} elseif ( in_array( $field, array( 'duration_minutes', 'is_published' ), true ) ) {
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
			$wpdb->prefix . 'paradb_activities',
			$update_data,
			array( 'activity_id' => $activity_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_update_error', __( 'Failed to update activity.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_activity_updated', $activity_id, $update_data );

		return true;
	}

	/**
	 * Get a activity by ID
	 *
	 * @since    1.0.0
	 * @param    int    $activity_id    Activity ID.
	 * @return   object|null          Activity object or null if not found.
	 */
	public static function get_activity( $activity_id ) {
		global $wpdb;

		$activity_id = absint( $activity_id );

		if ( 0 === $activity_id ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}paradb_activities WHERE activity_id = %d",
			$activity_id
		) );
	}

	/**
	 * Get activities with filters
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Array of activity objects.
	 */
	public static function get_activities( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'case_id'         => 0,
			'investigator_id' => 0,
			'activity_type'     => '',
			'search'          => '',
			'orderby'         => 'activity_date',
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

		// Filter by investigator.
		if ( $args['investigator_id'] > 0 ) {
			$where[] = 'investigator_id = %d';
			$where_values[] = $args['investigator_id'];
		}

		// Filter by activity type.
		if ( ! empty( $args['activity_type'] ) ) {
			$where[] = 'activity_type = %s';
			$where_values[] = $args['activity_type'];
		}

		// Search.
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(activity_title LIKE %s OR activity_content LIKE %s OR activity_summary LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = implode( ' AND ', $where );

		// Build query.
		$query = "SELECT * FROM {$wpdb->prefix}paradb_activities WHERE {$where_clause}";

		// Add ordering.
		$allowed_orderby = array( 'activity_id', 'activity_title', 'activity_date', 'date_created', 'activity_type' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'activity_date';
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
	 * Delete a activity
	 *
	 * @since    1.0.0
	 * @param    int    $activity_id    Activity ID.
	 * @return   bool|WP_Error        True on success, WP_Error on failure.
	 */
	public static function delete_activity( $activity_id ) {
		global $wpdb;

		$activity_id = absint( $activity_id );

		if ( 0 === $activity_id ) {
			return new WP_Error( 'invalid_activity_id', __( 'Invalid activity ID.', 'wp-paradb' ) );
		}

		// Delete related evidence references.
		$wpdb->update(
			$wpdb->prefix . 'paradb_evidence',
			array( 'activity_id' => null ),
			array( 'activity_id' => $activity_id ),
			array( '%d' ),
			array( '%d' )
		);

		// Delete activity.
		$result = $wpdb->delete(
			$wpdb->prefix . 'paradb_activities',
			array( 'activity_id' => $activity_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_delete_error', __( 'Failed to delete activity.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_activity_deleted', $activity_id );

		return true;
	}

	/**
	 * Get activity count for a case
	 *
	 * @since    1.0.0
	 * @param    int    $case_id    Case ID.
	 * @return   int                Activity count.
	 */
	public static function get_case_activity_count( $case_id ) {
		global $wpdb;

		return absint( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}paradb_activities WHERE case_id = %d",
			absint( $case_id )
		) ) );
	}
}
