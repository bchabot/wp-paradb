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
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';

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

// Handle Visibility
if ( $case->visibility === 'internal' && ! current_user_can( 'paradb_view_cases' ) ) {
	echo '<p>' . esc_html__( 'This case is for internal use only.', 'wp-paradb' ) . '</p>';
	return;
}

if ( $case->visibility === 'private' && ! current_user_can( 'paradb_view_cases' ) ) {
	$entered_password = isset( $_POST['case_password'] ) ? sanitize_text_field( wp_unslash( $_POST['case_password'] ) ) : '';
	if ( $entered_password !== $case->password ) {
		?>
		<div class="paradb-password-form">
			<form method="post" action="">
				<p><?php esc_html_e( 'This case is password protected. Please enter the password to view details:', 'wp-paradb' ); ?></p>
				<input type="password" name="case_password" required>
				<button type="submit" class="button"><?php esc_html_e( 'Submit', 'wp-paradb' ); ?></button>
				<?php if ( $entered_password ) : ?>
					<p style="color:red;"><?php esc_html_e( 'Incorrect password.', 'wp-paradb' ); ?></p>
				<?php endif; ?>
			</form>
		</div>
		<?php
		return;
	}
}

// Get related data.
$reports = WP_ParaDB_Report_Handler::get_reports( array( 'case_id' => $case_id, 'limit' => 100 ) );
$activities = WP_ParaDB_Activity_Handler::get_activities( array( 'case_id' => $case_id, 'limit' => 100 ) );
$evidence = WP_ParaDB_Evidence_Handler::get_evidence_files( array( 'case_id' => $case_id, 'limit' => 100 ) );
$relationships = WP_ParaDB_Relationship_Handler::get_relationships( $case_id, 'case' );
$phenomena = maybe_unserialize( $case->phenomena_types );

// Privacy: Get redaction terms
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-privacy.php';
$redaction_terms = WP_ParaDB_Privacy::get_case_redaction_terms( $case_id );
?>

