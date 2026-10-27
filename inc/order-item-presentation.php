<?php
/** Owner-only account item presentation. Historical amounts/variants remain native. */
defined('ABSPATH') || exit;
function loomara_order_item_view_context($item){
    if(!function_exists('is_account_page')||!is_account_page()||!is_wc_endpoint_url('view-order')||doing_action('woocommerce_email_order_details')||!$item instanceof WC_Order_Item_Product){return false;}
    $order=wc_get_order($item->get_order_id());return loomara_journey_owner($order)?$order:false;
}
add_action('woocommerce_checkout_create_order_line_item',function($item,$key,$values,$order){
    $p=$values['data']??false;if(!$item instanceof WC_Order_Item_Product||!$p instanceof WC_Product){return;}
    $item->add_meta_data('_loomara_purchase_sku',(string)$p->get_sku(),true);
    $image=$p->get_image_id();if(!$image&&$p->is_type('variation')){$parent=wc_get_product($p->get_parent_id());$image=$parent?$parent->get_image_id():0;}
    if($image){$url=wp_get_attachment_image_url($image,'woocommerce_thumbnail');if($url){$item->add_meta_data('_loomara_purchase_image',esc_url_raw($url),true);}}
},30,4);
add_filter('woocommerce_order_item_name',function($name,$item){
    if(!loomara_order_item_view_context($item)){return $name;}
    $p=$item->get_product();$sku=$item->get_meta('_loomara_purchase_sku',true);$saved=$item->meta_exists('_loomara_purchase_sku');
    if(!$saved&&$p){$sku=$p->get_sku();}
    $image=$item->get_meta('_loomara_purchase_image',true);$historical=(bool)$image;
    if(!$image&&$p){$id=$p->get_image_id();if(!$id&&$p->is_type('variation')){$parent=wc_get_product($p->get_parent_id());$id=$parent?$parent->get_image_id():0;}$image=$id?wp_get_attachment_image_url($id,'woocommerce_thumbnail'):'';}
    $out='<span class="order-item-identity">';if($image){$out.='<img src="'.esc_url($image).'" width="96" height="96" loading="lazy" alt="'.esc_attr($item->get_name()).'" title="'.esc_attr($historical?'Image recorded at checkout':'Current catalogue image').'">';}
    $out.='<span class="order-item-copy">'.$name.'<small>'.esc_html(($saved?'SKU':'SKU (current catalogue)').': '.($sku!==''?$sku:'Not recorded')).'</small>';
    $quantity=(float)$item->get_quantity();if($quantity>0){$unit=((float)$item->get_total()+(float)$item->get_total_tax())/$quantity;$out.='<small>Purchase price per unit: '.wp_kses_post(wc_price($unit,['currency'=>wc_get_order($item->get_order_id())->get_currency()])).' <span>(after discounts, including recorded tax)</span></small>';}
    return $out.'</span></span>';
},30,2);
add_action('wp_enqueue_scripts',function(){if(function_exists('is_account_page')&&is_account_page()&&is_wc_endpoint_url('view-order')){wp_enqueue_style('loomara-order-item-presentation',get_template_directory_uri().'/assets/order-item-presentation.css',[],filemtime(__DIR__.'/../assets/order-item-presentation.css'));}});
