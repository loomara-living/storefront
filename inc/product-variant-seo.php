<?php
/** Extend the existing WooCommerce graph with published, purchasable variants. */
defined('ABSPATH') || exit;

function loomara_variant_seo_schema($data,$parent){
    if(!$parent instanceof WC_Product || !$parent->is_type('variable') || !is_array($data) || ($data['@type']??'')!=='Product' || !empty($data['hasVariant'])){return $data;}
    $children=$parent->get_visible_children();
    // Avoid truncating a large catalogue or serialising unbounded product records.
    if(!$children || count($children)>100){return $data;}
    $variants=[];$varies=[];
    foreach($children as $id){
        $variant=wc_get_product($id);
        if(!$variant instanceof WC_Product_Variation || $variant->get_parent_id()!==$parent->get_id() || $variant->get_status()!=='publish' || !$variant->variation_is_visible()){continue;}
        $price=$variant->get_price();
        if($price==='' || !is_numeric($price)){continue;}
        $properties=[];
        foreach($variant->get_attributes() as $key=>$value){
            $name=strtolower(preg_replace('/^(attribute_)?pa_/','',$key));
            $name=preg_replace('/^attribute_/','',$name);
            $property=in_array($name,['colour','color'],true)?'color':($name==='size'?'size':'');
            if(!$property || $value===''){continue;}
            if(taxonomy_exists($key)){$term=get_term_by('slug',$value,$key);if($term && !is_wp_error($term)){$value=$term->name;}}
            $properties[$property]=wp_strip_all_tags((string)$value);
        }
        // Wildcard variants have no unique size/colour identity to publish here.
        if(!$properties){continue;}
        $url=$variant->get_permalink();
        $offer=[
            '@type'=>'Offer','url'=>$url,'priceCurrency'=>get_woocommerce_currency(),
            'price'=>wc_format_decimal(wc_get_price_to_display($variant),wc_get_price_decimals()),
            'availability'=>'https://schema.org/'.($variant->is_on_backorder(1)?'BackOrder':($variant->is_in_stock()?'InStock':'OutOfStock')),
        ];
        if(isset($data['offers'][0]['seller'])){$offer['seller']=$data['offers'][0]['seller'];}
        $offer=loomara_enrich_offer_schema($offer,$variant);
        $item=['@type'=>'Product','@id'=>get_permalink($parent->get_id()).'#variation-'.$variant->get_id(),
            'url'=>$url,'name'=>$parent->get_name().' - '.implode(' / ',$properties),'offers'=>$offer];
        if(!empty($data['brand'])){$item['brand']=$data['brand'];}
        if(!empty($data['description'])){$item['description']=$data['description'];}
        if($variant->get_sku()!==''){$item['sku']=$variant->get_sku();}
        $image=wp_get_attachment_url($variant->get_image_id()?:$parent->get_image_id());
        if($image){$item['image']=$image;}elseif(!empty($data['image'])){$item['image']=$data['image'];}
        foreach($properties as $key=>$value){$item[$key]=$value;$varies[$key]='https://schema.org/'.$key;}
        $variants[]=$item;
    }
    if(!$variants){return $data;}
    $data['@type']='ProductGroup';
    $data['productGroupID']=$parent->get_sku()?:('loomara-'.$parent->get_id());
    $data['variesBy']=array_values($varies);
    $data['hasVariant']=$variants;
    unset($data['offers']);
    return $data;
}
add_filter('woocommerce_structured_data_product','loomara_variant_seo_schema',40,2);
