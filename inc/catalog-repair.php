<?php
/** Restore registered product routes without touching product/order records. */
defined('ABSPATH') || exit;
add_action('wp_loaded', function () {
    if (get_option('loomara_product_routes_180') || !class_exists('WooCommerce') || !post_type_exists('product')) { return; }
    // Run after plugins have registered all post types/taxonomies. A soft flush
    // replaces only WordPress's generated rewrite rules, not .htaccess files.
    if (!add_option('loomara_route_repair_lock', time(), '', false)) { return; }
    try {
        flush_rewrite_rules(false);
        if (get_option('rewrite_rules')) { update_option('loomara_product_routes_180', time(), false); }
    } finally { delete_option('loomara_route_repair_lock'); }
}, 99);
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('loomara-catalog-repair', get_template_directory_uri().'/assets/catalog-layout-v180.css', ['loomara-refinements'], '1.8.0');
});
