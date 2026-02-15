<?php

/**
 * Admin tickets list view (patched to use helper display function for created timestamp)
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view parameter.
$is_trash_view = ('trash' === $current_view);
$is_review_view = ('review' === $current_view);
$is_archived_view = ('archived' === $current_view);

// Bulk Actions:
// - Main list + Trash are Admin-only.
// - Review queue can be actioned by Admins/Managers (pnpc_psd_delete_tickets).
$can_bulk_actions = $is_review_view ? current_user_can('pnpc_psd_delete_tickets') : current_user_can('manage_options');

// Initialize badge_counts if not set (initial page load)
if (!isset($badge_counts)) {
	$badge_counts = array();
}

// Pagination setup
$per_page = get_option('pnpc_psd_tickets_per_page', 20);
$current_page = isset( $paged ) ? max( 1, absint( $paged ) ) : ( isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination parameter.
$offset = ($current_page - 1) * $per_page;

// Total ticket count is provided by the controller (includes non-loaded pages).
// Fall back to the loaded row count for safety.
$total_items = isset( $total_tickets ) ? absint( $total_tickets ) : count( $tickets );
$total_pages = ( $per_page > 0 ) ? (int) ceil( $total_items / $per_page ) : 1;

// The controller already queries the correct page; no need to slice again.
$tickets_paginated = $tickets;

// Build pagination links helper function
if( !function_exists('pnpc_psd_get_pagination_link')) {
/**
 * Pnpc psd get pagination link.
 *
 * @param mixed $page 
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
	function pnpc_psd_get_pagination_link( $page) {
		$args = array();
		if ( isset( $_GET['page'] ) ) {
			$args['page'] = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}
		if ( isset( $_GET['view'] ) ) {
			$args['view'] = sanitize_text_field( wp_unslash( $_GET['view'] ) );
		}
		if ( isset( $_GET['status'] ) ) {
			$args['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
		}
		$args['paged'] = absint( $page );
		return esc_url( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	}
}
?>

<div class="wrap">
	<h1><?php esc_html_e('Service Desk Tickets', 'pnpc-pocket-service-desk'); ?></h1>
	<div style="margin: 10px 0 15px;">
		<?php
		$csv_export_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'pnpc_psd_export_tickets',
					'view'   => $current_view,
					'status' => isset( $status ) ? $status : '',
				),
				admin_url( 'admin-post.php' )
			),
			'pnpc_psd_export_tickets'
		);
		?>
		<a class="button button-secondary" href="<?php echo esc_url( $csv_export_url ); ?>">
			<?php esc_html_e( 'Export CSV', 'pnpc-pocket-service-desk' ); ?>
		</a>
	</div>


	<ul class="subsubsub">
		<?php
		$base_url  = admin_url( 'admin.php' );
		$base_args = array( 'page' => 'pnpc-service-desk' );

		$all_url      = add_query_arg( $base_args, $base_url );
		$open_url     = add_query_arg( array_merge( $base_args, array( 'status' => 'open' ) ), $base_url );
		$inprog_url   = add_query_arg( array_merge( $base_args, array( 'status' => 'in-progress' ) ), $base_url );
		$waiting_url  = add_query_arg( array_merge( $base_args, array( 'status' => 'waiting' ) ), $base_url );
		$closed_url   = add_query_arg( array_merge( $base_args, array( 'status' => 'closed' ) ), $base_url );
		$review_url   = add_query_arg( array_merge( $base_args, array( 'view' => 'review' ) ), $base_url );
		$trash_url    = add_query_arg( array_merge( $base_args, array( 'view' => 'trash' ) ), $base_url );
		$archived_url = add_query_arg( array_merge( $base_args, array( 'view' => 'archived' ) ), $base_url );

		$is_all_view = ( ! $is_review_view && ! $is_trash_view && ! $is_archived_view && ( empty( $status ) || 'all' === $status ) );
		?>
		<li class="all">
			<a href="<?php echo esc_url( $all_url ); ?>" <?php echo $is_all_view ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of tickets */
				printf( esc_html__( 'All (%d)', 'pnpc-pocket-service-desk' ), absint( $all_count ) );
				?>
			</a> |
		</li>
		<li class="open">
			<a href="<?php echo esc_url( $open_url ); ?>" <?php echo ( 'open' === $status && ! $is_review_view && ! $is_trash_view && ! $is_archived_view ) ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of open tickets */
				printf( esc_html__( 'Open (%d)', 'pnpc-pocket-service-desk' ), absint( $open_count ) );
				?>
			</a> |
		</li>
		<li class="in-progress">
			<a href="<?php echo esc_url( $inprog_url ); ?>" <?php echo ( 'in-progress' === $status && ! $is_review_view && ! $is_trash_view && ! $is_archived_view ) ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of in-progress tickets */
				printf( esc_html__( 'In Progress (%d)', 'pnpc-pocket-service-desk' ), absint( $in_progress_count ) );
				?>
			</a> |
		</li>
		<li class="waiting">
			<a href="<?php echo esc_url( $waiting_url ); ?>" <?php echo ( 'waiting' === $status && ! $is_review_view && ! $is_trash_view && ! $is_archived_view ) ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of waiting tickets */
				printf( esc_html__( 'Waiting (%d)', 'pnpc-pocket-service-desk' ), absint( $waiting_count ) );
				?>
			</a> |
		</li>
		<li class="closed">
			<a href="<?php echo esc_url( $closed_url ); ?>" <?php echo ( 'closed' === $status && ! $is_review_view && ! $is_trash_view && ! $is_archived_view ) ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of closed tickets */
				printf( esc_html__( 'Closed (%d)', 'pnpc-pocket-service-desk' ), absint( $closed_count ) );
				?>
			</a> |
		</li>
		<li class="review">
			<a href="<?php echo esc_url( $review_url ); ?>" <?php echo $is_review_view ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of review tickets */
				printf( esc_html__( 'Review (%d)', 'pnpc-pocket-service-desk' ), absint( $review_count ) );
				?>
			</a> |
		</li>
		<li class="trash">
			<a href="<?php echo esc_url( $trash_url ); ?>" <?php echo $is_trash_view ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of trashed tickets */
				printf( esc_html__( 'Trash (%d)', 'pnpc-pocket-service-desk' ), absint( $trash_count ) );
				?>
			</a> |
		</li>
		<li class="archived">
			<a href="<?php echo esc_url( $archived_url ); ?>" <?php echo $is_archived_view ? 'class="current"' : ''; ?>>
				<?php
				/* translators: %d: number of archived tickets */
				printf( esc_html__( 'Archived (%d)', 'pnpc-pocket-service-desk' ), absint( $archived_count ) );
				?>
			</a>
		</li>
	</ul>

	<?php if ($can_bulk_actions) : ?>
	<div class="tablenav top">
		<div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'pnpc-pocket-service-desk'); ?></label>
			<select name="action" id="bulk-action-selector-top">
				<option value="-1"><?php esc_html_e('Bulk Actions', 'pnpc-pocket-service-desk'); ?></option>
				<?php if ( $is_trash_view ) : ?>
					<option value="restore"><?php esc_html_e('Restore', 'pnpc-pocket-service-desk'); ?></option>
					<option value="delete"><?php esc_html_e('Delete Permanently', 'pnpc-pocket-service-desk'); ?></option>
				<?php elseif ( $is_archived_view ) : ?>
					<option value="restore_archive"><?php esc_html_e('Restore from Archive', 'pnpc-pocket-service-desk'); ?></option>
				<?php elseif ( $is_review_view ) : ?>
					<option value="approve_to_trash"><?php esc_html_e('Approve → Trash', 'pnpc-pocket-service-desk'); ?></option>
					<option value="cancel_review"><?php esc_html_e('Restore (Cancel Request)', 'pnpc-pocket-service-desk'); ?></option>
				<?php else : ?>
					<option value="trash"><?php esc_html_e('Move to Trash', 'pnpc-pocket-service-desk'); ?></option>
					<?php if ( isset( $status ) && 'closed' === $status && ! $is_trash_view && ! $is_review_view && ! $is_archived_view ) : ?>
						<option value="archive"><?php esc_html_e('Move to Archive', 'pnpc-pocket-service-desk'); ?></option>
					<?php endif; ?>
				<?php endif; ?>
			</select>
			<input type="button" id="doaction" class="button action" value="<?php esc_attr_e('Apply', 'pnpc-pocket-service-desk'); ?>">
		</div>
		<div id="pnpc-psd-bulk-message" style="display:none; margin-left: 20px; padding: 5px 10px; border-radius: 3px;"></div>
	</div>
	<?php endif; ?>

	<table class="wp-list-table widefat fixed striped" id="pnpc-psd-tickets-table">
		<thead>
						<tr class="pnpc-psd-ticket-row">
	<?php if ( $can_bulk_actions ) : ?>
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
				<?php if ( ! $is_trash_view && ! $is_review_view && ! $is_archived_view ) : ?>
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
				<?php elseif ( $is_archived_view ) : ?>
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
				<th class="pnpc-psd-sortable" data-sort-type="date" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Archived Date', 'pnpc-pocket-service-desk'); ?>">
					<?php esc_html_e('Archived At', 'pnpc-pocket-service-desk'); ?>
					<span class="pnpc-psd-sort-arrow"></span>
				</th>
