<?php
/**
 * Admin settings view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Service Desk Settings', 'pnpc-pocket-service-desk' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'pnpc_psd_settings' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="pnpc_psd_email_notifications">
						<?php esc_html_e( 'Email Notifications', 'pnpc-pocket-service-desk' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" id="pnpc_psd_email_notifications" name="pnpc_psd_email_notifications" value="1" <?php checked( get_option( 'pnpc_psd_email_notifications', 1 ), 1 ); ?> />
					<p class="description">
						<?php esc_html_e( 'Enable email notifications for ticket creation and responses.', 'pnpc-pocket-service-desk' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="pnpc_psd_auto_assign_tickets">
						<?php esc_html_e( 'Auto-Assign Tickets', 'pnpc-pocket-service-desk' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" id="pnpc_psd_auto_assign_tickets" name="pnpc_psd_auto_assign_tickets" value="1" <?php checked( get_option( 'pnpc_psd_auto_assign_tickets', 0 ), 1 ); ?> />
					<p class="description">
						<?php esc_html_e( 'Automatically assign new tickets to available agents.', 'pnpc-pocket-service-desk' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="pnpc_psd_allowed_file_types">
						<?php esc_html_e( 'Allowed File Types', 'pnpc-pocket-service-desk' ); ?>
					</label>
				</th>
				<td>
					<input type="text" id="pnpc_psd_allowed_file_types" name="pnpc_psd_allowed_file_types" value="<?php echo esc_attr( get_option( 'pnpc_psd_allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx' ) ); ?>" class="regular-text" />
					<p class="description">
						<?php esc_html_e( 'Comma-separated list of allowed file extensions for attachments.', 'pnpc-pocket-service-desk' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="pnpc_psd_products_mode">
						<?php esc_html_e( 'Products Mode', 'pnpc-pocket-service-desk' ); ?>
					</label>
				</th>
				<td>
					<select id="pnpc_psd_products_mode" name="pnpc_psd_products_mode">
						<option value="all" <?php selected( get_option( 'pnpc_psd_products_mode', 'all' ), 'all' ); ?>>
							<?php esc_html_e( 'All Products (default)', 'pnpc-pocket-service-desk' ); ?>
						</option>
						<option value="assigned" <?php selected( get_option( 'pnpc_psd_products_mode', 'all' ), 'assigned' ); ?>>
							<?php esc_html_e( 'Per-User Assigned Products Only', 'pnpc-pocket-service-desk' ); ?>
						</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Control which products users can see. "Assigned" mode requires per-user product assignments.', 'pnpc-pocket-service-desk' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="pnpc_psd_enable_user_products">
						<?php esc_html_e( 'Enable User Product Assignments', 'pnpc-pocket-service-desk' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" id="pnpc_psd_enable_user_products" name="pnpc_psd_enable_user_products" value="1" <?php checked( get_option( 'pnpc_psd_enable_user_products', 0 ), 1 ); ?> />
					<p class="description">
						<?php esc_html_e( 'Allow administrators to assign specific products to individual users. This is a PRO feature.', 'pnpc-pocket-service-desk' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
