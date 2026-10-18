<?php
/** Save the final, paid Magic Checkout shipping address to its order owner only. */
defined('ABSPATH') || exit;

function loomara_magic_address_key($address) {
    $parts = [];
    foreach (['first_name','last_name','phone','address_1','address_2','city','state','postcode','country'] as $key) {
        $parts[] = strtolower(trim(preg_replace('/\s+/u', ' ', (string) ($address[$key] ?? ''))));
    }
    return hash('sha256', implode('|', $parts));
}

function loomara_save_magic_order_address($order_or_id) {
    static $running = [];
    $order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order($order_or_id);
    if (!$order instanceof WC_Order || !$order->is_paid() || $order->get_payment_method() !== 'razorpay'
        || $order->get_meta('is_magic_checkout_order') !== 'yes') { return; }
    $user_id = (int) $order->get_customer_id();
    // No matching by submitted email, phone, browser session or webhook identity.
    if (!$user_id || !get_user_by('id', $user_id) || isset($running[$user_id])) { return; }
    if ($order->get_shipping_country() !== 'IN') { return; }
    $raw = ['label' => 'Home'];
    foreach (loomara_address_fields() as $key => $unused) {
        if ($key === 'label') { continue; }
        $getter = 'get_shipping_' . $key;
        $raw[$key] = is_callable([$order, $getter]) ? $order->$getter() : '';
    }
    $raw['phone'] = $raw['phone'] ?: $order->get_billing_phone();
    // Razorpay may supply the full recipient name in first_name, without last_name.
    // Preserve it faithfully instead of inventing a surname or rejecting the address.
    $address = loomara_validate_address($raw, true);
    if (is_wp_error($address)) { return; } // Retry naturally if the plugin saves a complete address later.
    $fingerprint = loomara_magic_address_key($address);
    if ($order->get_meta('_loomara_magic_address_saved') === $fingerprint) { return; }
    $lock = 'loomara_magic_address_lock_' . $user_id;
    if (!add_option($lock, time(), '', false)) {
        if (!wp_next_scheduled('loomara_retry_magic_address', [$order->get_id()])) {
            wp_schedule_single_event(time() + 120, 'loomara_retry_magic_address', [$order->get_id()]);
        }
        return;
    }
    $running[$user_id] = true;
    try {
        $stored = get_user_meta($user_id, '_loomara_shipping_addresses', true);
        $addresses = is_array($stored) ? $stored : [];
        // Preserve the native shipping address when introducing the first address-book record.
        if (!$addresses && get_user_meta($user_id, 'shipping_address_1', true)) {
            $legacy = ['label' => 'Home', 'country' => (string) get_user_meta($user_id, 'shipping_country', true)];
            foreach (loomara_address_fields() as $key => $unused) {
                if ($key !== 'label') { $legacy[$key] = (string) get_user_meta($user_id, 'shipping_' . $key, true); }
            }
            $legacy['phone'] = $legacy['phone'] ?: (string) get_user_meta($user_id, 'billing_phone', true);
            if ($legacy['country'] === 'IN') {
                $valid = loomara_validate_address($legacy, true);
                if (!is_wp_error($valid)) { $addresses['primary'] = $valid; }
            }
        }
        $found = '';
        foreach ($addresses as $id => $existing) {
            // Owner-requested policy: one auto-saved address per India PIN.
            // Keep the existing recipient/street/default even if this order uses another street.
            if (trim((string)($existing['postcode'] ?? '')) === $address['postcode']) { $found = $id; break; }
        }
        if (!$found) {
            if (count($addresses) >= 20) { return; }
            $found = wp_generate_uuid4();
            $addresses[$found] = $address;
        }
        update_user_meta($user_id, '_loomara_shipping_addresses', $addresses);
        // Never replace a customer's chosen default or native shipping fields.
        $default = (string) get_user_meta($user_id, '_loomara_default_shipping_address', true);
        if (!$default) { update_user_meta($user_id, '_loomara_default_shipping_address', (string) array_key_first($addresses)); }
        $order->update_meta_data('_loomara_magic_address_saved', $fingerprint);
        $order->save_meta_data();
    } finally {
        unset($running[$user_id]);
        delete_option($lock);
    }
}

// The native gateway owns signature verification/payment processing. Only observe its saved WC order.
add_action('woocommerce_after_order_object_save', 'loomara_save_magic_order_address', 40, 1);
add_action('woocommerce_payment_complete', 'loomara_save_magic_order_address', 40, 1);
add_action('woocommerce_order_status_changed', 'loomara_save_magic_order_address', 40, 1);
add_action('loomara_retry_magic_address', 'loomara_save_magic_order_address', 10, 1);
