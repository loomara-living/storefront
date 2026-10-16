<?php
/** Account, wishlist and India storefront behaviour. */
defined('ABSPATH') || exit;

add_action('init', function () {
    add_rewrite_endpoint('wishlist', EP_ROOT | EP_PAGES);
}, 5);

// Keep WooCommerce's native forms, nonces, customer storage and password hashing.
remove_action('woocommerce_before_customer_login_form', 'loomara_account_intro', 5);

function loomara_registration_mode() {
    return (isset($_GET['action']) && sanitize_key(wp_unslash($_GET['action'])) === 'register')
        || (isset($_POST['register'], $_POST['woocommerce-register-nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce-register-nonce'])), 'woocommerce-register'));
}

add_filter('language_attributes', function ($attributes) {
    return preg_replace('/lang="en(?:-US)?"/', 'lang="en-IN"', $attributes);
});

function loomara_password_valid($password) {
    return strlen($password) >= 6 && preg_match('/[a-zA-Z]/', $password)
        && preg_match('/[0-9]/', $password) && preg_match('/[^a-zA-Z0-9\s]/', $password);
}
add_filter('woocommerce_registration_errors', function ($errors) {
    if (!isset($_POST['register'])) {
        return $errors;
    }
    $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
    if (!loomara_password_valid($password)) {
        $errors->add('loomara_password', __('Use at least 6 characters, including a letter, a number and a special character.', 'loomara-living'));
    }
    return $errors;
}, 20);

// Replace the generic strength score with the explicit, server-enforced rules.
add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_account_page') && is_account_page() && !is_user_logged_in()) {
        wp_dequeue_script('wc-password-strength-meter');
    }
}, 100);

add_filter('woocommerce_account_menu_items', function ($items) {
    unset($items['downloads']);
    $logout = $items['customer-logout'] ?? null;
    unset($items['customer-logout']);
    $items['edit-address'] = __('Saved addresses', 'loomara-living');
    $items['wishlist'] = __('Wishlist', 'loomara-living');
    if ($logout) {
        $items['customer-logout'] = $logout;
    }
    return $items;
});

function loomara_wishlist_ids() {
    return is_user_logged_in() ? array_values(array_unique(array_filter(array_map('absint', (array) get_user_meta(get_current_user_id(), '_loomara_wishlist', true))))) : [];
}

function loomara_wishlist_button() {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $saved = in_array($product->get_id(), loomara_wishlist_ids(), true);
    if (!is_user_logged_in()) {
        echo '<a class="wishlist-button" href="' . esc_url(wc_get_page_permalink('myaccount')) . '"><span aria-hidden="true">♡</span> Sign in to save</a>';
        return;
    }
    echo '<form class="wishlist-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('loomara_wishlist', 'loomara_wishlist_nonce');
    echo '<input type="hidden" name="action" value="loomara_wishlist"><input type="hidden" name="product_id" value="' . absint($product->get_id()) . '">';
    echo '<button class="wishlist-button" type="submit" aria-label="' . esc_attr(($saved ? 'Remove from wishlist: ' : 'Save to wishlist: ') . $product->get_name()) . '"><span aria-hidden="true">' . ($saved ? '♥' : '♡') . '</span> ' . ($saved ? 'Saved · remove' : 'Save to wishlist') . '</button></form>';
}
add_action('woocommerce_after_shop_loop_item', 'loomara_wishlist_button', 20);
add_action('woocommerce_single_product_summary', 'loomara_wishlist_button', 35);

add_action('admin_post_loomara_wishlist', function () {
    if (!is_user_logged_in()) {
        wp_safe_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }
    check_admin_referer('loomara_wishlist', 'loomara_wishlist_nonce');
    $id = absint($_POST['product_id'] ?? 0);
    $product = wc_get_product($id);
    if (!$product || $product->get_status() !== 'publish') {
        wp_die(esc_html__('This product is unavailable.', 'loomara-living'), '', ['response' => 400]);
    }
    $ids = loomara_wishlist_ids();
    $saved = in_array($id, $ids, true);
    $ids = $saved ? array_values(array_diff($ids, [$id])) : array_merge($ids, [$id]);
    update_user_meta(get_current_user_id(), '_loomara_wishlist', $ids);
    // admin-post.php does not load WooCommerce's frontend notice functions.
    // The returned page renders the saved state from account metadata directly.
    $account_url = get_permalink(get_option('woocommerce_myaccount_page_id')) ?: home_url('/my-account/');
    wp_safe_redirect(wp_get_referer() ?: trailingslashit($account_url) . 'wishlist/');
    exit;
});

