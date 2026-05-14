<?php
/**
 * Client Notes (staff-only) for PNPC Pocket Service Desk (Free).
 *
 * Notes attach to the client (WP user) and persist across all of that client's tickets.
 * Stored in user meta as an array of note records.
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PNPC_PSD_Internal_Notes {
	/**
	 * User meta key for client notes.
	 */
	const USER_META_KEY = 'pnpc_psd_client_internal_notes';

	/**
	 * Legacy per-ticket meta key (kept for one-way migration on first view).
	 */
	const LEGACY_TICKET_META_KEY = 'pnpc_psd_internal_notes';

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $version Plugin version.
	 */
	public function __construct( $version ) {
		$this->version = (string) $version;
	}

	/**
	 * Enqueue assets for the admin ticket detail screen.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( ! is_admin() ) {
			return;
		}
		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'pnpc-service-desk-ticket' !== $page ) {
			return;
		}

		$ticket_id = isset( $_GET['ticket_id'] ) ? absint( wp_unslash( $_GET['ticket_id'] ) ) : 0;
		if ( $ticket_id <= 0 ) {
			return;
		}

		$src_js  = PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-client-notes.js';
		$src_css = PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-client-notes.css';

		$css_path = PNPC_PSD_PLUGIN_DIR . 'assets/css/pnpc-psd-client-notes.css';
		$js_path  = PNPC_PSD_PLUGIN_DIR . 'assets/js/pnpc-psd-client-notes.js';
		$css_ver  = $this->version;
		$js_ver   = $this->version;
		if ( file_exists( $css_path ) ) {
			$css_ver = (string) filemtime( $css_path );
		}
		if ( file_exists( $js_path ) ) {
			$js_ver = (string) filemtime( $js_path );
		}

		wp_enqueue_style( 'pnpc-psd-client-notes', $src_css, array(), $css_ver );
		wp_enqueue_script( 'pnpc-psd-client-notes', $src_js, array( 'jquery' ), $js_ver, true );

		wp_localize_script(
			'pnpc-psd-client-notes',
			'PNPC_PSD_CLIENT_NOTES',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'pnpc_psd_client_notes' ),
				'ticketId' => $ticket_id,
				'canWrite' => ( current_user_can( 'pnpc_psd_respond_to_tickets' ) || current_user_can( 'pnpc_psd_manage_tickets' ) ),
				'i18n'     => array(
					'toggle'      => __( 'Client Notes', 'pnpc-pocket-service-desk' ),
					'heading'     => __( 'Client Notes', 'pnpc-pocket-service-desk' ),
					'add'         => __( 'Add Note', 'pnpc-pocket-service-desk' ),
					'remove'      => __( 'Remove', 'pnpc-pocket-service-desk' ),
					'placeholder' => __( 'Write a private note for staff…', 'pnpc-pocket-service-desk' ),
					'loading'     => __( 'Loading…', 'pnpc-pocket-service-desk' ),
					'empty'       => __( 'No client notes yet.', 'pnpc-pocket-service-desk' ),
					'error'       => __( 'Unable to load client notes.', 'pnpc-pocket-service-desk' ),
					'forbidden'   => __( 'You do not have permission to add notes.', 'pnpc-pocket-service-desk' ),
				),
			)
		);
	}

	/**
	 * Determine the client (customer) user ID from a ticket object.
	 *
	 * @param object $ticket Ticket object.
	 * @return int
	 */
	private static function get_client_user_id_from_ticket( $ticket ) {
		if ( is_object( $ticket ) && isset( $ticket->user_id ) ) {
			return absint( $ticket->user_id );
		}
		return 0;
	}

	/**
	 * Load notes for a client.
	 *
	 * @param int $client_user_id Client user ID.
	 * @return array
	 */
	private static function get_client_notes( $client_user_id ) {
		$raw = get_user_meta( $client_user_id, self::USER_META_KEY, true );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Persist notes for a client.
	 *
	 * @param int   $client_user_id Client user ID.
	 * @param array $notes Notes.
	 * @return void
	 */
	private static function save_client_notes( $client_user_id, $notes ) {
		update_user_meta( $client_user_id, self::USER_META_KEY, $notes );
	}

	/**
	 * Format a note item for the UI.
	 *
	 * @param array $note Note record.
	 * @return array
	 */
	private static function format_item( $note ) {
		$note_id = isset( $note['id'] ) ? (string) $note['id'] : '';
		$user_id = isset( $note['user_id'] ) ? absint( $note['user_id'] ) : 0;
		$created = isset( $note['created'] ) ? absint( $note['created'] ) : 0;
		$content = isset( $note['content'] ) ? (string) $note['content'] : '';

		$user    = $user_id ? get_userdata( $user_id ) : null;
		$display = $user ? (string) $user->display_name : __( 'Unknown', 'pnpc-pocket-service-desk' );

		return array(
			'id'             => $note_id,
			'userId'         => $user_id,
			'author'         => $display,
			'created'        => $created,
			'createdDisplay' => $created ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $created ) : '',
			'content'        => $content,
		);
	}

	/**
	 * Attempt a one-way migration from legacy per-ticket notes to client notes.
	 * Only runs when the client has no notes yet and legacy ticket notes exist.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @param int $client_user_id Client user ID.
	 * @return array Migrated or existing notes.
	 */
	private static function maybe_migrate_legacy_ticket_notes( $ticket_id, $client_user_id ) {
		$existing = self::get_client_notes( $client_user_id );
		if ( ! empty( $existing ) ) {
			return $existing;
		}
		if ( ! class_exists( 'PNPC_PSD_Ticket' ) || ! method_exists( 'PNPC_PSD_Ticket', 'get_meta' ) ) {
			return $existing;
		}

		$raw    = PNPC_PSD_Ticket::get_meta( $ticket_id, self::LEGACY_TICKET_META_KEY, true );
		$legacy = is_array( $raw ) ? $raw : array();
		if ( empty( $legacy ) ) {
			return $existing;
		}

		$notes = array();
		foreach ( $legacy as $note ) {
			if ( ! is_array( $note ) ) {
				continue;
			}
			$notes[] = array(
				'id'      => isset( $note['id'] ) ? (string) $note['id'] : ( function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : (string) ( time() . '-' . wp_rand( 1000, 9999 ) ) ),
				'user_id' => isset( $note['user_id'] ) ? absint( $note['user_id'] ) : 0,
				'created' => isset( $note['created'] ) ? absint( $note['created'] ) : 0,
				'content' => isset( $note['content'] ) ? sanitize_textarea_field( (string) $note['content'] ) : '',
			);
		}

		if ( ! empty( $notes ) ) {
			self::save_client_notes( $client_user_id, $notes );
			return $notes;
		}

		return $existing;
	}

	/**
	 * AJAX: List client notes for a ticket.
	 */
	public function ajax_list() {
		check_ajax_referer( 'pnpc_psd_client_notes', 'nonce' );

		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$ticket_id = isset( $_POST['ticketId'] ) ? absint( wp_unslash( $_POST['ticketId'] ) ) : 0;
		if ( $ticket_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'bad_ticket_id' ), 400 );
		}
		if ( ! class_exists( 'PNPC_PSD_Ticket' ) || ! method_exists( 'PNPC_PSD_Ticket', 'get' ) ) {
			wp_send_json_error( array( 'message' => 'ticket_api_missing' ), 500 );
		}
		$ticket = PNPC_PSD_Ticket::get( $ticket_id );
		if ( ! $ticket ) {
			wp_send_json_error( array( 'message' => 'not_found' ), 404 );
		}

		$client_user_id = self::get_client_user_id_from_ticket( $ticket );
		if ( $client_user_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'bad_client' ), 400 );
		}

		$notes = self::maybe_migrate_legacy_ticket_notes( $ticket_id, $client_user_id );
		$items = array();
		foreach ( $notes as $note ) {
			if ( ! is_array( $note ) ) {
				continue;
			}
			$items[] = self::format_item( $note );
		}
		wp_send_json_success( array( 'items' => $items ) );
	}

	/**
	 * AJAX: Add a client note.
	 */
	public function ajax_add() {
		check_ajax_referer( 'pnpc_psd_client_notes', 'nonce' );

		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		if ( ! current_user_can( 'pnpc_psd_respond_to_tickets' ) && ! current_user_can( 'pnpc_psd_manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden_write' ), 403 );
		}

		$ticket_id = isset( $_POST['ticketId'] ) ? absint( wp_unslash( $_POST['ticketId'] ) ) : 0;
		if ( $ticket_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'bad_ticket_id' ), 400 );
		}
		$content_raw = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '';
		$content     = sanitize_textarea_field( $content_raw );
		$content     = trim( $content );
		if ( '' === $content ) {
			wp_send_json_error( array( 'message' => 'empty' ), 400 );
		}

		if ( ! class_exists( 'PNPC_PSD_Ticket' ) || ! method_exists( 'PNPC_PSD_Ticket', 'get' ) ) {
			wp_send_json_error( array( 'message' => 'ticket_api_missing' ), 500 );
		}
		$ticket = PNPC_PSD_Ticket::get( $ticket_id );
		if ( ! $ticket ) {
			wp_send_json_error( array( 'message' => 'not_found' ), 404 );
		}
		$client_user_id = self::get_client_user_id_from_ticket( $ticket );
		if ( $client_user_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'bad_client' ), 400 );
		}

		$notes = self::get_client_notes( $client_user_id );
		$note  = array(
			'id'      => function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : (string) ( time() . '-' . wp_rand( 1000, 9999 ) ),
			'user_id' => get_current_user_id(),
			'created' => (int) current_time( 'timestamp' ),
			'content' => $content,
		);
		$notes[] = $note;
		self::save_client_notes( $client_user_id, $notes );

		wp_send_json_success( array( 'item' => self::format_item( $note ) ) );
	}

	/**
	 * AJAX: Delete a client note by ID.
	 */
	public function ajax_delete() {
		check_ajax_referer( 'pnpc_psd_client_notes', 'nonce' );

		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		if ( ! current_user_can( 'pnpc_psd_respond_to_tickets' ) && ! current_user_can( 'pnpc_psd_manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden_write' ), 403 );
		}

		$ticket_id = isset( $_POST['ticketId'] ) ? absint( wp_unslash( $_POST['ticketId'] ) ) : 0;
		if ( $ticket_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'bad_ticket_id' ), 400 );
		}
		$note_id = isset( $_POST['noteId'] ) ? sanitize_text_field( wp_unslash( $_POST['noteId'] ) ) : '';
		$note_id = trim( (string) $note_id );
		if ( '' === $note_id ) {
			wp_send_json_error( array( 'message' => 'bad_note_id' ), 400 );
		}

		if ( ! class_exists( 'PNPC_PSD_Ticket' ) || ! method_exists( 'PNPC_PSD_Ticket', 'get' ) ) {
			wp_send_json_error( array( 'message' => 'ticket_api_missing' ), 500 );
		}
		$ticket = PNPC_PSD_Ticket::get( $ticket_id );
		if ( ! $ticket ) {
			wp_send_json_error( array( 'message' => 'not_found' ), 404 );
		}
		$client_user_id = self::get_client_user_id_from_ticket( $ticket );
		if ( $client_user_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'bad_client' ), 400 );
		}

		$notes = self::get_client_notes( $client_user_id );
		if ( empty( $notes ) ) {
			wp_send_json_success( array( 'deleted' => false ) );
		}

		$new_notes = array();
		$deleted   = false;
		foreach ( $notes as $n ) {
			if ( ! is_array( $n ) ) {
				continue;
			}
			$nid = isset( $n['id'] ) ? (string) $n['id'] : '';
			if ( '' !== $nid && $nid === $note_id ) {
				$deleted = true;
				continue;
			}
			$new_notes[] = $n;
		}

		if ( $deleted ) {
			self::save_client_notes( $client_user_id, $new_notes );
		}
		wp_send_json_success( array( 'deleted' => $deleted ) );
	}
}
