<?php
/**
 * Privacy and Redaction functionality
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.5.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle data redaction and privacy filtering
 *
 * @since      1.5.0
 */
class WP_ParaDB_Privacy {

	/**
	 * Redact sensitive content based on global and local settings
	 *
	 * @since    1.5.0
	 * @param    string    $content    The content to redact.
	 * @param    array     $extra_terms Additional terms to redact.
	 * @return   string                The redacted content.
	 */
	public static function redact( $content, $extra_terms = array() ) {
		if ( empty( $content ) ) {
			return $content;
		}

		// Don't redact for users who can manage settings (Admins/Directors)
		if ( current_user_can( 'paradb_manage_settings' ) ) {
			return $content;
		}

		$options = WP_ParaDB_Settings::get_settings();
		$placeholder = isset( $options['redaction_placeholder'] ) ? $options['redaction_placeholder'] : '[REDACTED]';
		
		$terms_to_redact = array();

		// Add global keywords
		if ( ! empty( $options['redaction_keywords'] ) ) {
			$global_terms = explode( ',', $options['redaction_keywords'] );
			$terms_to_redact = array_merge( $terms_to_redact, array_map( 'trim', $global_terms ) );
		}

		// Add local terms
		if ( ! empty( $extra_terms ) ) {
			$terms_to_redact = array_merge( $terms_to_redact, $extra_terms );
		}

		// Filter out empty terms
		$terms_to_redact = array_unique( array_filter( $terms_to_redact ) );

		if ( empty( $terms_to_redact ) ) {
			return $content;
		}

		// Sort by length descending to redact longest phrases first
		usort( $terms_to_redact, function( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );

		foreach ( $terms_to_redact as $term ) {
			if ( empty( $term ) ) continue;
			// Case-insensitive replacement
			$pattern = '/' . preg_quote( $term, '/' ) . '/i';
			$content = preg_replace( $pattern, $placeholder, $content );
		}

		return $content;
	}

	/**
	 * Get names associated with a case for redaction
	 *
	 * @since    1.5.0
	 */
	public static function get_case_redaction_terms( $case_id ) {
		$terms = array();
		
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-case-handler.php';
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-witness-handler.php';
		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-client-handler.php';

		$options = WP_ParaDB_Settings::get_settings();
		$case = WP_ParaDB_Case_Handler::get_case( $case_id );
		if ( ! $case ) return array();

		// Get Client names
		if ( $case->client_id ) {
			$client = WP_ParaDB_Client_Handler::get_client( $case->client_id );
			if ( $client ) {
				$terms[] = $client->first_name;
				$terms[] = $client->last_name;
				$terms[] = $client->first_name . ' ' . $client->last_name;
				if ( $case->sanitize_front_end ) {
					$terms[] = $client->address;
					$terms[] = $client->email;
					$terms[] = $client->phone;
				}
			}
		}

		// Get Witness names if redaction enabled or case sanitized
		if ( ! empty( $options['redact_witness_names'] ) || $case->sanitize_front_end ) {
			$witnesses = WP_ParaDB_Witness_Handler::get_witness_accounts( array( 'case_id' => $case_id, 'limit' => 100 ) );
			foreach ( $witnesses as $witness ) {
				if ( ! empty( $witness->account_name ) ) {
					$terms[] = $witness->account_name;
					// Split names to redact individual parts
					$parts = explode( ' ', $witness->account_name );
					if ( count( $parts ) > 1 ) {
						$terms = array_merge( $terms, $parts );
					}
				}
				if ( $case->sanitize_front_end ) {
					$terms[] = $witness->account_address;
					$terms[] = $witness->account_email;
					$terms[] = $witness->account_phone;
					$terms[] = $witness->incident_location;
				}
			}
		}

		// Add case location if sanitized
		if ( $case->sanitize_front_end ) {
			$terms[] = $case->location_name;
			$terms[] = $case->location_address;
			// We don't necessarily want to redact city/state unless they are very specific, 
			// but the user said "locations", so let's be safe.
			$terms[] = $case->location_city;
		}

		return array_unique( array_filter( $terms ) );
	}
}
