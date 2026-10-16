<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Store setup migration.
 *
 * This runs once per version and is deliberately idempotent so Git deployments
 * can safely be retried without duplicating pages, terms, media, or products.
 */
function loomara_store_setup() {
    $setup_version = '2026-10-16-2';
    if (get_option('loomara_store_setup_version') === $setup_version) {
        return;
    }

    $pages = loomara_create_store_pages();

    if (isset($pages['terms-and-conditions'])) {
        update_option('woocommerce_terms_page_id', $pages['terms-and-conditions']);
    }
    if (isset($pages['privacy-policy'])) {
        update_option('wp_page_for_privacy_policy', $pages['privacy-policy']);
    }

    // WooCommerce renders the password field and hashes the password through
    // WordPress' native salted, one-way password API before database storage.
    update_option('woocommerce_enable_myaccount_registration', 'yes');
    update_option('woocommerce_registration_generate_username', 'yes');
    update_option('woocommerce_registration_generate_password', 'no');

    if (class_exists('WooCommerce') && class_exists('WC_Product_Simple')) {
        loomara_create_catalog();
    }

    update_option('loomara_store_setup_version', $setup_version, false);
}
add_action('init', 'loomara_store_setup', 40);

function loomara_create_store_pages() {
    $last_updated = '16 October 2026';
    $contact = '<a href="mailto:hello@loomaraliving.com">hello@loomaraliving.com</a> or <a href="tel:+918175950351">+91 81759 50351</a>';

    $pages = [
        'our-story' => [
            'title' => 'Our Story',
            'content' => '<div class="editorial-copy"><p class="policy-lede">Loomara Living began with a simple belief: the pieces closest to us every day should bring calm, comfort and quiet beauty.</p><h2>Comfort, considered</h2><p>We create bedding and soft furnishings for real homes—easy layers, restful colour and tactile materials that feel welcoming from the first night. Our collections are designed to mix naturally, so a room can evolve without losing its sense of ease.</p><h2>Made for everyday rituals</h2><p>From the first cup of tea to the last chapter before sleep, home is built from small routines. Loomara pieces are selected to support those moments with practical care, honest descriptions and thoughtful service.</p></div>',
        ],
        'contact' => [
            'title' => 'Contact',
            'content' => '<div class="editorial-copy"><p class="policy-lede">Questions about a product, order or return? We are here to help.</p><div class="contact-cards"><div><span>Email</span><a href="mailto:hello@loomaraliving.com">hello@loomaraliving.com</a></div><div><span>Phone</span><a href="tel:+918175950351">+91 81759 50351</a></div></div><p>Please include your order number when contacting us about an existing purchase. We aim to respond within two business days.</p></div>',
        ],
        'returns-refunds' => [
            'title' => 'Returns & Refunds',
            'content' => '<div class="policy-document"><p class="policy-updated">Last updated: ' . $last_updated . '</p><p class="policy-lede">We want every Loomara layer to feel right at home. Eligible products may be returned within 7 days of delivery.</p><h2>Return eligibility</h2><ul><li>Contact us within 7 calendar days of delivery.</li><li>The item must be unused, unwashed, unaltered and returned with its original tags and packaging.</li><li>Please share the order number and clear photos of the product and packaging.</li></ul><h2>Damaged, defective or incorrect items</h2><p>Report damage, a manufacturing defect, missing contents or an incorrect item within 48 hours of delivery. Once verified, we will arrange a replacement or refund at no reverse-shipping cost to you.</p><h2>Change-of-mind returns</h2><p>Eligible change-of-mind returns may be accepted within the 7-day window. A ₹199 reverse-pickup and handling fee may be deducted from the refund. Original delivery charges, if any, are not refundable.</p><h2>Items we cannot accept</h2><ul><li>Products that have been used, washed, stained, scented, damaged after delivery or returned without original packaging.</li><li>Personalised, made-to-order, clearance or final-sale products unless defective.</li><li>Items rejected at quality inspection for hygiene or resale reasons.</li></ul><h2>Refund timing</h2><p>Approved refunds are sent to the original payment method after the returned item passes inspection. Banks and payment providers may take 7–10 business days to reflect the credit. Cash-on-delivery refunds, when offered, require verified bank details.</p><h2>Cancellations</h2><p>An order can be cancelled before dispatch by contacting us. Once dispatched, the return process above applies.</p><h2>Start a return</h2><p>Contact ' . $contact . ' with your order number and reason for return.</p></div>',
        ],
        'shipping-delivery' => [
            'title' => 'Shipping & Delivery',
            'content' => '<div class="policy-document"><p class="policy-updated">Last updated: ' . $last_updated . '</p><p class="policy-lede">We currently ship orders within India to serviceable postal codes.</p><h2>Processing and delivery</h2><p>In-stock orders are normally processed in 1–2 business days. Standard delivery generally takes 3–7 business days after dispatch, depending on destination and carrier availability. Remote locations may take longer.</p><h2>Shipping charges</h2><p>Any shipping charge or free-shipping threshold is shown at checkout before payment. Taxes are included or displayed as required on the product and checkout pages.</p><h2>Tracking</h2><p>When an order is dispatched, tracking details are sent to the email address or phone number used at checkout. Tracking may take up to 24 hours to become active.</p><h2>Delivery issues</h2><p>Please inspect the package at delivery. If it appears visibly damaged or tampered with, refuse it where possible and contact us promptly. Address changes are not guaranteed after dispatch. Loomara Living is not responsible for delays caused by incorrect addresses, failed delivery attempts, weather, carrier disruption or other events outside our reasonable control.</p><h2>Need help?</h2><p>Contact ' . $contact . ' and include your order number.</p></div>',
        ],
        'privacy-policy' => [
            'title' => 'Privacy Policy',
            'content' => '<div class="policy-document"><p class="policy-updated">Last updated: ' . $last_updated . '</p><p class="policy-lede">This policy explains how Loomara Living collects, uses and protects personal information when you visit our website, create an account or place an order.</p><h2>Information we collect</h2><ul><li>Account and contact details such as name, email address, phone number and delivery or billing address.</li><li>Order, return, support and communication history.</li><li>Device and usage information such as IP address, browser type, pages viewed, cookies and similar technologies.</li><li>Information supplied by Google when you choose Google sign-in, limited to the permissions you approve.</li></ul><h2>Payments</h2><p>Payments are handled by authorised payment providers. Loomara Living does not intentionally store complete card numbers, CVV codes or online-banking credentials on this website.</p><h2>How we use information</h2><p>We use information to create and secure accounts, process and deliver orders, provide support, prevent fraud, comply with legal obligations, improve the website and send marketing only where permitted or consented to.</p><h2>When information is shared</h2><p>Information may be shared with service providers that help us operate the store, including hosting, payment, analytics, communication, shipping and customer-support providers. We may also disclose information when legally required, to protect rights and safety, or as part of a business restructuring. We do not sell personal information.</p><h2>Cookies</h2><p>Necessary cookies keep the store, account and cart working. Optional analytics or marketing cookies may help us understand performance and communications. Browser settings can block or delete cookies, but some store features may stop working.</p><h2>Retention and security</h2><p>We retain information only as long as reasonably needed for orders, support, fraud prevention and legal or accounting obligations. We use access controls, secure connections and established WordPress/WooCommerce security practices. Passwords are stored as one-way salted hashes, not readable plaintext.</p><h2>Your choices</h2><p>You may request access, correction or deletion of eligible personal information, withdraw marketing consent, or ask a privacy question by contacting us. Some records must be kept for legal, tax, fraud-prevention or active-order purposes.</p><h2>Children</h2><p>The store is not intended for children to use independently. A parent or guardian should make purchases for minors.</p><h2>Contact</h2><p>For a privacy request, contact ' . $contact . '.</p></div>',
        ],
        'terms-and-conditions' => [
            'title' => 'Terms & Conditions',
            'content' => '<div class="policy-document"><p class="policy-updated">Last updated: ' . $last_updated . '</p><p class="policy-lede">These terms apply when you use the Loomara Living website, create an account or purchase a product.</p><h2>Using the website</h2><p>You must provide accurate information, use the website lawfully and keep your account credentials confidential. You are responsible for activity under your account and should contact us promptly if you suspect unauthorised access.</p><h2>Products and display</h2><p>We aim to describe products, dimensions, materials and colours accurately. Screen settings, photography and natural textile variation may cause small differences. Product availability can change without notice.</p><h2>Prices, taxes and payment</h2><p>Prices are shown in Indian rupees unless stated otherwise. Applicable taxes, delivery charges and discounts are shown on the product or checkout page. An order is accepted only after payment authorisation and our confirmation. We may cancel and refund an order affected by a pricing error, suspected fraud, stock discrepancy or delivery limitation.</p><h2>Orders and delivery</h2><p>You are responsible for reviewing product, quantity and address details before placing an order. Dispatch and delivery estimates are not guarantees. Our Shipping & Delivery policy forms part of these terms.</p><h2>Returns and refunds</h2><p>Returns, replacements, cancellations and refunds are governed by our Returns & Refunds policy, which forms part of these terms.</p><h2>Intellectual property</h2><p>The Loomara Living name, site design, copy, graphics and original imagery are protected by applicable intellectual-property laws. They may not be reproduced or commercially used without written permission.</p><h2>Prohibited activity</h2><p>You may not interfere with the website, attempt unauthorised access, misuse another person’s account, submit malicious code, scrape content at scale, commit fraud or use the website in violation of law.</p><h2>Third-party services</h2><p>Payments, sign-in, analytics, shipping and other features may be provided by third parties with their own terms and privacy practices. We are not responsible for third-party services outside our reasonable control.</p><h2>Liability</h2><p>To the extent permitted by law, Loomara Living is not liable for indirect or consequential losses arising from website use or product purchase. Nothing in these terms limits rights or remedies that cannot legally be excluded under applicable consumer law.</p><h2>Changes and governing law</h2><p>We may update these terms by posting a revised date. These terms are governed by the laws of India, and disputes are subject to courts with competent jurisdiction under applicable law.</p><h2>Contact</h2><p>Questions about these terms may be sent to ' . $contact . '.</p></div>',
        ],
    ];

    $ids = [];
    foreach ($pages as $slug => $page) {
        $existing = get_page_by_path($slug, OBJECT, 'page');
        if ($existing) {
            // WordPress creates a draft Privacy Policy placeholder during setup.
            // Publish that page with the store-specific policy instead of leaving
            // the footer linked to an unavailable draft.
            if ($slug === 'privacy-policy' && $existing->post_status !== 'publish') {
                wp_update_post([
                    'ID' => $existing->ID,
                    'post_status' => 'publish',
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'comment_status' => 'closed',
                ]);
            }
            $ids[$slug] = (int) $existing->ID;
            continue;
        }

        $page_id = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $page['title'],
            'post_name' => $slug,
            'post_content' => $page['content'],
            'comment_status' => 'closed',
        ]);
        if (!is_wp_error($page_id)) {
            $ids[$slug] = (int) $page_id;
        }
    }

    return $ids;
}

