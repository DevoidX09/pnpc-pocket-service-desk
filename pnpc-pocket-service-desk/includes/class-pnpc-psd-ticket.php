<?php

/**
 * Ticket management functionality
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * PNPC PSD Ticket.
 *
 * @since 1.1.1.4
 */
class PNPC_PSD_Ticket
{

	/**
	 * Create a new ticket.
	 *
	 * @since 1.0.0
	 * @param array $data Ticket data.
	 * @return int|false Ticket ID on success, false on failure.
	 */
	public static function create($data)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		$defaults = array(
			'user_id'     => get_current_user_id(),
			'subject'     => '',
			'description' => '',
			'status'      => 'open',
			'priority'    => 'normal',
			'assigned_to' => null,
		);

		$data = wp_parse_args($data, $defaults);

		// Validate required fields.
		if (empty($data['subject']) || empty($data['description'])) {
			return false;
		}

		// Apply default agent assignment if no assignee provided.
		if (empty($data['assigned_to'])) {
			$default_agent_id = absint(get_option('pnpc_psd_default_agent_user_id', 0));
			if ($default_agent_id > 0) {
				$staff_user = get_userdata($default_agent_id);
				if ($staff_user && ! empty($staff_user->ID) && ! empty($staff_user->roles)) {
					$allowed_roles = ( ( function_exists( 'pnpc_psd_enable_manager_role' ) && pnpc_psd_enable_manager_role() ) ? array( 'administrator', 'pnpc_psd_manager', 'pnpc_psd_agent' ) : array( 'administrator', 'pnpc_psd_agent' ) );
					$has_allowed_role = false;
					foreach ((array) $staff_user->roles as $r) {
						if (in_array((string) $r, $allowed_roles, true)) {
							$has_allowed_role = true;
							break;
						}
					}
					if ($has_allowed_role) {
						$data['assigned_to'] = absint($staff_user->ID);
					}
				}
			}
		}

		// Generate unique ticket number.
		$ticket_number = '';
		$attempts = 0;

		// Use helper to store UTC datetime explicitly
		$created_at_utc = function_exists('pnpc_psd_get_utc_mysql_datetime') ? pnpc_psd_get_utc_mysql_datetime() : current_time('mysql', true);

		// Generate a unique ticket number and insert. If a collision occurs, retry a few times.
		$max_attempts = 3;
		do {
			$attempts++;
			$ticket_number = self::generate_ticket_number();

			$insert_data = array(
				'ticket_number' => $ticket_number,
				'user_id'       => absint($data['user_id']),
				'subject'       => sanitize_text_field($data['subject']),
				'description'   => wp_kses_post($data['description']),
				'status'        => sanitize_text_field($data['status']),
				'priority'      => sanitize_text_field($data['priority']),
				'assigned_to'   => ! empty($data['assigned_to']) ? absint($data['assigned_to']) : null,
				'created_at'    => $created_at_utc,
			);

			// Add created_by_staff if provided
			if (isset($data['created_by_staff']) && ! empty($data['created_by_staff'])) {
				$insert_data['created_by_staff'] = absint($data['created_by_staff']);
			}

			// Format array must match the insert order above.
			$format = array('%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s');
			
			// Add format for created_by_staff if present
			if (isset($insert_data['created_by_staff'])) {
				$format[] = '%d';
			}

			$result = $wpdb->insert(
				$table_name,
				$insert_data,
				$format
			);

			if (false !== $result) {
				$ticket_id = (int) $wpdb->insert_id;
				self::update_activity_on_create( $ticket_id );
				self::send_ticket_created_notification($ticket_id);

				if ( class_exists( 'PNPC_PSD_Audit_Log' ) ) {
					PNPC_PSD_Audit_Log::log( $ticket_id, 'ticket_created', array(
						'user_id' => absint( $insert_data['user_id'] ),
						'assigned_to' => ! empty( $insert_data['assigned_to'] ) ? absint( $insert_data['assigned_to'] ) : 0,
						'status' => (string) $insert_data['status'],
						'priority' => (string) $insert_data['priority'],
					) );
				}

				return $ticket_id;
			}

			$last_error = isset($wpdb->last_error) ? (string) $wpdb->last_error : '';
			$is_duplicate = (false !== stripos($last_error, 'Duplicate entry')) && (false !== stripos($last_error, 'ticket_number'));
		} while ($attempts < $max_attempts && $is_duplicate);

		// Diagnostic logging for failures (only when debug enabled)
		if (defined('WP_DEBUG') && WP_DEBUG) {
			if (function_exists('pnpc_psd_debug_log')) {
				pnpc_psd_debug_log('ticket_create_failed', array(
					'insert_data'   => $insert_data,
					'wpdb_error'    => isset($wpdb->last_error) ? $wpdb->last_error : '',
					'wpdb_last_query' => isset($wpdb->last_query) ? $wpdb->last_query : '',
				));
			} else {
			}
		}

