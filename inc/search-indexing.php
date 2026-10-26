<?php
/** Scoped public SEO hints; never alter catalogue queries or transactional routes. */
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
    // These explicit defaults describe the same unfiltered results, not a new page.
    $defaults=['collection'=>'all','orderby'=>'menu_order'];
    if(function_exists('loomara_catalog_category') && is_tax('product_cat')){
        $defaults['catalog_category']=loomara_catalog_category();
        $defaults['catalog_subcategory']=loomara_catalog_subcategory();
    }
    foreach($defaults as $key=>$expected){
        if(isset($_GET[$key]) && is_scalar($_GET[$key]) && (string)$_GET[$key]===$expected){$allowed[]=$key;}
    }
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
    $targets=['returns-policy'=>'returns-refunds','about'=>'our-story'];
    $destination='';
    foreach($targets as $old_slug=>$new_slug){
        $old=wp_parse_url(home_url('/'.$old_slug.'/'),PHP_URL_PATH);
        if(is_string($path)&&rtrim($path,'/')===rtrim($old,'/')){$destination=$new_slug;break;}
    }
    if(!$destination){return '';}
    $page=get_page_by_path($destination);
    if(!$page || get_post_status($page)!=='publish'){return '';}
    return loomara_clean_canonical_url(get_permalink($page));
}
add_action('template_redirect',function(){
    $target=loomara_retired_policy_target($_SERVER['REQUEST_URI']??'',$_SERVER['REQUEST_METHOD']??'');
    if($target){wp_safe_redirect($target,301,'Loomara Living');exit;}
},1);

/** Filter-only views are useful to shoppers, but not separate search landing pages. */
function loomara_catalog_filter_view(){
    if(is_admin() || is_search() || is_feed() || is_preview() || is_404()){return false;}
    if(!(function_exists('is_shop')&&is_shop()) && !is_tax('product_cat')){return false;}
    foreach(['collection','orderby','catalog_size','catalog_material','min_price','max_price'] as $key){
        if(!isset($_GET[$key]) || $_GET[$key]===''){continue;}
        if($key==='collection' && $_GET[$key]==='all'){continue;}
        if($key==='orderby' && $_GET[$key]==='menu_order'){continue;}
        return true;
    }
    return false;
}
function loomara_seo_utility_noindex(){
    if(is_admin() || is_preview()){return false;}
    if(is_feed() || (function_exists('is_author')&&is_author())){return true;}
    if(is_tax('product_cat')){
        $term=get_queried_object();
        if(is_object($term)&&($term->slug??'')==='uncategorized'){return true;}
    }
    return loomara_catalog_filter_view();
}
add_filter('wp_robots',function($robots){
    if(loomara_seo_utility_noindex()){unset($robots['index']);$robots['noindex']=true;}
    return $robots;
},30);
add_filter('aioseo_robots_meta',function($robots){
    if(loomara_seo_utility_noindex()){unset($robots['index']);$robots['noindex']='noindex';}
    return $robots;
},30);
// XML feeds do not contain an HTML head. Do not touch REST/API responses.
add_action('template_redirect',function(){
    if(!is_admin() && is_feed() && !headers_sent()){header('X-Robots-Tag: noindex',false);}
},2);

/** Filter rendered term entries only; never exclude products assigned to a term. */
function loomara_seo_sitemap_terms($entries){
    if(!is_array($entries)){return $entries;}
    $utility=rtrim(home_url('/product-category/uncategorized/'),'/');
    $out=[];
    foreach($entries as $entry){
        if(!is_array($entry)){$out[]=$entry;continue;}
        if(rtrim((string)($entry['loc']??''),'/')===$utility){continue;}
        if(isset($entry['lastmod']) && is_string($entry['lastmod']) && str_starts_with($entry['lastmod'],'1970-01-01')){unset($entry['lastmod']);}
        $out[]=$entry;
    }
    return $out;
}
add_filter('aioseo_sitemap_terms','loomara_seo_sitemap_terms',30);
