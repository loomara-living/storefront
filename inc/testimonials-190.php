<?php
/** Genuine opt-in customer testimonials; always moderated before publication. */
defined('ABSPATH') || exit;
add_action('init',function(){register_post_type('loomara_testimonial',['labels'=>['name'=>'Customer testimonials','singular_name'=>'Customer testimonial'],'public'=>false,'show_ui'=>true,'show_in_menu'=>true,'show_in_rest'=>false,'supports'=>['title','editor','author'],'capability_type'=>'post','map_meta_cap'=>true,'menu_icon'=>'dashicons-format-quote']);});
function loomara_testimonial_eligible($id = 0) {
    $id=$id?:get_current_user_id();if(!$id)return false;
    $statuses=function_exists('loomara_purchase_statuses')?loomara_purchase_statuses():wc_get_is_paid_statuses();
    foreach(wc_get_orders(['customer_id'=>$id,'status'=>$statuses,'limit'=>20,'orderby'=>'date','order'=>'DESC']) as $order){if($order->is_paid())return true;}
    return false;
}
function loomara_customer_testimonial($user){
    $posts=get_posts(['post_type'=>'loomara_testimonial','post_status'=>['publish','pending','draft','private','future','trash'],'author'=>$user,'numberposts'=>1,'orderby'=>'ID','order'=>'ASC']);
    return $posts?$posts[0]:null;
}
function loomara_testimonial_city_default(){
    // A form suggestion only. Never publish a private saved city without consent.
    if(!is_user_logged_in())return '';
    if(function_exists('loomara_saved_addresses') && function_exists('loomara_default_address_id')){
        $addresses=loomara_saved_addresses();
        $default=loomara_default_address_id($addresses);
        if(isset($addresses[$default]))return sanitize_text_field((string)($addresses[$default]['city']??''));
    }
    return sanitize_text_field((string)get_user_meta(get_current_user_id(),'shipping_city',true));
}
function loomara_testimonial_prompt($context='home') {
    $existing=is_user_logged_in()?loomara_customer_testimonial(get_current_user_id()):null;
    if($existing && $existing->post_status==='trash'){echo '<p>Your testimonial is archived. Please contact us if you would like to restore it.</p>';return;}
    if($existing && $existing->post_status==='pending'){echo '<div class="testimonial-submission-status" role="status"><strong>Your story is safely with us.</strong><p>Your testimonial is awaiting approval. You can edit it below; changes will also be reviewed.</p></div>';}
    echo '<div class="testimonial-prompt"><details><summary>'.($existing?'Edit your testimonial':'Leave a testimonial').'</summary><p>Tell us how Loomara feels in your home. Your honest words can help another home find its next layer.</p>';
    if(!is_user_logged_in()){echo '<p><a class="button" href="'.esc_url(wc_get_page_permalink('myaccount')).'">Sign in to share</a></p>';}
    elseif(!$existing&&!loomara_testimonial_eligible()){echo '<p>Testimonials open after a paid purchase. We look forward to hearing your experience.</p>';}
    else{
        echo '<form class="testimonial-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_submit_testimonial">';wp_nonce_field('loomara_submit_testimonial');
        $city=$existing?(string)get_post_meta($existing->ID,'_loomara_public_city',true):loomara_testimonial_city_default();
        if($existing){echo '<input type="hidden" name="testimonial_id" value="'.absint($existing->ID).'">';}
        echo '<label>Public name<input type="text" name="public_name" maxlength="50" required autocomplete="given-name" value="'.esc_attr($existing?$existing->post_title:wp_get_current_user()->first_name).'"></label><label>Public city (optional)<input type="text" name="public_city" maxlength="60" autocomplete="address-level2" value="'.esc_attr($city).'"></label><label>Your experience<textarea name="testimonial" rows="4" minlength="30" maxlength="800" required>'.esc_textarea($existing?$existing->post_content:'').'</textarea></label><label class="testimonial-consent"><input type="checkbox" name="publish_consent" value="1" required> I agree that my testimonial, public name and city entered above may appear on this website after moderation.</label><p>Please do not include order numbers, street addresses, phone numbers or other private information. One testimonial per customer; you can edit yours anytime. Edits are reviewed before publication.</p><button class="button" type="submit">'.($existing?'Save changes for review':'Submit for review').'</button></form>';
    }
    echo '</details></div>';
}
function loomara_save_customer_testimonial($raw) {
    if(!is_user_logged_in())return new WP_Error('ineligible','Please sign in to manage your testimonial.');
    $name=is_string($raw['public_name']??null)?sanitize_text_field($raw['public_name']):'';
    $text=is_string($raw['testimonial']??null)?sanitize_textarea_field($raw['testimonial']):'';
    $city=is_string($raw['public_city']??null)?sanitize_text_field($raw['public_city']):'';
    if(empty($raw['publish_consent'])||mb_strlen($name)<1||mb_strlen($name)>50||mb_strlen($city)>60||mb_strlen($text)<30||mb_strlen($text)>800)return new WP_Error('invalid','Enter a public name, a city up to 60 characters and 30–800 characters of feedback, and confirm your publication consent.');
    $user=get_current_user_id();
    if(!add_option('loomara_testimonial_lock_'.$user,time(),' ',false))return new WP_Error('busy','A submission is already being processed. Please try later.');
    $error='';
    try{
        $existing=loomara_customer_testimonial($user);
        $edit=is_scalar($raw['testimonial_id']??null)?absint($raw['testimonial_id']):0;
        if($existing && ($edit!==(int)$existing->ID || $existing->post_status==='trash')){$error='You already have a testimonial. Please use Edit your testimonial instead of submitting another.';}
        elseif(!$existing && $edit){$error='This testimonial is not available to edit.';}
        elseif(!$existing && !loomara_testimonial_eligible()){$error='A paid purchase is required to share a customer testimonial.';}
        else{
            $data=['post_type'=>'loomara_testimonial','post_status'=>'pending','post_title'=>$name,'post_content'=>$text,'post_author'=>$user,'comment_status'=>'closed','meta_input'=>['_loomara_publication_consent'=>time(),'_loomara_public_city'=>$city]];
            if($existing){$data['ID']=$existing->ID;}
            $id=$existing?wp_update_post($data,true):wp_insert_post($data,true);
            if(is_wp_error($id)||!$id){$error='Unable to save your testimonial. Please try again.';}
        }
    }finally{delete_option('loomara_testimonial_lock_'.$user);}
    return $error?new WP_Error('submission',$error):$id;
}
add_action('admin_post_loomara_submit_testimonial',function(){
    if(!is_user_logged_in()){auth_redirect();}check_admin_referer('loomara_submit_testimonial');
    $result=loomara_save_customer_testimonial(wp_unslash($_POST));
    if(is_wp_error($result))wp_die(esc_html($result->get_error_message()),'',['response'=>400]);
    wp_safe_redirect(loomara_testimonial_return_url());exit;
});
function loomara_testimonial_return_url(){return add_query_arg('testimonial','submitted',home_url('/')).'#customer-testimonial-title';}
function loomara_testimonial_caption($quote){
    $city=get_post_meta($quote->ID,'_loomara_public_city',true);
    return $quote->post_title.(is_string($city)&&trim($city)!==''?' - '.sanitize_text_field($city):'');
}
function loomara_customer_testimonials() {
    $quotes=get_posts(['post_type'=>'loomara_testimonial','post_status'=>'publish','numberposts'=>5,'orderby'=>'date','order'=>'DESC']);
    echo '<section class="testimonials-190" aria-labelledby="customer-testimonial-title" data-customer-testimonials><p class="eyebrow">At home with Loomara</p><h2 id="customer-testimonial-title">Stories from softer spaces.</h2>';
    if(isset($_GET['testimonial'])&&$_GET['testimonial']==='submitted'){echo '<p role="status">Thank you. Your testimonial has been saved and is awaiting review.</p>';}
    if(!$quotes){echo '<div class="testimonials-empty"><p>Your everyday rituals are the heart of our story. Be among the first to tell us how a little more comfort feels.</p>';loomara_testimonial_prompt();echo '</div>';}
    else{echo '<p>A few honest words from the homes we’re grateful to be part of.</p><div class="testimonial-rail" aria-label="Latest customer testimonials" aria-roledescription="carousel">';foreach($quotes as $quote){echo '<figure><blockquote>'.esc_html($quote->post_content).'</blockquote><figcaption>'.esc_html(loomara_testimonial_caption($quote)).'</figcaption></figure>';}echo '</div>';if(count($quotes)>1)echo '<div class="testimonial-controls"><button type="button" class="button" data-testimonial-previous aria-label="Previous testimonial">←</button><span data-testimonial-position aria-live="off">1 / '.count($quotes).'</span><button type="button" class="button" data-testimonial-next aria-label="Next testimonial">→</button><button type="button" class="button testimonial-pause" data-pause-customer-testimonials>Pause testimonials</button></div>';loomara_testimonial_prompt();}
    echo '</section>';
}
