<?php

/**
 * Public profile settings view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if (! defined('ABSPATH')) {
	exit;
}

/** @var WP_User $current_user */
$current_user = wp_get_current_user();

// Defensive: derive an integer user_id and only fetch meta/avatar when valid.
$user_id = ! empty($current_user->ID) ? (int) $current_user->ID : 0;
$profile_image = $user_id ? get_user_meta($user_id, 'pnpc_psd_profile_image', true) : '';
$default_avatar = get_avatar_url($user_id ?: 0);

// helper: valid WP_User with non-zero ID
$is_valid_user = ($current_user instanceof WP_User) && ($user_id > 0);
?>
<div class="pnpc-psd-profile-settings">
	<h2><?php esc_html_e('Profile Settings', 'pnpc-pocket-service-desk'); ?></h2>

	<div class="pnpc-psd-profile-section">
		<h3><?php esc_html_e('Profile Image / Logo', 'pnpc-pocket-service-desk'); ?></h3>
		<div class="pnpc-psd-profile-image-wrapper">
			<div class="pnpc-psd-current-image">
				<img id="profile-image-preview" src="<?php echo esc_url($profile_image ? $profile_image : $default_avatar); ?>" alt="<?php esc_attr_e('Profile Image', 'pnpc-pocket-service-desk'); ?>" />
			</div>
			<form id="pnpc-psd-profile-image-form" enctype="multipart/form-data">
				<?php
				// Add nonce for CSRF protection. The AJAX handler currently verifies
				// 'pnpc_psd_public_nonce' with key 'nonce' and the public JS is localized
				// with that same nonce. Including wp_nonce_field here makes non-AJAX
				// fallback safe and quiets linters.
				wp_nonce_field('pnpc_psd_public_nonce', 'nonce');
				?>
				<div class="pnpc-psd-form-group">
					<label for="profile-image-upload" class="pnpc-psd-button">
						<?php esc_html_e('Choose Image', 'pnpc-pocket-service-desk'); ?>
					</label>
					<input type="file" id="profile-image-upload" name="profile_image" accept="image/*" style="display: none;" />
				</div>
				<p class="pnpc-psd-help-text">
					<?php esc_html_e('Supported formats: JPEG, PNG, GIF. Maximum size: 2MB.', 'pnpc-pocket-service-desk'); ?>
				</p>
				<div id="profile-image-message" class="pnpc-psd-message"></div>
			</form>
		</div>
	</div>

	<?php if ($is_valid_user) : ?>
		<div class="pnpc-psd-profile-section">
			<h3><?php esc_html_e('Account Information', 'pnpc-pocket-service-desk'); ?></h3>
			<div class="pnpc-psd-info-grid">
				<div class="pnpc-psd-info-item">
					<label><?php esc_html_e('Display Name:', 'pnpc-pocket-service-desk'); ?></label>
					<span><?php echo esc_html($current_user->display_name); ?></span>
				</div>
				<div class="pnpc-psd-info-item">
					<label><?php esc_html_e('Email:', 'pnpc-pocket-service-desk'); ?></label>
					<span><?php echo esc_html($current_user->user_email); ?></span>
				</div>
				<div class="pnpc-psd-info-item">
					<label><?php esc_html_e('Username:', 'pnpc-pocket-service-desk'); ?></label>
					<span><?php echo esc_html($current_user->user_login); ?></span>
				</div>
			</div>
			<p class="pnpc-psd-help-text">
				<?php
				// Build the translated string with the escaped URL, then sanitize the
				// entire HTML output using wp_kses_post().
				$profile_link = esc_url(admin_url('profile.php'));
				echo wp_kses_post(
					sprintf(
						/* translators: %s: URL to WordPress profile page */
						__('To change your account information, please visit your <a href="%s">WordPress profile page</a>.', 'pnpc-pocket-service-desk'),
						$profile_link
					)
				);
				?>
			</p>
		</div>
	<?php else : ?>
		<div class="pnpc-psd-profile-section">
			<p class="pnpc-psd-help-text">
				<?php esc_html_e('User information is not available.', 'pnpc-pocket-service-desk'); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if (class_exists('WooCommerce')) : ?>
		<div class="pnpc-psd-profile-section">
			<h3><?php esc_html_e('WooCommerce Integration', 'pnpc-pocket-service-desk'); ?></h3>
			<p>
				<?php if (function_exists('wc_get_page_permalink')) : ?>
					<a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="pnpc-psd-button pnpc-psd-button-primary">
						<?php esc_html_e('View Products', 'pnpc-pocket-service-desk'); ?>
					</a>
					<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="pnpc-psd-button">
						<?php esc_html_e('My Account', 'pnpc-pocket-service-desk'); ?>
					</a>
				<?php else : ?>
					<!-- Fallback: safe link if helper not available -->
					<a href="<?php echo esc_url(home_url()); ?>" class="pnpc-psd-button pnpc-psd-button-primary">
						<?php esc_html_e('View Products', 'pnpc-pocket-service-desk'); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>
</div>