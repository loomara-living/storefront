<?php
/** Public canonical hints and one retired policy URL. No indexing-policy changes. */
defined('ABSPATH') || exit;

function loomara_clean_canonical_url($url){
    return remove_query_arg(['v','utm_source','utm_medium','utm_campaign','utm_term','utm_content','gclid','fbclid'], $url);
}
add_filter('get_canonical_url','loomara_clean_canonical_url',20);

/** Colour pagination is independent of WordPress's parent-product pagination. */
function loomara_catalog_page_canonical($url){
    if(is_admin() || is_search() || is_feed() || is_preview() || is_404()){return $url;}
    if(!(function_exists('is_shop') && is_shop()) && !is_tax('product_cat')){return $url;}
    $allowed=['colour_page','v','utm_source','utm_medium','utm_campaign','utm_term','utm_content','gclid','fbclid'];
    foreach(array_keys($_GET) as $key){if(!in_array($key,$allowed,true)){return $url;}}
    $page=$_GET['colour_page']??null;
    if(!is_scalar($page) || !preg_match('/^[1-9][0-9]{0,5}$/',(string)$page)){return $url;}
    $base=is_shop()?wc_get_page_permalink('shop'):get_term_link(get_queried_object());
    if(is_wp_error($base) || !$base){return $url;}
    // Preserve a deliberately different canonical destination configured by staff.
    if($url && rtrim(strtok($url,'?'),'/')!==rtrim($base,'/')){return $url;}
    return (int)$page===1?$base:add_query_arg('colour_page',(int)$page,$base);
}
add_filter('aioseo_canonical_url','loomara_catalog_page_canonical',20);

function loomara_archive_canonical_url(){
    if(is_admin() || is_404() || is_search() || is_feed() || is_preview() || is_singular()) {return '';}
    if((function_exists('is_cart')&&is_cart()) || (function_exists('is_checkout')&&is_checkout()) || (function_exists('is_account_page')&&is_account_page())) {return '';}
    // Let an SEO plugin own its canonical output if one is introduced later.
    if(defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION')) {return '';}
    // Do not incorrectly declare filtered collections equivalent to an unfiltered page.
    $noise=['v','utm_source','utm_medium','utm_campaign','utm_term','utm_content','gclid','fbclid','paged','page'];
    foreach(array_keys($_GET) as $key){if(!in_array($key,$noise,true)){return '';}}
    if(is_front_page()){ $url=home_url('/'); }
    elseif(function_exists('is_shop')&&is_shop()){ $url=wc_get_page_permalink('shop'); }
    elseif(is_category() || is_tag() || is_tax()){
        $url=get_term_link(get_queried_object()); if(is_wp_error($url)){return '';}
    }else{return '';}
    $page=max(1,(int)get_query_var('paged'));
    if($page>1){$url=get_pagenum_link($page,false);}
    return loomara_clean_canonical_url($url);
}
add_action('wp_head',function(){
    $url=loomara_archive_canonical_url();
    if($url){echo '<link rel="canonical" href="'.esc_url($url).'" />'."\n";}
},9);

function loomara_retired_policy_target($uri,$method){
    if(!in_array($method,['GET','HEAD'],true)){return '';}
    $path=wp_parse_url($uri,PHP_URL_PATH);
    $old=wp_parse_url(home_url('/returns-policy/'),PHP_URL_PATH);
    if(!is_string($path)||rtrim($path,'/')!==rtrim($old,'/')){return '';}
    $page=get_page_by_path('returns-refunds');
    if(!$page || get_post_status($page)!=='publish'){return '';}
    return loomara_clean_canonical_url(get_permalink($page));
}
add_action('template_redirect',function(){
    $target=loomara_retired_policy_target($_SERVER['REQUEST_URI']??'',$_SERVER['REQUEST_METHOD']??'');
    if($target){wp_safe_redirect($target,301,'Loomara Living');exit;}
},1);
