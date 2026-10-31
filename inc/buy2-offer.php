<?php
/** BUY2 uses a real saved coupon; never creates discounts or coupon records on page load. */
defined('ABSPATH') || exit;
function loomara_buy2_has_quantity($items) {
    $counts = [];
    foreach ($items as $item) {
        $kind = loomara_combo_kind($item->product ?? null);
        $qty = (float)($item->quantity ?? 0);
        if (!in_array($kind, ['sheet','comforter','set','cover'], true) || $qty <= 0) { continue; }
        $counts[$kind] = ($counts[$kind] ?? 0) + $qty;
    }
    return $counts && max($counts) >= 2;
}
function loomara_buy2_configured($coupon) {
    return $coupon->get_id() > 0 && $coupon->get_status() === 'publish'
        && $coupon->get_discount_type() === 'percent' && (float)$coupon->get_amount() === 15.0
        && (float)$coupon->get_minimum_amount() === 2499.0;
}
add_filter('woocommerce_coupon_is_valid', function($valid, $coupon, $discounts) {
    if (strtolower($coupon->get_code()) !== 'buy2') { return $valid; }
    // WooCommerce validates the saved minimum against its pre-discount merchandise subtotal.
    if (!loomara_buy2_configured($coupon)) { throw new Exception('BUY2 is not currently available.'); }
    if (!$discounts || !loomara_buy2_has_quantity($discounts->get_items())) {
        throw new Exception('Choose at least two bedsheets, two comforters, two combo sets, or two pillow/cushion-cover items to use BUY2.');
    }
    return $valid;
}, 40, 3);
// Owner-confirmed revision: BUY2 is a standalone 15% offer, never stacked.
add_filter('woocommerce_coupon_get_individual_use', function($value, $coupon) {
    return strtolower($coupon->get_code()) === 'buy2' ? true : $value;
}, 40, 2);
function loomara_buy2_cart_details() {
    $coupon = new WC_Coupon('buy2');
    if (!loomara_buy2_configured($coupon)) { return; }
    echo '<div class="combo-buy2-details"><strong>More layers. More to love.</strong><p>Use <strong>BUY2</strong> for 15% off your cart when you choose at least two bedsheets, two comforters, two combo sets, or two pillow/cushion-cover items.</p><p class="buy2-terms">Minimum cart subtotal ₹2,499 before coupons. Different designs in the same category count. Cannot be combined with other coupons.</p></div>';
}
function loomara_buy2_home_banner() {
    if (!loomara_buy2_configured(new WC_Coupon('buy2'))) { return; }
    echo '<section class="loomara-combo-home loomara-buy2-home" aria-labelledby="buy2-home-title"><img src="'.esc_url(get_template_directory_uri().'/assets/home-buy2-offer.jpg').'" alt="" width="1983" height="793" loading="lazy" decoding="async"><div><p class="eyebrow">More layers. More to love.</p><h2 id="buy2-home-title">Buy 2. Save 15%.</h2><p>Two in one category.<br>Use <strong>BUY2</strong> &middot; Min. ₹2,499</p><a href="'.esc_url(wc_get_page_permalink('shop')).'" aria-label="Shop BUY2: at least two bedsheets, comforters, combo sets or pillow/cushion-cover items in one category. Minimum subtotal ₹2,499 before coupons. Cannot be combined with other coupons.">Explore the collection &rarr;</a><small>Same-category items.<br>Cannot be combined with other coupons.</small></div></section>';
}
