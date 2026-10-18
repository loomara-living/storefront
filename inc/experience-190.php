<?php
defined('ABSPATH') || exit;
function loomara_corrected_search_label($query) {
    return preg_replace_callback('/[a-zA-Z]{5,40}/',function($m){
        $variants=array_values(loomara_search_variants($m[0]));
        // Correct only an unambiguous nearest match; never invent a correction.
        return count($variants)===2 ? $variants[1] : $m[0];
    },$query);
}
add_filter('woocommerce_shipping_debug_mode', '__return_false', 100);
add_filter('pre_option_woocommerce_shipping_debug_mode',function(){return 'no';});
add_filter('the_content',function($content){
    if(!is_page('faqs')||!is_main_query()||!in_the_loop())return $content;
    return preg_replace_callback('/<p>\s*<a\b[^>]*>Shipping\s*&(?:amp;)?\s*delivery<\/a>\s*·\s*<a\b[^>]*>Returns\s*&(?:amp;)?\s*refunds<\/a>\s*<\/p>/i',function($m){return '<details><summary>Where can I find shipping and return policies?</summary>'.$m[0].'</details>';},$content,1);
},60);
add_action('wp_enqueue_scripts',function(){
    wp_enqueue_style('loomara-experience-190',get_template_directory_uri().'/assets/experience-v190.css',['loomara-orders-delivery'],filemtime(__DIR__.'/../assets/experience-v190.css'));
    wp_enqueue_style('loomara-cart-polish-194',get_template_directory_uri().'/assets/cart-polish-v194.css',['loomara-experience-190','loomara-store-refinements'],filemtime(__DIR__.'/../assets/cart-polish-v194.css'));
    wp_enqueue_script('loomara-experience-190',get_template_directory_uri().'/assets/experience-v190.js',[],filemtime(__DIR__.'/../assets/experience-v190.js'),true);
});
