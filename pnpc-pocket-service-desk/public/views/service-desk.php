<?php

/**
 * Public service desk dashboard view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if (! defined('ABSPATH')) {
	exit;
}

$current_user = wp_get_current_user();
$user_id      = ! empty($current_user->ID) ? (int) $current_user->ID : 0;

// Fetch tickets for counts
$tickets = PNPC_PSD_Ticket::get_by_user($user_id, array('limit' => 100));
$open_count = count(array_filter($tickets, function ($ticket) {
	return 'open' === $ticket->status || 'in-progress' === $ticket->status;
}));
$closed_count = count(array_filter($tickets, function ($ticket) {
	return 'closed' === $ticket->status;
}));

// Welcome toggle (service-desk specific)
$show_welcome_service = (bool) get_option('pnpc_psd_show_welcome_service_desk', 1);

?>
<div class="pnpc-psd-dashboard">

	<?php if ($show_welcome_service && $user_id) : ?>
		<h2><?php printf(esc_html__('Welcome, %s!', 'pnpc-pocket-service-desk'), esc_html($current_user->display_name)); ?></h2>
	<?php endif; ?>

	<!-- Ticket totals: single column, styled box -->
	<div class="pnpc-psd-ticket-totals" style="max-width:760px;margin:16px 0;padding:20px;border-radius:8px;background:linear-gradient(180deg,#ffffff,#f7f9fb);box-shadow:0 1px 4px rgba(0,0,0,0.04);border:1px solid #e6eef6;">
		<h3 style="margin-top:0;color:#234; font-size:1.15rem;"><?php esc_html_e('Ticket Totals', 'pnpc-pocket-service-desk'); ?></h3>
		<div style="display:flex;gap:20px;align-items:stretch;">
			<div style="flex:1;padding:12px;border-radius:6px;background:#fff;border:1px solid #e6eef6;text-align:center;">
				<div style="font-size:20px;font-weight:700;color:#e05a4f;"><?php echo esc_html($open_count); ?></div>
				<div style="color:#666;"><?php esc_html_e('Open / In-Progress', 'pnpc-pocket-service-desk'); ?></div>
			</div>
			<div style="flex:1;padding:12px;border-radius:6px;background:#fff;border:1px solid #e6eef6;text-align:center;">
				<div style="font-size:20px;font-weight:700;color:#2b9f6a;"><?php echo esc_html($closed_count); ?></div>
				<div style="color:#666;"><?php esc_html_e('Closed', 'pnpc-pocket-service-desk'); ?></div>
			</div>
		</div>
	</div>

	<!-- Note: Services removed from this dashboard; use [pnpc_services] shortcode to render services elsewhere. -->

</div>