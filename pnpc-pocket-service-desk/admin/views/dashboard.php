<?php
/**
 * Admin dashboard view.
 *
 * @package PNPC_Pocket_Service_Desk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_pro = function_exists( 'pnpc_psd_is_pro_active' ) && pnpc_psd_is_pro_active();

$upgrade_url = apply_filters( 'pnpc_psd_upgrade_url', '' );
$support_url = apply_filters( 'pnpc_psd_support_url', '' );

$logo_url = apply_filters( 'pnpc_psd_dashboard_logo_url', '' );
$logo_alt = apply_filters( 'pnpc_psd_dashboard_logo_alt', 'PNPC' );

$alerts = apply_filters( 'pnpc_psd_dashboard_alerts', array() );
if ( ! is_array( $alerts ) ) {
	$alerts = array();
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
	<style>
		.pnpc-psd-dashboard .psd-topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin:14px 0 18px;}
		.pnpc-psd-dashboard .psd-brand{display:flex;align-items:center;gap:12px;}
		.pnpc-psd-dashboard .psd-logo{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#fff;border:1px solid #dcdcde;box-shadow:0 1px 1px rgba(0,0,0,.04);}
		.pnpc-psd-dashboard .psd-logo img{max-width:100%;max-height:100%;display:block;}
		.pnpc-psd-dashboard .psd-logo--placeholder{font-weight:700;color:#1d2327;}
		.pnpc-psd-dashboard .psd-title{margin:0;font-size:22px;line-height:1.2;}
		.pnpc-psd-dashboard .psd-subtitle{margin:2px 0 0;color:#646970;}
		.pnpc-psd-dashboard .psd-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
		.pnpc-psd-dashboard .psd-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:14px;}
		.pnpc-psd-dashboard .psd-card{grid-column:span 12;background:#fff;border:1px solid #dcdcde;border-radius:14px;padding:14px 16px;box-shadow:0 1px 1px rgba(0,0,0,.04);}
		@media (min-width: 900px){
			.pnpc-psd-dashboard .psd-card--stats{grid-column:span 8;}
			.pnpc-psd-dashboard .psd-card--alerts{grid-column:span 4;}
		}
		.pnpc-psd-dashboard .psd-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:12px;}
		@media (max-width: 900px){ .pnpc-psd-dashboard .psd-metrics{grid-template-columns:1fr;}}
		.pnpc-psd-dashboard .psd-metric{border:1px solid #e5e5e5;border-radius:12px;padding:12px;}
		.pnpc-psd-dashboard .psd-metric .k{color:#646970;font-size:12px;margin:0 0 6px;}
		.pnpc-psd-dashboard .psd-metric .v{font-size:20px;font-weight:700;margin:0;}
		.pnpc-psd-dashboard .psd-split{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
		.pnpc-psd-dashboard .psd-rings{display:flex;gap:14px;flex-wrap:wrap;align-items:center;}
		.pnpc-psd-dashboard .psd-ring{--p:0;--size:92px;--th:10px;width:var(--size);height:var(--size);border-radius:999px;position:relative;display:grid;place-items:center;background:conic-gradient(currentColor calc(var(--p)*1%), #e5e5e5 0);color:#2271b1;}
		.pnpc-psd-dashboard .psd-ring::before{content:"";position:absolute;inset:var(--th);border-radius:999px;background:#fff;}
		.pnpc-psd-dashboard .psd-ring .psd-ring__label{position:relative;text-align:center;}
		.pnpc-psd-dashboard .psd-ring .psd-ring__value{display:block;font-size:20px;font-weight:800;line-height:1;}
		.pnpc-psd-dashboard .psd-ring .psd-ring__caption{display:block;margin-top:4px;font-size:11px;color:#646970;max-width:86px;}
		.pnpc-psd-dashboard .psd-alert{border:1px solid #e5e5e5;border-radius:12px;padding:12px;margin:0 0 10px;}
		.pnpc-psd-dashboard .psd-alert:last-child{margin-bottom:0;}
		.pnpc-psd-dashboard .psd-alert-title{margin:0 0 4px;font-weight:700;}
		.pnpc-psd-dashboard .psd-alert-body{margin:0;color:#646970;}
		.pnpc-psd-dashboard .psd-cta{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:10px;}
		.pnpc-psd-dashboard .psd-muted{color:#646970;}
	</style>

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
					<?php echo esc_html( $is_pro ? __( 'Priority Support', 'pnpc-pocket-service-desk' ) : __( 'Support', 'pnpc-pocket-service-desk' ) ); ?>
				</a>
			<?php else : ?>
				<span class="psd-muted"><?php echo esc_html__( 'Support link not configured.', 'pnpc-pocket-service-desk' ); ?></span>
			<?php endif; ?>

			<?php if ( ! $is_pro ) : ?>
				<?php if ( $upgrade_url ) : ?>
					<a class="button button-secondary" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $upgrade_url ); ?>">
						<?php echo esc_html__( 'Upgrade to Pro', 'pnpc-pocket-service-desk' ); ?>
					</a>
				<?php else : ?>
					<span class="psd-muted"><?php echo esc_html__( 'Upgrade link not configured.', 'pnpc-pocket-service-desk' ); ?></span>
				<?php endif; ?>
			<?php endif; ?>
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
			<h2 style="margin-top:0;"><?php echo esc_html__( 'Alert Inbox', 'pnpc-pocket-service-desk' ); ?></h2>
			<?php if ( empty( $alerts ) ) : ?>
				<p class="psd-muted"><?php echo esc_html__( 'No alerts right now.', 'pnpc-pocket-service-desk' ); ?></p>
			<?php else : ?>
				<?php foreach ( $alerts as $a ) : ?>
					<?php
					$title = isset( $a['title'] ) ? (string) $a['title'] : '';
					$body  = isset( $a['body'] ) ? (string) $a['body'] : '';
					?>
					<div class="psd-alert">
						<p class="psd-alert-title"><?php echo esc_html( $title ); ?></p>
						<p class="psd-alert-body"><?php echo esc_html( $body ); ?></p>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>

			<div class="psd-cta">
				<?php if ( $support_url ) : ?>
					<a class="button button-primary" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $support_url ); ?>">
						<?php echo esc_html( $is_pro ? __( 'Priority Support', 'pnpc-pocket-service-desk' ) : __( 'Get Support', 'pnpc-pocket-service-desk' ) ); ?>
					</a>
				<?php endif; ?>

				<?php if ( ! $is_pro && $upgrade_url ) : ?>
					<a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $upgrade_url ); ?>">
						<?php echo esc_html__( 'Upgrade for more', 'pnpc-pocket-service-desk' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<script>
	(function(){
		function animateRing(el){
			var target = parseInt(el.getAttribute('data-target')||'0',10);
			target = Math.max(0, Math.min(100, target));
			var numEl = el.querySelector('.psd-ring__num');
			var start = 0;
			var dur = 650;
			var t0 = null;
			function step(ts){
				if(!t0) t0 = ts;
				var p = Math.min(1, (ts - t0)/dur);
				var val = Math.round(start + (target-start)*p);
				el.style.setProperty('--p', val);
				if(numEl){ numEl.textContent = val; }
				if(p < 1){ requestAnimationFrame(step); }
			}
			requestAnimationFrame(step);
		}
		var rings = document.querySelectorAll('.pnpc-psd-dashboard .psd-ring');
		rings.forEach(function(r){ animateRing(r); });
	})();
	</script>
</div>
