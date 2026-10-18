<?php
/** Colour-level category cards; one sellable product remains one design. */
defined('ABSPATH') || exit;
function loomara_category_colour_entries($parents) {
    $entries = [];
    foreach ($parents as $post) {
        $parent = wc_get_product($post->ID);
        if (!$parent || !$parent->is_visible() || $parent->get_meta('_loomara_canonical_product')) { continue; }
        $colours = [];
        if ($parent->is_type('variable')) {
            foreach ($parent->get_available_variations('objects') as $variation) {
                if (loomara_collection_selection() === 'sale' && !$variation->is_on_sale()) { continue; }
                foreach ($variation->get_attributes() as $key => $value) {
                    if (!in_array(strtolower(str_replace('pa_', '', $key)), ['colour','color'], true) || $value === '') { continue; }
                    $label = $value;
                    if (taxonomy_exists($key)) { $term = get_term_by('slug',$value,$key); if ($term) { $label = $term->name; } }
                    $defaults = $parent->get_default_attributes();
                    $preferred = true;
                    foreach ($defaults as $attribute => $default) {
                        if ($attribute !== $key && $variation->get_attribute($attribute) !== $default) { $preferred = false; }
                    }
                    if (!isset($colours[$key . ':' . $value]) || $preferred) {
                        $colours[$key . ':' . $value] = ['parent'=>$parent,'variation'=>$variation,'colour'=>$label];
                    }
                    break;
                }
            }
        }
        if ($colours) { $entries = array_merge($entries,array_values($colours)); }
        elseif (!($parent->is_type('variable') && loomara_collection_selection() === 'sale')) { $entries[] = ['parent'=>$parent,'variation'=>null,'colour'=>'']; }
    }
    return $entries;
}
function loomara_category_colour_card($entry) {
    global $product, $post;
    $product = $entry['parent']; $post = get_post($product->get_id()); setup_postdata($post);
    $variation = $entry['variation'];
    if (!$variation) { wc_get_template_part('content','product'); return; }
    $parent_id = $product->get_id();
    $name = $product->get_name() . ' — ' . $entry['colour'];
    $url = $variation->get_permalink();
    $image_id = $variation->get_image_id() ?: $product->get_image_id();
    $price = $variation->get_price_html();
    $filters = [
        'the_title' => function ($value,$id) use ($parent_id,$name) { return (int)$id === $parent_id ? $name : $value; },
        'woocommerce_product_get_name' => function ($value,$p) use ($parent_id,$name) { return $p->get_id() === $parent_id ? $name : $value; },
        'woocommerce_product_get_image_id' => function ($value,$p) use ($parent_id,$image_id) { return $p->get_id() === $parent_id ? $image_id : $value; },
        'woocommerce_get_price_html' => function ($value,$p) use ($parent_id,$price) { return $p->get_id() === $parent_id ? $price : $value; },
        'woocommerce_loop_product_link' => function ($value,$p) use ($parent_id,$url) { return $p->get_id() === $parent_id ? $url : $value; },
        'woocommerce_loop_add_to_cart_link' => function ($value,$p) use ($parent_id,$url,$name) {
            return $p->get_id() === $parent_id ? '<a class="button product_type_variable" href="' . esc_url($url) . '" aria-label="' . esc_attr('Choose options for ' . $name) . '">Choose options</a>' : $value;
        },
    ];
    foreach ($filters as $hook=>$callback) { add_filter($hook,$callback,99,2); }
    try { wc_get_template_part('content','product'); }
    finally { foreach ($filters as $hook=>$callback) { remove_filter($hook,$callback,99); } }
}
function loomara_category_colour_content() {
    global $wp_query, $product;
    do_action('woocommerce_archive_description');
    // Expand BEFORE pagination: a second colour never disappears on another page.
    // A separate page parameter avoids WordPress 404s based on the parent count.
    $args = $wp_query->query_vars;
    $args['posts_per_page'] = -1; $args['nopaging'] = true; $args['paged'] = 1;
    $args['fields'] = 'all'; $args['no_found_rows'] = true;
    $parents = new WP_Query($args);
    $entries = loomara_category_colour_entries($parents->posts);
    $orderby = sanitize_text_field(wp_unslash($_GET['orderby'] ?? ''));
    if (in_array($orderby,['price','price-desc'],true)) {
        usort($entries,function ($a,$b) use ($orderby) {
            $left=$a['variation'] ?: $a['parent']; $right=$b['variation'] ?: $b['parent'];
            $result=(float)$left->get_price() <=> (float)$right->get_price();
            return $orderby === 'price-desc' ? -$result : $result;
        });
    }
    $total = count($entries); $per_page = 8; $pages = max(1,(int)ceil($total/$per_page));
    $page = min($pages,max(1,absint($_GET['colour_page'] ?? 1)));
    wc_set_loop_prop('total',$total); wc_set_loop_prop('per_page',$per_page);
    wc_set_loop_prop('current_page',$page); wc_set_loop_prop('total_pages',$pages);
    wc_set_loop_prop('columns',4); wc_set_loop_prop('is_paginated',true);
    if (!$total) { do_action('woocommerce_no_products_found'); return; }
    do_action('woocommerce_before_shop_loop');
    woocommerce_product_loop_start();
    foreach (array_slice($entries,($page-1)*$per_page,$per_page) as $entry) { loomara_category_colour_card($entry); }
    woocommerce_product_loop_end();
    $pagination_priority = has_action('woocommerce_after_shop_loop','woocommerce_pagination');
    if ($pagination_priority !== false) { remove_action('woocommerce_after_shop_loop','woocommerce_pagination',$pagination_priority); }
    try { do_action('woocommerce_after_shop_loop'); }
    finally { if ($pagination_priority !== false) { add_action('woocommerce_after_shop_loop','woocommerce_pagination',$pagination_priority); } }
    if ($pages>1) {
        $base = str_replace('999999999','%#%',add_query_arg('colour_page',999999999));
        echo '<nav class="woocommerce-pagination" aria-label="Product pagination">' . wp_kses_post(paginate_links(['base'=>$base,'format'=>'','current'=>$page,'total'=>$pages,'type'=>'list'])) . '</nav>';
    }
    wp_reset_postdata(); wc_reset_loop(); $product = null;
}
