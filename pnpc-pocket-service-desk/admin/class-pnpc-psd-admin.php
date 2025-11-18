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

			// Settings page - register and display
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
		if ($this->is_plugin_page()) {
			wp_enqueue_script(
				$this->plugin_name,
				PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-admin.js',
				array('jquery'),
				$this->version,
				false
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
		add_menu_page(
			__('Service Desk', 'pnpc-pocket-service-desk'),
			__('Service Desk', 'pnpc-pocket-service-desk'),
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

		$args = array(
			'status' => $status,
			'limit'  => 20,
		);

		$tickets      = PNPC_PSD_Ticket::get_all($args);
		$open_count   = PNPC_PSD_Ticket::get_count('open');
		$closed_count = PNPC_PSD_Ticket::get_count('closed');

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

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings()
	{
		// Email notifications (now an email input)
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_email_notifications',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => '',
			)
		);

		// Existing toggles
		register_setting('pnpc_psd_settings', 'pnpc_psd_auto_assign_tickets');
		register_setting('pnpc_psd_settings', 'pnpc_psd_allowed_file_types');

		// Welcome toggles
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

		// Products toggles
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

		// Color customization settings (hex colors)
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

		// Product card styling
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

		// Keep legacy setting registered for backward compatibility.
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

	// Render the "Assigned Services" field on user profile / edit user screens.
	public function render_user_allocated_products_field($user)
	{
		// Only show to administrators (manage_options).
		if (! current_user_can('manage_options')) {
			return;
		}

		// Fetch allocated products (comma-separated IDs) and convert to array of ints.
		$allocated = get_user_meta($user->ID, 'pnpc_psd_allocated_products', true);
		$selected_ids = array();
		if (! empty($allocated)) {
			$selected_ids = array_filter(array_map('absint', array_map('trim', explode(',', (string) $allocated))));
		}

		// Get published WooCommerce products to populate the select.
		$products = array();
		if (class_exists('WooCommerce')) {
			$products = wc_get_products(array('status' => 'publish', 'limit' => 200)); // limit to 200 for performance; adjust if needed
		}

?>
		<h2><?php esc_html_e('Allocated Products', 'pnpc-pocket-service-desk'); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="pnpc_psd_allocated_products"><?php esc_html_e('Allocated Products', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<?php if (! class_exists('WooCommerce')) : ?>
						<p class="description"><?php esc_html_e('WooCommerce is not active — you cannot allocate products until WooCommerce is installed and activated.', 'pnpc-pocket-service-desk'); ?></p>
					<?php else : ?>
						<select name="pnpc_psd_allocated_products[]" id="pnpc_psd_allocated_products" multiple size="8" style="width:100%;max-width:540px;">
							<?php foreach ($products as $product) :
								$p_id   = (int) $product->get_id();
								$p_name = $product->get_name();
							?>
								<option value="<?php echo esc_attr($p_id); ?>" <?php echo in_array($p_id, $selected_ids, true) ? 'selected' : ''; ?>>
									<?php echo esc_html($p_name . ' (ID: ' . $p_id . ')'); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e('Select one or more products to allocate to this user. Hold Ctrl (Windows) or Cmd (Mac) to select multiple. If you have many products, we can replace this with a search/select UI (select2) later.', 'pnpc-pocket-service-desk'); ?>
						</p>
						<?php wp_nonce_field('pnpc_psd_save_allocated_products', 'pnpc_psd_allocated_products_nonce'); ?>
					<?php endif; ?>
				</td>
			</tr>
		</table>
<?php
	}

	// Save the assigned products when the profile is updated.
	public function save_user_allocated_products($user_id)
	{
		// Only allow administrators to update this field.
		if (! current_user_can('manage_options')) {
			return;
		}

		// Verify nonce (fail closed).
		if (! isset($_POST['pnpc_psd_allocated_products_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['pnpc_psd_allocated_products_nonce']), 'pnpc_psd_save_allocated_products')) {
			return;
		}

		// If the field is not submitted, remove the meta.
		if (! isset($_POST['pnpc_psd_allocated_products'])) {
			delete_user_meta($user_id, 'pnpc_psd_allocated_products');
			return;
		}

		$posted = (array) $_POST['pnpc_psd_allocated_products'];
		// Sanitize to integers and remove empties / duplicates.
		$ids = array_filter(array_map('absint', $posted));
		$ids = array_values(array_unique($ids));

		if (empty($ids)) {
			delete_user_meta($user_id, 'pnpc_psd_allocated_products');
		} else {
			update_user_meta($user_id, 'pnpc_psd_allocated_products', implode(',', $ids));
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
