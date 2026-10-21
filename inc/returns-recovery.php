<?php
/** Bounded retries and explicit recovery. Never erase a carrier/refund intent. */
defined('ABSPATH') || exit;
function loomara_rt_error_code($error){return is_wp_error($error)?$error->get_error_code():'';}
function loomara_rt_temporary($error){return in_array(loomara_rt_error_code($error),['shiprocket','response','connection','gateway','token'],true);}
function loomara_rt_review($o,$book,$seq,$message,$phase='shipping'){
    $r=$book['requests'][$seq];
    if($r['stage']!=='review'){$r['resume_stage']=$r['stage'];}
    $r['stage']='review';$r['review_phase']=$phase;$r['message']=$message;$r['last_check']=time();unset($r['next_check']);
    $book['requests'][$seq]=$r;loomara_rt_save($o->get_id(),$book);
    $note='Return needs team review: '.$message.' Existing booking/refund intents must be reconciled, not repeated.';
    if(isset(loomara_rt_labels()[$o->get_status()])){$o->update_status('return-review',$note);}else{$o->add_order_note($note);}
    loomara_rt_notify($o,$r,'Return needs review','Your return request remains recorded and needs our team’s attention. No extra payment or repeat request is needed. Our team will verify the shipping/payment information and contact you if anything is required.');
}
function loomara_rt_retry($o,$book,$seq,$phase,$message){
    $r=$book['requests'][$seq];$now=time();$key=$phase.'_retry';$retry=$r[$key]??['started'=>$now,'count'=>0];
    // A submitted refund's original intent time bounds unavailable API responses.
    if($phase==='refund'&&!isset($r[$key])&&!empty($r['refund']['submitted_at'])){$retry['started']=(int)$r['refund']['submitted_at'];}
    $retry['count']++;$r[$key]=$retry;$r['last_check']=$now;$r['message']=$message;
    if($now-(int)$retry['started']>=DAY_IN_SECONDS || $retry['count']>112){$book['requests'][$seq]=$r;loomara_rt_review($o,$book,$seq,'Verification remained unavailable for the bounded waiting window. '.$message,$phase);return false;}
    $delay=$retry['count']<=6?300:900;$r['next_check']=$now+$delay;$book['requests'][$seq]=$r;loomara_rt_save($o->get_id(),$book);
    if($phase==='refund'){loomara_rt_refund_schedule($o->get_id(),$seq,$delay);}elseif($phase==='quote'){
        if(!wp_next_scheduled('loomara_return_auto_quote',[$o->get_id(),$seq])){wp_schedule_single_event($now+$delay,'loomara_return_auto_quote',[$o->get_id(),$seq]);}
    }else{loomara_rt_schedule($o->get_id(),$seq,$delay);}return true;
}
function loomara_rt_resume($o,$seq,$note){
    if(!$o instanceof WC_Order||!current_user_can('manage_woocommerce')||!current_user_can('edit_shop_order',$o->get_id())){return new WP_Error('permission','Order management permission required.');}
    $book=loomara_rt_book($o->get_id());$r=$book['requests'][$seq]??[];$note=sanitize_textarea_field($note);
    if(($r['stage']??'')!=='review'||strlen($note)<5||strlen($note)>1500||loomara_rt_identity($o)!==($r['identity']??'')||$o->get_status()!=='return-review'||$o->get_meta('_loomara_return_hold_111')==='yes'){return new WP_Error('review','Resolve the identity/status/hold issue and record a review note first.');}
    // Presence of an existing intent wins over any UI/old stage value.
    if(!empty($r['refund']['receipt'])&&!empty($r['refund']['submitted_at'])){$stage='refund_pending';$phase='refund';}
    elseif(!empty($r['booking_at'])){$stage='booking_uncertain';$phase='shipping';}
    elseif(($r['review_phase']??'')==='quote'&&loomara_rt_enabled()){$stage='requested';$phase='quote';}
    else{return new WP_Error('review','No submitted intent is available for read-only recovery. Contact support; do not reset the request or create a duplicate.');}
    $r['stage']=$stage;$r[$phase.'_retry']=['started'=>time(),'count'=>0];$r['last_check']=time();$r['next_check']=time()+1;
    if($phase==='quote'){$r['auto_quote_checked_at']=time();unset($r['quote']);}
    $r['review_history'][]=['at'=>time(),'by'=>get_current_user_id(),'note'=>$note];$r['review_history']=array_slice($r['review_history'],-20);
    $book['requests'][$seq]=$r;loomara_rt_save($o->get_id(),$book);$o->add_order_note('Return read-only verification resumed: '.$r['reference'].'. '.$note);
    if($phase==='refund'){loomara_rt_refund_schedule($o->get_id(),$seq,1);}elseif($phase==='shipping'){loomara_rt_schedule($o->get_id(),$seq,1);}
    else{$o->update_status('return-init','Staff review resumed. A fresh quote and explicit booking approval are required; no automatic booking requeued.');}return true;
}
function loomara_rt_reasons(){return ['other'=>'Other / change of mind','size'=>'Size or fit issue','damaged'=>'Damaged on arrival','defective'=>'Manufacturing defect','incorrect'=>'Incorrect item received'];}
function loomara_rt_policy($o,$selection,$reason='other'){
    if(!isset(loomara_rt_reasons()[$reason])){return new WP_Error('policy','Choose a valid return reason.');}$review=false;
    foreach($selection as $id=>$qty){$i=$o->get_item($id);$p=$i?$i->get_product():false;if(!$p){return new WP_Error('policy','Product eligibility needs team review.');}
        $ids=[$i->get_product_id(),$i->get_variation_id()];$restricted=false;
        foreach(array_filter($ids) as $pid){
            $rule=function_exists('get_post_meta')?get_post_meta($pid,'_loomara_return_policy',true):'';
            if(in_array($rule,['review','nonreturnable'],true)){$restricted=true;}
            if($rule!==''&&!in_array($rule,['standard','review','nonreturnable'],true)){$restricted=true;}
            if(function_exists('has_term')&&has_term(['final-sale','clearance','personalised','personalized','made-to-order'],'product_tag',$pid)){$restricted=true;}
        }
        if($restricted){if(!in_array($reason,['damaged','defective','incorrect'],true)){return new WP_Error('policy','This item needs support approval under the return policy. Contact us for help.');}$review=true;}
    }return ['review'=>$review];
}
// Explicit product control; tags above remain a conservative compatibility rule.
add_action('woocommerce_product_options_general_product_data',function(){woocommerce_wp_select(['id'=>'_loomara_return_policy','label'=>'Loomara return eligibility','options'=>['standard'=>'Standard seven-day policy','review'=>'Restricted — team review for defects','nonreturnable'=>'Final sale / personalised — support exceptions only']]);});
add_action('woocommerce_admin_process_product_object',function($p){if(!current_user_can('edit_products')){return;}$v=$_POST['_loomara_return_policy']??null;if(is_string($v)&&in_array($v,['standard','review','nonreturnable'],true)){$p->update_meta_data('_loomara_return_policy',$v);}});
