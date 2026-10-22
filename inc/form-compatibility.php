<?php
/** Avoid named form-action shadowing without patching or disabling payment SDKs. */
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts',function(){
    wp_enqueue_script('loomara-form-compatibility',get_template_directory_uri().'/assets/form-compatibility.js',[], '1',false);
},0);
add_filter('script_loader_tag',function($tag,$handle){
    return $handle==='loomara-form-compatibility'?str_replace('<script ','<script data-no-optimize="1" data-no-defer="1" data-cfasync="false" ',$tag):$tag;
},10,2);
