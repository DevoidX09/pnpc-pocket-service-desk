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
