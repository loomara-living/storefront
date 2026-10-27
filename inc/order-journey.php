<?php
/** Owner-only timeline and explicit, nonce-protected reorder review. No payments. */
defined('ABSPATH') || exit;
function loomara_journey_owner($o){return $o instanceof WC_Order && get_current_user_id()>0 && (int)$o->get_customer_id()===get_current_user_id();}
function loomara_journey_record($id,$from,$to,$order){
    if(!$order instanceof WC_Order || $from===$to || !isset(wc_get_order_statuses()['wc-'.$to])){return;}
    $events=$order->get_meta('_loomara_journey_events');$events=is_array($events)?$events:[];
    $events[]=['status'=>$to,'at'=>time()];
    $order->update_meta_data('_loomara_journey_events',array_slice($events,-40));$order->save_meta_data();
}
add_action('woocommerce_order_status_changed','loomara_journey_record',90,4);
function loomara_journey_events($o){
    $events=[];$labels=wc_get_order_statuses();
    $delivered=(int)($o->get_meta('_loomara_return_delivered_at')?:$o->get_meta('_loomara_delivered_at_111'));
    foreach(['get_date_created'=>['placed','Order placed'],'get_date_paid'=>['paid','Payment confirmed'],'get_date_completed'=>['completed','Completed']] as $method=>$row){
        $date=$o->$method();if($date){$events[]=['status'=>$row[0],'label'=>$row[1],'at'=>$date->getTimestamp()];}
    }
    foreach(array_slice((array)$o->get_meta('_loomara_journey_events'),-40) as $row){
        if(!is_array($row)||!isset($labels['wc-'.($row['status']??'')])||!is_numeric($row['at']??null)||(int)$row['at']<1){continue;}
        if(($row['status']==='delivered'&&$delivered)||($row['status']==='completed'&&$o->get_date_completed())){continue;}
        $events[]=['status'=>$row['status'],'label'=>$labels['wc-'.$row['status']],'at'=>(int)$row['at']];
    }
    if($delivered){$events[]=['status'=>'delivered','label'=>'Delivered','at'=>$delivered];}
    $requested=(int)$o->get_meta('_loomara_cancellation_requested_124');
    if($requested){$events[]=['status'=>'cancellation-requested','label'=>'Cancellation requested','at'=>$requested];}
    usort($events,static fn($a,$b)=>$a['at']<=>$b['at']);$out=[];$seen=[];
    foreach($events as $event){$key=$event['status'].':'.$event['at'];if(isset($seen[$key])){continue;}$seen[$key]=true;$out[]=$event;}
    return array_slice($out,-44);
}
function loomara_order_journey_panel($o){
    if(!is_account_page()||!is_wc_endpoint_url('view-order')||!loomara_journey_owner($o)){return;}
    $labels=wc_get_order_statuses();$status=$o->get_status();
    echo '<section class="loomara-order-journey" aria-labelledby="order-journey-title"><h2 id="order-journey-title">Order progress</h2><p class="journey-current">Current status: <strong>'.esc_html($labels['wc-'.$status]??wc_get_order_status_name($status)).'</strong></p><ol class="order-journey-events">';
    foreach(loomara_journey_events($o) as $event){echo '<li'.($event['status']===$status?' class="is-current"':'').'><span class="journey-dot" aria-hidden="true"></span><strong>'.esc_html($event['label']).'</strong><time datetime="'.esc_attr(gmdate('c',$event['at'])).'">'.esc_html(wp_date('d M Y · g:i a',$event['at'],new DateTimeZone('Asia/Kolkata'))).' IST</time></li>';}
    echo '</ol><p class="journey-help">Only recorded updates are shown. Older orders may not have every intermediate stage. Shipping updates depend on the courier; refund bank-processing times can vary.</p></section>';
}
add_action('woocommerce_order_details_after_order_table','loomara_order_journey_panel',5);
function loomara_buy_again_eligible($o){return loomara_journey_owner($o)&&in_array($o->get_status(),['delivered','completed','cancelled','refunded'],true);}
/** Preserve exact historical attributes; changed options/customised lines fail closed. */
function loomara_buy_again_line($item){
    $p=$item->get_product();if(!$p||!$p->exists()||!in_array($p->get_type(),['simple','variation'],true)){return false;}
    $parent=$p->is_type('variation')?wc_get_product($p->get_parent_id()):$p;
    if(!$parent||$parent->get_status()!=='publish'||!$parent->is_visible()||post_password_required($parent->get_id())||!$p->is_purchasable()||!$p->is_in_stock()){return false;}
    if($p->is_type('variation')&&((int)$item->get_variation_id()!==$p->get_id()||(int)$item->get_product_id()!==$p->get_parent_id())){return false;}
    $attrs=[];
    if($p->is_type('variation')){foreach($p->get_variation_attributes() as $key=>$now){
        $meta=substr($key,10);$old=$item->get_meta($meta,true);
        if(!is_scalar($old)||(string)$old===''){return false;}
        $value=taxonomy_exists($meta)?sanitize_title((string)$old):wc_clean(html_entity_decode((string)$old,ENT_QUOTES,get_bloginfo('charset')));
        if($now!=='' && $now!==$value){return false;}$attrs[$key]=$value;
    }}
    foreach($item->get_meta_data() as $meta){$key=(string)$meta->key;if($key!==''&&!in_array($key,['_reduced_stock','_restock_refunded_items','_loomara_purchase_sku','_loomara_purchase_image'],true)&&!array_key_exists('attribute_'.$key,$attrs)){return false;}}
    return ['product'=>$p,'product_id'=>$parent->get_id(),'variation_id'=>$p->is_type('variation')?$p->get_id():0,'attributes'=>$attrs];
}
function loomara_buy_again_form($o){
    if(!is_account_page()||!is_wc_endpoint_url('view-order')||!loomara_buy_again_eligible($o)){return;}
    $token=wp_generate_uuid4();$available=0;
    echo '<details class="loomara-buy-again" id="buy-again"><summary>Buy again</summary><p>Review today’s prices and availability. Selected items will be added to your existing cart; previous coupons, shipping charges and customisations are not copied. No payment is made here.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_buy_again"><input type="hidden" name="order_id" value="'.absint($o->get_id()).'"><input type="hidden" name="request_token" value="'.esc_attr($token).'">';
    wp_nonce_field('loomara_buy_again_'.$o->get_id().'_'.$token,'buy_again_nonce');
    foreach($o->get_items() as $id=>$item){$line=loomara_buy_again_line($item);echo '<div class="buy-again-line"><strong>'.esc_html($item->get_name()).'</strong>';
        if(!$line){echo '<p>Unavailable, customised or options have changed. Please choose this item again from the collection.</p></div>';continue;}
        $available++;$p=$line['product'];$qty=max(1,(int)$item->get_quantity());if($p->is_sold_individually()){$qty=1;}
        echo '<span>'.wp_kses_post($p->get_price_html()).' each</span><label><input type="checkbox" name="items['.absint($id).'][selected]" value="yes" checked> Include this item</label><label>Quantity <input type="number" min="1" max="99" step="1" name="items['.absint($id).'][quantity]" value="'.min(99,$qty).'"></label></div>';
    }
    if($available){echo '<button type="submit" class="button">Add selected items to cart</button><p role="status" class="buy-again-status"></p>';}else{echo '<p>No items can currently be reordered. <a href="'.esc_url(wc_get_page_permalink('shop')).'">Explore the collection</a>.</p>';}
    echo '</form></details>';
}
add_action('woocommerce_order_details_after_order_table','loomara_buy_again_form',22);
// Replace the native GET-based order-again button only on this account view.
// The review form never empties the customer's existing cart.
add_action('wp',function(){if(function_exists('is_account_page')&&is_account_page()&&is_wc_endpoint_url('view-order')){remove_action('woocommerce_order_details_after_order_table','woocommerce_order_again_button');}});
add_filter('woocommerce_my_account_my_orders_actions',function($actions,$o){if(loomara_buy_again_eligible($o)){$actions['loomara-buy-again']=['url'=>$o->get_view_order_url().'#buy-again','name'=>'Buy again'];}return $actions;},30,2);
/** Atomic short-lived claim; prevents double-submit/replay without changing orders. */
function loomara_buy_again_claim($id,$token){
    $key='loomara_ba_'.hash('sha256',get_current_user_id().':'.$id.':'.$token);
    if(wp_using_ext_object_cache()){return wp_cache_add($key,1,'loomara_buy_again',2*DAY_IN_SECONDS);}
    get_transient($key); // Allow WordPress to expire an old database-backed claim.
    if(!add_option('_transient_'.$key,1,'','no')){return false;}
    add_option('_transient_timeout_'.$key,time()+2*DAY_IN_SECONDS,'','no');return true;
}
function loomara_buy_again_add($o,$selected,$cart){
    $added=0;$skipped=0;
    if(!loomara_buy_again_eligible($o)){return ['added'=>0,'skipped'=>0];}
    foreach($o->get_items() as $id=>$item){
        $choice=$selected[$id]??null;if(!is_array($choice)||($choice['selected']??'')!=='yes'){continue;}
        $qty=$choice['quantity']??'';$line=loomara_buy_again_line($item);
        if(!is_scalar($qty)||!preg_match('/^[1-9][0-9]?$/D',(string)$qty)||!$line){$skipped++;continue;}
        $qty=(int)$qty;$p=$line['product'];
        if(($p->is_sold_individually()&&$qty>1)||!$p->has_enough_stock($qty)){$skipped++;continue;}
        try{
            $valid=apply_filters('woocommerce_add_to_cart_validation',true,$line['product_id'],$qty,$line['variation_id'],$line['attributes']);
            if($valid&&$cart->add_to_cart($line['product_id'],$qty,$line['variation_id'],$line['attributes'])){$added++;}else{$skipped++;}
        }catch(Throwable $e){$skipped++;}
    }
    return ['added'=>$added,'skipped'=>$skipped];
}
add_action('admin_post_loomara_buy_again',function(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!is_user_logged_in()){wp_die('Please sign in to reorder.','',['response'=>403]);}
    $id=is_scalar($_POST['order_id']??null)?absint($_POST['order_id']):0;$o=wc_get_order($id);
    $token=is_string($_POST['request_token']??null)?wp_unslash($_POST['request_token']):'';
    $nonce=is_string($_POST['buy_again_nonce']??null)?wp_unslash($_POST['buy_again_nonce']):'';
    if(!loomara_buy_again_eligible($o)||!preg_match('/^[a-f0-9-]{36}$/D',$token)||!wp_verify_nonce($nonce,'loomara_buy_again_'.$id.'_'.$token)){wp_die('This reorder request could not be verified.','',['response'=>403]);}
    if(!WC()->cart){wc_load_cart();}
    if(!loomara_buy_again_claim($id,$token)){wc_add_notice('This request was already handled. Please review your cart before trying again.','notice');wp_safe_redirect(wc_get_cart_url());exit;}
    $selected=is_array($_POST['items']??null)?wp_unslash($_POST['items']):[];
    $result=loomara_buy_again_add($o,$selected,WC()->cart);
    if($result['added']){wc_add_notice('Selected available items were added at current prices. Please review your cart before paying.','success');}
    if($result['skipped']){wc_add_notice('Some selected items could not be added because availability, options or purchase limits changed. Please review your cart.','notice');}
    if(!$result['added']&&!$result['skipped']){wc_add_notice('Choose at least one item to buy again.','notice');}
    wp_safe_redirect(wc_get_cart_url());exit;
});
add_action('wp_enqueue_scripts',function(){if(function_exists('is_account_page')&&is_account_page()){
    wp_enqueue_style('loomara-customer-journey',get_template_directory_uri().'/assets/customer-journey.css',[],filemtime(__DIR__.'/../assets/customer-journey.css'));
    wp_enqueue_script('loomara-buy-again',get_template_directory_uri().'/assets/buy-again.js',[],filemtime(__DIR__.'/../assets/buy-again.js'),true);
}});
