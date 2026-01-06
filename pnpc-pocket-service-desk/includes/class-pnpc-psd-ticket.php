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

			// Format array must match the insert order above.
			$format = array('%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s');

			$result = $wpdb->insert(
				$table_name,
				$insert_data,
				$format
			);

			if (false !== $result) {
				$ticket_id = (int) $wpdb->insert_id;
				self::send_ticket_created_notification($ticket_id);
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
				error_log('pnpc-psd-debug ticket_create_failed: ' . print_r($insert_data, true));
				error_log('pnpc-psd-debug wpdb_last_error: ' . (isset($wpdb->last_error) ? $wpdb->last_error : ''));
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
			'orderby'         => 'created_at',
			'order'           => 'DESC',
			'limit'           => 50,
			'offset'          => 0,
			'include_trashed' => false,
		);

		$args = wp_parse_args($args, $defaults);

		$where = $wpdb->prepare('WHERE user_id = %d', $user_id);

		// Exclude trashed tickets by default.
		if (! $args['include_trashed']) {
			$where .= ' AND deleted_at IS NULL';
		}

		if (! empty($args['status'])) {
			$where .= $wpdb->prepare(' AND status = %s', $args['status']);
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
		);

		$args = wp_parse_args($args, $defaults);

		$where = '1=1';

		// Exclude trashed tickets by default.
		if (! $args['include_trashed']) {
			$where .= ' AND deleted_at IS NULL';
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
	 * Update a ticket.
	 *
	 * @since 1.0.0
	 * @param int   $ticket_id Ticket ID.
	 * @param array $data Update data.
	 * @return bool True on success, false on failure.
	 */
	public static function update($ticket_id, $data)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_id  = absint($ticket_id);

		$allowed_fields = array('status', 'priority', 'assigned_to', 'subject', 'description');
		$update_data    = array();
		$format         = array();

		foreach ($allowed_fields as $field) {
			if (isset($data[$field])) {
				if ('assigned_to' === $field) {
					$update_data[$field] = ! empty($data[$field]) ? absint($data[$field]) : null;
					$format[]              = '%d';
				} else {
					$update_data[$field] = sanitize_text_field($data[$field]);
					$format[]              = '%s';
				}
			}
		}

		if (empty($update_data)) {
			return false;
		}

		$result = $wpdb->update(
			$table_name,
			$update_data,
			array('id' => $ticket_id),
			$format,
			array('%d')
		);

		return false !== $result;
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
					"SELECT COUNT(*) FROM {$table_name} WHERE status = %s AND deleted_at IS NULL",
					$status
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE deleted_at IS NULL");
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

		// Restore the ticket.
		$result = $wpdb->update(
			$table_name,
			array('deleted_at' => null),
			array('id' => $ticket_id),
			array('%s'),
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
}
