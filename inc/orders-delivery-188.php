<?php
/** Customer order presentation and explicit PIN-format validation. */
defined('ABSPATH') || exit;

// New orders only: immutable native database ID prevents collisions and races.
// Historic customer-facing numbers are deliberately not rewritten.
function loomara_assign_order_reference($id, $order = false) {
    $order = $order instanceof WC_Order ? $order : wc_get_order($id);
    if (!$order || $order->get_meta('_loomara_order_reference')) { return; }
    $date = $order->get_date_created();
    $reference = 'OD-'.wp_date('Ym', $date ? $date->getTimestamp() : time(), wp_timezone()).'-'.str_pad((string)$order->get_id(), 6, '0', STR_PAD_LEFT);
    $order->update_meta_data('_loomara_order_reference', $reference);
    $order->save_meta_data();
}
add_action('woocommerce_new_order', 'loomara_assign_order_reference', 20, 2);
add_filter('woocommerce_order_number', function ($number, $order) {
    return $order->get_meta('_loomara_order_reference') ?: $number;
}, 20, 2);

function loomara_order_status_selection() {
    $raw = $_GET['order_status'] ?? 'successful';
    $value = is_scalar($raw) ? sanitize_key(wp_unslash($raw)) : '';
    return in_array($value, ['successful','all'], true) || isset(wc_get_order_statuses()['wc-'.$value]) ? $value : 'successful';
}

function loomara_order_created_datetime($date) {
    return $date ? wp_date('d M Y · g:i a', $date->getTimestamp(), new DateTimeZone('Asia/Kolkata')) . ' IST' : '';
}

function loomara_successful_order_statuses() {
    $statuses = wc_get_is_paid_statuses();
    foreach (['ready-to-ship','shipped','delivered'] as $status) {
        if (isset(wc_get_order_statuses()['wc-'.$status])) { $statuses[] = $status; }
    }
    return array_values(array_unique($statuses));
}
function loomara_account_order_query($args) {
    $selected = loomara_order_status_selection();
    $args['status'] = $selected === 'all' ? array_keys(wc_get_order_statuses()) : ($selected === 'successful' ? loomara_successful_order_statuses() : [$selected]);
    // Ownership always wins over query parameters or other presentation filters.
    $args['customer'] = get_current_user_id();
    return $args;
}
add_filter('woocommerce_my_account_my_orders_query', 'loomara_account_order_query', 100);

// Shiprocket owns live courier checks when installed. Do not remove its native
// callback or override merchant configuration. Format-only fallback is local /
// plugin-absent only and must never be presented as courier serviceability.
function loomara_pin_format_valid($pin) {
    return is_scalar($pin) && preg_match('/^[1-9][0-9]{5}$/D', trim((string)$pin)) === 1;
}
add_action('woocommerce_single_product_summary', function () {
    if (function_exists('shiprocket_show_check_pincode')) { return; }
    echo '<section class="delivery-pin" aria-labelledby="delivery-pin-title"><h2 id="delivery-pin-title">Delivery to your door</h2><label for="loomara-delivery-pin">India PIN code</label><div class="delivery-pin-controls"><input id="loomara-delivery-pin" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="6" pattern="[1-9][0-9]{5}" placeholder="e.g. 560001" aria-describedby="delivery-pin-help delivery-pin-result"><button type="button" class="button" data-check-india-pin>Check PIN</button></div><p id="delivery-pin-help">Free shipping across India. This checks the six-digit PIN format, not live courier coverage.</p><p id="delivery-pin-result" role="status" aria-live="polite"></p></section>';
}, 21);
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('loomara-orders-delivery', get_template_directory_uri().'/assets/orders-delivery-v188.css', ['loomara-cart-account'], filemtime(__DIR__.'/../assets/orders-delivery-v188.css'));
    if (!function_exists('shiprocket_show_check_pincode')) {
        wp_enqueue_script('loomara-orders-delivery', get_template_directory_uri().'/assets/orders-delivery-v188.js', [], filemtime(__DIR__.'/../assets/orders-delivery-v188.js'), true);
    }
});
