<?php
/**
 * Audit log functionality.
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PNPC PSD Audit Log.
 *
 * @since 1.1.1.4
 */
class PNPC_PSD_Audit_Log {

	/**
	 * Insert an audit log row.
	 *
	 * @param int         $ticket_id Ticket ID (optional).
	 * @param string      $action    Action key.
	 * @param array|mixed $context   Context payload (will be JSON encoded).
	 * @param int|null    $actor_id  Actor user ID (defaults to current user).
	 * @return bool
	 */
	public static function log( $ticket_id, $action, $context = array(), $actor_id = null ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pnpc_psd_audit_log';

		$ticket_id = $ticket_id ? absint( $ticket_id ) : null;
		$actor_id  = null === $actor_id ? get_current_user_id() : absint( $actor_id );
		$action    = sanitize_key( (string) $action );

		if ( empty( $action ) ) {
			return false;
		}

		// Normalize context.
		if ( is_string( $context ) ) {
			$ctx = array( 'message' => $context );
		} elseif ( is_array( $context ) ) {
			$ctx = $context;
		} else {
			$ctx = array( 'value' => $context );
		}

		$payload = wp_json_encode( $ctx );
		if ( false === $payload ) {
			$payload = '';
		}

		$data = array(
			'ticket_id' => $ticket_id ? $ticket_id : null,
			'actor_id'  => $actor_id ? $actor_id : null,
			'action'    => $action,
			'context'   => $payload,
			'created_at'=> current_time( 'mysql', true ),
		);

		$formats = array( '%d', '%d', '%s', '%s', '%s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$ok = $wpdb->insert( $table, $data, $formats );

		if ( false === $ok ) {
			return false;
		}

		// Retention cap: keep the most recent N rows.
		$cap = function_exists( 'pnpc_psd_get_audit_log_cap' ) ? (int) pnpc_psd_get_audit_log_cap() : 250;
		if ( $cap > 0 ) {
			self::enforce_retention_cap( $cap );
		}

		return true;
	}

	/**
	 * Enforce a max row count for the audit log.
	 *
	 * @param int $cap Maximum number of rows to retain.
	 * @return void
	 */
	private static function enforce_retention_cap( $cap ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pnpc_psd_audit_log';
		$cap   = absint( $cap );
		if ( $cap < 1 ) {
			return;
		}

		// MySQL requires a derived table when deleting with a subquery on the same table.
		// Use prepared statement for security compliance.
		$sql = $wpdb->prepare(
			"DELETE FROM {$table} WHERE id NOT IN (SELECT id FROM (SELECT id FROM {$table} ORDER BY id DESC LIMIT %d) t)",
			$cap
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( $sql );
	}

	/**
	 * Get audit log rows for a ticket.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @param int $limit     Max rows.
	 * @return array
	 */
	public static function get_by_ticket( $ticket_id, $limit = 50 ) {
		global $wpdb;
		$table    = $wpdb->prefix . 'pnpc_psd_audit_log';
		$ticket_id = absint( $ticket_id );
		$limit    = max( 1, absint( $limit ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE ticket_id = %d ORDER BY id DESC LIMIT %d",
				$ticket_id,
				$limit
			)
		);
		return is_array( $rows ) ? $rows : array();
	}
}
