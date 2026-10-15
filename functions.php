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
    wp_enqueue_style('loomara-theme', get_template_directory_uri() . '/assets/loomara.css', ['loomara-fonts'], '1.1.0');
    if (class_exists('WooCommerce')) {
        wp_enqueue_style('loomara-woocommerce', get_template_directory_uri() . '/assets/woocommerce.css', ['loomara-theme'], '1.2.1');
        wp_enqueue_script('wc-cart-fragments');
    }
    wp_enqueue_script('loomara-theme', get_template_directory_uri() . '/assets/loomara.js', ['jquery'], '1.1.0', true);
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

// The theme provides the collection heading; hide WooCommerce's duplicate title.
add_filter('woocommerce_show_page_title', '__return_false');

function loomara_cart_item_count() {
    return function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
}

function loomara_header_cart_markup() {
    $count = loomara_cart_item_count();
    return sprintf(
        '<a class="header-cart" href="%1$s" aria-label="%2$s"><span class="header-cart-label">Cart</span><span class="header-cart-count">%3$d</span></a>',
        esc_url(wc_get_cart_url()),
        esc_attr(sprintf(_n('View cart, %d item', 'View cart, %d items', $count, 'loomara-living'), $count)),
        $count
    );
}

function loomara_product_cart_quantity($product_id) {
    if (!function_exists('WC') || !WC()->cart) {
        return 0;
    }

    $quantity = 0;
    foreach (WC()->cart->get_cart() as $item) {
        if ((int) $item['product_id'] === (int) $product_id) {
            $quantity += (int) $item['quantity'];
        }
    }
    return $quantity;
}

function loomara_loop_cart_status_markup($product_id) {
    $quantity = loomara_product_cart_quantity($product_id);
    $class = 'loop-cart-status loop-cart-status-' . absint($product_id);
    if (!$quantity) {
        $class .= ' is-empty';
    }
    $label = $quantity ? sprintf(_n('%d in your cart', '%d in your cart', $quantity, 'loomara-living'), $quantity) : '';
    $cart_link = $quantity ? sprintf(
        '<a href="%1$s">%2$s</a>',
        esc_url(wc_get_cart_url()),
        esc_html__('View cart', 'loomara-living')
    ) : '';

    return sprintf(
        '<span class="%1$s" aria-live="polite"><span class="loop-cart-status-label"><span aria-hidden="true">✓</span> %2$s</span>%3$s</span>',
        esc_attr($class),
        esc_html($label),
        $cart_link
    );
}

function loomara_loop_cart_status() {
    global $product;
    if ($product instanceof WC_Product) {
        echo loomara_loop_cart_status_markup($product->get_id()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
add_action('woocommerce_after_shop_loop_item', 'loomara_loop_cart_status', 7);

function loomara_cart_fragments($fragments) {
    $fragments['a.header-cart'] = loomara_header_cart_markup();

    if (function_exists('WC') && WC()->cart) {
        $product_ids = [];
        foreach (WC()->cart->get_cart() as $item) {
            $product_ids[(int) $item['product_id']] = true;
        }
        foreach (array_keys($product_ids) as $product_id) {
            $fragments['span.loop-cart-status-' . $product_id] = loomara_loop_cart_status_markup($product_id);
        }
    }

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'loomara_cart_fragments');

