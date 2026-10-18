<?php
defined('ABSPATH') || exit;
/** Remove only the product-page affordability advert, never checkout payment methods. */
function loomara_remove_product_emi_promotion() {
    if (!function_exists('is_product') || !is_product()) { return; }
    foreach ([
        ['woocommerce_before_single_product', 'trigger_affordability_widget'],
        ['woocommerce_before_add_to_cart_form', 'addAffordabilityWidgetHTML'],
    ] as $callback) {
        $priority = has_action($callback[0], $callback[1]);
        if ($priority !== false) { remove_action($callback[0], $callback[1], $priority); }
    }
}
add_action('wp', 'loomara_remove_product_emi_promotion', 100);
