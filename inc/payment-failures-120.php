<?php
/** Signed failure events only. No abandonment timers, payment capture or refunds. */
defined('ABSPATH') || exit;

function loomara_pf_config_120() { $c=get_option('loomara_payment_failure_connection_120', []); return is_array($c)?$c:[]; }
function loomara_pf_key_120() {
    $s = get_option('woocommerce_razorpay_settings', []);
    return !empty($s['key_id']) ? hash('sha256', $s['key_id']) : '';
}
function loomara_pf_ready_120() {
    $c = loomara_pf_config_120();
    return ($c['stage'] ?? '') === 'connected' && !empty($c['key']) && hash_equals($c['key'], loomara_pf_key_120());
}
function loomara_pf_secret_120() {
    $c = loomara_pf_config_120();
    $raw = base64_decode($c['secret'] ?? '', true);
    if (!$raw || strlen($raw) < 29 || !function_exists('openssl_decrypt')) { return false; }
    return openssl_decrypt(substr($raw,28), 'aes-256-gcm', hash('sha256',wp_salt('auth'),true), OPENSSL_RAW_DATA, substr($raw,0,12), substr($raw,12,16), 'loomara-payment-failure-120');
}
function loomara_pf_request_120($path, $method = 'GET', $body = null) {
    $read = preg_match('~^(orders/order_[A-Za-z0-9]+(?:/payments)?|webhooks(?:\?count=100&skip=[0-9]+)?)$~D', $path);
    if (!$read || !in_array($method,['GET','POST'],true) || ($method==='POST' && $path!=='webhooks')) { return new WP_Error('endpoint','Unsupported verification endpoint.'); }
    $s = get_option('woocommerce_razorpay_settings', []);
    if (empty($s['key_id']) || empty($s['key_secret'])) { return new WP_Error('connection','Razorpay connection unavailable.'); }
    $args = ['method'=>$method,'timeout'=>20,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>1048576,
        'headers'=>['Authorization'=>'Basic '.base64_encode($s['key_id'].':'.$s['key_secret']),'Content-Type'=>'application/json','Accept'=>'application/json']];
    if ($body !== null) { $args['body'] = wp_json_encode($body); }
    $r = wp_remote_request('https://api.razorpay.com/v1/'.$path, $args);
    if (is_wp_error($r) || !in_array(wp_remote_retrieve_response_code($r),[200,201],true)) { return new WP_Error('gateway','Razorpay verification unavailable; no payment status changed.'); }
    $d = json_decode(wp_remote_retrieve_body($r),true);
    return is_array($d) ? $d : new WP_Error('response','Unexpected gateway response.');
}
function loomara_pf_new_order_120($order) {
    $c=loomara_pf_config_120();
    if ($order instanceof WC_Order && !$order->get_meta('_loomara_failure_120_eligible') && loomara_pf_ready_120() && (!$order->get_date_created() || $order->get_date_created()->getTimestamp()>=(int)($c['enabled_at']??PHP_INT_MAX))) {
        $order->update_meta_data('_loomara_failure_120_eligible', ['at'=>time(),'key'=>loomara_pf_key_120()]);
    }
}
add_action('woocommerce_checkout_create_order','loomara_pf_new_order_120',110);
add_action('woocommerce_store_api_checkout_update_order_from_request', 'loomara_pf_new_order_120', 110);

