<?php
defined('ABSPATH') || exit;
/** Native WooCommerce email settings/templates/transport; no direct SMTP credentials. */
class Loomara_Email_Fulfilment_111 extends WC_Email {
    protected $fulfilment_status;
    protected $tracking_preview_123=false;
    public function __construct($status) {
        $this->fulfilment_status=in_array($status,['pickup-scheduled','shipped','out-for-delivery','delivered'],true)?$status:'shipped';
        $this->id='loomara_customer_'.$this->fulfilment_status;
        $this->customer_email=true;
        $this->title='Loomara — '.(['pickup-scheduled'=>'Pickup scheduled','shipped'=>'Shipped order','out-for-delivery'=>'Out for delivery','delivered'=>'Delivered order'][$this->fulfilment_status]);
        $this->description='Customer notification when an order first enters '.$this->fulfilment_status.'. Duplicate automatic notifications are suppressed.'.($this->fulfilment_status==='shipped'?' Tracking / AWB, courier and a Track your order button are inserted automatically when verified tracking is available; no subject placeholder is required. The settings preview uses a clearly labelled example, not a real shipment.':'');
        $this->placeholders=['{order_number}'=>'','{order_date}'=>''];
        add_action('woocommerce_order_status_'.$this->fulfilment_status.'_notification',[$this,'trigger'],10,2);
        if($this->fulfilment_status==='pickup-scheduled'){
            // Preserve existing settings ID and durable once-only mail marker.
            $this->title='Loomara — Ready to ship';
            $this->description='One Ready to ship email at Pickup Scheduled, with verified courier/AWB/tracking when available. Later pickup updates do not send separate emails.';
        }
        parent::__construct();
    }
    public function get_default_subject(){return '[{site_title}] Order #{order_number} '.(['pickup-scheduled'=>'pickup is scheduled','shipped'=>'is on its way','out-for-delivery'=>'is out for delivery','delivered'=>'has been delivered'][$this->fulfilment_status]);}
    public function get_default_heading(){return ['pickup-scheduled'=>'Your order pickup is scheduled','shipped'=>'Your Loomara order is on its way','out-for-delivery'=>'Your order is out for delivery','delivered'=>'Your Loomara order has been delivered'][$this->fulfilment_status];}
    protected function stage_message(){return ['pickup-scheduled'=>'Courier pickup is scheduled for your Loomara order. The parcel has not yet been confirmed collected.','shipped'=>'Your Loomara order has been shipped. A little comfort is on its way.','out-for-delivery'=>'Your Loomara order is out for delivery. Please keep your phone available for the courier.','delivered'=>'Your Loomara order has been marked Delivered. We hope it feels right at home.'][$this->fulfilment_status];}
    public function init_form_fields(){
        $this->form_fields=[
            'enabled'=>['title'=>'Enable/Disable','type'=>'checkbox','label'=>'Enable this customer email notification','default'=>'yes'],
            'subject'=>['title'=>'Subject','type'=>'text','placeholder'=>$this->get_default_subject(),'default'=>''],
            'heading'=>['title'=>'Email heading','type'=>'text','placeholder'=>$this->get_default_heading(),'default'=>''],
            'email_type'=>['title'=>'Email type','type'=>'select','default'=>'html','options'=>['plain'=>'Plain text','html'=>'HTML']],
        ];
    }
    public function trigger($id,$order=false,$tracking_ready=false){
        // Preview state must never leak into a real transactional notification.
        $this->tracking_preview_123=false;
        $this->object=$order instanceof WC_Order?$order:wc_get_order($id);
        $allowed=$this->fulfilment_status==='shipped'?['shipped','in-transit','out-for-delivery']:[$this->fulfilment_status];
        if($this->fulfilment_status==='pickup-scheduled'){$allowed=$tracking_ready?['pickup-scheduled','out-for-pickup','picked-up']:['pickup-scheduled'];}
        if(!$this->object||!in_array($this->object->get_status(),$allowed,true)){return;}
        if($this->fulfilment_status!=='delivered'&&$this->object->get_meta('_loomara_delivered_at_111')){return;}
        if($this->fulfilment_status==='pickup-scheduled'&&(get_option('loomara_email_111_shipped_'.$this->object->get_id())||get_option('loomara_email_111_out-for-delivery_'.$this->object->get_id()))){return;}
        $this->recipient=$this->object->get_billing_email();
        $this->placeholders['{order_number}']=$this->object->get_order_number();
        $this->placeholders['{order_date}']=$this->object->get_date_created()?$this->object->get_date_created()->date_i18n('d M Y'):'';
        if(!$this->is_enabled()||!$this->get_recipient()||!is_email($this->recipient)){return;}
        if($this->fulfilment_status==='shipped'&&!$tracking_ready&&function_exists('loomara_tracking_queue_112')){loomara_tracking_queue_112($this->object);return;}
        if($this->fulfilment_status==='pickup-scheduled'&&!$tracking_ready&&function_exists('loomara_tracking_queue_112')){loomara_tracking_queue_112($this->object,'pickup-scheduled');return;}
        $key='loomara_email_111_'.$this->fulfilment_status.'_'.$this->object->get_id();
        // Durable at-most-once automatic send; uncertain SMTP acceptance needs review.
        if(!add_option($key,'sending','','no')){return;}
        $this->setup_locale();
        try{
            $ok=$this->send($this->get_recipient(),$this->get_subject(),$this->get_content(),$this->get_headers(),$this->get_attachments());
            update_option($key,$ok?'accepted':'failed',false);
            if(!$ok){$this->object->add_order_note('Loomara '.$this->fulfilment_status.' email was not accepted by the mail transport. Review delivery before resending.');}
        }catch(Throwable $e){update_option($key,'uncertain',false);$this->object->add_order_note('Loomara '.$this->fulfilment_status.' email outcome is uncertain. Review mail logs before resending.');}
        finally{$this->restore_locale();}
    }
    protected function tracking_numbers(){
        $numbers=[];
        foreach($this->verified_tracking() as $row){$numbers[]=$row['awb'];}
        foreach(['_shiprocket_awb_code','awb_code','_tracking_number'] as $k){$n=$this->object->get_meta($k);if(is_scalar($n)&&trim((string)$n)!==''){$numbers[]=sanitize_text_field(substr((string)$n,0,100));}}
        $items=$this->object->get_meta('_wc_shipment_tracking_items');
        if(is_array($items)){foreach($items as $item){if(is_array($item)&&is_scalar($item['tracking_number']??null)){$numbers[]=sanitize_text_field(substr((string)$item['tracking_number'],0,100));}}}
        return array_values(array_unique(array_filter($numbers)));
    }
    protected function verified_tracking(){
        $rows=$this->object->get_meta('_loomara_verified_tracking_112');
        if(!is_array($rows)){return [];}
        return array_values(array_filter($rows,function($r){return is_array($r)&&is_string($r['awb']??null)&&preg_match('/^[A-Za-z0-9-]{3,100}$/D',$r['awb']);}));
    }
    public function enable_tracking_preview_123(){if($this->fulfilment_status==='shipped'){$this->tracking_preview_123=true;}return $this;}
    protected function show_tracking_preview_123(){return $this->tracking_preview_123 && $this->fulfilment_status==='shipped' && !$this->tracking_numbers();}
    public function get_content_plain(){
        $o=$this->object;$text=$this->get_heading()."\n\nOrder: ".$o->get_order_number()."\n";
        $text.=$this->stage_message()."\n";
        if($this->fulfilment_status==='delivered'){$text.="Eligible returns can be requested within 7 calendar days of delivery, subject to our returns policy.\n";}
        $numbers=$this->tracking_numbers();if($numbers){$text.='Tracking / AWB: '.implode(', ',$numbers)."\n";}
        if($this->show_tracking_preview_123()){$text.="Tracking example — preview only, not a real shipment\nTracking / AWB: EXAMPLE-AWB-123456\nCourier: Example courier\nA Track your order button appears when a verified tracking link is available.\n";}
        foreach($this->verified_tracking() as $row){
            if(!empty($row['courier'])){$text.='Courier: '.sanitize_text_field($row['courier'])."\n";}
            $url=function_exists('loomara_tracking_url_112')?loomara_tracking_url_112($row['url']??''):'';
            if($url){$text.='Track your order ('.$row['awb'].'): '.$url."\n";}
        }
        return $text."\nView order: ".$o->get_view_order_url()."\nReturns policy: ".home_url('/returns-refunds/')."\nQuestions? ".home_url('/contact/')."\nLoomara Living · India\n";
    }
    public function get_content_html(){
        $o=$this->object;ob_start();do_action('woocommerce_email_header',$this->get_heading(),$this);
        echo '<p>'.esc_html($this->stage_message()).'</p><p><strong>Order '.esc_html($o->get_order_number()).'</strong></p>';
        if($this->fulfilment_status==='delivered'){echo '<p>Eligible returns can be requested within 7 calendar days of delivery, subject to our <a href="'.esc_url(home_url('/returns-refunds/')).'">returns policy</a>. If there is an issue, please contact us before the window closes.</p>';}
        $numbers=$this->tracking_numbers();if($numbers){echo '<p><strong>Tracking / AWB:</strong> '.esc_html(implode(', ',$numbers)).'</p>';}
        if($this->show_tracking_preview_123()){echo '<div style="padding:16px 20px;margin:20px 0;border:1px solid #e1d2b8;border-radius:8px;background:#fffaf3"><p style="margin:0 0 10px"><strong>Tracking example — preview only</strong><br><small>Not a real shipment</small></p><p><strong>Tracking / AWB:</strong> EXAMPLE-AWB-123456<br><strong>Courier:</strong> Example courier</p><p style="margin:0">A <strong>Track your order</strong> button appears here when a verified tracking link is available.</p></div>';}
        foreach($this->verified_tracking() as $row){
            if(!empty($row['courier'])){echo '<p><strong>Courier:</strong> '.esc_html($row['courier']).'</p>';}
            $url=function_exists('loomara_tracking_url_112')?loomara_tracking_url_112($row['url']??''):'';
            if($url){echo '<p><a href="'.esc_url($url).'" style="display:inline-block;background:#102c4c;color:#ffffff;padding:12px 22px;border-radius:6px;text-decoration:none;font-weight:600">Track your order</a><br><small>'.esc_html($row['awb']).'</small></p>';}
        }
        echo '<p><a href="'.esc_url($o->get_view_order_url()).'">View your order</a> · <a href="'.esc_url(home_url('/contact/')).'">Contact Loomara Living</a></p>';
        do_action('woocommerce_email_order_details',$o,false,false,$this);
        do_action('woocommerce_email_footer',$this);return ob_get_clean();
    }
}
// WooCommerce settings and previews identify emails by concrete class name.
class Loomara_Email_Shipped extends Loomara_Email_Fulfilment_111 {
    public function __construct(){parent::__construct('shipped');}
}
class Loomara_Email_Delivered extends Loomara_Email_Fulfilment_111 {
    public function __construct(){parent::__construct('delivered');}
}
class Loomara_Email_Pickup_Scheduled extends Loomara_Email_Fulfilment_111 {
    public function __construct(){parent::__construct('pickup-scheduled');}
    public function get_default_subject(){return '[{site_title}] Order #{order_number} is ready to ship';}
    public function get_default_heading(){return 'Your order is ready to ship';}
    protected function stage_message(){return 'Your Loomara order is ready to ship. We’ll email you again when it is shipped.';}
}
class Loomara_Email_Out_For_Delivery extends Loomara_Email_Fulfilment_111 {
    public function __construct(){parent::__construct('out-for-delivery');}
}
// Shiprocket also posts this exact automatic tracking note on status pushes.
// Keep the note in order history, but use the milestone emails instead of
// repeatedly emailing the same link. Unrelated customer/team notes stay enabled.
function loomara_shipping_note_email_enabled($enabled,$order,$email){
    if(!$enabled||!$order instanceof WC_Order||($email->id??'')!=='customer_note'){return $enabled;}
    return loomara_is_automatic_tracking_note($email->customer_note??'')?false:$enabled;
}
function loomara_is_automatic_tracking_note($content){
    $text=trim(html_entity_decode(strip_tags((string)$content),ENT_QUOTES,'UTF-8'));
    return (bool)preg_match('~^Please click on the below url to track your Shipments:\s*https://(?:[a-z0-9-]+\.)?shiprocket\.(?:co|in)/tracking/[A-Za-z0-9-]{3,100}/?$~iD',$text);
}
// Keep future automatic link notes as private audit records, not customer notes.
// The email filter above also covers the notification fired before this hook.
add_action('woocommerce_order_note_added',function($note_id,$order){
    if(!$order instanceof WC_Order){return;}$note=get_comment($note_id);
    if($note&&$note->comment_type==='order_note'&&(int)$note->comment_post_ID===$order->get_id()&&loomara_is_automatic_tracking_note($note->comment_content)){update_comment_meta($note_id,'is_customer_note',0);}
},1,2);
add_filter('woocommerce_email_enabled_customer_note','loomara_shipping_note_email_enabled',100,3);
// Official WooCommerce preview hook: no order metadata is changed or saved.
add_filter('woocommerce_prepare_email_for_preview',function($email){return $email instanceof Loomara_Email_Shipped?$email->enable_tracking_preview_123():$email;});
