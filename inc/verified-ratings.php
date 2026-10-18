<?php
/** Only fulfilled, paid, unrefunded purchases qualify for a new rating. */
defined('ABSPATH') || exit;
function loomara_can_rate_product($id) {
    if (!is_user_logged_in()) { return false; }
    static $by_user=[];$uid=get_current_user_id();
    if (!isset($by_user[$uid])) {
        $by_user[$uid]=[];
        foreach (wc_get_orders(['customer_id'=>get_current_user_id(),'status'=>['completed'],'limit'=>-1]) as $order) {
            if (!$order->is_paid()) { continue; }
            foreach ($order->get_items() as $item_id=>$item) {
                if ($item->get_quantity() + $order->get_qty_refunded_for_item($item_id) > 0) { $by_user[$uid][(int)$item->get_product_id()]=true; }
            }
        }
    }
    return !empty($by_user[$uid][absint($id)]);
}
function loomara_existing_review($id) {
    return (bool)get_comments(['post_id'=>$id,'user_id'=>get_current_user_id(),'status'=>'all','type'=>'review','number'=>1]);
}
add_action('woocommerce_after_shop_loop_item', function () {
    global $product;
    if (!$product || !loomara_can_rate_product($product->get_id()) || loomara_existing_review($product->get_id())) { return; }
    echo '<div class="quick-rating"><button type="button" data-open-rating aria-label="Rate '.esc_attr($product->get_name()).'">☆ Rate your purchase</button><dialog class="quick-rating-dialog"><form action="'.esc_url(admin_url('admin-ajax.php')).'" method="post" data-quick-rating><h3>How is your Loomara piece?</h3><p>'.esc_html($product->get_name()).'</p><input type="hidden" name="action" value="loomara_quick_rating"><input type="hidden" name="product_id" value="'.absint($product->get_id()).'">';
    wp_nonce_field('loomara_quick_rating_'.$product->get_id(),'rating_nonce');
    echo '<label>Your rating<select name="rating" required><option value="">Choose stars</option>';
    for($i=1;$i<=5;$i++){echo '<option value="'.$i.'">'.$i.' '.str_repeat('★',$i).'</option>';}
    echo '</select></label><div class="rating-choice-buttons" aria-label="Choose your star rating">';
    for($i=1;$i<=5;$i++){echo '<button type="button" data-rating-value="'.$i.'" aria-label="'.$i.' stars">★</button>';}
    echo '</div><label>Your experience (optional)<textarea name="review" maxlength="3000" rows="3"></textarea></label><p role="status" data-rating-feedback></p><button class="button" type="submit">Submit rating</button><button type="button" data-close-rating>Cancel</button><p>Your rating appears after moderation.</p></form></dialog></div>';
}, 25);
add_action('wp_ajax_loomara_quick_rating', function () {
    $id=absint($_POST['product_id']??0); check_ajax_referer('loomara_quick_rating_'.$id,'rating_nonce');
    $product=wc_get_product($id);$raw_rating=$_POST['rating']??'';$rating=is_scalar($raw_rating)&&preg_match('/^[1-5]$/',(string)$raw_rating)?(int)$raw_rating:0;
    if (!$product || $product->get_status()!=='publish' || !loomara_can_rate_product($id)) {wp_send_json_error(['message'=>'Ratings are available after a completed, paid purchase of this piece.'],403);}
    if($rating<1||$rating>5){wp_send_json_error(['message'=>'Choose 1–5 stars.'],400);}
    // Serialize duplicate submissions for this user/product, without holding a transaction across email hooks.
    $lock='loomara_rating_'.get_current_user_id().'_'.$id;
    if(!add_option($lock,time(),' ',false)){wp_send_json_error(['message'=>'A rating is already being submitted. Please refresh.'],409);}
    $duplicate=false;$comment=0;
    try{
        if(loomara_existing_review($id)){$duplicate=true;}
        else {
        $user=wp_get_current_user();
        $review=is_scalar($_POST['review']??'')?mb_substr(sanitize_textarea_field(wp_unslash($_POST['review']??'')),0,3000):'';
        $comment=wp_insert_comment(['comment_post_ID'=>$id,'user_id'=>$user->ID,'comment_author'=>$user->display_name,'comment_author_email'=>$user->user_email,'comment_content'=>$review,'comment_type'=>'review','comment_approved'=>0,'comment_meta'=>['rating'=>$rating,'verified'=>1]]);
        }
    }finally{delete_option($lock);}
    if($duplicate){wp_send_json_error(['message'=>'You have already reviewed this piece.'],409);}
    if(!$comment){wp_send_json_error(['message'=>'Unable to save your rating. Please try again.'],500);}
    wp_send_json_success(['message'=>'Thank you! Your rating is awaiting moderation.']);
});
