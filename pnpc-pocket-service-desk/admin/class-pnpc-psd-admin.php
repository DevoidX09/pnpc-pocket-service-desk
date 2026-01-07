<?php

/**
 * The admin-specific functionality of the plugin
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin
 */

if (! defined('ABSPATH')) {
	exit;
}

class PNPC_PSD_Admin
{

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		if (is_admin()) {
			add_action('show_user_profile', array($this, 'render_user_allocated_products_field'));
			add_action('edit_user_profile', array($this, 'render_user_allocated_products_field'));

			add_action('personal_options_update', array($this, 'save_user_allocated_products'));
			add_action('edit_user_profile_update', array($this, 'save_user_allocated_products'));

			add_action('admin_init', array($this, 'register_settings'));
			add_action('admin_init', array($this, 'process_admin_create_ticket'));
		}
	}

	public function enqueue_styles()
	{
		if ($this->is_plugin_page()) {
			wp_enqueue_style(
				$this->plugin_name,
				PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-admin.css',
				array(),
				$this->version,
				'all'
			);

			// Enqueue attachments viewer CSS
			wp_enqueue_style(
				$this->plugin_name . '-attachments',
				PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-attachments.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	public function enqueue_scripts()
	{
		$force_load = (isset($_GET['page']) && 0 === strpos(sanitize_text_field(wp_unslash($_GET['page'])), 'pnpc-service-desk'));
		if ($this->is_plugin_page() || $force_load) {
			wp_enqueue_script(
				$this->plugin_name,
				PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-admin.js',
				array('jquery'),
				$this->version,
				true
			);

			wp_localize_script(
				$this->plugin_name,
				'pnpcPsdAdmin',
				array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'nonce'    => wp_create_nonce('pnpc_psd_admin_nonce'),
					'tickets_url' => admin_url('admin.php?page=pnpc-service-desk'),
					'i18n' => array(
						'new_ticket_singular' => __('1 new ticket arrived', 'pnpc-pocket-service-desk'),
						'new_tickets_plural' => __('new tickets arrived', 'pnpc-pocket-service-desk'),
					),
				)
			);

			// Enqueue real-time updates script
			wp_enqueue_script(
				$this->plugin_name . '-realtime',
				PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-realtime.js',
				array('jquery'),
				$this->version,
				true
			);

			// Get settings with defaults
			$enable_menu_badge = get_option('pnpc_psd_enable_menu_badge', '1');
			$enable_auto_refresh = get_option('pnpc_psd_enable_auto_refresh', '1');
			$menu_badge_interval = get_option('pnpc_psd_menu_badge_interval', '30');
			$auto_refresh_interval = get_option('pnpc_psd_auto_refresh_interval', '30');

			wp_localize_script(
				$this->plugin_name . '-realtime',
				'pnpcPsdRealtime',
				array(
					'ajaxUrl'              => admin_url('admin-ajax.php'),
					'nonce'                => wp_create_nonce('pnpc_psd_admin_nonce'),
					'enableMenuBadge'      => '1' === $enable_menu_badge,
					'enableAutoRefresh'    => '1' === $enable_auto_refresh,
					'menuBadgeInterval'    => absint($menu_badge_interval),
					'autoRefreshInterval'  => absint($auto_refresh_interval),
				)
			);

			// Enqueue attachments viewer script
			wp_enqueue_script(
				$this->plugin_name . '-attachments',
				PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-attachments.js',
				array('jquery'),
				$this->version,
				true
			);
		}

		// Enqueue Select2 on create ticket page
		// Note 1: $_GET['page'] access is safe here as we're only comparing to a known value
		// for script enqueueing decisions. The value is sanitized before any usage.
		// Note 2: Using CDN for simplicity. For production environments with strict security
		// requirements, consider bundling Select2 locally with SRI integrity hashes.
		if (isset($_GET['page']) && 'pnpc-service-desk-create-ticket' === sanitize_text_field(wp_unslash($_GET['page']))) {
			wp_enqueue_style(
				'select2',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
				array(),
				'4.1.0'
			);

			wp_enqueue_script(
				'select2',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
				array('jquery'),
				'4.1.0',
				true
			);

			wp_add_inline_script(
				'select2',
				'jQuery(document).ready(function($) {
					$("#customer_id").select2({
						placeholder: "' . esc_js(__('Search for a customer...', 'pnpc-pocket-service-desk')) . '",
						allowClear: true,
						width: "100%"
					});
				});'
			);
		}
	}

	public function add_plugin_admin_menu()
	{
		$open_count = 0;
		if (class_exists('PNPC_PSD_Ticket')) {
			$open_count  = (int) PNPC_PSD_Ticket::get_count('open');
			$open_count += (int) PNPC_PSD_Ticket::get_count('in-progress');
		}
		$menu_title = __('Service Desk', 'pnpc-pocket-service-desk');
		if ($open_count > 0) {
			$badge = sprintf(
				'<span class="update-plugins count-%1$d"><span class="plugin-count">%1$d</span></span>',
				absint($open_count)
			);
			$menu_title = sprintf(
				'%1$s %2$s',
				esc_html__('Service Desk', 'pnpc-pocket-service-desk'),
				$badge
			);
		}

		add_menu_page(
			__('Service Desk', 'pnpc-pocket-service-desk'),
			$menu_title,
			'pnpc_psd_view_tickets',
			'pnpc-service-desk',
			array($this, 'display_tickets_page'),
			'dashicons-tickets',
			30
		);

		add_submenu_page(
			'pnpc-service-desk',
			__('All Tickets', 'pnpc-pocket-service-desk'),
			__('All Tickets', 'pnpc-pocket-service-desk'),
			'pnpc_psd_view_tickets',
			'pnpc-service-desk',
			array($this, 'display_tickets_page')
		);

		add_submenu_page(
			'pnpc-service-desk',
			__('Create Ticket', 'pnpc-pocket-service-desk'),
			__('Create Ticket', 'pnpc-pocket-service-desk'),
			'pnpc_psd_view_tickets',
			'pnpc-service-desk-create-ticket',
			array($this, 'display_create_ticket_page')
		);

		add_submenu_page(
			null,
			__('View Ticket', 'pnpc-pocket-service-desk'),
			__('View Ticket', 'pnpc-pocket-service-desk'),
			'pnpc_psd_view_tickets',
			'pnpc-service-desk-ticket',
			array($this, 'display_ticket_detail_page')
		);

		add_submenu_page(
			'pnpc-service-desk',
			__('Settings', 'pnpc-pocket-service-desk'),
			__('Settings', 'pnpc-pocket-service-desk'),
			'pnpc_psd_manage_settings',
			'pnpc-service-desk-settings',
			array($this, 'display_settings_page')
		);
	}

	public function display_tickets_page()
	{
		if (! current_user_can('pnpc_psd_view_tickets')) {
			wp_die(esc_html__('You do not have permission to access this page.', 'pnpc-pocket-service-desk'));
		}

		$status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
		$view   = isset($_GET['view']) ? sanitize_text_field(wp_unslash($_GET['view'])) : '';

		$args = array(
			'limit'  => 20,
		);

		// Check if viewing trash.
		if ('trash' === $view) {
			$tickets = PNPC_PSD_Ticket::get_trashed($args);
		} else {
			$args['status'] = $status;
			$tickets = PNPC_PSD_Ticket::get_all($args);
		}

		$open_count   = PNPC_PSD_Ticket::get_count('open');
		$closed_count = PNPC_PSD_Ticket::get_count('closed');
		$trash_count  = PNPC_PSD_Ticket::get_trashed_count();

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/tickets-list.php';
	}

	public function display_ticket_detail_page()
	{
		if (! current_user_can('pnpc_psd_view_tickets')) {
			wp_die(esc_html__('You do not have permission to access this page.', 'pnpc-pocket-service-desk'));
		}

		$ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;

		if (! $ticket_id) {
			wp_die(esc_html__('Invalid ticket ID.', 'pnpc-pocket-service-desk'));
		}

		$ticket    = PNPC_PSD_Ticket::get($ticket_id);
		$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket_id);

		$current_user = wp_get_current_user();
		if ($current_user && ! empty($current_user->ID)) {
			update_user_meta(
				(int) $current_user->ID,
				'pnpc_psd_ticket_last_view_' . (int) $ticket_id,
				(int) current_time('timestamp')
			);
		}

		if (! $ticket) {
			wp_die(esc_html__('Ticket not found.', 'pnpc-pocket-service-desk'));
		}

		$agents = get_users(
			array(
				'role__in' => array('administrator', 'pnpc_psd_agent', 'pnpc_psd_manager'),
			)
		);

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/ticket-detail.php';
	}

	public function display_settings_page()
	{
		if (! current_user_can('pnpc_psd_manage_settings')) {
			wp_die(esc_html__('You do not have permission to access this page.', 'pnpc-pocket-service-desk'));
		}

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/settings.php';
	}

	public function register_settings()
	{
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_email_notifications',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => '',
			)
		);

		register_setting('pnpc_psd_settings', 'pnpc_psd_auto_assign_tickets');
		register_setting('pnpc_psd_settings', 'pnpc_psd_allowed_file_types');

		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_show_welcome_profile',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'absint',
				'default'           => 1,
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_show_welcome_service_desk',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'absint',
				'default'           => 1,
			)
		);

		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_show_products',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'absint',
				'default'           => 1,
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_user_specific_products',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		// Real-time updates settings
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_enable_menu_badge',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'absint',
				'default'           => 1,
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_menu_badge_interval',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 30,
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_enable_auto_refresh',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'absint',
				'default'           => 1,
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_auto_refresh_interval',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 30,
			)
		);

		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_tickets_per_page',
			array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => 'absint',
			)
		);

		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_primary_button_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#2b9f6a',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_primary_button_hover_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#238a56',
			)
		);

		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_secondary_button_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#6c757d',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_secondary_button_hover_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#5a6268',
			)
		);

		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_card_bg_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#ffffff',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_card_bg_hover_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#f7f9fb',
			)
		);

		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_card_button_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#2b9f6a',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_card_button_hover_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#238a56',
			)
		);

		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_products_premium_only',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
	}

	public function render_user_allocated_products_field($user)
	{
		if (! current_user_can('manage_options')) {
			return;
		}

		$allocated = get_user_meta($user->ID, 'pnpc_psd_allocated_products', true);
		$selected_ids = array();
		if (! empty($allocated)) {
			$selected_ids = array_filter(array_map('absint', array_map('trim', explode(',', (string) $allocated))));
		}

		$products = array();
		if (class_exists('WooCommerce')) {
			$products = wc_get_products(array('status' => 'publish', 'limit' => 200));
		}

?>
		<h2><?php esc_html_e('Allocated Products', 'pnpc-pocket-service-desk'); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="pnpc_psd_allocated_products"><?php esc_html_e('Allocated Products', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<?php if (! class_exists('WooCommerce')) :  ?>
						<p class="description"><?php esc_html_e('WooCommerce is not active — you cannot allocate products until WooCommerce is installed and activated.', 'pnpc-pocket-service-desk'); ?></p>
					<?php else :  ?>
						<select name="pnpc_psd_allocated_products[]" id="pnpc_psd_allocated_products" multiple size="8" style="width: 100%;max-width:540px;">
							<?php foreach ($products as $product) :
								$p_id   = (int) $product->get_id();
								$p_name = $product->get_name();
							?>
								<option value="<?php echo esc_attr($p_id); ?>" <?php echo in_array($p_id, $selected_ids, true) ? 'selected' : ''; ?>>
									<?php echo esc_html($p_name .  ' (ID: ' . $p_id . ')'); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e('Select one or more products to allocate to this user.  Hold Ctrl (Windows) or Cmd (Mac) to select multiple. ', 'pnpc-pocket-service-desk'); ?>
						</p>
						<?php wp_nonce_field('pnpc_psd_save_allocated_products', 'pnpc_psd_allocated_products_nonce'); ?>
					<?php endif; ?>
				</td>
			</tr>
		</table>
<?php
	}

	public function save_user_allocated_products($user_id)
	{
		if (! current_user_can('manage_options')) {
			return;
		}

		if (! isset($_POST['pnpc_psd_allocated_products_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['pnpc_psd_allocated_products_nonce']), 'pnpc_psd_save_allocated_products')) {
			return;
		}

		if (! isset($_POST['pnpc_psd_allocated_products'])) {
			delete_user_meta($user_id, 'pnpc_psd_allocated_products');
			return;
		}

		$posted = (array) $_POST['pnpc_psd_allocated_products'];
		$ids = array_filter(array_map('absint', $posted));
		$ids = array_values(array_unique($ids));

		if (empty($ids)) {
			delete_user_meta($user_id, 'pnpc_psd_allocated_products');
		} else {
			update_user_meta($user_id, 'pnpc_psd_allocated_products', implode(',', $ids));
		}
	}

	public function ajax_respond_to_ticket()
	{
		global $wpdb;

		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_respond_to_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied. ', 'pnpc-pocket-service-desk')));
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

		$attachments = array();
		if (! empty($_FILES) && isset($_FILES['attachments'])) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if (function_exists('pnpc_psd_rearrange_files')) {
				$files = pnpc_psd_rearrange_files($_FILES['attachments']);
			} elseif (function_exists('reArrayFiles')) {
				$files = reArrayFiles($_FILES['attachments']);
			} else {
				$files = array();
				if (is_array($_FILES['attachments']['name'])) {
					$count = count($_FILES['attachments']['name']);
					$keys  = array_keys($_FILES['attachments']);
					for ($i = 0; $i < $count; $i++) {
						$item = array();
						foreach ($keys as $k) {
							$item[$k] = isset($_FILES['attachments'][$k][$i]) ? $_FILES['attachments'][$k][$i] : null;
						}
						$files[] = $item;
					}
				} else {
					$files[] = $_FILES['attachments'];
				}
			}

			$allowed_mimes = get_option('pnpc_psd_allowed_file_types', 'image/jpeg,image/png,application/pdf');
			$allowed_list = array_map('trim', explode(',', $allowed_mimes));

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
					'file_type'   => isset($file['type']) ? $file['type'] :  '',
					'file_size'   => isset($file['size']) ? intval($file['size']) : 0,
					'uploaded_by' => get_current_user_id(),
				);
			}
		}

		$response_id = PNPC_PSD_Ticket_Response::create(
			array(
				'ticket_id'   => $ticket_id,
				'user_id'     => get_current_user_id(),
				'response'    => $response,
				'attachments' => $attachments,
			)
		);

		if ($response_id) {
			wp_send_json_success(array('message' => __('Response added successfully.', 'pnpc-pocket-service-desk')));
		}

		wp_send_json_error(array('message' => __('Failed to add response.', 'pnpc-pocket-service-desk')));
	}

	public function ajax_assign_ticket()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_assign_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id   = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
		$assigned_to = isset($_POST['assigned_to']) ? absint($_POST['assigned_to']) : 0;

		if (! $ticket_id) {
			wp_send_json_error(array('message' => __('Invalid data.', 'pnpc-pocket-service-desk')));
		}

		$result = PNPC_PSD_Ticket::update(
			$ticket_id,
			array('assigned_to' => $assigned_to)
		);

		if ($result) {
			wp_send_json_success(array('message' => __('Ticket assigned successfully. ', 'pnpc-pocket-service-desk')));
		} else {
			wp_send_json_error(array('message' => __('Failed to assign ticket.', 'pnpc-pocket-service-desk')));
		}
	}

	public function ajax_update_ticket_status()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_respond_to_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
		$status    = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

		if (! $ticket_id || empty($status)) {
			wp_send_json_error(array('message' => __('Invalid data. ', 'pnpc-pocket-service-desk')));
		}

		$result = PNPC_PSD_Ticket::update(
			$ticket_id,
			array('status' => $status)
		);

		if ($result) {
			wp_send_json_success(array('message' => __('Ticket status updated successfully. ', 'pnpc-pocket-service-desk')));
		} else {
			wp_send_json_error(array('message' => __('Failed to update status.', 'pnpc-pocket-service-desk')));
		}
	}

	public function ajax_delete_ticket()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_delete_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;

		if (! $ticket_id) {
			wp_send_json_error(array('message' => __('Invalid data. ', 'pnpc-pocket-service-desk')));
		}

		$result = PNPC_PSD_Ticket::delete($ticket_id);

		if ($result) {
			wp_send_json_success(array('message' => __('Ticket deleted. ', 'pnpc-pocket-service-desk')));
		} else {
			wp_send_json_error(array('message' => __('Failed to delete ticket.', 'pnpc-pocket-service-desk')));
		}
	}

	public function ajax_bulk_trash_tickets()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_delete_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids = isset($_POST['ticket_ids']) ? array_map('absint', (array) $_POST['ticket_ids']) : array();

		if (empty($ticket_ids)) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		$count = PNPC_PSD_Ticket::bulk_trash($ticket_ids);

		if ($count > 0) {
			/* translators: %d: number of tickets */
			$message = sprintf(_n('%d ticket moved to trash.', '%d tickets moved to trash.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array('message' => $message, 'count' => $count));
		} else {
			wp_send_json_error(array('message' => __('Failed to move tickets to trash.', 'pnpc-pocket-service-desk')));
		}
	}

	public function ajax_bulk_restore_tickets()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_delete_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids = isset($_POST['ticket_ids']) ? array_map('absint', (array) $_POST['ticket_ids']) : array();

		if (empty($ticket_ids)) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		$count = PNPC_PSD_Ticket::bulk_restore($ticket_ids);

		if ($count > 0) {
			/* translators: %d: number of tickets */
			$message = sprintf(_n('%d ticket restored.', '%d tickets restored.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array('message' => $message, 'count' => $count));
		} else {
			wp_send_json_error(array('message' => __('Failed to restore tickets.', 'pnpc-pocket-service-desk')));
		}
	}

	/**
	 * AJAX handler to trash tickets with a reason.
	 *
	 * @since 1.2.0
	 */
	public function ajax_trash_with_reason()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_delete_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids   = isset($_POST['ticket_ids']) ? array_map('absint', (array) $_POST['ticket_ids']) : array();
		$reason       = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : '';
		$reason_other = isset($_POST['reason_other']) ? sanitize_textarea_field(wp_unslash($_POST['reason_other'])) : '';

		if (empty($ticket_ids)) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		if (empty($reason)) {
			wp_send_json_error(array('message' => __('Please select a reason.', 'pnpc-pocket-service-desk')));
		}

		if ('other' === $reason && strlen($reason_other) < 10) {
			wp_send_json_error(array('message' => __('Please provide more details (at least 10 characters).', 'pnpc-pocket-service-desk')));
		}

		$count = PNPC_PSD_Ticket::bulk_trash_with_reason($ticket_ids, $reason, $reason_other);

		if ($count > 0) {
			/* translators: %d: number of tickets */
			$message = sprintf(_n('%d ticket moved to trash.', '%d tickets moved to trash.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(
				array(
					'message' => $message,
					'count'   => $count,
				)
			);
		} else {
			wp_send_json_error(array('message' => __('Failed to move tickets to trash.', 'pnpc-pocket-service-desk')));
		}
	}

	public function ajax_bulk_delete_permanently_tickets()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_delete_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids = isset($_POST['ticket_ids']) ? array_map('absint', (array) $_POST['ticket_ids']) : array();

		if (empty($ticket_ids)) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		$count = PNPC_PSD_Ticket::bulk_delete_permanently($ticket_ids);

		if ($count > 0) {
			/* translators: %d: number of tickets */
			$message = sprintf(_n('%d ticket permanently deleted.', '%d tickets permanently deleted.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array('message' => $message, 'count' => $count));
		} else {
			wp_send_json_error(array('message' => __('Failed to delete tickets permanently.', 'pnpc-pocket-service-desk')));
		}
	}

	/**
	 * AJAX handler: Get new ticket count for menu badge
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_new_ticket_count()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_view_tickets')) {
			wp_send_json_error(array('message' => __('Insufficient permissions', 'pnpc-pocket-service-desk')));
		}

		$count = $this->get_new_ticket_count_for_user();

		wp_send_json_success(array('count' => $count));
	}

	/**
	 * Get new ticket count for current user (with caching)
	 *
	 * @since 1.0.0
	 * @return int Number of new tickets
	 */
	private function get_new_ticket_count_for_user()
	{
		$user_id = get_current_user_id();
		$transient_key = 'pnpc_psd_new_count_' . $user_id;
		
		// Try to get cached count
		$count = get_transient($transient_key);
		
		if (false === $count) {
			// Query database for count
			$count = $this->query_new_ticket_count($user_id);
			
			// Cache for 10 seconds
			set_transient($transient_key, $count, 10);
		}
		
		return intval($count);
	}

	/**
	 * Query database for new ticket count
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 * @return int Number of new tickets
	 */
	private function query_new_ticket_count($user_id)
	{
		// Count open and in-progress tickets (not closed or trashed)
		// This matches the existing badge logic in add_plugin_admin_menu()
		$open_count = 0;
		if (class_exists('PNPC_PSD_Ticket')) {
			$open_count  = (int) PNPC_PSD_Ticket::get_count('open');
			$open_count += (int) PNPC_PSD_Ticket::get_count('in-progress');
		}
		
		return $open_count;
	}

	/**
	 * AJAX handler: Refresh ticket list
	 *
	 * @since 1.0.0
	 */
	public function ajax_refresh_ticket_list()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_view_tickets')) {
			wp_send_json_error(array('message' => __('Insufficient permissions', 'pnpc-pocket-service-desk')));
		}

		$status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
		$view   = isset($_POST['view']) ? sanitize_text_field(wp_unslash($_POST['view'])) : '';
		$paged  = isset($_POST['paged']) ? absint($_POST['paged']) : 1;
		$current_user_id = get_current_user_id();

		$args = array(
			'limit'  => 20,
		);

		// Check if viewing trash
		$is_trash_view = ('trash' === $view);
		
		if ($is_trash_view) {
			$tickets = PNPC_PSD_Ticket::get_trashed($args);
		} else {
			$args['status'] = $status;
			$tickets = PNPC_PSD_Ticket::get_all($args);
		}
		
		// Calculate badge counts for ALL tickets (not just assigned ones)
		$badge_counts = array();
		
		if (!$is_trash_view) {
			foreach ($tickets as $ticket) {
				$badge_count = $this->calculate_new_badge_count($ticket->id, $current_user_id);
				$badge_counts[$ticket->id] = $badge_count;
			}
		}
		
		// Separate active and closed tickets for proper display
		$active_tickets = array();
		$closed_tickets = array();
		
		if (!$is_trash_view) {
			foreach ($tickets as $ticket) {
				$status_lower = strtolower($ticket->status);
				if ($status_lower === 'closed' || $status_lower === 'resolved') {
					$closed_tickets[] = $ticket;
				} else {
					$active_tickets[] = $ticket;
				}
			}
		}
		
		// Pass pagination info to view
		$_GET['paged'] = $paged;

		// Generate HTML for ticket rows with separation
		ob_start();
		
		if ($is_trash_view) {
			// Trash view - no separation needed
			if (! empty($tickets)) {
				foreach ($tickets as $ticket) {
					$this->render_ticket_row($ticket, $is_trash_view, $badge_counts);
				}
			} else {
				$colspan = current_user_can('pnpc_psd_delete_tickets') ? '6' : '5';
				?>
				<tr>
					<td colspan="<?php echo esc_attr($colspan); ?>">
						<?php esc_html_e('No tickets in trash.', 'pnpc-pocket-service-desk'); ?>
					</td>
				</tr>
				<?php
			}
		} else {
			// Active view - separate active and closed tickets
			$has_active = !empty($active_tickets);
			$has_closed = !empty($closed_tickets);
			
			if (!$has_active && !$has_closed) {
				$colspan = current_user_can('pnpc_psd_delete_tickets') ? '10' : '9';
				?>
				<tr>
					<td colspan="<?php echo esc_attr($colspan); ?>">
						<?php esc_html_e('No tickets found.', 'pnpc-pocket-service-desk'); ?>
					</td>
				</tr>
				<?php
			} else {
				// Render active tickets first
				if ($has_active) {
					foreach ($active_tickets as $ticket) {
						$this->render_ticket_row($ticket, $is_trash_view, $badge_counts);
					}
				}
				
				// Add divider if we have both active and closed
				if ($has_active && $has_closed) {
					$colspan = current_user_can('pnpc_psd_delete_tickets') ? '10' : '9';
					?>
					<tr class="pnpc-psd-closed-divider">
						<td colspan="<?php echo esc_attr($colspan); ?>">
							<div class="pnpc-psd-divider-content">
								<span class="pnpc-psd-divider-line"></span>
								<span class="pnpc-psd-divider-text">
									<?php 
									printf(
										esc_html__('Closed Tickets (%d)', 'pnpc-pocket-service-desk'),
										count($closed_tickets)
									); 
									?>
								</span>
								<span class="pnpc-psd-divider-line"></span>
							</div>
						</td>
					</tr>
					<?php
				}
				
				// Render closed tickets
				if ($has_closed) {
					foreach ($closed_tickets as $ticket) {
						$this->render_ticket_row($ticket, $is_trash_view, $badge_counts, true);
					}
				}
			}
		}
		
		$html = ob_get_clean();

		// Get counts for tabs
		$open_count   = PNPC_PSD_Ticket::get_count('open');
		$closed_count = PNPC_PSD_Ticket::get_count('closed');
		$trash_count  = PNPC_PSD_Ticket::get_trashed_count();

		wp_send_json_success(array(
			'html' => $html,
			'badge_counts' => $badge_counts,
			'counts' => array(
				'open'   => $open_count,
				'closed' => $closed_count,
				'trash'  => $trash_count,
			),
			'debug' => array(
				'total_tickets' => count($tickets),
				'active_tickets' => count($active_tickets),
				'closed_tickets' => count($closed_tickets),
				'badges_calculated' => count($badge_counts),
			),
		));
	}
	
	/**
	 * Calculate "New" badge count for a ticket and specific agent
	 * Centralized logic to ensure consistency
	 *
	 * @since 1.0.0
	 * @param int $ticket_id Ticket ID
	 * @param int $user_id User ID
	 * @return int Number of new responses
	 */
	private function calculate_new_badge_count($ticket_id, $user_id)
	{
		// Get last view timestamp for this user
		$last_view_meta = get_user_meta($user_id, 'pnpc_psd_ticket_last_view_' . intval($ticket_id), true);
		
		if (empty($last_view_meta)) {
			// User has NEVER viewed this ticket
			return 1; // The ticket itself is "new"
		}
		
		// Convert to integer timestamp
		$last_view_time = is_numeric($last_view_meta) 
			? intval($last_view_meta) 
			: strtotime($last_view_meta);
		
		// Get all responses for this ticket
		$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket_id, array('orderby' => 'created_at', 'order' => 'ASC'));
		
		$new_count = 0;
		
		if (!empty($responses)) {
			foreach ($responses as $response) {
				// Skip this user's own responses
				if (intval($response->user_id) === intval($user_id)) {
					continue;
				}
				
				// Convert response timestamp
				$response_time = strtotime($response->created_at);
				
				// Count if response is AFTER last view
				if ($response_time > $last_view_time) {
					$new_count++;
				}
			}
		}
		
		return $new_count;
	}

	/**
	 * Separate tickets into active and closed
	 * Active = open, in-progress, waiting, pending, etc.
	 * Closed = closed, resolved
	 *
	 * @param array $tickets All tickets
	 * @return array ['active' => [], 'closed' => []]
	 */
	private function separate_active_and_closed_tickets($tickets)
	{
		$active = array();
		$closed = array();
		
		foreach ($tickets as $ticket) {
			$status_lower = strtolower($ticket->status);
			if ($status_lower === 'closed' || $status_lower === 'resolved') {
				$closed[] = $ticket;
			} else {
				$active[] = $ticket;
			}
		}
		
		return array(
			'active' => $active,
			'closed' => $closed,
		);
	}

	/**
	 * Render a single ticket row (extracted from tickets-list.php for reuse)
	 *
	 * @since 1.0.0
	 * @param object $ticket Ticket object
	 * @param bool $is_trash_view Whether viewing trash
	 * @param array $badge_counts Pre-calculated badge counts (optional)
	 * @param bool $is_closed Whether this is a closed ticket row
	 */
	private function render_ticket_row($ticket, $is_trash_view = false, $badge_counts = array(), $is_closed = false)
	{
		$user          = get_userdata($ticket->user_id);
		$assigned_user = $ticket->assigned_to ? get_userdata($ticket->assigned_to) : null;
		
		// Extract numeric part from ticket number for sorting
		$ticket_num_for_sort = (int) preg_replace('/[^0-9]/', '', $ticket->ticket_number);
		
		// Status sort order
		$status_order = array('open' => 1, 'in-progress' => 2, 'waiting' => 3, 'closed' => 4);
		$status_sort_value = isset($status_order[$ticket->status]) ? $status_order[$ticket->status] : 999;
		
		// Priority sort order
		$priority_order = array('urgent' => 1, 'high' => 2, 'normal' => 3, 'low' => 4);
		$priority_sort_value = isset($priority_order[$ticket->priority]) ? $priority_order[$ticket->priority] : 999;
		
		// Get timestamp for date sorting
		$created_timestamp = strtotime($ticket->created_at);
		if (false === $created_timestamp) {
			$created_timestamp = 0;
		}
		
		// Use pre-calculated badge count if available, otherwise calculate
		$new_responses = 0;
		if (isset($badge_counts[$ticket->id])) {
			$new_responses = $badge_counts[$ticket->id];
		} else {
			// Fallback calculation (used on initial page load from view)
			$current_admin_id = get_current_user_id();
			if ($current_admin_id && $ticket->assigned_to && (int) $ticket->assigned_to === (int) $current_admin_id) {
				// Use a transient to cache the response count
				$transient_key = 'pnpc_psd_new_resp_' . $ticket->id . '_' . $current_admin_id;
				$cached_count = get_transient($transient_key);
				
				if (false === $cached_count) {
					$last_view_key  = 'pnpc_psd_ticket_last_view_' . (int) $ticket->id;
					$last_view_raw  = get_user_meta($current_admin_id, $last_view_key, true);
					$last_view_time = $last_view_raw ? (int) $last_view_raw : 0;

					// Cache function_exists check
					static $use_helper_func = null;
					if (null === $use_helper_func) {
						$use_helper_func = function_exists('pnpc_psd_mysql_to_wp_local_ts');
					}

					$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket->id);
					if (! empty($responses)) {
						foreach ($responses as $response) {
							if ((int) $response->user_id === (int) $current_admin_id) {
								continue;
							}
							$resp_time = $use_helper_func ? intval(pnpc_psd_mysql_to_wp_local_ts($response->created_at)) : intval(strtotime($response->created_at));
							if ($resp_time > $last_view_time) {
								$new_responses++;
							}
						}
					}
					
					// Cache for 30 seconds
					set_transient($transient_key, $new_responses, 30);
				} else {
					$new_responses = (int) $cached_count;
				}
			}
		}
		
		// Add closed ticket class if applicable
		$row_classes = array();
		if ($is_closed) {
			$row_classes[] = 'pnpc-psd-ticket-closed';
		}
		$row_class_attr = !empty($row_classes) ? ' class="' . esc_attr(implode(' ', $row_classes)) . '"' : '';
		?>
		<tr<?php echo $row_class_attr; ?>>
			<?php if (current_user_can('pnpc_psd_delete_tickets')) : ?>
			<th scope="row" class="check-column">
				<label class="screen-reader-text" for="cb-select-<?php echo absint($ticket->id); ?>">
					<?php
					/* translators: %s: ticket number */
					printf(esc_html__('Select %s', 'pnpc-pocket-service-desk'), esc_html($ticket->ticket_number));
					?>
				</label>
				<input type="checkbox" name="ticket[]" id="cb-select-<?php echo absint($ticket->id); ?>" value="<?php echo absint($ticket->id); ?>">
			</th>
			<?php endif; ?>
			<td data-sort-value="<?php echo absint($ticket_num_for_sort); ?>"><strong><?php echo esc_html($ticket->ticket_number); ?></strong></td>
			<td data-sort-value="<?php echo esc_attr(strtolower($ticket->subject)); ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>">
					<?php echo esc_html($ticket->subject); ?>
				</a>
				<?php if (! empty($ticket->created_by_staff)) : ?>
					<span class="pnpc-psd-badge pnpc-psd-badge-staff-created" title="<?php esc_attr_e('Created by staff', 'pnpc-pocket-service-desk'); ?>">
						<span class="dashicons dashicons-admin-users"></span>
					</span>
				<?php endif; ?>
			</td>
			<td data-sort-value="<?php echo esc_attr(strtolower($user ? $user->display_name : 'zzz_unknown')); ?>"><?php echo $user ? esc_html($user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?></td>
			<td data-sort-value="<?php echo absint($status_sort_value); ?>">
				<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr($ticket->status); ?>">
					<?php echo esc_html(ucfirst($ticket->status)); ?>
				</span>
			</td>
			<td data-sort-value="<?php echo absint($priority_sort_value); ?>">
				<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr($ticket->priority); ?>">
					<?php echo esc_html(ucfirst($ticket->priority)); ?>
				</span>
			</td>
			<td data-sort-value="<?php echo esc_attr(strtolower($assigned_user ? $assigned_user->display_name : 'zzz_unassigned')); ?>"><?php echo $assigned_user ? esc_html($assigned_user->display_name) : esc_html__('Unassigned', 'pnpc-pocket-service-desk'); ?></td>
			<td data-sort-value="<?php echo absint($created_timestamp); ?>">
				<?php
				// Use helper to format DB datetime into WP-localized string
				if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
					echo esc_html(pnpc_psd_format_db_datetime_for_display($ticket->created_at));
				} else {
					echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at)));
				}
				?>
			</td>
			<?php if (! $is_trash_view) : ?>
			<td data-sort-value="<?php echo absint($new_responses); ?>">
				<?php if ($new_responses > 0) : ?>
					<span class="pnpc-psd-new-indicator-badge"><?php echo esc_html($new_responses); ?></span>
				<?php endif; ?>
			</td>
			<?php endif; ?>
			<td>
				<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>" class="button button-small">
					<?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?>
				</a>
			</td>
		</tr>
		<?php
	}

	private function is_plugin_page()
	{
		$screen = get_current_screen();
		if (! $screen) {
			return false;
		}

		return strpos($screen->id, 'pnpc-service-desk') !== false;
	}

	public function display_create_ticket_page()
	{
		if (! current_user_can('pnpc_psd_view_tickets')) {
			wp_die(esc_html__('You do not have permission to access this page.', 'pnpc-pocket-service-desk'));
		}

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/create-ticket-admin.php';
	}

	public function process_admin_create_ticket()
	{
		// Check if form submitted
		if (! isset($_POST['pnpc_psd_create_ticket_nonce'])) {
			return;
		}

		// Verify nonce
		if (! wp_verify_nonce(wp_unslash($_POST['pnpc_psd_create_ticket_nonce']), 'pnpc_psd_create_ticket_admin')) {
			wp_die(esc_html__('Security check failed.', 'pnpc-pocket-service-desk'));
		}

		// Check permissions
		if (! current_user_can('pnpc_psd_view_tickets')) {
			wp_die(esc_html__('Permission denied.', 'pnpc-pocket-service-desk'));
		}

		// Validate and sanitize input
		$customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
		$subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
		$description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
		$priority = isset($_POST['priority']) ? sanitize_text_field(wp_unslash($_POST['priority'])) : 'normal';
		$notify_customer = isset($_POST['notify_customer']);

		// Validate priority against allowed values
		$allowed_priorities = array('low', 'normal', 'high', 'urgent');
		if (! in_array($priority, $allowed_priorities, true)) {
			$priority = 'normal';
		}

		// Validate required fields
		if (! $customer_id || ! $subject || ! $description) {
			add_settings_error(
				'pnpc_psd_messages',
				'pnpc_psd_message',
				__('Please fill in all required fields.', 'pnpc-pocket-service-desk'),
				'error'
			);
			return;
		}

		// Verify customer exists
		$customer = get_userdata($customer_id);
		if (! $customer) {
			add_settings_error(
				'pnpc_psd_messages',
				'pnpc_psd_message',
				__('Invalid customer selected.', 'pnpc-pocket-service-desk'),
				'error'
			);
			return;
		}

		// Create ticket
		$ticket_id = PNPC_PSD_Ticket::create(array(
			'user_id' => $customer_id,
			'subject' => $subject,
			'description' => $description,
			'priority' => $priority,
			'status' => 'open',
			'created_by_staff' => get_current_user_id(),
		));

		if ($ticket_id) {
			// Handle file attachments
			if (!empty($_FILES['attachments']['name'][0])) {
				global $wpdb;
				$attachments_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';
				
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
				
				$files = $_FILES['attachments'];
				$file_count = count($files['name']);
				
				for ($i = 0; $i < $file_count; $i++) {
					// Skip if no file
					if (empty($files['name'][$i])) {
						continue;
					}
					
					// Check file size (5MB limit)
					if ($files['size'][$i] > 5 * 1024 * 1024) {
						add_settings_error(
							'pnpc_psd_messages',
							'pnpc_psd_message',
							sprintf(
								__('File "%s" exceeds 5MB limit and was skipped.', 'pnpc-pocket-service-desk'),
								$files['name'][$i]
							),
							'warning'
						);
						continue;
					}
					
					// Prepare file array for wp_handle_upload
					$file_array = array(
						'name'     => $files['name'][$i],
						'type'     => $files['type'][$i],
						'tmp_name' => $files['tmp_name'][$i],
						'error'    => $files['error'][$i],
						'size'     => $files['size'][$i],
					);
					
					// Upload file
					$upload = wp_handle_upload($file_array, array('test_form' => false));
					
					if (isset($upload['file']) && !isset($upload['error'])) {
						// Insert attachment record into database
						$created_at_utc = function_exists('pnpc_psd_get_utc_mysql_datetime') 
							? pnpc_psd_get_utc_mysql_datetime() 
							: current_time('mysql', true);
						
						$att_data = array(
							'ticket_id'   => absint($ticket_id),
							'response_id' => null,
							'file_name'   => sanitize_file_name($files['name'][$i]),
							'file_path'   => esc_url_raw($upload['url']),
							'file_type'   => sanitize_text_field($upload['type']),
							'file_size'   => intval($files['size'][$i]),
							'uploaded_by' => get_current_user_id(),
							'created_at'  => $created_at_utc,
						);
						
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$wpdb->insert(
							$attachments_table,
							$att_data,
							array('%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
						);
					}
				}
			}
			
			// Send notification if requested
			if ($notify_customer) {
				$this->send_staff_created_ticket_notification($ticket_id, $customer_id);
			}

			// Success message and redirect
			add_settings_error(
				'pnpc_psd_messages',
				'pnpc_psd_message',
				__('Ticket created successfully!', 'pnpc-pocket-service-desk'),
				'success'
			);

			// Redirect to ticket detail
			wp_redirect(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket_id));
			exit;
		} else {
			add_settings_error(
				'pnpc_psd_messages',
				'pnpc_psd_message',
				__('Failed to create ticket. Please try again.', 'pnpc-pocket-service-desk'),
				'error'
			);
		}
	}

	private function send_staff_created_ticket_notification($ticket_id, $customer_id)
	{
		$ticket = PNPC_PSD_Ticket::get($ticket_id);
		$customer = get_userdata($customer_id);
		$staff = wp_get_current_user();

		if (! $ticket || ! $customer) {
			return false;
		}

		$to = $customer->user_email;
		$subject = sprintf(
			/* translators: 1: site name, 2: ticket number */
			__('[%1$s] Support Ticket Created - #%2$s', 'pnpc-pocket-service-desk'),
			get_bloginfo('name'),
			$ticket->ticket_number
		);

		// Try to get customer-facing ticket detail page, fallback to admin URL
		$ticket_detail_page_id = absint(get_option('pnpc_psd_ticket_detail_page_id', 0));
		if ($ticket_detail_page_id > 0 && get_post($ticket_detail_page_id)) {
			$ticket_url = add_query_arg(
				array('ticket_id' => $ticket_id),
				get_permalink($ticket_detail_page_id)
			);
		} else {
			// Fallback to admin URL if no customer portal is configured
			$ticket_url = admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket_id);
		}

		$message = sprintf(
			/* translators: 1: customer name, 2: ticket number, 3: subject, 4: priority, 5: description, 6: ticket URL, 7: staff name, 8: site name */
			__('Hello %1$s,', 'pnpc-pocket-service-desk') . "\n\n" .
			__('A support ticket has been created for you by our support team.', 'pnpc-pocket-service-desk') . "\n\n" .
			__('Ticket Number: %2$s', 'pnpc-pocket-service-desk') . "\n" .
			__('Subject: %3$s', 'pnpc-pocket-service-desk') . "\n" .
			__('Priority: %4$s', 'pnpc-pocket-service-desk') . "\n\n" .
			__('Description:', 'pnpc-pocket-service-desk') . "\n%5$s\n\n" .
			__('You can view and respond to this ticket here:', 'pnpc-pocket-service-desk') . "\n%6$s\n\n" .
			__('Created by: %7$s', 'pnpc-pocket-service-desk') . "\n\n" .
			__('Thank you,', 'pnpc-pocket-service-desk') . "\n" .
			__('%8$s Support Team', 'pnpc-pocket-service-desk'),
			$customer->display_name,
			$ticket->ticket_number,
			$ticket->subject,
			ucfirst($ticket->priority),
			$ticket->description,
			$ticket_url,
			$staff->display_name,
			get_bloginfo('name')
		);

		$headers = array('Content-Type: text/plain; charset=UTF-8');

		return wp_mail($to, $subject, $message, $headers);
	}
}
