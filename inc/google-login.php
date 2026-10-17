<?php
/** Google customer login. Credentials and OAuth remain managed by Nextend. */
defined('ABSPATH') || exit;

function loomara_google_login_option() {
    if (is_user_logged_in() || !shortcode_exists('nextend_social_login')) { return; }
    $redirect = wc_get_page_permalink('myaccount');
    $button = do_shortcode('[nextend_social_login provider="google" style="fullwidth" link="0" unlink="0" customlabel="Continue with Google" redirect="'.esc_url($redirect).'"]');
    // The provider only renders when enabled. Never fake an active OAuth button.
    if (strpos($button, 'data-provider="google"') === false) { return; }
    echo '<div class="loomara-google-auth"><div class="loomara-auth-separator"><span>or</span></div>'.$button.'</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action('woocommerce_login_form_end', 'loomara_google_login_option');
add_action('woocommerce_register_form_end', 'loomara_google_login_option');

add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_account_page') && is_account_page() && !is_user_logged_in()) {
        wp_enqueue_style('loomara-google-auth', get_template_directory_uri().'/assets/google-login.css', ['loomara-refinements'], '1.7.3');
    }
});
