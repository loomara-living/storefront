<?php
/** Customer order presentation and explicit PIN-format validation. */
defined('ABSPATH') || exit;

// New orders only: immutable native database ID prevents collisions and races.
// Historic customer-facing numbers are deliberately not rewritten.
function loomara_assign_order_reference($id, $order = false) {
    $order = $order instanceof WC_Order ? $order : wc_get_order($id);
    if (!$order || $order->get_meta('_loomara_order_reference')) { return; }
    $date = $order->get_date_created();
    $reference = 'OD-'.wp_date('Ym', $date ? $date->getTimestamp() : time(), wp_timezone()).'-'.str_pad((string)$order->get_id(), 6, '0', STR_PAD_LEFT);
    $order->update_meta_data('_loomara_order_reference', $reference);
    $order->save_meta_data();
}
add_action('woocommerce_new_order', 'loomara_assign_order_reference', 20, 2);
add_filter('woocommerce_order_number', function ($number, $order) {
    return $order->get_meta('_loomara_order_reference') ?: $number;
}, 20, 2);

function loomara_order_status_selection() {
    $raw = $_GET['order_status'] ?? 'all';
    $value = is_scalar($raw) ? sanitize_key(wp_unslash($raw)) : '';
    if(in_array($value,['pending','checkout-draft','draft'],true)){return 'all';}
    return in_array($value, ['successful','all'], true) || isset(wc_get_order_statuses()['wc-'.$value]) ? $value : 'all';
}

function loomara_order_created_datetime($date) {
    return $date ? wp_date('d M Y · g:i a', $date->getTimestamp(), new DateTimeZone('Asia/Kolkata')) . ' IST' : '';
}

function loomara_successful_order_statuses() {
    $statuses = wc_get_is_paid_statuses();
    foreach (['ready-to-ship','shipped','delivered'] as $status) {
        if (isset(wc_get_order_statuses()['wc-'.$status])) { $statuses[] = $status; }
    }
    return array_values(array_unique($statuses));
}
function loomara_account_order_query($args) {
    $selected = loomara_order_status_selection();
    $args['status'] = $selected === 'all' ? array_values(array_unique(array_merge(array_keys(wc_get_order_statuses()),['wc-checkout-draft']))) : ($selected === 'successful' ? loomara_successful_order_statuses() : ($selected === 'pending'?['pending','checkout-draft']:[$selected]));
    // Ownership always wins over query parameters or other presentation filters.
    $args['customer'] = get_current_user_id();
    // Exclude before pagination so hidden attempts do not produce empty pages/counts.
    $args['exclude']=array_values(array_unique(array_merge((array)($args['exclude']??[]),loomara_hidden_unpaid_order_ids())));
    return $args;
}
add_filter('woocommerce_my_account_my_orders_query', 'loomara_account_order_query', 100);
function loomara_customer_order_hidden($o){
    if(function_exists('loomara_pf_visible_120') && loomara_pf_visible_120($o)){return false;}
    if($o instanceof WC_Order && $o->get_status()==='cancelled' && !$o->get_date_paid() && !(float)$o->get_total_refunded() && !count($o->get_refunds())){
        return loomara_unpaid_timeout_cancelled_121($o);
    }
    return $o instanceof WC_Order&&in_array($o->get_status(),['pending','checkout-draft','draft','failed'],true)&&!$o->get_date_paid()&&!(float)$o->get_total_refunded()&&!count($o->get_refunds());
}
/** Display-only classification. Unknown/manual cancellations remain visible. */
function loomara_unpaid_timeout_cancelled_121($o){
    if(method_exists($o,'get_transaction_id') && $o->get_transaction_id()){return false;}
    return loomara_has_stock_timeout_note_121($o);
}
function loomara_has_stock_timeout_note_121($o){
    if(!function_exists('wc_get_order_notes')){return false;}
    static $cache=[];$id=$o->get_id();if(isset($cache[$id])){return $cache[$id];}
    $normalise=static function($text){return trim(str_replace(['–','—'], '-', wp_strip_all_tags((string)$text)));};
    $messages=array_unique(['Unpaid order cancelled - time limit reached.',__('Unpaid order cancelled - time limit reached.','woocommerce')]);
    foreach($messages as $message){
        // Native note text is stored before wptexturize; search avoids loading unrelated notes.
        $notes=wc_get_order_notes(['order_id'=>$id,'type'=>'internal','search'=>$message,'limit'=>20]);
        foreach($notes as $note){
            if(($note->added_by??'')==='system' && empty($note->customer_note) && (int)($note->order_id??0)===$id && strpos($normalise($note->content??''),$normalise($message))===0){return $cache[$id]=true;}
        }
    }
    return $cache[$id]=false;
}
/** Suppress only the customer notice for an explicitly identified unpaid timeout. */
function loomara_stock_timeout_customer_email_122($enabled,$o){
    if($o instanceof WC_Order && $o->get_status()==='cancelled' && !$o->get_date_paid() && !(float)$o->get_total_refunded() && !count($o->get_refunds()) && loomara_has_stock_timeout_note_121($o)){return false;}
    return $enabled;
}
add_filter('woocommerce_email_enabled_customer_cancelled_order','loomara_stock_timeout_customer_email_122',100,2);
function loomara_hidden_unpaid_order_ids(){
    static $cache=[];$customer=get_current_user_id();if(!$customer){return [];}
    if(isset($cache[$customer])){return $cache[$customer];}
    $ids=[];
    // Native Woo CRUD query works with both classic order storage and HPOS.
    foreach(wc_get_orders(['customer'=>$customer,'status'=>['pending','checkout-draft','failed','cancelled'],'limit'=>-1,'return'=>'objects']) as $o){
        if((int)$o->get_customer_id()===$customer&&loomara_customer_order_hidden($o)){$ids[]=$o->get_id();}
    }
    return $cache[$customer]=$ids;
}
// Preserve the native pending key: payment gateways and stock-release hooks depend on it.
add_filter('wc_order_statuses',function($statuses){if(isset($statuses['wc-pending'])){$statuses['wc-pending']='Payment attempted';}return $statuses;},100);
function loomara_customer_order_status_label($status,$order=null){
    if (function_exists('loomara_cr_job') && $order instanceof WC_Order && in_array($status,['pending','on-hold','processing'],true)) {
        $job=loomara_cr_job($order);if (in_array($job['stage']??'',['requested','shipping_wait','review'],true)) { return 'Cancellation requested'; }
    }
    return in_array($status,['checkout-draft','draft'],true)?'Payment attempted':wc_get_order_status_name($status);
}

