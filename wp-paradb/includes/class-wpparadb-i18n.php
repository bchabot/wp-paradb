<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             0.0.0
 * @package           wp-paradb
 * @subpackage        wp-paradb/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link              https://github.com/bchabot/wp-paradb
 * @since             0.0.0
 * @package           wp-paradb
 * @subpackage        wp-paradb/includes
 * @author     Brian Chabot <bchabot@gmail.com>
 */
class wpparadb_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wpparadb',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
