<?php
/** Explicit package-only update, separate from textile/bed dimensions. */
defined('ABSPATH') || exit;
function loomara_package_197_values() {
    $unit=get_option('woocommerce_dimension_unit','cm');$out=[];
    foreach(['length'=>30,'width'=>20,'height'=>5] as $key=>$cm){$out[$key]=(string)wc_get_dimension($cm,$unit,'cm');}return $out;
}
function loomara_package_197_plan() {
    $base=['limit'=>-1,'return'=>'ids','status'=>['publish','private','draft','pending','future']];
    $ids=array_unique(array_merge(wc_get_products($base),wc_get_products($base+['type'=>'variation'])));sort($ids);$rows=[];
    foreach($ids as $id){$p=wc_get_product($id);if(!$p||$p->is_virtual()||$p->is_type('grouped')||$p->is_type('external'))continue;
        $protected=loomara_catalogue_186_protected($p);$old=array_intersect_key($protected,array_flip(['length','width','height']));unset($protected['length'],$protected['width'],$protected['height']);
        $rows[]=['id'=>$p->get_id(),'sku'=>$p->get_sku(),'type'=>$p->get_type(),'old'=>$old,'protected'=>$protected];
    }return $rows;
}
function loomara_package_197_apply() {
    if(!current_user_can('manage_woocommerce'))throw new Exception('WooCommerce management required.');
    if(!in_array(wp_parse_url(home_url(),PHP_URL_HOST),['loomaraliving.com','127.0.0.1','localhost'],true))throw new Exception('Wrong website.');
    if(get_option('loomara_package_197_complete'))return;
    if(!add_option('loomara_package_197_lock',time(),'','no'))throw new Exception('Package update already running.');
    try{$rows=loomara_package_197_plan();$values=loomara_package_197_values();
        if(get_option('loomara_package_197_snapshot'))throw new Exception('Snapshot already exists. Review the previous attempt before retrying.');
        if(!add_option('loomara_package_197_snapshot',['time'=>time(),'unit'=>get_option('woocommerce_dimension_unit','cm'),'values'=>$values,'rows'=>$rows],'','no'))throw new Exception('Could not save private snapshot.');
        foreach($rows as $r){$p=wc_get_product($r['id']);if(!$p||$p->get_sku()!==$r['sku']||$p->get_type()!==$r['type'])throw new Exception('Product identity changed. Stop and review snapshot.');
            foreach($values as $key=>$value){$setter='set_'.$key;$p->$setter($value);}$p->save();
            $now=loomara_catalogue_186_protected($p);unset($now['length'],$now['width'],$now['height']);if($now!==$r['protected'])throw new Exception('Protected product fields changed. Review snapshot; never restore stock automatically.');
            wc_delete_product_transients($p->is_type('variation')?$p->get_parent_id():$p->get_id());
        }update_option('loomara_package_197_complete',['time'=>time(),'count'=>count($rows)],false);
    }finally{delete_option('loomara_package_197_lock');}
}
add_action('woocommerce_before_product_object_save',function($p){
    if(!$p->get_id()&&!$p->is_virtual()&&$p->get_length('edit')===''&&$p->get_width('edit')===''&&$p->get_height('edit')===''){
        foreach(loomara_package_197_values() as $key=>$value){$setter='set_'.$key;$p->$setter($value);}
    }
});
add_filter('woocommerce_display_product_attributes',function($attributes){
    if(isset($attributes['dimensions'])){$attributes['dimensions']['label']='Packed parcel dimensions';}
    return $attributes;
});
add_action('admin_menu',function(){add_submenu_page('woocommerce','Package dimensions','Package dimensions','manage_woocommerce','loomara-package-197',function(){
    if(!current_user_can('manage_woocommerce'))return;
    echo '<div class="wrap"><h1>Parcel package dimensions</h1><p>Set all existing physical products and variations to 30 × 20 × 5 cm, converting to the configured WooCommerce unit. These are parcel dimensions, not bedding dimensions. Prices, stock, weights, images and customer/order records stay unchanged. A private snapshot is saved first.</p>';
    $done=get_option('loomara_package_197_complete');if($done){echo '<p>Completed: '.absint($done['count']).' product/variation records.</p></div>';return;}
    if(get_option('loomara_package_197_snapshot')){echo '<p>A previous update needs review before retrying.</p></div>';return;}
    echo '<p>Preflight: '.count(loomara_package_197_plan()).' physical product/variation records. Current dimension unit: '.esc_html(get_option('woocommerce_dimension_unit','cm')).'.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_package_197">';wp_nonce_field('loomara_package_197');echo '<button type="submit" class="button button-primary">Apply approved package dimensions</button></form></div>';
});});
add_action('admin_post_loomara_package_197',function(){check_admin_referer('loomara_package_197');try{loomara_package_197_apply();}catch(Throwable $e){wp_die(esc_html($e->getMessage()));}wp_safe_redirect(admin_url('admin.php?page=loomara-package-197'));exit;});
