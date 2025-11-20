<?php

/**
 * Admin ticket detail view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin/views
 */

if (! defined('ABSPATH')) {
	exit;
}

// Defensive defaults to avoid undefined variable notices if controller changes.
if (! isset($responses) || ! is_array($responses)) {
	$responses = array();
}
if (! isset($agents) || ! is_array($agents)) {
	$agents = array();
}

if ( ! isset( $ticket ) || ! $ticket ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid or missing ticket object.', 'pnpc-pocket-service-desk' ) . '</p></div>';
	return;
}

$user = get_userdata($ticket->user_id);

global $wpdb;
$attachments_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';

// Fetch ticket-level attachments from plugin table
$ticket_attachments = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$attachments_table} WHERE ticket_id = %d AND ( response_id IS NULL OR response_id = 0 ) ORDER BY id ASC",
		intval( $ticket->id )
	)
);

// If plugin-table returned nothing, try to find attachments by postmeta we set on upload
if ( empty( $ticket_attachments ) ) {
	$found = get_posts( array(
		'post_type'      => 'attachment',
		'posts_per_page' => -1,
		'meta_key'       => '_pnpc_psd_ticket_id',
		'meta_value'     => intval( $ticket->id ),
	) );

	if ( ! empty( $found ) ) {
		$ticket_attachments = array();
		foreach ( $found as $f ) {
			$file_path = get_attached_file( $f->ID );
			$ticket_attachments[] = (object) array(
				'file_name'     => $f->post_title ? $f->post_title : basename( $file_path ),
				'file_url'      => wp_get_attachment_url( $f->ID ),
				'file_path'     => $file_path,
				'file_size'     => file_exists( $file_path ) ? filesize( $file_path ) : 0,
				'attachment_id' => $f->ID,
			);
		}
	}
}

/**
 * Helper: resolve a usable public URL for an attachment row.
 * Tries, in order:
 *  - file_url property (if present)
 *  - attachment_id property via wp_get_attachment_url()
 *  - find attachment by file_path -> meta '_wp_attached_file'
 *  - fallback to searching attachments.guid by filename
 *
 * @param object $att Attachment row object
 * @return string URL or empty
 */
function pnpc_psd_resolve_attachment_url( $att ) {
	global $wpdb;

	// prefer file_url if stored
	if ( ! empty( $att->file_url ) ) {
		return esc_url_raw( $att->file_url );
	}

	// prefer attachment_id if stored
	if ( ! empty( $att->attachment_id ) ) {
		$attachment_url = wp_get_attachment_url( intval( $att->attachment_id ) );
		if ( $attachment_url ) {
			return esc_url_raw( $attachment_url );
		}
	}

	// try to find by file_path (match _wp_attached_file meta)
	if ( ! empty( $att->file_path ) ) {
		$uploads = wp_get_upload_dir();
		$basedir = isset( $uploads['basedir'] ) ? $uploads['basedir'] : ABSPATH . 'wp-content/uploads';
		// if file_path contains basedir, compute relative path
		if ( strpos( $att->file_path, $basedir ) !== false ) {
			$relative = ltrim( str_replace( $basedir, '', $att->file_path ), '/\' );
			// Search attachments by meta; use LIKE to handle possible different year/month prefixes
			$query = new WP_Query( array(
				'post_type'      => 'attachment',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'     => '_wp_attached_file',
						'value'   => $relative,
						'compare' => 'LIKE',
					),
				),
			) );
			if ( $query->have_posts() ) {
				$a = $query->posts[0];
				$url = wp_get_attachment_url( $a->ID );
				if ( $url ) {
					return esc_url_raw( $url );
				}
			}
		}
	}

	// fallback: try to find attachment by filename match in guid
	if ( ! empty( $att->file_name ) ) {
		$like = '%' . $wpdb->esc_like( $att->file_name ) . '%';
		$found_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid LIKE %s LIMIT 1",
			$like
		) );
		if ( $found_id ) {
			$url = wp_get_attachment_url( $found_id );
			if ( $url ) {
				return esc_url_raw( $url );
			}
		}
	}

	return '';
}
?>

