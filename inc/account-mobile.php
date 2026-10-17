<?php
/** Shared customer contact number for account details and India checkout. */
defined('ABSPATH') || exit;

function loomara_indian_mobile($value) {
    $digits = preg_replace('/\D/', '', (string) $value);
    if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') { $digits = substr($digits, 2); }
    return preg_match('/^[6-9][0-9]{9}$/', $digits) ? '+91'.$digits : '';
}
add_action('woocommerce_edit_account_form', function () {
    $value = get_user_meta(get_current_user_id(), 'billing_phone', true);
    if (isset($_POST['account_mobile'])) { $value = wc_clean(wp_unslash($_POST['account_mobile'])); }
    woocommerce_form_field('account_mobile', [
        'type' => 'tel', 'label' => 'Mobile number', 'autocomplete' => 'tel',
        'placeholder' => '+91 98765 43210', 'class' => ['form-row-wide'],
        'description' => 'Used for order and delivery updates. Required when placing an order. This number is not OTP-verified.',
    ], $value);
});
add_action('woocommerce_save_account_details_errors', function ($errors, $user) {
    if (!isset($_POST['account_mobile'])) { return; }
    $raw = wc_clean(wp_unslash($_POST['account_mobile']));
    if ($raw === '') { return; }
    $mobile = loomara_indian_mobile($raw);
    if (!$mobile) { $errors->add('loomara_account_mobile', 'Enter a valid 10-digit Indian mobile number, optionally prefixed with +91.'); return; }
}, 10, 2);
add_action('woocommerce_save_account_details', function ($user_id) {
    if (!isset($_POST['account_mobile'])) { return; }
    $mobile = loomara_indian_mobile(wc_clean(wp_unslash($_POST['account_mobile'])));
    $customer = new WC_Customer($user_id);
    $customer->set_billing_phone($mobile);
    $customer->save();
    if ($mobile) { update_user_meta($user_id, '_loomara_login_phone', loomara_normalize_mobile_number($mobile)); }
    else { delete_user_meta($user_id, '_loomara_login_phone'); }
});

// Classic checkout validates the submitted contact even if an account has one.
add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
    if (!loomara_indian_mobile($data['billing_phone'] ?? '')) {
        $errors->add('loomara_checkout_mobile', 'Enter a valid Indian mobile number for delivery updates.');
    }
}, 10, 2);

// Block checkout uses Store API, not classic checkout validation hooks.
add_action('woocommerce_store_api_checkout_update_order_from_request', function ($order, $request) {
    if (!loomara_indian_mobile($order->get_billing_phone())) {
        throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException('loomara_mobile_required', 'Enter a valid Indian mobile number for delivery updates.', 400);
    }
}, 10, 2);
