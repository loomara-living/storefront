<?php
/** Private, per-customer India shipping address book. No database schema changes. */
defined('ABSPATH') || exit;

function loomara_address_fields() {
    return [
        'label' => 'Address label (Home, Work…)', 'first_name' => 'First name', 'last_name' => 'Last name',
        'phone' => 'Mobile number', 'address_1' => 'Flat / house number, building, street',
        'address_2' => 'Area / landmark (optional)', 'city' => 'City',
        'state' => 'State / Union Territory', 'postcode' => 'PIN code',
    ];
}

function loomara_saved_addresses() {
    if (!is_user_logged_in()) { return []; }
    $stored = get_user_meta(get_current_user_id(), '_loomara_shipping_addresses', true);
    $addresses = is_array($stored) ? $stored : [];
    // Expose the pre-existing native shipping address without writing on a GET.
    $legacy = [];
    foreach (loomara_address_fields() as $field => $label) {
        $legacy[$field] = (string) get_user_meta(get_current_user_id(), 'shipping_' . $field, true);
    }
    if (!$addresses && $legacy['address_1'] && !get_user_meta(get_current_user_id(), '_loomara_address_book_initialized', true)) {
        $legacy['label'] = 'Home';
        $legacy['country'] = 'IN';
        $legacy['phone'] = $legacy['phone'] ?: (string) get_user_meta(get_current_user_id(), 'billing_phone', true);
        $addresses['primary'] = $legacy;
    }
    return $addresses;
}

function loomara_default_address_id($addresses = null) {
    $addresses = $addresses ?? loomara_saved_addresses();
    $default = (string) get_user_meta(get_current_user_id(), '_loomara_default_shipping_address', true);
    return isset($addresses[$default]) ? $default : (string) array_key_first($addresses);
}

function loomara_validate_address($raw, $allow_single_name = false) {
    if (isset($raw['label_choice'])) {
        $choice=is_scalar($raw['label_choice'])?sanitize_text_field($raw['label_choice']):'';
        $raw['label']=$choice==='Custom'?($raw['label_custom']??''):$choice;
    }
    $address = ['country' => 'IN'];
    foreach (loomara_address_fields() as $field => $label) {
        $address[$field] = isset($raw[$field]) && is_scalar($raw[$field]) ? sanitize_text_field($raw[$field]) : '';
        $address[$field] = substr($address[$field], 0, 160);
    }
    $errors = new WP_Error();
    foreach (['label', 'first_name', 'last_name', 'address_1', 'city', 'state', 'postcode', 'phone'] as $field) {
        if ($field === 'last_name' && $allow_single_name) { continue; }
        if (!$address[$field]) { $errors->add($field, loomara_address_fields()[$field] . ' is required.'); }
    }
    $states = WC()->countries->get_states('IN');
    if (!isset($states[$address['state']])) { $errors->add('state', 'Choose a valid Indian State / Union Territory.'); }
    if (!preg_match('/^[1-9][0-9]{5}$/', $address['postcode'])) { $errors->add('postcode', 'Enter a valid six-digit India PIN code.'); }
    $mobile = preg_replace('/\D/', '', $address['phone']);
    if (strlen($mobile) === 12 && substr($mobile, 0, 2) === '91') { $mobile = substr($mobile, 2); }
    if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) { $errors->add('phone', 'Enter a valid 10-digit Indian mobile number.'); }
    $address['phone'] = '+91' . $mobile;
    return $errors->has_errors() ? $errors : $address;
}

function loomara_sync_default_address($address) {
    $customer = new WC_Customer(get_current_user_id());
    foreach (['first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone'] as $key) {
        $setter = 'set_shipping_' . $key;
        if (is_callable([$customer, $setter])) { $customer->$setter($address[$key] ?? ''); }
    }
    $customer->save();
}

/** Delete only an address owned by the current account; never alter past orders. */
function loomara_delete_saved_address($id) {
    if (!is_user_logged_in()) { return new WP_Error('login', 'Sign in to manage your addresses.'); }
    $addresses = loomara_saved_addresses();
    if (!$id || !isset($addresses[$id])) { return new WP_Error('address', 'This address is not available in your account.'); }
    $default = loomara_default_address_id($addresses);
    unset($addresses[$id]);
    update_user_meta(get_current_user_id(), '_loomara_shipping_addresses', $addresses);
    update_user_meta(get_current_user_id(), '_loomara_address_book_initialized', '1');
    if (!$addresses) {
        delete_user_meta(get_current_user_id(), '_loomara_default_shipping_address');
        // Clear the native shipping fallback too, so the removed address cannot reappear.
        loomara_sync_default_address([]);
    } elseif ($default === $id) {
        $next = (string) array_key_first($addresses);
        update_user_meta(get_current_user_id(), '_loomara_default_shipping_address', $next);
        loomara_sync_default_address($addresses[$next]);
    }
    return true;
}
add_action('admin_post_loomara_delete_address', function () {
    if (!is_user_logged_in()) { auth_redirect(); }
    check_admin_referer('loomara_delete_address');
    $raw = wp_unslash($_POST['address_id'] ?? '');
    $result = loomara_delete_saved_address(is_scalar($raw) ? sanitize_key($raw) : '');
    if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message()), '', ['response' => 403]); }
    wp_safe_redirect(add_query_arg('address_deleted', '1', wc_get_account_endpoint_url('edit-address')));
    exit;
});

