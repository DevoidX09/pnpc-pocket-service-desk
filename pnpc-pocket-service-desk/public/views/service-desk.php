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

// Count how many *new staff responses* exist on open / in-progress tickets since the customer last viewed them.
// We prefer v1.1.0+ role-level timestamps on the ticket row to avoid scanning responses when nothing changed.
$updated_open_count = 0;
if ( $user_id ) {
	foreach ( $tickets as $ticket ) {
		if ( 'open' !== $ticket->status && 'in-progress' !== $ticket->status ) {
			continue;
		}

		$customer_viewed_raw = ! empty( $ticket->last_customer_viewed_at ) ? (string) $ticket->last_customer_viewed_at : '';
		$staff_activity_raw  = ! empty( $ticket->last_staff_activity_at ) ? (string) $ticket->last_staff_activity_at : '';

		// If we have role-level timestamps and nothing has changed since the customer last viewed, skip any deeper work.
		if ( '' !== $customer_viewed_raw && '' !== $staff_activity_raw ) {
			$customer_viewed_ts = strtotime( $customer_viewed_raw . ' UTC' );
			$staff_activity_ts  = strtotime( $staff_activity_raw . ' UTC' );
			if ( $staff_activity_ts <= $customer_viewed_ts ) {
				continue;
			}
		}

		// Count new staff responses since the customer last viewed.
		// Prefer the role-level viewed timestamp; fall back to legacy per-ticket user meta key.
		$last_view_ts = 0;
		if ( '' !== $customer_viewed_raw ) {
			$last_view_ts = strtotime( $customer_viewed_raw . ' UTC' );
		} else {
			$last_view_key  = 'pnpc_psd_ticket_last_view_' . (int) $ticket->id;
			$last_view_raw  = get_user_meta( $user_id, $last_view_key, true );
			$last_view_ts   = $last_view_raw ? ( is_numeric( $last_view_raw ) ? (int) $last_view_raw : (int) strtotime( (string) $last_view_raw ) ) : 0;
		}

		$responses = PNPC_PSD_Ticket_Response::get_by_ticket( $ticket->id );
		if ( empty( $responses ) ) {
			continue;
		}
		foreach ( $responses as $response ) {
			if ( (int) $response->user_id === (int) $user_id ) {
				continue;
			}
			// Responses are stored in UTC (current_time('mysql', true)); compare as UTC.
			$resp_time = (int) strtotime( (string) $response->created_at . ' UTC' );
			if ( $resp_time > $last_view_ts ) {
				$updated_open_count++;
			}
		}
	}
}
?>
<div class="pnpc-psd-dashboard">

	<?php if ((bool) get_option('pnpc_psd_show_welcome_service_desk', 1) && $user_id) : ?>
		<h2><?php printf(esc_html__('Welcome, %s!', 'pnpc-pocket-service-desk'), esc_html($current_user->display_name)); ?></h2>
	<?php endif; ?>

	<div class="pnpc-psd-ticket-totals" style="max-width:760px;margin:16px 0;padding:20px;border-radius:8px;background:linear-gradient(180deg,#ffffff,#f7f9fb);box-shadow:0 1px 4px rgba(0,0,0,0.04);border:1px solid #e6eef6;">
		<h3 style="margin-top:0;color:#234; font-size:1.15rem;"><?php esc_html_e('Ticket Totals', 'pnpc-pocket-service-desk'); ?></h3>
		<div style="display:flex;gap:20px;align-items:stretch;">
			<div style="flex:1;padding:12px;border-radius:6px;background:#fff;border:1px solid #e6eef6;text-align:center;">
				<div style="font-size:20px;font-weight:700;color:#e05a4f;">
					<?php echo esc_html($open_count); ?>
					<?php if (! empty($updated_open_count)) : ?>
						<span class="pnpc-psd-new-indicator-badge"><?php echo esc_html($updated_open_count); ?></span>
					<?php endif; ?>
				</div>
				<div style="color:#666;"><?php esc_html_e('Open / In-Progress', 'pnpc-pocket-service-desk'); ?></div>
			</div>
			<div style="flex:1;padding:12px;border-radius:6px;background:#fff;border:1px solid #e6eef6;text-align:center;">
				<div style="font-size:20px;font-weight:700;color:#2b9f6a;"><?php echo esc_html($closed_count); ?></div>
				<div style="color:#666;"><?php esc_html_e('Closed', 'pnpc-pocket-service-desk'); ?></div>
			</div>
		</div>
	</div>
</div>