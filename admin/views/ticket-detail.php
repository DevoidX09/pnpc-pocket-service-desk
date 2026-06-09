<?php

/**
 * Admin ticket detail view (includes assignment, status updates, responses, and attachments)
 *
 * Expects $ticket, $responses, $agents variables populated by the controller.
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Mark ticket as viewed by current agent
 * This clears the "New" badge in the ticket list
 */
if (isset($_GET['ticket_id']) && is_user_logged_in() && current_user_can('pnpc_psd_view_tickets')) {
	$current_user_id = get_current_user_id();
	$ticket_id = absint( wp_unslash( $_GET['ticket_id'] ) );

	if ($ticket_id > 0) {
		// Store current timestamp as "last viewed" for this agent
		update_user_meta(
			$current_user_id,
			'pnpc_psd_ticket_last_view_' . $ticket_id,
			current_time('timestamp') // WordPress local timestamp (integer)
		);
	}
}

global $wpdb;
$att_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safely constructed from $wpdb->prefix and hardcoded string
$ticket_attachments = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$att_table} WHERE ticket_id = %d AND deleted_at IS NULL AND (response_id IS NULL OR response_id = '' OR response_id = 0) ORDER BY id ASC",
		$ticket->id
	)
);
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

$status_options = array(
	'open'        => __('Open', 'pnpc-pocket-service-desk'),
	'in-progress' => __('In Progress', 'pnpc-pocket-service-desk'),
	'waiting'     => __('Waiting', 'pnpc-pocket-service-desk'),
	'closed'      => __('Closed', 'pnpc-pocket-service-desk'),
);

// Normalize stored status keys (some legacy rows used underscores).
$ticket_status_key = isset( $ticket->status ) ? strtolower( str_replace( '_', '-', (string) $ticket->status ) ) : '';

if (! function_exists('pnpc_psd_admin_format_datetime')) {
/**
 * Pnpc psd admin format datetime.
 *
 * @param mixed $datetime
 *
 * @since 1.1.1.4
 *
 * @return mixed
 */
	function pnpc_psd_admin_format_datetime($datetime)
	{
		return function_exists('pnpc_psd_format_db_datetime_for_display')
			? pnpc_psd_format_db_datetime_for_display($datetime)
			: date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($datetime));
	}
}

$ticket_created_display = pnpc_psd_admin_format_datetime($ticket->created_at);
$ticket_updated_display = ! empty( $ticket->updated_at ) ? pnpc_psd_admin_format_datetime( $ticket->updated_at ) : $ticket_created_display;

