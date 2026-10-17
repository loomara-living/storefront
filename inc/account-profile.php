<?php
/** Private customer profile and a curated, local-only avatar library. */
defined('ABSPATH') || exit;

function loomara_avatar_choices() {
    $choices = [];
    foreach (['male' => 'Male', 'female' => 'Female'] as $group => $label) {
        for ($i = 1; $i <= 5; $i++) { $choices[$group.'-'.$i] = $label.' avatar '.$i; }
    }
    return $choices;
}
function loomara_profile_avatar($key) {
    if (!isset(loomara_avatar_choices()[$key])) { return ''; }
    [$group, $index] = explode('-', $key);
    $i = (int) $index - 1;
    $background = ['#e6d9c5', '#d7e3dd', '#e6d5d9', '#d5dfeb', '#e9dfc9'][$i];
    $skin = ['#f2c7a7', '#b87851', '#dba47a', '#8a563e', '#e8b899'][$i];
    $hair = ['#302a29', '#4c3229', '#242323', '#2c2422', '#79503b'][$i];
    $clothes = ['#19374c', '#8e6247', '#516c61', '#7f5265', '#6b7188'][$i];
    $long = $group === 'female' ? '<path d="M27 47C24 12 76 12 73 47L78 78H22Z" fill="'.$hair.'"/>' : '';
    $fringe = $i % 2 ? 'M31 39Q34 14 57 25L72 39L61 33L54 38L43 31Z' : 'M29 39Q27 17 51 21Q77 20 72 42L63 33Q43 41 34 33Z';
    return '<svg viewBox="0 0 100 100" width="100" height="100" aria-hidden="true" focusable="false"><circle cx="50" cy="50" r="50" fill="'.$background.'"/>'.$long.'<path d="M14 100V91Q20 72 50 72Q80 72 86 91V100Z" fill="'.$clothes.'"/><path d="M43 63H57V79Q50 86 43 79Z" fill="'.$skin.'"/><ellipse cx="50" cy="47" rx="21" ry="26" fill="'.$skin.'"/><path d="'.$fringe.'" fill="'.$hair.'"/><g fill="none" stroke="#352c28" stroke-width="2" stroke-linecap="round"><path d="M39 47h1M60 47h1M44 60q6 5 12 0"/></g>'.($i === 4 ? '<g fill="none" stroke="#403838" stroke-width="1.5"><rect x="33" y="42" width="13" height="10" rx="3"/><rect x="54" y="42" width="13" height="10" rx="3"/><path d="M46 46h8"/></g>' : '').'</svg>';
}
add_action('woocommerce_account_dashboard', function () {
    $user = wp_get_current_user();
    $name = trim($user->first_name.' '.$user->last_name) ?: $user->display_name;
    $mobile = get_user_meta($user->ID, 'billing_phone', true);
    $avatar = get_user_meta($user->ID, '_loomara_avatar', true);
    echo '<section class="loomara-profile"><div class="profile-heading"><div class="profile-current-avatar">';
    echo loomara_profile_avatar($avatar) ?: '<span aria-hidden="true">'.esc_html(mb_substr($name, 0, 1)).'</span>';
    echo '</div><div><p class="eyebrow">Your Loomara profile</p><h2>'.esc_html($name).'</h2><a href="'.esc_url(wc_get_account_endpoint_url('edit-account')).'">Edit account details</a></div></div><dl class="profile-facts"><div><dt>Customer name</dt><dd>'.esc_html($name).'</dd></div><div><dt>Mobile number</dt><dd>'.esc_html($mobile ?: 'Not added yet').'</dd></div><div><dt>Registered email</dt><dd>'.esc_html($user->user_email).'</dd></div></dl>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="profile-avatar-form"><input type="hidden" name="action" value="loomara_save_avatar">';
    wp_nonce_field('loomara_save_avatar', 'loomara_avatar_nonce');
    echo '<h3>Make yourself at home</h3><p>Choose any avatar you like. Your choice is private to your account.</p>';
    foreach (['male' => 'Male avatars', 'female' => 'Female avatars'] as $group => $label) {
        echo '<fieldset class="avatar-group"><legend>'.esc_html($label).'</legend><div class="avatar-options">';
        foreach (loomara_avatar_choices() as $key => $name) {
            if (strpos($key, $group.'-') !== 0) { continue; }
            echo '<label class="avatar-choice"><input type="radio" name="avatar" value="'.esc_attr($key).'" '.checked($avatar, $key, false).'><span>'.loomara_profile_avatar($key).'</span><span class="sr-only">'.esc_html($name).'</span></label>';
        }
        echo '</div></fieldset>';
    }
    echo '<label class="avatar-initials"><input type="radio" name="avatar" value="initials" '.checked(!isset(loomara_avatar_choices()[$avatar]), true, false).'> Use my initial instead</label><p><button class="button" type="submit">Save avatar</button></p></form></section>';
});
add_action('admin_post_loomara_save_avatar', function () {
    if (!is_user_logged_in()) { wp_safe_redirect(wc_get_page_permalink('myaccount')); exit; }
    check_admin_referer('loomara_save_avatar', 'loomara_avatar_nonce');
    $key = isset($_POST['avatar']) && is_string($_POST['avatar']) ? sanitize_key(wp_unslash($_POST['avatar'])) : '';
    if ($key !== 'initials' && !isset(loomara_avatar_choices()[$key])) { wp_die('Choose one of the available avatars.', '', ['response' => 400]); }
    update_user_meta(get_current_user_id(), '_loomara_avatar', $key);
    wp_safe_redirect(add_query_arg('avatar-saved', '1', wc_get_page_permalink('myaccount')));
    exit;
});
add_action('woocommerce_account_dashboard', function () {
    if (isset($_GET['avatar-saved'])) { echo '<p class="woocommerce-message" role="status">Your avatar has been saved.</p>'; }
}, 5);
add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_account_page') || !is_account_page()) { return; }
    wp_enqueue_style('loomara-account-profile', get_template_directory_uri().'/assets/account-profile.css', ['loomara-refinements'], filemtime(get_template_directory().'/assets/account-profile.css'));
    wp_enqueue_script('loomara-account-profile', get_template_directory_uri().'/assets/account-profile.js', [], filemtime(get_template_directory().'/assets/account-profile.js'), true);
});
