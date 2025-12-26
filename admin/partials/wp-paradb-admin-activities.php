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
		<h1><?php echo 'new' === $action ? esc_html__( 'Add Activity', 'wp-paradb' ) : esc_html__( 'Edit Activity', 'wp-paradb' ); ?></h1>
		
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
