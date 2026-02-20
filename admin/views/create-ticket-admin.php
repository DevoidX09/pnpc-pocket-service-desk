<?php

/**
 * Admin create ticket view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Display any messages
settings_errors('pnpc_psd_messages');

// Get all customers
// Note: Limited to 500 for performance. For larger installations, consider implementing
// AJAX-based search using Select2's remote data feature.
$customers = get_users(array(
	'role__in' => array('customer', 'subscriber'),
	'orderby' => 'display_name',
	'order' => 'ASC',
	'number' => 500,
));
?>

<div class="wrap">
	<h1><?php esc_html_e('Create Ticket for Customer', 'pnpc-pocket-service-desk'); ?></h1>

	<form id="pnpc-psd-admin-create-ticket-form" method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field('pnpc_psd_create_ticket_admin', 'pnpc_psd_create_ticket_nonce'); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="customer_id"><?php esc_html_e('Customer', 'pnpc-pocket-service-desk'); ?> <span class="required">*</span></label>
				</th>
				<td>
					<select id="customer_id" name="customer_id" required style="width: 400px;">
						<option value=""><?php esc_html_e('Select a customer...', 'pnpc-pocket-service-desk'); ?></option>
						<?php
						foreach ($customers as $customer) {
							printf(
								'<option value="%d">%s (%s)</option>',
								absint($customer->ID),
								esc_html($customer->display_name),
								esc_html($customer->user_email)
							);
						}
						?>
					</select>
					<p class="description"><?php esc_html_e('Select the customer this ticket is for.', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="subject"><?php esc_html_e('Subject', 'pnpc-pocket-service-desk'); ?> <span class="required">*</span></label>
				</th>
				<td>
					<input type="text" id="subject" name="subject" class="regular-text" required />
					<p class="description"><?php esc_html_e('Brief description of the issue.', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="priority"><?php esc_html_e('Priority', 'pnpc-pocket-service-desk'); ?></label>
				</th>
				<td>
					<select id="priority" name="priority">
						<option value="low"><?php esc_html_e('Low', 'pnpc-pocket-service-desk'); ?></option>
						<option value="normal" selected><?php esc_html_e('Normal', 'pnpc-pocket-service-desk'); ?></option>
						<option value="high"><?php esc_html_e('High', 'pnpc-pocket-service-desk'); ?></option>
						<option value="urgent"><?php esc_html_e('Urgent', 'pnpc-pocket-service-desk'); ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="description"><?php esc_html_e('Description', 'pnpc-pocket-service-desk'); ?> <span class="required">*</span></label>
				</th>
				<td>
					<textarea id="description" name="description" rows="10" class="large-text" required></textarea>
					<p class="description"><?php esc_html_e('Detailed description of the issue.', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="attachments"><?php esc_html_e('Attachments', 'pnpc-pocket-service-desk'); ?></label>
				</th>
				<td>
					<input type="file" id="attachments" name="attachments[]" multiple />
					<p class="description">
						<?php
							$limit_max_bytes   = function_exists( 'pnpc_psd_get_max_attachment_bytes' ) ? (int) pnpc_psd_get_max_attachment_bytes() : (5 * 1024 * 1024);
							$server_max_bytes = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
							$effective_bytes  = ( $server_max_bytes > 0 ) ? min( $limit_max_bytes, $server_max_bytes ) : $limit_max_bytes;
							$effective_human  = function_exists( 'pnpc_psd_format_filesize' ) ? pnpc_psd_format_filesize( $effective_bytes ) : size_format( $effective_bytes );
/* translators: Placeholder(s) in localized string. */
							echo esc_html( sprintf( __( 'Maximum %s per file. Multiple files allowed.', 'pnpc-pocket-service-desk' ), $effective_human ) );
						?>
					</p>
					<div id="pnpc-psd-admin-attachments-preview" style="margin-top: 10px;"></div>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="notify_customer"><?php esc_html_e('Notify Customer', 'pnpc-pocket-service-desk'); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" id="notify_customer" name="notify_customer" value="1" checked />
						<?php esc_html_e('Send email notification to customer', 'pnpc-pocket-service-desk'); ?>
					</label>
					<p class="description"><?php esc_html_e('Customer will receive an email with ticket details.', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php esc_html_e('Create Ticket', 'pnpc-pocket-service-desk'); ?>
			</button>
			<a href="<?php echo esc_url(admin_url('admin.php?page=pnpc-service-desk')); ?>" class="button">
				<?php esc_html_e('Cancel', 'pnpc-pocket-service-desk'); ?>
			</a>
		</p>

		<div id="pnpc-psd-create-message"></div>
	</form>
</div>
