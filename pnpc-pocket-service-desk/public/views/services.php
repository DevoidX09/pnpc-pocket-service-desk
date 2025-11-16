<?php

/**
 * Public services/products view (standalone).
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if (! defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id      = ! empty($current_user->ID) ? (int) $current_user->ID : 0;

// Settings controlling services visibility
$show_products       = get_option('pnpc_psd_show_products', 1);
$show_user_products  = get_option('pnpc_psd_user_specific_products', 0);

// Backwards compatibility: support legacy premium-only flag if present
$legacy_premium = get_option('pnpc_psd_products_premium_only', null);
if ($legacy_premium !== null) {
    $show_user_products = ($show_user_products === null) ? (bool) $legacy_premium : (bool) $show_user_products;
}

// If services are disabled globally, short-circuit.
if (! $show_products) {
    echo '<div class="pnpc-psd-services"><p class="pnpc-psd-help-text">';
    esc_html_e('Services are not available at this time.', 'pnpc-pocket-service-desk');
    echo '</p></div>';
    return;
}

?>
<div class="pnpc-psd-services">
    <h3><?php esc_html_e('Services', 'pnpc-pocket-service-desk'); ?></h3>

    <?php
    // Determine which products to show:
    $allocated = $user_id ? get_user_meta($user_id, 'pnpc_psd_allocated_products', true) : '';
    $products_to_show = array();

    if ($show_user_products) {
        // User-specific mode: show allocated products for this user
        if (! empty($allocated) && class_exists('WooCommerce')) {
            $ids = array_filter(array_map('absint', array_map('trim', explode(',', (string) $allocated))));
            if (! empty($ids)) {
                $products_to_show = wc_get_products(array('include' => $ids, 'status' => 'publish', 'limit' => -1));
            }
        }
    } else {
        // Non user-specific: show a regular product set
        if (class_exists('WooCommerce')) {
            $products_to_show = wc_get_products(array('status' => 'publish', 'limit' => 6));
        }
    }

    if (! empty($products_to_show)) :
        echo '<div class="pnpc-psd-services-list" style="display:flex;gap:16px;flex-wrap:wrap;">';
        foreach ($products_to_show as $product) :
            if (is_numeric($product)) {
                $product = wc_get_product((int) $product);
            }
            if (! $product) {
                continue;
            }
            $product_id = $product->get_id();
            $permalink  = get_permalink($product_id);
            $title      = $product->get_name();
            $price_html = $product->get_price_html();
    ?>
            <div class="pnpc-psd-service-item" style="flex:0 0 220px;border:1px solid #eee;padding:12px;border-radius:6px;background:#fff;">
                <h4 style="margin:0 0 8px;"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h4>
                <div class="pnpc-psd-service-price" style="margin-bottom:8px;"><?php echo wp_kses_post($price_html); ?></div>
                <p><a class="pnpc-psd-button" href="<?php echo esc_url($permalink); ?>"><?php esc_html_e('View / Purchase', 'pnpc-pocket-service-desk'); ?></a></p>
            </div>
        <?php
        endforeach;
        echo '</div>';
    else :
        if ($show_user_products) :
        ?>
            <p class="pnpc-psd-help-text">
                <?php esc_html_e('Services available to you are currently restricted. If you believe you should have access, please contact support.', 'pnpc-pocket-service-desk'); ?>
            </p>
        <?php
        else :
        ?>
            <p class="pnpc-psd-help-text">
                <?php esc_html_e('No services are available at this time.', 'pnpc-pocket-service-desk'); ?>
            </p>
    <?php
        endif;
    endif;
    ?>
</div>