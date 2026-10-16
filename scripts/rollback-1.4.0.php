<?php
/** Run only via wp eval-file after redeploying the previous theme. */
if (!defined('WP_CLI') || !WP_CLI) {
    http_response_code(403);
    exit;
}
foreach ((array) get_option('loomara_before_1_4_0_options', []) as $key => $value) {
    if ($value === false) {
        delete_option($key);
    } else {
        update_option($key, $value);
    }
}
$pages = get_posts(['post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => -1, 'meta_key' => '_loomara_policy_before_1_4_0']);
foreach ($pages as $page) {
    wp_update_post(['ID' => $page->ID, 'post_content' => get_post_meta($page->ID, '_loomara_policy_before_1_4_0', true)]);
}
$reviews = get_comments(['status' => 'all', 'number' => 0, 'meta_key' => '_loomara_previous_status_1_4_0']);
foreach ($reviews as $review) {
    wp_set_comment_status($review->comment_ID, get_comment_meta($review->comment_ID, '_loomara_previous_status_1_4_0', true) === '1' ? 'approve' : 'hold');
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients($review->comment_post_ID);
    }
}
delete_option('loomara_experience_version');
flush_rewrite_rules(false);
WP_CLI::success('Restored the saved pre-v1.4.0 options, policy content and review statuses.');
