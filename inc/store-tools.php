<?php
/** Optional native-page tools. No frontend data seeding or provider mutations. */
defined('ABSPATH') || exit;
function loomara_set_categories(){return ['bedsheets'=>'Bedsheet','comforters'=>'Comforter','cushion-covers'=>'Cushion covers'];}
function loomara_set_option($p,$category){
    if(!$p||!in_array($p->get_type(),['simple','variation'],true)||!$p->is_purchasable()||!$p->is_in_stock()){return false;}
    $parent=$p->is_type('variation')?wc_get_product($p->get_parent_id()):$p;
    if(!$parent||$parent->get_status()!=='publish'||!$parent->is_visible()||post_password_required($parent->get_id())||!has_term($category,'product_cat',$parent->get_id())){return false;}
    $attrs=$p->is_type('variation')?$p->get_variation_attributes():[];
    if(in_array('', $attrs,true)||($p->is_type('variation')&&!$p->variation_is_visible())){return false;}
    return ['parent'=>$parent->get_id(),'variant'=>$p->is_type('variation')?$p->get_id():0,'attributes'=>$attrs];
}
function loomara_set_builder(){
    if(!function_exists('wc_get_products')){return '';}
    ob_start();echo '<section class="loomara-tools"><h2>Build your bedding set</h2><p>Choose complementary pieces at today’s individual prices. There is no additional bundle discount. Review your selection before adding it to your existing cart.</p>';
    if(!is_user_logged_in()){echo '<p><a href="'.esc_url(wc_get_page_permalink('myaccount')).'">Sign in to build your set</a>. You can still browse all pieces in the Shop.</p></section>';return ob_get_clean();}
    $token=wp_generate_uuid4();echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_build_set"><input type="hidden" name="token" value="'.esc_attr($token).'">';wp_nonce_field('loomara_set_'.$token,'set_nonce');
    foreach(loomara_set_categories() as $slug=>$label){echo '<label class="set-choice">'.esc_html($label).'<select name="pieces['.esc_attr($slug).']"><option value="0">Skip this piece</option>';
        $parents=wc_get_products(['status'=>'publish','category'=>[$slug],'limit'=>60,'orderby'=>'title','order'=>'ASC']);
        foreach($parents as $parent){$ids=$parent->is_type('variable')?array_slice($parent->get_children(),0,100):[$parent->get_id()];foreach($ids as $id){$p=wc_get_product($id);if(!loomara_set_option($p,$slug)){continue;}$name=$p->get_name();if($p->is_type('variation')){$name.=' — '.wc_get_formatted_variation($p,true,false,true);}echo '<option value="'.absint($id).'">'.esc_html($name.' · '.html_entity_decode(wp_strip_all_tags(wc_price(wc_get_price_to_display($p))),ENT_QUOTES,'UTF-8')).'</option>';}}
        echo '</select></label>';}
    echo '<p>One unit of each chosen piece. Exact colour and size appear in each option. Unavailable or custom-choice variants are omitted; browse Shop for the full catalogue. Prices and availability are checked again when added.</p><button class="button" type="submit">Add selected set to cart</button></form></section>';return ob_get_clean();
}
add_shortcode('loomara_bedding_builder','loomara_set_builder');
function loomara_room_children($id){$terms=get_terms(['taxonomy'=>'product_cat','parent'=>$id,'hide_empty'=>true]);return is_wp_error($terms)?[]:$terms;}
add_action('admin_post_loomara_build_set',function(){
    if($_SERVER['REQUEST_METHOD']!=='POST'||!is_user_logged_in()||!function_exists('WC')){wp_die('Not allowed.','',['response'=>403]);}
    $token=sanitize_text_field(wp_unslash($_POST['token']??''));if(!preg_match('/^[a-f0-9-]{36}$/i',$token)||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['set_nonce']??'')),'loomara_set_'.$token)){wp_die('Please reload the builder.','',['response'=>403]);}
    if(!WC()->cart){wc_load_cart();}if(!loomara_buy_again_claim(0,$token)){wc_add_notice('This request was already handled. Please review your cart.','notice');wp_safe_redirect(wc_get_cart_url());exit;}
    $pieces=is_array($_POST['pieces']??null)?wp_unslash($_POST['pieces']):[];$added=0;$failed=0;
    foreach(loomara_set_categories() as $slug=>$label){$id=absint(is_scalar($pieces[$slug]??null)?$pieces[$slug]:0);if(!$id){continue;}$p=wc_get_product($id);$line=loomara_set_option($p,$slug);
        try{if(!$line||!apply_filters('woocommerce_add_to_cart_validation',true,$line['parent'],1,$line['variant'],$line['attributes'])||!WC()->cart->add_to_cart($line['parent'],1,$line['variant'],$line['attributes'])){$failed++;}else{$added++;}}catch(Throwable $e){$failed++;}}
    wc_add_notice($added.' piece(s) added. '.($failed?'Some pieces could not be added; review the cart before continuing.':'Review quantities and current totals in your cart.'),$added?'success':'notice');wp_safe_redirect(wc_get_cart_url());exit;
});
/** Honest taxonomy-backed edits: no inferred product compatibility claims. */
function loomara_room_collections(){
    $groups=['Bedroom essentials'=>['bedsheets','comforters','combo'],'Living room accents'=>['cushion-covers']];
    ob_start();echo '<section class="loomara-tools"><h2>Shop by room & style</h2><p>Start with a room, then explore the colours and patterns in each collection.</p><div class="room-grid">';
    foreach($groups as $name=>$slugs){echo '<article><h3>'.esc_html($name).'</h3>';foreach($slugs as $slug){$term=get_term_by('slug',$slug,'product_cat');if(!$term){continue;}$url=get_term_link($term);if(is_wp_error($url)){continue;}echo '<a href="'.esc_url($url).'">'.esc_html($term->name).' →</a>';foreach(loomara_room_children($term->term_id) as $child){$link=get_term_link($child);if(!is_wp_error($link)){echo '<a class="room-style" href="'.esc_url($link).'">'.esc_html($child->name).'</a>';}}}echo '</article>';}
    echo '</div></section>';return ob_get_clean();
}
add_shortcode('loomara_room_collections','loomara_room_collections');
function loomara_store_page_plan(){return ['build-your-bedding-set'=>['Build your bedding set','[loomara_bedding_builder]'],'shop-by-room'=>['Shop by room & style','[loomara_room_collections]']];}
function loomara_store_pages_settings(){
    if(!current_user_can('manage_options')){return;}
    echo '<section><h2>Customer discovery pages</h2><p>Create only missing bedding-builder and room/style pages. Existing pages are never overwritten. Published matching pages are linked from Shop.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_store_pages">';wp_nonce_field('loomara_store_pages');echo '<button class="button">Create missing discovery pages</button></form></section>';
}
add_action('admin_post_loomara_store_pages',function(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!current_user_can('manage_options')){wp_die('Administrator access required.','',['response'=>403]);}check_admin_referer('loomara_store_pages');
    foreach(loomara_store_page_plan() as $slug=>$plan){$existing=get_page_by_path($slug,OBJECT,'page');if($existing){if($existing->post_status!=='publish'||!str_contains($existing->post_content,$plan[1])){wp_die('An existing destination needs review; it has not been changed.','',['response'=>409]);}continue;}$id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$plan[0],'post_content'=>$plan[1]],true);if(is_wp_error($id)||!$id){wp_die('Page creation failed. Existing pages were not changed.','',['response'=>500]);}}
    wp_safe_redirect(admin_url('admin.php?page=loomara-settings'));exit;
});
add_action('woocommerce_before_shop_loop',function(){
    if(is_search()){return;}$links=[];foreach(loomara_store_page_plan() as $slug=>$plan){$p=get_page_by_path($slug,OBJECT,'page');if($p&&$p->post_status==='publish'&&str_contains($p->post_content,$plan[1])){$links[]='<a href="'.esc_url(get_permalink($p)).'">'.esc_html($plan[0]).' →</a>';}}if($links){echo '<nav class="loomara-discovery-links" aria-label="Explore your home">'.implode(' ',$links).'</nav>';}
},5);
add_action('init',function(){register_post_type('loomara_case',['label'=>'Customer support cases','public'=>false,'show_ui'=>true,'show_in_menu'=>'woocommerce','show_in_rest'=>false,'supports'=>['title'],'capabilities'=>['edit_posts'=>'manage_woocommerce','edit_others_posts'=>'manage_woocommerce','publish_posts'=>'manage_woocommerce','read_private_posts'=>'manage_woocommerce','delete_posts'=>'manage_options','create_posts'=>'do_not_allow'],'map_meta_cap'=>true]);});
add_filter('wp_insert_post_data',function($data,$input){if(($data['post_type']??'')==='loomara_case'&&!in_array($data['post_status']??'',['trash','auto-draft'],true)){$data['post_status']='private';if(!empty($input['ID'])){$old=get_post((int)$input['ID']);if($old&&$old->post_type==='loomara_case'){$data['post_author']=$old->post_author;}}}return $data;},10,2);
function loomara_cases_panel(){
    if(!is_user_logged_in()||!function_exists('wc_get_orders')){return;}
    echo '<section class="loomara-tools"><h2>Customer support cases</h2><p>For order questions only. Submitting a case does not cancel an order, start a return or request a refund. Please use the dedicated order actions for those requests. Check this page for replies.</p>';
    $cases=get_posts(['post_type'=>'loomara_case','post_status'=>'private','author'=>get_current_user_id(),'numberposts'=>20,'orderby'=>'ID','order'=>'DESC']);
    foreach($cases as $case){$reply=get_post_meta($case->ID,'_loomara_case_reply',true);$status=get_post_meta($case->ID,'_loomara_case_status',true)==='resolved'?'Resolved':'Open';echo '<details><summary>Case #'.absint($case->ID).' — '.esc_html($case->post_title).' · '.esc_html($status).'</summary><p>'.nl2br(esc_html($case->post_content)).'</p><p><strong>Loomara reply:</strong> '.($reply?nl2br(esc_html($reply)):'Awaiting a team response.').'</p></details>';}
    $orders=wc_get_orders(['customer_id'=>get_current_user_id(),'limit'=>30,'orderby'=>'date','order'=>'DESC']);$token=wp_generate_uuid4();
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_create_case"><input type="hidden" name="token" value="'.esc_attr($token).'">';wp_nonce_field('loomara_case_'.$token,'case_nonce');
    echo '<label>Order<select name="order_id" required><option value="">Choose your order</option>';foreach($orders as $o){echo '<option value="'.absint($o->get_id()).'">'.esc_html($o->get_order_number()).'</option>';}echo '</select></label><label>Subject<input name="subject" maxlength="120" required></label><label>How can we help?<textarea name="message" maxlength="3000" required></textarea></label><p>Do not include card details, passwords or bank credentials. Recent 20 cases and 30 orders are shown. Contact us for older orders.</p><button class="button">Create support case</button></form></section>';
}
add_action('woocommerce_account_dashboard','loomara_cases_panel',30);
add_action('admin_post_loomara_create_case',function(){
    $token=sanitize_text_field(wp_unslash($_POST['token']??''));$o=function_exists('wc_get_order')?wc_get_order(absint($_POST['order_id']??0)):false;
    if($_SERVER['REQUEST_METHOD']!=='POST'||!preg_match('/^[a-f0-9-]{36}$/i',$token)||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['case_nonce']??'')),'loomara_case_'.$token)||!loomara_journey_owner($o)){wp_die('Not allowed.','',['response'=>403]);}
    $subject=sanitize_text_field(wp_unslash($_POST['subject']??''));$message=sanitize_textarea_field(wp_unslash($_POST['message']??''));
    if(!$subject||!$message||strlen($subject)>120||strlen($message)>3000){wp_die('Please enter a short subject and message.');}
    $recent=get_posts(['post_type'=>'loomara_case','post_status'=>'private','author'=>get_current_user_id(),'date_query'=>[['after'=>'1 hour ago']],'numberposts'=>5,'fields'=>'ids']);
    if(count($recent)>=5||!loomara_buy_again_claim(-1,$token)){wp_die('Please review existing cases or try again later.');}
    $id=wp_insert_post(['post_type'=>'loomara_case','post_status'=>'private','post_author'=>get_current_user_id(),'post_title'=>$subject,'post_content'=>$message],true);
    if(is_wp_error($id)){wp_die('Unable to save the case. Please contact us.');}update_post_meta($id,'_loomara_case_order',$o->get_id());wp_safe_redirect(wc_get_page_permalink('myaccount'));exit;
});
add_action('add_meta_boxes_loomara_case',function(){add_meta_box('loomara-case-review','Order and customer reply',function($post){if(!current_user_can('manage_woocommerce')){return;}$order=wc_get_order(get_post_meta($post->ID,'_loomara_case_order',true));wp_nonce_field('loomara_review_case','case_review_nonce');echo '<p>Order: '.($order?'<a href="'.esc_url($order->get_edit_order_url()).'">'.esc_html($order->get_order_number()).'</a>':'Unavailable').'</p><h3>Customer message</h3><p>'.nl2br(esc_html($post->post_content)).'</p><label>Customer-visible reply<textarea style="width:100%" rows="6" name="case_reply" maxlength="3000">'.esc_textarea(get_post_meta($post->ID,'_loomara_case_reply',true)).'</textarea></label><label><input type="checkbox" name="case_resolved" value="1" '.checked(get_post_meta($post->ID,'_loomara_case_status',true),'resolved',false).'> Resolved</label><p>Saving publishes this reply in the customer’s account. No email is sent.</p>';});});
add_action('save_post_loomara_case',function($id){if(wp_is_post_revision($id)||!current_user_can('manage_woocommerce')||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['case_review_nonce']??'')),'loomara_review_case')){return;}update_post_meta($id,'_loomara_case_reply',substr(sanitize_textarea_field(wp_unslash($_POST['case_reply']??'')),0,3000));update_post_meta($id,'_loomara_case_status',isset($_POST['case_resolved'])?'resolved':'open');});
add_action('admin_menu',function(){add_submenu_page('woocommerce','Integration health','Integration health','manage_options','loomara-integration-health','loomara_integration_health');});
function loomara_health_stage($stage){return in_array($stage,['review','failed','uncertain','error'],true)?'Needs attention':(in_array($stage,['waiting','shipping_wait','requested','pending'],true)?'Waiting':'Recorded state');}
function loomara_integration_health(){
    if(!current_user_can('manage_options')){return;}
    echo '<div class="wrap"><h1>Integration health — read only</h1><p>This is a bounded local-record snapshot, not a live API test. Opening it does not contact Shiprocket/Razorpay, send emails or run jobs. Saved verification does not prove current connectivity.</p>';
    $at=(int)get_option('loomara_shiprocket_verified_at',0);echo '<p>Shiprocket last saved verification: <strong>'.esc_html($at?wp_date('d M Y H:i T',$at):'Not recorded').'</strong></p><p>WordPress automatic cron: '.(defined('DISABLE_WP_CRON')&&DISABLE_WP_CRON?'Disabled in configuration; verify an external scheduler.':'Enabled in configuration; execution still depends on site traffic or an external scheduler.').'</p>';
    if(!function_exists('wc_get_orders')){echo '<p>WooCommerce unavailable.</p></div>';return;}
    echo '<h2>Recent workflow records</h2><p>Latest 100 orders only; missing records mean “not recorded”, not success. Review older orders in the dedicated integration tools.</p><table class="widefat striped"><thead><tr><th>Order</th><th>Workflow</th><th>Saved stage</th><th>Observation</th></tr></thead><tbody>';
    $count=0;foreach(wc_get_orders(['limit'=>100,'orderby'=>'date','order'=>'DESC']) as $o){foreach(['loomara_cancel_request_124_'=>'Cancellation','loomara_refund_110_'=>'Refund','loomara_tracking_job_112_'=>'Shipment details','loomara_pickup_tracking_job_'=>'Readiness details'] as $prefix=>$label){$job=get_option($prefix.$o->get_id(),[]);if(!is_array($job)||empty($job['stage'])){continue;}$stage=sanitize_key($job['stage']);$due=(int)($job['next_check']??0);$observation=loomara_health_stage($stage);if($due&&$due<time()-900&&!in_array($stage,['finished','confirmed','skipped','completed','review'],true)){$observation.=' · Check due more than 15 minutes ago; inspect scheduler';}echo '<tr><td><a href="'.esc_url($o->get_edit_order_url()).'">'.esc_html($o->get_order_number()).'</a></td><td>'.esc_html($label).'</td><td>'.esc_html($stage).'</td><td>'.esc_html($observation).'</td></tr>';$count++;}}
    if(!$count){echo '<tr><td colspan="4">No workflow records found in this sample.</td></tr>';}echo '</tbody></table><p>No credentials or customer contact details are displayed.</p></div>';
}
add_action('wp_enqueue_scripts',function(){if(is_page()||(function_exists('is_account_page')&&is_account_page())){wp_enqueue_style('loomara-store-tools',get_template_directory_uri().'/assets/store-tools.css',[],filemtime(get_template_directory().'/assets/store-tools.css'));}});
