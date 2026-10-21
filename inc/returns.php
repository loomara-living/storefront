<?php
/** Item-level customer returns. Local implementation; off until reviewed rollout. */
defined('ABSPATH') || exit;
function loomara_rt_enabled(){return get_option('loomara_returns_enabled')==='yes';}
function loomara_rt_key($id){return 'loomara_returns_v1_'.absint($id);}
function loomara_rt_book($id){$v=get_option(loomara_rt_key($id),[]);return is_array($v)?$v:[];}
function loomara_rt_save($id,$book){update_option(loomara_rt_key($id),$book,false);}
function loomara_rt_labels(){return ['return-init'=>'Return initiated','return-pickup'=>'Return pickup scheduled','return-transit'=>'Return collected','return-received'=>'Return received — inspection pending','return-complete'=>'Return complete','refund-initiated'=>'Refund initiated','part-refunded'=>'Partially refunded','return-review'=>'Return needs review','return-rejected'=>'Return inspection rejected'];}
add_action('init',function(){foreach(loomara_rt_labels() as $slug=>$label){if(!get_post_status_object('wc-'.$slug)){register_post_status('wc-'.$slug,['label'=>$label,'public'=>false,'internal'=>false,'exclude_from_search'=>false,'show_in_admin_all_list'=>true,'show_in_admin_status_list'=>true,'label_count'=>_n_noop($label.' <span class="count">(%s)</span>',$label.' <span class="count">(%s)</span>','loomara-living')]);}}});
add_filter('wc_order_statuses',function($s){foreach(loomara_rt_labels() as $k=>$v){$s['wc-'.$k]=$v;}return $s;},30);
add_filter('woocommerce_order_is_paid',function($paid,$o){return $o instanceof WC_Order&&isset(loomara_rt_labels()[$o->get_status()])?(bool)$o->get_date_paid():$paid;},30,2);
function loomara_rt_active($book){foreach(($book['requests']??[]) as $r){if(!in_array($r['stage']??'', ['complete','declined','inspection_rejected'],true)){return true;}}return false;}
add_filter('loomara_order_completion_on_hold',function($hold,$o){return $hold||($o instanceof WC_Order&&loomara_rt_active(loomara_rt_book($o->get_id())));},30,2);
function loomara_rt_window($o){
    if(!$o instanceof WC_Order || !in_array($o->get_status(),['delivered','completed','part-refunded','return-rejected'],true) || !$o->get_date_paid()){return false;}
    // Only the verified courier timestamp authorizes the seven-day window.
    $at=(int)$o->get_meta('_loomara_return_delivered_at');
    return $at>0 && $at<=time() && time()<$at+7*DAY_IN_SECONDS;
}
function loomara_rt_remaining($o,$book){
    $out=[];
    foreach($o->get_items('line_item') as $id=>$item){
        $q=(int)$item->get_quantity();$held=0;$done=0;
        foreach(($book['requests']??[]) as $r){$n=(int)($r['selection'][$id]??0);if(($r['stage']??'')==='complete'){$done+=$n;}elseif(($r['stage']??'')!=='declined'){$held+=$n;}}
        // Native/manual partial refunds also consume quantity. Do not subtract
        // our own completed refund twice: use the larger recorded reservation.
        $refunded=abs((int)$o->get_qty_refunded_for_item($id));
        $out[$id]=max(0,$q-max($done,$refunded)-$held);
    }return $out;
}
function loomara_rt_can_request($o,$uid){
    if(!loomara_rt_enabled() || !$o instanceof WC_Order || $uid<1 || (int)$o->get_customer_id()!==$uid || !loomara_rt_window($o)){return false;}
    $book=loomara_rt_book($o->get_id());return !loomara_rt_active($book) && array_sum(loomara_rt_remaining($o,$book))>0;
}
function loomara_rt_minor($v){return (int)round((float)$v*100);}
function loomara_rt_plan($o,$selection,$book){
    if($o->get_currency()!=='INR'||count($o->get_items('fee'))){return new WP_Error('unsupported','This order needs a manual return review. Please contact us.');}
    $remaining=loomara_rt_remaining($o,$book);$lines=[];$amount=0;
    if(!$selection||count($selection)>50){return new WP_Error('selection','Choose at least one item.');}
    foreach($selection as $id=>$raw){
        if(!ctype_digit((string)$id)||!is_scalar($raw)||!preg_match('/^[1-9][0-9]*$/D',(string)$raw)){return new WP_Error('quantity','Invalid return quantity.');}
        $item=$o->get_item((int)$id);$q=(int)$raw;
        if(!$item instanceof WC_Order_Item_Product || !isset($remaining[$id]) || $q>$remaining[$id]){return new WP_Error('quantity','Return quantity exceeds the available purchased quantity.');}
        $n=(int)$item->get_quantity();$used=$n-$remaining[$id];
        if($n<1||(float)$item->get_quantity()!==(float)$n){return new WP_Error('quantity','Fractional purchased quantities need team review.');}
        $portion=static function($v)use($used,$q,$n){$c=loomara_rt_minor($v);return (int)round($c*($used+$q)/$n)-(int)round($c*$used/$n);};
        $net=$portion($item->get_total());$tax=[];
        foreach(($item->get_taxes()['total']??[]) as $rate=>$v){$tax[$rate]=$portion($v);}
        $gross=$net+array_sum($tax);if($net<0||($tax&&min($tax)<0)||$gross<0){return new WP_Error('amount','Refund amount requires review.');}
        $lines[(int)$id]=['qty'=>$q,'net'=>$net,'tax'=>$tax,'gross'=>$gross];$amount+=$gross;
    }
    if($amount<1||$amount>loomara_rt_minor($o->get_total())-loomara_rt_minor($o->get_total_refunded())){return new WP_Error('balance','Refund balance requires review.');}
    return ['lines'=>$lines,'amount'=>$amount];
}
function loomara_rt_identity($o){
    $items=[];foreach($o->get_items('line_item') as $id=>$i){$items[$id]=[$i->get_quantity(),(string)$i->get_total(),$i->get_taxes(),$i->get_product_id(),$i->get_variation_id()];}
    $address=[];foreach(['first_name','last_name','address_1','address_2','city','state','postcode','country','phone'] as $f){$address[$f]=(string)$o->{'get_shipping_'.$f}();}
    return hash('sha256',wp_json_encode([(int)$o->get_customer_id(),$o->get_payment_method(),$o->get_transaction_id(),$o->get_currency(),(string)$o->get_total(),$items,$address]));
}
function loomara_rt_schedule($id,$seq,$delay=900){if(!wp_next_scheduled('loomara_return_check',[$id,$seq])){wp_schedule_single_event(time()+max(1,$delay),'loomara_return_check',[$id,$seq]);}}
function loomara_rt_request($o,$uid,$selection,$reason,$reason_code='other'){
    if(!$o instanceof WC_Order){return new WP_Error('order','Order unavailable.');}
    $id=$o->get_id();$lock='loomara_return_lock_'.$id;$delivery='loomara_delivery_complete_lock_111_'.$id;
    if(!add_option($lock,time(),'','no')){return new WP_Error('busy','A return is already being updated. Try again shortly.');}
    $got_delivery=false;
    try{
        if(!add_option($delivery,time(),'','no')){return new WP_Error('busy','The delivery record is being updated. Please try again.');}$got_delivery=true;
        $o=wc_get_order($id);
        if(!loomara_rt_can_request($o,$uid)){return new WP_Error('window','Return is unavailable, already in progress, or outside the seven-day window.');}
        $reason=sanitize_textarea_field($reason);if(strlen($reason)<5||strlen($reason)>1500){return new WP_Error('reason','Please give a return reason between 5 and 1,500 characters.');}
        $delivery_proof=loomara_rt_delivery_proof($o);
        if(is_wp_error($delivery_proof)){return $delivery_proof;}
        if(time()>=$delivery_proof['at']+7*DAY_IN_SECONDS){return new WP_Error('window','The verified delivery return window has closed.');}
        $o->update_meta_data('_loomara_return_delivered_at',$delivery_proof['at']);$o->save_meta_data();
        // Network checks can take time. Re-read ownership, stock-independent
        // eligibility and the immutable order snapshot before recording a request.
        $o=wc_get_order($id);if(!loomara_rt_can_request($o,$uid)){return new WP_Error('window','Order eligibility changed during verification.');}
        $book=loomara_rt_book($id);$plan=loomara_rt_plan($o,$selection,$book);if(is_wp_error($plan)){return $plan;}
        $policy=loomara_rt_policy($o,$selection,$reason_code);if(is_wp_error($policy)){return $policy;}
        $seq=(int)($book['sequence']??0)+1;
        $r=['seq'=>$seq,'reference'=>'LM-RET-'.$id.'-'.$seq,'created'=>time(),'stage'=>'requested','owner'=>$uid,'identity'=>loomara_rt_identity($o),'selection'=>array_map('intval',$selection),'plan'=>$plan,'baseline_refunded'=>loomara_rt_minor($o->get_total_refunded()),'reason'=>$reason,'delivery_at'=>$delivery_proof['at'],'forward'=>$delivery_proof['forward'],'attempts'=>0];
        $r['reason_code']=$reason_code;$r['policy_review']=$policy['review'];
        $book['sequence']=$seq;$book['requests'][$seq]=$r;loomara_rt_save($id,$book);
        if((loomara_rt_book($id)['requests'][$seq]??null)!==$r){return new WP_Error('persistence','Return request could not be safely saved. Contact us.');}
        $o->update_status('return-init','Customer return request '.$r['reference'].' accepted within the seven-day window. Selected items reserved for return; no pickup or refund confirmed.');
        if(!wp_next_scheduled('loomara_return_auto_quote',[$id,$seq])){wp_schedule_single_event(time()+30,'loomara_return_auto_quote',[$id,$seq]);}
        loomara_rt_notify($o,$r,'Return requested','Your return request has been received. We will review and arrange pickup. No refund is confirmed yet.');
        return $r;
    }catch(Throwable $e){
        $saved=loomara_rt_book($id);if(isset($seq,$saved['requests'][$seq])){$saved['requests'][$seq]['stage']='review';$saved['requests'][$seq]['message']='Request recording needs team review; no pickup or refund confirmed.';loomara_rt_save($id,$saved);}
        return new WP_Error('request','The return request could not finish safely. Please contact us before trying again.');
    }finally{if($got_delivery){delete_option($delivery);}delete_option($lock);}
}
function loomara_rt_notify($o,$r,$title,$text){
    $key='loomara_return_mail_'.$o->get_id().'_'.$r['seq'].'_'.sanitize_key($title);
    if(!add_option($key,'sending','','no')){return;}
    try{
        $user=get_user_by('id',$r['owner']);$emails=WC()->mailer()->get_emails();$new=$emails['WC_Email_New_Order']??null;
        $recipients=[$user->user_email??'',$new?$new->get_recipient():''];$all=true;
        foreach($recipients as $index=>$recipient){
            $list=array_filter(array_map('trim',explode(',',$recipient)),'is_email');
            if(!$list){$all=false;continue;}
            $url=$index===0?$o->get_view_order_url():$o->get_edit_order_url();
            $body='<h2>'.esc_html($title).'</h2><p>Order '.esc_html($o->get_order_number()).' · Return '.esc_html($r['reference']).'</p><p>'.esc_html($text).'</p><p><a href="'.esc_url($url).'">View order and return details</a></p>';
            $all=wc_mail(implode(',',$list),'Loomara Living — '.$title,WC()->mailer()->wrap_message($title,$body),['Content-Type: text/html; charset=UTF-8'])&&$all;
        }update_option($key,$all?'accepted':'uncertain',false);
    }catch(Throwable $e){update_option($key,'uncertain',false);}
}
// A late forward-shipment callback must not overwrite an active reverse flow.
add_action('woocommerce_before_order_object_save',function($o){
    if(!$o instanceof WC_Order || !$o->get_id() || empty($o->get_changes()['status'])){return;}
    $book=loomara_rt_book($o->get_id());
    if(loomara_rt_active($book)&&in_array($o->get_status(),['processing','ready-to-ship','pickup-scheduled','out-for-pickup','picked-up','shipped','delivered','completed'],true)){
        $r=end($book['requests']);$status=loomara_rt_status($r['stage']);$o->set_status($status);
    }
},100);
function loomara_rt_status($stage){return ['requested'=>'return-init','booking_intent'=>'return-init','booking_uncertain'=>'return-review','pickup'=>'return-pickup','collected'=>'return-transit','received'=>'return-received','approved'=>'return-complete','refund_intent'=>'refund-initiated','refund_pending'=>'refund-initiated','review'=>'return-review','inspection_rejected'=>'return-rejected'][$stage]??'return-review';}
require_once __DIR__.'/returns-recovery.php';
require_once __DIR__.'/returns-shiprocket.php';
require_once __DIR__.'/returns-refunds.php';
require_once __DIR__.'/returns-ui.php';
function loomara_rt_queue_delivery($o){
    if(!loomara_rt_enabled()||!$o instanceof WC_Order||!in_array($o->get_status(),['delivered','completed','part-refunded'],true)||$o->get_meta('_loomara_return_delivered_at')){return;}
    if((int)get_transient('loomara_return_delivery_rate_'.$o->get_id())>time()-3600){return;}
    set_transient('loomara_return_delivery_rate_'.$o->get_id(),time(),3600);
    if(!wp_next_scheduled('loomara_return_delivery_verify',[$o->get_id()])){wp_schedule_single_event(time()+1,'loomara_return_delivery_verify',[$o->get_id()]);}
}
add_action('woocommerce_order_status_delivered',function($id,$o=false){loomara_rt_queue_delivery($o instanceof WC_Order?$o:wc_get_order($id));},30,2);
add_action('woocommerce_order_details_before_order_table','loomara_rt_queue_delivery',5);
add_action('loomara_return_delivery_verify',function($id){
    $o=wc_get_order($id);if(!loomara_rt_enabled()||!$o||$o->get_meta('_loomara_return_delivered_at')){return;}
    $proof=loomara_rt_delivery_proof($o);if(!is_wp_error($proof)){$o->update_meta_data('_loomara_return_delivered_at',$proof['at']);$o->save_meta_data();}
});
