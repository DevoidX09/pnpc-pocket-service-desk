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

// Fetch user data once for reuse in multiple sections (creator info, requestor info).
$ticket_user = $ticket->user_id ? get_userdata($ticket->user_id) : null;
$ticket_user_name = $ticket_user ? esc_html($ticket_user->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk');
$ticket_user_edit_link = $ticket_user ? get_edit_user_link($ticket_user->ID) : '';
?>

<div class="wrap pnpc-psd-ticket-detail" id="pnpc-psd-ticket-detail" data-ticket-id="<?php echo esc_attr($ticket->id); ?>">
	<div class="pnpc-psd-ticket-detail-header">
		<div class="pnpc-psd-breadcrumb">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-tickets' ) ); ?>">
				<?php esc_html_e('All Tickets', 'pnpc-pocket-service-desk'); ?>
			</a>
			<span class="separator"> &raquo; </span>
			<span class="current"><?php echo esc_html($ticket->ticket_number); ?></span>
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
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-tickets&status=open' ) ); ?>" class="button">
				<?php esc_html_e('Open Tickets', 'pnpc-pocket-service-desk'); ?>
			</a>
		</div>
	</div>

	<?php
	// Check if staff-created
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
	<div class="pnpc-psd-ticket-header">
		<div>
			<h1><?php echo esc_html($ticket->subject); ?> <small>#<?php echo esc_html($ticket->ticket_number); ?></small></h1>
			<div class="pnpc-psd-ticket-meta">
				<p>
					<?php esc_html_e('Status:', 'pnpc-pocket-service-desk'); ?>
					<?php
					$ticket_status_label = isset( $status_options[ $ticket_status_key ] )
						? $status_options[ $ticket_status_key ]
						: ucwords( str_replace( '-', ' ', $ticket_status_key ) );
					?>
					<span class="pnpc-psd-status pnpc-psd-status-<?php echo esc_attr( $ticket_status_key ); ?>"><?php echo esc_html( $ticket_status_label ); ?></span>
				</p>
				<p>
					<?php esc_html_e( 'Priority:', 'pnpc-pocket-service-desk' ); ?>
					<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr( (string) $ticket->priority ); ?>">
						<?php echo esc_html( ucfirst( (string) $ticket->priority ) ); ?>
					</span>
				</p>
				<p>
					<?php esc_html_e('Assigned to:', 'pnpc-pocket-service-desk'); ?>
					<?php
					$assigned_user = $ticket->assigned_to ? get_userdata($ticket->assigned_to) : null;
					echo $assigned_user ? esc_html($assigned_user->display_name) : esc_html__('Unassigned', 'pnpc-pocket-service-desk');
					?>
				</p>
				<p><?php esc_html_e('Created:', 'pnpc-pocket-service-desk'); ?>
					<?php echo esc_html($ticket_created_display); ?>
				</p>
				<p>
					<?php esc_html_e('Created by:', 'pnpc-pocket-service-desk'); ?>
					<?php
					if ($ticket_user_edit_link) {
						echo '<a href="' . esc_url($ticket_user_edit_link) . '">' . esc_html( $ticket_user_name ) . '</a>';
					} else {
						echo esc_html( $ticket_user_name );
					}
					
					// Display user_url if present
					if ($ticket_user && !empty($ticket_user->user_url)) {
						echo ' | <a href="' . esc_url($ticket_user->user_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Website', 'pnpc-pocket-service-desk') . '</a>';
					}
					?>
				</p>
			</div>
			<div id="pnpc-psd-client-notes-anchor"></div>
		</div>

		<div class="pnpc-psd-ticket-actions">
			<div class="pnpc-psd-autosave-tip-wrap">
				<div class="pnpc-psd-autosave-tip-container">
				<a href="#" id="pnpc-psd-autosave-tip" class="pnpc-psd-autosave-tip" aria-expanded="false" aria-controls="pnpc-psd-autosave-tip-panel"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span> <?php echo esc_html__( 'Auto-save help', 'pnpc-pocket-service-desk' ); ?></a>
<div id="pnpc-psd-autosave-tip-panel" class="pnpc-psd-autosave-tip-panel" style="display:none;"><?php echo esc_html__( 'Agent, Status, and Priority save automatically when you change them. If anything fails, use the Save buttons.', 'pnpc-pocket-service-desk' ); ?></div>
				</div>
			</div>
			<div id="pnpc-psd-client-notes-controls-anchor"></div>
			<?php if (current_user_can('pnpc_psd_assign_tickets')) : ?>
				<div class="pnpc-psd-field">
					<label for="pnpc-psd-assign-agent"><?php esc_html_e('Assign Agent', 'pnpc-pocket-service-desk'); ?></label>
					<select id="pnpc-psd-assign-agent" name="assigned_to">
						<option value="0"><?php esc_html_e('Unassigned', 'pnpc-pocket-service-desk'); ?></option>
						<?php foreach ($agents as $agent) : ?>
							<option value="<?php echo esc_attr($agent->ID); ?>" <?php selected((int) $ticket->assigned_to, (int) $agent->ID); ?>>
								<?php echo esc_html($agent->display_name); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button" id="pnpc-psd-assign-button"><?php esc_html_e('Assign', 'pnpc-pocket-service-desk'); ?></button>
				</div>
			<?php endif; ?>

			<?php if (current_user_can('pnpc_psd_respond_to_tickets')) : ?>
				<div class="pnpc-psd-field">
					<label for="pnpc-psd-status-select"><?php esc_html_e('Ticket Status', 'pnpc-pocket-service-desk'); ?></label>
					<select id="pnpc-psd-status-select" name="status">
						<?php foreach ($status_options as $key => $label) : ?>
							<option value="<?php echo esc_attr($key); ?>" <?php selected($ticket_status_key, $key); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button" id="pnpc-psd-status-button"><?php esc_html_e('Update Status', 'pnpc-pocket-service-desk'); ?></button>
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
				<form class="pnpc-psd-field" id="pnpc-psd-priority-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="pnpc-service-desk-ticket" />
					<input type="hidden" name="ticket_id" value="<?php echo esc_attr( absint( $ticket->id ) ); ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
					<?php wp_nonce_field( 'pnpc_psd_update_ticket_priority', 'pnpc_psd_update_priority_nonce' ); ?>
					<label for="pnpc-psd-priority-select"><?php esc_html_e( 'Priority', 'pnpc-pocket-service-desk' ); ?></label>
					<select id="pnpc-psd-priority-select" name="priority">
						<?php foreach ( $priority_opts as $p_key => $p_label ) : ?>
							<option value="<?php echo esc_attr( $p_key ); ?>" <?php selected( (string) $ticket->priority, (string) $p_key ); ?>>
								<?php echo esc_html( $p_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="button" id="pnpc-psd-priority-button"><?php esc_html_e( 'Update Priority', 'pnpc-pocket-service-desk' ); ?></button>
				</form>
			<?php endif; ?>
		</div>
	</div>

	
	<div id="pnpc-psd-admin-action-message" class="pnpc-psd-message" style="display:none;"></div>

	<div class="pnpc-psd-ticket-body" style="margin: 15px 0; padding: 12px 14px; background: #FAFFA1; border: 1px solid #dcdcde; border-radius: 4px;">
		<h3 style="margin-top:0;"><?php esc_html_e('Ticket', 'pnpc-pocket-service-desk'); ?></h3>
		<p style="margin: 0 0 10px 0; font-size: 0.9em; color: #555;">
			<?php esc_html_e('Requestor:', 'pnpc-pocket-service-desk'); ?>
			<?php
			if ($ticket_user_edit_link) {
				echo '<a href="' . esc_url($ticket_user_edit_link) . '">' . esc_html( $ticket_user_name ) . '</a>';
			} else {
				echo esc_html( $ticket_user_name );
			}
			?>
		</p>
		<div class="pnpc-psd-ticket-content">
			<?php
			$ticket_body = isset($ticket->description) ? $ticket->description : '';
			echo wp_kses_post( wpautop( $ticket_body ) );
			?>
		</div>
	</div>


	<?php if (! empty($ticket_attachments)) : ?>
		<div class="pnpc-psd-attachments">
			<h3><?php esc_html_e('Attachments', 'pnpc-pocket-service-desk'); ?> (<?php echo count($ticket_attachments); ?>)</h3>
			
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
						<img src="<?php echo esc_url( $file_url ); ?>" 
							 alt="<?php echo esc_attr( $file_name ); ?>" 
							 class="pnpc-psd-attachment-thumbnail">
					<?php else : ?>
						<div class="pnpc-psd-attachment-icon">
							<?php echo esc_html( pnpc_psd_get_file_icon($file_ext) ); ?>
						</div>
					<?php endif; ?>
					
					<div class="pnpc-psd-attachment-info">
						<strong><?php echo esc_html( $file_name ); ?></strong>
						<span class="pnpc-psd-attachment-meta">
							<?php echo esc_html($file_size_formatted); ?> · <?php echo esc_html(strtoupper($file_ext)); ?>
						</span>
						
						<?php if (! $can_preview) : ?>
							<span class="pnpc-psd-attachment-warning">
								⚠ <?php
								/* translators: %s: file size limit */
								printf(
									esc_html__('Exceeds %s preview limit', 'pnpc-pocket-service-desk'),
									esc_html(pnpc_psd_format_filesize(PNPC_PSD_FREE_PREVIEW_LIMIT))
								);
								?>
							</span>
						<?php endif; ?>
					</div>
					
					<div class="pnpc-psd-attachment-actions">
						<?php if ($can_preview && in_array($file_type, array('image', 'pdf'), true)) : ?>
							<button type="button" class="pnpc-psd-view-attachment button" 
									data-type="<?php echo esc_attr($file_type); ?>" 
									data-url="<?php echo esc_url( $file_url ); ?>"
									data-filename="<?php echo esc_attr( $file_name ); ?>">
								<?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?>
							</button>
						<?php endif; ?>
						
						<a href="<?php echo esc_url( $download_url ); ?>" 
						   download 
						   class="button <?php echo ! $can_preview ? 'button-primary' : ''; ?>">
							<?php esc_html_e('Download', 'pnpc-pocket-service-desk'); ?>
						</a>
					</div>
				</div>
				
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 *
	 * @since 1.1.1.4
	 */
	do_action( 'pnpc_psd_admin_ticket_detail_before_conversation', $ticket );
	?>
	<div id="pnpc-psd-internal-collab-anchor"></div>

	<h3><?php esc_html_e('Conversation', 'pnpc-pocket-service-desk'); ?></h3>

	<?php if (! empty($responses)) : ?>
		<?php foreach ($responses as $r) : ?>
			<?php
			$responder = get_userdata($r->user_id);
			$is_staff = intval($r->is_staff_response) === 1;
			$atts_for_response = isset($response_attachments_map[intval($r->id)]) ? $response_attachments_map[intval($r->id)] : array();
			?>
			<div class="pnpc-psd-response <?php echo esc_attr( $is_staff ? 'pnpc-psd-response-staff' : 'pnpc-psd-response-customer' ); ?>">
				<div class="pnpc-psd-response-header">
					<strong><?php echo $responder ? esc_html($responder->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?></strong>
					<span class="pnpc-psd-response-date">
						<?php echo esc_html(pnpc_psd_admin_format_datetime($r->created_at)); ?>
					</span>
				</div>
				<div class="pnpc-psd-response-content">
					<?php echo wp_kses_post( wpautop( $r->response ) ); ?>
				</div>
				<?php if (! empty($atts_for_response)) : ?>
					<div class="pnpc-psd-response-attachments">
						<strong><?php esc_html_e('Attachments:', 'pnpc-pocket-service-desk'); ?></strong>
						
						<?php foreach ($atts_for_response as $ra) : ?>
							<?php
							$file_size = intval($ra->file_size);
							$file_url = esc_url( pnpc_psd_get_attachment_download_url( $ra->id, $ticket->id, true ) );
							$download_url = esc_url( pnpc_psd_get_attachment_download_url( $ra->id, $ticket->id, false ) );
							$file_name = esc_html($ra->file_name);
							$file_ext = strtolower(pathinfo($ra->file_name, PATHINFO_EXTENSION));
							$file_type = pnpc_psd_get_attachment_type($file_ext);
							$can_preview = pnpc_psd_can_preview_attachment($file_size);
							$file_size_formatted = pnpc_psd_format_filesize($file_size);
							?>
							
							<div class="pnpc-psd-attachment pnpc-psd-attachment-<?php echo esc_attr($file_type); ?>">
								<?php if ($file_type === 'image' && $can_preview) : ?>
									<img src="<?php echo esc_url( $file_url ); ?>" 
										 alt="<?php echo esc_attr( $file_name ); ?>" 
										 class="pnpc-psd-attachment-thumbnail">
								<?php else : ?>
									<div class="pnpc-psd-attachment-icon">
										<?php echo esc_html( pnpc_psd_get_file_icon($file_ext) ); ?>
									</div>
								<?php endif; ?>
								
								<div class="pnpc-psd-attachment-info">
									<strong><?php echo esc_html( $file_name ); ?></strong>
									<span class="pnpc-psd-attachment-meta">
										<?php echo esc_html($file_size_formatted); ?> · <?php echo esc_html(strtoupper($file_ext)); ?>
									</span>
									
									<?php if (! $can_preview) : ?>
										<span class="pnpc-psd-attachment-warning">
											⚠ <?php
											/* translators: %s: file size limit */
											printf(
												esc_html__('Exceeds %s preview limit', 'pnpc-pocket-service-desk'),
												esc_html(pnpc_psd_format_filesize(PNPC_PSD_FREE_PREVIEW_LIMIT))
											);
											?>
										</span>
									<?php endif; ?>
								</div>
								
								<div class="pnpc-psd-attachment-actions">
									<?php if ($can_preview && in_array($file_type, array('image', 'pdf'), true)) : ?>
										<button type="button" class="pnpc-psd-view-attachment button" 
												data-type="<?php echo esc_attr($file_type); ?>" 
												data-url="<?php echo esc_url( $file_url ); ?>"
												data-filename="<?php echo esc_attr( $file_name ); ?>">
											<?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?>
										</button>
									<?php endif; ?>
									
									<a href="<?php echo esc_url( $download_url ); ?>" 
									   download 
									   class="button <?php echo ! $can_preview ? 'button-primary' : ''; ?>">
										<?php esc_html_e('Download', 'pnpc-pocket-service-desk'); ?>
									</a>
								</div>
							</div>
							
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	<?php else : ?>
		<p><?php esc_html_e('No responses yet.', 'pnpc-pocket-service-desk'); ?></p>
	<?php endif; ?>

	<?php if ('closed' !== $ticket->status && current_user_can('pnpc_psd_respond_to_tickets')) : ?>
		<div class="pnpc-psd-add-response">
			<h3><?php esc_html_e('Add Response', 'pnpc-pocket-service-desk'); ?></h3>
			<form id="pnpc-psd-response-form-admin" enctype="multipart/form-data" data-ticket-id="<?php echo esc_attr($ticket->id); ?>">
				<?php wp_nonce_field('pnpc_psd_admin_nonce', 'nonce'); ?>
				<div>
					<textarea id="response-text" name="response" rows="6" style="width:100%;"></textarea>
				</div>
				<div id="pnpc-psd-signature-controls" class="pnpc-psd-signature-controls" style="margin-top:8px;">
					<strong><?php esc_html_e( 'Signature:', 'pnpc-pocket-service-desk' ); ?></strong>
					<label style="margin-left:10px;">
						<input type="radio" name="pnpc_psd_signature_mode" value="none" />
						<?php esc_html_e( 'None', 'pnpc-pocket-service-desk' ); ?>
					</label>
					<label style="margin-left:10px;">
						<input type="radio" name="pnpc_psd_signature_mode" value="personal" />
						<?php esc_html_e( 'Personal', 'pnpc-pocket-service-desk' ); ?>
					</label>
					<label style="margin-left:10px;">
						<input type="radio" name="pnpc_psd_signature_mode" value="group" />
						<?php esc_html_e( 'Group', 'pnpc-pocket-service-desk' ); ?>
					</label>
				</div>
				<div style="margin-top:8px;">
					<label for="admin-response-attachments"><?php esc_html_e('Attachments (optional)', 'pnpc-pocket-service-desk'); ?></label>
					<input type="file" id="admin-response-attachments" name="attachments[]" multiple />
					<div class="pnpc-psd-attachments-list" id="pnpc-psd-admin-response-attachments-list" style="margin-top:8px;"></div>
				</div>
				<div style="margin-top:8px;">
					<button type="submit" class="button button-primary"><?php esc_html_e('Add Response', 'pnpc-pocket-service-desk'); ?></button>
				</div>
				<div id="response-message" class="pnpc-psd-message"></div>
			</form>
		</div>
	<?php endif; ?>
</div>

<?php
// Agents can request deletion into the Review queue; only Managers/Admins can approve to Trash.
if ( current_user_can( 'pnpc_psd_view_tickets' ) ) :
?>
<div class="pnpc-psd-danger-zone">
	<h3><?php esc_html_e('Danger Zone', 'pnpc-pocket-service-desk'); ?></h3>
	<p><?php esc_html_e('Submitting a delete request sends this ticket to the Review queue for approval by a manager or administrator.', 'pnpc-pocket-service-desk'); ?></p>
	
	<button type="button" class="button button-danger pnpc-psd-delete-ticket-btn" data-ticket-id="<?php echo absint($ticket->id); ?>">
		<?php esc_html_e('Request Delete', 'pnpc-pocket-service-desk'); ?>
	</button>
</div>
<?php endif; ?>

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