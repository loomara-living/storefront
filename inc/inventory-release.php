<?php
/** Explicit, administrator-only inventory migration. Never runs on storefront requests. */
defined('ABSPATH') || exit;
function loomara_release_catalog() {
    return json_decode(file_get_contents(get_template_directory() . '/assets/inventory/catalog.json'), true);
}
function loomara_release_preflight($catalog) {
    if (wp_parse_url(home_url(), PHP_URL_HOST) !== $catalog['domain']) { throw new Exception('Wrong website: inventory migration refused.'); }
    foreach ($catalog['designs'] as $design) {
        foreach ($design['variants'] as $variant) {
            $product = wc_get_product($variant['source_id']);
            if (!$product || $product->get_type() !== 'simple' || stripos($product->get_name(), $design['name']) !== 0) { throw new Exception('Product identity/type mismatch for ' . $variant['sku']); }
            if (wc_get_product_id_by_sku($variant['sku']) || wc_get_product_id_by_sku($design['design_id'])) { throw new Exception('SKU already exists. Review migration status before retrying.'); }
            foreach ($variant['images'] as $file) {
                if (basename($file) !== $file || !is_file(get_template_directory() . '/assets/inventory/' . $file)) { throw new Exception('Missing inventory image.'); }
            }
        }
    }
}
add_action('admin_menu', function () {
    add_submenu_page('woocommerce', 'Loomara inventory release', 'Loomara inventory release', 'manage_woocommerce', 'loomara-inventory-release', 'loomara_inventory_release_page');
});
function loomara_inventory_release_page() {
    if (!current_user_can('manage_woocommerce')) { return; }
    echo '<div class="wrap"><h1>Loomara inventory release v1.7.0</h1>';
    $state = get_option('loomara_inventory_release_170');
    if ($state) { echo '<h2>Migration status</h2><pre>' . esc_html(wp_json_encode($state, JSON_PRETTY_PRINT)) . '</pre>'; }
    $catalog = loomara_release_catalog();
    echo '<p>Four designs, five variants. King size for bedsheets, comforters and combos. Existing prices, stock, orders and customers are preserved. The second bedsheet URL redirects to the colour selector, without deleting its original product record.</p>';
    if (!$state) {
        try {
            loomara_release_preflight($catalog);
            echo '<p>Preflight passed. Product snapshot will be saved in the private WordPress options table before changes.</p><table class="widefat"><tr><th>Variant</th><th>Existing product</th><th>Price</th><th>Stock</th></tr>';
            foreach ($catalog['designs'] as $design) { foreach ($design['variants'] as $variant) {
                $p = wc_get_product($variant['source_id']);
                echo '<tr><td>' . esc_html($variant['sku']) . '</td><td>' . esc_html($p->get_name() . ' (#' . $p->get_id() . ')') . '</td><td>' . esc_html($p->get_price()) . '</td><td>' . esc_html($p->get_manage_stock() ? $p->get_stock_quantity() : 'Not tracked') . '</td></tr>';
            } }
            echo '</table><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="loomara_apply_inventory_release">';
            wp_nonce_field('loomara_inventory_release_170');
            submit_button('Apply v1.7.0 inventory migration'); echo '</form>';
        } catch (Throwable $e) { echo '<p>' . esc_html($e->getMessage()) . '</p>'; }
    }
    echo '</div>';
}
function loomara_release_import_image($file, $title) {
    $source = get_template_directory() . '/assets/inventory/' . basename($file);
    $hash = hash_file('sha256', $source);
    $ids = get_posts(['post_type'=>'attachment','post_status'=>'inherit','fields'=>'ids','numberposts'=>1,'meta_key'=>'_loomara_inventory_hash','meta_value'=>$hash]);
    if ($ids) { return (int) $ids[0]; }
    $tmp = wp_tempnam($file);
    if (!$tmp || !copy($source,$tmp)) { throw new Exception('Unable to stage product image.'); }
    $id = media_handle_sideload(['name'=>basename($file),'tmp_name'=>$tmp],0,$title);
    if (is_wp_error($id)) { throw new Exception($id->get_error_message()); }
    update_post_meta($id,'_loomara_inventory_hash',$hash);
    update_post_meta($id,'_wp_attachment_image_alt',$title);
    wp_update_post(['ID'=>$id,'post_title'=>$title,'post_excerpt'=>'','post_content'=>'']);
    return $id;
}
add_action('admin_post_loomara_apply_inventory_release', function () {
    if (!current_user_can('manage_woocommerce')) { wp_die('Access denied.'); }
    check_admin_referer('loomara_inventory_release_170');
    if (get_option('loomara_inventory_release_170')) { wp_die('Already started. Review migration report; do not repeat blindly.'); }
    try {
        $catalog = loomara_release_catalog(); loomara_release_preflight($catalog);
        $snapshot = [];
        foreach ($catalog['designs'] as $design) { foreach ($design['variants'] as $variant) {
            $id = $variant['source_id'];
            $snapshot[$id] = ['data'=>wc_get_product($id)->get_data(),'post'=>get_post($id,ARRAY_A),'meta'=>get_post_meta($id),'terms'=>wp_get_object_terms($id,['product_type','product_cat','product_tag','product_visibility'],['fields'=>'all'])];
        } }
        if (!add_option('loomara_inventory_release_170', ['status'=>'running','started_at'=>gmdate('c')], '', false)) { throw new Exception('Migration locked.'); }
        add_option('loomara_inventory_backup_170',$snapshot,'',false);
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        require_once ABSPATH.'wp-admin/includes/image.php';
        // Import every image before modifying sellable products.
        $galleries = [];
        foreach ($catalog['designs'] as $design) { foreach ($design['variants'] as $variant) {
            $galleries[$variant['sku']] = array_map(function ($file) use ($design,$variant) { return loomara_release_import_image($file,$design['name'].' — '.$variant['colour']); },$variant['images']);
        } }
        $report = [];
        foreach ($catalog['designs'] as $design) {
            // Refresh inventory after media imports; don't use preview or stale stock.
            foreach ($design['variants'] as $variant) {
                clean_post_cache($variant['source_id']);
                $snapshot[$variant['source_id']]['data'] = wc_get_product($variant['source_id'])->get_data();
            }
            update_option('loomara_inventory_backup_170',$snapshot,false);
            $first = $design['variants'][0]; $parent_id = $first['source_id'];
            $attributes = [];
            foreach (['Colour'=>array_column($design['variants'],'colour'),'Size'=>array_column($design['variants'],'size')] as $name=>$values) {
                $a = new WC_Product_Attribute(); $a->set_name($name); $a->set_options(array_values(array_unique($values))); $a->set_visible(true); $a->set_variation(true); $attributes[]=$a;
            }
            $parent = new WC_Product_Variable($parent_id);
            $parent->set_name($design['name']); $parent->set_sku($design['design_id']); $parent->set_attributes($attributes);
            $parent->set_manage_stock(false); $parent->set_stock_quantity(null);
            $parent->set_default_attributes(['colour'=>$first['colour'],'size'=>$first['size']]);
            $original = $snapshot[$parent_id]['data'];
            $description = preg_replace('/\bqueen\b/i','King',$original['description']);
            $description = preg_replace('/<p[^>]*>[^<]*(?:AI[- ]generated|local preview|verified product photograph)[^<]*<\/p>/i','',$description);
            $parent->set_description($description);
            $parent->update_meta_data('_loomara_design_id',$design['design_id']);
            $parent->set_image_id($galleries[$first['sku']][0]); $parent->set_gallery_image_ids(array_slice($galleries[$first['sku']],1));
            $parent->update_meta_data('_loomara_default_gallery_ids',$galleries[$first['sku']]);
            $parent->save();
            $children = [];
            foreach ($design['variants'] as $variant) {
                $data = $snapshot[$variant['source_id']]['data'];
                $child = new WC_Product_Variation();
                $child->set_parent_id($parent_id); $child->set_status('publish'); $child->set_sku($variant['sku']);
                $child->set_attributes(['colour'=>$variant['colour'],'size'=>$variant['size']]);
                foreach (['regular_price','sale_price','date_on_sale_from','date_on_sale_to','manage_stock','stock_quantity','stock_status','backorders','sold_individually','tax_status','tax_class','weight','length','width','height','shipping_class_id'] as $key) {
                    $setter='set_'.$key; if (isset($data[$key]) || array_key_exists($key,$data)) { $child->$setter($data[$key]); }
                }
                $child->set_image_id($galleries[$variant['sku']][0]);
                $child->update_meta_data('_loomara_gallery_image_ids',$galleries[$variant['sku']]);
                $child->update_meta_data('_loomara_size_code',$variant['size_code']);
                $child_id=$child->save();
                $children[]=['sku'=>$variant['sku'],'id'=>$child_id,'size'=>$variant['size'],'stock'=>$child->get_stock_quantity(),'price'=>$child->get_price(),'images'=>count($galleries[$variant['sku']])];
                if ($variant['source_id'] !== $parent_id) {
                    $legacy=wc_get_product($variant['source_id']); $legacy->set_catalog_visibility('hidden');
                    $legacy->update_meta_data('_loomara_canonical_product',$parent_id);
                    $legacy->update_meta_data('_loomara_canonical_colour',$variant['colour']); $legacy->save();
                }
            }
            WC_Product_Variable::sync($parent_id); wc_delete_product_transients($parent_id);
            $report[]=['design'=>$design['design_id'],'parent_id'=>$parent_id,'variants'=>$children];
        }
        update_option('loomara_inventory_release_170',['status'=>'complete','finished_at'=>gmdate('c'),'products'=>$report],false);
    } catch (Throwable $e) {
        update_option('loomara_inventory_release_170',['status'=>'failed','error'=>$e->getMessage(),'time'=>gmdate('c')],false);
    }
    wp_safe_redirect(admin_url('admin.php?page=loomara-inventory-release')); exit;
});
add_action('template_redirect',function () {
    if (!is_singular('product')) { return; }
    $id=get_queried_object_id(); $parent=(int)get_post_meta($id,'_loomara_canonical_product',true);
    if ($parent) { wp_safe_redirect(add_query_arg('attribute_colour',get_post_meta($id,'_loomara_canonical_colour',true),get_permalink($parent)),301); exit; }
});
add_filter('woocommerce_is_purchasable',function ($purchasable,$product) {
    return $product->get_meta('_loomara_canonical_product') ? false : $purchasable;
},20,2);
