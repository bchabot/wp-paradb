<?php
/**
 * Admin field logs viewer
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.6.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-field-log-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';

$case_id = isset( $_GET['case_id'] ) ? absint( $_GET['case_id'] ) : 0;
$activity_id = isset( $_GET['activity_id'] ) ? absint( $_GET['activity_id'] ) : 0;
$investigator_id = isset( $_GET['investigator_id'] ) ? absint( $_GET['investigator_id'] ) : 0;

$args = array(
	'case_id'     => $case_id,
	'activity_id' => $activity_id,
	'limit'       => 100,
);

if ( $investigator_id ) {
	$args['investigator_id'] = $investigator_id;
}

$logs = WP_ParaDB_Field_Log_Handler::get_logs( $args );
$cases = WP_ParaDB_Case_Handler::get_cases( array( 'limit' => 1000 ) );
$activities = WP_ParaDB_Activity_Handler::get_activities( array( 'limit' => 1000 ) );
$investigators = WP_ParaDB_Roles::get_all_paradb_users();
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Field Log Viewer', 'wp-paradb' ); ?></h1>
	
	<div class="tablenav top">
		<div class="alignleft actions">
			<form method="get" action="">
				<input type="hidden" name="page" value="wp-paradb-logs">
				
				<select name="case_id">
					<option value=""><?php esc_html_e( 'All Cases', 'wp-paradb' ); ?></option>
					<?php foreach ( $cases as $c ) : ?>
						<option value="<?php echo esc_attr( $c->case_id ); ?>" <?php selected( $case_id, $c->case_id ); ?>><?php echo esc_html( $c->case_number ); ?></option>
					<?php endforeach; ?>
				</select>

				<select name="activity_id">
					<option value=""><?php esc_html_e( 'All Activities', 'wp-paradb' ); ?></option>
					<?php foreach ( $activities as $a ) : ?>
						<option value="<?php echo esc_attr( $a->activity_id ); ?>" <?php selected( $activity_id, $a->activity_id ); ?>><?php echo esc_html( $a->activity_title ); ?></option>
					<?php endforeach; ?>
				</select>

				<select name="investigator_id">
					<option value=""><?php esc_html_e( 'All Investigators', 'wp-paradb' ); ?></option>
					<?php foreach ( $investigators as $i ) : ?>
						<option value="<?php echo esc_attr( $i->ID ); ?>" <?php selected( $investigator_id, $i->ID ); ?>><?php echo esc_html( $i->display_name ); ?></option>
					<?php endforeach; ?>
				</select>

				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wp-paradb' ); ?>">
				
				<label style="margin-left: 20px;">
					<input type="checkbox" id="live-mode"> <strong><?php esc_html_e( 'Live Mode (Tail)', 'wp-paradb' ); ?></strong>
				</label>

				<label style="margin-left: 20px;">
					<input type="checkbox" id="expanded-view"> <strong><?php esc_html_e( 'Expanded View', 'wp-paradb' ); ?></strong>
				</label>
			</form>
		</div>
	</div>

	<table class="wp-list-table widefat fixed striped" id="logs-table">
		<thead>
			<tr>
				<th style="width: 180px;"><?php esc_html_e( 'Date/Time', 'wp-paradb' ); ?></th>
				<th style="width: 150px;"><?php esc_html_e( 'Investigator', 'wp-paradb' ); ?></th>
				<th style="width: 150px;" class="column-context"><?php esc_html_e( 'Context', 'wp-paradb' ); ?></th>
				<th><?php esc_html_e( 'Message', 'wp-paradb' ); ?></th>
				<th style="width: 100px;"><?php esc_html_e( 'Media', 'wp-paradb' ); ?></th>
			</tr>
		</thead>
		<tbody id="log-viewer-body">
			<?php if ( $logs ) : ?>
				<?php foreach ( $logs as $log ) : 
					$inv = get_userdata( $log->investigator_id );
					$case = WP_ParaDB_Case_Handler::get_case( $log->case_id );
					$act = $log->activity_id ? WP_ParaDB_Activity_Handler::get_activity( $log->activity_id ) : null;
					?>
					<tr id="log-row-<?php echo $log->log_id; ?>" data-id="<?php echo $log->log_id; ?>">
						<td><?php echo esc_html( gmdate( 'Y-m-d H:i:s', strtotime( $log->date_created ) ) ); ?></td>
						<td><strong><?php echo $inv ? esc_html( $inv->display_name ) : '—'; ?></strong></td>
						<td class="column-context">
							<small>
								<?php if ( $case ) echo 'Case: ' . esc_html( $case->case_number ) . '<br>'; ?>
								<?php if ( $act ) echo 'Act: ' . esc_html( $act->activity_title ); ?>
							</small>
						</td>
						<td>
							<?php echo wp_kses_post( $log->log_content ); ?>
							<div class="log-details" style="display: none; margin-top: 10px; font-size: 11px; color: #666; border-top: 1px dashed #ccc; padding-top: 5px;">
								<?php if ( $log->latitude ) printf( __( 'Location: %f, %f', 'wp-paradb' ), $log->latitude, $log->longitude ); ?>
								<?php if ( $log->log_id ) echo ' | ID: ' . $log->log_id; ?>
							</div>
						</td>
						<td>
							<?php if ( $log->file_url ) : ?>
								<a href="<?php echo esc_url( $log->file_url ); ?>" target="_blank" title="<?php esc_attr_e( 'View Media', 'wp-paradb' ); ?>">
									<?php 
									$isImg = preg_match( '/\.(jpg|jpeg|png|gif)$/i', $log->file_url );
									if ( $isImg ) : ?>
										<img src="<?php echo esc_url( $log->file_url ); ?>" style="max-width: 50px; max-height: 50px; border-radius: 2px;">
									<?php else : ?>
										<span class="dashicons dashicons-media-document"></span>
									<?php endif; ?>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No logs found.', 'wp-paradb' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<script>
jQuery(document).ready(function($) {
	var liveInterval;
	<?php
	global $wpdb;
	$max_id = $wpdb->get_var( "SELECT MAX(log_id) FROM {$wpdb->prefix}paradb_field_logs" );
	?>
	var lastId = <?php echo $logs ? intval($logs[0]->log_id) : ($max_id ? intval($max_id) : 0); ?>;

	$('#expanded-view').on('change', function() {
		if ($(this).is(':checked')) {
			$('.log-details').show();
			$('#logs-table').removeClass('fixed');
		} else {
			$('.log-details').hide();
			$('#logs-table').addClass('fixed');
		}
	}).trigger('change');

	$('#live-mode').on('change', function() {
		if ($(this).is(':checked')) {
			liveInterval = setInterval(fetchNewLogs, 3000);
		} else {
			clearInterval(liveInterval);
		}
	});

	function fetchNewLogs() {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'paradb_get_all_logs_live',
				last_id: lastId,
				case_id: $('select[name="case_id"]').val(),
				activity_id: $('select[name="activity_id"]').val(),
				investigator_id: $('select[name="investigator_id"]').val(),
				nonce: '<?php echo wp_create_nonce("paradb_log_viewer_nonce"); ?>'
			},
			success: function(res) {
				if (res.success && res.data.logs.length > 0) {
					res.data.logs.forEach(function(log) {
						if ($('#log-row-' + log.log_id).length === 0) {
							var isExpanded = $('#expanded-view').is(':checked');
							var html = '<tr id="log-row-' + log.log_id + '" data-id="' + log.log_id + '" style="background: #fff9c4;">';
							html += '<td>' + log.datetime + '</td>';
							html += '<td><strong>' + log.user_name + '</strong></td>';
							html += '<td class="column-context"><small>' + log.context + '</small></td>';
							html += '<td>' + log.content;
							html += '<div class="log-details" style="display: ' + (isExpanded ? 'block' : 'none') + '; margin-top: 10px; font-size: 11px; color: #666; border-top: 1px dashed #ccc; padding-top: 5px;">ID: ' + log.log_id + '</div>';
							html += '</td>';
							html += '<td>' + (log.file_url ? '<a href="' + log.file_url + '" target="_blank">View</a>' : '') + '</td>';
							html += '</tr>';
							$('#log-viewer-body').prepend(html);
							lastId = Math.max(lastId, parseInt(log.log_id));
						}
					});
				}
			}
		});
	}
});
</script>
