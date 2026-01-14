<?php

/**
 * Ticket response management functionality
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */

/**
 * Ticket response management class.
 *
 * Handles all ticket response-related operations.
 *
 * @since      1.0.0
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */
class PNPC_PSD_Ticket_Response
{

	/**
	 * Create a new ticket response.
	 *
	 * @since 1.0.0
	 * @param array $data Response data.
	 * @return int|false Response ID on success, false on failure.
	 */
	public static function create($data)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_ticket_responses';
		$attachments_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';

		$defaults = array(
			'ticket_id'         => 0,
			'user_id'           => get_current_user_id(),
			'response'          => '',
			'is_staff_response' => 0,
			'attachments'       => array(), // array of attachment arrays [url|file_path, file_name, file_type, file_size, uploaded_by]
		);

		$data = wp_parse_args($data, $defaults);

		// Validate required fields.
		if (empty($data['ticket_id']) || empty($data['response'])) {
			return false;
		}

		// Check if user is staff.
		$is_staff = current_user_can('pnpc_psd_respond_to_tickets');

		$created_at_utc = function_exists('pnpc_psd_get_utc_mysql_datetime') ? pnpc_psd_get_utc_mysql_datetime() : current_time('mysql', true);

		$insert_data = array(
			'ticket_id'         => absint($data['ticket_id']),
			'user_id'           => absint($data['user_id']),
			'response'          => wp_kses_post($data['response']),
			'is_staff_response' => $is_staff ? 1 : 0,
			'created_at'        => $created_at_utc,
		);

		$result = $wpdb->insert(
			$table_name,
			$insert_data,
			array('%d', '%d', '%s', '%d', '%s')
		);

		if ($result) {
			$response_id = $wpdb->insert_id;

			if ( class_exists( 'PNPC_PSD_Audit_Log' ) ) {
				PNPC_PSD_Audit_Log::log( absint($data['ticket_id']), $is_staff ? 'staff_replied' : 'customer_replied', array(
					'actor_id' => absint($data['user_id']),
					'response_id' => absint($response_id),
				) );
			}

			// Save attachments if provided (non-blocking)
			if (! empty($data['attachments']) && is_array($data['attachments'])) {
				foreach ($data['attachments'] as $att) {
					// Expected: att = array('file_name'=>..., 'file_path'=>..., 'file_type'=>..., 'file_size'=>..., 'uploaded_by'=>int)
					$att_data = array(
						'ticket_id'   => absint($data['ticket_id']),
						'response_id' => intval($response_id),
						'file_name'   => sanitize_file_name(isset($att['file_name']) ? $att['file_name'] : basename($att['file_path'])),
						'file_path'   => isset($att['file_path']) ? sanitize_text_field((string) $att['file_path']) : '',
						'file_type'   => isset($att['file_type']) ? sanitize_text_field($att['file_type']) : '',
						'file_size'   => isset($att['file_size']) ? intval($att['file_size']) : 0,
						'uploaded_by' => isset($att['uploaded_by']) ? absint($att['uploaded_by']) : absint(get_current_user_id()),
						'created_at'  => $created_at_utc,
					);
					// IMPORTANT: keep formats aligned with $att_data keys to avoid corrupting file_type/file_size.
					$wpdb->insert(
						$attachments_table,
						$att_data,
						array('%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s')
					);
				}
			}

			// Update ticket activity tracking (role-level) for unread indicators.
			// Important: Do NOT rely on $data['is_staff_response'] because it is an input hint and
			// may not be set by callers. Use the computed staff capability result instead.
			if ( class_exists( 'PNPC_PSD_Ticket' ) ) {
				PNPC_PSD_Ticket::update_activity_on_response( absint( $data['ticket_id'] ), (bool) $is_staff );
			}
			self::send_response_notification($response_id);

			return $response_id;
		}

