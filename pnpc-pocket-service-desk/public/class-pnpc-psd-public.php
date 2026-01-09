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
	private $plugin_name;
	private $version;

	public function __construct($plugin_name = 'pnpc-pocket-service-desk', $version = '1.0.0')
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
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

		// Apply admin-configured button colors (including the Profile Settings Logout button).
		// Sanitize/normalize hex colors defensively (prevents empty/invalid values from producing "no-op" CSS).
		$primary          = sanitize_hex_color( (string) get_option('pnpc_psd_primary_button_color', '#2b9f6a') ) ?: '#2b9f6a';
		$primary_hover    = sanitize_hex_color( (string) get_option('pnpc_psd_primary_button_hover_color', '#238a56') ) ?: '#238a56';
		$secondary        = sanitize_hex_color( (string) get_option('pnpc_psd_secondary_button_color', '#6c757d') ) ?: '#6c757d';
		$secondary_hover  = sanitize_hex_color( (string) get_option('pnpc_psd_secondary_button_hover_color', '#5a6268') ) ?: '#5a6268';
		$logout           = sanitize_hex_color( (string) get_option('pnpc_psd_logout_button_color', '#dc3545') ) ?: '#dc3545';
		$logout_hover     = sanitize_hex_color( (string) get_option('pnpc_psd_logout_button_hover_color', '#b02a37') ) ?: '#b02a37';


		// Product/Service card colors ([pnpc_services]).
		$card_bg          = (string) get_option('pnpc_psd_card_bg_color', '#ffffff');
		$card_bg_hover    = (string) get_option('pnpc_psd_card_bg_hover_color', '#f7f9fb');
		$card_title       = (string) get_option('pnpc_psd_card_title_color', '#2271b1');
		$card_title_hover = (string) get_option('pnpc_psd_card_title_hover_color', '#135e96');
		$card_btn         = (string) get_option('pnpc_psd_card_button_color', '#2b9f6a');
		$card_btn_hover   = (string) get_option('pnpc_psd_card_button_hover_color', '#238a56');

		// My Tickets card + View Details button colors ([pnpc_my_tickets]).
		$my_card_bg       = (string) get_option('pnpc_psd_my_tickets_card_bg_color', '#ffffff');
		$my_card_bg_hover = (string) get_option('pnpc_psd_my_tickets_card_bg_hover_color', '#f7f9fb');
		$my_view_btn      = (string) get_option('pnpc_psd_my_tickets_view_button_color', '#2b9f6a');
		$my_view_btn_hover= (string) get_option('pnpc_psd_my_tickets_view_button_hover_color', '#238a56');

		$css = '';
		$css .= '.pnpc-psd-button-primary{background:' . esc_attr($primary) . ';border-color:' . esc_attr($primary) . ';color:#fff;}';
		$css .= '.pnpc-psd-button-primary:hover{background:' . esc_attr($primary_hover) . ';border-color:' . esc_attr($primary_hover) . ';color:#fff;}';
		// Use !important for hover state to avoid theme overrides on <button> hover styles.
		$css .= '.pnpc-psd-button-secondary{background:' . esc_attr($secondary) . ';border-color:' . esc_attr($secondary) . ';color:#fff;}';
		$css .= '.pnpc-psd-button-secondary:hover{background:' . esc_attr($secondary_hover) . ' !important;border-color:' . esc_attr($secondary_hover) . ' !important;color:#fff !important;}';
		$css .= '.pnpc-psd-button-logout{background:' . esc_attr($logout) . ';border-color:' . esc_attr($logout) . ';color:#fff;}';
		$css .= '.pnpc-psd-button-logout:hover{background:' . esc_attr($logout_hover) . ';border-color:' . esc_attr($logout_hover) . ';color:#fff;}';


		// [pnpc_services] card + title + button styling.
		$css .= '.pnpc-psd-services .pnpc-psd-service-item{background:' . esc_attr($card_bg) . ';}';
		$css .= '.pnpc-psd-services .pnpc-psd-service-item:hover{background:' . esc_attr($card_bg_hover) . ';}';
		$css .= '.pnpc-psd-services .pnpc-psd-service-title a{color:' . esc_attr($card_title) . ';}';
		$css .= '.pnpc-psd-services .pnpc-psd-service-title a:hover{color:' . esc_attr($card_title_hover) . ';}';
		$css .= '.pnpc-psd-services .pnpc-psd-service-item .pnpc-psd-button{background:' . esc_attr($card_btn) . ';border-color:' . esc_attr($card_btn) . ';color:#fff;}';
		$css .= '.pnpc-psd-services .pnpc-psd-service-item .pnpc-psd-button:hover{background:' . esc_attr($card_btn_hover) . ';border-color:' . esc_attr($card_btn_hover) . ';color:#fff;}';

		// [pnpc_my_tickets] card + View Details button styling.
		$css .= '.pnpc-psd-my-tickets .pnpc-psd-ticket-item{background:' . esc_attr($my_card_bg) . ';}';
		$css .= '.pnpc-psd-my-tickets .pnpc-psd-ticket-item:hover{background:' . esc_attr($my_card_bg_hover) . ';}';
		$css .= '.pnpc-psd-my-tickets .pnpc-psd-my-tickets-view-btn{background:' . esc_attr($my_view_btn) . ';border-color:' . esc_attr($my_view_btn) . ';color:#fff;}';
		$css .= '.pnpc-psd-my-tickets .pnpc-psd-my-tickets-view-btn:hover{background:' . esc_attr($my_view_btn_hover) . ';border-color:' . esc_attr($my_view_btn_hover) . ';color:#fff;}';

		if (! empty($css)) {
			wp_add_inline_style($this->plugin_name, $css);
		}
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
			return '<p>' . esc_html__('Please log in to create a ticket. ', 'pnpc-pocket-service-desk') . '</p>';
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
		$tab          = isset($_GET['pnpc_psd_tab']) ? sanitize_key(wp_unslash($_GET['pnpc_psd_tab'])) : 'open';
		if (! in_array($tab, array('open', 'closed'), true)) {
			$tab = 'open';
		}

		$ticket_args = array();
		if ('closed' === $tab) {
			$ticket_args['status'] = 'closed';
		} else {
			$ticket_args['exclude_statuses'] = array('closed');
		}

		$tickets = PNPC_PSD_Ticket::get_by_user($current_user->ID, $ticket_args);

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/my-tickets.php';
		return ob_get_clean();
	}

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
			return '<p>' .  esc_html__('Ticket not found.', 'pnpc-pocket-service-desk') . '</p>';
		}

		$current_user = wp_get_current_user();
		$viewer_id = is_user_logged_in() ? intval($current_user->ID) : 0;
		$ticket_owner_id = isset($ticket->user_id) ? intval($ticket->user_id) : 0;

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

		if ($ticket_owner_id !== $viewer_id && ! current_user_can('pnpc_psd_view_tickets')) {
			return '<p>' . esc_html__('You do not have permission to view this ticket.', 'pnpc-pocket-service-desk') . '</p>';
		}

		$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket_id);

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
			return '<p>' .  esc_html__('Please log in to access profile settings.', 'pnpc-pocket-service-desk') . '</p>';
		}
		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/profile-settings.php';
		return ob_get_clean();
	}

	public function render_services($atts)
	{
		$atts = shortcode_atts(array('limit' => 4), (array) $atts, 'pnpc_services');
		$pnpc_psd_services_limit = max( 1, absint( $atts['limit'] ) );
		$pnpc_psd_services_page  = isset( $_GET['psd_services_page'] ) ? max( 1, absint( $_GET['psd_services_page'] ) ) : 1;
		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/services.php';
		return ob_get_clean();
	}

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
				$item[$k] = isset($file_post[$k][$i]) ? $file_post[$k][$i] :  null;
			}
			$files[] = $item;
		}
		return $files;
	}

	public function ajax_create_ticket()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'pnpc_psd_public_nonce') && ! wp_verify_nonce($nonce, 'pnpc_psd_admin_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.  Please refresh and try again.', 'pnpc-pocket-service-desk')));
		}

		if (! is_user_logged_in()) {
			wp_send_json_error(array('message' => __('You must be logged in. ', 'pnpc-pocket-service-desk')));
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
					'file_type' => isset($file['type']) ? $file['type'] :  '',
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
			global $wpdb;
			$error_detail = $wpdb->last_error ?  ' DB error: ' . $wpdb->last_error : '';
			error_log('pnpc-psd:  ajax_create_ticket failed to insert ticket.' . $error_detail);
			wp_send_json_error(array('message' => __('Failed to create ticket.  Please try again or contact support.', 'pnpc-pocket-service-desk')));
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


		// Persist any uploaded attachments against this ticket.
		if ( ! empty( $attachments ) && $ticket_id ) {
			global $wpdb;
			$attachments_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';

			foreach ( (array) $attachments as $att ) {
				$att_name = isset( $att['file_name'] ) ? (string) $att['file_name'] : '';
				$att_url  = isset( $att['file_path'] ) ? (string) $att['file_path'] : '';
				if ( '' === $att_name || '' === $att_url ) {
					continue;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$attachments_table,
					array(
						'ticket_id'    => absint( $ticket_id ),
						'response_id'  => null,
						'file_name'    => sanitize_file_name( $att_name ),
						'file_path'    => esc_url_raw( $att_url ),
						'file_type'    => isset( $att['file_type'] ) ? sanitize_text_field( (string) $att['file_type'] ) : '',
						'file_size'    => isset( $att['file_size'] ) ? absint( $att['file_size'] ) : 0,
						'uploaded_by'  => isset( $att['uploaded_by'] ) ? absint( $att['uploaded_by'] ) : absint( $current_user->ID ),
						'created_at'   => current_time( 'mysql', true ),
						'deleted_at'   => null,
					),
					array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
				);
			}
		}
		$ticket = PNPC_PSD_Ticket::get($ticket_id);
		$detail_url = function_exists('pnpc_psd_get_ticket_detail_url') ? pnpc_psd_get_ticket_detail_url($ticket_id) : '';

		wp_send_json_success(array(
			'message' => __('Ticket created successfully. ', 'pnpc-pocket-service-desk'),
			'ticket_number' => $ticket->ticket_number,
			'ticket_id' => $ticket_id,
			'ticket_detail_url' => $detail_url,
		));
	}

	public function ajax_respond_to_ticket()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'pnpc_psd_public_nonce') && ! wp_verify_nonce($nonce, 'pnpc_psd_admin_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.  Please refresh and try again.', 'pnpc-pocket-service-desk')));
		}

		if (! is_user_logged_in()) {
			wp_send_json_error(array('message' => __('You must be logged in. ', 'pnpc-pocket-service-desk')));
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
		$viewer_id = is_user_logged_in() ? intval($current_user->ID) : 0;
		$ticket_owner_id = isset($ticket->user_id) ? intval($ticket->user_id) : 0;

		if ($ticket_owner_id !== $viewer_id && ! current_user_can('pnpc_psd_respond_to_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

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
		wp_send_json_error(array('message' => __('Failed to add response. ', 'pnpc-pocket-service-desk')));
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
			wp_send_json_error(array('message' => __('Invalid file type.  Only JPEG, PNG, and GIF are allowed.', 'pnpc-pocket-service-desk')));
		}

		if ($file['size'] > 2097152) {
			wp_send_json_error(array('message' => __('File size must not exceed 2MB.', 'pnpc-pocket-service-desk')));
		}

		// Prefer WordPress media handling so uploads persist properly and are manageable.
		// This stores an attachment ID (more robust) and also stores the URL for backward compatibility.
		$attachment_id = media_handle_upload('profile_image', 0);
		if (is_wp_error($attachment_id)) {
			wp_send_json_error(array('message' => $attachment_id->get_error_message()));
		}

		$uploaded_url = wp_get_attachment_url($attachment_id);
		if (empty($uploaded_url)) {
			wp_send_json_error(array('message' => __('Upload succeeded but the file URL could not be determined.', 'pnpc-pocket-service-desk')));
		}

		$current_user = wp_get_current_user();
		update_user_meta($current_user->ID, 'pnpc_psd_profile_image_id', intval($attachment_id));
		update_user_meta($current_user->ID, 'pnpc_psd_profile_image', esc_url_raw($uploaded_url));

		wp_send_json_success(array(
			'message' => __('Profile image uploaded successfully.', 'pnpc-pocket-service-desk'),
			'url'     => $uploaded_url,
			'id'      => intval($attachment_id),
		));
	}

	/**
	 * AJAX: Refresh current user's ticket list for [pnpc_my_tickets].
	 */
	public function ajax_refresh_my_tickets()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'pnpc_psd_public_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed. Please refresh and try again.', 'pnpc-pocket-service-desk')));
		}

		if (! is_user_logged_in()) {
			wp_send_json_error(array('message' => __('You must be logged in.', 'pnpc-pocket-service-desk')));
		}

		$current_user = wp_get_current_user();
		$tab          = isset($_POST['tab']) ? sanitize_key(wp_unslash($_POST['tab'])) : 'open';
		if (! in_array($tab, array('open', 'closed'), true)) {
			$tab = 'open';
		}

		$ticket_args = array();
		if ('closed' === $tab) {
			$ticket_args['status'] = 'closed';
		} else {
			$ticket_args['exclude_statuses'] = array('closed');
		}

		$tickets = PNPC_PSD_Ticket::get_by_user($current_user->ID, $ticket_args);

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/my-tickets-list.php';
		$html = ob_get_clean();

		wp_send_json_success(array('html' => $html));
	}

	public function ajax_get_ticket_detail()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'pnpc_psd_public_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed. ', 'pnpc-pocket-service-desk')));
		}

		if (! is_user_logged_in()) {
			wp_send_json_error(array('message' => __('You must be logged in.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
		if (! $ticket_id) {
			wp_send_json_error(array('message' => __('Invalid ticket ID.', 'pnpc-pocket-service-desk')));
		}

		$ticket = PNPC_PSD_Ticket::get($ticket_id);
		if (! $ticket) {
			wp_send_json_error(array('message' => __('Ticket not found.', 'pnpc-pocket-service-desk')));
		}

		$current_user = wp_get_current_user();
		$viewer_id = intval($current_user->ID);
		$ticket_owner_id = intval($ticket->user_id);

		if ($ticket_owner_id !== $viewer_id && ! current_user_can('pnpc_psd_view_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket_id);

		wp_send_json_success(array(
			'ticket' => $ticket,
			'responses' => $responses,
		));
	}
}
