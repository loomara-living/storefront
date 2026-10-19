<?php
/** Explicit public offers; WooCommerce remains authoritative for validation and totals. */
defined('ABSPATH') || exit;
function loomara_offer_plan_113(){return [
    'flat5'=>['rate'=>5,'cap'=>500,'until'=>'2026-10-31','description'=>'5% off, up to ₹500. Valid through 31 October 2026.'],
    'diwali'=>['rate'=>10,'cap'=>500,'until'=>'2026-11-15','description'=>'10% off, up to ₹500. Valid through 15 November 2026.'],
];}
function loomara_offer_rates_113(){static $rates;return $rates??($rates=new WeakMap);}
function loomara_offer_cap_items_113($items,$coupon,$discounts){
    $plan=loomara_offer_plan_113()[strtolower($coupon->get_code())]??null;
    if(!$plan||$coupon->get_meta('_loomara_offer_113')!=='yes'||$coupon->get_discount_type()!=='percent'){return $items;}
    $base=0;
    $object=$discounts->get_object();
    foreach($items as $item){
        $price=get_option('woocommerce_calc_discounts_sequentially','no')==='yes'?$discounts->get_discounted_price_in_cents($item):round($item->price);
        $included=$object instanceof WC_Order?$object->get_prices_include_tax():wc_prices_include_tax();
        if(!$included&&wc_tax_enabled()){
            if($object instanceof WC_Order){$subtotal=(float)$item->object->get_subtotal();$price=$subtotal>0?$price*(1+(float)$item->object->get_subtotal_tax()/$subtotal):$price;}
            else{$price=wc_add_number_precision(wc_get_price_including_tax($item->product,['qty'=>1,'price'=>wc_remove_number_precision($price)]));}
        }
        $base+=$price;
    }
    // Reserve at most one minor unit per line for independent tax/line rounding.
    $reserve=wc_tax_enabled()?count($items)*pow(10,-wc_get_price_decimals()):0;
    $cap=wc_add_number_precision(max(0,$plan['cap']-$reserve));
    // An instance-scoped getter filter avoids changing/saving the coupon's advertised rate.
    loomara_offer_rates_113()[$coupon]=$base>0?min($plan['rate'],floor(($cap/$base*100)*100000000)/100000000):0;
    return $items;
}
add_filter('woocommerce_coupon_get_items_to_apply','loomara_offer_cap_items_113',100,3);
add_filter('woocommerce_coupon_get_amount',function($amount,$coupon){return loomara_offer_rates_113()[$coupon]??$amount;},100,2);
add_filter('rest_request_before_callbacks',function($response,$handler,$request){
    if($response===null&&$request->get_route()==='/1cc/v1/coupon/apply'&&$request->get_method()==='POST'){
        $order=wc_get_order(absint($request->get_param('order_id')));
        if($order&&$order->needs_payment()){$GLOBALS['loomara_offer_order_113']=$order;}
    }
    return $response;
},100,3);
add_filter('rest_request_after_callbacks',function($response){unset($GLOBALS['loomara_offer_order_113']);return $response;},200,1);
function loomara_welcome_order_eligible_113($order){
    $uid=(int)$order->get_customer_id();$owner=function_exists('loomara_magic_bound_owner')?loomara_magic_bound_owner($order):false;
    if(!$uid||(!$owner&&get_current_user_id()!==$uid)||($owner&&(int)$owner->ID!==$uid)){return false;}
    $user=get_user_by('id',$uid);if(!$user){return false;}
    $args=['exclude'=>[$order->get_id()],'status'=>array_values(array_diff(array_keys(wc_get_order_statuses()),['wc-cancelled','wc-failed','wc-checkout-draft'])),'limit'=>1,'return'=>'ids'];
    if(wc_get_orders($args+['customer_id'=>$uid])){return false;}
    foreach(array_unique(array_filter([$user->user_email,get_user_meta($uid,'billing_email',true)])) as $email){if(wc_get_orders($args+['billing_email'=>sanitize_email($email)])){return false;}}
    return true;
}
function loomara_magic_offer_response_113($response,$handler,$request){
    if($request->get_route()!=='/1cc/v1/coupon/list'||$request->get_method()!=='POST'||is_wp_error($response)||!$response instanceof WP_REST_Response||$response->get_status()!==200){return $response;}
    $data=$response->get_data();if(!is_array($data)||!is_array($data['promotions']??null)){return $response;}
    $order=wc_get_order(absint($request->get_param('order_id')));$visible=[];
    if($order&&$order->needs_payment()){
        foreach($data['promotions'] as $promotion){
            $code=is_string($promotion['code']??null)?strtolower($promotion['code']):'';
            if(!in_array($code,['welcome10','flat5','diwali'],true)){continue;}
            if($code==='welcome10'&&!loomara_welcome_order_eligible_113($order)){continue;}
            $coupon=new WC_Coupon($code);
            if(!$coupon->get_id()||($code!=='welcome10'&&$coupon->get_meta('_loomara_offer_113')!=='yes')){continue;}
            try{$valid=(new WC_Discounts($order))->is_coupon_valid($coupon);}catch(Throwable $e){$valid=false;}
            if($valid===true){$visible[]=$promotion;}
        }
    }
    $data['promotions']=$visible;$response->set_data($data);$response->header('Cache-Control','private, no-store');return $response;
}
add_filter('rest_request_after_callbacks','loomara_magic_offer_response_113',100,3);

