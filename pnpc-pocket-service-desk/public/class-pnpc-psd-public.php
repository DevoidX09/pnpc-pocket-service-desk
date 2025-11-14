<?php
/**
 * The public-facing functionality of the plugin
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public
 */
class PNPC_PSD_Public {

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
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-public.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-public.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		wp_localize_script(
			$this->plugin_name,
			'pnpcPsdPublic',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'pnpc_psd_public_nonce' ),
			)
		);
	}

	/**
	 * Register shortcodes.
	 *
	 * @since 1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'pnpc_service_desk', array( $this, 'render_service_desk' ) );
		add_shortcode( 'pnpc_create_ticket', array( $this, 'render_create_ticket' ) );
		add_shortcode( 'pnpc_my_tickets', array( $this, 'render_my_tickets' ) );
		add_shortcode( 'pnpc_ticket_detail', array( $this, 'render_ticket_detail' ) );
		add_shortcode( 'pnpc_profile_settings', array( $this, 'render_profile_settings' ) );
	}

	/**
	 * Render service desk shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_service_desk( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to access the service desk.', 'pnpc-pocket-service-desk' ) . '</p>';
		}

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/service-desk.php';
		return ob_get_clean();
	}

	/**
	 * Render create ticket shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_create_ticket( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to create a ticket.', 'pnpc-pocket-service-desk' ) . '</p>';
		}

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/create-ticket.php';
		return ob_get_clean();
	}

	/**
	 * Render my tickets shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_my_tickets( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view your tickets.', 'pnpc-pocket-service-desk' ) . '</p>';
		}

		$current_user = wp_get_current_user();
		$tickets      = PNPC_PSD_Ticket::get_by_user( $current_user->ID );

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/my-tickets.php';
		return ob_get_clean();
	}

	/**
	 * Render ticket detail shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_ticket_detail( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view ticket details.', 'pnpc-pocket-service-desk' ) . '</p>';
		}

		$ticket_id = isset( $_GET['ticket_id'] ) ? absint( $_GET['ticket_id'] ) : 0;

		if ( ! $ticket_id ) {
			return '<p>' . esc_html__( 'Invalid ticket ID.', 'pnpc-pocket-service-desk' ) . '</p>';
		}

		$ticket = PNPC_PSD_Ticket::get( $ticket_id );

		if ( ! $ticket ) {
			return '<p>' . esc_html__( 'Ticket not found.', 'pnpc-pocket-service-desk' ) . '</p>';
		}

		// Check if user owns this ticket or has permission to view it.
		$current_user = wp_get_current_user();
		if ( $ticket->user_id !== $current_user->ID && ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			return '<p>' . esc_html__( 'You do not have permission to view this ticket.', 'pnpc-pocket-service-desk' ) . '</p>';
		}

		$responses = PNPC_PSD_Ticket_Response::get_by_ticket( $ticket_id );

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/ticket-detail.php';
		return ob_get_clean();
	}

	/**
	 * Render profile settings shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_profile_settings( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to access profile settings.', 'pnpc-pocket-service-desk' ) . '</p>';
		}

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/profile-settings.php';
		return ob_get_clean();
	}

	/**
	 * AJAX handler to create a ticket.
	 *
	 * @since 1.0.0
	 */
	public function ajax_create_ticket() {
		check_ajax_referer( 'pnpc_psd_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'pnpc-pocket-service-desk' ) ) );
		}

		$subject     = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$priority    = isset( $_POST['priority'] ) ? sanitize_text_field( wp_unslash( $_POST['priority'] ) ) : 'normal';

		if ( empty( $subject ) || empty( $description ) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'pnpc-pocket-service-desk' ) ) );
		}

		$ticket_id = PNPC_PSD_Ticket::create(
			array(
				'subject'     => $subject,
				'description' => $description,
				'priority'    => $priority,
			)
		);

		if ( $ticket_id ) {
			// Handle file attachments if any.
			if ( ! empty( $_FILES['attachments'] ) && is_array( $_FILES['attachments']['name'] ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';

				$files      = $_FILES['attachments'];
				$file_count = count( $files['name'] );

				for ( $i = 0; $i < $file_count; $i++ ) {
					if ( empty( $files['name'][ $i ] ) ) {
						continue;
					}

					// Validate file size (max 5MB per file).
					if ( $files['size'][ $i ] > 5242880 ) {
						continue; // Skip files over 5MB.
					}

					// Prepare file array for wp_handle_upload.
					$file = array(
						'name'     => $files['name'][ $i ],
						'type'     => $files['type'][ $i ],
						'tmp_name' => $files['tmp_name'][ $i ],
						'error'    => $files['error'][ $i ],
						'size'     => $files['size'][ $i ],
					);

					$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

					if ( ! isset( $upload['error'] ) ) {
						$attachment_id = wp_insert_attachment(
							array(
								'post_title'     => sanitize_file_name( $files['name'][ $i ] ),
								'post_content'   => '',
								'post_status'    => 'inherit',
								'post_mime_type' => $upload['type'],
							),
							$upload['file']
						);

						if ( $attachment_id ) {
							wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
							PNPC_PSD_Ticket::attach_file( $ticket_id, $attachment_id );
						}
					}
				}
			}

			$ticket = PNPC_PSD_Ticket::get( $ticket_id );
			wp_send_json_success(
				array(
					'message'       => __( 'Ticket created successfully.', 'pnpc-pocket-service-desk' ),
					'ticket_number' => $ticket->ticket_number,
					'ticket_id'     => $ticket_id,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to create ticket.', 'pnpc-pocket-service-desk' ) ) );
		}
	}

	/**
	 * AJAX handler to respond to a ticket.
	 *
	 * @since 1.0.0
	 */
	public function ajax_respond_to_ticket() {
		check_ajax_referer( 'pnpc_psd_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'pnpc-pocket-service-desk' ) ) );
		}

		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : 0;
		$response  = isset( $_POST['response'] ) ? wp_kses_post( wp_unslash( $_POST['response'] ) ) : '';

		if ( ! $ticket_id || empty( $response ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'pnpc-pocket-service-desk' ) ) );
		}

		// Verify user owns the ticket or has permission.
		$ticket = PNPC_PSD_Ticket::get( $ticket_id );
		if ( ! $ticket ) {
			wp_send_json_error( array( 'message' => __( 'Ticket not found.', 'pnpc-pocket-service-desk' ) ) );
		}

		$current_user = wp_get_current_user();
		if ( $ticket->user_id !== $current_user->ID && ! current_user_can( 'pnpc_psd_respond_to_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'pnpc-pocket-service-desk' ) ) );
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
	 * AJAX handler to upload profile image.
	 *
	 * @since 1.0.0
	 */
	public function ajax_upload_profile_image() {
		check_ajax_referer( 'pnpc_psd_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'pnpc-pocket-service-desk' ) ) );
		}

		if ( ! isset( $_FILES['profile_image'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'pnpc-pocket-service-desk' ) ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$file = $_FILES['profile_image'];

		// Validate file type.
		$allowed_types = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif' );
		if ( ! in_array( $file['type'], $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid file type. Only JPEG, PNG, and GIF are allowed.', 'pnpc-pocket-service-desk' ) ) );
		}

		// Validate file size (max 2MB).
		if ( $file['size'] > 2097152 ) {
			wp_send_json_error( array( 'message' => __( 'File size must not exceed 2MB.', 'pnpc-pocket-service-desk' ) ) );
		}

		$upload = wp_handle_upload(
			$file,
			array( 'test_form' => false )
		);

		if ( isset( $upload['error'] ) ) {
			wp_send_json_error( array( 'message' => $upload['error'] ) );
		}

		// Save the profile image URL to user meta.
		$current_user = wp_get_current_user();
		update_user_meta( $current_user->ID, 'pnpc_psd_profile_image', $upload['url'] );

		wp_send_json_success(
			array(
				'message' => __( 'Profile image uploaded successfully.', 'pnpc-pocket-service-desk' ),
				'url'     => $upload['url'],
			)
		);
	}
}
