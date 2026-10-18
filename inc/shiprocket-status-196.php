<?php
/** Read-only shipment matching preflight. No cancellation/refund endpoints. */
defined('ABSPATH') || exit;
function loomara_sr_read_request($path,$token,$query=[]) {
    if (!preg_match('~^(orders|orders/show/[1-9][0-9]*|shipments/[1-9][0-9]*)$~D',$path)) { return new WP_Error('endpoint','Unsupported read-only endpoint.'); }
    $url='https://apiv2.shiprocket.in/v1/external/'.$path;
    if($query){$url=add_query_arg($query,$url);}
    $r=wp_remote_get($url,['timeout'=>20,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>1048576,'headers'=>['Authorization'=>'Bearer '.$token,'Accept'=>'application/json']]);
    if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200){return new WP_Error('read','Shiprocket status could not be verified. No order changes were made.');}
    $body=json_decode(wp_remote_retrieve_body($r),true);
    return is_array($body)&&is_array($body['data']??null)?$body:new WP_Error('response','Unexpected Shiprocket response. No order changes were made.');
}
function loomara_sr_read_token() {
    $c=loomara_sr_credentials();if(!$c){return new WP_Error('credentials','Connect the Shiprocket API user first.');}
    $r=wp_remote_post('https://apiv2.shiprocket.in/v1/external/auth/login',['timeout'=>20,'redirection'=>0,'sslverify'=>true,'headers'=>['Content-Type'=>'application/json'],'body'=>wp_json_encode($c)]);
    if(is_wp_error($r)){return new WP_Error('auth','Shiprocket authentication could not be verified.');}
    $body=json_decode(wp_remote_retrieve_body($r),true);
    return wp_remote_retrieve_response_code($r)===200&&is_string($body['token']??null)&&$body['token'] ? $body['token'] : new WP_Error('auth','Shiprocket authentication could not be verified.');
}
function loomara_sr_woocommerce_channel() {
    $channels=array_values(array_filter((array)get_option('loomara_shiprocket_channels',[]),function($c){return is_array($c)&&strtoupper(trim($c['name']??''))==='WOOCOMMERCE'&&!empty($c['id']);}));
    return count($channels)===1 ? absint($channels[0]['id']) : new WP_Error('channel','Exactly one WooCommerce channel must be verified before matching orders.');
}
function loomara_sr_exact_match($rows,$channel,$references) {
    $matches=[];
    foreach($rows as $row){
        if(!is_array($row)||(int)($row['channel_id']??0)!==$channel||!in_array((string)($row['channel_order_id']??''),$references,true)||empty($row['id'])){continue;}
        if(!empty($row['is_return'])||!empty($row['is_exchange'])){return new WP_Error('return','Return/exchange orders require manual verification.');}
        $matches[(int)$row['id']]=$row;
    }
    return $matches;
}
function loomara_sr_order_preflight($order) {
    $channel=loomara_sr_woocommerce_channel();if(is_wp_error($channel)){return $channel;}
    $token=loomara_sr_read_token();if(is_wp_error($token)){return $token;}
    $refs=array_values(array_unique([(string)$order->get_id(),(string)$order->get_order_number()]));$matches=[];
    foreach($refs as $reference){
        $list=loomara_sr_read_request('orders',$token,['channel_id'=>$channel,'filter_by'=>'channel_order_id','filter'=>$reference,'per_page'=>100]);
        if(is_wp_error($list)){return $list;}
        $pagination=$list['meta']['pagination']??[];
        if((int)($pagination['total_pages']??1)>1){return new WP_Error('pagination','Too many matching records. Manual verification required.');}
        $found=loomara_sr_exact_match($list['data'],$channel,$refs);if(is_wp_error($found)){return $found;}
        $matches+=$found;
    }
    if(count($matches)!==1){return new WP_Error('match',count($matches)?'Multiple Shiprocket matches found. No cancellation is permitted.':'No exact Shiprocket match found in the verified WooCommerce channel. Missing or unsynced records do not prove an order is unshipped.');}
    $id=(int)array_key_first($matches);$detail=loomara_sr_read_request('orders/show/'.$id,$token);if(is_wp_error($detail)){return $detail;}
    $check=loomara_sr_exact_match([$detail['data']],$channel,$refs);if(is_wp_error($check)){return $check;}
    if(count($check)!==1||!isset($check[$id])){return new WP_Error('identity','The Shiprocket detail identity does not match. No cancellation is permitted.');}
    $d=$detail['data'];$shipments=$d['shipments']??[];
    if(!is_array($shipments)){return new WP_Error('shipments','Shipment data is not verifiable.');}
    if(isset($shipments['id'])){$shipments=[$shipments];}
    if(count($shipments)>10){return new WP_Error('shipments','Multiple shipments require manual verification.');}
    $statuses=[];
    foreach($shipments as $shipment){
        $sid=absint($shipment['id']??0);if(!$sid){return new WP_Error('shipment','A shipment identifier is missing.');}
        $s=loomara_sr_read_request('shipments/'.$sid,$token);if(is_wp_error($s)){return $s;}
        $sd=$s['data'];
        if((int)($sd['id']??0)!==$sid||(int)($sd['order_id']??0)!==$id||(int)($sd['channel_id']??0)!==$channel){return new WP_Error('shipment','Shipment ownership could not be verified.');}
        $statuses[]=['shipment_id'=>$sid,'status'=>is_scalar($sd['status']??null)?sanitize_text_field((string)$sd['status']):'Unknown','status_code'=>is_scalar($sd['status_code']??null)?sanitize_text_field((string)$sd['status_code']):'Unknown'];
    }
    return ['woocommerce_id'=>$order->get_id(),'reference'=>$order->get_order_number(),'woocommerce_status'=>$order->get_status(),'channel_id'=>$channel,'shiprocket_order_id'=>$id,'order_status'=>is_scalar($d['status']??null)?sanitize_text_field((string)$d['status']):'Unknown','order_status_code'=>is_scalar($d['status_code']??null)?sanitize_text_field((string)$d['status_code']):'Unknown','shipments'=>$statuses,'checked_at'=>time()];
}
function loomara_sr_preflight_form() {
    if(!current_user_can('manage_options')){return;}
    echo '<div class="wrap"><hr><h2>Read-only shipment matching check</h2><p>Checks the exact WooCommerce channel and order reference, then reads shipment statuses. This does not cancel, refund, or change the order.</p>';
    $result=get_transient('loomara_sr_preflight_'.get_current_user_id());
    if($result){echo '<div class="notice notice-info inline"><pre style="white-space:pre-wrap;overflow-wrap:anywhere">'.esc_html(is_string($result)?$result:wp_json_encode($result,JSON_PRETTY_PRINT)).'</pre></div>';}
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_sr_preflight">';wp_nonce_field('loomara_sr_preflight');
    echo '<p><label for="loomara-sr-order">WooCommerce order ID or full OD reference</label><br><input id="loomara-sr-order" name="order_reference" type="text" required class="regular-text" placeholder="155 or OD-202610-000155"></p><button type="submit" class="button">Check shipping status — read only</button></form></div>';
}
function loomara_sr_run_preflight() {
    if(!current_user_can('manage_options')){wp_die('Administrator access required.',403);}check_admin_referer('loomara_sr_preflight');
    $uid=get_current_user_id();$rate='loomara_sr_preflight_rate_'.$uid;$count=(int)get_transient($rate);
    if($count>=5){wp_die('Please wait a minute before checking again.',429);}set_transient($rate,$count+1,60);
    $ref=is_string($_POST['order_reference']??null)?ltrim(trim(wp_unslash($_POST['order_reference'])),'#'):'';
    $id=0;if(preg_match('/^[1-9][0-9]*$/D',$ref)){$id=absint($ref);}elseif(preg_match('/^OD-[0-9]{6}-([0-9]+)$/D',$ref,$m)){$id=absint($m[1]);}
    $o=$id?wc_get_order($id):false;
    $result=(!$o||!$o instanceof WC_Order||(!ctype_digit($ref)&&$o->get_order_number()!==$ref))?new WP_Error('reference','Order reference could not be verified.'):loomara_sr_order_preflight($o);
    set_transient('loomara_sr_preflight_'.$uid,is_wp_error($result)?$result->get_error_message():$result,300);
    wp_safe_redirect(admin_url('admin.php?page=loomara-shiprocket'));exit;
}
add_action('admin_post_loomara_sr_preflight','loomara_sr_run_preflight');
add_action('loomara_shiprocket_connection_tools','loomara_sr_preflight_form');
