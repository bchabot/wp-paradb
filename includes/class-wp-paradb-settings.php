<?php
/**
 * Settings management functionality
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
 * Handle plugin settings
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Settings {

	/**
	 * Option name for storing settings
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	const OPTION_NAME = 'wp_paradb_options';

	/**
	 * Default privacy policy text
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	const DEFAULT_PRIVACY_POLICY = 'Privacy Policy for Paranormal Witness Submissions

Last Updated: [Date]

1. INFORMATION WE COLLECT
When you submit a witness report, we collect:
   - Contact information (name, email, phone - optional)
   - Location and date/time of incident
   - Description of your experience
   - Any supporting details you provide

2. HOW WE USE YOUR INFORMATION
Your information is used to:
   - Document and investigate paranormal phenomena
   - Contact you for follow-up questions (if you consent)
   - Compile statistical data about paranormal activity
   - Share your experience publicly (only with your explicit consent)

3. INFORMATION SHARING
   - Private Reports: Kept confidential, viewed only by authorized investigators
   - Anonymized Reports: Published without identifying information
   - Full Publication: Published with your permission, may include your name/details
   - We never sell your information to third parties
   - We may share with law enforcement if legally required

4. YOUR RIGHTS
You have the right to:
   - Request a copy of your submitted information
   - Request corrections to your information
   - Request deletion of your report (subject to legal retention requirements)
   - Withdraw consent for publication at any time
   - Create an account to track your submission

5. DATA SECURITY
We implement reasonable security measures to protect your information, including:
   - Secure encrypted storage
   - Limited access to authorized personnel only
   - Regular security audits

6. DATA RETENTION
We retain witness reports indefinitely for research purposes, unless you request deletion.

7. COOKIES AND TRACKING
Our website uses standard cookies for functionality. We do not use third-party tracking cookies.

8. CHANGES TO THIS POLICY
We may update this privacy policy. Changes will be posted on this page with an updated date.

9. CONTACT US
For questions about this privacy policy or your data:
[Your Organization Name]
[Contact Email]
[Contact Phone]

By submitting a witness report, you acknowledge that you have read and understood this privacy policy.';

	/**
	 * Get all settings
	 *
	 * @since    1.0.0
	 * @return   array    Settings array.
	 */
	public static function get_settings() {
		$defaults = self::get_default_settings();
		$settings = get_option( self::OPTION_NAME, array() );
		
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Get default settings
	 *
	 * @since    1.0.0
	 * @return   array    Default settings.
	 */
	public static function get_default_settings() {
		return array(
			// Witness form settings.
			'witness_form_enabled'           => true,
			'witness_account_creation'       => true,
			'witness_account_auto_approve'   => false,
			'require_privacy_acceptance'     => true,
			'privacy_policy_text'            => self::DEFAULT_PRIVACY_POLICY,
			'allow_public_submissions'       => true,
			'moderate_submissions'           => true,
			
			// Case settings.
			'case_number_format'             => 'CASE-%Y-%ID%',
			'require_client_consent'         => true,
			'items_per_page'                 => 20,
			'default_case_status'            => 'open',
			'case_statuses'                  => array(
				'open'       => __( 'Open', 'wp-paradb' ),
				'active'     => __( 'Active Investigation', 'wp-paradb' ),
				'reviewing'  => __( 'Under Review', 'wp-paradb' ),
				'closed'     => __( 'Closed', 'wp-paradb' ),
				'archived'   => __( 'Archived', 'wp-paradb' ),
			),

			// Consent settings.
			'consent_default'                => 'none', // none, private, anonymize, publish
			'consent_options_enabled'        => array(
				'private'    => true,
				'anonymize'  => true,
				'publish'    => true,
			),
			
			// Notification settings.
			'notify_admin_new_submission'    => true,
			'notify_witness_confirmation'    => true,
			'admin_notification_email'       => get_option( 'admin_email' ),
			'witness_success_message'        => __( 'Thank you for submitting your report. We will review it and may contact you for follow-up.', 'wp-paradb' ),
			
			// Phenomena types (can be customized).
			'phenomena_types'                => array(
				'apparition'         => 'Apparition/Ghost',
				'poltergeist'        => 'Poltergeist Activity',
				'evp'                => 'Electronic Voice Phenomenon (EVP)',
				'shadow_figure'      => 'Shadow Figure',
				'lights'             => 'Unexplained Lights',
				'sounds'             => 'Unexplained Sounds',
				'smells'             => 'Unexplained Smells',
				'temperature'        => 'Temperature Changes',
				'touch'              => 'Physical Touch/Sensation',
				'movement'           => 'Object Movement',
				'cryptid'            => 'Cryptid/Unknown Creature',
				'ufo'                => 'UFO/Aerial Phenomenon',
				'psychic'            => 'Psychic/Telepathic Experience',
				'time_anomaly'       => 'Time Anomaly',
				'other'              => 'Other',
			),
			
			// Form validation.
			'require_phone'                  => false,
			'require_address'                => false,
			'min_description_length'         => 50,
			'enable_recaptcha'               => false,
			'recaptcha_site_key'             => '',
			'recaptcha_secret_key'           => '',
			'map_provider'                   => 'google', // google, osm
			'google_maps_api_key'            => '',
			'locationiq_api_key'             => '',
			'weatherapi_api_key'             => '',
			'freeastroapi_api_key'           => '',
			'redaction_keywords'             => '', // Comma-separated list
			'redact_witness_names'           => true,
			'redact_investigator_names'      => false,
			'redaction_placeholder'          => '[REDACTED]',
			'delete_data_on_uninstall'       => false,
			'allowed_file_types'             => array( 'jpg', 'jpeg', 'png', 'gif', 'mp3', 'wav', 'ogg', 'mp4', 'avi', 'mov', 'pdf', 'doc', 'docx', 'txt', 'csv' ),
			'max_upload_size'                => 10485760, // 10MB
			'enable_geolocation'             => true,
			'enable_moon_phase'              => true,
			'units'                          => 'metric', // metric, imperial
		);
	}

	/**
	 * Get a specific setting
	 *
	 * @since    1.0.0
	 * @param    string    $key      Setting key.
	 * @param    mixed     $default  Default value if not found.
	 * @return   mixed               Setting value.
	 */
	public static function get_setting( $key, $default = null ) {
		$settings = self::get_settings();
		
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Update settings
	 *
	 * @since    1.0.0
	 * @param    array    $new_settings    New settings to save.
	 * @return   bool                      True on success.
	 */
	public static function update_settings( $new_settings ) {
		$current_settings = self::get_settings();
		$updated_settings = wp_parse_args( $new_settings, $current_settings );
		
		// Sanitize settings before saving.
		$updated_settings = self::sanitize_settings( $updated_settings );
		
		return update_option( self::OPTION_NAME, $updated_settings );
	}

	/**
	 * Update a specific setting
	 *
	 * @since    1.0.0
	 * @param    string    $key      Setting key.
	 * @param    mixed     $value    Setting value.
	 * @return   bool                True on success.
	 */
	public static function update_setting( $key, $value ) {
		$settings = self::get_settings();
		$settings[ $key ] = $value;
		
		return self::update_settings( $settings );
	}

	/**
	 * Sanitize settings
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Settings to sanitize.
	 * @return   array                 Sanitized settings.
	 */
	private static function sanitize_settings( $settings ) {
		$sanitized = array();

		// Boolean settings.
		$bool_keys = array(
			'witness_form_enabled',
			'witness_account_creation',
			'witness_account_auto_approve',
			'require_privacy_acceptance',
			'notify_admin_new_submission',
			'notify_witness_confirmation',
			'require_phone',
			'require_address',
			'enable_recaptcha',
			'redact_witness_names',
			'redact_investigator_names',
			'delete_data_on_uninstall',
			'allow_public_submissions',
			'moderate_submissions',
			'require_client_consent',
			'enable_geolocation',
			'enable_moon_phase',
		);

		foreach ( $bool_keys as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$sanitized[ $key ] = (bool) $settings[ $key ];
			}
		}

		// Text settings.
		if ( isset( $settings['privacy_policy_text'] ) ) {
			$sanitized['privacy_policy_text'] = wp_kses_post( $settings['privacy_policy_text'] );
		}

		if ( isset( $settings['witness_success_message'] ) ) {
			$sanitized['witness_success_message'] = sanitize_textarea_field( $settings['witness_success_message'] );
		}

		if ( isset( $settings['case_number_format'] ) ) {
			$sanitized['case_number_format'] = sanitize_text_field( $settings['case_number_format'] );
		}

		if ( isset( $settings['default_case_status'] ) ) {
			$sanitized['default_case_status'] = sanitize_text_field( $settings['default_case_status'] );
		}

		if ( isset( $settings['redaction_keywords'] ) ) {
			$sanitized['redaction_keywords'] = sanitize_textarea_field( $settings['redaction_keywords'] );
		}

		if ( isset( $settings['redaction_placeholder'] ) ) {
			$sanitized['redaction_placeholder'] = sanitize_text_field( $settings['redaction_placeholder'] );
		}

		// Email setting.
		if ( isset( $settings['admin_notification_email'] ) ) {
			$sanitized['admin_notification_email'] = sanitize_email( $settings['admin_notification_email'] );
		}

		// Consent default.
		if ( isset( $settings['consent_default'] ) ) {
			$allowed = array( 'none', 'private', 'anonymize', 'publish' );
			$sanitized['consent_default'] = in_array( $settings['consent_default'], $allowed, true ) 
				? $settings['consent_default'] 
				: 'none';
		}

		// Consent options.
		if ( isset( $settings['consent_options_enabled'] ) && is_array( $settings['consent_options_enabled'] ) ) {
			$sanitized['consent_options_enabled'] = array_map( 'boolval', $settings['consent_options_enabled'] );
		}

		// Phenomena types.
		if ( isset( $settings['phenomena_types'] ) && is_array( $settings['phenomena_types'] ) ) {
			$sanitized['phenomena_types'] = array_map( 'sanitize_text_field', $settings['phenomena_types'] );
		}

		// Case statuses.
		if ( isset( $settings['case_statuses'] ) && is_array( $settings['case_statuses'] ) ) {
			$sanitized['case_statuses'] = array_map( 'sanitize_text_field', $settings['case_statuses'] );
		} else {
			$sanitized['case_statuses'] = self::get_default_settings()['case_statuses'];
		}

		// Integer settings.
		if ( isset( $settings['min_description_length'] ) ) {
			$sanitized['min_description_length'] = absint( $settings['min_description_length'] );
		}

		if ( isset( $settings['items_per_page'] ) ) {
			$sanitized['items_per_page'] = absint( $settings['items_per_page'] );
		}

		if ( isset( $settings['max_upload_size'] ) ) {
			$sanitized['max_upload_size'] = absint( $settings['max_upload_size'] );
		}

		// Array settings.
		if ( isset( $settings['allowed_file_types'] ) && is_array( $settings['allowed_file_types'] ) ) {
			$sanitized['allowed_file_types'] = array_map( 'sanitize_key', $settings['allowed_file_types'] );
		}

		// ReCAPTCHA keys.
		if ( isset( $settings['recaptcha_site_key'] ) ) {
			$sanitized['recaptcha_site_key'] = sanitize_text_field( $settings['recaptcha_site_key'] );
		}
		if ( isset( $settings['recaptcha_secret_key'] ) ) {
			$sanitized['recaptcha_secret_key'] = sanitize_text_field( $settings['recaptcha_secret_key'] );
		}

		if ( isset( $settings['google_maps_api_key'] ) ) {
			$sanitized['google_maps_api_key'] = sanitize_text_field( $settings['google_maps_api_key'] );
		}
		if ( isset( $settings['map_provider'] ) ) {
			$sanitized['map_provider'] = in_array( $settings['map_provider'], array( 'google', 'osm' ), true ) ? $settings['map_provider'] : 'google';
		}
		if ( isset( $settings['units'] ) ) {
			$sanitized['units'] = in_array( $settings['units'], array( 'metric', 'imperial' ), true ) ? $settings['units'] : 'metric';
		}
		if ( isset( $settings['locationiq_api_key'] ) ) {
			$sanitized['locationiq_api_key'] = sanitize_text_field( $settings['locationiq_api_key'] );
		}
		if ( isset( $settings['weatherapi_api_key'] ) ) {
			$sanitized['weatherapi_api_key'] = sanitize_text_field( $settings['weatherapi_api_key'] );
		}
		if ( isset( $settings['freeastroapi_api_key'] ) ) {
			$sanitized['freeastroapi_api_key'] = sanitize_text_field( $settings['freeastroapi_api_key'] );
		}

		return $sanitized;
	}

	/**
	 * Reset settings to defaults
	 *
	 * @since    1.0.0
	 * @return   bool    True on success.
	 */
	public static function reset_settings() {
		return update_option( self::OPTION_NAME, self::get_default_settings() );
	}

	/**
	 * Get enabled phenomena types
	 *
	 * @since    1.0.0
	 * @return   array    Array of phenomena types.
	 */
	public static function get_phenomena_types() {
		return self::get_setting( 'phenomena_types', self::get_default_settings()['phenomena_types'] );
	}

	/**
	 * Get enabled consent options
	 *
	 * @since    1.0.0
	 * @return   array    Array of enabled consent options.
	 */
	public static function get_enabled_consent_options() {
		$all_options = array(
			'private'    => __( 'Keep my report private', 'wp-paradb' ),
			'anonymize'  => __( 'Anonymize my report for the public', 'wp-paradb' ),
			'publish'    => __( 'Publish in full (granted)', 'wp-paradb' ),
		);

		$enabled = self::get_setting( 'consent_options_enabled', array(
			'private'    => true,
			'anonymize'  => true,
			'publish'    => true,
		) );

		$enabled_options = array();
		foreach ( $all_options as $key => $label ) {
			if ( ! empty( $enabled[ $key ] ) ) {
				$enabled_options[ $key ] = $label;
			}
		}

		return $enabled_options;
	}

	/**
	 * Get default consent option
	 *
	 * @since    1.0.0
	 * @return   string    Default consent option.
	 */
	public static function get_default_consent() {
		return self::get_setting( 'consent_default', 'none' );
	}
}