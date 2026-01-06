<?php

/**
 * Public services/products view (standalone).
 *
 * Two display modes:
 *  - Display Public Products (free): shows general published products
 *  - User-specific Products (Pro): shows only products allocated to the viewing user
 * User-specific option takes precedence when enabled.
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if (! defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id      = ! empty($current_user->ID) ? (int) $current_user->ID : 0;

// Options:
// pnpc_psd_show_products            => Display Public Products (free)
// pnpc_psd_user_specific_products  => Enable User-specific Products (Pro)
$display_public = (bool) get_option('pnpc_psd_show_products', 1);
$user_specific  = (bool) get_option('pnpc_psd_user_specific_products', 0);

// If both are disabled, short-circuit.
if (! $display_public && ! $user_specific) {
    echo '<div class="pnpc-psd-services"><p class="pnpc-psd-help-text">';
    esc_html_e('Products are not available at this time.', 'pnpc-pocket-service-desk');
    echo '</p></div>';
    return;
}

// WooCommerce must be active to resolve product objects.
if (! class_exists('WooCommerce')) {
    echo '<div class="pnpc-psd-services"><p class="pnpc-psd-help-text">';
    esc_html_e('Products are not available because WooCommerce is not active.', 'pnpc-pocket-service-desk');
    echo '</p></div>';
    return;
}

$products = array();

// If user-specific (pro) is enabled, show allocated products only (takes precedence).
if ($user_specific) {
    if (! $user_id) {
        echo '<div class="pnpc-psd-services"><p class="pnpc-psd-help-text">';
        esc_html_e('Please log in to view services allocated to your account.', 'pnpc-pocket-service-desk');
        echo '</p></div>';
        return;
    }

    $allocated = get_user_meta($user_id, 'pnpc_psd_allocated_products', true);
    $ids       = array();

    if (! empty($allocated)) {
        $ids = array_filter(array_map('absint', array_map('trim', explode(',', (string) $allocated))));
        $ids = array_values(array_unique($ids));
    }

    if (empty($ids)) {
        echo '<div class="pnpc-psd-services"><p class="pnpc-psd-help-text">';
        esc_html_e('No services have been allocated to your account. Please contact an administrator for access.', 'pnpc-pocket-service-desk');
        echo '</p></div>';
        return;
    }

    $products = wc_get_products(array(
        'include' => $ids,
        'status'  => 'publish',
        'limit'   => -1,
    ));

    if (empty($products)) {
        echo '<div class="pnpc-psd-services"><p class="pnpc-psd-help-text">';
        esc_html_e('Allocated services are not available (they may have been unpublished). Please contact an administrator.', 'pnpc-pocket-service-desk');
        echo '</p></div>';
        return;
    }
} else {
    // Display public products (free mode).
    $products = wc_get_products(array(
        'status' => 'publish',
        'limit'  => 12,
    ));

    if (empty($products)) {
        echo '<div class="pnpc-psd-services"><p class="pnpc-psd-help-text">';
        esc_html_e('No services are available at this time.', 'pnpc-pocket-service-desk');
        echo '</p></div>';
        return;
    }
}
?>
<div class="pnpc-psd-services">
    <h3><?php esc_html_e('Services', 'pnpc-pocket-service-desk'); ?></h3>

    <div class="pnpc-psd-services-list" style="display:flex;gap:16px;flex-wrap:wrap;">
        <?php foreach ($products as $product) :
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
        <?php endforeach; ?>
    </div>
</div>