<?php

if (!defined('ABSPATH')) {
    exit;
}

function loomara_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus([
        'primary' => __('Primary navigation', 'loomara-living'),
    ]);
}
add_action('after_setup_theme', 'loomara_setup');

function loomara_assets() {
    wp_enqueue_style(
        'loomara-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap',
        [],
        null
    );
    wp_enqueue_style('loomara-theme', get_template_directory_uri() . '/assets/loomara.css', ['loomara-fonts'], '1.0.0');
    if (class_exists('WooCommerce')) {
        wp_enqueue_style('loomara-woocommerce', get_template_directory_uri() . '/assets/woocommerce.css', ['loomara-theme'], '1.0.0');
    }
    wp_enqueue_script('loomara-theme', get_template_directory_uri() . '/assets/loomara.js', [], '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'loomara_assets');

function loomara_menu_fallback() {
    echo '<a href="' . esc_url(home_url('/')) . '">Home</a>';
    if (class_exists('WooCommerce')) {
        echo '<a href="' . esc_url(wc_get_page_permalink('shop')) . '">Shop</a>';
    }
    echo '<a href="' . esc_url(home_url('/our-story/')) . '">Our story</a>';
    echo '<a href="' . esc_url(home_url('/contact/')) . '">Contact</a>';
}

function loomara_body_classes($classes) {
    $classes[] = 'loomara-wordpress';
    return $classes;
}
add_filter('body_class', 'loomara_body_classes');

/**
 * One-time WooCommerce pricing update. Removed after verification.
 */
function loomara_publish_initial_inventory() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    $update_key = 'loomara_inventory_publish_20261015';
    if (get_option($update_key)) {
        return;
    }

    update_option('woocommerce_currency', 'INR');
    $result = [];
    foreach (['LL-FB-IVORY-001', 'LL-FB-ROSE-001'] as $sku) {
        $product_id = wc_get_product_id_by_sku($sku);
        $product = $product_id ? wc_get_product($product_id) : false;
        if (!$product) {
            continue;
        }

        $product->set_regular_price('1000');
        $product->set_price('1000');
        $product->set_manage_stock(true);
        $product->set_stock_quantity(10);
        $product->set_stock_status('instock');
        $product->set_status('publish');
        $product->save();

        $result[] = [
            'id' => $product->get_id(),
            'sku' => $product->get_sku(),
            'name' => $product->get_name(),
            'status' => $product->get_status(),
            'price' => $product->get_price(),
            'stock_quantity' => $product->get_stock_quantity(),
            'currency' => get_woocommerce_currency(),
        ];
    }

    if (count($result) === 2) {
        update_option($update_key, $result, false);
    }
}
add_action('init', 'loomara_publish_initial_inventory', 20);

function loomara_register_inventory_publish_status() {
    register_rest_route('loomara/v1', '/inventory-publish-status', [
        'methods' => 'GET',
        'callback' => function () {
            return rest_ensure_response(get_option('loomara_inventory_publish_20261015', []));
        },
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'loomara_register_inventory_publish_status');

