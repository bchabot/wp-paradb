<?php
/**
 * Admin settings functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/admin
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle admin settings page
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/admin
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Admin_Settings {

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_paradb_save_settings', array( $this, 'handle_settings_save' ) );
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @since    1.0.0
	 */
	public function add_settings_page() {
		add_submenu_page(
			'paradb-dashboard',
			__( 'ParaDB Settings', 'wp-paradb' ),
			__( 'Settings', 'wp-paradb' ),
			'manage_paradb_settings',
			'paradb-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting( 'wp_paradb_settings', WP_ParaDB_Settings::OPTION_NAME );
	}

	/**
	 * Render settings page
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_paradb_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-paradb' ) );
		}

		$settings = WP_ParaDB_Settings::get_settings();
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'witness_form';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors(); ?>

			<h2 class="nav-tab-wrapper">
				<a href="?page=paradb-settings&tab=witness_form" 
				   class="nav-tab <?php echo 'witness_form' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Witness Form', 'wp-paradb' ); ?>
				</a>
				<a href="?page=paradb-settings&tab=consent" 
				   class="nav-tab <?php echo 'consent' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Consent Options', 'wp-paradb' ); ?>
				</a>
				<a href="?page=paradb-settings&tab=phenomena" 
				   class="nav-tab <?php echo 'phenomena' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Phenomena Types', 'wp-paradb' ); ?>
				</a>
				<a href="?page=paradb-settings&tab=privacy" 
				   class="nav-tab <?php echo 'privacy' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Privacy Policy', 'wp-paradb' ); ?>
				</a>
				<a href="?page=paradb-settings&tab=notifications" 
				   class="nav-tab <?php echo 'notifications' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Notifications', 'wp-paradb' ); ?>
				</a>
			</h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'paradb_save_settings', 'paradb_settings_nonce' ); ?>
				<input type="hidden" name="action" value="paradb_save_settings" />
				<input type="hidden" name="active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />

				<?php
				switch ( $active_tab ) {
					case 'witness_form':
						$this->render_witness_form_tab( $settings );
						break;
					case 'consent':
						$this->render_consent_tab( $settings );
						break;
					case 'phenomena':
						$this->render_phenomena_tab( $settings );
						break;
					case 'privacy':
						$this->render_privacy_tab( $settings );
						break;
					case 'notifications':
						$this->render_notifications_tab( $settings );
						break;
				}
				?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render witness form settings tab
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Current settings.
	 */
	private function render_witness_form_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Witness Form', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="witness_form_enabled" value="1" 
							<?php checked( $settings['witness_form_enabled'], true ); ?> />
						<?php esc_html_e( 'Allow public witness submissions', 'wp-paradb' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Use shortcode [paradb_witness_form] to display the form on any page.', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Account Creation', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="witness_account_creation" value="1" 
							<?php checked( $settings['witness_account_creation'], true ); ?> />
						<?php esc_html_e( 'Allow witnesses to create accounts', 'wp-paradb' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Witnesses can create an account to track their submission and submit future reports.', 'wp-paradb' ); ?>
					</p>

					<label style="margin-top: 10px; display: block;">
						<input type="checkbox" name="witness_account_auto_approve" value="1" 
							<?php checked( $settings['witness_account_auto_approve'], true ); ?> />
						<?php esc_html_e( 'Auto-approve new witness accounts', 'wp-paradb' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Required Fields', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="require_phone" value="1" 
							<?php checked( $settings['require_phone'], true ); ?> />
						<?php esc_html_e( 'Require phone number', 'wp-paradb' ); ?>
					</label><br>

					<label style="margin-top: 5px; display: block;">
						<input type="checkbox" name="require_address" value="1" 
							<?php checked( $settings['require_address'], true ); ?> />
						<?php esc_html_e( 'Require address', 'wp-paradb' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="min_description_length">
						<?php esc_html_e( 'Minimum Description Length', 'wp-paradb' ); ?>
					</label>
				</th>
				<td>
					<input type="number" id="min_description_length" name="min_description_length" 
						value="<?php echo esc_attr( $settings['min_description_length'] ); ?>" 
						min="0" max="1000" class="small-text" />
					<?php esc_html_e( 'characters', 'wp-paradb' ); ?>
					<p class="description">
						<?php esc_html_e( 'Minimum character count for incident description.', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'reCAPTCHA', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_recaptcha" value="1" 
							<?php checked( $settings['enable_recaptcha'], true ); ?> />
						<?php esc_html_e( 'Enable reCAPTCHA spam protection', 'wp-paradb' ); ?>
					</label>

					<div style="margin-top: 15px;">
						<label for="recaptcha_site_key" style="display: block; margin-bottom: 5px;">
							<?php esc_html_e( 'Site Key', 'wp-paradb' ); ?>
						</label>
						<input type="text" id="recaptcha_site_key" name="recaptcha_site_key" 
							value="<?php echo esc_attr( $settings['recaptcha_site_key'] ); ?>" 
							class="regular-text" />
					</div>

					<div style="margin-top: 10px;">
						<label for="recaptcha_secret_key" style="display: block; margin-bottom: 5px;">
							<?php esc_html_e( 'Secret Key', 'wp-paradb' ); ?>
						</label>
						<input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" 
							value="<?php echo esc_attr( $settings['recaptcha_secret_key'] ); ?>" 
							class="regular-text" />
					</div>

					<p class="description">
						<?php
						printf(
							wp_kses_post( __( 'Get your reCAPTCHA keys from <a href="%s" target="_blank">Google reCAPTCHA</a>.', 'wp-paradb' ) ),
							'https://www.google.com/recaptcha/admin'
						);
						?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render consent settings tab
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Current settings.
	 */
	private function render_consent_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Consent Option', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="radio" name="consent_default" value="none" 
							<?php checked( $settings['consent_default'], 'none' ); ?> />
						<?php esc_html_e( 'None - Require user to select', 'wp-paradb' ); ?>
					</label><br>

					<label style="margin-top: 5px; display: block;">
						<input type="radio" name="consent_default" value="private" 
							<?php checked( $settings['consent_default'], 'private' ); ?> />
						<?php esc_html_e( 'Keep report private', 'wp-paradb' ); ?>
					</label><br>

					<label style="margin-top: 5px; display: block;">
						<input type="radio" name="consent_default" value="anonymize" 
							<?php checked( $settings['consent_default'], 'anonymize' ); ?> />
						<?php esc_html_e( 'Anonymize for public', 'wp-paradb' ); ?>
					</label><br>

					<label style="margin-top: 5px; display: block;">
						<input type="radio" name="consent_default" value="publish" 
							<?php checked( $settings['consent_default'], 'publish' ); ?> />
						<?php esc_html_e( 'Publish in full', 'wp-paradb' ); ?>
					</label>

					<p class="description">
						<?php esc_html_e( 'Select the default consent option. If "None" is selected, witnesses must choose an option.', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Available Consent Options', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="consent_options_enabled[private]" value="1" 
							<?php checked( ! empty( $settings['consent_options_enabled']['private'] ), true ); ?> />
						<?php esc_html_e( 'Keep my report private', 'wp-paradb' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Report is kept confidential and only viewable by investigators.', 'wp-paradb' ); ?>
					</p>

					<label style="margin-top: 10px; display: block;">
						<input type="checkbox" name="consent_options_enabled[anonymize]" value="1" 
							<?php checked( ! empty( $settings['consent_options_enabled']['anonymize'] ), true ); ?> />
						<?php esc_html_e( 'Anonymize my report for the public', 'wp-paradb' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Report can be published without identifying information.', 'wp-paradb' ); ?>
					</p>

					<label style="margin-top: 10px; display: block;">
						<input type="checkbox" name="consent_options_enabled[publish]" value="1" 
							<?php checked( ! empty( $settings['consent_options_enabled']['publish'] ), true ); ?> />
						<?php esc_html_e( 'Publish in full (granted)', 'wp-paradb' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Full report can be published with witness details.', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render phenomena types tab
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Current settings.
	 */
	private function render_phenomena_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Phenomena Types', 'wp-paradb' ); ?></th>
				<td>
					<p class="description">
						<?php esc_html_e( 'Customize the types of phenomena witnesses can select. Format: one per line as "key|Label".', 'wp-paradb' ); ?>
					</p>
					<textarea name="phenomena_types_raw" rows="15" class="large-text code"><?php
						foreach ( $settings['phenomena_types'] as $key => $label ) {
							echo esc_textarea( $key . '|' . $label . "\n" );
						}
					?></textarea>
					<p class="description">
						<?php esc_html_e( 'Example: apparition|Apparition/Ghost', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render privacy policy tab
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Current settings.
	 */
	private function render_privacy_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Privacy Policy Requirement', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="require_privacy_acceptance" value="1" 
							<?php checked( $settings['require_privacy_acceptance'], true ); ?> />
						<?php esc_html_e( 'Require witnesses to accept privacy policy', 'wp-paradb' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="privacy_policy_text">
						<?php esc_html_e( 'Privacy Policy Text', 'wp-paradb' ); ?>
					</label>
				</th>
				<td>
					<?php
					wp_editor(
						$settings['privacy_policy_text'],
						'privacy_policy_text',
						array(
							'textarea_name' => 'privacy_policy_text',
							'textarea_rows' => 20,
							'media_buttons' => false,
							'teeny'         => true,
						)
					);
					?>
					<p class="description">
						<?php esc_html_e( 'This privacy policy will be displayed on the witness form.', 'wp-paradb' ); ?>
					</p>
					<button type="button" class="button" onclick="if(confirm('Reset to default privacy policy?')) { document.getElementById('privacy_policy_text').value = '<?php echo esc_js( WP_ParaDB_Settings::DEFAULT_PRIVACY_POLICY ); ?>'; }">
						<?php esc_html_e( 'Reset to Default', 'wp-paradb' ); ?>
					</button>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render notifications tab
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Current settings.
	 */
	private function render_notifications_tab( $settings ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Admin Notifications', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="notify_admin_new_submission" value="1" 
							<?php checked( $settings['notify_admin_new_submission'], true ); ?> />
						<?php esc_html_e( 'Send email notification on new submissions', 'wp-paradb' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="admin_notification_email">
						<?php esc_html_e( 'Notification Email', 'wp-paradb' ); ?>
					</label>
				</th>
				<td>
					<input type="email" id="admin_notification_email" name="admin_notification_email" 
						value="<?php echo esc_attr( $settings['admin_notification_email'] ); ?>" 
						class="regular-text" />
					<p class="description">
						<?php esc_html_e( 'Email address to receive submission notifications.', 'wp-paradb' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Witness Notifications', 'wp-paradb' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="notify_witness_confirmation" value="1" 
							<?php checked( $settings['notify_witness_confirmation'], true ); ?> />
						<?php esc_html_e( 'Send confirmation email to witnesses', 'wp-paradb' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Handle settings save
	 *
	 * @since    1.0.0
	 */
	public function handle_settings_save() {
		// Verify nonce.
		if ( ! isset( $_POST['paradb_settings_nonce'] ) || 
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['paradb_settings_nonce'] ) ), 'paradb_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wp-paradb' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_paradb_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to save settings.', 'wp-paradb' ) );
		}

		// Process phenomena types if provided.
		if ( isset( $_POST['phenomena_types_raw'] ) ) {
			$phenomena_types = array();
			$lines = explode( "\n", sanitize_textarea_field( wp_unslash( $_POST['phenomena_types_raw'] ) ) );
			
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( empty( $line ) ) {
					continue;
				}
				
				$parts = explode( '|', $line, 2 );
				if ( count( $parts ) === 2 ) {
					$key = sanitize_key( $parts[0] );
					$label = sanitize_text_field( $parts[1] );
					$phenomena_types[ $key ] = $label;
				}
			}
			
			$_POST['phenomena_types'] = $phenomena_types;
		}

		// Collect settings from POST.
		$new_settings = array(
			'witness_form_enabled'           => isset( $_POST['witness_form_enabled'] ),
			'witness_account_creation'       => isset( $_POST['witness_account_creation'] ),
			'witness_account_auto_approve'   => isset( $_POST['witness_account_auto_approve'] ),
			'require_privacy_acceptance'     => isset( $_POST['require_privacy_acceptance'] ),
			'privacy_policy_text'            => isset( $_POST['privacy_policy_text'] ) ? wp_kses_post( wp_unslash( $_POST['privacy_policy_text'] ) ) : '',
			'consent_default'                => isset( $_POST['consent_default'] ) ? sanitize_text_field( wp_unslash( $_POST['consent_default'] ) ) : 'none',
			'consent_options_enabled'        => isset( $_POST['consent_options_enabled'] ) && is_array( $_POST['consent_options_enabled'] )
				? array_map( 'boolval', wp_unslash( $_POST['consent_options_enabled'] ) )
				: array(),
			'notify_admin_new_submission'    => isset( $_POST['notify_admin_new_submission'] ),
			'notify_witness_confirmation'    => isset( $_POST['notify_witness_confirmation'] ),
			'admin_notification_email'       => isset( $_POST['admin_notification_email'] ) ? sanitize_email( wp_unslash( $_POST['admin_notification_email'] ) ) : '',
			'phenomena_types'                => isset( $_POST['phenomena_types'] ) ? $_POST['phenomena_types'] : array(),
			'require_phone'                  => isset( $_POST['require_phone'] ),
			'require_address'                => isset( $_POST['require_address'] ),
			'min_description_length'         => isset( $_POST['min_description_length'] ) ? absint( $_POST['min_description_length'] ) : 50,
			'enable_recaptcha'               => isset( $_POST['enable_recaptcha'] ),
			'recaptcha_site_key'             => isset( $_POST['recaptcha_site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_site_key'] ) ) : '',
			'recaptcha_secret_key'           => isset( $_POST['recaptcha_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['recaptcha_secret_key'] ) ) : '',
		);

		// Update settings.
		WP_ParaDB_Settings::update_settings( $new_settings );

		// Get active tab for redirect.
		$active_tab = isset( $_POST['active_tab'] ) ? sanitize_key( $_POST['active_tab'] ) : 'witness_form';

		// Redirect back with success message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'paradb-settings',
					'tab'      => $active_tab,
					'settings-updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

// Initialize settings admin.
new WP_ParaDB_Admin_Settings();