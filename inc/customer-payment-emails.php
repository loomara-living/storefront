<?php
/** Customer payment/refund references only; no gateway writes or resends. */
defined('ABSPATH') || exit;
function loomara_customer_payment_recipient($recipient,$order){
    if(!$order instanceof WC_Order || $order->get_meta('is_magic_checkout_order')!=='yes'){return $recipient;}
    $owner=(int)$order->get_meta('_loomara_checkout_owner');
    if(!$owner){return $recipient;} // Do not rewrite historical unbound guest identities.
    if((int)$order->get_customer_id()!==$owner){return '';}
    $user=get_user_by('id',$owner);
    return $user && is_email($user->user_email)?$user->user_email:'';
}
foreach(['customer_processing_order','customer_on_hold_order','customer_completed_order',
    'customer_refunded_order','customer_partially_refunded_order','customer_cancelled_order',
    'loomara_customer_shipped','loomara_customer_delivered'] as $id){
    add_filter('woocommerce_email_recipient_'.$id,'loomara_customer_payment_recipient',100,2);
}
// Official Magic Checkout supports prefill.email. Never patch vendor JavaScript,
// redirect a payment, change returned gateway email or rewrite an existing payment.
function loomara_magic_email_prefill($response,$server,$request){
    if(rtrim(strtolower($request->get_route()),'/')!=='/1cc/v1/order/create' || strtoupper($request->get_method())!=='POST' ||
        !$response instanceof WP_REST_Response || $response->get_status()!==200){return $response;}
    $uid=get_current_user_id();
    if(!$uid || (int)($GLOBALS['loomara_magic_create_117']??0)!==$uid){return $response;}
    $user=get_user_by('id',$uid);$data=$response->get_data();
    if(!$user || !is_email($user->user_email) || !is_array($data) || empty($data['one_click_checkout']) ||
        !preg_match('/^order_[A-Za-z0-9]+$/D',(string)($data['order_id']??''))){return $response;}
    $data['prefill']=is_array($data['prefill']??null)?$data['prefill']:[];
    $data['prefill']['email']=$user->user_email;$response->set_data($data);return $response;
}
add_filter('rest_post_dispatch','loomara_magic_email_prefill',100,3);
function loomara_success_email_payment_reference($order,$admin,$plain,$email){
    if($admin || !$order instanceof WC_Order ||
        !in_array($email->id??'', ['customer_processing_order','customer_completed_order','customer_invoice'],true) ||
        $order->get_payment_method()!=='razorpay' || !$order->get_date_paid() ||
        !in_array($order->get_status(),['processing','completed','ready-to-ship','shipped','delivered'],true)){return;}
    $payment=$order->get_transaction_id();
    if(!is_string($payment)||!preg_match('/^pay_[A-Za-z0-9]+$/D',$payment)){return;}
    if($plain){echo "\nPayment reference number: ".$payment."\n";}
    else{echo '<p style="margin:16px 0;padding:12px 16px;background:#f8f4ed;border-left:3px solid #bd914f;color:#102c4c;overflow-wrap:anywhere"><strong>Payment reference number:</strong> '.esc_html($payment).'</p>';}
}
add_action('woocommerce_email_order_meta','loomara_success_email_payment_reference',19,4);
function loomara_cancel_email_payment_details($order,$admin,$plain,$email){
    if($admin || !$order instanceof WC_Order || ($email->id??'')!=='customer_cancelled_order' ||
        $order->get_status()!=='cancelled' || $order->get_payment_method()!=='razorpay' || !$order->get_date_paid()){return;}
    $payment=$order->get_transaction_id();
    if(!is_string($payment)||!preg_match('/^pay_[A-Za-z0-9]+$/D',$payment)){return;}
    $help='Order cancellation does not by itself confirm a refund. Any eligible refund is verified separately. Please check your refund email for the Razorpay refund reference and any available bank tracking reference. Bank posting times may vary.';
    if($plain){echo "\nPayment and refund information\nPayment reference number: ".$payment."\n".$help."\n";}
    else{echo '<section style="padding:16px 20px;background:#f8f4ed;border:1px solid #e1d2b8;border-radius:8px;margin:20px 0"><h3 style="margin:0 0 12px;color:#102c4c">Payment and refund information</h3><p style="overflow-wrap:anywhere"><strong>Payment reference number:</strong> '.esc_html($payment).'</p><p>'.esc_html($help).'</p></section>';}
}
add_action('woocommerce_email_order_meta','loomara_cancel_email_payment_details',19,4);
// Persist verified details BEFORE wc_create_refund emits its native refund email.
// Custom args come only from the exact processed-refund reconciliation path.
function loomara_refund_email_metadata($refund,$args){
    $r=$args['_loomara_verified_refund']??null;
    if(!$refund instanceof WC_Order_Refund || !is_array($r) || ($r['status']??'')!=='processed' ||
        !preg_match('/^rfnd_[A-Za-z0-9]+$/D',(string)($r['id']??'')) ||
        !preg_match('/^pay_[A-Za-z0-9]+$/D',(string)($r['payment_id']??''))){return;}
    $order=wc_get_order($args['order_id']??0);
    if(!$order instanceof WC_Order || (int)$refund->get_parent_id()!==$order->get_id() ||
        $order->get_payment_method()!=='razorpay' || $order->get_transaction_id()!==$r['payment_id'] ||
        $order->get_currency()!==($r['currency']??'') || (int)round((float)$refund->get_amount()*100)!==($r['amount']??null)){return;}
    $refs=[];
    foreach(['arn','rrn','utr'] as $type){
        $value=$r['acquirer_data'][$type]??null;
        if(is_scalar($value) && preg_match('/^[A-Za-z0-9][A-Za-z0-9 -]{2,99}$/D',(string)$value)){$refs[strtoupper($type)]=(string)$value;}
    }
    $refund->update_meta_data('_loomara_razorpay_refund_id',$r['id']);
    $refund->update_meta_data('_loomara_refund_bank_references',$refs);
    $refund->update_meta_data('_loomara_refund_payment_id',$r['payment_id']);
}
add_action('woocommerce_create_refund','loomara_refund_email_metadata',10,2);
function loomara_refund_email_references($order,$admin,$plain,$email){
    if($admin || !$order instanceof WC_Order || !in_array($email->id??'', ['customer_refunded_order','customer_partially_refunded_order'],true)){return;}
    $refund=$email->refund??null;
    if(!$refund instanceof WC_Order_Refund || (int)$refund->get_parent_id()!==$order->get_id()){return;}
    $id=$refund->get_meta('_loomara_razorpay_refund_id');
    if(!is_string($id)||!preg_match('/^rfnd_[A-Za-z0-9]+$/D',$id)){return;}
    $rows=['Razorpay refund reference'=>$id];
    $payment=$refund->get_meta('_loomara_refund_payment_id');
    if(is_string($payment)&&preg_match('/^pay_[A-Za-z0-9]+$/D',$payment)&&$order->get_transaction_id()===$payment){$rows['Payment reference number']=$payment;}
    $has_bank_reference=false;
    $refs=$refund->get_meta('_loomara_refund_bank_references');
    if(is_array($refs)){foreach(['ARN','RRN','UTR'] as $type){$value=$refs[$type]??null;if(is_string($value)&&preg_match('/^[A-Za-z0-9][A-Za-z0-9 -]{2,99}$/D',$value)){$rows['Bank reference ('.$type.')']=$value;$has_bank_reference=true;}}}
    $help=$has_bank_reference?'You can quote the bank reference to your bank to trace this refund.':'The bank tracking reference has not been provided yet. Contact us and quote the Razorpay refund reference if you need help.';
    $help.=' Refunds return to the original payment method. Bank posting times may vary.';
    if($plain){echo "\nRefund tracking\n";foreach($rows as $label=>$value){echo $label.': '.$value."\n";}echo $help."\n";}
    else{echo '<section style="padding:16px 20px;background:#f8f4ed;border:1px solid #e1d2b8;border-radius:8px;margin:20px 0"><h3 style="margin:0 0 12px;color:#102c4c">Refund tracking</h3>';foreach($rows as $label=>$value){echo '<p style="margin:6px 0"><strong>'.esc_html($label).':</strong> '.esc_html($value).'</p>';}echo '<p style="margin:12px 0 0">'.esc_html($help).'</p></section>';}
}
add_action('woocommerce_email_order_meta','loomara_refund_email_references',20,4);
function loomara_refund_email_amounts($order,$admin,$plain,$email){
    $refund=$email->refund??null;
    if($admin || !$order instanceof WC_Order || !in_array($email->id??'', ['customer_refunded_order','customer_partially_refunded_order'],true) ||
        !$refund instanceof WC_Order_Refund || (int)$refund->get_parent_id()!==$order->get_id() || (float)$refund->get_amount()<=0){return;}
    $rows=['Amount of this refund'=>(float)$refund->get_amount(),'Total refunded to date'=>max((float)$refund->get_amount(),(float)$order->get_total_refunded())];
    if($plain){echo "\nRefund amounts\n";foreach($rows as $label=>$amount){echo $label.': '.html_entity_decode(strip_tags(wc_price($amount,['currency'=>$order->get_currency()])),ENT_QUOTES,'UTF-8')."\n";}}
    else{echo '<section style="padding:16px 20px;background:#f8f4ed;border:1px solid #e1d2b8;border-radius:8px;margin:20px 0"><h3>Refund amounts</h3>';foreach($rows as $label=>$amount){echo '<p><strong>'.esc_html($label).':</strong> '.wp_kses_post(wc_price($amount,['currency'=>$order->get_currency()])).'</p>';}echo '</section>';}
}
add_action('woocommerce_email_order_meta','loomara_refund_email_amounts',18,4);
// Scope native totals replacement strictly to the customer refund-email table.
// Do not change View order, invoices, other customer emails or admin totals.
add_action('woocommerce_email_before_order_table',function($order,$admin,$plain,$email){
    $GLOBALS['loomara_refund_email_table_stack'][]=(!$admin && $order instanceof WC_Order && in_array($email->id??'', ['customer_refunded_order','customer_partially_refunded_order'],true))?$order->get_id():0;
},1,4);
add_action('woocommerce_email_after_order_table',function(){if(!empty($GLOBALS['loomara_refund_email_table_stack'])){array_pop($GLOBALS['loomara_refund_email_table_stack']);}},999,4);
add_filter('woocommerce_get_order_item_totals',function($totals,$order){
    $stack=$GLOBALS['loomara_refund_email_table_stack']??[];
    if(!$order instanceof WC_Order || !$stack || end($stack)!==$order->get_id() || (float)$order->get_total_refunded()<=0){return $totals;}
    $totals['order_total']=['label'=>'Total refunded to date:','value'=>wc_price($order->get_total_refunded(),['currency'=>$order->get_currency()])];
    return $totals;
},100,2);
