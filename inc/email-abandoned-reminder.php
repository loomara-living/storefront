<?php
defined('ABSPATH') || exit;
class Loomara_Email_Abandoned_Reminder extends WC_Email{
    public function __construct(){
        $this->id='loomara_abandoned_next_day';$this->customer_email=true;
        $this->title='Loomara — Next-day payment-attempt reminder';
        $this->description='One reminder 24 hours after a new unpaid payment attempt, subject to payment verification and scheduler availability. No status changes. Existing orders are not enrolled.';
        parent::__construct();
    }
    public function get_default_subject(){return 'Still thinking about a little comfort?';}
    public function get_default_heading(){return 'Your next layer of comfort';}
    public function init_form_fields(){
        $this->form_fields=['enabled'=>['title'=>'Enable/Disable','type'=>'checkbox','label'=>'Enable next-day reminder','default'=>'yes'],
            'subject'=>['title'=>'Subject','type'=>'text','default'=>'','placeholder'=>$this->get_default_subject()],
            'heading'=>['title'=>'Heading','type'=>'text','default'=>'','placeholder'=>$this->get_default_heading()],
            'email_type'=>['title'=>'Email type','type'=>'select','default'=>'html','options'=>['html'=>'HTML','plain'=>'Plain text']]];
    }
    public function send_reminder($o,$recipient){
        $this->object=$o;$this->recipient=$recipient;$this->setup_locale();
        try{return $this->send($this->get_recipient(),$this->get_subject(),$this->get_content(),$this->get_headers(),$this->get_attachments());}
        finally{$this->restore_locale();}
    }
    protected function items(){
        $items=[];if(!$this->object instanceof WC_Order){return $items;}
        foreach($this->object->get_items() as $item){
            $product=$item->get_product();if(!$product||$product->get_status()!=='publish'){continue;}
            $items[]=['name'=>$item->get_name(),'url'=>$product->get_permalink()];
        }return $items;
    }
    public function get_content_plain(){
        $text=$this->get_heading()."\n\nYou recently tried to order from Loomara Living, but we couldn’t confirm a completed payment. If you’d still like these pieces, you can shop them again:\n\n";
        foreach($this->items() as $item){$text.=$item['name'].': '.$item['url']."\n";}
        return $text."\nExplore collection: ".home_url('/shop/')."\n\nPrices and availability may have changed. Already paid? Please don’t pay again; contact us: ".home_url('/contact/')."\nLoomara Living · India\n";
    }
    public function get_content_html(){
        ob_start();do_action('woocommerce_email_header',$this->get_heading(),$this);
        echo '<p>You recently tried to order from Loomara Living, but we couldn’t confirm a completed payment. If you’d still like these pieces, a little comfort is only a click away.</p>';
        foreach($this->items() as $item){echo '<p><a href="'.esc_url($item['url']).'" style="color:#102c4c;font-weight:600">Shop '.esc_html($item['name']).' again →</a></p>';}
        echo '<p><a href="'.esc_url(home_url('/shop/')).'" style="display:inline-block;background:#102c4c;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;font-weight:600">Explore collection</a></p><p>Prices and availability may have changed.</p><p>Already paid? Please don’t pay again. <a href="'.esc_url(home_url('/contact/')).'">Contact us</a> so we can help.</p>';
        do_action('woocommerce_email_footer',$this);return ob_get_clean();
    }
}
