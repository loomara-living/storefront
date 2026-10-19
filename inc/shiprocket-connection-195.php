<?php
/** Private Shiprocket API setup. This module never cancels or refunds orders. */
defined('ABSPATH') || exit;

function loomara_sr_encrypt($value) {
    if (!function_exists('openssl_encrypt')) { return new WP_Error('crypto','Secure credential storage is unavailable.'); }
    $iv=random_bytes(12); $tag='';
    $cipher=openssl_encrypt($value,'aes-256-gcm',hash('sha256',wp_salt('auth'),true),OPENSSL_RAW_DATA,$iv,$tag,'loomara-shiprocket-v1');
    return $cipher===false ? new WP_Error('crypto','Could not secure credentials.') : base64_encode($iv.$tag.$cipher);
}
function loomara_sr_decrypt($value) {
    if (!function_exists('openssl_decrypt') || !is_string($value)) { return false; }
    $raw=base64_decode($value,true);
    if ($raw===false || strlen($raw)<29) { return false; }
    return openssl_decrypt(substr($raw,28),'aes-256-gcm',hash('sha256',wp_salt('auth'),true),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16),'loomara-shiprocket-v1');
}
function loomara_sr_credentials() {
    $value=loomara_sr_decrypt(get_option('loomara_shiprocket_credentials',''));
    $data=$value ? json_decode($value,true) : false;
    return is_array($data)&&!empty($data['email'])&&!empty($data['password']) ? $data : false;
}
function loomara_sr_test_credentials($email,$password) {
    // Only Shiprocket's fixed HTTPS endpoint can receive these credentials.
    $r=wp_remote_post('https://apiv2.shiprocket.in/v1/external/auth/login',[
        'timeout'=>20,'redirection'=>0,'sslverify'=>true,
        'headers'=>['Content-Type'=>'application/json','Accept'=>'application/json'],
        'body'=>wp_json_encode(['email'=>$email,'password'=>$password]),
    ]);
    if (is_wp_error($r)) { return new WP_Error('connection','Shiprocket could not be reached. Credentials were not saved.'); }
    $data=json_decode(wp_remote_retrieve_body($r),true);
    if (wp_remote_retrieve_response_code($r)!==200 || !is_string($data['token']??null) || !$data['token']) {
        return new WP_Error('authentication','Shiprocket did not accept these API-user credentials. Credentials were not saved.');
    }
    // Verify the read-only channel permission; do not fetch customer/order data.
    $channels=wp_remote_get('https://apiv2.shiprocket.in/v1/external/channels',[
        'timeout'=>20,'redirection'=>0,'sslverify'=>true,
        'headers'=>['Authorization'=>'Bearer '.$data['token'],'Accept'=>'application/json'],
    ]);
    if (is_wp_error($channels)||wp_remote_retrieve_response_code($channels)!==200) {
        return new WP_Error('permission','Signed in, but channel access could not be verified. Credentials were not saved.');
    }
    $body=json_decode(wp_remote_retrieve_body($channels),true);
    if (!is_array($body['data']??null)) { return new WP_Error('response','Shiprocket returned an unexpected channel response. Credentials were not saved.'); }
    $result=[];
    foreach($body['data'] as $channel){
        if (is_array($channel)&&!empty($channel['id'])&&is_scalar($channel['name']??null)) {
            $result[]=['id'=>absint($channel['id']),'name'=>sanitize_text_field($channel['name'])];
        }
    }
    return $result;
}
add_action('admin_menu',function(){
    add_submenu_page('woocommerce','Loomara Shiprocket connection','Shiprocket connection','manage_options','loomara-shiprocket','loomara_sr_connection_page');
});
function loomara_sr_connection_page() {
    if (!current_user_can('manage_options')) { return; }
    $configured=(bool)loomara_sr_credentials();
    $channels=get_option('loomara_shiprocket_channels',[]);
    echo '<div class="wrap"><h1>Loomara Shiprocket connection</h1><p>Connect a dedicated Shiprocket API user. Credentials are encrypted server-side and never included in GitHub or public pages. Changing WordPress authentication salts requires reconnecting.</p>';
    $refunds_enabled=function_exists('loomara_ar_enabled')&&loomara_ar_enabled();
    echo '<div class="notice notice-warning inline"><p><strong>'.($refunds_enabled?'Automatic cancellation refunds are enabled.':'Automatic refunds are not enabled.').'</strong> This connection form only verifies authentication and reads channel names. Manage refund automation separately under WooCommerce → Automatic refunds.</p></div>';
    if(isset($_GET['connected'])&&$_GET['connected']==='1'){echo '<div class="notice notice-success"><p>Connection verified and encrypted credentials saved.</p></div>';}
    $error=get_transient('loomara_sr_setup_error_'.get_current_user_id());
    if($error){delete_transient('loomara_sr_setup_error_'.get_current_user_id());echo '<div class="notice notice-error"><p>'.esc_html($error).'</p></div>';}
    echo '<p>Connection: <strong>'.($configured?'Credentials saved':'Not connected').'</strong></p>';
    if($channels){echo '<h2>Verified channels</h2><ul>';foreach($channels as $c){echo '<li>'.esc_html($c['name']).' — ID '.absint($c['id']).'</li>';}echo '</ul><p>We will verify the exact Loomara channel before enabling order cancellation.</p>';}
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" autocomplete="off"><input type="hidden" name="action" value="loomara_sr_connect">';
    wp_nonce_field('loomara_sr_connect');
    echo '<table class="form-table"><tr><th><label for="loomara-sr-email">API user email</label></th><td><input type="email" id="loomara-sr-email" name="api_email" class="regular-text" autocomplete="off" required></td></tr><tr><th><label for="loomara-sr-password">API user password</label></th><td><input type="password" id="loomara-sr-password" name="api_password" class="regular-text" autocomplete="new-password" required><p class="description">Enter the dedicated API-user password, not your main Shiprocket login. Neither field is prefilled or returned after submission.</p></td></tr></table><p>Test and save sends these credentials only to Shiprocket’s authentication API. Successful credentials are encrypted in this WordPress database. Existing credentials remain intact if verification fails.</p><button class="button button-primary" type="submit">Test connection &amp; save securely</button></form></div>';
    do_action('loomara_shiprocket_connection_tools');
}
function loomara_sr_save_connection() {
    if(!current_user_can('manage_options')){wp_die('Administrator access required.',403);}
    check_admin_referer('loomara_sr_connect');
    $email=is_string($_POST['api_email']??null)?sanitize_email(wp_unslash($_POST['api_email'])):'';
    $password=is_string($_POST['api_password']??null)?wp_unslash($_POST['api_password']):'';
    $result=(!$email||!is_email($email)||!$password||strlen($password)>1024)?new WP_Error('input','Enter a valid API-user email and password.'):loomara_sr_test_credentials($email,$password);
    if(!is_wp_error($result)){
        $encrypted=loomara_sr_encrypt(wp_json_encode(['email'=>$email,'password'=>$password]));
        if(is_wp_error($encrypted)){$result=$encrypted;}
        else{update_option('loomara_shiprocket_credentials',$encrypted,false);update_option('loomara_shiprocket_channels',$result,false);update_option('loomara_shiprocket_verified_at',time(),false);}
    }
    if(is_wp_error($result)){set_transient('loomara_sr_setup_error_'.get_current_user_id(),$result->get_error_message(),120);}
    wp_safe_redirect(admin_url('admin.php?page=loomara-shiprocket'.(is_wp_error($result)?'':'&connected=1')));exit;
}
add_action('admin_post_loomara_sr_connect','loomara_sr_save_connection');
