<?php
/** Future delivery notifications and seven-calendar-day completion, without historic backfill. */
defined('ABSPATH') || exit;
add_filter('woocommerce_email_actions',function($actions){return array_values(array_unique(array_merge($actions,['woocommerce_order_status_shipped','woocommerce_order_status_delivered'])));});
add_filter('woocommerce_email_classes',function($emails){
    require_once __DIR__.'/email-fulfilment-111.php';
    $emails['Loomara_Email_Shipped']=new Loomara_Email_Shipped();
    $emails['Loomara_Email_Delivered']=new Loomara_Email_Delivered();
    return $emails;
});
function loomara_delivery_deadline_111($order) {
    $at=(int)$order->get_meta('_loomara_delivered_at_111');
    return $at>0?$at+7*DAY_IN_SECONDS:0;
}
function loomara_delivery_schedule_111($id,$due) {
    if(!wp_next_scheduled('loomara_complete_delivery_111',[$id])){wp_schedule_single_event(max(time()+60,$due),'loomara_complete_delivery_111',[$id]);}
}
function loomara_delivery_observed_111($id,$order=false) {
    $order=$order instanceof WC_Order?$order:wc_get_order($id);
    if(!$order||$order->get_status()!=='delivered'){return;}
    // Atomic first observation: duplicate callbacks cannot move the return deadline.
    $key='loomara_delivery_first_111_'.$order->get_id();
    $at=(int)$order->get_meta('_loomara_delivered_at_111');
    if(!$at){add_option($key,time(),'','no');$at=(int)get_option($key);if(!$at){return;}$order->update_meta_data('_loomara_delivered_at_111',$at);$order->save_meta_data();}
    loomara_delivery_schedule_111($order->get_id(),$at+7*DAY_IN_SECONDS);
}
add_action('woocommerce_order_status_delivered','loomara_delivery_observed_111',1,2);
function loomara_delivery_completion_block_111($order) {
    if($order->get_status()!=='delivered'){return 'Order is no longer Delivered.';}
    if(!$order->get_date_paid()&&(float)$order->get_total()>0){return 'Payment has not been recorded as paid.';}
    if((float)$order->get_total_refunded()>0||count($order->get_refunds())){return 'A refund record requires review.';}
    if($order->get_meta('_loomara_return_hold_111')==='yes'){return 'An active return/dispute hold is set.';}
    if(apply_filters('loomara_order_completion_on_hold',false,$order)){return 'An integrated return/dispute requires review.';}
    return '';
}
function loomara_complete_delivery_111($id) {
    $lock='loomara_delivery_complete_lock_111_'.absint($id);
    if(!add_option($lock,time(),'','no')){return;}
    try{
        $order=wc_get_order($id);if(!$order instanceof WC_Order){return;}
        $due=loomara_delivery_deadline_111($order);if(!$due){return;}
        if(time()<$due){loomara_delivery_schedule_111($id,$due);return;}
        $blocked=loomara_delivery_completion_block_111($order);
        if($blocked){if($order->get_status()==='delivered'&&$order->get_meta('_loomara_completion_hold_notice_111')!==$blocked){$order->add_order_note('Automatic completion paused: '.$blocked);$order->update_meta_data('_loomara_completion_hold_notice_111',$blocked);$order->save_meta_data();}return;}
        $order->update_meta_data('_loomara_auto_completed_111','yes');
        $order->save_meta_data();
        $order->update_status('completed','Automatically completed seven calendar days after the first recorded Delivered status. No active completion hold/refund and payment recorded.');
    }finally{delete_option($lock);}
}
add_action('loomara_complete_delivery_111','loomara_complete_delivery_111');
// Do not send the native "completed and shipped" email seven days after delivery.
add_filter('woocommerce_email_enabled_customer_completed_order',function($enabled,$order){return $order instanceof WC_Order&&$order->get_meta('_loomara_auto_completed_111')==='yes'?false:$enabled;},20,2);

add_action('woocommerce_admin_order_data_after_order_details',function($order){
    if(!current_user_can('edit_shop_orders')){return;}
    echo '<div style="clear:both"><p><strong>Loomara delivery lifecycle</strong></p><p>Automatic completion pauses while this hold is active. Use it for an open return, withdrawal, dispute or unresolved delivery issue. Requests received by email or another system must be marked on hold here.</p><label><input type="checkbox" name="loomara_return_hold_111" value="yes" '.checked($order->get_meta('_loomara_return_hold_111'),'yes',false).'> Active return/dispute — hold automatic completion</label>';
    $due=loomara_delivery_deadline_111($order);if($due){echo '<p>Return-window completion check: '.esc_html(wp_date('d M Y, g:i a',$due,new DateTimeZone('Asia/Kolkata'))).' IST.</p>';}
    wp_nonce_field('loomara_return_hold_111_'.$order->get_id(),'loomara_return_hold_nonce_111');echo '</div>';
});
add_action('woocommerce_process_shop_order_meta',function($id){
    if(!current_user_can('edit_shop_orders')||!isset($_POST['loomara_return_hold_nonce_111'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['loomara_return_hold_nonce_111'])),'loomara_return_hold_111_'.$id)){return;}
    $order=wc_get_order($id);if(!$order){return;}
    $hold=isset($_POST['loomara_return_hold_111'])&&$_POST['loomara_return_hold_111']==='yes'?'yes':'no';
    $order->update_meta_data('_loomara_return_hold_111',$hold);$order->save_meta_data();
    if($hold==='no'&&$order->get_status()==='delivered'&&loomara_delivery_deadline_111($order)){loomara_delivery_schedule_111($id,loomara_delivery_deadline_111($order));}
},50);
