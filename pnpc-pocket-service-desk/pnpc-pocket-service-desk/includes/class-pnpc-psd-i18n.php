<?php
/**
 * Define the internationalization functionality
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */
/**
 * PNPC PSD i18n.
 *
 * @since 1.1.1.4
 */
class PNPC_PSD_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * Translations are automatically loaded by WordPress.org for hosted plugins.
	 * No manual load_plugin_textdomain() call needed since WordPress 4.6+.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		// WordPress.org handles translations automatically since WP 4.6+.
		// load_plugin_textdomain(
		//     'pnpc-pocket-service-desk',
		//     false,
		//     dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		// );
	}
}
