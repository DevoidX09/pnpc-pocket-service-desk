<?php
/**
 * Fired when the plugin is uninstalled
 *
 * @package PNPC_Pocket_Service_Desk
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * Delete options by prefix during full uninstall cleanup.
 *
 * @param string $prefix Option prefix.
 * @return void
 */
function pnpc_psd_uninstall_delete_options_by_prefix( $prefix ) {
	global $wpdb;

	$like = $wpdb->esc_like( $prefix ) . '%';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup only.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$like
		)
	);
}

/**
 * Delete user meta by prefix during full uninstall cleanup.
 *
 * @param string $prefix User meta prefix.
 * @return void
 */
function pnpc_psd_uninstall_delete_user_meta_by_prefix( $prefix ) {
	global $wpdb;

	$like = $wpdb->esc_like( $prefix ) . '%';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup only.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
			$like
		)
	);
}

/**
 * Delete post meta by prefix during full uninstall cleanup.
 *
 * @param string $prefix Post meta prefix.
 * @return void
 */
function pnpc_psd_uninstall_delete_post_meta_by_prefix( $prefix ) {
	global $wpdb;

	$like = $wpdb->esc_like( $prefix ) . '%';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup only.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
			$like
		)
	);
}

/**
 * Remove Pro add-on data created by PNPC Pocket Service Desk Pro.
 *
 * This is intentionally duplicated in the base plugin uninstall handler so the
 * base plugin's "Delete data on uninstall" setting can clean the add-on data
 * even when WordPress only runs the base plugin uninstaller.
 *
 * @return void
 */
