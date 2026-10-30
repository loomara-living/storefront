<?php
/** Presentation only: bounded galleries, no variation/price/cart mutations. */
defined('ABSPATH') || exit;
function loomara_card_gallery_thumbnail() {
    global $product;
    if (!$product) { return; }
    $ids = $product->get_meta('_loomara_default_gallery_ids');
    if (!$ids) { $ids = $product->get_gallery_image_ids(); }
    $ids = apply_filters('loomara_card_gallery_ids', (array) $ids, $product);
    $images = [];
    foreach (array_slice(array_unique(array_merge([$product->get_image_id()], $ids)), 0, 4) as $id) {
        $src = wp_get_attachment_image_src(absint($id), 'woocommerce_thumbnail');
        if ($src) { $images[] = ['src'=>$src[0], 'srcset'=>wp_get_attachment_image_srcset(absint($id), 'woocommerce_thumbnail') ?: '']; }
    }
    echo '<span class="loomara-card-photos"' . (count($images)>1 ? ' data-card-photos="'.esc_attr(wp_json_encode($images)).'"' : '') . '>';
    echo woocommerce_get_product_thumbnail(); // Native escaped WooCommerce image markup.
    echo '</span>';
}
add_action('wp', function () {
    if (!function_exists('is_shop') || !(is_front_page() || is_shop() || is_product_taxonomy())) { return; }
    remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
    add_action('woocommerce_before_shop_loop_item_title', 'loomara_card_gallery_thumbnail', 10);
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('loomara-soft-motion', get_template_directory_uri().'/assets/soft-motion.css', ['loomara-theme'], '1.0.0');
    if (function_exists('is_shop') && (is_front_page() || is_shop() || is_product_taxonomy())) {
        wp_enqueue_script('loomara-card-motion', get_template_directory_uri().'/assets/card-motion.js', [], filemtime(__DIR__.'/../assets/card-motion.js'), true);
    }
});
