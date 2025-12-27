<?php
/**
 * Admin relationship management partial
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.3.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-relationship-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-taxonomy-handler.php';

// $object_id and $object_type are expected to be defined by the caller.
$object_id = isset( $object_id ) ? absint( $object_id ) : 0;
$object_type = isset( $object_type ) ? sanitize_text_field( $object_type ) : '';

if ( ! $object_id || ! $object_type ) {
	return;
}

// Handle adding relationship.
// (Removing the direct POST handling since we will use AJAX)

$relationships = WP_ParaDB_Relationship_Handler::get_relationships( $object_id, $object_type );
$rel_types = WP_ParaDB_Taxonomy_Handler::get_taxonomy_items( 'relationship_types' );
?>

<div class="paradb-relationships-section postbox" id="paradb-relationships-box">
	<h2 class="hndle"><?php esc_html_e( 'Linked Relationships', 'wp-paradb' ); ?></h2>
	<div class="inside">
		<div id="paradb-relationships-table-container">
			<?php if ( $relationships ) : ?>
				<table class="wp-list-table widefat fixed striped" style="margin-bottom: 20px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Relationship', 'wp-paradb' ); ?></th>
							<th><?php esc_html_e( 'Object', 'wp-paradb' ); ?></th>
							<th><?php esc_html_e( 'Notes', 'wp-paradb' ); ?></th>
							<th style="width: 50px;"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $relationships as $rel ) : 
							$is_from = ( absint( $rel->from_id ) === $object_id && $rel->from_type === $object_type );
							$target_id = $is_from ? $rel->to_id : $rel->from_id;
							$target_type = $is_from ? $rel->to_type : $rel->from_type;
							$label = WP_ParaDB_Relationship_Handler::get_object_label( $target_id, $target_type );
							$type_label = isset( $rel_types[ $rel->relationship_type ] ) ? $rel_types[ $rel->relationship_type ] : $rel->relationship_type;
							?>
							<tr data-rel-id="<?php echo esc_attr($rel->relationship_id); ?>">
								<td><?php echo esc_html( $type_label ); ?></td>
								<td><strong><?php echo esc_html( $label ); ?></strong> (<?php echo esc_html( ucfirst( $target_type ) ); ?>)</td>
								<td><?php echo esc_html( $rel->notes ); ?></td>
								<td>
									<button type="button" class="button button-small delete-rel-btn" data-id="<?php echo esc_attr($rel->relationship_id); ?>">×</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="no-rels"><?php esc_html_e( 'No relationships linked yet.', 'wp-paradb' ); ?></p>
			<?php endif; ?>
		</div>

		<hr>
		<h4><?php esc_html_e( 'Add New Relationship', 'wp-paradb' ); ?></h4>
		<div id="add-relationship-form-fields">
			<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: end;">
				<div>
					<label><?php esc_html_e( 'Link Type', 'wp-paradb' ); ?></label><br>
					<select id="rel_type" class="widefat">
						<?php foreach ( $rel_types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label><?php esc_html_e( 'Target Type', 'wp-paradb' ); ?></label><br>
					<select id="rel_target_type" class="widefat">
						<option value="case"><?php esc_html_e( 'Case', 'wp-paradb' ); ?></option>
						<option value="activity"><?php esc_html_e( 'Activity', 'wp-paradb' ); ?></option>
						<option value="report"><?php esc_html_e( 'Report', 'wp-paradb' ); ?></option>
						<option value="location"><?php esc_html_e( 'Location', 'wp-paradb' ); ?></option>
						<option value="witness"><?php esc_html_e( 'Witness Account', 'wp-paradb' ); ?></option>
						<option value="evidence"><?php esc_html_e( 'Evidence', 'wp-paradb' ); ?></option>
					</select>
				</div>
				<div>
					<label><?php esc_html_e( 'Target Object', 'wp-paradb' ); ?></label><br>
					<div id="rel_target_object_container">
						<select id="rel_target_id" class="widefat">
							<option value=""><?php esc_html_e( 'Select Target Type First', 'wp-paradb' ); ?></option>
						</select>
					</div>
					<div id="rel_target_loading" style="display:none; color: #666; font-style: italic;">
						<?php esc_html_e( 'Loading objects...', 'wp-paradb' ); ?>
					</div>
				</div>
			</div>
			<div style="margin-top: 10px;">
				<label><?php esc_html_e( 'Notes', 'wp-paradb' ); ?></label><br>
				<textarea id="rel_notes" class="widefat" rows="2"></textarea>
			</div>
			<div style="margin-top: 10px;">
				<button type="button" id="submit-add-rel" class="button"><?php esc_attr_e( 'Add Link', 'wp-paradb' ); ?></button>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	var objId = <?php echo $object_id; ?>;
	var objType = '<?php echo esc_js($object_type); ?>';

	function loadLinkableObjects() {
		var type = $('#rel_target_type').val();
		$('#rel_target_object_container').hide();
		$('#rel_target_loading').show();

		$.post(ajaxurl, {
			action: 'paradb_get_linkable_objects',
			type: type,
			nonce: '<?php echo wp_create_nonce("paradb_admin_nonce"); ?>'
		}, function(res) {
			$('#rel_target_loading').hide();
			var $select = $('#rel_target_id');
			$select.empty();
			
			if (res.success && res.data.length > 0) {
				res.data.forEach(function(obj) {
					$select.append('<option value="' + obj.id + '">' + obj.label + '</option>');
				});
				$('#rel_target_object_container').show();
			} else {
				$select.append('<option value=""><?php esc_html_e( 'No objects found', 'wp-paradb' ); ?></option>');
				$('#rel_target_object_container').show();
			}
		});
	}

	$('#rel_target_type').on('change', loadLinkableObjects);
	loadLinkableObjects(); // Initial load

	$('#submit-add-rel').on('click', function() {
		var data = {
			action: 'paradb_add_relationship',
			from_id: objId,
			from_type: objType,
			to_id: $('#rel_target_id').val(),
			to_type: $('#rel_target_type').val(),
			relationship_type: $('#rel_type').val(),
			notes: $('#rel_notes').val(),
			nonce: '<?php echo wp_create_nonce("paradb_admin_nonce"); ?>'
		};

		if (!data.to_id) {
			alert('<?php esc_html_e( "Please select a target object.", "wp-paradb" ); ?>');
			return;
		}

		$(this).prop('disabled', true).text('<?php esc_html_e( "Adding...", "wp-paradb" ); ?>');

		$.post(ajaxurl, data, function(res) {
			if (res.success) {
				location.reload(); // Simplest way to refresh the table for now
			} else {
				alert(res.data.message);
				$('#submit-add-rel').prop('disabled', false).text('<?php esc_html_e( "Add Link", "wp-paradb" ); ?>');
			}
		});
	});

	$(document).on('click', '.delete-rel-btn', function() {
		if (!confirm('<?php esc_html_e( "Remove this link?", "wp-paradb" ); ?>')) return;
		var id = $(this).data('id');
		var $row = $(this).closest('tr');

		$.post(ajaxurl, {
			action: 'paradb_delete_relationship',
			rel_id: id,
			nonce: '<?php echo wp_create_nonce("paradb_admin_nonce"); ?>'
		}, function(res) {
			if (res.success) {
				$row.remove();
			} else {
				alert(res.data.message);
			}
		});
	});
});
</script>
