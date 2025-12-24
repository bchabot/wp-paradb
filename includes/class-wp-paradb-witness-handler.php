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
	 * Submit a witness account
	 *
	 * @since    1.0.0
	 * @param    array    $data    Witness account data.
	 * @return   int|WP_Error      Witness ID on success, WP_Error on failure.
	 */
	public static function submit_account( $data ) {
		global $wpdb;

		// Validate required fields.
		if ( empty( $data['incident_description'] ) ) {
			return new WP_Error( 'missing_description', __( 'Incident description is required.', 'wp-paradb' ) );
		}

		// Check if submissions are enabled.
		$options = get_option( 'wp_paradb_options', array() );
		$allow_submissions = isset( $options['allow_public_submissions'] ) ? $options['allow_public_submissions'] : true;

		if ( ! $allow_submissions ) {
			return new WP_Error( 'submissions_disabled', __( 'Witness submissions are currently disabled.', 'wp-paradb' ) );
		}

		// Determine if anonymous.
		$is_anonymous = empty( $data['witness_name'] ) && empty( $data['witness_email'] );

		// Prepare witness data.
		$witness_data = array(
			'witness_name'         => isset( $data['witness_name'] ) ? sanitize_text_field( $data['witness_name'] ) : null,
			'witness_email'        => isset( $data['witness_email'] ) ? sanitize_email( $data['witness_email'] ) : null,
			'witness_phone'        => isset( $data['witness_phone'] ) ? sanitize_text_field( $data['witness_phone'] ) : null,
			'incident_date'        => isset( $data['incident_date'] ) ? sanitize_text_field( $data['incident_date'] ) : null,
			'incident_location'    => isset( $data['incident_location'] ) ? sanitize_text_field( $data['incident_location'] ) : null,
			'incident_description' => wp_kses_post( $data['incident_description'] ),
			'phenomena_type'       => isset( $data['phenomena_type'] ) ? sanitize_text_field( $data['phenomena_type'] ) : null,
			'witness_ip'           => self::get_client_ip(),
			'is_anonymous'         => $is_anonymous ? 1 : 0,
			'status'               => 'pending',
			'submission_date'      => current_time( 'mysql' ),
		);

		$format = array(
			'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s',
		);

		// Insert into database.
		$result = $wpdb->insert(
			$wpdb->prefix . 'paradb_witness_accounts',
			$witness_data,
			$format
		);

		if ( false === $result ) {
			return new WP_Error( 'db_insert_error', __( 'Failed to submit witness account.', 'wp-paradb' ) );
		}

		$witness_id = $wpdb->insert_id;

		do_action( 'wp_paradb_witness_submitted', $witness_id, $witness_data );

		// Send notification to administrators.
		self::send_submission_notification( $witness_id );

		return $witness_id;
	}

	/**
	 * Get client IP address
	 *
	 * @since    1.0.0
	 * @return   string    Client IP address.
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
	 * Send notification email about new submission
	 *
	 * @since    1.0.0
	 * @param    int    $witness_id    Witness account ID.
	 */
	private static function send_submission_notification( $witness_id ) {
		$witness = self::get_witness_account( $witness_id );
		if ( ! $witness ) {
			return;
		}

		$admin_email = get_option( 'admin_email' );
		$subject = sprintf(
			__( '[%s] New Witness Account Submitted', 'wp-paradb' ),
			get_bloginfo( 'name' )
		);

		$message = sprintf(
			__( 'A new witness account has been submitted and is awaiting review.', 'wp-paradb' ) . "\n\n" .
			__( 'Submission ID: %d', 'wp-paradb' ) . "\n" .
			__( 'Date: %s', 'wp-paradb' ) . "\n" .
			__( 'Location: %s', 'wp-paradb' ) . "\n\n" .
			__( 'Review this submission: %s', 'wp-paradb' ),
			$witness_id,
			$witness->submission_date,
			$witness->incident_location,
			admin_url( 'admin.php?page=wp-paradb-witnesses&action=view&witness_id=' . $witness_id )
		);

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Review a witness account
	 *
	 * @since    1.0.0
	 * @param    int       $witness_id    Witness account ID.
	 * @param    string    $status        New status (approved, rejected, spam).
	 * @return   bool|WP_Error            True on success, WP_Error on failure.
	 */
	public static function review_account( $witness_id, $status ) {
		global $wpdb;

		$witness_id = absint( $witness_id );

		if ( 0 === $witness_id ) {
			return new WP_Error( 'invalid_witness_id', __( 'Invalid witness account ID.', 'wp-paradb' ) );
		}

		$allowed_statuses = array( 'approved', 'rejected', 'spam', 'pending' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			return new WP_Error( 'invalid_status', __( 'Invalid status.', 'wp-paradb' ) );
		}

		$update_data = array(
			'status'      => $status,
			'is_reviewed' => 1,
			'reviewed_by' => get_current_user_id(),
			'review_date' => current_time( 'mysql' ),
		);

		$result = $wpdb->update(
			$wpdb->prefix . 'paradb_witness_accounts',
			$update_data,
			array( 'witness_id' => $witness_id ),
			array( '%s', '%d', '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_update_error', __( 'Failed to review witness account.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_witness_reviewed', $witness_id, $status );

		return true;
	}

	/**
	 * Link witness account to a case
	 *
	 * @since    1.0.0
	 * @param    int    $witness_id    Witness account ID.
	 * @param    int    $case_id       Case ID.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	public static function link_to_case( $witness_id, $case_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'paradb_witness_accounts',
			array( 'case_id' => absint( $case_id ) ),
			array( 'witness_id' => absint( $witness_id ) ),
			array( '%d' ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_update_error', __( 'Failed to link witness account to case.', 'wp-paradb' ) );
		}

		return true;
	}

	/**
	 * Get a witness account by ID
	 *
	 * @since    1.0.0
	 * @param    int    $witness_id    Witness account ID.
	 * @return   object|null           Witness account object or null if not found.
	 */
	public static function get_witness_account( $witness_id ) {
		global $wpdb;

		$witness_id = absint( $witness_id );

		if ( 0 === $witness_id ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}paradb_witness_accounts WHERE witness_id = %d",
			$witness_id
		) );
	}

	/**
	 * Get witness accounts with filters
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Array of witness account objects.
	 */
	public static function get_witness_accounts( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'   => '',
			'case_id'  => 0,
			'orderby'  => 'submission_date',
			'order'    => 'DESC',
			'limit'    => 20,
			'offset'   => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$where_values[] = $args['status'];
		}

		if ( $args['case_id'] > 0 ) {
			$where[] = 'case_id = %d';
			$where_values[] = $args['case_id'];
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT * FROM {$wpdb->prefix}paradb_witness_accounts WHERE {$where_clause}";

		$allowed_orderby = array( 'witness_id', 'submission_date', 'incident_date', 'status' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'submission_date';
		$order = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$query .= " ORDER BY {$orderby} {$order}";

		$query .= " LIMIT %d OFFSET %d";
		$where_values[] = absint( $args['limit'] );
		$where_values[] = absint( $args['offset'] );

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Delete a witness account
	 *
	 * @since    1.0.0
	 * @param    int    $witness_id    Witness account ID.
	 * @return   bool|WP_Error         True on success, WP_Error on failure.
	 */
	public static function delete_witness_account( $witness_id ) {
		global $wpdb;

		$witness_id = absint( $witness_id );

		if ( 0 === $witness_id ) {
			return new WP_Error( 'invalid_witness_id', __( 'Invalid witness account ID.', 'wp-paradb' ) );
		}

		$result = $wpdb->delete(
			$wpdb->prefix . 'paradb_witness_accounts',
			array( 'witness_id' => $witness_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_delete_error', __( 'Failed to delete witness account.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_witness_deleted', $witness_id );

		return true;
	}
}