<?php
/** Read-only public promotion discovery, evaluated for this customer's live cart. */
defined('ABSPATH') || exit;
function loomara_header_offers_button() {
    if (!is_front_page()) { return; }
    echo '<button type="button" class="header-icon header-offers" data-offers-open aria-label="Offers" title="Offers" aria-haspopup="dialog" aria-controls="loomara-offers-dialog"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 2 2.5 1.7 3-.1.9 2.9 2.5 1.7-.9 2.9.9 2.9-2.5 1.7-.9 2.9-3-.1L12 22l-2.5-1.6-3 .1-.9-2.9-2.5-1.7.9-2.9-.9-2.9 2.5-1.7.9-2.9 3 .1L12 2Z"/><path d="m8.5 15.5 7-7"/><circle cx="8.5" cy="8.5" r="1"/><circle cx="15.5" cy="15.5" r="1"/></svg></button>';
}
function loomara_header_offers_data() {
    if (!function_exists('WC') || !class_exists('WC_Coupon')) { wp_send_json_error(['message'=>'Offers are temporarily unavailable.'],503); }
    if (!WC()->cart && function_exists('wc_load_cart')) { wc_load_cart(); }
    nocache_headers();header('Cache-Control: private, no-store, max-age=0');
    $rows=[];
    // Explicit public offer catalogue: never expose private/email-only merchant codes.
    $copy=[
        'welcome20'=>'20% off your first eligible signed-in order.',
        'flat5'=>'5% off, up to ₹500.',
        'combo'=>'15% off eligible combo sets or a bedsheet + comforter pair. Covers qualify with eligible bedding.',
        'buy2'=>'15% off your cart: at least two units in one eligible category. Minimum ₹2,499 before coupons. Cannot be combined with other coupons.',
    ];
    foreach ($copy as $code=>$description) {
        $coupon=new WC_Coupon($code);
        if (!$coupon->get_id() || $coupon->get_status()!=='publish' || $coupon->get_email_restrictions()) { continue; }
        $expiry=$coupon->get_date_expires();if($expiry && $expiry->getTimestamp()<time()){continue;}
        if ($coupon->get_usage_limit() && $coupon->get_usage_count()>=$coupon->get_usage_limit()) { continue; }
        $reason='';$applicable=false;$user_ineligible=false;
        if ($code==='welcome20' && is_user_logged_in() && function_exists('loomara_validate_welcome_offer')) {
            try { loomara_validate_welcome_offer(true,$coupon); }
            catch (Exception $e) { $user_ineligible=true;$reason='This offer is for your first eligible order only.'; }
        }
        if (!$user_ineligible && is_user_logged_in() && $coupon->get_usage_limit_per_user()>0) {
            $user=wp_get_current_user();
            $identifiers=array_filter([(string)$user->ID,strtolower($user->user_email),strtolower((string)get_user_meta($user->ID,'billing_email',true))]);
            $uses=0;foreach($coupon->get_used_by() as $used){if(in_array(strtolower((string)$used),$identifiers,true)){$uses++;}}
            if($uses >= $coupon->get_usage_limit_per_user()){$user_ineligible=true;$reason='You have reached this offer’s usage limit.';}
        }
        if (!$user_ineligible && WC()->cart && !WC()->cart->is_empty()) {
            try {
                $result=(new WC_Discounts(WC()->cart))->is_coupon_valid($coupon);
                $applicable=$result===true;
            } catch (Throwable $e) { /* Leave cart eligibility unlabelled on uncertainty. */ }
        }
        $rows[]=['code'=>strtoupper($code),'description'=>$description,'applicable'=>$applicable,'user_ineligible'=>$user_ineligible,'reason'=>$reason,'minimum'=>(float)$coupon->get_minimum_amount(),'expires'=>$expiry?$expiry->date('d M Y'):null];
    }
    wp_send_json_success($rows);
}
add_action('wp_ajax_loomara_public_offers','loomara_header_offers_data');
add_action('wp_ajax_nopriv_loomara_public_offers','loomara_header_offers_data');
add_action('wp_footer',function(){
    if (!is_front_page() || !class_exists('WooCommerce')) { return; }
    echo '<dialog id="loomara-offers-dialog" class="loomara-offers-dialog" aria-labelledby="loomara-offers-title"><header><div><p class="eyebrow">A little more comfort</p><h2 id="loomara-offers-title">Offers for your home</h2></div><button type="button" data-offers-close aria-label="Close offers">&times;</button></header><p class="offers-checkout-note">Offers will appear again at checkout, where you can apply an eligible coupon before paying. Copying a code does not apply it.</p><div data-offers-list></div><p data-offers-message role="status" aria-live="polite"></p><a class="offers-cart-link" href="'.esc_url(wc_get_cart_url()).'">Review your cart &rarr;</a></dialog>';
});
add_action('wp_enqueue_scripts',function(){
    if (!is_front_page() || !class_exists('WooCommerce')) { return; }
    wp_enqueue_style('loomara-header-offers',get_template_directory_uri().'/assets/header-offers.css',[],filemtime(__DIR__.'/../assets/header-offers.css'));
    wp_enqueue_script('loomara-header-offers',get_template_directory_uri().'/assets/header-offers.js',[],filemtime(__DIR__.'/../assets/header-offers.js'),true);
    wp_localize_script('loomara-header-offers','loomaraHeaderOffers',['url'=>admin_url('admin-ajax.php')]);
});
