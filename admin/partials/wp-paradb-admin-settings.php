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

require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-settings.php';

// Handle form submission.
if ( isset( $_POST['save_settings'] ) && check_admin_referer( 'save_paradb_settings', 'settings_nonce' ) ) {
	$options = WP_ParaDB_Settings::get_settings();
	
	// General settings.
	if ( isset( $_POST['case_number_format'] ) ) {
		$options['case_number_format'] = sanitize_text_field( wp_unslash( $_POST['case_number_format'] ) );
	}
	$options['require_client_consent'] = isset( $_POST['require_client_consent'] ) ? 1 : 0;
	if ( isset( $_POST['items_per_page'] ) ) {
		$options['items_per_page'] = absint( $_POST['items_per_page'] );
	}
	
	// Witness submission settings.
	$options['allow_public_submissions'] = isset( $_POST['allow_public_submissions'] ) ? 1 : 0;
	$options['moderate_submissions'] = isset( $_POST['moderate_submissions'] ) ? 1 : 0;
	
	// File upload settings.
	if ( isset( $_POST['max_upload_size'] ) ) {
		$options['max_upload_size'] = absint( $_POST['max_upload_size'] );
	}
	if ( isset( $_POST['allowed_file_types'] ) ) {
		$options['allowed_file_types'] = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_POST['allowed_file_types'] ) ) ) );
	}
	
	// Map settings.
	$options['enable_geolocation'] = isset( $_POST['enable_geolocation'] ) ? 1 : 0;
	$options['enable_moon_phase'] = isset( $_POST['enable_moon_phase'] ) ? 1 : 0;
	if ( isset( $_POST['map_provider'] ) ) {
		$options['map_provider'] = sanitize_text_field( wp_unslash( $_POST['map_provider'] ) );
	}
	if ( isset( $_POST['units'] ) ) {
		$options['units'] = sanitize_text_field( wp_unslash( $_POST['units'] ) );
	}
	if ( isset( $_POST['google_maps_api_key'] ) ) {
		$options['google_maps_api_key'] = sanitize_text_field( wp_unslash( $_POST['google_maps_api_key'] ) );
	}
	if ( isset( $_POST['locationiq_api_key'] ) ) {
		$options['locationiq_api_key'] = sanitize_text_field( wp_unslash( $_POST['locationiq_api_key'] ) );
	}
	if ( isset( $_POST['weatherapi_api_key'] ) ) {
		$options['weatherapi_api_key'] = sanitize_text_field( wp_unslash( $_POST['weatherapi_api_key'] ) );
	}
	if ( isset( $_POST['freeastroapi_api_key'] ) ) {
		$options['freeastroapi_api_key'] = sanitize_text_field( wp_unslash( $_POST['freeastroapi_api_key'] ) );
	}
	
	// Privacy & Redaction.
	if ( isset( $_POST['privacy_policy_text'] ) ) {
		$options['privacy_policy_text'] = wp_kses_post( wp_unslash( $_POST['privacy_policy_text'] ) );
	}
	if ( isset( $_POST['redaction_keywords'] ) ) {
		$options['redaction_keywords'] = sanitize_textarea_field( wp_unslash( $_POST['redaction_keywords'] ) );
	}
	$options['redact_witness_names'] = isset( $_POST['redact_witness_names'] ) ? 1 : 0;
	$options['redact_investigator_names'] = isset( $_POST['redact_investigator_names'] ) ? 1 : 0;
	if ( isset( $_POST['redaction_placeholder'] ) ) {
		$options['redaction_placeholder'] = sanitize_text_field( wp_unslash( $_POST['redaction_placeholder'] ) );
	}
	$options['delete_data_on_uninstall'] = isset( $_POST['delete_data_on_uninstall'] ) ? 1 : 0;

	// Update options.
	WP_ParaDB_Settings::update_settings( $options );
	
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'wp-paradb' ) . '</p></div>';
}

