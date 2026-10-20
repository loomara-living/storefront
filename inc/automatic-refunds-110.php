<?php
/** Future Cancelled transitions only. Fixed endpoints, durable intent, fail-closed reconciliation. */
defined('ABSPATH') || exit;
function loomara_ar_enabled() { return (bool)get_option('loomara_auto_refunds_110_enabled',false); }
function loomara_ar_key($id) { return 'loomara_refund_110_'.absint($id); }
function loomara_ar_save($id,$job) { $job['updated']=time(); update_option(loomara_ar_key($id),$job,false); }
function loomara_ar_schedule($id,$delay=60) {
    if (!wp_next_scheduled('loomara_auto_refund_110',[$id])) { wp_schedule_single_event(time()+$delay,'loomara_auto_refund_110',[$id]); }
}
function loomara_ar_transition($id,$from,$to,$order) {
    if (!loomara_ar_enabled() || $to!=='cancelled' || $from==='cancelled' || !$order instanceof WC_Order) { return; }
    // Atomic insert prevents re-entry/duplicate hooks, including Shiprocket callbacks.
    if (!add_option(loomara_ar_key($id),['stage'=>'queued','created'=>time(),'attempts'=>0,'from'=>$from],'','no')) { return; }
    $order->add_order_note('Automatic cancellation refund queued. Shipping and captured payment must be verified; no refund has been confirmed yet.');
    loomara_ar_schedule($id);
}
add_action('woocommerce_order_status_changed','loomara_ar_transition',50,4);

