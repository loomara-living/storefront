<?php
/** Product quantity controls use WooCommerce's authenticated native Store API. */
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts',function(){
    if(!function_exists('is_product')||!is_product())return;
    wp_enqueue_style('loomara-product-quantity',get_template_directory_uri().'/assets/product-cart-quantity.css',['loomara-cart-account'],'1');
    wp_enqueue_script('loomara-product-quantity',get_template_directory_uri().'/assets/product-cart-quantity.js',['jquery','loomara-cart-account'],'1',true);
    wp_localize_script('loomara-product-quantity','loomaraProductQuantity',['cartEndpoint'=>esc_url_raw(rest_url('wc/store/v1/cart')),'cartUrl'=>wc_get_cart_url()]);
    wp_enqueue_style('loomara-product-choices',get_template_directory_uri().'/assets/product-choices.css',['loomara-product-quantity'],'1');
    wp_enqueue_script('loomara-product-choices',get_template_directory_uri().'/assets/product-choices.js',['jquery','wc-add-to-cart-variation'],'1',true);
});
