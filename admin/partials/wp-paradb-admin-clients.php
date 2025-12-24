<?php
/**
 * Admin clients management view
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
if ( ! current_user_can( 'paradb_view_clients' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-paradb' ) );
}

// Load required classes.
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-client-handler.php';

// Handle form submission.
if ( isset( $_POST['save_client'] ) && check_admin_referer( 'save_client', 'client_nonce' ) ) {
	$client_id = isset( $_POST['client_id'] ) ? absint( $_POST['client_id'] ) : 0;
	
	$client_data = array(
		'first_name'         => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
		'last_name'          => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
		'email'              => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'phone'              => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
		'address'            => isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '',
		'city'               => isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '',
		'state'              => isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '',
		'zip'                => isset( $_POST['zip'] ) ? sanitize_text_field( wp_unslash( $_POST['zip'] ) ) : '',
		'country'            => isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : 'United States',
		'preferred_contact'  => isset( $_POST['preferred_contact'] ) ? sanitize_text_field( wp_unslash( $_POST['preferred_contact'] ) ) : 'email',
		'notes'              => isset( $_POST['notes'] ) ? wp_kses_post( wp_unslash( $_POST['notes'] ) ) : '',
		'consent_to_publish' => isset( $_POST['consent_to_publish'] ) ? 1 : 0,
		'anonymize_data'     => isset( $_POST['anonymize_data'] ) ? 1 : 0,
	);

	if ( $client_id > 0 ) {
		$result = WP_ParaDB_Client_Handler::update_client( $client_id, $client_data );
	} else {
		$result = WP_ParaDB_Client_Handler::create_client( $client_data );
	}

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Client saved successfully.', 'wp-paradb' ) . '</p></div>';
	}
}

// Handle delete action.
if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['client_id'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_client_' . absint( $_GET['client_id'] ) ) ) {
		if ( current_user_can( 'paradb_delete_clients' ) ) {
			$result = WP_ParaDB_Client_Handler::delete_client( absint( $_GET['client_id'] ) );
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Client deleted successfully.', 'wp-paradb' ) . '</p></div>';
			}
		}
	}
}

// Get action.
$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
$client_id = isset( $_GET['client_id'] ) ? absint( $_GET['client_id'] ) : 0;

if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
	// Show form.
	$client = null;
	if ( 'edit' === $action && $client_id > 0 ) {
		$client = WP_ParaDB_Client_Handler::get_client( $client_id );
		if ( ! $client ) {
			wp_die( esc_html__( 'Client not found.', 'wp-paradb' ) );
		}
	}
	?>
	
	<div class="wrap">
		<h1><?php echo 'new' === $action ? esc_html__( 'Add Client', 'wp-paradb' ) : esc_html__( 'Edit Client', 'wp-paradb' ); ?></h1>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'save_client', 'client_nonce' ); ?>
			<?php if ( $client ) : ?>
				<input type="hidden" name="client_id" value="<?php echo esc_attr( $client->client_id ); ?>">
			<?php endif; ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="first_name"><?php esc_html_e( 'First Name', 'wp-paradb' ); ?> *</label>
					</th>
					<td>
						<input type="text" name="first_name" id="first_name" class="regular-text" value="<?php echo $client ? esc_attr( $client->first_name ) : ''; ?>" required>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="last_name"><?php esc_html_e( 'Last Name', 'wp-paradb' ); ?> *</label>
					</th>
					<td>
						<input type="text" name="last_name" id="last_name" class="regular-text" value="<?php echo $client ? esc_attr( $client->last_name ) : ''; ?>" required>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="email"><?php esc_html_e( 'Email', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="email" name="email" id="email" class="regular-text" value="<?php echo $client ? esc_attr( $client->email ) : ''; ?>">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="phone"><?php esc_html_e( 'Phone', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="phone" id="phone" class="regular-text" value="<?php echo $client ? esc_attr( $client->phone ) : ''; ?>">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="preferred_contact"><?php esc_html_e( 'Preferred Contact Method', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<select name="preferred_contact" id="preferred_contact">
							<option value="email" <?php selected( $client ? $client->preferred_contact : 'email', 'email' ); ?>><?php esc_html_e( 'Email', 'wp-paradb' ); ?></option>
							<option value="phone" <?php selected( $client ? $client->preferred_contact : 'email', 'phone' ); ?>><?php esc_html_e( 'Phone', 'wp-paradb' ); ?></option>
							<option value="mail" <?php selected( $client ? $client->preferred_contact : 'email', 'mail' ); ?>><?php esc_html_e( 'Mail', 'wp-paradb' ); ?></option>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="address"><?php esc_html_e( 'Address', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="address" id="address" class="regular-text" value="<?php echo $client ? esc_attr( $client->address ) : ''; ?>">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="city"><?php esc_html_e( 'City', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="city" id="city" class="regular-text" value="<?php echo $client ? esc_attr( $client->city ) : ''; ?>">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="state"><?php esc_html_e( 'State/Province', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="state" id="state" class="regular-text" value="<?php echo $client ? esc_attr( $client->state ) : ''; ?>">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="zip"><?php esc_html_e( 'ZIP/Postal Code', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="zip" id="zip" class="regular-text" value="<?php echo $client ? esc_attr( $client->zip ) : ''; ?>">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="country"><?php esc_html_e( 'Country', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="country" id="country" class="regular-text" value="<?php echo $client ? esc_attr( $client->country ) : 'United States'; ?>">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="notes"><?php esc_html_e( 'Notes', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<textarea name="notes" id="notes" rows="5" class="large-text"><?php echo $client ? esc_textarea( $client->notes ) : ''; ?></textarea>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'Privacy Settings', 'wp-paradb' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="consent_to_publish" value="1" <?php checked( $client ? $client->consent_to_publish : 0, 1 ); ?>>
							<?php esc_html_e( 'Client has consented to publish case information', 'wp-paradb' ); ?>
						</label>
						<br>
						<label>
							<input type="checkbox" name="anonymize_data" value="1" <?php checked( $client ? $client->anonymize_data : 1, 1 ); ?>>
							<?php esc_html_e( 'Anonymize client data in published reports', 'wp-paradb' ); ?>
						</label>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<input type="submit" name="save_client" class="button button-primary" value="<?php echo 'new' === $action ? esc_attr__( 'Create Client', 'wp-paradb' ) : esc_attr__( 'Update Client', 'wp-paradb' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-clients' ) ); ?>" class="button">
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
		'orderby' => 'last_name',
		'order'   => 'ASC',
	);
	
	$clients = WP_ParaDB_Client_Handler::get_clients( $args );
	?>
	
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Clients', 'wp-paradb' ); ?></h1>
		
		<?php if ( current_user_can( 'paradb_add_clients' ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-clients&action=new' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'wp-paradb' ); ?>
			</a>
		<?php endif; ?>
		
		<hr class="wp-header-end">
		
		<div class="tablenav top">
			<div class="alignleft actions">
				<form method="get" action="">
					<input type="hidden" name="page" value="wp-paradb-clients">
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search clients...', 'wp-paradb' ); ?>">
					<input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'wp-paradb' ); ?>">
				</form>
			</div>
		</div>
		
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Name', 'wp-paradb' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Email', 'wp-paradb' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Phone', 'wp-paradb' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Location', 'wp-paradb' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Consent', 'wp-paradb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $clients ) ) : ?>
					<?php foreach ( $clients as $client ) : ?>
						<tr>
							<td class="column-primary" data-colname="<?php esc_attr_e( 'Name', 'wp-paradb' ); ?>">
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-clients&action=edit&client_id=' . $client->client_id ) ); ?>">
										<?php echo esc_html( WP_ParaDB_Client_Handler::get_client_display_name( $client, false ) ); ?>
									</a>
								</strong>
								<div class="row-actions">
									<span class="edit">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-clients&action=edit&client_id=' . $client->client_id ) ); ?>">
											<?php esc_html_e( 'Edit', 'wp-paradb' ); ?>
										</a>
									</span>
									<?php if ( current_user_can( 'paradb_delete_clients' ) ) : ?>
										|
										<span class="delete">
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-clients&action=delete&client_id=' . $client->client_id ), 'delete_client_' . $client->client_id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this client? This will fail if there are cases associated with this client.', 'wp-paradb' ); ?>');">
												<?php esc_html_e( 'Delete', 'wp-paradb' ); ?>
											</a>
										</span>
									<?php endif; ?>
								</div>
							</td>
							<td data-colname="<?php esc_attr_e( 'Email', 'wp-paradb' ); ?>">
								<?php echo $client->email ? esc_html( $client->email ) : '—'; ?>
							</td>
							<td data-colname="<?php esc_attr_e( 'Phone', 'wp-paradb' ); ?>">
								<?php echo $client->phone ? esc_html( $client->phone ) : '—'; ?>
							</td>
							<td data-colname="<?php esc_attr_e( 'Location', 'wp-paradb' ); ?>">
								<?php
								if ( $client->city && $client->state ) {
									echo esc_html( $client->city . ', ' . $client->state );
								} elseif ( $client->city ) {
									echo esc_html( $client->city );
								} else {
									echo '—';
								}
								?>
							</td>
							<td data-colname="<?php esc_attr_e( 'Consent', 'wp-paradb' ); ?>">
								<?php if ( $client->consent_to_publish ) : ?>
									<span style="color: #46b450;">✓ <?php esc_html_e( 'Yes', 'wp-paradb' ); ?></span>
								<?php else : ?>
									<span style="color: #dc3232;">✗ <?php esc_html_e( 'No', 'wp-paradb' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5" style="text-align: center; padding: 20px;">
							<?php esc_html_e( 'No clients found.', 'wp-paradb' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	
	<?php
}