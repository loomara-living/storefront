<?php
/** Next-day reminder only: no status, stock, capture or refund mutations. */
defined('ABSPATH') || exit;
function loomara_abandoned_reminders_enabled(){return get_option('loomara_abandoned_reminders_enabled','yes')==='yes';}
function loomara_abandoned_reminder_settings(){
    if(!current_user_can('manage_options')){return;}
    $enabled=loomara_abandoned_reminders_enabled();
    echo '<section style="margin-top:24px;max-width:850px"><h2>Payment-attempt reminders</h2><p><strong>Status: '.($enabled?'Enabled':'Disabled').'</strong></p><p>One email 24 hours after an eligible unpaid attempt. Payment is verified first; this does not change order status. WordPress scheduling can run late. The WooCommerce reminder email must also be enabled.</p><p>Disabling stops new enrollment and suppresses outstanding reminders, even after re-enabling. Re-enabling applies only to new orders; completed reminders are never resent.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_abandoned_toggle"><input type="hidden" name="enabled" value="'.($enabled?'no':'yes').'">';
    wp_nonce_field('loomara_abandoned_toggle');
    echo '<button type="submit" class="button button-primary">'.($enabled?'Disable reminders':'Enable reminders').'</button></form></section>';
}
function loomara_abandoned_reminder_toggle(){
    if(!current_user_can('manage_options')){wp_die('Not allowed.','',['response'=>403]);}
    check_admin_referer('loomara_abandoned_toggle');
    $enabled=isset($_POST['enabled'])&&is_string($_POST['enabled'])&&$_POST['enabled']==='yes';
    $old=loomara_abandoned_reminders_enabled();
    if($old!==$enabled){
        if(!$enabled){update_option('loomara_abandoned_disabled_at',time(),false);}
        else{update_option('loomara_abandoned_enroll_after',time(),false);}
        update_option('loomara_abandoned_reminders_enabled',$enabled?'yes':'no',false);
    }
    wp_safe_redirect(admin_url('admin.php?page=loomara-settings'));exit;
}
add_action('admin_post_loomara_abandoned_toggle','loomara_abandoned_reminder_toggle');
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
/** Private audit only. A note failure must never change the reminder/send result. */
function loomara_abandoned_reminder_note($o,$stage){
    $messages=[
        'queued'=>'Reminder email queued — scheduled 24 hours after this payment attempt, subject to payment checks and scheduler availability.',
        'schedule_failed'=>'Reminder email could not be queued. Scheduler setup requires review; no reminder sent.',
        'accepted'=>'Reminder email accepted by the mail service. Inbox delivery is not confirmed; no automatic repeat will be sent.',
        'failed'=>'Reminder email was not accepted by the mail service. Review delivery before any manual resend.',
        'uncertain'=>'Reminder email outcome is uncertain. Review mail logs before resending; no automatic repeat will be sent.',
        'skipped'=>'Reminder email skipped — eligibility changed, a later purchase was recorded, or the reminder window expired.',
        'verification_unavailable'=>'Reminder email not sent — unpaid payment status could not be verified. Manual review required.',
        'disabled'=>'Reminder email not sent — the reminder email is disabled.',
        'duplicate_customer'=>'Reminder email skipped — another reminder already claimed this customer’s daily notification.',
    ];
    if(!$o instanceof WC_Order||!isset($messages[$stage])){return;}
    $key='loomara_abandoned_note_'.$o->get_id().'_'.$stage;
    if(!add_option($key,'writing','','no')){return;}
    try{$o->add_order_note($messages[$stage],false,false);update_option($key,'recorded',false);}
    catch(\Throwable $e){update_option($key,'failed',false);}
}
function loomara_abandoned_reminder_queue($o){
    if(!loomara_abandoned_reminders_enabled()){return;}
    $since=(int)get_option('loomara_abandoned_reminder_since',0);
    $since=max($since,(int)get_option('loomara_abandoned_enroll_after',0));
    $created=$o instanceof WC_Order?$o->get_date_created():false;
    if(!$since || !$created || $created->getTimestamp()<$since){return;}
    $identity=loomara_abandoned_reminder_identity($o);if(!$identity){return;}
    $key='loomara_abandoned_reminder_'.$o->get_id();
    $job=['identity'=>$identity,'at'=>time(),'due'=>time()+DAY_IN_SECONDS,'stage'=>'queued'];
    // Start only once a native gateway mapping exists, after checkout is saved.
    if(!$identity['mapping'] || !add_option($key,$job,'','no')){return;}
    $r=loomara_abandoned_reminder_schedule($o->get_id(),$job['due']);
    if(is_wp_error($r)||$r===false){$job['stage']='schedule_failed';update_option($key,$job,false);}
    loomara_abandoned_reminder_note($o,$job['stage']);
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
    if(!loomara_abandoned_reminders_enabled()||(int)($job['at']??0)<=(int)get_option('loomara_abandoned_disabled_at',0)){
        $job['stage']='disabled';$job['updated']=time();update_option($key,$job,false);
        $o=wc_get_order($id);if($o instanceof WC_Order){loomara_abandoned_reminder_note($o,'disabled');}return;
    }
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
                if(!$email || !$email->is_enabled() || !loomara_abandoned_reminders_enabled() || (int)$job['at']<=(int)get_option('loomara_abandoned_disabled_at',0)){$job['stage']='disabled';}
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
    if(isset($o)&&$o instanceof WC_Order){loomara_abandoned_reminder_note($o,$job['stage']);}
}
