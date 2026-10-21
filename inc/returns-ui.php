<?php
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts',function(){if(function_exists('is_account_page')&&is_account_page()){wp_enqueue_style('loomara-returns',get_template_directory_uri().'/assets/returns.css',[],'2');}});
add_action('admin_enqueue_scripts',function(){if(($_GET['page']??null)==='loomara-returns'&&current_user_can('manage_woocommerce')){wp_enqueue_style('loomara-returns-admin',get_template_directory_uri().'/assets/returns.css',[],'2');}});
add_filter('woocommerce_my_account_my_orders_actions',function($actions,$o){
    loomara_rt_queue_delivery($o);
    if(loomara_rt_can_request($o,get_current_user_id())){$actions['return']=['url'=>$o->get_view_order_url().'#loomara-return-request','name'=>'Request return'];}return $actions;
},40,2);
function loomara_rt_customer_panel($o){
    if(!$o instanceof WC_Order || !get_current_user_id() || (int)$o->get_customer_id()!==get_current_user_id()){return;}
    $book=loomara_rt_book($o->get_id());$can=loomara_rt_can_request($o,get_current_user_id());
    if(!$can&&empty($book['requests'])){
        if(loomara_rt_enabled()&&in_array($o->get_status(),['delivered','completed','part-refunded'],true)&&!$o->get_meta('_loomara_return_delivered_at')){echo '<p class="woocommerce-info">We are verifying your delivery date for return eligibility. Please check again shortly or contact us for help. The return window is seven days from actual delivery.</p>';}
        return;
    }
    echo '<section class="loomara-returns" id="loomara-return-request"><p class="eyebrow">Care beyond delivery</p><h2>Returns &amp; refunds</h2>';
    if(isset($_GET['return_notice'])){echo '<p role="status">'.esc_html($_GET['return_notice']==='saved'?'Your return request has been saved. Our team will review and arrange pickup.':'The request could not be completed. Please check eligibility and quantities, or contact us.').'</p>';}
    foreach(($book['requests']??[]) as $r){
        $label=$r['stage']==='complete'?'Refund processed':($r['stage']==='declined'?'Return request declined':(loomara_rt_labels()[loomara_rt_status($r['stage'])]??ucfirst($r['stage'])));
        echo '<article class="loomara-return-card"><header><h3>'.esc_html($r['reference']).'</h3><span>'.esc_html($label).'</span></header><ul>';
        foreach($r['selection'] as $id=>$qty){$item=$o->get_item($id);echo '<li>'.esc_html($item?$item->get_name():'Order item').' <strong>× '.absint($qty).'</strong></li>';}
        echo '</ul><p><strong>Selected-item refund estimate:</strong> '.wp_kses_post(wc_price($r['plan']['amount']/100,['currency'=>$o->get_currency()])).'. Subject to inspection and payment verification.</p>';
        if(!empty($r['shipping']['awb'])){echo '<p><strong>Return AWB:</strong> '.esc_html($r['shipping']['awb']).'</p>';}
        $url=loomara_tracking_url_112($r['shipping']['tracking_url']??'');if($url){echo '<a class="button" href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer">Track return</a>';}
        if(!empty($r['inspection'])){echo '<p><strong>Inspection result:</strong> '.esc_html($r['inspection']['note']).'</p>';}
        if($r['stage']==='review'){echo '<p>Your return remains recorded and needs our team’s review. No extra payment or repeat request is needed. We will contact you if anything is required.</p>';}
        if(!empty($r['next_check'])&&$r['stage']!=='review'){echo '<p>Verification is temporarily delayed. We will recheck automatically; no repeat request is needed.</p>';}
        if($r['stage']==='complete'){echo '<p>Return refund processed: <strong>'.esc_html($r['refund']['id']??'').'</strong>. Please check your refund email for the amount and bank references. Bank posting times may vary.</p>';}
        echo '</article>';
    }
    if($can){
        $remaining=loomara_rt_remaining($o,$book);$due=(int)($o->get_meta('_loomara_return_delivered_at')?:$o->get_meta('_loomara_delivered_at_111'))+7*DAY_IN_SECONDS;
        echo '<p>Request by '.esc_html(wp_date('d M Y, g:i a',$due,new DateTimeZone('Asia/Kolkata'))).' IST. Select the items and quantities you want to return.</p><form class="loomara-return-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_return_request"><input type="hidden" name="order_id" value="'.absint($o->get_id()).'">';
        wp_nonce_field('loomara_return_request_'.$o->get_id());
        foreach($o->get_items('line_item') as $id=>$item){$max=$remaining[$id]??0;if(!$max){continue;}
            echo '<label class="loomara-return-item"><span>'.esc_html($item->get_name()).'<small>Up to '.absint($max).' available</small></span><input aria-label="'.esc_attr('Return quantity for '.$item->get_name()).'" type="number" inputmode="numeric" min="0" max="'.absint($max).'" step="1" name="quantities['.absint($id).']" value="0"></label>';
        }
        echo '<label>Return reason<select name="reason_code" required>';foreach(loomara_rt_reasons() as $key=>$label){echo '<option value="'.esc_attr($key).'">'.esc_html($label).'</option>';}echo '</select></label><label>Reason details<textarea name="reason" required minlength="5" maxlength="1500" rows="3" placeholder="Tell us what happened and which items are affected"></textarea></label><p>Pickup uses the original delivery address. Contact us before requesting if it has changed. A verified courier quote below ₹100 can be approved automatically for eligible items; restricted items require team eligibility approval. All received returns need inspection before refund.</p><label class="loomara-return-consent"><input name="condition_confirmed" type="checkbox" value="yes" required><span>I have read the <a href="'.esc_url(home_url('/returns-refunds/')).'">return policy</a> and will return the selected items with their packaging. I understand pickup and refund require verification.</span></label><button class="button" type="submit">Submit return request</button></form>';
    }
    echo '<p><a class="button" href="'.esc_url(home_url('/contact/')).'">Contact us</a></p></section>';
}
add_action('woocommerce_order_details_after_order_table','loomara_rt_customer_panel',25);
function loomara_rt_input_id($key){$v=$_POST[$key]??'';return is_scalar($v)&&preg_match('/^[1-9][0-9]*$/D',(string)$v)?(int)$v:0;}
add_action('admin_post_loomara_return_request',function(){
    $id=loomara_rt_input_id('order_id');check_admin_referer('loomara_return_request_'.$id);$uid=get_current_user_id();$o=wc_get_order($id);
    if(!$uid||!$o||(int)$o->get_customer_id()!==$uid){wp_die('Order unavailable.',403);}
    $selection=[];$valid=is_array($_POST['quantities']??null)&&count($_POST['quantities'])<=50&&($_POST['condition_confirmed']??'')==='yes'&&is_string($_POST['reason']??null);
    if($valid){foreach($_POST['quantities'] as $key=>$value){if(!is_scalar($value)||!preg_match('/^[0-9]+$/D',(string)$value)){$valid=false;break;}if((int)$value>0){$selection[$key]=$value;}}}
    $reason_code=is_string($_POST['reason_code']??null)?sanitize_key(wp_unslash($_POST['reason_code'])):'';
    $result=$valid?loomara_rt_request($o,$uid,$selection,wp_unslash($_POST['reason']),$reason_code):new WP_Error('form','Check the return form.');
    wp_safe_redirect(add_query_arg('return_notice',is_wp_error($result)?'error':'saved',$o->get_view_order_url()).'#loomara-return-request');exit;
});
add_action('admin_menu',function(){add_submenu_page('woocommerce','Customer returns','Customer returns','manage_woocommerce','loomara-returns','loomara_rt_admin_page');});
function loomara_rt_admin_queue(){
    if(!current_user_can('manage_woocommerce')){return;}
    $raw=$_GET['return_page']??1;$page=is_scalar($raw)?max(1,min(100000,absint($raw))):1;
    $result=wc_get_orders(['status'=>array_merge(array_keys(loomara_rt_labels()),['refunded']),'limit'=>25,'page'=>$page,'paginate'=>true,'orderby'=>'date','order'=>'DESC']);
    echo '<section><h2>Return work queue</h2><p>Recent return orders, 25 per page. Submitted intents are never reset by these controls.</p><table class="widefat striped"><thead><tr><th>Order / return</th><th>Stage</th><th>Attention / last check</th><th>Action</th></tr></thead><tbody>';$shown=0;
    foreach(($result->orders??[]) as $o){if(!$o instanceof WC_Order||!current_user_can('edit_shop_order',$o->get_id())){continue;}
        foreach((loomara_rt_book($o->get_id())['requests']??[]) as $r){if(in_array($r['stage'],['complete','declined','inspection_rejected'],true)&&empty($r['bank_review'])){continue;}$shown++;
            echo '<tr><td>'.esc_html($o->get_order_number().' / '.$r['reference']).'</td><td>'.esc_html($r['stage']).'</td><td>'.esc_html(!empty($r['bank_review'])?'Bank-reference follow-up required':($r['message']??'Awaiting next stage')).'<br>'.esc_html(!empty($r['last_check'])?wp_date('d M Y, g:i a',(int)$r['last_check'],new DateTimeZone('Asia/Kolkata')).' IST':'Not yet checked').'</td><td><a class="button" href="'.esc_url(admin_url('admin.php?page=loomara-returns&order_id='.$o->get_id())).'">Review return</a></td></tr>';
        }
    }
    if(!$shown){echo '<tr><td colspan="4">No active return work on this page.</td></tr>';}
    echo '</tbody></table><p>';
    if($page>1){echo '<a class="button" href="'.esc_url(admin_url('admin.php?page=loomara-returns&return_page='.($page-1))).'">Previous</a> ';}
    if($page<(int)($result->max_num_pages??1)){echo '<a class="button" href="'.esc_url(admin_url('admin.php?page=loomara-returns&return_page='.($page+1))).'">Next</a>';}
    echo '</p></section>';
}
function loomara_rt_staff_panel($o){
    if(!$o instanceof WC_Order||!current_user_can('manage_woocommerce')||!current_user_can('edit_shop_order',$o->get_id())){return;}
    $book=loomara_rt_book($o->get_id());if(empty($book['requests'])){return;}
    echo '<section class="loomara-return-admin" style="clear:both"><h3>Customer returns</h3>';
    foreach($book['requests'] as $seq=>$r){
        echo '<div style="padding:12px;border:1px solid #ccc;margin:12px 0"><h4>'.esc_html($r['reference'].' — '.$r['stage']).'</h4><p>Reason: '.esc_html($r['reason']).'</p><p>Selected items: ';
        foreach($r['selection'] as $iid=>$qty){$item=$o->get_item($iid);echo esc_html(($item?$item->get_name():'Item').' × '.$qty).'; ';}
        echo '</p><p>Recorded refund estimate: '.wp_kses_post(wc_price($r['plan']['amount']/100,['currency'=>$o->get_currency()])).'</p>';
        if(!empty($r['message'])){echo '<p>'.esc_html($r['message']).'</p>';}
        foreach(['last_check'=>'Last check','next_check'=>'Next check'] as $key=>$label){if(!empty($r[$key])){echo '<p><strong>'.esc_html($label).':</strong> '.esc_html(wp_date('d M Y, g:i a',(int)$r[$key],new DateTimeZone('Asia/Kolkata'))).' IST</p>';}}
        if($r['stage']==='review'){
            loomara_rt_staff_form_start($o,$seq,'resume');echo '<p>Resolve the stated conflict first. Submitted intents resume GET-only verification; this never creates a replacement pickup or repeats a refund. Unsubmitted quote reviews return to manual approval.</p><label>Review / resolution note<textarea name="resume_note" required minlength="5" maxlength="1500" rows="3"></textarea></label><p><label><input type="checkbox" name="resume_confirmed" value="yes" required> I reviewed the issue and existing intent records.</label></p><button class="button">Resume verification safely</button></form>';
        }
        if($r['stage']==='requested'){
            loomara_rt_staff_form_start($o,$seq,'prepare');
            echo '<p>Confirm actual packed return dimensions and weight; saved product dimensions may not match the return parcel.</p>';
            foreach(['length'=>'Length (cm)','breadth'=>'Width (cm)','height'=>'Height (cm)','weight'=>'Weight (kg)'] as $f=>$label){echo '<label>'.esc_html($label).' <input type="number" name="parcel['.$f.']" min="0.01" step="0.01" required style="width:80px"></label> ';}
            echo '<p><button class="button">Get reverse-pickup quotes — read only</button></p></form>';
            if(!empty($r['quote'])){loomara_rt_staff_form_start($o,$seq,'book');echo '<p><label>Courier <select name="courier" required>';
                foreach($r['quote']['quotes'] as $q){echo '<option value="'.absint($q['id']).'">'.esc_html($q['name'].' — estimated ₹'.number_format($q['rate'],2)).'</option>';}
                echo '</select></label></p><p>Quotes expire after ten minutes. Carrier billing can change after actual weight/dimension checks; this is not a guaranteed final charge.</p>';
                $policy=loomara_rt_policy($o,$r['selection'],$r['reason_code']??'other');if(is_wp_error($policy)||$policy['review']){echo '<label>Restricted-item eligibility approval note<textarea name="eligibility_note" required minlength="5" maxlength="1500" rows="2" placeholder="Explain why this defect/incorrect-item exception is approved"></textarea></label>';}
                echo '<label><input type="checkbox" name="booking_confirmed" value="yes" required> I approve eligibility, the reverse-pickup wallet charge and verified package details.</label><p><button class="button button-primary">Approve and book reverse pickup once</button></p></form>';
            }
            loomara_rt_staff_form_start($o,$seq,'decline');echo '<p><label>Decline request before pickup<textarea name="decline_note" required minlength="5" maxlength="1500" rows="2" placeholder="Explain the eligibility issue"></textarea></label></p><button class="button">Decline request — no pickup or refund</button></form>';
        }
        if($r['stage']==='received'){
            loomara_rt_staff_form_start($o,$seq,'inspect');
            echo '<p>Inspect every selected item. Approval queues the selected-item Razorpay refund after fresh shipping/payment verification. No automatic restocking.</p><textarea name="inspection_note" required minlength="5" maxlength="1500" rows="3" placeholder="Record condition and inspection outcome"></textarea><p><select name="inspection_result"><option value="reject">Reject — no refund</option><option value="approve">Approve — queue eligible refund</option></select></p><label><input type="checkbox" name="inspection_confirmed" value="yes" required> I inspected all returned items and confirm this result.</label><p><button class="button button-primary">Save inspection decision</button></p></form>';
        }
        if(in_array($r['stage'],['booking_intent','booking_uncertain','pickup','collected'],true)){
            loomara_rt_staff_form_start($o,$seq,'recheck');echo '<button class="button">Queue read-only return status check</button></form>';
        }
        echo '</div>';
    }echo '</section>';
}
function loomara_rt_staff_form_start($o,$seq,$operation){
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_return_staff"><input type="hidden" name="order_id" value="'.absint($o->get_id()).'"><input type="hidden" name="seq" value="'.absint($seq).'"><input type="hidden" name="operation" value="'.esc_attr($operation).'">';wp_nonce_field('loomara_return_staff_'.$o->get_id().'_'.$seq);
}
// Do not nest forms inside WooCommerce's native edit-order form. Link to the
// dedicated operations page; this also works for HPOS and classic order storage.
add_action('woocommerce_admin_order_data_after_order_details',function($o){if($o instanceof WC_Order&&current_user_can('manage_woocommerce')&&loomara_rt_book($o->get_id())){echo '<p style="clear:both"><a class="button" href="'.esc_url(admin_url('admin.php?page=loomara-returns&order_id='.$o->get_id())).'">Manage customer returns</a></p>'; }},35);
function loomara_rt_admin_page(){
    if(!current_user_can('manage_woocommerce')){return;}
    echo '<div class="wrap"><h1>Customer returns</h1><p>Status: <strong>'.(loomara_rt_enabled()?'Enabled':'Disabled — local/review rollout required').'</strong></p><p>Item-level returns within seven days of verified courier delivery. Reverse-pickup quotes strictly below ₹100 may be auto-approved; all others need team approval. Every refund requires confirmed reverse delivery and team inspection. Final courier costs may vary.</p>';
    if(current_user_can('manage_options')){
        $preflight=get_option('loomara_returns_preflight',[]);
        echo '<p><strong>Read-only connection check:</strong> '.esc_html($preflight['message']??'Not yet run.').'</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_return_preflight">';wp_nonce_field('loomara_return_preflight');echo '<button class="button">Verify return connections — read only</button></form>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_return_settings"><input type="hidden" name="enabled" value="'.(loomara_rt_enabled()?'no':'yes').'">';wp_nonce_field('loomara_return_settings');
        echo '<p><button class="button">'.(loomara_rt_enabled()?'Stop new return requests and unsubmitted bookings/refunds':'Enable returns, under-₹100 pickups and post-inspection refunds').'</button></p><p>Enabling permits future customer requests to trigger courier-wallet charges below the quote threshold and verified post-inspection refunds. Disabling never erases submitted intents; read-only reconciliation continues. No historical returns or refunds are replayed.</p></form>';
    }
    $notice=get_transient('loomara_return_staff_notice_'.get_current_user_id());if(is_string($notice)){echo '<div class="notice notice-info inline"><p>'.esc_html($notice).'</p></div>';}
    loomara_rt_admin_queue();
    echo '<form method="get"><input type="hidden" name="page" value="loomara-returns"><label>WooCommerce order ID <input name="order_id" inputmode="numeric" pattern="[0-9]+" required></label> <button class="button">Open returns</button></form>';
    $raw=$_GET['order_id']??'';$id=is_scalar($raw)&&ctype_digit((string)$raw)?(int)$raw:0;
    if($id){$o=wc_get_order($id);if($o&&current_user_can('edit_shop_order',$id)){loomara_rt_staff_panel($o);}}
    echo '</div>';
}
add_action('admin_post_loomara_return_staff',function(){
    $id=loomara_rt_input_id('order_id');$seq=loomara_rt_input_id('seq');check_admin_referer('loomara_return_staff_'.$id.'_'.$seq);
    if(!current_user_can('manage_woocommerce')||!current_user_can('edit_shop_order',$id)){wp_die('Order management permission required.',403);}
    $lock='loomara_return_lock_'.$id;$result=new WP_Error('busy','Another return update is in progress.');
    if(add_option($lock,time(),'','no')){
        try{
            $o=wc_get_order($id);$book=loomara_rt_book($id);$r=$book['requests'][$seq]??[];$op=$_POST['operation']??'';
            if(!$o||!$r){$result=new WP_Error('disabled','Return record unavailable.');}
            elseif($op==='resume'&&($_POST['resume_confirmed']??'')==='yes'&&is_string($_POST['resume_note']??null)){$result=loomara_rt_resume($o,$seq,wp_unslash($_POST['resume_note']));}
            elseif($op==='recheck'&&in_array($r['stage'],['booking_intent','booking_uncertain','pickup','collected'],true)){loomara_rt_schedule($id,$seq,max(1,(int)($r['next_check']??0)-time()));$result=true;}
            elseif(!loomara_rt_enabled()){$result=new WP_Error('disabled','Returns are disabled. Only submitted-intent verification is available.');}
            elseif($op==='prepare'&&$r['stage']==='requested'&&is_array($_POST['parcel']??null)){
                $parcel=array_intersect_key($_POST['parcel'],array_flip(['length','breadth','height','weight']));$result=loomara_rt_prepare($o,$r,$parcel);
                if(!is_wp_error($result)){$book['requests'][$seq]['quote']=$result;loomara_rt_save($id,$book);$result=true;}
            }elseif($op==='book'&&($_POST['booking_confirmed']??'')==='yes'){$note=is_string($_POST['eligibility_note']??null)?wp_unslash($_POST['eligibility_note']):'';$result=loomara_rt_booking($o,$seq,loomara_rt_input_id('courier'),false,$note);}
            elseif($op==='inspect'&&($_POST['inspection_confirmed']??'')==='yes'&&is_string($_POST['inspection_note']??null)&&in_array($_POST['inspection_result']??'', ['approve','reject'],true)){$result=loomara_rt_inspect($o,$seq,$_POST['inspection_result']==='approve',wp_unslash($_POST['inspection_note']));}
            elseif($op==='decline'&&$r['stage']==='requested'&&is_string($_POST['decline_note']??null)){
                $note=sanitize_textarea_field(wp_unslash($_POST['decline_note']));
                if(strlen($note)<5||strlen($note)>1500){$result=new WP_Error('note','Give a clear decline reason.');}
                else{$r['stage']='declined';$r['inspection']=['by'=>get_current_user_id(),'at'=>time(),'approved'=>false,'note'=>$note];$book['requests'][$seq]=$r;loomara_rt_save($id,$book);$o->update_status((float)$o->get_total_refunded()>0?'part-refunded':'delivered','Return request declined before pickup: '.$note);loomara_rt_notify($o,$r,'Return request declined',$note);$result=true;}
            }
            elseif($op==='recheck'&&in_array($r['stage'],['booking_intent','booking_uncertain','pickup','collected'],true)){loomara_rt_schedule($id,$seq,1);$result=true;}
            else{$result=new WP_Error('action','Action unavailable for this return stage.');}
        }catch(Throwable $e){$result=new WP_Error('exception','Unexpected return operation error. Reconcile saved intents; do not blindly repeat a booking or refund.');}
        finally{delete_option($lock);}
    }
    set_transient('loomara_return_staff_notice_'.get_current_user_id(),is_wp_error($result)?$result->get_error_message():'Return action saved. Review the current stage below.',300);
    wp_safe_redirect(admin_url('admin.php?page=loomara-returns&order_id='.$id));exit;
});
add_action('admin_post_loomara_return_settings',function(){
    if(!current_user_can('manage_options')){wp_die('Administrator access required.',403);}check_admin_referer('loomara_return_settings');
    $enable=($_POST['enabled']??'')==='yes';
    $preflight=get_option('loomara_returns_preflight',[]);
    if($enable && (empty($preflight['ok'])||time()-(int)($preflight['at']??0)>3600||!loomara_sr_credentials()||is_wp_error(loomara_sr_woocommerce_channel())||(defined('DISABLE_WP_CRON')&&DISABLE_WP_CRON))){wp_die('Run the read-only return connection check and verify background scheduling before enabling returns.',409);}
    update_option('loomara_returns_enabled',$enable?'yes':'no',false);wp_safe_redirect(admin_url('admin.php?page=loomara-returns'));exit;
});
add_action('admin_post_loomara_return_preflight',function(){
    if(!current_user_can('manage_options')){wp_die('Administrator access required.',403);}check_admin_referer('loomara_return_preflight');
    $result=loomara_rt_connection_preflight();update_option('loomara_returns_preflight',is_wp_error($result)?['ok'=>false,'at'=>time(),'message'=>$result->get_error_message()]:$result,false);
    wp_safe_redirect(admin_url('admin.php?page=loomara-returns'));exit;
});
