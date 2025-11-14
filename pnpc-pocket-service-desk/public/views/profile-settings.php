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

$current_user   = wp_get_current_user();
$profile_image  = get_user_meta( $current_user->ID, 'pnpc_psd_profile_image', true );
$default_avatar = get_avatar_url( $current_user->ID );
?>

<div class="pnpc-psd-profile-settings">
	<h2><?php esc_html_e( 'Profile Settings', 'pnpc-pocket-service-desk' ); ?></h2>

	<div class="pnpc-psd-profile-section">
		<h3><?php esc_html_e( 'Profile Image / Logo', 'pnpc-pocket-service-desk' ); ?></h3>
		<div class="pnpc-psd-profile-image-wrapper">
			<div class="pnpc-psd-current-image">
				<img id="profile-image-preview" src="<?php echo esc_url( $profile_image ? $profile_image : $default_avatar ); ?>" alt="<?php esc_attr_e( 'Profile Image', 'pnpc-pocket-service-desk' ); ?>" />
			</div>
			<form id="pnpc-psd-profile-image-form" enctype="multipart/form-data">
				<div class="pnpc-psd-form-group">
					<label for="profile-image-upload" class="pnpc-psd-button">
						<?php esc_html_e( 'Choose Image', 'pnpc-pocket-service-desk' ); ?>
					</label>
					<input type="file" id="profile-image-upload" name="profile_image" accept="image/*" style="display: none;" />
				</div>
				<p class="pnpc-psd-help-text">
					<?php esc_html_e( 'Supported formats: JPEG, PNG, GIF. Maximum size: 2MB.', 'pnpc-pocket-service-desk' ); ?>
				</p>
				<div id="profile-image-message" class="pnpc-psd-message"></div>
			</form>
		</div>
	</div>

	<div class="pnpc-psd-profile-section">
		<h3><?php esc_html_e( 'Account Information', 'pnpc-pocket-service-desk' ); ?></h3>
		<div class="pnpc-psd-info-grid">
			<div class="pnpc-psd-info-item">
				<label><?php esc_html_e( 'Display Name:', 'pnpc-pocket-service-desk' ); ?></label>
				<span><?php echo esc_html( $current_user->display_name ); ?></span>
			</div>
			<div class="pnpc-psd-info-item">
				<label><?php esc_html_e( 'Email:', 'pnpc-pocket-service-desk' ); ?></label>
				<span><?php echo esc_html( $current_user->user_email ); ?></span>
			</div>
			<div class="pnpc-psd-info-item">
				<label><?php esc_html_e( 'Username:', 'pnpc-pocket-service-desk' ); ?></label>
				<span><?php echo esc_html( $current_user->user_login ); ?></span>
			</div>
		</div>
		<p class="pnpc-psd-help-text">
			<?php
			printf(
				/* translators: %s: URL to WordPress profile page */
				wp_kses_post( __( 'To change your account information, please visit your <a href="%s">WordPress profile page</a>.', 'pnpc-pocket-service-desk' ) ),
				esc_url( admin_url( 'profile.php' ) )
			);
			?>
		</p>
	</div>

	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<div class="pnpc-psd-profile-section">
			<h3><?php esc_html_e( 'WooCommerce Integration', 'pnpc-pocket-service-desk' ); ?></h3>
			<p>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="pnpc-psd-button pnpc-psd-button-primary">
					<?php esc_html_e( 'View Products', 'pnpc-pocket-service-desk' ); ?>
				</a>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="pnpc-psd-button">
					<?php esc_html_e( 'My Account', 'pnpc-pocket-service-desk' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>
</div>