add_action('woocommerce_account_wishlist_endpoint', function () {
    echo '<h2>Your softer-space wishlist</h2><p>Save your favourites here and come back whenever home calls for a little refresh.</p>';
    $ids = array_filter(loomara_wishlist_ids(), function ($id) {
        $product = wc_get_product($id);
        return $product && $product->get_status() === 'publish' && $product->is_visible();
    });
    if (!$ids) {
        echo '<div class="wishlist-empty"><span aria-hidden="true">♡</span><h3>A little room for favourites.</h3><p>Your saved pieces will appear here.</p><a class="button" href="' . esc_url(wc_get_page_permalink('shop')) . '">Explore the collection</a></div>';
        return;
    }
    echo do_shortcode('[products ids="' . implode(',', array_map('absint', $ids)) . '" columns="3" orderby="post__in"]');
});

// Apply the country restriction at the data layer for classic AND block checkout.
add_filter('woocommerce_countries_allowed_countries', function () { return ['IN' => 'India']; });
add_filter('woocommerce_countries_shipping_countries', function () { return ['IN' => 'India']; });
add_filter('default_checkout_billing_country', function () { return 'IN'; });
add_filter('default_checkout_shipping_country', function () { return 'IN'; });
add_filter('woocommerce_customer_default_location', function () { return 'IN'; });
add_filter('woocommerce_get_country_locale', function ($locale) {
    $locale['IN']['postcode']['label'] = 'PIN code';
    $locale['IN']['postcode']['placeholder'] = '6-digit PIN code';
    $locale['IN']['state']['label'] = 'State / Union Territory';
    $locale['IN']['state']['required'] = true;
    return $locale;
});

add_filter('woocommerce_checkout_get_value', function ($value, $key) {
    if (in_array($key, ['billing_country', 'shipping_country'], true)) {
        return 'IN';
    }
    if ($value !== null && $value !== '') {
        return $value;
    }
    if (!is_user_logged_in() || !preg_match('/^(billing|shipping)_/', $key)) {
        return $value;
    }
    $saved = get_user_meta(get_current_user_id(), $key, true);
    if ($saved !== '') {
        return $saved;
    }
    return $key === 'billing_email' ? wp_get_current_user()->user_email : $value;
}, 20, 2);

// Block checkout reads WC_Customer directly instead of checkout field filters.
add_action('woocommerce_init', function () {
    if (!WC()->customer || is_admin()) {
        return;
    }
    foreach (['billing', 'shipping'] as $type) {
        $getter = 'get_' . $type . '_country';
        if (WC()->customer->$getter() !== 'IN') {
            $setter = 'set_' . $type . '_country';
            WC()->customer->$setter('IN');
            $setter = 'set_' . $type . '_state';
            WC()->customer->$setter('');
        }
    }
    if (is_user_logged_in() && !WC()->customer->get_billing_email()) {
        WC()->customer->set_billing_email(wp_get_current_user()->user_email);
    }
});
add_filter('woocommerce_default_address_fields', function ($fields) {
    $fields['address_1']['placeholder'] = 'Flat / house number, building, street';
    $fields['address_2']['placeholder'] = 'Area, locality or landmark (optional)';
    return $fields;
});
add_filter('woocommerce_billing_fields', function ($fields) {
    $fields['billing_phone']['required'] = true;
    $fields['billing_phone']['label'] = 'Mobile number';
    $fields['billing_phone']['placeholder'] = '+91 98765 43210';
    return $fields;
});
add_action('woocommerce_after_edit_address_form_billing', function () {
    echo '<p class="address-tip">Save your India billing address here. It will be filled in at checkout when you are signed in.</p>';
});
add_filter('woocommerce_product_tabs', function ($tabs) { unset($tabs['reviews']); return $tabs; }, 99);
add_filter('woocommerce_product_get_reviews_allowed', '__return_false');
add_filter('woocommerce_product_get_average_rating', function () { return '0'; });
add_filter('woocommerce_product_get_rating_count', '__return_zero');
add_filter('woocommerce_product_get_review_count', '__return_zero');
add_action('init', function () {
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
});

