<?php

/**
 * The core plugin class
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */

class PNPC_PSD
{

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct()
	{
		$this->version     = PNPC_PSD_VERSION;
		$this->plugin_name = 'pnpc-pocket-service-desk';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies()
	{
		require_once PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-loader.php';
		require_once PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-i18n.php';
		require_once PNPC_PSD_PLUGIN_DIR . 'admin/class-pnpc-psd-admin.php';
		require_once PNPC_PSD_PLUGIN_DIR . 'public/class-pnpc-psd-public.php';
		require_once PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-ticket.php';
		require_once PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-ticket-response.php';

		$this->loader = new PNPC_PSD_Loader();
	}

	private function set_locale()
	{
		$plugin_i18n = new PNPC_PSD_i18n();
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	private function define_admin_hooks()
	{
		$plugin_admin = new PNPC_PSD_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
		$this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

		$this->loader->add_action('wp_ajax_pnpc_psd_admin_respond_to_ticket', $plugin_admin, 'ajax_respond_to_ticket');
		$this->loader->add_action('wp_ajax_pnpc_psd_assign_ticket', $plugin_admin, 'ajax_assign_ticket');
		$this->loader->add_action('wp_ajax_pnpc_psd_update_ticket_status', $plugin_admin, 'ajax_update_ticket_status');
		$this->loader->add_action('wp_ajax_pnpc_psd_delete_ticket', $plugin_admin, 'ajax_delete_ticket');
		$this->loader->add_action('wp_ajax_pnpc_psd_bulk_trash_tickets', $plugin_admin, 'ajax_bulk_trash_tickets');
		$this->loader->add_action('wp_ajax_pnpc_psd_bulk_restore_tickets', $plugin_admin, 'ajax_bulk_restore_tickets');
		$this->loader->add_action('wp_ajax_pnpc_psd_bulk_delete_permanently_tickets', $plugin_admin, 'ajax_bulk_delete_permanently_tickets');
	}

	private function define_public_hooks()
	{
		$plugin_public = new PNPC_PSD_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
		$this->loader->add_action('init', $plugin_public, 'register_shortcodes');

		$this->loader->add_action('wp_ajax_pnpc_psd_create_ticket', $plugin_public, 'ajax_create_ticket');
		$this->loader->add_action('wp_ajax_pnpc_psd_respond_to_ticket', $plugin_public, 'ajax_respond_to_ticket');
		$this->loader->add_action('wp_ajax_pnpc_psd_upload_profile_image', $plugin_public, 'ajax_upload_profile_image');
		$this->loader->add_action('wp_ajax_pnpc_psd_get_ticket_detail', $plugin_public, 'ajax_get_ticket_detail');
	}

	public function run()
	{
		$this->loader->run();
	}

	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	public function get_loader()
	{
		return $this->loader;
	}

	public function get_version()
	{
		return $this->version;
	}
}
