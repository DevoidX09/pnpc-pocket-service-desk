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

/**
 * PNPC PSD Admin.
 *
 * @since 1.1.1.4
 */
class PNPC_PSD_Admin
{

	private $plugin_name;
	private $version;

	/**
	* Construct.
	*
	* @param mixed $plugin_name
	* @param mixed $version
	*
	* @since 1.1.1.4
	*
	* @return void
	*/
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Plugin row links (Plugins screen).
		add_filter( 'plugin_action_links_' . PNPC_PSD_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );

		if (is_admin()) {
			// Provide baseline dashboard alerts (filterable).
			add_filter( 'pnpc_psd_dashboard_alerts', array( $this, 'get_default_dashboard_alerts' ) );

			// One-time setup wizard prompt if the customer dashboard page is not configured.
			add_action( 'admin_notices', array( $this, 'maybe_show_setup_wizard_notice' ) );

			// After first activation on a clean install, redirect admins straight into the Setup Wizard.
			add_action( 'admin_init', array( $this, 'maybe_redirect_to_setup_wizard' ), 1 );

			// Setup Wizard: seed sample data when the wizard is completed.
			add_action( 'admin_post_pnpc_psd_setup_finish', array( $this, 'handle_setup_finish' ) );

			// Setup Repair: create/attach required pages without changing existing content.
			add_action( 'admin_post_pnpc_psd_setup_repair', array( $this, 'handle_setup_repair' ) );

			// For non-admin service desk staff, keep wp-admin access but limit menus to reduce confusion.
			add_action('admin_menu', array($this, 'restrict_non_admin_menus'), 999);
			if ( function_exists( 'pnpc_psd_is_pro_active' ) && pnpc_psd_is_pro_active() ) {
				add_action( 'show_user_profile', array( $this, 'render_user_allocated_products_field' ) );
				add_action( 'edit_user_profile', array( $this, 'render_user_allocated_products_field' ) );

				add_action( 'personal_options_update', array( $this, 'save_user_allocated_products' ) );
				add_action( 'edit_user_profile_update', array( $this, 'save_user_allocated_products' ) );
			}
			add_action('admin_init', array($this, 'register_settings'));
			add_action('admin_init', array($this, 'process_admin_create_ticket'));
			add_action('admin_init', array($this, 'process_admin_update_ticket_priority'));
		}
	}

	/**
	 * Redirect into the Setup Wizard once after activation, if this is a clean install.
	 *
	 * @return void
	 */
	public function maybe_redirect_to_setup_wizard() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		if ( ! is_admin() ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pnpc_psd_manage_settings' ) ) {
			return;
		}
		// Do not redirect on bulk activation.
		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only.
			return;
		}

		$do_redirect = (int) get_option( 'pnpc_psd_do_setup_redirect', 0 );
		if ( ! $do_redirect ) {
			return;
		}

		$dash_id = (int) get_option( 'pnpc_psd_dashboard_page_id', 0 );
		if ( $this->is_dashboard_configured( $dash_id ) ) {
			update_option( 'pnpc_psd_do_setup_redirect', 0 );
			return;
		}
// If there is existing ticket history, do not auto-redirect (upgrade / reinstatement).
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		$ticket_count = 0;

		// Determine whether the tickets table exists before counting.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name );
		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$ticket_count = (int) $wpdb->get_var( "SELECT COUNT(1) FROM {$table_name}" );
		}

		if ( $ticket_count > 0 ) {
			update_option( 'pnpc_psd_do_setup_redirect', 0 );
			return;
		}

		update_option( 'pnpc_psd_do_setup_redirect', 0 );
		wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-setup' ) );
		exit;
	}

	/**
	 * Process admin ticket priority updates from the ticket detail screen.
	 *
	 * This is intentionally non-AJAX to keep behavior stable and reviewer-friendly.
	 *
	 * @return void
	 */
	public function process_admin_update_ticket_priority() {
		if ( ! isset( $_POST['pnpc_psd_update_priority_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['pnpc_psd_update_priority_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'pnpc_psd_update_ticket_priority' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'pnpc-pocket-service-desk' ) );
		}

		if ( ! current_user_can( 'pnpc_psd_assign_tickets' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'pnpc-pocket-service-desk' ) );
		}

		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
		$priority  = isset( $_POST['priority'] ) ? sanitize_key( wp_unslash( $_POST['priority'] ) ) : 'normal';
		$allowed   = array( 'low', 'normal', 'high', 'urgent' );
		if ( ! in_array( $priority, $allowed, true ) ) {
			$priority = 'normal';
		}

		$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
		if ( empty( $redirect ) ) {
			$redirect = admin_url( 'admin.php?page=pnpc-service-desk-tickets' );
		}

		if ( $ticket_id && class_exists( 'PNPC_PSD_Ticket' ) ) {
			PNPC_PSD_Ticket::update( $ticket_id, array( 'priority' => $priority ) );
		}

		wp_safe_redirect( $redirect );
		exit;
	}


	/**
	 * Restrict wp-admin menus for non-admin service desk staff (Agent/Manager).
	 * This complements legacy-cap allowances (edit_posts/level_0) used on some sites to permit backend access.
	 *
	 * @return void
	 */
	public function restrict_non_admin_menus() {
		// Only apply to non-admin staff who can view tickets but do not have admin privileges.
		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) || current_user_can( 'manage_options' ) ) {
			return;
		}

		// Remove common core menus. This does not prevent direct URL access if a user somehow gains caps,
		// but it keeps the admin experience focused on Service Desk screens.
		remove_menu_page( 'index.php' ); // Dashboard
		remove_menu_page( 'edit.php' ); // Posts
		remove_menu_page( 'upload.php' ); // Media
		remove_menu_page( 'edit.php?post_type=page' ); // Pages
		remove_menu_page( 'edit-comments.php' ); // Comments
		remove_menu_page( 'themes.php' ); // Appearance
		remove_menu_page( 'plugins.php' ); // Plugins
		remove_menu_page( 'users.php' ); // Users
		remove_menu_page( 'tools.php' ); // Tools
		remove_menu_page( 'options-general.php' ); // Settings

		// Optionally remove profile if you want to keep agents from editing their own profile in wp-admin.
		// remove_menu_page( 'profile.php' );
	}

	/**
	* Enqueue styles.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function enqueue_styles()
	{
		$force_load = ( isset( $_GET['page'] ) && 0 === strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'pnpc-service-desk' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check.

		// Cache-bust admin assets when files change (prevents stale CSS during updates).
		$admin_css_ver  = $this->version;
		$admin_css_path = PNPC_PSD_PLUGIN_DIR . 'assets/css/pnpc-psd-admin.css';
		if ( file_exists( $admin_css_path ) ) {
			$admin_css_ver = (string) filemtime( $admin_css_path );
		}

		if ( $this->is_plugin_page() || $force_load ) {
			wp_enqueue_style(
				$this->plugin_name,
				PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-admin.css',
				array(),
				$admin_css_ver,
				'all'
			);

// Enqueue attachments viewer CSS
			wp_enqueue_style(
				$this->plugin_name . '-attachments',
				PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-attachments.css',
				array(),
				$admin_css_ver,
				'all'
			);

			// Enqueue dashboard CSS on dashboard page
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check.
			if (isset($_GET['page']) && 'pnpc-service-desk' === sanitize_text_field(wp_unslash($_GET['page']))) {
				wp_enqueue_style(
					$this->plugin_name . '-dashboard',
					PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-dashboard.css',
					array(),
					$this->version,
					'all'
				);
			}

			// Enqueue setup wizard CSS on setup wizard page
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check.
			if (isset($_GET['page']) && 'pnpc-service-desk-setup' === sanitize_text_field(wp_unslash($_GET['page']))) {
				wp_enqueue_style(
					$this->plugin_name . '-setup-wizard',
					PNPC_PSD_PLUGIN_URL . 'admin/css/setup-wizard.css',
					array(),
					$this->version,
					'all'
				);
			}
		}
	}

	/**
	* Enqueue scripts.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function enqueue_scripts()
	{
		$force_load = (isset($_GET['page']) && 0 === strpos(sanitize_text_field(wp_unslash($_GET['page'])), 'pnpc-service-desk'));

		// Cache-bust admin assets when files change (prevents stale JS during updates).
		$admin_js_ver  = $this->version;
		$admin_js_path = PNPC_PSD_PLUGIN_DIR . 'assets/js/pnpc-psd-admin.js';
		if ( file_exists( $admin_js_path ) ) {
			$admin_js_ver = (string) filemtime( $admin_js_path );
		}
		if ($this->is_plugin_page() || $force_load) {
			wp_enqueue_script(
				$this->plugin_name,
				PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-admin.js',
				array('jquery'),
				$admin_js_ver,
				true
			);

			wp_localize_script(
				$this->plugin_name,
				'pnpcPsdAdmin',
				array(
					'ajax_url' => admin_url('admin-ajax.php', 'relative'),
					'nonce'    => wp_create_nonce('pnpc_psd_admin_nonce'),
					'tickets_url' => admin_url( 'admin.php?page=pnpc-service-desk-tickets' ),
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
					'ajaxUrl'              => admin_url('admin-ajax.php', 'relative'),
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

			// Enqueue dashboard script on dashboard page
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check.
			if (isset($_GET['page']) && 'pnpc-service-desk' === sanitize_text_field(wp_unslash($_GET['page']))) {
				wp_enqueue_script(
					$this->plugin_name . '-dashboard',
					PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-dashboard.js',
					array(),
					$this->version,
					true
				);
			}

			// Enqueue settings script on settings page
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check.
			if (isset($_GET['page']) && 'pnpc-service-desk-settings' === sanitize_text_field(wp_unslash($_GET['page']))) {
				wp_enqueue_script(
					$this->plugin_name . '-settings',
					PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-settings.js',
					array(),
					$this->version,
					true
				);
			}
		}

		// Enqueue Select2 on create ticket page
		// Note 1: $_GET['page'] access is safe here as we're only comparing to a known value
		// for script enqueueing decisions. The value is sanitized before any usage.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check.
		if (isset($_GET['page']) && 'pnpc-service-desk-create-ticket' === sanitize_text_field(wp_unslash($_GET['page']))) {
			wp_enqueue_style(
				'select2',
				PNPC_PSD_PLUGIN_URL . 'assets/vendor/select2/css/select2.min.css',
				array(),
				'4.1.0'
			);

			wp_enqueue_script(
				'select2',
				PNPC_PSD_PLUGIN_URL . 'assets/vendor/select2/js/select2.min.js',
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

	/**
	* Add plugin admin menu.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function add_plugin_admin_menu()
	{
		$all_count      = PNPC_PSD_Ticket::get_count();
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
			array($this, 'display_dashboard_page'),
			'dashicons-tickets',
			30
		);

		add_submenu_page(
			'pnpc-service-desk',
			esc_html__( 'Dashboard', 'pnpc-pocket-service-desk' ),
			esc_html__( 'Dashboard', 'pnpc-pocket-service-desk' ),
			'pnpc_psd_view_tickets',
			'pnpc-service-desk',
			array( $this, 'display_dashboard_page' )
		);


		add_submenu_page(
			'pnpc-service-desk',
			__('All Tickets', 'pnpc-pocket-service-desk'),
			__('All Tickets', 'pnpc-pocket-service-desk'),
			'pnpc_psd_view_tickets',
			'pnpc-service-desk-tickets',
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

		// Saved Replies is a Pro feature, but the menu slot location is reserved here
		// so ordering remains consistent across Free and Pro.
		if ( function_exists( 'pnpc_psd_is_pro_active' ) && pnpc_psd_is_pro_active() && method_exists( $this, 'display_saved_replies_page' ) ) {
			add_submenu_page(
				'pnpc-service-desk',
				esc_html__( 'Saved Replies', 'pnpc-pocket-service-desk' ),
				esc_html__( 'Saved Replies', 'pnpc-pocket-service-desk' ),
				'pnpc_psd_view_tickets',
				'pnpc-service-desk-saved-replies',
				array( $this, 'display_saved_replies_page' )
			);
		}

		add_submenu_page(
			'pnpc-service-desk',
			esc_html__( 'Setup Wizard', 'pnpc-pocket-service-desk' ),
			esc_html__( 'Setup Wizard', 'pnpc-pocket-service-desk' ),
			'pnpc_psd_manage_settings',
			'pnpc-service-desk-setup',
			array( $this, 'display_setup_wizard_page' )
		);

		

			add_submenu_page(
				'pnpc-service-desk',
				esc_html__( 'Repair Setup', 'pnpc-pocket-service-desk' ),
				esc_html__( 'Repair Setup', 'pnpc-pocket-service-desk' ),
				'pnpc_psd_manage_settings',
				'pnpc-service-desk-repair',
				array( $this, 'display_repair_setup_page' )
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
			esc_html__( 'Audit Log', 'pnpc-pocket-service-desk' ),
			esc_html__( 'Audit Log', 'pnpc-pocket-service-desk' ),
			'pnpc_psd_view_tickets',
			'pnpc-service-desk-audit-log',
			array( $this, 'display_audit_log_page' )
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

	
	/**
	 * Add quick links on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_plugin_action_links( $links ) {
		$dashboard = admin_url( 'admin.php?page=pnpc-service-desk-tickets' );
		$settings  = admin_url( 'admin.php?page=pnpc-service-desk-settings' );

		$custom = array(
			'<a href="' . esc_url( $dashboard ) . '">' . esc_html__( 'Dashboard', 'pnpc-pocket-service-desk' ) . '</a>',
			'<a href="' . esc_url( $settings ) . '">' . esc_html__( 'Settings', 'pnpc-pocket-service-desk' ) . '</a>',
		);

		return array_merge( $custom, (array) $links );
	}

	/**
	 * Display the Service Desk dashboard (admin landing screen).
	 *
	 * @return void
	 */
	public function display_dashboard_page() {
		$stats = $this->get_dashboard_stats();
		include plugin_dir_path( __FILE__ ) . 'views/dashboard.php';
	}

	/**
	 * Display the setup wizard page.
	 *
	 * Wizard creates or links the Support Dashboard page and provides header/menu linking guidance.
	 *
	 * @return void
	 */
	public function display_setup_wizard_page() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pnpc_psd_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pnpc-pocket-service-desk' ) );
		}

		$step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'landing'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only.
		$path = isset( $_GET['path'] ) ? sanitize_key( wp_unslash( $_GET['path'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only.

		$snapshot = $this->get_setup_snapshot();

		// If the user arrives without a path, infer it from the current step.
		if ( empty( $path ) ) {
			$path = in_array( $step, array( 'choose_existing', 'shortcodes' ), true ) ? 'existing' : 'builder';
		}

		// Handle POST actions.
		if ( 'POST' === strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) && isset( $_POST['pnpc_psd_setup_nonce'] ) ) {
			check_admin_referer( 'pnpc_psd_setup_wizard', 'pnpc_psd_setup_nonce' );

			$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';

			if ( 'begin_install' === $mode ) {
				wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=builder&path=builder' ) );
				exit;
			}

			if ( 'use_existing' === $mode ) {
				wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=choose_existing&path=existing' ) );
				exit;
			}

			if ( 'save_existing' === $mode ) {
				$page_id = isset( $_POST['dashboard_page_id'] ) ? absint( wp_unslash( $_POST['dashboard_page_id'] ) ) : 0;
				if ( $page_id > 0 && 'trash' !== get_post_status( $page_id ) ) {
					update_option( 'pnpc_psd_dashboard_page_id', $page_id, false );
					update_option( 'pnpc_psd_setup_completed_at', time(), false );
					wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=shortcodes&path=existing' ) );
					exit;
				}

				update_option( 'pnpc_psd_setup_error', esc_html__( 'Please choose a valid page to use as your dashboard.', 'pnpc-pocket-service-desk' ), false );
				wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=choose_existing&path=existing' ) );
				exit;
			}

			if ( 'confirm_shortcodes' === $mode ) {
				wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=complete&path=existing' ) );
				exit;
			}

			if ( 'create_dashboard' === $mode ) {
				$editor = isset( $_POST['editor'] ) ? sanitize_key( wp_unslash( $_POST['editor'] ) ) : 'block';
				if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
					$editor = 'block';
				}
				update_option( 'pnpc_psd_setup_editor', $editor, false );

				$page_id = $this->create_dashboard_page_from_wizard(
					array(
						'title'  => esc_html__( 'Support Dashboard', 'pnpc-pocket-service-desk' ),
						'slug'   => 'dashboard',
						'editor' => $editor,
					)
				);

				if ( is_wp_error( $page_id ) ) {
					update_option( 'pnpc_psd_setup_error', $page_id->get_error_message(), false );
					wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=builder&path=builder' ) );
					exit;
				}

				update_option( 'pnpc_psd_dashboard_page_id', (int) $page_id, false );
				update_option( 'pnpc_psd_setup_completed_at', time(), false );
				wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=complete&path=builder' ) );
				exit;
			}
		}

		$dashboard_page_id = (int) get_option( 'pnpc_psd_dashboard_page_id', 0 );
		$dashboard_page    = ( $dashboard_page_id > 0 ) ? get_post( $dashboard_page_id ) : null;
		$editor            = (string) get_option( 'pnpc_psd_setup_editor', defined( 'ELEMENTOR_VERSION' ) ? 'elementor' : 'block' );

		include plugin_dir_path( __FILE__ ) . 'views/setup-wizard.php';
	}


	/**
	 * Create a Support Dashboard page for the Setup Wizard.
	 *
	 * Elementor mode uses the bundled JSON template to seed the page builder layout.
	 * Block editor mode uses WordPress shortcode blocks.
	 *
	 * @param array<string,mixed> $args
	 * @return int|WP_Error
	 */
		/**
	 * Build a snapshot of the current site state for the Setup Wizard.
	 *
	 * @return array<string,mixed>
	 */
	
	/**
	 * Determine whether the configured dashboard page is valid for setup purposes.
	 *
	 * A dashboard is considered configured only if:
	 *  - the dashboard page ID option is set and the page exists (not trashed), and
	 *  - the page either contains a known dashboard-related shortcode OR was created by the wizard builder.
	 *
	 * @param int $page_id Dashboard page ID.
	 * @return bool
	 */
	private function is_dashboard_configured( $page_id ) {
		$page_id = absint( $page_id );
		if ( $page_id <= 0 ) {
			return false;
		}

		$status = get_post_status( $page_id );
		if ( empty( $status ) || 'trash' === $status || 'auto-draft' === $status ) {
			return false;
		}

		// Wizard-created pages are always considered valid.
		$created_by_builder = (bool) get_post_meta( $page_id, '_pnpc_psd_created_by_builder', true );
		if ( $created_by_builder ) {
			return true;
		}

		// Otherwise, require at least one expected shortcode to appear in content or Elementor data.
		return $this->page_contains_any_psd_shortcode( $page_id );
	}


