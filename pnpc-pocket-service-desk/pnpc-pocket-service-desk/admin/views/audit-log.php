<?php
/**
 * Admin Audit Log view.
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'pnpc-pocket-service-desk' ) );
}

require_once PNPC_PSD_PLUGIN_DIR . 'admin/class-pnpc-psd-audit-log-table.php';

$table = new PNPC_PSD_Audit_Log_Table();
$table->prepare_items();

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Audit Log', 'pnpc-pocket-service-desk' ); ?></h1>

	<form method="get">
		<input type="hidden" name="page" value="pnpc-service-desk-audit-log" />
		<?php $table->display(); ?>
	</form>
</div>
