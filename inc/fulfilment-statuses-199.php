<?php
/** Native fulfilment statuses. Registration never migrates orders or confirms payment. */
defined('ABSPATH') || exit;
function loomara_fulfilment_labels(){
    return ['ready-to-ship'=>'Ready to ship','pickup-scheduled'=>'Pickup scheduled','out-for-pickup'=>'Out for pickup','picked-up'=>'Picked up','shipped'=>'Shipped','delivered'=>'Delivered'];
}

function loomara_register_fulfilment_statuses() {
    foreach (loomara_fulfilment_labels() as $slug=>$label) {
        if (get_post_status_object('wc-'.$slug)) { continue; }
        register_post_status('wc-'.$slug,[
            'label'=>$label,'public'=>false,'internal'=>false,
            'exclude_from_search'=>false,'show_in_admin_all_list'=>true,
            'show_in_admin_status_list'=>true,
            'label_count'=>_n_noop($label.' <span class="count">(%s)</span>',$label.' <span class="count">(%s)</span>','loomara-living'),
        ]);
    }
}
add_action('init','loomara_register_fulfilment_statuses',20);
function loomara_fulfilment_status_labels($statuses) {
    $result=[];
    foreach ($statuses as $key=>$label) {
        $result[$key]=$label;
        if ($key==='wc-processing') {
            foreach(loomara_fulfilment_labels() as $slug=>$text){$result['wc-'.$slug]=$statuses['wc-'.$slug]??$text;}
        }
    }
    foreach(loomara_fulfilment_labels() as $slug=>$text){$result+=['wc-'.$slug=>$text];}return $result;
}
add_filter('wc_order_statuses','loomara_fulfilment_status_labels',20);

// Do not add fulfilment statuses to global paid statuses: shipping is not proof
// of a payment (e.g. COD). Preserve an already recorded payment for these orders.
function loomara_fulfilment_is_paid($paid,$order) {
    if ($order instanceof WC_Order && isset(loomara_fulfilment_labels()[$order->get_status()])) {
        return (bool)$order->get_date_paid();
    }
    return $paid;
}
add_filter('woocommerce_order_is_paid','loomara_fulfilment_is_paid',20,2);

// Paid-purchase checks must consider the newly registered fulfilment states,
// then still verify is_paid() for each order. Never infer payment from shipment.
function loomara_purchase_statuses() {
    return array_values(array_unique(array_merge(wc_get_is_paid_statuses(),array_keys(loomara_fulfilment_labels()))));
}
