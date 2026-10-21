<?php
/** Seven-day paid Processing policy. Disabled until explicitly enabled at rollout. */
defined('ABSPATH') || exit;
function loomara_processing_age_since($order){
    $paid=$order->get_date_paid();if(!$paid){return 0;}
    $entered=(int)$order->get_meta('_loomara_processing_entered_at');
    // Old orders have no transition timestamp. Last modification is deliberately
    // conservative: never infer that a recently changed order spent seven days here.
    if(!$entered){$modified=$order->get_date_modified();$entered=$modified?$modified->getTimestamp():0;}
    return $entered?max($paid->getTimestamp(),$entered):0;
}
function loomara_processing_age_eligible($order){
    if(!$order instanceof WC_Order || $order->get_status()!=='processing' || $order->get_payment_method()!=='razorpay' ||
        !$order->get_date_paid() || $order->get_meta('_shipped_date') || $order->get_meta('_loomara_return_hold_111')==='yes' ||
        apply_filters('loomara_order_completion_on_hold',false,$order) || (float)$order->get_total_refunded()>0 || count($order->get_refunds()) ||
        (int)$order->get_customer_id()<1 || !preg_match('/^pay_[A-Za-z0-9]+$/D',(string)$order->get_transaction_id())){return false;}
    $since=loomara_processing_age_since($order);return $since>0 && time()>=$since+7*DAY_IN_SECONDS;
}
add_action('woocommerce_order_status_changed',function($id,$from,$to,$order){
    if($to==='processing' && $from!=='processing' && $order instanceof WC_Order){
        $order->update_meta_data('_loomara_processing_entered_at',time());$order->save_meta_data();
    }
},30,4);
function loomara_processing_age_request($id){
    if(get_option('loomara_processing_age_enabled')!=='yes'){return;}
    $lock='loomara_cancel_order_lock_'.absint($id);
    if(!add_option($lock,time(),'','no')){return;}
    try{
        $order=wc_get_order($id);
        if(!loomara_processing_age_eligible($order) || loomara_cr_job($order)){return;}
        // Freeze the verified historical baseline before our own note/meta saves
        // change date_modified; never slide the seven-day deadline ourselves.
        if(!(int)$order->get_meta('_loomara_processing_entered_at')){
            $order->update_meta_data('_loomara_processing_entered_at',loomara_processing_age_since($order));$order->save_meta_data();
        }
        loomara_cr_create($order,'processing-age');
    }finally{delete_option($lock);}
}
function loomara_processing_age_scan($page=1){
    if(get_option('loomara_processing_age_enabled')!=='yes'){return;}
    // Include existing records, but only queue guarded requests; no direct
    // financial calls, status rewrites or existing intent/review resets here.
    $ids=wc_get_orders(['status'=>'processing','limit'=>50,'page'=>max(1,(int)$page),'orderby'=>'ID','order'=>'ASC','return'=>'ids']);
    foreach($ids as $id){loomara_processing_age_request($id);}
    if(count($ids)===50 && !wp_next_scheduled('loomara_processing_age_batch',[(int)$page+1])){
        wp_schedule_single_event(time()+60,'loomara_processing_age_batch',[(int)$page+1]);
    }
}
add_action('loomara_processing_age_scan','loomara_processing_age_scan');
add_action('loomara_processing_age_batch','loomara_processing_age_scan');
add_action('init',function(){
    if(get_option('loomara_processing_age_enabled')==='yes' && !wp_next_scheduled('loomara_processing_age_scan')){
        wp_schedule_event(time()+60,'hourly','loomara_processing_age_scan');
    }
});
function loomara_processing_age_settings(){
    if(!current_user_can('manage_options')){return;}
    $enabled=get_option('loomara_processing_age_enabled')==='yes';
    echo '<section style="max-width:1060px;margin-top:24px;padding:20px;background:white;border:1px solid #c3c4c7;border-radius:8px"><h2>Seven-day Processing cancellation</h2><p><strong>Status: '.($enabled?'Enabled':'Disabled').'</strong></p><p>Checks paid Processing orders hourly, including existing orders. Seven days starts at the later of payment and Processing entry; older orders without a transition timestamp use their last modification conservatively. WordPress scheduling can run late.</p><p>Eligible orders enter the existing Shiprocket cancellation checks. Only exact carrier cancellation confirmation permits WooCommerce cancellation; captured payment and refundable balance are independently checked before any automatic refund. Shipped, disputed, refunded, unbound or unverifiable orders are not blindly cancelled. Existing review records are not replayed.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    wp_nonce_field('loomara_processing_age_settings');
    echo '<input type="hidden" name="action" value="loomara_processing_age_settings"><input type="hidden" name="enabled" value="'.($enabled?'no':'yes').'"><button class="button '.($enabled?'':'button-primary').'">'.($enabled?'Stop new seven-day cancellation requests':'Enable seven-day checks — includes past eligible orders').'</button></form><p>Enabling can lead to real cancellations and refunds. Disabling stops new enrollment; already-started cancellation/refund intents remain governed by their existing safeguards and must be reconciled, not erased.</p></section>';
}
add_action('admin_post_loomara_processing_age_settings',function(){
    if(!current_user_can('manage_options')){wp_die('Administrator access required.',403);}
    check_admin_referer('loomara_processing_age_settings');
    $enabled=isset($_POST['enabled']) && is_string($_POST['enabled']) && $_POST['enabled']==='yes';
    update_option('loomara_processing_age_enabled',$enabled?'yes':'no',false);
    if(!$enabled){wp_clear_scheduled_hook('loomara_processing_age_scan');wp_clear_scheduled_hook('loomara_processing_age_batch');}
    wp_safe_redirect(admin_url('admin.php?page=loomara-settings'));exit;
});