function loomara_pf_mapping_120($o) {
    return array_values(array_unique(array_filter([(string)$o->get_meta('razorpay_order_id'.$o->get_id()), (string)$o->get_meta('razorpay_order_id_1cc'.$o->get_id())])));
}
function loomara_pf_eligible_120($o) {
    if (!$o instanceof WC_Order || !in_array($o->get_status(),['pending','checkout-draft','draft','failed'],true) || $o->get_date_paid() || (float)$o->get_total_refunded() || count($o->get_refunds()) || $o->get_currency()!=='INR' || (float)$o->get_total()<=0) { return false; }
    $e = $o->get_meta('_loomara_failure_120_eligible');
    return is_array($e) && !empty($e['at']) && ($e['key']??'')===loomara_pf_key_120() &&
        ($o->get_payment_method()==='razorpay' || ($o->get_payment_method()==='' && $o->get_meta('is_magic_checkout_order')==='yes'));
}
function loomara_pf_permission_120($request) {
    if (!loomara_pf_ready_120()) { return new WP_Error('not_ready','Payment failure receiver unavailable.', ['status'=>503]); }
    $body = $request->get_body(); $signature = $request->get_header('x-razorpay-signature'); $secret = loomara_pf_secret_120();
    if (!is_string($body) || strlen($body)>262144 || !is_string($signature) || !preg_match('/^[a-f0-9]{64}$/D',$signature) || !$secret || !hash_equals(hash_hmac('sha256',$body,$secret),$signature)) {
        return new WP_Error('signature','Webhook authentication failed.', ['status'=>401]);
    }
    return true;
}
add_action('rest_api_init',function(){ register_rest_route('loomara/v1','/razorpay-failures',[
    'methods'=>'POST','callback'=>'loomara_pf_receive_120','permission_callback'=>'loomara_pf_permission_120']); });
function loomara_pf_receive_120($request) {
    $auth = loomara_pf_permission_120($request); if (is_wp_error($auth)) { return $auth; }
    $d = json_decode($request->get_body(), true);
    if (!is_array($d) || ($d['event']??'')!=='payment.failed') { return new WP_REST_Response(['received'=>true,'ignored'=>true]); }
    $p = $d['payload']['payment']['entity'] ?? [];
    $id = $p['notes']['woocommerce_order_id'] ?? '';
    if (!is_scalar($id) || !ctype_digit((string)$id) || !$id || !preg_match('/^pay_[A-Za-z0-9]+$/D',(string)($p['id']??'')) || !preg_match('/^order_[A-Za-z0-9]+$/D',(string)($p['order_id']??'')) || ($p['entity']??'')!=='payment' || ($p['status']??'')!=='failed' || !empty($p['invoice_id'])) {
        return new WP_Error('payload','Invalid payment-failure identity.', ['status'=>400]);
    }
    $o = wc_get_order((int)$id);
    if (!loomara_pf_eligible_120($o)) { return new WP_REST_Response(['received'=>true,'ignored'=>true]); }
    $e = $o->get_meta('_loomara_failure_120_eligible'); $total = (int)round((float)$o->get_total()*100);
    if (!in_array($p['order_id'],loomara_pf_mapping_120($o),true) || ($p['currency']??'')!=='INR' || !is_int($p['amount']??null) || $p['amount']!==$total || !is_int($d['created_at']??null) || $d['created_at']<(int)$e['at'] || $d['created_at']>time()+300) {
        return new WP_Error('identity','Payment identity does not match this checkout.', ['status'=>409]);
    }
    if (!function_exists('as_enqueue_async_action')) { return new WP_Error('queue','Background verification unavailable.', ['status'=>503]); }
    $key = 'loomara_failed_120_'.$o->get_id().'_'.$p['id'];
    if (!get_option($key)) { add_option($key, ['stage'=>'queued','order'=>$o->get_id(),'payment'=>$p['id'],'gateway_order'=>$p['order_id'],'at'=>time(),'attempts'=>0], '', false); }
    $job = get_option($key);
    if (($job['stage']??'')==='queued' && !as_has_scheduled_action('loomara_verify_failure_120',[$key],'loomara-payments')) {
        if (!as_enqueue_async_action('loomara_verify_failure_120',[$key],'loomara-payments',true)) { return new WP_Error('queue','Could not queue verification.', ['status'=>503]); }
    }
    return new WP_REST_Response(['received'=>true]);
}

