<?php

/**
 * Public create ticket view (patched to include attachments field and preview/remove UI)
 */
if (! defined('ABSPATH')) {
	exit;
}
?>

<div class="pnpc-psd-create-ticket">
	<h2><?php esc_html_e('Create New Support Ticket', 'pnpc-pocket-service-desk'); ?></h2>

	<form id="pnpc-psd-create-ticket-form" enctype="multipart/form-data">
		<div class="pnpc-psd-form-group">
			<label for="ticket-subject"><?php esc_html_e('Subject', 'pnpc-pocket-service-desk'); ?> <span class="required">*</span></label>
			<input type="text" id="ticket-subject" name="subject" required placeholder="<?php esc_attr_e('Brief description of your issue', 'pnpc-pocket-service-desk'); ?>" />
		</div>

		<div class="pnpc-psd-form-group">
			<label for="ticket-priority"><?php esc_html_e('Priority', 'pnpc-pocket-service-desk'); ?></label>
			<select id="ticket-priority" name="priority">
				<option value="low"><?php esc_html_e('Low', 'pnpc-pocket-service-desk'); ?></option>
				<option value="normal" selected><?php esc_html_e('Normal', 'pnpc-pocket-service-desk'); ?></option>
				<option value="high"><?php esc_html_e('High', 'pnpc-pocket-service-desk'); ?></option>
				<option value="urgent"><?php esc_html_e('Urgent', 'pnpc-pocket-service-desk'); ?></option>
			</select>
		</div>

		<div class="pnpc-psd-form-group">
			<label for="ticket-description"><?php esc_html_e('Description', 'pnpc-pocket-service-desk'); ?> <span class="required">*</span></label>
			<textarea id="ticket-description" name="description" rows="8" required placeholder="<?php esc_attr_e('Please describe your issue in detail...', 'pnpc-pocket-service-desk'); ?>"></textarea>
		</div>

		<div class="pnpc-psd-form-group">
			<label for="ticket-attachments"><?php esc_html_e('Attachments (optional)', 'pnpc-pocket-service-desk'); ?></label>
			<input type="file" id="ticket-attachments" name="attachments[]" multiple />
			<div id="pnpc-psd-attachments-list" style="margin-top:8px;"></div>
			<p class="pnpc-psd-help-text"><?php esc_html_e('Allowed file types depend on site settings. Max size per file constrained by server settings.', 'pnpc-pocket-service-desk'); ?></p>
		</div>

		<div class="pnpc-psd-form-group">
			<button type="submit" class="pnpc-psd-button pnpc-psd-button-secondary">
				<?php esc_html_e('Create Ticket', 'pnpc-pocket-service-desk'); ?>
			</button>
		</div>

		<div id="ticket-create-message" class="pnpc-psd-message"></div>
	</form>
</div>