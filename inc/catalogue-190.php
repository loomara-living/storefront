<?php
/** Explicit fixed-identity weight/folder update. Never run on a storefront GET. */
defined('ABSPATH') || exit;
function loomara_catalogue_190_plan() {
    $plan=loomara_catalogue_186_plan();$rows=[];
    foreach($plan['designs'] as $d){$kg=['bedsheets'=>0.5,'comforters'=>1,'combo'=>1.5][$d['category']]??null;if($kg===null)continue;
        $rows[]=['id'=>$d['id'],'sku'=>$d['sku'],'kg'=>$kg,'folder'=>null];
        foreach($d['variants'] as $v){$parts=explode('/',$v['folder']);$prefix=implode('-',array_slice(explode('-',$v['sku']),0,3));array_splice($parts,1,0,[$prefix]);$rows[]=['id'=>$v['id'],'sku'=>$v['sku'],'kg'=>$kg,'folder'=>implode('/',$parts)];}
    }
    // Disposable local rehearsal has different database IDs; production never
    // accepts client-supplied identities and always uses the fixed production plan.
    if(loomara_is_local_preview()){foreach($rows as &$row){$row['id']=wc_get_product_id_by_sku($row['sku']);}unset($row);}
    return $rows;
}
function loomara_catalogue_190_preflight() {
    $rows=loomara_catalogue_190_plan();$seen=[];
    foreach($rows as &$r){$p=wc_get_product($r['id']);if(!$p||$p->get_sku()!==$r['sku']||isset($seen[$r['id']]))throw new Exception('Identity mismatch for '.$r['sku']);$seen[$r['id']]=true;
        $r['old_weight']=$p->get_weight('edit');$r['old_folder']=$p->get_meta('_loomara_variant_folder');$protected=loomara_catalogue_186_protected($p);unset($protected['weight']);$r['protected']=$protected;
    }unset($r);return $rows;
}
function loomara_catalogue_190_apply() {
    if(!current_user_can('manage_woocommerce'))throw new Exception('WooCommerce management required.');
    if(!in_array(wp_parse_url(home_url(),PHP_URL_HOST),['loomaraliving.com','127.0.0.1','localhost'],true))throw new Exception('Wrong website.');
    if(get_option('loomara_catalogue_190_complete'))return;
    if(!add_option('loomara_catalogue_190_lock',time(),' ',false))throw new Exception('Update already in progress.');
    try{$rows=loomara_catalogue_190_preflight();
        if(!get_option('loomara_catalogue_190_snapshot')&&!add_option('loomara_catalogue_190_snapshot',['created'=>time(),'rows'=>$rows],' ',false))throw new Exception('Could not save private snapshot.');
        foreach($rows as $r){$p=wc_get_product($r['id']);$p->set_weight((string)wc_get_weight($r['kg'],get_option('woocommerce_weight_unit','kg'),'kg'));if($r['folder'])$p->update_meta_data('_loomara_variant_folder',$r['folder']);$p->save();wc_delete_product_transients($p->is_type('variation')?$p->get_parent_id():$p->get_id());}
        foreach($rows as $r){$p=wc_get_product($r['id']);$now=loomara_catalogue_186_protected($p);unset($now['weight']);if($now!==$r['protected'])throw new Exception('Protected product data changed for #'.$r['id'].'. Inspect snapshot; never overwrite newer stock/orders.');}
        update_option('loomara_catalogue_190_complete',['time'=>time(),'records'=>count($rows)],false);
    }finally{delete_option('loomara_catalogue_190_lock');}
}
add_action('admin_menu',function(){add_submenu_page('woocommerce','Loomara v1.9 weights','Loomara v1.9 weights','manage_woocommerce','loomara-catalogue-190',function(){
    if(!current_user_can('manage_woocommerce'))return;
    echo '<div class="wrap"><h1>Loomara v1.9 shipping weights</h1><p>Fixed existing identities only. Bedsheets 500 g, Comforters 1 kg, Combo 1.5 kg. Existing cushion-cover pair, Sample and legacy Rose are excluded. No confirmed six-piece cover packs are in this plan.</p>';
    if(get_option('loomara_catalogue_190_complete')){echo '<p>Update completed. Prices, stock, images, sizes and URLs preserved.</p></div>';return;}
    try{$rows=loomara_catalogue_190_preflight();echo '<h2>Preflight passed: '.count($rows).' records</h2><p>A private snapshot is saved before updates. Variant folder metadata gains its subcategory directory.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_catalogue_190">';wp_nonce_field('loomara_catalogue_190');submit_button('Apply approved shipping weights');echo '</form>';}catch(Throwable $e){echo '<p>'.esc_html($e->getMessage()).'</p>';}echo '</div>';
});});
add_action('admin_post_loomara_catalogue_190',function(){if(!current_user_can('manage_woocommerce'))wp_die('Not allowed.','',['response'=>403]);check_admin_referer('loomara_catalogue_190');try{loomara_catalogue_190_apply();}catch(Throwable $e){wp_die(esc_html($e->getMessage()));}wp_safe_redirect(admin_url('admin.php?page=loomara-catalogue-190'));exit;});
