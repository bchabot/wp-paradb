<?php
/**
 * Taxonomy management functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/includes
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle taxonomy operations
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Taxonomy_Handler {

	/**
	 * Get default taxonomies
	 *
	 * @since    1.0.0
	 * @return   array    Default taxonomy definitions.
	 */
	public static function get_default_taxonomies() {
		return array(
			'phenomena_types' => array(
				'label'       => __( 'Phenomena Types', 'wp-paradb' ),
				'description' => __( 'Types of paranormal phenomena that can be reported', 'wp-paradb' ),
				'items'       => array(
					'Apparition',
					'Audio Phenomena',
					'Cold Spots',
					'Electronic Voice Phenomena (EVP)',
					'Full Body Apparition',
					'Object Movement',
					'Orbs',
					'Phantom Smells',
					'Physical Contact',
					'Shadow Figures',
					'Temperature Changes',
					'Visual Anomalies',
				),
			),
			'case_statuses' => array(
				'label'       => __( 'Case Statuses', 'wp-paradb' ),
				'description' => __( 'Available case status options', 'wp-paradb' ),
				'items'       => array(
					'open'       => __( 'Open', 'wp-paradb' ),
					'active'     => __( 'Active Investigation', 'wp-paradb' ),
					'reviewing'  => __( 'Under Review', 'wp-paradb' ),
					'closed'     => __( 'Closed', 'wp-paradb' ),
					'archived'   => __( 'Archived', 'wp-paradb' ),
				),
			),
			'case_types' => array(
				'label'       => __( 'Case Types', 'wp-paradb' ),
				'description' => __( 'Types of investigations or cases', 'wp-paradb' ),
				'items'       => array(
					'investigation' => __( 'Investigation', 'wp-paradb' ),
					'research'      => __( 'Research', 'wp-paradb' ),
					'consultation'  => __( 'Consultation', 'wp-paradb' ),
					'experiment'    => __( 'Experiment', 'wp-paradb' ),
				),
			),
			'case_priorities' => array(
				'label'       => __( 'Case Priorities', 'wp-paradb' ),
				'description' => __( 'Priority levels for cases', 'wp-paradb' ),
				'items'       => array(
					'low'    => __( 'Low', 'wp-paradb' ),
					'normal' => __( 'Normal', 'wp-paradb' ),
					'high'   => __( 'High', 'wp-paradb' ),
					'urgent' => __( 'Urgent', 'wp-paradb' ),
				),
			),
			'report_types' => array(
				'label'       => __( 'Report Types', 'wp-paradb' ),
				'description' => __( 'Types of investigation reports', 'wp-paradb' ),
				'items'       => array(
					'investigation' => __( 'Investigation Report', 'wp-paradb' ),
					'initial'       => __( 'Initial Assessment', 'wp-paradb' ),
					'followup'      => __( 'Follow-up Report', 'wp-paradb' ),
					'final'         => __( 'Final Report', 'wp-paradb' ),
					'analysis'      => __( 'Analysis Report', 'wp-paradb' ),
				),
			),
			'evidence_types' => array(
				'label'       => __( 'Evidence Types', 'wp-paradb' ),
				'description' => __( 'Types of evidence files', 'wp-paradb' ),
				'items'       => array(
					'photo'    => __( 'Photograph', 'wp-paradb' ),
					'audio'    => __( 'Audio Recording', 'wp-paradb' ),
					'video'    => __( 'Video Recording', 'wp-paradb' ),
					'document' => __( 'Document', 'wp-paradb' ),
					'data'     => __( 'Sensor Data', 'wp-paradb' ),
					'other'    => __( 'Other', 'wp-paradb' ),
				),
			),
			'equipment_types' => array(
				'label'       => __( 'Equipment Types', 'wp-paradb' ),
				'description' => __( 'Types of investigation equipment', 'wp-paradb' ),
				'items'       => array(
					'EMF Meter',
					'Digital Voice Recorder',
					'Infrared Camera',
					'Digital Camera',
					'Video Camera',
					'Temperature Sensor',
					'Motion Detector',
					'Spirit Box',
					'Laser Grid',
					'Dowsing Rods',
					'Geiger Counter',
					'White Noise Generator',
				),
			),
			'moon_phases' => array(
				'label'       => __( 'Moon Phases', 'wp-paradb' ),
				'description' => __( 'Lunar phases for environmental tracking', 'wp-paradb' ),
				'items'       => array(
					'new'             => __( 'New Moon', 'wp-paradb' ),
					'waxing_crescent' => __( 'Waxing Crescent', 'wp-paradb' ),
					'first_quarter'   => __( 'First Quarter', 'wp-paradb' ),
					'waxing_gibbous'  => __( 'Waxing Gibbous', 'wp-paradb' ),
					'full'            => __( 'Full Moon', 'wp-paradb' ),
					'waning_gibbous'  => __( 'Waning Gibbous', 'wp-paradb' ),
					'last_quarter'    => __( 'Last Quarter', 'wp-paradb' ),
					'waning_crescent' => __( 'Waning Crescent', 'wp-paradb' ),
				),
			),
		);
	}

	/**
	 * Get a specific taxonomy
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @return   array|null                 Taxonomy data or null if not found.
	 */
	public static function get_taxonomy( $taxonomy_key ) {
		$taxonomies = get_option( 'wp_paradb_taxonomies', array() );
		
		if ( empty( $taxonomies ) ) {
			$taxonomies = self::get_default_taxonomies();
		}
		
		return isset( $taxonomies[ $taxonomy_key ] ) ? $taxonomies[ $taxonomy_key ] : null;
	}

	/**
	 * Get taxonomy items
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @return   array                      Array of taxonomy items.
	 */
	public static function get_taxonomy_items( $taxonomy_key ) {
		$taxonomy = self::get_taxonomy( $taxonomy_key );
		return $taxonomy ? $taxonomy['items'] : array();
	}

	/**
	 * Update a taxonomy
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @param    array     $data            Taxonomy data.
	 * @return   bool                       Success status.
	 */
	public static function update_taxonomy( $taxonomy_key, $data ) {
		$taxonomies = get_option( 'wp_paradb_taxonomies', array() );
		
		if ( empty( $taxonomies ) ) {
			$taxonomies = self::get_default_taxonomies();
		}
		
		$taxonomies[ $taxonomy_key ] = array(
			'label'       => isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : '',
			'description' => isset( $data['description'] ) ? sanitize_text_field( $data['description'] ) : '',
			'items'       => isset( $data['items'] ) ? $data['items'] : array(),
		);
		
		return update_option( 'wp_paradb_taxonomies', $taxonomies );
	}

	/**
	 * Add item to taxonomy
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @param    mixed     $item            Item to add (string or key=>value).
	 * @return   bool                       Success status.
	 */
	public static function add_taxonomy_item( $taxonomy_key, $item ) {
		$taxonomy = self::get_taxonomy( $taxonomy_key );
		
		if ( ! $taxonomy ) {
			return false;
		}
		
		if ( is_array( $item ) ) {
			$taxonomy['items'] = array_merge( $taxonomy['items'], $item );
		} else {
			$taxonomy['items'][] = $item;
		}
		
		return self::update_taxonomy( $taxonomy_key, $taxonomy );
	}

	/**
	 * Remove item from taxonomy
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @param    mixed     $item_key        Item key or value to remove.
	 * @return   bool                       Success status.
	 */
	public static function remove_taxonomy_item( $taxonomy_key, $item_key ) {
		$taxonomy = self::get_taxonomy( $taxonomy_key );
		
		if ( ! $taxonomy ) {
			return false;
		}
		
		if ( isset( $taxonomy['items'][ $item_key ] ) ) {
			unset( $taxonomy['items'][ $item_key ] );
		} else {
			$key = array_search( $item_key, $taxonomy['items'], true );
			if ( false !== $key ) {
				unset( $taxonomy['items'][ $key ] );
			}
		}
		
		return self::update_taxonomy( $taxonomy_key, $taxonomy );
	}

	/**
	 * Reset taxonomy to defaults
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @return   bool                       Success status.
	 */
	public static function reset_taxonomy( $taxonomy_key ) {
		$defaults = self::get_default_taxonomies();
		
		if ( ! isset( $defaults[ $taxonomy_key ] ) ) {
			return false;
		}
		
		return self::update_taxonomy( $taxonomy_key, $defaults[ $taxonomy_key ] );
	}

	/**
	 * Reset all taxonomies to defaults
	 *
	 * @since    1.0.0
	 * @return   bool    Success status.
	 */
	public static function reset_all_taxonomies() {
		return update_option( 'wp_paradb_taxonomies', self::get_default_taxonomies() );
	}

	/**
	 * Initialize taxonomies on first run
	 *
	 * @since    1.0.0
	 */
	public static function initialize_taxonomies() {
		$taxonomies = get_option( 'wp_paradb_taxonomies', false );
		
		if ( false === $taxonomies ) {
			update_option( 'wp_paradb_taxonomies', self::get_default_taxonomies() );
		}
	}
}
Step 2: Create Taxonomy Management Admin Page
New File: admin/partials/wp-paradb-admin-taxonomies.php
php<?php
/**
 * Admin taxonomy management view
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
if ( ! current_user_can( 'paradb_manage_settings' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-paradb' ) );
}

// Load required classes.
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-taxonomy-handler.php';

// Handle form submissions.
if ( isset( $_POST['save_taxonomy'] ) && check_admin_referer( 'save_taxonomy', 'taxonomy_nonce' ) ) {
	$taxonomy_key = isset( $_POST['taxonomy_key'] ) ? sanitize_key( $_POST['taxonomy_key'] ) : '';
	
	if ( $taxonomy_key ) {
		$items = isset( $_POST['items'] ) ? $_POST['items'] : array();
		$cleaned_items = array();
		
		// Clean and process items based on taxonomy type.
		$taxonomy = WP_ParaDB_Taxonomy_Handler::get_taxonomy( $taxonomy_key );
		$is_associative = is_array( $taxonomy['items'] ) && array_keys( $taxonomy['items'] ) !== range( 0, count( $taxonomy['items'] ) - 1 );
		
		if ( $is_associative ) {
			// Key-value pairs.
			foreach ( $items as $key => $value ) {
				$clean_key = sanitize_key( $key );
				$clean_value = sanitize_text_field( wp_unslash( $value ) );
				if ( ! empty( $clean_key ) && ! empty( $clean_value ) ) {
					$cleaned_items[ $clean_key ] = $clean_value;
				}
			}
		} else {
			// Simple list.
			foreach ( $items as $item ) {
				$clean_item = sanitize_text_field( wp_unslash( $item ) );
				if ( ! empty( $clean_item ) ) {
					$cleaned_items[] = $clean_item;
				}
			}
		}
		
		$data = array(
			'label'       => isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '',
			'description' => isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '',
			'items'       => $cleaned_items,
		);
		
		if ( WP_ParaDB_Taxonomy_Handler::update_taxonomy( $taxonomy_key, $data ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Taxonomy updated successfully.', 'wp-paradb' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to update taxonomy.', 'wp-paradb' ) . '</p></div>';
		}
	}
}

// Handle reset action.
if ( isset( $_GET['action'] ) && 'reset' === $_GET['action'] && isset( $_GET['taxonomy'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'reset_taxonomy_' . sanitize_key( $_GET['taxonomy'] ) ) ) {
		$taxonomy_key = sanitize_key( $_GET['taxonomy'] );
		if ( WP_ParaDB_Taxonomy_Handler::reset_taxonomy( $taxonomy_key ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Taxonomy reset to defaults successfully.', 'wp-paradb' ) . '</p></div>';
		}
	}
}

// Get action.
$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
$taxonomy_key = isset( $_GET['taxonomy'] ) ? sanitize_key( $_GET['taxonomy'] ) : '';

if ( 'edit' === $action && ! empty( $taxonomy_key ) ) {
	// Show edit form.
	$taxonomy = WP_ParaDB_Taxonomy_Handler::get_taxonomy( $taxonomy_key );
	
	if ( ! $taxonomy ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Taxonomy not found.', 'wp-paradb' ) . '</p></div>';
		$action = 'list';
	}
}

if ( 'edit' === $action && ! empty( $taxonomy_key ) && $taxonomy ) :
	// Determine if this is an associative array.
	$is_associative = is_array( $taxonomy['items'] ) && array_keys( $taxonomy['items'] ) !== range( 0, count( $taxonomy['items'] ) - 1 );
	?>
	
	<div class="wrap">
		<h1><?php echo esc_html( sprintf( __( 'Edit Taxonomy: %s', 'wp-paradb' ), $taxonomy['label'] ) ); ?></h1>
		
		<form method="post" action="" id="taxonomy-form">
			<?php wp_nonce_field( 'save_taxonomy', 'taxonomy_nonce' ); ?>
			<input type="hidden" name="taxonomy_key" value="<?php echo esc_attr( $taxonomy_key ); ?>">
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="label"><?php esc_html_e( 'Label', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="label" id="label" class="regular-text" value="<?php echo esc_attr( $taxonomy['label'] ); ?>" required>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="description"><?php esc_html_e( 'Description', 'wp-paradb' ); ?></label>
					</th>
					<td>
						<input type="text" name="description" id="description" class="large-text" value="<?php echo esc_attr( $taxonomy['description'] ); ?>">
					</td>
				</tr>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'Items', 'wp-paradb' ); ?></th>
					<td>
						<div id="taxonomy-items">
							<?php if ( $is_associative ) : ?>
								<p class="description"><?php esc_html_e( 'Key-value pairs (key on left, display label on right)', 'wp-paradb' ); ?></p>
								<?php foreach ( $taxonomy['items'] as $key => $value ) : ?>
									<div class="taxonomy-item" style="margin-bottom: 10px;">
										<input type="text" name="items[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Display Label', 'wp-paradb' ); ?>">
										<button type="button" class="button remove-item"><?php esc_html_e( 'Remove', 'wp-paradb' ); ?></button>
										<span class="item-key" style="margin-left: 10px; color: #666;"><?php echo esc_html( sprintf( __( 'Key: %s', 'wp-paradb' ), $key ) ); ?></span>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<p class="description"><?php esc_html_e( 'Simple list of items', 'wp-paradb' ); ?></p>
								<?php foreach ( $taxonomy['items'] as $item ) : ?>
									<div class="taxonomy-item" style="margin-bottom: 10px;">
										<input type="text" name="items[]" value="<?php echo esc_attr( $item ); ?>" class="regular-text">
										<button type="button" class="button remove-item"><?php esc_html_e( 'Remove', 'wp-paradb' ); ?></button>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						
						<button type="button" id="add-item" class="button" style="margin-top: 10px;">
							<?php esc_html_e( 'Add New Item', 'wp-paradb' ); ?>
						</button>
					</td>
				</tr>
			</table>
			
			<p class="submit">
				<input type="submit" name="save_taxonomy" class="button button-primary" value="<?php esc_attr_e( 'Save Taxonomy', 'wp-paradb' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-taxonomies' ) ); ?>" class="button">
					<?php esc_html_e( 'Cancel', 'wp-paradb' ); ?>
				</a>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-taxonomies&action=reset&taxonomy=' . $taxonomy_key ), 'reset_taxonomy_' . $taxonomy_key ) ); ?>" class="button button-secondary" style="float: right;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to reset this taxonomy to default values?', 'wp-paradb' ); ?>');">
					<?php esc_html_e( 'Reset to Defaults', 'wp-paradb' ); ?>
				</a>
			</p>
		</form>
	</div>
	
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		var isAssociative = <?php echo $is_associative ? 'true' : 'false'; ?>;
		
		$('#add-item').on('click', function() {
			var html;
			if (isAssociative) {
				var key = prompt('<?php esc_attr_e( 'Enter key for new item:', 'wp-paradb' ); ?>');
				if (key) {
					html = '<div class="taxonomy-item" style="margin-bottom: 10px;">' +
						'<input type="text" name="items[' + key + ']" value="" class="regular-text" placeholder="<?php esc_attr_e( 'Display Label', 'wp-paradb' ); ?>">' +
						'<button type="button" class="button remove-item"><?php esc_html_e( 'Remove', 'wp-paradb' ); ?></button>' +
						'<span class="item-key" style="margin-left: 10px; color: #666;"><?php esc_html_e( 'Key:', 'wp-paradb' ); ?> ' + key + '</span>' +
						'</div>';
					$('#taxonomy-items').append(html);
				}
			} else {
				html = '<div class="taxonomy-item" style="margin-bottom: 10px;">' +
					'<input type="text" name="items[]" value="" class="regular-text">' +
					'<button type="button" class="button remove-item"><?php esc_html_e( 'Remove', 'wp-paradb' ); ?></button>' +
					'</div>';
				$('#taxonomy-items').append(html);
			}
		});
		
		$(document).on('click', '.remove-item', function() {
			$(this).closest('.taxonomy-item').remove();
		});
	});
	</script>
	
<?php else : ?>
	
	<div class="wrap">
		<h1><?php esc_html_e( 'Manage Taxonomies', 'wp-paradb' ); ?></h1>
		
		<p><?php esc_html_e( 'Customize the dropdown values and options used throughout ParaDB. Click on a taxonomy to edit its items.', 'wp-paradb' ); ?></p>
		
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Taxonomy', 'wp-paradb' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Description', 'wp-paradb' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Item Count', 'wp-paradb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$taxonomies = get_option( 'wp_paradb_taxonomies', WP_ParaDB_Taxonomy_Handler::get_default_taxonomies() );
				foreach ( $taxonomies as $key => $taxonomy ) :
					?>
					<tr>
						<td class="column-primary" data-colname="<?php esc_attr_e( 'Taxonomy', 'wp-paradb' ); ?>">
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-taxonomies&action=edit&taxonomy=' . $key ) ); ?>">
									<?php echo esc_html( $taxonomy['label'] ); ?>
								</a>
							</strong>
							<div class="row-actions">
								<span class="edit">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paradb-taxonomies&action=edit&taxonomy=' . $key ) ); ?>">
										<?php esc_html_e( 'Edit', 'wp-paradb' ); ?>
									</a>
								</span>
								|
								<span class="reset">
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-taxonomies&action=reset&taxonomy=' . $key ), 'reset_taxonomy_' . $key ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to reset this taxonomy to defaults?', 'wp-paradb' ); ?>');">
										<?php esc_html_e( 'Reset to Defaults', 'wp-paradb' ); ?>
									</a>
								</span>
							</div>
						</td>
						<td data-colname="<?php esc_attr_e( 'Description', 'wp-paradb' ); ?>">
							<?php echo esc_html( $taxonomy['description'] ); ?>
						</td>
						<td data-colname="<?php esc_attr_e( 'Item Count', 'wp-paradb' ); ?>">
							<?php echo esc_html( count( $taxonomy['items'] ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<p style="margin-top: 20px;">
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-paradb-taxonomies&action=reset_all' ), 'reset_all_taxonomies' ) ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to reset ALL taxonomies to their default values? This cannot be undone!', 'wp-paradb' ); ?>');">
				<?php esc_html_e( 'Reset All Taxonomies to Defaults', 'wp-paradb' ); ?>
			</a>
		</p>
	</div>
	
<?php endif; ?>