function loomara_create_catalog() {
    $categories = [
        'bedsheets' => ['Bedsheets', 'Breathable foundations in restful colours and easy-care fabrics.'],
        'comforters' => ['Comforters', 'Soft, lofty warmth for deeply comfortable nights.'],
        'combo' => ['Combo', 'Coordinated bedding sets made for effortless layering.'],
        'cushion-covers' => ['Cushion Covers', 'Tactile finishing touches for beds, sofas and reading corners.'],
    ];

    $category_ids = [];
    foreach ($categories as $slug => [$name, $description]) {
        $term = term_exists($slug, 'product_cat');
        if (!$term) {
            $term = wp_insert_term($name, 'product_cat', [
                'slug' => $slug,
                'description' => $description,
            ]);
        }
        if (!is_wp_error($term)) {
            $category_ids[$slug] = (int) (is_array($term) ? $term['term_id'] : $term);
        }
    }

    if (isset($category_ids['bedsheets'])) {
        foreach ([11, 14] as $existing_product_id) {
            if ('product' === get_post_type($existing_product_id)) {
                wp_set_object_terms($existing_product_id, [$category_ids['bedsheets']], 'product_cat', true);
            }
        }
    }

    $products = [
        [
            'sku' => 'LM-COM-001',
            'name' => 'Cloud Rest Comforter — Ivory',
            'slug' => 'cloud-rest-comforter-ivory',
            'category' => 'comforters',
            'price' => '2499',
            'stock' => 12,
            'image' => 'cloud-rest-comforter-ivory.jpg',
            'short' => 'A lofty all-season ivory comforter with softly quilted channels and a smooth cotton-feel shell.',
            'description' => '<p>Designed for cool, cocooning comfort without visual heaviness, the Cloud Rest Comforter brings an easy hotel-like layer to the everyday bedroom.</p><ul><li>Queen-size sample</li><li>Lightweight all-season loft</li><li>Channel quilting helps keep the fill evenly distributed</li><li>Soft ivory tone for effortless layering</li><li>Machine wash gently; dry thoroughly before use</li></ul>',
            'featured' => true,
        ],
        [
            'sku' => 'LM-CMB-001',
            'name' => 'Everyday Calm Bedding Combo — Sage',
            'slug' => 'everyday-calm-bedding-combo-sage',
            'category' => 'combo',
            'price' => '1899',
            'stock' => 15,
            'image' => 'everyday-calm-bedding-combo-sage.jpg',
            'short' => 'A coordinated sage bedsheet set with two matching pillowcases and one contrast cushion cover.',
            'description' => '<p>An instant room refresh in a quiet sage palette. The Everyday Calm Combo is balanced, breathable and simple to style with warm neutrals or deep navy.</p><ul><li>1 queen-size flat bedsheet</li><li>2 matching pillowcases</li><li>1 contrast ivory cushion cover</li><li>Smooth cotton-feel weave</li><li>Machine wash cold with similar colours</li></ul>',
            'featured' => true,
        ],
        [
            'sku' => 'LM-CUS-001',
            'name' => 'Soft Geometry Cushion Cover Pair',
            'slug' => 'soft-geometry-cushion-cover-pair',
            'category' => 'cushion-covers',
            'price' => '799',
            'stock' => 20,
            'image' => 'soft-geometry-cushion-cover-pair.jpg',
            'short' => 'A pair of tactile 45 × 45 cm cushion covers in terracotta and oat with subtle woven geometry.',
            'description' => '<p>A warm finishing layer for the bed or sofa. Soft Geometry combines earthy colour, woven detail and tailored piping for a collected—not overly matched—look.</p><ul><li>Set of 2 covers</li><li>45 × 45 cm</li><li>Textured cotton-linen feel</li><li>Concealed closure and piped edges</li><li>Cushion inserts are not included</li></ul>',
            'featured' => false,
        ],
    ];

    foreach ($products as $data) {
        loomara_create_sample_product($data, $category_ids);
    }
}

