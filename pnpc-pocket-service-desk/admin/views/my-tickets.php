<?php

/**
 * Admin tickets list view
 *
 * @package PNPC_Pocket_Service_Desk
 */

if (! defined('ABSPATH')) {
    exit;
}

// Ensure canonical helpers are available
$helpers = defined('PNPC_PSD_PLUGIN_DIR') ? PNPC_PSD_PLUGIN_DIR . 'includes/helpers.php' : '';
if ($helpers && file_exists($helpers)) {
    require_once $helpers;
}

$current_user = wp_get_current_user();
$current_user_id = $current_user ? intval($current_user->ID) : 0;
?>
<div class="wrap pnpc-psd-admin-tickets">
    <h1><?php esc_html_e('All Tickets', 'pnpc-pocket-service-desk'); ?></h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Ticket #', 'pnpc-pocket-service-desk'); ?></th>
                <th><?php esc_html_e('Subject', 'pnpc-pocket-service-desk'); ?></th>
                <th><?php esc_html_e('User', 'pnpc-pocket-service-desk'); ?></th>
                <th><?php esc_html_e('Status', 'pnpc-pocket-service-desk'); ?></th>
                <th><?php esc_html_e('Priority', 'pnpc-pocket-service-desk'); ?></th>
                <th><?php esc_html_e('Responses', 'pnpc-pocket-service-desk'); ?></th>
                <th><?php esc_html_e('Last Response', 'pnpc-pocket-service-desk'); ?></th>
                <th><?php esc_html_e('New', 'pnpc-pocket-service-desk'); ?></th>
                <th><?php esc_html_e('Actions', 'pnpc-pocket-service-desk'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($tickets)) : ?>
                <?php foreach ($tickets as $t) :
                    $response_count = PNPC_PSD_Ticket_Response::get_count($t->id);

                    // Compute last response for display
                    $last_response = '';
                    $responses = PNPC_PSD_Ticket_Response::get_by_ticket($t->id, array('orderby' => 'created_at', 'order' => 'DESC'));
                    if (! empty($responses)) {
                        $lr = $responses[0];
                        // Use canonical formatter (will convert DB->WP-local and format per site settings)
                        if (function_exists('pnpc_psd_format_db_datetime_for_display')) {
                            $last_response = pnpc_psd_format_db_datetime_for_display($lr->created_at);
                        } else {
                            if ( function_exists( 'pnpc_psd_format_db_datetime_for_display' ) ) {
    $last_response = pnpc_psd_format_db_datetime_for_display( $lr->created_at );
} else {
    $last_response = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $lr->created_at ) );
}
                    }

                    // Determine per-admin last viewed timestamp for this admin (if any)
                    $last_view_meta = $current_user_id ? get_user_meta($current_user_id, 'pnpc_psd_ticket_last_view_' . intval($t->id), true) : '';
                    if (is_numeric($last_view_meta)) {
                        $last_view_time = intval($last_view_meta);
                    } else {
                        $last_view_time = $last_view_meta ? intval(function_exists('pnpc_psd_mysql_to_wp_local_ts') ? pnpc_psd_mysql_to_wp_local_ts($last_view_meta) : strtotime($last_view_meta)) : 0;
                    }

                    // Count new responses since last view (and not from the current admin)
                    $new_responses = 0;
                    foreach ($responses as $r) {
                        $r_time = function_exists('pnpc_psd_mysql_to_wp_local_ts') ? intval(pnpc_psd_mysql_to_wp_local_ts($r->created_at)) : intval(strtotime($r->created_at));
                        if ($r_time > $last_view_time && intval($r->user_id) !== $current_user_id) {
                            $new_responses++;
                        }
                    }

                    // Prepare admin ticket detail URL
                    $detail_url = admin_url('admin.php?page=pnpc-service-desk-ticket&ticket_id=' . intval($t->id));
                ?>
                    <tr>
                        <td>#<?php echo esc_html($t->ticket_number); ?></td>
                        <td><?php echo esc_html($t->subject); ?></td>
                        <td><?php
                            $user = get_userdata($t->user_id);
                            echo $user ? esc_html($user->display_name . ' (' . $user->user_email . ')') : esc_html__('Unknown', 'pnpc-pocket-service-desk');
                            ?></td>
                        <td><?php echo esc_html(ucfirst($t->status)); ?></td>
                        <td><?php echo esc_html(ucfirst($t->priority)); ?></td>
                        <td>
                            <?php echo esc_html(intval($response_count)); ?>
                            <?php if (intval($response_count) > 0) : ?>
                                <span style="display:inline-block;background:#0073aa;color:#fff;padding:2px 6px;border-radius:12px;margin-left:6px;"><?php esc_html_e('Has responses', 'pnpc-pocket-service-desk'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $last_response ? esc_html($last_response) : esc_html__('—', 'pnpc-pocket-service-desk'); ?></td>

                        <td>
                            <?php if ($new_responses > 0) : ?>
                                <span class="pnpc-psd-notice-badge" style="background:#d63638;color:#fff;padding:3px 7px;border-radius:12px;font-weight:700;">
                                    <?php echo esc_html($new_responses); ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#888;"><?php esc_html_e('—', 'pnpc-pocket-service-desk'); ?></span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="<?php echo esc_url($detail_url); ?>" class="button"><?php esc_html_e('View', 'pnpc-pocket-service-desk'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="9"><?php esc_html_e('No tickets found.', 'pnpc-pocket-service-desk'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>