<?php
/**
 * Client management functionality
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
 * Handle client operations
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Client_Handler {

	/**
	 * Create a new client
	 *
	 * @since    1.0.0
	 * @param    array    $data    Client data.
	 * @return   int|WP_Error      Client ID on success, WP_Error on failure.
	 */
	public static function create_client( $data ) {
		global $wpdb;

		// Validate required fields.
		if ( empty( $data['first_name'] ) || empty( $data['last_name'] ) ) {
			return new WP_Error( 'missing_required_fields', __( 'First name and last name are required.', 'wp-paradb' ) );
		}

		// Prepare client data.
		$client_data = array(
			'first_name'         => sanitize_text_field( $data['first_name'] ),
			'last_name'          => sanitize_text_field( $data['last_name'] ),
			'email'              => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : null,
			'phone'              => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : null,
			'address'            => isset( $data['address'] ) ? sanitize_text_field( $data['address'] ) : null,
			'city'               => isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : null,
			'state'              => isset( $data['state'] ) ? sanitize_text_field( $data['state'] ) : null,
			'zip'                => isset( $data['zip'] ) ? sanitize_text_field( $data['zip'] ) : null,
			'country'            => isset( $data['country'] ) ? sanitize_text_field( $data['country'] ) : 'United States',
			'preferred_contact'  => isset( $data['preferred_contact'] ) ? sanitize_text_field( $data['preferred_contact'] ) : 'email',
			'notes'              => isset( $data['notes'] ) ? wp_kses_post( $data['notes'] ) : null,
			'consent_to_publish' => isset( $data['consent_to_publish'] ) ? absint( $data['consent_to_publish'] ) : 0,
			'anonymize_data'     => isset( $data['anonymize_data'] ) ? absint( $data['anonymize_data'] ) : 1,
			'created_by'         => get_current_user_id(),
			'date_created'       => current_time( 'mysql' ),
		);

		// Format types for database.
		$format = array(
			'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
			'%s', '%d', '%d', '%d', '%s',
		);

		// Insert into database.
		$result = $wpdb->insert(
			$wpdb->prefix . 'paradb_clients',
			$client_data,
			$format
		);

		if ( false === $result ) {
			return new WP_Error( 'db_insert_error', __( 'Failed to create client.', 'wp-paradb' ) );
		}

		$client_id = $wpdb->insert_id;

		do_action( 'wp_paradb_client_created', $client_id, $client_data );

		return $client_id;
	}

	/**
	 * Update an existing client
	 *
	 * @since    1.0.0
	 * @param    int      $client_id    Client ID.
	 * @param    array    $data         Updated client data.
	 * @return   bool|WP_Error          True on success, WP_Error on failure.
	 */
	public static function update_client( $client_id, $data ) {
		global $wpdb;

		$client_id = absint( $client_id );

		if ( 0 === $client_id ) {
			return new WP_Error( 'invalid_client_id', __( 'Invalid client ID.', 'wp-paradb' ) );
		}

		// Check if client exists.
		$client = self::get_client( $client_id );
		if ( ! $client ) {
			return new WP_Error( 'client_not_found', __( 'Client not found.', 'wp-paradb' ) );
		}

		// Prepare update data.
		$update_data = array();
		$format = array();

		$allowed_fields = array(
			'first_name', 'last_name', 'email', 'phone', 'address', 'city',
			'state', 'zip', 'country', 'preferred_contact', 'notes',
			'consent_to_publish', 'anonymize_data',
		);

		foreach ( $allowed_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				if ( 'email' === $field ) {
					$update_data[ $field ] = sanitize_email( $data[ $field ] );
					$format[] = '%s';
				} elseif ( 'notes' === $field ) {
					$update_data[ $field ] = wp_kses_post( $data[ $field ] );
					$format[] = '%s';
				} elseif ( in_array( $field, array( 'consent_to_publish', 'anonymize_data' ), true ) ) {
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
			$wpdb->prefix . 'paradb_clients',
			$update_data,
			array( 'client_id' => $client_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_update_error', __( 'Failed to update client.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_client_updated', $client_id, $update_data );

		return true;
	}

	/**
	 * Get a client by ID
	 *
	 * @since    1.0.0
	 * @param    int    $client_id    Client ID.
	 * @return   object|null          Client object or null if not found.
	 */
	public static function get_client( $client_id ) {
		global $wpdb;

		$client_id = absint( $client_id );

		if ( 0 === $client_id ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}paradb_clients WHERE client_id = %d",
			$client_id
		) );
	}

	/**
	 * Get clients with filters
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Array of client objects.
	 */
	public static function get_clients( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search'  => '',
			'orderby' => 'last_name',
			'order'   => 'ASC',
			'limit'   => 20,
			'offset'  => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		// Search.
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = implode( ' AND ', $where );

		// Build query.
		$query = "SELECT * FROM {$wpdb->prefix}paradb_clients WHERE {$where_clause}";

		// Add ordering.
		$allowed_orderby = array( 'client_id', 'first_name', 'last_name', 'email', 'date_created' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'last_name';
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
	 * Delete a client
	 *
	 * @since    1.0.0
	 * @param    int    $client_id    Client ID.
	 * @return   bool|WP_Error        True on success, WP_Error on failure.
	 */
	public static function delete_client( $client_id ) {
		global $wpdb;

		$client_id = absint( $client_id );

		if ( 0 === $client_id ) {
			return new WP_Error( 'invalid_client_id', __( 'Invalid client ID.', 'wp-paradb' ) );
		}

		// Check for associated cases.
		$case_count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}paradb_cases WHERE client_id = %d",
			$client_id
		) );

		if ( $case_count > 0 ) {
			return new WP_Error( 'client_has_cases', __( 'Cannot delete client with associated cases.', 'wp-paradb' ) );
		}

		// Delete client.
		$result = $wpdb->delete(
			$wpdb->prefix . 'paradb_clients',
			array( 'client_id' => $client_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_delete_error', __( 'Failed to delete client.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_client_deleted', $client_id );

		return true;
	}

	/**
	 * Get client display name
	 *
	 * @since    1.0.0
	 * @param    object    $client         Client object.
	 * @param    bool      $anonymize      Whether to anonymize the name.
	 * @return   string                    Client display name.
	 */
	public static function get_client_display_name( $client, $anonymize = null ) {
		if ( ! $client ) {
			return __( 'Unknown Client', 'wp-paradb' );
		}

		// Use client's preference if not specified.
		if ( null === $anonymize ) {
			$anonymize = isset( $client->anonymize_data ) ? (bool) $client->anonymize_data : false;
		}

		if ( $anonymize ) {
			return sprintf(
				__( 'Client %s', 'wp-paradb' ),
				substr( md5( $client->client_id ), 0, 8 )
			);
		}

		return trim( $client->first_name . ' ' . $client->last_name );
	}
}