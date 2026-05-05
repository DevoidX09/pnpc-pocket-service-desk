<?php
/**
 * Repair Setup page.
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$repair_action_url = admin_url( 'admin-post.php' );
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Repair Setup', 'pnpc-pocket-service-desk' ); ?></h1>

	<?php if ( 'repair_complete' === $notice ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html__( 'Repair completed. Summary below.', 'pnpc-pocket-service-desk' ); ?></p>
		</div>
	<?php endif; ?>

	<p>
		<?php echo esc_html__( 'This tool safely restores or re-links the required Service Desk pages (Dashboard and Ticket View) if they are missing or not configured. It will not delete content or overwrite existing pages.', 'pnpc-pocket-service-desk' ); ?>
	</p>

	<h2><?php echo esc_html__( 'Current Setup Status', 'pnpc-pocket-service-desk' ); ?></h2>
	<ul>
		<li>
			<?php echo esc_html__( 'Dashboard page:', 'pnpc-pocket-service-desk' ); ?>
			<strong>
				<?php echo ! empty( $snapshot['dashboard_configured'] ) ? esc_html__( 'Configured', 'pnpc-pocket-service-desk' ) : esc_html__( 'Missing / Not configured', 'pnpc-pocket-service-desk' ); ?>
			</strong>
			<?php if ( ! empty( $snapshot['dashboard_page_id'] ) ) : ?>
				(<?php echo esc_html__( 'ID', 'pnpc-pocket-service-desk' ); ?>: <?php echo (int) $snapshot['dashboard_page_id']; ?>)
			<?php endif; ?>
		</li>
		<li>
			<?php echo esc_html__( 'Ticket View page:', 'pnpc-pocket-service-desk' ); ?>
			<strong>
				<?php echo ! empty( $snapshot['ticket_view_configured'] ) ? esc_html__( 'Configured', 'pnpc-pocket-service-desk' ) : esc_html__( 'Missing / Not configured', 'pnpc-pocket-service-desk' ); ?>
			</strong>
			<?php if ( ! empty( $snapshot['ticket_view_page_id'] ) ) : ?>
				(<?php echo esc_html__( 'ID', 'pnpc-pocket-service-desk' ); ?>: <?php echo (int) $snapshot['ticket_view_page_id']; ?>)
			<?php endif; ?>
		</li>
	</ul>

	<form method="post" action="<?php echo esc_url( $repair_action_url ); ?>">
		<?php wp_nonce_field( 'pnpc_psd_repair_setup' ); ?>
		<input type="hidden" name="action" value="pnpc_psd_setup_repair" />
		<p>
			<button type="submit" class="button button-primary">
				<?php echo esc_html__( 'Run Repair', 'pnpc-pocket-service-desk' ); ?>
			</button>
		</p>
	</form>

	<?php if ( ! empty( $summary ) ) : ?>
		<h2><?php echo esc_html__( 'Repair Summary', 'pnpc-pocket-service-desk' ); ?></h2>
		<ul>
			<?php foreach ( $summary as $line ) : ?>
				<li><?php echo esc_html( (string) $line ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
