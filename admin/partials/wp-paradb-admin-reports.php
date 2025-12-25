<?php
/**
 * Admin reports management view
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
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-report-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';

// Handle form submission for new/edit report.
if ( isset( $_POST['save_report'] ) && check_admin_referer( 'save_report', 'report_nonce' ) ) {
	$report_id = isset( $_POST['report_id'] ) ? absint( $_POST['report_id'] ) : 0;
	
	$report_data = array(
		'case_id'            => isset( $_POST['case_id'] ) ? absint( $_POST['case_id'] ) : 0,
		'activity_id'        => isset( $_POST['activity_id'] ) ? absint( $_POST['activity_id'] ) : 0,
		'report_title'       => isset( $_POST['report_title'] ) ? sanitize_text_field( wp_unslash( $_POST['report_title'] ) ) : '',
		'report_type'        => isset( $_POST['report_type'] ) ? sanitize_text_field( wp_unslash( $_POST['report_type'] ) ) : 'report',
		'report_date'        => isset( $_POST['report_date'] ) ? sanitize_text_field( wp_unslash( $_POST['report_date'] ) ) : current_time( 'mysql' ),
		'report_content'     => isset( $_POST['report_content'] ) ? wp_kses_post( wp_unslash( $_POST['report_content'] ) ) : '',
		'report_summary'     => isset( $_POST['report_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['report_summary'] ) ) : '',
	);

	if ( $report_id > 0 ) {
		$result = WP_ParaDB_Report_Handler::update_report( $report_id, $report_data );
	} else {
		$result = WP_ParaDB_Report_Handler::create_report( $report_data );
	}

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Report saved successfully.', 'wp-paradb' ) . '</p></div>';
	}
}

// Handle delete action.
if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['report_id'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_report_' . absint( $_GET['report_id'] ) ) ) {
		if ( current_user_can( 'paradb_delete_reports' ) ) {
			$result = WP_ParaDB_Report_Handler::delete_report( absint( $_GET['report_id'] ) );
			if ( ! is_wp_error( $result ) ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Report deleted successfully.', 'wp-paradb' ) . '</p></div>';
			}
		}
	}
}

// Get action.
$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
$report_id = isset( $_GET['report_id'] ) ? absint( $_GET['report_id'] ) : 0;

if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
	// Show form.
	$report = null;
	if ( 'edit' === $action && $report_id > 0 ) {
		$report = WP_ParaDB_Report_Handler::get_report( $report_id );
	}
	
	// Get cases for dropdown.
	$cases = WP_ParaDB_Case_Handler::get_cases( array( 'limit' => 1000 ) );
	$activities = WP_ParaDB_Activity_Handler::get_activities( array( 'limit' => 1000 ) );
	?>
	
	<div class="wrap">
		<h1><?php echo 'new' === $action ? esc_html__( 'Add Report', 'wp-paradb' ) : esc_html__( 'Edit Report', 'wp-paradb' ); ?></h1>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'save_report', 'report_nonce' ); ?>
			<?php if ( $report ) : ?>
				<input type="hidden" name="report_id" value="<?php echo esc_attr( $report->report_id ); ?>">
			<?php endif; ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="case_id"><?php esc_html_e( 'Case', 'wp-paradb' ); ?> *</label>
					</th>
					<td>
						<select name="case_id" id="case_id" required class="regular-text">
							<option value=""><?php esc_html_e( 'Select Case', 'wp-paradb' ); ?></option>
							<?php foreach ( $cases as $case ) : ?>
								<option value="<?php echo esc_attr( $case->case_id ); ?>" <?php selected( $report ? $report->case_id : 0, $case->case_id ); ?>>
									<?php echo esc_html( $case->case_number . ' - ' . $case->case_name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="activity_id"><?php esc_html_e( 'Activity', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<select name="activity_id" id="activity_id" class="regular-text">
							<option value=""><?php esc_html_e( 'None', 'wp-paradb' ); ?></option>
							<?php foreach ( $activities as $activity ) : ?>
								<option value="<?php echo esc_attr( $activity->activity_id ); ?>" <?php selected( $report ? $report->activity_id : 0, $activity->activity_id ); ?>>
									<?php echo esc_html( $activity->activity_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Optionally link this report to a specific investigation activity.', 'wp-paradb' ); ?></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="report_title"><?php esc_html_e( 'Report Title', 'wp-paradb' ); ?> *</label>
					</th>
					<td>
						<input type="text" name="report_title" id="report_title" class="regular-text" value="<?php echo $report ? esc_attr( $report->report_title ) : ''; ?>" required>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="report_type"><?php esc_html_e( 'Report Type', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<select name="report_type" id="report_type">
							<option value="investigation" <?php selected( $report ? $report->report_type : 'investigation', 'investigation' ); ?>><?php esc_html_e( 'Investigation', 'wp-paradb' ); ?></option>
							<option value="initial" <?php selected( $report ? $report->report_type : '', 'initial' ); ?>><?php esc_html_e( 'Initial Assessment', 'wp-paradb' ); ?></option>
							<option value="followup" <?php selected( $report ? $report->report_type : '', 'followup' ); ?>><?php esc_html_e( 'Follow-up', 'wp-paradb' ); ?></option>
							<option value="final" <?php selected( $report ? $report->report_type : '', 'final' ); ?>><?php esc_html_e( 'Final Report', 'wp-paradb' ); ?></option>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="report_date"><?php esc_html_e( 'Report Date', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="datetime-local" name="report_date" id="report_date" value="<?php echo $report ? esc_attr( gmdate( 'Y-m-d\TH:i', strtotime( $report->report_date ) ) ) : esc_attr( gmdate( 'Y-m-d\TH:i' ) ); ?>">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="report_content"><?php esc_html_e( 'Report Content', 'wp-paradb' ); ?> *</label>
					</th>
					<td>
						<?php
						wp_editor(
							$report ? $report->report_content : '',
							'report_content',
							array(
								'textarea_name' => 'report_content',
								'textarea_rows' => 15,
								'media_buttons' => false,
							)
						);
						?>
					</td>
				</tr>
				
			</table>
			
			<p class="submit">
				<input type="submit" name="save_report" class="button button-primary" value="<?php echo 'new' === $action ? esc_attr__( 'Create Report', 'wp-paradb' ) : esc_attr__( 'Update Report', 'wp-paradb' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-reports' ) ); ?>" class="button">
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
		'orderby' => 'report_date',
		'order'   => 'DESC',
	);
	
	$reports = WP_ParaDB_Report_Handler::get_reports( $args );
	?>
	
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Reports', 'wp-paradb' ); ?></h1>
		
		<?php if ( current_user_can( 'paradb_add_reports' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-reports&action=new' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'wp-paradb' ); ?>
			</a>
		<?php endif; ?>
		
		<hr class="wp-header-end">
		
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
				<?php if ( ! empty( $reports ) ) : ?>
					<?php foreach ( $reports as $report ) : ?>
						<?php
						$case = WP_ParaDB_Case_Handler::get_case( $report->case_id );
						$investigator = get_userdata( $report->investigator_id );
						?>
						<tr>
							<td>
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-reports&action=edit&report_id=' . $report->report_id ) ); ?>">
										<?php echo esc_html( $report->report_title ); ?>
									</a>
								</strong>
								<div class="row-actions">
									<span class="edit">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-reports&action=edit&report_id=' . $report->report_id ) ); ?>">
											<?php esc_html_e( 'Edit', 'wp-paradb' ); ?>
										</a>
									</span>
									<?php if ( current_user_can( 'paradb_delete_reports' ) ) : ?>
										|
										<span class="delete">
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-reports&action=delete&report_id=' . $report->report_id ), 'delete_report_' . $report->report_id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wp-paradb' ); ?>');">
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
							<td><?php echo esc_html( ucfirst( $report->report_type ) ); ?></td>
							<td><?php echo esc_html( gmdate( 'M j, Y', strtotime( $report->report_date ) ) ); ?></td>
							<td><?php echo $investigator ? esc_html( $investigator->display_name ) : esc_html__( 'Unknown', 'wp-paradb' ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5" style="text-align: center; padding: 20px;">
							<?php esc_html_e( 'No reports found.', 'wp-paradb' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	
	<?php
}