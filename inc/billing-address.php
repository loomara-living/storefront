<?php
defined('ABSPATH') || exit;
add_action('admin_post_loomara_default_address', function () {
    if(!is_user_logged_in()){auth_redirect();}
    check_admin_referer('loomara_default_address');$addresses=loomara_saved_addresses();$id=sanitize_key(wp_unslash($_POST['address_id']??''));
    if(!isset($addresses[$id])){wp_die('This address does not belong to your account.','',['response'=>403]);}
    // Materialize a native legacy address as well, keeping a stable default ID.
    update_user_meta(get_current_user_id(),'_loomara_shipping_addresses',$addresses);
    update_user_meta(get_current_user_id(),'_loomara_default_shipping_address',$id);loomara_sync_default_address($addresses[$id]);
    wp_safe_redirect(wc_get_account_endpoint_url('edit-address'));exit;
});
add_action('woocommerce_after_checkout_billing_form',function(){
    $addresses=loomara_saved_addresses();$same=!isset($_POST['woocommerce-process-checkout-nonce'])||!empty($_POST['loomara_billing_same']);
    echo '<section class="separate-billing"><h3>Billing address</h3><label><input type="checkbox" name="loomara_billing_same" value="1" '.checked($same,true,false).' data-billing-same> Billing address same as shipping address</label><div data-separate-billing>';
    if($addresses){echo '<label>Choose a saved billing address<select name="loomara_billing_address_id" data-billing-select><option value="">Add a new billing address</option>';foreach($addresses as $id=>$address){echo '<option value="'.esc_attr($id).'">'.esc_html($address['label'].' — '.$address['address_1'].', '.$address['city']).'</option>';}echo '</select></label>';}
    echo '<div class="address-form-grid" data-manual-billing>';
    foreach(loomara_address_fields() as $key=>$label){
        if($key==='label')continue;
        echo '<label>'.esc_html($label);
        if($key==='state'){echo '<select name="loomara_billing_state"><option value="">Choose state / UT</option>';foreach(WC()->countries->get_states('IN') as $code=>$name){echo '<option value="'.esc_attr($code).'">'.esc_html($name).'</option>';}echo '</select>';}
        else{echo '<input type="'.($key==='phone'?'tel':'text').'" name="loomara_billing_'.esc_attr($key).'" maxlength="160" value="'.esc_attr(is_scalar($_POST['loomara_billing_'.$key]??'')?sanitize_text_field(wp_unslash($_POST['loomara_billing_'.$key]??'')):'').'">';}
        echo '</label>';
    }
    if(is_user_logged_in()){echo '<label><input type="checkbox" name="loomara_save_billing_address" value="1"> Save this new billing address to my account</label>';}
    echo '</div><p>India · This billing address does not change your delivery address.</p></div></section>';
},20);
add_action('woocommerce_after_checkout_validation',function($data,$errors){
    if(!empty($_POST['loomara_billing_same']))return;
    $raw=['label'=>'Billing address'];foreach(loomara_address_fields() as $key=>$label){if($key!=='label')$raw[$key]=$data['billing_'.$key]??'';}
    $result=loomara_validate_address($raw);if(is_wp_error($result)){foreach($result->get_error_codes() as $code){$errors->add('loomara_billing_'.$code,'Billing: '.$result->get_error_message($code));}}
},25,2);
add_action('woocommerce_checkout_order_processed',function($id,$data,$order){
    if(!is_user_logged_in()||!empty($_POST['loomara_billing_same'])||empty($_POST['loomara_save_billing_address'])||!empty($_POST['loomara_billing_address_id']))return;
    $raw=['label'=>'Billing '.wp_date('d M Y')];foreach(loomara_address_fields() as $key=>$label){if($key!=='label')$raw[$key]=$data['billing_'.$key]??'';}
    $address=loomara_validate_address($raw);if(is_wp_error($address))return;$addresses=loomara_saved_addresses();if(count($addresses)>=20)return;
    foreach($addresses as $existing){if(array_diff_assoc(array_diff_key($address,['label'=>true]),$existing)===[])return;}
    $addresses[wp_generate_uuid4()]=$address;update_user_meta(get_current_user_id(),'_loomara_shipping_addresses',$addresses);
},20,3);
