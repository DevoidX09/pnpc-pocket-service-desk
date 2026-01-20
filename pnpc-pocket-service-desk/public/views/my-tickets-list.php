<?php

/**
 * Partial: Ticket list markup for [pnpc_my_tickets] (used by initial render + AJAX refresh).
 *
 * Expects:
 *  - $tickets (array)
 *  - $total_tickets (int) Total tickets matching current tab (not just the current page).
 *  - $current_page (int)
 *  - $per_page (int)
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if (! defined('ABSPATH')) {
	exit;
}

$viewer_id = get_current_user_id();

$total_items  = isset( $total_tickets ) ? absint( $total_tickets ) : count( (array) $tickets );
$current_page = isset( $current_page ) ? max( 1, absint( $current_page ) ) : 1;
$per_page     = isset( $per_page ) ? max( 1, absint( $per_page ) ) : 20;
$total_pages  = ( $per_page > 0 ) ? (int) ceil( $total_items / $per_page ) : 1;

// Base URL for pagination links.
$base_url = remove_query_arg( array( 'pnpc_psd_page' ) );
?>

<?php if (! empty($tickets)) : ?>
	<div class="pnpc-psd-tickets-list" id="pnpc-psd-my-tickets-list">
		<?php foreach ($tickets as $ticket) : ?>
			<?php
			$response_count = PNPC_PSD_Ticket_Response::get_count($ticket->id);

			// v1.5.0+: unread/activity tracking stored on the ticket row (role-level).
			// Fallback to legacy per-user meta if the new columns are not present.
			$customer_viewed_raw = ! empty( $ticket->last_customer_viewed_at ) ? (string) $ticket->last_customer_viewed_at : '';
			$staff_activity_raw  = ! empty( $ticket->last_staff_activity_at ) ? (string) $ticket->last_staff_activity_at : '';
			if ( '' === $customer_viewed_raw ) {
				$last_view_meta = get_user_meta($viewer_id, 'pnpc_psd_ticket_last_view_' . intval($ticket->id), true);
				$customer_viewed_ts = $last_view_meta ? ( is_numeric($last_view_meta) ? intval($last_view_meta) : strtotime($last_view_meta) ) : 0;
			} else {
				$customer_viewed_ts = strtotime( $customer_viewed_raw . ' UTC' );
			}
			$staff_activity_ts = ( '' !== $staff_activity_raw ) ? strtotime( $staff_activity_raw . ' UTC' ) : 0;
			$new_responses = ( $staff_activity_ts > $customer_viewed_ts ) ? 1 : 0;

			// Build ticket detail URL:
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
			<div class="pnpc-psd-ticket-item" data-ticket-id="<?php echo esc_attr(intval($ticket->id)); ?>">
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
						<span class="pnpc-psd-ticket-number">#<?php echo esc_html($ticket->ticket_number); ?></span>
						<?php
						$raw_status = isset( $ticket->status ) ? (string) $ticket->status : '';
						$status_key = strtolower( str_replace( '_', '-', $raw_status ) );
						$status_labels = array(
							'open'        => __( 'Open', 'pnpc-pocket-service-desk' ),
							'in-progress' => __( 'In Progress', 'pnpc-pocket-service-desk' ),
							'waiting'     => __( 'Waiting', 'pnpc-pocket-service-desk' ),
							'closed'      => __( 'Closed', 'pnpc-pocket-service-desk' ),
						);
						$status_label = isset( $status_labels[ $status_key ] ) ? $status_labels[ $status_key ] : ucwords( str_replace( '-', ' ', $status_key ) );
						?>
						<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status_label ); ?></span>
						<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr($ticket->priority); ?>"><?php echo esc_html(ucfirst($ticket->priority)); ?></span>
					</div>
				</div>
				<div class="pnpc-psd-ticket-excerpt">
					<?php echo esc_html(wp_trim_words(wp_strip_all_tags($ticket->description), 30)); ?>
				</div>
				<div class="pnpc-psd-ticket-footer">
					<span class="pnpc-psd-ticket-date">
						<?php
						$created_display = function_exists('pnpc_psd_format_db_datetime_for_display')
							? pnpc_psd_format_db_datetime_for_display($ticket->created_at)
							: date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at));
						printf(esc_html__('Created %s', 'pnpc-pocket-service-desk'), esc_html($created_display));
						?>
					</span>
					<span class="pnpc-psd-ticket-responses">
						<?php printf(esc_html(_n('%d response', '%d responses', $response_count, 'pnpc-pocket-service-desk')), absint($response_count)); ?>
					</span>
					<a href="<?php echo esc_url($ticket_url); ?>" class="pnpc-psd-button pnpc-psd-button-small pnpc-psd-my-tickets-view-btn"><?php esc_html_e('View Details', 'pnpc-pocket-service-desk'); ?></a>
				</div>
			</div>
		<?php endforeach; ?>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="pnpc-psd-pagination" style="margin-top: 16px; text-align: center;">
				<?php
				$pagination = paginate_links(
					array(
						'base'      => esc_url_raw( add_query_arg( 'pnpc_psd_page', '%#%', $base_url ) ),
						'format'    => '',
						'current'   => (int) $current_page,
						'total'     => (int) $total_pages,
						'prev_text' => __( '&laquo; Prev', 'pnpc-pocket-service-desk' ),
						'next_text' => __( 'Next &raquo;', 'pnpc-pocket-service-desk' ),
						'type'      => 'array',
					)
				);
				if ( is_array( $pagination ) ) {
					foreach ( $pagination as $link ) {
						// Reuse existing button styles for a consistent look.
						echo wp_kses_post( str_replace( 'page-numbers', 'pnpc-psd-button pnpc-psd-button-small page-numbers', $link ) );
					}
				}
				?>
				<div class="pnpc-psd-pagination-meta" style="margin-top: 10px;">
					<?php
					printf(
						esc_html__( 'Page %1$d of %2$d', 'pnpc-pocket-service-desk' ),
						absint( $current_page ),
						absint( $total_pages )
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
<?php else : ?>
	<?php if ( $total_items > 0 && $current_page > 1 ) : ?>
		<p class="pnpc-psd-no-tickets"><?php esc_html_e( 'No tickets found on this page.', 'pnpc-pocket-service-desk' ); ?></p>
		<p>
			<a href="<?php echo esc_url( add_query_arg( 'pnpc_psd_page', 1, $base_url ) ); ?>" class="pnpc-psd-button pnpc-psd-button-primary">
				<?php esc_html_e( 'Go to Page 1', 'pnpc-pocket-service-desk' ); ?>
			</a>
		</p>
	<?php else : ?>
		<p class="pnpc-psd-no-tickets"><?php esc_html_e('You have not created any tickets yet.', 'pnpc-pocket-service-desk'); ?></p>
	<p>
		<a href="<?php echo esc_url(home_url('/create-ticket/')); ?>" class="pnpc-psd-button pnpc-psd-button-primary">
			<?php esc_html_e('Create Your First Ticket', 'pnpc-pocket-service-desk'); ?>
		</a>
	</p>
	<?php endif; ?>
<?php endif; ?>
