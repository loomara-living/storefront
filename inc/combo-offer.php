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
    echo '<section class="loomara-combo-home" aria-labelledby="combo-home-title"><img src="'.esc_url(get_template_directory_uri().'/assets/home-combo-offer.jpg').'" alt="" width="1983" height="793" loading="lazy" decoding="async"><div><p class="eyebrow">A little better together</p><h2 id="combo-home-title">Layer up. <br>Save 15%.</h2><p>Choose a combo set, or a separate bedsheet and comforter.<br>Use code <strong>COMBO</strong>.</p><a href="'.esc_url(home_url('/product-category/bedsheets/')).'">Find your bedding pair &rarr;</a><small>15% off combo sets or eligible bedsheets and comforters together. Pillow/cushion covers also qualify with a combo set or a separate bedsheet and comforter. Not combinable with other coupons.</small></div></section>';
}
add_filter('woocommerce_coupon_get_individual_use', function($value, $coupon) {
    return strtolower($coupon->get_code()) === 'combo' ? true : $value;
}, 30, 2);
add_action('woocommerce_before_shop_loop', 'loomara_combo_banner', 8);
add_action('woocommerce_before_cart', function() {
    if (!WC()->cart) { return; }
    $kinds = [];
    foreach (WC()->cart->get_cart() as $line) { $kinds[loomara_combo_kind($line['data'])] = true; }
    if (isset($kinds['set'])) {
        echo '<aside class="loomara-combo-offer"><strong>Your combo, a little better</strong><p>Use COMBO for 15% off combo sets. Separate bedsheets and comforters qualify only when both are added. Pillow/cushion covers also qualify with that bedding. Cannot be combined with other coupons.</p></aside>';
        return;
    }
    if (!isset($kinds['sheet'])) { return; }
    if (isset($kinds['comforter'])) {
        echo '<aside class="loomara-combo-offer"><strong>Your bedding pair is ready</strong><p>Use COMBO for 15% off eligible bedsheets and comforters. Pillow/cushion covers in this cart also qualify. Other coupons cannot be combined.</p></aside>';
    } else {
        echo '<aside class="loomara-combo-offer"><strong>Complete your comfort</strong><p>Add a matching comforter separately to unlock 15% off your bedsheet and comforter with <strong>COMBO</strong>.</p><a href="'.esc_url(home_url('/product-category/comforters/')).'">Find your matching comforter &rarr;</a><small>Choose your preferred colour and size. Also available on combo sets. Pillow/cushion covers also qualify with that bedding.</small></aside>';
    }
});
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('loomara-combo-offer', get_template_directory_uri().'/assets/combo-offer.css', [], filemtime(get_template_directory().'/assets/combo-offer.css'));
});
