<?php
/**
 * Database schema and table management
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
 * Handle database schema creation and updates
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Database {

	/**
	 * Create or update database tables
	 *
	 * @since    1.0.0
	 */
	public static function create_tables() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		$table_prefix = $wpdb->prefix . 'paradb_';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Cases table - Main investigation cases
		$sql_cases = "CREATE TABLE {$table_prefix}cases (
			case_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			case_number varchar(50) NOT NULL,
			case_name varchar(200) NOT NULL,
			case_status varchar(50) NOT NULL DEFAULT 'open',
			case_type varchar(50) NOT NULL DEFAULT 'investigation',
			client_id bigint(20) unsigned DEFAULT NULL,
			location_id bigint(20) unsigned DEFAULT NULL,
			location_name varchar(200) DEFAULT NULL,
			location_address varchar(255) DEFAULT NULL,
			location_city varchar(100) DEFAULT NULL,
			location_state varchar(50) DEFAULT NULL,
			location_zip varchar(20) DEFAULT NULL,
			location_country varchar(100) DEFAULT 'United States',
			latitude decimal(10,8) DEFAULT NULL,
			longitude decimal(11,8) DEFAULT NULL,
			case_description longtext DEFAULT NULL,
			phenomena_types text DEFAULT NULL,
			case_priority varchar(20) DEFAULT 'normal',
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			date_closed datetime DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL,
			assigned_to bigint(20) unsigned DEFAULT NULL,
			is_published tinyint(1) NOT NULL DEFAULT 0,
			publish_date datetime DEFAULT NULL,
			view_count bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (case_id),
			KEY case_number (case_number),
			KEY case_status (case_status),
			KEY client_id (client_id),
			KEY location_id (location_id),
			KEY created_by (created_by),
			KEY assigned_to (assigned_to),
			KEY is_published (is_published),
			KEY date_created (date_created)
		) $charset_collate;";

		// Reports table - Investigation reports and logs
		$sql_reports = "CREATE TABLE {$table_prefix}reports (
			report_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			case_id bigint(20) unsigned NOT NULL,
			activity_id bigint(20) unsigned DEFAULT NULL,
			location_id bigint(20) unsigned DEFAULT NULL,
			report_title varchar(200) NOT NULL,
			report_type varchar(50) NOT NULL DEFAULT 'report',
			report_date datetime NOT NULL,
			report_content longtext NOT NULL,
			report_summary text DEFAULT NULL,
			investigator_id bigint(20) unsigned NOT NULL,
			is_published tinyint(1) NOT NULL DEFAULT 0,
			publish_date datetime DEFAULT NULL,
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (report_id),
			KEY case_id (case_id),
			KEY activity_id (activity_id),
			KEY location_id (location_id),
			KEY investigator_id (investigator_id),
			KEY report_type (report_type),
			KEY report_date (report_date),
			KEY is_published (is_published)
		) $charset_collate;";

		// Activities table - Investigation activities and logs
		$sql_activities = "CREATE TABLE {$table_prefix}activities (
			activity_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			case_id bigint(20) unsigned NOT NULL,
			location_id bigint(20) unsigned DEFAULT NULL,
			activity_title varchar(200) NOT NULL,
			activity_type varchar(50) NOT NULL DEFAULT 'investigation',
			activity_date datetime NOT NULL,
			activity_content longtext NOT NULL,
			activity_summary text DEFAULT NULL,
			investigator_id bigint(20) unsigned NOT NULL,
			weather_conditions varchar(200) DEFAULT NULL,
			moon_phase varchar(50) DEFAULT NULL,
			temperature varchar(50) DEFAULT NULL,
			astrological_data text DEFAULT NULL,
			geomagnetic_data text DEFAULT NULL,
			equipment_used text DEFAULT NULL,
			evidence_collected text DEFAULT NULL,
			phenomena_observed text DEFAULT NULL,
			duration_minutes int(11) DEFAULT NULL,
			participants text DEFAULT NULL,
			is_published tinyint(1) NOT NULL DEFAULT 0,
			publish_date datetime DEFAULT NULL,
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (activity_id),
			KEY case_id (case_id),
			KEY location_id (location_id),
			KEY investigator_id (investigator_id),
			KEY activity_type (activity_type),
			KEY activity_date (activity_date),
			KEY is_published (is_published)
		) $charset_collate;";

		// Clients table - People requesting investigations
		$sql_clients = "CREATE TABLE {$table_prefix}clients (
			client_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			first_name varchar(100) NOT NULL,
			last_name varchar(100) NOT NULL,
			email varchar(100) DEFAULT NULL,
			phone varchar(30) DEFAULT NULL,
			address varchar(255) DEFAULT NULL,
			city varchar(100) DEFAULT NULL,
			state varchar(50) DEFAULT NULL,
			zip varchar(20) DEFAULT NULL,
			country varchar(100) DEFAULT 'United States',
			preferred_contact varchar(50) DEFAULT 'email',
			notes longtext DEFAULT NULL,
			consent_to_publish tinyint(1) NOT NULL DEFAULT 0,
			anonymize_data tinyint(1) NOT NULL DEFAULT 1,
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			created_by bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (client_id),
			KEY email (email),
			KEY created_by (created_by)
		) $charset_collate;";

		// Evidence files table
		$sql_evidence = "CREATE TABLE {$table_prefix}evidence (
			evidence_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			case_id bigint(20) unsigned NOT NULL,
			report_id bigint(20) unsigned DEFAULT NULL,
			activity_id bigint(20) unsigned DEFAULT NULL,
			location_id bigint(20) unsigned DEFAULT NULL,
			file_name varchar(255) NOT NULL,
			file_path varchar(500) NOT NULL,
			file_type varchar(50) NOT NULL,
			file_size bigint(20) unsigned NOT NULL,
			mime_type varchar(100) NOT NULL,
			evidence_type varchar(50) NOT NULL DEFAULT 'other',
			title varchar(200) DEFAULT NULL,
			description text DEFAULT NULL,
			capture_date datetime DEFAULT NULL,
			capture_location varchar(200) DEFAULT NULL,
			equipment_used varchar(200) DEFAULT NULL,
			analysis_notes text DEFAULT NULL,
			is_key_evidence tinyint(1) NOT NULL DEFAULT 0,
			uploaded_by bigint(20) unsigned NOT NULL,
			date_uploaded datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (evidence_id),
			KEY case_id (case_id),
			KEY report_id (report_id),
			KEY activity_id (activity_id),
			KEY location_id (location_id),
			KEY evidence_type (evidence_type),
			KEY uploaded_by (uploaded_by)
		) $charset_collate;";

		// Case notes table - General notes and comments
		$sql_notes = "CREATE TABLE {$table_prefix}case_notes (
			note_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			case_id bigint(20) unsigned NOT NULL,
			location_id bigint(20) unsigned DEFAULT NULL,
			note_content text NOT NULL,
			note_type varchar(50) NOT NULL DEFAULT 'general',
			is_internal tinyint(1) NOT NULL DEFAULT 1,
			author_id bigint(20) unsigned NOT NULL,
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (note_id),
			KEY case_id (case_id),
			KEY location_id (location_id),
			KEY author_id (author_id),
			KEY note_type (note_type)
		) $charset_collate;";

		// Team members table - Track investigation team assignments
		$sql_team = "CREATE TABLE {$table_prefix}case_team (
			assignment_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			case_id bigint(20) unsigned NOT NULL,
			activity_id bigint(20) unsigned DEFAULT NULL,
			user_id bigint(20) unsigned NOT NULL,
			role varchar(50) NOT NULL DEFAULT 'investigator',
			date_assigned datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			assigned_by bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (assignment_id),
			UNIQUE KEY assignment_unique (case_id, activity_id, user_id),
			KEY case_id (case_id),
			KEY activity_id (activity_id),
			KEY user_id (user_id)
		) $charset_collate;";

		// Locations table - Address book and shared locations
		$sql_locations = "CREATE TABLE {$table_prefix}locations (
			location_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			location_name varchar(200) NOT NULL,
			address varchar(255) DEFAULT NULL,
			city varchar(100) DEFAULT NULL,
			state varchar(50) DEFAULT NULL,
			zip varchar(20) DEFAULT NULL,
			country varchar(100) DEFAULT 'United States',
			latitude decimal(10,8) DEFAULT NULL,
			longitude decimal(11,8) DEFAULT NULL,
			location_notes text DEFAULT NULL,
			is_public tinyint(1) NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL,
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (location_id),
			KEY location_name (location_name),
			KEY created_by (created_by),
			KEY is_public (is_public)
		) $charset_collate;";

		// Relationships table - Link any two objects together
		$sql_relationships = "CREATE TABLE {$table_prefix}relationships (
			relationship_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			from_id bigint(20) unsigned NOT NULL,
			from_type varchar(50) NOT NULL,
			to_id bigint(20) unsigned NOT NULL,
			to_type varchar(50) NOT NULL,
			relationship_type varchar(50) NOT NULL,
			notes text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL,
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (relationship_id),
			KEY from_object (from_type, from_id),
			KEY to_object (to_type, to_id),
			KEY relationship_type (relationship_type)
		) $charset_collate;";

		// Field Logs table - Quick field entries
		$sql_field_logs = "CREATE TABLE {$table_prefix}field_logs (
			log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			case_id bigint(20) unsigned NOT NULL,
			activity_id bigint(20) unsigned DEFAULT NULL,
			investigator_id bigint(20) unsigned NOT NULL,
			log_content text NOT NULL,
			latitude decimal(10,8) DEFAULT NULL,
			longitude decimal(11,8) DEFAULT NULL,
			file_url varchar(500) DEFAULT NULL,
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (log_id),
			KEY case_id (case_id),
			KEY activity_id (activity_id),
			KEY investigator_id (investigator_id),
			KEY date_created (date_created)
		) $charset_collate;";

		// Witness accounts table - For public submissions
		$sql_witnesses = "CREATE TABLE {$table_prefix}witness_accounts (
			account_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned DEFAULT NULL,
			case_id bigint(20) unsigned NOT NULL DEFAULT 0,
			location_id bigint(20) unsigned DEFAULT NULL,
			account_email varchar(255) NOT NULL,
			account_name varchar(255) DEFAULT NULL,
			account_phone varchar(50) DEFAULT NULL,
			account_address text DEFAULT NULL,
			incident_date date NOT NULL,
			incident_time time DEFAULT NULL,
			incident_location varchar(255) NOT NULL,
			incident_description text NOT NULL,
			phenomena_types text NOT NULL,
			witnesses_present int(11) DEFAULT NULL,
			witness_names text DEFAULT NULL,
			previous_experiences tinyint(1) DEFAULT 0,
			previous_details text DEFAULT NULL,
			consent_status varchar(50) NOT NULL DEFAULT 'pending',
			consent_anonymize tinyint(1) DEFAULT 0,
			allow_publish tinyint(1) DEFAULT 0,
			allow_followup tinyint(1) DEFAULT 1,
			is_private tinyint(1) DEFAULT 0,
			privacy_accepted tinyint(1) DEFAULT 0,
			privacy_accepted_date datetime DEFAULT NULL,
			status varchar(50) NOT NULL DEFAULT 'pending',
			ip_address varchar(100) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			date_submitted datetime NOT NULL,
			date_modified datetime DEFAULT NULL,
			admin_notes text DEFAULT NULL,
			PRIMARY KEY  (account_id),
			KEY user_id (user_id),
			KEY case_id (case_id),
			KEY location_id (location_id),
			KEY status (status),
			KEY consent_status (consent_status),
			KEY date_submitted (date_submitted)
		) $charset_collate;";

		// Execute table creation
		dbDelta( $sql_cases );
		dbDelta( $sql_reports );
		dbDelta( $sql_activities );
		dbDelta( $sql_clients );
		dbDelta( $sql_evidence );
		dbDelta( $sql_notes );
		dbDelta( $sql_team );
		dbDelta( $sql_locations );
		dbDelta( $sql_relationships );
		dbDelta( $sql_field_logs );
		dbDelta( $sql_witnesses );

		// Store database version
		update_option( 'wp_paradb_db_version', '1.6.0' );
	}

	/**
	 * Drop all plugin tables
	 *
	 * @since    1.0.0
	 */
	public static function drop_tables() {
		global $wpdb;

		$table_prefix = $wpdb->prefix . 'paradb_';

		// Table names are hardcoded and safe - no user input.
		$tables = array(
			'case_team',
			'case_notes',
			'evidence',
			'witness_accounts',
			'field_logs',
			'relationships',
			'reports',
			'activities',
			'cases',
			'clients',
			'locations',
		);

		foreach ( $tables as $table ) {
			// Table name is constructed from WordPress prefix and hardcoded values only.
			$table_name = esc_sql( $table_prefix . $table );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is sanitized with esc_sql and constructed from safe values.
			$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
		}

		delete_option( 'wp_paradb_db_version' );
	}

	/**
	 * Get table name with prefix
	 *
	 * @since    1.0.0
	 * @param    string    $table    Table name without prefix.
	 * @return   string              Full table name with WordPress and plugin prefix.
	 */
	public static function get_table_name( $table ) {
		global $wpdb;
		return $wpdb->prefix . 'paradb_' . $table;
	}
}