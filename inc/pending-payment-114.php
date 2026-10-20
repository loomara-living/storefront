<?php
/** Pending-payment timers retired. Checkout offer notice remains active. */
defined('ABSPATH') || exit;
// Do not enrol, remind, verify or fail orders. Old queued hooks have no worker.
// Abandonment is not cancellation or a verified payment failure. Keep native
// pending (displayed as Payment attempted) Razorpay orders out of Woo's stock
// timeout cancellation, regardless of whether old timer metadata exists.
// This filter does not intercept explicit cancellations, payment completion or
// the separate signed, gateway-verified payment.failed workflow.
add_filter('woocommerce_cancel_unpaid_order',function($cancel,$o){
    if($o instanceof WC_Order && $o->get_payment_method()==='razorpay' &&
        ($o->get_status()==='pending' || $o->get_meta('_loomara_pending_started_114'))){return false;}
    return $cancel;
},100,2);

// Native wc_reserved_stock holds have an expiry and stop counting after it,
// independently of order status. Never increase physical stock or delete holds
// from a timeout worker: that could race payment/new reservations. Reacquire
// through Woo's atomic stock-reservation API before a native order-pay retry.
add_action('woocommerce_before_pay_action','loomara_reserve_retry_stock',10,1);
function loomara_reserve_retry_stock($order){
    if(!$order instanceof WC_Order || $order->get_payment_method()!=='razorpay' ||
        !in_array($order->get_status(),['pending','failed'],true) || $order->get_date_paid()){return;}
    if(!function_exists('wc_reserve_stock_for_order')){
        wc_add_notice('Stock availability could not be checked. Please try again shortly.','error');return;
    }
    try{wc_reserve_stock_for_order($order);}
    catch(\Exception $e){
        // Native pay_action checks error notices before calling the gateway.
        wc_add_notice('Some items are no longer available in the requested quantity. Please update your cart before paying.','error');
    }
}

add_action('woocommerce_after_cart','loomara_cart_coupon_note_114',1);
add_filter('render_block_woocommerce/cart',function($html){if(is_admin()||!is_cart()){return $html;}ob_start();loomara_cart_coupon_note_114();return ob_get_clean().$html;},5);
function loomara_cart_coupon_note_114(){
    static $shown=false;if($shown||!WC()->cart||WC()->cart->is_empty()){return;}$shown=true;
    echo '<aside class="loomara-checkout-offers-note" aria-label="Checkout offers"><span aria-hidden="true">✧</span><div><strong>A little more to look forward to</strong><p>All offers applicable to your order will be shown at checkout. Choose an eligible coupon before you pay.</p></div></aside>';
}
add_action('wp_enqueue_scripts',function(){if(is_cart()){wp_enqueue_style('loomara-pending-114',get_template_directory_uri().'/assets/pending-v114.css',[],wp_get_theme()->get('Version'));}});

function loomara_pending_cleanup_116(){
    $result=wp_unschedule_hook('loomara_pending_check_114');
    if($result===false||is_wp_error($result)){return new WP_Error('cleanup','Pending-payment schedule cleanup failed. No other task was removed.');}
    update_option('loomara_pending_retired_116',['at'=>time(),'removed'=>(int)$result],false);
    return (int)$result;
}
add_action('admin_menu',function(){add_submenu_page('woocommerce','Retired payment timers','Retired payment timers','manage_woocommerce','loomara-pending-retirement-116',function(){
    if(!current_user_can('manage_woocommerce')){return;}
    echo '<div class="wrap"><h1>Pending-payment automation removed</h1><p>No new reminder or payment-timeout tasks are created, and the retired worker/email class is no longer registered. This clears only the old pending-payment hook. Existing order metadata, email markers and other scheduled tasks are retained.</p>';
    $done=get_option('loomara_pending_retired_116');
    if(is_array($done)){echo '<p>Cleanup completed. Removed '.absint($done['removed']).' retired scheduled events.</p></div>';return;}
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_pending_cleanup_116">';wp_nonce_field('loomara_pending_cleanup_116');echo '<button class="button button-primary">Clear retired pending-payment events</button></form></div>';
});});
add_action('admin_post_loomara_pending_cleanup_116',function(){
    if(!current_user_can('manage_woocommerce')){wp_die('Administrator access required.',403);}check_admin_referer('loomara_pending_cleanup_116');
    $r=loomara_pending_cleanup_116();if(is_wp_error($r)){wp_die(esc_html($r->get_error_message()),409);}
    wp_safe_redirect(admin_url('admin.php?page=loomara-pending-retirement-116'));exit;
});
