<?php
/**
 * Admin mobile-friendly log chat view
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.6.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$activity_id = isset( $_GET['activity_id'] ) ? absint( $_GET['activity_id'] ) : 0;
if ( ! $activity_id ) {
	wp_die( __( 'Invalid Activity ID.', 'wp-paradb' ) );
}

require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';

$activity = WP_ParaDB_Activity_Handler::get_activity( $activity_id );
if ( ! $activity ) {
	wp_die( __( 'Activity not found.', 'wp-paradb' ) );
}

$case = WP_ParaDB_Case_Handler::get_case( $activity->case_id );

$options = get_option( 'wp_paradb_options', array() );
$default_scrollback = isset( $options['log_chat_scrollback'] ) ? absint( $options['log_chat_scrollback'] ) : 20;
?>

<div class="wrap paradb-log-chat-wrap standalone-ready">
	<style>
		/* Standalone mode styles */
		body.paradb-standalone #adminmenuback,
		body.paradb-standalone #adminmenuwrap,
		body.paradb-standalone #wpadminbar,
		body.paradb-standalone #footer-thankyou,
		body.paradb-standalone #footer-upgrade {
			display: none !important;
		}
		body.paradb-standalone #wpcontent,
		body.paradb-standalone #wpfooter {
			margin-left: 0 !important;
		}
		body.paradb-standalone html.wp-toolbar {
			padding-top: 0 !important;
		}
		
		.paradb-log-chat-container {
			display: flex;
			flex-direction: column;
			height: calc(100vh - 40px);
			max-width: 800px;
			margin: 0 auto;
			background: #f0f0f1;
			position: relative;
		}
		
		.log-chat-header {
			background: #2271b1;
			color: #fff;
			padding: 10px 15px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-shrink: 0;
		}
		
		#log-chat-messages {
			flex-grow: 1;
			overflow-y: auto;
			padding: 15px;
			display: flex;
			flex-direction: column; /* Changed to standard column for easier scrolling logic */
			background: #fff;
		}
		
		.log-chat-input {
			background: #f1f1f1;
			border-top: 1px solid #ccc;
			padding: 15px;
			flex-shrink: 0;
		}
		
		.paradb-log-msg {
			margin-bottom: 15px;
			max-width: 85%;
			display: flex;
			flex-direction: column;
		}
		
		.paradb-log-msg.own {
			align-self: flex-end;
			align-items: flex-end;
		}
		
		.paradb-log-msg-inner {
			padding: 10px;
			border-radius: 8px;
			background: #f0f0f0;
			position: relative;
			word-wrap: break-word;
		}
		
		.paradb-log-msg.own .paradb-log-msg-inner {
			background: #e1f5fe;
			border: 1px solid #b3e5fc;
		}
		
		.paradb-log-meta {
			font-size: 11px;
			color: #888;
			margin-bottom: 3px;
		}
		
		.chat-controls {
			background: #eee;
			padding: 5px 15px;
			font-size: 12px;
			display: flex;
			gap: 15px;
			border-bottom: 1px solid #ddd;
		}
	</style>

	<div class="paradb-log-chat-container">
		<div class="log-chat-header">
			<div>
				<strong><?php echo esc_html( $activity->activity_title ); ?></strong>
				<small style="display:block; opacity: 0.8;"><?php echo esc_html( $case->case_number ); ?></small>
			</div>
			<div style="display: flex; gap: 10px;">
				<button type="button" id="toggle-standalone" class="button button-small"><?php esc_html_e( 'Full Screen', 'wp-paradb' ); ?></button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-activities&action=edit&activity_id=' . $activity_id ) ); ?>" class="button button-small" style="background: rgba(255,255,255,0.2); border: none; color: #fff;"><?php esc_html_e( 'Close', 'wp-paradb' ); ?></a>
			</div>
		</div>

		<div class="chat-controls">
			<label>
				<?php esc_html_e( 'Scrollback:', 'wp-paradb' ); ?>
				<select id="chat-limit" style="font-size: 11px; height: auto; padding: 0 2px;">
					<option value="10">10</option>
					<option value="20" selected>20</option>
					<option value="50">50</option>
					<option value="100">100</option>
				</select>
			</label>
			<label title="<?php esc_attr_e( 'If checked, pressing Enter sends the message. Use Shift+Enter for new line.', 'wp-paradb' ); ?>">
				<input type="checkbox" id="enter-sends" checked> <?php esc_html_e( 'Enter sends', 'wp-paradb' ); ?>
			</label>
		</div>

		<div id="log-chat-messages">
			<div id="chat-loading" style="text-align: center; padding: 20px;"><?php esc_html_e( 'Loading logs...', 'wp-paradb' ); ?></div>
		</div>

		<div class="log-chat-input">
			<form id="paradb-log-chat-form">
				<?php wp_nonce_field( 'paradb_submit_log', 'paradb_log_nonce' ); ?>
				<input type="hidden" name="case_id" value="<?php echo esc_attr( $activity->case_id ); ?>">
				<input type="hidden" name="activity_id" value="<?php echo esc_attr( $activity_id ); ?>">
				<input type="hidden" name="latitude" id="log_lat" value="">
				<input type="hidden" name="longitude" id="log_lng" value="">

				<div style="margin-bottom: 10px;">
					<textarea name="log_content" id="log_content" style="width: 100%;" rows="3" placeholder="<?php esc_attr_e( 'Type your log entry...', 'wp-paradb' ); ?>" required></textarea>
				</div>

				<div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
					<label class="button button-secondary" title="<?php esc_attr_e( 'Upload File', 'wp-paradb' ); ?>">
						<span class="dashicons dashicons-paperclip" style="margin-top: 4px;"></span>
						<input type="file" name="log_file" id="log_file" style="display: none;">
					</label>
					
					<button type="button" id="record-voice" class="button button-secondary" title="<?php esc_attr_e( 'Record Voice Note', 'wp-paradb' ); ?>">
						<span class="dashicons dashicons-microphone" style="margin-top: 4px;"></span>
					</button>

					<button type="submit" class="button button-primary" style="flex: 1;"><?php esc_html_e( 'Send Entry', 'wp-paradb' ); ?></button>
				</div>
				<div id="file-preview" style="margin-top: 10px; font-size: 12px; color: #666;"></div>
			</form>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	var activityId = <?php echo $activity_id; ?>;
	var lastLogId = 0;
	var isStandalone = window.location.search.indexOf('standalone=1') > -1;

	if (isStandalone) {
		$('body').addClass('paradb-standalone');
		$('#toggle-standalone').text('<?php esc_js( esc_html_e( 'Exit Full Screen', 'wp-paradb' ) ); ?>');
	}

	$('#toggle-standalone').on('click', function() {
		if ($('body').hasClass('paradb-standalone')) {
			var url = window.location.href.replace('&standalone=1', '').replace('?standalone=1', '');
			window.location.href = url;
		} else {
			var url = window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'standalone=1';
			window.location.href = url;
		}
	});

	// Initial load
	loadLogs(true);

	// Poll for new logs every 5 seconds
	setInterval(function() {
		loadLogs(false);
	}, 5000);

	// Get location
	function updateLocation() {
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(function(pos) {
				$('#log_lat').val(pos.coords.latitude);
				$('#log_lng').val(pos.coords.longitude);
			});
		}
	}
	updateLocation();

	$('#log_file').on('change', function() {
		var filename = $(this).val().split('\\').pop();
		$('#file-preview').text(filename ? 'Selected: ' + filename : '');
	});

	// Enter sends logic
	$('#log_content').on('keydown', function(e) {
		if ($('#enter-sends').is(':checked') && e.which === 13 && !e.shiftKey) {
			e.preventDefault();
			$('#paradb-log-chat-form').submit();
		}
	});

	// Voice Recording
	var mediaRecorder;
	var audioChunks = [];
	var isRecording = false;

	$('#record-voice').on('click', function() {
		var $btn = $(this);
		if (!isRecording) {
			navigator.mediaDevices.getUserMedia({ audio: true })
				.then(stream => {
					mediaRecorder = new MediaRecorder(stream);
					mediaRecorder.start();
					isRecording = true;
					$btn.addClass('button-primary').css('color', '#fff');
					$btn.find('.dashicons').removeClass('dashicons-microphone').addClass('dashicons-media-spreadsheet');
					$('#file-preview').text('Recording...');
					
					mediaRecorder.ondataavailable = event => {
						audioChunks.push(event.data);
					};

					mediaRecorder.onstop = () => {
						var audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
						var file = new File([audioBlob], "voice-note-" + Date.now() + ".wav", { type: 'audio/wav' });
						
						var container = new DataTransfer();
						container.items.add(file);
						document.getElementById('log_file').files = container.files;
						
						$('#file-preview').text('Voice note recorded: ' + (audioBlob.size / 1024).toFixed(2) + ' KB');
						audioChunks = [];
					};
				});
		} else {
			mediaRecorder.stop();
			isRecording = false;
			$btn.removeClass('button-primary').css('color', '');
			$btn.find('.dashicons').removeClass('dashicons-media-spreadsheet').addClass('dashicons-microphone');
		}
	});

	$('#paradb-log-chat-form').on('submit', function(e) {
		e.preventDefault();
		var formData = new FormData(this);
		formData.append('action', 'paradb_submit_log_chat');

		var $btn = $(this).find('button[type="submit"]');
		var $content = $('#log_content');
		
		if (!$content.val().trim() && !$('#log_file').val()) return;

		$btn.prop('disabled', true).text('Sending...');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(res) {
				if (res.success) {
					$content.val('').focus();
					$('#log_file').val('');
					$('#file-preview').text('');
					loadLogs(false);
					updateLocation(); // Refresh location for next entry
				} else {
					alert('Error: ' + res.data.message);
				}
			},
			complete: function() {
				$btn.prop('disabled', false).text('Send Entry');
			}
		});
	});

	// Focus input on load
	$('#log_content').focus();

	function loadLogs(isInitial) {
		var limit = $('#chat-limit').val();
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'paradb_get_log_chat',
				activity_id: activityId,
				last_id: lastLogId,
				limit: limit,
				nonce: '<?php echo wp_create_nonce("paradb_chat_nonce"); ?>'
			},
			success: function(res) {
				if (res.success && res.data.logs.length > 0) {
					$('#chat-loading').hide();
					var $chat = $('#log-chat-messages');
					
					// If initial load or limit changed, clear and show all
					if (isInitial) {
						$chat.find('.paradb-log-msg').remove();
					}

					res.data.logs.forEach(function(log) {
						if ($('#log-msg-' + log.log_id).length === 0) {
							var isOwn = log.investigator_id == <?php echo get_current_user_id(); ?>;
							var html = '<div id="log-msg-' + log.log_id + '" class="paradb-log-msg ' + (isOwn ? 'own' : '') + '">';
							html += '<div class="paradb-log-meta"><strong>' + log.user_name + '</strong> • ' + log.time + '</div>';
							html += '<div class="paradb-log-msg-inner">' + log.content;
							if (log.file_url) {
								var isImg = log.file_url.match(/\.(jpg|jpeg|png|gif)$/i);
								html += '<div class="paradb-log-file" style="margin-top: 8px; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 5px;">';
								if (isImg) {
									html += '<a href="' + log.file_url + '" target="_blank" title="View full size"><img src="' + log.file_url + '" style="max-width: 120px; max-height: 120px; border-radius: 4px; border: 1px solid #ddd; padding: 2px; background: #fff;"></a>';
								} else {
									html += '<a href="' + log.file_url + '" target="_blank" class="button button-small">View Attachment</a>';
								}
							html += '</div>';
							}
						html += '</div></div>';
							$chat.append(html);
							lastLogId = Math.max(lastLogId, log.log_id);
						}
						
					});
					
					// Scroll to bottom
					$chat.scrollTop($chat[0].scrollHeight);
				} else if (isInitial) {
					$('#chat-loading').text('<?php esc_html_e( 'No logs yet.', 'wp-paradb' ); ?>');
				}
			}
		});
	}

	$('#chat-limit').on('change', function() {
		lastLogId = 0;
		loadLogs(true);
	});
});
</script>