<?php
/** Remove the optional Cashfree promotion widget, never the payment SDK/gateway. */
defined('ABSPATH') || exit;
function loomara_remove_cashfree_widget(){
    if(is_admin()){return;}
    $scripts=wp_scripts();$handle='cf-woocommerce-js';
    $src=$scripts->registered[$handle]->src??'';
    if(!is_string($src)){return;}
    $url=wp_parse_url(str_starts_with($src,'//')?'https:'.$src:$src);
    // Exact observed vendor/handle and widget path only. Leave checkout SDKs alone.
    if(!is_array($url)||strtolower($url['host']??'')!=='sdk.cashfree.com'||!str_starts_with($url['path']??'','/js/widget/')){return;}
    wp_dequeue_script($handle);
}
add_action('wp_enqueue_scripts','loomara_remove_cashfree_widget',PHP_INT_MAX);
add_action('wp_print_scripts','loomara_remove_cashfree_widget',0);
add_action('wp_print_footer_scripts','loomara_remove_cashfree_widget',0);