function pnpc_psd_pro_uninstall_cleanup_all_data() {
	global $wpdb;

	// Stop scheduled email polling/license checks.
	wp_clear_scheduled_hook( 'pnpc_psd_pro_poll_email_replies' );
	wp_clear_scheduled_hook( 'pnpc_psd_pro_daily_license_check' );

	// Delete Pro options, diagnostics, setup status, and Pro transients/timeouts.
	$option_prefixes = array(
		'pnpc_psd_pro_',
		'pnpc_psd_diag_',
		'pnpc_psd_license_',
		'_transient_pnpc_psd_pro_',
		'_transient_timeout_pnpc_psd_pro_',
		'_transient_pnpc_psd_internal_chat_active_',
		'_transient_timeout_pnpc_psd_internal_chat_active_',
	);

	foreach ( $option_prefixes as $prefix ) {
		$like = $wpdb->esc_like( $prefix ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup only.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);
	}

	delete_option( 'pnpc_psd_diag_log' );

	// Delete saved replies CPT content.
	$saved_reply_ids = get_posts(
		array(
			'post_type'      => 'pnpc_saved_reply',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	if ( is_array( $saved_reply_ids ) ) {
		foreach ( $saved_reply_ids as $saved_reply_id ) {
			wp_delete_post( absint( $saved_reply_id ), true );
		}
	}

	// Delete Pro user meta: allocations, client notes, and per-ticket chat seen markers.
	$user_meta_keys = array(
		'pnpc_psd_allocated_products',
		'pnpc_psd_client_internal_notes',
	);

	foreach ( $user_meta_keys as $meta_key ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup only.
		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $meta_key ), array( '%s' ) );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup only.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
			$wpdb->esc_like( 'pnpc_psd_internal_chat_last_seen_' ) . '%'
		)
	);

	// Delete Pro product/post meta.
	$post_meta_keys = array(
		'_pnpc_psd_pro_private_service_product',
	);

	foreach ( $post_meta_keys as $meta_key ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup only.
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ), array( '%s' ) );
	}

	// Delete Pro ticket meta if the base ticket meta table still exists.
	$ticket_meta_table = $wpdb->prefix . 'pnpc_psd_ticket_meta';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup only.
	$table_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$ticket_meta_table
		)
	);

	if ( $table_exists ) {
		$ticket_meta_keys = array(
			'pnpc_psd_internal_staff_chat',
			'pnpc_psd_internal_notes',
			'pnpc_psd_email_reply_token',
			'pnpc_psd_email_processed_message_ids',
			'pnpc_psd_created_at',
			'pnpc_psd_first_response_at',
			'pnpc_psd_closed_at',
			'pnpc_psd_assigned_agent_id',
			'pnpc_psd_source',
			'pnpc_psd_merged_into',
			'pnpc_psd_merged_at',
			'pnpc_psd_merged_by',
			'pnpc_psd_last_merged_ticket',
			'pnpc_psd_last_customer_activity_ts',
			'pnpc_psd_last_staff_activity_ts',
		);

		foreach ( $ticket_meta_keys as $meta_key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup only.
			$wpdb->delete( $ticket_meta_table, array( 'meta_key' => $meta_key ), array( '%s' ) );
		}
	}

	// Remove Pro-only capabilities and role.
	$pro_caps = array(
		'pnpc_psd_use_internal_chat',
		'pnpc_psd_manage_saved_replies',
		'pnpc_psd_use_saved_replies',
		'pnpc_psd_view_services',
		'pnpc_psd_merge_tickets',
		'pnpc_psd_view_advanced_stats',
	);

	foreach ( wp_roles()->roles as $role_name => $role_data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Role data is not needed.
		$role = get_role( $role_name );
		if ( ! $role ) {
			continue;
		}

		foreach ( $pro_caps as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	remove_role( 'pnpc_psd_manager' );
}


// Data retention policy:
// By default, preserve settings and user profile uploads across uninstall/reinstall.
// To fully remove plugin data, enable the "Delete data on uninstall" toggle in settings.
$delete_data = (bool) get_option( 'pnpc_psd_delete_data_on_uninstall', 0 ) && (bool) get_option( 'pnpc_psd_delete_data_on_uninstall_confirmed_at', false );

if ( $delete_data ) {

	// Remove Pro add-on data first, if the add-on has ever been installed.
	pnpc_psd_pro_uninstall_cleanup_all_data();

	// Delete only pages created by the plugin setup wizard/builder.
	$generated_page_ids = array();
	foreach ( array( 'pnpc_psd_dashboard_page_id', 'pnpc_psd_ticket_view_page_id' ) as $page_option ) {
		$page_id = absint( get_option( $page_option, 0 ) );
		if ( $page_id > 0 ) {
			$generated_page_ids[] = $page_id;
		}
	}

	$generated_pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_pnpc_psd_created_by_builder', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Uninstall cleanup only.
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Uninstall cleanup only.
		)
	);

	if ( is_array( $generated_pages ) ) {
		$generated_page_ids = array_merge( $generated_page_ids, array_map( 'absint', $generated_pages ) );
	}

	$generated_page_ids = array_unique( array_filter( $generated_page_ids ) );
	foreach ( $generated_page_ids as $generated_page_id ) {
		if ( (int) get_post_meta( $generated_page_id, '_pnpc_psd_created_by_builder', true ) === 1 ) {
			wp_delete_post( $generated_page_id, true );
		}
	}

	// Delete plugin options.
	delete_option( 'pnpc_psd_version' );
	delete_option( 'pnpc_psd_db_version' );
	delete_option( 'pnpc_psd_ticket_counter' );
	delete_option( 'pnpc_psd_email_notifications' );
	delete_option( 'pnpc_psd_auto_assign_tickets' );
	delete_option( 'pnpc_psd_default_agent_user_id' );
	delete_option( 'pnpc_psd_allowed_file_types' );
	delete_option( 'pnpc_psd_show_welcome_profile' );
	delete_option( 'pnpc_psd_show_welcome_service_desk' );
	delete_option( 'pnpc_psd_show_products' );
	delete_option( 'pnpc_psd_user_specific_products' );
	delete_option( 'pnpc_psd_enable_menu_badge' );
	delete_option( 'pnpc_psd_menu_badge_interval' );
	delete_option( 'pnpc_psd_enable_auto_refresh' );
	delete_option( 'pnpc_psd_auto_refresh_interval' );
	delete_option( 'pnpc_psd_tickets_per_page' );
	delete_option( 'pnpc_psd_dashboard_page_id' );
	delete_option( 'pnpc_psd_ticket_view_page_id' );
	delete_option( 'pnpc_psd_setup_completed_at' );
	delete_option( 'pnpc_psd_setup_editor' );
	delete_option( 'pnpc_psd_setup_error' );
	delete_option( 'pnpc_psd_error_log_cap' );
	delete_option( 'pnpc_psd_primary_button_color' );
	delete_option( 'pnpc_psd_primary_button_hover_color' );
	delete_option( 'pnpc_psd_logout_button_color' );
	delete_option( 'pnpc_psd_logout_button_hover_color' );
	delete_option( 'pnpc_psd_logout_redirect_page_id' );
	delete_option( 'pnpc_psd_secondary_button_color' );
	delete_option( 'pnpc_psd_secondary_button_hover_color' );
	delete_option( 'pnpc_psd_card_bg_color' );
	delete_option( 'pnpc_psd_card_bg_hover_color' );
	delete_option( 'pnpc_psd_card_title_color' );
	delete_option( 'pnpc_psd_card_title_hover_color' );
	delete_option( 'pnpc_psd_card_button_color' );
	delete_option( 'pnpc_psd_card_button_hover_color' );
	delete_option( 'pnpc_psd_my_tickets_card_bg_color' );
	delete_option( 'pnpc_psd_my_tickets_card_bg_hover_color' );
	delete_option( 'pnpc_psd_my_tickets_view_button_color' );
	delete_option( 'pnpc_psd_my_tickets_view_button_hover_color' );

	// Final sweep: remove all current and legacy Service Desk options that may not be listed above.
	pnpc_psd_uninstall_delete_options_by_prefix( 'pnpc_psd_' );
	pnpc_psd_uninstall_delete_options_by_prefix( '_transient_pnpc_psd_' );
	pnpc_psd_uninstall_delete_options_by_prefix( '_transient_timeout_pnpc_psd_' );
	delete_option( 'pnpc_psd_delete_data_on_uninstall' );
	delete_option( 'pnpc_psd_delete_data_on_uninstall_confirmed_at' );
}

