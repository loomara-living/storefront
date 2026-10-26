<?php
defined('ABSPATH') || exit;

function loomara_icon($name) {
    $paths = [
        'search' => '<circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/>',
        'user' => '<circle cx="12" cy="7" r="4.5"/><path d="M3.5 22v-2c0-4 3.8-6 8.5-6s8.5 2 8.5 6v2"/>',
        'heart' => '<path d="M20.5 4.8a5.5 5.5 0 0 0-8.5 1 5.5 5.5 0 0 0-8.5-1c-3.2 3.4-.9 7.5 2 10.3L12 21l6.5-5.9c2.9-2.8 5.2-6.9 2-10.3Z"/>',
        'cart' => '<path d="M5 7h14c2 0 2.7 1.5 2.4 3.5l-1 8c-.2 1.8-1.4 2.5-3.4 2.5H7c-2 0-3.2-.7-3.4-2.5l-1-8C2.3 8.5 3 7 5 7Z"/><path d="M8 7V5a4 4 0 0 1 8 0v2"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? '') . '</svg>';
}

function loomara_header_state() {
    nocache_headers();
    if (function_exists('WC') && !WC()->cart) {
        wc_load_cart();
    }
    $items = [];
    if (WC()->cart) { foreach (WC()->cart->get_cart() as $item) {
        $items[] = ['product' => (int)$item['product_id'], 'variation' => (int)$item['variation_id']];
    } }
    wp_send_json_success(['signedIn' => is_user_logged_in(), 'count' => loomara_cart_item_count(), 'items' => $items, 'cartUrl' => wc_get_cart_url(), 'wishlistIds' => loomara_wishlist_ids()]);
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

// Keep the results grid even when a query has a single match.
add_filter('woocommerce_redirect_single_search_result', '__return_false');

// WordPress's default search ignores product categories, tags and SKUs. Match
// each word against those fields too, without bypassing catalog visibility,
// stock filters, sale filters or native pagination in the enclosing query.
add_filter('posts_search', function ($search, $query) {
    if (is_admin() || (!$query->is_main_query() && !$query->get('loomara_catalogue_search')) || !$query->is_search()
        || !in_array('product', (array) $query->get('post_type'), true)) { return $search; }
    global $wpdb;
    $phrase = preg_replace('/\bbed[\s-]+sheets?\b/i', 'bedsheet', (string) $query->get('s'));
    $tokens = preg_split('/\s+/', trim($phrase), 6, PREG_SPLIT_NO_EMPTY);
    if (!$tokens) { return $search; }
    $clauses = [];
    foreach ($tokens as $token) {
        $variants = loomara_search_variants($token);
        $matches = [];
        foreach ($variants as $variant) {
            $like = '%' . $wpdb->esc_like($variant) . '%';
            $matches[] = $wpdb->prepare("({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR {$wpdb->posts}.post_content LIKE %s
                OR EXISTS (SELECT 1 FROM {$wpdb->term_relationships} sr JOIN {$wpdb->term_taxonomy} st ON st.term_taxonomy_id = sr.term_taxonomy_id JOIN {$wpdb->terms} sn ON sn.term_id = st.term_id WHERE sr.object_id = {$wpdb->posts}.ID AND st.taxonomy IN ('product_cat','product_tag') AND (sn.name LIKE %s OR sn.slug LIKE %s))
                OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} sm WHERE sm.post_id = {$wpdb->posts}.ID AND sm.meta_key IN ('_sku','_loomara_former_design_ids') AND sm.meta_value LIKE %s)
                OR EXISTS (SELECT 1 FROM {$wpdb->posts} sv JOIN {$wpdb->postmeta} vm ON vm.post_id = sv.ID WHERE sv.post_parent = {$wpdb->posts}.ID AND sv.post_type = 'product_variation' AND sv.post_status = 'publish' AND vm.meta_key IN ('_sku','_loomara_former_skus','attribute_colour','attribute_size') AND vm.meta_value LIKE %s))", $like, $like, $like, $like, $like, $like, $like);
        }
        $clauses[] = '(' . implode(' OR ', $matches) . ')';
    }
    $sql = ' AND (' . implode(' AND ', $clauses) . ')';
    if (!is_user_logged_in()) { $sql .= " AND ({$wpdb->posts}.post_password = '')"; }
    return $sql;
}, 20, 2);

function loomara_collection_selection() {
    $value = isset($_GET['collection']) ? sanitize_key(wp_unslash($_GET['collection'])) : 'all';
    return in_array($value, ['all', 'best-sellers', 'sale'], true) ? $value : 'all';
}

function loomara_catalog_page_banner() {
    if (!is_shop() || is_search()) { return; }
    $sale=loomara_collection_selection()==='sale';
    $asset=$sale?'sale-collection-banner.jpg':'shop-collection-banner.jpg';
    $alt=$sale?'Sale — premium home textiles at special prices. Bring home comfort and style for less.':'The Loomara Collection — Shop. Find your softer layer: bedsheets, comforters, combos and cushion covers.';
    echo '<div class="catalog-page-banner"><img src="'.esc_url(get_template_directory_uri().'/assets/'.$asset).'" width="1983" height="793" alt="'.esc_attr($alt).'" fetchpriority="high" decoding="async"></div>';
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
        if (function_exists('loomara_catalog_category')) {
            if (loomara_catalog_category()) { $args['catalog_category'] = loomara_catalog_category(); }
            if (loomara_catalog_size()) { $args['catalog_size'] = loomara_catalog_size(); }
            $args['orderby'] = loomara_catalog_order();
            foreach (['catalog_material','min_price','max_price'] as $filter) { if (loomara_catalog_value($filter) !== '') { $args[$filter] = loomara_catalog_value($filter); } }
            if (loomara_catalog_subcategory()) { $args['catalog_subcategory']=loomara_catalog_subcategory(); }
        }
        if (is_search()) { $args['s'] = get_search_query(); $args['post_type'] = 'product'; }
        $url = add_query_arg($args, is_search() ? home_url('/') : (is_product_taxonomy() ? get_term_link(get_queried_object()) : wc_get_page_permalink('shop')));
        echo '<a href="' . esc_url($url) . '"' . ($selection === $key ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a>';
    }
    echo '</nav>';
    loomara_catalog_sort_control();
    echo '</div>';
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

function loomara_top_rated_collection($ids = null) {
    if ($ids === null) { $ids = loomara_top_rated_ids(); }
    if ($ids) {
        echo do_shortcode('[products ids="' . implode(',', array_map('absint', $ids)) . '" columns="4" orderby="post__in"]');
    }
}