/** Require a complete, exact payment-attempt list for EVERY saved gateway order. */
function loomara_pf_plan_120($o,$job,$snapshots) {
    if (!loomara_pf_eligible_120($o)) { return 'protected'; }
    $mapping = loomara_pf_mapping_120($o); $total=(int)round((float)$o->get_total()*100); $found=false; $failed=[];
    if (!$mapping || !in_array($job['gateway_order'],$mapping,true) || count($mapping)!==count($snapshots)) { return 'review'; }
    foreach ($mapping as $gateway) {
        $g=$snapshots[$gateway]['order']??[]; $list=$snapshots[$gateway]['payments']??[];
        if (($g['entity']??'')!=='order' || ($g['id']??'')!==$gateway || ($g['currency']??'')!=='INR' || ($g['amount']??null)!==$total || !in_array($g['status']??'', ['created','attempted'],true) || ($g['amount_paid']??null)!==0 || ($g['amount_due']??null)!==$total || !empty($g['partial_payment']) || !is_int($g['attempts']??null) || $g['attempts']<1 || $g['attempts']>=100 || !empty($g['notes']['woocommerce_order_id']) && (string)$g['notes']['woocommerce_order_id']!==(string)$o->get_id()) { return 'review'; }
        if (($list['entity']??'')!=='collection' || !is_array($list['items']??null) || ($list['count']??null)!==count($list['items']) || count($list['items'])!==$g['attempts']) { return 'review'; }
        foreach ($list['items'] as $p) {
            $pid=$p['id']??'';
            if (!is_string($pid) || !preg_match('/^pay_[A-Za-z0-9]+$/D',$pid) || isset($failed[$pid]) || ($p['entity']??'')!=='payment' || ($p['order_id']??'')!==$gateway || ($p['currency']??'')!=='INR' || ($p['amount']??null)!==$total || !empty($p['invoice_id']) || !empty($p['captured']) || !empty($p['amount_refunded']) || ($p['status']??'')!=='failed' || (string)($p['notes']['woocommerce_order_id']??'')!==(string)$o->get_id()) { return 'review'; }
            $failed[$pid]=true;
            if ($pid===$job['payment'] && $gateway===$job['gateway_order']) { $found=true; }
        }
    }
    if ($o->get_transaction_id() && !isset($failed[$o->get_transaction_id()])) { return 'review'; }
    return $found ? 'failed' : 'review';
}
function loomara_pf_finish_120($key,$job,$stage,$o=null) {
    $job['stage']=$stage; $job['updated']=time(); update_option($key,$job,false);
    if ($stage==='review' && $o) { $o->add_order_note('Payment-failure sync needs review: payment attempts could not all be confirmed failed. No order status was changed by this verifier.'); }
}
/** Serialize the final status write with native gateway writes to the order row. */
function loomara_pf_apply_120($job,$snapshots,$mapping) {
    global $wpdb;
    $hpos=class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    $table=$hpos?$wpdb->prefix.'wc_orders':$wpdb->posts;
    $engine=$wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s',$wpdb->esc_like($table)));
    if (!$engine || strcasecmp($engine->Engine??'','InnoDB')!==0) { return 'review'; }
    if ($wpdb->query('START TRANSACTION')===false) { return 'review'; }
    try {
        $column=$hpos?'id':'ID'; $status_column=$hpos?'status':'post_status';
        $locked=$wpdb->get_row($wpdb->prepare("SELECT $column AS native_id, $status_column AS order_status FROM `$table` WHERE $column = %d FOR UPDATE",(int)$job['order']));
        if (!$locked || (int)$locked->native_id!==(int)$job['order']) { return 'review'; }
        if (!in_array(preg_replace('/^wc-/','',$locked->order_status),['pending','checkout-draft','draft','failed'],true)) { return 'protected'; }
        $o=wc_get_order($job['order']); if ($o) { $o->get_data_store()->read($o); }
        $plan=loomara_pf_plan_120($o,$job,$snapshots);
        if ($plan!=='failed' || $mapping!==loomara_pf_mapping_120($o)) { return $plan==='protected'?'protected':'review'; }
        $o->update_meta_data('_loomara_verified_payment_failure_120',['payment'=>$job['payment'],'gateway_order'=>$job['gateway_order'],'at'=>time()]);
        $o->save_meta_data();
        if ($o->get_status()!=='failed' && !$o->update_status('failed','Payment failed: Razorpay failure event and all saved gateway-order attempts verified. Customer can retry payment on this same order.')) { return 'review'; }
        if ($wpdb->query('COMMIT')===false) { return 'review'; }
        return 'complete';
    } finally { $wpdb->query('ROLLBACK'); }
}
function loomara_pf_worker_120($key) {
    if (!is_string($key) || !preg_match('/^loomara_failed_120_[0-9]+_pay_[A-Za-z0-9]+$/D',$key) || !loomara_pf_ready_120()) { return; }
    $job=get_option($key); if (!is_array($job) || !in_array($job['stage']??'', ['queued','waiting'],true)) { return; }
    $lock='loomara_failure_lock_120_'.(int)$job['order'];
    if (!add_option($lock,time(),'',false)) {
        // Never steal a crashed lock. A bounded delay lets a concurrent attempt finish.
        $job['contention']=(int)($job['contention']??0)+1;
        if ($job['contention']<=3 && function_exists('as_schedule_single_action')) {
            update_option($key,$job,false);
            if (as_schedule_single_action(time()+120,'loomara_verify_failure_120',[$key],'loomara-payments',false)) { return; }
        }
        loomara_pf_finish_120($key,$job,'review',wc_get_order($job['order'])); return;
    }
    try {
        $job=get_option($key); if (!in_array($job['stage']??'', ['queued','waiting'],true)) { return; }
        $o=wc_get_order($job['order']);
        if (!loomara_pf_eligible_120($o)) { loomara_pf_finish_120($key,$job,'protected'); return; }
        $mapping=loomara_pf_mapping_120($o); $snapshots=[]; $job['attempts']++;
        foreach ($mapping as $gateway) {
            if (!preg_match('/^order_[A-Za-z0-9]+$/D',$gateway)) { break; }
            $g=loomara_pf_request_120('orders/'.$gateway); $p=loomara_pf_request_120('orders/'.$gateway.'/payments');
            if (is_wp_error($g) || is_wp_error($p)) { break; }
            $snapshots[$gateway]=['order'=>$g,'payments'=>$p];
        }
        // Gateway waits may overlap a successful callback. Reload persisted state.
        $o=wc_get_order($job['order']); if ($o && method_exists($o,'get_data_store')) { $o->get_data_store()->read($o); }
        $plan=loomara_pf_plan_120($o,$job,$snapshots);
        if ($plan==='protected') { loomara_pf_finish_120($key,$job,'protected'); return; }
        if ($plan!=='failed' || $mapping!==loomara_pf_mapping_120($o)) {
            if ($job['attempts']<3 && function_exists('as_schedule_single_action')) {
                $job['stage']='waiting'; update_option($key,$job,false);
                $scheduled=as_schedule_single_action(time()+($job['attempts']===1?120:600),'loomara_verify_failure_120',[$key],'loomara-payments',false);
                if ($scheduled) { return; }
            }
            loomara_pf_finish_120($key,$job,'review',$o); return;
        }
        $applied=loomara_pf_apply_120($job,$snapshots,$mapping);
        loomara_pf_finish_120($key,$job,$applied,$applied==='review'?$o:null);
    } catch (Throwable $e) { loomara_pf_finish_120($key,$job,'review',isset($o)?$o:null); }
    finally { delete_option($lock); }
}
add_action('loomara_verify_failure_120','loomara_pf_worker_120');

function loomara_pf_visible_120($o) { return $o instanceof WC_Order && $o->get_status()==='failed' && is_array($o->get_meta('_loomara_verified_payment_failure_120')); }
add_filter('wc_order_statuses',function($statuses){if(isset($statuses['wc-failed'])){$statuses['wc-failed']='Payment failed';}return $statuses;});
add_filter('woocommerce_my_account_my_orders_actions',function($actions,$o){
    if (loomara_pf_visible_120($o) && get_current_user_id()>0 && (int)$o->get_customer_id()===get_current_user_id() && $o->needs_payment() && !$o->get_date_paid() && !(float)$o->get_total_refunded() && !count($o->get_refunds())) {
        $actions['pay']=['url'=>$o->get_checkout_payment_url(),'name'=>'Retry payment'];
    }
    return $actions;
},100,2);
