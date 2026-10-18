<?php
/** Reversible catalogue presentation; no inventory writes on frontend requests. */
defined('ABSPATH') || exit;
function loomara_catalog_price($key) {
    $value = loomara_catalog_value($key);
    return $value !== '' && is_numeric($value) && (float)$value >= 0 ? (string)min(100000000, (float)$value) : '';
}
function loomara_wishlist_total($id) {
    global $wpdb;
    return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = '_loomara_wishlist' AND meta_value REGEXP %s", 'i:[0-9]+;i:'.absint($id).';'));
}
function loomara_public_wishlist_counts() {
    $raw=$_POST['ids']??[];if(!is_array($raw)){$raw=[];}
    $ids=array_slice(array_unique(array_map('absint',$raw)),0,32);$counts=[];
    foreach($ids as $id){$p=wc_get_product($id);if($p&&$p->get_status()==='publish'&&$p->is_visible())$counts[$id]=loomara_wishlist_total($id);}
    nocache_headers();wp_send_json_success($counts);
}
add_action('wp_ajax_loomara_wishlist_counts','loomara_public_wishlist_counts');
add_action('wp_ajax_nopriv_loomara_wishlist_counts','loomara_public_wishlist_counts');
add_action('wp_ajax_loomara_toggle_wishlist', function () {
    check_ajax_referer('loomara_wishlist', 'loomara_wishlist_nonce');
    $id=absint($_POST['product_id'] ?? 0); $product=wc_get_product($id);
    if (!$product || $product->get_status() !== 'publish' || !$product->is_visible()) { wp_send_json_error(['message'=>'This product is unavailable.'],400); }
    $ids=loomara_wishlist_ids(); $saved=!in_array($id,$ids,true);
    $ids=$saved ? array_merge($ids,[$id]) : array_values(array_diff($ids,[$id]));
    update_user_meta(get_current_user_id(),'_loomara_wishlist',$ids);
    wp_send_json_success(['saved'=>$saved,'count'=>loomara_wishlist_total($id),'url'=>wc_get_account_endpoint_url('wishlist'),'name'=>$product->get_name()]);
});
add_action('woocommerce_before_main_content', function () {
    if (isset($_GET['wishlist_updated'])) { wc_print_notice('Your wishlist has been updated. <a href="'.esc_url(wc_get_account_endpoint_url('wishlist')).'">Visit your wishlist</a>.','success'); }
});
function loomara_product_material($product) {
    $parent = $product->is_type('variation') ? wc_get_product($product->get_parent_id()) : $product;
    $material = $product->get_attribute('material') ?: $product->get_attribute('pa_material');
    if (!$material && $parent) { $material = $parent->get_attribute('material') ?: $parent->get_attribute('pa_material'); }
    return $material ?: ($parent && has_term('cushion-covers', 'product_cat', $parent->get_id()) ? 'Mixed' : 'Cotton');
}
function loomara_material_options() {
    $options = ['Cotton', 'Mixed'];
    foreach (wc_get_products(['status'=>'publish','limit'=>-1]) as $product) {
        if ($product->is_visible()) { $options[] = loomara_product_material($product); }
    }
    $options = array_values(array_unique($options)); natcasesort($options); return $options;
}
function loomara_catalog_matches_details($entry) {
    $product = $entry['variation'] ?: $entry['parent'];
    $price = $product->get_price(); $min = loomara_catalog_price('min_price'); $max = loomara_catalog_price('max_price');
    if (($min !== '' && ($price === '' || (float)$price < (float)$min)) || ($max !== '' && ($price === '' || (float)$price > (float)$max))) { return false; }
    $material = loomara_catalog_value('catalog_material');
    return !$material || strcasecmp($material, loomara_product_material($product)) === 0;
}
// Keep all filter state when switching collection tabs (category links separately preserve Sale).
add_filter('woocommerce_display_product_attributes', function ($attributes, $product) {
    if (!$product->get_attribute('material') && !$product->get_attribute('pa_material')) {
        $attributes['loomara-material'] = ['label'=>'Material','value'=>esc_html(loomara_product_material($product))];
    }
    return $attributes;
}, 20, 2);
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('loomara-store-refinements', get_template_directory_uri().'/assets/store-refinements-v185.css', ['loomara-catalog-repair'], '1.8.5.'.filemtime(get_template_directory().'/assets/store-refinements-v185.css'));
    wp_enqueue_script('loomara-store-refinements', get_template_directory_uri().'/assets/store-refinements-v185.js', [], '1.8.5.'.filemtime(get_template_directory().'/assets/store-refinements-v185.js'), true);
});
add_filter('woocommerce_checkout_fields',function($groups){
    if(isset($groups['billing']['billing_phone'])){$groups['billing']['billing_phone']['required']=true;$groups['billing']['billing_phone']['label']='Mobile number';}
    foreach($groups as &$fields){foreach($fields as $key=>$field){
        if(($field['type']??'')==='checkbox' && (stripos($key,'newsletter')!==false || preg_match('/subscribe.*newsletter/i',wp_strip_all_tags($field['label']??'')))){unset($fields[$key]);}
    }}unset($fields);return $groups;
},100);
// Content improvements at render time retain original editable page revisions.
add_filter('the_content', function ($content) {
    if (!is_main_query() || !in_the_loop()) { return $content; }
    if (is_page('contact')) {
        return '<section class="contact-hub"><p class="lede">A little help for your home.</p><p>Questions about a piece, an order or a return? Choose how you would like to reach us.</p><div class="contact-channel-grid">'
            .'<a href="mailto:hello@loomaraliving.com"><span aria-hidden="true">✉</span><h2>Email us</h2><p>hello@loomaraliving.com</p></a>'
            .'<a href="tel:+918175950351"><span aria-hidden="true">☎</span><h2>Call us</h2><p>+91 81759 50351</p></a>'
            .'<a href="https://wa.me/918175950351" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">◌</span><h2>WhatsApp</h2><p>Chat with Loomara →</p></a>'
            .'<a href="https://www.instagram.com/loomara.living/" target="_blank" rel="noopener noreferrer">'.loomara_icon('instagram').'<h2>Instagram</h2><p>@loomara.living ↗</p></a></div><p>For order enquiries, include your order number. Please never share passwords, OTPs or payment credentials.</p></section>';
    }
    if (is_page('faqs')) {
        // Preserve merchant-edited questions; only correct the old blanket-size sentence.
        $content = str_replace('Our bedsheets, comforters and bedding combos are King size.', 'Sizes vary by product: Single, Double, Queen or King.', $content);
        if (strpos($content, 'Have more questions?') === false) { $content .= '<p class="faq-contact">Have more questions? <a href="'.esc_url(home_url('/contact/')).'">Contact us</a>.</p>'; }
    }
    return $content;
}, 40);
// Bound edit distance to long words, and retain the typed token as an alternative.
function loomara_search_variants($token) {
    $variants = preg_match('/^bedsheets?$/i', $token) ? ['bedsheet','bed sheet','bed-sheet'] : [$token];
    if (strlen($token) < 5 || strlen($token) > 40 || !preg_match('/^[a-z]+$/i', $token)) { return $variants; }
    static $words;
    if ($words === null) {
        $titles = get_posts(['post_type'=>'product','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids']);
        $text = 'bedsheet bedsheets comforter comforters cushion cushions pillow pillows combo cotton floral textured plain';
        foreach ($titles as $id) { $text .= ' '.get_the_title($id); }
        $words = array_unique(preg_split('/[^a-z]+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY));
    }
    $token = strtolower($token);
    if (in_array($token, $words, true)) { return $variants; }
    $best = strlen($token) >= 9 ? 2 : 1; $matches = [];
    foreach ($words as $word) {
        if (strlen($word) < 5 || abs(strlen($word)-strlen($token)) > $best) { continue; }
        $distance = levenshtein($token, $word);
        if ($distance < $best) { $best=$distance; $matches=[]; }
        if ($distance === $best) { $matches[]=$word; }
    }
    return array_unique(array_merge($variants, array_slice($matches,0,4)));
}
