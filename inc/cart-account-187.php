<?php
/** Checkout accounts use native secure password setup; never merge by submitted email alone. */
defined('ABSPATH') || exit;

function loomara_checkout_account_request() {
    return (function_exists('is_checkout') && is_checkout())
        || (isset($_GET['wc-ajax']) && sanitize_key(wp_unslash($_GET['wc-ajax'])) === 'checkout');
}
add_filter('woocommerce_checkout_registration_enabled', '__return_true', 30);
add_filter('option_woocommerce_registration_generate_password', function ($value) {
    return loomara_checkout_account_request() ? 'yes' : $value;
});
add_filter('woocommerce_checkout_fields', function ($fields) {
    unset($fields['account']['account_password']);
    return $fields;
}, 120);
add_action('woocommerce_after_checkout_registration_form', function () {
    echo '<p class="checkout-account-tip">Create an account to keep your orders together. After your order is placed, we’ll email a secure link to set your password. Already registered? Sign in with your existing email or Google account.</p>';
});

// This marker precedes WooCommerce's immediate or queued new-account notification.
add_action('woocommerce_created_customer', function ($id) {
    if (loomara_checkout_account_request()) {
        update_user_meta($id, '_loomara_checkout_setup_pending', 'yes');
    }
}, 1);
// Guard BEFORE trigger(): even a disabled native email regenerates its reset key.
function loomara_checkout_native_account_notification($id, $data = [], $generated = false) {
    if (get_user_meta($id, '_loomara_checkout_setup_pending', true) === 'yes'
        || get_user_meta($id, '_loomara_checkout_setup_claimed', true)) { return; }
    WC()->mailer()->customer_new_account($id, $data, $generated);
}
function loomara_checkout_guard_native_mailer($mailer) {
    remove_action('woocommerce_created_customer_notification', [$mailer, 'customer_new_account'], 10);
    add_action('woocommerce_created_customer_notification', 'loomara_checkout_native_account_notification', 10, 3);
}
add_action('woocommerce_email', 'loomara_checkout_guard_native_mailer', 30);
add_action('init', function () {
    if (function_exists('WC')) { loomara_checkout_guard_native_mailer(WC()->mailer()); }
}, 100);
add_filter('woocommerce_email_enabled_customer_new_account', function ($enabled, $user) {
    $id = is_object($user) ? ($user->ID ?? 0) : 0;
    if ($id && ($GLOBALS['loomara_checkout_setup_sending'] ?? 0) !== $id
        && (get_user_meta($id, '_loomara_checkout_setup_pending', true) === 'yes'
            || get_user_meta($id, '_loomara_checkout_setup_claimed', true))) { return false; }
    return $enabled;
}, 30, 2);
function loomara_checkout_setup_email($order_id) {
    $order = wc_get_order($order_id);
    if (!$order || !$order->has_status(['processing', 'completed', 'on-hold'])) { return; }
    $id = $order->get_customer_id();
    if (!$id || get_user_meta($id, '_loomara_checkout_setup_pending', true) !== 'yes') { return; }
    // Atomically claim this email once across repeated gateway callbacks.
    if (!add_user_meta($id, '_loomara_checkout_setup_claimed', $order->get_id(), true)) { return; }
    delete_user_meta($id, '_loomara_checkout_setup_pending');
    $GLOBALS['loomara_checkout_setup_sending'] = $id;
    try { WC()->mailer()->customer_new_account($id, [], true); }
    finally { unset($GLOBALS['loomara_checkout_setup_sending']); }
}
add_action('woocommerce_checkout_order_processed', 'loomara_checkout_setup_email', 30);
add_action('woocommerce_order_status_processing', 'loomara_checkout_setup_email', 30);
add_action('woocommerce_order_status_completed', 'loomara_checkout_setup_email', 30);
add_action('woocommerce_order_status_on-hold', 'loomara_checkout_setup_email', 30);

// A completed authentication is required before claiming prior guest orders.
// Never attach an order already owned by another customer.
function loomara_link_authenticated_orders($login, $user) {
    if ($user instanceof WP_User && function_exists('wc_update_new_customer_past_orders')) {
        wc_update_new_customer_past_orders($user->ID);
    }
}
add_action('wp_login', 'loomara_link_authenticated_orders', 30, 2);
add_action('nsl_login', function ($id) {
    $user = get_userdata(absint($id));
    if ($user) { loomara_link_authenticated_orders($user->user_login, $user); }
}, 30);

add_filter('woocommerce_loop_add_to_cart_link', function ($html, $product) {
    return preg_replace('/<a\s/', '<a data-cart-product="'.absint($product->get_id()).'" ', $html, 1);
}, 120, 2);
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('loomara-cart-account', get_template_directory_uri().'/assets/cart-account-v187.css', ['loomara-store-refinements'], filemtime(__DIR__.'/../assets/cart-account-v187.css'));
    wp_enqueue_script('loomara-cart-account', get_template_directory_uri().'/assets/cart-account-v187.js', ['jquery','loomara-theme','loomara-store-refinements'], filemtime(__DIR__.'/../assets/cart-account-v187.js'), true);
});
