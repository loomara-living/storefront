<?php
/** India location hints from Shiprocket; never an address or coverage guarantee. */
defined('ABSPATH') || exit;
function loomara_pin_location_parse($body,$pin,$states) {
    $d=$body['postcode_details']??null;
    if(!is_array($d)){return new WP_Error('location','Location unavailable. Please enter city and state manually.');}
    if(isset($d['postcode'])&&(string)$d['postcode']!==$pin){return new WP_Error('location','PIN response did not match. Please enter your location manually.');}
    $country=strtoupper(trim((string)($d['country']??'')));
    if(!in_array($country,['INDIA','IN'],true)){return new WP_Error('country','Indian location could not be verified. Please enter it manually.');}
    $city=is_scalar($d['city']??null)?sanitize_text_field((string)$d['city']):'';
    $state=is_scalar($d['state']??null)?sanitize_text_field((string)$d['state']):'';
    $normal=function($s){$s=strtolower(trim($s));return preg_replace('/[^a-z]/','',str_replace('&','and',$s));};
    $code='';foreach($states as $key=>$name){if($normal($state)===$normal($name)||strtoupper($state)===$key){$code=$key;break;}}
    $aliases=['orissa'=>'OR','uttaranchal'=>'UK','pondicherry'=>'PY','nctofdelhi'=>'DL','nationalcapitalterritoryofdelhi'=>'DL'];
    if(!$code&&isset($aliases[$normal($state)],$states[$aliases[$normal($state)]])){$code=$aliases[$normal($state)];}
    if(!$city||!$code){return new WP_Error('location','City/state could not be verified. Please enter them manually.');}
    return ['pin'=>$pin,'city'=>$city,'state'=>$code,'state_name'=>$states[$code],'country'=>'IN','country_name'=>'India'];
}
function loomara_pin_location($pin) {
    if(!is_string($pin)||!preg_match('/^[1-9][0-9]{5}$/D',$pin)){return new WP_Error('pin','Enter a valid six-digit Indian PIN.');}
    $cached=get_transient('loomara_pin_location_'.$pin);if(is_array($cached)){return $cached;}
    $encrypted=get_transient('loomara_pin_sr_token');$token=$encrypted?loomara_sr_decrypt($encrypted):false;
    if(!$token){$token=loomara_sr_read_token();if(is_wp_error($token)){return new WP_Error('lookup','Location lookup is unavailable. Please enter city and state manually.');}$encrypted=loomara_sr_encrypt($token);if(!is_wp_error($encrypted)){set_transient('loomara_pin_sr_token',$encrypted,300);}}
    $r=wp_remote_get('https://apiv2.shiprocket.in/v1/external/open/postcode/details?postcode='.$pin,['timeout'=>10,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>32768,'headers'=>['Authorization'=>'Bearer '.$token,'Accept'=>'application/json']]);
    if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200){if(!is_wp_error($r)&&wp_remote_retrieve_response_code($r)===401){delete_transient('loomara_pin_sr_token');}return new WP_Error('lookup','Location lookup is unavailable. Please enter city and state manually.');}
    $result=loomara_pin_location_parse(json_decode(wp_remote_retrieve_body($r),true),$pin,WC()->countries->get_states('IN'));
    if(!is_wp_error($result)){set_transient('loomara_pin_location_'.$pin,$result,DAY_IN_SECONDS);}
    return $result;
}
function loomara_pin_ajax() {
    check_ajax_referer('loomara_pin_location','nonce');
    $pin=is_string($_POST['pin']??null)?trim(wp_unslash($_POST['pin'])):'';
    $session=function_exists('WC')&&WC()->session?WC()->session->get_customer_id():($_SERVER['REMOTE_ADDR']??'anonymous');
    $key='loomara_pin_rate_'.hash_hmac('sha256',(string)$session,wp_salt());$n=(int)get_transient($key);
    $global=(int)get_transient('loomara_pin_global_rate');
    if($n>=20||$global>=150){wp_send_json_error(['message'=>'Please wait a minute, or enter your location manually.'],429);}
    set_transient($key,$n+1,60);set_transient('loomara_pin_global_rate',$global+1,60);
    $r=loomara_pin_location($pin);if(is_wp_error($r)){wp_send_json_error(['message'=>$r->get_error_message()],400);}wp_send_json_success($r);
}
add_action('wp_ajax_loomara_pin_location','loomara_pin_ajax');add_action('wp_ajax_nopriv_loomara_pin_location','loomara_pin_ajax');
add_action('wp_enqueue_scripts',function(){
    if(!is_account_page()&&!is_checkout()){return;}
    wp_enqueue_script('loomara-pin-autofill',get_template_directory_uri().'/assets/pin-autofill-v197.js',[],filemtime(__DIR__.'/../assets/pin-autofill-v197.js'),true);
    wp_localize_script('loomara-pin-autofill','loomaraPinLocation',['url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('loomara_pin_location')]);
});
