<?php
/** Pending-payment timers retired. Checkout offer notice remains active. */
defined('ABSPATH') || exit;
// Do not enrol, remind, verify or fail orders. Old queued hooks have no worker.
// Preserve the previous guard for already-enrolled orders so retirement itself
// cannot expose those orders to Woo's native hold-stock cancellation.
add_filter('woocommerce_cancel_unpaid_order',function($cancel,$o){return $o instanceof WC_Order&&$o->get_payment_method()==='razorpay'&&$o->get_meta('_loomara_pending_started_114')?false:$cancel;},100,2);

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
