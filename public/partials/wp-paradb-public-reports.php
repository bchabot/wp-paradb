<?php
/**
 * Public reports listing template
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.6.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/public/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-report-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';

$limit   = isset( $atts['limit'] ) ? absint( $atts['limit'] ) : 10;
$orderby = isset( $atts['orderby'] ) ? sanitize_key( $atts['orderby'] ) : 'report_date';
$order   = isset( $atts['order'] ) ? strtoupper( sanitize_key( $atts['order'] ) ) : 'DESC';
$case_id = isset( $atts['case_id'] ) ? absint( $atts['case_id'] ) : 0;

global $wpdb;
$reports_table = $wpdb->prefix . 'paradb_reports';
$cases_table = $wpdb->prefix . 'paradb_cases';

$query_parts = array();
$query_parts[] = "SELECT r.* FROM {$reports_table} r";
$query_parts[] = "JOIN {$cases_table} c ON r.case_id = c.case_id";
$query_parts[] = "WHERE r.is_published = 1 AND c.is_published = 1";

if ( ! current_user_can( 'paradb_view_cases' ) ) {
	$query_parts[] = "AND c.visibility != 'internal'";
}

if ( $case_id ) {
	$query_parts[] = $wpdb->prepare( "AND r.case_id = %d", $case_id );
}

$query = implode( ' ', $query_parts ) . " ORDER BY {$orderby} {$order} LIMIT %d";
$reports = $wpdb->get_results( $wpdb->prepare( $query, $limit ) );
?>

<div class="paradb-reports-listing">
	<?php if ( ! empty( $reports ) ) : ?>
		<div class="paradb-reports-grid">
			<?php foreach ( $reports as $report ) : 
				$case = WP_ParaDB_Case_Handler::get_case( $report->case_id );
				?>
				<article class="paradb-report-card">
					<header class="report-header">
						<h3 class="report-title">
							<a href="<?php echo esc_url( add_query_arg( array( 'report_id' => $report->report_id ), get_permalink() ) ); ?>">
								<?php echo esc_html( $report->report_title ); ?>
							</a>
						</h3>
						<div class="report-meta">
							<span class="report-date"><?php echo esc_html( gmdate( 'F j, Y', strtotime( $report->report_date ) ) ); ?></span>
							<?php if ( $case ) : ?>
								<span class="report-case"> - <?php echo esc_html( $case->case_name ); ?></span>
							<?php endif; ?>
						</div>
					</header>
					<div class="report-summary">
						<?php echo wp_kses_post( wp_trim_words( $report->report_summary ? $report->report_summary : $report->report_content, 30 ) ); ?>
					</div>
					<footer class="report-footer">
						<a href="<?php echo esc_url( add_query_arg( array( 'report_id' => $report->report_id ), get_permalink() ) ); ?>" class="read-more">
							<?php esc_html_e( 'Read Full Report', 'wp-paradb' ); ?> &rarr;
						</a>
					</footer>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No published reports found.', 'wp-paradb' ); ?></p>
	<?php endif; ?>
</div>