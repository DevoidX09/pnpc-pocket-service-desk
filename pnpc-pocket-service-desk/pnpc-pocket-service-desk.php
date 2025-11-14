<?php
/**
 * Plugin Name: PNPC Pocket Service Desk
 * Plugin URI: https://github.com/DevoidX09/pnpc-pocket-service-desk
 * Description: A comprehensive service desk plugin for managing customer support tickets with WooCommerce integration.
 * Version: 1.0.0
 * Author: PNPC
 * Author URI: https://github.com/DevoidX09
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pnpc-pocket-service-desk
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package PNPC_Pocket_Service_Desk
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'PNPC_PSD_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'PNPC_PSD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'PNPC_PSD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin base name.
 */
define( 'PNPC_PSD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_pnpc_pocket_service_desk() {
	require_once PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-activator.php';
	PNPC_PSD_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_pnpc_pocket_service_desk() {
	require_once PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-deactivator.php';
	PNPC_PSD_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pnpc_pocket_service_desk' );
register_deactivation_hook( __FILE__, 'deactivate_pnpc_pocket_service_desk' );

/**
 * The core plugin class.
 */
require PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_pnpc_pocket_service_desk() {
	$plugin = new PNPC_PSD();
	$plugin->run();
}

run_pnpc_pocket_service_desk();
