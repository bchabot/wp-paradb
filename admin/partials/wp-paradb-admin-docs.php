<?php
/**
 * Admin documentation page view
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
?>

<div class="wrap paradb-docs">
	<h1><?php esc_html_e( 'ParaDB Documentation', 'wp-paradb' ); ?></h1>

	<div class="nav-tab-wrapper">
		<a href="#getting-started" class="nav-tab nav-tab-active"><?php esc_html_e( 'Getting Started', 'wp-paradb' ); ?></a>
		<a href="#shortcodes" class="nav-tab"><?php esc_html_e( 'Shortcodes', 'wp-paradb' ); ?></a>
		<a href="#user-roles" class="nav-tab"><?php esc_html_e( 'User Roles', 'wp-paradb' ); ?></a>
		<a href="#environmental-data" class="nav-tab"><?php esc_html_e( 'Environmental Data', 'wp-paradb' ); ?></a>
		<a href="#api-setup" class="nav-tab"><?php esc_html_e( 'API Setup', 'wp-paradb' ); ?></a>
	</div>

	<div id="getting-started" class="tab-content" style="display: block; background: #fff; padding: 20px; border: 1px solid #ccc; border-top: none;">
		<h2><?php esc_html_e( 'Getting Started with ParaDB', 'wp-paradb' ); ?></h2>
		<p><?php esc_html_e( 'WP-ParaDB is a comprehensive system for managing paranormal investigations. It allows you to track cases, activities, reports, and evidence in a structured way.', 'wp-paradb' ); ?></p>
		
		<h3><?php esc_html_e( 'Workflow Overview', 'wp-paradb' ); ?></h3>
		<ol>
			<li><strong><?php esc_html_e( 'Create a Case:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Start by creating a new case. This is the top-level container for all your research at a specific location.', 'wp-paradb' ); ?></li>
			<li><strong><?php esc_html_e( 'Assign a Team:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Assign a Case Manager and multiple team members with specific roles (Lead, Researcher, Tech Specialist) to collaborate on the investigation.', 'wp-paradb' ); ?></li>
			<li><strong><?php esc_html_e( 'Log Activities & Field Notes:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Record site visits as Activities. Use the "Log My Actions" button on mobile for real-time field notes with GPS coordinates and photos.', 'wp-paradb' ); ?></li>
			<li><strong><?php esc_html_e( 'Write Reports:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Summarize your findings in formal Reports. Reports can be linked to specific Activities.', 'wp-paradb' ); ?></li>
			<li><strong><?php esc_html_e( 'Upload Evidence:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Attach photos, audio, or video files to your cases, activities, or reports.', 'wp-paradb' ); ?></li>
			<li><strong><?php esc_html_e( 'Publish:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'When ready, mark your cases and specific activities/reports as published to share them on your website.', 'wp-paradb' ); ?></li>
		</ol>
	</div>

	<div id="shortcodes" class="tab-content" style="display: none; background: #fff; padding: 20px; border: 1px solid #ccc; border-top: none;">
		<h2><?php esc_html_e( 'Available Shortcodes', 'wp-paradb' ); ?></h2>
		<p><?php esc_html_e( 'Use these shortcodes to display ParaDB content on your pages.', 'wp-paradb' ); ?></p>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shortcode', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'Description', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'Attributes', 'wp-paradb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>[paradb_cases]</code></td>
					<td><?php esc_html_e( 'Displays a grid of all published cases with search functionality.', 'wp-paradb' ); ?></td>
					<td>
						<code>limit="10"</code> - <?php esc_html_e( 'Number of cases to show.', 'wp-paradb' ); ?><br>
						<code>orderby="date_created"</code> - <?php esc_html_e( 'Field to sort by.', 'wp-paradb' ); ?><br>
						<code>order="DESC"</code> - <?php esc_html_e( 'Sort direction (ASC/DESC).', 'wp-paradb' ); ?>
					</td>
				</tr>
				<tr>
					<td><code>[paradb_single_case id="123"]</code></td>
					<td><?php esc_html_e( 'Displays full details for a specific case.', 'wp-paradb' ); ?></td>
					<td>
						<code>id="123"</code> - (<strong><?php esc_html_e( 'Required', 'wp-paradb' ); ?></strong>) <?php esc_html_e( 'The Case ID to display.', 'wp-paradb' ); ?>
					</td>
				</tr>
				<tr>
					<td><code>[paradb_witness_form]</code></td>
					<td><?php esc_html_e( 'Displays the public witness submission form.', 'wp-paradb' ); ?></td>
					<td>
						<code>redirect_url="https://site.com/thanks"</code> - <?php esc_html_e( 'URL to redirect to after successful submission.', 'wp-paradb' ); ?>
					</td>
				</tr>
				<tr>
					<td><code>[paradb_log_book]</code></td>
					<td><?php esc_html_e( 'Displays a chronological log of all published activities and reports.', 'wp-paradb' ); ?></td>
					<td>
						<code>limit="20"</code> - <?php esc_html_e( 'Number of entries to show.', 'wp-paradb' ); ?>
					</td>
				</tr>
				<tr>
					<td><code>[paradb_reports]</code></td>
					<td><?php esc_html_e( 'Displays a grid of all published investigation reports.', 'wp-paradb' ); ?></td>
					<td>
						<code>limit="10"</code> - <?php esc_html_e( 'Number of reports to show.', 'wp-paradb' ); ?><br>
						<code>case_id="123"</code> - <?php esc_html_e( 'Filter reports by a specific Case ID.', 'wp-paradb' ); ?>
					</td>
				</tr>
				<tr>
					<td><code>[paradb_single_report]</code></td>
					<td><?php esc_html_e( 'Displays full details for a specific report, including summaries of linked activities.', 'wp-paradb' ); ?></td>
					<td>
						<code>id="123"</code> - (<strong><?php esc_html_e( 'Required', 'wp-paradb' ); ?></strong>) <?php esc_html_e( 'The Report ID to display.', 'wp-paradb' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div id="user-roles" class="tab-content" style="display: none; background: #fff; padding: 20px; border: 1px solid #ccc; border-top: none;">
		<h2><?php esc_html_e( 'User Roles & Capabilities', 'wp-paradb' ); ?></h2>
		<p><?php esc_html_e( 'ParaDB includes three custom roles to manage your research team.', 'wp-paradb' ); ?></p>

		<ul>
			<li><strong><?php esc_html_e( 'ParaDB Director:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Full access to all cases, settings, and maintenance tools. Can manage witness submissions.', 'wp-paradb' ); ?></li>
			<li><strong><?php esc_html_e( 'ParaDB Team Leader:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Can create and edit any case. Can manage team assignments and publish cases.', 'wp-paradb' ); ?></li>
			<li><strong><?php esc_html_e( 'ParaDB Investigator:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Can view cases assigned to them and add reports/activities to those cases.', 'wp-paradb' ); ?></li>
		</ul>
	</div>

	<div id="environmental-data" class="tab-content" style="display: none; background: #fff; padding: 20px; border: 1px solid #ccc; border-top: none;">
		<h2><?php esc_html_e( 'Environmental Data Integration', 'wp-paradb' ); ?></h2>
		<p><?php esc_html_e( 'One of the most powerful features of ParaDB is the ability to correlate paranormal observations with environmental conditions.', 'wp-paradb' ); ?></p>
		
		<h3><?php esc_html_e( 'Auto-Fetching Data', 'wp-paradb' ); ?></h3>
		<p><?php esc_html_e( 'When editing an Activity, ensure you have set a valid location (or case location) and a date/time. Click "Auto-fetch Environmental Data" to retrieve:', 'wp-paradb' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Weather:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Historical or forecast temperature and conditions.', 'wp-paradb' ); ?></li>
			<li><strong><?php esc_html_e( 'Astronomical:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Moon phase and visibility.', 'wp-paradb' ); ?></li>
			<li><strong><?php esc_html_e( 'Astrological:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Planetary positions and transits.', 'wp-paradb' ); ?></li>
			<li><strong><?php esc_html_e( 'Geomagnetic:', 'wp-paradb' ); ?></strong> <?php esc_html_e( 'Solar activity and Kp-Index (Planetary K-index).', 'wp-paradb' ); ?></li>
		</ul>
		<p class="description"><?php esc_html_e( 'Note: Some data requires API keys to be configured in the ParaDB Settings page.', 'wp-paradb' ); ?></p>
	</div>

	<div id="api-setup" class="tab-content" style="display: none; background: #fff; padding: 20px; border: 1px solid #ccc; border-top: none;">
		<h2><?php esc_html_e( 'API Configuration', 'wp-paradb' ); ?></h2>
		<p><?php esc_html_e( 'To enable full functionality like maps and environmental data, you need to configure API keys from various providers.', 'wp-paradb' ); ?></p>
		
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Service', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'Purpose', 'wp-paradb' ); ?></th>
					<th><?php esc_html_e( 'How to Get a Key', 'wp-paradb' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Google Maps', 'wp-paradb' ); ?></strong></td>
					<td><?php esc_html_e( 'Interactive maps and address auto-suggest.', 'wp-paradb' ); ?></td>
					<td><?php esc_html_e( 'Create a project in the Google Cloud Console, enable "Maps JavaScript API" and "Places API", then create an API key.', 'wp-paradb' ); ?> <a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank"><?php esc_html_e( 'Link', 'wp-paradb' ); ?></a></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'LocationIQ', 'wp-paradb' ); ?></strong></td>
					<td><?php esc_html_e( 'Geocoding (converting addresses to coordinates) for OpenStreetMap.', 'wp-paradb' ); ?></td>
					<td><?php esc_html_e( 'Sign up for a free account at LocationIQ.com. They offer 10,000 free requests per day.', 'wp-paradb' ); ?> <a href="https://locationiq.com/" target="_blank"><?php esc_html_e( 'Link', 'wp-paradb' ); ?></a></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WeatherAPI', 'wp-paradb' ); ?></strong></td>
					<td><?php esc_html_e( 'Astronomical data (moon phase) and weather fetching.', 'wp-paradb' ); ?></td>
					<td><?php esc_html_e( 'Sign up for a free account at WeatherAPI.com.', 'wp-paradb' ); ?> <a href="https://www.weatherapi.com/signup.aspx" target="_blank"><?php esc_html_e( 'Link', 'wp-paradb' ); ?></a></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'FreeAstroAPI', 'wp-paradb' ); ?></strong></td>
					<td><?php esc_html_e( 'Detailed planetary transit and astrological data.', 'wp-paradb' ); ?></td>
					<td><?php esc_html_e( 'Sign up for an API key at FreeAstroAPI.com.', 'wp-paradb' ); ?> <a href="https://freeastroapi.com/" target="_blank"><?php esc_html_e( 'Link', 'wp-paradb' ); ?></a></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$('.nav-tab').on('click', function(e) {
		e.preventDefault();
		var target = $(this).attr('href');
		
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		
		$('.tab-content').hide();
		$(target).show();
	});
});
</script>
