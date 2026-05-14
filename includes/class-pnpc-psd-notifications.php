<?php

/**
 * Notification service for PNPC Pocket Service Desk.
 *
 * Centralizes all outbound email notifications so access control, recipients,
 * and user-configurable switches are handled consistently.
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PNPC PSD Notifications.
 *
 * @since 1.1.1.4
 */
class PNPC_PSD_Notifications {

	/**
	 * Get a boolean option with a default.
	 */
	private static function opt_bool( $key, $default = 0 ) {
		$val = get_option( $key, $default );
		return (int) $val === 1;
	}

	/**
	 * Get the configured "from" name/email.
	 */
	private static function get_from_headers() {
		$from_name  = get_option( 'pnpc_psd_notify_from_name', '' );
		$from_email = get_option( 'pnpc_psd_notify_from_email', '' );

		$from_name  = sanitize_text_field( (string) $from_name );
		$from_email = sanitize_email( (string) $from_email );

		$headers = array();
		if ( $from_email ) {
			$hdr = 'From: ';
			$hdr .= $from_name ? sprintf( '%s <%s>', $from_name, $from_email ) : $from_email;
			$headers[] = $hdr;
		}
		return $headers;
	}

	/**
	 * Resolve staff recipients for a ticket.
	 *
	 * - Assigned agent (if any) using per-agent override.
	 * - Global notification email (if set and distinct).
	 */
	private static function get_staff_recipients_for_ticket( $ticket ) {
		$to = array();
		$assigned = isset( $ticket->assigned_to ) ? absint( $ticket->assigned_to ) : 0;

		// 1) Assigned agent (or default assignment), using per-agent override routing.
		if ( $assigned && function_exists( 'pnpc_psd_get_agent_notification_email' ) ) {
			$e = pnpc_psd_get_agent_notification_email( $assigned );
			if ( $e ) {
				$to[] = $e;
			}
		}

		// 2) Additional enabled agents with Notifications ON.
		$cfg = get_option( 'pnpc_psd_agents', array() );
		if ( is_array( $cfg ) && function_exists( 'pnpc_psd_get_agent_notification_email' ) ) {
			foreach ( $cfg as $uid => $row ) {
				$uid = absint( $uid );
				if ( ! $uid ) {
					continue;
				}
				if ( $assigned && (int) $uid === (int) $assigned ) {
					continue;
				}
				$row = is_array( $row ) ? $row : array();
				$enabled = ! empty( $row['enabled'] );
				$notify  = ! empty( $row['notify'] );
				if ( $enabled && $notify ) {
					$e = pnpc_psd_get_agent_notification_email( $uid );
					if ( $e ) {
						$to[] = $e;
					}
				}
			}
		}

		// 3) Global notification email (if set).
		$global = sanitize_email( (string) get_option( 'pnpc_psd_email_notifications', '' ) );
		if ( $global ) {
			$to[] = $global;
		}

		$to = array_filter( array_unique( array_map( 'sanitize_email', $to ) ) );
		return $to;
	}

