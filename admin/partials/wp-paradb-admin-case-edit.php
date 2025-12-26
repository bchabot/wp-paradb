<?php
/**
 * Admin case edit view
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

// Load required classes.
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-client-handler.php';

// Check if editing existing case.
$case_id = isset( $_GET['case_id'] ) ? absint( $_GET['case_id'] ) : 0;
$case = null;
$is_new = true;

if ( $case_id > 0 ) {
	$case = WP_ParaDB_Case_Handler::get_case( $case_id );
	if ( ! $case ) {
		wp_die( esc_html__( 'Case not found.', 'wp-paradb' ) );
	}
	$is_new = false;
	
	// Check permissions.
	if ( ! current_user_can( 'paradb_edit_cases' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this case.', 'wp-paradb' ) );
	}
} else {
	// Check create permission.
	if ( ! current_user_can( 'paradb_create_cases' ) ) {
		wp_die( esc_html__( 'You do not have permission to create cases.', 'wp-paradb' ) );
	}
}

// Handle form submission.
if ( isset( $_POST['save_case'] ) && check_admin_referer( 'save_case_' . $case_id, 'case_nonce' ) ) {
	$case_data = array(
		'case_name'          => isset( $_POST['case_name'] ) ? sanitize_text_field( wp_unslash( $_POST['case_name'] ) ) : '',
		'case_status'        => isset( $_POST['case_status'] ) ? sanitize_text_field( wp_unslash( $_POST['case_status'] ) ) : 'open',
		'case_type'          => isset( $_POST['case_type'] ) ? sanitize_text_field( wp_unslash( $_POST['case_type'] ) ) : 'investigation',
		'client_id'          => isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : null,
		'location_name'      => isset( $_POST['location_name'] ) ? sanitize_text_field( wp_unslash( $_POST['location_name'] ) ) : '',
		'location_address'   => isset( $_POST['location_address'] ) ? sanitize_text_field( wp_unslash( $_POST['location_address'] ) ) : '',
		'location_city'      => isset( $_POST['location_city'] ) ? sanitize_text_field( wp_unslash( $_POST['location_city'] ) ) : '',
		'location_state'     => isset( $_POST['location_state'] ) ? sanitize_text_field( wp_unslash( $_POST['location_state'] ) ) : '',
		'location_zip'       => isset( $_POST['location_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['location_zip'] ) ) : '',
		'location_country'   => isset( $_POST['location_country'] ) ? sanitize_text_field( wp_unslash( $_POST['location_country'] ) ) : 'United States',
		'latitude'           => isset( $_POST['latitude'] ) ? floatval( $_POST['latitude'] ) : null,
		'longitude'          => isset( $_POST['longitude'] ) ? floatval( $_POST['longitude'] ) : null,
		'case_description'   => isset( $_POST['case_description'] ) ? wp_kses_post( wp_unslash( $_POST['case_description'] ) ) : '',
		'phenomena_types'    => isset( $_POST['phenomena_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['phenomena_types'] ) ) : array(),
		'case_priority'      => isset( $_POST['case_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['case_priority'] ) ) : 'normal',
		'assigned_to'        => isset( $_POST['assigned_to'] ) ? absint( $_POST['assigned_to'] ) : null,
	);

		if ( $is_new ) {
			$result = WP_ParaDB_Case_Handler::create_case( $case_data );
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				$redirect_url = admin_url( 'admin.php?page=wp-paradb-case-edit&case_id=' . $result . '&message=created' );
				echo '<script>window.location.href="' . esc_url_raw( $redirect_url ) . '";</script>';
				exit;
			}
		} else {
		$result = WP_ParaDB_Case_Handler::update_case( $case_id, $case_data );
		if ( is_wp_error( $result ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Case updated successfully.', 'wp-paradb' ) . '</p></div>';
			$case = WP_ParaDB_Case_Handler::get_case( $case_id );
		}
	}
}

// Display success message.
if ( isset( $_GET['message'] ) && 'created' === $_GET['message'] ) {
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Case created successfully.', 'wp-paradb' ) . '</p></div>';
}

// Get options.
$options = get_option( 'wp_paradb_options', array() );
$case_statuses = isset( $options['case_statuses'] ) ? $options['case_statuses'] : array();
$phenomena_types = isset( $options['phenomena_types'] ) ? $options['phenomena_types'] : array();

// Get clients for dropdown.
$clients = WP_ParaDB_Client_Handler::get_clients( array( 'limit' => 1000 ) );

// Get users for assignment.
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-roles.php';
$investigators = WP_ParaDB_Roles::get_all_paradb_users();
?>

<div class="wrap">
	<h1><?php echo $is_new ? esc_html__( 'Add New Case', 'wp-paradb' ) : esc_html__( 'Edit Case', 'wp-paradb' ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'save_case_' . $case_id, 'case_nonce' ); ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<!-- Main Case Information -->
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Case Information', 'wp-paradb' ); ?></h2>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="case_name"><?php esc_html_e( 'Case Name', 'wp-paradb' ); ?> *</label>
									</th>
									<td>
										<input type="text" name="case_name" id="case_name" class="regular-text" value="<?php echo $case ? esc_attr( $case->case_name ) : ''; ?>" required>
									</td>
								</tr>
								
								<tr>
									<th scope="row">
										<label for="case_description"><?php esc_html_e( 'Description', 'wp-paradb' ); ?></label>
									</th>
									<td>
										<?php
										wp_editor(
											$case ? $case->case_description : '',
											'case_description',
											array(
												'textarea_name' => 'case_description',
												'textarea_rows' => 10,
												'media_buttons' => false,
											)
										);
										?>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="phenomena_types"><?php esc_html_e( 'Phenomena Types', 'wp-paradb' ); ?></label>
									</th>
									<td>
										<?php
										$selected_phenomena = $case && is_array( $case->phenomena_types ) ? $case->phenomena_types : array();
										foreach ( $phenomena_types as $phenomenon ) :
											$checked = in_array( $phenomenon, $selected_phenomena, true );
											?>
											<label style="display: block; margin-bottom: 5px;">
												<input type="checkbox" name="phenomena_types[]" value="<?php echo esc_attr( $phenomenon ); ?>" <?php checked( $checked ); ?>>
												<?php echo esc_html( $phenomenon ); ?>
											</label>
										<?php endforeach; ?>
									</td>
								</tr>
							</table>
						</div>
					</div>

					<!-- Location Information -->
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Location Information', 'wp-paradb' ); ?></h2>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="location_name"><?php esc_html_e( 'Location Name', 'wp-paradb' ); ?></label>
									</th>
									<td>
										<input type="text" name="location_name" id="location_name" class="regular-text" value="<?php echo $case ? esc_attr( $case->location_name ) : ''; ?>">
										<p class="description"><?php esc_html_e( 'e.g., "Old Mill Hotel" or "Smith Residence"', 'wp-paradb' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="location_address"><?php esc_html_e( 'Address', 'wp-paradb' ); ?></label>
									</th>
									<td>
										<input type="text" name="location_address" id="location_address" class="regular-text" value="<?php echo $case ? esc_attr( $case->location_address ) : ''; ?>">
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="location_city"><?php esc_html_e( 'City', 'wp-paradb' ); ?></label>
									</th>
									<td>
										<input type="text" name="location_city" id="location_city" class="regular-text" value="<?php echo $case ? esc_attr( $case->location_city ) : ''; ?>">
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="location_state"><?php esc_html_e( 'State/Province', 'wp-paradb' ); ?></label>
									</th>
									<td>
										<input type="text" name="location_state" id="location_state" class="regular-text" value="<?php echo $case ? esc_attr( $case->location_state ) : ''; ?>">
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="location_zip"><?php esc_html_e( 'ZIP/Postal Code', 'wp-paradb' ); ?></label>
									</th>
									<td>
										<input type="text" name="location_zip" id="location_zip" class="regular-text" value="<?php echo $case ? esc_attr( $case->location_zip ) : ''; ?>">
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="location_country"><?php esc_html_e( 'Country', 'wp-paradb' ); ?></label>
									</th>
									<td>
										<input type="text" name="location_country" id="location_country" class="regular-text" value="<?php echo $case ? esc_attr( $case->location_country ) : 'United States'; ?>">
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="latitude"><?php esc_html_e( 'Coordinates', 'wp-paradb' ); ?></label>
									</th>
									<td>
										<input type="number" step="0.000001" name="latitude" id="latitude" placeholder="<?php esc_attr_e( 'Latitude', 'wp-paradb' ); ?>" value="<?php echo $case ? esc_attr( $case->latitude ) : ''; ?>" style="width: 150px;">
										<input type="number" step="0.000001" name="longitude" id="longitude" placeholder="<?php esc_attr_e( 'Longitude', 'wp-paradb' ); ?>" value="<?php echo $case ? esc_attr( $case->longitude ) : ''; ?>" style="width: 150px;">
									</td>
								</tr>
							</table>
						</div>
					</div>

					<!-- Case Relationships -->
					<?php if ( ! $is_new && $case_id > 0 ) : ?>
						
						<!-- Activities -->
						<div class="postbox">
							<h2 class="hndle">
								<span><?php esc_html_e( 'Investigation Activities', 'wp-paradb' ); ?></span>
								<?php if ( current_user_can( 'paradb_add_activities' ) ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-activities&action=new&case_id=' . $case_id ) ); ?>" class="button button-small" style="float: right; margin-top: -4px;"><?php esc_html_e( 'Add Activity', 'wp-paradb' ); ?></a>
								<?php endif; ?>
							</h2>
							<div class="inside">
								<?php
								require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';
								$activities = WP_ParaDB_Activity_Handler::get_activities( array( 'case_id' => $case_id, 'limit' => 100 ) );
								if ( $activities ) : ?>
									<table class="wp-list-table widefat fixed striped">
										<thead>
											<tr>
												<th><?php esc_html_e( 'Title', 'wp-paradb' ); ?></th>
												<th><?php esc_html_e( 'Date', 'wp-paradb' ); ?></th>
												<th><?php esc_html_e( 'Type', 'wp-paradb' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ( $activities as $activity ) : ?>
												<tr>
													<td>
														<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-activities&action=edit&activity_id=' . $activity->activity_id ) ); ?>">
															<?php echo esc_html( $activity->activity_title ); ?>
														</a>
													</td>
													<td><?php echo esc_html( gmdate( 'Y-m-d', strtotime( $activity->activity_date ) ) ); ?></td>
													<td><?php echo esc_html( ucfirst( $activity->activity_type ) ); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								<?php else : ?>
									<p><?php esc_html_e( 'No activities recorded for this case.', 'wp-paradb' ); ?></p>
								<?php endif; ?>
							</div>
						</div>

						<!-- Reports -->
						<div class="postbox">
							<h2 class="hndle">
								<span><?php esc_html_e( 'Investigation Reports', 'wp-paradb' ); ?></span>
								<?php if ( current_user_can( 'paradb_add_reports' ) ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-reports&action=new&case_id=' . $case_id ) ); ?>" class="button button-small" style="float: right; margin-top: -4px;"><?php esc_html_e( 'Add Report', 'wp-paradb' ); ?></a>
								<?php endif; ?>
							</h2>
							<div class="inside">
								<?php
								require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-report-handler.php';
								$reports = WP_ParaDB_Report_Handler::get_reports( array( 'case_id' => $case_id, 'limit' => 100 ) );
								if ( $reports ) : ?>
									<table class="wp-list-table widefat fixed striped">
										<thead>
											<tr>
												<th><?php esc_html_e( 'Title', 'wp-paradb' ); ?></th>
												<th><?php esc_html_e( 'Date', 'wp-paradb' ); ?></th>
												<th><?php esc_html_e( 'Type', 'wp-paradb' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ( $reports as $report ) : ?>
												<tr>
													<td>
														<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-reports&action=edit&report_id=' . $report->report_id ) ); ?>">
															<?php echo esc_html( $report->report_title ); ?>
														</a>
													</td>
													<td><?php echo esc_html( gmdate( 'Y-m-d', strtotime( $report->report_date ) ) ); ?></td>
													<td><?php echo esc_html( ucfirst( $report->report_type ) ); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								<?php else : ?>
									<p><?php esc_html_e( 'No reports recorded for this case.', 'wp-paradb' ); ?></p>
								<?php endif; ?>
							</div>
						</div>

						<!-- Evidence -->
						<div class="postbox">
							<h2 class="hndle">
								<span><?php esc_html_e( 'Evidence Files', 'wp-paradb' ); ?></span>
								<?php if ( current_user_can( 'paradb_upload_evidence' ) ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-evidence&action=upload&case_id=' . $case_id ) ); ?>" class="button button-small" style="float: right; margin-top: -4px;"><?php esc_html_e( 'Upload Evidence', 'wp-paradb' ); ?></a>
								<?php endif; ?>
							</h2>
							<div class="inside">
								<?php
								require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-evidence-handler.php';
								$evidence_files = WP_ParaDB_Evidence_Handler::get_evidence_files( array( 'case_id' => $case_id, 'limit' => 100 ) );
								if ( $evidence_files ) : ?>
									<div class="evidence-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;">
										<?php foreach ( $evidence_files as $evidence ) : 
											$file_url = WP_ParaDB_Evidence_Handler::get_evidence_url( $evidence );
											$is_image = in_array( $evidence->file_type, array( 'jpg', 'jpeg', 'png', 'gif' ), true );
											?>
											<div class="evidence-thumb" style="text-align: center; border: 1px solid #ddd; padding: 5px;">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-evidence&case_id=' . $case_id ) ); ?>" title="<?php echo esc_attr( $evidence->title ); ?>">
													<?php if ( $is_image ) : ?>
														<img src="<?php echo esc_url( $file_url ); ?>" style="max-width: 100%; height: auto; max-height: 80px; display: block; margin: 0 auto 5px;">
													<?php else : ?>
														<div style="font-size: 32px; margin-bottom: 5px;">📄</div>
													<?php endif; ?>
													<small style="display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo esc_html( $evidence->title ? $evidence->title : $evidence->file_name ); ?></small>
												</a>
											</div>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<p><?php esc_html_e( 'No evidence files linked to this case.', 'wp-paradb' ); ?></p>
								<?php endif; ?>
							</div>
						</div>

						<?php WP_ParaDB_Admin::render_relationship_section( $case_id, 'case' ); ?>

						<!-- Field Logs -->
						<div class="postbox">
							<h2 class="hndle"><?php esc_html_e( 'Field Log Entries', 'wp-paradb' ); ?></h2>
							<div class="inside">
								<?php
								require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';
								$logs = WP_ParaDB_Field_Log_Handler::get_logs( array( 'case_id' => $case_id ) );
								if ( $logs ) : ?>
									<table class="wp-list-table widefat fixed striped">
										<thead>
											<tr>
												<th><?php esc_html_e( 'Date', 'wp-paradb' ); ?></th>
												<th><?php esc_html_e( 'Investigator', 'wp-paradb' ); ?></th>
												<th><?php esc_html_e( 'Content', 'wp-paradb' ); ?></th>
												<th><?php esc_html_e( 'Location', 'wp-paradb' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ( $logs as $log ) : 
												$investigator = get_userdata( $log->investigator_id );
												?>
												<tr>
													<td><?php echo esc_html( gmdate( 'Y-m-d H:i', strtotime( $log->date_created ) ) ); ?></td>
													<td><?php echo $investigator ? esc_html( $investigator->display_name ) : '—'; ?></td>
													<td><?php echo wp_kses_post( $log->log_content ); ?></td>
													<td><?php echo $log->latitude ? esc_html( $log->latitude . ', ' . $log->longitude ) : '—'; ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								<?php else : ?>
									<p><?php esc_html_e( 'No field logs recorded for this case.', 'wp-paradb' ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<!-- Sidebar -->
				<div id="postbox-container-1" class="postbox-container">
					<!-- Save Box -->
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Save', 'wp-paradb' ); ?></h2>
						<div class="inside">
							<div class="submitbox">
								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="submit" name="save_case" class="button button-primary button-large" value="<?php echo $is_new ? esc_attr__( 'Create Case', 'wp-paradb' ) : esc_attr__( 'Update Case', 'wp-paradb' ); ?>">
									</div>
									<div class="clear"></div>
								</div>
							</div>
						</div>
					</div>

					<!-- Case Details -->
					<div class="postbox">
						<h2 class="hndle"><?php esc_html_e( 'Case Details', 'wp-paradb' ); ?></h2>
						<div class="inside">
							<p>
								<label for="case_status"><strong><?php esc_html_e( 'Status', 'wp-paradb' ); ?></strong></label><br>
								<select name="case_status" id="case_status" class="widefat">
									<?php foreach ( $case_statuses as $status_key => $status_label ) : ?>
										<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $case ? $case->case_status : 'open', $status_key ); ?>>
											<?php echo esc_html( $status_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>

							<p>
								<label for="case_priority"><strong><?php esc_html_e( 'Priority', 'wp-paradb' ); ?></strong></label><br>
								<select name="case_priority" id="case_priority" class="widefat">
									<option value="low" <?php selected( $case ? $case->case_priority : 'normal', 'low' ); ?>><?php esc_html_e( 'Low', 'wp-paradb' ); ?></option>
									<option value="normal" <?php selected( $case ? $case->case_priority : 'normal', 'normal' ); ?>><?php esc_html_e( 'Normal', 'wp-paradb' ); ?></option>
									<option value="high" <?php selected( $case ? $case->case_priority : 'normal', 'high' ); ?>><?php esc_html_e( 'High', 'wp-paradb' ); ?></option>
									<option value="urgent" <?php selected( $case ? $case->case_priority : 'normal', 'urgent' ); ?>><?php esc_html_e( 'Urgent', 'wp-paradb' ); ?></option>
								</select>
							</p>

							<p>
								<label for="case_type"><strong><?php esc_html_e( 'Case Type', 'wp-paradb' ); ?></strong></label><br>
								<select name="case_type" id="case_type" class="widefat">
									<option value="investigation" <?php selected( $case ? $case->case_type : 'investigation', 'investigation' ); ?>><?php esc_html_e( 'Investigation', 'wp-paradb' ); ?></option>
									<option value="research" <?php selected( $case ? $case->case_type : 'investigation', 'research' ); ?>><?php esc_html_e( 'Research', 'wp-paradb' ); ?></option>
									<option value="consultation" <?php selected( $case ? $case->case_type : 'investigation', 'consultation' ); ?>><?php esc_html_e( 'Consultation', 'wp-paradb' ); ?></option>
								</select>
							</p>

							<p>
								<label for="client_id"><strong><?php esc_html_e( 'Client', 'wp-paradb' ); ?></strong></label><br>
								<select name="client_id" id="client_id" class="widefat">
									<option value=""><?php esc_html_e( 'No Client', 'wp-paradb' ); ?></option>
									<?php foreach ( $clients as $client ) : ?>
										<option value="<?php echo esc_attr( $client->client_id ); ?>" <?php selected( $case ? $case->client_id : 0, $client->client_id ); ?>>
											<?php echo esc_html( WP_ParaDB_Client_Handler::get_client_display_name( $client, false ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<?php if ( current_user_can( 'paradb_add_clients' ) ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-clients&action=new' ) ); ?>" class="button button-small" style="margin-top: 5px;">
										<?php esc_html_e( 'Add New Client', 'wp-paradb' ); ?>
									</a>
								<?php endif; ?>
							</p>

							<p>
								<label for="assigned_to"><strong><?php esc_html_e( 'Assigned To', 'wp-paradb' ); ?></strong></label><br>
								<select name="assigned_to" id="assigned_to" class="widefat">
									<option value=""><?php esc_html_e( 'Unassigned', 'wp-paradb' ); ?></option>
									<?php foreach ( $investigators as $investigator ) : ?>
										<option value="<?php echo esc_attr( $investigator->ID ); ?>" <?php selected( $case ? $case->assigned_to : 0, $investigator->ID ); ?>>
											<?php echo esc_html( $investigator->display_name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>
						</div>
					</div>

					<?php if ( ! $is_new && $case ) : ?>
						<!-- Case Meta -->
						<div class="postbox">
							<h2 class="hndle"><?php esc_html_e( 'Case Meta', 'wp-paradb' ); ?></h2>
							<div class="inside">
								<p>
									<strong><?php esc_html_e( 'Case Number:', 'wp-paradb' ); ?></strong><br>
									<?php echo esc_html( $case->case_number ); ?>
								</p>
								<p>
									<strong><?php esc_html_e( 'Shortcode:', 'wp-paradb' ); ?></strong><br>
									<code style="display: block; padding: 5px; background: #f0f0f0; border: 1px solid #ccc; cursor: copy;" onclick="var s = this.innerText; navigator.clipboard.writeText(s); alert('Copied: ' + s);">[paradb_single_case id="<?php echo esc_attr( $case->case_id ); ?>"]</code>
									<small class="description"><?php esc_html_e( 'Click to copy shortcode.', 'wp-paradb' ); ?></small>
								</p>
								<p>
									<strong><?php esc_html_e( 'Created:', 'wp-paradb' ); ?></strong><br>
									<?php echo esc_html( gmdate( 'F j, Y g:i a', strtotime( $case->date_created ) ) ); ?>
								</p>
								<?php if ( $case->date_modified !== $case->date_created ) : ?>
									<p>
										<strong><?php esc_html_e( 'Last Modified:', 'wp-paradb' ); ?></strong><br>
										<?php echo esc_html( gmdate( 'F j, Y g:i a', strtotime( $case->date_modified ) ) ); ?>
									</p>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</form>
</div>