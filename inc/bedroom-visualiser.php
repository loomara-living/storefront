<?php
/** Public catalogue-backed colour study. No AI upload, cart mutation or provider call. */
defined('ABSPATH') || exit;
function loomara_bv_page_url(){ $page=get_page_by_path('style-your-bedroom',OBJECT,'page');return $page&&$page->post_status==='publish'&&has_shortcode($page->post_content,'loomara_bedroom_visualiser')?get_permalink($page):''; }
function loomara_bv_palette($label){
    $label=strtolower(wp_strip_all_tags($label));
    $colours=['powder blue'=>'#b7cbd9','deep teal'=>'#35676d','olive grey'=>'#999b85','ivory sand'=>'#e8deca','dusty rose'=>'#c99491','terracotta'=>'#b66f50','burgundy'=>'#743d4d','navy'=>'#253c58','sage'=>'#a6b19a','taupe'=>'#b5a495','beige'=>'#d5c4aa','ivory'=>'#f1e9d8','cream'=>'#eee3cc','grey'=>'#a5aaac','gray'=>'#a5aaac','pink'=>'#dab2b5','blue'=>'#859db6','teal'=>'#56898b','oat'=>'#d6cbb6','white'=>'#f4f1e9','gold'=>'#bd995d'];
    $found=[];foreach($colours as $name=>$hex){if(str_contains($label,$name)){$found[]=$hex;}}
    return [$found[0]??'#d9d0c2',$found[1]??($found[0]??'#d9d0c2'),(bool)$found];
}
function loomara_bv_product($p,$category){
    if(!$p||!in_array($p->get_type(),['simple','variation'],true)||$p->get_status()!=='publish'){return false;}
    $parent=$p->is_type('variation')?wc_get_product($p->get_parent_id()):$p;
    if(!$parent||$parent->get_status()!=='publish'||!$parent->is_visible()||post_password_required($parent->get_id())||!has_term($category,'product_cat',$parent->get_id())){return false;}
    if($p->is_type('variation')&&(!$p->variation_is_visible()||in_array('',$p->get_variation_attributes(),true))){return false;}
    $colour=$p->get_attribute('pa_colour')?:$p->get_attribute('colour');$size=$p->get_attribute('pa_size')?:$p->get_attribute('size');
    $image=$p->get_image_id()?:$parent->get_image_id();
    return ['id'=>$p->get_id(),'name'=>$p->get_name(),'colour'=>$colour,'size'=>$size,'palette'=>loomara_bv_palette($colour),'url'=>$p->get_permalink(),'image'=>$image?wp_get_attachment_image_url($image,'woocommerce_thumbnail'):wc_placeholder_img_src('woocommerce_thumbnail'),'price'=>html_entity_decode(wp_strip_all_tags($p->get_price_html()),ENT_QUOTES,'UTF-8'),'stock'=>$p->is_in_stock()?'Available':'Out of stock'];
}
function loomara_bv_catalogue(){
    $out=['sheet'=>[],'comforter'=>[],'pillow'=>[]];if(!function_exists('wc_get_products')){return $out;}
    foreach(['sheet'=>'bedsheets','comforter'=>'comforters','pillow'=>'cushion-covers'] as $layer=>$slug){foreach(wc_get_products(['status'=>'publish','category'=>[$slug],'limit'=>40,'orderby'=>'title','order'=>'ASC']) as $parent){$ids=$parent->is_type('variable')?array_slice($parent->get_children(),0,60):[$parent->get_id()];foreach($ids as $id){$row=loomara_bv_product(wc_get_product($id),$slug);if($row){$out[$layer][]=$row;}}}}
    return $out;
}
function loomara_bv_scene(){return <<<'SVG'
<svg class="bv-scene" viewBox="0 0 900 660" role="img" aria-labelledby="bv-scene-title bv-scene-desc">
<title id="bv-scene-title">Your bedroom colour study</title><desc id="bv-scene-desc">An illustrated bedroom. The bedsheet, comforter and accent cushions change colour with your selections. Fabric patterns, dimensions and exact colour are not reproduced.</desc>
<defs><linearGradient id="bv-wall" x2="0" y2="1"><stop stop-color="var(--bv-wall,#f2eee5)"/><stop offset="1" stop-color="#e5ddce"/></linearGradient><linearGradient id="bv-shade" x2="1" y2="1"><stop stop-color="#fff" stop-opacity=".25"/><stop offset=".55" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#152b3b" stop-opacity=".17"/></linearGradient><pattern id="bv-floor" width="140" height="44" patternUnits="userSpaceOnUse"><rect width="140" height="44" fill="#c5ae8d"/><path d="M0 43H140M139 0V44" stroke="#b69b77" stroke-width="1"/></pattern></defs>
<rect width="900" height="480" fill="url(#bv-wall)"/><path d="M0 480H900V660H0Z" fill="url(#bv-floor)"/><path d="M0 480H900" stroke="#d4c8b5" stroke-width="12"/>
<g aria-hidden="true"><rect x="54" y="65" width="172" height="256" rx="3" fill="#fffaf0"/><rect x="65" y="76" width="150" height="234" fill="#d9e3df"/><path d="M140 76V310M65 194H215" stroke="#fffaf0" stroke-width="8"/><path d="M55 315L268 315L600 660H120Z" fill="#fff9dd" opacity=".19"/>
<rect x="685" y="90" width="100" height="125" rx="3" fill="#c5b598"/><rect x="696" y="101" width="78" height="103" fill="#f4eee2"/><path d="M713 182Q748 112 762 171" fill="none" stroke="#89947b" stroke-width="6"/>
<rect x="285" y="227" width="365" height="215" rx="85" fill="#c5b69d"/><path d="M309 305H626" stroke="#b7a58a" opacity=".5"/>
<ellipse cx="467" cy="580" rx="330" ry="47" fill="#574b39" opacity=".13"/><path d="M236 397L672 397L782 587L146 587Z" fill="#786853"/><path d="M159 584V617M765 584V617" stroke="#66533c" stroke-width="12"/>
<path d="M235 365L675 365L765 533L160 533Z" fill="#fff9ec"/>
<g class="bv-sheet"><path d="M235 365L675 365L765 533L160 533Z" fill="var(--bv-sheet,#d9d0c2)"/><path d="M160 533L765 533L765 583Q470 607 160 583Z" fill="var(--bv-sheet,#d9d0c2)"/><path d="M235 365L675 365L765 533L160 533Z" fill="url(#bv-shade)"/><path d="M179 539Q440 567 745 540" fill="none" stroke="#fff" stroke-opacity=".25" stroke-width="3"/></g>
<g fill="#f6f0e4"><path d="M284 316Q333 299 424 318L428 369Q358 383 274 368Z"/><path d="M469 318Q548 302 628 320L638 371Q552 387 471 370Z"/></g>
<g data-bv-layer="comforter" hidden><path d="M208 428Q438 410 708 429L760 533L760 575Q491 603 163 576L163 532Z" fill="var(--bv-comforter,#a6b19a)"/><path d="M208 428Q438 410 708 429L760 533L760 575Q491 603 163 576L163 532Z" fill="url(#bv-shade)"/><path d="M208 428Q437 448 708 429" fill="none" stroke="var(--bv-comforter-secondary,#a6b19a)" stroke-width="17"/><path d="M235 455Q405 481 722 455M206 493Q483 520 740 495" fill="none" stroke="#fff" stroke-opacity=".17" stroke-width="3"/></g>
<g data-bv-layer="pillow" hidden><path d="M347 341Q377 327 414 342L426 394Q384 407 337 389Z" fill="var(--bv-pillow,#b66f50)"/><path d="M488 342Q525 326 564 342L577 392Q536 407 488 393Z" fill="var(--bv-pillow-secondary,#b66f50)"/><path d="M347 341Q377 327 414 342L426 394Q384 407 337 389Z" fill="url(#bv-shade)"/></g>
<path d="M38 455H173V468H38ZM48 468H162V532H48Z" fill="#9c7d58"/><rect x="100" y="370" width="7" height="85" fill="#7d704f"/><path d="M71 371L87 317H124L140 371Z" fill="#e8dbc0"/>
<path d="M799 479H867V492H799ZM809 492V548M857 492V548" fill="#9c7d58"/><path d="M818 477L813 431H849L844 477Z" fill="#c6b79d"/><path d="M829 432Q794 385 816 370M833 432Q865 399 855 383" fill="none" stroke="#768269" stroke-width="7"/>
</g></svg>
SVG;
}
function loomara_bedroom_visualiser(){
    static $shown=false;if($shown){return '';}$shown=true;$rows=loomara_bv_catalogue();ob_start();
    echo '<section class="loomara-bv" data-bedroom-visualiser><header><p class="eyebrow">Find your softer combination</p><h2>Style your bedroom</h2><p>Try different layers together, then explore the exact products you love.</p></header><div class="bv-layout"><figure class="bv-preview">'.loomara_bv_scene().'<figcaption><strong>Illustrative colour preview</strong> — fabric patterns are not simulated. Colour, scale and fit may differ. The accent-cushion shapes are illustrative; they do not show a pillow or insert being supplied.</figcaption><div class="bv-scene-controls"><button type="button" data-bv-light aria-pressed="false">Warm room tone</button><button type="button" data-bv-reset>Reset preview</button></div></figure><div class="bv-selections">';
    foreach(['sheet'=>'Bedsheet','comforter'=>'Comforter','pillow'=>'Cushion covers / pillow accents'] as $layer=>$label){echo '<section class="bv-choice"><label for="bv-'.$layer.'">'.esc_html($label).'</label><select id="bv-'.$layer.'" data-bv-select="'.esc_attr($layer).'"><option value="">'.($layer==='sheet'?'Neutral base':'No added layer').'</option>';foreach($rows[$layer] as $row){echo '<option value="'.absint($row['id']).'">'.esc_html($row['name'].' · '.$row['price'].($row['stock']==='Out of stock'?' · Out of stock':'')).'</option>';}echo '</select><div data-bv-card="'.esc_attr($layer).'" hidden></div></section>';}
    echo '<p class="bv-help">Choose each product’s actual size on its product page. This preview does not check size compatibility or add anything to your cart.</p><p class="bv-live" role="status" aria-live="polite" data-bv-status></p></div></div><noscript><p>The interactive preview needs JavaScript. <a href="'.esc_url(home_url('/shop/')).'">Explore products in the Shop</a>.</p></noscript><script type="application/json" data-bv-catalogue>'.wp_json_encode($rows,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script></section>';
    return ob_get_clean();
}
add_shortcode('loomara_bedroom_visualiser','loomara_bedroom_visualiser');
add_action('wp_enqueue_scripts',function(){if(!is_page()){return;}$p=get_post();if(!$p||!has_shortcode($p->post_content,'loomara_bedroom_visualiser')){return;}foreach(['css','js'] as $ext){$path='/assets/bedroom-visualiser.'.$ext;if($ext==='css'){wp_enqueue_style('loomara-bedroom-visualiser',get_template_directory_uri().$path,[],filemtime(get_template_directory().$path));}else{wp_enqueue_script('loomara-bedroom-visualiser',get_template_directory_uri().$path,[],filemtime(get_template_directory().$path),true);}}});