function loomara_ar_sr_state($p) {
    if (!is_array($p) || time()-(int)($p['checked_at']??0)>60 || empty($p['shiprocket_order_id'])) { return 'unknown'; }
    $label=strtoupper(str_replace(['_','-'],' ',trim((string)($p['order_status']??''))));
    $code=(string)($p['order_status_code']??'');
    $cancelled=in_array($code,['5','54'],true)&&in_array($label,['CANCELED','CANCELLED','CANCELED BEFORE DISPATCHED','CANCELLED BEFORE DISPATCHED'],true);
    if($code==='18'&&$label==='CANCELLATION REQUESTED'){return 'waiting';}
    // Canonical API codes, not broad dashboard buckets such as Processing or
    // Yet to Dispatch. Every attached shipment must independently be safe.
    $pre_dispatch=['1'=>'NEW','2'=>'INVOICED','3'=>'READY TO SHIP','4'=>'PICKUP SCHEDULED',
        '12'=>'PICKUP QUEUE','13'=>'PICKUP RESCHEDULED','14'=>'PICKUP ERROR'];
    $safe=isset($pre_dispatch[$code]) && $pre_dispatch[$code]===$label;
    if (!$cancelled&&!$safe) { return 'unknown'; }
    if (!is_array($p['shipments']??null)) { return 'unknown'; }
    foreach ($p['shipments'] as $s) {
        $raw=strtoupper(str_replace(['_','-'],' ',trim((string)($s['status']??''))));
        $sc=is_numeric($raw)?$raw:(string)($s['status_code']??'');
        // Contradictory textual and numeric status must never look cancellable.
        if (!is_numeric($raw) && is_numeric($sc)) {
            $labels=['PENDING'=>['11'],'AWB ASSIGNED'=>['1'],'LABEL GENERATED'=>['2'],'PICKUP SCHEDULED'=>['3'],'PICKUP GENERATED'=>['3'],'PICKUP QUEUED'=>['4'],'MANIFEST GENERATED'=>['5'],'PICKUP ERROR'=>['13'],'PICKUP RESCHEDULED'=>['15'],'CANCELED'=>['8'],'CANCELLED'=>['8'],'CANCELLED BEFORE DISPATCHED'=>['45'],'CANCELED BEFORE DISPATCHED'=>['45']];
            if (!isset($labels[$raw]) || !in_array($sc,$labels[$raw],true)) { return 'unknown'; }
        }
        if ($cancelled) {
            if (!in_array($sc,['8','45'],true) && !in_array($raw,['CANCELED','CANCELLED','CANCELLED BEFORE DISPATCHED','CANCELED BEFORE DISPATCHED'],true)) { return 'waiting'; }
        } elseif (!in_array($sc,['1','2','3','4','5','11','13','15'],true) && !in_array($raw,['PENDING','AWB ASSIGNED','LABEL GENERATED','PICKUP SCHEDULED','PICKUP GENERATED','PICKUP QUEUED','MANIFEST GENERATED','PICKUP ERROR','PICKUP RESCHEDULED'],true)) { return 'unknown'; }
    }
    return $cancelled?'cancelled':'cancellable';
}
function loomara_ar_rzp_request($method,$path,$body=null,$key='') {
    if (!preg_match('~^payments/pay_[A-Za-z0-9]+(?:/refunds\?count=100|/refund)?$~D',$path)) { return new WP_Error('endpoint','Unsupported payment endpoint.'); }
    $s=get_option('woocommerce_razorpay_settings',[]);
    if (empty($s['key_id'])||empty($s['key_secret'])) { return new WP_Error('credentials','Payment connection unavailable.'); }
    $headers=['Authorization'=>'Basic '.base64_encode($s['key_id'].':'.$s['key_secret']),'Content-Type'=>'application/json','Accept'=>'application/json'];
    if ($key) { $headers['X-Refund-Idempotency']=$key; }
    $args=['method'=>$method,'timeout'=>25,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>1048576,'headers'=>$headers];
    if ($body!==null) { $args['body']=wp_json_encode($body); }
    $r=wp_remote_request('https://api.razorpay.com/v1/'.$path,$args);
    if (is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200) { return new WP_Error('gateway','Payment verification unavailable; manual reconciliation may be required.'); }
    $d=json_decode(wp_remote_retrieve_body($r),true);
    return is_array($d)?$d:new WP_Error('response','Payment response could not be verified.');
}
function loomara_ar_payment_plan($order,$p,$refunds) {
    $payment=(string)$order->get_transaction_id();$id=$order->get_id();
    $rzp_orders=array_filter([(string)$order->get_meta('razorpay_order_id'.$id),(string)$order->get_meta('razorpay_order_id_1cc'.$id)]);
    if (!is_array($p)||!is_array($refunds)||($p['entity']??'')!=='payment'||($p['id']??'')!==$payment ||
        !in_array((string)($p['order_id']??''),$rzp_orders,true)||$order->get_currency()!=='INR'||($p['currency']??'')!=='INR'||
        !empty($p['invoice_id'])||!empty($p['disputed'])||(!empty($p['notes']['woocommerce_order_id'])&&(string)$p['notes']['woocommerce_order_id']!==(string)$id)) {
        return new WP_Error('identity','Payment identity/currency requires manual review.');
    }
    $total=(int)round((float)$order->get_total()*100);
    if (!in_array($p['status']??'', ['captured','refunded'],true)||empty($p['captured'])||(int)($p['amount']??0)!==$total||$total<1) {
        return new WP_Error('capture','Captured payment could not be confirmed.');
    }
    if (!is_array($refunds['items']??null)||count($refunds['items'])>=100||(int)($refunds['count']??-1)!==count($refunds['items'])) { return new WP_Error('refunds','Refund list incomplete.'); }
    $sum=0;
    foreach ($refunds['items'] as $r) {
        if (($r['payment_id']??'')!==$payment||($r['currency']??'')!=='INR'||!in_array($r['status']??'', ['processed','failed'],true)) { return new WP_Error('refunds','A pending/unknown refund needs reconciliation first.'); }
        if ($r['status']==='processed') { $sum+=(int)$r['amount']; }
    }
    if ($sum!==(int)($p['amount_refunded']??-1)||$sum!==(int)round((float)$order->get_total_refunded()*100)||$sum>$total) { return new WP_Error('ledger','Gateway and WooCommerce refunds do not reconcile.'); }
    return $total-$sum;
}
function loomara_ar_review($order,$job,$message) {
    $job['stage']='review';$job['message']=$message;loomara_ar_save($order->get_id(),$job);
    $order->add_order_note('Automatic refund needs manual review: '.$message.' No additional refund will be submitted automatically.');
}
function loomara_ar_wait($order,$job,$stage) {
    $job['stage']=$stage;
    if (($job['attempts']??0)>=($stage==='refund_pending'?180:24)) { loomara_ar_review($order,$job,'Verification did not reach a confirmed result.'); return; }
    loomara_ar_save($order->get_id(),$job);loomara_ar_schedule($order->get_id(),$stage==='refund_pending'?3600:60);
}
function loomara_ar_reconcile($order,$job) {
    $list=loomara_ar_rzp_request('GET','payments/'.$job['payment'].'/refunds?count=100');
    if (is_wp_error($list)||!is_array($list['items']??null)||count($list['items'])>=100) { loomara_ar_wait($order,$job,'refund_uncertain');return; }
    $found=array_values(array_filter($list['items'],function($r)use($job){return ($r['receipt']??'')===$job['receipt'];}));
    if (count($found)!==1) { if(count($found)>1){loomara_ar_review($order,$job,'Multiple refund matches.');}else{loomara_ar_wait($order,$job,'refund_uncertain');}return; }
    $r=$found[0];
    if (($r['payment_id']??'')!==$job['payment']||($r['currency']??'')!=='INR'||(int)($r['amount']??0)!==$job['amount']||!preg_match('/^rfnd_[A-Za-z0-9]+$/D',(string)($r['id']??''))) { loomara_ar_review($order,$job,'Refund identity mismatch.');return; }
    $job['refund_id']=$r['id'];
    if (($r['status']??'')==='pending') { loomara_ar_wait($order,$job,'refund_pending');return; }
    if (($r['status']??'')!=='processed') { loomara_ar_review($order,$job,'Razorpay did not confirm a processed refund.');return; }
    $reason='Automatic cancellation refund ['.$job['receipt'].']';
    $existing=[];
    foreach($order->get_refunds() as $refund){if($refund->get_reason()===$reason){$existing[]=$refund;}}
    if(count($existing)>1){loomara_ar_review($order,$job,'Duplicate local refund records require review.');return;}
    if($existing && ((int)round((float)$existing[0]->get_amount()*100)!==$job['amount'] || ($existing[0]->get_meta('_loomara_razorpay_refund_id') && $existing[0]->get_meta('_loomara_razorpay_refund_id')!==$r['id']))){loomara_ar_review($order,$job,'Local refund identity mismatch.');return;}
    if(!$existing){
        if((int)round((float)$order->get_total_refunded()*100)!==$job['before_refunded']){loomara_ar_review($order,$job,'Local refund balance changed during processing.');return;}
        $refund=wc_create_refund(['order_id'=>$order->get_id(),'amount'=>$job['amount']/100,'reason'=>$reason,'refund_payment'=>false,'restock_items'=>false,
            '_loomara_verified_refund'=>array_intersect_key($r,array_flip(['id','payment_id','amount','currency','status','acquirer_data']))]);
        if(is_wp_error($refund)){loomara_ar_review($order,$job,'Gateway refund processed; WooCommerce ledger needs reconciliation. Do not refund again.');return;}
        $refund->update_meta_data('_loomara_razorpay_refund_id',$r['id']);$refund->save();
    }
    $job['stage']='complete';$job['message']='Refund processed by Razorpay; bank posting time may vary.';loomara_ar_save($order->get_id(),$job);
    $order->add_order_note('Automatic cancellation refund processed: '.$r['id'].'. Original payment method; no second gateway refund issued by the WooCommerce ledger.');
}
function loomara_ar_worker($id) {
    if(!loomara_ar_enabled()){return;}
    $job=get_option(loomara_ar_key($id));$order=wc_get_order($id);
    if(!$order||!is_array($job)||in_array($job['stage']??'', ['complete','review','skipped'],true)){return;}
    $lock='loomara_refund_110_lock_'.absint($id);
    // No expiring lock takeover: a crashed financial worker needs manual review.
    if(!add_option($lock,time(),'','no')){return;}
    try {
        $job['attempts']=(int)($job['attempts']??0)+1;
        if(in_array($job['stage'],['refund_submitting','refund_uncertain','refund_pending'],true)){loomara_ar_reconcile($order,$job);return;}
        if(!in_array($job['from']??'', ['pending','on-hold','processing','ready-to-ship'],true)){loomara_ar_review($order,$job,'Previous order status is not a verified pre-dispatch state.');return;}
        if($order->get_status()!=='cancelled'){loomara_ar_review($order,$job,'Order is no longer Cancelled.');return;}
        $payment=(string)$order->get_transaction_id();
        if($order->get_payment_method()!=='razorpay'||!preg_match('/^pay_[A-Za-z0-9]+$/D',$payment)){loomara_ar_review($order,$job,'No supported captured Razorpay payment reference.');return;}
        $p=loomara_ar_rzp_request('GET','payments/'.$payment);
        $rs=loomara_ar_rzp_request('GET','payments/'.$payment.'/refunds?count=100');
        if(is_wp_error($p)||is_wp_error($rs)){loomara_ar_review($order,$job,'Payment service could not be verified.');return;}
        $amount=loomara_ar_payment_plan($order,$p,$rs);
        if(is_wp_error($amount)){loomara_ar_review($order,$job,$amount->get_error_message());return;}
        if($amount===0){$job['stage']='skipped';$job['message']='Payment already fully refunded.';loomara_ar_save($id,$job);return;}
        $sr=loomara_sr_order_preflight($order);
        if(is_wp_error($sr)){loomara_ar_review($order,$job,'Exact current Shiprocket order/shipment match unavailable.');return;}
        if(isset($job['shiprocket_order_id'])&&(int)$job['shiprocket_order_id']!==(int)$sr['shiprocket_order_id']){loomara_ar_review($order,$job,'Shipment identity changed.');return;}
        $state=loomara_ar_sr_state($sr);
        if($state==='unknown'){loomara_ar_review($order,$job,'Shipment dispatched, unknown or otherwise not safely cancellable.');return;}
        if($state==='waiting'){loomara_ar_wait($order,$job,'shipping_wait');return;}
        if($state==='cancellable'){
            // After an uncertain cancellation, only poll; never blindly resubmit.
            if($job['stage']==='shipping_wait'){loomara_ar_wait($order,$job,'shipping_wait');return;}
            if(!loomara_ar_enabled()){return;}
            $job['shiprocket_order_id']=$sr['shiprocket_order_id'];$job['stage']='shipping_wait';loomara_ar_save($id,$job);
            $token=loomara_sr_read_token();
            if(is_wp_error($token)){loomara_ar_review($order,$job,'Shipment cancellation authentication unavailable.');return;}
            wp_remote_post('https://apiv2.shiprocket.in/v1/external/orders/cancel',['timeout'=>20,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>65536,'headers'=>['Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'],'body'=>wp_json_encode(['ids'=>[(int)$sr['shiprocket_order_id']]])]);
            // A success/204 is only acknowledgement: verify final order + shipments on a fresh run.
            loomara_ar_wait($order,$job,'shipping_wait');return;
        }
        // Re-read the Woo order immediately before monetary intent is persisted.
        $order=wc_get_order($id);
        if($order->get_status()!=='cancelled'||$order->get_payment_method()!=='razorpay'||$order->get_transaction_id()!==$payment||loomara_ar_payment_plan($order,$p,$rs)!==$amount){loomara_ar_review($order,$job,'Order/payment changed during verification.');return;}
        if(!loomara_ar_enabled()){return;}
        $job['payment']=$payment;$job['amount']=$amount;$job['before_refunded']=(int)$p['amount_refunded'];
        $job['receipt']='LM-CANCEL-'.substr(hash('sha256',home_url('/').'|'.$id.'|'.$payment),0,28);
        $job['body']=['amount'=>$amount,'speed'=>'normal','receipt'=>$job['receipt'],'notes'=>['refund_from_website'=>'true','woocommerce_order_id'=>(string)$id,'comment'=>'Order cancellation']];
        $job['stage']='refund_submitting';loomara_ar_save($id,$job);
        $saved=get_option(loomara_ar_key($id));
        if (!is_array($saved)||($saved['stage']??'')!=='refund_submitting'||($saved['body']??null)!==$job['body']) { throw new RuntimeException('Durable refund intent could not be verified.'); }
        // Once submitted, all subsequent work is GET-only reconciliation, even after timeout/crash.
        loomara_ar_rzp_request('POST','payments/'.$payment.'/refund',$job['body'],$job['receipt']);
        loomara_ar_wait($order,$job,'refund_uncertain');
    } catch(Throwable $e) { loomara_ar_review($order,$job,'Unexpected processing error; review required before any further action.'); }
    finally { delete_option($lock); }
}
add_action('loomara_auto_refund_110','loomara_ar_worker');

