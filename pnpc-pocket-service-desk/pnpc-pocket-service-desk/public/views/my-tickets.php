<?php

/**
 * Public my tickets view
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php
// Current tab is provided by the shortcode renderer; default to 'open'.
$tab = isset($tab) ? (string) $tab : 'open';
if (! in_array($tab, array('open', 'closed'), true)) {
	$tab = 'open';
}

$sort = isset( $sort ) ? (string) $sort : 'latest';
if ( ! in_array( $sort, array( 'latest', 'newest', 'oldest', 'unread' ), true ) ) {
	$sort = 'latest';
}

$base_url = function_exists('get_permalink') ? get_permalink() : home_url('/');
$open_url = add_query_arg( array( 'pnpc_psd_tab' => 'open', 'pnpc_psd_sort' => $sort ), $base_url );
$closed_url = add_query_arg( array( 'pnpc_psd_tab' => 'closed', 'pnpc_psd_sort' => $sort ), $base_url );
?>

<div class="pnpc-psd-my-tickets" id="pnpc-psd-my-tickets" data-tab="<?php echo esc_attr($tab); ?>" data-sort="<?php echo esc_attr( $sort ); ?>" data-page="<?php echo esc_attr( isset( $current_page ) ? absint( $current_page ) : 1 ); ?>">
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
		<label for="pnpc-psd-my-tickets-sort" style="font-size:13px;opacity:0.85;">
			<?php esc_html_e( 'Sort:', 'pnpc-pocket-service-desk' ); ?>
		</label>
		<select id="pnpc-psd-my-tickets-sort" class="pnpc-psd-select" style="max-width:200px;" onchange="window.location=this.value;">
			<?php
			$sort_urls = array(
				'latest' => add_query_arg( array( 'pnpc_psd_tab' => $tab, 'pnpc_psd_sort' => 'latest' ), $base_url ),
				'newest' => add_query_arg( array( 'pnpc_psd_tab' => $tab, 'pnpc_psd_sort' => 'newest' ), $base_url ),
				'oldest' => add_query_arg( array( 'pnpc_psd_tab' => $tab, 'pnpc_psd_sort' => 'oldest' ), $base_url ),
				'unread' => add_query_arg( array( 'pnpc_psd_tab' => $tab, 'pnpc_psd_sort' => 'unread' ), $base_url ),
			);
			?>
			<option value="<?php echo esc_url( $sort_urls['latest'] ); ?>" <?php selected( $sort, 'latest' ); ?>><?php esc_html_e( 'Latest activity', 'pnpc-pocket-service-desk' ); ?></option>
			<option value="<?php echo esc_url( $sort_urls['unread'] ); ?>" <?php selected( $sort, 'unread' ); ?>><?php esc_html_e( 'Unread first', 'pnpc-pocket-service-desk' ); ?></option>
			<option value="<?php echo esc_url( $sort_urls['newest'] ); ?>" <?php selected( $sort, 'newest' ); ?>><?php esc_html_e( 'Newest first', 'pnpc-pocket-service-desk' ); ?></option>
			<option value="<?php echo esc_url( $sort_urls['oldest'] ); ?>" <?php selected( $sort, 'oldest' ); ?>><?php esc_html_e( 'Oldest first', 'pnpc-pocket-service-desk' ); ?></option>
		</select>
		<button type="button" class="pnpc-psd-button pnpc-psd-button-small" id="pnpc-psd-my-tickets-refresh">
			<?php esc_html_e('Refresh', 'pnpc-pocket-service-desk'); ?>
		</button>
		<span class="pnpc-psd-help-text" id="pnpc-psd-my-tickets-status" style="opacity:0.75;"></span>
	</div>

	<?php include PNPC_PSD_PLUGIN_DIR . 'public/views/my-tickets-list.php'; ?>
</div>