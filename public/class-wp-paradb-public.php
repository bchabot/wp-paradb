<?php
/**
 * The public-facing functionality of the plugin.
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
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/public
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of the plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Register shortcodes.
		add_shortcode( 'paradb_cases', array( $this, 'cases_shortcode' ) );
		add_shortcode( 'paradb_single_case', array( $this, 'single_case_shortcode' ) );
		add_shortcode( 'paradb_reports', array( $this, 'reports_shortcode' ) );
		add_shortcode( 'paradb_single_report', array( $this, 'single_report_shortcode' ) );

		// Handle witness form submission.
		add_action( 'init', array( $this, 'handle_witness_submission' ) );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/wp-paradb-public.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$options = get_option( 'wp_paradb_options', array() );
		$provider = isset( $options['map_provider'] ) ? $options['map_provider'] : 'google';
		$api_key = isset( $options['google_maps_api_key'] ) ? $options['google_maps_api_key'] : '';

		if ( 'google' === $provider && ! empty( $api_key ) ) {
			wp_enqueue_script(
				'google-maps',
				'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=places',
				array(),
				null,
				true
			);
		} elseif ( 'osm' === $provider ) {
			wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
			wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
		}

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/wp-paradb-public.js',
			array( 'jquery' ),
			$this->version,
			true // Load in footer for better performance.
		);

		wp_localize_script( $this->plugin_name, 'paradb_public', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );

		// Pass provider settings to JS
		wp_localize_script( $this->plugin_name, 'paradb_maps', array(
			'provider' => $provider,
			'locationiq_key' => isset( $options['locationiq_api_key'] ) ? $options['locationiq_api_key'] : ''
		) );
	}

	/**
	 * Cases listing shortcode.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            HTML output.
	 */
	public function cases_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'   => 10,
				'orderby' => 'date_created',
				'order'   => 'DESC',
			),
			$atts,
			'paradb_cases'
		);

		ob_start();
		include WP_PARADB_PLUGIN_DIR . 'public/partials/wp-paradb-public-cases.php';
		return ob_get_clean();
	}

	/**
	 * Single case display shortcode.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            HTML output.
	 */
	public function single_case_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'paradb_single_case'
		);

		ob_start();
		include WP_PARADB_PLUGIN_DIR . 'public/partials/wp-paradb-public-case-single.php';
		return ob_get_clean();
	}

	/**
	 * Reports listing shortcode.
	 *
	 * @since    1.6.0
	 */
	public function reports_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'   => 10,
				'orderby' => 'report_date',
				'order'   => 'DESC',
				'case_id' => 0,
			),
			$atts,
			'paradb_reports'
		);

		ob_start();
		include WP_PARADB_PLUGIN_DIR . 'public/partials/wp-paradb-public-reports.php';
		return ob_get_clean();
	}

	/**
	 * Single report display shortcode.
	 *
	 * @since    1.6.0
	 */
	public function single_report_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'paradb_single_report'
		);

		ob_start();
		include WP_PARADB_PLUGIN_DIR . 'public/partials/wp-paradb-public-report-single.php';
		return ob_get_clean();
	}

	/**
	 * Handle witness form submission.
	 *
	 * @since    1.0.0
	 */
	public function handle_witness_submission() {
		if ( ! isset( $_POST['submit_witness_account'] ) ) {
			return;
		}

		if ( ! isset( $_POST['witness_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['witness_nonce'] ) ), 'submit_witness_account' ) ) {
			return;
		}

		require_once WP_PARADB_PLUGIN_DIR . 'includes/class-wp-paradb-witness-handler.php';

		$data = array(
			'account_name'          => isset( $_POST['account_name'] ) ? sanitize_text_field( wp_unslash( $_POST['account_name'] ) ) : '',
			'account_email'         => isset( $_POST['account_email'] ) ? sanitize_email( wp_unslash( $_POST['account_email'] ) ) : '',
			'account_phone'         => isset( $_POST['account_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['account_phone'] ) ) : '',
			'incident_date'        => isset( $_POST['incident_date'] ) ? sanitize_text_field( wp_unslash( $_POST['incident_date'] ) ) : '',
			'incident_location'    => isset( $_POST['incident_location'] ) ? sanitize_text_field( wp_unslash( $_POST['incident_location'] ) ) : '',
			'incident_description' => isset( $_POST['incident_description'] ) ? wp_kses_post( wp_unslash( $_POST['incident_description'] ) ) : '',
			'phenomena_types'       => isset( $_POST['phenomena_types'] ) && is_array( $_POST['phenomena_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['phenomena_types'] ) ) : array(),
		);

		$result = WP_ParaDB_Witness_Handler::create_witness_account( $data );

		if ( is_wp_error( $result ) ) {
			set_transient( 'paradb_witness_error', $result->get_error_message(), 30 );
		} else {
			set_transient( 'paradb_witness_success', __( 'Thank you for your submission. Your account has been received and will be reviewed.', 'wp-paradb' ), 30 );
		}

		wp_safe_redirect( wp_get_referer() );
		exit;
	}
}