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
    echo '<div class="loomara-price-extras"><span class="loomara-save-badge" data-product-saving'.(!$saving?' hidden':'').'>'.esc_html($saving?'Save '.$saving.'%':'').'</span><small>'.esc_html(wc_prices_include_tax()?'Price inclusive of applicable taxes':'Applicable taxes calculated at checkout').'</small></div>';
},11);
add_filter('woocommerce_available_variation',function($data,$parent,$variation){$data['loomara_saving_percent']=loomara_detail_saving($variation);return $data;},30,3);
function loomara_detail_offer_money($amount) {
    return html_entity_decode(wp_strip_all_tags(wc_price($amount)), ENT_QUOTES, 'UTF-8');
}
function loomara_detail_offer_valid($coupon, $p) {
    if (!$coupon->is_valid_for_product($p)) { return false; }
    // Guests may discover the intentionally public welcome offer, but cannot
    // redeem it until authentication; do not bypass checkout validation.
    if (!is_user_logged_in()) { return strtolower($coupon->get_code()) === 'welcome10'; }
    try {
        // An isolated one-item pricing context, NOT a WC_Cart or saved order.
        // Native validators check account use, first-order rules and exclusions.
        $discounts = new WC_Discounts();
        $item = (object)['key'=>'loomara-preview','object'=>['data'=>$p,'quantity'=>1],'product'=>$p,'quantity'=>1,'price'=>wc_add_number_precision((float)wc_get_price_including_tax($p))];
        $discounts->set_items(['loomara-preview'=>$item]);
        if ($discounts->is_coupon_valid($coupon) === true) { return true; }
        // A larger current cart may meet a minimum spend not met by one item.
        return function_exists('WC') && WC()->cart && !WC()->cart->is_empty()
            && (new WC_Discounts(WC()->cart))->is_coupon_valid($coupon) === true;
    } catch (Throwable $e) { return false; }
}
function loomara_detail_offer_terms($coupon, $code) {
    $terms = [trim(wp_strip_all_tags($coupon->get_description()))];
    if ($code === 'welcome10') { $terms[] = 'First eligible order only. Sign in or create an account to redeem.'; }
    if ($coupon->get_date_expires()) { $terms[] = 'Valid until '.wc_format_datetime($coupon->get_date_expires()).'.'; }
    if ((float)$coupon->get_minimum_amount()>0) { $terms[]='Minimum order spend: '.loomara_detail_offer_money($coupon->get_minimum_amount()).'.'; }
    if ((float)$coupon->get_maximum_amount()>0) { $terms[]='Maximum order spend: '.loomara_detail_offer_money($coupon->get_maximum_amount()).'.'; }
    if ((float)$coupon->get_meta('_loomara_max_discount_113')>0) { $terms[]='Maximum discount: '.loomara_detail_offer_money($coupon->get_meta('_loomara_max_discount_113')).'.'; }
    if ($coupon->get_individual_use()) { $terms[]='Cannot be combined with another coupon.'; }
    if ($coupon->get_exclude_sale_items()) { $terms[]='Sale items excluded.'; }
    if ($coupon->get_usage_limit_per_user()) { $terms[]='Up to '.(int)$coupon->get_usage_limit_per_user().' use(s) per customer.'; }
    $terms[]='Applies to eligible items; quantity, selected variant and order spend can affect the discount. Apply the code before payment.';
    return array_values(array_unique(array_filter($terms)));
}
function loomara_detail_offer() {
    global $product;if(!$product instanceof WC_Product || !wc_coupons_enabled())return;
    $products=[$product];
    if ($product->is_type('variable')) {
        $products=[];
        foreach (array_slice($product->get_children(),0,100) as $id) {
            $p=wc_get_product($id);
            if ($p && $p->get_status()==='publish' && $p->is_purchasable() && $p->is_in_stock()) { $products[]=$p; }
        }
    }
    $rows=[];
    // Same intentionally public offer set as Magic Checkout. Never enumerate
    // hidden/email-targeted codes or disclose another customer's private offer.
    foreach(is_user_logged_in()?['welcome10','diwali','flat5']:['welcome10'] as $code) {
        $coupon=new WC_Coupon($code);
        if(!$coupon->get_id() || get_post_status($coupon->get_id())!=='publish')continue;
        $expires=$coupon->get_date_expires();
        if($expires && $expires->getTimestamp()<time())continue;
        if($coupon->get_usage_limit() && $coupon->get_usage_count()>=$coupon->get_usage_limit())continue;
        if($coupon->get_email_restrictions())continue;
        $ids=[];$prices=[];foreach($products as $p){if(loomara_detail_offer_valid($coupon,$p)){$ids[]=$p->get_id();$prices[$p->get_id()]=(float)wc_get_price_to_display($p);}}
        if(!$ids)continue;
        $label=$coupon->get_discount_type()==='percent'?(float)$coupon->get_amount().'% off':loomara_detail_offer_money($coupon->get_amount()).' off';
        $terms='';foreach(loomara_detail_offer_terms($coupon,$code) as $term){$terms.='<li>'.esc_html($term).'</li>';}
        $upper=strtoupper($code);
        $pricing=['prices'=>$prices,'type'=>$coupon->get_discount_type(),'amount'=>(float)$coupon->get_amount(),'cap'=>(float)$coupon->get_meta('_loomara_max_discount_113')];
        $base=reset($prices);$score=$pricing['type']==='percent'?$base*$pricing['amount']/100:($pricing['type']==='fixed_product'||$pricing['type']==='fixed_cart'?$pricing['amount']:0);
        $score=min($base,$pricing['cap']>0?min($score,$pricing['cap']):$score);
        if($score<=0)continue;
        $rows[]=['score'=>$score,'html'=>'<div class="loomara-product-offer" data-offer-variants="'.esc_attr(wp_json_encode($ids)).'" data-offer-pricing="'.esc_attr(wp_json_encode($pricing)).'"%HIDDEN%><div><strong>'.esc_html($upper).'</strong><small>'.esc_html($label).'</small></div><details class="loomara-offer-info"><summary aria-label="Terms and eligibility for '.esc_attr($upper).'"><span aria-hidden="true">i</span></summary><div class="loomara-offer-terms"><strong>'.esc_html($upper).' — Terms</strong><ul>'.$terms.'</ul></div></details><button type="button" class="loomara-copy-offer" data-copy-offer="'.esc_attr($upper).'" aria-label="Copy coupon '.esc_attr($upper).'">Copy</button><span class="screen-reader-text" data-copy-status role="status"></span></div>'];
    }
    if($rows){
        $best=0;foreach($rows as $i=>$row){if($row['score']>$rows[$best]['score'])$best=$i;}
        echo '<aside class="loomara-product-offers" data-offer-product="'.absint($product->get_id()).'" aria-label="Best available offer for this item"><p class="loomara-offer-eyebrow">A little more comfort</p>';
        foreach($rows as $i=>$row){echo str_replace('%HIDDEN%',$i===$best?'':' hidden',$row['html']);}
        echo '</aside>';
    }
}
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
    wp_enqueue_style('loomara-product-detail-polish',get_template_directory_uri().'/assets/product-detail-polish.css',['loomara-restock'],'2');
    wp_enqueue_script('loomara-product-detail-polish',get_template_directory_uri().'/assets/product-detail-polish.js',['jquery','wc-add-to-cart-variation'],'2',true);
}},30);
