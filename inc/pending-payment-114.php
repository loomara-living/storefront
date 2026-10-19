<?php
/** Future checkout orders only. Read-only gateway checks; never capture/refund a payment. */
defined('ABSPATH') || exit;
function loomara_pending_schedule_114($id,$stage,$due){
    $args=[(int)$id,$stage];
    if(!wp_next_scheduled('loomara_pending_check_114',$args)){wp_schedule_single_event(max(time()+1,$due),'loomara_pending_check_114',$args);}
}
function loomara_pending_start_114($id,$order=false){
    $o=$order instanceof WC_Order?$order:wc_get_order($id);
    if(!$o||!$o->get_meta('_loomara_pending_candidate_114')||$o->get_status()!=='pending'||$o->get_date_paid()||(float)$o->get_total()<=0){return;}
    $key='loomara_pending_start_114_'.$o->get_id();
    add_option($key,time(),'','no');$start=(int)get_option($key);
    if(!$start){return;}
    $o->update_meta_data('_loomara_pending_started_114',$start);$o->save_meta_data();
    loomara_pending_schedule_114($o->get_id(),'remind',$start+15*MINUTE_IN_SECONDS);
    loomara_pending_schedule_114($o->get_id(),'expire',$start+HOUR_IN_SECONDS);
}
add_action('woocommerce_new_order',function($id,$order=false){
    $o=$order instanceof WC_Order?$order:wc_get_order($id);if(!$o||$o->get_type()!=='shop_order'){return;}
    $o->update_meta_data('_loomara_pending_candidate_114',time());$o->save_meta_data();loomara_pending_start_114($id,$o);
},30,2);
add_action('woocommerce_order_status_pending','loomara_pending_start_114',30,2);
add_action('woocommerce_checkout_order_processed',function($id,$data,$o){loomara_pending_start_114($id,$o);},30,3);
add_action('woocommerce_store_api_checkout_order_processed',function($o){loomara_pending_start_114($o->get_id(),$o);},30);
// Avoid Woo's separate hold-stock job cancelling these same orders before verification.
add_filter('woocommerce_cancel_unpaid_order',function($cancel,$o){return $o instanceof WC_Order&&$o->get_payment_method()==='razorpay'&&$o->get_meta('_loomara_pending_started_114')?false:$cancel;},100,2);
function loomara_pending_open_114($o){
    return $o instanceof WC_Order&&$o->get_status()==='pending'&&!$o->get_date_paid()&&!$o->is_paid()&&!(float)$o->get_total_refunded()&&!count($o->get_refunds())&&(float)$o->get_total()>0&&$o->get_payment_method()==='razorpay';
}
function loomara_pending_request_114($path){
    if(!preg_match('~^orders/order_[A-Za-z0-9]+(?:/payments)?$~D',$path)){return new WP_Error('endpoint','Unsupported verification endpoint.');}
    $s=get_option('woocommerce_razorpay_settings',[]);
    if(empty($s['key_id'])||empty($s['key_secret'])){return new WP_Error('connection','Payment verification unavailable.');}
    $r=wp_remote_get('https://api.razorpay.com/v1/'.$path,['timeout'=>15,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>1048576,'headers'=>['Authorization'=>'Basic '.base64_encode($s['key_id'].':'.$s['key_secret']),'Accept'=>'application/json']]);
    if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200){return new WP_Error('gateway','Payment verification unavailable.');}
    $d=json_decode(wp_remote_retrieve_body($r),true);return is_array($d)?$d:new WP_Error('response','Payment response unavailable.');
}
function loomara_pending_gateway_114($o){
    $ids=array_values(array_unique(array_filter([(string)$o->get_meta('razorpay_order_id'.$o->get_id()),(string)$o->get_meta('razorpay_order_id_1cc'.$o->get_id())])));
    if(!$ids||$o->get_currency()!=='INR'){return 'unknown';}
    foreach($ids as $id){
        if(!preg_match('/^order_[A-Za-z0-9]+$/D',$id)){return 'unknown';}
        $r=loomara_pending_request_114('orders/'.$id);$p=loomara_pending_request_114('orders/'.$id.'/payments');
        if(is_wp_error($r)||is_wp_error($p)||($r['entity']??'')!=='order'||($r['id']??'')!==$id||($r['currency']??'')!=='INR'||!is_numeric($r['amount']??null)||(int)$r['amount']!==(int)round((float)$o->get_total()*100)||!is_numeric($r['amount_paid']??null)||(int)$r['amount_paid']!==0||!is_numeric($r['amount_due']??null)||(int)$r['amount_due']!==(int)$r['amount']||!in_array($r['status']??'',['created','attempted'],true)){return 'unknown';}
        if(($p['entity']??'')!=='collection'||!is_array($p['items']??null)||!is_numeric($p['count']??null)||(int)$p['count']!==count($p['items'])||!is_numeric($r['attempts']??null)||(int)$r['attempts']!==count($p['items'])||count($p['items'])>=100){return 'unknown';}
        $seen=[];
        foreach($p['items'] as $payment){
            $pid=$payment['id']??'';
            if(($payment['entity']??'')!=='payment'||($payment['order_id']??'')!==$id||($payment['currency']??'')!=='INR'||!is_string($pid)||!preg_match('/^pay_[A-Za-z0-9]+$/D',$pid)||isset($seen[$pid])||($payment['status']??'')!=='failed'||!empty($payment['captured'])||!empty($payment['amount_refunded'])){return 'unknown';}
            $seen[$pid]=true;
        }
        if($o->get_transaction_id()&&!isset($seen[$o->get_transaction_id()])){return 'unknown';}
    }
    return 'unpaid';
}
function loomara_pending_review_114($o){
    if(add_option('loomara_pending_review_114_'.$o->get_id(),time(),'','no')){$o->add_order_note('Pending-payment timeout paused: payment could not be conclusively verified as unpaid. Review Razorpay before changing the status. No payment, refund or cancellation was submitted.');}
}
function loomara_pending_identity_114($o){return [$o->get_payment_method(),$o->get_currency(),$o->get_total(),$o->get_transaction_id(),$o->get_meta('razorpay_order_id'.$o->get_id()),$o->get_meta('razorpay_order_id_1cc'.$o->get_id())];}
function loomara_pending_worker_114($id,$stage){
    if(!in_array($stage,['remind','expire'],true)){return;}
    $lock='loomara_pending_lock_114_'.absint($id);
    if(!add_option($lock,time(),'','no')){return;}
    try{
        $o=wc_get_order($id);if(!loomara_pending_open_114($o)){return;}
        $start=(int)$o->get_meta('_loomara_pending_started_114');if(!$start){return;}
        $due=$start+($stage==='remind'?15*MINUTE_IN_SECONDS:HOUR_IN_SECONDS);
        if(time()<$due){loomara_pending_schedule_114($id,$stage,$due);return;}
        if($stage==='remind'&&time()>=$start+HOUR_IN_SECONDS){return;}
        $identity=loomara_pending_identity_114($o);$state=loomara_pending_gateway_114($o);
        // Reload persistent fields after the network wait; never reuse a stale pending object.
        $o->get_data_store()->read($o);
        if(!loomara_pending_open_114($o)){return;}
        if($identity!==loomara_pending_identity_114($o)){$state='unknown';}
        if($state!=='unpaid'){
            if($stage==='expire'){loomara_pending_review_114($o);}
            return;
        }
        if($stage==='expire'){
            if(add_option('loomara_pending_expired_114_'.$id,time(),'','no')){$o->update_status('failed','Payment remained unconfirmed one hour after checkout; fresh Razorpay order/payment checks verified no received or authorised payment.');}
        }else{
            $emails=WC()->mailer()->get_emails();
            if(isset($emails['Loomara_Email_Pending_Payment_114'])){$emails['Loomara_Email_Pending_Payment_114']->trigger($id,$o);}
        }
    }finally{delete_option($lock);}
}
add_action('loomara_pending_check_114','loomara_pending_worker_114',10,2);
add_filter('woocommerce_email_classes',function($emails){require_once __DIR__.'/email-pending-payment-114.php';$emails['Loomara_Email_Pending_Payment_114']=new Loomara_Email_Pending_Payment_114();return $emails;});
add_action('woocommerce_after_cart','loomara_cart_coupon_note_114',1);
add_filter('render_block_woocommerce/cart',function($html){if(is_admin()||!is_cart()){return $html;}ob_start();loomara_cart_coupon_note_114();return ob_get_clean().$html;},5);
function loomara_cart_coupon_note_114(){
    static $shown=false;if($shown||!WC()->cart||WC()->cart->is_empty()){return;}$shown=true;
    echo '<aside class="loomara-checkout-offers-note" aria-label="Checkout offers"><span aria-hidden="true">✧</span><div><strong>A little more to look forward to</strong><p>All offers applicable to your order will be shown at checkout. Choose an eligible coupon before you pay.</p></div></aside>';
}
add_action('wp_enqueue_scripts',function(){if(is_cart()){wp_enqueue_style('loomara-pending-114',get_template_directory_uri().'/assets/pending-v114.css',[],wp_get_theme()->get('Version'));}});
