<?php

/**
 * Admin settings view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin/views
 */

if (! defined('ABSPATH')) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e('Service Desk Settings', 'pnpc-pocket-service-desk'); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields('pnpc_psd_settings'); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="pnpc_psd_email_notifications">
						<?php esc_html_e('Email Notifications', 'pnpc-pocket-service-desk'); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" id="pnpc_psd_email_notifications" name="pnpc_psd_email_notifications" value="1" <?php checked(get_option('pnpc_psd_email_notifications', 1), 1); ?> />
					<p class="description">
						<?php esc_html_e('Enable email notifications for ticket creation and responses.', 'pnpc-pocket-service-desk'); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="pnpc_psd_auto_assign_tickets">
						<?php esc_html_e('Auto-Assign Tickets', 'pnpc-pocket-service-desk'); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" id="pnpc_psd_auto_assign_tickets" name="pnpc_psd_auto_assign_tickets" value="1" <?php checked(get_option('pnpc_psd_auto_assign_tickets', 0), 1); ?> />
					<p class="description">
						<?php esc_html_e('Automatically assign new tickets to available agents.', 'pnpc-pocket-service-desk'); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="pnpc_psd_allowed_file_types">
						<?php esc_html_e('Allowed File Types', 'pnpc-pocket-service-desk'); ?>
					</label>
				</th>
				<td>
					<input type="text" id="pnpc_psd_allowed_file_types" name="pnpc_psd_allowed_file_types" value="<?php echo esc_attr(get_option('pnpc_psd_allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx')); ?>" class="regular-text" />
					<p class="description">
						<?php esc_html_e('Comma-separated list of allowed file extensions for attachments.', 'pnpc-pocket-service-desk'); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pnpc_psd_show_welcome"><?php esc_html_e('Show Dashboard Welcome', 'pnpc-pocket-service-desk'); ?></label>
				</th>
				<td>
					<input type="checkbox" id="pnpc_psd_show_welcome" name="pnpc_psd_show_welcome" value="1" <?php checked(get_option('pnpc_psd_show_welcome', 1), 1); ?> />
					<p class="description">
						<?php esc_html_e('Display a welcome message (e.g. "Welcome, {name}!") at the top of the Profile Settings page. Uncheck to hide the message.', 'pnpc-pocket-service-desk'); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>