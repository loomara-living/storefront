<?php
defined('ABSPATH') || exit;
class Loomara_Email_Pending_Payment_114 extends WC_Email {
    public function __construct(){
        $this->id='loomara_pending_payment';$this->customer_email=true;
        $this->title='Loomara — Pending payment reminder';
        $this->description='One reminder for unpaid Razorpay orders after 5 minutes. No reminder after the 30-minute timeout. Payment verification failures pause automation for review.';
        $this->placeholders=['{order_number}'=>''];parent::__construct();
    }
    public function get_default_subject(){return '[{site_title}] Complete payment for order #{order_number}';}
    public function get_default_heading(){return 'Your Loomara order is waiting for you';}
    public function init_form_fields(){
        $this->form_fields=['enabled'=>['title'=>'Enable/Disable','type'=>'checkbox','label'=>'Enable the pending-payment reminder','default'=>'yes'],'subject'=>['title'=>'Subject','type'=>'text','placeholder'=>$this->get_default_subject(),'default'=>''],'heading'=>['title'=>'Email heading','type'=>'text','placeholder'=>$this->get_default_heading(),'default'=>''],'email_type'=>['title'=>'Email type','type'=>'select','default'=>'html','options'=>['plain'=>'Plain text','html'=>'HTML']]];
    }
    public function trigger($id,$o=false){
        $this->object=$o instanceof WC_Order?$o:wc_get_order($id);
        if(!loomara_pending_open_114($this->object)){return;}
        $start=(int)$this->object->get_meta('_loomara_pending_started_114');
        if(!$start||time()<$start+5*MINUTE_IN_SECONDS||time()>=$start+30*MINUTE_IN_SECONDS){return;}
        $this->recipient=$this->object->get_billing_email();$this->placeholders['{order_number}']=$this->object->get_order_number();
        if(!$this->is_enabled()||!is_email($this->recipient)){return;}
        $key='loomara_pending_email_114_'.$id;if(!add_option($key,'sending','','no')){return;}
        $this->setup_locale();
        try{$ok=$this->send($this->get_recipient(),$this->get_subject(),$this->get_content(),$this->get_headers(),$this->get_attachments());update_option($key,$ok?'accepted':'failed',false);if(!$ok){$this->object->add_order_note('Pending-payment reminder transport failed. Review mail logs before manually resending.');}}
        catch(Throwable $e){update_option($key,'uncertain',false);$this->object->add_order_note('Pending-payment reminder outcome uncertain. Review mail logs before manually resending.');}
        finally{$this->restore_locale();}
    }
    public function get_content_plain(){return $this->get_heading()."\n\nOrder: ".$this->object->get_order_number()."\nPayment is still pending. Complete your order using the secure payment link below. If payment remains unconfirmed 30 minutes after checkout, the order may be marked Failed.\n\nComplete payment: ".$this->object->get_checkout_payment_url()."\n\nAlready paid? Please do not pay again; contact us so we can check your payment.\n".home_url('/contact/')."\nLoomara Living · India\n";}
    public function get_content_html(){
        ob_start();do_action('woocommerce_email_header',$this->get_heading(),$this);
        echo '<p>A little comfort is waiting for you. Payment for order <strong>'.esc_html($this->object->get_order_number()).'</strong> is still pending.</p><p>Complete your order using the secure button below. If payment remains unconfirmed 30 minutes after checkout, the order may be marked Failed.</p><p><a href="'.esc_url($this->object->get_checkout_payment_url()).'" style="display:inline-block;background:#102c4c;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;font-weight:600">Complete payment</a></p><p>Already paid? Please do not pay again. <a href="'.esc_url(home_url('/contact/')).'">Contact us</a> so we can check your payment.</p>';
        do_action('woocommerce_email_footer',$this);return ob_get_clean();
    }
}
