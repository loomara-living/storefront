<?php
/** Product courier estimates using the same Shiprocket service as the cart. */
defined('ABSPATH') || exit;
add_action('wp',function(){
    if(!is_product()){return;}
    foreach([['woocommerce_after_add_to_cart_button','addPdpCheckoutButton'],['woocommerce_single_product_summary','shiprocket_show_check_pincode']] as [$hook,$callback]){
        $priority=has_action($hook,$callback);if($priority!==false){remove_action($hook,$callback,$priority);}
    }
},200);
function loomara_product_delivery_panel_113(){
    global $product;if(!$product instanceof WC_Product||!$product->needs_shipping()){return;}
    echo '<section class="loomara-delivery-panel" aria-labelledby="product-delivery-heading"><h2 id="product-delivery-heading"><span aria-hidden="true">⌁</span> Delivery to your door</h2><p>Free India shipping. Check a destination PIN for a courier estimate.</p><form data-product-delivery method="post" action="'.esc_url(admin_url('admin-ajax.php')).'"><input type="hidden" name="action" value="loomara_product_delivery_113"><input type="hidden" name="product_id" value="'.esc_attr($product->get_id()).'"><input type="hidden" name="variation_id" value="0"><input type="hidden" name="quantity" value="1">';
    wp_nonce_field('loomara_product_delivery_113','delivery_nonce');
    echo '<label for="product-delivery-pin">India PIN code</label><div class="loomara-delivery-controls"><input id="product-delivery-pin" name="pin" type="text" inputmode="numeric" autocomplete="postal-code" pattern="[1-9][0-9]{5}" maxlength="6" required placeholder="e.g. 560001"><button class="button" type="submit">Check delivery</button></div><p data-product-delivery-result class="loomara-delivery-result" role="status" aria-live="polite">Enter a PIN to check delivery.</p><small>Courier dates are estimates, not guarantees. This does not change your saved address.</small></form></section>';
}
// Only replace the native live checker when that plugin is present; preserve plugin-absent fallback.
add_action('woocommerce_single_product_summary',function(){if(function_exists('shiprocket_show_check_pincode')){loomara_product_delivery_panel_113();}},35);
add_action('wp_enqueue_scripts',function(){
    if(!is_product()&&!is_cart()){return;}
    wp_enqueue_style('loomara-delivery-113',get_template_directory_uri().'/assets/delivery-v113.css',[],'1.11.3');
    if(is_product()){
        wp_enqueue_script('loomara-delivery-113',get_template_directory_uri().'/assets/delivery-v113.js',['jquery'],'1.11.3',true);
        wp_localize_script('loomara-delivery-113','loomaraDelivery113',['signedIn'=>is_user_logged_in(),'nonce'=>wp_create_nonce('loomara_product_delivery_113')]);
    }
});
add_action('wp_ajax_loomara_default_delivery_pin_113',function(){
    check_ajax_referer('loomara_product_delivery_113','delivery_nonce');nocache_headers();
    $addresses=loomara_saved_addresses();$default=loomara_default_address_id($addresses);$address=$addresses[$default]??[];
    $pin=($address['country']??'IN')==='IN'?($address['postcode']??''):'';
    wp_send_json_success(['pin'=>loomara_pin_format_valid($pin)?$pin:'']);
});
function loomara_delivery_message_113($weight,$pin){
    $settings=get_option('woocommerce_shiprocket_woocommerce_shipping_settings',[]);$merchant=trim((string)($settings['integration_id']??''));
    if(!$merchant){return new WP_Error('connection','The courier connection is not ready. Please contact us for an estimate.');}
    $url=add_query_arg(['weight'=>$weight,'cod'=>0,'delivery_postcode'=>$pin,'store_url'=>untrailingslashit(home_url()),'merchant_id'=>$merchant,'unit'=>'kg'],'https://apiv2.shiprocket.in/v1/external/woocommerce/courier/serviceability');
    $response=wp_remote_get($url,['timeout'=>15,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>1048576,'headers'=>['authorization'=>'ACCESS_TOKEN:source_wc_app_2022']]);
    if(is_wp_error($response)||wp_remote_retrieve_response_code($response)!==200){return new WP_Error('courier','Courier estimate unavailable right now. Please try again.');}
    $data=json_decode(wp_remote_retrieve_body($response),true);$couriers=$data['data']['available_courier_companies']??[];
    if((int)($data['status']??0)!==200||!is_array($couriers)||!$couriers){return new WP_Error('coverage','No courier estimate was returned for this PIN and product. Please contact us to confirm delivery options.');}
    $chosen=$couriers[0];foreach($couriers as $courier){if(($courier['courier_company_id']??null)===($data['data']['recommended_courier_company_id']??null)){$chosen=$courier;break;}}
    $date=is_scalar($chosen['etd']??null)?sanitize_text_field((string)$chosen['etd']):'';
    return $date?'Courier-estimated delivery: '.$date.'. Free India shipping. Dispatch within 24 hours; courier dates may change.':'Courier service is available. Free India shipping; dispatch within 24 hours. Estimated delivery is 5–10 business days after dispatch.';
}
function loomara_product_delivery_response_113(){
    check_ajax_referer('loomara_product_delivery_113','delivery_nonce');nocache_headers();
    $pin=is_scalar($_POST['pin']??null)?sanitize_text_field(wp_unslash($_POST['pin'])):'';
    if(!loomara_pin_format_valid($pin)){wp_send_json_error(['message'=>'Enter a valid six-digit India PIN code.'],400);}
    if(!WC()->session){wc_load_cart();}
    $identity=WC()->session?WC()->session->get_customer_id():get_current_user_id();
    $key='loomara_product_delivery_'.hash_hmac('sha256',(string)$identity,wp_salt());$count=(int)get_transient($key);
    if($count>=15){wp_send_json_error(['message'=>'Please wait a minute before checking again.'],429);}set_transient($key,$count+1,MINUTE_IN_SECONDS);
    $parent=wc_get_product(absint($_POST['product_id']??0));$variation=absint($_POST['variation_id']??0);$qty=max(1,absint($_POST['quantity']??1));
    if(!$parent||$parent->get_status()!=='publish'||!$parent->is_visible()||!$parent->is_purchasable()||$qty>100){wp_send_json_error(['message'=>'This product is not available for a delivery estimate.'],400);}
    $item=$parent;
    if($parent->is_type('variable')){
        $item=$variation?wc_get_product($variation):false;
        if(!$item||!$item->is_type('variation')||$item->get_parent_id()!==$parent->get_id()||!$item->variation_is_active()||!$item->is_purchasable()){wp_send_json_error(['message'=>'Choose colour and size to get an accurate delivery estimate.'],400);}
    }elseif($variation){wp_send_json_error(['message'=>'Choose a valid product option.'],400);}
    if(!$item->needs_shipping()||(float)$item->get_weight()<=0){wp_send_json_error(['message'=>'Shipping weight is being confirmed. Please contact us for an estimate.']);}
    $message=loomara_delivery_message_113((float)wc_get_weight($item->get_weight(),'kg')*$qty,$pin);
    if(is_wp_error($message)){wp_send_json_error(['message'=>$message->get_error_message()]);}wp_send_json_success(['message'=>$message]);
}
add_action('wp_ajax_loomara_product_delivery_113','loomara_product_delivery_response_113');
add_action('wp_ajax_nopriv_loomara_product_delivery_113','loomara_product_delivery_response_113');
