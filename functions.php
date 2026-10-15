<?php

if (!defined('ABSPATH')) {
    exit;
}

function loomara_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus([
        'primary' => __('Primary navigation', 'loomara-living'),
    ]);
}
add_action('after_setup_theme', 'loomara_setup');

function loomara_assets() {
    wp_enqueue_style(
        'loomara-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap',
        [],
        null
    );
    wp_enqueue_style('loomara-theme', get_template_directory_uri() . '/assets/loomara.css', ['loomara-fonts'], '1.0.0');
    if (class_exists('WooCommerce')) {
        wp_enqueue_style('loomara-woocommerce', get_template_directory_uri() . '/assets/woocommerce.css', ['loomara-theme'], '1.0.0');
    }
    wp_enqueue_script('loomara-theme', get_template_directory_uri() . '/assets/loomara.js', [], '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'loomara_assets');

function loomara_menu_fallback() {
    echo '<a href="' . esc_url(home_url('/')) . '">Home</a>';
    if (class_exists('WooCommerce')) {
        echo '<a href="' . esc_url(wc_get_page_permalink('shop')) . '">Shop</a>';
    }
    echo '<a href="' . esc_url(home_url('/our-story/')) . '">Our story</a>';
    echo '<a href="' . esc_url(home_url('/contact/')) . '">Contact</a>';
}

function loomara_body_classes($classes) {
    $classes[] = 'loomara-wordpress';
    return $classes;
}
add_filter('body_class', 'loomara_body_classes');

/**
 * One-time WooCommerce inventory import. This temporary routine is removed
 * after the products and their media have been created successfully.
 */
function loomara_seed_initial_inventory() {
    if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple')) {
        return;
    }

    $seed_key = 'loomara_inventory_seed_20261015';
    if (get_option($seed_key)) {
        return;
    }

    $products = [
        [
            'sku' => 'LL-FB-IVORY-001',
            'name' => 'Feather Botanical Blanket — Ivory Sand',
            'short_description' => 'A softly neutral statement blanket with an intricate botanical motif in warm ivory and sand tones.',
            'description' => '<p>Designed to bring a calm, layered finish to the bedroom, the Feather Botanical Blanket in Ivory Sand pairs a warm neutral ground with an intricate tonal leaf motif.</p><p>Its understated palette makes it easy to style across modern, minimal and warm contemporary interiors. Drape it across the bed as a generous finishing layer whenever the room calls for a little more softness and visual texture.</p><ul><li>Warm ivory and sand colour palette</li><li>Detailed all-over botanical design</li><li>Elegant scalloped-look border</li><li>A versatile finishing layer for restful bedrooms</li></ul>',
            'image' => 'assets/inventory/feather-botanical-blanket-ivory-sand.jpeg',
            'image_alt' => 'Feather Botanical Blanket in Ivory Sand styled on a bed',
        ],
        [
            'sku' => 'LL-FB-ROSE-001',
            'name' => 'Feather Botanical Blanket — Dusty Rose',
            'short_description' => 'A graceful botanical blanket in muted dusty rose and taupe, designed for an inviting layered bed.',
            'description' => '<p>The Feather Botanical Blanket in Dusty Rose brings gentle colour and intricate pattern to a restful bedroom. A muted rose border frames the tonal botanical artwork, creating a look that feels expressive without overwhelming the space.</p><p>Style it as the final layer over crisp white bedding or pair it with earthy neutrals for a warm, collected mood.</p><ul><li>Muted dusty rose and taupe colour palette</li><li>Detailed all-over botanical design</li><li>Elegant scalloped-look border</li><li>A warm finishing layer for relaxed interiors</li></ul>',
            'image' => 'assets/inventory/feather-botanical-blanket-dusty-rose.jpeg',
            'image_alt' => 'Feather Botanical Blanket in Dusty Rose styled on a bed',
        ],
    ];

    $result = [];
    foreach ($products as $data) {
        $product_id = wc_get_product_id_by_sku($data['sku']);
        $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();

        if (!$product) {
            continue;
        }

        $product->set_name($data['name']);
        $product->set_sku($data['sku']);
        $product->set_status('draft');
        $product->set_catalog_visibility('visible');
        $product->set_short_description($data['short_description']);
        $product->set_description($data['description']);
        $product->set_manage_stock(true);
        $product->set_stock_quantity(10);
        $product->set_stock_status('instock');
        $product->set_backorders('no');
        $product_id = $product->save();

        if ($product_id && !$product->get_image_id()) {
            $source = get_template_directory() . '/' . $data['image'];
            if (is_readable($source)) {
                $upload = wp_upload_bits(basename($source), null, file_get_contents($source));
                if (empty($upload['error'])) {
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $filetype = wp_check_filetype($upload['file']);
                    $attachment_id = wp_insert_attachment([
                        'post_mime_type' => $filetype['type'],
                        'post_title' => $data['name'],
                        'post_content' => '',
                        'post_status' => 'inherit',
                    ], $upload['file'], $product_id);
                    if (!is_wp_error($attachment_id)) {
                        wp_update_attachment_metadata(
                            $attachment_id,
                            wp_generate_attachment_metadata($attachment_id, $upload['file'])
                        );
                        update_post_meta($attachment_id, '_wp_attachment_image_alt', $data['image_alt']);
                        $product->set_image_id($attachment_id);
                        $product->save();
                    }
                }
            }
        }

        $result[] = [
            'id' => $product_id,
            'sku' => $product->get_sku(),
            'name' => $product->get_name(),
            'status' => $product->get_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'image_id' => $product->get_image_id(),
        ];
    }

    if (count($result) === count($products)) {
        update_option($seed_key, $result, false);
    }
}
add_action('init', 'loomara_seed_initial_inventory', 20);

function loomara_register_inventory_seed_status() {
    register_rest_route('loomara/v1', '/inventory-seed-status', [
        'methods' => 'GET',
        'callback' => function () {
            return rest_ensure_response(get_option('loomara_inventory_seed_20261015', []));
        },
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'loomara_register_inventory_seed_status');