/**
 * Determine whether the Ticket View page is properly configured.
 *
 * A Ticket View page is considered configured if:
 * - It exists and is a published page (not trashed), and
 * - It either contains the [pnpc_ticket_view] shortcode, or it was created by the wizard/builder.
 *
 * @param int $page_id Page ID.
 * @return bool
 */
private function is_ticket_view_configured( $page_id ) {
	$page_id = (int) $page_id;
	if ( $page_id <= 0 ) {
		return false;
	}

	$post = get_post( $page_id );
	if ( ! $post || 'page' !== $post->post_type ) {
		return false;
	}

	$status = get_post_status( $page_id );
	if ( ! $status || 'trash' === $status || 'auto-draft' === $status ) {
		return false;
	}

	// Wizard/builder-created pages are always acceptable.
	$created_flag = (int) get_post_meta( $page_id, '_pnpc_psd_created_by_builder', true );
	if ( $created_flag > 0 ) {
		return true;
	}

	$content = (string) $post->post_content;
	if ( false !== strpos( $content, '[pnpc_ticket_view' ) ) {
		return true;
	}

	return false;
}


	/**
	 * Check whether a page contains one of the expected Service Desk shortcodes.
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	private function page_contains_any_psd_shortcode( $page_id ) {
		$page_id = absint( $page_id );
		if ( $page_id <= 0 ) {
			return false;
		}

		$post = get_post( $page_id );
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$haystack = (string) $post->post_content;

		// Elementor stores layout JSON in post meta; include it if present.
		$elementor_data = (string) get_post_meta( $page_id, '_elementor_data', true );
		if ( ! empty( $elementor_data ) ) {
			$haystack .= "\n" . $elementor_data;
		}

		$shortcodes = array(
			'pnpc_profile_settings',
			'pnpc_service_desk',
			'pnpc_create_ticket',
			'pnpc_services',
			'pnpc_my_tickets',
			'pnpc_ticket_view',
		);

		foreach ( $shortcodes as $tag ) {
			if ( false !== strpos( $haystack, '[' . $tag ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return a read-only snapshot of setup state for the Setup Wizard UI.
	 *
	 * IMPORTANT: This method must be side-effect free. Do not create pages, seed data,
	 * or perform redirects from here.
	 *
	 * @return array<string,mixed>
	 */

	/**
	 * Finish setup wizard.
	 *
	 * Creates the Ticket View page (if missing) and seeds a sample customer + sample ticket,
	 * then redirects back to the ticket list.
	 *
	 * @return void
	 */
	public function handle_setup_finish() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pnpc_psd_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this action.', 'pnpc-pocket-service-desk' ) );
		}

		check_admin_referer( 'pnpc_psd_setup_finish', 'pnpc_psd_setup_finish_nonce' );

		$errors = array();

		$ticket_view_id = (int) get_option( 'pnpc_psd_ticket_view_page_id', 0 );

		// If an option is set but points to an invalid/unrelated page, try to discover a valid page by slug, otherwise create it.
		if ( ! $this->is_ticket_view_configured( $ticket_view_id ) ) {
			$existing = get_page_by_path( 'ticket-view' );
			if ( $existing && $this->is_ticket_view_configured( (int) $existing->ID ) ) {
				$ticket_view_id = (int) $existing->ID;
				update_option( 'pnpc_psd_ticket_view_page_id', $ticket_view_id, false );
			} else {
				$editor = (string) get_option( 'pnpc_psd_setup_editor', defined( 'ELEMENTOR_VERSION' ) ? 'elementor' : 'block' );

				$created_id = $this->create_ticket_view_page_from_wizard(
					array(
						'title'  => esc_html__( 'Ticket View', 'pnpc-pocket-service-desk' ),
						'slug'   => 'ticket-view',
						'editor' => $editor,
					)
				);

				if ( is_wp_error( $created_id ) ) {
					$errors[] = $created_id->get_error_message();
				} elseif ( empty( $created_id ) ) {
					$errors[] = esc_html__( 'Ticket View page could not be created. Please try again.', 'pnpc-pocket-service-desk' );
				} else {
					$ticket_view_id = (int) $created_id;
					update_option( 'pnpc_psd_ticket_view_page_id', $ticket_view_id, false );

					// If WordPress had to de-dupe the slug, try to force the desired slug if it is still available.
					$desired = get_page_by_path( 'ticket-view' );
					if ( ! $desired || (int) $desired->ID === $ticket_view_id ) {
						$update = array(
							'ID'        => $ticket_view_id,
							'post_name' => 'ticket-view',
						);
						wp_update_post( $update );
					}
				}
			}
		}

		if ( ! empty( $errors ) ) {
			update_option( 'pnpc_psd_setup_error', implode( "\n", array_map( 'sanitize_text_field', $errors ) ), false );
			wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=complete' ) );
			exit;
		}

		// Seed sample customer + one sample ticket (idempotent by existence, not just a flag).
		$sample_user_id   = (int) get_option( 'pnpc_psd_sample_customer_user_id', 0 );
		$sample_ticket_id = (int) get_option( 'pnpc_psd_sample_ticket_id', 0 );

		// Ensure sample customer exists.
		if ( $sample_user_id <= 0 || ! get_userdata( $sample_user_id ) ) {
			$login = 'pnpc_sample_customer';
			$email = 'sample-customer@example.com';

			if ( username_exists( $login ) ) {
				$login .= '_' . wp_generate_password( 6, false, false );
			}
			if ( email_exists( $email ) ) {
				$email = 'sample-customer+' . wp_generate_password( 6, false, false ) . '@example.com';
			}

			$inserted_user_id = wp_insert_user(
				array(
					'user_login' => $login,
					'user_pass'  => wp_generate_password( 12, false ),
					'user_email' => $email,
					'role'       => 'subscriber',
					'first_name' => 'Sample',
					'last_name'  => 'Customer',
				)
			);

			if ( ! is_wp_error( $inserted_user_id ) ) {
				$sample_user_id = (int) $inserted_user_id;
				update_option( 'pnpc_psd_sample_customer_user_id', $sample_user_id, false );
			} else {
				$sample_user_id = 0;
			}
		}

		// Ensure sample ticket exists (create if missing/deleted).
		if ( $sample_user_id > 0 && class_exists( 'PNPC_PSD_Ticket' ) ) {
			$ticket_exists = false;

			if ( $sample_ticket_id > 0 ) {
				$existing_ticket = PNPC_PSD_Ticket::get( $sample_ticket_id );
				if ( ! empty( $existing_ticket ) && ! empty( $existing_ticket->id ) ) {
					$ticket_exists = true;
				}
			}

			if ( ! $ticket_exists ) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

				// Only attempt to create the sample ticket if the tickets table exists.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$table_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name );

				if ( $table_exists ) {
					$created_ticket_id = PNPC_PSD_Ticket::create(
						array(
							'user_id'     => (int) $sample_user_id,
							'subject'     => esc_html__( 'Sample ticket: Welcome to PNPC Pocket Service Desk', 'pnpc-pocket-service-desk' ),
							'description' => esc_html__( 'This is a sample ticket created by the Setup Wizard. You can delete it at any time.', 'pnpc-pocket-service-desk' ),
							'status'      => 'open',
							'priority'    => 'normal',
						)
					);

					if ( $created_ticket_id ) {
						$sample_ticket_id = (int) $created_ticket_id;
						update_option( 'pnpc_psd_sample_ticket_id', $sample_ticket_id, false );
						update_option( 'pnpc_psd_sample_seeded', 1, false );
					}
				}
			}
		}

		update_option( 'pnpc_psd_needs_setup_wizard', 0 );
		if ( ! (int) get_option( 'pnpc_psd_setup_completed_at', 0 ) ) {
			update_option( 'pnpc_psd_setup_completed_at', time(), false );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-tickets&pnpc_psd_notice=setup_complete' ) );
		exit;
	}

	private function get_setup_snapshot() {
		$dashboard_page_id    = (int) get_option( 'pnpc_psd_dashboard_page_id', 0 );
		$dashboard_configured = $this->is_dashboard_configured( $dashboard_page_id );

		$ticket_count = 0;
		$has_tickets  = false;

		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';

		// Determine whether the tickets table exists before counting.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name );
		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$ticket_count = (int) $wpdb->get_var( "SELECT COUNT(1) FROM {$table_name}" );
			$has_tickets  = ( $ticket_count > 0 );
		}

		$ticket_view_page_id    = (int) get_option( 'pnpc_psd_ticket_view_page_id', 0 );
		$ticket_view_configured = $this->is_ticket_view_configured( $ticket_view_page_id );

		$setup_completed_at = (int) get_option( 'pnpc_psd_setup_completed_at', 0 );

		return array(
			'dashboard_page_id'     => $dashboard_page_id,
			'dashboard_configured'  => $dashboard_configured,
			'ticket_view_page_id'   => $ticket_view_page_id,
			'ticket_view_configured'=> $ticket_view_configured,
			'ticket_count'          => $ticket_count,
			'has_tickets'           => $has_tickets,
			'setup_completed_at'    => $setup_completed_at,
		);
	}

		/**
		 * Render the Repair Setup admin page.
		 *
		 * This is a safe, idempotent action intended to (re)attach or create the required public pages
		 * without modifying existing content or configuration beyond setting missing option values.
		 *
		 * @return void
		 */
		public function display_repair_setup_page() {
			if ( ! current_user_can( 'pnpc_psd_manage_settings' ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'pnpc-pocket-service-desk' ) );
			}

			$snapshot = $this->get_setup_snapshot();

			$notice = isset( $_GET['pnpc_psd_notice'] ) ? sanitize_key( wp_unslash( $_GET['pnpc_psd_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only
			$summary = get_transient( 'pnpc_psd_setup_repair_summary' );
			if ( ! is_array( $summary ) ) {
				$summary = array();
			}

			include plugin_dir_path( __FILE__ ) . 'views/repair-setup.php';
		}

		/**
		 * Handle the "Repair Setup" action.
		 *
		 * @return void
		 */
		public function handle_setup_repair() {
			if ( ! current_user_can( 'pnpc_psd_manage_settings' ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to perform this action.', 'pnpc-pocket-service-desk' ) );
			}

			check_admin_referer( 'pnpc_psd_repair_setup' );

			$summary = array();

			// Dashboard page.
			$dashboard_page_id    = (int) get_option( 'pnpc_psd_dashboard_page_id', 0 );
			$dashboard_configured = $this->is_dashboard_configured( $dashboard_page_id );

			if ( ! $dashboard_configured ) {
				$resolved_id = $this->resolve_existing_dashboard_page_id();
				if ( $resolved_id > 0 ) {
					update_option( 'pnpc_psd_dashboard_page_id', (int) $resolved_id );
					$summary[] = sprintf(
						/* translators: %d: page ID */
						esc_html__( 'Dashboard page linked to page ID %d.', 'pnpc-pocket-service-desk' ),
						(int) $resolved_id
					);
				} else {
					$created = $this->create_dashboard_page_from_wizard(
						array(
							'title'  => esc_html__( 'Support Dashboard', 'pnpc-pocket-service-desk' ),
							'slug'   => 'dashboard',
							'editor' => 'block',
						)
					);

					if ( is_wp_error( $created ) ) {
						$summary[] = esc_html__( 'Dashboard page could not be created (WP error).', 'pnpc-pocket-service-desk' );
					} else {
						update_option( 'pnpc_psd_dashboard_page_id', (int) $created );
						$summary[] = sprintf(
							/* translators: %d: page ID */
							esc_html__( 'Dashboard page created (page ID %d).', 'pnpc-pocket-service-desk' ),
							(int) $created
						);
					}
				}
			} else {
				$summary[] = esc_html__( 'Dashboard page already configured; no changes made.', 'pnpc-pocket-service-desk' );
			}

			// Ticket View page.
			$ticket_view_page_id    = (int) get_option( 'pnpc_psd_ticket_view_page_id', 0 );
			$ticket_view_configured = $this->is_ticket_view_configured( $ticket_view_page_id );

			if ( ! $ticket_view_configured ) {
				$resolved_id = $this->resolve_existing_ticket_view_page_id();
				if ( $resolved_id > 0 ) {
					update_option( 'pnpc_psd_ticket_view_page_id', (int) $resolved_id );
					$summary[] = sprintf(
						/* translators: %d: page ID */
						esc_html__( 'Ticket View page linked to page ID %d.', 'pnpc-pocket-service-desk' ),
						(int) $resolved_id
					);
				} else {
					$created = $this->create_ticket_view_page_from_wizard(
						array(
							'title'  => esc_html__( 'Ticket View', 'pnpc-pocket-service-desk' ),
							'slug'   => 'ticket-view',
							'editor' => ( defined( 'ELEMENTOR_VERSION' ) ? 'elementor' : 'block' ),
						)
					);

					if ( is_wp_error( $created ) ) {
						$summary[] = esc_html__( 'Ticket View page could not be created (WP error).', 'pnpc-pocket-service-desk' );
					} else {
						update_option( 'pnpc_psd_ticket_view_page_id', (int) $created );
						$summary[] = sprintf(
							/* translators: %d: page ID */
							esc_html__( 'Ticket View page created (page ID %d).', 'pnpc-pocket-service-desk' ),
							(int) $created
						);
					}
				}
			} else {
				$summary[] = esc_html__( 'Ticket View page already configured; no changes made.', 'pnpc-pocket-service-desk' );
			}

			set_transient( 'pnpc_psd_setup_repair_summary', $summary, 2 * MINUTE_IN_SECONDS );

			wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-repair&pnpc_psd_notice=repair_complete' ) );
			exit;
		}

		/**
		 * Attempt to find an existing Dashboard page to attach.
		 *
		 * @return int Page ID or 0.
		 */
		private function resolve_existing_dashboard_page_id() {
			// First try by canonical slug.
			$page = get_page_by_path( 'dashboard', OBJECT, 'page' );
			if ( $page instanceof WP_Post ) {
				return (int) $page->ID;
			}

			// Then look for any page that contains one of our dashboard-related shortcodes.
			$candidates = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => array( 'publish', 'private', 'draft' ),
					'posts_per_page' => 25,
					's'              => 'pnpc_service_desk',
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $candidates ) ) {
				foreach ( $candidates as $candidate_id ) {
					if ( $this->page_contains_any_psd_shortcode( (int) $candidate_id ) ) {
						return (int) $candidate_id;
					}
				}
			}

			return 0;
		}

		/**
		 * Attempt to find an existing Ticket View page to attach.
		 *
		 * @return int Page ID or 0.
		 */
		private function resolve_existing_ticket_view_page_id() {
			$page = get_page_by_path( 'ticket-view', OBJECT, 'page' );
			if ( $page instanceof WP_Post ) {
				return (int) $page->ID;
			}

			$candidates = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => array( 'publish', 'private', 'draft' ),
					'posts_per_page' => 25,
					's'              => 'pnpc_ticket_view',
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $candidates ) ) {
				foreach ( $candidates as $candidate_id ) {
					$post = get_post( (int) $candidate_id );
					if ( $post instanceof WP_Post && false !== strpos( (string) $post->post_content, '[pnpc_ticket_view' ) ) {
						return (int) $candidate_id;
					}
				}
			}

			return 0;
		}




