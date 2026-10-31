<?php
/** Separate bedding offer. No coupon records or cart contents are created here. */
defined('ABSPATH') || exit;
function loomara_combo_kind($product) {
    if (!$product) { return ''; }
    $id = $product->get_parent_id() ?: $product->get_id();
    if (has_term(['pillow-covers','cushion-covers'], 'product_cat', $id)) { return 'cover'; }
    if (has_term(['pillow','pillows'], 'product_cat', $id)) { return ''; }
    if (has_term('combo', 'product_cat', $id)) { return 'set'; }
    $sheet = has_term('bedsheets', 'product_cat', $id);
    $comforter = has_term('comforters', 'product_cat', $id);
    return $sheet && !$comforter ? 'sheet' : ($comforter && !$sheet ? 'comforter' : '');
}
function loomara_combo_has_set($items) {
    foreach ($items as $item) {
        if (($item->quantity ?? 0) > 0 && loomara_combo_kind($item->product ?? null) === 'set') { return true; }
    }
    return false;
}
function loomara_combo_pair($items) {
    $found = [];
    foreach ($items as $item) {
        if (($item->quantity ?? 0) > 0) { $found[loomara_combo_kind($item->product ?? null)] = true; }
    }
    return isset($found['sheet'], $found['comforter']);
}
// Use the saved merchant coupon only. Draft/disabled coupons get no virtual fallback.
add_filter('woocommerce_coupon_is_valid', function($valid, $coupon, $discounts) {
    if (strtolower($coupon->get_code()) !== 'combo') { return $valid; }
    if ($coupon->get_discount_type() !== 'percent' || (float)$coupon->get_amount() !== 15.0) {
        throw new Exception('COMBO requires the configured 15% bedding offer. Please contact us.');
    }
    if (!$discounts || (!loomara_combo_pair($discounts->get_items()) && !loomara_combo_has_set($discounts->get_items()))) {
        throw new Exception('Add a bedsheet and a comforter separately, or a combo set, to use COMBO. Pillow/cushion covers qualify only with that bedding.');
    }
    return $valid;
}, 30, 3);
// A set must not unlock discounts on an unpaired standalone bedsheet/comforter.
add_filter('woocommerce_coupon_get_items_to_apply', function($items, $coupon, $discounts) {
    if (strtolower($coupon->get_code()) !== 'combo') { return $items; }
    $pair = loomara_combo_pair($discounts->get_items());
    $qualifies = $pair || loomara_combo_has_set($discounts->get_items());
    return array_filter($items, function($item) use ($pair, $qualifies) {
        $kind = loomara_combo_kind($item->product ?? null);
        return $kind === 'set' || ($pair && in_array($kind, ['sheet','comforter'], true)) || ($qualifies && $kind === 'cover');
    });
}, 110, 3);
add_filter('woocommerce_coupon_is_valid_for_product', function($valid, $product, $coupon) {
    return strtolower($coupon->get_code()) === 'combo' ? $valid && loomara_combo_kind($product) !== '' : $valid;
}, 30, 3);
function loomara_combo_banner() {
    echo '<aside class="loomara-combo-offer" aria-label="Bedding offer"><strong>A little better together</strong><p>Save 15% on combo sets, or bedsheets and comforters added separately together, with <strong>COMBO</strong>.</p><small>Pillow/cushion covers also qualify with a combo set or a separate bedsheet and comforter. Cannot be combined with other coupons.</small></aside>';
}
function loomara_combo_home_banner() {
    echo '<section class="loomara-combo-home" aria-labelledby="combo-home-title"><img src="'.esc_url(get_template_directory_uri().'/assets/home-combo-offer.jpg').'" alt="" width="1983" height="793" loading="lazy" decoding="async"><div><p class="eyebrow">A little better together</p><h2 id="combo-home-title">Layer up. <br>Save 15%.</h2><p>Combo sets or bedsheet + comforter.<br>Use code <strong>COMBO</strong>.</p><a href="'.esc_url(home_url('/product-category/bedsheets/')).'">Find your bedding pair &rarr;</a><small>Pillow/cushion covers qualify with eligible bedding only. No coupon stacking.</small></div></section>';
}
add_filter('woocommerce_coupon_get_individual_use', function($value, $coupon) {
    return strtolower($coupon->get_code()) === 'combo' ? true : $value;
}, 30, 2);
function loomara_combo_cart_offer() {
    static $shown = false;
    if ($shown) { return; }
    if (!WC()->cart || WC()->cart->is_empty()) { return; }
    $shown = true;
    $kinds = [];
    foreach (WC()->cart->get_cart() as $line) { $kinds[loomara_combo_kind($line['data'])] = true; }
    echo '<aside class="loomara-combo-offer loomara-combo-cart" aria-label="COMBO offer"><span class="combo-cart-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h8l10 10-7 7L3 10V4Z"/><circle cx="7.5" cy="8" r="1"/></svg></span><div class="combo-cart-copy"><strong>A little better together</strong><p>Save 15% on combo sets, or bedsheets and comforters added separately together, with <strong>COMBO</strong>.';
    if (isset($kinds['sheet']) && !isset($kinds['comforter']) && !isset($kinds['set'])) {
        echo ' <a class="combo-pair-link" href="'.esc_url(home_url('/product-category/comforters/')).'"><span>Explore comforters</span><span aria-hidden="true">&rarr;</span></a>';
    } elseif (isset($kinds['comforter']) && !isset($kinds['sheet']) && !isset($kinds['set'])) {
        echo ' <a class="combo-pair-link" href="'.esc_url(home_url('/product-category/bedsheets/')).'"><span>Explore bedsheets</span><span aria-hidden="true">&rarr;</span></a>';
    }
    echo '</p>';
    if (function_exists('loomara_buy2_cart_details')) { loomara_buy2_cart_details(); }
    echo '</div></aside>';
}
add_action('woocommerce_before_cart', 'loomara_combo_cart_offer');
add_filter('render_block_woocommerce/cart', function($content) {
    if (!function_exists('is_cart') || !is_cart() || is_admin()) { return $content; }
    ob_start();
    loomara_combo_cart_offer();
    return ob_get_clean() . $content;
});
add_action('wp_enqueue_scripts', function() {
    if (!is_front_page() && !(function_exists('is_cart') && is_cart())) { return; }
    wp_enqueue_style('loomara-combo-offer', get_template_directory_uri().'/assets/combo-offer.css', [], filemtime(get_template_directory().'/assets/combo-offer.css'));
});
