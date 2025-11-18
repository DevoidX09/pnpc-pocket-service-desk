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

// Prevent direct access.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Current plugin version.
 */
if (! defined('PNPC_PSD_VERSION')) {
	define('PNPC_PSD_VERSION', '1.0.0');
}

/**
 * Plugin directory path.
 */
if (! defined('PNPC_PSD_PLUGIN_DIR')) {
	define('PNPC_PSD_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

/**
 * Plugin directory URL.
 */
if (! defined('PNPC_PSD_PLUGIN_URL')) {
	define('PNPC_PSD_PLUGIN_URL', plugin_dir_url(__FILE__));
}

/**
 * Plugin base name.
 */
if (! defined('PNPC_PSD_PLUGIN_BASENAME')) {
	define('PNPC_PSD_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

/**
 * Load translations on init (prevents "load_textdomain_just_in_time" notices).
 */
add_action(
	'init',
	function () {
		load_plugin_textdomain('pnpc-pocket-service-desk', false, dirname(plugin_basename(__FILE__)) . '/languages');
	},
	5
);

/**
 * Activation routine.
 */
function activate_pnpc_pocket_service_desk()
{
	$activator = PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-activator.php';
	if (file_exists($activator)) {
		require_once $activator;
		if (class_exists('PNPC_PSD_Activator') && method_exists('PNPC_PSD_Activator', 'activate')) {
			PNPC_PSD_Activator::activate();
			return;
		}
	}
	// Log if activator not available
	error_log('pnpc-activate: activator file missing or class not found: ' . $activator);
}
register_activation_hook(__FILE__, 'activate_pnpc_pocket_service_desk');

/**
 * Deactivation routine.
 */
function deactivate_pnpc_pocket_service_desk()
{
	$deactivator = PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-deactivator.php';
	if (file_exists($deactivator)) {
		require_once $deactivator;
		if (class_exists('PNPC_PSD_Deactivator') && method_exists('PNPC_PSD_Deactivator', 'deactivate')) {
			PNPC_PSD_Deactivator::deactivate();
			return;
		}
	}
	// Log if deactivator not available
	error_log('pnpc-deactivate: deactivator file missing or class not found: ' . $deactivator);
}
register_deactivation_hook(__FILE__, 'deactivate_pnpc_pocket_service_desk');

/**
 * Defensive bootstrap: require core files if present and avoid fatals.
 * Logs missing files so issues are visible without white-screens.
 */

// Core files that should exist (excluding ticket model which we handle separately)
$pnpc_core_files = array(
	PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd.php',
	PNPC_PSD_PLUGIN_DIR . 'public/class-pnpc-psd-public.php',
);

// Require core files if available, otherwise log.
foreach ($pnpc_core_files as $file) {
	if (file_exists($file)) {
		require_once $file;
	} else {
		error_log('pnpc-bootstrap: missing file ' . $file);
	}
}

/**
 * Load Ticket model in a backwards-compatible way:
 * prefer the actual current path "includes/class-pnpc-psd-ticket.php" but
 * fall back to legacy "includes/models/class-pnpc-psd-ticket.php" if present.
 */
$pnpc_ticket_paths = array(
	PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-ticket.php',        // current path
	PNPC_PSD_PLUGIN_DIR . 'includes/models/class-pnpc-psd-ticket.php', // legacy path
);

$pnpc_ticket_loaded = false;
foreach ($pnpc_ticket_paths as $ticket_file) {
	if (file_exists($ticket_file)) {
		require_once $ticket_file;
		$pnpc_ticket_loaded = true;
		break;
	}
}
if (! $pnpc_ticket_loaded) {
	// Log a single diagnostic pointing to the current expected file to reduce noise.
	error_log('pnpc-bootstrap: missing file ' . $pnpc_ticket_paths[0]);
}

/**
 * Instantiate and run plugin if the core class is available.
 * If not available, show a friendly admin notice instead of causing a fatal error.
 */
if (class_exists('PNPC_PSD')) {

	/**
	 * Begins execution of the plugin.
	 *
	 * @since 1.0.0
	 */
	function run_pnpc_pocket_service_desk()
	{
		$plugin = new PNPC_PSD('pnpc-pocket-service-desk', PNPC_PSD_VERSION);
		if (method_exists($plugin, 'run')) {
			$plugin->run();
		} else {
			error_log('pnpc-bootstrap: PNPC_PSD exists but run() method missing.');
		}
	}

	run_pnpc_pocket_service_desk();
} else {
	// Friendly admin notice and log entry; plugin will be inactive until files restored.
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__('PNPC Pocket Service Desk: some core files are missing. Plugin is partially disabled until files are restored.', 'pnpc-pocket-service-desk');
			echo '</p></div>';
		}
	);
	error_log('pnpc-bootstrap: core class PNPC_PSD not found; plugin not initialized.');
}