// Explicit administrator-only migration; never runs on public page loads.
add_action('admin_menu',function(){add_submenu_page('woocommerce','Checkout offer update','Checkout offer update','manage_woocommerce','loomara-offers-113','loomara_offer_admin_113');});
function loomara_offer_admin_113(){
    if(!current_user_can('manage_woocommerce')){return;}
    echo '<div class="wrap"><h1>Checkout offers v1.11.3</h1><p>Create FLAT5 (5%, maximum ₹500, through 31 October 2026) and DIWALI (10%, maximum ₹500, through 15 November 2026), each usable on its own. Preserve WELCOME10 limits and add a public description. Move only duplicate NEWUSER to recoverable Trash. Existing orders remain unchanged.</p>';
    if(get_option('loomara_offers_113_complete')){echo '<p><strong>Update completed.</strong></p></div>';return;}
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_offers_113">';wp_nonce_field('loomara_offers_113');echo '<button class="button button-primary">Apply confirmed offers</button></form></div>';
}
function loomara_offer_migrate_113(){
    if(!current_user_can('manage_woocommerce')){wp_die('Administrator access required.',403);}check_admin_referer('loomara_offers_113');
    if(get_option('loomara_offers_113_complete')){wp_safe_redirect(admin_url('admin.php?page=loomara-offers-113'));exit;}
    foreach(['flat5','diwali'] as $code){if(wc_get_coupon_id_by_code($code)){wp_die('An offer code already exists. Review it before applying this update.',409);}}
    $welcome=new WC_Coupon('welcome10');$duplicate=new WC_Coupon('newuser');
    if($welcome->get_id()!==74||$duplicate->get_id()!==20){wp_die('Existing coupon identities changed; review before applying.',409);}
    $snapshot=['welcome'=>$welcome->get_data(),'newuser'=>$duplicate->get_data(),'at'=>time()];
    if(!add_option('loomara_offers_113_snapshot',$snapshot,'','no')){wp_die('A prior attempt needs review before retrying.',409);}
    foreach(loomara_offer_plan_113() as $code=>$plan){
        $coupon=new WC_Coupon;$coupon->set_code($code);$coupon->set_discount_type('percent');$coupon->set_amount($plan['rate']);$coupon->set_individual_use(true);
        $coupon->set_description($plan['description']);$coupon->set_date_expires((new DateTimeImmutable($plan['until'].' 23:59:59',new DateTimeZone('Asia/Kolkata')))->getTimestamp());
        $coupon->update_meta_data('_loomara_offer_113','yes');$coupon->update_meta_data('_loomara_max_discount_113',500);$coupon->save();
    }
    $welcome->set_description('10% off your first eligible order. Sign in or create an account to use WELCOME10.');$welcome->save();
    if(!wp_trash_post($duplicate->get_id())){wp_die('New offers saved; duplicate coupon removal needs review. Do not rerun.',409);}
    update_option('loomara_offers_113_complete',time(),false);wp_safe_redirect(admin_url('admin.php?page=loomara-offers-113'));exit;
}
add_action('admin_post_loomara_offers_113','loomara_offer_migrate_113');
