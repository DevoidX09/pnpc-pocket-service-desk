<?php
/**
 * Support Hub admin view.
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'pnpc_psd_view_tickets' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'pnpc-pocket-service-desk' ) );
}

$current_user        = wp_get_current_user();
$receiver_name       = get_option( 'pnpc_psd_support_receiver_name', 'PNPC Support' );
$receiver_url        = get_option( 'pnpc_psd_support_receiver_url', 'https://plugnplayconsultants.com/dashboard/' );
$support_access_url  = get_option( 'pnpc_psd_support_access_url', 'https://plugnplayconsultants.com/pnpc-service-desk-support/' );
$bundle_url          = wp_nonce_url( admin_url( 'admin-post.php?action=pnpc_psd_download_support_bundle' ), 'pnpc_psd_download_support_bundle' );
$health              = function_exists( 'pnpc_psd_get_operational_health_summary' ) ? pnpc_psd_get_operational_health_summary() : array();
$status              = isset( $health['status'] ) ? sanitize_key( $health['status'] ) : 'healthy';
$label               = isset( $health['label'] ) ? $health['label'] : __( 'Healthy', 'pnpc-pocket-service-desk' );
$status_class        = 'critical' === $status ? 'notice-error' : ( 'needs_attention' === $status ? 'notice-warning' : 'notice-success' );
$detected_site_url   = home_url();
$prefill_name        = $current_user && $current_user->exists() ? $current_user->display_name : '';
$prefill_email       = $current_user && $current_user->exists() ? $current_user->user_email : '';
?>
<div class="wrap pnpc-psd-support-hub">
	<h1><?php esc_html_e( 'Service Desk Support', 'pnpc-pocket-service-desk' ); ?></h1>

	<div class="notice <?php echo esc_attr( $status_class ); ?> inline">
		<p><strong><?php esc_html_e( 'Operational Health:', 'pnpc-pocket-service-desk' ); ?></strong> <?php echo esc_html( $label ); ?></p>
	</div>

	<div class="pnpc-psd-support-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-top:18px;max-width:1180px;">
		<div class="card" style="max-width:none;padding:22px;">
			<h2><?php esc_html_e( 'Get Support Access', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php esc_html_e( 'Use this form to open or create your support access with the configured support destination. Name, email, and website URL are required so support can identify the site and account correctly.', 'pnpc-pocket-service-desk' ); ?></p>
			<p><strong><?php esc_html_e( 'Support destination:', 'pnpc-pocket-service-desk' ); ?></strong> <?php echo esc_html( $receiver_name ); ?></p>

			<?php if ( ! empty( $support_access_url ) ) : ?>
				<form method="post" action="<?php echo esc_url( $support_access_url ); ?>" target="_blank" rel="noopener noreferrer">
					<input type="hidden" name="pnpc_psd_support_source" value="remote-plugin" />
					<input type="hidden" name="pnpc_psd_product_edition" value="Core" />
					<input type="hidden" name="pnpc_psd_product_version" value="<?php echo esc_attr( defined( 'PNPC_PSD_VERSION' ) ? PNPC_PSD_VERSION : '' ); ?>" />
					<p>
						<label for="pnpc_psd_support_name"><strong><?php esc_html_e( 'Name', 'pnpc-pocket-service-desk' ); ?></strong></label><br />
						<input type="text" id="pnpc_psd_support_name" name="pnpc_psd_support_name" value="<?php echo esc_attr( $prefill_name ); ?>" class="regular-text" required />
					</p>
					<p>
						<label for="pnpc_psd_support_email"><strong><?php esc_html_e( 'Email', 'pnpc-pocket-service-desk' ); ?></strong></label><br />
						<input type="email" id="pnpc_psd_support_email" name="pnpc_psd_support_email" value="<?php echo esc_attr( $prefill_email ); ?>" class="regular-text" required />
					</p>
					<p>
						<label for="pnpc_psd_support_site_url"><strong><?php esc_html_e( 'Service Desk Website URL', 'pnpc-pocket-service-desk' ); ?></strong></label><br />
						<input type="url" id="pnpc_psd_support_site_url" name="pnpc_psd_support_site_url" value="<?php echo esc_url( $detected_site_url ); ?>" class="regular-text" required />
					</p>
					<p class="description"><?php esc_html_e( 'This information is required for proper support. If you have not installed Service Desk yet and only have a general question, email support@plugnplayconsultants.com.', 'pnpc-pocket-service-desk' ); ?></p>
					<p><button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Open or Create Support Access', 'pnpc-pocket-service-desk' ); ?></button></p>
				</form>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No support access URL is configured yet.', 'pnpc-pocket-service-desk' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width:none;padding:22px;">
			<h2><?php esc_html_e( 'Support Bundle', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php esc_html_e( 'Download a diagnostics-only support bundle for troubleshooting. This does not include customer ticket bodies, customer messages, attachments, WooCommerce orders, or customer personal data.', 'pnpc-pocket-service-desk' ); ?></p>
			<p><a class="button button-secondary button-hero" href="<?php echo esc_url( $bundle_url ); ?>"><?php esc_html_e( 'Download Support Bundle', 'pnpc-pocket-service-desk' ); ?></a></p>
		</div>

		<div class="card" style="max-width:none;padding:22px;">
			<h2><?php esc_html_e( 'Useful Resources', 'pnpc-pocket-service-desk' ); ?></h2>
			<ul style="list-style:disc;margin-left:20px;">
				<?php if ( ! empty( $receiver_url ) ) : ?>
					<li><a href="<?php echo esc_url( $receiver_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support Dashboard', 'pnpc-pocket-service-desk' ); ?></a></li>
				<?php endif; ?>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-diagnostics' ) ); ?>"><?php esc_html_e( 'Diagnostics', 'pnpc-pocket-service-desk' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-shortcodes' ) ); ?>"><?php esc_html_e( 'Shortcode Reference', 'pnpc-pocket-service-desk' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup' ) ); ?>"><?php esc_html_e( 'Setup Wizard', 'pnpc-pocket-service-desk' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-settings&tab=support' ) ); ?>"><?php esc_html_e( 'Support Settings', 'pnpc-pocket-service-desk' ); ?></a></li>
			</ul>
		</div>
	</div>

	<h2 style="margin-top:28px;"><?php esc_html_e( 'What the Support Bundle Includes', 'pnpc-pocket-service-desk' ); ?></h2>
	<table class="widefat striped" style="max-width:900px;">
		<tbody>
			<tr><th scope="row"><?php esc_html_e( 'Product', 'pnpc-pocket-service-desk' ); ?></th><td><?php esc_html_e( 'Edition and version.', 'pnpc-pocket-service-desk' ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Environment', 'pnpc-pocket-service-desk' ); ?></th><td><?php esc_html_e( 'Site URL, WordPress version, PHP version, database version, and multisite status.', 'pnpc-pocket-service-desk' ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Health Summary', 'pnpc-pocket-service-desk' ); ?></th><td><?php esc_html_e( 'Current operational health status and detected issues.', 'pnpc-pocket-service-desk' ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Recent Logs', 'pnpc-pocket-service-desk' ); ?></th><td><?php esc_html_e( 'Recent error log entries and recent audit event summaries.', 'pnpc-pocket-service-desk' ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Excluded', 'pnpc-pocket-service-desk' ); ?></th><td><?php esc_html_e( 'Customer ticket messages, attachments, WooCommerce orders, and customer personal data.', 'pnpc-pocket-service-desk' ); ?></td></tr>
		</tbody>
	</table>

	<?php do_action( 'pnpc_psd_support_hub_panels' ); ?>
</div>
