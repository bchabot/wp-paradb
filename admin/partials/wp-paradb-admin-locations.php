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
					<td><input name="location_name" type="text" id="location_name" value="<?php echo $location ? esc_attr( $location->location_name ) : ''; ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="address"><?php esc_html_e( 'Address', 'wp-paradb' ); ?></label></th>
					<td><input name="address" type="text" id="address" value="<?php echo $location ? esc_attr( $location->address ) : ''; ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="city"><?php esc_html_e( 'City', 'wp-paradb' ); ?></label></th>
					<td><input name="city" type="text" id="city" value="<?php echo $location ? esc_attr( $location->city ) : ''; ?>" class="regular-text"></td>
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
						<td><?php echo $loc->latitude ? esc_html( $loc->latitude . ', ' . $loc->longitude ) : '—'; ?></td>
					</tr>
				<?php endforeach; else : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No locations found.', 'wp-paradb' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
