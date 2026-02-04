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

/**
 * PNPC PSD Public.
 *
 * @since 1.1.1.4
 */
class PNPC_PSD_Public
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
	public function __construct($plugin_name = 'pnpc-pocket-service-desk', $version = '1.0.0')
	{
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
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
		wp_enqueue_style(
			$this->plugin_name,
			PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-public.css',
			array(),
			$this->version,
			'all'
		);

		// Attachment lightbox styles (used on public Ticket Detail view).
		wp_enqueue_style(
			$this->plugin_name . '-attachments',
			PNPC_PSD_PLUGIN_URL . 'assets/css/pnpc-psd-attachments.css',
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

	/**
	* Enqueue scripts.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function enqueue_scripts()
	{
		// Cache-bust the public JS reliably (CDN/proxy/browser caches often ignore plugin version bumps).
		$js_rel = 'assets/js/pnpc-psd-public.js';
		$js_abs = ( defined( 'PNPC_PSD_PLUGIN_DIR' ) ? PNPC_PSD_PLUGIN_DIR : plugin_dir_path( dirname( __FILE__ ) ) ) . $js_rel;
		$js_ver = file_exists( $js_abs ) ? (string) filemtime( $js_abs ) : $this->version;

		wp_enqueue_script(
			$this->plugin_name,
			PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-public.js',
			array('jquery'),
			$js_ver,
			true
		);

		// Attachment lightbox behaviour (used on public Ticket Detail view).
		wp_enqueue_script(
			$this->plugin_name . '-attachments',
			PNPC_PSD_PLUGIN_URL . 'assets/js/pnpc-psd-attachments.js',
			array('jquery'),
			$js_ver,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'pnpcPsdPublic',
			array(
				'ajax_url' => admin_url('admin-ajax.php', 'relative'),
				'nonce'    => wp_create_nonce('pnpc_psd_public_nonce'),
			)
		);
	}

	/**
	* Register shortcodes.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function register_shortcodes()
	{
		$shortcodes = array(
			'pnpc_service_desk'     => 'render_service_desk',
			'pnpc_create_ticket'    => 'render_create_ticket',
			'pnpc_my_tickets'       => 'render_my_tickets',
			'pnpc_ticket_detail'    => 'render_ticket_detail',
			// Back-compat aliases (older page templates / customer sites).
			'pnpc_ticket_details'   => 'render_ticket_detail',
			'pnpc_ticket_view'      => 'render_ticket_detail',
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
	 * Render a consistent login-required prompt for public shortcodes.
	 *
	 * @param string $redirect_url Where to send the user after login.
	 * @return string
	 */
	private function render_login_gate( $redirect_url = '' ) {
		// Multiple public shortcodes are often placed on a single dashboard page.
		// Only render the login prompt once per request to avoid showing multiple login forms.
		static $pnpc_psd_login_gate_rendered = false;
		if ( $pnpc_psd_login_gate_rendered ) {
			return '';
		}
		$pnpc_psd_login_gate_rendered = true;

		$redirect_url = is_string( $redirect_url ) ? trim( $redirect_url ) : '';
		if ( '' === $redirect_url ) {
			$redirect_url = home_url( '/' );
		}

		$mode = get_option( 'pnpc_psd_public_login_mode', 'inline' );
		$mode = is_string( $mode ) ? strtolower( trim( $mode ) ) : 'inline';
		if ( ! in_array( $mode, array( 'inline', 'link' ), true ) ) {
			$mode = 'inline';
		}

		$custom_url = get_option( 'pnpc_psd_public_login_url', '' );
		$custom_url = is_string( $custom_url ) ? trim( $custom_url ) : '';

		if ( 'link' === $mode ) {
			$url = $custom_url ? $custom_url : wp_login_url( $redirect_url );
			// If using a custom login page, provide a standard redirect_to parameter.
			if ( $custom_url ) {
				$url = add_query_arg( 'redirect_to', rawurlencode( $redirect_url ), $url );
			}
			return '<p>' . esc_html__( 'Please log in to continue.', 'pnpc-pocket-service-desk' ) . '</p>'
				. '<p><a class="button pnpc-psd-login-button" href="' . esc_url( $url ) . '">' . esc_html__( 'Log in', 'pnpc-pocket-service-desk' ) . '</a></p>';
		}

		$form = wp_login_form(
			array(
				'echo'     => false,
				'redirect' => $redirect_url,
			)
		);

		return '<div class="pnpc-psd-login-required">'
			. '<p>' . esc_html__( 'Please log in to continue.', 'pnpc-pocket-service-desk' ) . '</p>'
			. $form
			. '</div>';
	}


	/**
	* Render service desk.
	*
	* @param mixed $atts
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function render_service_desk($atts)
	{
		if (! is_user_logged_in()) {
			return $this->render_login_gate( function_exists('pnpc_psd_get_dashboard_url') ? pnpc_psd_get_dashboard_url() : home_url('/') );
		}
ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/service-desk.php';
		return ob_get_clean();
	}

	/**
	* Render create ticket.
	*
	* @param mixed $atts
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function render_create_ticket($atts)
	{
		if (! is_user_logged_in()) {
			return $this->render_login_gate( function_exists('pnpc_psd_get_dashboard_url') ? pnpc_psd_get_dashboard_url() : home_url('/') );
		}
ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/create-ticket.php';
		return ob_get_clean();
	}

	/**
	* Render my tickets.
	*
	* @param mixed $atts
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function render_my_tickets($atts)
	{
		if (! is_user_logged_in()) {
			return $this->render_login_gate( function_exists('pnpc_psd_get_my_tickets_url') ? pnpc_psd_get_my_tickets_url() : home_url('/') );
		}
		$current_user = wp_get_current_user();
		$tab          = isset( $_GET['pnpc_psd_tab'] ) ? sanitize_key( wp_unslash( $_GET['pnpc_psd_tab'] ) ) : 'open'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection.
		if (! in_array($tab, array('open', 'closed'), true)) {
			$tab = 'open';
		}

		$sort = isset( $_GET['pnpc_psd_sort'] ) ? sanitize_key( wp_unslash( $_GET['pnpc_psd_sort'] ) ) : 'latest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only sort parameter.
		if ( ! in_array( $sort, array( 'latest', 'newest', 'oldest', 'unread' ), true ) ) {
			$sort = 'latest';
		}

		// Pagination (public My Tickets screen).
		$current_page = isset( $_GET['pnpc_psd_page'] ) ? max( 1, absint( wp_unslash( $_GET['pnpc_psd_page'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination parameter.
		$per_page     = (int) get_option( 'pnpc_psd_tickets_per_page', 20 );
		if ( $per_page < 1 ) {
			$per_page = 20;
		}
		// Keep public output reasonable even if the admin increases the admin list page size.
		if ( $per_page > 100 ) {
			$per_page = 100;
		}
		$offset = ( $current_page - 1 ) * $per_page;

		$ticket_args = array(
			'limit'  => $per_page,
			'offset' => $offset,
		);
		if ('closed' === $tab) {
			$ticket_args['status'] = 'closed';
		} else {
			$ticket_args['exclude_statuses'] = array('closed');
		}

		// Default to most recent activity; user may switch to oldest/newest.
		if ( 'oldest' === $sort ) {
			$ticket_args['orderby'] = 'created_at';
			$ticket_args['order']   = 'ASC';
		} elseif ( 'newest' === $sort ) {
			$ticket_args['orderby'] = 'created_at';
			$ticket_args['order']   = 'DESC';
		} elseif ( 'unread' === $sort ) {
			$ticket_args['orderby'] = 'updated_at';
			$ticket_args['order']   = 'DESC';
		} else {
			$ticket_args['orderby'] = 'updated_at';
			$ticket_args['order']   = 'DESC';
		}

		$tickets = PNPC_PSD_Ticket::get_by_user($current_user->ID, $ticket_args);
		$total_tickets = method_exists( 'PNPC_PSD_Ticket', 'get_user_count' ) ? PNPC_PSD_Ticket::get_user_count( $current_user->ID, $ticket_args ) : count( (array) $tickets );

		if ( 'unread' === $sort && ! empty( $tickets ) ) {
			$unread = array();
			$read   = array();

			// Compute "unread" the same way the list template does (role-level columns when present, else per-user meta fallback).
			foreach ( (array) $tickets as $t ) {
				$customer_viewed_raw = ! empty( $t->last_customer_viewed_at ) ? (string) $t->last_customer_viewed_at : '';
				$staff_activity_raw  = ! empty( $t->last_staff_activity_at ) ? (string) $t->last_staff_activity_at : '';

				if ( '' === $customer_viewed_raw ) {
					$last_view_meta = get_user_meta( $current_user->ID, 'pnpc_psd_ticket_last_view_' . intval( $t->id ), true );
					$customer_viewed_ts = $last_view_meta ? ( is_numeric( $last_view_meta ) ? intval( $last_view_meta ) : strtotime( (string) $last_view_meta ) ) : 0;
				} else {
					$customer_viewed_ts = strtotime( $customer_viewed_raw . ' UTC' );
				}

				if ( '' !== $staff_activity_raw ) {
					$staff_activity_ts = strtotime( $staff_activity_raw . ' UTC' );
				} else {
					// Best-effort fallback: treat the ticket updated time as "latest activity".
					$staff_activity_ts = ! empty( $t->updated_at ) ? strtotime( (string) $t->updated_at . ' UTC' ) : 0;
				}

				$is_unread = ( $staff_activity_ts > $customer_viewed_ts );

				if ( $is_unread ) {
					$unread[] = $t;
				} else {
					$read[] = $t;
				}
			}
			$tickets = array_merge( $unread, $read );
		}

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/my-tickets.php';
		return ob_get_clean();
	}

	/**
	* Render ticket detail.
	*
	* @param mixed $atts
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function render_ticket_detail($atts)
	{
		if (! is_user_logged_in()) {
			return $this->render_login_gate( function_exists('pnpc_psd_get_my_tickets_url') ? pnpc_psd_get_my_tickets_url() : home_url('/') );
		}
$ticket_id = isset( $_GET['ticket_id'] ) ? absint( wp_unslash( $_GET['ticket_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only ticket selection.
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

	/**
	* Render profile settings.
	*
	* @param mixed $atts
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function render_profile_settings($atts)
	{
		if (! is_user_logged_in()) {
			return $this->render_login_gate( function_exists('pnpc_psd_get_dashboard_url') ? pnpc_psd_get_dashboard_url() : home_url('/') );
		}
ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/profile-settings.php';
		return ob_get_clean();
	}

	/**
	* Render services.
	*
	* @param mixed $atts
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function render_services($atts)
	{
		// Services is a neutral extension seam in the Free plugin.
		// - Wizard templates may include [pnpc_services] in the customer dashboard layout.
		// - Free should not show notices or dependency warnings.
		// - Extensions may inject output via the filter below.
		if ( ! is_user_logged_in() ) {
			return $this->render_login_gate( function_exists('pnpc_psd_get_dashboard_url') ? pnpc_psd_get_dashboard_url() : home_url('/') );
		}

		$atts = shortcode_atts(array('limit' => 4), (array) $atts, 'pnpc_services');
		/**
		 * Filter: render services block output.
		 *
		 * Free returns an empty string by default.
		 * Extensions can hook this to output a services/products UI.
		 *
		 * @param string $output Default output (empty).
		 * @param array  $atts   Shortcode attributes.
		 */
		$output = apply_filters( 'pnpc_psd_services_output', '', (array) $atts );

		// Allow extensions to enqueue assets when the block is present.
		do_action( 'pnpc_psd_services_enqueue_assets', (array) $atts );

		return (string) $output;
	}

	/**
	* Normalize files array.
	*
	* @param mixed $file_post
	*
	* @since 1.1.1.4
	*
	* @return mixed
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
				$item[$k] = isset($file_post[$k][$i]) ? $file_post[$k][$i] :  null;
			}
			$files[] = $item;
		}
		return $files;
	}

	/**
	* Ajax create ticket.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_create_ticket()
	{
		// Defensive: ensure no stray output (notices/warnings) corrupts JSON responses for AJAX callers.
		$__pnpc_ob_started = false;
		if ( 0 === ob_get_level() ) {
			$__pnpc_ob_started = true;
			ob_start();
		}
		$__pnpc_json_error = function( $message ) use ( &$__pnpc_ob_started ) {
			if ( $__pnpc_ob_started && ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_error( array( 'message' => $message ) );
		};
		$__pnpc_json_success = function( $payload ) use ( &$__pnpc_ob_started ) {
			if ( $__pnpc_ob_started && ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_success( $payload );
		};

		// Defensive: catch non-Throwable fatals during this AJAX request and return a JSON error instead of a white-screen/critical error.
		$__pnpc_fatal_ref = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'pnpc_psd_', true );
		register_shutdown_function(
			function () use ( $__pnpc_fatal_ref, &$__pnpc_ob_started ) {
				$err = error_get_last();
				if ( empty( $err ) ) {
					return;
				}
				$fatal_types = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR );
				if ( ! in_array( (int) $err['type'], $fatal_types, true ) ) {
					return;
				}
				// Best-effort: clear any buffered output so JSON is not corrupted.
				while ( ob_get_level() > 0 ) {
					ob_end_clean();
				}
				// Persist the last fatal for admin review (lightweight, no PII).
				update_option(
					'pnpc_psd_last_create_ticket_fatal',
					array(
						'ts'   => time(),
						'ref'  => $__pnpc_fatal_ref,
						'msg'  => isset( $err['message'] ) ? (string) $err['message'] : '',
						'file' => isset( $err['file'] ) ? (string) $err['file'] : '',
						'line' => isset( $err['line'] ) ? (int) $err['line'] : 0,
					),
					false
				);
				// Return a generic error to the browser; the ticket may still have been created.
				nocache_headers();
				header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
				echo wp_json_encode(
					array(
						'success' => false,
						'data'    => array(
							'message' => __( 'An internal error occurred while finalizing your ticket. The ticket may still have been created—please refresh your tickets list. If the issue persists, contact support and provide reference:', 'pnpc-pocket-service-desk' ) . ' ' . $__pnpc_fatal_ref,
							'ref'     => $__pnpc_fatal_ref,
						),
					)
				);
				exit;
			}
		);


		try {

		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'pnpc_psd_public_nonce') && ! wp_verify_nonce($nonce, 'pnpc_psd_admin_nonce')) {
			$__pnpc_json_error( __('Security check failed.  Please refresh and try again.', 'pnpc-pocket-service-desk') );
		}

		if (! is_user_logged_in()) {
			$__pnpc_json_error( __('You must be logged in. ', 'pnpc-pocket-service-desk') );
		}

		if ( ! current_user_can( 'pnpc_psd_create_tickets' ) ) {
			$__pnpc_json_error( __( 'Permission denied.', 'pnpc-pocket-service-desk' ) );
		}

		$current_user = wp_get_current_user();
		$subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
		$description = isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '';
		$priority = isset($_POST['priority']) ? sanitize_text_field(wp_unslash($_POST['priority'])) : 'normal';

		if (empty($subject) || empty($description)) {
			$__pnpc_json_error( __('Please fill in all required fields.', 'pnpc-pocket-service-desk') );
		}

		$attachments = array();
		$att_skipped = array();
		if (! empty($_FILES) && isset($_FILES['attachments'])) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$files = $this->normalize_files_array($_FILES['attachments']);
			$allowed_types = function_exists( 'pnpc_psd_get_allowed_file_types_list' )
				? pnpc_psd_get_allowed_file_types_list()
				: array_map( 'trim', preg_split( '/[s,;]+/', (string) get_option( 'pnpc_psd_allowed_file_types', 'jpg,jpeg,png,gif,webp,pdf,txt,csv,doc,docx,xls,xlsx,zip' ) ) );
			foreach ($files as $file) {
				if (empty($file['name'])) {
					continue;
				}
				// Respect PHP upload error codes first. If the server rejected the upload, surface the real reason.
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
				foreach ($allowed_types as $allow_item) {
					$allow_item = strtolower(trim((string) $allow_item));
					if ('' === $allow_item) {
						continue;
					}
					if (false !== strpos($allow_item, '/')) {
						// MIME match.
						if ($mime === $allow_item) {
							$allowed_ok = true;
							break;
						}
						// Support image/* style patterns.
						if (substr($allow_item, -2) === '/*' && 0 === strpos($mime, rtrim($allow_item, '*'))) {
							$allowed_ok = true;
							break;
						}
					} else {
						// Extension match (treat jpg/jpeg/jpe as equivalent).
						if ( $file_ext && ( $file_ext === $allow_item || ( isset( $ext_alias[ $allow_item ] ) && in_array( $file_ext, $ext_alias[ $allow_item ], true ) ) ) ) {
							$allowed_ok = true;
							break;
						}
					}
				}
				// If the allowlist is MIME-only, accept well-known extensions that correspond to allowed MIME types.
				if ( ! $allowed_ok && $file_ext ) {
					$mime_to_exts = array(
						'image/jpeg'       => array( 'jpg', 'jpeg', 'jpe' ),
						'image/png'        => array( 'png' ),
						'application/pdf'  => array( 'pdf' ),
						'image/gif'        => array( 'gif' ),
						'image/webp'       => array( 'webp' ),
					);
					foreach ( (array) $allowed_types as $allow_item ) {
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
						'allow'  => implode( ',', (array) $allowed_types ),
					);
					continue;
				}
				// Enforce attachment size cap (limit-aware).
				$max_bytes = function_exists( 'pnpc_psd_get_max_attachment_bytes' ) ? (int) pnpc_psd_get_max_attachment_bytes() : (5 * 1024 * 1024);
				$server_max_bytes = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
				$effective_max_bytes = ( $server_max_bytes > 0 ) ? min( $max_bytes, $server_max_bytes ) : $max_bytes;
				if ( isset( $file['size'] ) && (int) $file['size'] > $effective_max_bytes ) {
					$att_skipped[] = array('file' => $file_name, 'reason' => 'size', 'size' => (int) $file['size'], 'max' => (int) $effective_max_bytes);
					continue;
				}
				$move = wp_handle_upload($file, array('test_form' => false));
				if (isset($move['error'])) {
					// Preserve the underlying WP upload message so we do not misdiagnose as "size".
					$att_skipped[] = array('file' => $file_name, 'reason' => 'upload', 'msg' => (string) $move['error']);
					continue;
				}
				$attachments[] = array(
					'file_name' => sanitize_file_name($file['name']),
					'file_path' => $move['file'],
					'file_type' => $mime,
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
			if ( function_exists( 'pnpc_psd_debug_log' ) ) {
				pnpc_psd_debug_log( 'ajax_create_ticket_failed', $error_detail );
			}
			$__pnpc_json_error( __('Failed to create ticket.  Please try again or contact support.', 'pnpc-pocket-service-desk') );
		}

		// Attachments are persisted once, below.
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
							// Ticket-level attachment (response_id=0). Using NULL here can fail under strict SQL modes.
							'response_id'  => 0,
							'file_name'    => sanitize_file_name( $att_name ),
							'file_path'    => sanitize_text_field( $att_url ),
							'file_type'    => isset( $att['file_type'] ) ? sanitize_text_field( (string) $att['file_type'] ) : '',
							'file_size'    => isset( $att['file_size'] ) ? absint( $att['file_size'] ) : 0,
							'uploaded_by'  => isset( $att['uploaded_by'] ) ? absint( $att['uploaded_by'] ) : absint( $current_user->ID ),
							'created_at'   => current_time( 'mysql', true ),
						),
						array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s' )
					);
			}
		}
		$ticket = PNPC_PSD_Ticket::get($ticket_id);
		$detail_url = function_exists('pnpc_psd_get_ticket_detail_url') ? pnpc_psd_get_ticket_detail_url($ticket_id) : '';

		// If files were rejected, append a non-blocking note (avoid silent regressions).
		$note = '';
		if ( ! empty( $att_skipped ) ) {
			$max_note = '';
			$detail_note = '';
			foreach ( (array) $att_skipped as $sk ) {
				if ( isset( $sk['reason'] ) && 'size' === (string) $sk['reason'] && isset( $sk['max'] ) ) {
					$max_human = function_exists( 'pnpc_psd_format_filesize' ) ? pnpc_psd_format_filesize( (int) $sk['max'] ) : ( (int) $sk['max'] . ' bytes' );
					$max_note = ' ' . sprintf(
						/* translators: %s: max attachment size */
						esc_html__( 'Max per file: %s.', 'pnpc-pocket-service-desk' ),
						$max_human
					);
					// Provide a concrete comparison so size issues are diagnosable.
					if ( isset( $sk['size'] ) ) {
						$size_human = function_exists( 'pnpc_psd_format_filesize' ) ? pnpc_psd_format_filesize( (int) $sk['size'] ) : ( (int) $sk['size'] . ' bytes' );
						$detail_note = ' ' . sprintf(
							/* translators: 1: file size, 2: max size */
							esc_html__( 'File size was %1$s (max %2$s).', 'pnpc-pocket-service-desk' ),
							$size_human,
							$max_human
						);
					}
					break;
				}
				if ( empty( $detail_note ) && isset( $sk['reason'] ) && 'type' === (string) $sk['reason'] ) {
					$det = '';
					if ( ! empty( $sk['mime'] ) ) {
						$det .= ' ' . sprintf( esc_html__( 'Detected type: %s.', 'pnpc-pocket-service-desk' ), esc_html( (string) $sk['mime'] ) );
					}
					if ( ! empty( $sk['ext'] ) ) {
						$det .= ' ' . sprintf( esc_html__( 'Extension: %s.', 'pnpc-pocket-service-desk' ), esc_html( (string) $sk['ext'] ) );
					}
					if ( ! empty( $sk['allow'] ) ) {
						$det .= ' ' . sprintf( esc_html__( 'Allowed: %s.', 'pnpc-pocket-service-desk' ), esc_html( (string) $sk['allow'] ) );
					}
					$detail_note = trim( $det );
				}
				if ( empty( $detail_note ) && isset( $sk['reason'] ) && 'php' === (string) $sk['reason'] && isset( $sk['code'] ) ) {
					$detail_note = ' ' . sprintf(
						/* translators: %d: PHP upload error code */
						esc_html__( 'Upload rejected by server (code %d).', 'pnpc-pocket-service-desk' ),
						(int) $sk['code']
					);
				}
				if ( empty( $detail_note ) && isset( $sk['reason'] ) && 'upload' === (string) $sk['reason'] && ! empty( $sk['msg'] ) ) {
					// Keep this short; wp_handle_upload messages are already user-facing.
					$detail_note = ' ' . esc_html( (string) $sk['msg'] );
				}
			}
			$note = ' ' . sprintf(
				/* translators: 1: number of attachments skipped, 2: max size note */
				esc_html__( 'Note: %1$d attachment(s) were skipped due to type/size/upload rules.%2$s', 'pnpc-pocket-service-desk' ),
				count( $att_skipped ),
				$max_note . $detail_note
			);
		}

		$__pnpc_json_success( array(
			'message' => __('Ticket created successfully. ', 'pnpc-pocket-service-desk') . $note,
			'ticket_number' => ( is_object( $ticket ) && isset( $ticket->ticket_number ) ) ? $ticket->ticket_number : '',
			'ticket_id' => $ticket_id,
			'ticket_detail_url' => $detail_url,
		) );
		} catch (\Throwable $e) {
			if ( function_exists( 'pnpc_psd_debug_log' ) ) {
				pnpc_psd_debug_log( 'ajax_create_ticket_fatal', array( 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine() ) );
			}
			$__pnpc_json_error( __( 'An unexpected error occurred while creating the ticket. Please reload and try again.', 'pnpc-pocket-service-desk' ) );
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
		// Defensive: ensure no stray output (notices/warnings) corrupts JSON responses for AJAX callers.
		$__pnpc_ob_started = false;
		if ( 0 === ob_get_level() ) {
			$__pnpc_ob_started = true;
			ob_start();
		}
		$__pnpc_json_error = function( $message ) use ( &$__pnpc_ob_started ) {
			if ( $__pnpc_ob_started && ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_error( array( 'message' => $message ) );
		};
		$__pnpc_json_success = function( $payload ) use ( &$__pnpc_ob_started ) {
			if ( $__pnpc_ob_started && ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_success( $payload );
		};
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'pnpc_psd_public_nonce') && ! wp_verify_nonce($nonce, 'pnpc_psd_admin_nonce')) {
			$__pnpc_json_error( __('Security check failed.  Please refresh and try again.', 'pnpc-pocket-service-desk') );
		}

		if (! is_user_logged_in()) {
			$__pnpc_json_error( __('You must be logged in. ', 'pnpc-pocket-service-desk') );
		}

		if ( ! current_user_can( 'pnpc_psd_view_own_tickets' ) && ! current_user_can( 'pnpc_psd_respond_to_tickets' ) ) {
			$__pnpc_json_error( __( 'Permission denied.', 'pnpc-pocket-service-desk' ) );
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

		$current_user = wp_get_current_user();
		$viewer_id = is_user_logged_in() ? intval($current_user->ID) : 0;
		$ticket_owner_id = isset($ticket->user_id) ? intval($ticket->user_id) : 0;

		if ($ticket_owner_id !== $viewer_id && ! current_user_can('pnpc_psd_respond_to_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		$attachments = array();
		$att_skipped = array();
		if (! empty($_FILES) && isset($_FILES['attachments'])) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$files = $this->normalize_files_array($_FILES['attachments']);
			$allowed_list = function_exists( 'pnpc_psd_get_allowed_file_types_list' )
				? pnpc_psd_get_allowed_file_types_list()
				: array_map( 'trim', preg_split( '/[s,;]+/', (string) get_option( 'pnpc_psd_allowed_file_types', 'jpg,jpeg,png,gif,webp,pdf,txt,csv,doc,docx,xls,xlsx,zip' ) ) );
			foreach ($files as $file) {
				if (empty($file['name'])) {
					continue;
				}
				// Respect PHP upload error codes first. If the server rejected the upload, surface the real reason.
				if ( isset( $file['error'] ) && (int) $file['error'] !== UPLOAD_ERR_OK ) {
					$att_skipped[] = array('file' => (string) $file['name'], 'reason' => 'php', 'code' => (int) $file['error']);
					continue;
				}
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
						if ($mime === $allow_item) {
							$allowed_ok = true;
							break;
						}
						if (substr($allow_item, -2) === '/*' && 0 === strpos($mime, rtrim($allow_item, '*'))) {
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
						'image/jpeg'       => array( 'jpg', 'jpeg', 'jpe' ),
						'image/png'        => array( 'png' ),
						'application/pdf'  => array( 'pdf' ),
						'image/gif'        => array( 'gif' ),
						'image/webp'       => array( 'webp' ),
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
					// Preserve the underlying WP upload message so we do not misdiagnose as "size".
					$att_skipped[] = array('file' => $file_name, 'reason' => 'upload', 'msg' => (string) $move['error']);
					continue;
				}
				$attachments[] = array(
					'file_name'   => sanitize_file_name($file['name']),
					'file_path'   => $move['file'],
					'file_type'   => $mime,
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
			$note = '';
			if ( ! empty( $att_skipped ) ) {
				$max_note = '';
				$detail_note = '';
				foreach ( (array) $att_skipped as $sk ) {
					if ( isset( $sk['reason'] ) && 'size' === (string) $sk['reason'] && isset( $sk['max'] ) ) {
						$max_human = function_exists( 'pnpc_psd_format_filesize' ) ? pnpc_psd_format_filesize( (int) $sk['max'] ) : ( (int) $sk['max'] . ' bytes' );
						$max_note = ' ' . sprintf(
							/* translators: %s: max attachment size */
							esc_html__( 'Max per file: %s.', 'pnpc-pocket-service-desk' ),
							$max_human
						);
						if ( isset( $sk['size'] ) ) {
							$size_human = function_exists( 'pnpc_psd_format_filesize' ) ? pnpc_psd_format_filesize( (int) $sk['size'] ) : ( (int) $sk['size'] . ' bytes' );
							$detail_note = ' ' . sprintf(
								/* translators: 1: file size, 2: max size */
								esc_html__( 'File size was %1$s (max %2$s).', 'pnpc-pocket-service-desk' ),
								$size_human,
								$max_human
							);
						}
						break;
					}
					if ( empty( $detail_note ) && isset( $sk['reason'] ) && 'type' === (string) $sk['reason'] ) {
						$det = '';
						if ( ! empty( $sk['mime'] ) ) {
							$det .= ' ' . sprintf( esc_html__( 'Detected type: %s.', 'pnpc-pocket-service-desk' ), esc_html( (string) $sk['mime'] ) );
						}
						if ( ! empty( $sk['ext'] ) ) {
							$det .= ' ' . sprintf( esc_html__( 'Extension: %s.', 'pnpc-pocket-service-desk' ), esc_html( (string) $sk['ext'] ) );
						}
						if ( ! empty( $sk['allow'] ) ) {
							$det .= ' ' . sprintf( esc_html__( 'Allowed: %s.', 'pnpc-pocket-service-desk' ), esc_html( (string) $sk['allow'] ) );
						}
						$detail_note = trim( $det );
					}
					if ( empty( $detail_note ) && isset( $sk['reason'] ) && 'php' === (string) $sk['reason'] && isset( $sk['code'] ) ) {
						$detail_note = ' ' . sprintf(
							/* translators: %d: PHP upload error code */
							esc_html__( 'Upload rejected by server (code %d).', 'pnpc-pocket-service-desk' ),
							(int) $sk['code']
						);
					}
					if ( empty( $detail_note ) && isset( $sk['reason'] ) && 'upload' === (string) $sk['reason'] && ! empty( $sk['msg'] ) ) {
						$detail_note = ' ' . esc_html( (string) $sk['msg'] );
					}
				}
				$note = ' ' . sprintf(
					/* translators: 1: number of attachments skipped, 2: max size note */
					esc_html__( 'Note: %1$d attachment(s) were skipped due to type/size/upload rules.%2$s', 'pnpc-pocket-service-desk' ),
					count( $att_skipped ),
					$max_note . $detail_note
				);
			}
			wp_send_json_success(array('message' => __('Response added successfully.', 'pnpc-pocket-service-desk') . $note));
		}
		wp_send_json_error(array('message' => __('Failed to add response. ', 'pnpc-pocket-service-desk')));
	}

	/**
	* Ajax upload profile image.
	*
	* @since 1.1.1.4
	*
	* @return mixed
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

		if ( ! current_user_can( 'pnpc_psd_view_own_tickets' ) && ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'pnpc-pocket-service-desk' ) ) );
		}

		$current_user = wp_get_current_user();
		$tab          = isset($_POST['tab']) ? sanitize_key(wp_unslash($_POST['tab'])) : 'open';
		if (! in_array($tab, array('open', 'closed'), true)) {
			$tab = 'open';
		}

		$sort = isset( $_POST['sort'] ) ? sanitize_key( wp_unslash( $_POST['sort'] ) ) : 'latest';
		$page = isset( $_POST['page'] ) ? max( 1, absint( wp_unslash( $_POST['page'] ) ) ) : 1;
		$per_page = (int) get_option( 'pnpc_psd_tickets_per_page', 20 );
		if ( $per_page < 1 ) {
			$per_page = 20;
		}
		if ( $per_page > 100 ) {
			$per_page = 100;
		}
		$offset = ( $page - 1 ) * $per_page;
		if ( ! in_array( $sort, array( 'latest', 'newest', 'oldest', 'unread' ), true ) ) {
			$sort = 'latest';
		}

		$ticket_args = array(
			'limit'  => $per_page,
			'offset' => $offset,
		);
		if ('closed' === $tab) {
			$ticket_args['status'] = 'closed';
		} else {
			$ticket_args['exclude_statuses'] = array('closed');
		}

		if ( 'oldest' === $sort ) {
			$ticket_args['orderby'] = 'created_at';
			$ticket_args['order']   = 'ASC';
		} elseif ( 'newest' === $sort ) {
			$ticket_args['orderby'] = 'created_at';
			$ticket_args['order']   = 'DESC';
		} elseif ( 'unread' === $sort ) {
			$ticket_args['orderby'] = 'updated_at';
			$ticket_args['order']   = 'DESC';
		} else {
			$ticket_args['orderby'] = 'updated_at';
			$ticket_args['order']   = 'DESC';
		}

		$tickets = PNPC_PSD_Ticket::get_by_user($current_user->ID, $ticket_args);
		$total_tickets = method_exists( 'PNPC_PSD_Ticket', 'get_user_count' ) ? PNPC_PSD_Ticket::get_user_count( $current_user->ID, $ticket_args ) : count( (array) $tickets );
		$current_page = (int) $page;

		if ( 'unread' === $sort && ! empty( $tickets ) ) {
			$unread = array();
			$read   = array();

			// Compute "unread" the same way the list template does (role-level columns when present, else per-user meta fallback).
			foreach ( (array) $tickets as $t ) {
				$customer_viewed_raw = ! empty( $t->last_customer_viewed_at ) ? (string) $t->last_customer_viewed_at : '';
				$staff_activity_raw  = ! empty( $t->last_staff_activity_at ) ? (string) $t->last_staff_activity_at : '';

				if ( '' === $customer_viewed_raw ) {
					$last_view_meta = get_user_meta( $current_user->ID, 'pnpc_psd_ticket_last_view_' . intval( $t->id ), true );
					$customer_viewed_ts = $last_view_meta ? ( is_numeric( $last_view_meta ) ? intval( $last_view_meta ) : strtotime( (string) $last_view_meta ) ) : 0;
				} else {
					$customer_viewed_ts = strtotime( $customer_viewed_raw . ' UTC' );
				}

				if ( '' !== $staff_activity_raw ) {
					$staff_activity_ts = strtotime( $staff_activity_raw . ' UTC' );
				} else {
					// Best-effort fallback: treat the ticket updated time as "latest activity".
					$staff_activity_ts = ! empty( $t->updated_at ) ? strtotime( (string) $t->updated_at . ' UTC' ) : 0;
				}

				$is_unread = ( $staff_activity_ts > $customer_viewed_ts );

				if ( $is_unread ) {
					$unread[] = $t;
				} else {
					$read[] = $t;
				}
			}
			$tickets = array_merge( $unread, $read );
		}

		ob_start();
		include PNPC_PSD_PLUGIN_DIR . 'public/views/my-tickets-list.php';
		$html = ob_get_clean();

		wp_send_json_success(array('html' => $html));
	}

	/**
	* Ajax get ticket detail.
	*
	* @since 1.1.1.4
	*
	* @return mixed
	*/
	public function ajax_get_ticket_detail()
	{
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'pnpc_psd_public_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed. ', 'pnpc-pocket-service-desk')));
		}

		if (! is_user_logged_in()) {
			wp_send_json_error(array('message' => __('You must be logged in.', 'pnpc-pocket-service-desk')));
		}

		$ticket_id = isset($_POST['ticket_id']) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
		if (! $ticket_id) {
			wp_send_json_error(array('message' => __('Invalid ticket ID.', 'pnpc-pocket-service-desk')));
		}

		$ticket = PNPC_PSD_Ticket::get($ticket_id);
		if (! $ticket) {
			wp_send_json_error(array('message' => __('Ticket not found.', 'pnpc-pocket-service-desk')));
		}

		if ( ! current_user_can( 'pnpc_psd_view_own_tickets' ) && ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'pnpc-pocket-service-desk' ) ) );
		}

		$current_user = wp_get_current_user();
		$viewer_id = intval($current_user->ID);
		$ticket_owner_id = intval($ticket->user_id);

		if ($ticket_owner_id !== $viewer_id && ! current_user_can('pnpc_psd_view_tickets')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'pnpc-pocket-service-desk')));
		}

		// Mark viewed for unread/activity tracking.
		if ( class_exists( 'PNPC_PSD_Ticket' ) ) {
			if ( $ticket_owner_id === $viewer_id ) {
				PNPC_PSD_Ticket::mark_customer_viewed( $ticket_id );
				update_user_meta( $viewer_id, 'pnpc_psd_ticket_last_view_' . $ticket_id, time() );
			} else {
				PNPC_PSD_Ticket::mark_staff_viewed( $ticket_id );
			}
		}

		$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket_id);

		wp_send_json_success(array(
			'ticket' => $ticket,
			'responses' => $responses,
		));
	}
}