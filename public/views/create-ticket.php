<?php

/**
 * Public create ticket view (patched to include attachments field and preview/remove UI)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="pnpc-psd-create-ticket">
	<h2><?php esc_html_e('Create New Support Ticket', 'pnpc-pocket-service-desk'); ?></h2>

	<form id="pnpc-psd-create-ticket-form" enctype="multipart/form-data" novalidate>
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
			<div class="pnpc-psd-attachments-list" id="pnpc-psd-attachments-list" style="margin-top:8px;"></div>
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
			<button type="submit" class="pnpc-psd-button pnpc-psd-button-secondary">
				<?php esc_html_e('Create Ticket', 'pnpc-pocket-service-desk'); ?>
			</button>
		</div>

		<div id="ticket-create-message" class="pnpc-psd-create-message pnpc-psd-message"></div>
	</form>
</div>