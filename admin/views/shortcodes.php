<?php
/**
 * Shortcode reference admin view.
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dashboard_page_id = (int) get_option( 'pnpc_psd_dashboard_page_id', 0 );
$ticket_view_id    = (int) get_option( 'pnpc_psd_ticket_view_page_id', 0 );
$dashboard_url     = $dashboard_page_id > 0 && 'trash' !== get_post_status( $dashboard_page_id ) ? get_permalink( $dashboard_page_id ) : '';
$ticket_view_url   = $ticket_view_id > 0 && 'trash' !== get_post_status( $ticket_view_id ) ? get_permalink( $ticket_view_id ) : '';

$shortcodes = array(
	array(
		'code'        => '[pnpc_profile_settings]',
		'title'       => __( 'Profile Settings', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Displays the customer profile area for basic support details.', 'pnpc-pocket-service-desk' ),
		'placement'   => __( 'Customer dashboard page', 'pnpc-pocket-service-desk' ),
	),
	array(
		'code'        => '[pnpc_service_desk]',
		'title'       => __( 'Service Desk Wrapper', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Displays the primary customer-facing service desk dashboard area.', 'pnpc-pocket-service-desk' ),
		'placement'   => __( 'Customer dashboard page', 'pnpc-pocket-service-desk' ),
	),
	array(
		'code'        => '[pnpc_create_ticket]',
		'title'       => __( 'Create Ticket', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Displays the customer ticket submission form.', 'pnpc-pocket-service-desk' ),
		'placement'   => __( 'Customer dashboard page or dedicated submit-ticket page', 'pnpc-pocket-service-desk' ),
	),
	array(
		'code'        => '[pnpc_services]',
		'title'       => __( 'Services Area', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Reserved services area for connected workflows and future extensions.', 'pnpc-pocket-service-desk' ),
		'placement'   => __( 'Customer dashboard page', 'pnpc-pocket-service-desk' ),
	),
	array(
		'code'        => '[pnpc_my_tickets]',
		'title'       => __( 'My Tickets', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Displays the customer ticket list and current ticket statuses.', 'pnpc-pocket-service-desk' ),
		'placement'   => __( 'Customer dashboard page', 'pnpc-pocket-service-desk' ),
	),
	array(
		'code'        => '[pnpc_ticket_detail]',
		'title'       => __( 'Ticket Detail', 'pnpc-pocket-service-desk' ),
		'description' => __( 'Displays the single-ticket conversation view. This is normally used on the Ticket View page.', 'pnpc-pocket-service-desk' ),
		'placement'   => __( 'Ticket View page', 'pnpc-pocket-service-desk' ),
	),
);

$dashboard_stack = "[pnpc_profile_settings]\n\n[pnpc_service_desk]\n\n[pnpc_create_ticket]\n\n[pnpc_services]\n\n[pnpc_my_tickets]";
?>

<div class="wrap pnpc-psd-setup-wizard pnpc-psd-wizard-container">
	<div class="pnpc-psd-welcome-hero">
		<div class="pnpc-psd-hero-kicker"><?php echo esc_html__( 'Builder Reference', 'pnpc-pocket-service-desk' ); ?></div>
		<h1><?php echo esc_html__( 'Service Desk Shortcodes', 'pnpc-pocket-service-desk' ); ?></h1>
		<p><?php echo esc_html__( 'Use this page when building your customer support area manually with Gutenberg, Elementor, another page builder, or custom theme templates.', 'pnpc-pocket-service-desk' ); ?></p>
	</div>

	<div class="pnpc-psd-layout-grid">
		<div class="pnpc-psd-setup-card pnpc-psd-main-card">
			<h2><?php echo esc_html__( 'Available shortcodes', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'Copy each shortcode into the page builder section where you want that Service Desk element to appear.', 'pnpc-pocket-service-desk' ); ?></p>

			<div class="pnpc-psd-shortcodes-list">
				<?php foreach ( $shortcodes as $shortcode ) : ?>
					<div class="pnpc-psd-shortcode-item">
						<div class="shortcode-header">
							<h3 class="shortcode-title"><?php echo esc_html( $shortcode['title'] ); ?></h3>
							<code class="shortcode-code"><?php echo esc_html( $shortcode['code'] ); ?></code>
						</div>
						<p><?php echo esc_html( $shortcode['description'] ); ?></p>
						<p><strong><?php echo esc_html__( 'Recommended placement:', 'pnpc-pocket-service-desk' ); ?></strong> <?php echo esc_html( $shortcode['placement'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<aside class="pnpc-psd-setup-card pnpc-psd-status-card">
			<h2><?php echo esc_html__( 'Recommended dashboard stack', 'pnpc-pocket-service-desk' ); ?></h2>
			<p><?php echo esc_html__( 'For a simple all-in-one customer dashboard, place these shortcodes on one page in this order.', 'pnpc-pocket-service-desk' ); ?></p>
			<textarea class="large-text code pnpc-psd-shortcode-textarea" rows="10" readonly="readonly"><?php echo esc_textarea( $dashboard_stack ); ?></textarea>

			<div class="pnpc-psd-help-box">
				<strong><?php echo esc_html__( 'DIY builder note', 'pnpc-pocket-service-desk' ); ?></strong>
				<p><?php echo esc_html__( 'Most page builders have a Shortcode, HTML, or Text widget/block. Paste the shortcode into that widget and save the page.', 'pnpc-pocket-service-desk' ); ?></p>
			</div>

			<?php if ( ! empty( $dashboard_url ) ) : ?>
				<p><strong><?php echo esc_html__( 'Dashboard page:', 'pnpc-pocket-service-desk' ); ?></strong> <a href="<?php echo esc_url( $dashboard_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $dashboard_url ); ?></a></p>
			<?php endif; ?>

			<?php if ( ! empty( $ticket_view_url ) ) : ?>
				<p><strong><?php echo esc_html__( 'Ticket View page:', 'pnpc-pocket-service-desk' ); ?></strong> <a href="<?php echo esc_url( $ticket_view_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $ticket_view_url ); ?></a></p>
			<?php endif; ?>

			<div class="pnpc-psd-actions-row is-stacked">
				<a class="button button-primary pnpc-psd-button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-setup&step=landing' ) ); ?>"><?php echo esc_html__( 'Open Setup Wizard', 'pnpc-pocket-service-desk' ); ?></a>
				<a class="button button-secondary pnpc-psd-button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=pnpc-service-desk-diagnostics' ) ); ?>"><?php echo esc_html__( 'Open Diagnostics', 'pnpc-pocket-service-desk' ); ?></a>
			</div>
		</aside>
	</div>
</div>