function loomara_create_sample_product($data, $category_ids) {
    $existing_id = wc_get_product_id_by_sku($data['sku']);
    if ($existing_id) {
        return $existing_id;
    }
    if (empty($category_ids[$data['category']])) {
        return 0;
    }

    $product = new WC_Product_Simple();
    $product->set_name($data['name']);
    $product->set_slug($data['slug']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_description($data['description']);
    $product->set_short_description($data['short']);
    $product->set_sku($data['sku']);
    $product->set_regular_price($data['price']);
    $product->set_price($data['price']);
    $product->set_manage_stock(true);
    $product->set_stock_quantity((int) $data['stock']);
    $product->set_stock_status('instock');
    $product->set_category_ids([$category_ids[$data['category']]]);
    $product->set_featured((bool) $data['featured']);
    $product_id = $product->save();

    if ($product_id) {
        update_post_meta($product_id, '_loomara_sample_product', 'yes');
        $image_id = loomara_import_product_image($data['image'], $data['name']);
        if ($image_id) {
            $product->set_image_id($image_id);
            $product->save();
        }
    }

    return $product_id;
}

function loomara_import_product_image($filename, $title) {
    $source = trailingslashit(get_template_directory()) . 'assets/products/' . basename($filename);
    if (!file_exists($source)) {
        return 0;
    }

    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'meta_key' => '_loomara_source_asset',
        'meta_value' => basename($filename),
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);
    if ($existing) {
        return (int) $existing[0];
    }

    $upload = wp_upload_bits(basename($filename), null, file_get_contents($source));
    if (!empty($upload['error'])) {
        return 0;
    }

    $filetype = wp_check_filetype($upload['file']);
    $attachment_id = wp_insert_attachment([
        'post_mime_type' => $filetype['type'],
        'post_title' => $title,
        'post_content' => '',
        'post_status' => 'inherit',
    ], $upload['file']);

    if (is_wp_error($attachment_id)) {
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $metadata);
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $title);
    update_post_meta($attachment_id, '_loomara_source_asset', basename($filename));

    return (int) $attachment_id;
}

function loomara_account_intro() {
    if (is_user_logged_in()) {
        return;
    }
    echo '<div class="account-welcome"><p class="eyebrow">Your Loomara account</p><h2>Welcome back—or make yourself at home.</h2><p>Sign in to view orders and addresses, or create an account in a few moments.</p>';
    if (shortcode_exists('nextend_social_login')) {
        echo '<div class="social-login-wrap"><span>Continue securely with</span>';
        echo do_shortcode('[nextend_social_login provider="google" redirect="' . esc_url(wc_get_page_permalink('myaccount')) . '"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
    echo '</div>';
}
add_action('woocommerce_before_customer_login_form', 'loomara_account_intro', 5);

