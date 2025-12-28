<?php
/**
 * Admin field logs viewer
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.6.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';

$case_id = isset( $_GET['case_id'] ) ? absint( $_GET['case_id'] ) : 0;
$activity_id = isset( $_GET['activity_id'] ) ? absint( $_GET['activity_id'] ) : 0;
$investigator_id = isset( $_GET['investigator_id'] ) ? absint( $_GET['investigator_id'] ) : 0;

$readable_case_ids = WP_ParaDB_Case_Handler::get_readable_case_ids( get_current_user_id() );

$args = array(
	'case_id'     => $case_id,
	'case_ids'    => $readable_case_ids,
	'activity_id' => $activity_id,
	'limit'       => 100,
);

if ( $investigator_id ) {
	$args['investigator_id'] = $investigator_id;
}

if ( empty( $readable_case_ids ) ) {
	$logs = array();
} else {
	$logs = WP_ParaDB_Field_Log_Handler::get_logs( $args );
}

$cases = WP_ParaDB_Case_Handler::get_cases( array( 'limit' => 1000 ) ); 
$filtered_cases = array();
foreach ( $cases as $c ) {
	if ( in_array( $c->case_id, $readable_case_ids ) ) {
		$filtered_cases[] = $c;
	}
}
$cases = $filtered_cases;

$activities = WP_ParaDB_Activity_Handler::get_activities( array( 'limit' => 1000 ) );
$investigators = WP_ParaDB_Roles::get_all_paradb_users();
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Field Log Viewer', 'wp-paradb' ); ?></h1>
	
	<div class="tablenav top">
		<div class="alignleft actions">
			<form method="get" action="">
				<input type="hidden" name="page" value="wp-paradb-logs">
				
				<select name="case_id">
					<option value=""><?php esc_html_e( 'All Cases', 'wp-paradb' ); ?></option>
					<?php foreach ( $cases as $c ) : ?>
						<option value="<?php echo esc_attr( $c->case_id ); ?>" <?php selected( $case_id, $c->case_id ); ?>><?php echo esc_html( $c->case_number ); ?></option>
					<?php endforeach; ?>
				</select>

				<select name="activity_id">
					<option value=""><?php esc_html_e( 'All Activities', 'wp-paradb' ); ?></option>
					<?php foreach ( $activities as $a ) : ?>
						<option value="<?php echo esc_attr( $a->activity_id ); ?>" <?php selected( $activity_id, $a->activity_id ); ?>><?php echo esc_html( $a->activity_title ); ?></option>
					<?php endforeach; ?>
				</select>

				<select name="investigator_id">
					<option value=""><?php esc_html_e( 'All Investigators', 'wp-paradb' ); ?></option>
					<?php foreach ( $investigators as $i ) : ?>
						<option value="<?php echo esc_attr( $i->ID ); ?>" <?php selected( $investigator_id, $i->ID ); ?>><?php echo esc_html( $i->display_name ); ?></option>
					<?php endforeach; ?>
				</select>

				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wp-paradb' ); ?>">
				
				<label style="margin-left: 20px;">
					<input type="checkbox" id="live-mode"> <strong><?php esc_html_e( 'Live Mode (Tail)', 'wp-paradb' ); ?></strong>
				</label>

				<label style="margin-left: 20px;">
					<input type="checkbox" id="expanded-view"> <strong><?php esc_html_e( 'Expanded View', 'wp-paradb' ); ?></strong>
				</label>

				<button type="button" id="view-all-map" class="button button-secondary" style="margin-left: 20px;">
					<span class="dashicons dashicons-location" style="margin-top: 4px;"></span> <?php esc_html_e( 'View All on Map', 'wp-paradb' ); ?>
				</button>
			</form>
		</div>
	</div>

	<div id="paradb-map-modal" style="display:none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
		<div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; height: 80%; position: relative;">
			<span id="close-map-modal" style="position: absolute; right: 10px; top: 5px; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
			<div id="map-canvas" style="width: 100%; height: 100%;"></div>
		</div>
	</div>

	<table class="wp-list-table widefat fixed striped" id="logs-table">
		<thead>
			<tr>
				<th style="width: 180px;"><?php esc_html_e( 'Date/Time', 'wp-paradb' ); ?></th>
				<th style="width: 150px;"><?php esc_html_e( 'Investigator', 'wp-paradb' ); ?></th>
				<th style="width: 150px;" class="column-context"><?php esc_html_e( 'Context', 'wp-paradb' ); ?></th>
				<th><?php esc_html_e( 'Message', 'wp-paradb' ); ?></th>
				<th style="width: 100px;"><?php esc_html_e( 'Media', 'wp-paradb' ); ?></th>
				<th style="width: 100px;"><?php esc_html_e( 'Actions', 'wp-paradb' ); ?></th>
			</tr>
		</thead>
		<tbody id="log-viewer-body">
			<?php if ( $logs ) : ?>
				<?php foreach ( $logs as $log ) : 
					$inv = get_userdata( $log->investigator_id );
					$case = WP_ParaDB_Case_Handler::get_case( $log->case_id );
					$act = $log->activity_id ? WP_ParaDB_Activity_Handler::get_activity( $log->activity_id ) : null;
					?>
					<tr id="log-row-<?php echo $log->log_id; ?>" data-id="<?php echo $log->log_id; ?>">
						<td><?php echo esc_html( gmdate( 'Y-m-d H:i:s', strtotime( $log->date_created ) ) ); ?></td>
						<td><strong><?php echo $inv ? esc_html( $inv->display_name ) : '—'; ?></strong></td>
						<td class="column-context">
							<small>
								<?php if ( $case ) echo 'Case: ' . esc_html( $case->case_number ) . '<br>'; ?>
								<?php if ( $act ) echo 'Act: ' . esc_html( $act->activity_title ); ?>
							</small>
						</td>
						<td>
							<div class="log-display">
								<?php echo wp_kses_post( $log->log_content ); ?>
															<div class="log-details" style="display: none; margin-top: 10px; font-size: 11px; color: #666; border-top: 1px dashed #ccc; padding-top: 5px;">
																<?php if ( $log->latitude ) : ?>
																	<a href="#" class="view-log-map" data-lat="<?php echo esc_attr($log->latitude); ?>" data-lng="<?php echo esc_attr($log->longitude); ?>" data-title="<?php echo esc_attr(gmdate( 'Y-m-d H:i:s', strtotime( $log->date_created ) ) . ' - ' . ($inv ? $inv->display_name : '')); ?>">
																		<?php printf( __( 'Location: %f, %f', 'wp-paradb' ), $log->latitude, $log->longitude ); ?>
																	</a>
																<?php endif; ?>
																<?php if ( $log->log_id ) echo ' | ID: ' . $log->log_id; ?>
															</div>							</div>
							<div class="log-edit" style="display: none;">
								<textarea class="edit-log-content" style="width: 100%;" rows="3"><?php echo esc_textarea( $log->log_content ); ?></textarea>
								<div style="margin-top: 5px;">
									<input type="text" class="edit-log-lat" placeholder="Lat" value="<?php echo esc_attr( $log->latitude ); ?>" style="width: 80px;">
									<input type="text" class="edit-log-lng" placeholder="Lng" value="<?php echo esc_attr( $log->longitude ); ?>" style="width: 80px;">
									<button type="button" class="button button-small save-log-edit"><?php esc_html_e( 'Save', 'wp-paradb' ); ?></button>
									<button type="button" class="button button-small cancel-log-edit"><?php esc_html_e( 'Cancel', 'wp-paradb' ); ?></button>
								</div>
							</div>
						</td>
						<td>
							<?php if ( $log->file_url ) : ?>
								<a href="<?php echo esc_url( $log->file_url ); ?>" target="_blank" title="<?php esc_attr_e( 'View Media', 'wp-paradb' ); ?>">
									<?php 
									$isImg = preg_match( '/\.(jpg|jpeg|png|gif)$/i', $log->file_url );
									if ( $isImg ) : ?>
										<img src="<?php echo esc_url( $log->file_url ); ?>" style="max-width: 50px; max-height: 50px; border-radius: 2px;">
									<?php else : ?>
										<span class="dashicons dashicons-media-document"></span>
									<?php endif; ?>
								</a>
							<?php endif; ?>
						</td>
						<td>
							<button type="button" class="button-link edit-log-btn"><?php esc_html_e( 'Edit', 'wp-paradb' ); ?></button> | 
							<button type="button" class="button-link-delete delete-log-btn"><?php esc_html_e( 'Delete', 'wp-paradb' ); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No logs found.', 'wp-paradb' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<script>
jQuery(document).ready(function($) {
	var liveInterval;
	<?php
	global $wpdb;
	$max_id = $wpdb->get_var( "SELECT MAX(log_id) FROM {$wpdb->prefix}paradb_field_logs" );
	?>
	var lastId = <?php echo $logs ? intval($logs[0]->log_id) : ($max_id ? intval($max_id) : 0); ?>;

	$('#expanded-view').on('change', function() {
		if ($(this).is(':checked')) {
			$('.log-details').show();
			$('#logs-table').removeClass('fixed');
		} else {
			$('.log-details').hide();
			$('#logs-table').addClass('fixed');
		}
	}).trigger('change');

	$('#live-mode').on('change', function() {
		if ($(this).is(':checked')) {
			liveInterval = setInterval(fetchNewLogs, 3000);
		} else {
			clearInterval(liveInterval);
		}
	});

	$(document).on('click', '.edit-log-btn', function() {
		var $row = $(this).closest('tr');
		$row.find('.log-display').hide();
		$row.find('.log-edit').show();
	});

	$(document).on('click', '.cancel-log-edit', function() {
		var $row = $(this).closest('tr');
		$row.find('.log-edit').hide();
		$row.find('.log-display').show();
	});

	$(document).on('click', '.save-log-edit', function() {
		var $row = $(this).closest('tr');
		var logId = $row.data('id');
		var content = $row.find('.edit-log-content').val();
		var lat = $row.find('.edit-log-lat').val();
		var lng = $row.find('.edit-log-lng').val();

		$.post(ajaxurl, {
			action: 'paradb_update_log',
			log_id: logId,
			log_content: content,
			latitude: lat,
			longitude: lng,
			nonce: '<?php echo wp_create_nonce("paradb_log_nonce"); ?>'
		}, function(res) {
			if (res.success) {
				location.reload();
			} else {
				alert(res.data.message);
			}
		});
	});

	$(document).on('click', '.delete-log-btn', function() {
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

	// Map Logic
	var map;
	var markers = [];

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
				var infowindow = new google.maps.InfoWindow({
					content: loc.title
				});
				marker.addListener('click', function() {
					infowindow.open(map, marker);
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

	$('#view-all-map').on('click', function() {
		var locations = [];
		$('.view-log-map').each(function() {
			locations.push({
				lat: $(this).data('lat'),
				lng: $(this).data('lng'),
				title: $(this).data('title')
			});
		});
		if (locations.length > 0) {
			showMapModal(locations);
		} else {
			alert('<?php echo esc_js(__("No locations found in the current view.", "wp-paradb")); ?>');
		}
	});

	$('#close-map-modal').on('click', function() {
		$('#paradb-map-modal').hide();
	});

	$(window).on('click', function(event) {
		if (event.target.id == 'paradb-map-modal') {
			$('#paradb-map-modal').hide();
		}
	});

	function fetchNewLogs() {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'paradb_get_all_logs_live',
				last_id: lastId,
				case_id: $('select[name="case_id"]').val(),
				activity_id: $('select[name="activity_id"]').val(),
				investigator_id: $('select[name="investigator_id"]').val(),
				nonce: '<?php echo wp_create_nonce("paradb_log_viewer_nonce"); ?>'
			},
			success: function(res) {
				if (res.success && res.data.logs.length > 0) {
					res.data.logs.forEach(function(log) {
						if ($('#log-row-' + log.log_id).length === 0) {
							var isExpanded = $('#expanded-view').is(':checked');
							var html = '<tr id="log-row-' + log.log_id + '" data-id="' + log.log_id + '" style="background: #fff9c4;">';
							html += '<td>' + log.datetime + '</td>';
							html += '<td><strong>' + log.user_name + '</strong></td>';
							html += '<td class="column-context"><small>' + log.context + '</small></td>';
							html += '<td>' + log.content;
							html += '<div class="log-details" style="display: ' + (isExpanded ? 'block' : 'none') + '; margin-top: 10px; font-size: 11px; color: #666; border-top: 1px dashed #ccc; padding-top: 5px;">ID: ' + log.log_id + '</div>';
							html += '</td>';
							html += '<td>' + (log.file_url ? '<a href="' + log.file_url + '" target="_blank">View</a>' : '') + '</td>';
							html += '</tr>';
							$('#log-viewer-body').prepend(html);
							lastId = Math.max(lastId, parseInt(log.log_id));
						}
					});
				}
			}
		});
	}
});
</script>
