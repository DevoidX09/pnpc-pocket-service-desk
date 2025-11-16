<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin
 */
class PNPC_PSD_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		if ( $this->is_plugin_page() ) {
			wp_enqueue_style(
				$this->plugin_name,
				PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-admin.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( $this->is_plugin_page() ) {
			wp_enqueue_script(
				$this->plugin_name,
				PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-admin.js',
				array( 'jquery' ),
				$this->version,
				false
			);

			wp_localize_script(
				$this->plugin_name,
				'pnpcPsdAdmin',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'pnpc_psd_admin_nonce' ),
				)
			);
		}
	}

	/**
	 * Add plugin admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_admin_menu() {
		// Main menu.
		add_menu_page(
			__( 'Service Desk', 'pnpc-pocket-service-desk' ),
			__( 'Service Desk', 'pnpc-pocket-service-desk' ),
			'pnpc_psd_view_tickets',
			'pnpc-service-desk',
			array( $this, 'display_tickets_page' ),
			'dashicons-tickets',
			30
		);

		// All Tickets submenu.
		add_submenu_page(
			'pnpc-service-desk',
			__( 'All Tickets', 'pnpc-pocket-service-desk' ),
			__( 'All Tickets', 'pnpc-pocket-service-desk' ),
			'pnpc_psd_view_tickets',
			'pnpc-service-desk',
			array( $this, 'display_tickets_page' )
		);

		// View Ticket submenu (hidden).
		add_submenu_page(
			null,
			__( 'View Ticket', 'pnpc-pocket-service-desk' ),
			__( 'View Ticket', 'pnpc-pocket-service-desk' ),
			'pnpc_psd_view_tickets',
			'pnpc-service-desk-ticket',
			array( $this, 'display_ticket_detail_page' )
		);

		// Settings submenu.
		add_submenu_page(
			'pnpc-service-desk',
			__( 'Settings', 'pnpc-pocket-service-desk' ),
			__( 'Settings', 'pnpc-pocket-service-desk' ),
			'pnpc_psd_manage_settings',
			'pnpc-service-desk-settings',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Display tickets page.
	 *
	 * @since 1.0.0
	 */
	public function display_tickets_page() {
		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pnpc-pocket-service-desk' ) );
		}

		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

		$args = array(
			'status' => $status,
			'limit'  => 20,
		);

		$tickets      = PNPC_PSD_Ticket::get_all( $args );
		$open_count   = PNPC_PSD_Ticket::get_count( 'open' );
		$closed_count = PNPC_PSD_Ticket::get_count( 'closed' );

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/tickets-list.php';
	}

	/**
	 * Display ticket detail page.
	 *
	 * @since 1.0.0
	 */
	public function display_ticket_detail_page() {
		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pnpc-pocket-service-desk' ) );
		}

		$ticket_id = isset( $_GET['ticket_id'] ) ? absint( $_GET['ticket_id'] ) : 0;

		if ( ! $ticket_id ) {
			wp_die( esc_html__( 'Invalid ticket ID.', 'pnpc-pocket-service-desk' ) );
		}

		$ticket    = PNPC_PSD_Ticket::get( $ticket_id );
		$responses = PNPC_PSD_Ticket_Response::get_by_ticket( $ticket_id );

		if ( ! $ticket ) {
			wp_die( esc_html__( 'Ticket not found.', 'pnpc-pocket-service-desk' ) );
		}

		// Get list of agents for assignment.
		$agents = get_users(
			array(
				'role__in' => array( 'administrator', 'pnpc_psd_agent', 'pnpc_psd_manager' ),
			)
		);

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/ticket-detail.php';
	}

	/**
	 * Display settings page.
	 *
	 * @since 1.0.0
	 */
	public function display_settings_page() {
		if ( ! current_user_can( 'pnpc_psd_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pnpc-pocket-service-desk' ) );
		}

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_email_notifications' );
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_auto_assign_tickets' );
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_allowed_file_types' );
	}

	/**
	 * AJAX handler to respond to a ticket.
	 *
	 * @since 1.0.0
	 */
	public function ajax_respond_to_ticket() {
		check_ajax_referer( 'pnpc_psd_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pnpc_psd_respond_to_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'pnpc-pocket-service-desk' ) ) );
		}

		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
		$response  = isset( $_POST['response'] ) ? wp_kses_post( wp_unslash( $_POST['response'] ) ) : '';

		if ( ! $ticket_id || empty( $response ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'pnpc-pocket-service-desk' ) ) );
		}

		$response_id = PNPC_PSD_Ticket_Response::create(
			array(
				'ticket_id' => $ticket_id,
				'response'  => $response,
			)
		);

		if ( $response_id ) {
			wp_send_json_success( array( 'message' => __( 'Response added successfully.', 'pnpc-pocket-service-desk' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to add response.', 'pnpc-pocket-service-desk' ) ) );
		}
	}

	/**
	 * AJAX handler to assign a ticket.
	 *
	 * @since 1.0.0
	 */
	public function ajax_assign_ticket() {
		check_ajax_referer( 'pnpc_psd_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pnpc_psd_assign_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'pnpc-pocket-service-desk' ) ) );
		}

		$ticket_id   = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
		$assigned_to = isset( $_POST['assigned_to'] ) ? absint( $_POST['assigned_to'] ) : 0;

		if ( ! $ticket_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'pnpc-pocket-service-desk' ) ) );
		}

		$result = PNPC_PSD_Ticket::update(
			$ticket_id,
			array( 'assigned_to' => $assigned_to )
		);

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Ticket assigned successfully.', 'pnpc-pocket-service-desk' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to assign ticket.', 'pnpc-pocket-service-desk' ) ) );
		}
	}

	/**
	 * AJAX handler to update ticket status.
	 *
	 * @since 1.0.0
	 */
	public function ajax_update_ticket_status() {
		check_ajax_referer( 'pnpc_psd_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pnpc_psd_respond_to_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'pnpc-pocket-service-desk' ) ) );
		}

		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
		$status    = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! $ticket_id || empty( $status ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'pnpc-pocket-service-desk' ) ) );
		}

		$result = PNPC_PSD_Ticket::update(
			$ticket_id,
			array( 'status' => $status )
		);

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Ticket status updated successfully.', 'pnpc-pocket-service-desk' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update ticket status.', 'pnpc-pocket-service-desk' ) ) );
		}
	}

	/**
	 * AJAX handler to delete a ticket.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_ticket() {
		check_ajax_referer( 'pnpc_psd_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'pnpc_psd_delete_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'pnpc-pocket-service-desk' ) ) );
		}

		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;

		if ( ! $ticket_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'pnpc-pocket-service-desk' ) ) );
		}

		$result = PNPC_PSD_Ticket::delete( $ticket_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Ticket deleted successfully.', 'pnpc-pocket-service-desk' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete ticket.', 'pnpc-pocket-service-desk' ) ) );
		}
	}

	/**
	 * Check if current page is plugin page.
	 *
	 * @since  1.0.0
	 * @return bool True if plugin page, false otherwise.
	 */
	private function is_plugin_page() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		return strpos( $screen->id, 'pnpc-service-desk' ) !== false;
	}
}