function loomara_customer_refund_message($order){
    if (!$order instanceof WC_Order || !get_current_user_id() || (int)$order->get_customer_id()!==get_current_user_id()) { return ''; }
    $amount=(float)$order->get_total_refunded();
    if ($order->get_status()==='refunded') {
        return 'Your order is marked as Refunded. Please check your refund email for the amount and further details. Bank processing times may vary. If you have not received the email, please contact us.';
    }
    if ($amount>0 && count($order->get_refunds())) {
        return 'A refund has been recorded for this order. Please check your refund email for the refunded amount and further details. This may be a partial refund; the order status remains unchanged. Bank processing times may vary.';
    }
    return '';
}
function loomara_customer_refund_notice($order){
    $message=loomara_customer_refund_message($order);
    if ($message) { loomara_order_status_help('Refund details','Refund information',$message); }
}
add_action('woocommerce_order_details_after_order_table','loomara_customer_refund_notice',15);

function loomara_order_status_help($label,$heading,$message,$contact=false){
    // Native details is keyboard/touch accessible and remains usable without JavaScript.
    echo '<details class="order-status-help"><summary>'.esc_html($label).' <span class="order-status-help-icon" aria-hidden="true">i</span></summary><div class="order-status-help-content" role="status"><strong>'.esc_html($heading).'</strong><p>'.esc_html($message).'</p>';
    if ($contact) { echo '<a href="'.esc_url(home_url('/contact/')).'">Contact us</a>'; }
    echo '</div></details>';
}

// Shiprocket owns live courier checks when installed. Do not remove its native
// callback or override merchant configuration. Format-only fallback is local /
// plugin-absent only and must never be presented as courier serviceability.
function loomara_pin_format_valid($pin) {
    return is_scalar($pin) && preg_match('/^[1-9][0-9]{5}$/D', trim((string)$pin)) === 1;
}
add_action('woocommerce_single_product_summary', function () {
    if (function_exists('shiprocket_show_check_pincode')) { return; }
    echo '<section class="delivery-pin" aria-labelledby="delivery-pin-title"><h2 id="delivery-pin-title">Delivery to your door</h2><label for="loomara-delivery-pin">India PIN code</label><div class="delivery-pin-controls"><input id="loomara-delivery-pin" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="6" pattern="[1-9][0-9]{5}" placeholder="e.g. 560001" aria-describedby="delivery-pin-help delivery-pin-result"><button type="button" class="button" data-check-india-pin>Check PIN</button></div><p id="delivery-pin-help">Free shipping across India. This checks the six-digit PIN format, not live courier coverage.</p><p id="delivery-pin-result" role="status" aria-live="polite"></p></section>';
}, 21);
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('loomara-orders-delivery', get_template_directory_uri().'/assets/orders-delivery-v188.css', ['loomara-cart-account'], filemtime(__DIR__.'/../assets/orders-delivery-v188.css'));
    if (function_exists('is_account_page') && is_account_page()) {
        wp_enqueue_script('loomara-order-status-help',get_template_directory_uri().'/assets/order-status-help.js',[],filemtime(__DIR__.'/../assets/order-status-help.js'),true);
    }
    if (!function_exists('shiprocket_show_check_pincode')) {
        wp_enqueue_script('loomara-orders-delivery', get_template_directory_uri().'/assets/orders-delivery-v188.js', [], filemtime(__DIR__.'/../assets/orders-delivery-v188.js'), true);
    }
});
