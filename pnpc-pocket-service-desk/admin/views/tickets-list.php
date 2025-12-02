<?php

/**
 * Admin tickets list view (patched to use helper display function for created timestamp)
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin/views
 */

if (! defined('ABSPATH')) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e('Service Desk Tickets', 'pnpc-pocket-service-desk'); ?></h1>

	<ul class="subsubsub">
		<li>
			<a href="?page=pnpc-service-desk" <?php echo empty($status) ? 'class="current"' : ''; ?>>
				<?php esc_html_e('All', 'pnpc-pocket-service-desk'); ?>
			</a> |
		</li>
		<li>
			<a href="?page=pnpc-service-desk&status=open" <?php echo 'open' === $status ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of open tickets */
				printf(esc_html__('Open (%d)', 'pnpc-pocket-service-desk'), absint($open_count));
				?>
			</a> |
		</li>
		<li>
			<a href="?page=pnpc-service-desk&status=closed" <?php echo 'closed' === $status ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of closed tickets */
				printf(esc_html__('Closed (%d)', 'pnpc-pocket-service-desk'), absint($closed_count));
				?>
			</a>
		</li>
	</ul>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e('Ticket #', 'pnpc-pocket-service-desk'); ?></th>
				<th><?php esc_html_e('Subject', 'pnpc-pocket-service-desk'); ?></th>
				<th><?php esc_html_e('Customer', 'pnpc-pocket-service-desk'); ?></th>
				<th><?php esc_html_e('Status', 'pnpc-pocket-service-desk'); ?></th>
				<th><?php esc_html_e('Priority', 'pnpc-pocket-service-desk'); ?></th>
				<th><?php esc_html_e('Assigned To', 'pnpc-pocket-service-desk'); ?></th>
				<th><?php esc_html_e('Created', 'pnpc-pocket-service-desk'); ?></th>
				<th><?php esc_html_e('New', 'pnpc-pocket-service-desk'); ?></th>
				<th><?php esc_html_e('Actions', 'pnpc-pocket-service-desk'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (! empty($tickets)) : ?>
				<?php foreach ($tickets as $ticket) : ?>
					<?php
					$user          = get_userdata($ticket->user_id);
					$assigned_user = $ticket->assigned_to ? get_userdata($ticket->assigned_to) : null;
					?>
					<tr>
						<td><strong><?php echo esc_html($ticket->ticket_number); ?></strong></td>
						<td>
							<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>">
								<?php echo esc_html($ticket->subject); ?>
							</a>
						</td>
						<td><?php echo $user ? esc_html($user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?></td>
						<td>
							<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr($ticket->status); ?>">
								<?php echo esc_html(ucfirst($ticket->status)); ?>
							</span>
						</td>
						<td>
							<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr($ticket->priority); ?>">
								<?php echo esc_html(ucfirst($ticket->priority)); ?>
							</span>
						</td>
						<td><?php echo $assigned_user ? esc_html($assigned_user->display_name) : esc_html__('Unassigned', 'pnpc-pocket-service-desk'); ?></td>
						<td>
							<?php
							// Use helper to format DB datetime into WP-localized string
							if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
								echo esc_html(pnpc_psd_format_db_datetime_for_display($ticket->created_at));
							} else {
								echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at)));
							}
							?>
						</td>
						<td>
							<?php
							$new_responses = 0;
							$current_admin_id = get_current_user_id();
							if ($current_admin_id && $ticket->assigned_to && (int) $ticket->assigned_to === (int) $current_admin_id) {
								$last_view_key  = 'pnpc_psd_ticket_last_view_' . (int) $ticket->id;
								$last_view_raw  = get_user_meta($current_admin_id, $last_view_key, true);
								$last_view_time = $last_view_raw ? (int) $last_view_raw : 0;

								$responses = PNPC_PSD_Ticket_Response::get_by_ticket($ticket->id);
								if (! empty($responses)) {
									foreach ($responses as $response) {
										if ((int) $response->user_id === (int) $current_admin_id) {
											continue;
										}
										$resp_time = function_exists('pnpc_psd_mysql_to_wp_local_ts') ? intval(pnpc_psd_mysql_to_wp_local_ts($response->created_at)) : intval(strtotime($response->created_at));
										if ($resp_time > $last_view_time) {
											$new_responses++;
										}
									}
								}
							}
							?>
							<?php if ($new_responses > 0) : ?>
								<span class="pnpc-psd-new-indicator-badge"><?php echo esc_html($new_responses); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>" class="button button-small">
								<?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="9"><?php esc_html_e('No tickets found.', 'pnpc-pocket-service-desk'); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>