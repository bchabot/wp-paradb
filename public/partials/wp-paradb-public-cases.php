<?php
/**
 * Public cases listing template
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/public/partials
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes.
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';

// Get published cases.
$limit   = isset( $atts['limit'] ) ? absint( $atts['limit'] ) : 10;
$orderby = isset( $atts['orderby'] ) ? sanitize_key( $atts['orderby'] ) : 'date_created';
$order   = isset( $atts['order'] ) ? strtoupper( sanitize_key( $atts['order'] ) ) : 'DESC';

// Validate orderby to prevent SQL injection.
$allowed_orderby = array( 'case_id', 'case_number', 'case_name', 'date_created', 'date_modified' );
if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
	$orderby = 'date_created';
}

// Validate order direction.
if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
	$order = 'DESC';
}

// Build query with proper preparation.
global $wpdb;
$cases_table = $wpdb->prefix . 'paradb_cases';

// Start building the query.
$query_parts = array();
$query_parts[] = "SELECT * FROM {$cases_table} WHERE is_published = 1";

// Handle search.
if ( ! empty( $_GET['search'] ) ) {
	$search_term = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) . '%';
	$query_parts[] = $wpdb->prepare(
		'AND (case_name LIKE %s OR case_description LIKE %s OR location_name LIKE %s)',
		$search_term,
		$search_term,
		$search_term
	);
}

// Add ordering and limit (these are validated above, so safe to interpolate).
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $orderby and $order are validated against allowlists.
$query = implode( ' ', $query_parts ) . " ORDER BY {$orderby} {$order} LIMIT %d";

// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is built with proper escaping above.
$cases = $wpdb->get_results( $wpdb->prepare( $query, $limit ) );
?>

<div class="paradb-cases-listing">
	<div class="paradb-search-form">
		<form method="get" action="">
			<?php
			foreach ( $_GET as $key => $value ) {
				if ( 'search' !== $key ) {
					echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
				}
			}
			?>
			<input type="search" name="search" placeholder="<?php esc_attr_e( 'Search cases...', 'wp-paradb' ); ?>" value="<?php echo isset( $_GET['search'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) : ''; ?>">
			<button type="submit"><?php esc_html_e( 'Search', 'wp-paradb' ); ?></button>
		</form>
	</div>

	<?php if ( ! empty( $cases ) ) : ?>
		<div class="paradb-cases-grid">
			<?php foreach ( $cases as $case ) : ?>
				<?php
				$phenomena = maybe_unserialize( $case->phenomena_types );
				$phenomena_list = is_array( $phenomena ) ? implode( ', ', array_slice( $phenomena, 0, 3 ) ) : '';
				?>
				<article class="paradb-case-card">
					<header class="case-header">
						<h3 class="case-title">
							<a href="<?php echo esc_url( add_query_arg( array( 'case_id' => $case->case_id ), get_permalink() ) ); ?>">
								<?php echo esc_html( $case->case_name ); ?>
							</a>
						</h3>
						<div class="case-meta">
							<span class="case-number"><?php echo esc_html( $case->case_number ); ?></span>
							<?php if ( $case->location_city && $case->location_state ) : ?>
								<span class="case-location">
									<?php echo esc_html( $case->location_city . ', ' . $case->location_state ); ?>
								</span>
							<?php endif; ?>
						</div>
					</header>

					<div class="case-content">
						<?php if ( $case->case_description ) : ?>
							<div class="case-description">
								<?php echo wp_kses_post( wp_trim_words( $case->case_description, 50 ) ); ?>
							</div>
						<?php endif; ?>

						<?php if ( $phenomena_list ) : ?>
							<div class="case-phenomena">
								<strong><?php esc_html_e( 'Phenomena:', 'wp-paradb' ); ?></strong>
								<?php echo esc_html( $phenomena_list ); ?>
								<?php if ( is_array( $phenomena ) && count( $phenomena ) > 3 ) : ?>
									<span class="more-phenomena"><?php echo esc_html( sprintf( __( '+ %d more', 'wp-paradb' ), count( $phenomena ) - 3 ) ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<footer class="case-footer">
						<a href="<?php echo esc_url( add_query_arg( array( 'case_id' => $case->case_id ), get_permalink() ) ); ?>" class="read-more">
							<?php esc_html_e( 'Read Full Case', 'wp-paradb' ); ?> &rarr;
						</a>
						<span class="case-date">
							<?php echo esc_html( gmdate( 'F j, Y', strtotime( $case->date_created ) ) ); ?>
						</span>
					</footer>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="paradb-no-cases">
			<p><?php esc_html_e( 'No published cases found.', 'wp-paradb' ); ?></p>
		</div>
	<?php endif; ?>
</div>