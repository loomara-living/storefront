<?php
/** Read-only recorded coupon summary; never recompute historical checkout totals. */
defined('ABSPATH') || exit;
function loomara_order_coupon_details($order){
    if(!$order instanceof WC_Order){return;}
    $codes=$order->get_coupon_codes();
    if(!$codes){return;}
    echo '<section class="loomara-order-coupons"><h3>Applied coupons</h3><p><strong>Coupon code:</strong> '.esc_html(implode(', ',$codes)).'</p>';
    echo '<p><strong>Coupon savings:</strong> '.wp_kses_post(wc_price((float)$order->get_discount_total()+(float)$order->get_discount_tax(),['currency'=>$order->get_currency()])).'</p>';
    echo '<p><strong>Order amount after discounts:</strong> '.wp_kses_post(wc_price($order->get_total(),['currency'=>$order->get_currency()])).'</p></section>';
}
add_action('woocommerce_admin_order_data_after_order_details','loomara_order_coupon_details',30);
add_action('woocommerce_order_details_after_order_table',function($order){
    if(function_exists('loomara_order_reference_owner') && loomara_order_reference_owner($order)){loomara_order_coupon_details($order);}
},19);