		return false;
	}

	/**
	 * Get a ticket by ID.
	 *
	 * @since 1.0.0
	 * @param int $ticket_id Ticket ID.
	 * @return object|null Ticket object or null if not found.
	 */
	public static function get($ticket_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id  = absint($ticket_id);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$ticket_id
			)
		);

		return $ticket;
	}

	/**
	 * Update a ticket row.
	 *
	 * This is a lightweight wrapper around $wpdb->update() with strict field allowlisting.
	 * It is intentionally conservative to avoid accidental schema writes.
	 *
	 * @since 1.4.2
	 * @param int   $ticket_id Ticket ID.
	 * @param array $data      Associative array of columns to update.
	 * @return bool True when a row is updated (or values are identical), false on error.
	 */
	public static function update( $ticket_id, $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id  = absint( $ticket_id );
		if ( ! $ticket_id || ! is_array( $data ) || empty( $data ) ) {
			return false;
		}

		$old_ticket = self::get( $ticket_id );
		$old_vals = is_object( $old_ticket ) ? (array) $old_ticket : array();

		// Allowlist: only columns that should ever be changed via update().
		$allowed = array(
			'status'      => '%s',
			'priority'    => '%s',
			'assigned_to' => '%d',
			'subject'     => '%s',
		);

		$update_data   = array();
		$update_format = array();
		foreach ( $data as $key => $value ) {
			$key = sanitize_key( $key );
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}
			if ( 'assigned_to' === $key ) {
				$update_data[ $key ] = ( 0 === absint( $value ) ) ? null : absint( $value );
				$update_format[] = '%d';
				continue;
			}
			$update_data[ $key ] = is_string( $value ) ? sanitize_text_field( wp_unslash( $value ) ) : $value;
			$update_format[] = $allowed[ $key ];
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		// Always bump updated_at for visibility.
		$update_data['updated_at'] = function_exists( 'pnpc_psd_get_utc_mysql_datetime' ) ? pnpc_psd_get_utc_mysql_datetime() : current_time( 'mysql', true );
		$update_format[] = '%s';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$table_name,
			$update_data,
			array( 'id' => $ticket_id ),
			$update_format,
			array( '%d' )
		);

		// Audit log for material changes (best-effort).
		if ( false !== $result && class_exists( 'PNPC_PSD_Audit_Log' ) ) {
			$actor = get_current_user_id();
			$changed = array();
			foreach ( array('status','priority','assigned_to','subject') as $k ) {
				if ( array_key_exists( $k, $update_data ) ) {
					$before = isset( $old_vals[ $k ] ) ? $old_vals[ $k ] : null;
					$after  = $update_data[ $k ];
					if ( 'assigned_to' === $k ) {
						$before = empty( $before ) ? null : (int) $before;
						$after  = empty( $after ) ? null : (int) $after;
					}
					if ( $before != $after ) {
						$changed[ $k ] = array( 'from' => $before, 'to' => $after );
					}
				}
			}
			if ( ! empty( $changed ) ) {
				PNPC_PSD_Audit_Log::log( $ticket_id, 'ticket_updated', array( 'actor_id' => $actor, 'changes' => $changed ) );
			}
		}

		// $wpdb->update returns 0 when data is identical; treat as success.
		return false !== $result;
	}

	/**
	 * Get tickets for a user.
	 *
	 * @since 1.0.0
	 * @param int   $user_id User ID.
	 * @param array $args Query arguments.
	 * @return array Array of ticket objects.
	 */
	public static function get_by_user($user_id, $args = array())
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$user_id    = absint($user_id);

		$defaults = array(
			'status'          => '',
			'exclude_statuses' => array(),
			'orderby'         => 'created_at',
			'order'           => 'DESC',
			'limit'           => 50,
			'offset'          => 0,
			'include_trashed' => false,
			'include_archived' => false,
		);

		$args = wp_parse_args($args, $defaults);

		$where = $wpdb->prepare('WHERE user_id = %d', $user_id);

		// Exclude trashed tickets by default.
		if (! $args['include_trashed']) {
			$where .= ' AND deleted_at IS NULL';
		}

		// Exclude archived tickets by default.
		if ( empty( $args['include_archived'] ) ) {
			$where .= " AND archived_at IS NULL AND status <> 'archived'";
		}

		if (! empty($args['status'])) {
			$where .= $wpdb->prepare(' AND status = %s', $args['status']);
		}

		// Exclude statuses when requested (e.g., hide Closed on the My Tickets tab).
		if (! empty($args['exclude_statuses']) && is_array($args['exclude_statuses'])) {
			$exclude = array_values(array_filter(array_map('sanitize_key', $args['exclude_statuses'])));
			if (! empty($exclude)) {
				$placeholders = implode(',', array_fill(0, count($exclude), '%s'));
				$query = " AND status NOT IN ($placeholders)";
				// wpdb::prepare expects a varargs list; use call_user_func_array for dynamic placeholder counts.
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$where .= call_user_func_array(array($wpdb, 'prepare'), array_merge(array($query), $exclude));
			}
		}

		// Whitelist allowed orderby columns.
		$allowed_orderby = array('id', 'ticket_number', 'created_at', 'updated_at', 'status', 'priority');
		if (! in_array($args['orderby'], $allowed_orderby, true)) {
			$args['orderby'] = 'created_at';
		}

		// Validate order direction.
		$args['order'] = strtoupper($args['order']);
		if (! in_array($args['order'], array('ASC', 'DESC'), true)) {
			$args['order'] = 'DESC';
		}

		$orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
		if (false === $orderby) {
			$orderby = 'created_at DESC';
		}

		$limit   = absint($args['limit']);
		$offset  = absint($args['offset']);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$tickets = $wpdb->get_results(
			"SELECT * FROM {$table_name} {$where} ORDER BY {$orderby} LIMIT {$limit} OFFSET {$offset}"
		);

		return $tickets;
	}

	/**
	 * Count tickets for a user.
	 *
	 * Uses the same filters as get_by_user() (status, exclude_statuses, include_trashed, include_archived).
	 *
	 * @since 1.1.1
	 * @param int   $user_id User ID.
	 * @param array $args Query arguments.
	 * @return int Ticket count.
	 */
	public static function get_user_count( $user_id, $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$user_id    = absint( $user_id );
		if ( ! $user_id ) {
			return 0;
		}

		$defaults = array(
			'status'           => '',
			'exclude_statuses' => array(),
			'include_trashed'  => false,
			'include_archived' => false,
		);
		$args = wp_parse_args( $args, $defaults );

		$where = $wpdb->prepare( 'WHERE user_id = %d', $user_id );

		if ( empty( $args['include_trashed'] ) ) {
			$where .= ' AND deleted_at IS NULL';
		}

		if ( empty( $args['include_archived'] ) ) {
			$where .= " AND archived_at IS NULL AND status <> 'archived'";
		}

		if ( ! empty( $args['status'] ) ) {
			$where .= $wpdb->prepare( ' AND status = %s', $args['status'] );
		}

		if ( ! empty( $args['exclude_statuses'] ) && is_array( $args['exclude_statuses'] ) ) {
			$exclude = array_values( array_filter( array_map( 'sanitize_key', $args['exclude_statuses'] ) ) );
			if ( ! empty( $exclude ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $exclude ), '%s' ) );
				$query = " AND status NOT IN ($placeholders)";
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$where .= call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $query ), $exclude ) );
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} {$where}" );
		return $count;
	}

	/**
	 * Get all tickets.
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments.
	 * @return array Array of ticket objects.
	 */
	public static function get_all($args = array())
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		$defaults = array(
			'status'          => '',
			'assigned_to'     => '',
			'orderby'         => 'created_at',
			'order'           => 'DESC',
			'limit'           => 50,
			'offset'          => 0,
			'include_trashed' => false,
			'include_archived' => false,
			'include_pending_delete' => false,
		);

		$args = wp_parse_args($args, $defaults);

		$where = '1=1';

		// Exclude trashed tickets by default.
		if (! $args['include_trashed']) {
			$where .= ' AND deleted_at IS NULL';
		}

		// Exclude archived tickets by default.
		if ( empty( $args['include_archived'] ) ) {
			$where .= " AND archived_at IS NULL AND status <> 'archived'";
		}

		// Exclude pending delete tickets by default (these belong in the Review queue).
		if ( empty( $args['include_pending_delete'] ) ) {
			$where .= ' AND pending_delete_at IS NULL';
		}

		if (! empty($args['status'])) {
			$where .= $wpdb->prepare(' AND status = %s', $args['status']);
		}

		if (! empty($args['assigned_to'])) {
			$where .= $wpdb->prepare(' AND assigned_to = %d', absint($args['assigned_to']));
		}

		// Whitelist allowed orderby columns.
		$allowed_orderby = array('id', 'ticket_number', 'created_at', 'updated_at', 'status', 'priority', 'assigned_to');
		if (! in_array($args['orderby'], $allowed_orderby, true)) {
			$args['orderby'] = 'created_at';
		}

		// Validate order direction.
		$args['order'] = strtoupper($args['order']);
		if (! in_array($args['order'], array('ASC', 'DESC'), true)) {
			$args['order'] = 'DESC';
		}

		$orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
		if (false === $orderby) {
			$orderby = 'created_at DESC';
		}

		$limit   = absint($args['limit']);
		$offset  = absint($args['offset']);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$tickets = $wpdb->get_results(
			"SELECT * FROM {$table_name} WHERE {$where} ORDER BY {$orderby} LIMIT {$limit} OFFSET {$offset}"
		);

		return $tickets;
	}

	/**
	 * Delete a ticket.
	 *
	 * @since 1.0.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete($ticket_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id  = absint($ticket_id);

		// Delete associated responses.
		PNPC_PSD_Ticket_Response::delete_by_ticket($ticket_id);

		$result = $wpdb->delete(
			$table_name,
			array('id' => $ticket_id),
			array('%d')
		);

		return false !== $result;
	}

	/**
	 * Generate a unique ticket number.
	 *
	 * @since 1.0.0
	 * @return string Ticket number.
	 */
	private static function generate_ticket_number()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		// Start with the stored counter.
		$counter = (int) get_option('pnpc_psd_ticket_counter', 0);

		// If the DB already contains higher numbers (e.g., option reset or migrated DB), sync to DB.
		$db_max = 0;
		if (! empty($table_name)) {
			$db_max_raw = $wpdb->get_var(
				"SELECT MAX(CAST(SUBSTRING(ticket_number, 6) AS UNSIGNED)) FROM {$table_name} WHERE ticket_number LIKE 'PNPC-%'"
			);
			$db_max = (int) $db_max_raw;
		}

		$counter = max($counter, $db_max, 1000);
		$counter++;
		update_option('pnpc_psd_ticket_counter', $counter);

		return 'PNPC-' . $counter;
	}

	/**
	 * Send ticket created notification.
	 *
	 * @since 1.0.0
	 * @param int $ticket_id Ticket ID.
	 */
	private static function send_ticket_created_notification($ticket_id)
	{
		// v1.1.0+: central notification service.
		if ( class_exists( 'PNPC_PSD_Notifications' ) ) {
			PNPC_PSD_Notifications::ticket_created( (int) $ticket_id );
			return;
		}

		$ticket = self::get($ticket_id);
		if (! $ticket) {
			return;
		}

		$user = get_userdata($ticket->user_id);
		if (! $user) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: ticket number */
			__('New Support Ticket Created: %s', 'pnpc-pocket-service-desk'),
			$ticket->ticket_number
		);

		$message = sprintf(
			/* translators: 1: user display name, 2: ticket number, 3: ticket subject */
			__('Hello %1$s,

Your support ticket has been created successfully.

Ticket Number: %2$s
Subject: %3$s

We will respond to your ticket as soon as possible.

Thank you for contacting us!', 'pnpc-pocket-service-desk'),
			$user->display_name,
			$ticket->ticket_number,
			$ticket->subject
		);

		wp_mail($user->user_email, $subject, $message);

		// Notify admins.
		$admin_email = get_option('admin_email');
		$admin_subject = sprintf(
			/* translators: %s: ticket number */
			__('New Support Ticket: %s', 'pnpc-pocket-service-desk'),
			$ticket->ticket_number
		);

		$admin_message = sprintf(
			/* translators: 1: ticket number, 2: user display name, 3: ticket subject */
			__('A new support ticket has been created.

Ticket Number: %1$s
From: %2$s
Subject: %3$s

Please log in to the admin panel to view and respond to this ticket.', 'pnpc-pocket-service-desk'),
			$ticket->ticket_number,
			$user->display_name,
			$ticket->subject
		);

		wp_mail($admin_email, $admin_subject, $admin_message);
	}

	/**
	 * Detect whether v1.5.0 activity columns exist (cached).
	 *
	 * @return bool
	 */
	private static function has_activity_columns() {
		static $has = null;
		if ( null !== $has ) {
			return (bool) $has;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'pnpc_psd_tickets';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$col = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'last_customer_activity_at' ) );
		$has = ( ! empty( $col ) );
		return (bool) $has;
	}

	/**
	 * Initialize activity tracking for a newly created ticket.
	 */
	public static function update_activity_on_create( $ticket_id ) {
		$ticket_id = absint( $ticket_id );
		if ( ! $ticket_id || ! self::has_activity_columns() ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'pnpc_psd_tickets';
		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$table,
			array(
				'last_customer_activity_at' => $now,
				'last_customer_viewed_at'   => $now,
				'last_staff_activity_at'    => null,
				'last_staff_viewed_at'      => null,
			),
			array( 'id' => $ticket_id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Update activity tracking when a response is created.
	 */
	public static function update_activity_on_response( $ticket_id, $is_staff_response ) {
		$ticket_id = absint( $ticket_id );
		if ( ! $ticket_id || ! self::has_activity_columns() ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'pnpc_psd_tickets';
		$now = current_time( 'mysql', true );
		$fields = ( $is_staff_response ) ? array( 'last_staff_activity_at' => $now ) : array( 'last_customer_activity_at' => $now );
		$fields['updated_at'] = $now;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update( $table, $fields, array( 'id' => $ticket_id ) );
	}

	/**
	 * Mark viewed for the customer side.
	 */
	public static function mark_customer_viewed( $ticket_id ) {
		$ticket_id = absint( $ticket_id );
		if ( ! $ticket_id || ! self::has_activity_columns() ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'pnpc_psd_tickets';
		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update( $table, array( 'last_customer_viewed_at' => $now ), array( 'id' => $ticket_id ) );
	}

	/**
	 * Mark viewed for the staff side.
	 */
	public static function mark_staff_viewed( $ticket_id ) {
		$ticket_id = absint( $ticket_id );
		if ( ! $ticket_id || ! self::has_activity_columns() ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'pnpc_psd_tickets';
		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update( $table, array( 'last_staff_viewed_at' => $now ), array( 'id' => $ticket_id ) );
	}

	/**
	 * Get ticket count by status.
	 *
	 * @since 1.0.0
	 * @param string $status Status (optional).
	 * @return int Ticket count.
	 */
	public static function get_count($status = '')
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		if (! empty($status)) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE status = %s AND deleted_at IS NULL AND pending_delete_at IS NULL AND archived_at IS NULL AND status <> 'archived'",
					$status
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE deleted_at IS NULL AND pending_delete_at IS NULL AND archived_at IS NULL AND status <> 'archived'");
		}

		return absint($count);
	}

	/**
	 * Get trashed tickets.
	 *
	 * @since 1.1.0
	 * @param array $args Query arguments.
	 * @return array Array of ticket objects.
	 */
	public static function get_trashed($args = array())
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		$defaults = array(
			'orderby' => 'deleted_at',
			'order'   => 'DESC',
			'limit'   => 50,
			'offset'  => 0,
		);

		$args = wp_parse_args($args, $defaults);

		// Whitelist allowed orderby columns.
		$allowed_orderby = array('id', 'ticket_number', 'created_at', 'updated_at', 'deleted_at', 'status', 'priority');
		if (! in_array($args['orderby'], $allowed_orderby, true)) {
			$args['orderby'] = 'deleted_at';
		}

		// Validate order direction.
		$args['order'] = strtoupper($args['order']);
		if (! in_array($args['order'], array('ASC', 'DESC'), true)) {
			$args['order'] = 'DESC';
		}

		$orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
		if (false === $orderby) {
			$orderby = 'deleted_at DESC';
		}

		$limit   = absint($args['limit']);
		$offset  = absint($args['offset']);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$tickets = $wpdb->get_results(
			"SELECT * FROM {$table_name} WHERE deleted_at IS NOT NULL ORDER BY {$orderby} LIMIT {$limit} OFFSET {$offset}"
		);

		return $tickets;
	}

	/**
	 * Get count of trashed tickets.
	 *
	 * @since 1.1.0
	 * @return int Trashed ticket count.
	 */
	public static function get_trashed_count()
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE deleted_at IS NOT NULL");

		return absint($count);
	}

	/**
	 * Get archived tickets.
	 *
	 * Archived tickets are hidden from normal views but can be restored.
	 *
	 * @since 1.6.0
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_archived( $args = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		$defaults = array(
			'orderby' => 'archived_at',
			'order'   => 'DESC',
			'limit'   => 50,
			'offset'  => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		$allowed_orderby = array('id', 'ticket_number', 'created_at', 'updated_at', 'archived_at', 'status', 'priority');
		if ( ! in_array( $args['orderby'], $allowed_orderby, true ) ) {
			$args['orderby'] = 'archived_at';
		}

		$args['order'] = strtoupper( $args['order'] );
		if ( ! in_array( $args['order'], array('ASC', 'DESC'), true ) ) {
			$args['order'] = 'DESC';
		}

		$orderby = sanitize_sql_orderby( "{$args['orderby']} {$args['order']}" );
		if ( false === $orderby ) {
			$orderby = 'archived_at DESC';
		}

		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT * FROM {$table_name} WHERE archived_at IS NOT NULL AND deleted_at IS NULL ORDER BY {$orderby} LIMIT {$limit} OFFSET {$offset}"
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Get count of archived tickets.
	 *
	 * @since 1.6.0
	 * @return int
	 */
	public static function get_archived_count() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE archived_at IS NOT NULL AND deleted_at IS NULL" );
		return absint( $count );
	}

	/**
	 * Archive a ticket (moves it out of normal lists without deleting).
	 *
	 * @since 1.6.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool
	 */
	public static function archive( $ticket_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id = absint( $ticket_id );
		if ( ! $ticket_id ) {
			return false;
		}

		$ticket = self::get( $ticket_id );
		if ( empty( $ticket ) || ! empty( $ticket->deleted_at ) ) {
			return false;
		}
		// Archiving is intended for resolved tickets only.
		// Be tolerant of historical capitalization / formatting.
		$ticket_status = isset( $ticket->status ) ? sanitize_key( (string) $ticket->status ) : '';
		if ( '' !== $ticket_status && 'closed' !== $ticket_status ) {
			return false;
		}

		$now = function_exists( 'pnpc_psd_get_utc_mysql_datetime' ) ? pnpc_psd_get_utc_mysql_datetime() : current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$res = $wpdb->update(
			$table_name,
			array(
				'status'      => 'archived',
				'archived_at' => $now,
				'updated_at'  => $now,
			),
			array( 'id' => $ticket_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $res && class_exists( 'PNPC_PSD_Audit_Log' ) ) {
			PNPC_PSD_Audit_Log::log( $ticket_id, 'ticket_archived', array( 'actor_id' => get_current_user_id() ) );
		}
		return false !== $res;
	}

	/**
	 * Archive a ticket from the Trash view.
	 *
	 * Trash items are soft-deleted (deleted_at is set), and the standard archive()
	 * path intentionally refuses to archive deleted tickets. This helper supports
	 * the UX request to move a trashed ticket into Archive by restoring it first
	 * (preserving delete history) and then archiving it.
	 *
	 * This is intentionally only callable from the explicit Trash action path.
	 *
	 * @since 1.6.1
	 * @param int $ticket_id Ticket ID.
	 * @return bool
	 */
	public static function archive_from_trash( $ticket_id ) {
		$ticket_id = absint( $ticket_id );
		if ( ! $ticket_id ) {
			return false;
		}

		$ticket = self::get( $ticket_id );
		if ( empty( $ticket ) ) {
			return false;
		}

		// Only support this for tickets that are currently in Trash.
		if ( empty( $ticket->deleted_at ) ) {
			return false;
		}

		// Restore first (preserves delete history + restores attachments/responses).
		if ( ! self::restore( $ticket_id ) ) {
			return false;
		}

		// Then archive. From the Trash action path, allow archiving regardless of
		// prior status because the user explicitly requested retention rather than
		// deletion.
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$now        = function_exists( 'pnpc_psd_get_utc_mysql_datetime' ) ? pnpc_psd_get_utc_mysql_datetime() : current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$res = $wpdb->update(
			$table_name,
			array(
				'status'      => 'archived',
				'archived_at' => $now,
				'updated_at'  => $now,
			),
			array( 'id' => $ticket_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $res && class_exists( 'PNPC_PSD_Audit_Log' ) ) {
			PNPC_PSD_Audit_Log::log( $ticket_id, 'ticket_archived', array( 'actor_id' => get_current_user_id() ) );
		}
		return false !== $res;
	}

	/**
	 * Restore a ticket from archive.
	 *
	 * @since 1.6.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool
	 */
	public static function restore_from_archive( $ticket_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id = absint( $ticket_id );
		if ( ! $ticket_id ) {
			return false;
		}

		$now = function_exists( 'pnpc_psd_get_utc_mysql_datetime' ) ? pnpc_psd_get_utc_mysql_datetime() : current_time( 'mysql', true );

		// Restore to closed by default (archiving intended for resolved items).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$res = $wpdb->update(
			$table_name,
			array(
				'status'      => 'closed',
				'archived_at' => null,
				'updated_at'  => $now,
			),
			array( 'id' => $ticket_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $res && class_exists( 'PNPC_PSD_Audit_Log' ) ) {
			PNPC_PSD_Audit_Log::log( $ticket_id, 'ticket_restored_from_archive', array( 'actor_id' => get_current_user_id() ) );
		}
		return false !== $res;
	}

		/**
		 * Get tickets pending delete review (Review queue).
		 *
		 * @since 1.4.0
		 * @param array $args Query arguments.
		 * @return array Array of ticket objects.
		 */
		public static function get_pending_delete($args = array())
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		$defaults = array(
			'orderby' => 'pending_delete_at',
			'order'   => 'DESC',
			'limit'   => 50,
			'offset'  => 0,
		);
		$args = wp_parse_args($args, $defaults);

		$allowed_orderby = array('id', 'ticket_number', 'created_at', 'updated_at', 'pending_delete_at', 'status', 'priority');
		if (! in_array($args['orderby'], $allowed_orderby, true)) {
			$args['orderby'] = 'pending_delete_at';
		}

		$args['order'] = strtoupper($args['order']);
		if (! in_array($args['order'], array('ASC', 'DESC'), true)) {
			$args['order'] = 'DESC';
		}

		$orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
		if (false === $orderby) {
			$orderby = 'pending_delete_at DESC';
		}

		$limit  = absint($args['limit']);
		$offset = absint($args['offset']);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			"SELECT * FROM {$table_name} WHERE deleted_at IS NULL AND pending_delete_at IS NOT NULL ORDER BY {$orderby} LIMIT {$limit} OFFSET {$offset}"
		);
	}

	/**
	 * Get count of tickets pending delete review.
	 *
	 * @since 1.4.0
	 * @return int
	 */
	public static function get_pending_delete_count()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE deleted_at IS NULL AND pending_delete_at IS NOT NULL");
		return absint($count);
	}

	/**
	 * Request deletion for a ticket (puts it into Review queue).
	 *
	 * @since 1.4.0
	 * @param int    $ticket_id Ticket ID.
	 * @param int    $requested_by User ID requesting deletion.
	 * @param string $reason Delete reason.
	 * @param string $reason_other Optional details.
	 * @return bool
	 */
	public static function request_delete_with_reason($ticket_id, $requested_by, $reason, $reason_other = '')
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id  = absint($ticket_id);
		$requested_by = absint($requested_by);
		if (! $ticket_id || ! $requested_by) {
			return false;
		}

		$pending_at = function_exists('pnpc_psd_get_utc_mysql_datetime') ? pnpc_psd_get_utc_mysql_datetime() : current_time('mysql', true);

		$update_data = array(
			'pending_delete_at'           => $pending_at,
			'pending_delete_by'           => $requested_by,
			'pending_delete_reason'       => sanitize_text_field($reason),
			'pending_delete_reason_other' => null,
		);
		$formats = array('%s', '%d', '%s', '%s');
		if ('other' === $reason && ! empty($reason_other)) {
			$update_data['pending_delete_reason_other'] = sanitize_textarea_field($reason_other);
		}

		$updated = $wpdb->update(
			$table_name,
			$update_data,
			array('id' => $ticket_id),
			$formats,
			array('%d')
		);

		return false !== $updated;
	}

	/**
	 * Bulk request deletion for tickets (Review queue).
	 *
	 * @since 1.4.0
	 * @param array  $ticket_ids Ticket IDs.
	 * @param int    $requested_by User requesting deletion.
	 * @param string $reason Reason.
	 * @param string $reason_other Optional details.
	 * @return int
	 */
	public static function bulk_request_delete_with_reason($ticket_ids, $requested_by, $reason, $reason_other = '')
	{
		if (! is_array($ticket_ids) || empty($ticket_ids)) {
			return 0;
		}
		$count = 0;
		foreach ($ticket_ids as $ticket_id) {
			if (self::request_delete_with_reason($ticket_id, $requested_by, $reason, $reason_other)) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Cancel a pending delete request (restore from Review queue).
	 *
	 * @since 1.4.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool
	 */
	public static function cancel_pending_delete($ticket_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id  = absint($ticket_id);
		if (! $ticket_id) {
			return false;
		}

		$updated = $wpdb->update(
			$table_name,
			array(
				'pending_delete_at'           => null,
				'pending_delete_by'           => null,
				'pending_delete_reason'       => null,
				'pending_delete_reason_other' => null,
			),
			array('id' => $ticket_id),
			array('%s', '%d', '%s', '%s'),
			array('%d')
		);

		return false !== $updated;
	}

	/**
	 * Bulk cancel pending delete requests (restore from Review queue).
	 *
	 * @since 1.6.1
	 * @param array $ticket_ids Array of ticket IDs.
	 * @return int Number of tickets updated.
	 */
	public static function bulk_cancel_pending_delete( $ticket_ids ) {
		if ( ! is_array( $ticket_ids ) || empty( $ticket_ids ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $ticket_ids as $ticket_id ) {
			if ( self::cancel_pending_delete( $ticket_id ) ) {
				$count++;
			}
		}
		return $count;
	}



	/**
	 * Approve a pending delete request and move the ticket to Trash.
	 *
	 * Copies the queued delete reason/requester from the Review queue fields to the Trash fields.
	 *
	 * @since 1.4.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool
	 */
	public static function approve_pending_delete_to_trash($ticket_id)
	{
		$ticket_id = absint($ticket_id);
		if (! $ticket_id) {
			return false;
		}

		// Ensure delete tracking columns exist before we attempt to persist them.
		if ( class_exists( 'PNPC_PSD_Activator' ) && method_exists( 'PNPC_PSD_Activator', 'ensure_delete_reason_columns' ) ) {
			PNPC_PSD_Activator::ensure_delete_reason_columns();
		}

		$ticket = self::get($ticket_id);
		if (! $ticket) {
			return false;
		}

		// Must have a pending delete request to approve.
		$pending_at = isset($ticket->pending_delete_at) ? (string) $ticket->pending_delete_at : '';
		if ( '' === $pending_at ) {
			return false;
		}

		// Prefer the pending request data; fall back to a direct re-query if anything looks missing.
		$reason       = ! empty($ticket->pending_delete_reason) ? (string) $ticket->pending_delete_reason : '';
		$reason_other = ! empty($ticket->pending_delete_reason_other) ? (string) $ticket->pending_delete_reason_other : '';
		$requested_by = ! empty($ticket->pending_delete_by) ? absint($ticket->pending_delete_by) : 0;

		if ( '' === $reason || 0 === $requested_by ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$pending = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT pending_delete_by, pending_delete_reason, pending_delete_reason_other FROM {$table_name} WHERE id = %d",
					$ticket_id
				)
			);
			if ( $pending ) {
				if ( 0 === $requested_by && ! empty( $pending->pending_delete_by ) ) {
					$requested_by = absint( $pending->pending_delete_by );
				}
				if ( '' === $reason && ! empty( $pending->pending_delete_reason ) ) {
					$reason = (string) $pending->pending_delete_reason;
				}
				if ( '' === $reason_other && ! empty( $pending->pending_delete_reason_other ) ) {
					$reason_other = (string) $pending->pending_delete_reason_other;
				}
			}
		}

		// Move to trash first (soft-delete).
		$trashed = self::trash($ticket_id);
		if (! $trashed) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		$data = array(
			'delete_reason'               => $reason ? sanitize_text_field($reason) : null,
			'delete_reason_other'         => ('other' === $reason && ! empty($reason_other)) ? sanitize_textarea_field($reason_other) : null,
			'deleted_by'                  => $requested_by ? $requested_by : get_current_user_id(),
			'pending_delete_at'           => null,
			'pending_delete_by'           => null,
			'pending_delete_reason'       => null,
			'pending_delete_reason_other' => null,
		);

		// Use NULL formats for NULL values so $wpdb writes actual SQL NULLs.
		$formats = array();
		foreach ( $data as $v ) {
			if ( null === $v ) {
				$formats[] = null;
			} elseif ( is_int( $v ) ) {
				$formats[] = '%d';
			} else {
				$formats[] = '%s';
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$updated = $wpdb->update(
			$table_name,
			$data,
			array('id' => $ticket_id),
			$formats,
			array('%d')
		);

		return false !== $updated;
	}


	/**
	 * Bulk approve pending delete requests.
	 *
	 * @since 1.4.0
	 * @param array $ticket_ids Ticket IDs.
	 * @return int
	 */
	public static function bulk_approve_pending_delete_to_trash($ticket_ids)
	{
		if (! is_array($ticket_ids) || empty($ticket_ids)) {
			return 0;
		}
		$count = 0;
		foreach ($ticket_ids as $ticket_id) {
			if (self::approve_pending_delete_to_trash($ticket_id)) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Move a ticket to trash (soft delete).
	 *
	 * @since 1.1.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool True on success, false on failure.
	 */
	public static function trash($ticket_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id  = absint($ticket_id);

		if (! $ticket_id) {
			return false;
		}

		$deleted_at = function_exists('pnpc_psd_get_utc_mysql_datetime') ? pnpc_psd_get_utc_mysql_datetime() : current_time('mysql', true);

		// Soft delete the ticket.
		$result = $wpdb->update(
			$table_name,
			array('deleted_at' => $deleted_at),
			array('id' => $ticket_id),
			array('%s'),
			array('%d')
		);

		if (false === $result) {
			return false;
		}

		// Soft delete associated responses.
		PNPC_PSD_Ticket_Response::trash_by_ticket($ticket_id);

		// Soft delete associated attachments.
		self::trash_attachments_by_ticket($ticket_id);

		return true;
	}

	/**
	 * Move multiple tickets to trash.
	 *
	 * @since 1.1.0
	 * @param array $ticket_ids Array of ticket IDs.
	 * @return int Number of tickets trashed.
	 */
	public static function bulk_trash($ticket_ids)
	{
		if (! is_array($ticket_ids) || empty($ticket_ids)) {
			return 0;
		}

		$count = 0;
		foreach ($ticket_ids as $ticket_id) {
			if (self::trash($ticket_id)) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Restore a ticket from trash.
	 *
	 * @since 1.1.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool True on success, false on failure.
	 */
	public static function restore($ticket_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id  = absint($ticket_id);

		if (! $ticket_id) {
			return false;
		}

		// Get current ticket data to preserve deletion history.
		$ticket = self::get($ticket_id);

		if ($ticket && $ticket->deleted_at) {
			// Archive current delete reason to history.
			$history = self::get_meta($ticket_id, 'pnpc_psd_delete_history', true);
			if (! is_array($history)) {
				$history = array();
			}

			$history[] = array(
				'reason'        => $ticket->delete_reason,
				'reason_other'  => $ticket->delete_reason_other,
				'deleted_by'    => $ticket->deleted_by,
				'deleted_at'    => $ticket->deleted_at,
				'restored_at'   => function_exists('pnpc_psd_get_utc_mysql_datetime') ? pnpc_psd_get_utc_mysql_datetime() : current_time('mysql', true),
				'restored_by'   => get_current_user_id(),
			);

			self::update_meta($ticket_id, 'pnpc_psd_delete_history', $history);
		}

		// Restore the ticket and clear delete metadata.
		$result = $wpdb->update(
			$table_name,
			array(
				'deleted_at'          => null,
				'delete_reason'       => null,
				'delete_reason_other' => null,
				'deleted_by'          => null,
			),
			array('id' => $ticket_id),
			array('%s', '%s', '%s', '%s'),
			array('%d')
		);

		if (false === $result) {
			return false;
		}

		// Restore associated responses.
		PNPC_PSD_Ticket_Response::restore_by_ticket($ticket_id);

		// Restore associated attachments.
		self::restore_attachments_by_ticket($ticket_id);

		return true;
	}

	/**
	 * Restore multiple tickets from trash.
	 *
	 * @since 1.1.0
	 * @param array $ticket_ids Array of ticket IDs.
	 * @return int Number of tickets restored.
	 */
	public static function bulk_restore($ticket_ids)
	{
		if (! is_array($ticket_ids) || empty($ticket_ids)) {
			return 0;
		}

		$count = 0;
		foreach ($ticket_ids as $ticket_id) {
			if (self::restore($ticket_id)) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Permanently delete a ticket and all related data.
	 *
	 * @since 1.1.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_permanently($ticket_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id  = absint($ticket_id);

		if (! $ticket_id) {
			return false;
		}

		// Delete associated responses.
		PNPC_PSD_Ticket_Response::delete_by_ticket($ticket_id);

		// Delete associated attachments.
		self::delete_attachments_by_ticket($ticket_id);

		// Delete user meta related to this ticket.
		delete_metadata('user', 0, 'pnpc_psd_ticket_last_view_' . $ticket_id, '', true);

		// Delete the ticket.
		$result = $wpdb->delete(
			$table_name,
			array('id' => $ticket_id),
			array('%d')
		);

		return false !== $result;
	}

	/**
	 * Permanently delete multiple tickets.
	 *
	 * @since 1.1.0
	 * @param array $ticket_ids Array of ticket IDs.
	 * @return int Number of tickets deleted.
	 */
	
	/**
	 * Bulk archive closed tickets.
	 *
	 * @since 1.1.1.1
	 * @param array $ticket_ids Ticket IDs.
	 * @return int Number archived.
	 */
	public static function bulk_archive_closed( $ticket_ids ) {
		$ticket_ids = array_filter( array_map( 'absint', (array) $ticket_ids ) );
		if ( empty( $ticket_ids ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $ticket_ids as $ticket_id ) {
			if ( self::archive( $ticket_id ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Bulk restore tickets from archive.
	 *
	 * @since 1.1.1.1
	 * @param array $ticket_ids Ticket IDs.
	 * @return int Number restored.
	 */
	public static function bulk_restore_from_archive( $ticket_ids ) {
		$ticket_ids = array_filter( array_map( 'absint', (array) $ticket_ids ) );
		if ( empty( $ticket_ids ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $ticket_ids as $ticket_id ) {
			if ( self::restore_from_archive( $ticket_id ) ) {
				$count++;
			}
		}
		return $count;
	}
	/**
	* Bulk delete permanently.
	*
	* @param mixed $ticket_ids
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public static function bulk_delete_permanently($ticket_ids)
	{
		if (! is_array($ticket_ids) || empty($ticket_ids)) {
			return 0;
		}

		$count = 0;
		foreach ($ticket_ids as $ticket_id) {
			if (self::delete_permanently($ticket_id)) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Trash attachments by ticket ID.
	 *
	 * @since 1.1.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool True on success, false on failure.
	 */
	private static function trash_attachments_by_ticket($ticket_id)
	{
		global $wpdb;

		$attachments_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';
		$ticket_id         = absint($ticket_id);

		$deleted_at = function_exists('pnpc_psd_get_utc_mysql_datetime') ? pnpc_psd_get_utc_mysql_datetime() : current_time('mysql', true);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$attachments_table,
			array('deleted_at' => $deleted_at),
			array('ticket_id' => $ticket_id),
			array('%s'),
			array('%d')
		);

		return false !== $result;
	}

	/**
	 * Restore attachments by ticket ID.
	 *
	 * @since 1.1.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool True on success, false on failure.
	 */
	private static function restore_attachments_by_ticket($ticket_id)
	{
		global $wpdb;

		$attachments_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';
		$ticket_id         = absint($ticket_id);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$attachments_table,
			array('deleted_at' => null),
			array('ticket_id' => $ticket_id),
			array('%s'),
			array('%d')
		);

		return false !== $result;
	}

	/**
	 * Delete attachments by ticket ID.
	 *
	 * @since 1.1.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool True on success, false on failure.
	 */
	private static function delete_attachments_by_ticket($ticket_id)
	{
		global $wpdb;

		$attachments_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';
		$ticket_id         = absint($ticket_id);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete(
			$attachments_table,
			array('ticket_id' => $ticket_id),
			array('%d')
		);

		return false !== $result;
	}

	/**
	 * Move a ticket to trash with a reason (soft delete).
	 *
	 * @since 1.2.0
	 * @param int    $ticket_id Ticket ID.
	 * @param string $reason Delete reason.
	 * @param string $reason_other Optional. Additional details if reason is 'other'.
	 * @return bool True on success, false on failure.
	 */
	public static function trash_with_reason($ticket_id, $reason, $reason_other = '')
	{
		// Call existing trash method.
		$result = self::trash($ticket_id);

		if ($result) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

			// Store reason in tickets table columns.
			$update_data = array(
				'delete_reason'       => sanitize_text_field($reason),
				'delete_reason_other' => null, // Clear by default
				'deleted_by'          => get_current_user_id(),
			);

			$format = array('%s', '%s', '%d');

			if ('other' === $reason && ! empty($reason_other)) {
				$update_data['delete_reason_other'] = sanitize_textarea_field($reason_other);
			}

			$wpdb->update(
				$table_name,
				$update_data,
				array('id' => absint($ticket_id)),
				$format,
				array('%d')
			);
		}

		return $result;
	}

	/**
	 * Move multiple tickets to trash with a reason.
	 *
	 * @since 1.2.0
	 * @param array  $ticket_ids Array of ticket IDs.
	 * @param string $reason Delete reason.
	 * @param string $reason_other Optional. Additional details if reason is 'other'.
	 * @return int Number of tickets trashed.
	 */
	public static function bulk_trash_with_reason($ticket_ids, $reason, $reason_other = '')
	{
		if (! is_array($ticket_ids) || empty($ticket_ids)) {
			return 0;
		}

		$count = 0;
		foreach ($ticket_ids as $ticket_id) {
			if (self::trash_with_reason($ticket_id, $reason, $reason_other)) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Update ticket meta.
	 *
	 * @since 1.2.0
	 * @param int    $ticket_id Ticket ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return bool|int False on failure, number of rows affected on success.
	 */
	public static function update_meta($ticket_id, $meta_key, $meta_value)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_ticket_meta';

		// Check if meta exists.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_id FROM {$table_name} WHERE ticket_id = %d AND meta_key = %s",
				$ticket_id,
				$meta_key
			)
		);

		if ($exists) {
			return $wpdb->update(
				$table_name,
				array('meta_value' => maybe_serialize($meta_value)),
				array(
					'ticket_id' => $ticket_id,
					'meta_key'  => $meta_key,
				),
				array('%s'),
				array('%d', '%s')
			);
		} else {
			return $wpdb->insert(
				$table_name,
				array(
					'ticket_id'  => $ticket_id,
					'meta_key'   => $meta_key,
					'meta_value' => maybe_serialize($meta_value),
				),
				array('%d', '%s', '%s')
			);
		}
	}

	/**
	 * Get ticket meta.
	 *
	 * @since 1.2.0
	 * @param int    $ticket_id Ticket ID.
	 * @param string $meta_key Meta key.
	 * @param bool   $single Optional. Whether to return a single value. Default true.
	 * @return mixed Meta value or null if not found.
	 */
	public static function get_meta($ticket_id, $meta_key, $single = true)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_ticket_meta';

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$table_name} WHERE ticket_id = %d AND meta_key = %s",
				$ticket_id,
				$meta_key
			)
		);

		if ($result) {
			return maybe_unserialize($result);
		}

		return null;
	}

	/**
	 * Delete ticket meta.
	 *
	 * @since 1.2.0
	 * @param int    $ticket_id Ticket ID.
	 * @param string $meta_key Meta key.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_meta($ticket_id, $meta_key)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_ticket_meta';

		$result = $wpdb->delete(
			$table_name,
			array(
				'ticket_id' => $ticket_id,
				'meta_key'  => $meta_key,
			),
			array('%d', '%s')
		);

		return false !== $result;
	}
}