// Handle maintenance actions.
if ( isset( $_POST['paradb_maintenance_action'] ) && check_admin_referer( 'paradb_maintenance_nonce', 'maintenance_nonce' ) ) {
	require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-maintenance-handler.php';
	$action = sanitize_text_field( $_POST['paradb_maintenance_action'] );
	
	if ( 'backup' === $action ) {
		$data = WP_ParaDB_Maintenance_Handler::export_data();
		$filename = 'paradb-backup-' . date( 'Y-m-d-His' ) . '.json';
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo $data;
		exit;
	} elseif ( 'restore' === $action ) {
		if ( ! empty( $_FILES['restore_file']['tmp_name'] ) ) {
			$json = file_get_contents( $_FILES['restore_file']['tmp_name'] );
			$result = WP_ParaDB_Maintenance_Handler::import_data( $json );
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Data restored successfully.', 'wp-paradb' ) . '</p></div>';
			}
		}
	} elseif ( 'reset' === $action ) {
		WP_ParaDB_Maintenance_Handler::reset_all();
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'All ParaDB data and settings have been reset.', 'wp-paradb' ) . '</p></div>';
	}
}

// Get current options.
$options = WP_ParaDB_Settings::get_settings();
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
?>

<div class="wrap">
	<h1><?php esc_html_e( 'ParaDB Settings', 'wp-paradb' ); ?></h1>
	
	<h2 class="nav-tab-wrapper">
		<a href="?page=wp-paradb-settings&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'wp-paradb' ); ?></a>
		<a href="?page=wp-paradb-settings&tab=witnesses" class="nav-tab <?php echo 'witnesses' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Witnesses', 'wp-paradb' ); ?></a>
		<a href="?page=wp-paradb-settings&tab=maps" class="nav-tab <?php echo 'maps' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Maps & APIs', 'wp-paradb' ); ?></a>
		<a href="?page=wp-paradb-settings&tab=privacy" class="nav-tab <?php echo 'privacy' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Privacy Policy', 'wp-paradb' ); ?></a>
		<a href="?page=wp-paradb-settings&tab=advanced" class="nav-tab <?php echo 'advanced' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Advanced', 'wp-paradb' ); ?></a>
	</h2>

	<form method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field( 'save_paradb_settings', 'settings_nonce' ); ?>
		
		<?php if ( 'general' === $active_tab ) : ?>
			<h2 class="title"><?php esc_html_e( 'General Settings', 'wp-paradb' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="case_number_format"><?php esc_html_e( 'Case Number Format', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="case_number_format" id="case_number_format" class="regular-text" value="<?php echo esc_attr( $options['case_number_format'] ?? 'CASE-%Y-%ID%' ); ?>">
						<p class="description">
							<?php esc_html_e( 'Use placeholders: %Y% (year), %M% (month), %D% (day), %ID% (case ID)', 'wp-paradb' ); ?><br>
							<?php esc_html_e( 'Example: CASE-%Y%-%ID% produces CASE-2024-0001', 'wp-paradb' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="items_per_page"><?php esc_html_e( 'Items Per Page', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="number" name="items_per_page" id="items_per_page" min="1" max="100" value="<?php echo esc_attr( $options['items_per_page'] ?? 20 ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="units"><?php esc_html_e( 'Measurement Units', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<select name="units" id="units">
							<option value="metric" <?php selected( $options['units'] ?? 'metric', 'metric' ); ?>><?php esc_html_e( 'Metric (Celsius, km/h)', 'wp-paradb' ); ?></option>
							<option value="imperial" <?php selected( $options['units'] ?? 'metric', 'imperial' ); ?>><?php esc_html_e( 'Imperial (Fahrenheit, mph)', 'wp-paradb' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Defaults', 'wp-paradb' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="require_client_consent" value="1" <?php checked( $options['require_client_consent'] ?? 1, 1 ); ?>>
							<?php esc_html_e( 'Require client consent before publishing case information', 'wp-paradb' ); ?>
						</label>
					</td>
				</tr>
			</table>
		<?php endif; ?>

		<?php if ( 'witnesses' === $active_tab ) : ?>
			<h2 class="title"><?php esc_html_e( 'Witness Submission Settings', 'wp-paradb' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Public Submissions', 'wp-paradb' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="allow_public_submissions" value="1" <?php checked( $options['allow_public_submissions'] ?? 1, 1 ); ?>>
							<?php esc_html_e( 'Allow public witness account submissions', 'wp-paradb' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Moderation', 'wp-paradb' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="moderate_submissions" value="1" <?php checked( $options['moderate_submissions'] ?? 1, 1 ); ?>>
							<?php esc_html_e( 'Hold witness submissions for moderation', 'wp-paradb' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="witness_success_message"><?php esc_html_e( 'Success Message', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<textarea name="witness_success_message" id="witness_success_message" rows="3" class="large-text"><?php echo esc_textarea( $options['witness_success_message'] ?? '' ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Message shown to witnesses after successful submission.', 'wp-paradb' ); ?></p>
					</td>
				</tr>
			</table>
		<?php endif; ?>

		<?php if ( 'maps' === $active_tab ) : ?>
			<h2 class="title"><?php esc_html_e( 'Map & API Settings', 'wp-paradb' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="map_provider"><?php esc_html_e( 'Map Provider', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<select name="map_provider" id="map_provider">
							<option value="google" <?php selected( $options['map_provider'] ?? 'google', 'google' ); ?>><?php esc_html_e( 'Google Maps (Requires API Key)', 'wp-paradb' ); ?></option>
							<option value="osm" <?php selected( $options['map_provider'] ?? 'google', 'osm' ); ?>><?php esc_html_e( 'OpenStreetMap / Leaflet (Free)', 'wp-paradb' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="google_maps_api_key"><?php esc_html_e( 'Google Maps API Key', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="password" name="google_maps_api_key" id="google_maps_api_key" class="regular-text" value="<?php echo esc_attr( $options['google_maps_api_key'] ?? '' ); ?>">
						<p class="description">
							<?php esc_html_e( 'Required for Maps and Address Auto-suggest.', 'wp-paradb' ); ?>
							<a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank"><?php esc_html_e( 'Get Google Maps Key', 'wp-paradb' ); ?></a>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="locationiq_api_key"><?php esc_html_e( 'LocationIQ API Key', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="password" name="locationiq_api_key" id="locationiq_api_key" class="regular-text" value="<?php echo esc_attr( $options['locationiq_api_key'] ?? '' ); ?>">
						<p class="description">
							<?php esc_html_e( 'Optional: Used for free geocoding with OpenStreetMap.', 'wp-paradb' ); ?>
							<a href="https://locationiq.com/" target="_blank"><?php esc_html_e( 'Get LocationIQ Key', 'wp-paradb' ); ?></a>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="weatherapi_api_key"><?php esc_html_e( 'WeatherAPI.com API Key', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="password" name="weatherapi_api_key" id="weatherapi_api_key" class="regular-text" value="<?php echo esc_attr( $options['weatherapi_api_key'] ?? '' ); ?>">
						<p class="description">
							<a href="https://www.weatherapi.com/signup.aspx" target="_blank"><?php esc_html_e( 'Get WeatherAPI Key', 'wp-paradb' ); ?></a>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="freeastroapi_api_key"><?php esc_html_e( 'FreeAstroAPI Key', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="password" name="freeastroapi_api_key" id="freeastroapi_api_key" class="regular-text" value="<?php echo esc_attr( $options['freeastroapi_api_key'] ?? '' ); ?>">
						<p class="description">
							<a href="https://freeastroapi.com/" target="_blank"><?php esc_html_e( 'Get FreeAstroAPI Key', 'wp-paradb' ); ?></a>
						</p>
					</td>
				</tr>
			</table>
		<?php endif; ?>

		<?php if ( 'privacy' === $active_tab ) : ?>
			<h2 class="title"><?php esc_html_e( 'Privacy & Redaction', 'wp-paradb' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="privacy_policy_text"><?php esc_html_e( 'Witness Privacy Policy', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<?php
						wp_editor(
							$options['privacy_policy_text'] ?? '',
							'privacy_policy_text',
							array(
								'textarea_name' => 'privacy_policy_text',
								'textarea_rows' => 15,
								'media_buttons' => false,
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="redaction_keywords"><?php esc_html_e( 'Global Redaction Keywords', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<textarea name="redaction_keywords" id="redaction_keywords" rows="4" class="large-text"><?php echo esc_textarea( $options['redaction_keywords'] ?? '' ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Comma-separated list of terms to automatically redact.', 'wp-paradb' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Automatic Redaction', 'wp-paradb' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="redact_witness_names" value="1" <?php checked( $options['redact_witness_names'] ?? 1, 1 ); ?>>
							<?php esc_html_e( 'Redact witness names from public view', 'wp-paradb' ); ?>
						</label>
					</td>
				</tr>
			</table>
		<?php endif; ?>

		<?php if ( 'advanced' === $active_tab ) : ?>
			<h2 class="title"><?php esc_html_e( 'Advanced Tools & Database', 'wp-paradb' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Data Removal', 'wp-paradb' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="delete_data_on_uninstall" id="delete_data_on_uninstall" value="1" <?php checked( $options['delete_data_on_uninstall'] ?? 0, 1 ); ?>>
							<span style="color: #dc3232; font-weight: bold;"><?php esc_html_e( 'Permanently delete ALL data on plugin de-activation', 'wp-paradb' ); ?></span>
						</label>
						<p class="description" style="color: #dc3232;">
							<?php esc_html_e( 'WARNING: If enabled, all cases, witness reports, and evidence will be PERMANENTLY DELETED whenever the plugin is deactivated (disabled) from the WordPress Plugins page. This cannot be undone.', 'wp-paradb' ); ?>
						</p>
						<script>
						jQuery(document).ready(function($) {
							$('#delete_data_on_uninstall').on('change', function() {
								if (this.checked) {
									if (!confirm('<?php echo esc_js( __( 'Are you absolutely sure? Enabling this will cause ALL your data to be deleted whenever the plugin is deactivated. This cannot be undone.', 'wp-paradb' ) ); ?>')) {
										this.checked = false;
									}
								}
							});
						});
						</script>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Status Info', 'wp-paradb' ); ?></th>
					<td>
						<p><strong><?php esc_html_e( 'Database Version:', 'wp-paradb' ); ?></strong> <?php echo esc_html( get_option( 'wp_paradb_db_version' ) ); ?></p>
						<p><strong><?php esc_html_e( 'Plugin Version:', 'wp-paradb' ); ?></strong> <?php echo esc_html( WP_PARADB_VERSION ); ?></p>
					</td>
				</tr>
			</table>
		<?php endif; ?>

		<p class="submit">
			<input type="submit" name="save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'wp-paradb' ); ?>">
		</p>
	</form>

	<?php if ( 'advanced' === $active_tab ) : ?>
		<hr>
		<h3><?php esc_html_e( 'Maintenance', 'wp-paradb' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Backup Data', 'wp-paradb' ); ?></th>
				<td>
					<form method="post" action="">
						<?php wp_nonce_field( 'paradb_maintenance_nonce', 'maintenance_nonce' ); ?>
						<input type="hidden" name="paradb_maintenance_action" value="backup">
						<input type="submit" class="button" value="<?php esc_attr_e( 'Download Backup (JSON)', 'wp-paradb' ); ?>">
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
						<input type="submit" class="button" value="<?php esc_attr_e( 'Reset All Data', 'wp-paradb' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Delete EVERYTHING? This cannot be undone.', 'wp-paradb' ); ?>');">
					</form>
				</td>
			</tr>
		</table>
	<?php endif; ?>
</div>