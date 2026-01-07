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

$current_view = isset($_GET['view']) ? sanitize_text_field(wp_unslash($_GET['view'])) : '';
$is_trash_view = ('trash' === $current_view);
?>

<div class="wrap">
	<h1><?php esc_html_e('Service Desk Tickets', 'pnpc-pocket-service-desk'); ?></h1>

	<ul class="subsubsub">
		<li>
			<a href="?page=pnpc-service-desk" <?php echo empty($status) && ! $is_trash_view ? 'class="current"' : ''; ?>>
				<?php esc_html_e('All', 'pnpc-pocket-service-desk'); ?>
			</a> |
		</li>
		<li>
			<a href="?page=pnpc-service-desk&status=open" <?php echo 'open' === $status && ! $is_trash_view ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of open tickets */
				printf(esc_html__('Open (%d)', 'pnpc-pocket-service-desk'), absint($open_count));
				?>
			</a> |
		</li>
		<li>
			<a href="?page=pnpc-service-desk&status=closed" <?php echo 'closed' === $status && ! $is_trash_view ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of closed tickets */
				printf(esc_html__('Closed (%d)', 'pnpc-pocket-service-desk'), absint($closed_count));
				?>
			</a> |
		</li>
		<li>
			<a href="?page=pnpc-service-desk&view=trash" <?php echo $is_trash_view ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of trashed tickets */
				printf(esc_html__('Trash (%d)', 'pnpc-pocket-service-desk'), absint($trash_count));
				?>
			</a>
		</li>
	</ul>

	<?php if (current_user_can('pnpc_psd_delete_tickets')) : ?>
	<div class="tablenav top">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'pnpc-pocket-service-desk'); ?></label>
			<select name="action" id="bulk-action-selector-top">
				<option value="-1"><?php esc_html_e('Bulk Actions', 'pnpc-pocket-service-desk'); ?></option>
				<?php if ($is_trash_view) : ?>
					<option value="restore"><?php esc_html_e('Restore', 'pnpc-pocket-service-desk'); ?></option>
					<option value="delete"><?php esc_html_e('Delete Permanently', 'pnpc-pocket-service-desk'); ?></option>
				<?php else : ?>
					<option value="trash"><?php esc_html_e('Move to Trash', 'pnpc-pocket-service-desk'); ?></option>
				<?php endif; ?>
			</select>
			<input type="button" id="doaction" class="button action" value="<?php esc_attr_e('Apply', 'pnpc-pocket-service-desk'); ?>">
		</div>
		<div id="pnpc-psd-bulk-message" style="display:none; margin-left: 20px; padding: 5px 10px; border-radius: 3px;"></div>
	</div>
	<?php endif; ?>

	<table class="wp-list-table widefat fixed striped" id="pnpc-psd-tickets-table">
		<thead>
			<tr>
				<?php if (current_user_can('pnpc_psd_delete_tickets')) : ?>
				<td id="cb" class="manage-column column-cb check-column">
					<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e('Select All', 'pnpc-pocket-service-desk'); ?></label>
					<input id="cb-select-all-1" type="checkbox">
				</td>
				<?php endif; ?>
				<th class="pnpc-psd-sortable" data-sort-type="ticket-number" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Ticket Number', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Ticket #', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<th class="pnpc-psd-sortable" data-sort-type="text" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Subject', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Subject', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<?php if (! $is_trash_view) : ?>
				<th class="pnpc-psd-sortable" data-sort-type="text" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Customer', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Customer', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<th class="pnpc-psd-sortable" data-sort-type="status" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Status', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Status', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<th class="pnpc-psd-sortable" data-sort-type="priority" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Priority', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Priority', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<th class="pnpc-psd-sortable" data-sort-type="text" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Assigned To', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Assigned To', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<th class="pnpc-psd-sortable" data-sort-type="date" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Created Date', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Created', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<th class="pnpc-psd-sortable" data-sort-type="boolean" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by New Responses', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('New', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<?php else : ?>
				<th class="pnpc-psd-sortable" data-sort-type="text" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Delete Reason', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Delete Reason', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<th class="pnpc-psd-sortable" data-sort-type="text" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Deleted By', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Deleted By', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<th class="pnpc-psd-sortable" data-sort-type="date" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Deleted Date', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Deleted At', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
				<?php endif; ?>
				<th><?php esc_html_e('Actions', 'pnpc-pocket-service-desk'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (! empty($tickets)) : ?>
				<?php foreach ($tickets as $ticket) : ?>
					<?php
					$user          = get_userdata($ticket->user_id);
					$assigned_user = $ticket->assigned_to ? get_userdata($ticket->assigned_to) : null;
					
					// Extract numeric part from ticket number for sorting (e.g., PNPC-1234 -> 1234)
					$ticket_num_for_sort = (int) preg_replace('/[^0-9]/', '', $ticket->ticket_number);
					
					// Status sort order: open=1, in-progress=2, waiting=3, closed=4
					$status_order = array('open' => 1, 'in-progress' => 2, 'waiting' => 3, 'closed' => 4);
					$status_sort_value = isset($status_order[$ticket->status]) ? $status_order[$ticket->status] : 999;
					
					// Priority sort order: urgent=1, high=2, normal=3, low=4
					$priority_order = array('urgent' => 1, 'high' => 2, 'normal' => 3, 'low' => 4);
					$priority_sort_value = isset($priority_order[$ticket->priority]) ? $priority_order[$ticket->priority] : 999;
					
					// Get timestamp for date sorting
					$created_timestamp = strtotime($ticket->created_at);
					if (false === $created_timestamp) {
						$created_timestamp = 0; // Fallback for invalid dates
					}
					
					// Calculate new responses for this ticket
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
					<tr>
						<?php if (current_user_can('pnpc_psd_delete_tickets')) : ?>
						<th scope="row" class="check-column">
							<label class="screen-reader-text" for="cb-select-<?php echo absint($ticket->id); ?>">
								<?php
								/* translators: %s: ticket number */
								printf(esc_html__('Select %s', 'pnpc-pocket-service-desk'), esc_html($ticket->ticket_number));
								?>
							</label>
							<input type="checkbox" name="ticket[]" id="cb-select-<?php echo absint($ticket->id); ?>" value="<?php echo absint($ticket->id); ?>">
						</th>
						<?php endif; ?>
						<td data-sort-value="<?php echo absint($ticket_num_for_sort); ?>"><strong><?php echo esc_html($ticket->ticket_number); ?></strong></td>
						<td data-sort-value="<?php echo esc_attr(strtolower($ticket->subject)); ?>">
							<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>">
								<?php echo esc_html($ticket->subject); ?>
							</a>
						</td>
						<?php if (! $is_trash_view) : ?>
						<td data-sort-value="<?php echo esc_attr(strtolower($user ? $user->display_name : 'zzz_unknown')); ?>"><?php echo $user ? esc_html($user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?></td>
						<td data-sort-value="<?php echo absint($status_sort_value); ?>">
							<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr($ticket->status); ?>">
								<?php echo esc_html(ucfirst($ticket->status)); ?>
							</span>
						</td>
						<td data-sort-value="<?php echo absint($priority_sort_value); ?>">
							<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr($ticket->priority); ?>">
								<?php echo esc_html(ucfirst($ticket->priority)); ?>
							</span>
						</td>
						<td data-sort-value="<?php echo esc_attr(strtolower($assigned_user ? $assigned_user->display_name : 'zzz_unassigned')); ?>"><?php echo $assigned_user ? esc_html($assigned_user->display_name) : esc_html__('Unassigned', 'pnpc-pocket-service-desk'); ?></td>
						<td data-sort-value="<?php echo absint($created_timestamp); ?>">
							<?php
							// Use helper to format DB datetime into WP-localized string
							if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
								echo esc_html(pnpc_psd_format_db_datetime_for_display($ticket->created_at));
							} else {
								echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at)));
							}
							?>
						</td>
						<td data-sort-value="<?php echo absint($new_responses); ?>">
							<?php if ($new_responses > 0) : ?>
								<span class="pnpc-psd-new-indicator-badge"><?php echo esc_html($new_responses); ?></span>
							<?php endif; ?>
						</td>
						<?php else : ?>
						<?php
						// Trash view: show delete reason, deleted by, deleted at
						$delete_reason       = ! empty($ticket->delete_reason) ? $ticket->delete_reason : '';
						$delete_reason_other = ! empty($ticket->delete_reason_other) ? $ticket->delete_reason_other : '';
						$deleted_by_id       = ! empty($ticket->deleted_by) ? absint($ticket->deleted_by) : 0;
						$deleted_by_user     = $deleted_by_id ? get_userdata($deleted_by_id) : null;
						$deleted_at          = ! empty($ticket->deleted_at) ? $ticket->deleted_at : '';

						// Get timestamp for deleted at sorting
						$deleted_timestamp = $deleted_at ? strtotime($deleted_at) : 0;
						?>
						<td data-sort-value="<?php echo esc_attr(strtolower($delete_reason)); ?>">
							<?php echo esc_html(pnpc_psd_format_delete_reason($delete_reason, $delete_reason_other)); ?>
						</td>
						<td data-sort-value="<?php echo esc_attr(strtolower($deleted_by_user ? $deleted_by_user->display_name : 'zzz_unknown')); ?>">
							<?php echo $deleted_by_user ? esc_html($deleted_by_user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?>
						</td>
						<td data-sort-value="<?php echo absint($deleted_timestamp); ?>">
							<?php
							if ($deleted_at) {
								if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
									echo esc_html(pnpc_psd_format_db_datetime_for_display($deleted_at));
								} else {
									echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($deleted_at)));
								}
							} else {
								esc_html_e('Unknown', 'pnpc-pocket-service-desk');
							}
							?>
						</td>
						<?php endif; ?>
						<?php endif; ?>
						<td>
							<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>" class="button button-small">
								<?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="<?php echo $is_trash_view ? (current_user_can('pnpc_psd_delete_tickets') ? '6' : '5') : (current_user_can('pnpc_psd_delete_tickets') ? '10' : '9'); ?>">
						<?php
						if ($is_trash_view) {
							esc_html_e('No tickets in trash.', 'pnpc-pocket-service-desk');
						} else {
							esc_html_e('No tickets found.', 'pnpc-pocket-service-desk');
						}
						?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<!-- Delete Reason Modal -->