function create_dashboard_page_from_wizard( $args ) {
		$title  = isset( $args['title'] ) ? sanitize_text_field( (string) $args['title'] ) : esc_html__( 'Support Dashboard', 'pnpc-pocket-service-desk' );
		$slug   = isset( $args['slug'] ) ? sanitize_title( (string) $args['slug'] ) : 'dashboard';
		$editor = isset( $args['editor'] ) ? sanitize_key( (string) $args['editor'] ) : 'block';

		// Debug output.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'PNPC PSD: Creating dashboard page with args: ' . print_r( $args, true ) ); }
		}

		// Canonical shortcode content (safe fallback).
		$content = "[pnpc_profile_settings]\n\n[pnpc_service_desk]\n\n[pnpc_create_ticket]\n\n[pnpc_services]\n\n[pnpc_my_tickets]";

		// For DIY mode, skip page creation.
		if ( 'diy' === $editor ) {
			return 0;
		}

		// Create page with safe defaults.
		$page_data = array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => get_current_user_id(),
		);

		$page_id = wp_insert_post( $page_data, true );

		if ( is_wp_error( $page_id ) ) {
			return $page_id;
		}

		// Mark as wizard-created so detection remains reliable even if the content is later edited.
		update_post_meta( $page_id, '_pnpc_psd_created_by_builder', 1 );

		// Debug output.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'PNPC PSD: Page created successfully with ID: ' . $page_id ); }
		}

		// Only attempt Elementor if it's active and specifically requested.
		if ( 'elementor' === $editor && defined( 'ELEMENTOR_VERSION' ) && class_exists( '\Elementor\Plugin' ) ) {
			try {
				// Load template JSON.
				$template_path = PNPC_PSD_PLUGIN_DIR . 'admin/assets/dashboard-template-elementor.json';

				if ( file_exists( $template_path ) ) {
					$template_json = file_get_contents( $template_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					
					if ( false === $template_json ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'PNPC PSD: Failed to read Elementor template file' ); }
						}
						return (int) $page_id;
					}
					
					$template_data = json_decode( $template_json, true );

					// Validate JSON structure.
					if ( json_last_error() === JSON_ERROR_NONE && isset( $template_data['content'] ) && is_array( $template_data['content'] ) ) {
						// Apply Elementor template.
						update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
						update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
						update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
						update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $template_data['content'] ) ) );

						// Clear Elementor cache.
						if ( class_exists( '\Elementor\Plugin' ) ) {
							\Elementor\Plugin::$instance->files_manager->clear_cache();
						}
					} else {
						// JSON invalid - fall back to shortcodes.
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'PNPC PSD: Invalid Elementor template JSON, using shortcode fallback' ); }
						}
					}
				} else {
					// Template file missing - fall back to shortcodes.
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'PNPC PSD: Elementor template file not found at ' . $template_path ); }
					}
				}
			} catch ( Exception $e ) {
				// Any error - fall back to shortcodes.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'PNPC PSD: Error loading Elementor template - ' . $e->getMessage() ); }
				}
			}
		}

		// For block editor, ensure content is properly formatted.
		if ( 'block' === $editor ) {
			// Set default page template.
			// Block editor will render the shortcodes in the post_content.
			update_post_meta( $page_id, '_wp_page_template', 'default' );
		}

		return (int) $page_id;
	}

	/**
	 * Create the customer-facing Ticket View page from the wizard (Elementor template if available).
	 *
	 * @param array<string,mixed> $args Args: title, slug, editor.
	 * @return int|WP_Error Page ID on success, WP_Error on failure.
	 */
	function create_ticket_view_page_from_wizard( $args ) {
		$title  = isset( $args['title'] ) ? sanitize_text_field( (string) $args['title'] ) : esc_html__( 'Ticket View', 'pnpc-pocket-service-desk' );
		$slug   = isset( $args['slug'] ) ? sanitize_title( (string) $args['slug'] ) : 'ticket-view';
		$editor = isset( $args['editor'] ) ? sanitize_key( (string) $args['editor'] ) : 'elementor';

		// Default shortcode fallback.
		$content = "[pnpc_ticket_view]";

		$page_data = array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => get_current_user_id(),
		);

		$page_id = wp_insert_post( $page_data, true );
		if ( is_wp_error( $page_id ) ) {
			return $page_id;
		}

		// Mark as wizard-created for reliable detection.
		update_post_meta( $page_id, '_pnpc_psd_created_by_builder', 1 );

		// Apply Elementor template if available and Elementor is active.
		if ( 'elementor' === $editor && defined( 'ELEMENTOR_VERSION' ) ) {
			$template_path = PNPC_PSD_PLUGIN_DIR . 'admin/templates/elementor-ticket-view.json';
			if ( file_exists( $template_path ) ) {
				$template_json = file_get_contents( $template_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( false !== $template_json ) {
					$template_data = json_decode( $template_json, true );
					if ( JSON_ERROR_NONE === json_last_error() && isset( $template_data['content'] ) && is_array( $template_data['content'] ) ) {
						update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
						update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
						update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
						update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $template_data['content'] ) ) );

						if ( class_exists( '\\Elementor\\Plugin' ) ) {
							\Elementor\Plugin::$instance->files_manager->clear_cache();
						}
					}
				}
			}
		}

		return (int) $page_id;
	}



	/**
	 * Get basic ticket statistics for the dashboard.
	 *
	 * Uses created_at for "opened" counts and updated_at for "closed" counts (status='closed').
	 *
	 * @return array<string,mixed>
	 */
	private function get_dashboard_stats() {
		global $wpdb;

		$table = $wpdb->prefix . 'pnpc_psd_tickets';
		$now_ts = current_time( 'timestamp' );

		// Period starts (local WP timezone).
		$week_start  = strtotime( 'monday this week', $now_ts );
		$month_start = strtotime( date( 'Y-m-01 00:00:00', $now_ts ) );
		$year_start  = strtotime( date( 'Y-01-01 00:00:00', $now_ts ) );

				$week_start_dt  = date( 'Y-m-d H:i:s', $week_start );
		$month_start_dt = date( 'Y-m-d H:i:s', $month_start );
		$year_start_dt  = date( 'Y-m-d H:i:s', $year_start );

		$now_dt = current_time( 'mysql' );

		$opened_week  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND created_at >= %s AND created_at <= %s", $week_start_dt, $now_dt ) );
		$opened_month = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND created_at >= %s AND created_at <= %s", $month_start_dt, $now_dt ) );
		$opened_year  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND created_at >= %s AND created_at <= %s", $year_start_dt, $now_dt ) );

		// Status values should be stored as lowercase keys, but some legacy rows may contain mixed case.
		// Use LOWER(status) to produce correct counts without requiring data migrations.
		$closed_week  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND LOWER(status) = 'closed' AND updated_at >= %s AND updated_at <= %s", $week_start_dt, $now_dt ) );
		$closed_month = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND LOWER(status) = 'closed' AND updated_at >= %s AND updated_at <= %s", $month_start_dt, $now_dt ) );
		$closed_year  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND LOWER(status) = 'closed' AND updated_at >= %s AND updated_at <= %s", $year_start_dt, $now_dt ) );

		$open_total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND LOWER(status) <> 'closed'" );
		$closed_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND LOWER(status) = 'closed'" );

		$completion_rate = 0.0;
		$denom = ( $open_total + $closed_total );
		if ( $denom > 0 ) {
			$completion_rate = round( ( $closed_total / $denom ) * 100, 1 );
		}

		return array(
			'opened' => array(
				'week'  => $opened_week,
				'month' => $opened_month,
				'year'  => $opened_year,
			),
			'closed' => array(
				'week'  => $closed_week,
				'month' => $closed_month,
				'year'  => $closed_year,
			),
			'total' => array(
				'open'   => $open_total,
				'closed' => $closed_total,
			),
			'completion_rate' => $completion_rate,
		);
	}

	/**
	 * Provide baseline dashboard alerts.
	 *
	 * @param array $alerts Existing alerts.
	 * @return array Alerts.
	 */
	public function get_default_dashboard_alerts( $alerts ) {
		if ( ! is_array( $alerts ) ) {
			$alerts = array();
		}

		// Review queue: pending delete requests awaiting staff action.
		if ( class_exists( 'PNPC_PSD_Ticket' ) ) {
			$review_count = (int) PNPC_PSD_Ticket::get_pending_delete_count();
			if ( $review_count > 0 ) {
				// Build URL to Review tab
				$review_url = admin_url( 'admin.php?page=pnpc-service-desk-tickets&view=review' );

				$alerts[] = array(
					'title' => __( 'Review queue requires attention', 'pnpc-pocket-service-desk' ),
					/* translators: %d: count */
					'body'  => sprintf( _n( '%d ticket is awaiting review in the Review tab.', '%d tickets are awaiting review in the Review tab.', $review_count, 'pnpc-pocket-service-desk' ), $review_count ),
					'url'   => $review_url,
					'button_text' => __( 'View Review Queue', 'pnpc-pocket-service-desk' ),
				);
			}
		}

		return $alerts;
	}

