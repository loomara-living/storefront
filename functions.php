<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/inc/store-setup.php';
require_once get_template_directory() . '/inc/form-compatibility.php';
require_once get_template_directory() . '/inc/cashfree-widget.php';
require_once get_template_directory() . '/inc/customer-experience.php';
require_once get_template_directory() . '/inc/discovery.php';
require_once get_template_directory() . '/inc/shopping-experience.php';
require_once get_template_directory() . '/inc/address-book.php';
require_once get_template_directory() . '/inc/magic-address-sync.php';
require_once get_template_directory() . '/inc/product-promotions.php';
require_once get_template_directory() . '/inc/product-sharing.php';
require_once get_template_directory() . '/inc/variant-gallery.php';
require_once get_template_directory() . '/inc/inventory-release.php';
require_once get_template_directory() . '/inc/category-colours.php';
require_once get_template_directory() . '/inc/catalog-browser.php';
require_once get_template_directory() . '/inc/catalog-navigation.php';
require_once get_template_directory() . '/inc/merchant-schema.php';
require_once get_template_directory() . '/inc/launch-experience.php';
require_once get_template_directory() . '/inc/google-login.php';
require_once get_template_directory() . '/inc/account-mobile.php';
require_once get_template_directory() . '/inc/email-otp.php';
require_once get_template_directory() . '/inc/account-profile.php';
require_once get_template_directory() . '/inc/offer-banner.php';
require_once get_template_directory() . '/inc/catalog-repair.php';
require_once get_template_directory() . '/inc/store-refinements.php';
require_once get_template_directory() . '/inc/verified-ratings.php';
require_once get_template_directory() . '/inc/billing-address.php';
require_once get_template_directory() . '/inc/catalogue-186.php';
require_once get_template_directory() . '/inc/cart-account-187.php';
require_once get_template_directory() . '/inc/orders-delivery-188.php';
require_once get_template_directory() . '/inc/experience-190.php';
require_once get_template_directory() . '/inc/testimonials-190.php';
require_once get_template_directory() . '/inc/cart-discovery-190.php';
require_once get_template_directory() . '/inc/catalogue-190.php';
require_once get_template_directory() . '/inc/order-safety-194.php';
require_once get_template_directory() . '/inc/customer-payment-emails.php';
require_once get_template_directory() . '/inc/account-order-references.php';
require_once get_template_directory() . '/inc/order-coupon-details.php';
require_once get_template_directory() . '/inc/checkout-identity-117.php';
require_once get_template_directory() . '/inc/shiprocket-connection-195.php';
require_once get_template_directory() . '/inc/shiprocket-status-196.php';
require_once get_template_directory() . '/inc/pin-autofill-197.php';
require_once get_template_directory() . '/inc/package-dimensions-197.php';
require_once get_template_directory() . '/inc/fulfilment-statuses-199.php';
require_once get_template_directory() . '/inc/automatic-refunds-110.php';
require_once get_template_directory() . '/inc/cancellation-refund-references.php';
require_once get_template_directory() . '/inc/cancellation-request-124.php';
require_once get_template_directory() . '/inc/processing-age-cancellation.php';
require_once get_template_directory() . '/inc/returns.php';
require_once get_template_directory() . '/inc/delivery-lifecycle-111.php';
require_once get_template_directory() . '/inc/shipped-tracking-112.php';
require_once get_template_directory() . '/inc/checkout-offers-113.php';
require_once get_template_directory() . '/inc/product-delivery-113.php';
require_once get_template_directory() . '/inc/pending-payment-114.php';
require_once get_template_directory() . '/inc/payment-failures-120.php';
require_once get_template_directory() . '/inc/abandoned-reminder.php';
require_once get_template_directory() . '/inc/payment-failure-setup-120.php';
require_once get_template_directory() . '/inc/admin-navigation.php';

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
    wp_enqueue_style('loomara-theme', get_template_directory_uri() . '/assets/loomara.css', ['loomara-fonts'], '1.8.0');
    if (class_exists('WooCommerce')) {
        wp_enqueue_style('loomara-woocommerce', get_template_directory_uri() . '/assets/woocommerce.css', ['loomara-theme'], '1.8.0');
        wp_enqueue_script('wc-cart-fragments');
        wp_enqueue_style('loomara-discovery', get_template_directory_uri() . '/assets/discovery.css', ['loomara-woocommerce'], '1.8.0');
    }
    wp_enqueue_style('loomara-refinements', get_template_directory_uri() . '/assets/refinements-v179.css', [class_exists('WooCommerce') ? 'loomara-discovery' : 'loomara-theme'], '1.8.0');
    wp_enqueue_script('loomara-theme', get_template_directory_uri() . '/assets/loomara.js', ['jquery'], '1.8.7', true);
    wp_localize_script('loomara-theme', 'loomaraStore', ['ajaxUrl' => admin_url('admin-ajax.php')]);
}
add_action('wp_enqueue_scripts', 'loomara_assets');

function loomara_menu_fallback() {
    echo '<a href="' . esc_url(home_url('/')) . '">Home</a>';
    if (class_exists('WooCommerce')) {
        echo '<a href="' . esc_url(wc_get_page_permalink('shop')) . '">Shop</a>';
        echo '<a class="nav-sale" href="' . esc_url(add_query_arg('collection', 'sale', wc_get_page_permalink('shop'))) . '">Sale <span aria-hidden="true">✦</span></a>';
    }
    echo '<a href="' . esc_url(home_url('/our-story/')) . '">Our story</a>';
    echo '<a href="' . esc_url(home_url('/contact/')) . '">Contact</a>';
}

function loomara_body_classes($classes) {
    $classes[] = 'loomara-wordpress';
    if (is_page()) {
        $page = get_queried_object();
        if ($page instanceof WP_Post) {
            $classes[] = 'loomara-page-' . sanitize_html_class($page->post_name);
        }
    }
    return $classes;
}
add_filter('body_class', 'loomara_body_classes');

function loomara_product_category_url($slug) {
    if (!taxonomy_exists('product_cat')) {
        return home_url('/');
    }
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) {
        return class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/');
    }
    $url = get_term_link($term, 'product_cat');
    return is_wp_error($url) ? wc_get_page_permalink('shop') : $url;
}

// The theme provides the collection heading; hide WooCommerce's duplicate title.
add_filter('woocommerce_show_page_title', '__return_false');

function loomara_cart_item_count() {
    return function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
}

function loomara_header_cart_markup() {
    $count = loomara_cart_item_count();
    return sprintf(
        '<a class="header-cart header-icon" href="%1$s" aria-label="%2$s">' . loomara_icon('cart') . '<span class="header-cart-count" aria-hidden="true">%3$d</span></a>',
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