add_action('admin_post_loomara_save_address', function () {
    if (!is_user_logged_in()) { auth_redirect(); }
    check_admin_referer('loomara_save_address');
    $addresses = loomara_saved_addresses();
    $id = sanitize_key(wp_unslash($_POST['address_id'] ?? ''));
    if ($id && !isset($addresses[$id])) { wp_die('This address is not available in your account.', '', ['response' => 403]); }
    if (!$id && count($addresses) >= 20) { wp_die('You can save up to 20 shipping addresses.', '', ['response' => 400]); }
    $address = loomara_validate_address(wp_unslash($_POST));
    if (is_wp_error($address)) {
        // Render safely on the POST response so invalid input is never lost or
        // placed in URLs, session logs or another customer's metadata.
        status_header(400);
        get_header();
        echo '<main id="main" class="wp-page-content"><div class="entry-content woocommerce"><div class="woocommerce-error" role="alert">' . esc_html(implode(' ', $address->get_error_messages())) . '</div>';
        loomara_address_form($id, array_map('sanitize_text_field', wp_unslash($_POST)));
        echo '</div></main>';
        get_footer();
        exit;
    }
    $id = $id ?: wp_generate_uuid4();
    $addresses[$id] = $address;
    update_user_meta(get_current_user_id(), '_loomara_shipping_addresses', $addresses);
    if (!empty($_POST['make_default']) || count($addresses) === 1 || loomara_default_address_id($addresses) === $id) {
        update_user_meta(get_current_user_id(), '_loomara_default_shipping_address', $id);
        loomara_sync_default_address($address);
    }
    wp_safe_redirect(add_query_arg('address_saved', '1', wc_get_account_endpoint_url('edit-address')));
    exit;
});

function loomara_address_form($id = '', $address = []) {
    echo '<div class="address-editor"><p class="eyebrow">A place for your next parcel</p><h3>' . ($id ? 'Edit shipping address' : 'Add shipping address') . '</h3><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('loomara_save_address');
    echo '<input type="hidden" name="action" value="loomara_save_address"><input type="hidden" name="address_id" value="' . esc_attr($id) . '"><div class="address-form-grid">';
    foreach (loomara_address_fields() as $field => $label) {
        echo '<p class="address-field address-field-' . esc_attr($field) . '"><label for="address-' . esc_attr($field) . '">' . esc_html($label) . '</label>';
        $value = $address[$field] ?? '';
        if ($field === 'label') {
            $choice=in_array($value,['Home','Work'],true)?$value:($value?'Custom':'Home');
            echo '<select name="label_choice" id="address-label" data-address-label>';foreach(['Home','Work','Custom'] as $option){echo '<option '.selected($choice,$option,false).'>'.esc_html($option).'</option>';}echo '</select><label class="custom-label-editor"'.($choice==='Custom'?'':' hidden').'>Custom label<input name="label_custom" maxlength="160" value="'.esc_attr($choice==='Custom'?$value:'').'" '.($choice==='Custom'?'required':'disabled').'></label>';
        } elseif ($field === 'state') {
            echo '<select name="state" id="address-state" required autocomplete="address-level1"><option value="">Choose state / UT</option>';
            foreach (WC()->countries->get_states('IN') as $code => $state) { echo '<option value="' . esc_attr($code) . '" ' . selected($value, $code, false) . '>' . esc_html($state) . '</option>'; }
            echo '</select>';
        } else {
            $autocomplete = ['first_name' => 'given-name', 'last_name' => 'family-name', 'phone' => 'tel', 'address_1' => 'address-line1', 'address_2' => 'address-line2', 'city' => 'address-level2', 'postcode' => 'postal-code'];
            echo '<input id="address-' . esc_attr($field) . '" name="' . esc_attr($field) . '" type="' . ($field === 'phone' ? 'tel' : 'text') . '" maxlength="' . ($field === 'postcode' ? '6' : '160') . '" value="' . esc_attr($value) . '" autocomplete="' . esc_attr($autocomplete[$field] ?? 'off') . '"' . ($field === 'address_2' ? '' : ' required') . ($field === 'postcode' ? ' inputmode="numeric" pattern="[1-9][0-9]{5}"' : '') . '>';
        }
        echo '</p>';
    }
    echo '</div><p class="address-country">India <span>We deliver within India only.</span></p><label class="address-default"><input type="checkbox" name="make_default" value="1" ' . checked((!$id && !loomara_saved_addresses()) || ($id && $id === loomara_default_address_id()), true, false) . '> Use as my default shipping address</label><div class="address-form-actions"><button class="button" type="submit">Save shipping address</button><a href="' . esc_url(wc_get_account_endpoint_url('edit-address')) . '">Cancel</a></div></form></div>';
}

