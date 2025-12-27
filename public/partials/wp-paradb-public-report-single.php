<?php
/**
 * Public single report display template
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
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-activity-handler.php';
require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-privacy.php';

$report_id = isset( $atts['id'] ) ? absint( $atts['id'] ) : ( isset( $_GET['report_id'] ) ? absint( $_GET['report_id'] ) : 0 );

if ( ! $report_id ) {
	echo '<p>' . esc_html__( 'No report specified.', 'wp-paradb' ) . '</p>';
	return;
}

$report = WP_ParaDB_Report_Handler::get_report( $report_id );
if ( ! $report || ! $report->is_published ) {
	echo '<p>' . esc_html__( 'Report not found or not published.', 'wp-paradb' ) . '</p>';
	return;
}

$case = WP_ParaDB_Case_Handler::get_case( $report->case_id );
if ( ! $case || ! $case->is_published ) {
	echo '<p>' . esc_html__( 'The case associated with this report is not available.', 'wp-paradb' ) . '</p>';
	return;
}

// Check Case Visibility
if ( $case->visibility === 'internal' && ! current_user_can( 'paradb_view_cases' ) ) {
	echo '<p>' . esc_html__( 'This report is restricted to internal team members.', 'wp-paradb' ) . '</p>';
	return;
}

$redaction_terms = WP_ParaDB_Privacy::get_case_redaction_terms( $case->case_id );
?>

<article class="paradb-single-report">
	<header class="report-header">
		<h1 class="report-title"><?php echo esc_html( WP_ParaDB_Privacy::redact( $report->report_title, $redaction_terms ) ); ?></h1>
		<div class="report-meta">
			<div class="meta-item">
				<strong><?php esc_html_e( 'Case:', 'wp-paradb' ); ?></strong>
				<a href="<?php echo esc_url( add_query_arg( array( 'case_id' => $case->case_id ), get_permalink() ) ); ?>">
					<?php echo esc_html( WP_ParaDB_Privacy::redact( $case->case_name, $redaction_terms ) ); ?>
				</a>
			</div>
			<div class="meta-item">
				<strong><?php esc_html_e( 'Date:', 'wp-paradb' ); ?></strong>
				<?php echo esc_html( gmdate( 'F j, Y', strtotime( $report->report_date ) ) ); ?>
			</div>
			<div class="meta-item">
				<strong><?php esc_html_e( 'Type:', 'wp-paradb' ); ?></strong>
				<?php echo esc_html( ucfirst( $report->report_type ) ); ?>
			</div>
		</div>
	</header>

	<div class="report-content">
		<?php if ( $report->report_summary ) : ?>
			<section class="report-section summary">
				<h2><?php esc_html_e( 'Executive Summary', 'wp-paradb' ); ?></h2>
				<div class="content">
					<?php echo wp_kses_post( wpautop( WP_ParaDB_Privacy::redact( $report->report_summary, $redaction_terms ) ) ); ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="report-section main-content">
			<h2><?php esc_html_e( 'Detailed Findings', 'wp-paradb' ); ?></h2>
			<div class="content">
				<?php echo wp_kses_post( wpautop( WP_ParaDB_Privacy::redact( $report->report_content, $redaction_terms ) ) ); ?>
			</div>
		</section>

		<?php
		// COMPOSITE ELEMENTS: Link Activities
		if ( $report->activity_id ) :
			$activity = WP_ParaDB_Activity_Handler::get_activity( $report->activity_id );
			if ( $activity && $activity->is_published ) :
				?>
				<section class="report-section activity-data">
					<h2><?php esc_html_e( 'Activity Details', 'wp-paradb' ); ?></h2>
					<div class="activity-summary-box" style="background: #f9f9f9; padding: 15px; border-left: 4px solid #2271b1; margin-bottom: 20px;">
						<h3><?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->activity_title, $redaction_terms ) ); ?></h3>
						<p><strong><?php esc_html_e( 'Type:', 'wp-paradb' ); ?></strong> <?php echo esc_html( ucfirst( $activity->activity_type ) ); ?></p>
						
						<?php if ( $activity->activity_summary ) : ?>
							<div class="activity-summary">
								<?php echo wp_kses_post( wpautop( WP_ParaDB_Privacy::redact( $activity->activity_summary, $redaction_terms ) ) ); ?>
							</div>
						<?php endif; ?>

						<?php if ( $activity->weather_conditions || $activity->temperature || $activity->moon_phase ) : ?>
							<div class="activity-env">
								<h4><?php esc_html_e( 'Environmental Snapshot', 'wp-paradb' ); ?></h4>
								<ul style="list-style: none; padding: 0;">
									<?php if ( $activity->weather_conditions ) : ?>
										<li><strong><?php esc_html_e( 'Weather:', 'wp-paradb' ); ?></strong> <?php echo esc_html( WP_ParaDB_Privacy::redact( $activity->weather_conditions, $redaction_terms ) ); ?></li>
									<?php endif; ?>
									<?php if ( $activity->temperature ) : ?>
										<li><strong><?php esc_html_e( 'Temperature:', 'wp-paradb' ); ?></strong> <?php echo esc_html( $activity->temperature ); ?></li>
									<?php endif; ?>
									<?php if ( $activity->moon_phase ) : ?>
										<li><strong><?php esc_html_e( 'Moon Phase:', 'wp-paradb' ); ?></strong> <?php echo esc_html( ucwords( str_replace( '_', ' ', $activity->moon_phase ) ) ); ?></li>
									<?php endif; ?>
								</ul>
							</div>
						<?php endif; ?>
					</div>
				</section>
				<?php
			endif;
		endif;
		?>

		<?php
		// Aggregate multiple activities if linked via relationship
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-relationship-handler.php';
		$linked_activities = WP_ParaDB_Relationship_Handler::get_relationships( $report_id, 'report' );
		
		$found_activities = false;
		foreach ( $linked_activities as $rel ) {
			$target_id = ($rel->from_id == $report_id && $rel->from_type == 'report') ? $rel->to_id : $rel->from_id;
			$target_type = ($rel->from_id == $report_id && $rel->from_type == 'report') ? $rel->to_type : $rel->from_type;
			
			if ( $target_type === 'activity' ) {
				$act = WP_ParaDB_Activity_Handler::get_activity( $target_id );
				if ( $act && $act->is_published && $act->activity_id != $report->activity_id ) {
					if ( !$found_activities ) {
						echo '<h3>' . esc_html__( 'Related Investigation Activities', 'wp-paradb' ) . '</h3>';
						$found_activities = true;
					}
					?>
					<div class="activity-summary-box small" style="background: #fff; padding: 10px; border: 1px solid #eee; margin-bottom: 10px;">
						<strong><?php echo esc_html( WP_ParaDB_Privacy::redact( $act->activity_title, $redaction_terms ) ); ?></strong>
						<small>(<?php echo esc_html( gmdate( 'M j, Y', strtotime( $act->activity_date ) ) ); ?>)</small>
						<?php if ( $act->activity_summary ) : ?>
							<div style="font-size: 0.9em; margin-top: 5px;">
								<?php echo wp_kses_post( wpautop( WP_ParaDB_Privacy::redact( $act->activity_summary, $redaction_terms ) ) ); ?>
							</div>
						<?php endif; ?>
					</div>
					<?php
				}
			}
		}
		?>
	</div>
</article>