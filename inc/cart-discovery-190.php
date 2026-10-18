<?php
defined('ABSPATH') || exit;
function loomara_matching_combo_sku($sku) {
    return preg_match('/^LM-(BD|CF)-[A-Z]{2}-[A-Z]{2}[0-9]{5}-[SDQKX]$/D',$sku)?preg_replace('/^LM-(BD|CF)-/','LM-CB-',$sku):'';
}
function loomara_cart_discovery() {
    if(!WC()->cart||WC()->cart->is_empty())return;
    $matches=[];$inCart=[];
    foreach(WC()->cart->get_cart() as $item){$p=$item['data'];$inCart[]=$p->get_id();$sku=loomara_matching_combo_sku($p->get_sku());if($sku){$id=wc_get_product_id_by_sku($sku);$combo=$id?wc_get_product($id):false;if($combo&&$combo->get_status()==='publish'&&$combo->is_purchasable()&&$combo->is_in_stock())$matches[$id]=$combo;}}
    $covers=wc_get_products(['status'=>'publish','category'=>['cushion-covers'],'limit'=>4]);
    echo '<section class="cart-discovery-190"><h2>A little extra comfort</h2><p>Add-on covers to finish your space.</p><div class="cart-addon-list">';
    foreach($covers as $p){if(!$p->is_visible()||!$p->is_purchasable()||!$p->is_in_stock()||in_array($p->get_id(),$inCart,true))continue;loomara_cart_addon($p);}
    echo '</div>';
    if($matches){echo '<h3>Complete the matching set</h3><div class="cart-addon-list">';foreach($matches as $id=>$p){if(!in_array($id,$inCart,true))loomara_cart_addon($p);}echo '</div>';}
    echo '</section>';
}
function loomara_cart_addon($p) {
    echo '<article class="cart-addon">'.wp_kses_post($p->get_image('woocommerce_thumbnail')).'<div><h3>'.esc_html($p->get_name()).'</h3>'.wp_kses_post($p->get_price_html()).'<br><a class="button" href="'.esc_url($p->get_permalink()).'">'.($p->is_type('variable')?'Choose options':'View add-on').'</a></div></article>';
}
add_action('woocommerce_after_cart','loomara_cart_discovery',5);
function loomara_cart_delivery() {
    if(!WC()->cart||WC()->cart->is_empty())return;
    $addresses=loomara_saved_addresses();$default=loomara_default_address_id($addresses);$pin=$addresses[$default]['postcode']??'';
    echo '<section class="cart-discovery-190"><h2>Your delivery estimate</h2><p>Check the default address PIN, or enter another destination.</p><form class="cart-delivery-form" data-cart-delivery method="post" action="'.esc_url(admin_url('admin-ajax.php')).'"><input type="hidden" name="action" value="loomara_cart_delivery">';wp_nonce_field('loomara_cart_delivery','delivery_nonce');
    echo '<label for="cart-delivery-pin">India PIN code</label><input id="cart-delivery-pin" name="pin" inputmode="numeric" autocomplete="postal-code" pattern="[1-9][0-9]{5}" maxlength="6" required value="'.esc_attr($pin).'"><button class="button" type="submit">Check delivery</button></form><p class="cart-delivery-result" data-cart-delivery-result role="status" aria-live="polite">Enter a PIN to get a courier estimate.</p><p class="cart-delivery-notice">Prepaid courier estimate for the cart’s current weight. Dates are estimates, not a delivery guarantee. Changing this PIN does not change your saved address.</p></section>';
}
add_action('woocommerce_after_cart','loomara_cart_delivery',10);
add_filter('render_block_woocommerce/cart',function($html){if(is_admin()||!is_cart())return $html;ob_start();loomara_cart_discovery();loomara_cart_delivery();return $html.ob_get_clean();});
function loomara_cart_delivery_response() {
    check_ajax_referer('loomara_cart_delivery','delivery_nonce');nocache_headers();
    $pin=is_scalar($_POST['pin']??null)?sanitize_text_field(wp_unslash($_POST['pin'])):'';
    if(!loomara_pin_format_valid($pin))wp_send_json_error(['message'=>'Enter a valid six-digit India PIN code.'],400);
    if(!WC()->cart)wc_load_cart();
    if(!WC()->cart||WC()->cart->is_empty())wp_send_json_error(['message'=>'Add a product to your cart before checking delivery.'],400);
    $key='loomara_delivery_'.hash_hmac('sha256',WC()->session->get_customer_id(),wp_salt());$requests=(int)get_transient($key);
    if($requests>=15)wp_send_json_error(['message'=>'Please wait a minute before checking again.'],429);set_transient($key,$requests+1,MINUTE_IN_SECONDS);
    $settings=get_option('woocommerce_shiprocket_woocommerce_shipping_settings',[]);$merchant=trim((string)($settings['integration_id']??''));$weight=0;
    foreach(WC()->cart->get_cart() as $item){$w=$item['data']->get_weight();if($w===''||(float)$w<=0)wp_send_json_error(['message'=>'Courier estimate is unavailable while an item’s shipping weight is being confirmed.']);$weight+=(float)wc_get_weight((float)$w,'kg')*(int)$item['quantity'];}
    if(!$merchant)wp_send_json_error(['message'=>'The courier connection is not ready. Please contact us for a delivery estimate.']);
    $url=add_query_arg(['weight'=>$weight,'cod'=>0,'delivery_postcode'=>$pin,'store_url'=>untrailingslashit(home_url()),'merchant_id'=>$merchant,'unit'=>'kg'],'https://apiv2.shiprocket.in/v1/external/woocommerce/courier/serviceability');
    $response=wp_remote_get($url,['timeout'=>15,'redirection'=>0,'headers'=>['authorization'=>'ACCESS_TOKEN:source_wc_app_2022']]);
    if(is_wp_error($response)||wp_remote_retrieve_response_code($response)!==200)wp_send_json_error(['message'=>'The courier service could not provide an estimate right now. Please try again or contact us.']);
    $data=json_decode(wp_remote_retrieve_body($response),true);$couriers=$data['data']['available_courier_companies']??[];
    if((int)($data['status']??0)!==200||!is_array($couriers)||!$couriers)wp_send_json_error(['message'=>'No courier estimate was returned for this PIN and cart. Please contact us to confirm delivery options.']);
    $chosen=$couriers[0];foreach($couriers as $courier){if(($courier['courier_company_id']??null)===($data['data']['recommended_courier_company_id']??null)){$chosen=$courier;break;}}
    $date=sanitize_text_field(is_scalar($chosen['etd']??null)?$chosen['etd']:'');
    wp_send_json_success(['message'=>$date?'Courier-estimated delivery: '.$date.'. Free India shipping. Dispatch within 24 hours; courier dates may change.':'Courier service is available for this cart and PIN. Dispatch within 24 hours; estimated delivery is 5–10 business days after dispatch.']);
}
add_action('wp_ajax_loomara_cart_delivery','loomara_cart_delivery_response');
add_action('wp_ajax_nopriv_loomara_cart_delivery','loomara_cart_delivery_response');
