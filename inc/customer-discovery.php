<?php
/** Browser-local recent products; public catalogue data only, never customer data. */
defined('ABSPATH') || exit;
function loomara_recent_product_row($id){
    $id=absint($id);if(!$id){return null;}$p=wc_get_product($id);if(!$p){return null;}
    $parent=$p->is_type('variation')?wc_get_product($p->get_parent_id()):$p;
    if(!$parent || $parent->get_status()!=='publish' || !$parent->is_visible() || post_password_required($parent->get_id()) || $p->get_status()!=='publish' || ($p->is_type('variation')&&!$p->variation_is_visible())){return null;}
    $image=$p->get_image_id()?:$parent->get_image_id();
    return ['on_sale'=>$p->is_on_sale(),'id'=>$p->get_id(),'parent'=>$parent->get_id(),'name'=>$p->get_name(),'url'=>$p->get_permalink(),'image'=>$image?wp_get_attachment_image_url($image,'woocommerce_thumbnail'):wc_placeholder_img_src('woocommerce_thumbnail'),'price_html'=>wp_kses($p->get_price_html(),['span'=>['class'=>true,'aria-hidden'=>true],'bdi'=>[],'del'=>['aria-hidden'=>true],'ins'=>['aria-hidden'=>true],'small'=>['class'=>true]]),'price'=>wp_strip_all_tags(html_entity_decode($p->get_price_html(),ENT_QUOTES,get_bloginfo('charset'))),'stock'=>$p->is_in_stock()?'':'Out of stock','rating'=>method_exists($parent,'get_average_rating')?(float)$parent->get_average_rating():0,'rating_count'=>method_exists($parent,'get_rating_count')?(int)$parent->get_rating_count():0,'action'=>$p->is_in_stock()?'Choose options':'Read more'];
}
add_action('rest_api_init',function(){
    if(!function_exists('wc_get_product')){return;}
    register_rest_route('loomara/v1','/recent-products',['methods'=>'GET','permission_callback'=>'__return_true','callback'=>function($request){
        $raw=$request->get_param('ids');
        if(!is_string($raw) || strlen($raw)>120 || !preg_match('/^[0-9]+(?:,[0-9]+){0,7}$/D',$raw)){return new WP_Error('invalid_ids','Choose up to eight product IDs.',['status'=>400]);}
        $rows=[];foreach(array_unique(array_map('absint',explode(',',$raw))) as $id){$row=loomara_recent_product_row($id);if($row){$rows[]=$row;}}
        $response=new WP_REST_Response($rows);$response->header('Cache-Control','no-store');return $response;
    }]);
});
function loomara_recent_products_section(){
    static $shown=false;if($shown){return;}$shown=true;
    echo '<section class="loomara-recent recent-native-cards home-products" data-recent-products hidden aria-labelledby="loomara-recent-title"><header class="section-heading"><div><p class="eyebrow">Continue exploring</p><h2 id="loomara-recent-title">Recently viewed</h2></div><button type="button" data-recent-clear>Clear history</button></header><p class="recent-privacy">Saved on this browser for up to 30 days. Not shared with other customers.</p><div class="home-product-grid"><div class="woocommerce"><ul class="recent-product-grid products columns-4"></ul></div></div><p role="status" data-recent-status></p></section>';
}
add_action('woocommerce_after_single_product_summary','loomara_recent_products_section',25);
add_action('wp_enqueue_scripts',function(){
    if(!class_exists('WooCommerce') || !(is_front_page()||is_shop()||is_product_taxonomy()||is_product())){return;}
    $p=is_product()?wc_get_product(get_queried_object_id()):false;
    wp_enqueue_style('loomara-customer-journey',get_template_directory_uri().'/assets/customer-journey.css',[],filemtime(__DIR__.'/../assets/customer-journey.css'));
    wp_enqueue_script('loomara-recent-products',get_template_directory_uri().'/assets/recent-products.js',['jquery'],filemtime(__DIR__.'/../assets/recent-products.js'),true);
    wp_localize_script('loomara-recent-products','loomaraRecent',['endpoint'=>rest_url('loomara/v1/recent-products'),'product'=>$p?$p->get_id():0]);
});
