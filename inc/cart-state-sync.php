<?php
/** Refresh Cart Blocks from the native server cart, never rebuild or mutate it. */
defined('ABSPATH') || exit;
function loomara_cart_state_sync_assets() {
    if (!function_exists('is_cart') || !is_cart()) { return; }
    wp_enqueue_script('loomara-cart-state-sync', get_template_directory_uri().'/assets/cart-state-sync.js', ['wp-data','wc-blocks-data-store'], '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'loomara_cart_state_sync_assets', 30);
