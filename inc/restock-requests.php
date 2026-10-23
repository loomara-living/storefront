<?php
/** Private demand tracking. Never changes stock or promises restock notifications. */
defined('ABSPATH') || exit;
function loomara_stock_customer_text($text, $product) {
    return !$product->is_in_stock() ? 'Out of stock' : ($product->is_on_backorder(1) ? 'Available on backorder' : 'In stock');
}
add_filter('woocommerce_get_availability_text', 'loomara_stock_customer_text', 99, 2);
add_filter('woocommerce_get_stock_html', function($html, $product) {
    return '<p class="stock '.(!$product->is_in_stock() ? 'out-of-stock' : 'in-stock').'">'.esc_html(loomara_stock_customer_text('', $product)).'</p>';
}, 99, 2);
function loomara_restock_badge() {
    global $product;
    if ($product instanceof WC_Product && !$product->is_in_stock()) {
        echo '<span class="loomara-stock-badge">Out of stock</span>';
    }
}
add_action('woocommerce_before_shop_loop_item_title', 'loomara_restock_badge', 11);
function loomara_restock_form() {
    global $product;
    if (!($product instanceof WC_Product)) { return; }
    echo '<div class="loomara-restock" data-restock-parent="'.absint($product->get_id()).'" data-restock-initial="'.($product->is_in_stock()?'0':'1').'"'.($product->is_in_stock()?' hidden':'').'>';
    echo '<p>Interested in this piece? Let Loomara know you’d like it back.</p>';
    if (!is_user_logged_in()) {
        echo '<a class="button" href="'.esc_url(add_query_arg('redirect_to',get_permalink($product->get_id()),wc_get_page_permalink('myaccount'))).'">Sign in to request restock</a>';
    } else {
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_restock_request"><input type="hidden" name="product_id" value="'.absint($product->get_id()).'">';
        wp_nonce_field('loomara_restock_request');
        echo '<button class="button" type="submit">Request restock</button></form>';
    }
    echo '<p class="loomara-restock-feedback" role="status"></p></div>';
}
add_action('woocommerce_single_product_summary','loomara_restock_form',31);
function loomara_restock_record($id, $uid) {
    $p=wc_get_product($id);
    $parent=$p && $p->is_type('variation') ? wc_get_product($p->get_parent_id()) : $p;
    if (!$uid || !$p || !$parent || $parent->get_status()!=='publish' || !$parent->is_visible() || $p->is_in_stock() || ($p->is_type('variation') && $p->get_status()!=='publish')) { return new WP_Error('unavailable','This item is not available for a restock request.'); }
    $key='loomara_restock_'.$id.'_'.$uid;
    // A permanent unique request per customer/SKU prevents double counts and duplicate emails.
    if (add_option($key,time(),'','no')) {
        if (!add_post_meta($id,'_loomara_restock_request_user',$uid,false)) { delete_option($key); return new WP_Error('save','Could not save your request. Please try again.'); }
        $settings=get_option('woocommerce_new_order_settings',[]);
        $recipient=!empty($settings['recipient'])?$settings['recipient']:get_option('admin_email');
        $sent=wp_mail($recipient,'Restock requested: '.($p->get_sku()?:'#'.$id),
            'A signed-in customer requested this product: '.$p->get_name()."\nSKU: ".($p->get_sku()?:'#'.$id)."\n".get_permalink($parent->get_id())."\nView request totals in WooCommerce → Loomara Settings.");
        update_post_meta($id,'_loomara_restock_last_email_status',$sent?'accepted':'failed');
    }
    return $parent->get_id();
}
add_action('admin_post_loomara_restock_request', function() {
    if (!is_user_logged_in()) { wp_die('Please sign in.'); }
    check_admin_referer('loomara_restock_request');
    $parent_id=loomara_restock_record(absint($_POST['product_id']??0),get_current_user_id());
    if (is_wp_error($parent_id)) { wp_die(esc_html($parent_id->get_error_message())); }
    wp_safe_redirect(add_query_arg('restock_requested','1',get_permalink($parent_id))); exit;
});
add_action('woocommerce_before_single_product', function() {
    if (isset($_GET['restock_requested']) && is_user_logged_in()) { wc_print_notice('Your restock request has been recorded. Thank you for letting us know.','success'); }
});
function loomara_restock_settings() {
    if (!current_user_can('manage_options')) { return; }
    $page=max(1,absint($_GET['restock_page']??1));
    $query=new WP_Query(['post_type'=>['product','product_variation'],'post_status'=>['publish','private','draft'],'posts_per_page'=>25,'paged'=>$page,'fields'=>'ids','meta_query'=>[['key'=>'_loomara_restock_request_user','compare'=>'EXISTS']]]);
    echo '<section><h2>Restock requests</h2><p>Unique signed-in customer requests per product/SKU. No automatic customer restock email is promised. Email status means transport acceptance, not inbox delivery.</p><table class="widefat striped"><thead><tr><th>Product / SKU</th><th>Requests</th><th>Last team email</th></tr></thead><tbody>';
    foreach($query->posts as $id) {
        $p=wc_get_product($id); if (!$p) { continue; }
        $users=array_unique(get_post_meta($p->get_id(),'_loomara_restock_request_user',false));
        echo '<tr><td>'.esc_html($p->get_name().' / '.($p->get_sku()?:'#'.$p->get_id())).'</td><td>'.absint(count($users)).'</td><td>'.esc_html($p->get_meta('_loomara_restock_last_email_status')).'</td></tr>';
    }
    if (!$query->posts) { echo '<tr><td colspan="3">No restock requests yet.</td></tr>'; }
    echo '</tbody></table>';
    for($i=max(1,$page-2);$i<=min($query->max_num_pages,$page+2);$i++) { echo '<a class="button" href="'.esc_url(add_query_arg(['page'=>'loomara-settings','restock_page'=>$i],admin_url('admin.php'))).'">'.absint($i).'</a> '; }
    echo '</section>';
}
add_action('wp_enqueue_scripts',function() {
    wp_enqueue_style('loomara-restock',get_template_directory_uri().'/assets/restock.css',[],'1');
    if (is_product()) { wp_enqueue_script('loomara-restock',get_template_directory_uri().'/assets/restock.js',['jquery','wc-add-to-cart-variation'],'1',true); }
});
