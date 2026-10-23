<?php
/** Public product-only RSS for Merchant Center scheduled fetch. No customer data. */
defined('ABSPATH') || exit;

function loomara_merchant_feed_item($product,$parent=null){
    if(!$product instanceof WC_Product || $product->get_status()!=='publish' || $product->get_price()===''){return '';}
    $base=$parent?:$product;
    if(!$base->is_visible() || $base->get_meta('_loomara_canonical_product') || $base->get_meta('_loomara_google_exclude')){return '';}
    $url=$product->get_permalink();
    $image=wp_get_attachment_url($product->get_image_id()?:$base->get_image_id());
    if(!$image || !is_numeric($product->get_price())){return '';}
    $description=trim(wp_strip_all_tags($base->get_description()?:$base->get_short_description()));
    if($description===''){return '';}
    $fields=[
        'id'=>$product->get_sku()?:('LM-WC-'.$product->get_id()),
        'title'=>$product->get_name(),'description'=>$description,'link'=>$url,
        'image_link'=>$image,'availability'=>$product->is_on_backorder(1)?'backorder':($product->is_in_stock()?'in_stock':'out_of_stock'),
        'price'=>wc_format_decimal(wc_get_price_to_display($product),wc_get_price_decimals()).' '.get_woocommerce_currency(),
        'brand'=>'Loomara Living',
    ];
    if($parent){
        $fields['item_group_id']=$parent->get_sku()?:('LM-WC-'.$parent->get_id());
        foreach($product->get_attributes() as $key=>$value){
            $name=preg_replace('/^pa_/','',strtolower($key));
            $property=in_array($name,['colour','color'],true)?'color':($name==='size'?'size':'');
            if(!$property || $value===''){continue;}
            if(taxonomy_exists($key)){$term=get_term_by('slug',$value,$key);if($term&&!is_wp_error($term)){$value=$term->name;}}
            $fields[$property]=wp_strip_all_tags((string)$value);
        }
    }
    $xml='<item>';
    foreach($fields as $name=>$value){$xml.='<g:'.$name.'>'.esc_xml($value).'</g:'.$name.'>';}
    return $xml.'</item>';
}

function loomara_merchant_feed_xml(){
    $xml='<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>';
    $xml.='<title>Loomara Living catalogue</title><link>'.esc_xml(home_url('/')).'</link><description>Published Loomara Living products</description>';
    $page=1;
    do{
        $batch=wc_get_products(['status'=>'publish','type'=>['simple','variable'],'limit'=>100,'paginate'=>true,'page'=>$page,'orderby'=>'ID','order'=>'ASC']);
        foreach($batch->products as $parent){
            if(!$parent->is_visible() || $parent->get_meta('_loomara_canonical_product') || $parent->get_meta('_loomara_google_exclude')){continue;}
            if($parent->is_type('variable')){
                foreach($parent->get_visible_children() as $id){
                    $child=wc_get_product($id);
                    if($child instanceof WC_Product_Variation && $child->get_parent_id()===$parent->get_id() && $child->variation_is_visible()){$xml.=loomara_merchant_feed_item($child,$parent);}
                }
            }else{$xml.=loomara_merchant_feed_item($parent);}
        }
        $page++;
    }while($page<=(int)$batch->max_num_pages);
    return $xml.'</channel></rss>';
}

add_action('template_redirect',function(){
    if(($_GET['loomara_product_feed']??null)!=='1'){return;}
    if(!in_array($_SERVER['REQUEST_METHOD']??'GET',['GET','HEAD'],true)){status_header(405);header('Allow: GET, HEAD');exit;}
    if(!function_exists('wc_get_products')){status_header(503);exit;}
    // Anonymous reads keep price filters independent of the signed-in operator.
    if(is_user_logged_in()){status_header(403);exit;}
    header('Content-Type: application/rss+xml; charset=UTF-8');
    header('X-Robots-Tag: noindex');
    header('Cache-Control: public, max-age=900');
    if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD'){exit;}
    $xml=get_transient('loomara_merchant_feed_v1');
    if(!is_string($xml) || $xml===''){$xml=loomara_merchant_feed_xml();set_transient('loomara_merchant_feed_v1',$xml,900);}
    echo $xml;exit;
},0);
