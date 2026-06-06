<?php
/**
 * Core diagnostics view.
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'pnpc-pocket-service-desk' ) );
}

require_once PNPC_PSD_PLUGIN_DIR . 'admin/class-pnpc-psd-error-log-table.php';

$error_table = new PNPC_PSD_Error_Log_Table();
$error_table->prepare_items();

$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=pnpc_psd_export_error_log' ), 'pnpc_psd_export_error_log' );
$health     = function_exists( 'pnpc_psd_get_operational_health_summary' ) ? pnpc_psd_get_operational_health_summary() : array();
$status     = isset( $health['status'] ) ? $health['status'] : 'healthy';
$label      = isset( $health['label'] ) ? $health['label'] : __( 'Healthy', 'pnpc-pocket-service-desk' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Diagnostics', 'pnpc-pocket-service-desk' ); ?></h1>

	<div class="notice notice-info inline">
		<p><strong><?php esc_html_e( 'Operational Health:', 'pnpc-pocket-service-desk' ); ?></strong> <?php echo esc_html( $label ); ?></p>
	</div>

	<div class="card" style="max-width:900px;margin-top:16px;padding:18px;">
		<h2 style="margin-top:0;"><?php esc_html_e( 'Need Help?', 'pnpc-pocket-service-desk' ); ?></h2>
		<p><?php esc_html_e( 'Open the Support Hub to download a diagnostics-only support bundle or access your configured support portal.', 'pnpc-pocket-service-desk' ); ?></p>
		<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-support' ) ); ?>"><?php esc_html_e( 'Open Support Hub', 'pnpc-pocket-service-desk' ); ?></a></p>
	</div>

	<h2><?php esc_html_e( 'Environment', 'pnpc-pocket-service-desk' ); ?></h2>
	<table class="widefat striped" style="max-width: 900px;">
		<tbody>
			<tr><th scope="row"><?php esc_html_e( 'Product Edition', 'pnpc-pocket-service-desk' ); ?></th><td><?php esc_html_e( 'Core', 'pnpc-pocket-service-desk' ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Product Version', 'pnpc-pocket-service-desk' ); ?></th><td><?php echo esc_html( PNPC_PSD_VERSION ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Site URL', 'pnpc-pocket-service-desk' ); ?></th><td><?php echo esc_url( home_url() ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'WordPress Version', 'pnpc-pocket-service-desk' ); ?></th><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'PHP Version', 'pnpc-pocket-service-desk' ); ?></th><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Database Version', 'pnpc-pocket-service-desk' ); ?></th><td><?php global $wpdb; echo esc_html( $wpdb->db_version() ); ?></td></tr>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Error Log', 'pnpc-pocket-service-desk' ); ?></h2>
	<p><?php esc_html_e( 'This log records Service Desk failures only, including email, attachment, database, runtime, and licensing-related failures.', 'pnpc-pocket-service-desk' ); ?></p>
	<p><a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export Error Log CSV', 'pnpc-pocket-service-desk' ); ?></a></p>

	<form method="get">
		<input type="hidden" name="page" value="pnpc-service-desk-diagnostics" />
		<?php $error_table->display(); ?>
	</form>
</div>
