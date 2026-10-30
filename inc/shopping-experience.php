<?php
/** Discovery and genuine, moderated customer ratings. */
defined('ABSPATH') || exit;

// Restyle only WooCommerce's account ownership notice. Keep the exact native
// nonce-protected action link and all verification behaviour unchanged.
add_filter('woocommerce_add_notice', function ($message) {
    if (!function_exists('is_account_page') || !is_account_page()
        || strpos($message, 'Confirm your email address to check for past orders and link them to your account.') === false) { return $message; }
    if (!preg_match('/<a\b[^>]*>\s*Confirm email address\s*<\/a>/i', $message, $match)) { return $message; }
    return '<div class="loomara-email-confirm">' . $match[0] . '<details class="loomara-email-help"><summary>Why confirm?</summary><p>Confirm your email address to securely link past orders to your account. We will send a confirmation link to your inbox.</p></details></div>';
});

// Append to configured menus as well as the theme fallback; never duplicate Sale.
function loomara_sale_navigation($items) {
    if (!class_exists('WooCommerce') || strpos($items, 'collection=sale') !== false) { return $items; }
    return $items . '<a class="nav-sale" href="' . esc_url(add_query_arg('collection', 'sale', wc_get_page_permalink('shop'))) . '">Sale <span aria-hidden="true">✦</span></a>';
}
add_filter('wp_nav_menu_items', function ($items, $args) {
    return $args->theme_location === 'primary' ? loomara_sale_navigation($items) : $items;
}, 20, 2);

// These filters are reversible with a theme rollback. Archived admin reviews
// remain on hold: enabling new reviews must never republish old samples.
foreach (['woocommerce_enable_reviews', 'woocommerce_enable_review_rating', 'woocommerce_review_rating_required'] as $option) {
    add_filter('pre_option_' . $option, function () { return 'yes'; });
}
add_filter('woocommerce_product_get_reviews_allowed', '__return_true');
add_filter('comments_open', function ($open, $id) { return get_post_type($id) === 'product' ? true : $open; }, 20, 2);
add_action('comment_form', function ($post_id) {
    if (get_post_type($post_id) === 'product') { wp_nonce_field('loomara_review_' . $post_id, 'loomara_review_nonce'); }
});
add_filter('preprocess_comment', function ($data) {
    $id = absint($data['comment_post_ID'] ?? 0);
    if (get_post_type($id) !== 'product') { return $data; }
    $nonce = sanitize_text_field(wp_unslash($_POST['loomara_review_nonce'] ?? ''));
    $rating = wp_unslash($_POST['rating'] ?? '');
    if (!is_user_logged_in() || !wp_verify_nonce($nonce, 'loomara_review_' . $id)) {
        wp_die('Please sign in and refresh the product page before submitting a review.', '', ['response' => 403]);
    }
    if (!is_scalar($rating) || !preg_match('/^[1-5]$/', (string) $rating)) {
        wp_die('Please choose a rating from 1 to 5 stars.', '', ['response' => 400]);
    }
    if (!loomara_can_rate_product($id)) { wp_die('Only customers with a completed, paid purchase of this item may rate it.', '', ['response'=>403]); }
    if (get_comments(['post_id' => $id, 'user_id' => get_current_user_id(), 'status' => 'all', 'type' => 'review', 'number' => 1])) {
        wp_die('You have already reviewed this piece. Contact support if you would like to update your review.', '', ['response' => 400]);
    }
    $data['user_id'] = get_current_user_id();
    $data['comment_author_email'] = wp_get_current_user()->user_email;
    return $data;
});
add_filter('pre_comment_approved', function ($approved, $data) {
    return get_post_type(absint($data['comment_post_ID'] ?? 0)) === 'product' && !is_wp_error($approved) ? 0 : $approved;
}, 99, 2);
add_filter('woocommerce_product_review_comment_form_args', function ($args) {
    global $product;
    if (!is_user_logged_in() || !$product || !loomara_can_rate_product($product->get_id())) {
        $args['comment_notes_before'] = '<p>Ratings are available after a completed, paid purchase of this piece. <a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">View your account</a>.</p>';
        $args['must_log_in'] = '<p>Please <a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">sign in</a> to rate this piece.</p>';
        $args['comment_field'] = '';
        $args['fields'] = [];
        $args['submit_button'] = '';
    } else {
        $args['comment_notes_before'] = '<p>Your honest experience helps another home. Reviews appear after moderation.</p>';
    }
    return $args;
});

function loomara_product_rating() {
    global $product;
    if (!$product instanceof WC_Product) { return; }
    $rating = (float) $product->get_average_rating();
    $count = $product->get_rating_count();
    // Native aggregates contain approved ratings only; never render an empty star row.
    if ($count < 1 || $rating <= 0) { return; }
    $label = $count ? sprintf('%.1f out of 5, %d ratings', $rating, $count) : 'No ratings yet';
    echo '<span class="loomara-rating" role="img" aria-label="' . esc_attr($label) . '"><span class="rating-stars" aria-hidden="true"><span style="width:' . esc_attr(min(100, max(0, $rating * 20))) . '%">★★★★★</span>☆☆☆☆☆</span><span class="rating-caption">' . esc_html($count ? number_format_i18n($rating, 1) . ' (' . $count . ')' : 'Be the first to rate') . '</span></span>';
}
add_action('init', function () {
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
    add_action('woocommerce_after_shop_loop_item_title', 'loomara_product_rating', 5);
});

function loomara_top_rated_ids() {
    // Named clauses make ordering deterministic before LIMIT, not after it.
    $query = new WP_Query([
        'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 8, 'fields' => 'ids',
        'no_found_rows' => true, 'tax_query' => WC()->query->get_tax_query(),
        'meta_query' => [
            'rating' => ['key' => '_wc_average_rating', 'value' => 4, 'compare' => '>=', 'type' => 'DECIMAL(4,2)'],
            'reviews' => ['key' => '_wc_review_count', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC'],
        ],
        'orderby' => ['reviews' => 'DESC', 'rating' => 'DESC', 'date' => 'DESC', 'ID' => 'DESC'],
    ]);
    return $query->posts;
}
