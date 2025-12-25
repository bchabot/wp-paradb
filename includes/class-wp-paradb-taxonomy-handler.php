<?php
/**
 * Taxonomy management functionality
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
 * Handle taxonomy operations
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Taxonomy_Handler {

	/**
	 * Get default taxonomies
	 *
	 * @since    1.0.0
	 * @return   array    Default taxonomy definitions.
	 */
	public static function get_default_taxonomies() {
		return array(
			'phenomena_types' => array(
				'label'       => __( 'Phenomena Types', 'wp-paradb' ),
				'description' => __( 'Types of paranormal phenomena that can be reported', 'wp-paradb' ),
				'items'       => array(
					'Apparition',
					'Audio Phenomena',
					'Cold Spots',
					'Electronic Voice Phenomena (EVP)',
					'Full Body Apparition',
					'Object Movement',
					'Orbs',
					'Phantom Smells',
					'Physical Contact',
					'Shadow Figures',
					'Temperature Changes',
					'Visual Anomalies',
				),
			),
			'case_statuses' => array(
				'label'       => __( 'Case Statuses', 'wp-paradb' ),
				'description' => __( 'Available case status options', 'wp-paradb' ),
				'items'       => array(
					'open'       => __( 'Open', 'wp-paradb' ),
					'active'     => __( 'Active Investigation', 'wp-paradb' ),
					'reviewing'  => __( 'Under Review', 'wp-paradb' ),
					'closed'     => __( 'Closed', 'wp-paradb' ),
					'archived'   => __( 'Archived', 'wp-paradb' ),
				),
			),
			'case_types' => array(
				'label'       => __( 'Case Types', 'wp-paradb' ),
				'description' => __( 'Types of investigations or cases', 'wp-paradb' ),
				'items'       => array(
					'investigation' => __( 'Investigation', 'wp-paradb' ),
					'research'      => __( 'Research', 'wp-paradb' ),
					'consultation'  => __( 'Consultation', 'wp-paradb' ),
					'experiment'    => __( 'Experiment', 'wp-paradb' ),
				),
			),
			'case_priorities' => array(
				'label'       => __( 'Case Priorities', 'wp-paradb' ),
				'description' => __( 'Priority levels for cases', 'wp-paradb' ),
				'items'       => array(
					'low'    => __( 'Low', 'wp-paradb' ),
					'normal' => __( 'Normal', 'wp-paradb' ),
					'high'   => __( 'High', 'wp-paradb' ),
					'urgent' => __( 'Urgent', 'wp-paradb' ),
				),
			),
			'report_types' => array(
				'label'       => __( 'Report Types', 'wp-paradb' ),
				'description' => __( 'Types of investigation reports', 'wp-paradb' ),
				'items'       => array(
					'investigation' => __( 'Investigation Report', 'wp-paradb' ),
					'initial'       => __( 'Initial Assessment', 'wp-paradb' ),
					'followup'      => __( 'Follow-up Report', 'wp-paradb' ),
					'final'         => __( 'Final Report', 'wp-paradb' ),
					'analysis'      => __( 'Analysis Report', 'wp-paradb' ),
				),
			),
			'evidence_types' => array(
				'label'       => __( 'Evidence Types', 'wp-paradb' ),
				'description' => __( 'Types of evidence files', 'wp-paradb' ),
				'items'       => array(
					'photo'    => __( 'Photograph', 'wp-paradb' ),
					'audio'    => __( 'Audio Recording', 'wp-paradb' ),
					'video'    => __( 'Video Recording', 'wp-paradb' ),
					'document' => __( 'Document', 'wp-paradb' ),
					'data'     => __( 'Sensor Data', 'wp-paradb' ),
					'other'    => __( 'Other', 'wp-paradb' ),
				),
			),
			'equipment_types' => array(
				'label'       => __( 'Equipment Types', 'wp-paradb' ),
				'description' => __( 'Types of investigation equipment', 'wp-paradb' ),
				'items'       => array(
					'EMF Meter',
					'Digital Voice Recorder',
					'Infrared Camera',
					'Digital Camera',
					'Video Camera',
					'Temperature Sensor',
					'Motion Detector',
					'Spirit Box',
					'Laser Grid',
					'Dowsing Rods',
					'Geiger Counter',
					'White Noise Generator',
				),
			),
			'moon_phases' => array(
				'label'       => __( 'Moon Phases', 'wp-paradb' ),
				'description' => __( 'Lunar phases for environmental tracking', 'wp-paradb' ),
				'items'       => array(
					'new'             => __( 'New Moon', 'wp-paradb' ),
					'waxing_crescent' => __( 'Waxing Crescent', 'wp-paradb' ),
					'first_quarter'   => __( 'First Quarter', 'wp-paradb' ),
					'waxing_gibbous'  => __( 'Waxing Gibbous', 'wp-paradb' ),
					'full'            => __( 'Full Moon', 'wp-paradb' ),
					'waning_gibbous'  => __( 'Waning Gibbous', 'wp-paradb' ),
					'last_quarter'    => __( 'Last Quarter', 'wp-paradb' ),
					'waning_crescent' => __( 'Waning Crescent', 'wp-paradb' ),
				),
			),
		);
	}

	/**
	 * Get a specific taxonomy
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @return   array|null                 Taxonomy data or null if not found.
	 */
	public static function get_taxonomy( $taxonomy_key ) {
		$taxonomies = get_option( 'wp_paradb_taxonomies', array() );
		
		if ( empty( $taxonomies ) ) {
			$taxonomies = self::get_default_taxonomies();
		}
		
		return isset( $taxonomies[ $taxonomy_key ] ) ? $taxonomies[ $taxonomy_key ] : null;
	}

	/**
	 * Get taxonomy items
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @return   array                      Array of taxonomy items.
	 */
	public static function get_taxonomy_items( $taxonomy_key ) {
		$taxonomy = self::get_taxonomy( $taxonomy_key );
		return $taxonomy ? $taxonomy['items'] : array();
	}

	/**
	 * Update a taxonomy
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @param    array     $data            Taxonomy data.
	 * @return   bool                       Success status.
	 */
	public static function update_taxonomy( $taxonomy_key, $data ) {
		$taxonomies = get_option( 'wp_paradb_taxonomies', array() );
		
		if ( empty( $taxonomies ) ) {
			$taxonomies = self::get_default_taxonomies();
		}
		
		$taxonomies[ $taxonomy_key ] = array(
			'label'       => isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : '',
			'description' => isset( $data['description'] ) ? sanitize_text_field( $data['description'] ) : '',
			'items'       => isset( $data['items'] ) ? $data['items'] : array(),
		);
		
		return update_option( 'wp_paradb_taxonomies', $taxonomies );
	}

	/**
	 * Add item to taxonomy
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @param    mixed     $item            Item to add (string or key=>value).
	 * @return   bool                       Success status.
	 */
	public static function add_taxonomy_item( $taxonomy_key, $item ) {
		$taxonomy = self::get_taxonomy( $taxonomy_key );
		
		if ( ! $taxonomy ) {
			return false;
		}
		
		if ( is_array( $item ) ) {
			$taxonomy['items'] = array_merge( $taxonomy['items'], $item );
		} else {
			$taxonomy['items'][] = $item;
		}
		
		return self::update_taxonomy( $taxonomy_key, $taxonomy );
	}

	/**
	 * Remove item from taxonomy
	 *
	 * @since    1.0.0
	 * @param    string    $taxonomy_key    The taxonomy key.
	 * @param    mixed     $item_key        Item key or value to remove.
	 * @return   bool                       Success status.
	 */
	public static function remove_taxonomy_item( $taxonomy_key, $item_key )