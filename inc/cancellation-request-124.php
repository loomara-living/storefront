<?php
/** Future owner-confirmed requests only; native status stays importable until carrier confirmation. */
defined('ABSPATH') || exit;
function loomara_cr_key($id) { return 'loomara_cancel_request_124_'.absint($id); }
function loomara_cr_job($order) { return $order instanceof WC_Order ? get_option(loomara_cr_key($order->get_id()),[]) : []; }
function loomara_cr_message() {
    return 'Your cancellation request has been received. Please check your order status again in 15–30 minutes. Cancellation is subject to shipping confirmation. If approved, we’ll verify your refund eligibility and email you an update. This is an estimated check-back window, not a guaranteed completion time.';
}
function loomara_cr_save($id,$job) { update_option(loomara_cr_key($id),$job,false); }
function loomara_cr_schedule($id) {
    if (!wp_next_scheduled('loomara_cancel_request_124',[$id])) { wp_schedule_single_event(time()+180,'loomara_cancel_request_124',[$id]); }
}
function loomara_cr_request($order) {
    // Caller holds the owner/nonce-checked cancellation lock. No historical enrolment.
    if (!$order instanceof WC_Order || !loomara_order_can_customer_cancel($order,get_current_user_id()) ||
        $order->get_payment_method()!=='razorpay' || !$order->get_date_paid() ||
        !preg_match('/^pay_[A-Za-z0-9]+$/D',(string)$order->get_transaction_id()) ||
        (float)$order->get_total_refunded()>0 || count($order->get_refunds())) { return false; }
    $id=$order->get_id();
    $job=['stage'=>'requested','created'=>time(),'deadline'=>time()+2700,'attempts'=>0,
        'owner'=>(int)$order->get_customer_id(),'payment'=>(string)$order->get_transaction_id(),
        'currency'=>$order->get_currency(),'total'=>(string)$order->get_total()];
    if (!add_option(loomara_cr_key($id),$job,'','no')) { return false; }
    loomara_cr_schedule($id);
    // Mail is a separate bounded event: transport failure must never lose the cancellation check.
    if (!wp_next_scheduled('loomara_cancel_request_mail_124',[$id])) { wp_schedule_single_event(time()+1,'loomara_cancel_request_mail_124',[$id]); }
    try {
        $order->update_meta_data('_loomara_cancellation_requested_124',$job['created']);$order->save();
        $order->add_order_note('Customer requested cancellation. DO NOT PACK OR DISPATCH while checks are pending. Native status retained for Shiprocket import. Check-back estimate 15–30 minutes; unresolved requests require review after 45 minutes. No cancellation or refund confirmed.');
    } catch (Throwable $e) {
        $job['stage']='review';$job['reason']='Order request metadata/note could not be saved; inspect before fulfilment.';loomara_cr_save($id,$job);
    }
    return true;
}
function loomara_cr_review($order,$job,$reason) {
    $job['stage']='review';$job['reason']=$reason;loomara_cr_save($order->get_id(),$job);
    $order->add_order_note('Cancellation request requires manual review: '.$reason.' Do not dispatch without resolving this request. No automatic refund submitted by the request checker.');
}
function loomara_cr_identity($order,$job) {
    return $order instanceof WC_Order && (int)$order->get_customer_id()===(int)$job['owner'] &&
        $order->get_payment_method()==='razorpay' && $order->get_transaction_id()===$job['payment'] &&
        $order->get_currency()===$job['currency'] && (string)$order->get_total()===$job['total'] &&
        $order->get_date_paid() && !(float)$order->get_total_refunded() && !count($order->get_refunds());
}
function loomara_cr_worker($id) {
    $job=get_option(loomara_cr_key($id));$order=wc_get_order($id);
    if (!$order || !is_array($job) || !in_array($job['stage']??'',['requested','shipping_wait'],true)) { return; }
    $lock='loomara_cancel_order_lock_'.absint($id);
    // Same lock as the customer handler. Never steal a crashed worker's lock.
    if (!add_option($lock,time(),'','no')) { return; }
    try {
        // A duplicate event may have waited while another worker finished. Reload under the lock.
        wp_cache_delete(loomara_cr_key($id),'options');
        $job=get_option(loomara_cr_key($id));$order=wc_get_order($id);
        if (!$order || !is_array($job) || !in_array($job['stage']??'',['requested','shipping_wait'],true)) { return; }
        $job['attempts']=(int)$job['attempts']+1;
        if (!loomara_cr_identity($order,$job) || !in_array($order->get_status(),['pending','on-hold','processing'],true)) {
            loomara_cr_review($order,$job,'Ownership, payment, balance or fulfilment changed.');return;
        }
        if (time()>=(int)$job['deadline'] || $job['attempts']>16) { loomara_cr_review($order,$job,'Shipping verification did not finish within the waiting window.');return; }
        $sr=loomara_sr_order_preflight($order);
        if (is_wp_error($sr)) {
            // Missing/unsynced never permits cancellation or refund. Read-only retries are bounded.
            loomara_cr_save($id,$job);loomara_cr_schedule($id);return;
        }
        if (isset($job['shiprocket_order_id']) && (int)$job['shiprocket_order_id']!==(int)$sr['shiprocket_order_id']) {
            loomara_cr_review($order,$job,'Exact Shiprocket identity changed.');return;
        }
        $job['shiprocket_order_id']=$sr['shiprocket_order_id'];
        $state=loomara_ar_sr_state($sr);
        if ($state==='unknown') { loomara_cr_review($order,$job,'Shipment dispatched or status is not safely cancellable.');return; }
        $order=wc_get_order($id);
        if (!loomara_cr_identity($order,$job) || !in_array($order->get_status(),['pending','on-hold','processing'],true) || time()>=(int)$job['deadline']) {
            loomara_cr_review($order,$job,'Order changed or waiting window expired during verification.');return;
        }
        if ($state==='cancelled') {
            $job['stage']='confirmed';$job['shiprocket_order_id']=$sr['shiprocket_order_id'];loomara_cr_save($id,$job);
            $saved=get_option(loomara_cr_key($id));
            if (!is_array($saved) || ($saved['stage']??'')!=='confirmed' || ($saved['shiprocket_order_id']??0)!==$job['shiprocket_order_id']) { throw new RuntimeException('Confirmation persistence failed'); }
            $order->update_status('cancelled','Customer cancellation confirmed against exact current Shiprocket order and all shipments. Refund eligibility will be independently verified; no refund confirmed yet.');
            return;
        }
        if ($state==='waiting' || $job['stage']==='shipping_wait') { loomara_cr_save($id,$job);loomara_cr_schedule($id);return; }
        $token=loomara_sr_read_token();
        if (is_wp_error($token)) { loomara_cr_save($id,$job);loomara_cr_schedule($id);return; }
        $order=wc_get_order($id);
        if (!loomara_cr_identity($order,$job) || !in_array($order->get_status(),['pending','on-hold','processing'],true) || time()-(int)$sr['checked_at']>60 || time()>=(int)$job['deadline']) {
            loomara_cr_review($order,$job,'Order or shipping verification changed before cancellation.');return;
        }
        $job['stage']='shipping_wait';$job['shiprocket_order_id']=$sr['shiprocket_order_id'];loomara_cr_save($id,$job);
        $saved=get_option(loomara_cr_key($id));
        if (!is_array($saved) || ($saved['stage']??'')!=='shipping_wait' || ($saved['shiprocket_order_id']??0)!==$job['shiprocket_order_id']) { throw new RuntimeException('Intent persistence failed'); }
        wp_remote_post('https://apiv2.shiprocket.in/v1/external/orders/cancel',['timeout'=>20,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>65536,
            'headers'=>['Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'],'body'=>wp_json_encode(['ids'=>[(int)$sr['shiprocket_order_id']]])]);
        // HTTP success is only acknowledgement. Every subsequent check is GET-only, even on timeout.
        loomara_cr_schedule($id);
    } catch (Throwable $e) { loomara_cr_review($order,$job,'Unexpected verification error; inspect before retrying any action.'); }
    finally { delete_option($lock); }
}
add_action('loomara_cancel_request_124','loomara_cr_worker');
function loomara_cr_mail($id) {
    loomara_cr_send_mail($id,'team');
    loomara_cr_send_mail($id,'customer');
}
function loomara_cr_send_mail($id,$audience) {
    $order=wc_get_order($id);$job=$order?loomara_cr_job($order):[];
    if (!$job || !in_array($job['stage']??'',['requested','shipping_wait','review','confirmed'],true)) { return; }
    $mailer=WC()->mailer();$emails=$mailer->get_emails();$new=$emails['WC_Email_New_Order']??null;
    $customer=$audience==='customer';
    $user=$customer && (int)$order->get_customer_id()===(int)$job['owner'] ? get_user_by('id',(int)$job['owner']) : false;
    $recipients=$customer ? ($user->user_email??'') : ($new ? $new->get_recipient() : '');
    $list=array_filter(array_map('trim',explode(',',$recipients)),'is_email');
    $key='loomara_cancel_request_mail_124_'.$id.($customer?'_customer':'');
    if (!add_option($key,'sending','','no')) { return; }
    try {
        if (!$list) { throw new RuntimeException('No configured order notification recipient'); }
        $name=trim($order->get_billing_first_name().' '.$order->get_billing_last_name());
        $body='<h2>Cancellation requested — do not pack or dispatch</h2><p>Order: <strong>'.esc_html($order->get_order_number()).'</strong><br>Customer: '.esc_html($name).'<br>Requested: '.esc_html(wp_date('d M Y, h:i a T',(int)$job['created'],new DateTimeZone('Asia/Kolkata'))).'</p><p>The customer requested cancellation. Shipping checks are in progress or require review. Cancellation and refund are not confirmed by this email. Check the current order before taking any fulfilment action.</p><p><a href="'.esc_url($order->get_edit_order_url()).'">Open order in WooCommerce</a></p>';
        if ($customer) {
            $body='<p>Hi '.esc_html($order->get_billing_first_name()).',</p><p>We have received your cancellation request for order <strong>'.esc_html($order->get_order_number()).'</strong>.</p><p>'.esc_html(loomara_cr_message()).'</p><p>This email acknowledges your request, not final cancellation or a completed refund. Please view your order for the latest status.</p><p><a href="'.esc_url($order->get_view_order_url()).'">View your order</a> · <a href="'.esc_url(home_url('/contact/')).'">Contact Loomara Living</a></p>';
        }
        $ok=wc_mail(implode(',',$list),'['.wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES).'] Cancellation requested — '.$order->get_order_number(),$mailer->wrap_message('Cancellation requested',$body),['Content-Type: text/html; charset=UTF-8']);
        update_option($key,$ok?'accepted':'failed',false);
        if (!$ok) { $order->add_order_note('Cancellation-request '.$audience.' email was not accepted by the transport. Review mail logs before resending.'); }
    } catch (Throwable $e) { update_option($key,'uncertain',false);$order->add_order_note('Cancellation-request '.$audience.' email outcome uncertain or recipient unavailable. Review mail logs/settings before resending; request remains recorded.'); }
}
add_action('loomara_cancel_request_mail_124','loomara_cr_mail');
function loomara_cr_panel($order) {
    $job=loomara_cr_job($order);if (!$job || !in_array($job['stage']??'',['requested','shipping_wait','review'],true)) { return; }
    echo '<div class="woocommerce-info" role="status"><strong>Cancellation requested</strong><p>'.esc_html($job['stage']==='review'?'Your cancellation request needs our team’s review. Cancellation and refund are not yet confirmed. Please contact us for an update.':loomara_cr_message()).'</p><a class="button" href="'.esc_url(home_url('/contact/')).'">Contact us</a></div>';
}
add_action('woocommerce_order_details_before_order_table','loomara_cr_panel',15);
add_action('woocommerce_admin_order_data_after_order_details',function($order){
    $job=loomara_cr_job($order);if (!$job || !in_array($job['stage']??'',['requested','shipping_wait','review'],true)) { return; }
    echo '<div style="clear:both;border-left:4px solid #b32d2e;padding:12px;background:#fff3cd"><strong>Cancellation requested — DO NOT PACK OR DISPATCH</strong><p>'.esc_html($job['stage']==='review'?'Manual review required. Resolve the customer request before fulfilment.':'Waiting for exact Shiprocket cancellation confirmation. No refund confirmed.').'</p></div>';
});
