<?php

/**
 * Admin ticket detail view (minimal, includes attachments list and response form with attachments)
 *
 * Place at admin/views/ticket-detail.php
 *
 * Expects $ticket, $responses, $agents variables populated by the controller.
 */

if (! defined('ABSPATH')) {
	exit;
}

global $wpdb;
$att_table = $wpdb->prefix . 'pnpc_psd_ticket_attachments';

$ticket_attachments = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$att_table} WHERE ticket_id = %d AND (response_id IS NULL OR response_id = '') ORDER BY id ASC", $ticket->id));
$response_attachments_map = array();
$all_response_atts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$att_table} WHERE ticket_id = %d AND response_id IS NOT NULL ORDER BY id ASC", $ticket->id));
if ($all_response_atts) {
	foreach ($all_response_atts as $ra) {
		$response_attachments_map[intval($ra->response_id)][] = $ra;
	}
}
?>

<div class="wrap pnpc-psd-ticket-detail">
	<h1><?php echo esc_html($ticket->subject); ?> <small>#<?php echo esc_html($ticket->ticket_number); ?></small></h1>

	<div class="pnpc-psd-ticket-meta">
		<p><?php esc_html_e('Status:', 'pnpc-pocket-service-desk'); ?> <?php echo esc_html(ucfirst($ticket->status)); ?></p>
		<p><?php esc_html_e('Created:', 'pnpc-pocket-service-desk'); ?>
			<?php echo esc_html(function_exists('pnpc_psd_format_db_datetime_for_display') ? pnpc_psd_format_db_datetime_for_display($ticket->created_at) : date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket->created_at))); ?>
		</p>
	</div>

	<?php if (! empty($ticket_attachments)) : ?>
		<h3><?php esc_html_e('Attachments', 'pnpc-pocket-service-desk'); ?></h3>
		<ul>
			<?php foreach ($ticket_attachments as $att) : ?>
				<li>
					<a href="<?php echo esc_url($att->file_path); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($att->file_name); ?></a>
					<span style="color:#777;margin-left:8px;"><?php echo esc_html(pnpc_psd_format_filesize($att->file_size)); ?></span>
					<span style="color:#777;margin-left:8px;"><?php echo esc_html(function_exists('pnpc_psd_format_db_datetime_for_display') ? pnpc_psd_format_db_datetime_for_display($att->created_at) : $att->created_at); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<h3><?php esc_html_e('Conversation', 'pnpc-pocket-service-desk'); ?></h3>

	<?php if (! empty($responses)) : ?>
		<?php foreach ($responses as $r) : ?>
			<?php
			$responder = get_userdata($r->user_id);
			$is_staff = intval($r->is_staff_response) === 1;
			$atts_for_response = isset($response_attachments_map[intval($r->id)]) ? $response_attachments_map[intval($r->id)] : array();
			?>
			<div class="pnpc-psd-response <?php echo $is_staff ? 'pnpc-psd-response-staff' : 'pnpc-psd-response-customer'; ?>">
				<div class="pnpc-psd-response-header">
					<strong><?php echo $responder ? esc_html($responder->display_name) : esc_html__('Unknown', 'pnpc-pocket-service-desk'); ?></strong>
					<span class="pnpc-psd-response-date">
						<?php echo esc_html(function_exists('pnpc_psd_format_db_datetime_for_display') ? pnpc_psd_format_db_datetime_for_display($r->created_at) : date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($r->created_at))); ?>
					</span>
				</div>
				<div class="pnpc-psd-response-content">
					<?php echo wp_kses_post($r->response); ?>
				</div>
				<?php if (! empty($atts_for_response)) : ?>
					<div class="pnpc-psd-response-attachments">
						<strong><?php esc_html_e('Attachments:', 'pnpc-pocket-service-desk'); ?></strong>
						<ul>
							<?php foreach ($atts_for_response as $ra) : ?>
								<li><a href="<?php echo esc_url($ra->file_path); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($ra->file_name); ?></a> <small>(<?php echo esc_html(pnpc_psd_format_filesize($ra->file_size)); ?>)</small></li>
							<?php endforeach; ?>
						</ul>
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
				<div style="margin-top:8px;">
					<label for="admin-response-attachments"><?php esc_html_e('Attachments (optional)', 'pnpc-pocket-service-desk'); ?></label>
					<input type="file" id="admin-response-attachments" name="attachments[]" multiple />
				</div>
				<div style="margin-top:8px;">
					<button type="submit" class="button button-primary"><?php esc_html_e('Add Response', 'pnpc-pocket-service-desk'); ?></button>
				</div>
				<div id="response-message" class="pnpc-psd-message"></div>
			</form>
		</div>
	<?php endif; ?>
</div>