/**
 * Display tickets page.
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
public function display_tickets_page()
	{
		if (! current_user_can('pnpc_psd_view_tickets')) {
			wp_die(esc_html__('You do not have permission to access this page.', 'pnpc-pocket-service-desk'));
		}

		$status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
		$view   = isset($_GET['view']) ? sanitize_text_field(wp_unslash($_GET['view'])) : '';
		$paged  = isset($_GET['paged']) ? max(1, absint(wp_unslash($_GET['paged']))) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination parameter.

		$per_page = (int) get_option('pnpc_psd_tickets_per_page', 20);
		if ( $per_page < 1 ) {
			$per_page = 20;
		}
		$offset = ( $paged - 1 ) * $per_page;

		$args = array(
			'limit'  => $per_page,
			'offset' => $offset,
		);

		// Check special list views.
		if ('trash' === $view) {
			$tickets = PNPC_PSD_Ticket::get_trashed($args);
		} elseif ('review' === $view) {
			$tickets = PNPC_PSD_Ticket::get_pending_delete($args);
		} elseif ('archived' === $view) {
			$tickets = PNPC_PSD_Ticket::get_archived($args);
		} else {
			$args['status'] = $status;
			$tickets = PNPC_PSD_Ticket::get_all($args);
		}

		$open_count        = PNPC_PSD_Ticket::get_count('open');
		$in_progress_count = PNPC_PSD_Ticket::get_count('in-progress');
		$waiting_count     = PNPC_PSD_Ticket::get_count('waiting');
		$closed_count      = PNPC_PSD_Ticket::get_count('closed');
		$trash_count  = PNPC_PSD_Ticket::get_trashed_count();
		$review_count = PNPC_PSD_Ticket::get_pending_delete_count();
		$archived_count = method_exists('PNPC_PSD_Ticket','get_archived_count') ? PNPC_PSD_Ticket::get_archived_count() : 0;

		$all_count = (int) $open_count + (int) $in_progress_count + (int) $waiting_count + (int) $closed_count;

		// Total for the current view (used by the list template pagination).
		if ( 'trash' === $view ) {
			$total_tickets = (int) $trash_count;
		} elseif ( 'review' === $view ) {
			$total_tickets = (int) $review_count;
		} elseif ( 'archived' === $view ) {
			$total_tickets = (int) $archived_count;
		} elseif ( empty( $status ) || 'all' === $status ) {
			$total_tickets = (int) $all_count;
		} elseif ( 'open' === $status ) {
			$total_tickets = (int) $open_count;
		} elseif ( 'in-progress' === $status ) {
			$total_tickets = (int) $in_progress_count;
		} elseif ( 'waiting' === $status ) {
			$total_tickets = (int) $waiting_count;
		} elseif ( 'closed' === $status ) {
			$total_tickets = (int) $closed_count;
		} else {
			$total_tickets = (int) $all_count;
		}

		// Provide $paged explicitly for the view.
		$paged = (int) $paged;
		include PNPC_PSD_PLUGIN_DIR . 'admin/views/tickets-list.php';
	}

	/**
	 * Display the Audit Log admin page.
	 */
	public function display_audit_log_page() {
		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pnpc-pocket-service-desk' ) );
		}

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/audit-log.php';
	}



	/**
	* Display ticket detail page.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function display_ticket_detail_page()
	{
		if (! current_user_can('pnpc_psd_view_tickets')) {
			wp_die(esc_html__('You do not have permission to access this page.', 'pnpc-pocket-service-desk'));
		}

		$ticket_id = isset($_GET['ticket_id']) ? absint( wp_unslash( $_GET['ticket_id'] ) ) : 0;

		if (! $ticket_id) {
			wp_die(esc_html__('Invalid ticket ID.', 'pnpc-pocket-service-desk'));
		}

		$ticket    = PNPC_PSD_Ticket::get($ticket_id);
		$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket_id);

		$current_user = wp_get_current_user();
		if ($current_user && ! empty($current_user->ID)) {
			if ( class_exists( 'PNPC_PSD_Ticket' ) ) {
				PNPC_PSD_Ticket::mark_staff_viewed( (int) $ticket_id );
			}
			update_user_meta(
				(int) $current_user->ID,
				'pnpc_psd_ticket_last_view_' . (int) $ticket_id,
				(int) current_time('timestamp')
			);
		}

		if (! $ticket) {
			wp_die(esc_html__('Ticket not found.', 'pnpc-pocket-service-desk'));
		}


		// Assignable agents: uses configured list if present, otherwise falls back to staff roles.
		$agents = function_exists( 'pnpc_psd_get_assignable_agents' )
			? pnpc_psd_get_assignable_agents()
			: get_users(
				array(
					'role__in' => ( ( function_exists( 'pnpc_psd_enable_manager_role' ) && pnpc_psd_enable_manager_role() ) ? array( 'administrator', 'pnpc_psd_agent', 'pnpc_psd_manager' ) : array( 'administrator', 'pnpc_psd_agent' ) ),
				)
			);

		// Compute adjacent ticket IDs for Prev/Next navigation.
		global $wpdb;
		$table_name = $wpdb->prefix . 'pnpc_psd_tickets';
		
		// Get previous ticket (by ID, descending).
		$prev_ticket_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE id < %d AND deleted_at IS NULL ORDER BY id DESC LIMIT 1",
				$ticket_id
			)
		);
		$prev_ticket_id = $prev_ticket_id ? absint( $prev_ticket_id ) : 0;
		
		// Get next ticket (by ID, ascending).
		$next_ticket_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE id > %d AND deleted_at IS NULL ORDER BY id ASC LIMIT 1",
				$ticket_id
			)
		);
		$next_ticket_id = $next_ticket_id ? absint( $next_ticket_id ) : 0;

		// Localize ticket-specific data for JS (priority auto-save, etc.)
		wp_localize_script(
			$this->plugin_name,
			'pnpcPsdTicketDetail',
			array(
				'ticketId'    => absint( $ticket_id ),
				'adminNonce'  => wp_create_nonce( 'pnpc_psd_admin_nonce' ),
			)
		);

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/ticket-detail.php';
	}

	/**
	* Display settings page.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function display_settings_page()
	{
		if (! current_user_can('pnpc_psd_manage_settings')) {
			wp_die(esc_html__('You do not have permission to access this page.', 'pnpc-pocket-service-desk'));
		}

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	* Register settings.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function register_settings()
	{
		// Assigned/eligible agents list + per-agent notification emails (stored in option array).
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_agents',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'pnpc_psd_sanitize_agents_option',
				'default'           => array(),
			)
		);

		// Default: disable per-user notify_email overrides (use the user's account email).
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_disable_agent_notify_overrides',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 1,
			)
		);

		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_email_notifications',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => '',
			)
		);

		// Notification switches (v1.1.0): allow tightening behavior without code edits.
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_notify_customer_on_create', array( 'type' => 'boolean', 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_notify_staff_on_create', array( 'type' => 'boolean', 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_notify_customer_on_staff_reply', array( 'type' => 'boolean', 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_notify_staff_on_customer_reply', array( 'type' => 'boolean', 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_notify_customer_on_close', array( 'type' => 'boolean', 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_notify_from_name', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_notify_from_email', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => '' ) );

		// Attachment size limit in MB (v1.1.0). Clamped at runtime by plan.
		register_setting( 'pnpc_psd_settings', 'pnpc_psd_max_attachment_mb', array( 'type' => 'integer', 'sanitize_callback' => 'pnpc_psd_sanitize_max_attachment_mb', 'default' => 5 ) );

		register_setting('pnpc_psd_settings', 'pnpc_psd_auto_assign_tickets');
		// Default agent assignment (staff user ID). 0 = no default.
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_default_agent_user_id',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
		// Allowed attachment types (MIME and/or extensions). Normalize and never store blank.
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_allowed_file_types',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'pnpc_psd_sanitize_allowed_file_types',
				'default'           => 'image/jpeg,image/png,application/pdf',
			)
		);

		// Public login behavior for shortcode pages (dashboard/create/my tickets).
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_public_login_mode',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'pnpc_psd_sanitize_public_login_mode',
				'default'           => 'inline',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_public_login_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'pnpc_psd_sanitize_public_login_url',
				'default'           => '',
			)
		);


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

		// Logout button colors + redirect target for public profile settings.
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_logout_button_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#dc3545',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_logout_button_hover_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#b02a37',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_logout_redirect_page_id',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
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
			'pnpc_psd_card_title_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#2271b1',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_card_title_hover_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#135e96',
			)
		);

		// [pnpc_my_tickets] card + View Details button colors.
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_my_tickets_card_bg_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#ffffff',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_my_tickets_card_bg_hover_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#f7f9fb',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_my_tickets_view_button_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#2b9f6a',
			)
		);
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_my_tickets_view_button_hover_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#238a56',
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

		// Data retention: only delete settings/data on uninstall when explicitly enabled.
		register_setting(
			'pnpc_psd_settings',
			'pnpc_psd_delete_data_on_uninstall',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
	}

	/**
	* Render user allocated products field.
	*
	* @param mixed $user
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
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

	/**
	* Save user allocated products.
	*
	* @param mixed $user_id
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function save_user_allocated_products($user_id)
	{
		if (! current_user_can('manage_options')) {
			return;
		}

		if (! isset($_POST['pnpc_psd_allocated_products_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pnpc_psd_allocated_products_nonce'])), 'pnpc_psd_save_allocated_products')) {
			return;
		}

		if (! isset($_POST['pnpc_psd_allocated_products'])) {
			delete_user_meta($user_id, 'pnpc_psd_allocated_products');
			return;
		}

		$posted = (array) wp_unslash( $_POST['pnpc_psd_allocated_products'] );
		$ids = array_filter(array_map('absint', $posted));
		$ids = array_values(array_unique($ids));

		if (empty($ids)) {
			delete_user_meta($user_id, 'pnpc_psd_allocated_products');
		} else {
			update_user_meta($user_id, 'pnpc_psd_allocated_products', implode(',', $ids));
		}
	}

	/**
	* Ajax respond to ticket.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_respond_to_ticket()
	{
		global $wpdb;

		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_respond_to_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied. ', 'pnpc-pocket-service-desk')));
		}

		$ticket_id = isset($_POST['ticket_id']) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
		$response  = isset($_POST['response']) ? wp_kses_post(wp_unslash($_POST['response'])) : '';

		if (! $ticket_id || empty($response)) {
			wp_send_json_error(array('message' => __('Invalid data.', 'pnpc-pocket-service-desk')));
		}

		$ticket = PNPC_PSD_Ticket::get($ticket_id);
		if (! $ticket) {
			wp_send_json_error(array('message' => __('Ticket not found.', 'pnpc-pocket-service-desk')));
		}

		$attachments = array();
		$att_skipped = array();
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

			$allowed_list = function_exists( 'pnpc_psd_get_allowed_file_types_list' )
				? pnpc_psd_get_allowed_file_types_list()
				: array_map( 'trim', preg_split( '/[s,;]+/', (string) get_option( 'pnpc_psd_allowed_file_types', 'jpg,jpeg,png,gif,webp,pdf,txt,csv,doc,docx,xls,xlsx,zip' ) ) );

			foreach ($files as $file) {
				if (empty($file['name'])) {
					continue;
				}
				// Respect PHP upload error codes first.
				if ( isset( $file['error'] ) && (int) $file['error'] !== UPLOAD_ERR_OK ) {
					$att_skipped[] = array('file' => (string) $file['name'], 'reason' => 'php', 'code' => (int) $file['error']);
					continue;
				}
				// Allowlist supports both MIME strings (image/png) and extensions (png).
				$file_name = (string) $file['name'];
				$file_ext  = strtolower((string) pathinfo($file_name, PATHINFO_EXTENSION));
				$mime      = ! empty($file['type']) ? (string) $file['type'] : '';
				if (! empty($file['tmp_name']) && function_exists('wp_check_filetype_and_ext')) {
					$checked = wp_check_filetype_and_ext($file['tmp_name'], $file_name);
					if (! empty($checked['type'])) {
						$mime = (string) $checked['type'];
					}
				}
				// Normalize common MIME aliases so allowlists don't unexpectedly reject standard files.
				$mime_norm = strtolower( (string) $mime );
				if ( 'image/jpg' === $mime_norm ) {
					$mime_norm = 'image/jpeg';
				}
				if ( 'application/x-pdf' === $mime_norm ) {
					$mime_norm = 'application/pdf';
				}
				$mime = $mime_norm;
				$allowed_ok = false;
				$ext_alias = array(
					'jpg'  => array( 'jpg', 'jpeg', 'jpe' ),
					'jpeg' => array( 'jpg', 'jpeg', 'jpe' ),
					'jpe'  => array( 'jpg', 'jpeg', 'jpe' ),
				);
				foreach ($allowed_list as $allow_item) {
					$allow_item = strtolower(trim((string) $allow_item));
					if ('' === $allow_item) {
						continue;
					}
					if (false !== strpos($allow_item, '/')) {
						if (strtolower($mime) === $allow_item) {
							$allowed_ok = true;
							break;
						}
						if (substr($allow_item, -2) === '/*' && 0 === strpos(strtolower($mime), rtrim($allow_item, '*'))) {
							$allowed_ok = true;
							break;
						}
					} else {
						if ( $file_ext && ( $file_ext === $allow_item || ( isset( $ext_alias[ $allow_item ] ) && in_array( $file_ext, $ext_alias[ $allow_item ], true ) ) ) ) {
							$allowed_ok = true;
							break;
						}
					}
				}
				// If the allowlist is MIME-only, accept well-known extensions that correspond to allowed MIME types.
				if ( ! $allowed_ok && $file_ext ) {
					$mime_to_exts = array(
						'image/jpeg'      => array( 'jpg', 'jpeg', 'jpe' ),
						'image/png'       => array( 'png' ),
						'application/pdf' => array( 'pdf' ),
						'image/gif'       => array( 'gif' ),
						'image/webp'      => array( 'webp' ),
					);
					foreach ( (array) $allowed_list as $allow_item ) {
						$allow_item = strtolower( trim( (string) $allow_item ) );
						if ( isset( $mime_to_exts[ $allow_item ] ) && in_array( $file_ext, $mime_to_exts[ $allow_item ], true ) ) {
							$allowed_ok = true;
							break;
						}
					}
				}
				if (! $allowed_ok) {
					$att_skipped[] = array(
						'file'   => $file_name,
						'reason' => 'type',
						'mime'   => $mime,
						'ext'    => $file_ext,
						'allow'  => implode( ',', (array) $allowed_list ),
					);
					continue;
				}
				$max_bytes = function_exists( 'pnpc_psd_get_max_attachment_bytes' ) ? (int) pnpc_psd_get_max_attachment_bytes() : (5 * 1024 * 1024);
				$server_max_bytes = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
				$effective_max_bytes = ( $server_max_bytes > 0 ) ? min( $max_bytes, $server_max_bytes ) : $max_bytes;
				if ( isset( $file['size'] ) && (int) $file['size'] > $effective_max_bytes ) {
					$att_skipped[] = array('file' => $file_name, 'reason' => 'size', 'size' => (int) $file['size'], 'max' => (int) $effective_max_bytes);
					continue;
				}
				$move = wp_handle_upload($file, array('test_form' => false));
				if (isset($move['error'])) {
					$att_skipped[] = array('file' => $file_name, 'reason' => 'upload', 'msg' => (string) $move['error']);
					continue;
				}
				$attachments[] = array(
					'file_name'   => sanitize_file_name($file['name']),
					'file_path'   => $move['file'],
					'file_type'   => $mime,
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
			$note = '';
			if ( ! empty( $att_skipped ) ) {
				$detail = '';
				foreach ( (array) $att_skipped as $sk ) {
					if ( isset( $sk['reason'] ) && 'size' === (string) $sk['reason'] && isset( $sk['max'] ) ) {
						$max_human  = function_exists( 'pnpc_psd_format_filesize' ) ? pnpc_psd_format_filesize( (int) $sk['max'] ) : ( (int) $sk['max'] . ' bytes' );
						$size_human = ( isset( $sk['size'] ) && function_exists( 'pnpc_psd_format_filesize' ) ) ? pnpc_psd_format_filesize( (int) $sk['size'] ) : '';
						$detail = ' ' . sprintf( esc_html__( 'Max per file: %s.', 'pnpc-pocket-service-desk' ), $max_human );
						if ( $size_human ) {
							$detail .= ' ' . sprintf( esc_html__( 'File size was %s.', 'pnpc-pocket-service-desk' ), $size_human );
						}
						break;
					}
					if ( empty( $detail ) && isset( $sk['reason'] ) && 'type' === (string) $sk['reason'] ) {
						if ( ! empty( $sk['mime'] ) ) {
							$detail .= ' ' . sprintf( esc_html__( 'Detected type: %s.', 'pnpc-pocket-service-desk' ), esc_html( (string) $sk['mime'] ) );
						}
						if ( ! empty( $sk['ext'] ) ) {
							$detail .= ' ' . sprintf( esc_html__( 'Extension: %s.', 'pnpc-pocket-service-desk' ), esc_html( (string) $sk['ext'] ) );
						}
						if ( ! empty( $sk['allow'] ) ) {
							$detail .= ' ' . sprintf( esc_html__( 'Allowed: %s.', 'pnpc-pocket-service-desk' ), esc_html( (string) $sk['allow'] ) );
						}
						$detail = trim( $detail );
					}
					if ( empty( $detail ) && isset( $sk['reason'] ) && 'php' === (string) $sk['reason'] && isset( $sk['code'] ) ) {
						$detail = ' ' . sprintf( esc_html__( 'Upload rejected by server (code %d).', 'pnpc-pocket-service-desk' ), (int) $sk['code'] );
					}
					if ( empty( $detail ) && isset( $sk['reason'] ) && 'upload' === (string) $sk['reason'] && ! empty( $sk['msg'] ) ) {
						$detail = ' ' . esc_html( (string) $sk['msg'] );
					}
					if ( ! empty( $detail ) ) {
						break;
					}
				}
				$note = ' ' . sprintf(
					/* translators: 1: number of attachments skipped, 2: detail */
					esc_html__( 'Note: %1$d attachment(s) were skipped due to type/size/upload rules.%2$s', 'pnpc-pocket-service-desk' ),
					count( $att_skipped ),
					$detail
				);
			}
			wp_send_json_success(array('message' => __('Response added successfully.', 'pnpc-pocket-service-desk') . $note));
		}

		wp_send_json_error(array('message' => __('Failed to add response.', 'pnpc-pocket-service-desk')));
	}

	/**
	* Ajax assign ticket.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_assign_ticket()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_assign_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id   = isset($_POST['ticket_id']) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
		$assigned_to = isset($_POST['assigned_to']) ? absint( wp_unslash( $_POST['assigned_to'] ) ) : 0;

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

	/**
	* Ajax update ticket status.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_update_ticket_status()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if (! current_user_can('pnpc_psd_respond_to_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id = isset($_POST['ticket_id']) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
		$status    = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

		if (! $ticket_id || empty($status)) {
			wp_send_json_error(array('message' => __('Invalid data. ', 'pnpc-pocket-service-desk')));
		}

		$old_ticket = PNPC_PSD_Ticket::get( $ticket_id );
		$old_status = ( $old_ticket && isset( $old_ticket->status ) ) ? (string) $old_ticket->status : '';

		$result = PNPC_PSD_Ticket::update(
			$ticket_id,
			array('status' => $status)
		);

		if ($result) {
			// Treat staff status change as staff activity for unread tracking.
			if ( class_exists( 'PNPC_PSD_Ticket' ) ) {
				PNPC_PSD_Ticket::update_activity_on_response( $ticket_id, true );
			}

			// Notify customer if a ticket is closed (configurable).
			if ( 'closed' === $status && 'closed' !== $old_status && class_exists( 'PNPC_PSD_Notifications' ) ) {
				PNPC_PSD_Notifications::ticket_closed( (int) $ticket_id, get_current_user_id() );
			}

			wp_send_json_success(array('message' => __('Ticket status updated successfully. ', 'pnpc-pocket-service-desk')));
		} else {
			wp_send_json_error(array('message' => __('Failed to update status.', 'pnpc-pocket-service-desk')));
		}
	}


	/**
	* Ajax update ticket priority.
	*
	* @since 1.1.1.4
	*
	* @return void
	*/
	public function ajax_update_ticket_priority()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if ( ! current_user_can( 'pnpc_psd_assign_tickets' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id = isset($_POST['ticket_id']) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
		$priority  = isset($_POST['priority']) ? sanitize_key( wp_unslash( $_POST['priority'] ) ) : '';

		$allowed = array('low', 'normal', 'high', 'urgent');
		if ( ! $ticket_id || empty($priority) || ! in_array($priority, $allowed, true) ) {
			wp_send_json_error(array('message' => __('Invalid data.', 'pnpc-pocket-service-desk')));
		}

		$result = PNPC_PSD_Ticket::update(
			$ticket_id,
			array('priority' => $priority)
		);

		if ( $result ) {
			wp_send_json_success(array('message' => __('Priority updated successfully.', 'pnpc-pocket-service-desk')));
		} else {
			wp_send_json_error(array('message' => __('Failed to update priority.', 'pnpc-pocket-service-desk')));
		}
	}

	/**
	* Ajax delete ticket.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_delete_ticket()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		// Bulk admin list deletes are Admin-only.
		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id = isset($_POST['ticket_id']) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;

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

	/**
	* Ajax bulk trash tickets.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_bulk_trash_tickets()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		// Bulk admin list deletes are Admin-only.
		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids = isset($_POST['ticket_ids']) ? array_map('absint', (array) wp_unslash($_POST['ticket_ids'])) : array();

		if (empty($ticket_ids)) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		$count = PNPC_PSD_Ticket::bulk_trash($ticket_ids);

		if ($count > 0) {
			/* translators: %d: number of tickets */
			$message = sprintf(_n('%d ticket moved to trash.', '%d tickets moved to trash.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array('message' => $message, 'count' => $count, 'counts' => $this->get_ticket_tab_counts()));
		} else {
			wp_send_json_error(array('message' => __('Failed to move tickets to trash.', 'pnpc-pocket-service-desk')));
		}
	}

	/**
	* Ajax bulk restore tickets.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_bulk_restore_tickets()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		// Bulk admin list deletes are Admin-only.
		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids = isset($_POST['ticket_ids']) ? array_map('absint', (array) wp_unslash($_POST['ticket_ids'])) : array();

		if (empty($ticket_ids)) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		$count = PNPC_PSD_Ticket::bulk_restore($ticket_ids);

		if ($count > 0) {
			/* translators: %d: number of tickets */
			$message = sprintf(_n('%d ticket restored.', '%d tickets restored.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array('message' => $message, 'count' => $count, 'counts' => $this->get_ticket_tab_counts()));
		} else {
			wp_send_json_error(array('message' => __('Failed to restore tickets.', 'pnpc-pocket-service-desk')));
		}
	}

	/**
	* Ajax bulk archive tickets.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_bulk_archive_tickets()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids = isset($_POST['ticket_ids']) ? array_map('absint', (array) wp_unslash($_POST['ticket_ids'])) : array();
		if ( empty($ticket_ids) ) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		$count = PNPC_PSD_Ticket::bulk_archive_closed($ticket_ids);
		if ( $count > 0 ) {
			$message = sprintf(_n('%d ticket moved to archive.', '%d tickets moved to archive.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array('message' => $message, 'count' => $count, 'counts' => $this->get_ticket_tab_counts()));
		}

		wp_send_json_error(array('message' => __('No closed tickets were archived.', 'pnpc-pocket-service-desk')));
	}

	/**
	* Ajax bulk restore archived tickets.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_bulk_restore_archived_tickets()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids = isset($_POST['ticket_ids']) ? array_map('absint', (array) wp_unslash($_POST['ticket_ids'])) : array();
		if ( empty($ticket_ids) ) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		$count = PNPC_PSD_Ticket::bulk_restore_from_archive($ticket_ids);
		if ( $count > 0 ) {
			$message = sprintf(_n('%d ticket restored from archive.', '%d tickets restored from archive.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array('message' => $message, 'count' => $count, 'counts' => $this->get_ticket_tab_counts()));
		}

		wp_send_json_error(array('message' => __('No tickets were restored from archive.', 'pnpc-pocket-service-desk')));
	}

	/**
	 * AJAX handler to trash tickets with a reason.
	 *
	 * @since 1.2.0
	 */
	public function ajax_trash_with_reason()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		// Bulk admin list deletes are Admin-only.
		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids   = isset($_POST['ticket_ids']) ? array_map('absint', (array) wp_unslash($_POST['ticket_ids'])) : array();
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

	/**
	 * Danger Zone deletion requests: move tickets into the Review queue (pending delete) instead of Trash.
	 *
	 * @since 1.4.0
	 */
	public function ajax_request_delete_with_reason()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if ( ! current_user_can('pnpc_psd_view_tickets') ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids   = isset($_POST['ticket_ids']) ? array_map('absint', (array) wp_unslash($_POST['ticket_ids'])) : array();
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

		$count = PNPC_PSD_Ticket::bulk_request_delete_with_reason($ticket_ids, get_current_user_id(), $reason, $reason_other);

		if ($count > 0) {
			/* translators: %d: number of tickets */
			$message = sprintf(_n('%d ticket queued for review.', '%d tickets queued for review.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array(
				'message' => $message,
				'count'   => $count,
			));
		}

		wp_send_json_error(array('message' => __('Failed to queue tickets for review.', 'pnpc-pocket-service-desk')));
	}

	/**
	 * Approve tickets in Review queue and move to Trash.
	 *
	 * @since 1.4.0
	 */
	public function ajax_bulk_approve_review_tickets()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if ( ! current_user_can('pnpc_psd_delete_tickets') ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids = isset($_POST['ticket_ids']) ? array_map('absint', (array) wp_unslash($_POST['ticket_ids'])) : array();
		if (empty($ticket_ids)) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		// Guard: Only approve tickets that actually have a pending delete request.
		$eligible_ids = array();
		foreach ( $ticket_ids as $tid ) {
			$ticket = PNPC_PSD_Ticket::get( $tid );
			$pending_at = ( $ticket && isset( $ticket->pending_delete_at ) ) ? (string) $ticket->pending_delete_at : '';
			if ( '' !== $pending_at ) {
				$eligible_ids[] = absint( $tid );
			}
		}

		if ( empty( $eligible_ids ) ) {
			wp_send_json_error(array(
				'message' => __('No tickets were approved. The selected ticket(s) do not appear to have a pending delete request (Review queue). Refresh the page and try again.', 'pnpc-pocket-service-desk'),
			));
		}

		$count = PNPC_PSD_Ticket::bulk_approve_pending_delete_to_trash( $eligible_ids );
		if ($count > 0) {
			/* translators: %d: number of tickets */
			$message = sprintf(_n('%d ticket moved to trash.', '%d tickets moved to trash.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array('message' => $message, 'count' => $count, 'counts' => $this->get_ticket_tab_counts()));
		}

		wp_send_json_error(array(
			'message' => __('No tickets were approved. Please refresh the Review list and try again.', 'pnpc-pocket-service-desk'),
		));
	}

	/**
	 * Cancel delete review requests and restore tickets to their prior status.
	 *
	 * @since 1.4.0
	 */
	public function ajax_bulk_cancel_review_tickets()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		if ( ! current_user_can('pnpc_psd_delete_tickets') ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids = isset($_POST['ticket_ids']) ? array_map('absint', (array) wp_unslash($_POST['ticket_ids'])) : array();
		if (empty($ticket_ids)) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		$count = PNPC_PSD_Ticket::bulk_cancel_pending_delete($ticket_ids);
		if ($count > 0) {
			/* translators: %d: number of tickets */
			$message = sprintf(_n('%d ticket restored.', '%d tickets restored.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array('message' => $message, 'count' => $count, 'counts' => $this->get_ticket_tab_counts()));
		}

		wp_send_json_error(array('message' => __('Failed to restore tickets.', 'pnpc-pocket-service-desk')));
	}

	/**
	* Ajax bulk delete permanently tickets.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_bulk_delete_permanently_tickets()
	{
		check_ajax_referer('pnpc_psd_admin_nonce', 'nonce');

		// Bulk admin list deletes are Admin-only.
		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$ticket_ids = isset($_POST['ticket_ids']) ? array_map('absint', (array) wp_unslash($_POST['ticket_ids'])) : array();

		if (empty($ticket_ids)) {
			wp_send_json_error(array('message' => __('No tickets selected.', 'pnpc-pocket-service-desk')));
		}

		$count = PNPC_PSD_Ticket::bulk_delete_permanently($ticket_ids);

		if ($count > 0) {
			/* translators: %d: number of tickets */
			$message = sprintf(_n('%d ticket permanently deleted.', '%d tickets permanently deleted.', $count, 'pnpc-pocket-service-desk'), $count);
			wp_send_json_success(array('message' => $message, 'count' => $count, 'counts' => $this->get_ticket_tab_counts()));
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
		$paged  = isset($_POST['paged']) ? absint( wp_unslash( $_POST['paged'] ) ) : 1;
		$current_user_id = get_current_user_id();

		$per_page = (int) get_option( 'pnpc_psd_tickets_per_page', 20 );
		if ( $per_page < 1 ) {
			$per_page = 20;
		}
		$paged  = max( 1, (int) $paged );
		$offset = ( $paged - 1 ) * $per_page;

		$args = array(
			'limit'  => $per_page,
			'offset' => $offset,
		);

		// Check special list views.
		$is_trash_view    = ( 'trash' === $view );
		$is_review_view   = ( 'review' === $view );
		$is_archived_view = ( 'archived' === $view );

		if ( $is_trash_view ) {
			$tickets = PNPC_PSD_Ticket::get_trashed($args);
		} elseif ( $is_review_view ) {
			$tickets = PNPC_PSD_Ticket::get_pending_delete($args);
		} elseif ( $is_archived_view ) {
			$tickets = PNPC_PSD_Ticket::get_archived( $args );
		} else {
			$args['status'] = $status;
			$tickets = PNPC_PSD_Ticket::get_all($args);
		}
		
		// Calculate badge counts for each ticket (fresh calculation)
		$badge_counts = array();
		
		foreach ($tickets as $ticket) {
			if ( ! $is_trash_view && ! $is_review_view && ! $is_archived_view ) {
				$badge_count = $this->calculate_new_badge_count($ticket->id, $current_user_id);
				$badge_counts[$ticket->id] = $badge_count;
			}
		}
		
		// Pass pagination info to view (use local $paged; avoid mutating superglobals)

		// Generate HTML for ticket rows
		ob_start();
		if (! empty($tickets)) {
			// For trash/review/archived views, don't separate by status.
			if ( $is_trash_view || $is_review_view || $is_archived_view ) {
				foreach ($tickets as $ticket) {
					$this->render_ticket_row($ticket, $is_trash_view, false, $view);
				}
			} else {
				// Separate active and closed tickets
				$separated = $this->separate_active_and_closed_tickets($tickets);
				$active_tickets = $separated['active'];
				$closed_tickets = $separated['closed'];
				$has_active = !empty($active_tickets);
				$has_closed = !empty($closed_tickets);

				// Render active tickets
				if ($has_active) {
					foreach ($active_tickets as $ticket) {
						$this->render_ticket_row($ticket, false, false, '');
					}
				}

				// Render divider if both sections exist
				if ($has_active && $has_closed) {
					?>
					<tr class="pnpc-psd-closed-divider">
						<td colspan="<?php echo current_user_can('pnpc_psd_delete_tickets') ? '10' : '9'; ?>">
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

				// Render closed tickets with special class
				if ($has_closed) {
					foreach ($closed_tickets as $ticket) {
						$this->render_ticket_row($ticket, false, true, '');
					}
				}
			}
		} else {
			$colspan = ( $is_trash_view || $is_review_view || $is_archived_view )
				? (current_user_can('pnpc_psd_delete_tickets') ? '6' : '5')
				: (current_user_can('pnpc_psd_delete_tickets') ? '10' : '9');
			?>
			<tr>
				<td colspan="<?php echo esc_attr($colspan); ?>">
					<?php
					if ($is_trash_view) {
						esc_html_e('No tickets in trash.', 'pnpc-pocket-service-desk');
					} elseif ( $is_review_view ) {
						esc_html_e('No tickets pending review.', 'pnpc-pocket-service-desk');
					} elseif ( $is_archived_view ) {
						esc_html_e('No archived tickets.', 'pnpc-pocket-service-desk');
					} else {
						esc_html_e('No tickets found.', 'pnpc-pocket-service-desk');
					}
					?>
				</td>
			</tr>
			<?php
		}
		$html = ob_get_clean();

		// Get counts for tabs
		$open_count   = PNPC_PSD_Ticket::get_count('open');
		$closed_count = PNPC_PSD_Ticket::get_count('closed');
		$trash_count  = PNPC_PSD_Ticket::get_trashed_count();
		$review_count = PNPC_PSD_Ticket::get_pending_delete_count();
		$archived_count = method_exists('PNPC_PSD_Ticket','get_archived_count') ? PNPC_PSD_Ticket::get_archived_count() : 0;

		wp_send_json_success(array(
			'html' => $html,
			'badge_counts' => $badge_counts,
			'counts' => array(
				'open'   => $open_count,
				'closed' => $closed_count,
				'trash'  => $trash_count,
				'review' => $review_count,
				'archived' => $archived_count,
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
		$ticket_id = absint( $ticket_id );
		$user_id   = absint( $user_id );
		if ( ! $ticket_id || ! $user_id || ! class_exists( 'PNPC_PSD_Ticket' ) ) {
			return 0;
		}

		// v1.1.0+: Prefer deterministic, DB-backed role timestamps on the ticket row.
		$ticket = PNPC_PSD_Ticket::get( $ticket_id );
		if ( $ticket && ! empty( $ticket->last_customer_activity_at ) ) {
			$customer_activity_raw = ! empty( $ticket->last_customer_activity_at ) ? (string) $ticket->last_customer_activity_at : (string) $ticket->created_at;
			$staff_viewed_raw      = ! empty( $ticket->last_staff_viewed_at ) ? (string) $ticket->last_staff_viewed_at : '';
			$customer_activity_ts  = ( '' !== $customer_activity_raw ) ? strtotime( $customer_activity_raw . ' UTC' ) : 0;
			$staff_viewed_ts       = ( '' !== $staff_viewed_raw ) ? strtotime( $staff_viewed_raw . ' UTC' ) : 0;
			return ( $customer_activity_ts > $staff_viewed_ts ) ? 1 : 0;
		}

		// Legacy fallback (should be hit only on very old DBs): user-meta based.
		$last_view_meta = get_user_meta( $user_id, 'pnpc_psd_ticket_last_view_' . $ticket_id, true );
		if ( empty( $last_view_meta ) ) {
			return 1;
		}
		$last_view_time = is_numeric( $last_view_meta ) ? (int) $last_view_meta : (int) strtotime( (string) $last_view_meta );
		if ( ! $last_view_time ) {
			return 1;
		}
		$responses = class_exists( 'PNPC_PSD_Ticket_Response' ) ? PNPC_PSD_Ticket_Response::get_by_ticket( $ticket_id, array( 'orderby' => 'created_at', 'order' => 'ASC' ) ) : array();
		$new_count = 0;
		if ( ! empty( $responses ) ) {
			foreach ( $responses as $response ) {
				if ( (int) $response->user_id === (int) $user_id ) {
					continue;
				}
				$response_time = (int) strtotime( (string) $response->created_at );
				if ( $response_time > $last_view_time ) {
					$new_count++;
				}
			}
		}
		return (int) $new_count;
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
	 * @param bool $is_closed Whether this is a closed ticket (for styling)
	 */
	private function render_ticket_row($ticket, $is_trash_view = false, $is_closed = false, $view = '')
	{
		$user          = get_userdata($ticket->user_id);
		$assigned_user = $ticket->assigned_to ? get_userdata($ticket->assigned_to) : null;
		$is_review_view = ('review' === $view);
		$can_bulk = $is_review_view ? current_user_can('pnpc_psd_delete_tickets') : current_user_can('manage_options');
		
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
		
		// Calculate new responses - only relevant on the main list (not Trash/Review).
		$new_responses = 0;
		$current_admin_id = get_current_user_id();
		if (! $is_trash_view && ! $is_review_view && $current_admin_id && $ticket->assigned_to && (int) $ticket->assigned_to === (int) $current_admin_id) {
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
		?>
		<tr<?php echo $is_closed ? ' class="pnpc-psd-ticket-closed"' : ''; ?>>
			<?php if ( $can_bulk ) : ?>
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
			<?php if ( $is_trash_view ) : ?>
				<?php
					$delete_reason = isset($ticket->delete_reason) ? (string) $ticket->delete_reason : '';
					$delete_reason_other = isset($ticket->delete_reason_other) ? (string) $ticket->delete_reason_other : '';
					$deleted_at = isset($ticket->deleted_at) ? (string) $ticket->deleted_at : '';
					$deleted_by_user = ! empty($ticket->deleted_by) ? get_userdata(absint($ticket->deleted_by)) : null;
					$deleted_timestamp = $deleted_at ? strtotime($deleted_at) : 0;
				?>
				<td data-sort-value="<?php echo esc_attr(strtolower($delete_reason)); ?>">
					<?php echo esc_html(pnpc_psd_format_delete_reason($delete_reason, $delete_reason_other)); ?>
				</td>
				<td data-sort-value="<?php echo esc_attr(strtolower($deleted_by_user ? $deleted_by_user->display_name : 'zzz_unknown')); ?>">
					<?php echo $deleted_by_user ? esc_html($deleted_by_user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?>
				</td>
				<td data-sort-value="<?php echo absint($deleted_timestamp); ?>">
					<?php
					if ( $deleted_at ) {
						if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
							echo esc_html(pnpc_psd_format_db_datetime_for_display($deleted_at));
						} else {
							echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($deleted_at)));
						}
					} else {
						esc_html_e('Unknown', 'pnpc-pocket-service-desk');
					}
					?>
				</td>
			<?php elseif ( $is_review_view ) : ?>
				<?php
					$req_reason = isset($ticket->pending_delete_reason) ? (string) $ticket->pending_delete_reason : '';
					$req_reason_other = isset($ticket->pending_delete_reason_other) ? (string) $ticket->pending_delete_reason_other : '';
					$req_at = isset($ticket->pending_delete_at) ? (string) $ticket->pending_delete_at : '';
					$req_by_user = ! empty($ticket->pending_delete_by) ? get_userdata(absint($ticket->pending_delete_by)) : null;
					$req_timestamp = $req_at ? strtotime($req_at) : 0;
				?>
				<td data-sort-value="<?php echo esc_attr(strtolower($req_reason)); ?>">
					<?php echo esc_html(pnpc_psd_format_delete_reason($req_reason, $req_reason_other)); ?>
				</td>
				<td data-sort-value="<?php echo esc_attr(strtolower($req_by_user ? $req_by_user->display_name : 'zzz_unknown')); ?>">
					<?php echo $req_by_user ? esc_html($req_by_user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?>
				</td>
				<td data-sort-value="<?php echo absint($req_timestamp); ?>">
					<?php
					if ( $req_at ) {
						if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
							echo esc_html(pnpc_psd_format_db_datetime_for_display($req_at));
						} else {
							echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($req_at)));
						}
					} else {
						esc_html_e('Unknown', 'pnpc-pocket-service-desk');
					}
					?>
				</td>
			<?php else : ?>
				<td data-sort-value="<?php echo esc_attr(strtolower($user ? $user->display_name : 'zzz_unknown')); ?>"><?php echo $user ? esc_html($user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?></td>
			<td data-sort-value="<?php echo absint($status_sort_value); ?>">
				<?php
				$raw_status = isset( $ticket->status ) ? (string) $ticket->status : '';
				$status_key = strtolower( str_replace( '_', '-', $raw_status ) );
				$status_labels = array(
					'open'        => __( 'Open', 'pnpc-pocket-service-desk' ),
					'in-progress' => __( 'In Progress', 'pnpc-pocket-service-desk' ),
					'waiting'     => __( 'Waiting', 'pnpc-pocket-service-desk' ),
					'closed'      => __( 'Closed', 'pnpc-pocket-service-desk' ),
				);
				$status_label = isset( $status_labels[ $status_key ] ) ? $status_labels[ $status_key ] : ucwords( str_replace( '-', ' ', $status_key ) );
				?>
				<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $status_key ); ?>">
					<?php echo esc_html( $status_label ); ?>
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
					if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
						echo esc_html(pnpc_psd_format_db_datetime_for_display($ticket->created_at));
					} else {
						echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at)));
					}
					?>
				</td>
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

	/**
	* Is plugin page.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	private function is_plugin_page()
	{
		$screen = get_current_screen();
		if (! $screen) {
			return false;
		}

		return strpos($screen->id, 'pnpc-service-desk') !== false;
	}

	/**
	* Display create ticket page.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function display_create_ticket_page()
	{
		if (! current_user_can('pnpc_psd_view_tickets')) {
			wp_die(esc_html__('You do not have permission to access this page.', 'pnpc-pocket-service-desk'));
		}

		include PNPC_PSD_PLUGIN_DIR . 'admin/views/create-ticket-admin.php';
	}

	/**
	* Process admin create ticket.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function process_admin_create_ticket()
	{
		// Check if form submitted
		if (! isset($_POST['pnpc_psd_create_ticket_nonce'])) {
			return;
		}

		// Verify nonce
		if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pnpc_psd_create_ticket_nonce'])), 'pnpc_psd_create_ticket_admin')) {
			wp_die(esc_html__('Security check failed.', 'pnpc-pocket-service-desk'));
		}

		// Check permissions
		if (! current_user_can('pnpc_psd_view_tickets')) {
			wp_die(esc_html__('Permission denied.', 'pnpc-pocket-service-desk'));
		}

		// Validate and sanitize input
		$customer_id = isset($_POST['customer_id']) ? absint( wp_unslash( $_POST['customer_id'] ) ) : 0;
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
				
				// Plan-aware size cap, additionally clamped by the server/WP upload cap.
				$plan_max_bytes = function_exists( 'pnpc_psd_get_max_attachment_bytes' ) ? (int) pnpc_psd_get_max_attachment_bytes() : (5 * 1024 * 1024);
				$server_max_bytes = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
				$effective_max_bytes = ( $server_max_bytes > 0 ) ? min( $plan_max_bytes, $server_max_bytes ) : $plan_max_bytes;

				for ($i = 0; $i < $file_count; $i++) {
					// Skip if no file
					if (empty($files['name'][$i])) {
						continue;
					}
					
					// Respect PHP/WP upload error codes first; do not mislabel server rejections as "size" issues.
					if ( isset( $files['error'][$i] ) && (int) $files['error'][$i] !== UPLOAD_ERR_OK ) {
						add_settings_error(
							'pnpc_psd_messages',
							'pnpc_psd_message',
							sprintf(
								/* translators: 1: filename */
								__('File "%1$s" could not be uploaded (server rejected the upload). Please confirm your server upload limits and try again.', 'pnpc-pocket-service-desk'),
								$files['name'][$i]
							),
							'warning'
						);
						continue;
					}

					// Check file size (plan-aware cap clamped by server/WP max upload size).
					if ( isset( $files['size'][$i] ) && (int) $files['size'][$i] > $effective_max_bytes ) {
						$size_human = function_exists( 'pnpc_psd_format_filesize' ) ? pnpc_psd_format_filesize( (int) $files['size'][$i] ) : ( (int) $files['size'][$i] . ' bytes' );
						$max_human  = function_exists( 'pnpc_psd_format_filesize' ) ? pnpc_psd_format_filesize( (int) $effective_max_bytes ) : ( (int) $effective_max_bytes . ' bytes' );
						add_settings_error(
							'pnpc_psd_messages',
							'pnpc_psd_message',
							sprintf(
								/* translators: 1: filename, 2: file size, 3: max size */
								__('File "%1$s" (%2$s) exceeds the maximum allowed size (%3$s) and was skipped.', 'pnpc-pocket-service-desk'),
								$files['name'][$i],
								$size_human,
								$max_human
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
							'response_id' => 0,
							'file_name'   => sanitize_file_name($files['name'][$i]),
							'file_path'   => sanitize_text_field($upload['file']),
							'file_type'   => sanitize_text_field($upload['type']),
							'file_size'   => intval($files['size'][$i]),
							'uploaded_by' => get_current_user_id(),
							'created_at'  => $created_at_utc,
						);
						
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$wpdb->insert(
							$attachments_table,
							$att_data,
							array('%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s')
						);
					}
				}
			}
			
			// Send notification if requested (don't let email failures break the redirect)
			if ($notify_customer) {
				try {
					$this->send_staff_created_ticket_notification($ticket_id, $customer_id);
				} catch (Exception $e) {
					// Log but don't break the flow
					error_log('PNPC PSD: Notification failed: ' . $e->getMessage());
				}
			}

			// Success message and redirect
			add_settings_error(
				'pnpc_psd_messages',
				'pnpc_psd_message',
				__('Ticket created successfully!', 'pnpc-pocket-service-desk'),
				'success'
			);

			// Redirect to ticket detail
			wp_safe_redirect(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket_id));
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

	/**
	* Send staff created ticket notification.
	*
	* @param mixed $ticket_id
	* @param mixed $customer_id
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	private function send_staff_created_ticket_notification($ticket_id, $customer_id)
	{
		try {
			$ticket = PNPC_PSD_Ticket::get($ticket_id);
			$customer = get_userdata($customer_id);
			$staff = wp_get_current_user();

			if (! $ticket || ! $customer) {
				error_log('PNPC PSD: Failed to send notification - invalid ticket or customer');
				return false;
			}

			$to = $customer->user_email;
			
			// Validate email address
			if (!is_email($to)) {
				error_log('PNPC PSD: Invalid customer email address for customer ID: ' . absint($customer_id));
				return false;
			}
			
			$subject = sprintf(
				/* translators: 1: site name, 2: ticket number */
				__('[%1$s] Support Ticket Created - #%2$s', 'pnpc-pocket-service-desk'),
				get_bloginfo('name'),
				$ticket->ticket_number
			);

			// Try to get customer-facing ticket detail page, fallback to admin URL
			$ticket_url = admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . absint($ticket_id));
			
			$ticket_detail_page_id = absint(get_option('pnpc_psd_ticket_detail_page_id', 0));
			if ($ticket_detail_page_id > 0) {
				$page = get_post($ticket_detail_page_id);
				if ($page && $page->post_status === 'publish') {
					$ticket_url = add_query_arg(
						array('ticket_id' => absint($ticket_id)),
						get_permalink($ticket_detail_page_id)
					);
				}
			}

			// Build message using array and implode to avoid sprintf format specifier issues
			$message_parts = array();

			/* translators: %s: customer display name */
			$message_parts[] = sprintf( __( 'Hello %s,', 'pnpc-pocket-service-desk' ), $customer->display_name );
			$message_parts[] = '';
			$message_parts[] = __( 'A support ticket has been created for you by our support team.', 'pnpc-pocket-service-desk' );
			$message_parts[] = '';
			/* translators: %s: ticket number */
			$message_parts[] = sprintf( __( 'Ticket Number: %s', 'pnpc-pocket-service-desk' ), $ticket->ticket_number );
			/* translators: %s: ticket subject */
			$message_parts[] = sprintf( __( 'Subject: %s', 'pnpc-pocket-service-desk' ), $ticket->subject );
			/* translators: %s: ticket priority */
			$message_parts[] = sprintf( __( 'Priority: %s', 'pnpc-pocket-service-desk' ), ucfirst( $ticket->priority ) );
			$message_parts[] = '';
			$message_parts[] = __( 'Description:', 'pnpc-pocket-service-desk' );
			$message_parts[] = $ticket->description;
			$message_parts[] = '';
			$message_parts[] = __( 'You can view and respond to this ticket here:', 'pnpc-pocket-service-desk' );
			$message_parts[] = $ticket_url;
			$message_parts[] = '';
			/* translators: %s: staff display name */
			$message_parts[] = sprintf( __( 'Created by: %s', 'pnpc-pocket-service-desk' ), $staff->display_name );
			$message_parts[] = '';
			$message_parts[] = __( 'Thank you,', 'pnpc-pocket-service-desk' );
			/* translators: %s: site name */
			$message_parts[] = sprintf( __( '%s Support Team', 'pnpc-pocket-service-desk' ), get_bloginfo( 'name' ) );

			$message = implode( "\n", $message_parts );

			$headers = array('Content-Type: text/plain; charset=UTF-8');

			$result = wp_mail($to, $subject, $message, $headers);
			
			if (!$result) {
				error_log('PNPC PSD: Failed to send notification email for ticket ID: ' . absint($ticket_id) . ', customer ID: ' . absint($customer_id));
			}
			
			return $result;
			
		} catch (Exception $e) {
			error_log('PNPC PSD: Exception in send_staff_created_ticket_notification: ' . $e->getMessage());
			return false;
		}
	}



	/**
	 * Handle archiving a ticket from admin list.
	 */
	public function handle_archive_ticket() {
		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'pnpc-pocket-service-desk' ) );
		}

		$ticket_id = isset( $_GET['ticket_id'] ) ? absint( wp_unslash( $_GET['ticket_id'] ) ) : 0;
		if ( ! $ticket_id ) {
			wp_die( esc_html__( 'Invalid ticket.', 'pnpc-pocket-service-desk' ) );
		}

		check_admin_referer( 'pnpc_psd_archive_ticket_' . $ticket_id );

		// Optional return target so single-item actions can originate from different list tabs (e.g., Trash).
		$return_to = isset( $_GET['return_to'] ) ? sanitize_key( wp_unslash( $_GET['return_to'] ) ) : '';

		if ( class_exists( 'PNPC_PSD_Ticket' ) ) {
			if ( 'trash' === $return_to && method_exists( 'PNPC_PSD_Ticket', 'archive_from_trash' ) ) {
				PNPC_PSD_Ticket::archive_from_trash( $ticket_id );
			} else {
				PNPC_PSD_Ticket::archive( $ticket_id );
			}
		}

		// $return_to already captured above.
		$redirect  = admin_url( 'admin.php?page=pnpc-service-desk-tickets&status=closed' );
		if ( $return_to ) {
			switch ( $return_to ) {
				case 'trash':
					$redirect = admin_url( 'admin.php?page=pnpc-service-desk-tickets&view=trash' );
					break;
				case 'review':
					$redirect = admin_url( 'admin.php?page=pnpc-service-desk-tickets&view=review' );
					break;
				case 'archived':
					$redirect = admin_url( 'admin.php?page=pnpc-service-desk-tickets&view=archived' );
					break;
				case 'all':
					$redirect = admin_url( 'admin.php?page=pnpc-service-desk-tickets' );
					break;
				case 'closed':
				default:
					$redirect = admin_url( 'admin.php?page=pnpc-service-desk-tickets&status=closed' );
					break;
			}
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle restoring an archived ticket.
	 */
	public function handle_restore_archived_ticket() {
		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'pnpc-pocket-service-desk' ) );
		}

		$ticket_id = isset( $_GET['ticket_id'] ) ? absint( wp_unslash( $_GET['ticket_id'] ) ) : 0;
		if ( ! $ticket_id ) {
			wp_die( esc_html__( 'Invalid ticket.', 'pnpc-pocket-service-desk' ) );
		}

		check_admin_referer( 'pnpc_psd_restore_archived_ticket_' . $ticket_id );

		if ( class_exists( 'PNPC_PSD_Ticket' ) ) {
			PNPC_PSD_Ticket::restore_from_archive( $ticket_id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=pnpc-service-desk-tickets&view=archived' ) );
		exit;
	}

	/**
	 * Export tickets as a CSV.
	 */
	public function handle_export_tickets() {
		if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_die( esc_html__( 'You do not have permission to export tickets.', 'pnpc-pocket-service-desk' ) );
		}

		check_admin_referer( 'pnpc_psd_export_tickets' );

		$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

		$args = array(
			'per_page' => 5000,
			'page'     => 1,
		);

		if ( 'archived' === $view ) {
			$tickets = method_exists( 'PNPC_PSD_Ticket', 'get_archived' ) ? PNPC_PSD_Ticket::get_archived( $args ) : array();
		} elseif ( 'trash' === $view ) {
			$tickets = PNPC_PSD_Ticket::get_trashed( $args );
		} elseif ( 'review' === $view ) {
			$tickets = PNPC_PSD_Ticket::get_pending_delete( $args );
		} else {
			if ( ! empty( $status ) ) {
				$args['status'] = $status;
			}
			$args['include_archived'] = true;
			$tickets = PNPC_PSD_Ticket::get_all( $args );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pnpc-tickets-' . gmdate( 'Ymd-His' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'Ticket Number', 'Subject', 'Status', 'Priority', 'Customer', 'Assigned To', 'Created', 'Archived At', 'Deleted At' ) );

		foreach ( (array) $tickets as $ticket ) {
			$customer = ! empty( $ticket->user_id ) ? get_userdata( absint( $ticket->user_id ) ) : null;
			$assigned = ! empty( $ticket->assigned_to ) ? get_userdata( absint( $ticket->assigned_to ) ) : null;

			fputcsv( $out, array(
				(string) ( $ticket->ticket_number ?? '' ),
				(string) ( $ticket->subject ?? '' ),
				(string) ( $ticket->status ?? '' ),
				(string) ( $ticket->priority ?? '' ),
				$customer ? (string) $customer->display_name : '',
				$assigned ? (string) $assigned->display_name : '',
				(string) ( $ticket->created_at ?? '' ),
				(string) ( $ticket->archived_at ?? '' ),
				(string) ( $ticket->deleted_at ?? '' ),
			) );
		}

		fclose( $out );
		exit;
	}


	/**
	 * Show a one-time setup wizard prompt after activation, if dashboard is not configured.
	 *
	 * @return void
	 */
	public function maybe_show_setup_wizard_notice() {
		if ( ! current_user_can( 'pnpc_psd_manage_settings' ) ) {
			return;
		}
		// Only show on wp-admin screens (avoid ajax).
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Admin hygiene: avoid showing the setup notice across unrelated wp-admin pages.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen ) {
			$screen_id = isset( $screen->id ) ? (string) $screen->id : '';
			$base      = isset( $screen->base ) ? (string) $screen->base : '';
			$allowed   = ( false !== strpos( $screen_id, 'pnpc-service-desk' ) ) || ( 'dashboard' === $base );
			if ( ! $allowed ) {
				return;
			}
		}


		$dash_id     = (int) get_option( 'pnpc_psd_dashboard_page_id', 0 );
		$needs_setup = (int) get_option( 'pnpc_psd_needs_setup_wizard', 0 );
		$dismissed   = (int) get_option( 'pnpc_psd_setup_notice_dismissed', 0 );

		if ( $dismissed ) {
			return;
		}


		// If a dashboard page is already configured and exists, stop prompting.
		if ( $dash_id > 0 && 'trash' !== get_post_status( $dash_id ) ) {
			if ( $needs_setup ) {
				update_option( 'pnpc_psd_needs_setup_wizard', 0 );
			}
			return;
		}

		// If no dashboard is configured, prompt the admin. We do not require the activation flag,
		// because plugins are often updated in-place without re-running activation hooks.
		if ( ! $needs_setup ) {
			update_option( 'pnpc_psd_needs_setup_wizard', 1 );
		}
		$needs_setup = 1;

		$dismiss_url = wp_nonce_url( add_query_arg( array( 'pnpc_psd_dismiss_setup' => 1 ) ), 'pnpc_psd_dismiss_setup' );
		$wizard_url  = admin_url( 'admin.php?page=pnpc-service-desk-setup' );

		// Handle dismiss.
		if ( isset( $_GET['pnpc_psd_dismiss_setup'] ) && check_admin_referer( 'pnpc_psd_dismiss_setup' ) ) {
			update_option( 'pnpc_psd_setup_notice_dismissed', 1 );
			return;
		}

		echo '<div class="notice notice-info is-dismissible">';
		echo '<p><strong>' . esc_html__( 'Service Desk setup is almost done.', 'pnpc-pocket-service-desk' ) . '</strong> ' . esc_html__( 'Run the Setup Wizard to create or link your customer dashboard page.', 'pnpc-pocket-service-desk' ) . '</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( $wizard_url ) . '">' . esc_html__( 'Run Setup Wizard', 'pnpc-pocket-service-desk' ) . '</a> ';
		echo '<a class="button" href="' . esc_url( $dismiss_url ) . '">' . esc_html__( 'Dismiss', 'pnpc-pocket-service-desk' ) . '</a></p>';
		echo '</div>';
	}

	/**
	 * Get counts for admin list tabs.
	 *
	 * @return array<string,int>
	 */
	private function get_ticket_tab_counts() {
		$counts = array();
		$counts['all']         = PNPC_PSD_Ticket::get_count('');
		$counts['open']        = PNPC_PSD_Ticket::get_count('open');
		$counts['in-progress'] = PNPC_PSD_Ticket::get_count('in-progress');
		$counts['waiting']     = PNPC_PSD_Ticket::get_count('waiting');
		$counts['closed']      = PNPC_PSD_Ticket::get_count('closed');
		$counts['review']      = PNPC_PSD_Ticket::get_pending_delete_count();
		$counts['trash']       = PNPC_PSD_Ticket::get_trashed_count();
		$counts['archived']    = PNPC_PSD_Ticket::get_archived_count();
		return $counts;
	}


	/**
	 * Show a lightweight admin notice if a recent create-ticket fatal was captured.
	 * This is non-invasive and only surfaces diagnostic context to admins.
	 */
	public function maybe_notice_last_create_ticket_fatal() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$last = get_option( 'pnpc_psd_last_create_ticket_fatal' );
		if ( empty( $last ) || ! is_array( $last ) ) {
			return;
		}
		$ts = isset( $last['ts'] ) ? (int) $last['ts'] : 0;
		// Only show if within last 30 minutes.
		if ( $ts < ( time() - 1800 ) ) {
			return;
		}
		$ref  = isset( $last['ref'] ) ? (string) $last['ref'] : '';
		$msg  = isset( $last['msg'] ) ? (string) $last['msg'] : '';
		$file = isset( $last['file'] ) ? (string) $last['file'] : '';
		$line = isset( $last['line'] ) ? (int) $last['line'] : 0;
		// Sanitize for display.
		$ref_d  = esc_html( $ref );
		$msg_d  = esc_html( $msg );
		$file_d = esc_html( basename( $file ) );
		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Service Desk:', 'pnpc-pocket-service-desk' ) . '</strong> ' .
			esc_html__( 'A critical error was captured during ticket creation.', 'pnpc-pocket-service-desk' ) .
			' ' . esc_html__( 'Reference:', 'pnpc-pocket-service-desk' ) . ' <code>' . $ref_d . '</code>' .
			( $file_d ? ' &mdash; <code>' . $file_d . ':' . (int) $line . '</code>' : '' ) .
			( $msg_d ? '<br/><span style="display:inline-block;margin-top:6px;max-width:900px;">' . $msg_d . '</span>' : '' ) .
			'</p></div>';
	}


}