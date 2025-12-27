<?php
/**
 * Public witness form functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/public
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle public witness form display and submission
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/public
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Witness_Form {

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_shortcode( 'paradb_witness_form', array( $this, 'render_form' ) );
		add_action( 'wp_ajax_nopriv_paradb_submit_witness_form', array( $this, 'handle_form_submission' ) );
		add_action( 'wp_ajax_paradb_submit_witness_form', array( $this, 'handle_form_submission' ) );
	}

	/**
	 * Render the witness form
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            Form HTML.
	 */
	public function render_form( $atts ) {
		// Check if form is enabled.
		if ( ! WP_ParaDB_Settings::get_setting( 'witness_form_enabled', true ) ) {
			return '<p>' . esc_html__( 'The witness submission form is currently unavailable.', 'wp-paradb' ) . '</p>';
		}

		$atts = shortcode_atts( array(
			'redirect_url' => '',
		), $atts, 'paradb_witness_form' );

		ob_start();
		?>
		<div class="paradb-witness-form-container">
			<div class="paradb-form-messages"></div>
			
			<form id="paradb-witness-form" class="paradb-witness-form" method="post">
				<?php wp_nonce_field( 'paradb_witness_form', 'paradb_witness_nonce' ); ?>
				
				<!-- Contact Information -->
				<fieldset class="paradb-fieldset">
					<legend><?php esc_html_e( 'Contact Information', 'wp-paradb' ); ?></legend>
					
					<p class="paradb-form-field">
						<label for="account_name"><?php esc_html_e( 'Name (Optional)', 'wp-paradb' ); ?></label>
						<input type="text" id="account_name" name="account_name" />
					</p>

					<p class="paradb-form-field required">
						<label for="account_email"><?php esc_html_e( 'Email Address', 'wp-paradb' ); ?> *</label>
						<input type="email" id="account_email" name="account_email" required />
					</p>

					<p class="paradb-form-field<?php echo WP_ParaDB_Settings::get_setting( 'require_phone', false ) ? ' required' : ''; ?>">
						<label for="account_phone">
							<?php esc_html_e( 'Phone Number', 'wp-paradb' ); ?>
							<?php if ( WP_ParaDB_Settings::get_setting( 'require_phone', false ) ) : ?>
								*
							<?php else : ?>
								<?php esc_html_e( '(Optional)', 'wp-paradb' ); ?>
							<?php endif; ?>
						</label>
						<input type="tel" id="account_phone" name="account_phone" 
							<?php echo WP_ParaDB_Settings::get_setting( 'require_phone', false ) ? 'required' : ''; ?> />
					</p>

					<p class="paradb-form-field<?php echo WP_ParaDB_Settings::get_setting( 'require_address', false ) ? ' required' : ''; ?>">
						<label for="account_address">
							<?php esc_html_e( 'Address', 'wp-paradb' ); ?>
							<?php if ( WP_ParaDB_Settings::get_setting( 'require_address', false ) ) : ?>
								*
							<?php else : ?>
								<?php esc_html_e( '(Optional)', 'wp-paradb' ); ?>
							<?php endif; ?>
						</label>
						<div style="display:flex; gap: 5px;">
							<textarea id="account_address" name="account_address" rows="3" style="flex:1;"
								<?php echo WP_ParaDB_Settings::get_setting( 'require_address', false ) ? 'required' : ''; ?>></textarea>
							<button type="button" class="get-current-location button" data-target="#account_address" title="<?php esc_attr_e( 'Use current GPS location', 'wp-paradb' ); ?>" style="align-self: flex-start;">📍</button>
						</div>
					</p>
				</fieldset>

				<!-- Incident Information -->
				<fieldset class="paradb-fieldset">
					<legend><?php esc_html_e( 'Incident Information', 'wp-paradb' ); ?></legend>
					
					<p class="paradb-form-field required">
						<label for="incident_date"><?php esc_html_e( 'Date of Incident', 'wp-paradb' ); ?> *</label>
						<input type="date" id="incident_date" name="incident_date" required />
					</p>

					<p class="paradb-form-field">
						<label for="incident_time"><?php esc_html_e( 'Time of Incident (Optional)', 'wp-paradb' ); ?></label>
						<input type="time" id="incident_time" name="incident_time" />
					</p>

					<p class="paradb-form-field required">
						<label for="incident_location"><?php esc_html_e( 'Location of Incident', 'wp-paradb' ); ?> *</label>
						<div style="display:flex; gap: 5px;">
							<input type="text" id="incident_location" name="incident_location" required style="flex:1;" autocomplete="off"
								placeholder="<?php esc_attr_e( 'e.g., 123 Main St, City, State', 'wp-paradb' ); ?>" />
							<button type="button" class="get-current-location button" data-target="#incident_location" title="<?php esc_attr_e( 'Use current GPS location', 'wp-paradb' ); ?>">📍</button>
						</div>
						<div style="margin-top: 5px;">
							<input type="hidden" name="latitude" id="latitude">
							<input type="hidden" name="longitude" id="longitude">
							<button type="button" id="geocode-address" class="button button-small"><?php esc_html_e( 'Find on Map', 'wp-paradb' ); ?></button>
						</div>
						<div id="location-map" style="height: 300px; margin-top: 10px; border: 1px solid #ccc;"></div>
					</p>

					<p class="paradb-form-field required">
						<label><?php esc_html_e( 'Type(s) of Phenomenon Experienced', 'wp-paradb' ); ?></label>
						<span class="paradb-help-text"><?php esc_html_e( 'Select all that apply', 'wp-paradb' ); ?></span>
						<?php
						$phenomena_types = WP_ParaDB_Settings::get_phenomena_types();
						foreach ( $phenomena_types as $key => $label ) :
						?>
						<label class="paradb-checkbox-label">
							<input type="checkbox" name="phenomena_types[]" value="<?php echo esc_attr( $key ); ?>" />
							<?php echo esc_html( $label ); ?>
						</label>
						<?php endforeach; ?>
					</p>

					<p class="paradb-form-field required">
						<label for="incident_description"><?php esc_html_e( 'Description of Experience', 'wp-paradb' ); ?> *</label>
						<span class="paradb-help-text">
							<?php
							$min_length = WP_ParaDB_Settings::get_setting( 'min_description_length', 50 );
							printf(
								esc_html__( 'Please provide a detailed description (minimum %d characters)', 'wp-paradb' ),
								$min_length
							);
							?>
						</span>
						<textarea id="incident_description" name="incident_description" rows="8" required 
							data-min-length="<?php echo esc_attr( $min_length ); ?>"></textarea>
					</p>

					<p class="paradb-form-field">
						<label for="witnesses_present"><?php esc_html_e( 'Number of Other Witnesses Present', 'wp-paradb' ); ?></label>
						<input type="number" id="witnesses_present" name="witnesses_present" min="0" max="100" />
					</p>

					<p class="paradb-form-field">
						<label for="witness_names"><?php esc_html_e( 'Names of Other Witnesses (Optional)', 'wp-paradb' ); ?></label>
						<textarea id="witness_names" name="witness_names" rows="3"></textarea>
					</p>
				</fieldset>

				<!-- Previous Experiences -->
				<fieldset class="paradb-fieldset">
					<legend><?php esc_html_e( 'Previous Experiences', 'wp-paradb' ); ?></legend>
					
					<p class="paradb-form-field">
						<label>
							<input type="checkbox" id="previous_experiences" name="previous_experiences" value="1" />
							<?php esc_html_e( 'I have experienced similar phenomena before', 'wp-paradb' ); ?>
						</label>
					</p>

					<p class="paradb-form-field paradb-conditional" data-depends-on="previous_experiences" style="display:none;">
						<label for="previous_details"><?php esc_html_e( 'Please describe your previous experiences', 'wp-paradb' ); ?></label>
						<textarea id="previous_details" name="previous_details" rows="4"></textarea>
					</p>
				</fieldset>

				<!-- Consent Options -->
				<fieldset class="paradb-fieldset">
					<legend><?php esc_html_e( 'Publication Consent', 'wp-paradb' ); ?></legend>
					
					<?php
					$consent_options = WP_ParaDB_Settings::get_enabled_consent_options();
					$default_consent = WP_ParaDB_Settings::get_default_consent();
					
					if ( ! empty( $consent_options ) ) :
					?>
					<p class="paradb-form-field required">
						<span class="paradb-help-text">
							<?php esc_html_e( 'Please select how you would like your report to be handled:', 'wp-paradb' ); ?>
						</span>
						<?php foreach ( $consent_options as $key => $label ) : ?>
						<label class="paradb-radio-label">
							<input type="radio" name="consent_status" value="<?php echo esc_attr( $key ); ?>" 
								<?php checked( $default_consent, $key ); ?>
								<?php echo 'none' === $default_consent ? 'required' : ''; ?> />
							<?php echo esc_html( $label ); ?>
						</label>
						<?php endforeach; ?>
					</p>
					<?php endif; ?>

					<p class="paradb-form-field">
						<label>
							<input type="checkbox" name="allow_followup" value="1" checked />
							<?php esc_html_e( 'I consent to being contacted for follow-up questions', 'wp-paradb' ); ?>
						</label>
					</p>
				</fieldset>

				<!-- Account Creation -->
				<?php if ( WP_ParaDB_Settings::get_setting( 'witness_account_creation', true ) && ! is_user_logged_in() ) : ?>
				<fieldset class="paradb-fieldset">
					<legend><?php esc_html_e( 'Account Creation (Optional)', 'wp-paradb' ); ?></legend>
					
					<p class="paradb-form-field">
						<label>
							<input type="checkbox" id="create_account" name="create_account" value="1" />
							<?php esc_html_e( 'Create an account to track your submission and submit future reports', 'wp-paradb' ); ?>
						</label>
						<span class="paradb-help-text">
							<?php esc_html_e( 'An account will be created using your email address. You will receive login credentials via email.', 'wp-paradb' ); ?>
						</span>
					</p>
				</fieldset>
				<?php endif; ?>

				<!-- Privacy Policy -->
				<?php if ( WP_ParaDB_Settings::get_setting( 'require_privacy_acceptance', true ) ) : ?>
				<fieldset class="paradb-fieldset">
					<legend><?php esc_html_e( 'Privacy Policy', 'wp-paradb' ); ?></legend>
					
					<div class="paradb-privacy-policy">
						<?php echo wp_kses_post( wpautop( WP_ParaDB_Settings::get_setting( 'privacy_policy_text' ) ) ); ?>
					</div>

					<p class="paradb-form-field required">
						<label>
							<input type="checkbox" name="privacy_accepted" value="1" required />
							<?php esc_html_e( 'I have read and accept the privacy policy', 'wp-paradb' ); ?> *
						</label>
					</p>
				</fieldset>
				<?php endif; ?>

				<p class="paradb-form-actions">
					<button type="submit" class="paradb-submit-button">
						<?php esc_html_e( 'Submit Report', 'wp-paradb' ); ?>
					</button>
				</p>

				<?php if ( ! empty( $atts['redirect_url'] ) ) : ?>
				<input type="hidden" name="redirect_url" value="<?php echo esc_url( $atts['redirect_url'] ); ?>" />
				<?php endif; ?>
			</form>
		</div>

		<script>
		(function($) {
			'use strict';

			$(document).ready(function() {
				// Geolocation support
				$('.get-current-location').on('click', function() {
					var $btn = $(this);
					var target = $btn.data('target');
					var $input = $(target);
					
					if (!navigator.geolocation) {
						alert('<?php echo esc_js( __( 'Geolocation is not supported by your browser.', 'wp-paradb' ) ); ?>');
						return;
					}

					$btn.text('⌛');
					navigator.geolocation.getCurrentPosition(function(pos) {
						var coords = pos.coords.latitude.toFixed(6) + ', ' + pos.coords.longitude.toFixed(6);
						$input.val(coords);
						$btn.text('📍');
					}, function(err) {
						alert('Error: ' + err.message);
						$btn.text('📍');
					});
				});

				// Location autocomplete (Address Book)
				if ($.fn.autocomplete) {
					$('#incident_location, #account_address').autocomplete({
						source: function(request, response) {
							$.ajax({
								url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
								data: {
									action: 'paradb_search_locations',
									term: request.term
								},
								success: function(res) {
									if (res.success) {
										response(res.data);
									}
								}
							});
						},
						minLength: 2
					});
				}

				// Handle conditional field display
				$('#previous_experiences').on('change', function() {
					var $conditional = $('.paradb-conditional[data-depends-on="previous_experiences"]');
					if ($(this).is(':checked')) {
						$conditional.slideDown();
					} else {
						$conditional.slideUp();
						$('#previous_details').val('');
					}
				});

				// Form validation
				$('#paradb-witness-form').on('submit', function(e) {
					var isValid = true;
					var messages = [];

					// Check phenomena types
					if ($('input[name="phenomena_types[]"]:checked').length === 0) {
						isValid = false;
						messages.push('<?php echo esc_js( __( 'Please select at least one type of phenomenon.', 'wp-paradb' ) ); ?>');
					}

					// Check description length
					var description = $('#incident_description').val();
					var minLength = parseInt($('#incident_description').data('min-length')) || 50;
					if (description.length < minLength) {
						isValid = false;
						messages.push('<?php echo esc_js( __( 'Description must be at least', 'wp-paradb' ) ); ?> ' + minLength + ' <?php echo esc_js( __( 'characters.', 'wp-paradb' ) ); ?>');
					}

					// Check consent if required
					<?php if ( 'none' === WP_ParaDB_Settings::get_default_consent() ) : ?>
					if ($('input[name="consent_status"]:checked').length === 0) {
						isValid = false;
						messages.push('<?php echo esc_js( __( 'Please select a publication consent option.', 'wp-paradb' ) ); ?>');
					}
					<?php endif; ?>

					if (!isValid) {
						e.preventDefault();
						showMessage('error', messages.join('<br>'));
						return false;
					}

					// Handle AJAX submission
					e.preventDefault();
					submitForm($(this));
				});

				function submitForm($form) {
					var $button = $form.find('.paradb-submit-button');
					var originalText = $button.text();
					
					$button.prop('disabled', true).text('<?php echo esc_js( __( 'Submitting...', 'wp-paradb' ) ); ?>');
					$('.paradb-form-messages').empty();

					$.ajax({
						url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: $form.serialize() + '&action=paradb_submit_witness_form',
						dataType: 'json',
						success: function(response) {
							if (response.success) {
								showMessage('success', response.data.message);
								$form[0].reset();
								
								// Redirect if specified
								if (response.data.redirect_url) {
									setTimeout(function() {
										window.location.href = response.data.redirect_url;
									}, 2000);
								}
							} else {
								showMessage('error', response.data.message || '<?php echo esc_js( __( 'An error occurred. Please try again.', 'wp-paradb' ) ); ?>');
							}
						},
						error: function() {
							showMessage('error', '<?php echo esc_js( __( 'An error occurred. Please try again.', 'wp-paradb' ) ); ?>');
						},
						complete: function() {
							$button.prop('disabled', false).text(originalText);
						}
					});
				}

				function showMessage(type, message) {
					var $container = $('.paradb-form-messages');
					var className = 'paradb-message-' + type;
					
					$container.html('<div class="' + className + '">' + message + '</div>');
					$('html, body').animate({
						scrollTop: $container.offset().top - 100
					}, 500);
				}
			});
		})(jQuery);
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle form submission via AJAX
	 *
	 * @since    1.0.0
	 */
	public function handle_form_submission() {
		// Verify nonce.
		if ( ! isset( $_POST['paradb_witness_nonce'] ) || 
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['paradb_witness_nonce'] ) ), 'paradb_witness_form' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-paradb' ) ) );
		}

		// Prepare data from form.
		$data = array(
			'account_email'         => isset( $_POST['account_email'] ) ? sanitize_email( wp_unslash( $_POST['account_email'] ) ) : '',
			'account_name'          => isset( $_POST['account_name'] ) ? sanitize_text_field( wp_unslash( $_POST['account_name'] ) ) : '',
			'account_phone'         => isset( $_POST['account_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['account_phone'] ) ) : '',
			'account_address'       => isset( $_POST['account_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['account_address'] ) ) : '',
			'incident_date'         => isset( $_POST['incident_date'] ) ? sanitize_text_field( wp_unslash( $_POST['incident_date'] ) ) : '',
			'incident_time'         => isset( $_POST['incident_time'] ) ? sanitize_text_field( wp_unslash( $_POST['incident_time'] ) ) : '',
			'incident_location'     => isset( $_POST['incident_location'] ) ? sanitize_text_field( wp_unslash( $_POST['incident_location'] ) ) : '',
			'incident_description'  => isset( $_POST['incident_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['incident_description'] ) ) : '',
			'phenomena_types'       => isset( $_POST['phenomena_types'] ) && is_array( $_POST['phenomena_types'] ) 
				? array_map( 'sanitize_text_field', wp_unslash( $_POST['phenomena_types'] ) ) 
				: array(),
			'witnesses_present'     => isset( $_POST['witnesses_present'] ) ? absint( $_POST['witnesses_present'] ) : 0,
			'witness_names'         => isset( $_POST['witness_names'] ) ? sanitize_textarea_field( wp_unslash( $_POST['witness_names'] ) ) : '',
			'previous_experiences'  => isset( $_POST['previous_experiences'] ) ? true : false,
			'previous_details'      => isset( $_POST['previous_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['previous_details'] ) ) : '',
			'consent_status'        => isset( $_POST['consent_status'] ) ? sanitize_text_field( wp_unslash( $_POST['consent_status'] ) ) : '',
			'allow_followup'        => isset( $_POST['allow_followup'] ) ? true : false,
			'privacy_accepted'      => isset( $_POST['privacy_accepted'] ) ? true : false,
			'create_account'        => isset( $_POST['create_account'] ) ? true : false,
		);

		// Attempt to create witness account.
		$result = WP_ParaDB_Witness_Handler::create_witness_account( $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$response_data = array(
			'message' => __( 'Thank you for submitting your report. We will review it and may contact you for follow-up.', 'wp-paradb' ),
		);

		// Handle redirect.
		if ( isset( $_POST['redirect_url'] ) && ! empty( $_POST['redirect_url'] ) ) {
			$response_data['redirect_url'] = esc_url_raw( wp_unslash( $_POST['redirect_url'] ) );
		}

		wp_send_json_success( $response_data );
	}
}