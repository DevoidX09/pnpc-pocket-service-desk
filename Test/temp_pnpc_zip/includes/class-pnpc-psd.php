<?php
/**
 * The core plugin class
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/includes
 */
class PNPC_PSD {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    PNPC_PSD_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->version     = PNPC_PSD_VERSION;
		$this->plugin_name = 'pnpc-pocket-service-desk';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		// The class responsible for orchestrating the actions and filters of the core plugin.
		require_once PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-loader.php';

		// The class responsible for defining internationalization functionality.
		require_once PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-i18n.php';

		// The class responsible for defining all actions that occur in the admin area.
		require_once PNPC_PSD_PLUGIN_DIR . 'admin/class-pnpc-psd-admin.php';

		// The class responsible for defining all actions that occur in the public-facing side.
		require_once PNPC_PSD_PLUGIN_DIR . 'public/class-pnpc-psd-public.php';

		// The class responsible for ticket management.
		require_once PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-ticket.php';

		// The class responsible for ticket response management.
		require_once PNPC_PSD_PLUGIN_DIR . 'includes/class-pnpc-psd-ticket-response.php';

		$this->loader = new PNPC_PSD_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {
		$plugin_i18n = new PNPC_PSD_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new PNPC_PSD_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

		// AJAX handlers for admin.
		$this->loader->add_action( 'wp_ajax_pnpc_psd_respond_to_ticket', $plugin_admin, 'ajax_respond_to_ticket' );
		$this->loader->add_action( 'wp_ajax_pnpc_psd_assign_ticket', $plugin_admin, 'ajax_assign_ticket' );
		$this->loader->add_action( 'wp_ajax_pnpc_psd_update_ticket_status', $plugin_admin, 'ajax_update_ticket_status' );
		$this->loader->add_action( 'wp_ajax_pnpc_psd_delete_ticket', $plugin_admin, 'ajax_delete_ticket' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new PNPC_PSD_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		// AJAX handlers for public.
		$this->loader->add_action( 'wp_ajax_pnpc_psd_create_ticket', $plugin_public, 'ajax_create_ticket' );
		$this->loader->add_action( 'wp_ajax_pnpc_psd_respond_to_ticket', $plugin_public, 'ajax_respond_to_ticket' );
		$this->loader->add_action( 'wp_ajax_pnpc_psd_upload_profile_image', $plugin_public, 'ajax_upload_profile_image' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since  1.0.0
	 * @return string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return PNPC_PSD_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
