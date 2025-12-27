<?php
/**
 * Witness account management functionality
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
 * Handle witness account operations
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Witness_Handler {

	/**
	 * Create a new witness account
	 *
	 * @since    1.0.0
	 * @param    array    $data    Witness account data.
	 * @return   int|WP_Error      Account ID on success, WP_Error on failure.
	 */
	public static function create_witness_account( $data ) {
		global $wpdb;

		// Validate required fields.
		if ( empty( $data['account_email'] ) || empty( $data['incident_date'] ) || 
		     empty( $data['incident_location'] ) || empty( $data['incident_description'] ) ) {
			return new WP_Error( 
				'missing_required_fields', 
				__( 'Email, incident date, location, and description are required.', 'wp-paradb' ) 
			);
		}

		// Validate email.
		$email = sanitize_email( $data['account_email'] );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'wp-paradb' ) );
		}

		// Handle phenomena types (should be array).
		$phenomena_types = isset( $data['phenomena_types'] ) && is_array( $data['phenomena_types'] )
			? $data['phenomena_types']
			: array();

		if ( empty( $phenomena_types ) ) {
			return new WP_Error( 
				'missing_phenomena', 
				__( 'Please select at least one type of phenomenon.', 'wp-paradb' ) 
			);
		}

		// Validate phenomena types against allowed types.
		$allowed_types = array_keys( WP_ParaDB_Settings::get_phenomena_types() );
		$phenomena_types = array_intersect( $phenomena_types, $allowed_types );

		if ( empty( $phenomena_types ) ) {
			return new WP_Error( 
				'invalid_phenomena', 
				__( 'Invalid phenomenon types selected.', 'wp-paradb' ) 
			);
		}

		// Handle consent status.
		$consent_status = isset( $data['consent_status'] ) ? sanitize_text_field( $data['consent_status'] ) : '';
		$allowed_consent = array( 'private', 'anonymize', 'publish' );
		
		if ( ! in_array( $consent_status, $allowed_consent, true ) ) {
			// Check if consent is required.
			$default_consent = WP_ParaDB_Settings::get_default_consent();
			if ( 'none' === $default_consent ) {
				return new WP_Error( 
					'consent_required', 
					__( 'Please select a publication consent option.', 'wp-paradb' ) 
				);
			}
			$consent_status = $default_consent;
		}

		// Prepare account data.
		$account_data = array(
			'account_email'         => $email,
			'user_id'               => isset( $data['user_id'] ) ? absint( $data['user_id'] ) : null,
			'case_id'               => isset( $data['case_id'] ) ? absint( $data['case_id'] ) : null,
			'account_name'          => ! empty( $data['account_name'] ) ? sanitize_text_field( $data['account_name'] ) : null,
			'account_phone'         => ! empty( $data['account_phone'] ) ? sanitize_text_field( $data['account_phone'] ) : null,
			'account_address'       => ! empty( $data['account_address'] ) ? sanitize_textarea_field( $data['account_address'] ) : null,
			'incident_date'         => sanitize_text_field( $data['incident_date'] ),
			'incident_time'         => ! empty( $data['incident_time'] ) ? sanitize_text_field( $data['incident_time'] ) : null,
			'incident_location'     => sanitize_text_field( $data['incident_location'] ),
			'incident_description'  => sanitize_textarea_field( $data['incident_description'] ),
			'phenomena_types'       => wp_json_encode( $phenomena_types ),
			'witnesses_present'     => isset( $data['witnesses_present'] ) ? absint( $data['witnesses_present'] ) : null,
			'witness_names'         => ! empty( $data['witness_names'] ) ? sanitize_textarea_field( $data['witness_names'] ) : null,
			'previous_experiences'  => isset( $data['previous_experiences'] ) ? (bool) $data['previous_experiences'] : false,
			'previous_details'      => ! empty( $data['previous_details'] ) ? sanitize_textarea_field( $data['previous_details'] ) : null,
			'consent_status'        => $consent_status,
			'consent_anonymize'     => ( 'anonymize' === $consent_status ) ? 1 : 0,
			'allow_publish'         => ( 'publish' === $consent_status ) ? 1 : 0,
			'allow_followup'        => isset( $data['allow_followup'] ) ? (bool) $data['allow_followup'] : true,
			'privacy_accepted'      => isset( $data['privacy_accepted'] ) ? (bool) $data['privacy_accepted'] : false,
			'privacy_accepted_date' => isset( $data['privacy_accepted'] ) && $data['privacy_accepted'] ? current_time( 'mysql' ) : null,
			'status'                => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'pending',
			'ip_address'            => self::get_client_ip(),
			'user_agent'            => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : null,
			'date_submitted'        => current_time( 'mysql' ),
		);

		// Check if privacy policy acceptance is required.
		if ( WP_ParaDB_Settings::get_setting( 'require_privacy_acceptance', true ) && ! $account_data['privacy_accepted'] ) {
			return new WP_Error( 
				'privacy_not_accepted', 
				__( 'You must accept the privacy policy to submit a report.', 'wp-paradb' ) 
			);
		}

		// Format types for database.
		$format = array(
			'%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
			'%s', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d',
			'%s', '%s', '%s', '%s', '%s',
		);

		// Insert into database.
		$result = $wpdb->insert(
			$wpdb->prefix . 'paradb_witness_accounts',
			$account_data,
			$format
		);

		if ( false === $result ) {
			return new WP_Error( 'db_insert_error', __( 'Failed to submit witness report.', 'wp-paradb' ) . ' ' . $wpdb->last_error );
		}

		$account_id = $wpdb->insert_id;

		// Handle account creation if requested.
		if ( isset( $data['create_account'] ) && $data['create_account'] && 
		     WP_ParaDB_Settings::get_setting( 'witness_account_creation', true ) ) {
			$user_id = self::create_wordpress_user( $email, $data );
			
			if ( ! is_wp_error( $user_id ) ) {
				// Link user to witness account.
				$wpdb->update(
					$wpdb->prefix . 'paradb_witness_accounts',
					array( 'user_id' => $user_id ),
					array( 'account_id' => $account_id ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}

		// Send notifications.
		self::send_submission_notifications( $account_id, $account_data );

		do_action( 'wp_paradb_witness_account_created', $account_id, $account_data );

		return $account_id;
	}

	/**
	 * Create WordPress user for witness
	 *
	 * @since    1.0.0
	 * @param    string    $email    User email.
	 * @param    array     $data     Additional user data.
	 * @return   int|WP_Error        User ID on success, WP_Error on failure.
	 */
	private static function create_wordpress_user( $email, $data ) {
		// Check if user already exists.
		$existing_user = get_user_by( 'email', $email );
		if ( $existing_user ) {
			return $existing_user->ID;
		}

		// Generate username from email.
		$username = sanitize_user( current( explode( '@', $email ) ), true );
		
		// Ensure username is unique.
		$base_username = $username;
		$counter = 1;
		while ( username_exists( $username ) ) {
			$username = $base_username . $counter;
			$counter++;
		}

		// Generate secure password.
		$password = wp_generate_password( 12, true, true );

		// Create user.
		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Update user meta.
		if ( isset( $data['account_name'] ) ) {
			$name_parts = explode( ' ', $data['account_name'], 2 );
			wp_update_user( array(
				'ID'           => $user_id,
				'first_name'   => $name_parts[0],
				'last_name'    => isset( $name_parts[1] ) ? $name_parts[1] : '',
				'display_name' => $data['account_name'],
			) );
		}

		// Set user role to subscriber by default.
		$user = new WP_User( $user_id );
		$user->set_role( 'subscriber' );

		// Add witness-specific meta.
		update_user_meta( $user_id, 'paradb_witness', true );

		// Send new user notification.
		wp_new_user_notification( $user_id, null, 'both' );

		return $user_id;
	}

	/**
	 * Send submission notifications
	 *
	 * @since    1.0.0
	 * @param    int      $account_id    Account ID.
	 * @param    array    $account_data  Account data.
	 */
	private static function send_submission_notifications( $account_id, $account_data ) {
		// Send admin notification.
		if ( WP_ParaDB_Settings::get_setting( 'notify_admin_new_submission', true ) ) {
			$admin_email = WP_ParaDB_Settings::get_setting( 'admin_notification_email', get_option( 'admin_email' ) );
			
			$subject = sprintf( 
				__( '[%s] New Paranormal Witness Report Submitted', 'wp-paradb' ),
				get_bloginfo( 'name' )
			);
			
			$message = sprintf(
				__( "A new witness report has been submitted:\n\nReport ID: %d\nDate: %s\nLocation: %s\nContact: %s\n\nView and manage this report in the admin panel.", 'wp-paradb' ),
				$account_id,
				$account_data['incident_date'],
				$account_data['incident_location'],
				$account_data['account_email']
			);
			
			wp_mail( $admin_email, $subject, $message );
		}

		// Send witness confirmation.
		if ( WP_ParaDB_Settings::get_setting( 'notify_witness_confirmation', true ) ) {
			$subject = sprintf( 
				__( '[%s] Thank you for your paranormal report submission', 'wp-paradb' ),
				get_bloginfo( 'name' )
			);
			
			$message = sprintf(
				__( "Thank you for submitting your paranormal experience report.\n\nYour report has been received and will be reviewed by our team. We will contact you if we need any additional information.\n\nReport ID: %d\nSubmission Date: %s\n\nThank you for contributing to our research.", 'wp-paradb' ),
				$account_id,
				current_time( 'mysql' )
			);
			
			wp_mail( $account_data['account_email'], $subject, $message );
		}
	}

	/**
	 * Update witness account
	 *
	 * @since    1.0.0
	 * @param    int      $account_id    Account ID.
	 * @param    array    $data          Updated account data.
	 * @return   bool|WP_Error           True on success, WP_Error on failure.
	 */
	public static function update_witness_account( $account_id, $data ) {
		global $wpdb;

		$account_id = absint( $account_id );

		if ( 0 === $account_id ) {
			return new WP_Error( 'invalid_account_id', __( 'Invalid account ID.', 'wp-paradb' ) );
		}

		// Check if account exists.
		$account = self::get_witness_account( $account_id );
		if ( ! $account ) {
			return new WP_Error( 'account_not_found', __( 'Witness account not found.', 'wp-paradb' ) );
		}

		// Prepare update data.
		$update_data = array();
		$format = array();

		// Handle phenomena types if provided.
		if ( isset( $data['phenomena_types'] ) && is_array( $data['phenomena_types'] ) ) {
			$allowed_types = array_keys( WP_ParaDB_Settings::get_phenomena_types() );
			$phenomena_types = array_intersect( $data['phenomena_types'], $allowed_types );
			$update_data['phenomena_types'] = wp_json_encode( $phenomena_types );
			$format[] = '%s';
		}

		// Handle consent status.
		if ( isset( $data['consent_status'] ) ) {
			$allowed_consent = array( 'private', 'anonymize', 'publish' );
			if ( in_array( $data['consent_status'], $allowed_consent, true ) ) {
				$update_data['consent_status'] = $data['consent_status'];
				$update_data['consent_anonymize'] = ( 'anonymize' === $data['consent_status'] ) ? 1 : 0;
				$update_data['allow_publish'] = ( 'publish' === $data['consent_status'] ) ? 1 : 0;
				$format[] = '%s';
				$format[] = '%d';
				$format[] = '%d';
			}
		}

		// Handle other updatable fields.
		$text_fields = array( 
			'account_name', 'account_email', 'account_phone', 'incident_location', 
			'incident_date', 'incident_time', 'status' 
		);
		$textarea_fields = array( 
			'account_address', 'incident_description', 'witness_names', 
			'previous_details', 'admin_notes' 
		);
		$bool_fields = array( 'previous_experiences', 'allow_followup' );
		$int_fields = array( 'witnesses_present', 'user_id', 'case_id' );

		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$update_data[ $field ] = ! empty( $data[ $field ] ) ? sanitize_text_field( $data[ $field ] ) : null;
				$format[] = ( null === $update_data[ $field ] ) ? null : '%s';
			}
		}

		foreach ( $textarea_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$update_data[ $field ] = ! empty( $data[ $field ] ) ? sanitize_textarea_field( $data[ $field ] ) : null;
				$format[] = ( null === $update_data[ $field ] ) ? null : '%s';
			}
		}

		foreach ( $bool_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$update_data[ $field ] = (bool) $data[ $field ] ? 1 : 0;
				$format[] = '%d';
			}
		}

		foreach ( $int_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$update_data[ $field ] = absint( $data[ $field ] );
				$format[] = '%d';
			}
		}

		$update_data['date_modified'] = current_time( 'mysql' );
		$format[] = '%s';

		// Update database.
		$result = $wpdb->update(
			$wpdb->prefix . 'paradb_witness_accounts',
			$update_data,
			array( 'account_id' => $account_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_update_error', __( 'Failed to update witness account.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_witness_account_updated', $account_id, $update_data );

		return true;
	}

	/**
	 * Get witness account by ID
	 *
	 * @since    1.0.0
	 * @param    int    $account_id    Account ID.
	 * @return   object|null           Account object or null if not found.
	 */
	public static function get_witness_account( $account_id ) {
		global $wpdb;

		$account_id = absint( $account_id );

		if ( 0 === $account_id ) {
			return null;
		}

		$account = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}paradb_witness_accounts WHERE account_id = %d",
			$account_id
		) );

		if ( $account && ! empty( $account->phenomena_types ) ) {
			$account->phenomena_types = json_decode( $account->phenomena_types, true );
		}

		return $account;
	}

	/**
	 * Get client IP address
	 *
	 * @since    1.0.0
	 * @return   string    IP address.
	 */
	private static function get_client_ip() {
		$ip = '';

		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * Get witness accounts with filters
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Array of account objects.
	 */
	public static function get_witness_accounts( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'      => '',
			'user_id'     => 0,
			'search'      => '',
			'orderby'     => 'date_submitted',
			'order'       => 'DESC',
			'limit'       => 20,
			'offset'      => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		// Filter by status.
		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$where_values[] = $args['status'];
		}

		// Filter by user.
		if ( $args['user_id'] > 0 ) {
			$where[] = 'user_id = %d';
			$where_values[] = $args['user_id'];
		}

		// Search.
		if ( ! empty( $args['search'] ) ) {
			$where[] = '(account_name LIKE %s OR account_email LIKE %s OR incident_location LIKE %s OR incident_description LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = implode( ' AND ', $where );

		// Build query.
		$query = "SELECT * FROM {$wpdb->prefix}paradb_witness_accounts WHERE {$where_clause}";

		// Add ordering.
		$allowed_orderby = array( 'account_id', 'date_submitted', 'incident_date', 'status' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'date_submitted';
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

		$accounts = $wpdb->get_results( $query );

		// Decode phenomena types for each account.
		foreach ( $accounts as $account ) {
			if ( ! empty( $account->phenomena_types ) ) {
				$account->phenomena_types = json_decode( $account->phenomena_types, true );
			}
		}

		return $accounts;
	}

	/**
	 * Delete witness account
	 *
	 * @since    1.0.0
	 * @param    int    $account_id    Account ID.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	public static function delete_witness_account( $account_id ) {
		global $wpdb;

		$account_id = absint( $account_id );

		if ( 0 === $account_id ) {
			return new WP_Error( 'invalid_account_id', __( 'Invalid account ID.', 'wp-paradb' ) );
		}

		$result = $wpdb->delete(
			$wpdb->prefix . 'paradb_witness_accounts',
			array( 'account_id' => $account_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_delete_error', __( 'Failed to delete witness account.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_witness_account_deleted', $account_id );

		return true;
	}

	/**
	 * Review a witness account status
	 *
	 * @since    1.0.0
	 * @param    int       $account_id    Account ID.
	 * @param    string    $status        New status.
	 * @return   bool|WP_Error            True on success, WP_Error on failure.
	 */
	public static function review_account( $account_id, $status ) {
		return self::update_witness_account( $account_id, array( 'status' => $status ) );
	}

	/**
	 * Link a witness account to a case
	 *
	 * @since    1.0.0
	 * @param    int    $account_id    Account ID.
	 * @param    int    $case_id       Case ID.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	public static function link_to_case( $account_id, $case_id ) {
		return self::update_witness_account( $account_id, array( 'case_id' => $case_id ) );
	}
}