	/**
	 * Send an email safely.
	 */
	private static function send( $to, $subject, $message ) {
		$to = is_array( $to ) ? $to : array( $to );
		$to = array_filter( array_unique( array_map( 'sanitize_email', $to ) ) );
		if ( empty( $to ) ) {
			return;
		}

		$subject = sanitize_text_field( (string) $subject );
		$message = (string) $message;
		$headers = self::get_from_headers();

		// wp_mail accepts array recipients.
		wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Ticket created.
	 */
	public static function ticket_created( $ticket_id ) {
		if ( ! class_exists( 'PNPC_PSD_Ticket' ) ) {
			return;
		}
		$ticket = PNPC_PSD_Ticket::get( $ticket_id );
		if ( ! $ticket ) {
			return;
		}

		$user = get_userdata( (int) $ticket->user_id );
		if ( ! $user ) {
			return;
		}

		if ( self::opt_bool( 'pnpc_psd_notify_customer_on_create', 1 ) ) {
			$dashboard_url   = function_exists( 'pnpc_psd_get_dashboard_url' ) ? pnpc_psd_get_dashboard_url() : '';
			$ticket_view_url = function_exists( 'pnpc_psd_get_ticket_detail_url' ) ? pnpc_psd_get_ticket_detail_url( $ticket_id ) : '';
			$subj = sprintf( __( 'Ticket created: %s', 'pnpc-pocket-service-desk' ), $ticket->ticket_number );
			$msg  = sprintf(
				__( "Hello %1\$s,\n\nYour support ticket has been created.\n\nTicket: %2\$s\nSubject: %3\$s\n\nWe will respond as soon as possible.", 'pnpc-pocket-service-desk' ),
				(string) $user->display_name,
				(string) $ticket->ticket_number,
				(string) $ticket->subject
			);
			if ( ! empty( $dashboard_url ) ) {
				$msg .= "\n\n" . __( 'Dashboard:', 'pnpc-pocket-service-desk' ) . "\n" . $dashboard_url;
			}
			if ( ! empty( $ticket_view_url ) ) {
				$msg .= "\n\n" . __( 'View this ticket:', 'pnpc-pocket-service-desk' ) . "\n" . $ticket_view_url;
			}
			self::send( (string) $user->user_email, $subj, $msg );
		}

		if ( self::opt_bool( 'pnpc_psd_notify_staff_on_create', 1 ) ) {
			$admin_ticket_url = admin_url( 'admin.php?page=pnpc-service-desk-ticket&ticket_id=' . absint( $ticket_id ) );
			$to = self::get_staff_recipients_for_ticket( $ticket );
			if ( ! empty( $to ) ) {
				$subj = sprintf( __( 'New ticket: %s', 'pnpc-pocket-service-desk' ), $ticket->ticket_number );
				$msg  = sprintf(
					__( "A new support ticket has been created.\n\nTicket: %1\$s\nFrom: %2\$s\nSubject: %3\$s\n\nLog in to review and respond.", 'pnpc-pocket-service-desk' ),
					(string) $ticket->ticket_number,
					(string) $user->display_name,
					(string) $ticket->subject
				);
						if ( ! empty( $admin_ticket_url ) ) {
							$msg .= "\n\n" . __( 'Admin ticket link:', 'pnpc-pocket-service-desk' ) . "\n" . $admin_ticket_url;
						}
				self::send( $to, $subj, $msg );
			}
		}
	}

	/**
	 * Response created.
	 */
	public static function response_created( $response_id ) {
		if ( ! class_exists( 'PNPC_PSD_Ticket_Response' ) || ! class_exists( 'PNPC_PSD_Ticket' ) ) {
			return;
		}
		$response = PNPC_PSD_Ticket_Response::get( $response_id );
		if ( ! $response ) {
			return;
		}
		$ticket = PNPC_PSD_Ticket::get( (int) $response->ticket_id );
		if ( ! $ticket ) {
			return;
		}

		$is_staff = ! empty( $response->is_staff_response );
		$customer = get_userdata( (int) $ticket->user_id );
		if ( ! $customer ) {
			return;
		}

		// Context-aware links for both customer and staff messages.
		$resolved_ticket_id = isset( $ticket->id ) ? absint( $ticket->id ) : absint( $response->ticket_id );
		$dashboard_url      = function_exists( 'pnpc_psd_get_dashboard_url' ) ? pnpc_psd_get_dashboard_url() : '';
		$ticket_view_url    = function_exists( 'pnpc_psd_get_ticket_detail_url' ) ? pnpc_psd_get_ticket_detail_url( (int) $resolved_ticket_id ) : '';
		$admin_ticket_url   = admin_url( 'admin.php?page=pnpc-service-desk-ticket&ticket_id=' . absint( $resolved_ticket_id ) );

		if ( $is_staff ) {
			if ( self::opt_bool( 'pnpc_psd_notify_customer_on_staff_reply', 1 ) ) {
				$subj = sprintf( __( 'Update on ticket %s', 'pnpc-pocket-service-desk' ), $ticket->ticket_number );
				$msg  = sprintf(
					__( "Hello %1\$s,\n\nYou have a new response on your ticket %2\$s.\n\nSubject: %3\$s\n\nLog in to view and reply.", 'pnpc-pocket-service-desk' ),
					(string) $customer->display_name,
					(string) $ticket->ticket_number,
					(string) $ticket->subject
				);
				if ( ! empty( $dashboard_url ) ) {
					$msg .= "\n\n" . __( 'Dashboard:', 'pnpc-pocket-service-desk' ) . "\n" . $dashboard_url;
				}
				if ( ! empty( $ticket_view_url ) ) {
					$msg .= "\n\n" . __( 'View this ticket:', 'pnpc-pocket-service-desk' ) . "\n" . $ticket_view_url;
				}
				self::send( (string) $customer->user_email, $subj, $msg );
			}
		} else {
			if ( self::opt_bool( 'pnpc_psd_notify_staff_on_customer_reply', 1 ) ) {
				$to = self::get_staff_recipients_for_ticket( $ticket );
				if ( ! empty( $to ) ) {
					$subj = sprintf( __( 'Customer replied: %s', 'pnpc-pocket-service-desk' ), $ticket->ticket_number );
					$msg  = sprintf(
						__( "A customer has replied to a ticket.\n\nTicket: %1\$s\nCustomer: %2\$s\nSubject: %3\$s\n\nLog in to respond.", 'pnpc-pocket-service-desk' ),
						(string) $ticket->ticket_number,
						(string) $customer->display_name,
						(string) $ticket->subject
					);
						if ( ! empty( $admin_ticket_url ) ) {
							$msg .= "\n\n" . __( 'Admin ticket link:', 'pnpc-pocket-service-desk' ) . "\n" . $admin_ticket_url;
						}
					self::send( $to, $subj, $msg );
				}
			}
		}
	}

	/**
	 * Ticket closed.
	 */
	public static function ticket_closed( $ticket_id ) {
		if ( ! class_exists( 'PNPC_PSD_Ticket' ) ) {
			return;
		}
		$ticket = PNPC_PSD_Ticket::get( $ticket_id );
		if ( ! $ticket ) {
			return;
		}
		$user = get_userdata( (int) $ticket->user_id );
		if ( ! $user ) {
			return;
		}
		if ( ! self::opt_bool( 'pnpc_psd_notify_customer_on_close', 1 ) ) {
			return;
		}
		$subj = sprintf( __( 'Ticket closed: %s', 'pnpc-pocket-service-desk' ), $ticket->ticket_number );
		$msg  = sprintf(
			__( "Hello %1\$s,\n\nYour ticket %2\$s has been marked closed.\n\nSubject: %3\$s\n\nIf you need further help, you can reply to reopen or create a new ticket.", 'pnpc-pocket-service-desk' ),
			(string) $user->display_name,
			(string) $ticket->ticket_number,
			(string) $ticket->subject
		);
		self::send( (string) $user->user_email, $subj, $msg );
	}
}
