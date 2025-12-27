<?php
/**
 * Admin locations management view
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.2.0
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

require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-location-handler.php';

$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
$location_id = isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0;

// Handle delete action.
if ( 'delete' === $action && $location_id > 0 && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_location_' . $location_id ) ) {
		WP_ParaDB_Location_Handler::delete_location( $location_id );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Location deleted successfully.', 'wp-paradb' ) . '</p></div>';
		$action = 'list';
	}
}

// Handle form submission.
if ( isset( $_POST['save_location'] ) && check_admin_referer( 'save_location', 'location_nonce' ) ) {
	$location_data = array(
		'location_name'  => sanitize_text_field( $_POST['location_name'] ),
		'address'        => sanitize_text_field( $_POST['address'] ),
		'city'           => sanitize_text_field( $_POST['city'] ),
		'state'          => sanitize_text_field( $_POST['state'] ),
		'zip'            => sanitize_text_field( $_POST['zip'] ),
		'country'        => sanitize_text_field( $_POST['country'] ),
		'latitude'       => floatval( $_POST['latitude'] ),
		'longitude'      => floatval( $_POST['longitude'] ),
		'location_notes' => sanitize_textarea_field( $_POST['location_notes'] ),
		'is_public'      => isset( $_POST['is_public'] ) ? 1 : 0,
	);

	if ( $location_id > 0 ) {
		$result = WP_ParaDB_Location_Handler::update_location( $location_id, $location_data );
		if ( is_wp_error( $result ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Location updated successfully.', 'wp-paradb' ) . '</p></div>';
			$action = 'list';
		}
	} else {
		$result = WP_ParaDB_Location_Handler::create_location( $location_data );
		if ( is_wp_error( $result ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Location saved successfully.', 'wp-paradb' ) . '</p></div>';
			$action = 'list';
		}
	}
}

if ( 'new' === $action || 'edit' === $action ) {
	$location = null;
	if ( 'edit' === $action && $location_id > 0 ) {
		$location = WP_ParaDB_Location_Handler::get_location( $location_id );
	}
	?>
	<div class="wrap">
		<h1><?php echo $location ? esc_html__( 'Edit Location', 'wp-paradb' ) : esc_html__( 'Add New Location', 'wp-paradb' ); ?></h1>
		<form method="post" action="">
			<?php wp_nonce_field( 'save_location', 'location_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="location_name"><?php esc_html_e( 'Location Name', 'wp-paradb' ); ?> *</label></th>
					<td><input name="location_name" type="text" id="location_name" value="<?php echo $location ? esc_attr( $location->location_name ) : ''; ?>" class="regular-text" required autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><label for="address"><?php esc_html_e( 'Address', 'wp-paradb' ); ?></label></th>
					<td>
						<div style="display:flex; gap: 5px;">
							<input name="address" type="text" id="address" value="<?php echo $location ? esc_attr( $location->address ) : ''; ?>" class="regular-text" style="flex:1;" autocomplete="off">
							<button type="button" class="get-current-location button" data-target="#address" title="<?php esc_attr_e( 'Use current GPS location', 'wp-paradb' ); ?>">📍</button>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="city"><?php esc_html_e( 'City', 'wp-paradb' ); ?></label></th>
					<td><input name="city" type="text" id="city" value="<?php echo $location ? esc_attr( $location->city ) : ''; ?>" class="regular-text" autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><label for="latitude"><?php esc_html_e( 'Coordinates', 'wp-paradb' ); ?></label></th>
					<td>
						<input name="latitude" type="number" step="any" id="latitude" value="<?php echo $location ? esc_attr( $location->latitude ) : ''; ?>" placeholder="Latitude">
						<input name="longitude" type="number" step="any" id="longitude" value="<?php echo $location ? esc_attr( $location->longitude ) : ''; ?>" placeholder="Longitude">
						<button type="button" id="geocode-address" class="button"><?php esc_html_e( 'Find on Map', 'wp-paradb' ); ?></button>
						<div id="location-map" style="height: 300px; margin-top: 10px; border: 1px solid #ccc;"></div>
					</td>
				</tr>
			</table>

			<?php if ( $location && $location_id > 0 ) : ?>
				<?php WP_ParaDB_Admin::render_relationship_section( $location_id, 'location' ); ?>
			<?php endif; ?>

			<?php submit_button( __( 'Save Location', 'wp-paradb' ), 'primary', 'save_location' ); ?>
		</form>
	</div>
	<?php
} else {
	$locations = WP_ParaDB_Location_Handler::get_locations();
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Locations (Address Book)', 'wp-paradb' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-locations&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'wp-paradb' ); ?></a>
		<hr class="wp-header-end">
		
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'Address', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'City', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'Coordinates', 'wp-paradb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $locations ) : foreach ( $locations as $loc ) : ?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-locations&action=edit&location_id=' . $loc->location_id ) ); ?>"><?php echo esc_html( $loc->location_name ); ?></a></strong>
							<div class="row-actions">
								<span class="edit"><a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-locations&action=edit&location_id=' . $loc->location_id ) ); ?>"><?php esc_html_e( 'Edit', 'wp-paradb' ); ?></a> | </span>
								<span class="delete"><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-locations&action=delete&location_id=' . $loc->location_id ), 'delete_location_' . $loc->location_id ) ); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wp-paradb' ); ?>');"><?php esc_html_e( 'Delete', 'wp-paradb' ); ?></a></span>
							</div>
						</td>
						<td><?php echo esc_html( $loc->address ); ?></td>
						<td><?php echo esc_html( $loc->city ); ?></td>
						<td>
							<?php if ( $loc->latitude ) : ?>
								<a href="#" class="view-loc-map" data-lat="<?php echo esc_attr($loc->latitude); ?>" data-lng="<?php echo esc_attr($loc->longitude); ?>" data-title="<?php echo esc_attr($loc->location_name); ?>">
									<?php echo esc_html( $loc->latitude . ', ' . $loc->longitude ); ?>
								</a>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; else : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No locations found.', 'wp-paradb' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div id="paradb-map-modal" style="display:none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
		<div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; height: 80%; position: relative;">
			<span id="close-map-modal" style="position: absolute; right: 10px; top: 5px; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
			<div id="map-canvas" style="width: 100%; height: 100%;"></div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
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

		$('.view-loc-map').on('click', function(e) {
			e.preventDefault();
			showMapModal([{
				lat: $(this).data('lat'),
				lng: $(this).data('lng'),
				title: $(this).data('title')
			}]);
		});

		$('#close-map-modal').on('click', function() {
			$('#paradb-map-modal').hide();
		});
	});
	</script>
	<?php
}
