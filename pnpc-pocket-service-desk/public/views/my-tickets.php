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

	<?php $current_user_id = get_current_user_id(); ?>
	<?php if (! empty($tickets)) : ?>
		<div class="pnpc-psd-tickets-list">
			<?php foreach ($tickets as $ticket) : ?>
				<?php
				$response_count = PNPC_PSD_Ticket_Response::get_count($ticket->id);
				$ticket_url = function_exists('pnpc_psd_get_ticket_detail_url')
					? pnpc_psd_get_ticket_detail_url($ticket->id)
					: add_query_arg('ticket_id', $ticket->id, get_permalink());

				$new_responses = 0;
				if ( ! empty( $current_user_id ) ) {
					$last_view_key  = 'pnpc_psd_ticket_last_view_customer_' . (int) $ticket->id;
					$last_view_raw  = get_user_meta( $current_user_id, $last_view_key, true );
					$last_view_time = $last_view_raw ? (int) $last_view_raw : 0;

					if ( $last_view_time > 0 ) {
						$responses = PNPC_PSD_Ticket_Response::get_by_ticket( $ticket->id );
						if ( ! empty( $responses ) ) {
							foreach ( $responses as $response ) {
								if ( (int) $response->user_id === (int) $current_user_id ) {
									continue;
								}
								$resp_time = strtotime( $response->created_at );
								if ( $resp_time > $last_view_time ) {
									$new_responses++;
								}
							}
						}
					}
				}
				?>
				<div class="pnpc-psd-ticket-item">
					<div class="pnpc-psd-ticket-header">
						<h3>
							<a href="<?php echo esc_url($ticket_url); ?>">
								<?php echo esc_html($ticket->subject); ?>
							</a>
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
						<?php if ( $new_responses > 0 ) : ?>
							<span class="pnpc-psd-new-indicator-badge">
								<?php echo esc_html( $new_responses ); ?>
							</span>
						<?php endif; ?>
						</div>
					</div>
					<div class="pnpc-psd-ticket-excerpt">
						<?php echo esc_html(wp_trim_words(wp_strip_all_tags($ticket->description), 30)); ?>
					</div>
					<div class="pnpc-psd-ticket-footer">
						<span class="pnpc-psd-ticket-date">
							<?php
							/* translators: %s: time ago */
							printf(esc_html__('Created %s', 'pnpc-pocket-service-desk'), esc_html(human_time_diff(strtotime($ticket->created_at), current_time('timestamp')) . ' ' . __('ago', 'pnpc-pocket-service-desk')));
							?>
						</span>
						<span class="pnpc-psd-ticket-responses">
							<?php
							/* translators: %d: number of responses */
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