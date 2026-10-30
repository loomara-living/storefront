<?php
/** Responsive announcement and owner-confirmed first-order offer. */
defined('ABSPATH') || exit;
function loomara_offer_banner() {
    $text = 'Launch Offer · Use WELCOME20 to get 20% additional discount on First Order. Dispatch across India within 24 hours';
    echo '<aside class="launch-strip" aria-label="Launch offer"><div class="offer-viewport"><div class="offer-track"><span>'.esc_html($text).'</span><span aria-hidden="true">'.esc_html($text).'</span></div></div><button type="button" data-offer-pause aria-label="Pause offer animation" aria-pressed="false">Pause</button></aside>';
}
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('loomara-offer-banner', get_template_directory_uri().'/assets/offer-banner.css', ['loomara-refinements'], '1.7.7');
    wp_enqueue_script('loomara-offer-banner', get_template_directory_uri().'/assets/offer-banner.js', [], '1.7.7', true);
});
// Make the advertised offer usable without silently changing an existing
// merchant-managed coupon. The fallback uses WooCommerce's virtual coupon API.
function loomara_welcome_coupon_data($data, $code) {
    if ($data || !is_string($code) || strtolower($code) !== 'welcome20' || wc_get_coupon_id_by_code($code)) { return $data; }
    return ['discount_type' => 'percent', 'amount' => 20, 'individual_use' => true, 'exclude_sale_items' => false, 'description' => '20% off the first order; sign-in required.'];
}
add_filter('woocommerce_get_shop_coupon_data', 'loomara_welcome_coupon_data', 10, 2);
function loomara_validate_welcome_offer($valid, $coupon, $discounts = null) {
    if (strtolower($coupon->get_code()) !== 'welcome20') { return $valid; }
    $object=$discounts&&method_exists($discounts,'get_object')?$discounts->get_object():null;
    if(!$object instanceof WC_Order&&isset($GLOBALS['loomara_offer_order_113'])){$object=$GLOBALS['loomara_offer_order_113'];}
    if($object instanceof WC_Order&&function_exists('loomara_welcome_order_eligible_113')){
        if(!loomara_welcome_order_eligible_113($object)){throw new Exception('WELCOME20 is available on your first eligible signed-in order only.');}
        return $valid;
    }
    if (!is_user_logged_in()) { throw new Exception('Please sign in or create an account to use your first-order offer.'); }
    $statuses = array_values(array_diff(array_keys(wc_get_order_statuses()), ['wc-cancelled', 'wc-failed', 'wc-checkout-draft']));
    $user = wp_get_current_user();
    $emails = [$user->user_email, get_user_meta($user->ID, 'billing_email', true)];
    if (WC()->customer) { $emails[] = WC()->customer->get_billing_email(); }
    $has_order = (bool) wc_get_orders(['customer_id' => $user->ID, 'status' => $statuses, 'limit' => 1, 'return' => 'ids']);
    foreach (array_unique(array_filter($emails)) as $email) {
        if (wc_get_orders(['billing_email' => sanitize_email($email), 'status' => $statuses, 'limit' => 1, 'return' => 'ids'])) { $has_order = true; break; }
    }
    if ($has_order) { throw new Exception('WELCOME20 is available on your first order only.'); }
    return $valid;
}
add_filter('woocommerce_coupon_is_valid', 'loomara_validate_welcome_offer', 20, 3);

/** Explicit deployment step: rename the saved coupon, preserving its limits/audit. */
function loomara_welcome20_settings() {
    if (!current_user_can('manage_options')) { return; }
    echo '<section><h2>Welcome offer update</h2><p>Rename WELCOME10 to WELCOME20 and set 20% off. Preserve existing usage, restrictions and historical orders.</p>';
    if (get_option('loomara_welcome20_complete')) { echo '<p>Update completed.</p></section>'; return; }
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_welcome20">';
    wp_nonce_field('loomara_welcome20');
    echo '<button class="button">Apply WELCOME20 update</button></form></section>';
}
function loomara_welcome20_migrate() {
    if (!current_user_can('manage_options')) { wp_die('Administrator access required.',403); }
    check_admin_referer('loomara_welcome20');
    if (get_option('loomara_welcome20_complete')) { wp_safe_redirect(admin_url('admin.php?page=loomara-settings')); exit; }
    $coupon = new WC_Coupon('welcome10');
    if (!$coupon->get_id() || wc_get_coupon_id_by_code('welcome20')) { wp_die('Coupon identity changed. Review before applying.',409); }
    if (!add_option('loomara_welcome20_snapshot',['at'=>time(),'coupon'=>$coupon->get_data()],'','no')) { wp_die('Prior attempt needs review; do not replay.',409); }
    $coupon->set_code('welcome20');$coupon->set_discount_type('percent');$coupon->set_amount(20);
    $coupon->set_description('20% off your first eligible order. Sign in or create an account to use WELCOME20.');
    $coupon->save();
    update_option('loomara_welcome20_complete',time(),false);
    wp_safe_redirect(admin_url('admin.php?page=loomara-settings'));exit;
}
add_action('admin_post_loomara_welcome20','loomara_welcome20_migrate');