<article class="paradb-single-case">
	<header class="case-header">
		<div class="case-number"><?php echo esc_html( $case->case_number ); ?></div>
		<h1 class="case-title"><?php echo esc_html( WP_ParaDB_Privacy::redact( $case->case_name, $redaction_terms ) ); ?></h1>
		
		<div class="case-meta">
			<?php if ( $case->location_name || $case->location_city ) : ?>
				<div class="meta-item location">
					<strong><?php esc_html_e( 'Location:', 'wp-paradb' ); ?></strong>
					<?php
					if ( $case->location_name ) {
						echo esc_html( WP_ParaDB_Privacy::redact( $case->location_name, $redaction_terms ) );
						if ( $case->location_city ) {
							echo ' - ';
						}
					}
					if ( $case->location_city ) {
						echo esc_html( WP_ParaDB_Privacy::redact( $case->location_city, $redaction_terms ) );
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
						<li><?php echo esc_html( WP_ParaDB_Privacy::redact( $phenomenon, $redaction_terms ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $case->case_description ) : ?>
			<section class="case-section description">
				<h2><?php esc_html_e( 'Case Description', 'wp-paradb' ); ?></h2>
				<div class="description-content">
					<?php echo wp_kses_post( wpautop( WP_ParaDB_Privacy::redact( $case->case_description, $redaction_terms ) ) ); ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $relationships ) ) : ?>
			<section class="case-section relationships">
				<h2><?php esc_html_e( 'Linked Relationships', 'wp-paradb' ); ?></h2>
				<ul class="relationship-list">
					<?php 
					$rel_types = WP_ParaDB_Taxonomy_Handler::get_taxonomy_items( 'relationship_types' );
					foreach ( $relationships as $rel ) : 
						$is_from = ( absint( $rel->from_id ) === $case_id && $rel->from_type === 'case' );
						$target_id = $is_from ? $rel->to_id : $rel->from_id;
						$target_type = $is_from ? $rel->to_type : $rel->from_type;
						$label = WP_ParaDB_Relationship_Handler::get_object_label( $target_id, $target_type );
						$label = WP_ParaDB_Privacy::redact( $label, $redaction_terms );
						$type_label = isset( $rel_types[ $rel->relationship_type ] ) ? $rel_types[ $rel->relationship_type ] : $rel->relationship_type;
						?>
						<li>
							<strong><?php echo esc_html( $type_label ); ?>:</strong> 
							<?php echo esc_html( $label ); ?> 
							<small>(<?php echo esc_html( ucfirst( $target_type ) ); ?>)</small>
							<?php if ( $rel->notes ) : ?>
								<p class="rel-notes"><em><?php echo esc_html( WP_ParaDB_Privacy::redact( $rel->notes, $redaction_terms ) ); ?></em></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $reports ) ) : ?>
			<section class="case-section reports">
				<h2><?php esc_html_e( 'Investigation Reports', 'wp-paradb' ); ?></h2>
				<?php foreach ( $reports as $report ) : ?>
					<?php if ( $report->is_published ) : ?>
						<article class="report">
							<h3 class="report-title"><?php echo esc_html( WP_ParaDB_Privacy::redact( $report->report_title, $redaction_terms ) ); ?></h3>
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
									<?php echo wp_kses_post( wpautop( WP_ParaDB_Privacy::redact( $report->report_summary, $redaction_terms ) ) ); ?>
								</div>
							<?php endif; ?>
							
							<?php if ( $report->report_content ) : ?>
								<div class="report-content">
									<?php echo wp_kses_post( wpautop( WP_ParaDB_Privacy::redact( $report->report_content, $redaction_terms ) ) ); ?>
								</div>
							<?php endif; ?>
							
							<?php
							$activity = null;
							if ( $report->activity_id ) {
								$activity = WP_ParaDB_Activity_Handler::get_activity( $report->activity_id );
							}
							?>

							<?php if ( $activity && ( $activity->weather_conditions || $activity->temperature || $activity->moon_phase ) ) : ?>
								<div class="report-conditions">
									<h4><?php esc_html_e( 'Environmental Conditions (from linked activity)', 'wp-paradb' ); ?></h4>
									<?php if ( $activity->weather_conditions ) : ?>
										<p><strong><?php esc_html_e( 'Weather:', 'wp-paradb' ); ?></strong> <?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->weather_conditions, $redaction_terms ) ); ?></p>
									<?php endif; ?>
									<?php if ( $activity->temperature ) : ?>
										<p><strong><?php esc_html_e( 'Temperature:', 'wp-paradb' ); ?></strong> <?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->temperature, $redaction_terms ) ); ?></p>
									<?php endif; ?>
									<?php if ( $activity->moon_phase ) : ?>
										<p><strong><?php esc_html_e( 'Moon Phase:', 'wp-paradb' ); ?></strong> <?php echo esc_html( ucwords( str_replace( '_', ' ', $activity->moon_phase ) ) ); ?></p>
									<?php endif; ?>
									<?php if ( $activity->astrological_data ) : ?>
										<p><strong><?php esc_html_e( 'Astrological:', 'wp-paradb' ); ?></strong> <?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->astrological_data, $redaction_terms ) ); ?></p>
									<?php endif; ?>
									<?php if ( $activity->geomagnetic_data ) : ?>
										<p><strong><?php esc_html_e( 'Geomagnetic:', 'wp-paradb' ); ?></strong> <?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->geomagnetic_data, $redaction_terms ) ); ?></p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</article>
					<?php endif; ?>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $activities ) ) : ?>
			<section class="case-section activities">
				<h2><?php esc_html_e( 'Investigation Activities', 'wp-paradb' ); ?></h2>
				<?php foreach ( $activities as $activity ) : ?>
					<?php if ( $activity->is_published ) : ?>
						<article class="activity">
							<h3 class="activity-title"><?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->activity_title, $redaction_terms ) ); ?></h3>
							<div class="activity-meta">
								<span class="activity-date">
									<?php echo esc_html( gmdate( 'F j, Y', strtotime( $activity->activity_date ) ) ); ?>
								</span>
								<span class="activity-type">
									<?php echo esc_html( ucfirst( $activity->activity_type ) ); ?>
								</span>
							</div>

							<div class="activity-content">
								<?php echo wp_kses_post( wpautop( WP_ParaDB_Privacy::redact( $activity->activity_content, $redaction_terms ) ) ); ?>
							</div>

							<?php if ( $activity->weather_conditions || $activity->temperature || $activity->moon_phase || $activity->astrological_data || $activity->geomagnetic_data ) : ?>
								<div class="activity-conditions">
									<h4><?php esc_html_e( 'Environmental Conditions', 'wp-paradb' ); ?></h4>
									<?php if ( $activity->weather_conditions ) : ?>
										<p><strong><?php esc_html_e( 'Weather:', 'wp-paradb' ); ?></strong> <?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->weather_conditions, $redaction_terms ) ); ?></p>
									<?php endif; ?>
									<?php if ( $activity->temperature ) : ?>
										<p><strong><?php esc_html_e( 'Temperature:', 'wp-paradb' ); ?></strong> <?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->temperature, $redaction_terms ) ); ?></p>
									<?php endif; ?>
									<?php if ( $activity->moon_phase ) : ?>
										<p><strong><?php esc_html_e( 'Moon Phase:', 'wp-paradb' ); ?></strong> <?php echo esc_html( ucwords( str_replace( '_', ' ', $activity->moon_phase ) ) ); ?></p>
									<?php endif; ?>
									<?php if ( $activity->astrological_data ) : ?>
										<p><strong><?php esc_html_e( 'Astrological:', 'wp-paradb' ); ?></strong> <?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->astrological_data, $redaction_terms ) ); ?></p>
									<?php endif; ?>
									<?php if ( $activity->geomagnetic_data ) : ?>
										<p><strong><?php esc_html_e( 'Geomagnetic:', 'wp-paradb' ); ?></strong> <?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->geomagnetic_data, $redaction_terms ) ); ?></p>
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
									<img src="<?php echo esc_url( $file_url ); ?>" alt="<?php echo esc_attr( WP_ParaDB_Privacy::redact( $item->title ? $item->title : $item->file_name, $redaction_terms ) ); ?>">
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" class="evidence-link file">
									<span class="file-icon">📄</span>
									<span class="file-name"><?php echo esc_html( WP_ParaDB_Privacy::redact( $item->title ? $item->title : $item->file_name, $redaction_terms ) ); ?></span>
								</a>
							<?php endif; ?>
							<?php if ( $item->description ) : ?>
								<div class="evidence-description">
									<?php echo esc_html( WP_ParaDB_Privacy::redact( $item->description, $redaction_terms ) ); ?>
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