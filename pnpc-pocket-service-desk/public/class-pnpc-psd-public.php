<?php

/**
 * The public-facing functionality of the plugin
 *
 * Full patched version with robust render_ticket_detail, attachment handling,
 * UTC timestamp handling expectations, and AJAX handlers for create/respond/upload.
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public
 */

if (! defined('ABSPATH')) {
	exit;
}

class PNPC_PSD_Public
{
	private $plugin_name;
	private $version;

	public function __construct($plugin_name = 'pnpc-pocket-service-desk', $version = '1.0.0')
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('init', array($this, 'register_shortcodes'));

		// Public AJAX handlers (logged-in)
		add_action('wp_ajax_pnpc_psd_get_ticket_detail', array($this, 'ajax_get_ticket_detail'));
		add_action('wp_ajax_pnpc_psd_respond_to_ticket', array($this, 'ajax_respond_to_ticket'));
		add_action('wp_ajax_pnpc_psd_create_ticket', array($this, 'ajax_create_ticket'));
		add_action('wp_ajax_pnpc_psd_upload_profile_image', array($this, 'ajax_upload_profile_image'));
	}

	public function enqueue_styles()
	{
		wp_enqueue_style(
			$this->plugin_name,
			PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-public.css',
			array(),
			$this->version,
			'all'
		);
		// Inline CSS omitted here for brevity (unchanged from prior)
	}

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

	public function render_service_desk($atts)
	{
		if (! is_user_logged_in()) {
			return '<p>' . esc_html__('Please log in to access the service desk.', 'pnpc-pocket-service-desk') . '</p>';
		}
		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/service-desk.php';
		return ob_get_clean();
	}

	public function render_create_ticket($atts)
	{
		if (! is_user_logged_in()) {
			return '<p>' . esc_html__('Please log in to create a ticket.', 'pnpc-pocket-service-desk') . '</p>';
		}
		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/create-ticket.php';
		return ob_get_clean();
	}

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
	 * Robust render_ticket_detail implementation.
	 */
	public function render_ticket_detail($atts)
	{
		if (! is_user_logged_in()) {
			return '<p>' . esc_html__('Please log in to view ticket details.', 'pnpc-pocket-service-desk') . '</p>';
		}

		// Use numeric ticket id from query
		$ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;
		if (! $ticket_id) {
			return '<p>' . esc_html__('Invalid ticket ID.', 'pnpc-pocket-service-desk') . '</p>';
		}

		$ticket = PNPC_PSD_Ticket::get($ticket_id);
		if (! $ticket) {
			return '<p>' . esc_html__('Ticket not found.', 'pnpc-pocket-service-desk') . '</p>';
		}

		// Ownership check: cast to int to avoid type issues
		$current_user = wp_get_current_user();
		$viewer_id = is_user_logged_in() ? intval($current_user->ID) : 0;
		$ticket_owner_id = isset($ticket->user_id) ? intval($ticket->user_id) : 0;

		// Debug logging when WP_DEBUG
		if (defined('WP_DEBUG') && WP_DEBUG) {
			$log_data = array(
				'action'          => 'render_ticket_detail',
				'ticket_id'       => $ticket_id,
				'ticket_owner_id' => $ticket_owner_id,
				'viewer_id'       => $viewer_id,
				'is_owner'        => ($ticket_owner_id === $viewer_id) ? 1 : 0,
			);
			if (function_exists('pnpc_psd_debug_log')) {
				pnpc_psd_debug_log('render_ticket_detail', $log_data);
			} else {
				error_log('pnpc-psd-debug: ' . print_r($log_data, true));
			}
		}

		// Deny if not owner and does not have the view capability
		if ($ticket_owner_id !== $viewer_id && ! current_user_can('pnpc_psd_view_tickets')) {
			return '<p>' . esc_html__('You do not have permission to view this ticket.', 'pnpc-pocket-service-desk') . '</p>';
		}

		// Get responses
		$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket_id);

		// Update last view meta only when owner views
		if ($viewer_id && $ticket_owner_id === $viewer_id) {
			update_user_meta($viewer_id, 'pnpc_psd_ticket_last_view_' . intval($ticket_id), intval(current_time('timestamp')));
		}

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/ticket-detail.php';
		return ob_get_clean();
	}

	public function render_profile_settings($atts)
	{
		if (! is_user_logged_in()) {
			return '<p>' . esc_html__('Please log in to access profile settings.', 'pnpc-pocket-service-desk') . '</p>';
		}
		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/profile-settings.php';
		return ob_get_clean();
	}

	public function render_services($atts)
	{
		$atts = shortcode_atts(array('limit' => 6), (array) $atts, 'pnpc_services');
		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/services.php';
		return ob_get_clean();
	}

	/**
	 * Normalize uploaded files array (compat fallback).
	 */
	private function normalize_files_array($file_post)
	{
		if (function_exists('pnpc_psd_rearrange_files')) {
			return pnpc_psd_rearrange_files($file_post);
		}
		if (function_exists('reArrayFiles')) {
			return reArrayFiles($file_post);
		}
		$files = array();
		if (! is_array($file_post) || empty($file_post['name'])) {
			return $files;
		}
		if (! is_array($file_post['name'])) {
			return array($file_post);
		}
		$count = count($file_post['name']);
		$keys = array_keys($file_post);
		for ($i = 0; $i < $count; $i++) {
			$item = array();
			foreach ($keys as $k) {
				$item[$k] = isset($file_post[$k][$i]) ? $file_post[$k][$i] : null;
			}
			$files[] = $item;
		}
		return $files;
	}

	public function ajax_create_ticket()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'pnpc_psd_public_nonce') && ! wp_verify_nonce($nonce, 'pnpc_psd_admin_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed. Please refresh and try again.', 'pnpc-pocket-service-desk')));
		}

		if (! is_user_logged_in()) {
			wp_send_json_error(array('message' => __('You must be logged in.', 'pnpc-pocket-service-desk')));
		}

		$current_user = wp_get_current_user();
		$subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
		$description = isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '';
		$priority = isset($_POST['priority']) ? sanitize_text_field(wp_unslash($_POST['priority'])) : 'normal';

		if (empty($subject) || empty($description)) {
			wp_send_json_error(array('message' => __('Please fill in all required fields.', 'pnpc-pocket-service-desk')));
		}

		$attachments = array();
		if (! empty($_FILES) && isset($_FILES['attachments'])) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$files = $this->normalize_files_array($_FILES['attachments']);
			$allowed_mimes = get_option('pnpc_psd_allowed_file_types', 'image/jpeg,image/png,application/pdf');
			$allowed_types = array_map('trim', explode(',', $allowed_mimes));
			foreach ($files as $file) {
				if (empty($file['name'])) {
					continue;
				}
				if (! empty($file['type']) && ! in_array($file['type'], $allowed_types, true)) {
					continue;
				}
				$move = wp_handle_upload($file, array('test_form' => false));
				if (isset($move['error'])) {
					continue;
				}
				$attachments[] = array(
					'file_name' => sanitize_file_name($file['name']),
					'file_path' => $move['url'],
					'file_type' => isset($file['type']) ? $file['type'] : '',
					'file_size' => isset($file['size']) ? intval($file['size']) : 0,
					'uploaded_by' => $current_user->ID,
				);
			}
		}

		$ticket_id = PNPC_PSD_Ticket::create(array(
			'user_id' => $current_user->ID,
			'subject' => $subject,
			'description' => $description,
			'priority' => $priority,
		));

		if (! $ticket_id) {
			wp_send_json_error(array('message' => __('Failed to create ticket.', 'pnpc-pocket-service-desk')));
		}

		if (! empty($attachments)) {
			global $wpdb;
			$attachments_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';
			$created_at_utc = function_exists('pnpc_psd_get_utc_mysql_datetime') ? pnpc_psd_get_utc_mysql_datetime() : current_time('mysql', true);
			foreach ($attachments as $att) {
				$wpdb->insert($attachments_table, array(
					'ticket_id' => $ticket_id,
					'response_id' => null,
					'file_name' => $att['file_name'],
					'file_path' => $att['file_path'],
					'file_type' => $att['file_type'],
					'file_size' => $att['file_size'],
					'uploaded_by' => $att['uploaded_by'],
					'created_at' => $created_at_utc,
				), array('%d', '%s', '%s', '%s', '%d', '%d', '%s'));
			}
		}

		$ticket = PNPC_PSD_Ticket::get($ticket_id);
		$detail_url = function_exists('pnpc_psd_get_ticket_detail_url') ? pnpc_psd_get_ticket_detail_url($ticket_id) : '';

		wp_send_json_success(array(
			'message' => __('Ticket created successfully.', 'pnpc-pocket-service-desk'),
			'ticket_number' => $ticket->ticket_number,
			'ticket_id' => $ticket_id,
			'ticket_detail_url' => $detail_url,
		));
	}

	public function ajax_respond_to_ticket()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'pnpc_psd_public_nonce') && ! wp_verify_nonce($nonce, 'pnpc_psd_admin_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed. Please refresh and try again.', 'pnpc-pocket-service-desk')));
		}

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

		// Robust permission check: ticket owner or capability to respond
		$current_user = wp_get_current_user();
		$viewer_id = is_user_logged_in() ? intval($current_user->ID) : 0;
		$ticket_owner_id = isset($ticket->user_id) ? intval($ticket->user_id) : 0;

		if ($ticket_owner_id !== $viewer_id && ! current_user_can('pnpc_psd_respond_to_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		// Handle attachments same as create
		$attachments = array();
		if (! empty($_FILES) && isset($_FILES['attachments'])) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$files = $this->normalize_files_array($_FILES['attachments']);
			$allowed = get_option('pnpc_psd_allowed_file_types', 'image/jpeg,image/png,application/pdf');
			$allowed_list = array_map('trim', explode(',', $allowed));
			foreach ($files as $file) {
				if (empty($file['name'])) {
					continue;
				}
				if (! empty($file['type']) && ! in_array($file['type'], $allowed_list, true)) {
					continue;
				}
				$move = wp_handle_upload($file, array('test_form' => false));
				if (isset($move['error'])) {
					continue;
				}
				$attachments[] = array(
					'file_name'   => sanitize_file_name($file['name']),
					'file_path'   => $move['url'],
					'file_type'   => isset($file['type']) ? $file['type'] : '',
					'file_size'   => isset($file['size']) ? intval($file['size']) : 0,
					'uploaded_by' => $viewer_id,
				);
			}
		}

		$response_id = PNPC_PSD_Ticket_Response::create(array(
			'ticket_id' => $ticket_id,
			'user_id' => $viewer_id,
			'response' => $response,
			'attachments' => $attachments,
		));

		if ($response_id) {
			wp_send_json_success(array('message' => __('Response added successfully.', 'pnpc-pocket-service-desk')));
		}
		wp_send_json_error(array('message' => __('Failed to add response.', 'pnpc-pocket-service-desk')));
	}

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

		wp_send_json_success(array('message' => __('Profile image uploaded successfully.', 'pnpc-pocket-service-desk'), 'url' => $upload['url']));
	}
}
