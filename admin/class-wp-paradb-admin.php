<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             1.0.0
 * @package           WP_ParaDB
 * @subpackage        WP_ParaDB/admin
 * @author            Brian Chabot <bchabot@gmail.com>
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    WP_ParaDB
 * @subpackage WP_ParaDB/admin
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class WP_ParaDB_Admin {

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
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/wp-paradb-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/wp-paradb-admin.js',
			array( 'jquery' ),
			$this->version,
			true // Load in footer for better performance.
		);
	}

	/**
	 * Check if current user has required capabilities.
	 *
	 * @since    1.0.0
	 * @param    string    $capability    Required capability.
	 * @return   bool                     True if user has capability, false otherwise.
	 */
	public function check_user_capability( $capability = 'manage_options' ) {
		return current_user_can( $capability );
	}

	/**
	 * Verify nonce for security.
	 *
	 * @since    1.0.0
	 * @param    string    $nonce_action    Nonce action.
	 * @param    string    $nonce_name      Nonce field name.
	 * @param    string    $method          Request method ('POST' or 'GET').
	 * @return   bool                       True if nonce is valid, false otherwise.
	 */
	public function verify_nonce( $nonce_action, $nonce_name = '_wpnonce', $method = 'POST' ) {
		$request_data = 'GET' === strtoupper( $method ) ? $_GET : $_POST;

		if ( ! isset( $request_data[ $nonce_name ] ) ) {
			return false;
		}

		return wp_verify_nonce( sanitize_text_field( wp_unslash( $request_data[ $nonce_name ] ) ), $nonce_action );
	}

	/**
	 * Sanitize input data.
	 *
	 * @since    1.0.0
	 * @param    mixed     $input    Input data to sanitize.
	 * @param    string    $type     Type of sanitization (text, email, url, etc.).
	 * @return   mixed               Sanitized data.
	 */
	public function sanitize_input( $input, $type = 'text' ) {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $input );
			case 'url':
				return esc_url_raw( $input );
			case 'textarea':
				return sanitize_textarea_field( $input );
			case 'key':
				return sanitize_key( $input );
			case 'text':
			default:
				return sanitize_text_field( $input );
		}
	}

	/**
	 * Render the relationship management section
	 *
	 * @since    1.3.0
	 */
	public static function render_relationship_section( $object_id, $object_type ) {
		require_once WP_PARADB_PLUGIN_DIR . 'admin/partials/wp-paradb-admin-relationships.php';
	}

}