add_action('admin_menu',function(){add_submenu_page('woocommerce','Automatic cancellation refunds','Automatic refunds','manage_options','loomara-auto-refunds','loomara_ar_page');});
function loomara_ar_page(){
    if(!current_user_can('manage_options')){return;}
    echo '<div class="wrap"><h1>Automatic cancellation refunds</h1><p>Status: <strong>'.(loomara_ar_enabled()?'Enabled for future Cancelled transitions':'Disabled').'</strong></p><p>Customer, team and external status changes are included. Existing cancelled orders are not scanned. Exact Shiprocket cancellation and captured Razorpay payment must be verified. Unknown, dispatched, unmatched, partial-ledger conflicts or failed cases require manual review. Normal refunds return to the original payment method.</p><p>No real order is cancelled or refunded by saving this setting. Future cancellations can move money. Monitor WooCommerce order notes and WP-Cron. Uncertain responses are reconciled read-only, never blindly refunded again.</p><p>Background scheduler: '.((defined('DISABLE_WP_CRON')&&DISABLE_WP_CRON)?'Disabled — configure and verify a server scheduler before activation.':'WordPress cron enabled; jobs run on subsequent website requests.').'</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_auto_refunds_toggle">';wp_nonce_field('loomara_auto_refunds_toggle');
    echo '<label><input name="enabled" type="checkbox" value="1" '.checked(loomara_ar_enabled(),true,false).'> Enable automatic refunds for future cancellations</label><p><button class="button button-primary">Save automation setting</button></p></form></div>';
}
add_action('admin_post_loomara_auto_refunds_toggle',function(){
    if(!current_user_can('manage_options')){wp_die('Administrator access required.',403);}check_admin_referer('loomara_auto_refunds_toggle');
    $enable=isset($_POST['enabled'])&&$_POST['enabled']==='1';
    if($enable){$settings=get_option('woocommerce_razorpay_settings',[]);if(!class_exists('WC_Razorpay')||!loomara_sr_credentials()||is_wp_error(loomara_sr_woocommerce_channel())||empty($settings['key_id'])||empty($settings['key_secret'])||(defined('DISABLE_WP_CRON')&&DISABLE_WP_CRON)){wp_die('Verify the saved payment/shipping connections and background scheduler before activation.',409);}}
    update_option('loomara_auto_refunds_110_enabled',$enable,false);
    wp_safe_redirect(admin_url('admin.php?page=loomara-auto-refunds'));exit;
});
function loomara_ar_order_message($order){
    if(!$order instanceof WC_Order){return;}$job=get_option(loomara_ar_key($order->get_id()));if(!is_array($job)){return;}
    // The shared account/detail notice already explains recorded refunds and refund email.
    if (($job['stage']??'')==='complete' && function_exists('loomara_customer_refund_message') && loomara_customer_refund_message($order)) { return; }
    $stage=$job['stage']??'';$message=$stage==='complete'?'Refund processed by Razorpay. Bank posting time may vary.':($stage==='review'?'Your cancellation refund needs manual review. Please contact us.':($stage==='skipped'?'No additional automatic refund is due.':'Your cancellation refund is being verified. It is not confirmed until payment and shipment checks pass.'));
    echo '<p class="woocommerce-info">'.esc_html($message).'</p>';
}
add_action('woocommerce_order_details_after_order_table','loomara_ar_order_message');
