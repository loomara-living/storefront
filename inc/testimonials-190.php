<?php
/** Genuine opt-in customer testimonials; always moderated before publication. */
defined('ABSPATH') || exit;
add_action('init',function(){register_post_type('loomara_testimonial',['labels'=>['name'=>'Customer testimonials','singular_name'=>'Customer testimonial'],'public'=>false,'show_ui'=>true,'show_in_menu'=>true,'show_in_rest'=>false,'supports'=>['title','editor','author'],'capability_type'=>'post','map_meta_cap'=>true,'menu_icon'=>'dashicons-format-quote']);});
function loomara_testimonial_eligible($id = 0) {
    $id=$id?:get_current_user_id();if(!$id)return false;
    foreach(wc_get_orders(['customer_id'=>$id,'status'=>wc_get_is_paid_statuses(),'limit'=>20,'orderby'=>'date','order'=>'DESC']) as $order){if($order->is_paid())return true;}
    return false;
}
function loomara_testimonial_prompt($context='home') {
    if(is_user_logged_in() && get_posts(['post_type'=>'loomara_testimonial','post_status'=>'pending','author'=>get_current_user_id(),'numberposts'=>1,'fields'=>'ids'])) {
        echo '<div class="testimonial-submission-status" role="status"><strong>Your story is safely with us.</strong><p>Your testimonial is awaiting approval. Once published, it can appear among the five latest customer stories.</p></div>';
        return;
    }
    echo '<div class="testimonial-prompt"><details><summary>Leave a testimonial</summary><p>Tell us how Loomara feels in your home. Your honest words can help another home find its next layer.</p>';
    if(!is_user_logged_in()){echo '<p><a class="button" href="'.esc_url(wc_get_page_permalink('myaccount')).'">Sign in to share</a></p>';}
    elseif(!loomara_testimonial_eligible()){echo '<p>Testimonials open after a paid purchase. We look forward to hearing your experience.</p>';}
    else{
        echo '<form class="testimonial-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_submit_testimonial">';wp_nonce_field('loomara_submit_testimonial');
        echo '<label>Public name<input type="text" name="public_name" maxlength="50" required autocomplete="given-name" value="'.esc_attr(wp_get_current_user()->first_name).'"></label><label>Your experience<textarea name="testimonial" rows="4" minlength="30" maxlength="800" required></textarea></label><label class="testimonial-consent"><input type="checkbox" name="publish_consent" value="1" required> I agree that my testimonial and the public name above may appear on this website after moderation.</label><p>Please do not include order numbers, addresses, phone numbers or other private information.</p><button class="button" type="submit">Submit for review</button></form>';
    }
    echo '</details></div>';
}
function loomara_save_customer_testimonial($raw) {
    if(!is_user_logged_in()||!loomara_testimonial_eligible())return new WP_Error('ineligible','A paid purchase is required to share a customer testimonial.');
    $name=is_string($raw['public_name']??null)?sanitize_text_field($raw['public_name']):'';
    $text=is_string($raw['testimonial']??null)?sanitize_textarea_field($raw['testimonial']):'';
    if(empty($raw['publish_consent'])||mb_strlen($name)<1||mb_strlen($name)>50||mb_strlen($text)<30||mb_strlen($text)>800)return new WP_Error('invalid','Enter a public name and 30–800 characters, and confirm your publication consent.');
    $user=get_current_user_id();
    if(!add_option('loomara_testimonial_lock_'.$user,time(),' ',false))return new WP_Error('busy','A submission is already being processed. Please try later.');
    $error='';
    try{
        if(get_posts(['post_type'=>'loomara_testimonial','post_status'=>['publish','pending','draft'],'author'=>$user,'date_query'=>[['after'=>'30 days ago']],'numberposts'=>1])){$error='Thank you. You already have a recent submission; please allow time for review.';}
        else{$id=wp_insert_post(['post_type'=>'loomara_testimonial','post_status'=>'pending','post_title'=>$name,'post_content'=>$text,'post_author'=>$user,'comment_status'=>'closed','meta_input'=>['_loomara_publication_consent'=>time()]],true);if(is_wp_error($id))$error='Unable to save your testimonial. Please try again.';}
    }finally{delete_option('loomara_testimonial_lock_'.$user);}
    return $error?new WP_Error('submission',$error):$id;
}
add_action('admin_post_loomara_submit_testimonial',function(){
    if(!is_user_logged_in()){auth_redirect();}check_admin_referer('loomara_submit_testimonial');
    $result=loomara_save_customer_testimonial(wp_unslash($_POST));
    if(is_wp_error($result))wp_die(esc_html($result->get_error_message()),'',['response'=>400]);
    wp_safe_redirect(add_query_arg('testimonial','submitted',wc_get_account_endpoint_url('orders')));exit;
});
function loomara_customer_testimonials() {
    $quotes=get_posts(['post_type'=>'loomara_testimonial','post_status'=>'publish','numberposts'=>5,'orderby'=>'date','order'=>'DESC']);
    echo '<section class="testimonials-190" aria-labelledby="customer-testimonial-title" data-customer-testimonials><p class="eyebrow">At home with Loomara</p><h2 id="customer-testimonial-title">Stories from softer spaces.</h2>';
    if(!$quotes){echo '<div class="testimonials-empty"><p>Your everyday rituals are the heart of our story. Be among the first to tell us how a little more comfort feels.</p>';loomara_testimonial_prompt();echo '</div>';}
    else{echo '<p>A few honest words from the homes we’re grateful to be part of.</p><div class="testimonial-rail" aria-label="Latest customer testimonials" aria-roledescription="carousel">';foreach($quotes as $quote){echo '<figure><blockquote>'.esc_html($quote->post_content).'</blockquote><figcaption>'.esc_html($quote->post_title).' · Loomara customer</figcaption></figure>';}echo '</div>';if(count($quotes)>1)echo '<div class="testimonial-controls"><button type="button" class="button" data-testimonial-previous aria-label="Previous testimonial">←</button><span data-testimonial-position aria-live="off">1 / '.count($quotes).'</span><button type="button" class="button" data-testimonial-next aria-label="Next testimonial">→</button><button type="button" class="button testimonial-pause" data-pause-customer-testimonials>Pause testimonials</button></div>';loomara_testimonial_prompt();}
    echo '</section>';
}
