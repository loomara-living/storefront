<?php
/** Email-first checkout identity; no historical order reassignment. */
defined('ABSPATH') || exit;

function loomara_checkout_login_url_117() {
    return add_query_arg(['checkout-login' => '1', 'checkout-intent' => loomara_checkout_intent_117()], wc_get_page_permalink('myaccount'));
}

/** Navigation intent only, never an authentication/payment credential. */
function loomara_valid_checkout_intent_117($value) {
    return is_string($value) && preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D', $value);
}
function loomara_checkout_intent_117() {
    $value = $_GET['checkout-intent'] ?? '';
    if (loomara_valid_checkout_intent_117($value)) { return $value; }
    static $generated;
    if (!$generated) { $generated = wp_generate_uuid4(); }
    return $generated;
}
function loomara_checkout_resume_url_117($intent = '') {
    return add_query_arg('loomara-payment-intent', loomara_valid_checkout_intent_117($intent) ? $intent : loomara_checkout_intent_117(), wc_get_cart_url());
}

/** Only the browser's new-order endpoint, never gateway callbacks/services. */
function loomara_magic_identity_gate_117($result, $server, $request) {
    $route = rtrim(strtolower($request->get_route()), '/');
    if ($route !== '/1cc/v1/order/create' || strtoupper($request->get_method()) !== 'POST') { return $result; }
    if ($result !== null) { return $result; }
    $user = wp_get_current_user();
    if (!$user->ID || !is_email($user->user_email)) {
        return new WP_Error('loomara_checkout_login_required', 'Please sign in with Google or your Loomara account before checkout.', [
            'status' => 401, 'login_url' => loomara_checkout_login_url_117(),
        ]);
    }
    // Native Razorpay still performs its permission and wp_rest nonce checks.
    $GLOBALS['loomara_magic_create_117'] = (int) $user->ID;
    return $result;
}
add_filter('rest_pre_dispatch', 'loomara_magic_identity_gate_117', 100, 3);

function loomara_magic_create_owner_117($order) {
    $id = (int) ($GLOBALS['loomara_magic_create_117'] ?? 0);
    if (!$id || $id !== get_current_user_id() || !$order instanceof WC_Order) { return; }
    $user = get_user_by('id', $id);
    if (!$user || !is_email($user->user_email)) { throw new Exception('Please sign in with a valid email before checkout.'); }
    $order->set_customer_id($id);
    $order->set_billing_email($user->user_email);
    $order->update_meta_data('_loomara_checkout_owner', $id);
}
add_action('woocommerce_checkout_create_order', 'loomara_magic_create_owner_117', 90, 1);

// Standard guest checkout remains available, but a phone cannot replace email.
add_filter('woocommerce_checkout_fields', function ($fields) {
    $fields['billing']['billing_email'] = array_merge($fields['billing']['billing_email'] ?? [], [
        'type' => 'email', 'label' => 'Email address', 'required' => true,
        'priority' => 1, 'class' => ['form-row-wide'], 'validate' => ['email'],
    ]);
    return $fields;
}, 1000);
add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
    if (!is_email($data['billing_email'] ?? '')) {
        $errors->add('loomara_checkout_email_required', 'Enter a valid email address to receive and access your order.');
    }
}, 100, 2);

function loomara_checkout_login_prompt_117() {
    if (is_user_logged_in()) { return; }
    echo '<section class="loomara-checkout-identity" aria-label="Sign in for checkout"><h3>Sign in to continue to payment</h3><p>Your secure payment window opens automatically after sign-in. Your order stays linked to your account email.</p>';
    if (shortcode_exists('nextend_social_login')) {
        $button = do_shortcode('[nextend_social_login provider="google" style="fullwidth" link="0" unlink="0" customlabel="Continue with Google" redirect="'.esc_url(loomara_checkout_resume_url_117()).'"]');
        if (strpos($button, 'data-provider="google"') !== false) { echo $button; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    echo '<a class="loomara-checkout-account" href="'.esc_url(loomara_checkout_login_url_117()).'">Sign in with your Loomara account</a></section>';
}
add_action('woocommerce_proceed_to_checkout', 'loomara_checkout_login_prompt_117', 1);
add_action('woocommerce_before_customer_login_form', function () {
    if (isset($_GET['checkout-login']) && $_GET['checkout-login'] === '1') { loomara_checkout_login_prompt_117(); }
});

function loomara_checkout_auth_redirect_117($redirect, $user = null) {
    if (!isset($_POST['loomara_checkout_login']) || $_POST['loomara_checkout_login'] !== '1') { return $redirect; }
    $intent = $_POST['loomara_checkout_intent'] ?? '';
    return loomara_valid_checkout_intent_117($intent) ? loomara_checkout_resume_url_117($intent) : wc_get_cart_url();
}
add_filter('woocommerce_login_redirect', 'loomara_checkout_auth_redirect_117', 100, 2);
add_filter('woocommerce_registration_redirect', 'loomara_checkout_auth_redirect_117', 100, 2);
function loomara_checkout_auth_fields_117() {
    if (isset($_GET['checkout-login']) && $_GET['checkout-login'] === '1') {
        echo '<input type="hidden" name="loomara_checkout_login" value="1"><input type="hidden" name="loomara_checkout_intent" value="'.esc_attr(loomara_checkout_intent_117()).'">';
    }
}
add_action('woocommerce_login_form', 'loomara_checkout_auth_fields_117');
add_action('woocommerce_register_form', 'loomara_checkout_auth_fields_117');

add_action('wp_enqueue_scripts', function () {
    $logged_in = is_user_logged_in();
    $intent = $_GET['loomara-payment-intent'] ?? '';
    $resume = $logged_in && is_cart() && loomara_valid_checkout_intent_117($intent);
    wp_enqueue_style('loomara-checkout-identity', get_template_directory_uri().'/assets/checkout-identity-v117.css', [], '1.11.7');
    wp_enqueue_script('loomara-checkout-identity', get_template_directory_uri().'/assets/checkout-identity-v117.js', [], '1.11.7', false);
    wp_localize_script('loomara-checkout-identity', 'loomaraCheckoutIdentity', [
        'loginUrl' => $logged_in ? '' : loomara_checkout_login_url_117(),
        'loggedIn' => $logged_in, 'resumeIntent' => $resume ? $intent : '',
    ]);
}, 1);
