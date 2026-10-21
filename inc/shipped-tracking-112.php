<?php
/** Read-only Shiprocket enrichment for future Shipped emails. No shipment/payment writes. */
defined('ABSPATH') || exit;
function loomara_tracking_url_112($url) {
    if(!is_string($url)||strlen($url)>2048){return '';}
    $p=wp_parse_url($url);$host=strtolower($p['host']??'');
    if(($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['port'])){return '';}
    foreach(['shiprocket.in','shiprocket.co'] as $domain){if($host===$domain||substr($host,-strlen('.'.$domain))==='.'. $domain){return esc_url_raw($url);}}
    return '';
}
function loomara_tracking_fetch_112($order) {
    $match=loomara_sr_order_preflight($order);
    if(is_wp_error($match)||empty($match['shipments'])){return [];}
    $token=loomara_sr_read_token();if(is_wp_error($token)){return [];}
    $out=[];
    foreach($match['shipments'] as $shipment){
        $sid=absint($shipment['shipment_id']);$r=loomara_sr_read_request('shipments/'.$sid,$token);
        if(is_wp_error($r)){return [];}$d=$r['data'];
        if((int)($d['id']??0)!==$sid||(int)($d['order_id']??0)!==(int)$match['shiprocket_order_id']||(int)($d['channel_id']??0)!==(int)$match['channel_id']){return [];}
        $awb=is_scalar($d['awb']??null)?trim((string)$d['awb']):'';
        if(!preg_match('/^[A-Za-z0-9-]{3,100}$/D',$awb)){return [];}
        $courier=is_scalar($d['courier_name']??null)?sanitize_text_field(substr((string)$d['courier_name'],0,120)):'';
        $url='';
        // Fixed read endpoint; never request a URL supplied by the remote response.
        $response=wp_remote_get('https://apiv2.shiprocket.in/v1/external/courier/track/shipment/'.$sid,['timeout'=>15,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>1048576,'headers'=>['Authorization'=>'Bearer '.$token,'Accept'=>'application/json']]);
        if(!is_wp_error($response)&&wp_remote_retrieve_response_code($response)===200){
            $body=json_decode(wp_remote_retrieve_body($response),true);
            $tracking=$body['tracking_data']??null;
            if(is_array($tracking)&&is_array($tracking['shipment_track']??null)){
                foreach($tracking['shipment_track'] as $row){
                    if(is_array($row)&&(string)($row['awb_code']??'')===$awb){
                        $url=loomara_tracking_url_112($tracking['track_url']??'');
                        if(!$courier&&is_scalar($row['courier_name']??null)){$courier=sanitize_text_field(substr((string)$row['courier_name'],0,120));}
                        break;
                    }
                }
            }
        }
        $out[]=['shipment_id'=>$sid,'awb'=>$awb,'courier'=>$courier,'url'=>$url];
    }
    return $out;
}
function loomara_tracking_queue_112($order) {
    $id=$order->get_id();$key='loomara_tracking_job_112_'.$id;
    if(get_option('loomara_email_111_shipped_'.$id)){return;}
    add_option($key,['attempts'=>0,'stage'=>'waiting'],'','no');
    $job=get_option($key);
    if(is_array($job)&&$job['stage']==='waiting'&&!wp_next_scheduled('loomara_tracking_email_112',[$id])){wp_schedule_single_event(time()+60,'loomara_tracking_email_112',[$id]);}
}
function loomara_tracking_worker_112($id) {
    $id=absint($id);$key='loomara_tracking_job_112_'.$id;$job=get_option($key);
    if(!is_array($job)||($job['stage']??'')!=='waiting'||get_option('loomara_email_111_shipped_'.$id)){return;}
    $lock='loomara_tracking_lock_112_'.$id;
    if(!add_option($lock,time(),'','no')){return;}
    try {
        $order=wc_get_order($id);
        if(!$order||!in_array($order->get_status(),['shipped','in-transit','out-for-delivery'],true)||$order->get_meta('_loomara_delivered_at_111')){$job['stage']='skipped';update_option($key,$job,false);return;}
        $job['attempts']++;update_option($key,$job,false);
        $rows=loomara_tracking_fetch_112($order);$complete=(bool)$rows;
        foreach($rows as $row){if(!$row['url']||!$row['courier']){$complete=false;}}
        if($rows){$order->update_meta_data('_loomara_verified_tracking_112',$rows);$order->save_meta_data();}
        if(!$complete&&$job['attempts']<4){
            wp_schedule_single_event(time()+[1=>120,2=>300,3=>600][$job['attempts']],'loomara_tracking_email_112',[$id]);return;
        }
        // Reload status immediately before sending; cancelled/delivered orders must not get stale mail.
        $order=wc_get_order($id);
        if(!$order||!in_array($order->get_status(),['shipped','in-transit','out-for-delivery'],true)||$order->get_meta('_loomara_delivered_at_111')){$job['stage']='skipped';update_option($key,$job,false);return;}
        $emails=WC()->mailer()->get_emails();$email=$emails['Loomara_Email_Shipped']??null;
        if(!$email instanceof Loomara_Email_Shipped){$job['stage']='review';update_option($key,$job,false);return;}
        $email->trigger($id,$order,true);
        $job['stage']=get_option('loomara_email_111_shipped_'.$id)?'finished':'review';update_option($key,$job,false);
        if(!$complete){$order->add_order_note('Shipped email tracking enrichment timed out after four read-only checks. Available tracking was included; no duplicate email will be sent automatically.');}
    } catch(Throwable $e){$job['stage']='review';update_option($key,$job,false);}
    finally {delete_option($lock);}
}
add_action('loomara_tracking_email_112','loomara_tracking_worker_112');
