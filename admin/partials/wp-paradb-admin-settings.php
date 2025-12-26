<?php
/**
 * Admin settings page view
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/admin/partials
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user capabilities.
if ( ! current_user_can( 'paradb_manage_settings' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-paradb' ) );
}

// Handle form submission.
if ( isset( $_POST['save_settings'] ) && check_admin_referer( 'save_paradb_settings', 'settings_nonce' ) ) {
	$options = get_option( 'wp_paradb_options', array() );
	
	// General settings.
	$options['case_number_format'] = isset( $_POST['case_number_format'] ) ? sanitize_text_field( wp_unslash( $_POST['case_number_format'] ) ) : 'CASE-%Y-%ID%';
	$options['require_client_consent'] = isset( $_POST['require_client_consent'] ) ? 1 : 0;
	$options['items_per_page'] = isset( $_POST['items_per_page'] ) ? absint( $_POST['items_per_page'] ) : 20;
	
	// Witness submission settings.
	$options['allow_public_submissions'] = isset( $_POST['allow_public_submissions'] ) ? 1 : 0;
	$options['moderate_submissions'] = isset( $_POST['moderate_submissions'] ) ? 1 : 0;
	
	// File upload settings.
	$options['max_upload_size'] = isset( $_POST['max_upload_size'] ) ? absint( $_POST['max_upload_size'] ) : 10485760;
	$options['allowed_file_types'] = isset( $_POST['allowed_file_types'] ) ? array_map( 'sanitize_text_field', explode( ',', wp_unslash( $_POST['allowed_file_types'] ) ) ) : array();
	
	// Map settings.
	$options['enable_geolocation'] = isset( $_POST['enable_geolocation'] ) ? 1 : 0;
	$options['enable_moon_phase'] = isset( $_POST['enable_moon_phase'] ) ? 1 : 0;
	$options['map_provider'] = isset( $_POST['map_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['map_provider'] ) ) : 'google';
	$options['google_maps_api_key'] = isset( $_POST['google_maps_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['google_maps_api_key'] ) ) : '';
	$options['locationiq_api_key'] = isset( $_POST['locationiq_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['locationiq_api_key'] ) ) : '';
	$options['weatherapi_api_key'] = isset( $_POST['weatherapi_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['weatherapi_api_key'] ) ) : '';
	$options['freeastroapi_api_key'] = isset( $_POST['freeastroapi_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['freeastroapi_api_key'] ) ) : '';
	
	// Privacy & Redaction.
	$options['redaction_keywords'] = isset( $_POST['redaction_keywords'] ) ? sanitize_textarea_field( wp_unslash( $_POST['redaction_keywords'] ) ) : '';
	$options['redact_witness_names'] = isset( $_POST['redact_witness_names'] ) ? 1 : 0;
	$options['redact_investigator_names'] = isset( $_POST['redact_investigator_names'] ) ? 1 : 0;
	$options['redaction_placeholder'] = isset( $_POST['redaction_placeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['redaction_placeholder'] ) ) : '[REDACTED]';
	$options['delete_data_on_uninstall'] = isset( $_POST['delete_data_on_uninstall'] ) ? 1 : 0;

	// Update options.
	update_option( 'wp_paradb_options', $options );
	
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully.', 'wp-paradb' ) . '</p></div>';
}

// Get current options.
$options = get_option( 'wp_paradb_options', array() );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'ParaDB Settings', 'wp-paradb' ); ?></h1>
	
	<form method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field( 'save_paradb_settings', 'settings_nonce' ); ?>
		<?php settings_errors( 'paradb_messages' ); ?>
		
		<h2 class="title"><?php esc_html_e( 'General Settings', 'wp-paradb' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="case_number_format"><?php esc_html_e( 'Case Number Format', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<input type="text" name="case_number_format" id="case_number_format" class="regular-text" value="<?php echo esc_attr( isset( $options['case_number_format'] ) ? $options['case_number_format'] : 'CASE-%Y-%ID%' ); ?>">
					<p class="description">
						<?php esc_html_e( 'Use placeholders: %Y% (year), %M% (month), %D% (day), %ID% (case ID)', 'wp-paradb' ); ?><br>
						<?php esc_html_e( 'Example: CASE-%Y-%ID% produces CASE-2024-0001', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="items_per_page"><?php esc_html_e( 'Items Per Page', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<input type="number" name="items_per_page" id="items_per_page" min="1" max="100" value="<?php echo esc_attr( isset( $options['items_per_page'] ) ? $options['items_per_page'] : 20 ); ?>">
					<p class="description"><?php esc_html_e( 'Number of items to display per page in listings', 'wp-paradb' ); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row"><?php esc_html_e( 'Client Consent', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="require_client_consent" value="1" <?php checked( isset( $options['require_client_consent'] ) ? $options['require_client_consent'] : 1, 1 ); ?>>
						<?php esc_html_e( 'Require client consent before publishing case information', 'wp-paradb' ); ?>
					</label>
				</td>
			</tr>
		</table>
		
		<h2 class="title"><?php esc_html_e( 'Witness Submission Settings', 'wp-paradb' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Public Submissions', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="allow_public_submissions" value="1" <?php checked( isset( $options['allow_public_submissions'] ) ? $options['allow_public_submissions'] : 1, 1 ); ?>>
						<?php esc_html_e( 'Allow public witness account submissions', 'wp-paradb' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Enable the public witness submission form', 'wp-paradb' ); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row"><?php esc_html_e( 'Moderation', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="moderate_submissions" value="1" <?php checked( isset( $options['moderate_submissions'] ) ? $options['moderate_submissions'] : 1, 1 ); ?>>
						<?php esc_html_e( 'Hold witness submissions for moderation', 'wp-paradb' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Submissions will not be visible until reviewed', 'wp-paradb' ); ?></p>
				</td>
			</tr>
		</table>
		
		<h2 class="title"><?php esc_html_e( 'File Upload Settings', 'wp-paradb' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="max_upload_size"><?php esc_html_e( 'Maximum Upload Size', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<input type="number" name="max_upload_size" id="max_upload_size" min="1048576" max="104857600" step="1048576" value="<?php echo esc_attr( isset( $options['max_upload_size'] ) ? $options['max_upload_size'] : 10485760 ); ?>">
					<p class="description">
						<?php
						printf(
							esc_html__( 'Maximum file size in bytes (Current: %s)', 'wp-paradb' ),
							size_format( isset( $options['max_upload_size'] ) ? $options['max_upload_size'] : 10485760 )
						);
						?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="allowed_file_types"><?php esc_html_e( 'Allowed File Types', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<?php
					$default_types = array( 'jpg', 'jpeg', 'png', 'gif', 'mp3', 'wav', 'ogg', 'mp4', 'avi', 'mov', 'pdf', 'doc', 'docx', 'txt', 'csv' );
					$allowed_types = isset( $options['allowed_file_types'] ) ? $options['allowed_file_types'] : $default_types;
					?>
					<input type="text" name="allowed_file_types" id="allowed_file_types" class="large-text" value="<?php echo esc_attr( implode( ', ', $allowed_types ) ); ?>">
					<p class="description"><?php esc_html_e( 'Comma-separated list of allowed file extensions (e.g., jpg, png, mp3, pdf)', 'wp-paradb' ); ?></p>
				</td>
			</tr>
		</table>
		
		<h2 class="title"><?php esc_html_e( 'Feature Settings', 'wp-paradb' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Geolocation', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_geolocation" value="1" <?php checked( isset( $options['enable_geolocation'] ) ? $options['enable_geolocation'] : 1, 1 ); ?>>
						<?php esc_html_e( 'Enable geolocation features for cases', 'wp-paradb' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Allow storing latitude/longitude coordinates for case locations', 'wp-paradb' ); ?></p>
				</td>
			</tr>
			
			<tr>
				<th scope="row"><?php esc_html_e( 'Moon Phase Tracking', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_moon_phase" value="1" <?php checked( isset( $options['enable_moon_phase'] ) ? $options['enable_moon_phase'] : 1, 1 ); ?>>
						<?php esc_html_e( 'Enable moon phase tracking in reports', 'wp-paradb' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Track lunar phase during investigations', 'wp-paradb' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Map & API Settings', 'wp-paradb' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="map_provider"><?php esc_html_e( 'Map Provider', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<select name="map_provider" id="map_provider">
						<option value="google" <?php selected( isset( $options['map_provider'] ) ? $options['map_provider'] : 'google', 'google' ); ?>><?php esc_html_e( 'Google Maps (Requires API Key)', 'wp-paradb' ); ?></option>
						<option value="osm" <?php selected( isset( $options['map_provider'] ) ? $options['map_provider'] : 'google', 'osm' ); ?>><?php esc_html_e( 'OpenStreetMap / Leaflet (Free)', 'wp-paradb' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="google_maps_api_key"><?php esc_html_e( 'Google Maps API Key', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<input type="password" name="google_maps_api_key" id="google_maps_api_key" class="regular-text" value="<?php echo esc_attr( isset( $options['google_maps_api_key'] ) ? $options['google_maps_api_key'] : '' ); ?>">
					<p class="description">
						<?php esc_html_e( 'Required if Google Maps is selected.', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="locationiq_api_key"><?php esc_html_e( 'LocationIQ API Key', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<input type="password" name="locationiq_api_key" id="locationiq_api_key" class="regular-text" value="<?php echo esc_attr( isset( $options['locationiq_api_key'] ) ? $options['locationiq_api_key'] : '' ); ?>">
					<p class="description">
						<?php esc_html_e( 'Used for free geocoding if OpenStreetMap is selected. (10,000 free requests/day)', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="weatherapi_api_key"><?php esc_html_e( 'WeatherAPI.com API Key', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<input type="password" name="weatherapi_api_key" id="weatherapi_api_key" class="regular-text" value="<?php echo esc_attr( isset( $options['weatherapi_api_key'] ) ? $options['weatherapi_api_key'] : '' ); ?>">
					<p class="description">
						<?php esc_html_e( 'Used for fetching weather and moon phase data. (Free tier available)', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="freeastroapi_api_key"><?php esc_html_e( 'FreeAstroAPI Key', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<input type="password" name="freeastroapi_api_key" id="freeastroapi_api_key" class="regular-text" value="<?php echo esc_attr( isset( $options['freeastroapi_api_key'] ) ? $options['freeastroapi_api_key'] : '' ); ?>">
					<p class="description">
						<?php esc_html_e( 'Used for fetching astrological transit data. (Optional)', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Privacy & Redaction Settings', 'wp-paradb' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="redaction_keywords"><?php esc_html_e( 'Global Redaction Keywords', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<textarea name="redaction_keywords" id="redaction_keywords" rows="4" class="large-text"><?php echo esc_textarea( isset( $options['redaction_keywords'] ) ? $options['redaction_keywords'] : '' ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Comma-separated list of terms to automatically redact from all public displays. (e.g., Sensitive locations, private phone numbers, project secrets)', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Automatic Name Redaction', 'wp-paradb' ); ?></th>
				<td>
					<label style="display: block; margin-bottom: 5px;">
						<input type="checkbox" name="redact_witness_names" value="1" <?php checked( isset( $options['redact_witness_names'] ) ? $options['redact_witness_names'] : 1, 1 ); ?>>
						<?php esc_html_e( 'Automatically redact Witness names from case content', 'wp-paradb' ); ?>
					</label>
					<label style="display: block;">
						<input type="checkbox" name="redact_investigator_names" value="1" <?php checked( isset( $options['redact_investigator_names'] ) ? $options['redact_investigator_names'] : 0, 1 ); ?>>
						<?php esc_html_e( 'Automatically redact Investigator names from case content', 'wp-paradb' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="redaction_placeholder"><?php esc_html_e( 'Redaction Placeholder', 'wp-paradb' ); ?></label>
				</th>
				<td>
					<input type="text" name="redaction_placeholder" id="redaction_placeholder" class="regular-text" value="<?php echo esc_attr( isset( $options['redaction_placeholder'] ) ? $options['redaction_placeholder'] : '[REDACTED]' ); ?>">
					<p class="description">
						<?php esc_html_e( 'The text that will replace redacted terms. (e.g., [REDACTED] or [SENSITIVE])', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Data Removal', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( isset( $options['delete_data_on_uninstall'] ) ? $options['delete_data_on_uninstall'] : 0, 1 ); ?>>
						<span style="color: #dc3232; font-weight: bold;"><?php esc_html_e( 'Permanently delete ALL data and tables when this plugin is uninstalled', 'wp-paradb' ); ?></span>
					</label>
					<p class="description"><?php esc_html_e( 'Warning: This cannot be undone. Keep this unchecked if you want to preserve your data between installations.', 'wp-paradb' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Database Information', 'wp-paradb' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Database Version', 'wp-paradb' ); ?></th>
				<td><?php echo esc_html( get_option( 'wp_paradb_db_version', '1.0.0' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Plugin Version', 'wp-paradb' ); ?></th>
				<td><?php echo esc_html( WP_PARADB_VERSION ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Database Tables', 'wp-paradb' ); ?></th>
				<td>
					<?php
					global $wpdb;
					$tables = array(
						'paradb_cases',
						'paradb_reports',
						'paradb_clients',
						'paradb_evidence',
						'paradb_case_notes',
						'paradb_case_team',
						'paradb_witness_accounts',
					);

					echo '<ul style="margin: 0;">';
					foreach ( $tables as $table ) {
						$full_table_name = $wpdb->prefix . $table;
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking table existence for admin display.
						$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full_table_name ) ) === $full_table_name;
						$status_icon = $exists ? '<span style="color: #46b450;">✓</span>' : '<span style="color: #dc3232;">✗</span>';
						echo '<li>' . wp_kses( $status_icon, array( 'span' => array( 'style' => array() ) ) ) . ' ' . esc_html( $full_table_name ) . '</li>';
					}
					echo '</ul>';
					?>
				</td>
			</tr>
		</table>
		
		<p class="submit">
			<input type="submit" name="save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'wp-paradb' ); ?>">
		</p>
	</form>
	
	<hr>
	
	<h2><?php esc_html_e( 'Advanced Tools', 'wp-paradb' ); ?></h2>
	<p><?php esc_html_e( 'Use these tools with caution. These actions cannot be undone.', 'wp-paradb' ); ?></p>
	
	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Backup Data', 'wp-paradb' ); ?></th>
			<td>
				<form method="post" action="">
					<?php wp_nonce_field( 'paradb_maintenance_nonce', 'maintenance_nonce' ); ?>
					<input type="hidden" name="paradb_maintenance_action" value="backup">
					<input type="submit" class="button" value="<?php esc_attr_e( 'Download Backup (JSON)', 'wp-paradb' ); ?>">
					<p class="description"><?php esc_html_e( 'Export all cases, logs, locations, and settings to a JSON file.', 'wp-paradb' ); ?></p>
				</form>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Restore Data', 'wp-paradb' ); ?></th>
			<td>
				<form method="post" action="" enctype="multipart/form-data">
					<?php wp_nonce_field( 'paradb_maintenance_nonce', 'maintenance_nonce' ); ?>
					<input type="hidden" name="paradb_maintenance_action" value="restore">
					<input type="file" name="restore_file" accept=".json" required>
					<input type="submit" class="button" value="<?php esc_attr_e( 'Restore from Backup', 'wp-paradb' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Warning: This will overwrite all current data. Continue?', 'wp-paradb' ); ?>');">
				</form>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Reset Plugin', 'wp-paradb' ); ?></th>
			<td>
				<form method="post" action="">
					<?php wp_nonce_field( 'paradb_maintenance_nonce', 'maintenance_nonce' ); ?>
					<input type="hidden" name="paradb_maintenance_action" value="reset">
					<input type="submit" class="button" value="<?php esc_attr_e( 'Reset All Data and Settings', 'wp-paradb' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you absolutely sure? This will delete ALL ParaDB data and settings and cannot be undone!', 'wp-paradb' ); ?>');">
					<p class="description"><?php esc_html_e( 'This will remove all plugin data including cases, reports, clients, and evidence files. User roles will be preserved.', 'wp-paradb' ); ?></p>
				</form>
			</td>
		</tr>
	</table>
</div>