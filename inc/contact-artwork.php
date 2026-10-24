<?php
/** Image-led contact channels; no messages sent or page records changed. */
defined('ABSPATH') || exit;
function loomara_contact_artwork() {
    $channels=[
        ['contact-email.jpg',833,467,'Email us — we would love to hear from you.','mailto:hello@loomaraliving.com','hello@loomaraliving.com',false],
        ['contact-phone.jpg',832,467,'Call us — speak with our team directly.','tel:+918175950351','+91 81759 50351',false],
        ['contact-whatsapp.jpg',833,464,'WhatsApp us — get answers to your queries.','https://wa.me/918175950351','Chat on WhatsApp · +91 81759 50351',true],
        ['contact-instagram.jpg',832,464,'Follow us on Instagram for inspiration, new arrivals and home styling ideas.','https://www.instagram.com/loomara.living/','@loomara.living on Instagram',true],
    ];
    $html='<section class="contact-hub contact-artwork"><p class="lede">A little help for your home.</p><p>Questions about a piece, an order or a return? Choose how you would like to reach us.</p><div class="contact-artwork-grid">';
    foreach($channels as [$asset,$width,$height,$alt,$url,$label,$external]){
        $html.='<a href="'.esc_url($url).'"'.($external?' target="_blank" rel="noopener noreferrer"':'').' aria-label="'.esc_attr($label.($external?' (opens in a new tab)':'')).'"><img src="'.esc_url(get_template_directory_uri().'/assets/'.$asset).'" width="'.$width.'" height="'.$height.'" alt="'.esc_attr($alt).'" loading="lazy" decoding="async"></a>';
    }
    $orders=function_exists('wc_get_account_endpoint_url')?wc_get_account_endpoint_url('orders'):home_url('/my-account/');
    return $html.'</div><p class="contact-safety-note">For order enquiries, include your order number. Please never share passwords, OTPs or payment credentials.</p><nav class="contact-help-links" aria-label="More order help"><a href="'.esc_url($orders).'">View or track your order</a><a href="'.esc_url(home_url('/faqs/')).'">Frequently asked questions</a></nav></section>';
}
add_action('wp_enqueue_scripts',function(){if(is_page('contact'))wp_enqueue_style('loomara-contact-artwork',get_template_directory_uri().'/assets/contact-artwork.css',['loomara-store-refinements'],'2');},40);