/** One-time, reversible migration: keep prior options, page revisions and reviews. */
add_action('init', function () {
    if (!class_exists('WooCommerce') || get_option('loomara_experience_version') === '1.4.0') {
        return;
    }
    $options = [
        'woocommerce_default_country' => 'IN', 'woocommerce_currency' => 'INR',
        'woocommerce_allowed_countries' => 'specific', 'woocommerce_specific_allowed_countries' => ['IN'],
        'woocommerce_ship_to_countries' => 'specific', 'woocommerce_specific_ship_to_countries' => ['IN'],
        'woocommerce_default_customer_address' => 'base',
        'woocommerce_checkout_phone_field' => 'required',
        'woocommerce_enable_reviews' => 'no', 'woocommerce_enable_review_rating' => 'no',
        'woocommerce_email_footer_text' => '{site_title} · India · Prices in INR · Support: hello@loomaraliving.com',
    ];
    $backup = [];
    foreach ($options as $key => $new_value) {
        $backup[$key] = get_option($key);
        if ($key === 'woocommerce_default_country' && strpos((string) $backup[$key], 'IN:') === 0) {
            continue;
        }
        update_option($key, $new_value);
    }
    // Remove the initial overseas demo address; never invent a business address.
    if (preg_match('/^(US|USA|United States)(:|$)/i', (string) $backup['woocommerce_default_country'])) {
        foreach (['woocommerce_store_address', 'woocommerce_store_address_2', 'woocommerce_store_city', 'woocommerce_store_postcode'] as $key) {
            $backup[$key] = get_option($key);
            update_option($key, '');
        }
    }
    add_option('loomara_before_1_4_0_options', $backup, '', false);
    $reviews = get_comments(['post_type' => 'product', 'status' => 'approve', 'number' => 0]);
    foreach ($reviews as $review) {
        add_comment_meta($review->comment_ID, '_loomara_previous_status_1_4_0', $review->comment_approved, true);
        wp_set_comment_status($review->comment_ID, 'hold');
        wc_delete_product_transients($review->comment_post_ID);
    }
    loomara_expand_policies();
    flush_rewrite_rules(false);
    update_option('loomara_experience_version', '1.4.0', false);
}, 60);

