<?php
/** Missing footer information pages: explicit administrator provisioning only. */
defined('ABSPATH') || exit;
add_action('wp_enqueue_scripts',function(){wp_enqueue_style('loomara-footer-artwork',get_template_directory_uri().'/assets/footer-artwork.css',['loomara-refinements'],'1');},40);
function loomara_footer_guide_plan() {
    $contact=esc_url(home_url('/contact/'));$shop=esc_url(home_url('/shop/'));
    return [
        'our-materials'=>['title'=>'Our Materials','content'=>'<p>Comfort starts with knowing what you are bringing home. Check the Material row in each product’s Description for the fabric information listed for that design.</p><h2>Bedding and soft furnishings</h2><p>Our catalogue includes cotton bedding and mixed-material cushion covers. Material can vary between products; the individual product listing is the reference for your chosen piece.</p><h2>Choose with confidence</h2><p>We do not assume an exact fibre percentage, certification or filling specification where it is not supplied. If you need these details before ordering, <a href="'.$contact.'">contact us with the product name or SKU</a>.</p><p><a href="'.$shop.'">Explore the collection</a></p>'],
        'care-guide'=>['title'=>'Care Guide','content'=>'<p>A little care helps your favourite layers stay part of everyday life. Always follow the sewn-in care label for your particular product; it takes precedence over general guidance.</p><h2>Before washing</h2><p>Check the product’s Description for care instructions. Our listed machine-washable pieces should still be washed using the cycle, temperature and drying instructions on their own label. Separate colours and avoid washing delicate bedding with sharp fastenings.</p><h2>Comforters and covers</h2><p>Make sure your machine has enough capacity for the item to move freely. Where a label calls for professional cleaning or a particular drying method, follow that instruction. Do not assume all fabrics or fillings use the same treatment.</p><h2>Unsure about your piece?</h2><p><a href="'.$contact.'">Ask us for product-specific care advice</a> before using bleach, high heat or a treatment not recommended on the label.</p>'],
        'size-guide'=>['title'=>'Size Guide','content'=>'<p>Choose a size for your actual bed or cushion insert, rather than relying on a size name alone. Single, Double, Queen and King dimensions can differ between products.</p><h2>Measure before choosing</h2><p>Measure your mattress width and length, plus its depth and the amount of drape or tuck you prefer. For a comforter, allow for the coverage you want on either side. For cushion covers, measure the insert edge to edge.</p><h2>Check the selected variant</h2><p>Use the Size selector and Product details in the Description to check the available variant. Packed parcel dimensions and shipping weight describe the delivery package, not the dimensions of the bedsheet, comforter or cover.</p><h2>Need exact textile dimensions?</h2><p>If they are not shown on the listing, <a href="'.$contact.'">contact us with the product name and size</a> before ordering. We will confirm rather than give a generic measurement that may not fit your selected product.</p><p><a href="'.$shop.'">Find your next layer</a></p>'],
    ];
}
function loomara_footer_guides_settings() {
    if(!current_user_can('manage_options'))return;
    echo '<section><h2>Footer guide pages</h2><p>Create only missing Our Materials, Care Guide and Size Guide pages. Existing pages and policies are preserved.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_footer_guides">';
    wp_nonce_field('loomara_footer_guides');echo '<button class="button">Create missing guide pages</button></form></section>';
}
function loomara_footer_guides_create() {
    if(!current_user_can('manage_options'))wp_die('Administrator access required.',403);
    check_admin_referer('loomara_footer_guides');
    foreach(loomara_footer_guide_plan() as $slug=>$page){
        $existing=get_page_by_path($slug,OBJECT,'page');
        if($existing){if($existing->post_status!=='publish')wp_die('An existing guide is not published. Review it before linking publicly.',409);continue;}
        $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$page['title'],'post_content'=>$page['content']],true);
        if(is_wp_error($id)||!$id)wp_die('Page creation needs review. Existing pages were not changed.',500);
    }
    wp_safe_redirect(admin_url('admin.php?page=loomara-settings'));exit;
}
add_action('admin_post_loomara_footer_guides','loomara_footer_guides_create');
