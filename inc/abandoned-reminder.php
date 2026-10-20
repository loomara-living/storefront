<?php
/** Next-day reminder only: no status, stock, capture or refund mutations. */
defined('ABSPATH') || exit;
add_action('init',function(){add_option('loomara_abandoned_reminder_since',time(),'','no');});
add_action('woocommerce_after_order_object_save','loomara_abandoned_reminder_queue',20,1);
add_action('loomara_abandoned_reminder','loomara_abandoned_reminder_worker',10,1);
add_filter('woocommerce_email_classes',function($emails){
    require_once __DIR__.'/email-abandoned-reminder.php';
    $emails['Loomara_Email_Abandoned_Reminder']=new Loomara_Email_Abandoned_Reminder();return $emails;
});
function loomara_abandoned_reminder_identity($o){
    if(!$o instanceof WC_Order || $o->get_status()!=='pending' || $o->get_date_paid() ||
        (float)$o->get_total_refunded() || count($o->get_refunds()) || $o->get_transaction_id() ||
        $o->get_currency()!=='INR' || (float)$o->get_total()<=0 ||
        !($o->get_payment_method()==='razorpay' || ($o->get_payment_method()==='' && $o->get_meta('is_magic_checkout_order')==='yes'))){return false;}
    $uid=(int)$o->get_customer_id();$user=$uid?get_userdata($uid):false;
    if(!$user || !is_email($user->user_email) || strcasecmp($user->user_email,$o->get_billing_email())!==0){return false;}
    return ['owner'=>$uid,'email'=>strtolower($user->user_email),'amount'=>(int)round((float)$o->get_total()*100),'mapping'=>loomara_pf_mapping_120($o)];
}
function loomara_abandoned_reminder_schedule($id,$at){
    // Existing WordPress scheduler only; no server cron or repeating scan.
    return wp_schedule_single_event($at,'loomara_abandoned_reminder',[$id],true);
}
function loomara_abandoned_reminder_queue($o){
    $since=(int)get_option('loomara_abandoned_reminder_since',0);
    $created=$o instanceof WC_Order?$o->get_date_created():false;
    if(!$since || !$created || $created->getTimestamp()<$since){return;}
    $identity=loomara_abandoned_reminder_identity($o);if(!$identity){return;}
    $key='loomara_abandoned_reminder_'.$o->get_id();
    $job=['identity'=>$identity,'at'=>time(),'due'=>time()+DAY_IN_SECONDS,'stage'=>'queued'];
    // Start only once a native gateway mapping exists, after checkout is saved.
    if(!$identity['mapping'] || !add_option($key,$job,'','no')){return;}
    $r=loomara_abandoned_reminder_schedule($o->get_id(),$job['due']);
    if(is_wp_error($r)||$r===false){$job['stage']='schedule_failed';update_option($key,$job,false);}
}
function loomara_abandoned_reminder_unpaid($o){
    $maps=loomara_pf_mapping_120($o);if(!$maps || count($maps)>5){return false;}
    $amount=(int)round((float)$o->get_total()*100);$seen=[];
    foreach($maps as $id){
        if(!preg_match('/^order_[A-Za-z0-9]+$/D',$id)){return false;}
        $g=loomara_pf_request_120('orders/'.$id);$p=loomara_pf_request_120('orders/'.$id.'/payments');
        if(is_wp_error($g)||is_wp_error($p) || ($g['entity']??'')!=='order' || ($g['id']??'')!==$id ||
            ($g['currency']??'')!=='INR' || ($g['amount']??null)!==$amount || ($g['amount_paid']??null)!==0 ||
            ($g['amount_due']??null)!==$amount || !empty($g['partial_payment']) ||
            !in_array($g['status']??'',['created','attempted'],true) || !is_int($g['attempts']??null) ||
            $g['attempts']<0 || $g['attempts']>=100 ||
            (!empty($g['notes']['woocommerce_order_id']) && (string)$g['notes']['woocommerce_order_id']!==(string)$o->get_id()) ||
            ($p['entity']??'')!=='collection' || !is_array($p['items']??null) ||
            ($p['count']??null)!==count($p['items']) || count($p['items'])!==$g['attempts']){return false;}
        foreach($p['items'] as $payment){
            $pid=$payment['id']??'';
            if(!is_string($pid)||!preg_match('/^pay_[A-Za-z0-9]+$/D',$pid)||isset($seen[$pid]) ||
                ($payment['entity']??'')!=='payment' || ($payment['order_id']??'')!==$id ||
                ($payment['currency']??'')!=='INR' || ($payment['amount']??null)!==$amount ||
                !in_array($payment['status']??'',['created','failed'],true) || !empty($payment['captured']) ||
                !empty($payment['amount_refunded']) || !empty($payment['invoice_id'])){return false;}
            $seen[$pid]=true;
        }
    }
    return true;
}
function loomara_abandoned_reminder_purchased($identity,$at){
    // Any recorded paid purchase since the attempt suppresses this reminder.
    return (bool)wc_get_orders(['customer'=>$identity['owner'],'date_paid'=>'>='.$at,'limit'=>1,'return'=>'ids']);
}
function loomara_abandoned_reminder_worker($id){
    $id=absint($id);$key='loomara_abandoned_reminder_'.$id;$job=get_option($key);
    if(!is_array($job)||($job['stage']??'')!=='queued'){return;}
    if(time()<$job['due']){loomara_abandoned_reminder_schedule($id,$job['due']);return;}
    // Never steal a crashed lock or blindly resend an uncertain delivery.
    if(!add_option($key.'_lock',time(),'','no')){return;}
    try{
        $o=wc_get_order($id);$identity=loomara_abandoned_reminder_identity($o);
        if(time()>$job['due']+DAY_IN_SECONDS || !$identity || $identity!==$job['identity'] ||
            loomara_abandoned_reminder_purchased($identity,$job['at'])){$job['stage']='skipped';}
        elseif(!loomara_abandoned_reminder_unpaid($o)){$job['stage']='verification_unavailable';}
        else{
            // Reload identity/payment after network waits; never use gateway email.
            $o=wc_get_order($id);$o->get_data_store()->read($o);
            if(loomara_abandoned_reminder_identity($o)!==$identity || loomara_abandoned_reminder_purchased($identity,$job['at'])){$job['stage']='skipped';}
            else{
                $emails=WC()->mailer()->get_emails();$email=$emails['Loomara_Email_Abandoned_Reminder']??null;
                if(!$email || !$email->is_enabled()){$job['stage']='disabled';}
                else{
                    // Same customer/day dedupe prevents several abandoned carts
                    // sending a batch of messages on the same day.
                    $customer_key='loomara_abandoned_customer_'.$identity['owner'].'_'.gmdate('Ymd',$job['due']);
                    if(!add_option($customer_key,$id,'','no')){$job['stage']='duplicate_customer';}
                    else{
                        $job['stage']='sending';update_option($key,$job,false);
                        $job['stage']=$email->send_reminder($o,$identity['email'])?'accepted':'failed';
                    }
                }
            }
        }
    }catch(\Throwable $e){$job['stage']='uncertain';}
    $job['updated']=time();update_option($key,$job,false);
}
