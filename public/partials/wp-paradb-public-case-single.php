<?php
/**
 * Public single case display template
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
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-report-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-evidence-handler.php';

// Get case ID.
$case_id = isset( $atts['id'] ) ? absint( $atts['id'] ) : ( isset( $_GET['case_id'] ) ? absint( $_GET['case_id'] ) : 0 );

if ( 0 === $case_id ) {
	echo '<p>' . esc_html__( 'No case specified.', 'wp-paradb' ) . '</p>';
	return;
}

// Get case.
$case = WP_ParaDB_Case_Handler::get_case( $case_id );

if ( ! $case || ! $case->is_published ) {
	echo '<p>' . esc_html__( 'Case not found or not published.', 'wp-paradb' ) . '</p>';
	return;
}

// Get related data.
$reports = WP_ParaDB_Report_Handler::get_reports( array( 'case_id' => $case_id, 'limit' => 100 ) );
$evidence = WP_ParaDB_Evidence_Handler::get_evidence_files( array( 'case_id' => $case_id, 'limit' => 100 ) );
$phenomena = maybe_unserialize( $case->phenomena_types );

// Increment view count.
global $wpdb;
$wpdb->query( $wpdb->prepare(
	"UPDATE {$wpdb->prefix}paradb_cases SET view_count = view_count + 1 WHERE case_id = %d",
	$case_id
) );
?>

<article class="paradb-single-case">
	<header class="case-header">
		<div class="case-number"><?php echo esc_html( $case->case_number ); ?></div>
		<h1 class="case-title"><?php echo esc_html( $case->case_name ); ?></h1>
		
		<div class="case-meta">
			<?php if ( $case->location_name || $case->location_city ) : ?>
				<div class="meta-item location">
					<strong><?php esc_html_e( 'Location:', 'wp-paradb' ); ?></strong>
					<?php
					if ( $case->location_name ) {
						echo esc_html( $case->location_name );
						if ( $case->location_city ) {
							echo ' - ';
						}
					}
					if ( $case->location_city ) {
						echo esc_html( $case->location_city );
						if ( $case->location_state ) {
							echo ', ' . esc_html( $case->location_state );
						}
					}
					?>
				</div>
			<?php endif; ?>
			
			<div class="meta-item date">
				<strong><?php esc_html_e( 'Investigation Date:', 'wp-paradb' ); ?></strong>
				<?php echo esc_html( gmdate( 'F j, Y', strtotime( $case->date_created ) ) ); ?>
			</div>
			
			<?php if ( $case->case_status ) : ?>
				<div class="meta-item status">
					<strong><?php esc_html_e( 'Status:', 'wp-paradb' ); ?></strong>
					<?php echo esc_html( ucfirst( $case->case_status ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</header>

	<div class="case-content">
		<?php if ( is_array( $phenomena ) && ! empty( $phenomena ) ) : ?>
			<section class="case-section phenomena">
				<h2><?php esc_html_e( 'Reported Phenomena', 'wp-paradb' ); ?></h2>
				<ul class="phenomena-list">
					<?php foreach ( $phenomena as $phenomenon ) : ?>
						<li><?php echo esc_html( $phenomenon ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $case->case_description ) : ?>
			<section class="case-section description">
				<h2><?php esc_html_e( 'Case Description', 'wp-paradb' ); ?></h2>
				<div class="description-content">
					<?php echo wp_kses_post( wpautop( $case->case_description ) ); ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $reports ) ) : ?>
			<section class="case-section reports">
				<h2><?php esc_html_e( 'Investigation Reports', 'wp-paradb' ); ?></h2>
				<?php foreach ( $reports as $report ) : ?>
					<?php if ( $report->is_published ) : ?>
						<article class="report">
							<h3 class="report-title"><?php echo esc_html( $report->report_title ); ?></h3>
							<div class="report-meta">
								<span class="report-date">
									<?php echo esc_html( gmdate( 'F j, Y', strtotime( $report->report_date ) ) ); ?>
								</span>
								<span class="report-type">
									<?php echo esc_html( ucfirst( $report->report_type ) ); ?>
								</span>
							</div>
							
							<?php if ( $report->report_summary ) : ?>
								<div class="report-summary">
									<?php echo wp_kses_post( wpautop( $report->report_summary ) ); ?>
								</div>
							<?php endif; ?>
							
							<?php if ( $report->report_content ) : ?>
								<div class="report-content">
									<?php echo wp_kses_post( wpautop( $report->report_content ) ); ?>
								</div>
							<?php endif; ?>
							
							<?php if ( $report->weather_conditions || $report->temperature || $report->moon_phase ) : ?>
								<div class="report-conditions">
									<h4><?php esc_html_e( 'Environmental Conditions', 'wp-paradb' ); ?></h4>
									<?php if ( $report->weather_conditions ) : ?>
										<p><strong><?php esc_html_e( 'Weather:', 'wp-paradb' ); ?></strong> <?php echo esc_html( $report->weather_conditions ); ?></p>
									<?php endif; ?>
									<?php if ( $report->temperature ) : ?>
										<p><strong><?php esc_html_e( 'Temperature:', 'wp-paradb' ); ?></strong> <?php echo esc_html( $report->temperature ); ?></p>
									<?php endif; ?>
									<?php if ( $report->moon_phase ) : ?>
										<p><strong><?php esc_html_e( 'Moon Phase:', 'wp-paradb' ); ?></strong> <?php echo esc_html( ucwords( str_replace( '_', ' ', $report->moon_phase ) ) ); ?></p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</article>
					<?php endif; ?>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $evidence ) ) : ?>
			<section class="case-section evidence">
				<h2><?php esc_html_e( 'Evidence', 'wp-paradb' ); ?></h2>
				<div class="evidence-grid">
					<?php foreach ( $evidence as $item ) : ?>
						<?php
						$file_url = WP_ParaDB_Evidence_Handler::get_evidence_url( $item );
						$is_image = in_array( strtolower( $item->file_type ), array( 'jpg', 'jpeg', 'png', 'gif' ), true );
						?>
						<div class="evidence-item">
							<?php if ( $is_image ) : ?>
								<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" class="evidence-link">
									<img src="<?php echo esc_url( $file_url ); ?>" alt="<?php echo esc_attr( $item->title ? $item->title : $item->file_name ); ?>">
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" class="evidence-link file">
									<span class="file-icon">📄</span>
									<span class="file-name"><?php echo esc_html( $item->title ? $item->title : $item->file_name ); ?></span>
								</a>
							<?php endif; ?>
							<?php if ( $item->description ) : ?>
								<div class="evidence-description">
									<?php echo esc_html( $item->description ); ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	</div>

	<footer class="case-footer">
		<div class="case-stats">
			<span class="view-count">
				<?php
				printf(
					_n( '%s view', '%s views', $case->view_count, 'wp-paradb' ),
					number_format_i18n( $case->view_count )
				);
				?>
			</span>
			<?php if ( $case->date_closed ) : ?>
				<span class="closed-date">
					<?php
					printf(
						esc_html__( 'Closed: %s', 'wp-paradb' ),
						esc_html( gmdate( 'F j, Y', strtotime( $case->date_closed ) ) )
					);
					?>
				</span>
			<?php endif; ?>
		</div>
	</footer>
</article>