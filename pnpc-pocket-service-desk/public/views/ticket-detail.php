<?php

/**
 * Public ticket detail view
 *
 * - Shows ticket-level and response-level attachments with download links.
 * - Adds attachments[] input to the public reply form (multiple).
 * - Back button goes to the configured dashboard page (pnpc_psd_get_dashboard_url),
 *   falls back to page with slug 'dashboard-single' or to home_url('/dashboard-single/').
 *
 * Expects $ticket and $responses to be provided by render_ticket_detail().
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Mark ticket as viewed (customer or staff) to clear unread indicators.
 *
 * Newer builds track unread/activity on the ticket row (role-level). Historically we also
 * stored a per-user last-view meta key; we keep that for backward compatibility.
 */
if ( isset( $_GET['ticket_id'] ) && is_user_logged_in() ) {
	$current_user_id = get_current_user_id();
	$ticket_id       = absint( wp_unslash( $_GET['ticket_id'] ) );

	if ( $ticket_id > 0 ) {
		// Back-compat: per-user last view timestamp (used by older UI pieces).
		update_user_meta(
			$current_user_id,
			'pnpc_psd_ticket_last_view_' . $ticket_id,
			current_time( 'timestamp' )
		);

		// Primary: role-level tracking on the ticket row.
		if ( class_exists( 'PNPC_PSD_Ticket' ) && isset( $ticket ) && ! empty( $ticket->id ) ) {
			$owner_id = isset( $ticket->user_id ) ? (int) $ticket->user_id : 0;
			$viewer_id = (int) $current_user_id;
			if ( $owner_id && $owner_id === $viewer_id ) {
				PNPC_PSD_Ticket::mark_customer_viewed( (int) $ticket_id );
			} elseif ( current_user_can( 'pnpc_psd_view_tickets' ) ) {
				PNPC_PSD_Ticket::mark_staff_viewed( (int) $ticket_id );
			}
		}
	}
}

// Ensure helpers are available
$helpers = defined('PNPC_PSD_PLUGIN_DIR') ? PNPC_PSD_PLUGIN_DIR . 'includes/helpers.php' : '';
if ($helpers && file_exists($helpers)) {
	require_once $helpers;
}

global $wpdb;
$att_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';

// Ticket-level attachments (response_id NULL/empty/0) and not deleted.
// NOTE: Earlier builds incorrectly persisted ticket-level attachments with response_id=0.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safely constructed from $wpdb->prefix and hardcoded string
$ticket_attachments = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$att_table} WHERE ticket_id = %d AND deleted_at IS NULL AND (response_id IS NULL OR response_id = '' OR response_id = 0) ORDER BY id ASC",
		$ticket->id
	)
);

// Response attachments keyed by response_id
$response_attachments_map = array();
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safely constructed from $wpdb->prefix and hardcoded string
$all_response_atts = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$att_table} WHERE ticket_id = %d AND deleted_at IS NULL AND response_id IS NOT NULL AND response_id <> 0 ORDER BY id ASC",
		$ticket->id
	)
);
if ($all_response_atts) {
	foreach ($all_response_atts as $ra) {
		$response_attachments_map[intval($ra->response_id)][] = $ra;
	}
}

// Resolve dashboard/back URL (prefer helper)
$dashboard_url = '';
if (function_exists('pnpc_psd_get_dashboard_url')) {
	$dashboard_url = pnpc_psd_get_dashboard_url();
}
if (empty($dashboard_url)) {
	$page = get_page_by_path('dashboard');
	if ($page && ! is_wp_error($page)) {
		$dashboard_url = get_permalink($page->ID);
	} else {
		// Fallback to a stable slug; home_url(...) respects multisite/subdirectory installs.
		$dashboard_url = home_url('/dashboard/');
	}
}
?>

