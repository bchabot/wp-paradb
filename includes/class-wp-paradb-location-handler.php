<?php
/**
 * Location management functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.2.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/includes
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle location operations
 *
 * @since      1.2.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Location_Handler {

	/**
	 * Create a new location
	 *
	 * @since    1.2.0
	 * @param    array    $data    Location data.
	 * @return   int|WP_Error      Location ID on success, WP_Error on failure.
	 */
	public static function create_location( $data ) {
		global $wpdb;

		if ( empty( $data['location_name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Location name is required.', 'wp-paradb' ) );
		}

		$location_data = array(
			'location_name'   => sanitize_text_field( $data['location_name'] ),
			'address'         => isset( $data['address'] ) ? sanitize_text_field( $data['address'] ) : null,
			'city'            => isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : null,
			'state'           => isset( $data['state'] ) ? sanitize_text_field( $data['state'] ) : null,
			'zip'             => isset( $data['zip'] ) ? sanitize_text_field( $data['zip'] ) : null,
			'country'         => isset( $data['country'] ) ? sanitize_text_field( $data['country'] ) : 'United States',
			'latitude'        => isset( $data['latitude'] ) ? floatval( $data['latitude'] ) : null,
			'longitude'       => isset( $data['longitude'] ) ? floatval( $data['longitude'] ) : null,
			'location_notes'  => isset( $data['location_notes'] ) ? sanitize_textarea_field( $data['location_notes'] ) : null,
			'is_public'       => isset( $data['is_public'] ) ? (bool) $data['is_public'] : 0,
			'created_by'      => get_current_user_id(),
			'date_created'    => current_time( 'mysql' ),
		);

		$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%d', '%s' );

		$result = $wpdb->insert(
			$wpdb->prefix . 'paradb_locations',
			$location_data,
			$format
		);

		if ( false === $result ) {
			return new WP_Error( 'db_insert_error', __( 'Failed to create location.', 'wp-paradb' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get a location by ID
	 *
	 * @since    1.2.0
	 */
	public static function get_location( $location_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}paradb_locations WHERE location_id = %d",
			absint( $location_id )
		) );
	}

	/**
	 * Get locations with filters
	 *
	 * @since    1.2.0
	 */
	public static function get_locations( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search'  => '',
			'limit'   => 100,
			'offset'  => 0,
			'orderby' => 'location_name',
			'order'   => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['search'] ) ) {
			$where[] = '(location_name LIKE %s OR address LIKE %s OR city LIKE %s)';
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search;
			$where_values[] = $search;
			$where_values[] = $search;
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT * FROM {$wpdb->prefix}paradb_locations WHERE {$where_clause}";
		
		$query .= " ORDER BY {$args['orderby']} {$args['order']}";
		$query .= " LIMIT %d OFFSET %d";
		$where_values[] = $args['limit'];
		$where_values[] = $args['offset'];

		return $wpdb->get_results( $wpdb->prepare( $query, $where_values ) );
	}

	/**
	 * Update a location
	 *
	 * @since    1.2.0
	 */
	public static function update_location( $location_id, $data ) {
		global $wpdb;

		$location_id = absint( $location_id );
		if ( 0 === $location_id ) {
			return new WP_Error( 'invalid_id', __( 'Invalid location ID.', 'wp-paradb' ) );
		}

		$update_data = array();
		$format = array();

		$allowed_fields = array(
			'location_name', 'address', 'city', 'state', 'zip', 'country',
			'latitude', 'longitude', 'location_notes', 'is_public'
		);

		foreach ( $allowed_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				if ( in_array( $field, array( 'latitude', 'longitude' ), true ) ) {
					$update_data[ $field ] = floatval( $data[ $field ] );
					$format[] = '%f';
				} elseif ( 'is_public' === $field ) {
					$update_data[ $field ] = (bool) $data[ $field ] ? 1 : 0;
					$format[] = '%d';
				} elseif ( 'location_notes' === $field ) {
					$update_data[ $field ] = sanitize_textarea_field( $data[ $field ] );
					$format[] = '%s';
				} else {
					$update_data[ $field ] = sanitize_text_field( $data[ $field ] );
					$format[] = '%s';
				}
			}
		}

		if ( empty( $update_data ) ) {
			return true;
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'paradb_locations',
			$update_data,
			array( 'location_id' => $location_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_update_error', __( 'Failed to update location.', 'wp-paradb' ) );
		}

		return true;
	}

	/**
	 * Delete a location
	 *
	 * @since    1.2.0
	 */
	public static function delete_location( $location_id ) {
		global $wpdb;

		$location_id = absint( $location_id );
		
		// Nullify references in cases and activities
		$wpdb->update( $wpdb->prefix . 'paradb_cases', array( 'location_id' => null ), array( 'location_id' => $location_id ), array( '%d' ), array( '%d' ) );
		$wpdb->update( $wpdb->prefix . 'paradb_activities', array( 'location_id' => null ), array( 'location_id' => $location_id ), array( '%d' ), array( '%d' ) );
		$wpdb->update( $wpdb->prefix . 'paradb_witness_accounts', array( 'location_id' => null ), array( 'location_id' => $location_id ), array( '%d' ), array( '%d' ) );

		return $wpdb->delete(
			$wpdb->prefix . 'paradb_locations',
			array( 'location_id' => $location_id ),
			array( '%d' )
		);
	}
}
