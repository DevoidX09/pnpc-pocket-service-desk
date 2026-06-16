<?php
/**
 * Error log functionality.
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PNPC PSD Error Log.
 */
class PNPC_PSD_Error_Log {

	/**
	 * Insert an error log row.
	 *
	 * @param string $type Error type.
	 * @param string $message Error message.
	 * @param array  $context Optional context.
	 * @param string $severity Error severity.
	 * @return int|false
	 */
	public static function log( $type, $message, $context = array(), $severity = 'error' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pnpc_psd_error_log';

		$type     = sanitize_key( $type );
		$severity = sanitize_key( $severity );
		$message  = wp_strip_all_tags( (string) $message );
		$context  = is_array( $context ) ? $context : array( 'value' => $context );

		if ( '' === $type ) {
			$type = 'runtime';
		}

		if ( '' === $message ) {
			$message = __( 'An unspecified Service Desk error was recorded.', 'pnpc-pocket-service-desk' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned custom tables and activation/schema maintenance use WordPress database APIs with sanitized values.
		$inserted = $wpdb->insert(
			$table,
			array(
				'type'       => $type,
				'severity'   => $severity,
				'message'    => $message,
				'context'    => wp_json_encode( self::sanitize_context( $context ) ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return false;
		}

		self::trim();

		return (int) $wpdb->insert_id;
	}

	/**
	 * Sanitize context for storage.
	 *
	 * @param array $context Context.
	 * @return array
	 */
	private static function sanitize_context( $context ) {
		$clean = array();
		foreach ( $context as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}
			if ( is_scalar( $value ) || null === $value ) {
				$clean[ $key ] = sanitize_text_field( (string) $value );
			} elseif ( is_array( $value ) ) {
				$clean[ $key ] = self::sanitize_context( $value );
			} else {
				$clean[ $key ] = sanitize_text_field( wp_json_encode( $value ) );
			}
		}
		return $clean;
	}

	/**
	 * Keep the log within configured retention.
	 *
	 * @return void
	 */
	public static function trim() {
		global $wpdb;

		$table = $wpdb->prefix . 'pnpc_psd_error_log';
		$cap   = function_exists( 'pnpc_psd_get_error_log_cap' ) ? (int) pnpc_psd_get_error_log_cap() : 250;

		if ( $cap <= 0 ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned custom tables and activation/schema maintenance use WordPress database APIs with sanitized values.
		$count = (int) $wpdb->get_var( "SELECT COUNT(1) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		if ( $count <= $cap ) {
			return;
		}

		$delete_count = $count - $cap;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Plugin-owned custom tables and activation/schema maintenance use WordPress database APIs with sanitized values.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} ORDER BY created_at ASC, id ASC LIMIT %d", $delete_count ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Record wp_mail failures.
	 *
	 * @param WP_Error $error Mail failure object.
	 * @return void
	 */
	public static function log_mail_failure( $error ) {
		if ( ! is_wp_error( $error ) ) {
			return;
		}

		self::log(
			'email',
			$error->get_error_message(),
			array(
				'code' => $error->get_error_code(),
				'data' => $error->get_error_data(),
			)
		);
	}

	/**
	 * Record fatal errors involving this plugin path.
	 *
	 * @return void
	 */
	public static function maybe_log_shutdown_error() {
		$error = error_get_last();
		if ( empty( $error ) || empty( $error['type'] ) ) {
			return;
		}

		$fatal_types = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR );
		if ( ! in_array( (int) $error['type'], $fatal_types, true ) ) {
			return;
		}

		$file = isset( $error['file'] ) ? (string) $error['file'] : '';
		if ( false === strpos( $file, wp_normalize_path( PNPC_PSD_PLUGIN_DIR ) ) ) {
			return;
		}

		self::log(
			'runtime',
			isset( $error['message'] ) ? $error['message'] : __( 'Fatal runtime error.', 'pnpc-pocket-service-desk' ),
			array(
				'file' => str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $file ) ),
				'line' => isset( $error['line'] ) ? absint( $error['line'] ) : 0,
				'type' => absint( $error['type'] ),
			),
			'critical'
		);
	}
}
