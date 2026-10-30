<?php
/** Presentation only. Native catalogue, review eligibility and commerce actions stay intact. */
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts',function(){
    wp_enqueue_style('loomara-storefront-aesthetics',get_template_directory_uri().'/assets/storefront-aesthetics.css',[],filemtime(__DIR__.'/../assets/storefront-aesthetics.css'));
},100);
