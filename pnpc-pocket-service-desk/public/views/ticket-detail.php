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

if ( ! defined( 'ABSPATH' ) ) {
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
/* translators: Placeholder(s) in localized string. */
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

/* translators: Placeholder(s) in localized string. */
			printf(esc_html__('Created on %s', 'pnpc-pocket-service-desk'), esc_html($created_display));
			?>
		</div>
	</div>

	<?php if (! empty($ticket_attachments)) : ?>
		<div class="pnpc-psd-ticket-attachments">
			<h3><?php esc_html_e('Attachments', 'pnpc-pocket-service-desk'); ?></h3>
			<div class="pnpc-psd-attachments-grid">
				<?php foreach ($ticket_attachments as $att) : ?>
					<?php
						$file_name = (string) $att->file_name;
						$file_ext  = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
						$file_type = function_exists( 'pnpc_psd_get_attachment_type' ) ? pnpc_psd_get_attachment_type( $file_ext ) : 'other';
						$file_size = (int) $att->file_size;
						$can_preview = function_exists( 'pnpc_psd_can_preview_attachment' ) ? pnpc_psd_can_preview_attachment( $file_size ) : true;
						$file_url = pnpc_psd_get_attachment_download_url( $att->id, $ticket_id, true );
						$download_url = pnpc_psd_get_attachment_download_url( $att->id, $ticket_id, false );
						$file_size_formatted = function_exists( 'pnpc_psd_format_filesize' ) ? pnpc_psd_format_filesize( $file_size ) : size_format( $file_size );
						?>
						<div class="pnpc-psd-attachment pnpc-psd-attachment-<?php echo esc_attr( $file_type ); ?>">
							<?php if ( 'image' === $file_type && $can_preview ) : ?>
								<img src="<?php echo esc_url( $file_url ); ?>" alt="<?php echo esc_attr( $file_name ); ?>" class="pnpc-psd-attachment-thumbnail" />
							<?php else : ?>
								<div class="pnpc-psd-attachment-icon"><?php echo esc_html( function_exists( 'pnpc_psd_get_file_icon' ) ? pnpc_psd_get_file_icon( $file_ext ) : '📎' ); ?></div>
							<?php endif; ?>
							<div class="pnpc-psd-attachment-info">
								<strong><?php echo esc_html( $file_name ); ?></strong>
								<span class="pnpc-psd-attachment-meta"><?php echo esc_html( $file_size_formatted ); ?> · <?php echo esc_html( strtoupper( $file_ext ) ); ?></span>
							</div>
							<div class="pnpc-psd-attachment-actions">
								<?php if ( $can_preview && in_array( $file_type, array( 'image', 'pdf' ), true ) ) : ?>
									<button type="button" class="pnpc-psd-view-attachment pnpc-psd-button" data-type="<?php echo esc_attr( $file_type ); ?>" data-url="<?php echo esc_url( $file_url ); ?>" data-filename="<?php echo esc_attr( $file_name ); ?>"><?php esc_html_e( 'View', 'pnpc-pocket-service-desk' ); ?></button>
								<?php endif; ?>
								<a href="<?php echo esc_url( $download_url ); ?>" class="pnpc-psd-button pnpc-psd-button-secondary" download><?php esc_html_e( 'Download', 'pnpc-pocket-service-desk' ); ?></a>
							</div>
						</div>
				<?php endforeach; ?>
			</div>
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
							<div class="pnpc-psd-attachments-grid">
								<?php foreach ($atts_for_response as $ra) : ?>
									<?php
										$file_name = (string) $ra->file_name;
										$file_ext  = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
										$file_type = function_exists( 'pnpc_psd_get_attachment_type' ) ? pnpc_psd_get_attachment_type( $file_ext ) : 'other';
										$file_size = (int) $ra->file_size;
										$can_preview = function_exists( 'pnpc_psd_can_preview_attachment' ) ? pnpc_psd_can_preview_attachment( $file_size ) : true;
										$file_url = pnpc_psd_get_attachment_download_url( $ra->id, $ticket_id, true );
										$download_url = pnpc_psd_get_attachment_download_url( $ra->id, $ticket_id, false );
										$file_size_formatted = function_exists( 'pnpc_psd_format_filesize' ) ? pnpc_psd_format_filesize( $file_size ) : size_format( $file_size );
										?>
										<div class="pnpc-psd-attachment pnpc-psd-attachment-<?php echo esc_attr( $file_type ); ?>">
											<?php if ( 'image' === $file_type && $can_preview ) : ?>
												<img src="<?php echo esc_url( $file_url ); ?>" alt="<?php echo esc_attr( $file_name ); ?>" class="pnpc-psd-attachment-thumbnail" />
											<?php else : ?>
												<div class="pnpc-psd-attachment-icon"><?php echo esc_html( function_exists( 'pnpc_psd_get_file_icon' ) ? pnpc_psd_get_file_icon( $file_ext ) : '📎' ); ?></div>
											<?php endif; ?>
											<div class="pnpc-psd-attachment-info">
												<strong><?php echo esc_html( $file_name ); ?></strong>
												<span class="pnpc-psd-attachment-meta"><?php echo esc_html( $file_size_formatted ); ?> · <?php echo esc_html( strtoupper( $file_ext ) ); ?></span>
											</div>
											<div class="pnpc-psd-attachment-actions">
												<?php if ( $can_preview && in_array( $file_type, array( 'image', 'pdf' ), true ) ) : ?>
													<button type="button" class="pnpc-psd-view-attachment pnpc-psd-button" data-type="<?php echo esc_attr( $file_type ); ?>" data-url="<?php echo esc_url( $file_url ); ?>" data-filename="<?php echo esc_attr( $file_name ); ?>"><?php esc_html_e( 'View', 'pnpc-pocket-service-desk' ); ?></button>
												<?php endif; ?>
												<a href="<?php echo esc_url( $download_url ); ?>" class="pnpc-psd-button pnpc-psd-button-secondary" download><?php esc_html_e( 'Download', 'pnpc-pocket-service-desk' ); ?></a>
											</div>
										</div>
								<?php endforeach; ?>
							</div>
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
/* translators: Placeholder(s) in localized string. */
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

	<?php // Attachment lightbox (shared UI with admin ticket view). ?>
	<div id="pnpc-psd-lightbox" class="pnpc-psd-lightbox" style="display:none;" role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php esc_attr_e('Attachment Viewer', 'pnpc-pocket-service-desk'); ?>">
		<div class="pnpc-psd-lightbox-backdrop"></div>
		<div class="pnpc-psd-lightbox-content">
			<div class="pnpc-psd-lightbox-header">
				<button type="button" class="pnpc-psd-lightbox-close" aria-label="<?php esc_attr_e('Close', 'pnpc-pocket-service-desk'); ?>">×</button>
				<a href="#" download class="pnpc-psd-lightbox-download pnpc-psd-button pnpc-psd-button-primary"><?php esc_html_e('Download', 'pnpc-pocket-service-desk'); ?></a>
			</div>
			<div class="pnpc-psd-lightbox-image-container">
				<img src="" alt="" class="pnpc-psd-lightbox-image" />
				<div class="pnpc-psd-lightbox-caption">
					<span class="pnpc-psd-lightbox-filename"></span>
					<span class="pnpc-psd-lightbox-counter"></span>
				</div>
			</div>
			<div class="pnpc-psd-lightbox-pdf-container" style="display:none;">
				<iframe src="" type="application/pdf" class="pnpc-psd-lightbox-pdf" title="<?php esc_attr_e('PDF Viewer', 'pnpc-pocket-service-desk'); ?>"></iframe>
			</div>
			<div class="pnpc-psd-lightbox-loading" style="display:none;">
				<div class="pnpc-psd-spinner"></div>
				<span><?php esc_html_e('Loading...', 'pnpc-pocket-service-desk'); ?></span>
			</div>
			<button type="button" class="pnpc-psd-lightbox-prev" aria-label="<?php esc_attr_e('Previous', 'pnpc-pocket-service-desk'); ?>">‹</button>
			<button type="button" class="pnpc-psd-lightbox-next" aria-label="<?php esc_attr_e('Next', 'pnpc-pocket-service-desk'); ?>">›</button>
		</div>
	</div>
</div>