		return false;
	}

	/**
	 * Get a response by ID.
	 *
	 * @since 1.0.0
	 * @param int $response_id Response ID.
	 * @return object|null Response object or null if not found.
	 */
	public static function get($response_id)
	{
		global $wpdb;

		$table_name  = $wpdb->prefix . 'pnpc_psd_ticket_responses';
		$response_id = absint($response_id);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$response = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$response_id
			)
		);

		return $response;
	}

	/**
	 * Get responses for a ticket.
	 *
	 * @since 1.0.0
	 * @param int   $ticket_id Ticket ID.
	 * @param array $args Query arguments.
	 * @return array Array of response objects.
	 */
	public static function get_by_ticket($ticket_id, $args = array())
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_ticket_responses';
		$ticket_id  = absint($ticket_id);

		$defaults = array(
			'orderby'         => 'created_at',
			'order'           => 'ASC',
			'include_trashed' => false,
		);

		$args = wp_parse_args($args, $defaults);

		$where = $wpdb->prepare('WHERE ticket_id = %d', $ticket_id);

		// Exclude trashed responses by default.
		if (! $args['include_trashed']) {
			$where .= ' AND deleted_at IS NULL';
		}

		// Whitelist allowed orderby columns.
		$allowed_orderby = array('id', 'created_at', 'user_id', 'is_staff_response');
		if (! in_array($args['orderby'], $allowed_orderby, true)) {
			$args['orderby'] = 'created_at';
		}

		// Validate order direction.
		$args['order'] = strtoupper($args['order']);
		if (! in_array($args['order'], array('ASC', 'DESC'), true)) {
			$args['order'] = 'ASC';
		}

		$orderby = sanitize_sql_orderby("{$args['orderby']} {$args['order']}");
		if (false === $orderby) {
			$orderby = 'created_at ASC';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$responses = $wpdb->get_results(
			"SELECT * FROM {$table_name} {$where} ORDER BY {$orderby}"
		);

		return $responses;
	}

	/**
	 * Delete responses for a ticket.
	 *
	 * @since 1.0.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_by_ticket($ticket_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_ticket_responses';
		$ticket_id  = absint($ticket_id);

		$result = $wpdb->delete(
			$table_name,
			array('ticket_id' => $ticket_id),
			array('%d')
		);

		return false !== $result;
	}

	/**
	 * Send response notification.
	 *
	 * @since 1.0.0
	 * @param int $response_id Response ID.
	 */
	private static function send_response_notification($response_id)
	{
		// v1.1.0+: central notification service.
		if ( class_exists( 'PNPC_PSD_Notifications' ) ) {
			PNPC_PSD_Notifications::response_created( (int) $response_id );
			return;
		}

		$response = self::get($response_id);
		if (! $response) {
			return;
		}

		$ticket = PNPC_PSD_Ticket::get($response->ticket_id);
		if (! $ticket) {
			return;
		}

		$responder = get_userdata($response->user_id);
		if (! $responder) {
			return;
		}

		// Notify ticket owner if response is from staff.
		if ($response->is_staff_response) {
			$ticket_owner = get_userdata($ticket->user_id);
			if ($ticket_owner) {
				$subject = sprintf(
					/* translators: %s: ticket number */
					__('New Response to Your Ticket: %s', 'pnpc-pocket-service-desk'),
					$ticket->ticket_number
				);

				$message = sprintf(
					/* translators: 1: user display name, 2: ticket number, 3: response */
					__('Hello %1$s,

You have received a new response to your support ticket.

Ticket Number: %2$s

Response:
%3$s

Please log in to view and respond to this ticket.

Thank you!', 'pnpc-pocket-service-desk'),
					$ticket_owner->display_name,
					$ticket->ticket_number,
					wp_strip_all_tags($response->response)
				);

				wp_mail($ticket_owner->user_email, $subject, $message);
			}
		} else {
			// Notify admin if response is from customer.
			$admin_email = get_option('admin_email');
			$subject     = sprintf(
				/* translators: %s: ticket number */
				__('New Customer Response: %s', 'pnpc-pocket-service-desk'),
				$ticket->ticket_number
			);

			$message = sprintf(
				/* translators: 1: ticket number, 2: user display name, 3: response */
				__('A customer has responded to a support ticket.

Ticket Number: %1$s
From: %2$s

Response:
%3$s

Please log in to the admin panel to view and respond.', 'pnpc-pocket-service-desk'),
				$ticket->ticket_number,
				$responder->display_name,
				wp_strip_all_tags($response->response)
			);

			wp_mail($admin_email, $subject, $message);
		}
	}

	/**
	 * Get response count for a ticket.
	 *
	 * @since 1.0.0
	 * @param int $ticket_id Ticket ID.
	 * @return int Response count.
	 */
	public static function get_count($ticket_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_ticket_responses';
		$ticket_id  = absint($ticket_id);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE ticket_id = %d AND deleted_at IS NULL",
				$ticket_id
			)
		);

		return absint($count);
	}

	/**
	 * Trash responses by ticket ID.
	 *
	 * @since 1.1.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool True on success, false on failure.
	 */
	public static function trash_by_ticket($ticket_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_ticket_responses';
		$ticket_id  = absint($ticket_id);

		$deleted_at = function_exists('pnpc_psd_get_utc_mysql_datetime') ? pnpc_psd_get_utc_mysql_datetime() : current_time('mysql', true);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$table_name,
			array('deleted_at' => $deleted_at),
			array('ticket_id' => $ticket_id),
			array('%s'),
			array('%d')
		);

		return false !== $result;
	}

	/**
	 * Restore responses by ticket ID.
	 *
	 * @since 1.1.0
	 * @param int $ticket_id Ticket ID.
	 * @return bool True on success, false on failure.
	 */
	public static function restore_by_ticket($ticket_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'pnpc_psd_ticket_responses';
		$ticket_id  = absint($ticket_id);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$table_name,
			array('deleted_at' => null),
			array('ticket_id' => $ticket_id),
			array('%s'),
			array('%d')
		);

		return false !== $result;
	}
}