<div class="wrap">
	<h1>
		<?php
		/* translators: %s: ticket number */
		printf(esc_html__('Ticket: %s', 'pnpc-pocket-service-desk'), esc_html($ticket->ticket_number));
		?>
	</h1>

	<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk')); ?>" class="button">
		&larr; <?php esc_html_e('Back to All Tickets', 'pnpc-pocket-service-desk'); ?>
	</a>

	<div class="pnpc-psd-ticket-detail">
		<div class="pnpc-psd-ticket-header">
			<div class="pnpc-psd-ticket-meta">
				<h2><?php echo esc_html($ticket->subject); ?></h2>
				<p>
					<strong><?php esc_html_e('Customer:', 'pnpc-pocket_service_desk'); ?></strong>
					<?php echo $user ? esc_html($user->display_name . ' (' . $user->user_email . ')') : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?>
				</p>
				<p>
					<strong><?php esc_html_e('Created:', 'pnpc-pocket_service_desk'); ?></strong>
					<?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at))); ?>
				</p>
				<p>
					<strong><?php esc_html_e('Last Updated:', 'pnpc-pocket_service_desk'); ?></strong>
					<?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->updated_at))); ?>
				</p>

				<?php if ( ! empty( $ticket_attachments ) ) : ?>
					<div class="pnpc-psd-ticket-attachments" style="margin-top:8px;">
						<strong><?php esc_html_e( 'Attachments:', 'pnpc-pocket_service_desk' ); ?></strong>
						<ul style="margin:6px 0 0 18px;padding:0;">
							<?php foreach ( $ticket_attachments as $att ) :
								$file_name = ! empty( $att->file_name ) ? $att->file_name : basename( $att->file_path );
								$file_url  = pnpc_psd_resolve_attachment_url( $att );
								$file_path = ! empty( $att->file_path ) ? $att->file_path : '';?
								<li style="margin-bottom:6px;">
								<strong><?php echo esc_html( $file_name ); ?></strong>
								<?php if ( $file_url ) : ?>
									— <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open', 'pnpc-pocket_service_desk' ); ?></a>
									| <a href="<?php echo esc_url( $file_url ); ?>" download><?php esc_html_e( 'Download', 'pnpc-pocket_service_desk' ); ?></a>
								<?php elseif ( $file_path && file_exists( $file_path ) ) :
									$possible_url = str_replace( ABSPATH, site_url( '/' ), $file_path );
									?>
								— <a href="<?php echo esc_url( $possible_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open', 'pnpc-pocket_service_desk' ); ?></a>
								| <a href="<?php echo esc_url( $possible_url ); ?>" download><?php esc_html_e( 'Download', 'pnpc-pocket_service_desk' ); ?></a>
								<?php else : ?>
								— <span class="description"><?php esc_html_e( 'File unavailable', 'pnpc-pocket_service_desk' ); ?></span>
								<?php endif; ?>
								<?php if ( isset( $att->file_size ) ) : ?>
									<span class="description"> — <?php echo esc_html( size_format( intval( $att->file_size ) ) ); ?></span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>

				<div class="pnpc-psd-ticket-actions">
					<div class="pnpc-psd-field">
						<label for="ticket-status"><?php esc_html_e('Status:', 'pnpc-pocket_service_desk'); ?></label>
						<?php if (current_user_can('pnpc_psd_assign_tickets')) : ?>
							<select id="ticket-status" data-ticket-id="<?php echo esc_attr($ticket->id); ?>">
								<option value="open" <?php selected($ticket->status, 'open'); ?>><?php esc_html_e('Open', 'pnpc-pocket_service_desk'); ?></option>
								<option value="in-progress" <?php selected($ticket->status, 'in-progress'); ?>><?php esc_html_e('In Progress', 'pnpc-pocket_service_desk'); ?></option>
								<option value="waiting" <?php selected($ticket->status, 'waiting'); ?>><?php esc_html_e('Waiting on Customer', 'pnpc-pocket_service_desk'); ?></option>
								<option value="closed" <?php selected($ticket->status, 'closed'); ?>><?php esc_html_e('Closed', 'pnpc-pocket_service_desk'); ?></option>
							</select>
						<?php else : ?>
							<span class="pnpc-psd-ticket-status-readonly"><?php echo esc_html(ucfirst($ticket->status)); ?></span>
						<?php endif; ?>
					</div>

					<div class="pnpc-psd-field">
						<label for="ticket-priority"><?php esc_html_e('Priority:', 'pnpc-pocket_service_desk'); ?></label>
						<span class="pnpc-psd-priority pnpc-psd-priority-<?php echo esc_attr($ticket->priority); ?>"><?php echo esc_html(ucfirst($ticket->priority)); ?></span>
					</div>

					<div class="pnpc-psd-field">
						<label for="ticket-assign"><?php esc_html_e('Assign To:', 'pnpc-pocket_service_desk'); ?></label>
						<?php if (current_user_can('pnpc_psd_assign_tickets')) : ?>
							<select id="ticket-assign" data-ticket-id="<?php echo esc_attr($ticket->id); ?>">
								<option value="0"><?php esc_html_e('Unassigned', 'pnpc-pocket_service_desk'); ?></option>
								<?php foreach ($agents as $agent) : ?>
									<option value="<?php echo esc_attr($agent->ID); ?>" <?php selected($ticket->assigned_to, $agent->ID); ?>><?php echo esc_html($agent->display_name); ?></option>
								<?php endforeach; ?>
							</select>
						<?php else : ?>
							<?php
							if (! empty($ticket->assigned_to)) {
								$assigned = get_user_by('ID', $ticket->assigned_to);
								echo '<span class="pnpc-psd-assigned-readonly">' . esc_html($assigned ? $assigned->display_name : esc_html__('Assigned', 'pnpc-pocket_service_desk')) . '</span>';
							} else {
								echo '<span class="pnpc-psd-assigned-readonly">' . esc_html__('Unassigned', 'pnpc-pocket_service_desk') . '</span>';
							}
							?>
						<?php endif; ?>
						</div>
					</div>
			</div>

			<div class="pnpc-psd-ticket-description">
				<h3><?php esc_html_e('Description', 'pnpc-pocket_service_desk'); ?></h3>
				<div class="pnpc-psd-ticket-content">
					<?php echo wp_kses_post($ticket->description); ?>
				</div>
			</div>

			<div class="pnpc-psd-ticket-responses">
				<h3><?php esc_html_e('Responses', 'pnpc-pocket_service_desk'); ?></h3>

				<?php if (! empty($responses)) : ?>
					<?php foreach ($responses as $response) : ?>
						<?php
						$response_user = get_userdata($response->user_id);

						// Fetch attachments for this response from plugin table
						$resp_attachments = $wpdb->get_results(
							$wpdb->prepare( "SELECT * FROM {$attachments_table} WHERE response_id = %d ORDER BY id ASC", intval( $response->id ) )
						);

						// Fallback: if no plugin-table rows found, try attachments by postmeta we set on upload
						if ( empty( $resp_attachments ) ) {
							$found = get_posts( array(
								'post_type'      => 'attachment',
								'posts_per_page' => -1,
								'meta_key'       => '_pnpc_psd_response_id',
								'meta_value'     => intval( $response->id ),
							) );

							if ( ! empty( $found ) ) {
								$resp_attachments = array();
								foreach ( $found as $f ) {
									$file_path = get_attached_file( $f->ID );
									$resp_attachments[] = (object) array(
										'file_name'     => $f->post_title ? $f->post_title : basename( $file_path ),
										'file_url'      => wp_get_attachment_url( $f->ID ),
										'file_path'     => $file_path,
										'file_size'     => file_exists( $file_path ) ? filesize( $file_path ) : 0,
										'attachment_id' => $f->ID,
									);
								}
							}
						}
						?>
						<div class="pnpc-psd-response <?php echo $response->is_staff_response ? 'pnpc-psd-response-staff' : 'pnpc-psd-response-customer'; ?>">
							<div class="pnpc-psd-response-header">
								<strong><?php echo $response_user ? esc_html($response_user->display_name) : esc_html__('Unknown', 'pnpc-pocket_service_desk'); ?></strong>
								<span class="pnpc-psd-response-type">
								<?php echo $response->is_staff_response ? esc_html__('(Staff)', 'pnpc-pocket_service_desk') : esc_html__('(Customer)', 'pnpc-pocket_service_desk'); ?>
								</span>
								<span class="pnpc-psd-response-date">
								<?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($response->created_at))); ?>
								</span>
							</div>
							<div class="pnpc-psd-response-content">
								<?php echo wp_kses_post($response->response); ?>
							</div>

							<?php if ( ! empty( $resp_attachments ) ) : ?>
								<div class="pnpc-psd-response-attachments" style="margin-top:8px;">
									<strong><?php esc_html_e( 'Attachments:', 'pnpc-pocket_service_desk' ); ?></strong>
									<ul style="margin:6px 0 0 18px;padding:0;">
									<?php foreach ( $resp_attachments as $att ) :
									$file_name = ! empty( $att->file_name ) ? $att->file_name : basename( $att->file_path );
									$file_url  = pnpc_psd_resolve_attachment_url( $att );
									$file_path = ! empty( $att->file_path ) ? $att->file_path : '';?
									<li style="margin-bottom:6px;">
									<strong><?php echo esc_html( $file_name ); ?></strong>
									<?php if ( $file_url ) : ?>
										— <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open', 'pnpc-pocket_service_desk' ); ?></a>
										| <a href="<?php echo esc_url( $file_url ); ?>" download><?php esc_html_e( 'Download', 'pnpc-pocket_service_desk' ); ?></a>
									<?php elseif ( $file_path && file_exists( $file_path ) ) :
										$possible_url = str_replace( ABSPATH, site_url( '/' ), $file_path );
										?>
									— <a href="<?php echo esc_url( $possible_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open', 'pnpc-pocket_service_desk' ); ?></a>
										| <a href="<?php echo esc_url( $possible_url ); ?>" download><?php esc_html_e( 'Download', 'pnpc-pocket_service_desk' ); ?></a>
									<?php else : ?>
									— <span class="description"><?php esc_html_e( 'File unavailable', 'pnpc-pocket_service_desk' ); ?></span>
									<?php endif; ?>
									<?php if ( isset( $att->file_size ) ) : ?>
										<span class="description"> — <?php echo esc_html( size_format( intval( $att->file_size ) ) ); ?></span>
									<?php endif; ?>
								</li>
								<?php endforeach; ?>
								</ul>
							</div>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p><?php esc_html_e('No responses yet.', 'pnpc-pocket_service_desk'); ?></p>
					<?php endif; ?>
				</div>

				<div class="pnpc-psd-add-response">
					<h3><?php esc_html_e('Add Response', 'pnpc-pocket_service_desk'); ?></h3>
					<form id="pnpc-psd-response-form" data-ticket-id="<?php echo esc_attr($ticket->id); ?>">
						<textarea id="response-text" name="response" rows="6" placeholder="<?php esc_attr_e('Enter your response...', 'pnpc-pocket_service_desk'); ?>" required></textarea>
						<button type="submit" class="button button-primary"><?php esc_html_e('Add Response', 'pnpc-pocket_service_desk'); ?></button>
					</form>
					<div id="response-message"></div>
				</div>

				<?php if (current_user_can('pnpc_psd_delete_tickets')) : ?>
					<div class="pnpc-psd-danger-zone">
						<h3><?php esc_html_e('Danger Zone', 'pnpc-pocket_service_desk'); ?></h3>
						<button id="delete-ticket" class="button button-danger" data-ticket-id="<?php echo esc_attr($ticket->id); ?>">
							<?php esc_html_e('Delete Ticket', 'pnpc-pocket_service_desk'); ?>
						</button>
					</div>
				<?php endif; ?>
			</div>

	</div>

</div>