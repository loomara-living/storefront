<?php
/** Launch content. Preview testimonials must never appear on production. */
defined('ABSPATH') || exit;

function loomara_is_local_preview() {
    return in_array(wp_parse_url(home_url('/'), PHP_URL_HOST), ['localhost', '127.0.0.1'], true);
}
function loomara_testimonial_preview() {
    if (!loomara_is_local_preview()) { return; }
    $quotes = ['A softer start to slow mornings.', 'Calm colours, thoughtfully layered.', 'Little details that make a room feel like home.'];
    echo '<section class="loomara-testimonials" aria-label="Testimonial layout preview" data-testimonials><p class="eyebrow">Local layout preview · not customer reviews</p><h2>At home with Loomara</h2><div class="testimonial-stage">';
    foreach ($quotes as $i => $quote) {
        echo '<figure class="testimonial-slide"'.($i ? ' hidden' : '').'><blockquote>'.esc_html($quote).'</blockquote><figcaption>Sample testimonial '.absint($i + 1).' · placeholder, not a customer quote</figcaption></figure>';
    }
    echo '</div><div class="testimonial-controls"><button type="button" data-testimonial-prev aria-label="Previous testimonial">←</button><span data-testimonial-count aria-live="polite">1 / 3</span><button type="button" data-testimonial-next aria-label="Next testimonial">→</button><button type="button" data-testimonial-pause>Pause</button></div></section>';
}

// A real editable WordPress page also participates in the native page sitemap.
function loomara_faq_content() {
    $content = '<div class="loomara-faqs">';
    $items = [
        'Where do you deliver?' => 'We deliver to serviceable PIN codes across India. Shipping is free, with no minimum order value.',
        'When will my order arrive?' => 'In-stock orders are dispatched within 24 hours. Delivery is estimated at 5–10 business days after dispatch. Carrier or destination-related delays may occur.',
        'What sizes are available?' => 'Available sizes vary by product and may include Single, Double, Queen or King. Check each product’s size selector before ordering. Contact us for exact dimensions if they are not listed.',
        'How do I use WELCOME20?' => 'Sign in or create an account, then enter WELCOME20 in the coupon field at checkout for 20% off your first order. The discount applies to eligible merchandise, not shipping. Your final discount is shown before payment.',
        'Can I return an item?' => 'Eligible items may be returned within 7 days of delivery, with no return-shipping or handling fee. Contact hello@loomaraliving.com for approval and instructions. See our Returns & refunds policy for eligibility and exclusions.',
        'How should I care for my bedding?' => 'Follow the care label supplied with your item. Care requirements vary by fabric and product; contact us if you need help before washing.',
        'How do I get help with an order?' => 'Email hello@loomaraliving.com with your order number. Never send your account password or payment credentials.',
    ];
    foreach ($items as $question => $answer) { $content .= '<details><summary>'.esc_html($question).'</summary><p>'.esc_html($answer).'</p></details>'; }
    return $content.'<p><a href="'.esc_url(home_url('/shipping-delivery/')).'">Shipping & delivery</a> · <a href="'.esc_url(home_url('/returns-refunds/')).'">Returns & refunds</a></p><p>Have more questions? <a href="'.esc_url(home_url('/contact/')).'">Contact us</a>.</p></div>';
}
add_action('init', function () {
    if (get_option('loomara_faq_page_179')) { return; }
    $page = get_page_by_path('faqs');
    if ($page) { update_option('loomara_faq_page_179', $page->ID, false); return; }
    if (!add_option('loomara_faq_create_lock', time(), '', false)) { return; }
    try {
        $page = get_page_by_path('faqs');
        $id = $page ? $page->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'faqs', 'post_title' => 'Frequently asked questions', 'post_content' => loomara_faq_content(), 'comment_status' => 'closed'], true);
        if (!is_wp_error($id) && $id) { update_option('loomara_faq_page_179', $id, false); }
    } finally { delete_option('loomara_faq_create_lock'); }
}, 40);


add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('loomara-launch', get_template_directory_uri().'/assets/launch.css', ['loomara-refinements'], filemtime(get_template_directory().'/assets/launch.css'));
    if (loomara_is_local_preview()) {
        wp_enqueue_script('loomara-launch', get_template_directory_uri().'/assets/launch.js', [], filemtime(get_template_directory().'/assets/launch.js'), true);
    }
});