<div id="pnpc-psd-delete-modal" class="pnpc-psd-modal" style="display:none;">
	<div class="pnpc-psd-modal-backdrop"></div>
	<div class="pnpc-psd-modal-content">
		<div class="pnpc-psd-modal-header">
			<h2><?php esc_html_e('Confirm Delete', 'pnpc-pocket-service-desk'); ?></h2>
			<button type="button" class="pnpc-psd-modal-close">&times;</button>
		</div>
		<div class="pnpc-psd-modal-body">
			<p id="pnpc-psd-delete-modal-message"></p>
			
			<div class="pnpc-psd-form-group">
				<label for="pnpc-psd-delete-reason-select">
					<?php esc_html_e('Reason:', 'pnpc-pocket-service-desk'); ?> <span class="required">*</span>
				</label>
				<select id="pnpc-psd-delete-reason-select">
					<option value=""><?php esc_html_e('Select a reason', 'pnpc-pocket-service-desk'); ?></option>
					<option value="spam"><?php esc_html_e('Spam', 'pnpc-pocket-service-desk'); ?></option>
					<option value="duplicate"><?php esc_html_e('Duplicate ticket', 'pnpc-pocket-service-desk'); ?></option>
					<option value="resolved_elsewhere"><?php esc_html_e('Resolved elsewhere', 'pnpc-pocket-service-desk'); ?></option>
					<option value="customer_request"><?php esc_html_e('Customer request', 'pnpc-pocket-service-desk'); ?></option>
					<option value="test"><?php esc_html_e('Test ticket', 'pnpc-pocket-service-desk'); ?></option>
					<option value="other"><?php esc_html_e('Other (please specify)', 'pnpc-pocket-service-desk'); ?></option>
				</select>
			</div>
			
			<div class="pnpc-psd-form-group" id="pnpc-psd-delete-reason-other-wrapper" style="display:none;">
				<label for="pnpc-psd-delete-reason-other">
					<?php esc_html_e('Additional details:', 'pnpc-pocket-service-desk'); ?> <span class="required">*</span>
				</label>
				<textarea id="pnpc-psd-delete-reason-other" rows="3" placeholder="<?php esc_attr_e('Please provide more details (minimum 10 characters)', 'pnpc-pocket-service-desk'); ?>"></textarea>
			</div>
		</div>
		<div class="pnpc-psd-modal-footer">
			<button type="button" class="button pnpc-psd-delete-cancel"><?php esc_html_e('Cancel', 'pnpc-pocket-service-desk'); ?></button>
			<button type="button" class="button button-primary pnpc-psd-delete-submit"><?php esc_html_e('Move to Trash', 'pnpc-pocket-service-desk'); ?></button>
		</div>
	</div>
</div>