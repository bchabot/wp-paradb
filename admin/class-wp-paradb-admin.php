<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/admin
 * @author            Brian Chabot <bchabot@gmail.com>
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/admin
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action( 'wp_ajax_paradb_fetch_environmental_data', array( $this, 'ajax_fetch_environmental_data' ) );
		add_action( 'wp_ajax_paradb_submit_log_chat', array( $this, 'ajax_submit_log_chat' ) );
		add_action( 'wp_ajax_paradb_get_log_chat', array( $this, 'ajax_get_log_chat' ) );
		add_action( 'wp_ajax_paradb_assign_team_member', array( $this, 'ajax_assign_team_member' ) );
		add_action( 'wp_ajax_paradb_remove_team_member', array( $this, 'ajax_remove_team_member' ) );
		add_action( 'wp_ajax_paradb_get_all_logs_live', array( $this, 'ajax_get_all_logs_live' ) );
		add_action( 'wp_ajax_paradb_update_log', array( $this, 'ajax_update_log' ) );
		add_action( 'wp_ajax_paradb_delete_log', array( $this, 'ajax_delete_log' ) );
		add_action( 'wp_ajax_paradb_get_linkable_objects', array( $this, 'ajax_get_linkable_objects' ) );
		add_action( 'wp_ajax_paradb_add_relationship', array( $this, 'ajax_add_relationship' ) );
		add_action( 'wp_ajax_paradb_delete_relationship', array( $this, 'ajax_delete_relationship' ) );
		add_action( 'wp_ajax_paradb_search_locations', array( $this, 'ajax_search_locations' ) );
		add_action( 'wp_ajax_nopriv_paradb_search_locations', array( $this, 'ajax_search_locations' ) );
		add_action( 'admin_init', array( $this, 'handle_maintenance_actions' ) );
		add_action( 'admin_init', array( $this, 'upgrade_check' ) );
	}

	/**
	 * Check if database needs upgrade.
	 */
	public function upgrade_check() {
		$db_version = get_option( 'wp_paradb_db_version', '0.0.0' );
		if ( version_compare( $db_version, $this->version, '<' ) ) {
			require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-database.php';
			WP_ParaDB_Database::create_tables();
		}
	}

	/**
	 * AJAX handler for live log viewer (global)
	 */
	public function ajax_get_all_logs_live() {
		check_ajax_referer( 'paradb_log_viewer_nonce', 'nonce' );

		if ( ! current_user_can( 'paradb_view_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';

		$last_id         = absint( $_POST['last_id'] );
		$case_id         = isset( $_POST['case_id'] ) ? absint( $_POST['case_id'] ) : 0;
		$activity_id     = isset( $_POST['activity_id'] ) ? absint( $_POST['activity_id'] ) : 0;
		$investigator_id = isset( $_POST['investigator_id'] ) ? absint( $_POST['investigator_id'] ) : 0;

		global $wpdb;
		$table = $wpdb->prefix . 'paradb_field_logs';
		
		$where = array( $wpdb->prepare( 'l.log_id > %d', $last_id ) );
		if ( $case_id ) {
			$where[] = $wpdb->prepare( 'l.case_id = %d', $case_id );
		}
		if ( $activity_id ) {
			$where[] = $wpdb->prepare( 'l.activity_id = %d', $activity_id );
		}
		if ( $investigator_id ) {
			$where[] = $wpdb->prepare( 'l.investigator_id = %d', $investigator_id );
		}

		$where_clause = implode( ' AND ', $where );

		$query = "SELECT l.*, u.display_name FROM {$table} l 
			 JOIN {$wpdb->users} u ON l.investigator_id = u.ID
			 WHERE {$where_clause} 
			 ORDER BY l.date_created ASC LIMIT 50";

		$results = $wpdb->get_results( $query );
		$logs = array();

		foreach ( $results as $row ) {
			$context = '';
			$case = WP_ParaDB_Case_Handler::get_case( $row->case_id );
			if ( $case ) $context .= 'Case: ' . $case->case_number . '<br>';
			if ( $row->activity_id ) {
				$act = WP_ParaDB_Activity_Handler::get_activity( $row->activity_id );
				if ( $act ) $context .= 'Act: ' . $act->activity_title;
			}

			$logs[] = array(
				'log_id'    => $row->log_id,
				'user_name' => $row->display_name,
				'datetime'  => gmdate( 'Y-m-d H:i:s', strtotime( $row->date_created ) ),
				'context'   => $context,
				'content'   => wp_kses_post( $row->log_content ),
				'file_url'  => $row->file_url,
			);
		}

		wp_send_json_success( array( 'logs' => $logs ) );
	}

	/**
	 * AJAX handler for submitting log chat entry
	 */
	public function ajax_submit_log_chat() {
		check_admin_referer( 'paradb_submit_log', 'paradb_log_nonce' );

		if ( ! current_user_can( 'paradb_view_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-evidence-handler.php';

		$log_data = array(
			'case_id'     => absint( $_POST['case_id'] ),
			'activity_id' => isset( $_POST['activity_id'] ) ? absint( $_POST['activity_id'] ) : 0,
			'log_content' => sanitize_textarea_field( $_POST['log_content'] ),
			'latitude'    => ( isset( $_POST['latitude'] ) && '' !== $_POST['latitude'] ) ? floatval( $_POST['latitude'] ) : null,
			'longitude'   => ( isset( $_POST['longitude'] ) && '' !== $_POST['longitude'] ) ? floatval( $_POST['longitude'] ) : null,
		);

		// Handle file upload if present
		if ( ! empty( $_FILES['log_file']['name'] ) ) {
			$file_result = WP_ParaDB_Evidence_Handler::upload_evidence( $_FILES['log_file'], array(
				'case_id'     => $log_data['case_id'],
				'activity_id' => $log_data['activity_id'],
				'title'       => 'Log Attachment - ' . current_time( 'mysql' ),
			) );

			if ( ! is_wp_error( $file_result ) ) {
				$log_data['file_url'] = WP_ParaDB_Evidence_Handler::get_evidence_url( WP_ParaDB_Evidence_Handler::get_evidence( $file_result ) );
			}
		}

		$result = WP_ParaDB_Field_Log_Handler::create_log( $log_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'log_id' => $result ) );
	}

	/**
	 * AJAX handler for getting log chat messages
	 */
	public function ajax_get_log_chat() {
		check_ajax_referer( 'paradb_chat_nonce', 'nonce' );

		if ( ! current_user_can( 'paradb_view_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';

		$activity_id = absint( $_POST['activity_id'] );
		$last_id     = absint( $_POST['last_id'] );
		$limit       = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 20;

		global $wpdb;
		$table = $wpdb->prefix . 'paradb_field_logs';
		
		// If last_id is 0, we want the LAST $limit messages.
		// If last_id > 0, we want messages AFTER last_id.
		if ( $last_id > 0 ) {
			$query = $wpdb->prepare(
				"SELECT l.*, u.display_name FROM {$table} l 
				 JOIN {$wpdb->users} u ON l.investigator_id = u.ID
				 WHERE l.activity_id = %d AND l.log_id > %d 
				 ORDER BY l.date_created ASC",
				$activity_id,
				$last_id
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT * FROM (
					SELECT l.*, u.display_name FROM {$table} l 
					JOIN {$wpdb->users} u ON l.investigator_id = u.ID
					WHERE l.activity_id = %d 
					ORDER BY l.date_created DESC LIMIT %d
				) AS sub ORDER BY date_created ASC",
				$activity_id,
				$limit
			);
		}

		$results = $wpdb->get_results( $query );
		$logs = array();
		$current_user_id = get_current_user_id();

		foreach ( $results as $row ) {
			$logs[] = array(
				'log_id'          => $row->log_id,
				'investigator_id' => $row->investigator_id,
				'is_own'          => (int) $row->investigator_id === $current_user_id,
				'user_name'       => $row->display_name,
				'content'         => wpautop( esc_html( $row->log_content ) ),
				'file_url'        => $row->file_url,
				'time'            => gmdate( 'H:i', strtotime( $row->date_created ) ),
				'datetime'        => gmdate( 'M j, H:i', strtotime( $row->date_created ) ),
			);
		}

		wp_send_json_success( array( 'logs' => $logs ) );
	}

	/**
	 * AJAX handler for updating a log entry
	 */
	public function ajax_update_log() {
		check_ajax_referer( 'paradb_log_nonce', 'nonce' );

		if ( ! current_user_can( 'paradb_view_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';

		$log_id = absint( $_POST['log_id'] );
		$data = array(
			'log_content' => sanitize_textarea_field( $_POST['log_content'] ),
			'latitude'    => isset( $_POST['latitude'] ) ? $_POST['latitude'] : '',
			'longitude'   => isset( $_POST['longitude'] ) ? $_POST['longitude'] : '',
		);

		$result = WP_ParaDB_Field_Log_Handler::update_log( $log_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX handler for deleting a log entry
	 */
	public function ajax_delete_log() {
		check_ajax_referer( 'paradb_log_nonce', 'nonce' );

		if ( ! current_user_can( 'paradb_view_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';

		$log_id = absint( $_POST['log_id'] );
		$result = WP_ParaDB_Field_Log_Handler::delete_log( $log_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete log entry.', 'wp-paradb' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX handler for assigning team member to case
	 */
	public function ajax_assign_team_member() {
		check_ajax_referer( 'paradb_team_nonce', 'nonce' );

		if ( ! current_user_can( 'paradb_assign_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';

		$case_id = absint( $_POST['case_id'] );
		$user_id = absint( $_POST['user_id'] );
		$role = sanitize_text_field( $_POST['role'] );

		$result = WP_ParaDB_Case_Handler::assign_team_member( $case_id, $user_id, $role );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX handler for removing team member from case
	 */
	public function ajax_remove_team_member() {
		check_ajax_referer( 'paradb_team_nonce', 'nonce' );

		if ( ! current_user_can( 'paradb_assign_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';

		$case_id = absint( $_POST['case_id'] );
		$user_id = absint( $_POST['user_id'] );

		$result = WP_ParaDB_Case_Handler::remove_team_member( $case_id, $user_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success();
	}

	/**
	 * Handle Maintenance form actions (Backup/Restore/Reset)
	 */
	public function handle_maintenance_actions() {
		if ( ! isset( $_POST['paradb_maintenance_action'] ) ) return;
		
		check_admin_referer( 'paradb_maintenance_nonce', 'maintenance_nonce' );

		if ( ! current_user_can( 'paradb_manage_settings' ) ) {
			wp_die( __( 'Unauthorized.', 'wp-paradb' ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-maintenance-handler.php';

		$action = sanitize_text_field( $_POST['paradb_maintenance_action'] );

		switch ( $action ) {
			case 'backup':
				$data = WP_ParaDB_Maintenance_Handler::export_data();
				$filename = 'paradb-backup-' . date( 'Y-m-d-His' ) . '.json';
				header( 'Content-Type: application/json' );
				header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
				echo $data;
				exit;

			case 'restore':
				if ( ! empty( $_FILES['restore_file']['tmp_name'] ) ) {
					$json = file_get_contents( $_FILES['restore_file']['tmp_name'] );
					$result = WP_ParaDB_Maintenance_Handler::import_data( $json );
					if ( is_wp_error( $result ) ) {
						add_settings_error( 'paradb_messages', 'restore_error', $result->get_error_message(), 'error' );
					} else {
						add_settings_error( 'paradb_messages', 'restore_success', __( 'Data restored successfully.', 'wp-paradb' ), 'updated' );
					}
				}
				break;

			case 'reset':
				WP_ParaDB_Maintenance_Handler::reset_all();
				add_settings_error( 'paradb_messages', 'reset_success', __( 'All data and settings have been reset.', 'wp-paradb' ), 'updated' );
				break;
		}
	}

	/**
	 * AJAX handler for fetching environmental data
	 */
	public function ajax_fetch_environmental_data() {
		check_ajax_referer( 'paradb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'paradb_view_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		$lat = isset( $_POST['lat'] ) ? floatval( $_POST['lat'] ) : 0;
		$lng = isset( $_POST['lng'] ) ? floatval( $_POST['lng'] ) : 0;
		$datetime = isset( $_POST['datetime'] ) ? sanitize_text_field( $_POST['datetime'] ) : '';
		$case_id = isset( $_POST['case_id'] ) ? absint( $_POST['case_id'] ) : 0;

		// Fallback to Case coordinates if Activity coordinates are 0
		if ( ( ! $lat || ! $lng ) && $case_id ) {
			require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
			$case = WP_ParaDB_Case_Handler::get_case( $case_id );
			if ( $case && $case->latitude && $case->longitude ) {
				$lat = $case->latitude;
				$lng = $case->longitude;
			}
		}

		if ( ! $lat || ! $lng || ! $datetime ) {
			wp_send_json_error( array( 'message' => __( 'Missing location or date.', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-environmental-fetcher.php';
		$data = WP_ParaDB_Environmental_Fetcher::fetch_all( $lat, $lng, $datetime );

		wp_send_json_success( $data );
	}

	/**
	 * AJAX handler for getting linkable objects for relationships
	 */
	public function ajax_get_linkable_objects() {
		check_ajax_referer( 'paradb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'paradb_view_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
		$objects = array();

		global $wpdb;

		switch ( $type ) {
			case 'case':
				$results = $wpdb->get_results( "SELECT case_id as id, case_number, case_name FROM {$wpdb->prefix}paradb_cases ORDER BY date_created DESC LIMIT 100" );
				foreach ( $results as $row ) {
					$objects[] = array( 'id' => $row->id, 'label' => $row->case_number . ' - ' . $row->case_name );
				}
				break;
			case 'activity':
				$results = $wpdb->get_results( "SELECT activity_id as id, activity_title, activity_date FROM {$wpdb->prefix}paradb_activities ORDER BY activity_date DESC LIMIT 100" );
				foreach ( $results as $row ) {
					$objects[] = array( 'id' => $row->id, 'label' => $row->activity_title . ' (' . date( 'Y-m-d', strtotime( $row->activity_date ) ) . ')' );
				}
				break;
			case 'report':
				$results = $wpdb->get_results( "SELECT report_id as id, report_title, report_date FROM {$wpdb->prefix}paradb_reports ORDER BY report_date DESC LIMIT 100" );
				foreach ( $results as $row ) {
					$objects[] = array( 'id' => $row->id, 'label' => $row->report_title . ' (' . date( 'Y-m-d', strtotime( $row->report_date ) ) . ')' );
				}
				break;
			case 'location':
				$results = $wpdb->get_results( "SELECT location_id as id, location_name FROM {$wpdb->prefix}paradb_locations ORDER BY location_name ASC LIMIT 100" );
				foreach ( $results as $row ) {
					$objects[] = array( 'id' => $row->id, 'label' => $row->location_name );
				}
				break;
			case 'witness':
				$results = $wpdb->get_results( "SELECT account_id as id, account_name, incident_location FROM {$wpdb->prefix}paradb_witness_accounts ORDER BY date_submitted DESC LIMIT 100" );
				foreach ( $results as $row ) {
					$objects[] = array( 'id' => $row->id, 'label' => ( $row->account_name ? $row->account_name : 'Anonymous' ) . ' - ' . $row->incident_location );
				}
				break;
			case 'evidence':
				$results = $wpdb->get_results( "SELECT evidence_id as id, title, file_name FROM {$wpdb->prefix}paradb_evidence ORDER BY date_uploaded DESC LIMIT 100" );
				foreach ( $results as $row ) {
					$objects[] = array( 'id' => $row->id, 'label' => ( $row->title ? $row->title : $row->file_name ) );
				}
				break;
		}

		wp_send_json_success( array( 'objects' => $objects ) );
	}

	/**
	 * AJAX handler for adding a relationship
	 */
	public function ajax_add_relationship() {
		check_ajax_referer( 'paradb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'paradb_view_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-relationship-handler.php';

		$data = array(
			'from_id'           => absint( $_POST['from_id'] ),
			'from_type'         => sanitize_text_field( $_POST['from_type'] ),
			'to_id'             => absint( $_POST['to_id'] ),
			'to_type'           => sanitize_text_field( $_POST['to_type'] ),
			'relationship_type' => sanitize_text_field( $_POST['relationship_type'] ),
			'notes'             => sanitize_textarea_field( $_POST['notes'] ),
		);

		$result = WP_ParaDB_Relationship_Handler::create_relationship( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX handler for deleting a relationship
	 */
	public function ajax_delete_relationship() {
		check_ajax_referer( 'paradb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'paradb_view_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-relationship-handler.php';

		$rel_id = absint( $_POST['rel_id'] );
		$result = WP_ParaDB_Relationship_Handler::delete_relationship( $rel_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete relationship.', 'wp-paradb' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX handler for searching locations
	 */
	public function ajax_search_locations() {
		// Only check admin nonce if user is logged in and trying to search all locations.
		if ( current_user_can( 'paradb_view_cases' ) ) {
			check_ajax_referer( 'paradb_admin_nonce', 'nonce' );
			$is_admin_search = true;
		} else {
			// Public search, only show public locations.
			$is_admin_search = false;
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
		
		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		global $wpdb;
		$where = "location_name LIKE %s";
		if ( ! $is_admin_search ) {
			$where .= " AND is_public = 1";
		}

		$results = $wpdb->get_results( $wpdb->prepare( 
			"SELECT * FROM {$wpdb->prefix}paradb_locations WHERE {$where} ORDER BY location_name ASC LIMIT 10",
			'%' . $wpdb->esc_like( $term ) . '%'
		) );

		$locations = array();
		foreach ( $results as $row ) {
			$locations[] = array(
				'label' => $row->location_name,
				'value' => $row->location_name,
				'data'  => $row
			);
		}

		wp_send_json_success( $locations );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/wp-paradb-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$options = get_option( 'wp_paradb_options', array() );
		$provider = isset( $options['map_provider'] ) ? $options['map_provider'] : 'google';
		$api_key = isset( $options['google_maps_api_key'] ) ? $options['google_maps_api_key'] : '';

		if ( 'google' === $provider && ! empty( $api_key ) ) {
			wp_enqueue_script(
				'google-maps',
				'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=places',
				array(),
				null,
				true
			);
		} elseif ( 'osm' === $provider ) {
			wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
			wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
		}

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/wp-paradb-admin.js',
			array( 'jquery', 'jquery-ui-autocomplete' ),
			$this->version,
			true // Load in footer for better performance.
		);

		wp_localize_script( $this->plugin_name, 'paradb_admin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'paradb_admin_nonce' ),
			'i18n'     => array(
				'preview_title' => __( 'Environmental Data Preview', 'wp-paradb' ),
				'preview_desc'  => __( 'Review the fetched environmental data below. Click "Apply Data" to update the form fields.', 'wp-paradb' ),
				'weather'       => __( 'Weather', 'wp-paradb' ),
				'moon_phase'    => __( 'Moon Phase', 'wp-paradb' ),
				'astrology'     => __( 'Astrology', 'wp-paradb' ),
				'geomagnetic'   => __( 'Geomagnetic', 'wp-paradb' ),
				'cancel'        => __( 'Cancel', 'wp-paradb' ),
				'apply_data'    => __( 'Apply Data', 'wp-paradb' ),
				'fetch_data'    => __( 'Auto-fetch Environmental Data', 'wp-paradb' ),
				'data_applied'  => __( 'Environmental data has been applied to the form.', 'wp-paradb' ),
			)
		) );
		
		// Pass provider settings to JS
		wp_localize_script( $this->plugin_name, 'paradb_maps', array(
			'provider' => $provider,
			'locationiq_key' => isset( $options['locationiq_api_key'] ) ? $options['locationiq_api_key'] : '',
			'units' => isset( $options['units'] ) ? $options['units'] : 'metric'
		) );
	}

	/**
	 * Render breadcrumb navigation for admin pages.
	 *
	 * @since    1.0.0
	 */
	public static function render_breadcrumbs() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		if ( ! $page ) return;

		$crumbs = array();
		$crumbs[] = array( 'title' => __( 'Dashboard', 'wp-paradb' ), 'url' => admin_url( 'admin.php?page=wp-paradb' ) );

		switch ( $page ) {
			case 'wp-paradb-cases':
				$crumbs[] = array( 'title' => __( 'Cases', 'wp-paradb' ), 'url' => '' );
				break;
			case 'wp-paradb-case-edit':
				$crumbs[] = array( 'title' => __( 'Cases', 'wp-paradb' ), 'url' => admin_url( 'admin.php?page=wp-paradb-cases' ) );
				if ( isset( $_GET['case_id'] ) ) {
					require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
					$case = WP_ParaDB_Case_Handler::get_case( absint( $_GET['case_id'] ) );
					$crumbs[] = array( 'title' => $case ? $case->case_number : __( 'Edit Case', 'wp-paradb' ), 'url' => '' );
				} else {
					$crumbs[] = array( 'title' => __( 'Add New Case', 'wp-paradb' ), 'url' => '' );
				}
				break;
			case 'wp-paradb-activities':
				$crumbs[] = array( 'title' => __( 'Activities', 'wp-paradb' ), 'url' => '' );
				break;
			case 'wp-paradb-reports':
				$crumbs[] = array( 'title' => __( 'Reports', 'wp-paradb' ), 'url' => '' );
				break;
			case 'wp-paradb-witnesses':
				$crumbs[] = array( 'title' => __( 'Witnesses', 'wp-paradb' ), 'url' => '' );
				break;
			case 'wp-paradb-settings':
				$crumbs[] = array( 'title' => __( 'Settings', 'wp-paradb' ), 'url' => '' );
				break;
		}

		if ( count( $crumbs ) > 1 ) {
			echo '<nav class="paradb-breadcrumbs" style="margin: 10px 0; color: #666; font-size: 13px;">';
			foreach ( $crumbs as $i => $crumb ) {
				if ( $i > 0 ) echo ' <span class="dashicons dashicons-arrow-right-alt2" style="font-size: 14px; width: 14px; height: 14px; margin-top: 2px;"></span> ';
				if ( $crumb['url'] ) {
					echo '<a href="' . esc_url( $crumb['url'] ) . '" style="text-decoration: none; color: #2271b1;">' . esc_html( $crumb['title'] ) . '</a>';
				} else {
					echo '<span>' . esc_html( $crumb['title'] ) . '</span>';
				}
			}
			echo '</nav>';
		}
	}

	/**
	 * Check if current user has required capabilities.
	 *
	 * @since    1.0.0
	 * @param    string    $capability    Required capability.
	 * @return   bool                     True if user has capability, false otherwise.
	 */
	public function check_user_capability( $capability = 'manage_options' ) {
		return current_user_can( $capability );
	}

	/**
	 * Verify nonce for security.
	 *
	 * @since    1.0.0
	 * @param    string    $nonce_action    Nonce action.
	 * @param    string    $nonce_name      Nonce field name.
	 * @param    string    $method          Request method ('POST' or 'GET').
	 * @return   bool                       True if nonce is valid, false otherwise.
	 */
	public function verify_nonce( $nonce_action, $nonce_name = '_wpnonce', $method = 'POST' ) {
		$request_data = 'GET' === strtoupper( $method ) ? $_GET : $_POST;

		if ( ! isset( $request_data[ $nonce_name ] ) ) {
			return false;
		}

		return wp_verify_nonce( sanitize_text_field( wp_unslash( $request_data[ $nonce_name ] ) ), $nonce_action );
	}

	/**
	 * Sanitize input data.
	 *
	 * @since    1.0.0
	 * @param    mixed     $input    Input data to sanitize.
	 * @param    string    $type     Type of sanitization (text, email, url, etc.).
	 * @return   mixed               Sanitized data.
	 */
	public function sanitize_input( $input, $type = 'text' ) {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $input );
			case 'url':
				return esc_url_raw( $input );
			case 'textarea':
				return sanitize_textarea_field( $input );
			case 'key':
				return sanitize_key( $input );
			case 'text':
			default:
				return sanitize_text_field( $input );
		}
	}

	/**
	 * Render the relationship management section
	 *
	 * @since    1.3.0
	 */
	public static function render_relationship_section( $object_id, $object_type ) {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-relationships.php';
	}

}
