<?php
/**
 * Public field log book functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.4.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle public field log book display and submission
 */
class WP_ParaDB_Log_Book {

	public function __construct() {
		add_shortcode( 'paradb_log_book', array( $this, 'render_log_book' ) );
		add_action( 'wp_ajax_paradb_submit_field_log', array( $this, 'handle_log_submission' ) );
	}

	public function render_log_book( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p>' . __( 'You must be logged in to access the Field Log Book.', 'wp-paradb' ) . '</p>';
		}

		if ( ! current_user_can( 'paradb_view_cases' ) ) {
			return '<p>' . __( 'You do not have permission to use the Field Log Book.', 'wp-paradb' ) . '</p>';
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';

		$cases = WP_ParaDB_Case_Handler::get_cases( array( 'limit' => 1000 ) );

		ob_start();
		?>
		<div class="paradb-log-book-container mobile-optimized">
			<h3><?php esc_html_e( 'Field Log Book', 'wp-paradb' ); ?></h3>
			<div class="paradb-form-messages"></div>

			<form id="paradb-field-log-form" enctype="multipart/form-data">
				<?php wp_nonce_field( 'paradb_submit_log', 'paradb_log_nonce' ); ?>
				
				<p>
					<label for="log_case_id"><?php esc_html_e( 'Active Case', 'wp-paradb' ); ?> *</label><br>
					<select name="case_id" id="log_case_id" class="widefat" required>
						<option value=""><?php esc_html_e( '— Select Case —', 'wp-paradb' ); ?></option>
						<?php foreach ( $cases as $case ) : ?>
							<option value="<?php echo esc_attr( $case->case_id ); ?>"><?php echo esc_html( $case->case_number . ' - ' . $case->case_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<button type="button" id="get-location" class="button button-secondary"><?php esc_html_e( '📍 Get Precise Location', 'wp-paradb' ); ?></button>
					<span id="location-status"></span>
					<input type="hidden" name="latitude" id="log_lat">
					<input type="hidden" name="longitude" id="log_lng">
				</p>

				<p>
					<label for="log_content"><?php esc_html_e( 'Log Notes', 'wp-paradb' ); ?> *</label><br>
					<textarea name="log_content" id="log_content" class="widefat" rows="5" required placeholder="<?php esc_attr_e( 'What are you observing right now?', 'wp-paradb' ); ?>"></textarea>
				</p>

				<p>
					<label for="log_evidence"><?php esc_html_e( 'Upload Evidence (Photo/Audio)', 'wp-paradb' ); ?></label><br>
					<input type="file" name="log_evidence" id="log_evidence" accept="image/*,audio/*,video/*" capture="environment">
				</p>

				<p>
					<button type="submit" class="button button-primary button-large" style="width: 100%; height: 50px; font-size: 1.2em;">
						<?php esc_html_e( 'Log Entry', 'wp-paradb' ); ?>
					</button>
				</p>
			</form>
		</div>

		<script>
		(function($) {
			$('#get-location').on('click', function() {
				var $status = $('#location-status');
				if (!navigator.geolocation) {
					$status.text('Geolocation not supported');
					return;
				}
				$status.text('Locating...');
				navigator.geolocation.getCurrentPosition(function(pos) {
					$('#log_lat').val(pos.coords.latitude);
					$('#log_lng').val(pos.coords.longitude);
					$status.text('Fixed: ' + pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4));
				}, function(err) {
					$status.text('Error: ' + err.message);
				});
			});

			$('#paradb-field-log-form').on('submit', function(e) {
				e.preventDefault();
				var $form = $(this);
				var formData = new FormData(this);
				formData.append('action', 'paradb_submit_field_log');

				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(res) {
						if (res.success) {
							alert('Logged successfully!');
							$('#log_content').val('');
							$('#log_evidence').val('');
						} else {
							alert('Error: ' + res.data.message);
						}
					}
				});
			});
		})(jQuery);
		</script>
		<style>
		.paradb-log-book-container.mobile-optimized {
			max-width: 500px;
			margin: 0 auto;
			padding: 15px;
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 8px;
		}
		.paradb-log-book-container input, .paradb-log-book-container textarea, .paradb-log-book-container select {
			margin-bottom: 10px;
		}
		</style>
		<?php
		return ob_get_clean();
	}

	public function handle_log_submission() {
		check_ajax_referer( 'paradb_submit_log', 'paradb_log_nonce' );

		if ( ! is_user_logged_in() || ! current_user_can( 'paradb_view_cases' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wp-paradb' ) ) );
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';
		
		$log_id = WP_ParaDB_Field_Log_Handler::create_log( array(
			'case_id'     => $_POST['case_id'],
			'log_content' => $_POST['log_content'],
			'latitude'    => $_POST['latitude'],
			'longitude'   => $_POST['longitude']
		) );

		if ( is_wp_error( $log_id ) ) {
			wp_send_json_error( array( 'message' => $log_id->get_error_message() ) );
		}

		// Handle evidence upload if present
		if ( ! empty( $_FILES['log_evidence']['name'] ) ) {
			require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-evidence-handler.php';
			$metadata = array(
				'case_id'       => $_POST['case_id'],
				'title'         => 'Field Log Evidence - ' . current_time( 'mysql' ),
				'description'   => 'Uploaded via Field Log Book entry #' . $log_id,
				'evidence_type' => 'photo'
			);
			WP_ParaDB_Evidence_Handler::upload_evidence( $_FILES['log_evidence'], $metadata );
		}

		wp_send_json_success( array( 'message' => __( 'Log entry saved.', 'wp-paradb' ) ) );
	}
}
