<?php
/** Combine native product specifications with the description; no catalogue writes. */
defined('ABSPATH') || exit;

function loomara_description_details_tabs($tabs) {
    unset($tabs['additional_information']);
    $tabs['description'] = array_merge($tabs['description'] ?? [], [
        'title' => __('Description', 'woocommerce'),
        'priority' => 10,
        'callback' => 'loomara_description_details_render',
    ]);
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'loomara_description_details_tabs', 99);

function loomara_description_details_render() {
    global $product;
    if (!($product instanceof WC_Product)) { return; }
    wc_get_template('single-product/tabs/description.php');
    echo '<section class="loomara-description-specifications"><h3>' . esc_html__('Product details', 'loomara-living') . '</h3>';
    wc_display_product_attributes($product);
    echo '</section>';
}

function loomara_description_details_attributes($attributes, $product) {
    if (!($product instanceof WC_Product)) { return $attributes; }
    $text = wp_strip_all_tags($product->get_description() . ' ' . $product->get_short_description());
    foreach ($attributes as $row) {
        $text .= ' ' . wp_strip_all_tags(($row['label'] ?? '') . ' ' . ($row['value'] ?? ''));
    }
    if (!preg_match('/machine[\s-]*washable|care\s*(?:instructions?|guide)/i', $text)) {
        $attributes['loomara-care'] = ['label' => 'Care instructions', 'value' => 'Machine washable'];
    }
    $id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
    if (has_term('cushion-covers', 'product_cat', $id) && !preg_match('/pack\s*(?:size|quantity)|set\s*of|pair/i', $text)) {
        $pack = $product->get_meta('_loomara_pack_quantity');
        if (!$pack && $product->is_type('variation')) { $parent = wc_get_product($id); $pack = $parent ? $parent->get_meta('_loomara_pack_quantity') : ''; }
        if (in_array((int)$pack, [2,4], true)) { $attributes['loomara-pack'] = ['label'=>'Pack size','value'=>'Set of '.(int)$pack]; }
    }
    if (!preg_match('/thread[\s_-]*count/i', $text)) {
        if (has_term('comforters', 'product_cat', $id)) {
            $attributes['loomara-thread-count'] = ['label' => 'Thread count', 'value' => 'NA'];
        } elseif (has_term('bedsheets', 'product_cat', $id)) {
            $attributes['loomara-thread-count'] = ['label' => 'Thread count', 'value' => '186'];
        }
    }
    if (isset($attributes['weight'])) { $attributes['weight']['label'] = 'Shipping weight'; }
    // Native WooCommerce values use the actual saved dimensions and weight.
    // Never invent textile measurements or override shipment data.
    return $attributes;
}
add_filter('woocommerce_display_product_attributes', 'loomara_description_details_attributes', 99, 2);
