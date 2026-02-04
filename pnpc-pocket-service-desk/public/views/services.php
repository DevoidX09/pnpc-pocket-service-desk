<?php

/**
 * Placeholder view for services/integrations.
 *
 * This file is intentionally blank in the base plugin.
 */

 * Public services view (standalone).
 *
 * Display mode:
 *  - Display Public Products: shows general published products
 *
 * @package    PNPC_Pocket_Service_Desk
 * @subpackage PNPC_Pocket_Service_Desk/public/views
 */

if (! defined('ABSPATH')) {
    exit;
}

// Option: pnpc_psd_show_products => Display Public Products
$display_public = (bool) get_option( 'pnpc_psd_show_products', 1 );

// If disabled, short-circuit.
if ( ! $display_public ) {
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

// Pagination: defaults to 4 per page (can be overridden via shortcode attribute).
$per_page = isset( $pnpc_psd_services_limit ) ? max( 1, absint( $pnpc_psd_services_limit ) ) : 4;
$paged    = isset( $pnpc_psd_services_page ) ? max( 1, absint( $pnpc_psd_services_page ) ) : 1;
$total_pages = 1;

// Display public products.
$q = new WP_Query( array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => false,
) );

$products = array();
if ( $q->have_posts() ) {
    foreach ( $q->posts as $p ) {
        $products[] = wc_get_product( (int) $p->ID );
    }
}
$total_pages = ! empty( $q->max_num_pages ) ? (int) $q->max_num_pages : 1;

if (empty($products)) {
    echo '<div class="pnpc-psd-services"><p class="pnpc-psd-help-text">';
    esc_html_e('No services are available at this time.', 'pnpc-pocket-service-desk');
    echo '</p></div>';
    return;
}

?>
<div class="pnpc-psd-services">
    <h3><?php esc_html_e('Services', 'pnpc-pocket-service-desk'); ?></h3>

    <div class="pnpc-psd-services-list">
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
            <div class="pnpc-psd-service-item">
                <h4 class="pnpc-psd-service-title"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h4>
                <div class="pnpc-psd-service-price"><?php echo wp_kses_post($price_html); ?></div>
                <p><a class="pnpc-psd-button" href="<?php echo esc_url($permalink); ?>"><?php esc_html_e('View / Purchase', 'pnpc-pocket-service-desk'); ?></a></p>
            </div>
        <?php endforeach; ?>
    </div>

	<?php if ( $total_pages > 1 ) : ?>
		<div class="pnpc-psd-pagination" style="margin-top:16px;">
			<?php
			$base = remove_query_arg( 'psd_services_page' );
			echo wp_kses_post(
				paginate_links( array(
					'base'      => add_query_arg( 'psd_services_page', '%#%', $base ),
					'format'    => '',
					'current'   => $paged,
					'total'     => $total_pages,
					'prev_text' => '&laquo; ' . esc_html__( 'Previous', 'pnpc-pocket-service-desk' ),
					'next_text' => esc_html__( 'Next', 'pnpc-pocket-service-desk' ) . ' &raquo;',
				)
				)
			);
			?>
		</div>
	<?php endif; ?>
</div>