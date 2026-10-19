<?php
/** Customer-bound checkout identity and owner-only, pre-dispatch cancellation. */
defined('ABSPATH') || exit;

// Capture only from an authenticated checkout, never from POST email/customer IDs.
function loomara_bind_checkout_owner($order) {
    $id=get_current_user_id();
    if (!$id || !$order instanceof WC_Order || $order->get_meta('_loomara_checkout_owner')) { return; }
    $user=get_user_by('id',$id);
    if (!$user || (int)$order->get_customer_id()!==$id) { return; }
    $order->update_meta_data('_loomara_checkout_owner',$id);
}
add_action('woocommerce_checkout_create_order','loomara_bind_checkout_owner',100,1);

function loomara_magic_bound_owner($order) {
    if (!$order instanceof WC_Order || $order->get_meta('is_magic_checkout_order')!=='yes') { return false; }
    $id=(int)$order->get_meta('_loomara_checkout_owner');
    return $id>0 ? get_user_by('id',$id) : false;
}
function loomara_guard_magic_owner($order) {
    $user=loomara_magic_bound_owner($order);
    if (!$user) { return; }
    $order->set_customer_id($user->ID);
    $order->set_billing_email($user->user_email);
    // Used only during this request to suppress redundant gateway account creation.
    $GLOBALS['loomara_magic_bound_request']=true;
    $GLOBALS['loomara_magic_bound_users'][$user->ID]=true;
}
add_action('woocommerce_before_order_object_save','loomara_guard_magic_owner',100,1);
add_action('woocommerce_order_loaded',function($order){
    $user=loomara_magic_bound_owner($order);
    if ($user) { $GLOBALS['loomara_magic_bound_request']=true; $GLOBALS['loomara_magic_bound_users'][$user->ID]=true; }
},10,1);
add_filter('option_woocommerce_razorpay_settings',function($settings){
    // Do not change stored settings, guest checkout, or the gateway credentials.
    if (is_array($settings) && (!is_admin() || wp_doing_ajax()) &&
        (get_current_user_id() || !empty($GLOBALS['loomara_magic_bound_request']))) {
        $settings['1cc_account_creation']='no';
    }
    return $settings;
});
// Gateway profile synchronisation must not replace the authenticated email.
add_filter('update_user_metadata',function($check,$id,$key,$value){
    if ($check!==null || !in_array($key,['billing_email','shipping_email'],true) || empty($GLOBALS['loomara_magic_bound_users'][$id])) { return $check; }
    $user=get_user_by('id',$id);
    return $user && strcasecmp((string)$value,$user->user_email)!==0 ? true : $check;
},10,4);

