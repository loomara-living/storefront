<?php
defined('ABSPATH') || exit;

function loomara_icon($name) {
    $paths = [
        'search' => '<circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/>',
        'user' => '<circle cx="12" cy="7" r="4.5"/><path d="M3.5 22v-2c0-4 3.8-6 8.5-6s8.5 2 8.5 6v2"/>',
        'cart' => '<path d="M5 7h14c2 0 2.7 1.5 2.4 3.5l-1 8c-.2 1.8-1.4 2.5-3.4 2.5H7c-2 0-3.2-.7-3.4-2.5l-1-8C2.3 8.5 3 7 5 7Z"/><path d="M8 7V5a4 4 0 0 1 8 0v2"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? '') . '</svg>';
}

function loomara_header_state() {
    nocache_headers();
    if (function_exists('WC') && !WC()->cart) {
        wc_load_cart();
    }
    wp_send_json_success(['signedIn' => is_user_logged_in(), 'count' => loomara_cart_item_count()]);
}
add_action('wp_ajax_loomara_header_state', 'loomara_header_state');
add_action('wp_ajax_nopriv_loomara_header_state', 'loomara_header_state');
add_action('template_redirect', function () {
    if (is_user_logged_in()) {
        if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
        nocache_headers();
    }
});

add_filter('loop_shop_per_page', function () { return 8; }, 99);
add_filter('loop_shop_columns', function () { return 4; }, 99);

function loomara_collection_selection() {
    $value = isset($_GET['collection']) ? sanitize_key(wp_unslash($_GET['collection'])) : 'all';
    return in_array($value, ['all', 'best-sellers', 'sale'], true) ? $value : 'all';
}

add_action('woocommerce_product_query', function ($query) {
    $selection = loomara_collection_selection();
    if ($selection === 'sale') {
        $query->set('post__in', wc_get_product_ids_on_sale() ?: [0]);
    } elseif ($selection === 'best-sellers') {
        $meta = (array) $query->get('meta_query');
        $meta[] = ['key' => 'total_sales', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC'];
        $query->set('meta_query', $meta);
        $query->set('meta_key', 'total_sales');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC');
    }
});

function loomara_shop_discovery() {
    $selection = loomara_collection_selection();
    echo '<div class="shop-discovery"><nav class="collection-tabs" aria-label="Product collections">';
    foreach (['all' => 'All pieces', 'best-sellers' => 'Best sellers', 'sale' => 'Sale'] as $key => $label) {
        $args = ['collection' => $key];
        if (is_search()) { $args['s'] = get_search_query(); $args['post_type'] = 'product'; }
        $url = add_query_arg($args, is_search() ? home_url('/') : (is_product_taxonomy() ? get_term_link(get_queried_object()) : wc_get_page_permalink('shop')));
        echo '<a href="' . esc_url($url) . '"' . ($selection === $key ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a>';
    }
    echo '</nav><form id="collection-search" class="collection-search" role="search" method="get" action="' . esc_url(home_url('/')) . '"><label class="sr-only" for="shop-product-search">Search products</label><input id="shop-product-search" name="s" type="search" placeholder="Search the collection" value="' . esc_attr(get_search_query()) . '" required><input type="hidden" name="post_type" value="product"><button type="submit" aria-label="Search products">' . loomara_icon('search') . '</button></form></div>';
}

function loomara_home_collection($selection) {
    $args = ['status' => 'publish', 'limit' => 8, 'return' => 'ids', 'visibility' => 'visible'];
    if ($selection === 'best-sellers') {
        $ids = get_posts([
            'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 8,
            'fields' => 'ids', 'meta_key' => 'total_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC',
            'meta_query' => [['key' => 'total_sales', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC']],
            'tax_query' => WC()->query->get_tax_query(),
        ]);
    } elseif ($selection === 'sale') {
        $args['include'] = wc_get_product_ids_on_sale() ?: [0];
        $ids = wc_get_products($args);
    } else {
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        $ids = wc_get_products($args);
    }
    if ($ids) {
        echo do_shortcode('[products ids="' . implode(',', array_map('absint', $ids)) . '" columns="4" orderby="post__in"]');
    } else {
        $text = $selection === 'sale' ? 'Good things are worth waiting for. Our next sale will appear here.' : 'Our customer favourites are still taking shape. Explore the collection and find your own.';
        echo '<div class="collection-empty"><p>' . esc_html($text) . '</p><a class="text-link" href="' . esc_url(wc_get_page_permalink('shop')) . '">Explore all pieces →</a></div>';
    }
}

function loomara_top_rated_collection() {
    $products = wc_get_products(['status' => 'publish', 'visibility' => 'visible', 'limit' => 8, 'orderby' => 'rating', 'order' => 'DESC']);
    $ids = [];
    foreach ($products as $product) {
        // Reviews were deliberately unpublished. Never relabel admin samples as
        // genuine ratings or silently bring them back through a shortcode.
        if (get_comments(['post_id' => $product->get_id(), 'status' => 'approve', 'type' => 'review', 'count' => true, 'meta_query' => [['key' => 'verified', 'value' => 1], ['key' => 'rating', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC']]])) {
            $ids[] = $product->get_id();
        }
    }
    if ($ids) {
        echo do_shortcode('[products ids="' . implode(',', array_map('absint', $ids)) . '" columns="4" orderby="post__in"]');
    } else {
        echo '<div class="collection-empty"><span class="rating-outline" aria-hidden="true">☆ ☆ ☆ ☆ ☆</span><h3>Real homes. Honest favourites.</h3><p>Top-rated pieces will appear here when genuine customer reviews are available. For now, discover the layers that feel right for your home.</p><a class="text-link" href="' . esc_url(wc_get_page_permalink('shop')) . '">Find your favourite →</a></div>';
    }
}
