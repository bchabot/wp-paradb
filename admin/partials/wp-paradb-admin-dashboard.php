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
$clients_table = $wpdb->prefix . 'paradb_clients';
$evidence_table = $wpdb->prefix . 'paradb_evidence';

$total_cases = $wpdb->get_var( "SELECT COUNT(*) FROM {$cases_table}" );
$open_cases = $wpdb->get_var( "SELECT COUNT(*) FROM {$cases_table} WHERE case_status = 'open' OR case_status = 'active'" );
$closed_cases = $wpdb->get_var( "SELECT COUNT(*) FROM {$cases_table} WHERE case_status = 'closed'" );
$total_reports = $wpdb->get_var( "SELECT COUNT(*) FROM {$reports_table}" );
$total_clients = $wpdb->get_var( "SELECT COUNT(*) FROM {$clients_table}" );
$total_evidence = $wpdb->get_var( "SELECT COUNT(*) FROM {$evidence_table}" );

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
?>

<div class="wrap">
	<h1><?php esc_html_e( 'ParaDB Dashboard', 'wp-paradb' ); ?></h1>

	<div class="paradb-dashboard">
		<!-- Statistics Overview -->
		<div class="paradb-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
			
			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h3 style="margin: 0 0 10px 0; color: #0073aa;"><?php echo esc_html( number_format( $total_cases ) ); ?></h3>
				<p style="margin: 0; color: #666;"><?php esc_html_e( 'Total Cases', 'wp-paradb' ); ?></p>
			</div>

			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h3 style="margin: 0 0 10px 0; color: #46b450;"><?php echo esc_html( number_format( $open_cases ) ); ?></h3>
				<p style="margin: 0; color: #666;"><?php esc_html_e( 'Open Cases', 'wp-paradb' ); ?></p>
			</div>

			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h3 style="margin: 0 0 10px 0; color: #dc3232;"><?php echo esc_html( number_format( $closed_cases ) ); ?></h3>
				<p style="margin: 0; color: #666;"><?php esc_html_e( 'Closed Cases', 'wp-paradb' ); ?></p>
			</div>

			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h3 style="margin: 0 0 10px 0; color: #826eb4;"><?php echo esc_html( number_format( $total_reports ) ); ?></h3>
				<p style="margin: 0; color: #666;"><?php esc_html_e( 'Total Reports', 'wp-paradb' ); ?></p>
			</div>

			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h3 style="margin: 0 0 10px 0; color: #f56e28;"><?php echo esc_html( number_format( $total_clients ) ); ?></h3>
				<p style="margin: 0; color: #666;"><?php esc_html_e( 'Clients', 'wp-paradb' ); ?></p>
			</div>

			<div class="paradb-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
				<h3 style="margin: 0 0 10px 0; color: #00a0d2;"><?php echo esc_html( number_format( $total_evidence ) ); ?></h3>
				<p style="margin: 0; color: #666;"><?php esc_html_e( 'Evidence Files', 'wp-paradb' ); ?></p>
			</div>
		</div>

		<!-- Quick Actions -->
		<div class="paradb-quick-actions" style="margin: 30px 0;">
			<h2><?php esc_html_e( 'Quick Actions', 'wp-paradb' ); ?></h2>
			<div style="display: flex; gap: 10px; flex-wrap: wrap;">
				<?php if ( current_user_can( 'paradb_create_cases' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Create New Case', 'wp-paradb' ); ?>
					</a>
				<?php endif; ?>
				
				<?php if ( current_user_can( 'paradb_add_reports' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-reports&action=new' ) ); ?>" class="button">
						<?php esc_html_e( 'Add Report', 'wp-paradb' ); ?>
					</a>
				<?php endif; ?>
				
				<?php if ( current_user_can( 'paradb_add_clients' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-clients&action=new' ) ); ?>" class="button">
						<?php esc_html_e( 'Add Client', 'wp-paradb' ); ?>
					</a>
				<?php endif; ?>
				
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-cases' ) ); ?>" class="button">
					<?php esc_html_e( 'View All Cases', 'wp-paradb' ); ?>
				</a>
			</div>
		</div>

		<!-- Two Column Layout -->
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0;">
			
			<!-- Recent Cases -->
			<div class="paradb-panel" style="background: #fff; padding: 20px; border: 1px solid #ccc;">
				<h2><?php esc_html_e( 'Recent Cases', 'wp-paradb' ); ?></h2>
				<?php if ( ! empty( $recent_cases ) ) : ?>
					<table class="widefat" style="margin-top: 15px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Case Number', 'wp-paradb' ); ?></th>
								<th><?php esc_html_e( 'Name', 'wp-paradb' ); ?></th>
								<th><?php esc_html_e( 'Status', 'wp-paradb' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_cases as $case ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit&case_id=' . $case->case_id ) ); ?>">
											<?php echo esc_html( $case->case_number ); ?>
										</a>
									</td>
									<td><?php echo esc_html( $case->case_name ); ?></td>
									<td><?php echo esc_html( ucfirst( $case->case_status ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No cases found.', 'wp-paradb' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- My Assigned Cases -->
			<div class="paradb-panel" style="background: #fff; padding: 20px; border: 1px solid #ccc;">
				<h2><?php esc_html_e( 'My Assigned Cases', 'wp-paradb' ); ?></h2>
				<?php if ( ! empty( $my_cases ) ) : ?>
					<table class="widefat" style="margin-top: 15px;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Case Number', 'wp-paradb' ); ?></th>
								<th><?php esc_html_e( 'Name', 'wp-paradb' ); ?></th>
								<th><?php esc_html_e( 'Status', 'wp-paradb' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $my_cases as $case ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit&case_id=' . $case->case_id ) ); ?>">
											<?php echo esc_html( $case->case_number ); ?>
										</a>
									</td>
									<td><?php echo esc_html( $case->case_name ); ?></td>
									<td><?php echo esc_html( ucfirst( $case->case_status ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No cases assigned to you.', 'wp-paradb' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>