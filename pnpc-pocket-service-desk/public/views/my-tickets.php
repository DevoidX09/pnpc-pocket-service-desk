<?php

/**
 * Public my tickets view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if (! defined('ABSPATH')) {
	exit;
}
?>

<div class="pnpc-psd-my-tickets">
	<h2><?php esc_html_e('My Support Tickets', 'pnpc-pocket-service-desk'); ?></h2>

	<?php if (! empty($tickets)) : ?>
		<div class="pnpc-psd-tickets-list">
			<?php foreach ($tickets as $ticket) : ?>
				<?php
				$response_count = PNPC_PSD_Ticket_Response::get_count($ticket->id);

				// Compute new_responses for the current user (staff responses since last view)
				$current_user = wp_get_current_user();
				$last_view_meta = $current_user ? get_user_meta($current_user->ID, 'pnpc_psd_ticket_last_view_' . intval($ticket->id), true) : '';
				if (is_numeric($last_view_meta)) {
					$last_view_time = intval($last_view_meta);
				} else {
					$last_view_time = $last_view_meta ? intval(function_exists('pnpc_psd_mysql_to_wp_local_ts') ? pnpc_psd_mysql_to_wp_local_ts($last_view_meta) : strtotime($last_view_meta)) : 0;
				}

				$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket->id);
				$new_responses = 0;
				if (! empty($responses)) {
					foreach ($responses as $r) {
						// Only staff responses count for customers
						if (empty($r->is_staff_response)) {
							continue;
						}
						$r_time = function_exists('pnpc_psd_mysql_to_wp_local_ts') ? intval(pnpc_psd_mysql_to_wp_local_ts($r->created_at)) : intval(strtotime($r->created_at));
						if ($r_time > $last_view_time && intval($r->user_id) !== intval($current_user->ID)) {
							$new_responses++;
						}
					}
				}

				// Build ticket detail URL:
				// 1) Use configured page (pnpc_psd_get_ticket_detail_page_id)
				// 2) Fallback to page with slug 'ticket-view'
				// 3) Fallback to home_url('/ticket-view/') with ticket_id query param
				$ticket_url = '';
				if (function_exists('pnpc_psd_get_ticket_detail_page_id')) {
					$page_id = pnpc_psd_get_ticket_detail_page_id();
					if ($page_id && get_post($page_id)) {
						$ticket_url = add_query_arg('ticket_id', $ticket->id, get_permalink($page_id));
					}
				}

				if (empty($ticket_url)) {
					$page = get_page_by_path('ticket-view');
					if ($page && ! is_wp_error($page)) {
						$ticket_url = add_query_arg('ticket_id', $ticket->id, get_permalink($page->ID));
					}
				}

				if (empty($ticket_url)) {
					$ticket_url = add_query_arg('ticket_id', $ticket->id, home_url('/ticket-view/'));
				}
				?>
				<div class="pnpc-psd-ticket-item">
					<div class="pnpc-psd-ticket-header">
						<h3>
							<a href="<?php echo esc_url($ticket_url); ?>">
								<?php echo esc_html($ticket->subject); ?>
							</a>
							<?php if ($new_responses > 0) : ?>
								<span class="pnpc-psd-ticket-updated-dot" title="<?php echo esc_attr(sprintf(_n('%d new response', '%d new responses', $new_responses, 'pnpc-pocket-service-desk'), absint($new_responses))); ?>" style="display:inline-block;width:9px;height:9px;border-radius:50%;background:#28a745;margin-left:8px;vertical-align:middle;"></span>
							<?php endif; ?>
						</h3>
						<div class="pnpc-psd-ticket-meta">
							<span class="pnpc-psd-ticket-number">
								#<?php echo esc_html($ticket->ticket_number); ?>
							</span>
							<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr($ticket->status); ?>">
								<?php echo esc_html(ucfirst($ticket->status)); ?>
							</span>
							<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr($ticket->priority); ?>">
								<?php echo esc_html(ucfirst($ticket->priority)); ?>
							</span>
						</div>
					</div>
					<div class="pnpc-psd-ticket-excerpt">
						<?php echo esc_html(wp_trim_words(wp_strip_all_tags($ticket->description), 30)); ?>
					</div>
					<div class="pnpc-psd-ticket-footer">
						<span class="pnpc-psd-ticket-date">
							<?php
							$created_display = function_exists('pnpc_psd_format_db_datetime_for_display') ? pnpc_psd_format_db_datetime_for_display($ticket->created_at) : date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at));
							printf(esc_html__('Created %s', 'pnpc-pocket-service-desk'), esc_html($created_display));
							?>
						</span>
						<span class="pnpc-psd-ticket-responses">
							<?php
							printf(esc_html(_n('%d response', '%d responses', $response_count, 'pnpc-pocket-service-desk')), absint($response_count));
							?>
						</span>
						<a href="<?php echo esc_url($ticket_url); ?>" class="pnpc-psd-button pnpc-psd-button-small">
							<?php esc_html_e('View Details', 'pnpc-pocket-service-desk'); ?>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="pnpc-psd-no-tickets">
			<?php esc_html_e('You have not created any tickets yet.', 'pnpc-pocket-service-desk'); ?>
		</p>
		<p>
			<a href="<?php echo esc_url(home_url('/create-ticket/')); ?>" class="pnpc-psd-button pnpc-psd-button-primary">
				<?php esc_html_e('Create Your First Ticket', 'pnpc-pocket-service-desk'); ?>
			</a>
		</p>
	<?php endif; ?>
</div>