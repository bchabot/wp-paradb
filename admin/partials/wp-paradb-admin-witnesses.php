<?php
/**
 * Admin witness accounts management view
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
if ( ! current_user_can( 'paradb_manage_witnesses' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-paradb' ) );
}

// Load required classes.
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-witness-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';

// Handle review action.
if ( isset( $_POST['review_witness'] ) && check_admin_referer( 'review_witness', 'witness_nonce' ) ) {
	$witness_id = isset( $_POST['witness_id'] ) ? absint( $_POST['witness_id'] ) : 0;
	$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'pending';
	
	if ( $witness_id > 0 ) {
		$result = WP_ParaDB_Witness_Handler::review_account( $witness_id, $status );
		
		if ( is_wp_error( $result ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Witness account reviewed successfully.', 'wp-paradb' ) . '</p></div>';
		}
	}
}

// Handle link to case action.
if ( isset( $_POST['link_to_case'] ) && check_admin_referer( 'link_witness', 'link_nonce' ) ) {
	$witness_id = isset( $_POST['witness_id'] ) ? absint( $_POST['witness_id'] ) : 0;
	$case_id = isset( $_POST['case_id'] ) ? absint( $_POST['case_id'] ) : 0;
	
	if ( $witness_id > 0 && $case_id > 0 ) {
		$result = WP_ParaDB_Witness_Handler::link_to_case( $witness_id, $case_id );
		
		if ( is_wp_error( $result ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Witness account linked to case successfully.', 'wp-paradb' ) . '</p></div>';
		}
	}
}

// Handle delete action.
if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['witness_id'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_witness_' . absint( $_GET['witness_id'] ) ) ) {
		$result = WP_ParaDB_Witness_Handler::delete_witness_account( absint( $_GET['witness_id'] ) );
		if ( ! is_wp_error( $result ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Witness account deleted successfully.', 'wp-paradb' ) . '</p></div>';
		}
	}
}

// Get action.
$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
$witness_id = isset( $_GET['witness_id'] ) ? absint( $_GET['witness_id'] ) : 0;

if ( 'view' === $action && $witness_id > 0 ) {
	// Show detailed view.
	$witness = WP_ParaDB_Witness_Handler::get_witness_account( $witness_id );
	if ( ! $witness ) {
		wp_die( esc_html__( 'Witness account not found.', 'wp-paradb' ) );
	}
	
	$linked_case = null;
	if ( $witness->case_id ) {
		$linked_case = WP_ParaDB_Case_Handler::get_case( $witness->case_id );
	}
	
	$cases = WP_ParaDB_Case_Handler::get_cases( array( 'limit' => 1000, 'status' => 'open' ) );
	?>
	
	<div class="wrap">
		<h1><?php esc_html_e( 'Witness Account Details', 'wp-paradb' ); ?></h1>
		
		<div style="background: #fff; padding: 20px; border: 1px solid #ccc; margin-top: 20px;">
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Submission Date', 'wp-paradb' ); ?></th>
					<td><?php echo esc_html( gmdate( 'F j, Y g:i a', strtotime( $witness->date_submitted ) ) ); ?></td>
				</tr>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'Status', 'wp-paradb' ); ?></th>
					<td>
						<?php
						$status_colors = array(
							'pending'  => '#f0ad4e',
							'approved' => '#5cb85c',
							'rejected' => '#d9534f',
							'spam'     => '#999',
						);
						$color = isset( $status_colors[ $witness->status ] ) ? $status_colors[ $witness->status ] : '#999';
						?>
						<span style="padding: 3px 8px; background: <?php echo esc_attr( $color ); ?>; color: #fff; border-radius: 3px;">
							<?php echo esc_html( ucfirst( $witness->status ) ); ?>
						</span>
					</td>
				</tr>
				
				<?php if ( ! empty( $witness->account_name ) ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Witness Name', 'wp-paradb' ); ?></th>
						<td><?php echo esc_html( $witness->account_name ); ?></td>
					</tr>
					
					<?php if ( $witness->account_email ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Email', 'wp-paradb' ); ?></th>
							<td><a href="mailto:<?php echo esc_attr( $witness->account_email ); ?>"><?php echo esc_html( $witness->account_email ); ?></a></td>
						</tr>
					<?php endif; ?>
					
					<?php if ( $witness->account_phone ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Phone', 'wp-paradb' ); ?></th>
							<td><?php echo esc_html( $witness->account_phone ); ?></td>
						</tr>
					<?php endif; ?>
				<?php else : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Submission Type', 'wp-paradb' ); ?></th>
						<td><em><?php esc_html_e( 'Anonymous Submission', 'wp-paradb' ); ?></em></td>
					</tr>
					<?php if ( $witness->account_email ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Email (Private)', 'wp-paradb' ); ?></th>
							<td><a href="mailto:<?php echo esc_attr( $witness->account_email ); ?>"><?php echo esc_html( $witness->account_email ); ?></a></td>
						</tr>
					<?php endif; ?>
				<?php endif; ?>
				
				<?php if ( $witness->incident_date ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Incident Date', 'wp-paradb' ); ?></th>
						<td><?php echo esc_html( gmdate( 'F j, Y', strtotime( $witness->incident_date ) ) ); ?></td>
					</tr>
				<?php endif; ?>
				
				<?php if ( $witness->incident_location ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Incident Location', 'wp-paradb' ); ?></th>
						<td><?php echo esc_html( $witness->incident_location ); ?></td>
					</tr>
				<?php endif; ?>
				
				<?php if ( ! empty( $witness->phenomena_types ) ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Phenomena Types', 'wp-paradb' ); ?></th>
						<td><?php echo esc_html( is_array( $witness->phenomena_types ) ? implode( ', ', $witness->phenomena_types ) : $witness->phenomena_types ); ?></td>
					</tr>
				<?php endif; ?>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'Incident Description', 'wp-paradb' ); ?></th>
					<td><?php echo wp_kses_post( wpautop( $witness->incident_description ) ); ?></td>
				</tr>
				
				<?php if ( $linked_case ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Linked Case', 'wp-paradb' ); ?></th>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-case-edit&case_id=' . $linked_case->case_id ) ); ?>">
								<?php echo esc_html( $linked_case->case_number . ' - ' . $linked_case->case_name ); ?>
							</a>
						</td>
					</tr>
				<?php endif; ?>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'IP Address', 'wp-paradb' ); ?></th>
					<td><?php echo esc_html( $witness->ip_address ); ?></td>
				</tr>
			</table>
		</div>
		
		<div style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Actions', 'wp-paradb' ); ?></h2>
			
			<div style="background: #fff; padding: 20px; border: 1px solid #ccc;">
				<form method="post" action="" style="margin-bottom: 20px;">
					<?php wp_nonce_field( 'review_witness', 'witness_nonce' ); ?>
					<input type="hidden" name="witness_id" value="<?php echo esc_attr( $witness_id ); ?>">
					
					<p>
						<label for="status"><strong><?php esc_html_e( 'Review Status', 'wp-paradb' ); ?></strong></label><br>
						<select name="status" id="status">
							<option value="pending" <?php selected( $witness->status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'wp-paradb' ); ?></option>
							<option value="approved" <?php selected( $witness->status, 'approved' ); ?>><?php esc_html_e( 'Approved', 'wp-paradb' ); ?></option>
							<option value="rejected" <?php selected( $witness->status, 'rejected' ); ?>><?php esc_html_e( 'Rejected', 'wp-paradb' ); ?></option>
							<option value="spam" <?php selected( $witness->status, 'spam' ); ?>><?php esc_html_e( 'Spam', 'wp-paradb' ); ?></option>
						</select>
						<input type="submit" name="review_witness" class="button button-primary" value="<?php esc_attr_e( 'Update Status', 'wp-paradb' ); ?>">
					</p>
				</form>
				
				<?php if ( ! $linked_case ) : ?>
					<form method="post" action="">
						<?php wp_nonce_field( 'link_witness', 'link_nonce' ); ?>
						<input type="hidden" name="witness_id" value="<?php echo esc_attr( $witness_id ); ?>">
						
						<p>
							<label for="case_id"><strong><?php esc_html_e( 'Link to Case', 'wp-paradb' ); ?></strong></label><br>
							<select name="case_id" id="case_id" class="regular-text">
								<option value=""><?php esc_html_e( 'Select Case', 'wp-paradb' ); ?></option>
								<?php foreach ( $cases as $case ) : ?>
									<option value="<?php echo esc_attr( $case->case_id ); ?>">
										<?php echo esc_html( $case->case_number . ' - ' . $case->case_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<input type="submit" name="link_to_case" class="button" value="<?php esc_attr_e( 'Link to Case', 'wp-paradb' ); ?>">
						</p>
					</form>
				<?php endif; ?>
				
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-witnesses' ) ); ?>" class="button">
						<?php esc_html_e( 'Back to List', 'wp-paradb' ); ?>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-witnesses&action=delete&witness_id=' . $witness_id ), 'delete_witness_' . $witness_id ) ); ?>" class="button" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this witness account?', 'wp-paradb' ); ?>');">
						<?php esc_html_e( 'Delete', 'wp-paradb' ); ?>
					</a>
				</p>
			</div>
		</div>
	</div>
	
	<?php
} else {
	// Show list.
	$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
	$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	$per_page = 20;
	
	$args = array(
		'status'  => $status_filter,
		'limit'   => $per_page,
		'offset'  => ( $paged - 1 ) * $per_page,
		'orderby' => 'date_submitted',
		'order'   => 'DESC',
	);
	
	$witnesses = WP_ParaDB_Witness_Handler::get_witness_accounts( $args );
	?>
	
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Witness Accounts', 'wp-paradb' ); ?></h1>
		
		<hr class="wp-header-end">
		
		<div class="tablenav top">
			<div class="alignleft actions">
				<form method="get" action="">
					<input type="hidden" name="page" value="wp-paradb-witnesses">
					
					<select name="status" id="filter-by-status">
						<option value=""><?php esc_html_e( 'All Statuses', 'wp-paradb' ); ?></option>
						<option value="pending" <?php selected( $status_filter, 'pending' ); ?>><?php esc_html_e( 'Pending', 'wp-paradb' ); ?></option>
						<option value="approved" <?php selected( $status_filter, 'approved' ); ?>><?php esc_html_e( 'Approved', 'wp-paradb' ); ?></option>
						<option value="rejected" <?php selected( $status_filter, 'rejected' ); ?>><?php esc_html_e( 'Rejected', 'wp-paradb' ); ?></option>
						<option value="spam" <?php selected( $status_filter, 'spam' ); ?>><?php esc_html_e( 'Spam', 'wp-paradb' ); ?></option>
					</select>
					
					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wp-paradb' ); ?>">
				</form>
			</div>
		</div>
		
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Submitted', 'wp-paradb' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Witness', 'wp-paradb' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Location', 'wp-paradb' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'wp-paradb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $witnesses ) ) : ?>
					<?php foreach ( $witnesses as $witness ) : ?>
						<tr>
							<td class="column-primary" data-colname="<?php esc_attr_e( 'Submitted', 'wp-paradb' ); ?>">
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-witnesses&action=view&witness_id=' . $witness->account_id ) ); ?>">
										<?php echo esc_html( gmdate( 'M j, Y g:i a', strtotime( $witness->date_submitted ) ) ); ?>
									</a>
								</strong>
								<div class="row-actions">
									<span class="view">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-witnesses&action=view&witness_id=' . $witness->account_id ) ); ?>">
											<?php esc_html_e( 'View', 'wp-paradb' ); ?>
										</a>
									</span>
									|
									<span class="delete">
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-witnesses&action=delete&witness_id=' . $witness->account_id ), 'delete_witness_' . $witness->account_id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wp-paradb' ); ?>');">
											<?php esc_html_e( 'Delete', 'wp-paradb' ); ?>
										</a>
									</span>
								</div>
							</td>
							<td data-colname="<?php esc_attr_e( 'Witness', 'wp-paradb' ); ?>">
								<?php
								if ( empty( $witness->account_name ) ) {
									esc_html_e( 'Anonymous', 'wp-paradb' );
								} else {
									echo esc_html( $witness->account_name );
								}
								?>
							</td>
							<td data-colname="<?php esc_attr_e( 'Location', 'wp-paradb' ); ?>">
								<?php echo esc_html( $witness->incident_location ? $witness->incident_location : '—' ); ?>
							</td>
							<td data-colname="<?php esc_attr_e( 'Status', 'wp-paradb' ); ?>">
								<?php echo esc_html( ucfirst( $witness->status ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="4" style="text-align: center; padding: 20px;">
							<?php esc_html_e( 'No witness accounts found.', 'wp-paradb' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	
	<?php
}