<?php elseif ( $is_trash_view ) : ?>
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
				<?php else : ?>
					<th class="pnpc-psd-sortable" data-sort-type="text" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Delete Reason', 'pnpc-pocket-service-desk'); ?>">
						<?php esc_html_e('Delete Reason', 'pnpc-pocket-service-desk'); ?>
						<span class="pnpc-psd-sort-arrow"></span>
					</th>
					<th class="pnpc-psd-sortable" data-sort-type="text" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Requested By', 'pnpc-pocket-service-desk'); ?>">
						<?php esc_html_e('Requested By', 'pnpc-pocket-service-desk'); ?>
						<span class="pnpc-psd-sort-arrow"></span>
					</th>
					<th class="pnpc-psd-sortable" data-sort-type="date" data-sort-order="" role="button" tabindex="0" aria-label="<?php esc_attr_e('Sort by Requested Date', 'pnpc-pocket-service-desk'); ?>">
						<?php esc_html_e('Requested At', 'pnpc-pocket-service-desk'); ?>
						<span class="pnpc-psd-sort-arrow"></span>
					</th>
				<?php endif; ?>
				<th><?php esc_html_e('Actions', 'pnpc-pocket-service-desk'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			// Separate active and closed tickets (only for main list view)
			if (! empty($tickets) && ! $is_trash_view && ! $is_review_view && ! $is_archived_view) {
				$active_tickets = array();
				$closed_tickets = array();
				
				foreach ($tickets as $ticket) {
					$status_lower = strtolower($ticket->status);
					if ($status_lower === 'closed' || $status_lower === 'resolved') {
						$closed_tickets[] = $ticket;
					} else {
						$active_tickets[] = $ticket;
					}
				}
				
				$has_active = !empty($active_tickets);
				$has_closed = !empty($closed_tickets);
			} else {
				$active_tickets = array();
				$closed_tickets = array();
				$has_active = false;
				$has_closed = false;
			}
			?>
			
			<?php if (! empty($tickets)) : ?>
				<?php if ( $is_archived_view ) : ?>
				<?php foreach ( $tickets as $ticket ) : ?>
					<?php
					$user = get_userdata( $ticket->user_id );
					$assigned_user = $ticket->assigned_to ? get_userdata( $ticket->assigned_to ) : null;
					$ticket_num_for_sort = (int) preg_replace('/[^0-9]/', '', $ticket->ticket_number);
					$archived_at_raw = ! empty( $ticket->archived_at ) ? (string) $ticket->archived_at : '';
					$archived_ts = $archived_at_raw ? strtotime( $archived_at_raw ) : 0;
					?>
					<tr class="pnpc-psd-ticket-row" data-sort-ticket-number="<?php echo esc_attr( $ticket_num_for_sort ); ?>" data-sort-text="<?php echo esc_attr( strtolower( (string) $ticket->subject ) ); ?>" data-sort-date="<?php echo esc_attr( $archived_ts ); ?>">
						<?php if ( $can_bulk_actions ) : ?>
						<th scope="row" class="check-column">
							<label class="screen-reader-text" for="cb-select-<?php echo absint($ticket->id); ?>"><?php 
							// translators: %s is the ticket number.
							printf(esc_html__('Select %s', 'pnpc-pocket-service-desk'), esc_html($ticket->ticket_number)); ?></label>
							<input type="checkbox" name="ticket[]" id="cb-select-<?php echo esc_attr( absint( $ticket->id ) ); ?>" value="<?php echo esc_attr( absint( $ticket->id ) ); ?>">
						</th>
						<?php endif; ?>
						<td><strong><?php echo esc_html( $ticket->ticket_number ); ?></strong></td>
						<td><a href="<?php echo esc_url( admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id) ); ?>"><?php echo esc_html( $ticket->subject ); ?></a></td>
						<td><?php echo esc_html( $user ? $user->display_name : '' ); ?></td>
					<td>
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
						<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $status_key ); ?>">
							<?php echo esc_html( $status_label ); ?>
						</span>
					</td>
						<td><?php echo esc_html( ucfirst( (string) $ticket->priority ) ); ?></td>
						<td><?php echo esc_html( $assigned_user ? $assigned_user->display_name : __('Unassigned', 'pnpc-pocket-service-desk') ); ?></td>
						<td><?php if ( $archived_at_raw ) { echo esc_html( function_exists('pnpc_psd_format_db_datetime_for_display') ? pnpc_psd_format_db_datetime_for_display( $archived_at_raw ) : date_i18n( get_option('date_format').' '.get_option('time_format'), $archived_ts ) ); } ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id) ); ?>" class="button button-small"><?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?></a>
							<?php $restore_url = wp_nonce_url( admin_url('admin-post.php?action=pnpc_psd_restore_archived_ticket&ticket_id=' . absint($ticket->id)), 'pnpc_psd_restore_archived_ticket_' . absint($ticket->id) ); ?>
							<a href="<?php echo esc_url( $restore_url ); ?>" class="button button-small"><?php esc_html_e('Restore', 'pnpc-pocket-service-desk'); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php elseif ( $is_trash_view ) : ?>
					<?php // Trash view: render all tickets normally ?>
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
						
						// Trash view: show delete reason, deleted by, deleted at
						$delete_reason       = ! empty($ticket->delete_reason) ? $ticket->delete_reason : '';
						$delete_reason_other = ! empty($ticket->delete_reason_other) ? $ticket->delete_reason_other : '';
						$deleted_by_id       = ! empty($ticket->deleted_by) ? absint($ticket->deleted_by) : 0;
						$deleted_by_user     = $deleted_by_id ? get_userdata($deleted_by_id) : null;
						$deleted_at          = ! empty($ticket->deleted_at) ? $ticket->deleted_at : '';

						// Get timestamp for deleted at sorting
						$deleted_timestamp = $deleted_at ? strtotime($deleted_at) : 0;
						?>
						<tr class="pnpc-psd-ticket-row">
							<?php if ( $can_bulk_actions ) : ?>
							<th scope="row" class="check-column">
								<label class="screen-reader-text" for="cb-select-<?php echo absint($ticket->id); ?>">
									<?php
									/* translators: %s: ticket number */
									printf(esc_html__('Select %s', 'pnpc-pocket-service-desk'), esc_html($ticket->ticket_number));
									?>
								</label>
								<input type="checkbox" name="ticket[]" id="cb-select-<?php echo esc_attr( absint( $ticket->id ) ); ?>" value="<?php echo esc_attr( absint( $ticket->id ) ); ?>">
							</th>
							<?php endif; ?>
							<td data-sort-value="<?php echo esc_attr( absint($ticket_num_for_sort) ); ?>"><strong><?php echo esc_html($ticket->ticket_number); ?></strong></td>
							<td data-sort-value="<?php echo esc_attr(strtolower($ticket->subject)); ?>">
								<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>">
									<?php echo esc_html($ticket->subject); ?>
								</a>
								<?php if (! empty($ticket->created_by_staff)) : ?>
									<span class="pnpc-psd-badge pnpc-psd-badge-staff-created" title="<?php esc_attr_e('Created by staff', 'pnpc-pocket-service-desk'); ?>">
										<span class="dashicons dashicons-admin-users"></span>
									</span>
								<?php endif; ?>
							</td>
							<td data-sort-value="<?php echo esc_attr(strtolower($delete_reason)); ?>">
								<?php echo esc_html(pnpc_psd_format_delete_reason($delete_reason, $delete_reason_other)); ?>
							</td>
							<td data-sort-value="<?php echo esc_attr(strtolower($deleted_by_user ? $deleted_by_user->display_name : 'zzz_unknown')); ?>">
								<?php echo $deleted_by_user ? esc_html($deleted_by_user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?>
							</td>
							<td data-sort-value="<?php echo esc_attr( absint($deleted_timestamp) ); ?>">
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
							<td>
								<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>" class="button button-small">
									<?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?>
								</a>
								<?php if ( $is_trash_view ) : ?>
									<?php
									// Allow single-item archiving directly from Trash.
									$archive_url = wp_nonce_url(
										admin_url('admin-post.php?action=pnpc_psd_archive_ticket&ticket_id=' . absint($ticket->id) . '&return_to=trash'),
										'pnpc_psd_archive_ticket_' . absint($ticket->id)
									);
									?>
									<a href="<?php echo esc_url( $archive_url ); ?>" class="button button-small"><?php esc_html_e('Archive', 'pnpc-pocket-service-desk'); ?></a>
								<?php elseif ( isset($ticket->status) && 'closed' === strtolower((string)$ticket->status) ) : ?>
									<?php $archive_url = wp_nonce_url( admin_url('admin-post.php?action=pnpc_psd_archive_ticket&ticket_id=' . absint($ticket->id)), 'pnpc_psd_archive_ticket_' . absint($ticket->id) ); ?>
									<a href="<?php echo esc_url( $archive_url ); ?>" class="button button-small"><?php esc_html_e('Archive', 'pnpc-pocket-service-desk'); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php elseif ( $is_review_view ) : ?>
					<?php // Review queue: render all tickets normally ?>
					<?php foreach ($tickets as $ticket) : ?>
						<?php
							$ticket_num_for_sort = (int) preg_replace('/[^0-9]/', '', $ticket->ticket_number);
							$req_reason = ! empty($ticket->pending_delete_reason) ? (string) $ticket->pending_delete_reason : '';
							$req_reason_other = ! empty($ticket->pending_delete_reason_other) ? (string) $ticket->pending_delete_reason_other : '';
							$req_by_id = ! empty($ticket->pending_delete_by) ? absint($ticket->pending_delete_by) : 0;
							$req_by_user = $req_by_id ? get_userdata($req_by_id) : null;
							$req_at = ! empty($ticket->pending_delete_at) ? (string) $ticket->pending_delete_at : '';
							$req_timestamp = $req_at ? strtotime($req_at) : 0;
						?>
						<tr class="pnpc-psd-ticket-row">
							<?php if ( $can_bulk_actions ) : ?>
							<th scope="row" class="check-column">
								<label class="screen-reader-text" for="cb-select-<?php echo absint($ticket->id); ?>">
									<?php
									/* translators: %s: ticket number */
									printf(esc_html__('Select %s', 'pnpc-pocket-service-desk'), esc_html($ticket->ticket_number));
									?>
								</label>
								<input type="checkbox" name="ticket[]" id="cb-select-<?php echo esc_attr( absint( $ticket->id ) ); ?>" value="<?php echo esc_attr( absint( $ticket->id ) ); ?>">
							</th>
							<?php endif; ?>
							<td data-sort-value="<?php echo esc_attr( absint($ticket_num_for_sort) ); ?>"><strong><?php echo esc_html($ticket->ticket_number); ?></strong></td>
							<td data-sort-value="<?php echo esc_attr(strtolower($ticket->subject)); ?>">
								<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>">
									<?php echo esc_html($ticket->subject); ?>
								</a>
							</td>
							<td data-sort-value="<?php echo esc_attr(strtolower($req_reason)); ?>">
								<?php echo esc_html(pnpc_psd_format_delete_reason($req_reason, $req_reason_other)); ?>
							</td>
							<td data-sort-value="<?php echo esc_attr(strtolower($req_by_user ? $req_by_user->display_name : 'zzz_unknown')); ?>">
								<?php echo $req_by_user ? esc_html($req_by_user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?>
							</td>
							<td data-sort-value="<?php echo esc_attr( absint($req_timestamp) ); ?>">
								<?php
								if ($req_at) {
									if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
										echo esc_html(pnpc_psd_format_db_datetime_for_display($req_at));
									} else {
										echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($req_at)));
									}
								} else {
									esc_html_e('Unknown', 'pnpc-pocket-service-desk');
								}
								?>
							</td>
							<td>
								<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>" class="button button-small">
									<?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<?php // ACTIVE TICKETS SECTION ?>
					<?php if ($has_active) : ?>
						<?php foreach ($active_tickets as $ticket) : ?>
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
					
					// Calculate "New" badge count for this agent
					// Use pre-calculated badge count if available (from AJAX refresh)
					if (isset($badge_counts) && isset($badge_counts[$ticket->id])) {
						$new_responses = $badge_counts[$ticket->id];
					} else {
						// Calculate fresh (initial page load)
						$new_responses = 0;

						if ( ! $is_trash_view && current_user_can( 'pnpc_psd_view_tickets' ) ) {
							// v1.5.0+: unread/activity tracking stored on the ticket row (role-level).
							$customer_activity_raw = ! empty( $ticket->last_customer_activity_at ) ? (string) $ticket->last_customer_activity_at : (string) $ticket->created_at;
							$staff_viewed_raw      = ! empty( $ticket->last_staff_viewed_at ) ? (string) $ticket->last_staff_viewed_at : '';
							$customer_activity_ts  = ( '' !== $customer_activity_raw ) ? strtotime( $customer_activity_raw . ' UTC' ) : 0;
							$staff_viewed_ts       = ( '' !== $staff_viewed_raw ) ? strtotime( $staff_viewed_raw . ' UTC' ) : 0;
							$new_responses         = ( $customer_activity_ts > $staff_viewed_ts ) ? 1 : 0;
						}
					}
					?>
					<tr class="pnpc-psd-ticket-row">
						<?php if ( $can_bulk_actions ) : ?>
						<th scope="row" class="check-column">
							<label class="screen-reader-text" for="cb-select-<?php echo absint($ticket->id); ?>">
								<?php
								/* translators: %s: ticket number */
								printf(esc_html__('Select %s', 'pnpc-pocket-service-desk'), esc_html($ticket->ticket_number));
								?>
							</label>
							<input type="checkbox" name="ticket[]" id="cb-select-<?php echo esc_attr( absint( $ticket->id ) ); ?>" value="<?php echo esc_attr( absint( $ticket->id ) ); ?>">
						</th>
						<?php endif; ?>
						<td data-sort-value="<?php echo esc_attr( absint($ticket_num_for_sort) ); ?>"><strong><?php echo esc_html($ticket->ticket_number); ?></strong></td>
						<td data-sort-value="<?php echo esc_attr(strtolower($ticket->subject)); ?>">
							<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>">
								<?php echo esc_html($ticket->subject); ?>
							</a>
							<?php if (! empty($ticket->created_by_staff)) : ?>
								<span class="pnpc-psd-badge pnpc-psd-badge-staff-created" title="<?php esc_attr_e('Created by staff', 'pnpc-pocket-service-desk'); ?>">
									<span class="dashicons dashicons-admin-users"></span>
								</span>
							<?php endif; ?>
						</td>
						<td data-sort-value="<?php echo esc_attr(strtolower($user ? $user->display_name : 'zzz_unknown')); ?>"><?php echo $user ? esc_html($user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?></td>
						<td data-sort-value="<?php echo esc_attr( absint( $status_sort_value ) ); ?>">
							<?php
							$raw_status  = isset( $ticket->status ) ? (string) $ticket->status : '';
							$status_key  = strtolower( str_replace( '_', '-', $raw_status ) );
							$status_labels = array(
								'open'        => __( 'Open', 'pnpc-pocket-service-desk' ),
								'in-progress' => __( 'In Progress', 'pnpc-pocket-service-desk' ),
								'waiting'     => __( 'Waiting', 'pnpc-pocket-service-desk' ),
								'closed'      => __( 'Closed', 'pnpc-pocket-service-desk' ),
								'review'      => __( 'Review', 'pnpc-pocket-service-desk' ),
								'archived'    => __( 'Archived', 'pnpc-pocket-service-desk' ),
							);
							$status_label = isset( $status_labels[ $status_key ] ) ? $status_labels[ $status_key ] : ucwords( str_replace( '-', ' ', $status_key ) );
							?>
							<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $status_key ); ?>">
								<?php echo esc_html( $status_label ); ?>
							</span>
						</td>
						<td data-sort-value="<?php echo esc_attr( absint($priority_sort_value) ); ?>">
							<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr($ticket->priority); ?>">
								<?php echo esc_html(ucfirst($ticket->priority)); ?>
							</span>
						</td>
						<td data-sort-value="<?php echo esc_attr(strtolower($assigned_user ? $assigned_user->display_name : 'zzz_unassigned')); ?>"><?php echo $assigned_user ? esc_html($assigned_user->display_name) : esc_html__('Unassigned', 'pnpc-pocket-service-desk'); ?></td>
						<td data-sort-value="<?php echo esc_attr( absint($created_timestamp) ); ?>">
							<?php
							// Use helper to format DB datetime into WP-localized string
							if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
								echo esc_html(pnpc_psd_format_db_datetime_for_display($ticket->created_at));
							} else {
								echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at)));
							}
							?>
						</td>
						<td data-sort-value="<?php echo esc_attr( absint($new_responses) ); ?>">
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
			<?php endif; ?>
			
			<?php // DIVIDER ROW - Only show if both active and closed exist ?>
			<?php if ($has_active && $has_closed) : ?>
				<tr class="pnpc-psd-closed-divider">
					<td colspan="<?php echo $can_bulk_actions ? 10 : 9; ?>">
						<div class="pnpc-psd-divider-content">
							<span class="pnpc-psd-divider-line"></span>
							<span class="pnpc-psd-divider-text">
								<?php 
								printf(
/* translators: Placeholder(s) in localized string. */
									esc_html__('Closed Tickets (%d)', 'pnpc-pocket-service-desk'),
									count($closed_tickets)
								); 
								?>
							</span>
							<span class="pnpc-psd-divider-line"></span>
						</div>
					</td>
				</tr>
			<?php endif; ?>
			
			<?php // CLOSED TICKETS SECTION ?>
			<?php if ($has_closed) : ?>
				<?php foreach ($closed_tickets as $ticket) : ?>
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
					?>
					<tr class="pnpc-psd-ticket-row pnpc-psd-ticket-closed">
						<?php if ( $can_bulk_actions ) : ?>
						<th scope="row" class="check-column">
							<label class="screen-reader-text" for="cb-select-<?php echo absint($ticket->id); ?>">
								<?php
								/* translators: %s: ticket number */
								printf(esc_html__('Select %s', 'pnpc-pocket-service-desk'), esc_html($ticket->ticket_number));
								?>
							</label>
							<input type="checkbox" name="ticket[]" id="cb-select-<?php echo esc_attr( absint( $ticket->id ) ); ?>" value="<?php echo esc_attr( absint( $ticket->id ) ); ?>">
						</th>
						<?php endif; ?>
						<td data-sort-value="<?php echo esc_attr( absint($ticket_num_for_sort) ); ?>"><strong><?php echo esc_html($ticket->ticket_number); ?></strong></td>
						<td data-sort-value="<?php echo esc_attr(strtolower($ticket->subject)); ?>">
							<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>">
								<?php echo esc_html($ticket->subject); ?>
							</a>
							<?php if (! empty($ticket->created_by_staff)) : ?>
								<span class="pnpc-psd-badge pnpc-psd-badge-staff-created" title="<?php esc_attr_e('Created by staff', 'pnpc-pocket-service-desk'); ?>">
									<span class="dashicons dashicons-admin-users"></span>
								</span>
							<?php endif; ?>
						</td>
						<td data-sort-value="<?php echo esc_attr(strtolower($user ? $user->display_name : 'zzz_unknown')); ?>"><?php echo $user ? esc_html($user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?></td>
						<td data-sort-value="<?php echo esc_attr( absint( $status_sort_value ) ); ?>">
							<?php
							$raw_status  = isset( $ticket->status ) ? (string) $ticket->status : '';
							$status_key  = strtolower( str_replace( '_', '-', $raw_status ) );
							$status_labels = array(
								'open'        => __( 'Open', 'pnpc-pocket-service-desk' ),
								'in-progress' => __( 'In Progress', 'pnpc-pocket-service-desk' ),
								'waiting'     => __( 'Waiting', 'pnpc-pocket-service-desk' ),
								'closed'      => __( 'Closed', 'pnpc-pocket-service-desk' ),
								'review'      => __( 'Review', 'pnpc-pocket-service-desk' ),
								'archived'    => __( 'Archived', 'pnpc-pocket-service-desk' ),
							);
							$status_label = isset( $status_labels[ $status_key ] ) ? $status_labels[ $status_key ] : ucwords( str_replace( '-', ' ', $status_key ) );
							?>
							<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $status_key ); ?>">
								<?php echo esc_html( $status_label ); ?>
							</span>
						</td>
						<td data-sort-value="<?php echo esc_attr( absint($priority_sort_value) ); ?>">
							<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr($ticket->priority); ?>">
								<?php echo esc_html(ucfirst($ticket->priority)); ?>
							</span>
						</td>
						<td data-sort-value="<?php echo esc_attr(strtolower($assigned_user ? $assigned_user->display_name : 'zzz_unassigned')); ?>"><?php echo $assigned_user ? esc_html($assigned_user->display_name) : esc_html__('Unassigned', 'pnpc-pocket-service-desk'); ?></td>
						<td data-sort-value="<?php echo esc_attr( absint($created_timestamp) ); ?>">
							<?php
							// Use helper to format DB datetime into WP-localized string
							if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
								echo esc_html(pnpc_psd_format_db_datetime_for_display($ticket->created_at));
							} else {
								echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at)));
							}
							?>
						</td>
						<td data-sort-value="0">
							<?php // Closed tickets have no new responses ?>
						</td>
						<td>
							<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . $ticket->id)); ?>" class="button button-small">
								<?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
			<?php else : ?>
				<tr>
					<td colspan="<?php echo ( $is_trash_view || $is_review_view ) ? ( $can_bulk_actions ? '7' : '6' ) : ( $can_bulk_actions ? '10' : '9' ); ?>">
						<?php
						if ( $is_trash_view ) {
							esc_html_e('No tickets in trash.', 'pnpc-pocket-service-desk');
						} elseif ( $is_review_view ) {
							esc_html_e('No tickets pending review.', 'pnpc-pocket-service-desk');
						} else {
							esc_html_e('No tickets found.', 'pnpc-pocket-service-desk');
						}
						?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	
	<?php if ( $total_pages > 1 ) : ?>
		<?php
		$showing_start = $offset + 1;
		$showing_end   = min( $offset + $per_page, $total_items );
		$pagination_links = paginate_links(
			array(
				'base'      => esc_url_raw( pnpc_psd_get_pagination_link( '%#%' ) ),
				'format'    => '',
				'current'   => (int) $current_page,
				'total'     => (int) $total_pages,
				'prev_text' => __( '&laquo;', 'pnpc-pocket-service-desk' ),
				'next_text' => __( '&raquo;', 'pnpc-pocket-service-desk' ),
				'type'      => 'array',
			)
		);

		// Render pagination using WordPress admin "button" styling for a cleaner, more familiar UI.
		$pagination_html = '';
		if ( is_array( $pagination_links ) && ! empty( $pagination_links ) ) {
			foreach ( $pagination_links as $link_html ) {
				$link_html = (string) $link_html;

				// Add button styles to anchors/spans.
				$link_html = preg_replace( '/class="([^"]*page-numbers[^"]*)"/i', 'class="$1 button"', $link_html );
				$link_html = str_replace( 'page-numbers current', 'page-numbers current button disabled', $link_html );
				$link_html = str_replace( 'page-numbers dots', 'page-numbers dots button disabled', $link_html );

				$pagination_html .= $link_html;
			}
		}

		printf(
			'<div class="tablenav bottom"><div class="tablenav-pages">' .
			'<span class="displaying-num">%s</span>' .
			'<span class="pagination-links">%s</span>' .
			'</div></div>',
			esc_html(
				sprintf(
					/* translators: 1: start, 2: end, 3: total */
					__( 'Showing %1$d-%2$d of %3$d tickets', 'pnpc-pocket-service-desk' ),
					(int) $showing_start,
					(int) $showing_end,
					(int) $total_items
				)
			),
			wp_kses_post( $pagination_html )
		);
		?>
	<?php endif; ?>
</div>