function loomara_address_book() {
    $addresses = loomara_saved_addresses();
    $default = loomara_default_address_id($addresses);
    $editing = isset($_GET['address']) ? sanitize_key(wp_unslash($_GET['address'])) : '';
    echo '<section class="address-book"><div class="address-book-heading"><div><p class="eyebrow">Your familiar places</p><h2>Saved shipping addresses</h2><p>Home, work, or somewhere you feel at home. Choose a saved address at checkout.</p></div><a class="address-add" href="' . esc_url(add_query_arg('address', 'new', wc_get_account_endpoint_url('edit-address'))) . '"><span aria-hidden="true">＋</span> Add address</a></div>';
    if (isset($_GET['address_saved'])) { echo '<p class="woocommerce-message" role="status">Your shipping address is saved.</p>'; }
    if (isset($_GET['address_deleted'])) { echo '<p class="woocommerce-message" role="status">Address deleted. Your past orders are unchanged.</p>'; }
    if ($editing) {
        if ($editing === 'new' || isset($addresses[$editing])) { loomara_address_form($editing === 'new' ? '' : $editing, $addresses[$editing] ?? []); }
        else { echo '<p role="alert">That address is not available.</p>'; }
    }
    echo '<div class="address-card-grid">';
    foreach ($addresses as $id => $address) {
        echo '<article class="address-card"><div class="address-card-heading"><h3>' . esc_html($address['label']) . '</h3>' . ($id === $default ? '<span class="address-badge">Default</span>' : '') . '</div><address>' . wp_kses_post(WC()->countries->get_formatted_address($address)) . '</address><p>' . esc_html($address['phone'] ?? '') . '</p><a class="text-link" href="' . esc_url(add_query_arg('address', $id, wc_get_account_endpoint_url('edit-address'))) . '">Edit address →</a>';
        if ($id !== $default) { echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_default_address"><input type="hidden" name="address_id" value="'.esc_attr($id).'">';wp_nonce_field('loomara_default_address');echo '<button class="button" type="submit">Make default</button></form>'; }
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" data-delete-saved-address><input type="hidden" name="action" value="loomara_delete_address"><input type="hidden" name="address_id" value="'.esc_attr($id).'">';
        wp_nonce_field('loomara_delete_address');
        echo '<button class="button address-delete-button" type="submit">Delete address</button></form></article>';
    }
    if (!$addresses) { echo '<div class="address-empty"><span aria-hidden="true">⌂</span><h3>Make room for your first delivery.</h3><p>Add an India shipping address and make your next checkout a little easier.</p></div>'; }
    echo '</div><p class="address-note">Choose a default shipping address. You can use a different billing address at checkout.</p></section>';
}

// Intercept only this account endpoint, preserving WooCommerce authentication.
add_action('init', function () {
    remove_action('woocommerce_account_edit-address_endpoint', 'woocommerce_account_edit_address');
    add_action('woocommerce_account_edit-address_endpoint', 'loomara_address_book');
}, 30);

// Render the native classic checkout for a predictable address picker with
// server validation. No page-content rewrite; reverting the theme restores blocks.
add_filter('render_block', function ($content, $block) {
    if (($block['blockName'] ?? '') === 'woocommerce/checkout' && !is_admin() && is_checkout() && !is_wc_endpoint_url()) {
        return do_shortcode('[woocommerce_checkout]');
    }
    return $content;
}, 10, 2);
add_filter('option_woocommerce_ship_to_destination', function () { return 'shipping'; });
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');
add_filter('woocommerce_ship_to_different_address_checked', '__return_false');

add_action('woocommerce_checkout_before_customer_details', function () {
    if (!is_user_logged_in()) { return; }
    $addresses = loomara_saved_addresses();
    echo '<section class="checkout-address-book"><h3>Where shall we deliver?</h3><p>Choose a saved shipping address or enter a new one below.</p>';
    // Escaped JSON stays private inside the signed-in, no-cache checkout page.
    echo '<div data-checkout-addresses data-addresses="' . esc_attr(wp_json_encode($addresses)) . '"><label for="loomara-address-select">Shipping address</label><select id="loomara-address-select" name="loomara_address_id"><option value="">Enter an address manually</option>';
    $selected = isset($_POST['loomara_address_id']) ? sanitize_key(wp_unslash($_POST['loomara_address_id'])) : loomara_default_address_id($addresses);
    foreach ($addresses as $id => $address) {
        echo '<option value="' . esc_attr($id) . '" ' . selected($selected, $id, false) . '>' . esc_html($address['label'] . ' — ' . $address['address_1'] . ', ' . $address['city'] . ' ' . $address['postcode']) . '</option>';
    }
    echo '</select><p class="address-note">Editing a field switches to manual entry. Existing saved addresses are not overwritten.</p></div></section>';
});
add_filter('woocommerce_checkout_get_value', function ($value, $key) {
    if (!is_user_logged_in() || isset($_POST[$key]) || !preg_match('/^(?:billing|shipping)_(first_name|last_name|address_1|address_2|city|state|postcode|country|phone)$/', $key, $match)) { return $value; }
    $addresses = loomara_saved_addresses();
    $default = loomara_default_address_id($addresses);
    return $addresses[$default][$match[1]] ?? $value;
}, 30, 2);
add_action('woocommerce_after_checkout_billing_form', function () {
    if (is_user_logged_in()) { echo '<p class="form-row"><label><input type="checkbox" name="loomara_save_new_address" value="1"> Save this new shipping address to my account</label></p>'; }
});
add_filter('woocommerce_checkout_posted_data', function ($data) {
    $id = sanitize_key(wp_unslash($_POST['loomara_address_id'] ?? ''));
    if ($id && is_user_logged_in()) {
        $addresses = loomara_saved_addresses();
        if (!isset($addresses[$id])) { wc_add_notice('Please choose one of your own saved addresses or enter a new address.', 'error'); return $data; }
        foreach ($addresses[$id] as $key => $value) {
            if ($key === 'label') { continue; }
            $data['billing_' . $key] = $value;
            $data['shipping_' . $key] = $value;
        }
    }
    $data['billing_country'] = $data['shipping_country'] = 'IN';
    // The main form is the delivery/contact address. Copy it before changing billing.
    foreach (['first_name','last_name','address_1','address_2','city','state','postcode','country','phone'] as $key) { $data['shipping_'.$key] = $data['billing_'.$key] ?? ''; }
    if (empty($_POST['loomara_billing_same'])) {
        $billing_id = sanitize_key(wp_unslash($_POST['loomara_billing_address_id'] ?? ''));
        $addresses = loomara_saved_addresses();
        $billing = $billing_id && isset($addresses[$billing_id]) ? $addresses[$billing_id] : [];
        if ($billing_id && !isset($addresses[$billing_id])) { wc_add_notice('Choose one of your own saved billing addresses.', 'error'); }
        foreach (['first_name','last_name','address_1','address_2','city','state','postcode','phone'] as $key) {
            $raw = $_POST['loomara_billing_'.$key] ?? '';
            $data['billing_'.$key] = $billing[$key] ?? (is_scalar($raw) ? sanitize_text_field(wp_unslash($raw)) : '');
        }
    }
    $data['ship_to_different_address'] = true;
    return $data;
});
add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
    $raw = ['label' => 'Shipping address'];
    foreach (loomara_address_fields() as $key => $label) {
        if ($key !== 'label') { $raw[$key] = $data['shipping_' . $key] ?? ''; }
    }
    $result = loomara_validate_address($raw);
    if (is_wp_error($result)) {
        foreach ($result->get_error_codes() as $code) { $errors->add('loomara_address_' . $code, $result->get_error_message($code)); }
    }
}, 20, 2);
add_action('woocommerce_checkout_order_processed', function ($order_id, $data, $order) {
    if (!is_user_logged_in() || empty($_POST['loomara_save_new_address']) || !empty($_POST['loomara_address_id'])) { return; }
    $raw = ['label' => 'Delivery ' . wp_date('d M Y')];
    foreach (loomara_address_fields() as $key => $label) { if ($key !== 'label') { $raw[$key] = $data['shipping_' . $key] ?? ''; } }
    $address = loomara_validate_address($raw);
    if (is_wp_error($address)) { return; }
    $addresses = loomara_saved_addresses();
    foreach ($addresses as $existing) { if (array_diff_assoc(array_diff_key($address, ['label' => true]), $existing) === []) { return; } }
    if (count($addresses) >= 20) { return; }
    $id = wp_generate_uuid4();
    $addresses[$id] = $address;
    update_user_meta(get_current_user_id(), '_loomara_shipping_addresses', $addresses);
}, 10, 3);
add_filter('gettext', function ($translated, $text, $domain) {
    if ($domain === 'woocommerce' && $text === 'Billing details' && function_exists('is_checkout') && is_checkout()) { return 'Shipping & contact details'; }
    if ($domain === 'woocommerce' && $text === 'shipping and billing addresses') { return 'saved shipping addresses'; }
    return $translated;
}, 20, 3);
