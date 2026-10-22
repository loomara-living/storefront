<?php
/** Menu presentation only. Keep existing page registrations, permissions and actions intact. */
defined('ABSPATH') || exit;
function loomara_admin_tool_slugs(){
    return ['loomara-inventory-release','loomara-catalogue-186','loomara-catalogue-190',
        'loomara-package-197','loomara-offers-113','loomara-pending-retirement-116',
        'loomara-shiprocket','loomara-auto-refunds','loomara-payment-failures','loomara-order-push','loomara-returns'];
}
add_action('admin_menu',function(){
    add_submenu_page('woocommerce','Loomara Settings','Loomara Settings','manage_options','loomara-settings','loomara_admin_settings_page');
    // Preserve every original submenu registration: WordPress uses it to
    // discover the parent during access checks. Hide presentation only below.
},999);
add_action('admin_head',function(){
    echo '<style>';
    foreach(loomara_admin_tool_slugs() as $slug){echo '#adminmenu a[href="admin.php?page='.esc_attr($slug).'"]{display:none!important}';}
    echo '</style>';
});
function loomara_admin_settings_page(){
    if(!current_user_can('manage_options')){return;}
    $tools=[
        ['Shiprocket connection','loomara-shiprocket','Manage the shipping connection and run read-only order matching checks.'],
        ['Paid order push to Shiprocket','loomara-order-push','Manage the existing paid-order exporter and review its recent export records.'],
        ['Customer returns','loomara-returns','Review item-level return requests, approve reverse pickups and inspect received returns.'],
        ['Automatic refunds','loomara-auto-refunds','Review the automatic cancellation-refund setting and its verification safeguards.'],
        ['Payment failure sync','loomara-payment-failures','Review the dedicated Razorpay payment-failure webhook connection.'],
    ];
    echo '<div class="wrap"><h1>Loomara Settings</h1><p>Ongoing store integrations in one place. Opening this page does not change settings or process orders.</p><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:16px;max-width:1100px;margin-top:24px">';
    foreach($tools as [$title,$slug,$description]){
        echo '<section style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:20px"><h2 style="margin-top:0">'.esc_html($title).'</h2><p>'.esc_html($description).'</p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page='.$slug)).'">Open '.esc_html($title).'</a></section>';
    }
    echo '</div>';
    if(function_exists('loomara_processing_age_settings')){loomara_processing_age_settings();}
    echo '<p style="max-width:850px;margin-top:24px;color:#50575e">Completed inventory, catalogue, weight, parcel and coupon setup tools, and retired payment-timer cleanup, are hidden from the menu. Their protected code, saved audit records and existing administrator URLs remain intact. They have not been rerun or deleted.</p></div>';
}
function loomara_admin_tool_selected(){
    $page=$_GET['page']??'';
    return is_string($page) && in_array(sanitize_key(wp_unslash($page)),loomara_admin_tool_slugs(),true);
}
// Hidden tools retain their original parent/capability; highlight their new hub without re-registering them.
add_filter('parent_file',function($parent){return loomara_admin_tool_selected()?'woocommerce':$parent;});
add_filter('submenu_file',function($submenu){return loomara_admin_tool_selected()?'loomara-settings':$submenu;});
