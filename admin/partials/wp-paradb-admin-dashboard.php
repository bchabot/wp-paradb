<?php
/**
 * Admin dashboard view
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
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';

// Get dashboard statistics.
global $wpdb;
$cases_table = $wpdb->prefix . 'paradb_cases';
$reports_table = $wpdb->prefix . 'paradb_reports';
$activities_table = $wpdb->prefix . 'paradb_activities';
$clients_table = $wpdb->prefix . 'paradb_clients';
$evidence_table = $wpdb->prefix . 'paradb_evidence';

$total_cases = $wpdb->get_var( "SELECT COUNT(*) FROM {$cases_table}" );
$open_cases = $wpdb->get_var( "SELECT COUNT(*) FROM {$cases_table} WHERE case_status = 'open' OR case_status = 'active'" );
$closed_cases = $wpdb->get_var( "SELECT COUNT(*) FROM {$cases_table} WHERE case_status = 'closed'" );
$total_reports = $wpdb->get_var( "SELECT COUNT(*) FROM {$reports_table}" );
$total_activities = $wpdb->get_var( "SELECT COUNT(*) FROM {$activities_table}" );
$total_clients = $wpdb->get_var( "SELECT COUNT(*) FROM {$clients_table}" );
$total_evidence = $wpdb->get_var( "SELECT COUNT(*) FROM {$evidence_table}" );

// Get counts by status for chart.
$status_counts = $wpdb->get_results( "SELECT case_status, COUNT(*) as count FROM {$cases_table} GROUP BY case_status", OBJECT_K );
$options = get_option( 'wp_paradb_options', array() );
$case_statuses = isset( $options['case_statuses'] ) ? $options['case_statuses'] : array();

// Get recent cases.
$recent_cases = WP_ParaDB_Case_Handler::get_cases( array(
	'orderby' => 'date_created',
	'order'   => 'DESC',
	'limit'   => 5,
) );

// Get user's assigned cases.
$user_id = get_current_user_id();
$my_cases = WP_ParaDB_Case_Handler::get_cases( array(
	'assigned_to' => $user_id,
	'limit'       => 5,
) );

// Get recent activities.
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';
$recent_activities = WP_ParaDB_Activity_Handler::get_activities( array(
	'limit' => 5,
) );

// Get pending witness accounts.
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-witness-handler.php';
$pending_witnesses = WP_ParaDB_Witness_Handler::get_witness_accounts( array(
	'status' => 'pending',
	'limit'  => 5,
) );
?>

<div class="wrap">
	<?php WP_ParaDB_Admin::render_breadcrumbs(); ?>
	<h1><?php esc_html_e( 'ParaDB Dashboard', 'wp-paradb' ); ?></h1>

	<div class="paradb-dashboard">
		<!-- Statistics Overview -->
		<div class="paradb-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin: 20px 0;">
			
			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; border-top: 4px solid #0073aa;">
				<h3 style="margin: 0 0 10px 0; color: #0073aa; font-size: 24px;"><?php echo esc_html( number_format( $total_cases ) ); ?></h3>
				<p style="margin: 0; color: #666; font-weight: 600;"><?php esc_html_e( 'Total Cases', 'wp-paradb' ); ?></p>
			</div>

			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; border-top: 4px solid #46b450;">
				<h3 style="margin: 0 0 10px 0; color: #46b450; font-size: 24px;"><?php echo esc_html( number_format( $open_cases ) ); ?></h3>
				<p style="margin: 0; color: #666; font-weight: 600;"><?php esc_html_e( 'Open Cases', 'wp-paradb' ); ?></p>
			</div>

			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; border-top: 4px solid #826eb4;">
				<h3 style="margin: 0 0 10px 0; color: #826eb4; font-size: 24px;"><?php echo esc_html( number_format( $total_reports ) ); ?></h3>
				<p style="margin: 0; color: #666; font-weight: 600;"><?php esc_html_e( 'Reports', 'wp-paradb' ); ?></p>
			</div>

			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; border-top: 4px solid #32c1dc;">
				<h3 style="margin: 0 0 10px 0; color: #32c1dc; font-size: 24px;"><?php echo esc_html( number_format( $total_activities ) ); ?></h3>
				<p style="margin: 0; color: #666; font-weight: 600;"><?php esc_html_e( 'Activities', 'wp-paradb' ); ?></p>
			</div>

			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; border-top: 4px solid #f56e28;">
				<h3 style="margin: 0 0 10px 0; color: #f56e28; font-size: 24px;"><?php echo esc_html( number_format( $total_clients ) ); ?></h3>
				<p style="margin: 0; color: #666; font-weight: 600;"><?php esc_html_e( 'Clients', 'wp-paradb' ); ?></p>
			</div>

			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; border-top: 4px solid #00a0d2;">
				<h3 style="margin: 0 0 10px 0; color: #00a0d2; font-size: 24px;"><?php echo esc_html( number_format( $total_evidence ) ); ?></h3>
				<p style="margin: 0; color: #666; font-weight: 600;"><?php esc_html_e( 'Evidence', 'wp-paradb' ); ?></p>
			</div>
		</div>

		<!-- Visualization Row -->
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin: 30px 0;">
			<div class="paradb-panel" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Cases by Status', 'wp-paradb' ); ?></h2>
				<div style="margin-top: 20px;">
					<?php foreach ( $case_statuses as $status_key => $status_label ) : 
						$count = isset( $status_counts[ $status_key ] ) ? $status_counts[ $status_key ]->count : 0;
						$percentage = $total_cases > 0 ? ( $count / $total_cases ) * 100 : 0;
						$bar_color = '#0073aa';
						if ( 'open' === $status_key ) $bar_color = '#00a0d2';
						if ( 'active' === $status_key ) $bar_color = '#46b450';
						if ( 'closed' === $status_key ) $bar_color = '#666';
						?>
						<div style="margin-bottom: 15px;">
							<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
								<span><strong><?php echo esc_html( $status_label ); ?></strong></span>
								<span><?php echo esc_html( $count ); ?></span>
							</div>
							<div style="background: #f0f0f1; height: 12px; border-radius: 6px; overflow: hidden;">
								<div style="background: <?php echo esc_attr( $bar_color ); ?>; width: <?php echo esc_attr( $percentage ); ?>%; height: 100%;"></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="paradb-panel" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
				<div style="font-size: 48px; margin-bottom: 10px;">📊</div>
				<h2><?php esc_html_e( 'Data Health', 'wp-paradb' ); ?></h2>
				<p><?php printf( esc_html__( 'Your database contains %d records across %d cases.', 'wp-paradb' ), $total_reports + $total_activities + $total_evidence, $total_cases ); ?></p>
				<div style="display: flex; gap: 20px; margin-top: 10px;">
					<div>
						<span style="font-size: 20px; font-weight: bold; color: #0073aa;"><?php echo $total_cases > 0 ? round($total_evidence / $total_cases, 1) : 0; ?></span><br>
						<small><?php esc_html_e( 'Evidence/Case', 'wp-paradb' ); ?></small>
					</div>
					<div>
						<span style="font-size: 20px; font-weight: bold; color: #46b450;"><?php echo $total_cases > 0 ? round($total_reports / $total_cases, 1) : 0; ?></span><br>
						<small><?php esc_html_e( 'Reports/Case', 'wp-paradb' ); ?></small>
					</div>
				</div>
			</div>
		</div>

		<!-- Quick Actions -->
		<div class="paradb-quick-actions" style="margin: 30px 0; background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
			<h2 style="margin-top: 0;"><?php esc_html_e( 'Quick Actions', 'wp-paradb' ); ?></h2>
			<div style="display: flex; gap: 10px; flex-wrap: wrap;">
				<?php if ( current_user_can( 'paradb_create_cases' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit' ) ); ?>" class="button button-primary">
						<span class="dashicons dashicons-plus" style="margin-top: 4px;"></span> <?php esc_html_e( 'Create New Case', 'wp-paradb' ); ?>
					</a>
				<?php endif; ?>
				
				<?php if ( current_user_can( 'paradb_add_reports' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-reports&action=new' ) ); ?>" class="button">
						<span class="dashicons dashicons-clipboard" style="margin-top: 4px;"></span> <?php esc_html_e( 'Add Report', 'wp-paradb' ); ?>
					</a>
				<?php endif; ?>

				<?php if ( current_user_can( 'paradb_add_activities' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-activities&action=new' ) ); ?>" class="button">
						<span class="dashicons dashicons-performance" style="margin-top: 4px;"></span> <?php esc_html_e( 'Add Activity', 'wp-paradb' ); ?>
					</a>
				<?php endif; ?>				

				<?php if ( current_user_can( 'paradb_upload_evidence' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-evidence&action=upload' ) ); ?>" class="button">
						<span class="dashicons dashicons-format-image" style="margin-top: 4px;"></span> <?php esc_html_e( 'Upload Evidence', 'wp-paradb' ); ?>
					</a>
				<?php endif; ?>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-cases' ) ); ?>" class="button">
					<span class="dashicons dashicons-search" style="margin-top: 4px;"></span> <?php esc_html_e( 'View All Cases', 'wp-paradb' ); ?>
				</a>
			</div>
		</div>

		<!-- Grid Layout -->
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin: 30px 0;">
			
			<!-- My Assigned Cases -->
			<div class="paradb-panel" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'My Assigned Cases', 'wp-paradb' ); ?></h2>
				<?php if ( ! empty( $my_cases ) ) : ?>
					<table class="widefat striped" style="margin-top: 15px; border: none;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Case', 'wp-paradb' ); ?></th>
								<th><?php esc_html_e( 'Status', 'wp-paradb' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $my_cases as $case ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit&case_id=' . $case->case_id ) ); ?>" style="font-weight: 600;">
											<?php echo esc_html( $case->case_number ); ?>: <?php echo esc_html( $case->case_name ); ?>
										</a>
									</td>
									<td>
										<?php
										$status_label = isset( $case_statuses[ $case->case_status ] ) ? $case_statuses[ $case->case_status ] : ucfirst( $case->case_status );
										echo esc_html( $status_label );
										?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p style="color: #666; font-style: italic;"><?php esc_html_e( 'No cases assigned to you.', 'wp-paradb' ); ?></p>
				<?php endif; ?>
			</div>

			<div class="paradb-panel" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Recent Activities', 'wp-paradb' ); ?></h2>
				<?php if ( ! empty( $recent_activities ) ) : ?>
					<table class="widefat striped" style="margin-top: 15px; border: none;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Activity', 'wp-paradb' ); ?></th>
								<th><?php esc_html_e( 'Case', 'wp-paradb' ); ?></th>
								<th><?php esc_html_e( 'Type', 'wp-paradb' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_activities as $act ) : 
								$act_case = WP_ParaDB_Case_Handler::get_case( $act->case_id );
								?>
								<tr>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-activities&action=edit&activity_id=' . $act->activity_id ) ); ?>" style="font-weight: 600;">
											<?php echo esc_html( $act->activity_title ); ?>
										</a>
										<br><small><?php echo esc_html( gmdate( 'M j', strtotime( $act->activity_date ) ) ); ?></small>
									</td>
									<td>
										<?php if ( $act_case ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit&case_id=' . $act_case->case_id ) ); ?>">
												<?php echo esc_html( $act_case->case_number ); ?>
											</a>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( ucfirst( $act->activity_type ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p style="color: #666; font-style: italic;"><?php esc_html_e( 'No recent activities.', 'wp-paradb' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- Pending Witness Submissions -->
			<?php if ( current_user_can( 'paradb_manage_witnesses' ) ) : ?>
			<div class="paradb-panel" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Pending Witness Reports', 'wp-paradb' ); ?></h2>
				<?php if ( ! empty( $pending_witnesses ) ) : ?>
					<table class="widefat striped" style="margin-top: 15px; border: none;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Witness', 'wp-paradb' ); ?></th>
								<th><?php esc_html_e( 'Location', 'wp-paradb' ); ?></th>
								<th><?php esc_html_e( 'Phenomena', 'wp-paradb' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $pending_witnesses as $witness ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-witnesses&action=view&witness_id=' . $witness->account_id ) ); ?>" style="font-weight: 600;">
											<?php echo esc_html( $witness->account_name ? $witness->account_name : $witness->account_email ); ?>
										</a>
										<br><small><?php echo esc_html( gmdate( 'M j, Y', strtotime( $witness->date_submitted ) ) ); ?></small>
									</td>
									<td><?php echo esc_html( $witness->incident_location ); ?></td>
									<td>
										<?php 
										if ( is_array( $witness->phenomena_types ) ) {
											echo esc_html( implode( ', ', array_slice( $witness->phenomena_types, 0, 2 ) ) );
											if ( count( $witness->phenomena_types ) > 2 ) echo '...';
										}
										?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p style="margin-top: 15px;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-witnesses&status=pending' ) ); ?>" class="button button-small"><?php esc_html_e( 'View All Pending', 'wp-paradb' ); ?></a>
					</p>
				<?php else : ?>
					<p style="color: #666; font-style: italic;"><?php esc_html_e( 'No pending witness submissions.', 'wp-paradb' ); ?></p>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<!-- Recent Cases -->
			<div class="paradb-panel" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Recent Cases', 'wp-paradb' ); ?></h2>
				<?php if ( ! empty( $recent_cases ) ) : ?>
					<table class="widefat striped" style="margin-top: 15px; border: none;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Case', 'wp-paradb' ); ?></th>
								<th><?php esc_html_e( 'Created', 'wp-paradb' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_cases as $case ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit&case_id=' . $case->case_id ) ); ?>" style="font-weight: 600;">
											<?php echo esc_html( $case->case_number ); ?>
										</a>
									</td>
									<td><?php echo esc_html( gmdate( 'M j, Y', strtotime( $case->date_created ) ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p style="color: #666; font-style: italic;"><?php esc_html_e( 'No cases found.', 'wp-paradb' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>