<div class="pnpc-psd-ticket-detail">
	<div class="pnpc-psd-ticket-header">
		<h2><?php echo esc_html($ticket->subject); ?></h2>
		<div class="pnpc-psd-ticket-meta">
			<span class="pnpc-psd-ticket-number">
				<?php printf(esc_html__('Ticket #%s', 'pnpc-pocket-service-desk'), esc_html($ticket->ticket_number)); ?>
			</span>
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
			<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr($ticket->priority); ?>">
				<?php echo esc_html(ucfirst($ticket->priority)); ?>
			</span>
		</div>
		<div class="pnpc-psd-ticket-date">
			<?php
			$created_display = function_exists('pnpc_psd_format_db_datetime_for_display')
				? pnpc_psd_format_db_datetime_for_display($ticket->created_at)
				: date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at));

			printf(esc_html__('Created on %s', 'pnpc-pocket-service-desk'), esc_html($created_display));
			?>
		</div>
	</div>

	<?php if (! empty($ticket_attachments)) : ?>
		<div class="pnpc-psd-ticket-attachments">
			<h3><?php esc_html_e('Attachments', 'pnpc-pocket-service-desk'); ?></h3>
			<ul>
				<?php foreach ($ticket_attachments as $att) : ?>
					<li>
						<a href="<?php echo esc_url( pnpc_psd_get_attachment_download_url( $att->id, $ticket_id, false ) ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html($att->file_name); ?>
						</a>
						<small class="pnpc-psd-attachment-meta">
							<?php echo esc_html(function_exists('pnpc_psd_format_filesize') ? pnpc_psd_format_filesize($att->file_size) : size_format(intval($att->file_size))); ?>
							— <?php echo esc_html(function_exists('pnpc_psd_format_db_datetime_for_display') ? pnpc_psd_format_db_datetime_for_display($att->created_at) : $att->created_at); ?>
						</small>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="pnpc-psd-ticket-description">
		<h3><?php esc_html_e('Your Request', 'pnpc-pocket-service-desk'); ?></h3>
		<div class="pnpc-psd-ticket-content">
			<?php echo wp_kses_post($ticket->description); ?>
		</div>
	</div>

	<div class="pnpc-psd-ticket-responses">
		<h3><?php esc_html_e('Conversation', 'pnpc-pocket-service-desk'); ?></h3>

		<?php if (! empty($responses)) : ?>
			<?php foreach ($responses as $response) : ?>
				<?php
				$response_user = get_userdata($response->user_id);
				$is_staff = intval($response->is_staff_response) === 1;
				$resp_ts = function_exists('pnpc_psd_mysql_to_wp_local_ts') ? intval(pnpc_psd_mysql_to_wp_local_ts($response->created_at)) : intval(strtotime($response->created_at));
				$atts_for_response = isset($response_attachments_map[intval($response->id)]) ? $response_attachments_map[intval($response->id)] : array();
				?>
				<div class="pnpc-psd-response <?php echo esc_attr( $is_staff ? 'pnpc-psd-response-staff' : 'pnpc-psd-response-customer' ); ?>">
					<div class="pnpc-psd-response-header">
						<div class="pnpc-psd-response-author">
							<strong><?php echo $response_user ? esc_html($response_user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?></strong>
							<?php if ($is_staff) : ?>
								<span class="pnpc-psd-staff-badge"><?php esc_html_e('Support Staff', 'pnpc-pocket-service-desk'); ?></span>
							<?php endif; ?>
						</div>
						<span class="pnpc-psd-response-date">
							<?php echo esc_html(human_time_diff($resp_ts, current_time('timestamp')) . ' ' . __('ago', 'pnpc-pocket-service-desk')); ?>
						</span>
					</div>
					<div class="pnpc-psd-response-content">
						<?php echo wp_kses_post($response->response); ?>
					</div>

					<?php if (! empty($atts_for_response)) : ?>
						<div class="pnpc-psd-response-attachments">
							<strong><?php esc_html_e('Attachments:', 'pnpc-pocket-service-desk'); ?></strong>
							<ul>
								<?php foreach ($atts_for_response as $ra) : ?>
									<li>
										<a href="<?php echo esc_url( pnpc_psd_get_attachment_download_url( $ra->id, $ticket_id, false ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($ra->file_name); ?></a>
										<small> (<?php echo esc_html(function_exists('pnpc_psd_format_filesize') ? pnpc_psd_format_filesize($ra->file_size) : size_format(intval($ra->file_size))); ?>)</small>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="pnpc-psd-no-responses"><?php esc_html_e('No responses yet. We will get back to you as soon as possible.', 'pnpc-pocket-service-desk'); ?></p>
		<?php endif; ?>
	</div>

	<?php if ('closed' !== $ticket->status) : ?>
		<div class="pnpc-psd-add-response">
			<h3><?php esc_html_e('Add a Reply', 'pnpc-pocket-service-desk'); ?></h3>

			<form id="pnpc-psd-response-form" data-ticket-id="<?php echo esc_attr($ticket->id); ?>" enctype="multipart/form-data">
				<div class="pnpc-psd-form-group">
					<textarea id="response-text" name="response" rows="6" placeholder="<?php esc_attr_e('Type your message here...', 'pnpc-pocket-service-desk'); ?>" required></textarea>
				</div>

				<div class="pnpc-psd-form-group">
					<label for="response-attachments"><?php esc_html_e('Attachments (optional)', 'pnpc-pocket-service-desk'); ?></label>
					<input type="file" id="response-attachments" name="attachments[]" multiple />
					<div id="pnpc-psd-response-attachments-list" style="margin-top:8px;"></div>
							<?php
								$allowed_items = function_exists( 'pnpc_psd_get_allowed_file_types_list' ) ? pnpc_psd_get_allowed_file_types_list() : array();
								$mime_to_ext = array(
									'image/jpeg' => array( 'jpg', 'jpeg' ),
									'image/png'  => array( 'png' ),
									'image/gif'  => array( 'gif' ),
									'image/webp' => array( 'webp' ),
									'application/pdf' => array( 'pdf' ),
								);
								$exts = array();
								foreach ( (array) $allowed_items as $it ) {
									$it = strtolower( trim( (string) $it ) );
									if ( '' === $it ) { continue; }
									if ( false !== strpos( $it, '/' ) && isset( $mime_to_ext[ $it ] ) ) {
										$exts = array_merge( $exts, (array) $mime_to_ext[ $it ] );
									} else {
										$exts[] = preg_replace( '/[^a-z0-9]/', '', $it );
									}
								}
								$exts = array_values( array_unique( array_filter( $exts ) ) );
								if ( empty( $exts ) ) {
									$exts = array( 'jpg', 'jpeg', 'png', 'pdf' );
								}
								sort( $exts );
								$max_mb = function_exists( 'pnpc_psd_get_max_attachment_mb' ) ? (int) pnpc_psd_get_max_attachment_mb() : 5;
							?>
							<p class="pnpc-psd-help-text">
								<?php
								printf(
									esc_html__( 'Allowed formats: %1$s. Max size per file: %2$dMB (server limits may apply).', 'pnpc-pocket-service-desk' ),
									esc_html( implode( ', ', $exts ) ),
									(int) $max_mb
								);
								?>
							</p>
				</div>

				<div class="pnpc-psd-form-group">
					<button type="submit" class="pnpc-psd-button pnpc-psd-button-primary"><?php esc_html_e('Send Reply', 'pnpc-pocket-service-desk'); ?></button>
				</div>

				<div id="response-message" class="pnpc-psd-message"></div>
			</form>
		</div>
	<?php else : ?>
		<div class="pnpc-psd-ticket-closed-notice">
			<p><?php esc_html_e('This ticket has been closed. If you need further assistance, please create a new ticket.', 'pnpc-pocket-service-desk'); ?></p>
		</div>
	<?php endif; ?>

	<div class="pnpc-psd-ticket-actions">
		<a href="<?php echo esc_url($dashboard_url); ?>" class="pnpc-psd-button">
			&larr; <?php esc_html_e('Back to Dashboard', 'pnpc-pocket-service-desk'); ?>
		</a>
	</div>
</div>