<?php
/**
 * Admin dashboard view.
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$support_url = apply_filters( 'pnpc_psd_support_url', '' );

// Default to the bundled dashboard logo if none is provided by a theme/site filter.
$logo_url = apply_filters( 'pnpc_psd_dashboard_logo_url', '' );
if ( ! $logo_url ) {
	$logo_url = plugins_url( 'assets/images/pnpc-pocket-service-desk.png', dirname( __FILE__, 3 ) . '/pnpc-pocket-service-desk.php' );
}
$logo_alt = apply_filters( 'pnpc_psd_dashboard_logo_alt', 'PNPC Pocket Service Desk' );

$alerts = apply_filters( 'pnpc_psd_dashboard_alerts', array() );
if ( ! is_array( $alerts ) ) {
	$alerts = array();
}

// Review queue count (used for the dashboard alert header indicator).
$review_queue_count = 0;
if ( class_exists( 'PNPC_PSD_Ticket' ) ) {
	$review_queue_count = (int) PNPC_PSD_Ticket::get_pending_delete_count();
}

$opened_week  = isset( $stats['opened']['week'] ) ? (int) $stats['opened']['week'] : 0;
$opened_month = isset( $stats['opened']['month'] ) ? (int) $stats['opened']['month'] : 0;
$opened_year  = isset( $stats['opened']['year'] ) ? (int) $stats['opened']['year'] : 0;

$closed_week  = isset( $stats['closed']['week'] ) ? (int) $stats['closed']['week'] : 0;
$closed_month = isset( $stats['closed']['month'] ) ? (int) $stats['closed']['month'] : 0;
$closed_year  = isset( $stats['closed']['year'] ) ? (int) $stats['closed']['year'] : 0;

$total_open   = isset( $stats['total']['open'] ) ? (int) $stats['total']['open'] : 0;
$total_closed = isset( $stats['total']['closed'] ) ? (int) $stats['total']['closed'] : 0;

$completion_rate = isset( $stats['completion_rate'] ) ? (float) $stats['completion_rate'] : 0.0;
$completion_pct  = (int) round( max( 0, min( 100, $completion_rate ) ) );

$menu_tickets_url  = admin_url( 'admin.php?page=pnpc-service-desk-tickets' );
$menu_create_url   = admin_url( 'admin.php?page=pnpc-service-desk-create-ticket' );
$menu_audit_url    = admin_url( 'admin.php?page=pnpc-service-desk-audit-log' );
$menu_settings_url = admin_url( 'admin.php?page=pnpc-service-desk-settings' );

?>
<div class="wrap pnpc-psd-dashboard">

	<div class="psd-topbar">
		<div class="psd-brand">
			<div class="psd-logo">
				<?php if ( $logo_url ) : ?>
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" />
				<?php else : ?>
					<span class="psd-logo--placeholder"><?php echo esc_html( strtoupper( substr( (string) $logo_alt, 0, 4 ) ) ); ?></span>
				<?php endif; ?>
			</div>
			<div>
				<h1 class="psd-title"><?php echo esc_html__( 'Service Desk', 'pnpc-pocket-service-desk' ); ?></h1>
				<p class="psd-subtitle"><?php echo esc_html__( 'Overview, stats, and quick actions.', 'pnpc-pocket-service-desk' ); ?></p>
			</div>
		</div>

		<div class="psd-actions">
			<a class="button button-primary" href="<?php echo esc_url( $menu_tickets_url ); ?>"><?php echo esc_html__( 'All Tickets', 'pnpc-pocket-service-desk' ); ?></a>
			<a class="button" href="<?php echo esc_url( $menu_create_url ); ?>"><?php echo esc_html__( 'Create Ticket', 'pnpc-pocket-service-desk' ); ?></a>
			<a class="button" href="<?php echo esc_url( $menu_audit_url ); ?>"><?php echo esc_html__( 'Audit Log', 'pnpc-pocket-service-desk' ); ?></a>
			<a class="button" href="<?php echo esc_url( $menu_settings_url ); ?>"><?php echo esc_html__( 'Settings', 'pnpc-pocket-service-desk' ); ?></a>

			<?php if ( $support_url ) : ?>
				<a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $support_url ); ?>">
					<?php echo esc_html__( 'Support', 'pnpc-pocket-service-desk' ); ?>
				</a>
			<?php else : ?>
				<span class="psd-muted"><?php echo esc_html__( 'Support link not configured.', 'pnpc-pocket-service-desk' ); ?></span>
			<?php endif; ?>

			<?php /* No upgrade/promotional CTAs in the Free build. */ ?>
		</div>
	</div>

	<div class="psd-grid">
		<div class="psd-card psd-card--stats">
			<div class="psd-split">
				<div>
					<h2 style="margin:0 0 4px;"><?php echo esc_html__( 'Ticket Stats', 'pnpc-pocket-service-desk' ); ?></h2>
					<p class="psd-muted" style="margin:0;"><?php echo esc_html__( 'Counts are based on ticket creation and closure activity.', 'pnpc-pocket-service-desk' ); ?></p>
				</div>

				<div class="psd-rings">
					<div class="psd-ring" data-target="<?php echo esc_attr( $completion_pct ); ?>" aria-label="<?php echo esc_attr__( 'Completion rate', 'pnpc-pocket-service-desk' ); ?>">
						<div class="psd-ring__label">
							<span class="psd-ring__value"><span class="psd-ring__num">0</span>%</span>
							<span class="psd-ring__caption"><?php echo esc_html__( 'Completion', 'pnpc-pocket-service-desk' ); ?></span>
						</div>
					</div>

					<?php
					$den = max( 1, ( $total_open + $total_closed ) );
					$closed_pct = (int) round( 100 * ( $total_closed / $den ) );
					?>
					<div class="psd-ring" style="color:#2a7a2a;" data-target="<?php echo esc_attr( $closed_pct ); ?>" aria-label="<?php echo esc_attr__( 'Closed ratio', 'pnpc-pocket-service-desk' ); ?>">
						<div class="psd-ring__label">
							<span class="psd-ring__value"><span class="psd-ring__num">0</span>%</span>
							<span class="psd-ring__caption"><?php echo esc_html__( 'Closed of total', 'pnpc-pocket-service-desk' ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<div class="psd-metrics">
				<div class="psd-metric">
					<p class="k"><?php echo esc_html__( 'Opened', 'pnpc-pocket-service-desk' ); ?> — <?php echo esc_html__( 'This week', 'pnpc-pocket-service-desk' ); ?></p>
					<p class="v"><?php echo esc_html( number_format_i18n( $opened_week ) ); ?></p>
				</div>
				<div class="psd-metric">
					<p class="k"><?php echo esc_html__( 'Opened', 'pnpc-pocket-service-desk' ); ?> — <?php echo esc_html__( 'This month', 'pnpc-pocket-service-desk' ); ?></p>
					<p class="v"><?php echo esc_html( number_format_i18n( $opened_month ) ); ?></p>
				</div>
				<div class="psd-metric">
					<p class="k"><?php echo esc_html__( 'Opened', 'pnpc-pocket-service-desk' ); ?> — <?php echo esc_html__( 'This year', 'pnpc-pocket-service-desk' ); ?></p>
					<p class="v"><?php echo esc_html( number_format_i18n( $opened_year ) ); ?></p>
				</div>

				<div class="psd-metric">
					<p class="k"><?php echo esc_html__( 'Closed', 'pnpc-pocket-service-desk' ); ?> — <?php echo esc_html__( 'This week', 'pnpc-pocket-service-desk' ); ?></p>
					<p class="v"><?php echo esc_html( number_format_i18n( $closed_week ) ); ?></p>
				</div>
				<div class="psd-metric">
					<p class="k"><?php echo esc_html__( 'Closed', 'pnpc-pocket-service-desk' ); ?> — <?php echo esc_html__( 'This month', 'pnpc-pocket-service-desk' ); ?></p>
					<p class="v"><?php echo esc_html( number_format_i18n( $closed_month ) ); ?></p>
				</div>
				<div class="psd-metric">
					<p class="k"><?php echo esc_html__( 'Closed', 'pnpc-pocket-service-desk' ); ?> — <?php echo esc_html__( 'This year', 'pnpc-pocket-service-desk' ); ?></p>
					<p class="v"><?php echo esc_html( number_format_i18n( $closed_year ) ); ?></p>
				</div>

				<div class="psd-metric">
					<p class="k"><?php echo esc_html__( 'Currently open', 'pnpc-pocket-service-desk' ); ?></p>
					<p class="v"><?php echo esc_html( number_format_i18n( $total_open ) ); ?></p>
				</div>
				<div class="psd-metric">
					<p class="k"><?php echo esc_html__( 'Currently closed', 'pnpc-pocket-service-desk' ); ?></p>
					<p class="v"><?php echo esc_html( number_format_i18n( $total_closed ) ); ?></p>
				</div>
				<div class="psd-metric">
					<p class="k"><?php echo esc_html__( 'Completion rate', 'pnpc-pocket-service-desk' ); ?></p>
					<p class="v"><?php echo esc_html( $completion_pct ); ?>%</p>
				</div>
			</div>

			<?php
			/**
			 * Allow Pro (or extensions) to add additional stats blocks/cards.
			 *
			 * @param array $stats Dashboard stats array.
			 */
			do_action( 'pnpc_psd_dashboard_after_stats', $stats );
			?>
		</div>

		<div class="psd-card psd-card--alerts">
			<h2 style="margin-top:0;">
				<?php echo esc_html__( 'Alert Inbox', 'pnpc-pocket-service-desk' ); ?>
				<?php if ( $review_queue_count > 0 ) : ?>
					<small>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: count */
								__( 'Review queue: %d', 'pnpc-pocket-service-desk' ),
								absint( $review_queue_count )
							)
						);
						?>
					</small>
				<?php endif; ?>
			</h2>
			<?php if ( empty( $alerts ) ) : ?>
				<p class="psd-muted"><?php echo esc_html__( 'No alerts right now.', 'pnpc-pocket-service-desk' ); ?></p>
			<?php else : ?>
				<?php foreach ( $alerts as $a ) : ?>
					<?php
					$title = isset( $a['title'] ) ? (string) $a['title'] : '';
					$body  = isset( $a['body'] ) ? (string) $a['body'] : '';
					$url   = isset( $a['url'] ) ? (string) $a['url'] : '';
					$button_text = isset( $a['button_text'] ) ? (string) $a['button_text'] : __( 'View', 'pnpc-pocket-service-desk' );
					?>
					<div class="psd-alert">
						<p class="psd-alert-title"><?php echo esc_html( $title ); ?></p>
						<p class="psd-alert-body"><?php echo esc_html( $body ); ?></p>
						<?php if ( $url ) : ?>
							<p class="psd-alert-actions">
								<a href="<?php echo esc_url( $url ); ?>" class="button button-small">
									<?php echo esc_html( $button_text ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>

			<div class="psd-cta">
				<?php if ( $support_url ) : ?>
					<a class="button button-primary" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $support_url ); ?>">
						<?php echo esc_html__( 'Support', 'pnpc-pocket-service-desk' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

</div>
