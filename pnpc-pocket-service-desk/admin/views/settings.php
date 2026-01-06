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

		<h2><?php esc_html_e('Real-Time Updates', 'pnpc-pocket-service-desk'); ?></h2>
		<p class="description"><?php esc_html_e('Configure automatic ticket list updates and menu badge notifications.', 'pnpc-pocket-service-desk'); ?></p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e('Enable Menu Badge', 'pnpc-pocket-service-desk'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="pnpc_psd_enable_menu_badge" value="1" <?php checked(1, get_option('pnpc_psd_enable_menu_badge', 1)); ?> />
						<?php esc_html_e('Show ticket count badge in admin menu', 'pnpc-pocket-service-desk'); ?>
					</label>
					<p class="description"><?php esc_html_e('Display a real-time counter of open and in-progress tickets in the admin sidebar menu.', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="pnpc_psd_menu_badge_interval"><?php esc_html_e('Menu Badge Update Interval', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<select name="pnpc_psd_menu_badge_interval" id="pnpc_psd_menu_badge_interval">
						<option value="15" <?php selected(15, get_option('pnpc_psd_menu_badge_interval', 30)); ?>>15 <?php esc_html_e('seconds', 'pnpc-pocket-service-desk'); ?></option>
						<option value="30" <?php selected(30, get_option('pnpc_psd_menu_badge_interval', 30)); ?>>30 <?php esc_html_e('seconds', 'pnpc-pocket-service-desk'); ?></option>
						<option value="60" <?php selected(60, get_option('pnpc_psd_menu_badge_interval', 30)); ?>>60 <?php esc_html_e('seconds', 'pnpc-pocket-service-desk'); ?></option>
						<option value="120" <?php selected(120, get_option('pnpc_psd_menu_badge_interval', 30)); ?>>2 <?php esc_html_e('minutes', 'pnpc-pocket-service-desk'); ?></option>
					</select>
					<p class="description"><?php esc_html_e('How often to check for new tickets and update the menu badge.', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e('Enable Auto-Refresh', 'pnpc-pocket-service-desk'); ?></th>
				<td>
					<label>
						<input type="checkbox" name="pnpc_psd_enable_auto_refresh" value="1" <?php checked(1, get_option('pnpc_psd_enable_auto_refresh', 1)); ?> />
						<?php esc_html_e('Automatically refresh ticket list', 'pnpc-pocket-service-desk'); ?>
					</label>
					<p class="description"><?php esc_html_e('Automatically update the ticket list without requiring a page reload. Users can pause/resume this feature.', 'pnpc-pocket-service-desk'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="pnpc_psd_auto_refresh_interval"><?php esc_html_e('Auto-Refresh Interval', 'pnpc-pocket-service-desk'); ?></label></th>
				<td>
					<select name="pnpc_psd_auto_refresh_interval" id="pnpc_psd_auto_refresh_interval">
						<option value="15" <?php selected(15, get_option('pnpc_psd_auto_refresh_interval', 30)); ?>>15 <?php esc_html_e('seconds', 'pnpc-pocket-service-desk'); ?></option>
						<option value="30" <?php selected(30, get_option('pnpc_psd_auto_refresh_interval', 30)); ?>>30 <?php esc_html_e('seconds', 'pnpc-pocket-service-desk'); ?></option>
						<option value="60" <?php selected(60, get_option('pnpc_psd_auto_refresh_interval', 30)); ?>>60 <?php esc_html_e('seconds', 'pnpc-pocket-service-desk'); ?></option>
						<option value="120" <?php selected(120, get_option('pnpc_psd_auto_refresh_interval', 30)); ?>>2 <?php esc_html_e('minutes', 'pnpc-pocket-service-desk'); ?></option>
					</select>
					<p class="description"><?php esc_html_e('How often to automatically refresh the ticket list on the admin page.', 'pnpc-pocket-service-desk'); ?></p>
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