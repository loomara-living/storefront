<?php
/** Full colour/size gallery switching, backed by imported variation attachments. */
defined('ABSPATH') || exit;
function loomara_gallery_data($ids) {
    $images = [];
    foreach (array_slice(array_unique(array_map('absint', (array) $ids)), 0, 8) as $id) {
        $display = wp_get_attachment_image_src($id, 'woocommerce_single');
        $full = wp_get_attachment_image_src($id, 'full');
        $thumb = wp_get_attachment_image_src($id, 'woocommerce_gallery_thumbnail');
        if (!$display || !$full) { continue; }
        $images[] = ['src' => $display[0], 'full' => $full[0], 'thumb' => $thumb ? $thumb[0] : $display[0], 'width' => $display[1], 'height' => $display[2], 'alt' => (string) get_post_meta($id, '_wp_attachment_image_alt', true), 'caption' => (string) wp_get_attachment_caption($id)];
    }
    return $images;
}
add_filter('woocommerce_available_variation', function ($data, $parent, $variation) {
    $ids = $variation->get_meta('_loomara_gallery_image_ids');
    if (is_array($ids) && $ids) { $data['loomara_gallery'] = loomara_gallery_data($ids); }
    return $data;
}, 20, 3);

function loomara_render_variant_gallery() {
    global $product;
    $images = loomara_gallery_data($product->get_meta('_loomara_default_gallery_ids'));
    if (!$images) { woocommerce_show_product_images(); return; }
    $image = $images[0];
    echo '<div class="images loomara-variant-gallery" data-variant-gallery data-initial-gallery="' . esc_attr(wp_json_encode($images)) . '">';
    echo '<a class="variant-main-link" data-variant-main-link href="' . esc_url($image['full']) . '" target="_blank" rel="noopener" aria-label="Open selected product image at full size"><img data-variant-main src="' . esc_url($image['src']) . '" alt="' . esc_attr($image['alt'] ?: $product->get_name()) . '" width="' . absint($image['width']) . '" height="' . absint($image['height']) . '"></a>';
    echo '<div class="variant-thumbnails" data-variant-thumbnails aria-label="Product photographs">';
    foreach ($images as $index => $item) {
        echo '<button type="button" data-gallery-index="' . absint($index) . '" aria-label="View product image ' . absint($index + 1) . '" aria-pressed="' . ($index === 0 ? 'true' : 'false') . '"><img src="' . esc_url($item['thumb']) . '" alt="" width="72" height="72"></button>';
    }
    echo '</div><p class="variant-image-caption" data-variant-caption aria-live="polite">' . esc_html($image['caption']) . '</p><p class="variant-selection" data-variant-selection aria-live="polite"></p></div>';
}
add_action('wp', function () {
    if (!function_exists('is_product') || !is_product()) { return; }
    $product = wc_get_product(get_queried_object_id());
    if (!$product || !$product->is_type('variable') || !$product->get_meta('_loomara_default_gallery_ids')) { return; }
    remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
    add_action('woocommerce_before_single_product_summary', 'loomara_render_variant_gallery', 20);
});
add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_product') && is_product()) {
        wp_enqueue_script('loomara-variants', get_template_directory_uri() . '/assets/variants.js', ['jquery', 'wc-add-to-cart-variation'], '1.7.2', true);
    }
});
