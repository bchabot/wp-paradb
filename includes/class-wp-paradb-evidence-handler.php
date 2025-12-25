<?php
/**
 * Evidence file management functionality
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
 * Handle evidence file operations
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Evidence_Handler {

	/**
	 * Upload and create evidence record
	 *
	 * @since    1.0.0
	 * @param    array    $file        File data from $_FILES.
	 * @param    array    $metadata    Evidence metadata.
	 * @return   int|WP_Error          Evidence ID on success, WP_Error on failure.
	 */
	public static function upload_evidence( $file, $metadata ) {
		// Validate required fields.
		if ( empty( $metadata['case_id'] ) ) {
			return new WP_Error( 'missing_case_id', __( 'Case ID is required.', 'wp-paradb' ) );
		}

		// Validate file.
		$validation = self::validate_file( $file );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Handle file upload.
		$upload_result = self::handle_file_upload( $file, $metadata['case_id'] );
		if ( is_wp_error( $upload_result ) ) {
			return $upload_result;
		}

		// Create database record.
		return self::create_evidence_record( $upload_result, $metadata );
	}

	/**
	 * Validate uploaded file
	 *
	 * @since    1.0.0
	 * @param    array    $file    File data from $_FILES.
	 * @return   bool|WP_Error     True if valid, WP_Error on failure.
	 */
	private static function validate_file( $file ) {
		// Check for upload errors.
		if ( ! isset( $file['error'] ) || is_array( $file['error'] ) ) {
			return new WP_Error( 'invalid_file', __( 'Invalid file upload.', 'wp-paradb' ) );
		}

		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			return new WP_Error( 'upload_error', __( 'File upload failed.', 'wp-paradb' ) );
		}

		// Check file size.
		$options = get_option( 'wp_paradb_options', array() );
		$max_size = isset( $options['max_upload_size'] ) ? $options['max_upload_size'] : 10485760;

		if ( $file['size'] > $max_size ) {
			return new WP_Error(
				'file_too_large',
				sprintf(
					__( 'File size exceeds maximum allowed (%s).', 'wp-paradb' ),
					size_format( $max_size )
				)
			);
		}

		// Check file type.
		$allowed_types = isset( $options['allowed_file_types'] ) ? $options['allowed_file_types'] : array();
		$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		if ( ! empty( $allowed_types ) && ! in_array( $file_extension, $allowed_types, true ) ) {
			return new WP_Error( 'invalid_file_type', __( 'File type not allowed.', 'wp-paradb' ) );
		}

		return true;
	}

	/**
	 * Handle file upload to server
	 *
	 * @since    1.0.0
	 * @param    array    $file       File data from $_FILES.
	 * @param    int      $case_id    Case ID.
	 * @return   array|WP_Error       Upload data on success, WP_Error on failure.
	 */
	private static function handle_file_upload( $file, $case_id ) {
		$upload_dir = wp_upload_dir();
		$paradb_dir = $upload_dir['basedir'] . '/paradb-evidence';
		$year = gmdate( 'Y' );
		$month = gmdate( 'm' );

		// Create directory structure.
		$target_dir = $paradb_dir . '/' . $year . '/' . $month;
		if ( ! file_exists( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
		}

		// Generate unique filename.
		$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$unique_filename = sprintf(
			'case-%d-%s.%s',
			$case_id,
			wp_generate_uuid4(),
			$file_extension
		);

		$target_file = $target_dir . '/' . $unique_filename;

		// Move uploaded file using WordPress function.
		$moved = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'unique_filename_callback' => function() use ( $unique_filename ) {
					return $unique_filename;
				},
			)
		);

		// If wp_handle_upload doesn't work for our custom directory, fall back to move_uploaded_file.
		if ( isset( $moved['error'] ) || ! isset( $moved['file'] ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Fallback for custom upload directory.
			if ( ! @move_uploaded_file( $file['tmp_name'], $target_file ) ) {
				return new WP_Error( 'upload_failed', __( 'Failed to save uploaded file.', 'wp-paradb' ) );
			}
		} else {
			// Move from default upload location to our custom directory.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Moving file within uploads directory.
			if ( $moved['file'] !== $target_file ) {
				rename( $moved['file'], $target_file );
			}
		}

		// Initialize WordPress filesystem for permission setting.
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Set proper permissions using WordPress filesystem.
		$wp_filesystem->chmod( $target_file, FS_CHMOD_FILE );

		// Get file info.
		$file_size = filesize( $target_file );
		$mime_type = wp_check_filetype( $target_file );

		return array(
			'file_name' => $unique_filename,
			'file_path' => str_replace( $upload_dir['basedir'], '', $target_file ),
			'file_size' => $file_size,
			'mime_type' => $mime_type['type'] ? $mime_type['type'] : 'application/octet-stream',
		);
	}

	/**
	 * Create evidence database record
	 *
	 * @since    1.0.0
	 * @param    array    $file_data    File upload data.
	 * @param    array    $metadata     Evidence metadata.
	 * @return   int|WP_Error           Evidence ID on success, WP_Error on failure.
	 */
	private static function create_evidence_record( $file_data, $metadata ) {
		global $wpdb;

		// Determine evidence type from file extension.
		$extension = strtolower( pathinfo( $file_data['file_name'], PATHINFO_EXTENSION ) );
		$evidence_type = self::get_evidence_type_from_extension( $extension );

		$evidence_data = array(
			'case_id'         => absint( $metadata['case_id'] ),
			'report_id'       => isset( $metadata['report_id'] ) ? absint( $metadata['report_id'] ) : null,
			'activity_id'     => isset( $metadata['activity_id'] ) ? absint( $metadata['activity_id'] ) : null,
			'file_name'       => $file_data['file_name'],
			'file_path'       => $file_data['file_path'],
			'file_type'       => $extension,
			'file_size'       => $file_data['file_size'],
			'mime_type'       => $file_data['mime_type'],
			'evidence_type'   => isset( $metadata['evidence_type'] ) ? sanitize_text_field( $metadata['evidence_type'] ) : $evidence_type,
			'title'           => isset( $metadata['title'] ) ? sanitize_text_field( $metadata['title'] ) : null,
			'description'     => isset( $metadata['description'] ) ? sanitize_textarea_field( $metadata['description'] ) : null,
			'capture_date'    => isset( $metadata['capture_date'] ) ? sanitize_text_field( $metadata['capture_date'] ) : null,
			'capture_location' => isset( $metadata['capture_location'] ) ? sanitize_text_field( $metadata['capture_location'] ) : null,
			'equipment_used'  => isset( $metadata['equipment_used'] ) ? sanitize_text_field( $metadata['equipment_used'] ) : null,
			'analysis_notes'  => isset( $metadata['analysis_notes'] ) ? sanitize_textarea_field( $metadata['analysis_notes'] ) : null,
			'is_key_evidence' => isset( $metadata['is_key_evidence'] ) ? absint( $metadata['is_key_evidence'] ) : 0,
			'uploaded_by'     => get_current_user_id(),
			'date_uploaded'   => current_time( 'mysql' ),
		);

		$format = array(
			'%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s',
			'%s', '%s', '%s', '%s', '%d', '%d', '%s',
		);

		$result = $wpdb->insert(
			$wpdb->prefix . 'paradb_evidence',
			$evidence_data,
			$format
		);

		if ( false === $result ) {
			// Clean up uploaded file.
			$upload_dir = wp_upload_dir();
			$file_path = $upload_dir['basedir'] . $file_data['file_path'];
			if ( file_exists( $file_path ) ) {
				unlink( $file_path );
			}
			return new WP_Error( 'db_insert_error', __( 'Failed to create evidence record.', 'wp-paradb' ) );
		}

		$evidence_id = $wpdb->insert_id;

		do_action( 'wp_paradb_evidence_uploaded', $evidence_id, $evidence_data );

		return $evidence_id;
	}

	/**
	 * Get evidence type from file extension
	 *
	 * @since    1.0.0
	 * @param    string    $extension    File extension.
	 * @return   string                  Evidence type.
	 */
	private static function get_evidence_type_from_extension( $extension ) {
		$image_types = array( 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff' );
		$audio_types = array( 'mp3', 'wav', 'ogg', 'flac', 'm4a' );
		$video_types = array( 'mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv' );
		$document_types = array( 'pdf', 'doc', 'docx', 'txt', 'rtf' );

		if ( in_array( $extension, $image_types, true ) ) {
			return 'photo';
		} elseif ( in_array( $extension, $audio_types, true ) ) {
			return 'audio';
		} elseif ( in_array( $extension, $video_types, true ) ) {
			return 'video';
		} elseif ( in_array( $extension, $document_types, true ) ) {
			return 'document';
		}

		return 'other';
	}

	/**
	 * Get evidence by ID
	 *
	 * @since    1.0.0
	 * @param    int    $evidence_id    Evidence ID.
	 * @return   object|null            Evidence object or null if not found.
	 */
	public static function get_evidence( $evidence_id ) {
		global $wpdb;

		$evidence_id = absint( $evidence_id );

		if ( 0 === $evidence_id ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}paradb_evidence WHERE evidence_id = %d",
			$evidence_id
		) );
	}

	/**
	 * Get evidence files with filters
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments.
	 * @return   array             Array of evidence objects.
	 */
	public static function get_evidence_files( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'case_id'       => 0,
			'report_id'     => 0,
			'activity_id'   => 0,
			'evidence_type' => '',
			'orderby'       => 'date_uploaded',
			'order'         => 'DESC',
			'limit'         => 50,
			'offset'        => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		if ( $args['case_id'] > 0 ) {
			$where[] = 'case_id = %d';
			$where_values[] = $args['case_id'];
		}

		if ( $args['report_id'] > 0 ) {
			$where[] = 'report_id = %d';
			$where_values[] = $args['report_id'];
		}

		if ( $args['activity_id'] > 0 ) {
			$where[] = 'activity_id = %d';
			$where_values[] = $args['activity_id'];
		}

		if ( ! empty( $args['evidence_type'] ) ) {
			$where[] = 'evidence_type = %s';
			$where_values[] = $args['evidence_type'];
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT * FROM {$wpdb->prefix}paradb_evidence WHERE {$where_clause}";

		$allowed_orderby = array( 'evidence_id', 'date_uploaded', 'file_name', 'evidence_type' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'date_uploaded';
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
	 * Delete evidence file
	 *
	 * @since    1.0.0
	 * @param    int    $evidence_id    Evidence ID.
	 * @return   bool|WP_Error          True on success, WP_Error on failure.
	 */
	public static function delete_evidence( $evidence_id ) {
		global $wpdb;

		$evidence_id = absint( $evidence_id );

		if ( 0 === $evidence_id ) {
			return new WP_Error( 'invalid_evidence_id', __( 'Invalid evidence ID.', 'wp-paradb' ) );
		}

		// Get evidence record.
		$evidence = self::get_evidence( $evidence_id );
		if ( ! $evidence ) {
			return new WP_Error( 'evidence_not_found', __( 'Evidence not found.', 'wp-paradb' ) );
		}

		// Delete physical file.
		$upload_dir = wp_upload_dir();
		$file_path = $upload_dir['basedir'] . $evidence->file_path;
		if ( file_exists( $file_path ) ) {
			unlink( $file_path );
		}

		// Delete database record.
		$result = $wpdb->delete(
			$wpdb->prefix . 'paradb_evidence',
			array( 'evidence_id' => $evidence_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_delete_error', __( 'Failed to delete evidence.', 'wp-paradb' ) );
		}

		do_action( 'wp_paradb_evidence_deleted', $evidence_id );

		return true;
	}

	/**
	 * Get evidence file URL
	 *
	 * @since    1.0.0
	 * @param    object    $evidence    Evidence object.
	 * @return   string                 File URL.
	 */
	public static function get_evidence_url( $evidence ) {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . $evidence->file_path;
	}
}