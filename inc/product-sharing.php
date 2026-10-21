<?php
/** Mobile public-product sharing; customers choose the app/recipient and send. */
defined('ABSPATH') || exit;
function loomara_product_share_data($product){
    if(!$product instanceof WC_Product || $product->get_status()!=='publish'){return [];}
    $url=get_permalink($product->get_id());
    if(!is_string($url)||!preg_match('~^https?://~i',$url)){return [];}
    $title=sanitize_text_field(html_entity_decode(wp_strip_all_tags($product->get_name()),ENT_QUOTES,'UTF-8'));
    $price=$product->get_price()===''?'':sanitize_text_field(html_entity_decode(wp_strip_all_tags(wc_price(wc_get_price_to_display($product))),ENT_QUOTES,'UTF-8'));
    if($price!==''&&$product->is_type('variable')){$price='From '.$price;}
    $image=wp_get_attachment_image_src($product->get_image_id(),'large');
    return ['title'=>$title,'price'=>$price,'url'=>$url,'image'=>is_array($image)?$image[0]:'','text'=>$title.($price!==''?' — '.$price:'').' · Loomara Living'];
}
function loomara_product_whatsapp_share_url($product){
    $data=loomara_product_share_data($product);
    return $data?'https://wa.me/?text='.rawurlencode($data['text'].' '.$data['url']):'';
}
function loomara_product_share_icon(){
    return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4M8.6 13.5l6.8 4"/></svg>';
}
function loomara_product_share(){
    global $product;$data=loomara_product_share_data($product);if(!$data){return;}
    // WordPress removes encoded newlines from non-mailto URLs. A plain space
    // keeps the fallback URL recognisable after native esc_url sanitisation.
    $text=$data['text'].' '.$data['url'];$icon=loomara_product_share_icon();
    echo '<div class="loomara-product-share" data-product-share="'.esc_attr(wp_json_encode($data)).'"><button type="button" class="loomara-share-icon" data-native-product-share hidden aria-label="Share product" title="Share product">'.$icon.'</button><details class="loomara-share-fallback"><summary class="loomara-share-icon" aria-label="Share product" title="Share product">'.$icon.'</summary><nav aria-label="Share product options"><a href="'.esc_url('https://wa.me/?text='.rawurlencode($text)).'" target="_blank" rel="noopener noreferrer">WhatsApp</a><a href="'.esc_url('mailto:?subject='.rawurlencode($data['title']).'&body='.rawurlencode($text)).'">Email</a><a href="'.esc_url('sms:?body='.rawurlencode($text)).'">Message / SMS</a></nav></details><p class="loomara-share-status" role="status" hidden></p></div>';
}
// Rendered by the shared gallery controls below wishlist; no second summary copy.
add_action('wp_enqueue_scripts',function(){
    if(function_exists('is_product')&&is_product()){
        wp_enqueue_style('loomara-product-sharing',get_template_directory_uri().'/assets/product-sharing.css',[],'4');
        wp_enqueue_script('loomara-product-sharing',get_template_directory_uri().'/assets/product-sharing.js',[],'2',true);
    }
});
// Supply public link-preview metadata unless a known SEO plugin owns it.
add_action('wp_head',function(){
    if(!function_exists('is_product')||!is_product()||defined('WPSEO_VERSION')||defined('RANK_MATH_VERSION')||defined('SEOPRESS_VERSION')||function_exists('aioseo')){return;}
    $data=loomara_product_share_data(wc_get_product(get_queried_object_id()));if(!$data){return;}
    foreach(['og:type'=>'product','og:title'=>$data['title'].' | Loomara Living','og:url'=>$data['url'],'og:description'=>$data['text'],'og:image'=>$data['image']] as $property=>$value){if($value!==''){echo '<meta property="'.esc_attr($property).'" content="'.esc_attr($value).'">'."\n";}}
},5);