// Fetch user data once for reuse in multiple sections (creator info, requestor info).
$ticket_user = $ticket->user_id ? get_userdata($ticket->user_id) : null;
$ticket_user_name = $ticket_user ? esc_html($ticket_user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk');
$ticket_user_edit_link = $ticket_user ? get_edit_user_link($ticket_user->ID) : '';
?>

<div class="wrap pnpc-psd-ticket-detail pnpc-psd-ticket-detail-modern" id="pnpc-psd-ticket-detail" data-ticket-id="<?php echo esc_attr($ticket->id); ?>">
	<div class="pnpc-psd-ticket-detail-header pnpc-psd-modern-topbar">
		<div class="pnpc-psd-breadcrumb">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-tickets' ) ); ?>">
				<?php esc_html_e('All Tickets', 'pnpc-pocket-service-desk'); ?>
			</a>
			<span class="separator"> &raquo; </span>
			<span class="current pnpc-psd-ticket-detail-current"><?php esc_html_e( 'Ticket Detail', 'pnpc-pocket-service-desk' ); ?></span>
			<span class="separator"> &raquo; </span>
			<span class="pnpc-psd-ticket-number-current"><?php echo esc_html($ticket->ticket_number); ?></span>
		</div>

		<div class="pnpc-psd-quick-nav">
			<?php if ( ! empty( $prev_ticket_id ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-ticket&ticket_id=' . absint( $prev_ticket_id ) ) ); ?>" class="button" title="<?php esc_attr_e( 'Previous Ticket', 'pnpc-pocket-service-desk' ); ?>">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
					<?php esc_html_e('Prev', 'pnpc-pocket-service-desk'); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! empty( $next_ticket_id ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-ticket&ticket_id=' . absint( $next_ticket_id ) ) ); ?>" class="button" title="<?php esc_attr_e( 'Next Ticket', 'pnpc-pocket-service-desk' ); ?>">
					<?php esc_html_e('Next', 'pnpc-pocket-service-desk'); ?>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-tickets' ) ); ?>" class="button">
				<span class="dashicons dashicons-arrow-left-alt2"></span>
				<?php esc_html_e('Back to All Tickets', 'pnpc-pocket-service-desk'); ?>
			</a>
		</div>
	</div>

	<?php
	// Check if staff-created.
	if (! empty($ticket->created_by_staff)) {
		$staff_user = get_userdata($ticket->created_by_staff);
		$customer_user = get_userdata($ticket->user_id);
		$staff_name = $staff_user ? esc_html($staff_user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk');
		$customer_name = $customer_user ? esc_html($customer_user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk');
		?>
		<div class="pnpc-psd-staff-created-badge">
			<span class="dashicons dashicons-admin-users"></span>
			<?php
			/* translators: 1: staff member name, 2: customer name */
			printf(
				esc_html__('Staff-Created Ticket: Created by %1$s on behalf of %2$s', 'pnpc-pocket-service-desk'),
				'<strong>' . esc_html( $staff_name ) . '</strong>',
				'<strong>' . esc_html( $customer_name ) . '</strong>'
			);
			?>
		</div>
		<?php
	}
	?>

	<div id="pnpc-psd-admin-action-message" class="pnpc-psd-message" style="display:none;"></div>

	<div class="pnpc-psd-ticket-shell">
		<main class="pnpc-psd-ticket-main-column">
			<section class="pnpc-psd-ticket-hero-card">
				<div class="pnpc-psd-ticket-hero-icon" aria-hidden="true">
					<span class="dashicons dashicons-portfolio"></span>
				</div>
				<div class="pnpc-psd-ticket-hero-content">
					<div class="pnpc-psd-ticket-title-row">
						<h1><?php echo esc_html($ticket->subject); ?></h1>
						<?php
						$ticket_status_label = isset( $status_options[ $ticket_status_key ] )
							? $status_options[ $ticket_status_key ]
							: ucwords( str_replace( '-', ' ', $ticket_status_key ) );
						?>
						<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $ticket_status_key ); ?>"><?php echo esc_html( $ticket_status_label ); ?></span>
					</div>
					<div class="pnpc-psd-ticket-hero-meta">
						<span>#<?php echo esc_html($ticket->ticket_number); ?></span>
						<span><?php esc_html_e('Created', 'pnpc-pocket-service-desk'); ?> <?php echo esc_html($ticket_created_display); ?></span>
						<span><?php esc_html_e('Updated', 'pnpc-pocket-service-desk'); ?> <?php echo esc_html($ticket_updated_display); ?></span>
						<span><?php esc_html_e('via Web Portal', 'pnpc-pocket-service-desk'); ?></span>
					</div>
					<div class="pnpc-psd-ticket-request-body">
						<?php
						$ticket_body = isset($ticket->description) ? $ticket->description : '';
						echo wp_kses_post( wpautop( $ticket_body ) );
						?>
					</div>
				</div>
			</section>

			<?php do_action( 'pnpc_psd_admin_ticket_detail_before_conversation', $ticket ); ?>
			<div id="pnpc-psd-internal-collab-anchor"></div>

			<section class="pnpc-psd-conversation-modern-card pnpc-psd-modern-card">
				<div class="pnpc-psd-conversation-modern-header">
					<h3><?php esc_html_e('Conversation', 'pnpc-pocket-service-desk'); ?></h3>
					<span class="pnpc-psd-subtle-pill"><?php esc_html_e('All Activity', 'pnpc-pocket-service-desk'); ?></span>
				</div>

				<div class="pnpc-psd-message-thread">
				<?php if (! empty($responses)) : ?>
					<?php foreach ($responses as $r) : ?>
						<?php
						$responder = get_userdata($r->user_id);
						$is_staff = intval($r->is_staff_response) === 1;
						$atts_for_response = isset($response_attachments_map[intval($r->id)]) ? $response_attachments_map[intval($r->id)] : array();
						$responder_name = $responder ? $responder->display_name : __('Unknown', 'pnpc-pocket-service-desk');
						$initials = '??';
						if ( $responder_name ) {
							$parts = preg_split( '/\s+/', trim( (string) $responder_name ) );
							$initials = strtoupper( substr( $parts[0], 0, 1 ) . ( isset( $parts[1] ) ? substr( $parts[1], 0, 1 ) : '' ) );
						}
						?>
						<div class="pnpc-psd-response <?php echo esc_attr( $is_staff ? 'pnpc-psd-response-staff' : 'pnpc-psd-response-customer' ); ?>">
							<div class="pnpc-psd-message-avatar" aria-hidden="true"><?php echo esc_html( $initials ); ?></div>
							<div class="pnpc-psd-message-bubble">
								<div class="pnpc-psd-response-header">
									<strong><?php echo esc_html($responder_name); ?></strong>
									<?php if ( $is_staff ) : ?><span class="pnpc-psd-agent-chip"><?php esc_html_e('Agent', 'pnpc-pocket-service-desk'); ?></span><?php endif; ?>
									<span class="pnpc-psd-response-date"><?php echo esc_html(pnpc_psd_admin_format_datetime($r->created_at)); ?></span>
								</div>
								<div class="pnpc-psd-response-content"><?php echo wp_kses_post( wpautop( $r->response ) ); ?></div>
								<?php if (! empty($atts_for_response)) : ?>
									<div class="pnpc-psd-response-attachments">
										<strong><?php esc_html_e('Attachments:', 'pnpc-pocket-service-desk'); ?></strong>
										<?php foreach ($atts_for_response as $ra) : ?>
											<?php
											$file_size = intval($ra->file_size);
											$download_url = esc_url( pnpc_psd_get_attachment_download_url( $ra->id, $ticket->id, false ) );
											$file_name = esc_html($ra->file_name);
											$file_size_formatted = pnpc_psd_format_filesize($file_size);
											?>
											<a class="pnpc-psd-message-attachment" href="<?php echo esc_url( $download_url ); ?>" download><?php echo esc_html( $file_name ); ?> <span><?php echo esc_html($file_size_formatted); ?></span></a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p><?php esc_html_e('No responses yet.', 'pnpc-pocket-service-desk'); ?></p>
				<?php endif; ?>
				</div>

				<h3 class="screen-reader-text pnpc-psd-conversation-anchor-heading"><?php esc_html_e('Conversation', 'pnpc-pocket-service-desk'); ?></h3>
				<?php if ('closed' !== $ticket->status && current_user_can('pnpc_psd_respond_to_tickets')) : ?>
					<div class="pnpc-psd-add-response">
						<div class="pnpc-psd-reply-tabs" role="tablist" aria-label="<?php esc_attr_e('Response type', 'pnpc-pocket-service-desk'); ?>">
							<button type="button" class="pnpc-psd-reply-tab is-active"><?php esc_html_e('Reply to Client', 'pnpc-pocket-service-desk'); ?></button>
						</div>
						<form id="pnpc-psd-response-form-admin" enctype="multipart/form-data" data-ticket-id="<?php echo esc_attr($ticket->id); ?>">
							<?php wp_nonce_field('pnpc_psd_admin_nonce', 'nonce'); ?>
							<textarea id="response-text" name="response" rows="6" placeholder="<?php esc_attr_e('Type your reply to the customer...', 'pnpc-pocket-service-desk'); ?>"></textarea>
							<div id="pnpc-psd-signature-controls" class="pnpc-psd-signature-controls" style="margin-top:8px;">
								<strong><?php esc_html_e( 'Signature:', 'pnpc-pocket-service-desk' ); ?></strong>
								<label><input type="radio" name="pnpc_psd_signature_mode" value="none" /> <?php esc_html_e( 'None', 'pnpc-pocket-service-desk' ); ?></label>
								<label><input type="radio" name="pnpc_psd_signature_mode" value="personal" /> <?php esc_html_e( 'Personal', 'pnpc-pocket-service-desk' ); ?></label>
								<label><input type="radio" name="pnpc_psd_signature_mode" value="group" /> <?php esc_html_e( 'Group', 'pnpc-pocket-service-desk' ); ?></label>
							</div>
							<div class="pnpc-psd-reply-actions-row">
								<label class="pnpc-psd-file-button" for="admin-response-attachments"><span class="dashicons dashicons-paperclip"></span><?php esc_html_e('Attach Files', 'pnpc-pocket-service-desk'); ?></label>
								<input type="file" id="admin-response-attachments" name="attachments[]" multiple />
								<div class="pnpc-psd-attachments-list" id="pnpc-psd-admin-response-attachments-list"></div>
								<button type="submit" class="button button-primary pnpc-psd-send-reply-button"><span class="dashicons dashicons-email-alt2"></span><?php esc_html_e('Send Reply to Client', 'pnpc-pocket-service-desk'); ?></button>
							</div>
							<div id="response-message" class="pnpc-psd-message" style="display:none;"></div>
						</form>
						<?php do_action( 'pnpc_psd_admin_ticket_reply_internal_panel', $ticket ); ?>
					</div>
				<?php endif; ?>
			</section>
			<?php if (! empty($ticket_attachments)) : ?>
				<section class="pnpc-psd-attachments pnpc-psd-modern-card">
					<h3><?php esc_html_e('Attachments', 'pnpc-pocket-service-desk'); ?> <span class="pnpc-psd-count-pill"><?php echo count($ticket_attachments); ?></span></h3>

					<?php foreach ($ticket_attachments as $att) : ?>
						<?php
						$file_size = intval($att->file_size);
						$file_url = esc_url( pnpc_psd_get_attachment_download_url( $att->id, $ticket->id, true ) );
						$download_url = esc_url( pnpc_psd_get_attachment_download_url( $att->id, $ticket->id, false ) );
						$file_name = esc_html($att->file_name);
						$file_ext = strtolower(pathinfo($att->file_name, PATHINFO_EXTENSION));
						$file_type = pnpc_psd_get_attachment_type($file_ext);
						$can_preview = pnpc_psd_can_preview_attachment($file_size);
						$file_size_formatted = pnpc_psd_format_filesize($file_size);
						?>

						<div class="pnpc-psd-attachment pnpc-psd-attachment-<?php echo esc_attr($file_type); ?>">
							<?php if ($file_type === 'image' && $can_preview) : ?>
								<img src="<?php echo esc_url( $file_url ); ?>" alt="<?php echo esc_attr( $file_name ); ?>" class="pnpc-psd-attachment-thumbnail">
							<?php else : ?>
								<div class="pnpc-psd-attachment-icon"><?php echo esc_html( pnpc_psd_get_file_icon($file_ext) ); ?></div>
							<?php endif; ?>

							<div class="pnpc-psd-attachment-info">
								<strong><?php echo esc_html( $file_name ); ?></strong>
								<span class="pnpc-psd-attachment-meta"><?php echo esc_html($file_size_formatted); ?> · <?php echo esc_html(strtoupper($file_ext)); ?></span>
							</div>

							<div class="pnpc-psd-attachment-actions">
								<?php if ($can_preview && in_array($file_type, array('image', 'pdf'), true)) : ?>
									<button type="button" class="pnpc-psd-view-attachment button" data-type="<?php echo esc_attr($file_type); ?>" data-url="<?php echo esc_url( $file_url ); ?>" data-filename="<?php echo esc_attr( $file_name ); ?>"><?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?></button>
								<?php endif; ?>
								<a href="<?php echo esc_url( $download_url ); ?>" download class="button <?php echo ! $can_preview ? 'button-primary' : ''; ?>"><?php esc_html_e('Download', 'pnpc-pocket-service-desk'); ?></a>
							</div>
						</div>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>

		</main>

		<aside class="pnpc-psd-ticket-sidebar-column">
			<?php
			$ticket_priority_key = isset( $ticket->priority ) ? strtolower( sanitize_key( (string) $ticket->priority ) ) : 'normal';
			?>
			<section class="pnpc-psd-sidebar-card pnpc-psd-customer-control-card">
				<div class="pnpc-psd-ticket-actions">
					<?php if (current_user_can('pnpc_psd_assign_tickets')) : ?>
						<div class="pnpc-psd-field pnpc-psd-pill-field pnpc-psd-select-pill-field">
							<label for="pnpc-psd-assign-agent"><?php esc_html_e('Assign Agent', 'pnpc-pocket-service-desk'); ?></label>
							<select id="pnpc-psd-assign-agent" name="assigned_to">
								<option value="0"><?php esc_html_e('Unassigned', 'pnpc-pocket-service-desk'); ?></option>
								<?php foreach ($agents as $agent) : ?>
									<option value="<?php echo esc_attr($agent->ID); ?>" <?php selected((int) $ticket->assigned_to, (int) $agent->ID); ?>><?php echo esc_html($agent->display_name); ?></option>
								<?php endforeach; ?>
							</select>
							<button type="button" class="button pnpc-psd-hidden-action-button" id="pnpc-psd-assign-button"><?php esc_html_e('Assign', 'pnpc-pocket-service-desk'); ?></button>
						</div>
					<?php endif; ?>

					<?php if (current_user_can('pnpc_psd_respond_to_tickets')) : ?>
						<div class="pnpc-psd-field pnpc-psd-pill-field pnpc-psd-select-pill-field pnpc-psd-status-pill-field pnpc-psd-current-status-<?php echo esc_attr( $ticket_status_key ); ?>">
							<label for="pnpc-psd-status-select"><?php esc_html_e('Ticket Status', 'pnpc-pocket-service-desk'); ?></label>
							<select id="pnpc-psd-status-select" name="status" class="pnpc-psd-ticket-control-select pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $ticket_status_key ); ?>">
								<?php foreach ($status_options as $key => $label) : ?>
									<option value="<?php echo esc_attr($key); ?>" <?php selected($ticket_status_key, $key); ?>><?php echo esc_html($label); ?></option>
								<?php endforeach; ?>
							</select>
							<button type="button" class="button pnpc-psd-hidden-action-button" id="pnpc-psd-status-button"><?php esc_html_e('Update Status', 'pnpc-pocket-service-desk'); ?></button>
						</div>
					<?php endif; ?>

					<?php if ( current_user_can( 'pnpc_psd_assign_tickets' ) || current_user_can( 'manage_options' ) ) : ?>
						<?php
						$priority_opts = array(
							'low'    => __( 'Low', 'pnpc-pocket-service-desk' ),
							'normal' => __( 'Normal', 'pnpc-pocket-service-desk' ),
							'high'   => __( 'High', 'pnpc-pocket-service-desk' ),
							'urgent' => __( 'Urgent', 'pnpc-pocket-service-desk' ),
						);
						$redirect_to = add_query_arg(
							array(
								'page'      => 'pnpc-service-desk-ticket',
								'ticket_id' => absint( $ticket->id ),
							),
							admin_url( 'admin.php' )
						);
						?>
						<form class="pnpc-psd-field pnpc-psd-pill-field pnpc-psd-select-pill-field pnpc-psd-priority-pill-field pnpc-psd-current-priority-<?php echo esc_attr( $ticket_priority_key ); ?>" id="pnpc-psd-priority-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
							<input type="hidden" name="page" value="pnpc-service-desk-ticket" />
							<input type="hidden" name="ticket_id" value="<?php echo esc_attr( absint( $ticket->id ) ); ?>" />
							<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
							<?php wp_nonce_field( 'pnpc_psd_update_ticket_priority', 'pnpc_psd_update_priority_nonce' ); ?>
							<label for="pnpc-psd-priority-select"><?php esc_html_e( 'Priority', 'pnpc-pocket-service-desk' ); ?></label>
							<select id="pnpc-psd-priority-select" name="priority" class="pnpc-psd-ticket-control-select pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr( $ticket_priority_key ); ?>">
								<?php foreach ( $priority_opts as $p_key => $p_label ) : ?>
									<option value="<?php echo esc_attr( $p_key ); ?>" <?php selected( (string) $ticket->priority, (string) $p_key ); ?>><?php echo esc_html( $p_label ); ?></option>
								<?php endforeach; ?>
							</select>
							<button type="submit" class="button pnpc-psd-hidden-action-button" id="pnpc-psd-priority-button"><?php esc_html_e( 'Update Priority', 'pnpc-pocket-service-desk' ); ?></button>
						</form>
					<?php endif; ?>

					<?php do_action( 'pnpc_psd_admin_ticket_controls', $ticket ); ?>
				</div>
			</section>

			<section class="pnpc-psd-sidebar-card pnpc-psd-customer-info-card">
				<h2><?php esc_html_e('Customer Information', 'pnpc-pocket-service-desk'); ?></h2>
				<div class="pnpc-psd-customer-profile-row">
					<div class="pnpc-psd-customer-avatar" aria-hidden="true"><?php echo esc_html( strtoupper( substr( wp_strip_all_tags( $ticket_user_name ), 0, 2 ) ) ); ?></div>
					<div class="pnpc-psd-customer-info-fields">
						<div class="pnpc-psd-customer-info-field pnpc-psd-customer-info-name"><span class="pnpc-psd-customer-info-label"><?php esc_html_e('Name', 'pnpc-pocket-service-desk'); ?></span><strong><?php echo esc_html( $ticket_user_name ); ?></strong></div>
						<?php if ($ticket_user) : ?><div class="pnpc-psd-customer-info-field"><span class="pnpc-psd-customer-info-label"><?php esc_html_e('Email', 'pnpc-pocket-service-desk'); ?></span><span><?php echo esc_html( $ticket_user->user_email ); ?></span></div><?php endif; ?>
						<?php if ($ticket_user && ! empty($ticket_user->user_url)) : ?><div class="pnpc-psd-customer-info-field"><span class="pnpc-psd-customer-info-label"><?php esc_html_e('URL', 'pnpc-pocket-service-desk'); ?></span><a href="<?php echo esc_url($ticket_user->user_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $ticket_user->user_url ); ?></a></div><?php endif; ?>
						<?php if ($ticket_user_edit_link) : ?><div class="pnpc-psd-customer-info-field"><span class="pnpc-psd-customer-info-label"><?php esc_html_e('Profile', 'pnpc-pocket-service-desk'); ?></span><a href="<?php echo esc_url($ticket_user_edit_link); ?>"><?php esc_html_e('View Customer Profile', 'pnpc-pocket-service-desk'); ?></a></div><?php endif; ?>
					</div>
				</div>
				<div class="pnpc-psd-customer-info-tools" id="pnpc-psd-client-notes-controls-anchor"></div>
				<div id="pnpc-psd-client-notes-anchor"></div>
			</section>

			<section class="pnpc-psd-sidebar-card pnpc-psd-ticket-details-card">
				<h2><?php esc_html_e('Ticket Details', 'pnpc-pocket-service-desk'); ?></h2>
				<dl class="pnpc-psd-details-list">
					<dt><?php esc_html_e('Status', 'pnpc-pocket-service-desk'); ?></dt><dd><?php echo esc_html( $ticket_status_label ); ?></dd>
					<dt><?php esc_html_e('Priority', 'pnpc-pocket-service-desk'); ?></dt><dd><?php echo esc_html( ucfirst( (string) $ticket->priority ) ); ?></dd>
					<dt><?php esc_html_e('Assigned', 'pnpc-pocket-service-desk'); ?></dt><dd><?php $assigned_user = $ticket->assigned_to ? get_userdata($ticket->assigned_to) : null; echo $assigned_user ? esc_html($assigned_user->display_name) : esc_html__('Unassigned', 'pnpc-pocket-service-desk'); ?></dd>
					<dt><?php esc_html_e('Created', 'pnpc-pocket-service-desk'); ?></dt><dd><?php echo esc_html($ticket_created_display); ?></dd>
					<dt><?php esc_html_e('Updated', 'pnpc-pocket-service-desk'); ?></dt><dd><?php echo esc_html($ticket_updated_display); ?></dd>
				</dl>
			</section>
		</aside>
	</div>

	<?php
	// Agents can request deletion into the Review queue; only Managers/Admins can approve to Trash.
	if ( current_user_can( 'pnpc_psd_view_tickets' ) ) :
	?>
	<section class="pnpc-psd-danger-zone" aria-labelledby="pnpc-psd-danger-zone-title">
		<h3 id="pnpc-psd-danger-zone-title"><?php esc_html_e('Danger Zone', 'pnpc-pocket-service-desk'); ?></h3>
		<p><?php esc_html_e('Submitting a delete request sends this ticket to the Review queue for approval by a manager or administrator.', 'pnpc-pocket-service-desk'); ?></p>

		<button type="button" class="button button-danger pnpc-psd-delete-ticket-btn" data-ticket-id="<?php echo absint($ticket->id); ?>">
			<?php esc_html_e('Request Delete', 'pnpc-pocket-service-desk'); ?>
		</button>
	</section>
	<?php endif; ?>
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

			<div id="pnpc-psd-delete-error-message" class="pnpc-psd-error-message" style="display:none;"></div>
		</div>
		<div class="pnpc-psd-modal-footer">
			<button type="button" class="button pnpc-psd-delete-cancel"><?php esc_html_e('Cancel', 'pnpc-pocket-service-desk'); ?></button>
			<button type="button" class="button button-primary pnpc-psd-delete-submit"><?php esc_html_e('Delete Ticket', 'pnpc-pocket-service-desk'); ?></button>
		</div>
	</div>
</div>

<!-- Lightbox Modal for Attachments -->
<div id="pnpc-psd-lightbox" class="pnpc-psd-lightbox" style="display:none;" role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php esc_attr_e('Attachment Viewer', 'pnpc-pocket-service-desk'); ?>">
	<div class="pnpc-psd-lightbox-backdrop"></div>
	<div class="pnpc-psd-lightbox-content">
		<!-- Close Button -->
		<button type="button" class="pnpc-psd-lightbox-close" aria-label="<?php esc_attr_e('Close', 'pnpc-pocket-service-desk'); ?>">×</button>

		<!-- Download Button -->
		<a href="#" download class="pnpc-psd-lightbox-download button">
			<?php esc_html_e('Download', 'pnpc-pocket-service-desk'); ?>
		</a>

		<!-- Image View -->
		<div class="pnpc-psd-lightbox-image-container">
			<img src="" alt="" class="pnpc-psd-lightbox-image">
			<div class="pnpc-psd-lightbox-caption">
				<span class="pnpc-psd-lightbox-filename"></span>
				<span class="pnpc-psd-lightbox-counter"></span>
			</div>
		</div>

		<!-- PDF View -->
		<div class="pnpc-psd-lightbox-pdf-container" style="display:none;">
			<iframe src="" type="application/pdf" class="pnpc-psd-lightbox-pdf" title="<?php esc_attr_e('PDF Viewer', 'pnpc-pocket-service-desk'); ?>"></iframe>
			<div class="pnpc-psd-pdf-fallback" style="display:none;">
				<p><?php esc_html_e('Your browser cannot display this PDF.', 'pnpc-pocket-service-desk'); ?></p>
				<a href="#" download class="button button-primary">
					<?php esc_html_e('Download PDF', 'pnpc-pocket-service-desk'); ?>
				</a>
			</div>
		</div>

		<!-- Navigation Arrows -->
		<button type="button" class="pnpc-psd-lightbox-prev" aria-label="<?php esc_attr_e('Previous', 'pnpc-pocket-service-desk'); ?>">‹</button>
		<button type="button" class="pnpc-psd-lightbox-next" aria-label="<?php esc_attr_e('Next', 'pnpc-pocket-service-desk'); ?>">›</button>
	</div>
</div>