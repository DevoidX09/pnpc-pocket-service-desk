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
$user_id        = ! empty($current_user->ID) ? (int) $current_user->ID : 0;
$profile_image  = $user_id ? get_user_meta($user_id, 'pnpc_psd_profile_image', true) : '';
$default_avatar = get_avatar_url($user_id ?: 0);

// helper: valid WP_User with non-zero ID
$is_valid_user = ($current_user instanceof WP_User) && ($user_id > 0);

// Admin-controlled toggle for the welcome message. Default to true (1).
$show_welcome = (bool) get_option('pnpc_psd_show_welcome', 1);
?>
<div class="pnpc-psd-profile-settings">

	<?php if ($show_welcome && $is_valid_user) : ?>
		<?php
		/* translators: %s: user display name */
		printf(
			'<div class="pnpc-psd-welcome"><h2>%s</h2></div>',
			sprintf(esc_html__('Welcome, %s!', 'pnpc-pocket-service-desk'), esc_html($current_user->display_name))
		);
		?>
	<?php endif; ?>

	<h2><?php esc_html_e('Profile Settings', 'pnpc-pocket-service-desk'); ?></h2>

	<div class="pnpc-psd-profile-section">
		<h3><?php esc_html_e('Profile Image / Logo', 'pnpc-pocket-service-desk'); ?></h3>
		<div class="pnpc-psd-profile-image-wrapper">
			<div class="pnpc-psd-current-image">
				<img id="profile-image-preview" src="<?php echo esc_url($profile_image ? $profile_image : $default_avatar); ?>" alt="<?php esc_attr_e('Profile Image', 'pnpc-pocket-service-desk'); ?>" />
			</div>
			<form id="pnpc-psd-profile-image-form" enctype="multipart/form-data">
				<?php
				// CSRF protection. Matches the AJAX handler's nonce name/action.
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
			<h3><?php esc_html_e('Account Settings', 'pnpc-pocket-service-desk'); ?></h3>
			<p class="pnpc-psd-help-text">
				<?php
				/* translators: %s: URL to WordPress profile page */
				$profile_link = esc_url(admin_url('profile.php'));
				echo wp_kses_post(sprintf(__('To change your name or email, please visit your <a href="%s">WordPress profile page</a>.', 'pnpc-pocket-service-desk'), $profile_link));
				?>
			</p>
			<p>
				<a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="pnpc-psd-button pnpc-psd-button-primary">
					<?php esc_html_e('Edit Profile', 'pnpc-pocket-service-desk'); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<div class="pnpc-psd-profile-section">
			<p class="pnpc-psd-help-text">
				<?php esc_html_e('User information is not available. Please log in to manage your profile.', 'pnpc-pocket-service-desk'); ?>
			</p>
		</div>
	<?php endif; ?>
</div>