// Drop custom tables only when delete_data is enabled.
if ( $delete_data ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_tickets" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_ticket_responses" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_ticket_attachments" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_audit_log" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_error_log" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_ticket_meta" );
}

// Remove custom capabilities from roles.
$roles = array( 'administrator', 'customer', 'subscriber' );
foreach ( $roles as $role_name ) {
	$role = get_role( $role_name );
	if ( $role ) {
		$role->remove_cap( 'pnpc_psd_view_tickets' );
		$role->remove_cap( 'pnpc_psd_respond_to_tickets' );
		$role->remove_cap( 'pnpc_psd_assign_tickets' );
		$role->remove_cap( 'pnpc_psd_delete_tickets' );
		$role->remove_cap( 'pnpc_psd_manage_settings' );
		$role->remove_cap( 'pnpc_psd_create_tickets' );
		$role->remove_cap( 'pnpc_psd_view_own_tickets' );
	}
}

// Remove custom roles.
remove_role( 'pnpc_psd_agent' );
remove_role( 'pnpc_psd_manager' );

// Delete user meta for profile images only when delete_data is enabled.
if ( $delete_data ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'pnpc_psd_profile_image'" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'pnpc_psd_profile_image_id'" );

	// Final sweep: remove current and legacy Service Desk metadata.
	pnpc_psd_uninstall_delete_user_meta_by_prefix( 'pnpc_psd_' );
	pnpc_psd_uninstall_delete_post_meta_by_prefix( 'pnpc_psd_' );
	pnpc_psd_uninstall_delete_post_meta_by_prefix( '_pnpc_psd_' );
}
