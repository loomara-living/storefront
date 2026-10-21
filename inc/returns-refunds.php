<?php
/** Return refunds: only after verified reverse delivery AND staff inspection. */
defined('ABSPATH') || exit;
function loomara_rt_refund_schedule($id,$seq,$delay=60){if(!wp_next_scheduled('loomara_return_refund',[$id,$seq])){wp_schedule_single_event(time()+$delay,'loomara_return_refund',[$id,$seq]);}}
function loomara_rt_inspect($o,$seq,$approve,$note){
    $id=$o->get_id();$book=loomara_rt_book($id);$r=$book['requests'][$seq]??[];
    if(($r['stage']??'')!=='received'||$o->get_status()!=='return-received'||loomara_rt_identity($o)!==$r['identity']){return new WP_Error('inspection','This return has not reached verified inspection readiness.');}
    $note=sanitize_textarea_field($note);if(strlen($note)<5||strlen($note)>1500){return new WP_Error('inspection','Record a clear inspection result (5–1,500 characters).');}
    if($approve){
        $token=loomara_sr_read_token();$d=is_wp_error($token)?$token:loomara_rt_reverse_match($r,$token);$state=is_wp_error($d)||!$d?$d:loomara_rt_ship_state($d,$r,$token);
        if(is_wp_error($state)||!is_array($state)||($state['stage']??'')!=='received'||(int)($state['shiprocket_id']??0)!==(int)($r['shipping']['shiprocket_id']??0)){return new WP_Error('inspection','Fresh exact return-delivery confirmation is required.');}
    }
    $r['inspection']=['by'=>get_current_user_id(),'at'=>time(),'approved'=>$approve,'note'=>$note];$r['stage']=$approve?'approved':'inspection_rejected';
    $book['requests'][$seq]=$r;loomara_rt_save($id,$book);
    $o->update_status(loomara_rt_status($r['stage']),($approve?'Return inspection approved.':'Return inspection rejected.').' '.$r['reference'].': '.$note);
    loomara_rt_notify($o,$r,$approve?'Return complete':'Return inspection update',$approve?'Your returned items passed inspection. We will verify and request the eligible refund to the original payment method. No bank credit is confirmed yet.':'Your return did not pass inspection. Please view the order for the inspection result or contact us.');
    if($approve){loomara_rt_refund_schedule($id,$seq);}return true;
}
function loomara_rt_refund_review($o,$book,$seq,$message){
    loomara_rt_review($o,$book,$seq,$message,'refund');
}
function loomara_rt_refund_reconcile($o,$book,$seq){
    $r=$book['requests'][$seq];$f=$r['refund'];
    $list=loomara_ar_rzp_request('GET','payments/'.$f['payment'].'/refunds?count=100');
    if(is_wp_error($list)||!is_array($list['items']??null)||count($list['items'])>=100||(int)($list['count']??-1)!==count($list['items'])){
        loomara_rt_retry($o,$book,$seq,'refund','Refund verification response unavailable or incomplete. No second refund will be submitted.');return;
    }
    $matches=array_values(array_filter($list['items'],static function($v)use($f){return ($v['receipt']??'')===$f['receipt'];}));
    if(!$matches){loomara_rt_retry($o,$book,$seq,'refund','Submitted refund not found yet. Read-only reconciliation only.');return;}
    if(count($matches)!==1){loomara_rt_refund_review($o,$book,$seq,'Multiple matching gateway refunds.');return;}$v=$matches[0];
    if(($v['payment_id']??'')!==$f['payment']||($v['currency']??'')!=='INR'||(int)($v['amount']??0)!==$r['plan']['amount']||!preg_match('/^rfnd_[A-Za-z0-9]+$/D',(string)($v['id']??''))){loomara_rt_refund_review($o,$book,$seq,'Gateway refund identity or amount mismatch.');return;}
    if(($v['status']??'')==='pending'){
        if(time()-(int)$f['submitted_at']>7*DAY_IN_SECONDS){loomara_rt_refund_review($o,$book,$seq,'Gateway refund still pending after seven days.');}else{$r['last_check']=time();$r['next_check']=time()+3600;unset($r['refund_retry']);$book['requests'][$seq]=$r;loomara_rt_save($o->get_id(),$book);loomara_rt_refund_schedule($o->get_id(),$seq,3600);}return;
    }
    if(($v['status']??'')!=='processed'){loomara_rt_refund_review($o,$book,$seq,'Gateway did not confirm processed refund.');return;}
    $o=wc_get_order($o->get_id());if(!$o||loomara_rt_identity($o)!==$r['identity']){if($o){loomara_rt_refund_review($o,$book,$seq,'Order changed during refund reconciliation.');}return;}
    $reason='Return refund '.$r['reference'];$existing=[];
    foreach($o->get_refunds() as $refund){if($refund->get_reason()===$reason){$existing[]=$refund;}}
    if(count($existing)>1||($existing&&(loomara_rt_minor($existing[0]->get_amount())!==$r['plan']['amount']||$existing[0]->get_meta('_loomara_razorpay_refund_id')!==$v['id']))){loomara_rt_refund_review($o,$book,$seq,'Existing local refund requires reconciliation.');return;}
    if(!$existing){
        if(loomara_rt_minor($o->get_total_refunded())!==$f['before_refunded']){loomara_rt_refund_review($o,$book,$seq,'Refund ledger changed while the provider processed the return refund.');return;}
        $lines=[];foreach($r['plan']['lines'] as $id=>$line){$lines[$id]=['qty'=>$line['qty'],'refund_total'=>$line['net']/100,'refund_tax'=>array_map(static function($n){return $n/100;},$line['tax'])];}
        $refund=wc_create_refund(['order_id'=>$o->get_id(),'amount'=>$r['plan']['amount']/100,'reason'=>$reason,'line_items'=>$lines,'refund_payment'=>false,'restock_items'=>false,'_loomara_verified_refund'=>array_intersect_key($v,array_flip(['id','payment_id','amount','currency','status','acquirer_data']))]);
        if(is_wp_error($refund)){loomara_rt_refund_review($o,$book,$seq,'Razorpay processed the refund but local recording failed. Do not refund again.');return;}
        $refund->update_meta_data('_loomara_razorpay_refund_id',$v['id']);$refund->save();
    }
    $r['stage']='complete';$r['refund']['id']=$v['id'];$r['completed_at']=time();$r['last_check']=time();unset($r['next_check'],$r['refund_retry']);
    foreach(['arn','rrn','utr'] as $key){$value=$v['acquirer_data'][$key]??null;if(is_scalar($value)&&preg_match('/^[A-Za-z0-9][A-Za-z0-9 -]{2,99}$/D',(string)$value)){$r['bank_complete']=true;}}
    $book['requests'][$seq]=$r;loomara_rt_save($o->get_id(),$book);
    if(empty($r['bank_complete'])){loomara_rt_bank_schedule($o->get_id(),$seq,3600);}
    $o=wc_get_order($o->get_id());$full=loomara_rt_minor($o->get_total_refunded())>=loomara_rt_minor($o->get_total());
    $o->update_status($full?'refunded':'part-refunded','Return refund processed by Razorpay: '.$v['id'].'. No second gateway refund or automatic stock adjustment.');
    loomara_rt_notify($o,$r,$full?'Refunded':'Partially refunded','Razorpay has processed the return refund. Please check your refund email for the amount and bank references. Bank posting times may vary.');
}
function loomara_rt_refund_worker($id,$seq){
    $lock='loomara_return_lock_'.absint($id);$financial='loomara_refund_110_lock_'.absint($id);
    if(!add_option($lock,time(),'','no')){loomara_rt_refund_schedule($id,$seq,900);return;}$money=false;
    try{
        if(!add_option($financial,time(),'','no')){loomara_rt_refund_schedule($id,$seq,900);return;}$money=true;
        $o=wc_get_order($id);$book=loomara_rt_book($id);$r=$book['requests'][$seq]??[];
        if(!$o||!in_array($r['stage']??'',['approved','refund_intent','refund_pending'],true)){return;}
        if((int)($r['next_check']??0)>time()){return;}
        if(loomara_rt_identity($o)!==$r['identity']){loomara_rt_refund_review($o,$book,$seq,'Order ownership/payment/items changed.');return;}
        if(in_array($r['stage'],['refund_intent','refund_pending'],true)){loomara_rt_refund_reconcile($o,$book,$seq);return;}
        if(!loomara_rt_enabled()||empty($r['inspection']['approved'])){return;}
        if($o->get_status()!=='return-complete'||$o->get_meta('_loomara_return_hold_111')==='yes'||$o->get_payment_method()!=='razorpay'||!preg_match('/^pay_[A-Za-z0-9]+$/D',(string)$o->get_transaction_id())){loomara_rt_refund_review($o,$book,$seq,'Payment method, dispute hold or return status needs review.');return;}
        $token=loomara_sr_read_token();$d=is_wp_error($token)?$token:loomara_rt_reverse_match($r,$token);$s=is_wp_error($d)||!$d?$d:loomara_rt_ship_state($d,$r,$token);
        if(is_wp_error($s)&&loomara_rt_temporary($s)){loomara_rt_retry($o,$book,$seq,'refund','Reverse delivery verification temporarily unavailable. No refund submitted.');return;}
        if(is_wp_error($s)||!is_array($s)||($s['stage']??'')!=='received'||(int)($s['shiprocket_id']??0)!==(int)$r['shipping']['shiprocket_id']){loomara_rt_refund_review($o,$book,$seq,'Reverse delivery no longer verifiable.');return;}
        $pay=(string)$o->get_transaction_id();$p=loomara_ar_rzp_request('GET','payments/'.$pay);$list=loomara_ar_rzp_request('GET','payments/'.$pay.'/refunds?count=100');
        $balance=loomara_ar_payment_plan($o,$p,$list);
        if((is_wp_error($p)&&loomara_rt_temporary($p))||(is_wp_error($list)&&loomara_rt_temporary($list))){loomara_rt_retry($o,$book,$seq,'refund','Payment verification temporarily unavailable. No refund submitted.');return;}
        if(is_wp_error($balance)||$balance<$r['plan']['amount']||loomara_rt_minor($o->get_total_refunded())!==(int)$r['baseline_refunded']){loomara_rt_refund_review($o,$book,$seq,'Captured payment, selected-item entitlement or refundable balance requires review.');return;}
        $o=wc_get_order($id);
        if(loomara_rt_identity($o)!==$r['identity']||$o->get_status()!=='return-complete'||$o->get_meta('_loomara_return_hold_111')==='yes'||!loomara_rt_enabled()||loomara_ar_payment_plan($o,$p,$list)!==$balance){loomara_rt_refund_review($o,$book,$seq,'Order or payment changed before refund intent.');return;}
        unset($r['next_check'],$r['refund_retry']);
        $r['refund']=['payment'=>$pay,'receipt'=>'LM-RETURN-'.substr(hash('sha256',home_url('/').'|'.$id.'|'.$seq.'|'.$pay),0,28),'before_refunded'=>loomara_rt_minor($o->get_total_refunded()),'submitted_at'=>time()];
        $r['stage']='refund_intent';$book['requests'][$seq]=$r;loomara_rt_save($id,$book);
        if((loomara_rt_book($id)['requests'][$seq]??null)!==$r){throw new RuntimeException('Durable refund intent unavailable.');}
        loomara_rt_refund_schedule($id,$seq);
        $o->update_status('refund-initiated','Return refund request initiated; not yet confirmed processed.');
        loomara_ar_rzp_request('POST','payments/'.$pay.'/refund',['amount'=>$r['plan']['amount'],'speed'=>'normal','receipt'=>$r['refund']['receipt'],'notes'=>['woocommerce_order_id'=>(string)$id,'return_reference'=>$r['reference']]],$r['refund']['receipt']);
        $r['stage']='refund_pending';$book['requests'][$seq]=$r;loomara_rt_save($id,$book);loomara_rt_refund_schedule($id,$seq);
    }catch(Throwable $e){if(isset($o,$book)&&$o){loomara_rt_refund_review($o,$book,$seq,'Unexpected refund error. Reconcile any existing intent before further action.');}}
    finally{if($money){delete_option($financial);}delete_option($lock);}
}
add_action('loomara_return_refund','loomara_rt_refund_worker',10,2);
function loomara_rt_bank_schedule($id,$seq,$delay=21600){if(!wp_next_scheduled('loomara_return_bank_reference',[$id,$seq])){wp_schedule_single_event(time()+$delay,'loomara_return_bank_reference',[$id,$seq]);}}
function loomara_rt_bank_worker($id,$seq){
    $lock='loomara_return_lock_'.absint($id);if(!add_option($lock,time(),'','no')){loomara_rt_bank_schedule($id,$seq);return;}
    try{
        $o=wc_get_order($id);$book=loomara_rt_book($id);$r=$book['requests'][$seq]??[];
        if(!$o||($r['stage']??'')!=='complete'||!empty($r['bank_complete'])||loomara_rt_identity($o)!==$r['identity']){return;}
        $r['bank_checks']=(int)($r['bank_checks']??0)+1;
        if(time()-(int)$r['completed_at']>14*DAY_IN_SECONDS||$r['bank_checks']>56){$r['bank_review']=true;$book['requests'][$seq]=$r;loomara_rt_save($id,$book);loomara_rt_notify($o,$r,'Refund reference update pending','Your refund remains processed. The bank tracking reference has not been verified yet; please contact us and quote the Razorpay refund reference if you need help.');return;}
        $list=loomara_ar_rzp_request('GET','payments/'.$r['refund']['payment'].'/refunds?count=100');$matches=[];
        if(!is_wp_error($list)&&is_array($list['items']??null)&&count($list['items'])<100&&(int)($list['count']??-1)===count($list['items'])){
            foreach($list['items'] as $v){if(($v['id']??'')===($r['refund']['id']??'')){$matches[]=$v;}}
        }
        if(count($matches)===1){$v=$matches[0];
            if(($v['status']??'')==='processed'&&($v['receipt']??'')===$r['refund']['receipt']&&($v['payment_id']??'')===$o->get_transaction_id()&&($v['currency']??'')==='INR'&&(int)($v['amount']??0)===$r['plan']['amount']){
                $refunds=array_values(array_filter($o->get_refunds(),static function($f)use($v,$o){return $f instanceof WC_Order_Refund&&(int)$f->get_parent_id()===$o->get_id()&&$f->get_meta('_loomara_razorpay_refund_id')===$v['id']&&$f->get_meta('_loomara_refund_payment_id')===$v['payment_id']&&loomara_rt_minor($f->get_amount())===(int)$v['amount'];}));
                $refs=[];foreach(['arn','rrn','utr'] as $k){$value=$v['acquirer_data'][$k]??null;if(is_scalar($value)&&preg_match('/^[A-Za-z0-9][A-Za-z0-9 -]{2,99}$/D',(string)$value)){$refs[strtoupper($k)]=(string)$value;}}
                if(count($refunds)===1&&$refs){$f=$refunds[0];$old=$f->get_meta('_loomara_refund_bank_references');$old=is_array($old)?$old:[];$conflict=false;foreach($refs as $k=>$value){if(isset($old[$k])&&$old[$k]!==$value){$conflict=true;}}
                    if(!$conflict){$f->update_meta_data('_loomara_refund_bank_references',array_merge($old,$refs));$f->save();$r['bank_complete']=true;$book['requests'][$seq]=$r;loomara_rt_save($id,$book);loomara_rt_notify($o,$r,'Refund bank reference available','Razorpay refund '.$v['id'].'. '.implode(' · ',array_map(static function($k,$v){return $k.': '.$v;},array_keys($refs),array_values($refs))).'. Bank posting times may vary.');return;}
                }
            }
        }
        $book['requests'][$seq]=$r;loomara_rt_save($id,$book);loomara_rt_bank_schedule($id,$seq);
    }catch(Throwable $e){
        if(isset($o,$book,$r)&&$o&&($r['stage']??'')==='complete'){$r['bank_review']=true;$book['requests'][$seq]=$r;loomara_rt_save($id,$book);loomara_rt_notify($o,$r,'Refund reference update pending','Your refund remains processed. Bank-reference verification needs our team’s review. Quote the Razorpay refund reference if you need help.');}
    }finally{delete_option($lock);}
}
add_action('loomara_return_bank_reference','loomara_rt_bank_worker',10,2);
