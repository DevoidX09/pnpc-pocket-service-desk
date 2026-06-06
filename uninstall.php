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

// Data retention policy:
// By default, preserve settings and user profile uploads across uninstall/reinstall.
// To fully remove plugin data, enable the "Delete data on uninstall" toggle in settings.
$delete_data = (bool) get_option( 'pnpc_psd_delete_data_on_uninstall', 0 );

if ( $delete_data ) {

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
	delete_option( 'pnpc_psd_delete_data_on_uninstall' );
}

// Drop custom tables only when delete_data is enabled.
if ( $delete_data ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_tickets" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_ticket_responses" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_ticket_attachments" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_audit_log" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_error_log" );
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
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'pnpc_psd_profile_image'" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'pnpc_psd_profile_image_id'" );
}
