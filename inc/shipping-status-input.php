<?php
/** Normalize supported courier labels at API boundaries, never write an order here. */
defined('ABSPATH') || exit;

function loomara_shipping_status_input($value) {
    if (!is_string($value)) { return $value; }
    // Explicit allowlist: no fuzzy matching, and no mapping of payment/return states.
    $key = preg_replace('/[\s_-]+/u', '-', strtolower(trim($value)));
    $map = [
        'ready-to-ship' => 'ready-to-ship',
        'out-for-pickup' => 'out-for-pickup',
        'picked-up' => 'picked-up',
        'shipped' => 'shipped',
        'in-transit' => 'in-transit',
        'out-for-delivery' => 'out-for-delivery',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
    ];
    $slug = $map[$key ?? ''] ?? null;
    if (!$slug || !isset(wc_get_order_statuses()['wc-'.$slug])) { return $value; }
    return $slug;
}

// Before REST argument/enum validation; core still handles authentication,
// endpoint permissions, stock/order hooks and all financial safeguards.
function loomara_shipping_rest_input($result, $server, $request) {
    if ($result !== null || !$request instanceof WP_REST_Request ||
        !in_array(strtoupper($request->get_method()), ['POST','PUT','PATCH'], true) ||
        !preg_match('~^/wc/v[123]/orders/[1-9][0-9]*/?$~D', $request->get_route())) {
        return $result;
    }
    $value = $request->get_param('status');
    $normalized = loomara_shipping_status_input($value);
    if ($normalized !== $value) { $request->set_param('status', $normalized); }
    return $result;
}
add_filter('rest_pre_dispatch', 'loomara_shipping_rest_input', 20, 3);

// Legacy Woo API invokes this filter after edit permission validation.
function loomara_shipping_legacy_input($data, $id, $controller = null) {
    if (!is_array($data) || !is_numeric($id) || (int)$id < 1 || !array_key_exists('status', $data)) { return $data; }
    $data['status'] = loomara_shipping_status_input($data['status']);
    return $data;
}
add_filter('woocommerce_api_edit_order_data', 'loomara_shipping_legacy_input', 20, 3);
