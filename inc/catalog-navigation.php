<?php
/** Accessible catalogue breadcrumbs and direct category navigation. */
defined('ABSPATH') || exit;
function loomara_navigation_category($product_id) {
    $terms = get_the_terms($product_id,'product_cat');
    if (!$terms || is_wp_error($terms)) { return null; }
    $default = (int)get_option('default_product_cat');
    $meaningful = array_filter($terms,function ($term) use ($default) { return $term->term_id !== $default; });
    if ($meaningful) { $terms = array_values($meaningful); }
    usort($terms,function ($a,$b) {
        $depth = count(get_ancestors($b->term_id,'product_cat','taxonomy')) <=> count(get_ancestors($a->term_id,'product_cat','taxonomy'));
        return $depth ?: ($a->term_id <=> $b->term_id);
    });
    return $terms[0];
}
function loomara_catalog_navigation() {
    $crumbs = [['label'=>'Home','url'=>home_url('/')]];
    $category = null; $code = '';
    if (is_product_category()) { $category = get_queried_object(); }
    elseif (is_product()) {
        $p = wc_get_product(get_queried_object_id());
        if ($p) { $category=loomara_navigation_category($p->get_id()); $code=$p->get_sku() ?: $p->get_name(); }
    }
    if ($category) {
        $ids = array_merge(array_reverse(get_ancestors($category->term_id,'product_cat','taxonomy')),[$category->term_id]);
        foreach ($ids as $id) {
            $term=get_term($id,'product_cat');
            if (!$term || is_wp_error($term)) { continue; }
            $url=get_term_link($term); if (is_wp_error($url)) { continue; }
            $crumbs[]=['label'=>$term->name,'url'=>$url];
        }
    } elseif (!is_shop() && !is_product()) {
        $crumbs[]=['label'=>'Shop','url'=>wc_get_page_permalink('shop')];
    }
    if (is_product()) { $crumbs[]=['label'=>$code,'url'=>'','product'=>true]; }
    elseif (is_shop()) { $crumbs[]=['label'=>'Shop','url'=>'']; }
    elseif (is_search()) { $crumbs[]=['label'=>'Search results','url'=>'']; }
    elseif (!$category && is_product_taxonomy()) { $crumbs[]=['label'=>single_term_title('',false),'url'=>'']; }
    echo '<div class="catalog-navigation"><nav class="catalog-breadcrumb" aria-label="Breadcrumb"><ol>';
    foreach ($crumbs as $index=>$crumb) {
        $current=$index === count($crumbs)-1;
        echo '<li>';
        if ($index) { echo '<span class="breadcrumb-separator" aria-hidden="true">›</span>'; }
        if (!$current) { echo '<a href="'.esc_url($crumb['url']).'">'.esc_html($crumb['label']).'</a>'; }
        else { echo '<span aria-current="page"'.(!empty($crumb['product']) ? ' data-breadcrumb-product-code data-default-code="'.esc_attr($code).'"' : '').'>'.esc_html($crumb['label']).'</span>'; }
        echo '</li>';
    }
    echo '</ol></nav>';
    if (is_product()) { loomara_catalog_category_links('Browse categories', $category); }
    echo '</div>';
}
function loomara_catalog_category_links($label = 'Shop by category', $category = null) {
    if (!$category && is_product_category()) { $category = get_queried_object(); }
    if (!$category && function_exists('loomara_catalog_category') && loomara_catalog_category()) { $category = get_term_by('slug',loomara_catalog_category(),'product_cat'); }
    echo '<nav class="catalog-category-links" aria-label="'.esc_attr($label).'">';
    foreach (['bedsheets','comforters','combo','cushion-covers'] as $slug) {
        $term=get_term_by('slug',$slug,'product_cat'); if (!$term) { continue; }
        $url=get_term_link($term); if (is_wp_error($url)) { continue; }
        if (function_exists('loomara_collection_selection') && loomara_collection_selection() !== 'all') { $url = add_query_arg('collection', loomara_collection_selection(), $url); }
        $active=$category && ($category->term_id === $term->term_id || term_is_ancestor_of($term,$category,'product_cat'));
        echo '<a'.($active ? ' class="is-active"' : '').($active && is_product_category() && $category->term_id === $term->term_id ? ' aria-current="page"' : '').' href="'.esc_url($url).'">'.esc_html($term->name).'</a>';
    }
    echo '</nav>';
    if (!is_product() && function_exists('loomara_catalog_category') && loomara_catalog_category()) {
        $root=get_term_by('slug',loomara_catalog_category(),'product_cat');
        $children=get_terms(['taxonomy'=>'product_cat','parent'=>$root->term_id,'hide_empty'=>true]);
        if(!is_wp_error($children)&&$children){
            echo '<nav class="catalog-style-links" aria-label="Shop by style">';
            foreach($children as $child){$url=get_term_link($child);if(is_wp_error($url))continue;if(loomara_collection_selection()!=='all')$url=add_query_arg('collection',loomara_collection_selection(),$url);echo '<a href="'.esc_url($url).'"'.(loomara_catalog_subcategory()===$child->slug?' aria-current="page"':'').'>'.esc_html($child->name).'</a>';}
            echo '</nav>';
        }
    }
}
