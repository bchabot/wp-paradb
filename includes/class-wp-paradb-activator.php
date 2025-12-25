<?php
/**
 * Fired during plugin activation
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/includes
 * @author            Brian Chabot <bchabot@gmail.com>
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Set up database tables, default options, user roles, and any other
	 * initialization tasks required for the plugin.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Check WordPress version compatibility.
		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'WordPress Paranormal Database requires WordPress 5.0 or higher.', 'wp-paradb' ) );
		}

		// Check PHP version compatibility.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'WordPress Paranormal Database requires PHP 7.4 or higher.', 'wp-paradb' ) );
		}

		// Load required classes.
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-database.php';
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-roles.php';

		// Create database tables.
		WP_ParaDB_Database::create_tables();

		// Create user roles and capabilities.
		WP_ParaDB_Roles::create_roles();

		// Set default options.
		self::set_default_options();

		// Create upload directory.
		self::create_upload_directory();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Set default plugin options.
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		$default_options = array(
			'version'                    => WP_PARADB_VERSION,
			'case_number_format'         => 'CASE-%Y%-%ID%',
			'require_client_consent'     => true,
			'allow_public_submissions'   => true,
			'moderate_submissions'       => true,
			'default_case_status'        => 'open',
			'phenomena_types'            => array(
				'Apparition',
				'Audio Phenomena',
				'Cold Spots',
				'Electronic Voice Phenomena (EVP)',
				'Full Body Apparition',
				'Object Movement',
				'Orbs',
				'Phantom Smells',
				'Physical Contact',
				'Shadow Figures',
				'Temperature Changes',
				'Visual Anomalies',
			),
			'case_statuses'              => array(
				'open'       => __( 'Open', 'wp-paradb' ),
				'active'     => __( 'Active Investigation', 'wp-paradb' ),
				'reviewing'  => __( 'Under Review', 'wp-paradb' ),
				'closed'     => __( 'Closed', 'wp-paradb' ),
				'archived'   => __( 'Archived', 'wp-paradb' ),
			),
			'evidence_types'             => array(
				'photo'      => __( 'Photograph', 'wp-paradb' ),
				'audio'      => __( 'Audio Recording', 'wp-paradb' ),
				'video'      => __( 'Video Recording', 'wp-paradb' ),
				'document'   => __( 'Document', 'wp-paradb' ),
				'data'       => __( 'Sensor Data', 'wp-paradb' ),
				'other'      => __( 'Other', 'wp-paradb' ),
			),
			'max_upload_size'            => 10485760, // 10MB
			'allowed_file_types'         => array(
				'jpg', 'jpeg', 'png', 'gif',
				'mp3', 'wav', 'ogg',
				'mp4', 'avi', 'mov',
				'pdf', 'doc', 'docx',
				'txt', 'csv',
			),
			'items_per_page'             => 20,
			'enable_geolocation'         => true,
			'enable_moon_phase'          => true,
			'date_format'                => 'Y-m-d H:i:s',
			'timezone'                   => get_option( 'timezone_string', 'UTC' ),
		);

		add_option( 'wp_paradb_options', $default_options );
	}

	/**
	 * Create upload directory for evidence files.
	 *
	 * @since    1.0.0
	 */
	private static function create_upload_directory() {
		$upload_dir = wp_upload_dir();
		$paradb_dir = $upload_dir['basedir'] . '/paradb-evidence';

		if ( ! file_exists( $paradb_dir ) ) {
			wp_mkdir_p( $paradb_dir );

			// Initialize WordPress filesystem.
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}

			// Create .htaccess file for additional security.
			$htaccess_content = "# Protect ParaDB evidence files\n";
			$htaccess_content .= "<Files *>\n";
			$htaccess_content .= "Order Allow,Deny\n";
			$htaccess_content .= "Deny from all\n";
			$htaccess_content .= "</Files>\n";
			$htaccess_content .= "<FilesMatch '\.(jpg|jpeg|png|gif|mp3|wav|mp4|pdf)$'>\n";
			$htaccess_content .= "Allow from all\n";
			$htaccess_content .= "</FilesMatch>\n";

			$wp_filesystem->put_contents( $paradb_dir . '/.htaccess', $htaccess_content, FS_CHMOD_FILE );

			// Create index.php to prevent directory browsing.
			$wp_filesystem->put_contents( $paradb_dir . '/index.php', '<?php // Silence is golden', FS_CHMOD_FILE );
		}

		// Create subdirectories by year.
		$current_year = gmdate( 'Y' );
		$year_dir = $paradb_dir . '/' . $current_year;
		if ( ! file_exists( $year_dir ) ) {
			wp_mkdir_p( $year_dir );
		}
	}
}