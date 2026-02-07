<?php
/**
 * Plugin Name:       PNPC Pocket Service Desk
 * Plugin URI:        https://github.com/DevoidX09/pnpc-pocket-service-desk
 * Description:       A WordPress-native service desk plugin for managing customer support tickets.
 * Version:           1.1.5
 * Author:            PNPC
 * Author URI:        https://github.com/DevoidX09
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pnpc-pocket-service-desk
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PNPC_PSD_VERSION' ) ) {
	define( 'PNPC_PSD_VERSION', '1.1.5' );
}

if ( ! defined( 'PNPC_PSD_PLUGIN_DIR' ) ) {
	define( 'PNPC_PSD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'PNPC_PSD_PLUGIN_URL' ) ) {
	define( 'PNPC_PSD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'PNPC_PSD_PLUGIN_BASENAME' ) ) {
	define( 'PNPC_PSD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Translations are automatically loaded by WordPress.org for hosted plugins.
 * No manual load_plugin_textdomain() call needed since WordPress 4.6+.
 */

/**
 * Run database migrations on update (not only on activation).
 *
 * @return void
 */
function pnpc_psd_maybe_run_migrations() {
	$activator_file = PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-activator.php';
	if ( ! file_exists( $activator_file ) ) {
		return;
	}

	require_once $activator_file;

	if ( class_exists( 'PNPC_PSD_Activator' ) && method_exists( 'PNPC_PSD_Activator', 'maybe_upgrade_database' ) ) {
		PNPC_PSD_Activator::maybe_upgrade_database();
	}
}
add_action( 'plugins_loaded', 'pnpc_psd_maybe_run_migrations', 6 );

/**
 * Activation routine.
 *
 * @return void
 */
function activate_pnpc_pocket_service_desk() {
	$activator_file = PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-activator.php';
	if ( ! file_exists( $activator_file ) ) {
		return;
	}

	require_once $activator_file;

	if ( class_exists( 'PNPC_PSD_Activator' ) && method_exists( 'PNPC_PSD_Activator', 'activate' ) ) {
		PNPC_PSD_Activator::activate();
	}
}
register_activation_hook( __FILE__, 'activate_pnpc_pocket_service_desk' );

/**
 * Deactivation routine.
 *
 * @return void
 */
function deactivate_pnpc_pocket_service_desk() {
	$deactivator_file = PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-deactivator.php';
	if ( ! file_exists( $deactivator_file ) ) {
		return;
	}

	require_once $deactivator_file;

	if ( class_exists( 'PNPC_PSD_Deactivator' ) && method_exists( 'PNPC_PSD_Deactivator', 'deactivate' ) ) {
		PNPC_PSD_Deactivator::deactivate();
	}
}
register_deactivation_hook( __FILE__, 'deactivate_pnpc_pocket_service_desk' );

/**
 * Require core plugin files defensively.
 *
 * @return bool True when core classes can be loaded.
 */
function pnpc_psd_require_core_files() {
	$core_files = array(
		PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd.php',
		PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-notifications.php',
		PNPC_PSD_PLUGIN_DIR . 'includes/helpers.php',
		PNPC_PSD_PLUGIN_DIR . 'public/class-pnpc-psd-public.php',
	);

	foreach ( $core_files as $file ) {
		if ( file_exists( $file ) ) {
			require_once $file;
		} else {
			return false;
		}
	}

	// Ticket model (supports legacy path if present).
	$ticket_paths = array(
		PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-ticket.php',
		PNPC_PSD_PLUGIN_DIR . 'includes/models/class-pnpc-psd-ticket.php',
	);

	foreach ( $ticket_paths as $ticket_file ) {
		if ( file_exists( $ticket_file ) ) {
			require_once $ticket_file;
			break;
		}
	}

	return true;
}

/**
 * Boot the plugin (or show an admin notice if core files are missing).
 *
 * @return void
 */
function pnpc_psd_boot() {
	$loaded = pnpc_psd_require_core_files();
	if ( ! $loaded || ! class_exists( 'PNPC_PSD' ) ) {
		add_action(
			'admin_notices',
			static function () {
				echo '<div class="notice notice-warning"><p>';
				echo esc_html__( 'PNPC Pocket Service Desk: some core files are missing. The plugin is partially disabled until files are restored.', 'pnpc-pocket-service-desk' );
				echo '</p></div>';
			}
		);
		return;
	}

	$plugin = new PNPC_PSD( 'pnpc-pocket-service-desk', PNPC_PSD_VERSION );
	if ( method_exists( $plugin, 'run' ) ) {
		$plugin->run();
	}
}

pnpc_psd_boot();
