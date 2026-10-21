<?php
/** Owner-only presentation of saved references. No provider calls or order writes. */
defined('ABSPATH') || exit;
function loomara_account_refund_totals_copy($totals,$order){
    if(is_admin() || !is_account_page() || !is_wc_endpoint_url('view-order') ||
        !loomara_order_reference_owner($order) || $order->get_status()!=='refunded' ||
        !empty($GLOBALS['loomara_refund_email_table_stack'])){return $totals;}
    // Strip only our exact internal reconciliation reason from native display.
    // Do not change get_reason(): the refund worker uses it as an identity guard.
    foreach($totals as $key=>$row){
        if(strpos((string)$key,'refund_')!==0 || !is_string($row['value']??null)){continue;}
        $totals[$key]['value']=preg_replace('~<br\s*/?>\s*<small>Automatic cancellation refund \[LM-CANCEL-[a-f0-9]{28}\]</small>~','',$row['value']);
    }
    return $totals;
}
add_filter('woocommerce_get_order_item_totals','loomara_account_refund_totals_copy',90,2);
function loomara_order_reference_owner($order){
    return $order instanceof WC_Order && get_current_user_id()>0 && (int)$order->get_customer_id()===get_current_user_id();
}
function loomara_order_refunded_amount($order){
    return max(0,(float)$order->get_total_refunded());
}
function loomara_order_card_amount($order){
    $amount=loomara_order_refunded_amount($order);
    if($amount>0 || $order->get_status()==='refunded'){
        return ['label'=>'Total refunded','value'=>wc_price($amount,['currency'=>$order->get_currency()])];
    }
    return ['label'=>'Order total','value'=>$order->get_formatted_order_total()];
}
function loomara_account_order_references($order){
    if(!loomara_order_reference_owner($order)){return;}
    $payment=$order->get_transaction_id();
    $valid_payment=$order->get_payment_method()==='razorpay' && $order->get_date_paid() && is_string($payment) && preg_match('/^pay_[A-Za-z0-9]+$/D',$payment);
    $rows=[];
    if($valid_payment){$rows['Payment reference number']=$payment;}
    $amount=loomara_order_refunded_amount($order);
    if($amount>0 || $order->get_status()==='refunded'){$rows['Total refunded']=wc_price($amount,['currency'=>$order->get_currency()]);}
    echo '<section class="loomara-order-references" aria-label="Payment, refund and tracking details"><h2>Payment &amp; tracking</h2>';
    if($rows){echo '<dl>';foreach($rows as $label=>$value){echo '<div><dt>'.esc_html($label).'</dt><dd>'.($label==='Total refunded'?wp_kses_post($value):esc_html($value)).'</dd></div>';}echo '</dl>';}
    foreach($order->get_refunds() as $refund){
        if(!$refund instanceof WC_Order_Refund || (int)$refund->get_parent_id()!==$order->get_id()){continue;}
        $id=$refund->get_meta('_loomara_razorpay_refund_id');
        $bound=$refund->get_meta('_loomara_refund_payment_id');
        // Only the saved verified provider metadata is displayed; no guessed IDs.
        if(!$valid_payment || $bound!==$payment || !is_string($id)||!preg_match('/^rfnd_[A-Za-z0-9]+$/D',$id)){continue;}
        echo '<div class="loomara-refund-reference"><h3>Refund details</h3><dl><div><dt>Refund amount</dt><dd>'.wp_kses_post(wc_price($refund->get_amount(),['currency'=>$order->get_currency()])).'</dd></div><div><dt>Razorpay refund reference</dt><dd>'.esc_html($id).'</dd></div>';
        $refs=$refund->get_meta('_loomara_refund_bank_references');$found=false;
        foreach(['ARN','RRN','UTR'] as $type){$value=is_array($refs)?($refs[$type]??null):null;if(is_string($value)&&preg_match('/^[A-Za-z0-9][A-Za-z0-9 -]{2,99}$/D',$value)){$found=true;echo '<div><dt>Bank reference ('.esc_html($type).')</dt><dd>'.esc_html($value).'</dd></div>';}}
        echo '</dl>';if(!$found){echo '<p>Bank reference not available yet. Please quote the Razorpay refund reference if you contact us.</p>';}echo '</div>';
    }
    if($amount>0){echo '<p>Please check your refund email for further details. Bank posting times may vary.</p>';}
    if(in_array($order->get_status(),['pickup-scheduled','out-for-pickup','picked-up','shipped','in-transit','out-for-delivery','delivered','completed'],true)){
        $tracking=$order->get_meta('_loomara_verified_tracking_112');
        if(is_array($tracking)){foreach($tracking as $row){
            if(!is_array($row)||!is_string($row['awb']??null)||!preg_match('/^[A-Za-z0-9-]{3,100}$/D',$row['awb'])){continue;}
            $url=function_exists('loomara_tracking_url_112')?loomara_tracking_url_112($row['url']??''):'';
            echo '<div class="loomara-order-tracking"><p><strong>Tracking / AWB:</strong> '.esc_html($row['awb']);
            if(is_string($row['courier']??null)&&$row['courier']!==''){echo '<br><strong>Courier:</strong> '.esc_html($row['courier']);}
            echo '</p>';if($url){echo '<a class="button" target="_blank" rel="noopener noreferrer" href="'.esc_url($url).'">Track shipment <span class="screen-reader-text">'.esc_html($row['awb']).' (opens in a new tab)</span></a>';}else{echo '<p>The tracking link is not available yet.</p>';}echo '</div>';
        }}
    }
    if(!$rows && !$amount){echo '<p>No payment reference is available for this order yet.</p>';}
    echo '</section>';
}
add_action('woocommerce_order_details_after_order_table','loomara_account_order_references',18);
function loomara_account_order_footer_actions($order_id){
    $order=wc_get_order(absint($order_id));
    if(!loomara_order_reference_owner($order)){return;}
    echo '<nav class="loomara-order-footer-actions" aria-label="Order help and shopping"><a class="button order-contact-button" href="'.esc_url(home_url('/contact/')).'">Contact us</a><a class="button order-shop-button" href="'.esc_url(wc_get_page_permalink('shop')).'">Find your next layer</a></nav>';
}
// Runs after the complete native account View order output, including addresses.
add_action('woocommerce_account_view-order_endpoint','loomara_account_order_footer_actions',99,1);
add_action('wp_enqueue_scripts',function(){if(function_exists('is_account_page')&&is_account_page()){wp_enqueue_style('loomara-order-references',get_template_directory_uri().'/assets/order-references.css',[], '1');}});
