<?php
/**
 * Admin cases list view
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

// Handle actions.
if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['case_id'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_case_' . absint( $_GET['case_id'] ) ) ) {
		if ( current_user_can( 'paradb_delete_cases' ) ) {
			$result = WP_ParaDB_Case_Handler::delete_case( absint( $_GET['case_id'] ) );
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Case deleted successfully.', 'wp-paradb' ) . '</p></div>';
			}
		}
	}
}

// Get filter parameters.
$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$assigned_to = isset( $_GET['assigned_to'] ) ? absint( $_GET['assigned_to'] ) : 0;
$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$per_page = 20;

// Get cases.
$args = array(
	'status'      => $status,
	'assigned_to' => $assigned_to,
	'search'      => $search,
	'limit'       => $per_page,
	'offset'      => ( $paged - 1 ) * $per_page,
	'orderby'     => 'date_created',
	'order'       => 'DESC',
);

$cases = WP_ParaDB_Case_Handler::get_cases( $args );

// Get total count for pagination.
global $wpdb;
$where_clause = '1=1';
if ( ! empty( $status ) ) {
	$where_clause .= $wpdb->prepare( ' AND case_status = %s', $status );
}
if ( $assigned_to > 0 ) {
	$where_clause .= $wpdb->prepare( ' AND assigned_to = %d', $assigned_to );
}
if ( ! empty( $search ) ) {
	$search_term = '%' . $wpdb->esc_like( $search ) . '%';
	$where_clause .= $wpdb->prepare( ' AND (case_name LIKE %s OR case_number LIKE %s)', $search_term, $search_term );
}
$total_cases = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}paradb_cases WHERE {$where_clause}" );
$total_pages = ceil( $total_cases / $per_page );

// Get status options.
$options = get_option( 'wp_paradb_options', array() );
$case_statuses = isset( $options['case_statuses'] ) ? $options['case_statuses'] : array();

// Get investigators for filter.
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-roles.php';
$investigators = WP_ParaDB_Roles::get_all_paradb_users();
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Cases', 'wp-paradb' ); ?></h1>
	
	<?php if ( current_user_can( 'paradb_create_cases' ) ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Add New', 'wp-paradb' ); ?>
		</a>
	<?php endif; ?>

	<hr class="wp-header-end">

	<!-- Filters -->
	<div class="tablenav top">
		<div class="alignleft actions">
			<form method="get" action="">
				<input type="hidden" name="page" value="wp-paradb-cases">
				
				<select name="status" id="filter-by-status">
					<option value=""><?php esc_html_e( 'All Statuses', 'wp-paradb' ); ?></option>
					<?php foreach ( $case_statuses as $status_key => $status_label ) : ?>
						<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status, $status_key ); ?>>
							<?php echo esc_html( $status_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<select name="assigned_to" id="filter-by-assignee">
					<option value=""><?php esc_html_e( 'All Assignees', 'wp-paradb' ); ?></option>
					<?php foreach ( $investigators as $investigator ) : ?>
						<option value="<?php echo esc_attr( $investigator->ID ); ?>" <?php selected( $assigned_to, $investigator->ID ); ?>>
							<?php echo esc_html( $investigator->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				
				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wp-paradb' ); ?>">
			</form>
		</div>
		
		<div class="alignleft actions">
			<form method="get" action="">
				<input type="hidden" name="page" value="wp-paradb-cases">
				<?php if ( ! empty( $status ) ) : ?>
					<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
				<?php endif; ?>
				
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search cases...', 'wp-paradb' ); ?>">
				<input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'wp-paradb' ); ?>">
			</form>
		</div>
		
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php
				printf(
					_n( '%s case', '%s cases', $total_cases, 'wp-paradb' ),
					number_format_i18n( $total_cases )
				);
				?>
			</span>
		</div>
	</div>

	<!-- Cases Table -->
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Case Number', 'wp-paradb' ); ?></th>
				<th scope="col" class="manage-column"><?php esc_html_e( 'Name', 'wp-paradb' ); ?></th>
				<th scope="col" class="manage-column"><?php esc_html_e( 'Assignee', 'wp-paradb' ); ?></th>
				<th scope="col" class="manage-column"><?php esc_html_e( 'Stats', 'wp-paradb' ); ?></th>
				<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'wp-paradb' ); ?></th>
				<th scope="col" class="manage-column"><?php esc_html_e( 'Created', 'wp-paradb' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $cases ) ) : ?>
				<?php foreach ( $cases as $case ) : 
					$assignee = $case->assigned_to ? get_userdata( $case->assigned_to ) : null;
					
					require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-report-handler.php';
					require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';
					require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-evidence-handler.php';
					
					$report_count = WP_ParaDB_Report_Handler::get_case_report_count( $case->case_id );
					$activity_count = WP_ParaDB_Activity_Handler::get_case_activity_count( $case->case_id );
					$evidence_count = WP_ParaDB_Evidence_Handler::get_case_evidence_count( $case->case_id );
					?>
					<tr>
						<td class="column-primary" data-colname="<?php esc_attr_e( 'Case Number', 'wp-paradb' ); ?>">
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit&case_id=' . $case->case_id ) ); ?>">
									<?php echo esc_html( $case->case_number ); ?>
								</a>
							</strong>
							<div class="row-actions">
								<span class="edit">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit&case_id=' . $case->case_id ) ); ?>">
										<?php esc_html_e( 'Edit', 'wp-paradb' ); ?>
									</a>
								</span>
								<?php if ( current_user_can( 'paradb_delete_cases' ) ) : ?>
									|
									<span class="delete">
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-cases&action=delete&case_id=' . $case->case_id ), 'delete_case_' . $case->case_id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this case?', 'wp-paradb' ); ?>');">
											<?php esc_html_e( 'Delete', 'wp-paradb' ); ?>
										</a>
									</span>
								<?php endif; ?>
							</div>
						</td>
						<td data-colname="<?php esc_attr_e( 'Name', 'wp-paradb' ); ?>">
							<?php echo esc_html( $case->case_name ); ?><br>
							<small><?php echo esc_html( $case->location_name ? $case->location_name : $case->location_city ); ?></small>
						</td>
						<td data-colname="<?php esc_attr_e( 'Assignee', 'wp-paradb' ); ?>">
							<?php echo $assignee ? esc_html( $assignee->display_name ) : '—'; ?>
						</td>
						<td data-colname="<?php esc_attr_e( 'Stats', 'wp-paradb' ); ?>">
							<span class="dashicons dashicons-clipboard" title="<?php esc_attr_e( 'Reports', 'wp-paradb' ); ?>"></span> <?php echo esc_html( $report_count ); ?>&nbsp;
							<span class="dashicons dashicons-performance" title="<?php esc_attr_e( 'Activities', 'wp-paradb' ); ?>"></span> <?php echo esc_html( $activity_count ); ?>&nbsp;
							<span class="dashicons dashicons-format-image" title="<?php esc_attr_e( 'Evidence', 'wp-paradb' ); ?>"></span> <?php echo esc_html( $evidence_count ); ?>
						</td>
						<td data-colname="<?php esc_attr_e( 'Status', 'wp-paradb' ); ?>">
							<?php
							$status_label = isset( $case_statuses[ $case->case_status ] ) ? $case_statuses[ $case->case_status ] : ucfirst( $case->case_status );
							echo esc_html( $status_label );
							?>
						</td>
						<td data-colname="<?php esc_attr_e( 'Created', 'wp-paradb' ); ?>">
							<?php echo esc_html( gmdate( 'M j, Y', strtotime( $case->date_created ) ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6" style="text-align: center; padding: 20px;">
						<?php esc_html_e( 'No cases found.', 'wp-paradb' ); ?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Pagination -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php
				$page_links = paginate_links( array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'prev_text' => __( '&laquo;', 'wp-paradb' ),
					'next_text' => __( '&raquo;', 'wp-paradb' ),
					'total'     => $total_pages,
					'current'   => $paged,
				) );

				if ( $page_links ) {
					echo '<span class="pagination-links">' . $page_links . '</span>';
				}
				?>
			</div>
		</div>
	<?php endif; ?>
</div>