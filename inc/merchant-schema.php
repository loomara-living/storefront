<?php
/** Owner-confirmed merchant terms. Extend WooCommerce's single schema output. */
defined('ABSPATH') || exit;
function loomara_shipping_schema() {
    return [
        '@type'=>'OfferShippingDetails',
        'shippingRate'=>['@type'=>'MonetaryAmount','value'=>0,'currency'=>'INR'],
        'shippingDestination'=>['@type'=>'DefinedRegion','addressCountry'=>'IN'],
        'deliveryTime'=>[
            '@type'=>'ShippingDeliveryTime',
            'handlingTime'=>['@type'=>'QuantitativeValue','minValue'=>0,'maxValue'=>1,'unitCode'=>'DAY'],
            'transitTime'=>['@type'=>'QuantitativeValue','minValue'=>5,'maxValue'=>10,'unitCode'=>'DAY'],
        ],
    ];
}
function loomara_return_schema() {
    return [
        '@type'=>'MerchantReturnPolicy','applicableCountry'=>'IN',
        'returnPolicyCategory'=>'https://schema.org/MerchantReturnFiniteReturnWindow',
        'merchantReturnDays'=>7,'returnMethod'=>'https://schema.org/ReturnByMail',
        'returnFees'=>'https://schema.org/FreeReturn',
        'merchantReturnLink'=>home_url('/returns-refunds/'),
    ];
}
function loomara_enrich_offer_schema($offer,$product) {
    if (!is_array($offer)) { return $offer; }
    if (in_array('Offer',(array)($offer['@type'] ?? []),true)) {
        $offer['shippingDetails']=loomara_shipping_schema();
        $offer['hasMerchantReturnPolicy']=loomara_return_schema();
        // An arbitrary future expiry or publication date is not a sale schedule.
        unset($offer['validFrom'],$offer['priceValidUntil'],$offer['validThrough']);
        if (!empty($offer['priceSpecification'])) {
            if (isset($offer['priceSpecification']['@type'])) {
                unset($offer['priceSpecification']['validFrom'],$offer['priceSpecification']['validThrough']);
            } else {
                foreach ($offer['priceSpecification'] as &$specification) {
                    if (is_array($specification)) { unset($specification['validFrom'],$specification['validThrough']); }
                }
                unset($specification);
            }
        }
        if (!$product->is_type('variable') && $product->is_on_sale()) {
            $from=$product->get_date_on_sale_from(); $to=$product->get_date_on_sale_to();
            if ($from) { $offer['validFrom']=$from->date(DATE_ATOM); }
            if ($to) { $offer['priceValidUntil']=$to->date(DATE_ATOM); }
        }
    }
    if (isset($offer['offers'])) {
        if (isset($offer['offers']['@type'])) { $offer['offers']=loomara_enrich_offer_schema($offer['offers'],$product); }
        else { foreach ($offer['offers'] as &$child) { $child=loomara_enrich_offer_schema($child,$product); } unset($child); }
    }
    return $offer;
}
add_filter('woocommerce_structured_data_product',function ($data,$product) {
    if (!$product instanceof WC_Product || !$data) { return $data; }
    $data['brand']=['@type'=>'Brand','name'=>'Loomara Living'];
    if (isset($data['offers']['@type'])) { $data['offers']=loomara_enrich_offer_schema($data['offers'],$product); }
    elseif (!empty($data['offers'])) { foreach ($data['offers'] as &$offer) { $offer=loomara_enrich_offer_schema($offer,$product); } unset($offer); }
    // Preserve native, genuine reviews. Never synthesize ratings or GTINs.
    return $data;
},30,2);

function loomara_confirmed_policy_copy($content) {
    if (get_post_type() !== 'page') { return $content; }
    $slug=get_post_field('post_name',get_the_ID());
    if ($slug === 'returns-refunds') {
        $content=str_replace('A ₹199 reverse-pickup and handling fee may be deducted from the refund. Original delivery charges, if any, are not refundable.',
            'Eligible returns within 7 days of delivery have no return-shipping or handling fee. Contact us for approval and return instructions.',$content);
    } elseif ($slug === 'shipping-delivery') {
        $content=str_replace('In-stock orders are normally processed in 1–2 business days. Standard delivery generally takes 3–7 business days after dispatch, depending on destination and carrier availability. Remote locations may take longer.',
            'In-stock orders are dispatched within 24 hours. Standard delivery takes an estimated 5–10 business days after dispatch, depending on destination and carrier availability.',$content);
        $content=str_replace('dispatched within 1–2 business days', 'dispatched within 24 hours', $content);
        $content=str_replace('Any shipping charge or free-shipping threshold is shown at checkout before payment. Taxes are included or displayed as required on the product and checkout pages.',
            'Shipping is free for all orders within our serviceable delivery areas in India, with no minimum order value. Taxes are included or displayed as required on the product and checkout pages.',$content);
    }
    return $content;
}
add_filter('the_content','loomara_confirmed_policy_copy',20);
add_action('woocommerce_single_product_summary',function () {
    echo '<p class="product-delivery-terms">Free India shipping · Dispatch within 24 hours · Delivery in 5–10 business days after dispatch. <a href="'.esc_url(home_url('/returns-refunds/')).'">Free eligible returns within 7 days of delivery.</a></p>';
},25);
