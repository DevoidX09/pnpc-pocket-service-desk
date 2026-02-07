<?php

/**
 * Public profile settings view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var WP_User $current_user */
$current_user = wp_get_current_user();

// Defensive: derive an integer user_id and only fetch meta/avatar when valid.
$user_id        = ! empty($current_user->ID) ? (int) $current_user->ID : 0;
$profile_image  = $user_id ? get_user_meta($user_id, 'pnpc_psd_profile_image', true) : '';
$default_avatar = get_avatar_url($user_id ?: 0);

// Logout redirect target (admin-configurable). Default to home page.
$logout_redirect_page_id = absint(get_option('pnpc_psd_logout_redirect_page_id', 0));
$logout_redirect_url     = home_url('/');
if ($logout_redirect_page_id) {
	$maybe = get_permalink($logout_redirect_page_id);
	if (! empty($maybe)) {
		$logout_redirect_url = $maybe;
	}
}

// helper: valid WP_User with non-zero ID
$is_valid_user = ($current_user instanceof WP_User) && ($user_id > 0);

// Admin-controlled toggle for the profile-settings welcome message. Default to true (1).
$show_welcome_profile = (bool) get_option('pnpc_psd_show_welcome_profile', 1);
?>
<div class="pnpc-psd-profile-settings">

	<?php if ($show_welcome_profile && $is_valid_user) : ?>
		<?php
		/* translators: %s: user display name */
		printf(
			'<div class="pnpc-psd-welcome"><h2>%s</h2></div>',
/* translators: Placeholder(s) in localized string. */
			sprintf(esc_html__('Welcome, %s!', 'pnpc-pocket-service-desk'), esc_html($current_user->display_name))
		);
		?>
	<?php endif; ?>

	<?php if ($is_valid_user) : ?>
		<!-- Two-column layout: profile image (left) and account settings (right) -->
		<div class="pnpc-psd-profile-grid" style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;">
			<div class="pnpc-psd-profile-column" style="flex:1;min-width:280px;">
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
			</div>

			<div class="pnpc-psd-profile-column" style="flex:1;min-width:280px;">
				<div class="pnpc-psd-profile-section">
					<h3><?php esc_html_e('Account Settings', 'pnpc-pocket-service-desk'); ?></h3>
					<div style="text-align:center;">
						<p class="pnpc-psd-help-text" style="margin:10px auto 18px;max-width:520px;">
							<?php esc_html_e('To view or edit your account settings press the button below.', 'pnpc-pocket-service-desk'); ?>
						</p>
						<p style="margin:0;">
							<a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="pnpc-psd-button pnpc-psd-button-primary" style="display:inline-block;">
								<?php esc_html_e('Edit Profile', 'pnpc-pocket-service-desk'); ?>
							</a>
							<a href="<?php echo esc_url(wp_logout_url($logout_redirect_url)); ?>" class="pnpc-psd-button pnpc-psd-button-logout" style="display:inline-block;margin-left:10px;">
								<?php esc_html_e('Logout', 'pnpc-pocket-service-desk'); ?>
							</a>
						</p>
					</div>
				</div>
			</div>
		</div>
	<?php else : ?>
		<!-- Not logged in: show profile image section alone and a friendly message -->
		<div class="pnpc-psd-profile-section">
			<h3><?php esc_html_e('Profile Image / Logo', 'pnpc-pocket-service-desk'); ?></h3>
			<div class="pnpc-psd-profile-image-wrapper">
				<div class="pnpc-psd-current-image">
					<img id="profile-image-preview" src="<?php echo esc_url($profile_image ? $profile_image : $default_avatar); ?>" alt="<?php esc_attr_e('Profile Image', 'pnpc-pocket-service-desk'); ?>" />
				</div>
				<form id="pnpc-psd-profile-image-form" enctype="multipart/form-data">
					<?php wp_nonce_field('pnpc_psd_public_nonce', 'nonce'); ?>
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

		<div class="pnpc-psd-profile-section">
			<p class="pnpc-psd-help-text">
				<?php esc_html_e('User information is not available. Please log in to manage your profile.', 'pnpc-pocket-service-desk'); ?>
			</p>
		</div>
	<?php endif; ?>
</div>