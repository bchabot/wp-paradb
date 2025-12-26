<?php
/**
 * Case management functionality
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
 * Handle case operations
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Case_Handler {

	/**
	 * Create a new case
	 *
	 * @since    1.0.0
	 * @param    array    $data    Case data.
	 * @return   int|WP_Error      Case ID on success, WP_Error on failure.
	 */
	public static function create_case( $data ) {
		global $wpdb;

		// Validate required fields.
		if ( empty( $data['case_name'] ) ) {
			return new WP_Error( 'missing_case_name', __( 'Case name is required.', 'wp-paradb' ) );
		}

		// Generate case number.
		$case_number = self::generate_case_number();

		// Prepare case data.
		$case_data = array(
			'case_number'        => $case_number,
			'case_name'          => sanitize_text_field( $data['case_name'] ),
			'case_status'        => isset( $data['case_status'] ) ? sanitize_text_field( $data['case_status'] ) : 'open',
			'case_type'          => isset( $data['case_type'] ) ? sanitize_text_field( $data['case_type'] ) : 'investigation',
			'client_id'          => isset( $data['client_id'] ) ? absint( $data['client_id'] ) : null,
			'location_name'      => isset( $data['location_name'] ) ? sanitize_text_field( $data['location_name'] ) : null,
			'location_address'   => isset( $data['location_address'] ) ? sanitize_text_field( $data['location_address'] ) : null,
			'location_city'      => isset( $data['location_city'] ) ? sanitize_text_field( $data['location_city'] ) : null,
			'location_state'     => isset( $data['location_state'] ) ? sanitize_text_field( $data['location_state'] ) : null,
			'location_zip'       => isset( $data['location_zip'] ) ? sanitize_text_field( $data['location_zip'] ) : null,
			'location_country'   => isset( $data['location_country'] ) ? sanitize_text_field( $data['location_country'] ) : 'United States',
			'latitude'           => isset( $data['latitude'] ) ? floatval( $data['latitude'] ) : null,
			'longitude'          => isset( $data['longitude'] ) ? floatval( $data['longitude'] ) : null,
			'case_description'   => isset( $data['case_description'] ) ? wp_kses_post( $data['case_description'] ) : null,
			'phenomena_types'    => isset( $data['phenomena_types'] ) ? maybe_serialize( $data['phenomena_types'] ) : null,
			'case_priority'      => isset( $data['case_priority'] ) ? sanitize_text_field( $data['case_priority'] ) : 'normal',
			'created_by'         => get_current_user_id(),
			'assigned_to'        => isset( $data['assigned_to'] ) ? absint( $data['assigned_to'] ) : null,
			'date_created'       => current_time( 'mysql' ),
		);

		// Format types for database.
		$format = array(
			'%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s',
			'%f', '%f', '%s', '%s', '%s', '%d', '%d', '%s',
		);

		// Insert into database.
		$result = $wpdb->insert(
			$wpdb->prefix . 'paradb_cases',
			$case_data,
			$format
		);

		if ( false === $result ) {
			return new WP_Error( 'db_insert_error', __( 'Failed to create case.', 'wp-paradb' ) );
		}

		$case_id = $wpdb->insert_id;

		// Assign creator to case team.
		self::assign_team_member( $case_id, get_current_user_id(), 'lead' );

		do_action( 'wp_paradb_case_created', $case_id, $case_data );

		return $case_id;
	}

	/**
	 * Update an existing case
	 *
	 * @since    1.0.0
	 * @param    int      $case_id    Case ID.
	 * @param    array    $data       Updated case data.
	 * @return   bool|WP_Error        True on success, WP_Error on failure.
	 */
	public static function update_case( $case_id, $data ) {
		global $wpdb;

		$case_id = absint( $case_id );

		if ( 0 === $case_id ) {
			return new WP_Error( 'invalid_case_id', __( 'Invalid case ID.', 'wp-paradb' ) );
		}

		// Check if case exists.
		$case = self::get_case( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'case_not_found', __( 'Case not found.', 'wp-paradb' ) );
		}

		// Prepare update data.
		$update_data = array();
		$format = array();

		$allowed_fields = array(
			'case_name', 'case_status', 'case_type', 'client_id', 'location_name',
			'location_address', 'location_city', 'location_state', 'location_zip',
			'location_country', 'latitude', 'longitude', 'case_description',
			'phenomena_types', 'case_priority', 'assigned_to',
		);

		foreach ( $allowed_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				if ( in_array( $field, array( 'case_description' ), true ) ) {
					$update_data[ $field ] = wp_kses_post( $data[ $field ] );
					$format[] = '%s';
				} elseif ( in_array( $field, array( 'client_id', 'assigned_to' ), true ) ) {
					$update_data[ $field ] = absint( $data[ $field ] );
					$format[] = '%d';
				} elseif ( in_array( $field, array( 'latitude', 'longitude' ), true ) ) {
					$update_data[ $field ] = floatval( $data[ $field ] );
					$format[] = '%f';
				} elseif ( 'phenomena_types' === $field ) {
					$update_data[ $field ] = maybe_serialize( $data[ $field ] );
					$format[] = '%s';
				} else {
					$update_data[ $field ] = sanitize_text_field( $data[ $field ] );
					$format[] = '%s';
				}
			}
		}

		// Handle case closure.
		if ( isset( $data['case_status'] ) && 'closed' === $data['case_status'] && 'closed' !== $case->case_status ) {
			$update_data['date_closed'] = current_time( 'mysql' );
			$format[] = '%s';
		}

		$update_data['date_modified'] = current_time( 'mysql' );
		$format[] = '%s';

		// Update database.
		$result = $wpdb->update(
			$wpdb->prefix . 'paradb_cases',
			$update_data,
			array( 'case_id' => $case_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_update_error', __( 'Failed to update case.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_case_updated', $case_id, $update_data );

		return true;
	}

	/**
	 * Get a case by ID
	 *
	 * @since    1.0.0
	 * @param    int    $case_id    Case ID.
	 * @return   object|null        Case object or null if not found.
	 */
	public static function get_case( $case_id ) {
		global $wpdb;

		$case_id = absint( $case_id );

		if ( 0 === $case_id ) {
			return null;
		}

		$case = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}paradb_cases WHERE case_id = %d",
			$case_id
		) );

		if ( $case ) {
			$case->phenomena_types = maybe_unserialize( $case->phenomena_types );
		}

		return $case;
	}

	/**
	 * Get cases with filters
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Array of case objects.
	 */
	public static function get_cases( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'      => '',
			'assigned_to' => 0,
			'created_by'  => 0,
			'search'      => '',
			'orderby'     => 'date_created',
			'order'       => 'DESC',
			'limit'       => 20,
			'offset'      => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		// Filter by status.
		if ( ! empty( $args['status'] ) ) {
			$where[] = 'case_status = %s';
			$where_values[] = $args['status'];
		}

		// Filter by assigned user.
		if ( $args['assigned_to'] > 0 ) {
			$where[] = 'assigned_to = %d';
			$where_values[] = $args['assigned_to'];
		}

		// Filter by creator.
		if ( $args['created_by'] > 0 ) {
			$where[] = 'created_by = %d';
			$where_values[] = $args['created_by'];
		}

		// Search.
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(case_name LIKE %s OR case_number LIKE %s OR case_description LIKE %s OR location_name LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = implode( ' AND ', $where );

		// Build query.
		$query = "SELECT * FROM {$wpdb->prefix}paradb_cases WHERE {$where_clause}";

		// Add ordering.
		$allowed_orderby = array( 'case_id', 'case_number', 'case_name', 'date_created', 'date_modified', 'case_status' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'date_created';
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

		$cases = $wpdb->get_results( $query );

		// Unserialize phenomena types.
		foreach ( $cases as $case ) {
			$case->phenomena_types = maybe_unserialize( $case->phenomena_types );
		}

		return $cases;
	}

	/**
	 * Delete a case
	 *
	 * @since    1.0.0
	 * @param    int    $case_id    Case ID.
	 * @return   bool|WP_Error      True on success, WP_Error on failure.
	 */
	public static function delete_case( $case_id ) {
		global $wpdb;

		$case_id = absint( $case_id );

		if ( 0 === $case_id ) {
			return new WP_Error( 'invalid_case_id', __( 'Invalid case ID.', 'wp-paradb' ) );
		}

		// Delete related records.
		$wpdb->delete( $wpdb->prefix . 'paradb_case_team', array( 'case_id' => $case_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'paradb_case_notes', array( 'case_id' => $case_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'paradb_reports', array( 'case_id' => $case_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'paradb_activities', array( 'case_id' => $case_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'paradb_evidence', array( 'case_id' => $case_id ), array( '%d' ) );

		// Delete case.
		$result = $wpdb->delete(
			$wpdb->prefix . 'paradb_cases',
			array( 'case_id' => $case_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_delete_error', __( 'Failed to delete case.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_case_deleted', $case_id );

		return true;
	}

	/**
	 * Generate a unique case number
	 *
	 * @since    1.0.0
	 * @return   string    Generated case number.
	 */
	private static function generate_case_number() {
		global $wpdb;

		$options = get_option( 'wp_paradb_options', array() );
		$format = isset( $options['case_number_format'] ) ? $options['case_number_format'] : 'CASE-%Y-%ID%';

		// Get next ID.
		$next_id = $wpdb->get_var( "SELECT MAX(case_id) FROM {$wpdb->prefix}paradb_cases" );
		$next_id = $next_id ? $next_id + 1 : 1;

		// Replace placeholders.
		$case_number = str_replace( '%Y%', gmdate( 'Y' ), $format );
		$case_number = str_replace( '%M%', gmdate( 'm' ), $case_number );
		$case_number = str_replace( '%D%', gmdate( 'd' ), $case_number );
		$case_number = str_replace( '%ID%', str_pad( $next_id, 4, '0', STR_PAD_LEFT ), $case_number );

		return $case_number;
	}

	/**
	 * Assign team member to case
	 *
	 * @since    1.0.0
	 * @param    int       $case_id    Case ID.
	 * @param    int       $user_id    User ID.
	 * @param    string    $role       Team member role.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	public static function assign_team_member( $case_id, $user_id, $role = 'investigator' ) {
		global $wpdb;

		$assignment_data = array(
			'case_id'       => absint( $case_id ),
			'user_id'       => absint( $user_id ),
			'role'          => sanitize_text_field( $role ),
			'assigned_by'   => get_current_user_id(),
			'date_assigned' => current_time( 'mysql' ),
		);

		$result = $wpdb->replace(
			$wpdb->prefix . 'paradb_case_team',
			$assignment_data,
			array( '%d', '%d', '%s', '%d', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_insert_error', __( 'Failed to assign team member.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_team_member_assigned', $case_id, $user_id, $role );

		return true;
	}

	/**
	 * Remove team member from case
	 *
	 * @since    1.6.0
	 * @param    int    $case_id    Case ID.
	 * @param    int    $user_id    User ID.
	 * @return   bool|WP_Error      True on success, WP_Error on failure.
	 */
	public static function remove_team_member( $case_id, $user_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$wpdb->prefix . 'paradb_case_team',
			array(
				'case_id' => absint( $case_id ),
				'user_id' => absint( $user_id ),
			),
			array( '%d', '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_delete_error', __( 'Failed to remove team member.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_team_member_removed', $case_id, $user_id );

		return true;
	}

	/**
	 * Get team members for a case
	 *
	 * @since    1.0.0
	 * @param    int    $case_id    Case ID.
	 * @return   array              Array of team member objects.
	 */
	public static function get_case_team( $case_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}paradb_case_team WHERE case_id = %d ORDER BY date_assigned DESC",
			absint( $case_id )
		) );
	}
}