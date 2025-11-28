<?php

/**
 * The public-facing functionality of the plugin
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public
 */

if (! defined('ABSPATH')) {
	exit;
}

class PNPC_PSD_Public
{

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
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Enqueue styles and scripts
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		// Register shortcodes on init
		add_action('init', array($this, 'register_shortcodes'));

		// AJAX handlers (logged-in)
		add_action('wp_ajax_pnpc_psd_get_ticket_detail', array($this, 'ajax_get_ticket_detail'));
		add_action('wp_ajax_pnpc_psd_respond_to_ticket', array($this, 'ajax_respond_to_ticket'));
		add_action('wp_ajax_pnpc_psd_create_ticket', array($this, 'ajax_create_ticket'));
		add_action('wp_ajax_pnpc_psd_upload_profile_image', array($this, 'ajax_upload_profile_image'));

		// If you support guest AJAX, add wp_ajax_nopriv_ hooks as needed.
	}

	/**
	 * Enqueue public styles
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style(
			$this->plugin_name,
			PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-public.css',
			array(),
			$this->version,
			'all'
		);

		// Build inline CSS from settings (sanitized)
		$primary       = sanitize_hex_color(get_option('pnpc_psd_primary_button_color', '#2b9f6a'));
		$primary_hover = sanitize_hex_color(get_option('pnpc_psd_primary_button_hover_color', '#238a56'));

		$secondary       = sanitize_hex_color(get_option('pnpc_psd_secondary_button_color', '#6c757d'));
		$secondary_hover = sanitize_hex_color(get_option('pnpc_psd_secondary_button_hover_color', '#5a6268'));

		$card_bg       = sanitize_hex_color(get_option('pnpc_psd_card_bg_color', '#ffffff'));
		$card_bg_hover = sanitize_hex_color(get_option('pnpc_psd_card_bg_hover_color', '#f7f9fb'));

		$card_button       = sanitize_hex_color(get_option('pnpc_psd_card_button_color', '#2b9f6a'));
		$card_button_hover = sanitize_hex_color(get_option('pnpc_psd_card_button_hover_color', '#238a56'));

		$css = "
		/* Dynamic plugin colors */
		.pnpc-psd-button-primary { background: {$primary}; border-color: {$primary}; color: #fff; }
		.pnpc-psd-button-primary:hover { background: {$primary_hover}; border-color: {$primary_hover}; color: #fff; }

		.pnpc-psd-button-secondary { background: {$secondary}; border-color: {$secondary}; color: #fff; }
		.pnpc-psd-button-secondary:hover { background: {$secondary_hover}; border-color: {$secondary_hover}; color: #fff; }

		/* Product card */
		.pnpc-psd-service-card, .pnpc-psd-service-item { background: {$card_bg}; transition: background .15s ease; }
		.pnpc-psd-service-card:hover, .pnpc-psd-service-item:hover { background: {$card_bg_hover}; }

		/* Product card button */
		.pnpc-psd-service-card .pnpc-psd-button, .pnpc-psd-service-item .pnpc-psd-button { background: {$card_button}; border-color: {$card_button}; color: #fff; }
		.pnpc-psd-service-card .pnpc-psd-button:hover, .pnpc-psd-service-item .pnpc-psd-button:hover { background: {$card_button_hover}; border-color: {$card_button_hover}; color: #fff; }
		";

		wp_add_inline_style($this->plugin_name, $css);
	}

	/**
	 * Enqueue public scripts and localize
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script(
			$this->plugin_name,
			PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-public.js',
			array('jquery'),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'pnpcPsdPublic',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce'    => wp_create_nonce('pnpc_psd_public_nonce'),
			)
		);
	}

	/**
	 * Register shortcodes.
	 *
	 * Ensures callbacks exist and are registered on init.
	 */
	public function register_shortcodes()
	{
		$shortcodes = array(
			'pnpc_service_desk'     => 'render_service_desk',
			'pnpc_create_ticket'    => 'render_create_ticket',
			'pnpc_my_tickets'       => 'render_my_tickets',
			'pnpc_ticket_detail'    => 'render_ticket_detail',
			'pnpc_profile_settings' => 'render_profile_settings',
			'pnpc_services'         => 'render_services',
		);

		foreach ($shortcodes as $tag => $method) {
			if (is_callable(array($this, $method))) {
				add_shortcode($tag, array($this, $method));
			}
		}
	}

	/**
	 * Render service desk shortcode.
	 */
	public function render_service_desk($atts)
	{
		if (! is_user_logged_in()) {
			return '<p>' . esc_html__('Please log in to access the service desk.', 'pnpc-pocket-service-desk') . '</p>';
		}

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/service-desk.php';
		return ob_get_clean();
	}

	/**
	 * Render create ticket shortcode.
	 */
	public function render_create_ticket($atts)
	{
		if (! is_user_logged_in()) {
			return '<p>' . esc_html__('Please log in to create a ticket.', 'pnpc-pocket-service-desk') . '</p>';
		}

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/create-ticket.php';
		return ob_get_clean();
	}

	/**
	 * Render my tickets shortcode.
	 */
	public function render_my_tickets($atts)
	{
		if (! is_user_logged_in()) {
			return '<p>' . esc_html__('Please log in to view your tickets.', 'pnpc-pocket-service-desk') . '</p>';
		}

		$current_user = wp_get_current_user();
		$tickets      = PNPC_PSD_Ticket::get_by_user($current_user->ID);

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/my-tickets.php';
		return ob_get_clean();
	}

	/**
	 * Render ticket detail shortcode.
	 */
	public function render_ticket_detail($atts)
	{
		if (! is_user_logged_in()) {
			return '<p>' . esc_html__('Please log in to view ticket details.', 'pnpc-pocket-service-desk') . '</p>';
		}

		$ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;

		if (! $ticket_id) {
			return '<p>' . esc_html__('Invalid ticket ID.', 'pnpc-pocket-service-desk') . '</p>';
		}

		$ticket = PNPC_PSD_Ticket::get($ticket_id);

		if (! $ticket) {
			return '<p>' . esc_html__('Ticket not found.', 'pnpc-pocket-service-desk') . '</p>';
		}

		$current_user = wp_get_current_user();
		if ($ticket->user_id !== $current_user->ID && ! current_user_can('pnpc_psd_view_tickets')) {
			return '<p>' . esc_html__('You do not have permission to view this ticket.', 'pnpc-pocket-service-desk') . '</p>';
		}

		// Mark this ticket as viewed for the current user so public notifications can clear.
		if ( $current_user && ! empty( $current_user->ID ) && (int) $ticket->user_id === (int) $current_user->ID ) {
			update_user_meta(
				(int) $current_user->ID,
				'pnpc_psd_ticket_last_view_customer_' . (int) $ticket_id,
				(int) current_time('timestamp')
			);
		}

		$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket_id);

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/ticket-detail.php';
		return ob_get_clean();
	}

	/**
	 * Render profile settings shortcode.
	 */
	public function render_profile_settings($atts)
	{
		if (! is_user_logged_in()) {
			return '<p>' . esc_html__('Please log in to access profile settings.', 'pnpc-pocket-service-desk') . '</p>';
		}

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/profile-settings.php';
		return ob_get_clean();
	}

	/**
	 * Render services/products shortcode.
	 */
	public function render_services($atts)
	{
		$atts = shortcode_atts(
			array(
				'limit' => 6,
			),
			(array) $atts,
			'pnpc_services'
		);

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/services.php';
		return ob_get_clean();
	}

	/**
	 * AJAX handler to create a ticket.
	 */
	public function ajax_create_ticket()
	{
		check_ajax_referer('pnpc_psd_public_nonce', 'nonce');

		if (! is_user_logged_in()) {
			wp_send_json_error(array('message' => __('You must be logged in.', 'pnpc-pocket-service-desk')));
		}

		$subject     = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
		$description = isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '';
		$priority    = isset($_POST['priority']) ? sanitize_text_field(wp_unslash($_POST['priority'])) : 'normal';

		if (empty($subject) || empty($description)) {
			wp_send_json_error(array('message' => __('Please fill in all required fields.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id = PNPC_PSD_Ticket::create(
			array(
				'subject'     => $subject,
				'description' => $description,
				'priority'    => $priority,
			)
		);

		if ($ticket_id) {
			$ticket = PNPC_PSD_Ticket::get($ticket_id);
			wp_send_json_success(
				array(
					'message'       => __('Ticket created successfully.', 'pnpc-pocket-service-desk'),
					'ticket_number' => $ticket->ticket_number,
					'ticket_id'     => $ticket_id,
				)
			);
		}

		wp_send_json_error(array('message' => __('Failed to create ticket.', 'pnpc-pocket-service-desk')));
	}

	/**
	 * AJAX handler to respond to a ticket.
	 */
	public function ajax_respond_to_ticket()
	{
		check_ajax_referer('pnpc_psd_public_nonce', 'nonce');

		if (! is_user_logged_in()) {
			wp_send_json_error(array('message' => __('You must be logged in.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
		$response  = isset($_POST['response']) ? wp_kses_post(wp_unslash($_POST['response'])) : '';

		if (! $ticket_id || empty($response)) {
			wp_send_json_error(array('message' => __('Invalid data.', 'pnpc-pocket-service-desk')));
		}

		$ticket = PNPC_PSD_Ticket::get($ticket_id);
		if (! $ticket) {
			wp_send_json_error(array('message' => __('Ticket not found.', 'pnpc-pocket-service-desk')));
		}

		$current_user = wp_get_current_user();
		if ($ticket->user_id !== $current_user->ID && ! current_user_can('pnpc_psd_respond_to_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$response_id = PNPC_PSD_Ticket_Response::create(
			array(
				'ticket_id' => $ticket_id,
				'response'  => $response,
			)
		);

		if ($response_id) {
			wp_send_json_success(array('message' => __('Response added successfully.', 'pnpc-pocket-service-desk')));
		}

		wp_send_json_error(array('message' => __('Failed to add response.', 'pnpc-pocket-service-desk')));
	}

	/**
	 * AJAX handler to upload profile image.
	 */
	public function ajax_upload_profile_image()
	{
		check_ajax_referer('pnpc_psd_public_nonce', 'nonce');

		if (! is_user_logged_in()) {
			wp_send_json_error(array('message' => __('You must be logged in.', 'pnpc-pocket-service-desk')));
		}

		if (! isset($_FILES['profile_image'])) {
			wp_send_json_error(array('message' => __('No file uploaded.', 'pnpc-pocket-service-desk')));
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$file = $_FILES['profile_image'];

		$allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
		if (! in_array($file['type'], $allowed_types, true)) {
			wp_send_json_error(array('message' => __('Invalid file type. Only JPEG, PNG, and GIF are allowed.', 'pnpc-pocket-service-desk')));
		}

		if ($file['size'] > 2097152) {
			wp_send_json_error(array('message' => __('File size must not exceed 2MB.', 'pnpc-pocket-service-desk')));
		}

		$upload = wp_handle_upload($file, array('test_form' => false));

		if (isset($upload['error'])) {
			wp_send_json_error(array('message' => $upload['error']));
		}

		$current_user = wp_get_current_user();
		update_user_meta($current_user->ID, 'pnpc_psd_profile_image', $upload['url']);

		wp_send_json_success(
			array(
				'message' => __('Profile image uploaded successfully.', 'pnpc-pocket-service-desk'),
				'url'     => $upload['url'],
			)
		);
	}
}
