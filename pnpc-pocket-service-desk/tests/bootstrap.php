<?php
/**
 * PHPUnit bootstrap file
 *
 * @package PNPC_Pocket_Service_Desk
 */

// Placeholder bootstrap for CI testing.
// This is a minimal scaffold to allow phpunit to run.
// In a full WordPress test environment, this would load WordPress test library.

define( 'PNPC_PSD_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'PNPC_PSD_PLUGIN_URL', 'http://example.com/wp-content/plugins/pnpc-pocket-service-desk/' );
define( 'PNPC_PSD_VERSION', '1.1.0' );

// Stub ABSPATH if not defined.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}
