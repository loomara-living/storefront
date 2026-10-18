<?php
/** Explicit metadata-only migration. No frontend writes or automatic execution. */
defined('ABSPATH') || exit;
function loomara_catalogue_186_plan() { return require __DIR__.'/catalogue-186-plan.php'; }
function loomara_catalogue_186_meta_keys() {
    return ['_loomara_design_id','_loomara_former_design_ids','_loomara_former_skus','_loomara_subcategory_code','_loomara_variant_folder'];
}
function loomara_catalogue_186_protected($p) {
    $data=$p->get_data();$keys=['name','slug','status','description','short_description','regular_price','sale_price','price','date_on_sale_from','date_on_sale_to','manage_stock','stock_quantity','stock_status','backorders','sold_individually','image_id','gallery_image_ids','default_attributes','parent_id','tax_status','tax_class','shipping_class_id','weight','length','width','height'];
    $result=[];foreach($keys as $key){$value=$data[$key]??null;$result[$key]=$value instanceof WC_DateTime?$value->getTimestamp():$value;}
    return $result;
}
function loomara_catalogue_186_snapshot_product($p) {
    $meta=[];foreach(loomara_catalogue_186_meta_keys() as $key){$meta[$key]=['exists'=>metadata_exists('post',$p->get_id(),$key),'value'=>$p->get_meta($key)];}
    return ['id'=>$p->get_id(),'sku'=>$p->get_sku(),'categories'=>$p->get_category_ids(),'attributes'=>array_map(function($a){return $a instanceof WC_Product_Attribute?$a->get_data():$a;},$p->get_attributes()),'meta'=>$meta,'protected'=>loomara_catalogue_186_protected($p)];
}
function loomara_catalogue_186_preflight($plan) {
    $rows=[];$seen=[];
    foreach($plan['designs'] as $design){
        $parent=wc_get_product($design['id']);
        if(!$parent||!$parent->is_type('variable')||!in_array($parent->get_sku(),[$design['old_sku'],$design['sku']],true)){throw new Exception('Parent identity/type mismatch for '.$design['sku']);}
        $category=get_term_by('slug',$design['category'],'product_cat');
        if(!$category||$category->parent){throw new Exception('Missing root category '.$design['category']);}
        $styleSlug=$category->slug.'-'.strtolower($design['subcategory']['code']);$style=get_term_by('slug',$styleSlug,'product_cat');
        if($style&&(int)$style->parent!==(int)$category->term_id){throw new Exception('Subcategory parent mismatch: '.$styleSlug);}
        $expected=array_map('absint',array_column($design['variants'],'id'));
        $extra=array_diff($parent->get_children(),$expected);
        if($extra){throw new Exception('Additional variations need review for '.$design['sku']);}
        $items=array_merge([['id'=>$design['id'],'old_sku'=>$design['old_sku'],'sku'=>$design['sku']]],$design['variants']);
        foreach($items as $item){
            if(isset($seen[$item['id']])){throw new Exception('Duplicate identity in migration plan.');}$seen[$item['id']]=true;
            $p=wc_get_product($item['id']);
            if(!$p||!in_array($p->get_sku(),[$item['old_sku'],$item['sku']],true)){throw new Exception('SKU identity mismatch for '.$item['sku']);}
            $collision=wc_get_product_id_by_sku($item['sku']);if($collision&&(int)$collision!==(int)$item['id']){throw new Exception('SKU collision for '.$item['sku']);}
            if($item['id']!==$design['id']){
                if(!$p->is_type('variation')||(int)$p->get_parent_id()!==(int)$design['id']){throw new Exception('Variation parent mismatch for '.$item['sku']);}
                $colour=$p->get_attribute('colour')?:($p->get_attribute('pa_colour')?:$p->get_attribute('color'));
                $size=$p->get_attribute('size')?:$p->get_attribute('pa_size');
                if(sanitize_title($colour)!==sanitize_title($item['colour'])||sanitize_title($size)!==sanitize_title($item['size'])){throw new Exception('Colour/size mismatch for '.$item['sku'].' ('.$colour.' / '.$size.').');}
            }
            $rows[]=loomara_catalogue_186_snapshot_product($p);
        }
    }
    return $rows;
}
function loomara_catalogue_186_apply($plan) {
    if(!current_user_can('manage_woocommerce')){throw new Exception('WooCommerce management permission required.');}
    $host=wp_parse_url(home_url(),PHP_URL_HOST);
    if($host!==$plan['domain']&&!in_array($host,['127.0.0.1','localhost'],true)){throw new Exception('Wrong website; migration refused.');}
    if(!add_option('loomara_catalogue_186_lock',time(),' ',false)){throw new Exception('Migration already in progress. Do not retry until it has finished.');}
    try{
        $before=loomara_catalogue_186_preflight($plan);
        if(!get_option('loomara_catalogue_186_snapshot')){
            if(!add_option('loomara_catalogue_186_snapshot',['created'=>time(),'domain'=>home_url(),'products'=>$before],' ',false)){throw new Exception('Unable to save private product snapshot.');}
        }
        $report=[];
        foreach($plan['designs'] as $design){
            $parent=wc_get_product($design['id']);$category=get_term_by('slug',$design['category'],'product_cat');
            $slug=$category->slug.'-'.strtolower($design['subcategory']['code']);$style=get_term_by('slug',$slug,'product_cat');
            if(!$style){$insert=wp_insert_term($design['subcategory']['name'],'product_cat',['slug'=>$slug,'parent'=>$category->term_id]);if(is_wp_error($insert)){throw new Exception($insert->get_error_message());}$style=get_term($insert['term_id'],'product_cat');}
            $parent->set_sku($design['sku']);
            $parent->set_category_ids(array_values(array_unique(array_merge($parent->get_category_ids(),[(int)$category->term_id,(int)$style->term_id]))));
            $attrs=$parent->get_attributes();
            if(!$parent->get_attribute('material')&&!$parent->get_attribute('pa_material')){$material=new WC_Product_Attribute();$material->set_name('Material');$material->set_options([$design['material']]);$material->set_visible(true);$material->set_variation(false);$attrs['material']=$material;$parent->set_attributes($attrs);}
            $aliases=array_values(array_unique(array_merge((array)$parent->get_meta('_loomara_former_design_ids'),[$design['old_sku']])));
            $parent->update_meta_data('_loomara_former_design_ids',array_values(array_filter($aliases)));
            $parent->update_meta_data('_loomara_design_id',$design['sku']);$parent->update_meta_data('_loomara_subcategory_code',$design['subcategory']['code']);$parent->save();
            foreach($design['variants'] as $variant){
                $p=wc_get_product($variant['id']);$p->set_sku($variant['sku']);
                $aliases=array_merge((array)$p->get_meta('_loomara_former_skus'),$variant['aliases']??[],[$variant['old_sku']]);
                $p->update_meta_data('_loomara_former_skus',array_values(array_unique(array_filter($aliases))));$p->update_meta_data('_loomara_variant_folder',$variant['folder']);$p->update_meta_data('_loomara_subcategory_code',$design['subcategory']['code']);$p->save();
                $report[]=['id'=>$p->get_id(),'sku'=>$p->get_sku()];
            }
            wc_delete_product_transients($parent->get_id());
        }
        foreach($before as $old){$current=wc_get_product($old['id']);if(loomara_catalogue_186_protected($current)!==$old['protected']){throw new Exception('Protected fields changed during migration for #'.$old['id'].'. Review snapshot; never restore stock over newer orders.');}}
        update_option('loomara_catalogue_186_status',['completed'=>time(),'parents'=>count($plan['designs']),'variants'=>$report],false);
        return $report;
    }finally{delete_option('loomara_catalogue_186_lock');}
}
add_action('admin_menu',function(){add_submenu_page('woocommerce','Loomara catalogue v1.8.6','Loomara catalogue update','manage_woocommerce','loomara-catalogue-186','loomara_catalogue_186_page');});
function loomara_catalogue_186_page(){
    if(!current_user_can('manage_woocommerce'))return;
    echo '<div class="wrap"><h1>Loomara catalogue update v1.8.6</h1><p>Update existing SKUs, visual-style subcategories and missing material attributes only. Preserve live prices, stock, images, URLs, orders, customers and unrelated products. A private product snapshot is saved before any update.</p>';
    if(get_option('loomara_catalogue_186_status')){echo '<div class="notice notice-success"><p>Migration completed. Nine designs and twenty-one variations retain their existing WooCommerce identities.</p></div></div>';return;}
    try{$rows=loomara_catalogue_186_preflight(loomara_catalogue_186_plan());echo '<h2>Preflight passed</h2><p>'.count($rows).' existing product/variation identities checked. Nothing has been changed yet.</p><table class="widefat"><thead><tr><th>ID</th><th>Current SKU</th><th>Live price</th><th>Live stock</th></tr></thead><tbody>';foreach($rows as $row){echo '<tr><td>'.absint($row['id']).'</td><td>'.esc_html($row['sku']).'</td><td>'.esc_html($row['protected']['price']).'</td><td>'.esc_html($row['protected']['stock_quantity']??'Not individually managed').'</td></tr>';}echo '</tbody></table><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_catalogue_186">';wp_nonce_field('loomara_catalogue_186');submit_button('Apply approved catalogue update');echo '</form>';}catch(Throwable $e){echo '<div class="notice notice-error"><p>'.esc_html($e->getMessage()).'</p></div>';}
    echo '</div>';
}
add_action('admin_post_loomara_catalogue_186',function(){
    if(!current_user_can('manage_woocommerce'))wp_die('Permission denied.','',['response'=>403]);check_admin_referer('loomara_catalogue_186');
    try{if(!get_option('loomara_catalogue_186_status'))loomara_catalogue_186_apply(loomara_catalogue_186_plan());wp_safe_redirect(admin_url('admin.php?page=loomara-catalogue-186'));exit;}catch(Throwable $e){wp_die(esc_html($e->getMessage()),'Catalogue migration paused',['response'=>400,'back_link'=>true]);}
});