function loomara_expand_policies() {
    $contact = '<a href="mailto:hello@loomaraliving.com">hello@loomaraliving.com</a>';
    $extra = [
        'returns-refunds' => '<h2>How a return works</h2><ol><li>Email ' . $contact . ' with your order number, the affected item, reason and available photographs.</li><li>Wait for return approval and the correct return address before sending a parcel. Do not send it to the courier sender address without confirmation.</li><li>Keep all pieces of a set together, including pillowcases, accessories and tags. Pack securely to avoid damage in transit.</li><li>Keep your pickup receipt or tracking number until we confirm receipt and inspection.</li></ol><h2>Exchanges and partial returns</h2><p>Exchanges depend on available stock. If a replacement is unavailable, we will discuss an eligible refund. A coordinated set or combo must be returned as a complete set unless we agree otherwise for a defective component. Discounts are allocated to individual items when calculating a partial refund.</p><h2>Refund destination and status</h2><p>Refunds are processed in Indian rupees to the original payment method where possible. Never share an OTP, UPI PIN, CVV or banking password for a refund. We will explain any legitimate account details needed for an approved cash-on-delivery refund through our support channel.</p><h2>Your statutory rights</h2><p>The reporting windows help us investigate quickly; they do not remove rights available under applicable Indian consumer law for defective, misdescribed or incorrectly supplied goods. If a return is declined, you may request the inspection reason and supporting information.</p>',
        'shipping-delivery' => '<h2>India delivery addresses</h2><p>Enter the recipient’s full name, reachable Indian mobile number, flat or house number, street, locality, city, State or Union Territory and correct six-digit PIN code. Saved account addresses can be reused at checkout. Delivery availability depends on the courier network and PIN code.</p><h2>Working days and dispatch</h2><p>Delivery estimates exclude public holidays and may be affected by weekends, festivals or severe weather. If an order includes more than one parcel, items may arrive separately. Contact us if tracking has not progressed or a parcel is marked delivered but has not arrived.</p><h2>Missed deliveries and address corrections</h2><p>Contact ' . $contact . ' immediately if an address is incorrect. We will try to help before dispatch. If a parcel returns after unsuccessful delivery, we will explain available redelivery or refund options and any applicable charge before arranging a new shipment.</p><h2>International orders</h2><p>We currently accept delivery addresses within India only. International shipping and customs handling are not offered.</p>',
        'privacy-policy' => '<h2>India operations and service providers</h2><p>Loomara Living serves customers in India. Providers that process hosting, transactional emails or payments may operate infrastructure outside India. We share only information needed for the relevant service and applicable obligations. We do not promise that all information is stored exclusively in India.</p><h2>Account addresses and wishlists</h2><p>When signed in, you can save billing and delivery addresses and a product wishlist to your account. These are used to simplify future visits and checkout. You can edit addresses and remove wishlist items through My account. Signing out prevents other users of the same browser from viewing your account.</p><h2>Transactional email and preferences</h2><p>Order updates, account security and password recovery emails support the service you request. Marketing consent is separate and may be withdrawn. A password-reset link is time limited; we will never ask you to email your password, OTP or payment PIN.</p><h2>Privacy requests and complaints</h2><p>Email ' . $contact . ' with the subject “Privacy request”. Describe the request without attaching unnecessary identity documents. We may verify account ownership before disclosing or changing information. We will explain if a request cannot be fully completed because records must be retained under applicable law.</p>',
        'terms-and-conditions' => '<h2>India-based store and currency</h2><p>Loomara Living is based in India and serves delivery addresses within India. Product prices and refunds are in Indian rupees (INR). Review the final payable amount, taxes where applicable and shipping charges before confirming payment.</p><h2>Order corrections, stock and payment issues</h2><p>An order acknowledgment confirms receipt of your request. If we cannot fulfil an item, we will contact you about an available alternative or refund; we will not substitute an item without agreement. If money is debited but no order confirmation appears, contact support with the payment reference, without sharing card secrets or UPI credentials.</p><h2>Promotions and product care</h2><p>Promotions are subject to the conditions displayed with the offer. Unless specified, discounts cannot be exchanged for cash. Follow each product’s care label; colour variation on a screen does not excuse a materially incorrect or defective product.</p><h2>Customer support and complaints</h2><p>For an order concern or complaint, email ' . $contact . ' with your order number and requested resolution. If the first response does not resolve the concern, reply on the same thread and ask for escalation. Rights and remedies under applicable Indian consumer law remain available.</p><h2>Policy priority</h2><p>Product-specific information and the final checkout details govern the item and amount purchased. These terms should be read with the Shipping &amp; Delivery, Returns &amp; Refunds and Privacy policies. Updates apply prospectively and do not remove rights attached to an existing order.</p>',
    ];
    foreach ($extra as $slug => $content) {
        $page = get_page_by_path($slug);
        if (!$page || strpos($page->post_content, '<!-- loomara-policy-1.4.0 -->') !== false) {
            continue;
        }
        add_post_meta($page->ID, '_loomara_policy_before_1_4_0', $page->post_content, true);
        wp_save_post_revision($page->ID);
        wp_update_post(['ID' => $page->ID, 'post_content' => $page->post_content . '<div class="policy-document"><!-- loomara-policy-1.4.0 -->' . $content . '</div>']);
    }
}
