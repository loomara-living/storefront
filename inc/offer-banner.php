<?php
/** Responsive announcement and owner-confirmed first-order offer. */
defined('ABSPATH') || exit;
function loomara_offer_banner() {
    $text = 'Launch Offer · Use WELCOME10 to get 10% additional discount on First Order. Dispatch across India within 24 hours';
    echo '<aside class="launch-strip" aria-label="Launch offer"><div class="offer-viewport"><div class="offer-track"><span>'.esc_html($text).'</span><span aria-hidden="true">'.esc_html($text).'</span></div></div><button type="button" data-offer-pause aria-label="Pause offer animation" aria-pressed="false">Pause</button></aside>';
}
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('loomara-offer-banner', get_template_directory_uri().'/assets/offer-banner.css', ['loomara-refinements'], '1.7.7');
    wp_enqueue_script('loomara-offer-banner', get_template_directory_uri().'/assets/offer-banner.js', [], '1.7.7', true);
});
// Make the advertised offer usable without silently changing an existing
// merchant-managed coupon. The fallback uses WooCommerce's virtual coupon API.
function loomara_welcome_coupon_data($data, $code) {
    if ($data || !is_string($code) || strtolower($code) !== 'welcome10' || wc_get_coupon_id_by_code($code)) { return $data; }
    return ['discount_type' => 'percent', 'amount' => 10, 'individual_use' => true, 'exclude_sale_items' => false, 'description' => '10% off the first order; sign-in required.'];
}
add_filter('woocommerce_get_shop_coupon_data', 'loomara_welcome_coupon_data', 10, 2);
function loomara_validate_welcome_offer($valid, $coupon) {
    if (strtolower($coupon->get_code()) !== 'welcome10') { return $valid; }
    if (!is_user_logged_in()) { throw new Exception('Please sign in or create an account to use your first-order offer.'); }
    $statuses = array_values(array_diff(array_keys(wc_get_order_statuses()), ['wc-cancelled', 'wc-failed', 'wc-checkout-draft']));
    $user = wp_get_current_user();
    $emails = [$user->user_email, get_user_meta($user->ID, 'billing_email', true)];
    if (WC()->customer) { $emails[] = WC()->customer->get_billing_email(); }
    $has_order = (bool) wc_get_orders(['customer_id' => $user->ID, 'status' => $statuses, 'limit' => 1, 'return' => 'ids']);
    foreach (array_unique(array_filter($emails)) as $email) {
        if (wc_get_orders(['billing_email' => sanitize_email($email), 'status' => $statuses, 'limit' => 1, 'return' => 'ids'])) { $has_order = true; break; }
    }
    if ($has_order) { throw new Exception('WELCOME10 is available on your first order only.'); }
    return $valid;
}
add_filter('woocommerce_coupon_is_valid', 'loomara_validate_welcome_offer', 20, 2);
