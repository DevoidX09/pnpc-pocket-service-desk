<?php

/**
 * Public my tickets view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if (! defined('ABSPATH')) {
	exit;
}
?>

<?php
// Current tab is provided by the shortcode renderer; default to 'open'.
$tab = isset($tab) ? (string) $tab : 'open';
if (! in_array($tab, array('open', 'closed'), true)) {
	$tab = 'open';
}

$base_url = function_exists('get_permalink') ? get_permalink() : home_url('/');
$open_url = add_query_arg('pnpc_psd_tab', 'open', $base_url);
$closed_url = add_query_arg('pnpc_psd_tab', 'closed', $base_url);
?>

<div class="pnpc-psd-my-tickets" id="pnpc-psd-my-tickets" data-tab="<?php echo esc_attr($tab); ?>">
	<h2><?php esc_html_e('My Support Tickets', 'pnpc-pocket-service-desk'); ?></h2>

	<div class="pnpc-psd-my-tickets-tabs" style="display:flex;gap:12px;align-items:center;margin:8px 0 12px;flex-wrap:wrap;">
		<a href="<?php echo esc_url($open_url); ?>" class="pnpc-psd-my-tickets-tab<?php echo ('open' === $tab) ? ' is-active' : ''; ?>" style="text-decoration:none;<?php echo ('open' === $tab) ? 'font-weight:600;' : ''; ?>">
			<?php esc_html_e('Open', 'pnpc-pocket-service-desk'); ?>
		</a>
		<span style="opacity:0.35;">|</span>
		<a href="<?php echo esc_url($closed_url); ?>" class="pnpc-psd-my-tickets-tab<?php echo ('closed' === $tab) ? ' is-active' : ''; ?>" style="text-decoration:none;<?php echo ('closed' === $tab) ? 'font-weight:600;' : ''; ?>">
			<?php esc_html_e('Closed', 'pnpc-pocket-service-desk'); ?>
		</a>
	</div>

	<div class="pnpc-psd-my-tickets-toolbar" style="display:flex;gap:10px;align-items:center;margin:10px 0 14px;flex-wrap:wrap;">
		<button type="button" class="pnpc-psd-button pnpc-psd-button-small" id="pnpc-psd-my-tickets-refresh">
			<?php esc_html_e('Refresh', 'pnpc-pocket-service-desk'); ?>
		</button>
		<span class="pnpc-psd-help-text" id="pnpc-psd-my-tickets-status" style="opacity:0.75;"></span>
	</div>

	<?php include PNPC_PSD_PLUGIN_DIR . 'public/views/my-tickets-list.php'; ?>
</div>