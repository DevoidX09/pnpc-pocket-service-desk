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
				)
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

	private function is_plugin_page()
	{
		$screen = get_current_screen();
		if (! $screen) {
			return false;
		}

		return strpos($screen->id, 'pnpc-service-desk') !== false;
	}
}
