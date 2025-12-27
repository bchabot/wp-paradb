<?php
/**
 * Admin activities management view
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
if ( ! current_user_can( 'paradb_view_cases' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-paradb' ) );
}

// Load required classes.
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';

// Handle form submission for new/edit activity.
if ( isset( $_POST['save_activity'] ) && check_admin_referer( 'save_activity', 'activity_nonce' ) ) {
	$activity_id = isset( $_POST['activity_id'] ) ? absint( $_POST['activity_id'] ) : 0;
	
	$activity_data = array(
		'case_id'            => isset( $_POST['case_id'] ) ? absint( $_POST['case_id'] ) : 0,
		'activity_title'       => isset( $_POST['activity_title'] ) ? sanitize_text_field( wp_unslash( $_POST['activity_title'] ) ) : '',
		'activity_type'        => isset( $_POST['activity_type'] ) ? sanitize_text_field( wp_unslash( $_POST['activity_type'] ) ) : 'investigation',
		'activity_date'        => isset( $_POST['activity_date'] ) ? sanitize_text_field( wp_unslash( $_POST['activity_date'] ) ) : current_time( 'mysql' ),
		'activity_content'     => isset( $_POST['activity_content'] ) ? wp_kses_post( wp_unslash( $_POST['activity_content'] ) ) : '',
		'activity_summary'     => isset( $_POST['activity_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['activity_summary'] ) ) : '',
		'weather_conditions' => isset( $_POST['weather_conditions'] ) ? sanitize_text_field( wp_unslash( $_POST['weather_conditions'] ) ) : '',
		'moon_phase'         => isset( $_POST['moon_phase'] ) ? sanitize_text_field( wp_unslash( $_POST['moon_phase'] ) ) : '',
		'temperature'        => isset( $_POST['temperature'] ) ? sanitize_text_field( wp_unslash( $_POST['temperature'] ) ) : '',
		'astrological_data'  => isset( $_POST['astrological_data'] ) ? sanitize_textarea_field( wp_unslash( $_POST['astrological_data'] ) ) : '',
		'geomagnetic_data'   => isset( $_POST['geomagnetic_data'] ) ? sanitize_textarea_field( wp_unslash( $_POST['geomagnetic_data'] ) ) : '',
		'duration_minutes'   => isset( $_POST['duration_minutes'] ) ? absint( $_POST['duration_minutes'] ) : 0,
		'is_published'       => isset( $_POST['is_published'] ) ? 1 : 0,
	);

	if ( $activity_id > 0 ) {
		$result = WP_ParaDB_Activity_Handler::update_activity( $activity_id, $activity_data );
	} else {
		$result = WP_ParaDB_Activity_Handler::create_activity( $activity_data );
	}

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Activity saved successfully.', 'wp-paradb' ) . '</p></div>';
	}
}

// Get case_id from URL for pre-selection
$pre_case_id = isset( $_GET['case_id'] ) ? absint( $_GET['case_id'] ) : 0;

// Handle delete action.
if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['activity_id'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_activity_' . absint( $_GET['activity_id'] ) ) ) {
		if ( current_user_can( 'paradb_delete_activities' ) ) {
			$result = WP_ParaDB_Activity_Handler::delete_activity( absint( $_GET['activity_id'] ) );
			if ( ! is_wp_error( $result ) ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Activity deleted successfully.', 'wp-paradb' ) . '</p></div>';
			}
		}
	}
}

// Get action.
$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
$activity_id = isset( $_GET['activity_id'] ) ? absint( $_GET['activity_id'] ) : 0;

if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
	// Show form.
	$activity = null;
	if ( 'edit' === $action && $activity_id > 0 ) {
		$activity = WP_ParaDB_Activity_Handler::get_activity( $activity_id );
	}
	
	// Get cases for dropdown.
	$cases = WP_ParaDB_Case_Handler::get_cases( array( 'limit' => 1000 ) );
	?>
	
	<div class="wrap">
		<h1>
			<?php echo 'new' === $action ? esc_html__( 'Add Activity', 'wp-paradb' ) : esc_html__( 'Edit Activity', 'wp-paradb' ); ?>
			<?php if ( $activity ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-log-chat&activity_id=' . $activity_id ) ); ?>" class="page-title-action" target="_blank">
					<span class="dashicons dashicons-format-chat" style="margin-top: 4px;"></span> <?php esc_html_e( 'Log My Actions', 'wp-paradb' ); ?>
				</a>
			<?php endif; ?>
		</h1>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'save_activity', 'activity_nonce' ); ?>
			<?php if ( $activity ) : ?>
				<input type="hidden" name="activity_id" value="<?php echo esc_attr( $activity->activity_id ); ?>">
			<?php endif; ?>
			
			<?php
			// Get case location for data fetching if needed
			$activity_lat = 0;
			$activity_lng = 0;
			if ( $activity ) {
				if ( $activity->location_id ) {
					$loc = WP_ParaDB_Location_Handler::get_location( $activity->location_id );
					if ( $loc ) {
						$activity_lat = $loc->latitude;
						$activity_lng = $loc->longitude;
					}
				} else {
					$case = WP_ParaDB_Case_Handler::get_case( $activity->case_id );
					if ( $case ) {
						$activity_lat = $case->latitude;
						$activity_lng = $case->longitude;
					}
				}
			}
			?>
			<input type="hidden" id="latitude" value="<?php echo esc_attr( $activity_lat ); ?>">
			<input type="hidden" id="longitude" value="<?php echo esc_attr( $activity_lng ); ?>">
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="case_id"><?php esc_html_e( 'Case', 'wp-paradb' ); ?> *</label>
					</th>
					<td>
						<select name="case_id" id="case_id" required class="regular-text">
							<option value=""><?php esc_html_e( 'Select Case', 'wp-paradb' ); ?></option>
							<?php foreach ( $cases as $case ) : 
								$selected_case_id = $activity ? $activity->case_id : $pre_case_id;
								?>
								<option value="<?php echo esc_attr( $case->case_id ); ?>" <?php selected( $selected_case_id, $case->case_id ); ?>>
									<?php echo esc_html( $case->case_number . ' - ' . $case->case_name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="activity_title"><?php esc_html_e( 'Activity Title', 'wp-paradb' ); ?> *</label>
					</th>
					<td>
						<input type="text" name="activity_title" id="activity_title" class="regular-text" value="<?php echo $activity ? esc_attr( $activity->activity_title ) : ''; ?>" required>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="activity_type"><?php esc_html_e( 'Activity Type', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<select name="activity_type" id="activity_type">
							<option value="investigation" <?php selected( $activity ? $activity->activity_type : 'investigation', 'investigation' ); ?>><?php esc_html_e( 'Investigation', 'wp-paradb' ); ?></option>
							<option value="research" <?php selected( $activity ? $activity->activity_type : '', 'research' ); ?>><?php esc_html_e( 'Research', 'wp-paradb' ); ?></option>
							<option value="experiment" <?php selected( $activity ? $activity->activity_type : '', 'experiment' ); ?>><?php esc_html_e( 'Experiment', 'wp-paradb' ); ?></option>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="activity_date"><?php esc_html_e( 'Activity Date', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="datetime-local" name="activity_date" id="activity_date" value="<?php echo $activity ? esc_attr( gmdate( 'Y-m-d\TH:i', strtotime( $activity->activity_date ) ) ) : esc_attr( gmdate( 'Y-m-d\TH:i' ) ); ?>">
					</td>
				</tr>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'Publish Status', 'wp-paradb' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="is_published" value="1" <?php checked( $activity ? $activity->is_published : 0, 1 ); ?>>
							<?php esc_html_e( 'Publish this activity on the public case page', 'wp-paradb' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="activity_content"><?php esc_html_e( 'Activity Content', 'wp-paradb' ); ?> *</label>
					</th>
					<td>
						<?php
						wp_editor(
							$activity ? $activity->activity_content : '',
							'activity_content',
							array(
								'textarea_name' => 'activity_content',
								'textarea_rows' => 15,
								'media_buttons' => false,
							)
						);
						?>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="weather_conditions"><?php esc_html_e( 'Weather Conditions', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="weather_conditions" id="weather_conditions" class="regular-text" value="<?php echo $activity ? esc_attr( $activity->weather_conditions ) : ''; ?>">
						<button type="button" id="fetch-environmental-data" class="button"><?php esc_html_e( 'Auto-fetch Environmental Data', 'wp-paradb' ); ?></button>
						<p class="description"><?php esc_html_e( 'Requires Case/Activity location and date to be set first.', 'wp-paradb' ); ?></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="temperature"><?php esc_html_e( 'Temperature', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="temperature" id="temperature" class="regular-text" value="<?php echo $activity ? esc_attr( $activity->temperature ) : ''; ?>">
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="astrological_data"><?php esc_html_e( 'Astrological Data', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<textarea name="astrological_data" id="astrological_data" rows="3" class="large-text"><?php echo $activity ? esc_textarea( $activity->astrological_data ) : ''; ?></textarea>
						<p class="description"><?php esc_html_e( 'Planetary positions and transits.', 'wp-paradb' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="geomagnetic_data"><?php esc_html_e( 'Geomagnetic Data', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<textarea name="geomagnetic_data" id="geomagnetic_data" rows="3" class="large-text"><?php echo $activity ? esc_textarea( $activity->geomagnetic_data ) : ''; ?></textarea>
						<p class="description"><?php esc_html_e( 'Space weather and geomagnetic activity (e.g. Kp-Index).', 'wp-paradb' ); ?></p>
					</td>
				</tr>

				<tr id="fetch-results-row" style="display: none;">
					<th scope="row"><?php esc_html_e( 'Fetch Results', 'wp-paradb' ); ?></th>
					<td id="fetch-results-container">
						<!-- JS will populate this -->
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="moon_phase"><?php esc_html_e( 'Moon Phase', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<select name="moon_phase" id="moon_phase">
							<option value=""><?php esc_html_e( 'Unknown', 'wp-paradb' ); ?></option>
							<option value="new" <?php selected( $activity ? $activity->moon_phase : '', 'new' ); ?>><?php esc_html_e( 'New Moon', 'wp-paradb' ); ?></option>
							<option value="waxing_crescent" <?php selected( $activity ? $activity->moon_phase : '', 'waxing_crescent' ); ?>><?php esc_html_e( 'Waxing Crescent', 'wp-paradb' ); ?></option>
							<option value="first_quarter" <?php selected( $activity ? $activity->moon_phase : '', 'first_quarter' ); ?>><?php esc_html_e( 'First Quarter', 'wp-paradb' ); ?></option>
							<option value="waxing_gibbous" <?php selected( $activity ? $activity->moon_phase : '', 'waxing_gibbous' ); ?>><?php esc_html_e( 'Waxing Gibbous', 'wp-paradb' ); ?></option>
							<option value="full" <?php selected( $activity ? $activity->moon_phase : '', 'full' ); ?>><?php esc_html_e( 'Full Moon', 'wp-paradb' ); ?></option>
							<option value="waning_gibbous" <?php selected( $activity ? $activity->moon_phase : '', 'waning_gibbous' ); ?>><?php esc_html_e( 'Waning Gibbous', 'wp-paradb' ); ?></option>
							<option value="last_quarter" <?php selected( $activity ? $activity->moon_phase : '', 'last_quarter' ); ?>><?php esc_html_e( 'Last Quarter', 'wp-paradb' ); ?></option>
							<option value="waning_crescent" <?php selected( $activity ? $activity->moon_phase : '', 'waning_crescent' ); ?>><?php esc_html_e( 'Waning Crescent', 'wp-paradb' ); ?></option>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="duration_minutes"><?php esc_html_e( 'Duration (minutes)', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="number" name="duration_minutes" id="duration_minutes" value="<?php echo $activity ? esc_attr( $activity->duration_minutes ) : ''; ?>">
					</td>
				</tr>
			</table>

			<?php if ( $activity && $activity_id > 0 ) : ?>
				<div class="postbox closed">
					<div class="postbox-header">
						<h2 class="hndle">
							<span><?php esc_html_e( 'Activity Field Logs', 'wp-paradb' ); ?></span>
						</h2>
						<div class="handle-actions hide-if-no-js">
							<button type="button" class="handlediv" aria-expanded="false"><span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Activity Field Logs', 'wp-paradb' ); ?></span><span class="toggle-indicator" aria-hidden="true"></span></button>
						</div>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-log-chat&activity_id=' . $activity_id ) ); ?>" class="button button-small" style="float: right; margin: 7px 10px 0 0;" target="_blank">
							<?php esc_html_e( 'Open Mobile Log', 'wp-paradb' ); ?>
						</a>
					</div>
					<div class="inside" style="max-height: 400px; overflow-y: auto;">
						<?php
						require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';
						$logs = WP_ParaDB_Field_Log_Handler::get_logs( array( 'activity_id' => $activity_id, 'order' => 'DESC' ) );
						if ( $logs ) : ?>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th style="width: 150px;"><?php esc_html_e( 'Time', 'wp-paradb' ); ?></th>
										<th style="width: 150px;"><?php esc_html_e( 'Investigator', 'wp-paradb' ); ?></th>
										<th><?php esc_html_e( 'Log Entry', 'wp-paradb' ); ?></th>
										<th style="width: 100px;"><?php esc_html_e( 'Actions', 'wp-paradb' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $logs as $log ) : 
										$inv = get_userdata( $log->investigator_id );
										?>
										<tr id="log-row-<?php echo $log->log_id; ?>" data-id="<?php echo $log->log_id; ?>">
											<td>
												<?php echo esc_html( gmdate( 'Y-m-d H:i:s', strtotime( $log->date_created ) ) ); ?>
												<?php if ( $log->latitude ) : ?>
													<br><a href="#" class="view-log-map" data-lat="<?php echo esc_attr($log->latitude); ?>" data-lng="<?php echo esc_attr($log->longitude); ?>" data-title="<?php echo esc_attr(gmdate( 'Y-m-d H:i:s', strtotime( $log->date_created ) ) . ' - ' . ($inv ? $inv->display_name : '')); ?>" style="font-size: 10px;">[<?php esc_html_e( 'Map', 'wp-paradb' ); ?>]</a>
												<?php endif; ?>
											</td>
											<td><strong><?php echo $inv ? esc_html( $inv->display_name ) : '—'; ?></strong></td>
											<td>
												<div class="log-display">
													<?php echo wp_kses_post( $log->log_content ); ?>
													<?php if ( $log->file_url ) : ?>
														<div style="margin-top: 5px;">
															<?php 
															$is_img = preg_match( '/\.(jpg|jpeg|png|gif)$/i', $log->file_url );
															if ( $is_img ) : ?>
																<a href="<?php echo esc_url( $log->file_url ); ?>" target="_blank">
																	<img src="<?php echo esc_url( $log->file_url ); ?>" style="max-width: 60px; max-height: 60px; border-radius: 2px; border: 1px solid #ddd;">
																</a>
															<?php else : ?>
																<a href="<?php echo esc_url( $log->file_url ); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'View Attachment', 'wp-paradb' ); ?></a>
															<?php endif; ?>
														</div>
													<?php endif; ?>
												</div>
												<div class="log-edit" style="display: none;">
													<textarea class="edit-log-content" style="width: 100%;" rows="3"><?php echo esc_textarea( $log->log_content ); ?></textarea>
													<div style="margin-top: 5px;">
														<button type="button" class="button button-small save-log-edit"><?php esc_html_e( 'Save', 'wp-paradb' ); ?></button>
														<button type="button" class="button button-small cancel-log-edit"><?php esc_html_e( 'Cancel', 'wp-paradb' ); ?></button>
													</div>
												</div>
											</td>
											<td>
												<button type="button" class="button-link edit-log-btn"><?php esc_html_e( 'Edit', 'wp-paradb' ); ?></button> | 
												<button type="button" class="button-link-delete delete-log-btn"><?php esc_html_e( 'Delete', 'wp-paradb' ); ?></button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<script>
							jQuery(document).ready(function($) {
								$('.edit-log-btn').on('click', function() {
									var $row = $(this).closest('tr');
									$row.find('.log-display').hide();
									$row.find('.log-edit').show();
								});

								$('.cancel-log-edit').on('click', function() {
									var $row = $(this).closest('tr');
									$row.find('.log-edit').hide();
									$row.find('.log-display').show();
								});

								$('.save-log-edit').on('click', function() {
									var $row = $(this).closest('tr');
									var logId = $row.data('id');
									var content = $row.find('.edit-log-content').val();

									$.post(ajaxurl, {
										action: 'paradb_update_log',
										log_id: logId,
										log_content: content,
										nonce: '<?php echo wp_create_nonce("paradb_log_nonce"); ?>'
									}, function(res) {
										if (res.success) {
											location.reload();
										} else {
											alert(res.data.message);
										}
									});
								});

								$('.delete-log-btn').on('click', function() {
									if (!confirm('<?php echo esc_js(__("Are you sure you want to delete this log entry?", "wp-paradb")); ?>')) return;
									var $row = $(this).closest('tr');
									var logId = $row.data('id');

									$.post(ajaxurl, {
										action: 'paradb_delete_log',
										log_id: logId,
										nonce: '<?php echo wp_create_nonce("paradb_log_nonce"); ?>'
									}, function(res) {
										if (res.success) {
											$row.remove();
										} else {
											alert(res.data.message);
										}
									});
								});
							});
							</script>
						<?php else : ?>
							<p><?php esc_html_e( 'No field logs recorded for this activity.', 'wp-paradb' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
				<?php WP_ParaDB_Admin::render_relationship_section( $activity_id, 'activity' ); ?>
			<?php endif; ?>
			
			<p class="submit">
				<input type="submit" name="save_activity" class="button button-primary" value="<?php echo 'new' === $action ? esc_attr__( 'Create Activity', 'wp-paradb' ) : esc_attr__( 'Update Activity', 'wp-paradb' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-activities' ) ); ?>" class="button">
					<?php esc_html_e( 'Cancel', 'wp-paradb' ); ?>
				</a>
			</p>
		</form>
	</div>
	
	<div id="paradb-map-modal" style="display:none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
		<div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; height: 80%; position: relative;">
			<span id="close-map-modal" style="position: absolute; right: 10px; top: 5px; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
			<div id="map-canvas" style="width: 100%; height: 100%;"></div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		// Map Logic
		var map;

		function initMap() {
			if (typeof L === 'undefined' && (typeof google === 'undefined' || !google.maps)) {
				console.error('Map library not loaded');
				return false;
			}
			return true;
		}

		function showMapModal(locations) {
			$('#paradb-map-modal').show();
			if (!initMap()) return;

			var provider = '<?php echo esc_js(get_option("wp_paradb_options")["map_provider"] ?? "osm"); ?>';
			
			if (provider === 'osm') {
				if (map && map.remove) map.remove();
				map = L.map('map-canvas');
				L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
					attribution: '© OpenStreetMap contributors'
				}).addTo(map);

				var group = new L.featureGroup();
				locations.forEach(function(loc) {
					var marker = L.marker([loc.lat, loc.lng]).addTo(map).bindPopup(loc.title);
					group.addLayer(marker);
				});
				map.fitBounds(group.getBounds());
			} else if (provider === 'google') {
				var mapOptions = {
					zoom: 12,
					center: new google.maps.LatLng(locations[0].lat, locations[0].lng)
				};
				map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
				var bounds = new google.maps.LatLngBounds();
				locations.forEach(function(loc) {
					var latLng = new google.maps.LatLng(loc.lat, loc.lng);
					var marker = new google.maps.Marker({
						position: latLng,
						map: map,
						title: loc.title
					});
					bounds.extend(latLng);
				});
				if (locations.length > 1) map.fitBounds(bounds);
			}
		}

		$(document).on('click', '.view-log-map', function(e) {
			e.preventDefault();
			var lat = $(this).data('lat');
			var lng = $(this).data('lng');
			var title = $(this).data('title');
			showMapModal([{lat: lat, lng: lng, title: title}]);
		});

		$('#close-map-modal').on('click', function() {
			$('#paradb-map-modal').hide();
		});

		$(window).on('click', function(event) {
			if (event.target.id == 'paradb-map-modal') {
				$('#paradb-map-modal').hide();
			}
		});
	});
	</script>
	<?php
} else {
	// Show list.
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	$per_page = 20;
	
	$args = array(
		'search'  => $search,
		'limit'   => $per_page,
		'offset'  => ( $paged - 1 ) * $per_page,
		'orderby' => 'activity_date',
		'order'   => 'DESC',
	);
	
	$activities = WP_ParaDB_Activity_Handler::get_activities( $args );
	?>
	
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Activities', 'wp-paradb' ); ?></h1>
		
		<?php if ( current_user_can( 'paradb_add_activities' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-activities&action=new' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'wp-paradb' ); ?>
			</a>
		<?php endif; ?>
		
		<hr class="wp-header-end">
		
		<script>
		jQuery(document).ready(function($) {
			$('#location_id').on('change', function() {
				var $selected = $(this).find('option:selected');
				if ($(this).val()) {
					$('#latitude').val($selected.data('lat') || 0);
					$('#longitude').val($selected.data('lng') || 0);
				}
			});
		});
		</script>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'Case', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'Type', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'Date', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'Investigator', 'wp-paradb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $activities ) ) : ?>
					<?php foreach ( $activities as $activity ) : ?>
						<?php
						$case = WP_ParaDB_Case_Handler::get_case( $activity->case_id );
						$investigator = get_userdata( $activity->investigator_id );
						?>
						<tr>
							<td>
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-activities&action=edit&activity_id=' . $activity->activity_id ) ); ?>">
										<?php echo esc_html( $activity->activity_title ); ?>
									</a>
								</strong>
								<div class="row-actions">
									<span class="edit">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-activities&action=edit&activity_id=' . $activity->activity_id ) ); ?>">
											<?php esc_html_e( 'Edit', 'wp-paradb' ); ?>
										</a>
									</span>
									<?php if ( current_user_can( 'paradb_delete_activities' ) ) : ?>
										|
										<span class="delete">
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-activities&action=delete&activity_id=' . $activity->activity_id ), 'delete_activity_' . $activity->activity_id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wp-paradb' ); ?>');">
												<?php esc_html_e( 'Delete', 'wp-paradb' ); ?>
											</a>
										</span>
									<?php endif; ?>
								</div>
							</td>
							<td>
								<?php if ( $case ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit&case_id=' . $case->case_id ) ); ?>">
										<?php echo esc_html( $case->case_number ); ?>
									</a>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( ucfirst( $activity->activity_type ) ); ?></td>
							<td><?php echo esc_html( gmdate( 'M j, Y', strtotime( $activity->activity_date ) ) ); ?></td>
							<td><?php echo $investigator ? esc_html( $investigator->display_name ) : esc_html__( 'Unknown', 'wp-paradb' ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5" style="text-align: center; padding: 20px;">
							<?php esc_html_e( 'No activities found.', 'wp-paradb' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	
	<?php
}
