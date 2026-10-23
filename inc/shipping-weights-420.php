<?php
/** Explicit owner-approved weight update. Never run on public page loads. */
defined('ABSPATH') || exit;
function loomara_weight_420_target($p, $parent) {
    if ($p->is_virtual() || !preg_match('/^LM-(BD|CF|CB|CC)-/', $p->get_sku(), $m)) { return null; }
    if ($m[1] !== 'CC') { return ['kg'=>$m[1]==='CB'?2:1.2,'pack'=>null]; }
    $pack=(int)($p->get_meta('_loomara_pack_quantity')?:$parent->get_meta('_loomara_pack_quantity'));
    $text=wp_strip_all_tags($parent->get_name().' '.$parent->get_description().' '.$p->get_attribute('pack-size').' '.$p->get_attribute('pa_pack-size'));
    if (!$pack && preg_match('/(?:set|pack)\s*(?:of\s*)?4\b/i',$text)) { $pack=4; }
    if (!$pack && preg_match('/\bpair\b|(?:set|pack)\s*(?:of\s*)?2\b/i',$text)) { $pack=2; }
    return isset([2=>0.3,4=>0.5][$pack]) ? ['kg'=>[2=>0.3,4=>0.5][$pack],'pack'=>$pack] : null;
}
function loomara_weight_420_plan() {
    $ids=wc_get_products(['limit'=>-1,'return'=>'ids','status'=>['publish','private'],'type'=>['simple','variable']]);
    $all=$ids;
    foreach($ids as $id) { $p=wc_get_product($id); if($p && $p->is_type('variable')) { $all=array_merge($all,$p->get_children()); } }
    $rows=[];
    foreach(array_unique($all) as $id) {
        $p=wc_get_product($id); if(!$p)continue;
        $parent=$p->is_type('variation')?wc_get_product($p->get_parent_id()):$p;
        if(!$parent)continue;
        $target=loomara_weight_420_target($p,$parent); if(!$target)continue;
        $protected=loomara_catalogue_186_protected($p); unset($protected['weight']);
        $rows[]=['id'=>$id,'sku'=>$p->get_sku(),'name'=>$p->get_name(),'target'=>$target,'old_weight'=>$p->get_weight('edit'),'old_pack'=>$p->get_meta('_loomara_pack_quantity'),'protected'=>$protected];
    }
    return $rows;
}
function loomara_weight_420_apply() {
    if(!current_user_can('manage_options'))throw new Exception('Administrator required.');
    if(wp_parse_url(home_url(),PHP_URL_HOST)!=='loomaraliving.com')throw new Exception('Wrong website.');
    if(get_option('loomara_weight_420_complete'))return;
    if(!add_option('loomara_weight_420_lock',time(),'','no'))throw new Exception('Update already running.');
    try {
        if(get_option('loomara_weight_420_snapshot'))throw new Exception('Existing snapshot requires review; no blind replay.');
        $rows=loomara_weight_420_plan();
        if(!$rows || !add_option('loomara_weight_420_snapshot',['time'=>time(),'unit'=>get_option('woocommerce_weight_unit','kg'),'rows'=>$rows],'','no'))throw new Exception('Could not save private snapshot.');
        foreach($rows as $r) {
            $p=wc_get_product($r['id']);
            if(!$p)throw new Exception('Product no longer exists; stop and review.');
            $now=loomara_catalogue_186_protected($p);unset($now['weight']);
            if($p->get_sku()!==$r['sku'] || $now!==$r['protected'])throw new Exception('Product changed; stop and review.');
            $p->set_weight((string)wc_get_weight($r['target']['kg'],get_option('woocommerce_weight_unit','kg'),'kg'));
            if($r['target']['pack'])$p->update_meta_data('_loomara_pack_quantity',$r['target']['pack']);
            $p->save();wc_delete_product_transients($p->is_type('variation')?$p->get_parent_id():$p->get_id());
        }
        foreach($rows as $r) { $p=wc_get_product($r['id']);$now=loomara_catalogue_186_protected($p);unset($now['weight']);if($now!==$r['protected'])throw new Exception('Protected product data changed; inspect snapshot, never overwrite stock.'); }
        update_option('loomara_weight_420_complete',['time'=>time(),'records'=>count($rows)],false);
    } finally { delete_option('loomara_weight_420_lock'); }
}
add_action('admin_menu',function(){add_submenu_page('woocommerce','Approved shipping weights','Approved shipping weights','manage_options','loomara-weights-420',function(){
    if(!current_user_can('manage_options'))return;
    echo '<div class="wrap"><h1>Approved shipping weights — v4.2.0</h1><p>Bedsheets and comforters 1.2 kg; combos 2 kg; confirmed cushion-cover set of 2: 0.3 kg, set of 4: 0.5 kg. Sample and unknown SKUs/packs are excluded. No prices, stock or orders change. A private snapshot is saved first.</p>';
    $done=get_option('loomara_weight_420_complete');if($done){echo '<p>Completed: '.absint($done['records']).' records.</p></div>';return;}
    $rows=loomara_weight_420_plan();echo '<h2>Preflight: '.count($rows).' records</h2><table class="widefat"><tr><th>SKU</th><th>Current weight (store units)</th><th>New weight (kg)</th></tr>';
    foreach($rows as $r)echo '<tr><td>'.esc_html($r['sku']).'</td><td>'.esc_html($r['old_weight']).'</td><td>'.esc_html($r['target']['kg']).'</td></tr>';
    echo '</table><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_weights_420">';wp_nonce_field('loomara_weights_420');submit_button('Apply approved weights');echo '</form></div>';
});});
add_action('admin_post_loomara_weights_420',function(){check_admin_referer('loomara_weights_420');try{loomara_weight_420_apply();}catch(Throwable $e){wp_die(esc_html($e->getMessage()));}wp_safe_redirect(admin_url('admin.php?page=loomara-weights-420'));exit;});
