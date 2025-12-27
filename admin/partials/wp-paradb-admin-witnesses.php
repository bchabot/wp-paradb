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
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-settings.php';

// Handle creation action.
if ( isset( $_POST['save_witness'] ) && check_admin_referer( 'save_witness', 'witness_nonce' ) ) {
	$data = array(
		'first_name'           => sanitize_text_field( wp_unslash( $_POST['first_name'] ) ),
		'last_name'            => sanitize_text_field( wp_unslash( $_POST['last_name'] ) ),
		'account_email'        => sanitize_email( wp_unslash( $_POST['account_email'] ) ),
		'account_phone'        => sanitize_text_field( wp_unslash( $_POST['account_phone'] ) ),
		'account_address'      => sanitize_textarea_field( wp_unslash( $_POST['account_address'] ) ),
		'contact_preference'   => sanitize_text_field( wp_unslash( $_POST['contact_preference'] ) ),
		'incident_date'        => sanitize_text_field( wp_unslash( $_POST['incident_date'] ) ),
		'incident_time'        => sanitize_text_field( wp_unslash( $_POST['incident_time'] ) ),
		'incident_location'    => sanitize_text_field( wp_unslash( $_POST['incident_location'] ) ),
		'incident_description' => sanitize_textarea_field( wp_unslash( $_POST['incident_description'] ) ),
		'phenomena_types'      => isset( $_POST['phenomena_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['phenomena_types'] ) ) : array(),
		'consent_status'       => sanitize_text_field( wp_unslash( $_POST['consent_status'] ) ),
		'status'               => sanitize_text_field( wp_unslash( $_POST['status'] ) ),
		'case_id'              => absint( $_POST['case_id'] ),
		'privacy_accepted'     => 1,
	);

	$result = WP_ParaDB_Witness_Handler::create_witness_account( $data );

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Witness account created successfully.', 'wp-paradb' ) . '</p></div>';
		$action = 'list';
	}
}

// Handle convert to client action.
if ( isset( $_POST['convert_to_client'] ) && check_admin_referer( 'convert_witness', 'convert_nonce' ) ) {
	$witness_id = isset( $_POST['witness_id'] ) ? absint( $_POST['witness_id'] ) : 0;
	if ( $witness_id > 0 ) {
		$witness = WP_ParaDB_Witness_Handler::get_witness_account( $witness_id );
		if ( $witness ) {
			require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-client-handler.php';
			$client_data = array(
				'first_name' => $witness->first_name,
				'last_name'  => $witness->last_name,
				'email'      => $witness->account_email,
				'phone'      => $witness->account_phone,
				'address'    => $witness->account_address,
				'notes'      => sprintf( __( 'Converted from Witness Account #%d', 'wp-paradb' ), $witness_id ),
			);
			$client_id = WP_ParaDB_Client_Handler::create_client( $client_data );
			if ( ! is_wp_error( $client_id ) ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Witness successfully converted to Client.', 'wp-paradb' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html( $client_id->get_error_message() ) . '</p></div>';
			}
		}
	}
}

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

if ( 'new' === $action ) {
	$cases = WP_ParaDB_Case_Handler::get_cases( array( 'limit' => 1000 ) );
	$phenomena_types = WP_ParaDB_Settings::get_phenomena_types();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Add New Witness Account', 'wp-paradb' ); ?></h1>
		<form method="post" action="">
			<?php wp_nonce_field( 'save_witness', 'witness_nonce' ); ?>
			
			<h2 class="title"><?php esc_html_e( 'Contact Information', 'wp-paradb' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="first_name"><?php esc_html_e( 'First Name', 'wp-paradb' ); ?></label></th>
					<td><input type="text" name="first_name" id="first_name" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="last_name"><?php esc_html_e( 'Last Name', 'wp-paradb' ); ?></label></th>
					<td><input type="text" name="last_name" id="last_name" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="account_email"><?php esc_html_e( 'Email Address', 'wp-paradb' ); ?> *</label></th>
					<td><input type="email" name="account_email" id="account_email" class="regular-text" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="account_phone"><?php esc_html_e( 'Phone Number', 'wp-paradb' ); ?></label></th>
					<td><input type="text" name="account_phone" id="account_phone" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="account_address"><?php esc_html_e( 'Contact Address', 'wp-paradb' ); ?></label></th>
					<td>
						<div style="display:flex; gap: 5px;">
							<textarea name="account_address" id="account_address" class="regular-text" rows="2" style="flex:1;"></textarea>
							<button type="button" class="get-current-location button" data-target="#account_address" title="<?php esc_attr_e( 'Use my Current Location', 'wp-paradb' ); ?>">📍</button>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="contact_preference"><?php esc_html_e( 'Contact Preference', 'wp-paradb' ); ?></label></th>
					<td>
						<select name="contact_preference" id="contact_preference">
							<option value="email"><?php esc_html_e( 'Email', 'wp-paradb' ); ?></option>
							<option value="phone"><?php esc_html_e( 'Phone', 'wp-paradb' ); ?></option>
							<option value="none"><?php esc_html_e( 'Do not contact', 'wp-paradb' ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Incident Details', 'wp-paradb' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="incident_location"><?php esc_html_e( 'Incident Location', 'wp-paradb' ); ?> *</label></th>
					<td>
						<div style="display:flex; gap: 5px;">
							<input type="text" name="incident_location" id="incident_location" class="regular-text" required style="flex:1;" autocomplete="off">
							<button type="button" class="get-current-location button" data-target="#incident_location" title="<?php esc_attr_e( 'Use my Current Location', 'wp-paradb' ); ?>">📍</button>
						</div>
						<div style="margin-top: 5px;">
							<input type="hidden" name="latitude" id="latitude">
							<input type="hidden" name="longitude" id="longitude">
							<button type="button" id="geocode-address" class="button button-small"><?php esc_html_e( 'Find Address on Map', 'wp-paradb' ); ?></button>
						</div>
						<div id="location-map" class="location-map" style="height: 400px; margin-top: 10px; border: 1px solid #ccc;"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="incident_date"><?php esc_html_e( 'Incident Date & Time', 'wp-paradb' ); ?> *</label></th>
					<td>
						<input type="date" name="incident_date" id="incident_date" required>
						<input type="time" name="incident_time" id="incident_time">
					</td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'Phenomena Types', 'wp-paradb' ); ?></label></th>
					<td>
						<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 5px;">
							<?php foreach ( $phenomena_types as $key => $label ) : ?>
								<label><input type="checkbox" name="phenomena_types[]" value="<?php echo esc_attr( $key ); ?>"> <?php echo esc_html( $label ); ?></label>
							<?php endforeach; ?>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="incident_description"><?php esc_html_e( 'Description', 'wp-paradb' ); ?> *</label></th>
					<td><textarea name="incident_description" id="incident_description" rows="8" class="large-text" required></textarea></td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Administrative', 'wp-paradb' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="case_id"><?php esc_html_e( 'Link to Case', 'wp-paradb' ); ?></label></th>
					<td>
						<select name="case_id" id="case_id">
							<option value="0"><?php esc_html_e( 'None', 'wp-paradb' ); ?></option>
							<?php foreach ( $cases as $case ) : ?>
								<option value="<?php echo esc_attr( $case->case_id ); ?>"><?php echo esc_html( $case->case_number . ' - ' . $case->case_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="consent_status"><?php esc_html_e( 'Consent', 'wp-paradb' ); ?></label></th>
					<td>
						<select name="consent_status" id="consent_status">
							<option value="private"><?php esc_html_e( 'Private', 'wp-paradb' ); ?></option>
							<option value="anonymize"><?php esc_html_e( 'Anonymize', 'wp-paradb' ); ?></option>
							<option value="publish"><?php esc_html_e( 'Publish', 'wp-paradb' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="status"><?php esc_html_e( 'Status', 'wp-paradb' ); ?></label></th>
					<td>
						<select name="status" id="status">
							<option value="pending"><?php esc_html_e( 'Pending', 'wp-paradb' ); ?></option>
							<option value="approved"><?php esc_html_e( 'Approved', 'wp-paradb' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="save_witness" class="button button-primary" value="<?php esc_attr_e( 'Create Witness Account', 'wp-paradb' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-witnesses' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-paradb' ); ?></a>
			</p>
		</form>
	</div>
	<?php
} elseif ( 'view' === $action && $witness_id > 0 ) {
	$witness = WP_ParaDB_Witness_Handler::get_witness_account( $witness_id );
	if ( ! $witness ) {
		wp_die( esc_html__( 'Witness account not found.', 'wp-paradb' ) );
	}
	
	$linked_case = $witness->case_id ? WP_ParaDB_Case_Handler::get_case( $witness->case_id ) : null;
	$cases = WP_ParaDB_Case_Handler::get_cases( array( 'limit' => 1000, 'status' => 'open' ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Witness Account Details', 'wp-paradb' ); ?></h1>
		
		<div class="postbox" style="margin-top: 20px;">
			<div class="inside">
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Witness Name', 'wp-paradb' ); ?></th>
						<td><strong><?php echo esc_html( trim( ($witness->first_name ?? '') . ' ' . ($witness->last_name ?? '') ) ?: __( 'Anonymous', 'wp-paradb' ) ); ?></strong></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Email', 'wp-paradb' ); ?></th>
						<td><a href="mailto:<?php echo esc_attr( $witness->account_email ); ?>"><?php echo esc_html( $witness->account_email ); ?></a></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Phone', 'wp-paradb' ); ?></th>
						<td><?php echo esc_html( $witness->account_phone ?: '—' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Contact Address', 'wp-paradb' ); ?></th>
						<td><?php echo esc_html( $witness->account_address ?: '—' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Contact Preference', 'wp-paradb' ); ?></th>
						<td><?php echo esc_html( ucfirst( $witness->contact_preference ?? 'email' ) ); ?></td>
					</tr>
					<tr><td colspan="2"><hr></td></tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Incident Location', 'wp-paradb' ); ?></th>
						<td><?php echo esc_html( $witness->incident_location ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Incident Date', 'wp-paradb' ); ?></th>
						<td><?php echo esc_html( gmdate( 'F j, Y', strtotime( $witness->incident_date ) ) ); ?> <?php echo esc_html( $witness->incident_time ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Phenomena Types', 'wp-paradb' ); ?></th>
						<td><?php echo esc_html( is_array( $witness->phenomena_types ) ? implode( ', ', $witness->phenomena_types ) : '—' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Description', 'wp-paradb' ); ?></th>
						<td><?php echo wp_kses_post( wpautop( $witness->incident_description ) ); ?></td>
					</tr>
				</table>
			</div>
		</div>

		<div class="postbox">
			<h2 class="hndle"><?php esc_html_e( 'Actions', 'wp-paradb' ); ?></h2>
			<div class="inside">
				<form method="post" action="" style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
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
					<form method="post" action="" style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
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

				<form method="post" action="" style="display:inline-block;">
					<?php wp_nonce_field( 'convert_witness', 'convert_nonce' ); ?>
					<input type="hidden" name="witness_id" value="<?php echo esc_attr( $witness_id ); ?>">
					<input type="submit" name="convert_to_client" class="button button-secondary" value="<?php esc_attr_e( 'Create Client from Witness', 'wp-paradb' ); ?>" onclick="return confirm('<?php esc_attr_e( 'This will create a new Client record using this witness\'s information. Continue?', 'wp-paradb' ); ?>');">
				</form>
				
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-witnesses&action=delete&witness_id=' . $witness_id ), 'delete_witness_' . $witness_id ) ); ?>" class="button button-link-delete" style="margin-left: 10px;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this witness account?', 'wp-paradb' ); ?>');"><?php esc_html_e( 'Delete Account', 'wp-paradb' ); ?></a>
			</div>
		</div>
	</div>
	<?php
} else {
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
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-witnesses&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'wp-paradb' ); ?></a>
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
				<?php if ( $witnesses ) : foreach ( $witnesses as $w ) : ?>
					<tr>
						<td class="column-primary" data-colname="<?php esc_attr_e( 'Submitted', 'wp-paradb' ); ?>">
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-witnesses&action=view&witness_id=' . $w->account_id ) ); ?>">
									<?php echo esc_html( gmdate( 'M j, Y g:i a', strtotime( $w->date_submitted ) ) ); ?>
								</a>
							</strong>
							<div class="row-actions">
								<span class="view">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-witnesses&action=view&witness_id=' . $w->account_id ) ); ?>">
										<?php esc_html_e( 'View', 'wp-paradb' ); ?>
									</a>
								</span>
								|
								<span class="delete">
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-witnesses&action=delete&witness_id=' . $w->account_id ), 'delete_witness_' . $w->account_id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wp-paradb' ); ?>');">
										<?php esc_html_e( 'Delete', 'wp-paradb' ); ?>
									</a>
								</span>
							</div>
						</td>
						<td><?php echo esc_html( trim( ($w->first_name ?? '') . ' ' . ($w->last_name ?? '') ) ?: __( 'Anonymous', 'wp-paradb' ) ); ?></td>
						<td><?php echo esc_html( $w->incident_location ); ?></td>
						<td><?php echo esc_html( ucfirst( $w->status ) ); ?></td>
					</tr>
				<?php endforeach; else : ?>
					<tr><td colspan="4" style="text-align: center; padding: 20px;"><?php esc_html_e( 'No reports found.', 'wp-paradb' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
?>
<div id="paradb-map-modal" style="display:none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
	<div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; height: 80%; position: relative;">
		<span id="close-map-modal" style="position: absolute; right: 10px; top: 5px; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
		<div id="map-canvas" style="width: 100%; height: 100%;"></div>
	</div>
</div>
