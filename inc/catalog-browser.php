<?php
/** Shared, server-rendered catalogue filters and progressive loading. */
defined('ABSPATH') || exit;
function loomara_catalog_value($key) {
    $value = $_GET[$key] ?? '';
    return is_scalar($value) ? sanitize_text_field(wp_unslash((string)$value)) : '';
}
function loomara_catalog_size() {
    $size = sanitize_key(loomara_catalog_value('catalog_size'));
    return in_array($size, ['single','double','queen','king','unspecified'], true) ? $size : '';
}
function loomara_catalog_category() {
    $slug = sanitize_title(loomara_catalog_value('catalog_category'));
    if (!$slug && is_product_category()) { $slug = get_queried_object()->slug; }
    return $slug && get_term_by('slug', $slug, 'product_cat') ? $slug : '';
}
function loomara_catalog_order() {
    $order = loomara_catalog_value('orderby');
    return in_array($order, ['popularity','rating','date','price','price-desc'], true) ? $order : 'menu_order';
}
function loomara_catalog_matches_size($product) {
    $wanted = loomara_catalog_size();
    if (!$wanted) { return true; }
    $value = $product->get_attribute('size') ?: $product->get_attribute('pa_size');
    if (!$value) { $value = $product->get_meta('_loomara_size_code'); }
    $aliases = ['s'=>'single','d'=>'double','q'=>'queen','k'=>'king','x'=>'unspecified','size-not-specified'=>'unspecified','unknown'=>'unspecified'];
    $actual = sanitize_title($value);
    return ($aliases[$actual] ?? ($actual ?: 'unspecified')) === $wanted;
}
function loomara_catalog_filters() {
    $category = loomara_catalog_category(); $size = loomara_catalog_size();
    $orders = ['menu_order'=>'Recommended','date'=>'Newest first','price'=>'Price: low to high','price-desc'=>'Price: high to low','popularity'=>'Best selling','rating'=>'Highest rated'];
    $active = (int)($category !== '') + (int)($size !== '');
    echo '<aside class="catalog-filter-panel" id="catalog-filter-panel" aria-label="Catalogue filters"><div data-filter-content><div class="catalog-filter-heading"><h2 id="catalog-filter-heading">Filters &amp; sorting</h2><a class="catalog-filter-close" data-filter-close href="#catalog-products" aria-label="Close filters">×</a></div>';
    echo '<form class="catalog-filters" method="get" action="'.esc_url(wc_get_page_permalink('shop')).'" aria-label="Filter and sort products">';
    if (is_search()) { echo '<input type="hidden" name="s" value="'.esc_attr(get_search_query()).'"><input type="hidden" name="post_type" value="product">'; }
    if (loomara_collection_selection() !== 'all') { echo '<input type="hidden" name="collection" value="'.esc_attr(loomara_collection_selection()).'">'; }
    echo '<label>Category<select name="catalog_category"><option value="">All categories</option>';
    $terms = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'exclude'=>[(int)get_option('default_product_cat')]]);
    if (!is_wp_error($terms)) { foreach ($terms as $term) { echo '<option value="'.esc_attr($term->slug).'"'.selected($category,$term->slug,false).'>'.esc_html($term->name).'</option>'; } }
    echo '</select></label><label>Size<select name="catalog_size"><option value="">All sizes</option>';
    foreach (['single'=>'Single','double'=>'Double','queen'=>'Queen','king'=>'King','unspecified'=>'Size not specified'] as $key=>$label) { echo '<option value="'.esc_attr($key).'"'.selected($size,$key,false).'>'.esc_html($label).'</option>'; }
    echo '</select></label><label class="catalog-sort">Sort by<select name="orderby">';
    foreach ($orders as $key=>$label) { echo '<option value="'.esc_attr($key).'"'.selected(loomara_catalog_order(),$key,false).'>'.esc_html($label).'</option>'; }
    echo '</select></label><button class="button" type="submit">Apply filters</button><a class="catalog-clear" href="'.esc_url(wc_get_page_permalink('shop')).'">Clear filters</a></form></div></aside>';
    echo '<dialog class="catalog-filter-dialog" data-filter-dialog aria-labelledby="catalog-filter-heading"><div data-filter-mount></div></dialog><a class="catalog-filter-toggle" data-filter-toggle href="#catalog-filter-panel" role="button" aria-controls="catalog-filter-panel" aria-expanded="false"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/><circle cx="8" cy="6" r="2" fill="currentColor"/><circle cx="16" cy="12" r="2" fill="currentColor"/><circle cx="10" cy="18" r="2" fill="currentColor"/></svg><span>Filters &amp; sort</span>'.($active ? '<span class="catalog-filter-count">'.esc_html($active).'</span>' : '').'</a>';
}
function loomara_catalog_sort_entries($entries) {
    $order = loomara_catalog_order();
    usort($entries, function ($a, $b) use ($order) {
        $left = $a['variation'] ?: $a['parent']; $right = $b['variation'] ?: $b['parent'];
        $pa = $a['parent']; $pb = $b['parent']; $comparison = 0;
        if ($order === 'price' || $order === 'price-desc') {
            $comparison = (float)$left->get_price() <=> (float)$right->get_price();
            if ($order === 'price-desc') { $comparison = -$comparison; }
        } elseif ($order === 'popularity') { $comparison = $pb->get_total_sales() <=> $pa->get_total_sales(); }
        elseif ($order === 'rating') { $comparison = (float)$pb->get_average_rating() <=> (float)$pa->get_average_rating(); }
        elseif ($order === 'date') { $comparison = ($pb->get_date_created() ? $pb->get_date_created()->getTimestamp() : 0) <=> ($pa->get_date_created() ? $pa->get_date_created()->getTimestamp() : 0); }
        else { $comparison = $pa->get_menu_order() <=> $pb->get_menu_order(); }
        if (!$comparison) { $comparison = strnatcasecmp($pa->get_name(), $pb->get_name()); }
        return $comparison ?: strcmp($left->get_sku() ?: (string)$left->get_id(), $right->get_sku() ?: (string)$right->get_id());
    });
    return $entries;
}
add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_shop') && (is_shop() || is_product_taxonomy() || (is_search() && in_array('product',(array)get_query_var('post_type'),true)))) {
        wp_enqueue_script('loomara-catalog-browser', get_template_directory_uri().'/assets/catalog-browser-v183.js', [], '1.8.3', true);
    }
});
