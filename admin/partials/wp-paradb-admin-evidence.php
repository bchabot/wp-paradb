<?php
/**
 * Admin evidence management view
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
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-evidence-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-report-handler.php';

// Handle file upload.
if ( isset( $_POST['upload_evidence'] ) && check_admin_referer( 'upload_evidence', 'evidence_nonce' ) ) {
	if ( ! empty( $_FILES['evidence_file']['name'] ) ) {
		$metadata = array(
			'case_id'          => isset( $_POST['case_id'] ) ? absint( $_POST['case_id'] ) : 0,
			'report_id'        => isset( $_POST['report_id'] ) ? absint( $_POST['report_id'] ) : null,
			'activity_id'      => isset( $_POST['activity_id'] ) ? absint( $_POST['activity_id'] ) : null,
			'evidence_type'    => isset( $_POST['evidence_type'] ) ? sanitize_text_field( wp_unslash( $_POST['evidence_type'] ) ) : 'other',
			'title'            => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'description'      => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'capture_date'     => isset( $_POST['capture_date'] ) ? sanitize_text_field( wp_unslash( $_POST['capture_date'] ) ) : '',
			'capture_location' => isset( $_POST['capture_location'] ) ? sanitize_text_field( wp_unslash( $_POST['capture_location'] ) ) : '',
			'equipment_used'   => isset( $_POST['equipment_used'] ) ? sanitize_text_field( wp_unslash( $_POST['equipment_used'] ) ) : '',
			'is_key_evidence'  => isset( $_POST['is_key_evidence'] ) ? 1 : 0,
		);

		$result = WP_ParaDB_Evidence_Handler::upload_evidence( $_FILES['evidence_file'], $metadata );
		
		if ( is_wp_error( $result ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Evidence uploaded successfully.', 'wp-paradb' ) . '</p></div>';
		}
	} else {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Please select a file to upload.', 'wp-paradb' ) . '</p></div>';
	}
}

// Handle delete action.
if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['evidence_id'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_evidence_' . absint( $_GET['evidence_id'] ) ) ) {
		if ( current_user_can( 'paradb_manage_evidence' ) ) {
			$result = WP_ParaDB_Evidence_Handler::delete_evidence( absint( $_GET['evidence_id'] ) );
			if ( ! is_wp_error( $result ) ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Evidence deleted successfully.', 'wp-paradb' ) . '</p></div>';
			}
		}
	}
}

// Get action.
$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';

if ( 'upload' === $action ) {
	// Show upload form.
	$cases = WP_ParaDB_Case_Handler::get_cases( array( 'limit' => 1000 ) );
	$reports = WP_ParaDB_Report_Handler::get_reports( array( 'limit' => 1000 ) );
	$activities = WP_ParaDB_Activity_Handler::get_activities( array( 'limit' => 1000 ) );
	$options = get_option( 'wp_paradb_options', array() );
	$evidence_types = isset( $options['evidence_types'] ) ? $options['evidence_types'] : array();
	?>
	
	<div class="wrap">
		<h1><?php esc_html_e( 'Upload Evidence', 'wp-paradb' ); ?></h1>
		
		<form method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( 'upload_evidence', 'evidence_nonce' ); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="case_id"><?php esc_html_e( 'Case', 'wp-paradb' ); ?> *</label>
					</th>
					<td>
						<select name="case_id" id="case_id" class="regular-text" required>
							<option value=""><?php esc_html_e( 'Select Case', 'wp-paradb' ); ?></option>
							<?php foreach ( $cases as $case ) : ?>
								<option value="<?php echo esc_attr( $case->case_id ); ?>">
									<?php echo esc_html( $case->case_number . ' - ' . $case->case_name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="report_id"><?php esc_html_e( 'Linked Report', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<select name="report_id" id="report_id" class="regular-text">
							<option value=""><?php esc_html_e( 'None', 'wp-paradb' ); ?></option>
							<?php foreach ( $reports as $report ) : ?>
								<option value="<?php echo esc_attr( $report->report_id ); ?>">
									<?php echo esc_html( $report->report_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="activity_id"><?php esc_html_e( 'Linked Activity', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<select name="activity_id" id="activity_id" class="regular-text">
							<option value=""><?php esc_html_e( 'None', 'wp-paradb' ); ?></option>
							<?php foreach ( $activities as $activity ) : ?>
								<option value="<?php echo esc_attr( $activity->activity_id ); ?>">
									<?php echo esc_html( $activity->activity_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="evidence_file"><?php esc_html_e( 'File', 'wp-paradb' ); ?> *</label>
					</th>
					<td>
						<input type="file" name="evidence_file" id="evidence_file" required>
						<p class="description">
							<?php
							$max_size = isset( $options['max_upload_size'] ) ? $options['max_upload_size'] : 10485760;
							printf(
								esc_html__( 'Maximum file size: %s', 'wp-paradb' ),
								esc_html( size_format( $max_size ) )
							);
							?>
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="evidence_type"><?php esc_html_e( 'Evidence Type', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<select name="evidence_type" id="evidence_type">
							<?php foreach ( $evidence_types as $type_key => $type_label ) : ?>
								<option value="<?php echo esc_attr( $type_key ); ?>">
									<?php echo esc_html( $type_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="title"><?php esc_html_e( 'Title', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="title" id="title" class="regular-text">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="description"><?php esc_html_e( 'Description', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<textarea name="description" id="description" rows="4" class="large-text"></textarea>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="capture_date"><?php esc_html_e( 'Capture Date/Time', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="datetime-local" name="capture_date" id="capture_date">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="capture_location"><?php esc_html_e( 'Capture Location', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="capture_location" id="capture_location" class="regular-text">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="equipment_used"><?php esc_html_e( 'Equipment Used', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="equipment_used" id="equipment_used" class="regular-text">
					</td>
				</tr>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'Key Evidence', 'wp-paradb' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="is_key_evidence" value="1">
							<?php esc_html_e( 'Mark this as key evidence for the case', 'wp-paradb' ); ?>
						</label>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<input type="submit" name="upload_evidence" class="button button-primary" value="<?php esc_attr_e( 'Upload Evidence', 'wp-paradb' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-evidence' ) ); ?>" class="button">
					<?php esc_html_e( 'Cancel', 'wp-paradb' ); ?>
				</a>
			</p>
		</form>
	</div>
	
	<?php
} else {
	// Show list.
	$case_filter = isset( $_GET['case_id'] ) ? absint( $_GET['case_id'] ) : 0;
	$activity_filter = isset( $_GET['activity_id'] ) ? absint( $_GET['activity_id'] ) : 0;
	$type_filter = isset( $_GET['evidence_type'] ) ? sanitize_text_field( wp_unslash( $_GET['evidence_type'] ) ) : '';
	$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	$per_page = 30;
	
	$args = array(
		'case_id'       => $case_filter,
		'activity_id'   => $activity_filter,
		'evidence_type' => $type_filter,
		'limit'         => $per_page,
		'offset'        => ( $paged - 1 ) * $per_page,
		'orderby'       => 'date_uploaded',
		'order'         => 'DESC',
	);
	
	$evidence_files = WP_ParaDB_Evidence_Handler::get_evidence_files( $args );
	$cases = WP_ParaDB_Case_Handler::get_cases( array( 'limit' => 1000 ) );
	$activities = WP_ParaDB_Activity_Handler::get_activities( array( 'limit' => 1000 ) );
	$options = get_option( 'wp_paradb_options', array() );
	$evidence_types = isset( $options['evidence_types'] ) ? $options['evidence_types'] : array();
	?>
	
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Evidence Files', 'wp-paradb' ); ?></h1>
		
		<?php if ( current_user_can( 'paradb_upload_evidence' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-evidence&action=upload' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Upload New', 'wp-paradb' ); ?>
			</a>
		<?php endif; ?>
		
		<hr class="wp-header-end">
		
		<div class="tablenav top">
			<div class="alignleft actions">
				<form method="get" action="">
					<input type="hidden" name="page" value="wp-paradb-evidence">
					
					<select name="case_id" id="filter-by-case">
						<option value=""><?php esc_html_e( 'All Cases', 'wp-paradb' ); ?></option>
						<?php foreach ( $cases as $case ) : ?>
							<option value="<?php echo esc_attr( $case->case_id ); ?>" <?php selected( $case_filter, $case->case_id ); ?>>
								<?php echo esc_html( $case->case_number ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select name="activity_id" id="filter-by-activity">
						<option value=""><?php esc_html_e( 'All Activities', 'wp-paradb' ); ?></option>
						<?php foreach ( $activities as $activity ) : ?>
							<option value="<?php echo esc_attr( $activity->activity_id ); ?>" <?php selected( $activity_filter, $activity->activity_id ); ?>>
								<?php echo esc_html( $activity->activity_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					
					<select name="evidence_type" id="filter-by-type">
						<option value=""><?php esc_html_e( 'All Types', 'wp-paradb' ); ?></option>
						<?php foreach ( $evidence_types as $type_key => $type_label ) : ?>
							<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $type_filter, $type_key ); ?>>
								<?php echo esc_html( $type_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					
					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wp-paradb' ); ?>">
				</form>
			</div>
		</div>
		
		<div class="evidence-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
			<?php if ( ! empty( $evidence_files ) ) : ?>
				<?php foreach ( $evidence_files as $evidence ) : ?>
					<?php
					$case = WP_ParaDB_Case_Handler::get_case( $evidence->case_id );
					$file_url = WP_ParaDB_Evidence_Handler::get_evidence_url( $evidence );
					$is_image = in_array( $evidence->file_type, array( 'jpg', 'jpeg', 'png', 'gif' ), true );
					?>
					<div class="evidence-item" style="background: #fff; border: 1px solid #ccc; padding: 10px; text-align: center;">
						<?php if ( $is_image ) : ?>
							<a href="<?php echo esc_url( $file_url ); ?>" target="_blank">
								<img src="<?php echo esc_url( $file_url ); ?>" alt="<?php echo esc_attr( $evidence->title ? $evidence->title : $evidence->file_name ); ?>" style="max-width: 100%; height: auto; display: block; margin-bottom: 10px;">
							</a>
						<?php else : ?>
							<div style="padding: 40px 0; background: #f0f0f0; margin-bottom: 10px;">
								<span style="font-size: 48px;">📄</span>
							</div>
						<?php endif; ?>
						
						<div style="text-align: left;">
							<strong><?php echo esc_html( $evidence->title ? $evidence->title : $evidence->file_name ); ?></strong><br>
							<small><?php echo esc_html( strtoupper( $evidence->file_type ) . ' • ' . size_format( $evidence->file_size ) ); ?></small><br>
							<?php if ( $case ) : ?>
								<small><strong><?php esc_html_e( 'Case:', 'wp-paradb' ); ?></strong> <?php echo esc_html( $case->case_number ); ?></small><br>
							<?php endif; ?>
							<?php if ( $evidence->report_id ) : ?>
								<?php $report = WP_ParaDB_Report_Handler::get_report( $evidence->report_id ); ?>
								<?php if ( $report ) : ?>
									<small><strong><?php esc_html_e( 'Report:', 'wp-paradb' ); ?></strong> <?php echo esc_html( $report->report_title ); ?></small><br>
								<?php endif; ?>
							<?php endif; ?>
							<?php if ( $evidence->activity_id ) : ?>
								<?php $activity = WP_ParaDB_Activity_Handler::get_activity( $evidence->activity_id ); ?>
								<?php if ( $activity ) : ?>
									<small><strong><?php esc_html_e( 'Activity:', 'wp-paradb' ); ?></strong> <?php echo esc_html( $activity->activity_title ); ?></small><br>
								<?php endif; ?>
							<?php endif; ?>
							<small><?php echo esc_html( gmdate( 'M j, Y', strtotime( $evidence->date_uploaded ) ) ); ?></small>
							
							<div style="margin-top: 10px;">
								<a href="<?php echo esc_url( $file_url ); ?>" class="button button-small" target="_blank">
									<?php esc_html_e( 'View', 'wp-paradb' ); ?>
								</a>
								<?php if ( current_user_can( 'paradb_manage_evidence' ) ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-evidence&action=delete&evidence_id=' . $evidence->evidence_id ), 'delete_evidence_' . $evidence->evidence_id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this evidence file?', 'wp-paradb' ); ?>');">
										<?php esc_html_e( 'Delete', 'wp-paradb' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
					<p><?php esc_html_e( 'No evidence files found.', 'wp-paradb' ); ?></p>
					<?php if ( current_user_can( 'paradb_upload_evidence' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-evidence&action=upload' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Upload First Evidence File', 'wp-paradb' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	
	<?php
}