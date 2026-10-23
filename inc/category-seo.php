<?php
/** Metadata-only defaults for the four main collections. No markup or data writes. */
defined('ABSPATH') || exit;

function loomara_category_seo_profiles() {
    return [
        'bedsheets' => [
            'title' => 'Bedsheets Online in India | Loomara Living',
            'description' => 'Explore Loomara Living bedsheets in floral and patterned designs. Compare available colours and sizes, and shop online with free shipping across India.',
        ],
        'comforters' => [
            'title' => 'Comforters Online in India | Loomara Living',
            'description' => 'Shop Loomara Living comforters for your bedroom. Explore plain and floral styles, compare available sizes and colours, with free shipping across India.',
        ],
        'combo' => [
            'title' => 'Bedding Combo Sets Online in India | Loomara Living',
            'description' => 'Explore coordinated bedding combo sets at Loomara Living. Check each set for included pieces, colours and sizes. Enjoy free shipping across India.',
        ],
        'cushion-covers' => [
            'title' => 'Cushion Covers Online in India | Loomara Living',
            'description' => 'Explore Loomara Living cushion covers for sofas, beds and reading corners. Check each listing for size and pack details, with free shipping across India.',
        ],
    ];
}

function loomara_category_seo_context() {
    if (is_admin() || is_search() || is_feed() || is_preview() || !is_tax('product_cat') || (int)get_query_var('paged') > 1) { return null; }
    // Never apply an unfiltered collection description to search/filter/pagination URLs.
    $noise = ['v','utm_source','utm_medium','utm_campaign','utm_term','utm_content','gclid','fbclid'];
    foreach (array_keys($_GET) as $key) { if (!in_array($key,$noise,true)) { return null; } }
    $term = get_queried_object();
    if (!is_object($term) || ($term->taxonomy ?? '') !== 'product_cat' || (int)($term->parent ?? -1) !== 0) { return null; }
    $profiles = loomara_category_seo_profiles();
    return isset($profiles[$term->slug ?? '']) ? ['term'=>$term,'profile'=>$profiles[$term->slug]] : null;
}

function loomara_category_seo_text($value) {
    return trim(preg_replace('/\s+/u',' ',html_entity_decode(wp_strip_all_tags((string)$value),ENT_QUOTES | ENT_HTML5,'UTF-8')));
}

function loomara_category_seo_title($title) {
    $context = loomara_category_seo_context();
    if (!$context) { return $title; }
    $current = loomara_category_seo_text($title);
    $term_name = loomara_category_seo_text($context['term']->name);
    $site_name = loomara_category_seo_text(get_bloginfo('name'));
    // Respect any explicitly customised title rather than taking over the plugin.
    $default_pattern = '/^'.preg_quote($term_name,'/').'\s*[-–—|»›‣→•\/]\s*'.preg_quote($site_name,'/').'$/u';
    return $current === '' || preg_match($default_pattern,$current) ? $context['profile']['title'] : $title;
}

function loomara_category_seo_description($description) {
    $context = loomara_category_seo_context();
    if (!$context) { return $description; }
    $current = loomara_category_seo_text($description);
    $native = loomara_category_seo_text($context['term']->description ?? '');
    // A separate AIOSEO description wins; visible term descriptions are never edited.
    return $current === '' || $current === $native ? $context['profile']['description'] : $description;
}

add_filter('aioseo_title','loomara_category_seo_title',20);
add_filter('aioseo_description','loomara_category_seo_description',20);

// Start the existing font connections earlier; keep the same fonts and rendering.
add_filter('wp_resource_hints',function($urls,$relation){
    if($relation!=='preconnect' || !wp_style_is('loomara-fonts','enqueued')){return $urls;}
    $hosts=[];
    foreach($urls as $url){$hosts[]=rtrim(is_array($url)?($url['href']??''):$url,'/');}
    if(!in_array('https://fonts.googleapis.com',$hosts,true)){$urls[]='https://fonts.googleapis.com';}
    if(!in_array('https://fonts.gstatic.com',$hosts,true)){$urls[]=['href'=>'https://fonts.gstatic.com','crossorigin'=>'anonymous'];}
    return $urls;
},20,2);
