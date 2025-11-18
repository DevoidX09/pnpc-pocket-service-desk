<?php

/**
 * Plugin settings page (admin)
 *
 * @package PNPC_Pocket_Service_Desk
 */

if (! defined('ABSPATH')) {
	exit;
}

?>
<div class="wrap pnpc-psd-settings">
	<h1><?php esc_html_e('PNPC Pocket Service Desk Settings', 'pnpc-pocket-service-desk'); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields('pnpc_psd_settings');
		do_settings_sections('pnpc_psd_settings');
		?>

		<h2><?php esc_html_e('Notifications', 'pnpc-pocket-service-desk'); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="pnpc_psd_email_notifications"><?php esc_html_e('Notification Email', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<input type="email" name="pnpc_psd_email_notifications" id="pnpc_psd_email_notifications" value="<?php echo esc_attr(get_option('pnpc_psd_email_notifications', '')); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e('Enter the email address that should receive plugin notifications (one address).', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e('Product / Services Display', 'pnpc-pocket-service-desk'); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e('Display Public Products', 'pnpc-pocket-service-desk'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="pnpc_psd_show_products" value="1" <?php checked(1, get_option('pnpc_psd_show_products', 1)); ?> />
						<?php esc_html_e('Show a public product catalog in the Services block (free feature).', 'pnpc-pocket-service-desk'); ?>
					</label>
					<p class="description"><?php esc_html_e('If enabled, the Services shortcode will show general published products to viewers (unless user-specific products are enabled).', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e('Enable User-specific Products (Pro)', 'pnpc-pocket-service-desk'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="pnpc_psd_user_specific_products" value="1" <?php checked(1, get_option('pnpc_psd_user_specific_products', 0)); ?> />
						<?php esc_html_e('Restrict product listings to products allocated to an individual user. This is a pro feature (gated in future).', 'pnpc-pocket-service-desk'); ?>
					</label>
					<p class="description"><?php esc_html_e('When enabled, the Services block will show only products explicitly allocated to the viewing user (admins can still assign allocations). Use one mode or the other (user-specific takes precedence).', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e('Colors & Buttons', 'pnpc-pocket-service-desk'); ?></h2>
		<p class="description"><?php esc_html_e('Customize colors used across shortcodes and product cards. Colors are hex values.', 'pnpc-pocket-service-desk'); ?></p>
		<table class="form-table">

			<tr>
				<th scope="row"><label for="pnpc_psd_primary_button_color"><?php esc_html_e('Edit Profile Button color', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<input type="color" id="pnpc_psd_primary_button_color" name="pnpc_psd_primary_button_color" value="<?php echo esc_attr(get_option('pnpc_psd_primary_button_color', '#2b9f6a')); ?>" />
					<p class="description"><?php esc_html_e('Used for the Edit Profile button and other primary actions.', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="pnpc_psd_primary_button_hover_color"><?php esc_html_e('Edit Profile Button hover color', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<input type="color" id="pnpc_psd_primary_button_hover_color" name="pnpc_psd_primary_button_hover_color" value="<?php echo esc_attr(get_option('pnpc_psd_primary_button_hover_color', '#238a56')); ?>" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="pnpc_psd_secondary_button_color"><?php esc_html_e('Create Ticket Button color', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<input type="color" id="pnpc_psd_secondary_button_color" name="pnpc_psd_secondary_button_color" value="<?php echo esc_attr(get_option('pnpc_psd_secondary_button_color', '#6c757d')); ?>" />
					<p class="description"><?php esc_html_e('Used for the Create Ticket button in the Service Desk.', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="pnpc_psd_secondary_button_hover_color"><?php esc_html_e('Create Ticket Button hover color', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<input type="color" id="pnpc_psd_secondary_button_hover_color" name="pnpc_psd_secondary_button_hover_color" value="<?php echo esc_attr(get_option('pnpc_psd_secondary_button_hover_color', '#5a6268')); ?>" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="pnpc_psd_card_bg_color"><?php esc_html_e('Product card background', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<input type="color" id="pnpc_psd_card_bg_color" name="pnpc_psd_card_bg_color" value="<?php echo esc_attr(get_option('pnpc_psd_card_bg_color', '#ffffff')); ?>" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="pnpc_psd_card_bg_hover_color"><?php esc_html_e('Product card hover background', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<input type="color" id="pnpc_psd_card_bg_hover_color" name="pnpc_psd_card_bg_hover_color" value="<?php echo esc_attr(get_option('pnpc_psd_card_bg_hover_color', '#f7f9fb')); ?>" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="pnpc_psd_card_button_color"><?php esc_html_e('Product card button color', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<input type="color" id="pnpc_psd_card_button_color" name="pnpc_psd_card_button_color" value="<?php echo esc_attr(get_option('pnpc_psd_card_button_color', '#2b9f6a')); ?>" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="pnpc_psd_card_button_hover_color"><?php esc_html_e('Product card button hover color', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<input type="color" id="pnpc_psd_card_button_hover_color" name="pnpc_psd_card_button_hover_color" value="<?php echo esc_attr(get_option('pnpc_psd_card_button_hover_color', '#238a56')); ?>" />
				</td>
			</tr>

		</table>

		<?php submit_button(); ?>
	</form>
</div>