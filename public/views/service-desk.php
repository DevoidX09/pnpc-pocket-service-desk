<?php

/**
 * Public service desk dashboard view (patched to use helpers for timestamps)
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

// Count unread customer-facing ticket updates. This must represent unviewed updates,
// not merely the presence of an open ticket. Keep this aligned with the My Tickets
// green-dot semantics by counting actual responses after the customer's last view.
$updated_open_count = ( $user_id && class_exists( 'PNPC_PSD_Ticket_Response' ) && method_exists( 'PNPC_PSD_Ticket_Response', 'count_unread_tickets_for_customer' ) )
	? (int) PNPC_PSD_Ticket_Response::count_unread_tickets_for_customer( $user_id )
	: 0;
?>
<div class="pnpc-psd-dashboard" data-pnpc-psd-dashboard="1">

	<?php if ((bool) get_option('pnpc_psd_show_welcome_service_desk', 1) && $user_id) : ?>
		<h2><?php /* translators: %s: current user's display name. */ printf(esc_html__('Welcome, %s!', 'pnpc-pocket-service-desk'), esc_html($current_user->display_name)); ?></h2>
	<?php endif; ?>

	<div class="pnpc-psd-ticket-totals">
		<div class="pnpc-psd-ticket-totals-header">
			<h3><?php esc_html_e('Ticket Totals', 'pnpc-pocket-service-desk'); ?></h3>
			<p><?php esc_html_e('A live snapshot of your support activity.', 'pnpc-pocket-service-desk'); ?></p>
		</div>
		<div class="pnpc-psd-ticket-total-grid">
			<div class="pnpc-psd-dashboard-total-card pnpc-psd-dashboard-total-card-open">
				<span class="pnpc-psd-new-indicator-badge pnpc-psd-dashboard-alert-badge<?php echo ! empty( $updated_open_count ) ? ' is-visible' : ''; ?>" data-pnpc-psd-dashboard-alert="1" title="<?php esc_attr_e('New unread activity', 'pnpc-pocket-service-desk'); ?>" aria-label="<?php esc_attr_e('New unread activity', 'pnpc-pocket-service-desk'); ?>" <?php echo empty( $updated_open_count ) ? 'hidden' : ''; ?>><?php esc_html_e('New', 'pnpc-pocket-service-desk'); ?></span>
				<div class="pnpc-psd-total-number" data-pnpc-psd-open-count="1">
					<?php echo esc_html($open_count); ?>
				</div>
				<div class="pnpc-psd-total-label"><?php esc_html_e('Open / In-Progress', 'pnpc-pocket-service-desk'); ?></div>
			</div>
			<div class="pnpc-psd-dashboard-total-card pnpc-psd-dashboard-total-card-closed">
				<div class="pnpc-psd-total-number" data-pnpc-psd-closed-count="1"><?php echo esc_html($closed_count); ?></div>
				<div class="pnpc-psd-total-label"><?php esc_html_e('Closed', 'pnpc-pocket-service-desk'); ?></div>
			</div>
		</div>
	</div>
</div>
