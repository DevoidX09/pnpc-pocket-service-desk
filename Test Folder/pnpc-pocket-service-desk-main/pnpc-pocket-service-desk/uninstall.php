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

// Delete plugin options.
delete_option( 'pnpc_psd_version' );
delete_option( 'pnpc_psd_db_version' );
delete_option( 'pnpc_psd_ticket_counter' );
delete_option( 'pnpc_psd_email_notifications' );
delete_option( 'pnpc_psd_auto_assign_tickets' );
delete_option( 'pnpc_psd_allowed_file_types' );

// Drop custom tables (uncomment if you want to remove all data on uninstall).
/*
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_tickets" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_ticket_responses" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pnpc_psd_ticket_attachments" );
*/

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

// Delete user meta for profile images.
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'pnpc_psd_profile_image'" );
