<?php
defined('ABSPATH') || exit;
/** Native WooCommerce email settings/templates/transport; no direct SMTP credentials. */
class Loomara_Email_Fulfilment_111 extends WC_Email {
    protected $fulfilment_status;
    public function __construct($status) {
        $this->fulfilment_status=$status==='delivered'?'delivered':'shipped';
        $this->id='loomara_customer_'.$this->fulfilment_status;
        $this->customer_email=true;
        $this->title=$this->fulfilment_status==='shipped'?'Loomara — Shipped order':'Loomara — Delivered order';
        $this->description='Customer notification when an order first enters '.$this->fulfilment_status.'. Duplicate automatic notifications are suppressed.';
        $this->placeholders=['{order_number}'=>'','{order_date}'=>''];
        add_action('woocommerce_order_status_'.$this->fulfilment_status.'_notification',[$this,'trigger'],10,2);
        parent::__construct();
    }
    public function get_default_subject(){return '[{site_title}] Order #{order_number} '.($this->fulfilment_status==='shipped'?'is on its way':'has been delivered');}
    public function get_default_heading(){return $this->fulfilment_status==='shipped'?'Your Loomara order is on its way':'Your Loomara order has been delivered';}
    public function init_form_fields(){
        $this->form_fields=[
            'enabled'=>['title'=>'Enable/Disable','type'=>'checkbox','label'=>'Enable this customer email notification','default'=>'yes'],
            'subject'=>['title'=>'Subject','type'=>'text','placeholder'=>$this->get_default_subject(),'default'=>''],
            'heading'=>['title'=>'Email heading','type'=>'text','placeholder'=>$this->get_default_heading(),'default'=>''],
            'email_type'=>['title'=>'Email type','type'=>'select','default'=>'html','options'=>['plain'=>'Plain text','html'=>'HTML']],
        ];
    }
    public function trigger($id,$order=false){
        $this->object=$order instanceof WC_Order?$order:wc_get_order($id);
        if(!$this->object||$this->object->get_status()!==$this->fulfilment_status){return;}
        if($this->fulfilment_status==='shipped'&&$this->object->get_meta('_loomara_delivered_at_111')){return;}
        $this->recipient=$this->object->get_billing_email();
        $this->placeholders['{order_number}']=$this->object->get_order_number();
        $this->placeholders['{order_date}']=$this->object->get_date_created()?$this->object->get_date_created()->date_i18n('d M Y'):'';
        if(!$this->is_enabled()||!$this->get_recipient()||!is_email($this->recipient)){return;}
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
        foreach(['_shiprocket_awb_code','awb_code','_tracking_number'] as $k){$n=$this->object->get_meta($k);if(is_scalar($n)&&trim((string)$n)!==''){$numbers[]=sanitize_text_field(substr((string)$n,0,100));}}
        $items=$this->object->get_meta('_wc_shipment_tracking_items');
        if(is_array($items)){foreach($items as $item){if(is_array($item)&&is_scalar($item['tracking_number']??null)){$numbers[]=sanitize_text_field(substr((string)$item['tracking_number'],0,100));}}}
        return array_values(array_unique(array_filter($numbers)));
    }
    public function get_content_plain(){
        $o=$this->object;$text=$this->get_heading()."\n\nOrder: ".$o->get_order_number()."\n";
        $text.=$this->fulfilment_status==='shipped'?"Your order has been marked Shipped. Follow the courier's tracking for updates.\n":"Your order has been marked Delivered. Eligible returns can be requested within 7 calendar days of delivery, subject to our returns policy.\n";
        $numbers=$this->tracking_numbers();if($numbers){$text.='Tracking / AWB: '.implode(', ',$numbers)."\n";}
        return $text."\nView order: ".$o->get_view_order_url()."\nReturns policy: ".home_url('/returns-refunds/')."\nQuestions? ".home_url('/contact/')."\nLoomara Living · India\n";
    }
    public function get_content_html(){
        $o=$this->object;ob_start();do_action('woocommerce_email_header',$this->get_heading(),$this);
        echo '<p>'.esc_html($this->fulfilment_status==='shipped'?'Your Loomara order has been marked Shipped. A little comfort is on its way.':'Your Loomara order has been marked Delivered. We hope it feels right at home.').'</p><p><strong>Order '.esc_html($o->get_order_number()).'</strong></p>';
        if($this->fulfilment_status==='delivered'){echo '<p>Eligible returns can be requested within 7 calendar days of delivery, subject to our <a href="'.esc_url(home_url('/returns-refunds/')).'">returns policy</a>. If there is an issue, please contact us before the window closes.</p>';}
        $numbers=$this->tracking_numbers();if($numbers){echo '<p><strong>Tracking / AWB:</strong> '.esc_html(implode(', ',$numbers)).'</p>';}
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
