<?php
/** Product-page presentation only; native pricing, checkout and review rules remain authoritative. */
defined('ABSPATH') || exit;
function loomara_detail_saving($p) {
    $regular=(float)$p->get_regular_price();$sale=(float)$p->get_sale_price();
    if ($p->is_type('variable') || !$p->is_on_sale() || $regular<=0 || $sale<0 || $sale>=$regular) { return 0; }
    return (int)floor(($regular-$sale)/$regular*100);
}
add_action('wp',function(){
    if (!is_product()) { return; }
    remove_action('woocommerce_single_product_summary','woocommerce_template_single_rating',10);
    add_action('woocommerce_single_product_summary','loomara_detail_rating',6);
});
function loomara_detail_rating() {
    global $product;
    if (!$product instanceof WC_Product) { return; }
    echo '<a class="loomara-detail-rating" href="#loomara-customer-reviews">';
    loomara_product_rating();
    echo '<span>Customer reviews ('.absint($product->get_review_count()).')</span></a>';
}
add_action('woocommerce_single_product_summary',function(){
    global $product;if(!$product instanceof WC_Product)return;
    $saving=loomara_detail_saving($product);
    echo '<div class="loomara-price-extras"><span class="loomara-save-badge" data-product-saving'.(!$saving?' hidden':'').'>'.esc_html($saving?'Save '.$saving.'%':'').'</span><small>'.esc_html('Price inclusive of taxes').'</small></div>';
},11);
add_filter('woocommerce_available_variation',function($data,$parent,$variation){$data['loomara_saving_percent']=loomara_detail_saving($variation);return $data;},30,3);
function loomara_detail_offer() {
    global $product;if(!$product instanceof WC_Product || !wc_coupons_enabled())return;
    // Display only the owner's intentionally public codes, never enumerate private coupons.
    foreach(['welcome20','flat5'] as $code) {
        $coupon=new WC_Coupon($code);
        if(!$coupon->get_id() || get_post_status($coupon->get_id())!=='publish')continue;
        $expires=$coupon->get_date_expires();
        if($expires && $expires->getTimestamp()<time())continue;
        if($coupon->get_usage_limit() && $coupon->get_usage_count()>=$coupon->get_usage_limit())continue;
        if($coupon->get_email_restrictions())continue;
        if(!$coupon->is_valid_for_product($product))continue;
        $description=trim(wp_strip_all_tags($coupon->get_description()));
        if ($code === 'welcome20') {
            $description=trim(str_replace('Sign in or create an account to use WELCOME20.', '', $description));
        }
        if(!$description)continue;
        echo '<aside class="loomara-product-offer" aria-label="Available offer"><div><span class="loomara-offer-eyebrow">A little more comfort</span><p>Code <strong>'.esc_html(strtoupper($code)).'</strong></p><small>'.esc_html($description).'</small></div><button type="button" class="loomara-copy-offer" data-copy-offer="'.esc_attr(strtoupper($code)).'" aria-label="Copy coupon '.esc_attr(strtoupper($code)).'">Copy</button><span class="screen-reader-text" data-copy-status role="status"></span></aside>';
        break;
    }
}
// Native price is priority 10; tax note 11, then the offer before the excerpt.
add_action('woocommerce_single_product_summary','loomara_detail_offer',12);
add_action('woocommerce_single_product_summary',function(){
    echo '<aside class="loomara-product-promises" aria-label="Shopping with Loomara"><p><span aria-hidden="true">↗</span><span><strong>In-stock orders dispatch within 24 hours</strong><small>Thoughtfully packed, ready for your home.</small></span></p><p><span aria-hidden="true">♡</span><span><strong>Made for everyday comfort</strong><small>Find materials and care instructions in Product details.</small></span></p></aside>';
},32);
add_filter('woocommerce_product_tabs',function($tabs){unset($tabs['reviews']);return $tabs;},100);
function loomara_detail_reviews() {
    global $product;if(!$product instanceof WC_Product || !comments_open())return;
    $count=(int)$product->get_review_count();
    echo '<section class="loomara-customer-reviews" id="loomara-customer-reviews" aria-labelledby="loomara-review-title"><div class="loomara-review-header"><div><p class="loomara-offer-eyebrow">From real homes</p><h2 id="loomara-review-title">Customer reviews</h2>';
    loomara_product_rating();
    echo '<p>'.esc_html($count?sprintf(_n('%d customer review','%d customer reviews',$count,'loomara-living'),$count):'No customer reviews yet. Your experience could help someone find their next layer.').'</p></div>';
    if(is_user_logged_in() && loomara_can_rate_product($product->get_id()) && !loomara_existing_review($product->get_id())) {
        echo '<a class="button" href="#review_form_wrapper">Write a review</a>';
    } else {
        echo '<p class="loomara-review-eligibility">Reviews are available after a delivered, paid purchase.</p>';
    }
    echo '</div>';
    comments_template();
    echo '</section>';
}
add_action('woocommerce_after_single_product_summary','loomara_detail_reviews',11);
add_action('wp_enqueue_scripts',function(){if(is_product()){
    wp_enqueue_style('loomara-product-detail-polish',get_template_directory_uri().'/assets/product-detail-polish.css',['loomara-restock'],'4');
    wp_enqueue_script('loomara-product-detail-polish',get_template_directory_uri().'/assets/product-detail-polish.js',['jquery','wc-add-to-cart-variation'],'3',true);
}},30);
