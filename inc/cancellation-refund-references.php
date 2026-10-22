<?php
/** Future completed cancellation refunds: GET-only bank-reference enrichment. */
defined('ABSPATH') || exit;
function loomara_cb_key($id){return 'loomara_cancel_bank_refs_'.absint($id);}
function loomara_cb_refs($remote){
    $out=[];foreach(['arn','rrn','utr'] as $key){$v=$remote['acquirer_data'][$key]??null;if(is_scalar($v)&&preg_match('/^[A-Za-z0-9][A-Za-z0-9 -]{2,99}$/D',(string)$v)){$out[strtoupper($key)]=(string)$v;}}return $out;
}
function loomara_cb_schedule($id,$delay=21600){
    $job=get_option(loomara_cb_key($id),[]);if(($job['stage']??'')!=='waiting'){return;}
    $job['next_check']=time()+$delay;update_option(loomara_cb_key($id),$job,false);
    if(!wp_next_scheduled('loomara_cancel_bank_reference',[$id])){wp_schedule_single_event($job['next_check'],'loomara_cancel_bank_reference',[$id]);}
}
function loomara_ar_bank_enroll($o,$job,$remote){
    if(($job['stage']??'')!=='complete'||loomara_cb_refs($remote)||($remote['status']??'')!=='processed'||($remote['id']??'')!==($job['refund_id']??'')){return;}
    $record=['stage'=>'waiting','created'=>time(),'checks'=>0,'payment'=>$job['payment'],'refund'=>$job['refund_id'],'receipt'=>$job['receipt'],'amount'=>(int)$job['amount'],'before_refunded'=>(int)$job['before_refunded']];
    if(add_option(loomara_cb_key($o->get_id()),$record,'','no')){loomara_cb_schedule($o->get_id(),3600);}
}
function loomara_cb_notify($o,$event,$message){
    $mailer=WC()->mailer();$emails=$mailer->get_emails();
    $customer=loomara_customer_payment_recipient($o->get_billing_email(),$o);
    $team=isset($emails['WC_Email_New_Order'])?$emails['WC_Email_New_Order']->get_recipient():'';
    foreach(['customer'=>$customer,'team'=>$team] as $audience=>$recipient){
        $parts=array_filter(array_map('trim',explode(',',(string)$recipient)));if(!$parts){continue;}
        foreach($parts as $address){if(!is_email($address)){continue 2;}}
        $key='loomara_cb_mail_'.$o->get_id().'_'.$event.'_'.$audience;
        if(!add_option($key,'sending','','no')){continue;}
        try{$subject='Loomara Living — refund reference update — '.$o->get_order_number();$html=$mailer->wrap_message('Refund reference update','<p>Order '.esc_html($o->get_order_number()).'</p><p>'.esc_html($message).'</p><p><a href="'.esc_url($o->get_view_order_url()).'">View order</a></p>');
            update_option($key,wc_mail(implode(',',$parts),$subject,$html,['Content-Type: text/html; charset=UTF-8'])?'accepted':'failed',false);
        }catch(Throwable $e){update_option($key,'uncertain',false);}
    }
}
function loomara_cb_review($o,$job,$message){
    $job['stage']='review';$job['message']=$message;update_option(loomara_cb_key($o->get_id()),$job,false);
    $o->add_order_note('Refund bank-reference follow-up needs review: '.$message.' The existing refund remains processed; do not refund again.');
    loomara_cb_notify($o,'review','Your refund remains processed. Bank-reference verification needs our team’s review. Quote Razorpay refund '.$job['refund'].' when contacting us.');
}
function loomara_cb_worker($id){
    $id=absint($id);$job=get_option(loomara_cb_key($id),[]);if(($job['stage']??'')!=='waiting'){return;}
    $o=wc_get_order($id);if(!$o instanceof WC_Order){return;}
    if(time()-(int)$job['created']>=14*86400||(int)$job['checks']>=56){loomara_cb_review($o,$job,'Bounded fourteen-day reference follow-up exhausted.');return;}
    if(time()<(int)($job['next_check']??0)){return;}
    $lock='loomara_refund_110_lock_'.$id;if(!add_option($lock,time(),'','no')){loomara_cb_schedule($id);return;}
    try{
        $job['checks']++;$job['last_check']=time();update_option(loomara_cb_key($id),$job,false);
        $original=get_option(loomara_ar_key($id),[]);
        if(($original['payment']??'')!==$job['payment']||(int)($original['before_refunded']??-1)!==$job['before_refunded']||$job['amount']<=0){loomara_cb_review($o,$job,'Saved refund payment or balance identity changed.');return;}
        if(($original['stage']??'')!=='complete'||$o->get_status()!=='refunded'||$o->get_payment_method()!=='razorpay'||$o->get_currency()!=='INR'||$o->get_transaction_id()!==$job['payment']||($original['refund_id']??'')!==$job['refund']||($original['receipt']??'')!==$job['receipt']||(int)($original['amount']??0)!==$job['amount']||(int)round((float)$o->get_total_refunded()*100)!==$job['before_refunded']+$job['amount']){loomara_cb_review($o,$job,'Payment, status or refund identity changed.');return;}
        $list=loomara_ar_rzp_request('GET','payments/'.$job['payment'].'/refunds?count=100');
        if(is_wp_error($list)||!is_array($list['items']??null)||count($list['items'])>=100||(int)($list['count']??-1)!==count($list['items'])){loomara_cb_schedule($id);return;}
        $matches=array_values(array_filter($list['items'],static fn($r)=>is_array($r)&&($r['id']??'')===$job['refund']));
        if(!$matches){loomara_cb_schedule($id);return;}
        if(count($matches)!==1){loomara_cb_review($o,$job,'Ambiguous refund response.');return;}
        $r=$matches[0];
        if(($r['status']??'')!=='processed'||($r['receipt']??'')!==$job['receipt']||($r['payment_id']??'')!==$job['payment']||($r['currency']??'')!=='INR'||(int)($r['amount']??0)!==$job['amount']){loomara_cb_review($o,$job,'Gateway refund identity changed.');return;}
        $refs=loomara_cb_refs($r);if(!$refs){loomara_cb_schedule($id);return;}
        $records=array_values(array_filter($o->get_refunds(),static function($f)use($o,$job){return $f instanceof WC_Order_Refund&&(int)$f->get_parent_id()===$o->get_id()&&$f->get_reason()==='Automatic cancellation refund ['.$job['receipt'].']'&&$f->get_meta('_loomara_razorpay_refund_id')===$job['refund']&&$f->get_meta('_loomara_refund_payment_id')===$job['payment']&&(int)round((float)$f->get_amount()*100)===$job['amount'];}));
        if(count($records)!==1){loomara_cb_review($o,$job,'Exact native refund record unavailable.');return;}
        $f=$records[0];$old=$f->get_meta('_loomara_refund_bank_references');$old=is_array($old)?$old:[];
        foreach($refs as $key=>$value){if(isset($old[$key])&&$old[$key]!==$value){loomara_cb_review($o,$job,'Existing bank reference conflicts with verified response.');return;}}
        $f->update_meta_data('_loomara_refund_bank_references',array_merge($old,$refs));$f->save();
        $job['stage']='complete';$job['references']=$refs;update_option(loomara_cb_key($id),$job,false);
        loomara_cb_notify($o,'available','Razorpay refund '.$job['refund'].'. '.implode(' · ',array_map(static fn($k,$v)=>$k.': '.$v,array_keys($refs),array_values($refs))).'. Bank posting times may vary.');
    }catch(Throwable $e){loomara_cb_review($o,$job,'Reference lookup needs staff reconciliation.');}
    finally{delete_option($lock);}
}
add_action('loomara_cancel_bank_reference','loomara_cb_worker');
