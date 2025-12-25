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
			KEY created_by (created_by),
			KEY assigned_to (assigned_to),
			KEY is_published (is_published),
			KEY date_created (date_created)
		) $charset_collate;";

		// Reports table - Investigation reports and logs
		$sql_reports = "CREATE TABLE {$table_prefix}reports (
			report_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			case_id bigint(20) unsigned NOT NULL,
			report_title varchar(200) NOT NULL,
			report_type varchar(50) NOT NULL DEFAULT 'investigation',
			report_date datetime NOT NULL,
			report_content longtext NOT NULL,
			report_summary text DEFAULT NULL,
			investigator_id bigint(20) unsigned NOT NULL,
			weather_conditions varchar(200) DEFAULT NULL,
			moon_phase varchar(50) DEFAULT NULL,
			temperature varchar(50) DEFAULT NULL,
			equipment_used text DEFAULT NULL,
			evidence_collected text DEFAULT NULL,
			phenomena_observed text DEFAULT NULL,
			duration_minutes int(11) DEFAULT NULL,
			participants text DEFAULT NULL,
			is_published tinyint(1) NOT NULL DEFAULT 0,
			publish_date datetime DEFAULT NULL,
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (report_id),
			KEY case_id (case_id),
			KEY investigator_id (investigator_id),
			KEY report_type (report_type),
			KEY report_date (report_date),
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
			KEY evidence_type (evidence_type),
			KEY uploaded_by (uploaded_by)
		) $charset_collate;";

		// Case notes table - General notes and comments
		$sql_notes = "CREATE TABLE {$table_prefix}case_notes (
			note_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			case_id bigint(20) unsigned NOT NULL,
			note_content text NOT NULL,
			note_type varchar(50) NOT NULL DEFAULT 'general',
			is_internal tinyint(1) NOT NULL DEFAULT 1,
			author_id bigint(20) unsigned NOT NULL,
			date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (note_id),
			KEY case_id (case_id),
			KEY author_id (author_id),
			KEY note_type (note_type)
		) $charset_collate;";

		// Team members table - Track investigation team assignments
		$sql_team = "CREATE TABLE {$table_prefix}case_team (
			assignment_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			case_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			role varchar(50) NOT NULL DEFAULT 'investigator',
			date_assigned datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			assigned_by bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (assignment_id),
			UNIQUE KEY case_user (case_id, user_id),
			KEY case_id (case_id),
			KEY user_id (user_id)
		) $charset_collate;";

		// Witness accounts table - For public submissions
		$sql_witnesses = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}paradb_witness_accounts (
			account_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
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
			privacy_accepted tinyint(1) DEFAULT 0,
			privacy_accepted_date datetime DEFAULT NULL,
			status varchar(50) NOT NULL DEFAULT 'pending',
			ip_address varchar(100) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			date_submitted datetime NOT NULL,
			date_modified datetime DEFAULT NULL,
			admin_notes text DEFAULT NULL,
			PRIMARY KEY (account_id),
			KEY user_id (user_id),
			KEY status (status),
			KEY consent_status (consent_status),
			KEY date_submitted (date_submitted)
		) {$charset_collate};";

		// Execute table creation
		dbDelta( $sql_cases );
		dbDelta( $sql_reports );
		dbDelta( $sql_clients );
		dbDelta( $sql_evidence );
		dbDelta( $sql_notes );
		dbDelta( $sql_team );
		dbDelta( $sql_witnesses );

		// Store database version
		update_option( 'wp_paradb_db_version', '1.0.0' );
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
			'reports',
			'cases',
			'clients',
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