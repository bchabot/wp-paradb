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
		add_action( 'wp_ajax_paradb_get_case_activities', array( $this, 'ajax_get_case_activities' ) );
		add_action( 'wp_ajax_paradb_get_field_logs', array( $this, 'ajax_get_logs' ) );
	}

	public function ajax_get_logs() {
		check_ajax_referer( 'paradb_log_nonce', 'nonce' );
		
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';
		
		$last_id = isset($_POST['last_id']) ? absint($_POST['last_id']) : 0;
		$limit = isset($_POST['limit']) ? absint($_POST['limit']) : 20;

		global $wpdb;
		$table = $wpdb->prefix . 'paradb_field_logs';
		$users = $wpdb->users;

		if ($last_id > 0) {
			$query = $wpdb->prepare(
				"SELECT l.*, u.display_name FROM {$table} l 
				 JOIN {$users} u ON l.investigator_id = u.ID
				 WHERE l.log_id > %d 
				 ORDER BY l.date_created ASC",
				$last_id
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT * FROM (
					SELECT l.*, u.display_name FROM {$table} l 
					JOIN {$users} u ON l.investigator_id = u.ID
					ORDER BY l.date_created DESC LIMIT %d
				) AS sub ORDER BY date_created ASC",
				$limit
			);
		}

		$results = $wpdb->get_results($query);
		$logs = array();

		foreach ($results as $row) {
			$logs[] = array(
				'id' => $row->log_id,
				'user' => $row->display_name,
				'content' => wpautop(esc_html($row->log_content)),
				'time' => gmdate('H:i', strtotime($row->date_created)),
				'is_own' => ($row->investigator_id == get_current_user_id()),
				'file_url' => $row->file_url
			);
		}

		wp_send_json_success(array('logs' => $logs));
	}

	public function ajax_get_case_activities() {
		check_ajax_referer( 'paradb_log_nonce', 'nonce' );
		
		$case_id = isset($_POST['case_id']) ? absint($_POST['case_id']) : 0;
		if (!$case_id) {
			wp_send_json_error(array('message' => 'Invalid Case ID'));
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';
		$results = WP_ParaDB_Activity_Handler::get_activities(array('case_id' => $case_id, 'limit' => 500));
		
		$activities = array();
		foreach ($results as $act) {
			$activities[] = array(
				'id' => $act->activity_id,
				'title' => $act->activity_title
			);
		}

		wp_send_json_success(array('activities' => $activities));
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
			<div class="log-book-header" style="background: #2271b1; color: #fff; padding: 10px 15px; border-radius: 4px 4px 0 0;">
				<strong><?php esc_html_e( 'Field Log Book', 'wp-paradb' ); ?></strong>
			</div>

			<div class="log-book-controls" style="background: #eee; padding: 5px 15px; font-size: 12px; display: flex; gap: 15px; border-bottom: 1px solid #ddd;">
				<label>
					<?php esc_html_e( 'Scrollback:', 'wp-paradb' ); ?>
					<select id="log-limit" style="font-size: 11px; height: auto; padding: 0 2px;">
						<option value="10">10</option>
						<option value="20" selected>20</option>
						<option value="50">50</option>
					</select>
				</label>
				<label>
					<input type="checkbox" id="log-enter-sends" checked> <?php esc_html_e( 'Enter sends', 'wp-paradb' ); ?>
				</label>
			</div>

			<div id="paradb-log-messages" style="height: 400px; overflow-y: auto; background: #fff; border: 1px solid #ddd; border-top: none; padding: 15px; display: flex; flex-direction: column;">
				<div id="log-loading" style="text-align: center; padding: 20px;"><?php esc_html_e( 'Loading logs...', 'wp-paradb' ); ?></div>
			</div>

			<div class="paradb-log-input-area" style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;">
				<div class="paradb-form-messages"></div>

				<form id="paradb-field-log-form" enctype="multipart/form-data">
					<?php wp_nonce_field( 'paradb_submit_log', 'paradb_log_nonce' ); ?>
					
					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
						<div>
							<label for="log_case_id" style="font-size: 12px; font-weight: bold;"><?php esc_html_e( 'Case', 'wp-paradb' ); ?> *</label>
							<select name="case_id" id="log_case_id" style="width: 100%;" required>
								<option value=""><?php esc_html_e( '— Select Case —', 'wp-paradb' ); ?></option>
								<?php foreach ( $cases as $case ) : ?>
									<option value="<?php echo esc_attr( $case->case_id ); ?>"><?php echo esc_html( $case->case_number ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label for="log_activity_id" style="font-size: 12px; font-weight: bold;"><?php esc_html_e( 'Activity', 'wp-paradb' ); ?></label>
							<select name="activity_id" id="log_activity_id" style="width: 100%;">
								<option value=""><?php esc_html_e( '— Select Activity —', 'wp-paradb' ); ?></option>
							</select>
						</div>
					</div>

					<div style="margin-bottom: 10px;">
						<textarea name="log_content" id="log_content" style="width: 100%;" rows="3" required placeholder="<?php esc_attr_e( 'Type your log entry...', 'wp-paradb' ); ?>"></textarea>
					</div>

					<div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
						<label class="button button-secondary" title="<?php esc_attr_e( 'Upload File', 'wp-paradb' ); ?>" style="margin: 0;">
							<span class="dashicons dashicons-paperclip" style="margin-top: 4px;"></span>
							<input type="file" name="log_evidence" id="log_evidence" style="display: none;" accept="image/*,audio/*,video/*" capture="environment">
						</label>
						
						<button type="button" id="get-location" class="button button-secondary" title="<?php esc_attr_e( 'Update Location', 'wp-paradb' ); ?>">
							<span class="dashicons dashicons-location" style="margin-top: 4px;"></span>
						</button>

						<button type="submit" class="button button-primary" style="flex: 1;"><?php esc_html_e( 'Send Entry', 'wp-paradb' ); ?></button>
					</div>
					<div id="file-preview" style="margin-top: 5px; font-size: 11px; color: #666;"></div>
					<input type="hidden" name="latitude" id="log_lat">
					<input type="hidden" name="longitude" id="log_lng">
				</form>
			</div>
		</div>

		<script>
		(function($) {
			var lastLogId = 0;

			// Initial load
			loadLogs(true);

			// Poll
			setInterval(function() {
				loadLogs(false);
			}, 5000);

			function getLocation() {
				if (!navigator.geolocation) return;
				navigator.geolocation.getCurrentPosition(function(pos) {
					$('#log_lat').val(pos.coords.latitude);
					$('#log_lng').val(pos.coords.longitude);
				});
			}
			getLocation();

			$('#get-location').on('click', getLocation);

			$('#log_evidence').on('change', function() {
				var filename = $(this).val().split('\\').pop();
				$('#file-preview').text(filename ? 'Selected: ' + filename : '');
			});

			// Enter sends logic
			$('#log_content').on('keydown', function(e) {
				if ($('#log-enter-sends').is(':checked') && e.which === 13 && !e.shiftKey) {
					e.preventDefault();
					$('#paradb-field-log-form').submit();
				}
			});

			$('#log_case_id').on('change', function() {
				var caseId = $(this).val();
				var $actSelect = $('#log_activity_id');
				if (!caseId) return;
				$.post('<?php echo admin_url('admin-ajax.php'); ?>', {
					action: 'paradb_get_case_activities',
					case_id: caseId,
					nonce: '<?php echo wp_create_nonce("paradb_log_nonce"); ?>'
				}, function(res) {
					$actSelect.html('<option value=""><?php esc_html_e( '— Select Activity —', 'wp-paradb' ); ?></option>');
					if (res.success && res.data.activities) {
						res.data.activities.forEach(function(act) {
							$actSelect.append('<option value="' + act.id + '">' + act.title + '</option>');
						});
					}
				});
			});

			$('#paradb-field-log-form').on('submit', function(e) {
				e.preventDefault();
				var formData = new FormData(this);
				formData.append('action', 'paradb_submit_field_log');

				var $btn = $(this).find('button[type="submit"]');
				$btn.prop('disabled', true).text('<?php esc_html_e( 'Saving...', 'wp-paradb' ); ?>');

				$.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(res) {
						if (res.success) {
							$('#log_content').val('').focus();
							$('#log_evidence').val('');
							$('#file-preview').text('');
							loadLogs(false);
							getLocation();
						} else {
							alert('Error: ' + res.data.message);
						}
					},
					complete: function() {
						$btn.prop('disabled', false).text('<?php esc_html_e( 'Send Entry', 'wp-paradb' ); ?>');
					}
				});
			});

			function loadLogs(isInitial) {
				var limit = $('#log-limit').val();
				$.post('<?php echo admin_url('admin-ajax.php'); ?>', {
					action: 'paradb_get_field_logs',
					last_id: lastLogId,
					limit: limit,
					nonce: '<?php echo wp_create_nonce("paradb_log_nonce"); ?>'
				}, function(res) {
					if (res.success && res.data.logs.length > 0) {
						$('#log-loading').hide();
						var $container = $('#paradb-log-messages');
						if (isInitial) $container.find('.log-msg').remove();

						res.data.logs.forEach(function(log) {
							if ($('#log-msg-' + log.id).length === 0) {
								var html = '<div id="log-msg-' + log.id + '" class="log-msg ' + (log.is_own ? 'own' : '') + '" style="margin-bottom: 10px; padding: 10px; border-radius: 8px; background: ' + (log.is_own ? '#e1f5fe' : '#f0f0f0') + '; align-self: ' + (log.is_own ? 'flex-end' : 'flex-start') + '; max-width: 85%;">';
								html += '<div style="font-size: 11px; color: #888;"><strong>' + log.user + '</strong> • ' + log.time + '</div>';
								html += '<div>' + log.content + '</div>';
								if (log.file_url) {
									var isImg = log.file_url.match(/\.(jpg|jpeg|png|gif)$/i);
									html += '<div style="margin-top: 5px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 5px;">';
									if (isImg) {
										html += '<a href="' + log.file_url + '" target="_blank"><img src="' + log.file_url + '" style="max-width: 100px; max-height: 100px; border-radius: 4px;"></a>';
									} else {
										html += '<a href="' + log.file_url + '" target="_blank" class="button button-small" style="font-size: 10px;">View Attachment</a>';
									}
									html += '</div>';
								}
								html += '</div>';
								$container.append(html);
								lastLogId = Math.max(lastLogId, log.id);
							}
						});
						$container.scrollTop($container[0].scrollHeight);
					} else if (isInitial) {
						$('#log-loading').text('No entries yet.');
					}
				});
			}

			$('#log-limit').on('change', function() {
				lastLogId = 0;
				loadLogs(true);
			});

		})(jQuery);
		</script>
		<style>
		.paradb-log-book-container {
			max-width: 600px;
			margin: 0 auto;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
		}
		#paradb-log-messages::-webkit-scrollbar { width: 6px; }
		#paradb-log-messages::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
		</style>
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
			'activity_id' => ( isset( $_POST['activity_id'] ) && $_POST['activity_id'] > 0 ) ? absint( $_POST['activity_id'] ) : null,
			'log_content' => $_POST['log_content'],
			'latitude'    => ( isset( $_POST['latitude'] ) && '' !== $_POST['latitude'] ) ? $_POST['latitude'] : null,
			'longitude'   => ( isset( $_POST['longitude'] ) && '' !== $_POST['longitude'] ) ? $_POST['longitude'] : null
		) );

		if ( is_wp_error( $log_id ) ) {
			wp_send_json_error( array( 'message' => $log_id->get_error_message() ) );
		}

		// Handle evidence upload if present
		if ( ! empty( $_FILES['log_evidence']['name'] ) ) {
			require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-evidence-handler.php';
			$metadata = array(
				'case_id'       => $_POST['case_id'],
				'activity_id'   => isset($_POST['activity_id']) ? absint($_POST['activity_id']) : null,
				'title'         => 'Field Log Evidence - ' . current_time( 'mysql' ),
				'description'   => 'Uploaded via Field Log Book entry #' . $log_id,
				'evidence_type' => 'photo'
			);
			WP_ParaDB_Evidence_Handler::upload_evidence( $_FILES['log_evidence'], $metadata );
		}

		wp_send_json_success( array( 'message' => __( 'Log entry saved.', 'wp-paradb' ) ) );
	}
}
