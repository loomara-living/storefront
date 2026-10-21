<?php
/** Reverse shipping has its own durable intent; never reuse forward-order IDs. */
defined('ABSPATH') || exit;
function loomara_rt_sr($path,$token,$query=[],$body=null){
    $get=in_array($path,['settings/company/pickup','orders/processing/return','courier/serviceability/'],true)||preg_match('~^(orders/show|shipments|courier/track/shipment)/[1-9][0-9]*$~D',$path);
    if(($body===null&&!$get)||($body!==null&&$path!=='shipments/create/return-shipment')){return new WP_Error('endpoint','Unsupported returns endpoint.');}
    $url='https://apiv2.shiprocket.in/v1/external/'.$path;if($query){$url=add_query_arg($query,$url);}
    $args=['timeout'=>25,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>1048576,'headers'=>['Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json','Accept'=>'application/json']];
    if($body!==null){$args['body']=wp_json_encode($body);$result=wp_remote_post($url,$args);}else{$result=wp_remote_get($url,$args);}
    if(is_wp_error($result)||wp_remote_retrieve_response_code($result)!==200){return new WP_Error('shiprocket','Shiprocket response unavailable. No success is assumed.');}
    $d=json_decode(wp_remote_retrieve_body($result),true);return is_array($d)?$d:new WP_Error('response','Unexpected returns response.');
}
function loomara_rt_home($token){
    $d=loomara_rt_sr('settings/company/pickup',$token);if(is_wp_error($d)){return $d;}
    $rows=array_filter($d['data']['shipping_address']??[],static function($p){return is_array($p)&&($p['pickup_location']??'')==='Home';});
    if(count($rows)!==1){return new WP_Error('home','Exactly one Home return location must be verified.');}$p=array_values($rows)[0];
    foreach(['name','address','city','state','country','pin_code','phone','email'] as $f){if(!is_scalar($p[$f]??null)||trim((string)$p[$f])===''){return new WP_Error('home','Home return contact/address is incomplete.');}}
    if(!in_array(strtoupper($p['country']),['IN','INDIA'],true)||!preg_match('/^[1-9][0-9]{5}$/D',(string)$p['pin_code'])||!is_email($p['email'])){return new WP_Error('home','Home must have valid India contact details.');}
    return $p;
}
function loomara_rt_delivery_proof($o){
    $p=loomara_sr_order_preflight($o);if(is_wp_error($p)){return $p;}
    if((string)($p['order_status_code']??'')!=='7'||strtoupper((string)($p['order_status']??''))!=='DELIVERED'||count($p['shipments']??[])!==1){return new WP_Error('delivery','Exact delivered single-shipment proof is required. Split deliveries need team review.');}
    $token=loomara_sr_read_token();if(is_wp_error($token)){return $token;}$sid=(int)$p['shipments'][0]['shipment_id'];
    $s=loomara_rt_sr('shipments/'.$sid,$token);if(is_wp_error($s)){return $s;}$d=$s['data']??[];
    if((int)($d['id']??0)!==$sid||(int)($d['order_id']??0)!==(int)$p['shiprocket_order_id']||(int)($d['channel_id']??0)!==(int)$p['channel_id']){return new WP_Error('delivery','Forward shipment identity changed.');}
    $awb=(string)($d['awb']??'');if(!preg_match('/^[A-Za-z0-9-]{3,100}$/D',$awb)){return new WP_Error('delivery','Forward AWB unavailable.');}
    $track=loomara_rt_sr('courier/track/shipment/'.$sid,$token);if(is_wp_error($track)){return $track;}
    $matches=array_values(array_filter($track['tracking_data']['shipment_track']??[],static function($r)use($awb){return is_array($r)&&(string)($r['awb_code']??'')===$awb;}));
    if(count($matches)!==1){return new WP_Error('delivery','Delivery timestamp identity is ambiguous.');}
    $raw=$matches[0]['delivered_date']??null;$date=is_string($raw)?DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$raw,new DateTimeZone('Asia/Kolkata')):false;
    if(!$date||$date->format('Y-m-d H:i:s')!==$raw||$date->getTimestamp()<1||$date->getTimestamp()>time()){return new WP_Error('delivery','Actual courier delivery date unavailable. Please contact us for a manual return review.');}
    return ['at'=>$date->getTimestamp(),'forward'=>['order_id'=>(int)$p['shiprocket_order_id'],'shipment_id'=>$sid,'awb'=>$awb,'channel_id'=>(int)$p['channel_id']]];
}
function loomara_rt_payload($o,$r,$home,$parcel){
    if(loomara_rt_identity($o)!==$r['identity']){return new WP_Error('identity','Order contents or ownership changed.');}
    $user=get_user_by('id',$r['owner']);if(!$user||!is_email($user->user_email)){return new WP_Error('owner','Original customer account unavailable.');}
    $a=[];foreach(['first_name','last_name','address_1','address_2','city','state','postcode','country','phone'] as $f){$a[$f]=(string)$o->{'get_shipping_'.$f}();}
    if(!$a['phone']){$a['phone']=(string)$o->get_billing_phone();}$phone=preg_replace('/\D/','',$a['phone']);if(strlen($phone)===12&&substr($phone,0,2)==='91'){$phone=substr($phone,2);}
    $states=WC()->countries->get_states('IN');
    if($a['country']!=='IN'||!isset($states[$a['state']])||!$a['first_name']||!$a['address_1']||!$a['city']||!preg_match('/^[1-9][0-9]{5}$/D',$a['postcode'])||!preg_match('/^[6-9][0-9]{9}$/D',$phone)){return new WP_Error('address','Original India pickup address needs team correction.');}
    foreach(['length','breadth','height','weight'] as $f){if(!isset($parcel[$f])||!is_numeric($parcel[$f])||!is_finite((float)$parcel[$f])||(float)$parcel[$f]<=0){return new WP_Error('parcel','Confirm actual return parcel dimensions and weight.');}}
    if(max($parcel['length'],$parcel['breadth'],$parcel['height'])>200||$parcel['weight']>50){return new WP_Error('parcel','Return parcel requires manual handling.');}
    $items=[];$sum=0;
    foreach($r['plan']['lines'] as $id=>$line){$item=$o->get_item($id);$p=$item?$item->get_product():false;
        if(!$p||$p->is_virtual()||!$p->get_sku()||$line['gross']%$line['qty']!==0){return new WP_Error('items','Missing SKU, virtual item or fractional unit price requires review.');}
        $items[]=['name'=>$item->get_name(),'sku'=>$p->get_sku(),'units'=>$line['qty'],'selling_price'=>($line['gross']/$line['qty'])/100];$sum+=$line['gross'];
    }
    if(!$items||$sum!==$r['plan']['amount']){return new WP_Error('items','Return invoice is inconsistent.');}
    $channel=loomara_sr_woocommerce_channel();if(is_wp_error($channel)||$channel<1||(int)$channel!==(int)($r['forward']['channel_id']??0)){return new WP_Error('channel','Original return channel changed.');}
    return array_merge(['order_id'=>$r['reference'],'order_date'=>wp_date('Y-m-d H:i',$r['created'],new DateTimeZone('Asia/Kolkata')),
        'channel_id'=>$channel,'pickup_customer_name'=>$a['first_name'],'pickup_last_name'=>$a['last_name'],'pickup_address'=>$a['address_1'],'pickup_address_2'=>$a['address_2'],'pickup_city'=>$a['city'],'pickup_state'=>$states[$a['state']],'pickup_country'=>'India','pickup_pincode'=>$a['postcode'],'pickup_email'=>$user->user_email,'pickup_phone'=>$phone,'pickup_isd_code'=>'91',
        'shipping_customer_name'=>$home['name'],'shipping_address'=>$home['address'],'shipping_address_2'=>$home['address_2']??'','shipping_city'=>$home['city'],'shipping_state'=>$home['state'],'shipping_country'=>'India','shipping_pincode'=>(string)$home['pin_code'],'shipping_email'=>$home['email'],'shipping_phone'=>(string)$home['phone'],'shipping_isd_code'=>'91',
        'order_items'=>$items,'payment_method'=>'PREPAID','total_discount'=>0,'sub_total'=>$sum/100,'request_pickup'=>true,'print_label'=>false,'generate_manifest'=>false],array_map('floatval',$parcel));
}
function loomara_rt_reverse_match($r,$token){
    $d=loomara_rt_sr('orders/processing/return',$token,['search'=>$r['reference'],'per_page'=>100,'page'=>1]);if(is_wp_error($d)){return $d;}
    $pagination=$d['meta']['pagination']??[];
    if(!is_array($d['data']??null)||!isset($pagination['total_pages'])||(int)$pagination['total_pages']>1||count($d['data'])>=100){return new WP_Error('list','Complete reverse-order duplicate search unavailable.');}
    $matches=[];foreach($d['data'] as $row){if(is_array($row)&&(string)($row['channel_order_id']??'')===$r['reference']){$matches[(int)($row['id']??0)]=$row;}}
    if(count($matches)>1||isset($matches[0])){return new WP_Error('duplicate','Ambiguous reverse-order match.');}
    if(!$matches){return false;}$id=(int)array_key_first($matches);
    $detail=loomara_rt_sr('orders/show/'.$id,$token);if(is_wp_error($detail)){return $detail;}$v=$detail['data']??[];
    $channel=loomara_sr_woocommerce_channel();
    if(is_wp_error($channel)||(int)($v['id']??0)!==$id||(string)($v['channel_order_id']??'')!==$r['reference']||(int)($v['channel_id']??0)!==$channel||!in_array($v['is_return']??null,[1,'1',true],true)||!empty($v['is_exchange'])){return new WP_Error('identity','Reverse order identity/channel/type mismatch.');}
    return $v;
}
function loomara_rt_prepare($o,$r,$parcel){
    if($o->get_status()!=='return-init'||loomara_rt_identity($o)!==$r['identity']||loomara_rt_minor($o->get_total_refunded())!==(int)$r['baseline_refunded']||$o->get_meta('_loomara_return_hold_111')==='yes'){return new WP_Error('identity','Order, refund balance, dispute hold or request state changed.');}
    $forward=loomara_sr_order_preflight($o);if(is_wp_error($forward)||(string)($forward['order_status_code']??'')!=='7'||empty($forward['shipments'])){return new WP_Error('forward','Exact delivered forward shipment must be verified before a reverse pickup.');}
    foreach($forward['shipments'] as $s){if((string)($s['status_code']??'')!=='7' && (string)($s['status']??'')!=='7'){return new WP_Error('forward','Every forward shipment must be delivered.');}}
    if((int)$forward['shiprocket_order_id']!==(int)$r['forward']['order_id']){return new WP_Error('identity','Original forward shipment changed.');}
    $token=loomara_sr_read_token();if(is_wp_error($token)){return $token;}$home=loomara_rt_home($token);if(is_wp_error($home)){return $home;}
    $payload=loomara_rt_payload($o,$r,$home,$parcel);if(is_wp_error($payload)){return $payload;}
    $match=loomara_rt_reverse_match($r,$token);if(is_wp_error($match)){return $match;}if($match){return new WP_Error('duplicate','A reverse order already exists. Reconcile it; do not book again.');}
    $d=loomara_rt_sr('courier/serviceability/',$token,['pickup_postcode'=>$payload['pickup_pincode'],'delivery_postcode'=>$payload['shipping_pincode'],'cod'=>0,'weight'=>$payload['weight'],'length'=>$payload['length'],'breadth'=>$payload['breadth'],'height'=>$payload['height'],'is_return'=>1,'declared_value'=>$payload['sub_total']]);if(is_wp_error($d)){return $d;}
    if(($d['currency']??'')!=='INR'){return new WP_Error('currency','Reverse quote currency could not be confirmed as INR.');}
    $quotes=[];foreach(($d['data']['available_courier_companies']??[]) as $q){
        if(!empty($q['blocked'])||!is_numeric($q['rate']??null)||!is_finite((float)$q['rate'])||(float)$q['rate']<=0||empty($q['courier_company_id'])){continue;}
        $quotes[(int)$q['courier_company_id']]=['id'=>(int)$q['courier_company_id'],'name'=>sanitize_text_field($q['courier_name']??'Courier'),'rate'=>round((float)$q['rate'],2)];
    }
    if(!$quotes){return new WP_Error('quote','No reliable reverse-serviceability quote. Team review required.');}
    return ['at'=>time(),'parcel'=>array_map('floatval',$parcel),'payload_hash'=>hash('sha256',wp_json_encode($payload)),'quotes'=>$quotes];
}
function loomara_rt_booking($o,$seq,$courier,$automatic=false){
    $id=$o->get_id();$book=loomara_rt_book($id);$r=$book['requests'][$seq]??[];$quote=$r['quote']??[];
    if(!loomara_rt_enabled()||($r['stage']??'')!=='requested'||time()-(int)($quote['at']??0)>600||!isset($quote['quotes'][$courier])){return new WP_Error('quote','Prepare a fresh quote before approving pickup.');}
    $fresh=loomara_rt_prepare($o,$r,$quote['parcel']);if(is_wp_error($fresh)){return $fresh;}
    if($fresh['payload_hash']!==$quote['payload_hash']||!isset($fresh['quotes'][$courier])||$fresh['quotes'][$courier]['rate']>$quote['quotes'][$courier]['rate']){return new WP_Error('quote','Pickup details or estimated charges changed. Prepare and approve a new quote.');}
    if($automatic && !loomara_rt_auto_quote_eligible($fresh['quotes'][$courier]['rate'])){return new WP_Error('cap','Automatic pickup requires a quote strictly below ₹100.');}
    $token=loomara_sr_read_token();if(is_wp_error($token)){return $token;}$home=loomara_rt_home($token);if(is_wp_error($home)){return $home;}$payload=loomara_rt_payload($o,$r,$home,$quote['parcel']);if(is_wp_error($payload)){return $payload;}
    if(hash('sha256',wp_json_encode($payload))!==$quote['payload_hash']){return new WP_Error('identity','Pickup details changed before booking.');}
    $o=wc_get_order($id);
    if(!$o||$o->get_status()!=='return-init'||loomara_rt_identity($o)!==$r['identity']||loomara_rt_minor($o->get_total_refunded())!==(int)$r['baseline_refunded']||$o->get_meta('_loomara_return_hold_111')==='yes'||!loomara_rt_enabled()){return new WP_Error('identity','Return changed immediately before booking intent.');}
    $r['stage']='booking_intent';$r['booking_at']=time();$r['approved_by']=$automatic?0:get_current_user_id();$r['approval_source']=$automatic?'quote-below-100':'staff';$r['courier']=$courier;$r['quote_rate']=$fresh['quotes'][$courier]['rate'];$r['parcel']=$quote['parcel'];
    unset($r['quote']);$book['requests'][$seq]=$r;loomara_rt_save($id,$book);
    if((loomara_rt_book($id)['requests'][$seq]??null)!==$r){return new WP_Error('intent','Booking intent could not be saved.');}
    loomara_rt_schedule($id,$seq,60);
    $payload['courier_id']=$courier;
    // May charge the merchant's courier wallet. Exactly one POST per durable
    // return intent. Every later action is GET reconciliation, never a resend.
    loomara_rt_sr('shipments/create/return-shipment',$token,[],$payload);
    $r['stage']='booking_uncertain';$book['requests'][$seq]=$r;loomara_rt_save($id,$book);loomara_rt_schedule($id,$seq,60);
    return true;
}
function loomara_rt_ship_state($d,$r,$token){
    $shipments=$d['shipments']??[];if(isset($shipments['id'])){$shipments=[$shipments];}
    if(!is_array($shipments)||count($shipments)!==1){return new WP_Error('shipment','One exact reverse shipment is required.');}
    $sid=absint($shipments[0]['id']??0);if(!$sid){return new WP_Error('shipment','Reverse shipment ID missing.');}
    $response=loomara_rt_sr('shipments/'.$sid,$token);if(is_wp_error($response)){return $response;}$s=$response['data']??[];
    if((int)($s['id']??0)!==$sid||(int)($s['order_id']??0)!==(int)$d['id']||(int)($s['channel_id']??0)!==(int)$d['channel_id']){return new WP_Error('shipment','Reverse shipment ownership mismatch.');}
    $oc=(string)($d['status_code']??'');$sc=(string)($s['status_code']??(is_numeric($s['status']??null)?$s['status']:''));
    $label=strtoupper(str_replace('_',' ',(string)($d['status']??'')));
    $shipment_label=strtoupper(str_replace('_',' ',(string)($s['status']??'')));
    if($oc==='26'&&$label==='RETURN DELIVERED'&&$sc==='7'&&in_array($shipment_label,['7','DELIVERED'],true)){$stage='received';}
    elseif(in_array($oc,['25','32','48'],true)&&in_array($sc,['6','18','42','17'],true)){$stage='collected';}
    elseif(in_array($oc,['21','22','23','28','31','47'],true)&&in_array($sc,['1','3','4','15','19','11'],true)){$stage=$sc==='11'?'booking_uncertain':'pickup';}
    else{return new WP_Error('state','Reverse shipment is not in a verified supported state. Team review required.');}
    $awb=(string)($s['awb']??'');$url='';
    if(preg_match('/^[A-Za-z0-9-]{3,100}$/D',$awb)){
        $track=loomara_rt_sr('courier/track/shipment/'.$sid,$token);
        if(!is_wp_error($track)){foreach(($track['tracking_data']['shipment_track']??[]) as $t){if((string)($t['awb_code']??'')===$awb){$url=loomara_tracking_url_112($track['tracking_data']['track_url']??'');}}}
    }else{$awb='';}
    return ['stage'=>$stage,'shiprocket_id'=>(int)$d['id'],'shipment_id'=>$sid,'awb'=>$awb,'tracking_url'=>$url,'checked_at'=>time()];
}
function loomara_rt_shipping_worker($id,$seq){
    // Already submitted reverse bookings must still be reconciled when new
    // returns are disabled. This worker never creates a shipment or moves money.
    $lock='loomara_return_lock_'.absint($id);if(!add_option($lock,time(),'','no')){loomara_rt_schedule($id,$seq);return;}
    try{
        $o=wc_get_order($id);$book=loomara_rt_book($id);$r=$book['requests'][$seq]??[];
        if(!$o||!in_array($r['stage']??'',['booking_intent','booking_uncertain','pickup','collected'],true)){return;}
        if(!isset(loomara_rt_labels()[$o->get_status()])){$r['stage']='review';$r['message']='Order status changed outside the return flow. Reconcile before continuing.';$book['requests'][$seq]=$r;loomara_rt_save($id,$book);$o->add_order_note($r['message']);return;}
        if(loomara_rt_identity($o)!==$r['identity']||time()-(int)$r['created']>30*DAY_IN_SECONDS){$r['stage']='review';$r['message']='Return identity changed or tracking exceeded thirty days.';}
        else{
            $token=loomara_sr_read_token();$d=is_wp_error($token)?$token:loomara_rt_reverse_match($r,$token);
            if(is_wp_error($d)||!$d){$r['attempts']=(int)$r['attempts']+1;if($r['attempts']<96){$book['requests'][$seq]=$r;loomara_rt_save($id,$book);loomara_rt_schedule($id,$seq);return;}$r['stage']='review';$r['message']='Exact reverse record could not be reconciled. Do not create another booking.';}
            else{
                if(isset($r['shipping']['shiprocket_id'])&&(int)$r['shipping']['shiprocket_id']!==(int)$d['id']){$s=new WP_Error('identity','Reverse identity changed.');}else{$s=loomara_rt_ship_state($d,$r,$token);}
                if(is_wp_error($s)){$r['stage']='review';$r['message']=$s->get_error_message();}
                else{$old=$r['stage'];$r['shipping']=$s;$r['stage']=$s['stage'];if($r['stage']==='received'){$r['received_at']=time();}
                    if($old==='collected'&&in_array($r['stage'],['pickup','booking_uncertain'],true)){$r['stage']='collected';}
                    $book['requests'][$seq]=$r;loomara_rt_save($id,$book);$o->update_status(loomara_rt_status($r['stage']),'Verified reverse shipment update for '.$r['reference'].'.');
                    if($old!==$r['stage']){loomara_rt_notify($o,$r,loomara_rt_labels()[loomara_rt_status($r['stage'])],'Return status updated. View your order for reverse tracking and next steps. Receipt is not approval of a refund.');}
                    if($r['stage']!=='received'){loomara_rt_schedule($id,$seq);}return;
                }
            }
        }
        $book['requests'][$seq]=$r;loomara_rt_save($id,$book);$o->update_status('return-review',$r['message']);
    }catch(Throwable $e){
        $saved=loomara_rt_book($id);if(isset($saved['requests'][$seq])){$saved['requests'][$seq]['stage']='review';$saved['requests'][$seq]['message']='Reverse tracking requires review after an unexpected response; never rebook blindly.';loomara_rt_save($id,$saved);}
    }finally{delete_option($lock);}
}
add_action('loomara_return_check','loomara_rt_shipping_worker',10,2);
function loomara_rt_auto_quote_eligible($rate){return is_numeric($rate)&&is_finite((float)$rate)&&loomara_rt_minor($rate)>0&&loomara_rt_minor($rate)<10000;}
function loomara_rt_saved_parcel($o,$r){
    $parcel=['length'=>0,'breadth'=>0,'height'=>0,'weight'=>0];
    foreach($r['selection'] as $id=>$qty){$item=$o->get_item($id);$p=$item?$item->get_product():false;if(!$p||$p->is_virtual()){return new WP_Error('parcel','Return package needs team verification.');}
        $w=(float)wc_get_weight($p->get_weight(),'kg');$l=(float)wc_get_dimension($p->get_length(),'cm');$b=(float)wc_get_dimension($p->get_width(),'cm');$h=(float)wc_get_dimension($p->get_height(),'cm');
        if(min($w,$l,$b,$h)<=0||!is_finite($w+$l+$b+$h)){return new WP_Error('parcel','Saved parcel data incomplete.');}
        $parcel['weight']+=$w*$qty;$parcel['length']=max($parcel['length'],$l);$parcel['breadth']=max($parcel['breadth'],$b);$parcel['height']+=$h*$qty;
    }return $parcel;
}
function loomara_rt_auto_quote_worker($id,$seq){
    if(!loomara_rt_enabled()){return;}$lock='loomara_return_lock_'.absint($id);if(!add_option($lock,time(),'','no')){return;}
    try{
        $o=wc_get_order($id);$book=loomara_rt_book($id);$r=$book['requests'][$seq]??[];
        if(!$o||($r['stage']??'')!=='requested'||isset($r['auto_quote_checked_at'])){return;}
        $parcel=loomara_rt_saved_parcel($o,$r);$quote=is_wp_error($parcel)?$parcel:loomara_rt_prepare($o,$r,$parcel);
        $r['auto_quote_checked_at']=time();
        if(is_wp_error($quote)){$r['message']='Automatic quote unavailable; team pickup review required.';$book['requests'][$seq]=$r;loomara_rt_save($id,$book);return;}
        $r['quote']=$quote;$book['requests'][$seq]=$r;loomara_rt_save($id,$book);$choices=$quote['quotes'];uasort($choices,static function($a,$b){return $a['rate']<=>$b['rate'];});
        foreach($choices as $q){if(loomara_rt_auto_quote_eligible($q['rate'])){$result=loomara_rt_booking($o,$seq,$q['id'],true);if(is_wp_error($result)){$saved=loomara_rt_book($id);$saved['requests'][$seq]['message']='Automatic pickup could not pass final checks. Team review required.';loomara_rt_save($id,$saved);}return;}}
        $r['message']='No quote below ₹100. Team approval required before pickup.';$book['requests'][$seq]=$r;loomara_rt_save($id,$book);
    }catch(Throwable $e){
        $saved=loomara_rt_book($id);if(isset($saved['requests'][$seq])){$saved['requests'][$seq]['message']='Pickup checks need team review. Preserve any existing booking intent.';loomara_rt_save($id,$saved);if(in_array($saved['requests'][$seq]['stage'],['booking_intent','booking_uncertain'],true)){loomara_rt_schedule($id,$seq);}}
    }finally{delete_option($lock);}
}
add_action('loomara_return_auto_quote','loomara_rt_auto_quote_worker',10,2);
function loomara_rt_connection_preflight(){
    $token=loomara_sr_read_token();if(is_wp_error($token)){return $token;}
    $home=loomara_rt_home($token);if(is_wp_error($home)){return $home;}
    $reference='LM-RET-PREFLIGHT-'.substr(hash('sha256',home_url('/')),0,16);
    $list=loomara_rt_sr('orders/processing/return',$token,['search'=>$reference,'per_page'=>100,'page'=>1]);if(is_wp_error($list)){return $list;}
    if(!is_array($list['data']??null)||!isset($list['meta']['pagination']['total_pages'])||(int)$list['meta']['pagination']['total_pages']>1){return new WP_Error('schema','Reverse-order search pagination could not be verified. Keep returns disabled.');}
    $channel=loomara_sr_woocommerce_channel();if(is_wp_error($channel)){return $channel;}
    $payment=get_option('woocommerce_razorpay_settings',[]);if(empty($payment['key_id'])||empty($payment['key_secret'])){return new WP_Error('payment','Saved Razorpay connection is incomplete.');}
    return ['ok'=>true,'at'=>time(),'message'=>'Saved Home return contact and reverse-list response verified read-only. No booking, quote, refund, order change or actual courier-create permission test.'];
}
