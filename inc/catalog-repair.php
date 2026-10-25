<?php
/** Restore registered product routes without touching product/order records. */
defined('ABSPATH') || exit;
// Hostinger Ecommerce (Beta) registers this exact route for its separate
// non-WooCommerce catalogue. On this WooCommerce storefront its rule resolves
// Woo product slugs as Hostinger IDs, then forces a 404. Keep the URL unchanged
// while resolving it through WooCommerce's native product query variable.
add_filter('rewrite_rules_array', function ($rules) {
    if (!class_exists('WooCommerce')) { return $rules; }
    $route = '^product/([^/]+)/?$';
    if (isset($rules[$route]) && strpos($rules[$route], 'hostinger_product_id=') !== false) {
        $rules[$route] = 'index.php?product=$matches[1]';
    }
    return $rules;
}, 99);
add_action('wp_loaded', function () {
    if (get_option('loomara_product_routes_181') || !class_exists('WooCommerce') || !post_type_exists('product')) { return; }
    // Run after plugins have registered all post types/taxonomies. A soft flush
    // replaces only WordPress's generated rewrite rules, not .htaccess files.
    if (!add_option('loomara_route_repair_lock', time(), '', false)) { return; }
    try {
        flush_rewrite_rules(false);
        if (get_option('rewrite_rules')) { update_option('loomara_product_routes_181', time(), false); }
    } finally { delete_option('loomara_route_repair_lock'); }
}, 99);
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('loomara-catalog-repair', get_template_directory_uri().'/assets/catalog-layout-v183.css', ['loomara-refinements'], '1.8.3.2');
});
