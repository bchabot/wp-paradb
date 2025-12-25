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
if ( isset( $_POST['add_relationship'] ) && check_admin_referer( 'add_relationship_' . $object_id, 'relationship_nonce' ) ) {
	$rel_data = array(
		'from_id'           => $object_id,
		'from_type'         => $object_type,
		'to_id'             => absint( $_POST['to_id'] ),
		'to_type'           => sanitize_text_field( $_POST['to_type'] ),
		'relationship_type' => sanitize_text_field( $_POST['relationship_type'] ),
		'notes'             => sanitize_textarea_field( $_POST['relationship_notes'] ),
	);

	$result = WP_ParaDB_Relationship_Handler::create_relationship( $rel_data );
	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Relationship added.', 'wp-paradb' ) . '</p></div>';
	}
}

// Handle deleting relationship.
if ( isset( $_GET['del_rel'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( $_GET['_wpnonce'], 'delete_rel_' . $_GET['del_rel'] ) ) {
		WP_ParaDB_Relationship_Handler::delete_relationship( $_GET['del_rel'] );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Relationship removed.', 'wp-paradb' ) . '</p></div>';
	}
}

$relationships = WP_ParaDB_Relationship_Handler::get_relationships( $object_id, $object_type );
$rel_types = WP_ParaDB_Taxonomy_Handler::get_taxonomy_items( 'relationship_types' );
?>

<div class="paradb-relationships-section postbox">
	<h2 class="hndle"><?php esc_html_e( 'Linked Relationships', 'wp-paradb' ); ?></h2>
	<div class="inside">
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
						<tr>
							<td><?php echo esc_html( $type_label ); ?></td>
							<td><strong><?php echo esc_html( $label ); ?></strong> (<?php echo esc_html( ucfirst( $target_type ) ); ?>)</td>
							<td><?php echo esc_html( $rel->notes ); ?></td>
							<td>
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'del_rel', $rel->relationship_id ), 'delete_rel_' . $rel->relationship_id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Remove this link?', 'wp-paradb' ); ?>');">×</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No relationships linked yet.', 'wp-paradb' ); ?></p>
		<?php endif; ?>

		<hr>
		<h4><?php esc_html_e( 'Add New Relationship', 'wp-paradb' ); ?></h4>
		<form method="post" action="">
			<?php wp_nonce_field( 'add_relationship_' . $object_id, 'relationship_nonce' ); ?>
			<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: end;">
				<div>
					<label><?php esc_html_e( 'Link Type', 'wp-paradb' ); ?></label><br>
					<select name="relationship_type" class="widefat" required>
						<?php foreach ( $rel_types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label><?php esc_html_e( 'Target Type', 'wp-paradb' ); ?></label><br>
					<select name="to_type" id="rel_target_type" class="widefat" required>
						<option value="case"><?php esc_html_e( 'Case', 'wp-paradb' ); ?></option>
						<option value="activity"><?php esc_html_e( 'Activity', 'wp-paradb' ); ?></option>
						<option value="report"><?php esc_html_e( 'Report', 'wp-paradb' ); ?></option>
						<option value="location"><?php esc_html_e( 'Location', 'wp-paradb' ); ?></option>
						<option value="witness"><?php esc_html_e( 'Witness Account', 'wp-paradb' ); ?></option>
						<option value="evidence"><?php esc_html_e( 'Evidence', 'wp-paradb' ); ?></option>
					</select>
				</div>
				<div>
					<label><?php esc_html_e( 'Target Object ID', 'wp-paradb' ); ?></label><br>
					<input type="number" name="to_id" class="widefat" required placeholder="e.g. 123">
				</div>
			</div>
			<div style="margin-top: 10px;">
				<label><?php esc_html_e( 'Notes', 'wp-paradb' ); ?></label><br>
				<textarea name="relationship_notes" class="widefat" rows="2"></textarea>
			</div>
			<div style="margin-top: 10px;">
				<input type="submit" name="add_relationship" class="button" value="<?php esc_attr_e( 'Add Link', 'wp-paradb' ); ?>">
			</div>
		</form>
	</div>
</div>