function loomara_order_can_customer_cancel($order,$user_id) {
    if (function_exists('loomara_cr_job') && loomara_cr_job($order)) { return false; }
    if (!$order instanceof WC_Order || !$user_id || (int)$order->get_customer_id()!==(int)$user_id ||
        !in_array($order->get_status(),['pending','on-hold','processing'],true)) { return false; }
    // Fail closed once a tracking/shipping record exists, even if status lags.
    foreach (['_wc_shipment_tracking_items','_shiprocket_awb_code','awb_code','_tracking_number','_shipped_date'] as $key) {
        if ($order->get_meta($key)) { return false; }
    }
    return true;
}
function loomara_customer_cancel_form($order) {
    if (!loomara_order_can_customer_cancel($order,get_current_user_id())) { return; }
    $automatic=function_exists('loomara_ar_enabled')&&loomara_ar_enabled();
    if (function_exists('loomara_cr_request') && $order->get_date_paid()) {
        echo '<details class="loomara-cancel-order"><summary>Cancel order</summary><p>Request cancellation before dispatch? Please check back in 15–30 minutes. Cancellation depends on shipping confirmation; any refund is subject to payment verification.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_cancel_order"><input type="hidden" name="order_id" value="'.absint($order->get_id()).'">';
        wp_nonce_field('loomara_cancel_order_'.$order->get_id());echo '<button type="submit" class="button">Request cancellation</button></form></details>';return;
    }
    echo '<details class="loomara-cancel-order"><summary>Cancel order</summary><p>'.($automatic?'Cancel this order before dispatch? Refund eligibility will be verified against Shiprocket and Razorpay. A refund is not confirmed until these checks pass; exceptions need manual review.':'Cancel this order before dispatch? If you have paid, our team will arrange your refund separately. Cancellation does not instantly refund your payment.').'</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    echo '<input type="hidden" name="action" value="loomara_cancel_order"><input type="hidden" name="order_id" value="'.absint($order->get_id()).'">';
    wp_nonce_field('loomara_cancel_order_'.$order->get_id());
    echo '<button type="submit" class="button">Confirm cancellation</button></form></details>';
}
// admin-post.php does not load WooCommerce's frontend notice functions/session.
// Carry only an allowlisted outcome to the authenticated customer's next page.
function loomara_queue_cancel_notice($outcome) {
    if (get_current_user_id() && in_array($outcome,['blocked','cancelled','paid','requested'],true)) {
        set_transient('loomara_cancel_notice_'.get_current_user_id(),$outcome,5*MINUTE_IN_SECONDS);
    }
}
function loomara_show_cancel_notice() {
    if (!get_current_user_id() || !function_exists('is_account_page') || !is_account_page() || !function_exists('wc_add_notice') || !WC()->session) { return; }
    $key='loomara_cancel_notice_'.get_current_user_id();
    $outcome=get_transient($key);
    if (!$outcome) { return; }
    delete_transient($key);
    $messages=[
        'requested'=>[function_exists('loomara_cr_message')?loomara_cr_message():'Cancellation requested. Please check back in 15–30 minutes.','success'],
        'blocked'=>['This order can no longer be cancelled online. Please contact us.','error'],
        'cancelled'=>['Your order has been cancelled.','success'],
        'paid'=>['Order cancelled. Our team will arrange your refund separately.','success'],
    ];
    if(function_exists('loomara_ar_enabled')&&loomara_ar_enabled()){$messages['paid']=['Cancellation recorded. Your refund eligibility is being verified. Check this order for updates; exceptions need manual review.','success'];}
    if (isset($messages[$outcome])) { wc_add_notice($messages[$outcome][0],$messages[$outcome][1]); }
}
add_action('template_redirect','loomara_show_cancel_notice',20);
function loomara_customer_cancel_order() {
    $id=isset($_POST['order_id'])&&is_scalar($_POST['order_id'])?absint($_POST['order_id']):0;
    if (!get_current_user_id() || !$id) { wp_die('Please sign in to manage your order.',403); }
    check_admin_referer('loomara_cancel_order_'.$id);
    $lock='loomara_cancel_order_lock_'.$id;
    if (!add_option($lock,time(),'','no')) { wp_die('This order is being updated. Refresh and try again.',409); }
    try {
        $order=wc_get_order($id);
        if (!loomara_order_can_customer_cancel($order,get_current_user_id())) {
            loomara_queue_cancel_notice('blocked');
        } else {
            $paid=$order->is_paid();
            if (function_exists('loomara_cr_request') && $order->get_date_paid()) {
                loomara_queue_cancel_notice(loomara_cr_request($order)?'requested':'blocked');
            } else {
            $automatic=function_exists('loomara_ar_enabled')&&loomara_ar_enabled();
            $order->update_status('cancelled','Customer confirmed cancellation before dispatch. '.($automatic?'Automatic refund verification will run in the background; no refund is confirmed yet.':($paid?'Paid order: manual refund review required; no automatic refund submitted.':'')));
            loomara_queue_cancel_notice($paid?'paid':'cancelled');
            }
        }
    } finally { delete_option($lock); }
    wp_safe_redirect(add_query_arg('order_status','all',wc_get_account_endpoint_url('orders'))); exit;
}
add_action('admin_post_loomara_cancel_order','loomara_customer_cancel_order');
add_action('woocommerce_order_details_after_order_table','loomara_customer_cancel_form');
add_filter('woocommerce_my_account_my_orders_actions',function($actions,$order){
    // Use the explicit confirmation form rather than the native one-click link.
    if ((int)$order->get_customer_id()===get_current_user_id()) { unset($actions['cancel']); }
    return $actions;
},100,2);
add_filter('woocommerce_valid_order_statuses_for_cancel',function($statuses,$order){
    // Old/bookmarked native one-click links must not bypass the paid request workflow.
    if (function_exists('loomara_cr_request') && $order instanceof WC_Order && $order->get_date_paid()) { return []; }
    return loomara_order_can_customer_cancel($order,get_current_user_id()) ? $statuses : [];
},100,2);
