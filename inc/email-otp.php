<?php
/** Email ownership verification for the normal WooCommerce signup form only. */
defined('ABSPATH') || exit;

function loomara_otp_key($value) {
    return hash_hmac('sha256', (string) $value, wp_salt('auth'));
}
function loomara_otp_email($email) {
    return strtolower(sanitize_email(trim((string) $email)));
}
// Database-backed locks protect rate counters, attempt counts and consumption
// even when object caching is unavailable. Stale locks are removed by value.
function loomara_otp_locked($keys, $callback) {
    global $wpdb;
    $locks = [];
    sort($keys);
    try {
        foreach (array_unique($keys) as $key) {
            $name = 'loomara_otp_lock_'.loomara_otp_key($key);
            $value = time().':'.wp_generate_password(20, false);
            $old = get_option($name);
            if ($old && (int) $old < time() - 120) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, $old));
                wp_cache_delete($name, 'options');
            }
            if (!add_option($name, $value, '', false)) { return new WP_Error('otp_busy', 'Please wait a moment and try again.'); }
            $locks[$name] = $value;
        }
        return $callback();
    } finally {
        foreach ($locks as $name => $value) {
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, $value));
            wp_cache_delete($name, 'options');
        }
    }
}
function loomara_otp_issue($email, $ip) {
    $email = loomara_otp_email($email);
    if (!is_email($email)) { return new WP_Error('otp_email', 'Enter a valid email address first.'); }
    $email_key = 'loomara_otp_e_'.loomara_otp_key($email);
    $ip_key = 'loomara_otp_i_'.loomara_otp_key($ip);
    return loomara_otp_locked([$email_key, $ip_key], function () use ($email, $email_key, $ip_key) {
        $now = time();
        $email_rate = get_transient($email_key) ?: ['count' => 0, 'last' => 0, 'until' => $now + HOUR_IN_SECONDS];
        $ip_rate = get_transient($ip_key) ?: ['count' => 0, 'until' => $now + HOUR_IN_SECONDS];
        if ($now - $email_rate['last'] < 60) { return new WP_Error('otp_cooldown', 'Please wait 60 seconds between code requests.'); }
        if ($email_rate['count'] >= 5 || $ip_rate['count'] >= 20) { return new WP_Error('otp_limit', 'Too many code requests. Please try again in an hour.'); }
        if (!empty($email_rate['challenge'])) { delete_transient('loomara_otp_c_'.$email_rate['challenge']); }
        $token = bin2hex(random_bytes(24));
        $email_rate['count']++;
        $email_rate['last'] = $now;
        $email_rate['challenge'] = $token;
        $ip_rate['count']++;
        set_transient($email_key, $email_rate, max(1, $email_rate['until'] - $now));
        set_transient($ip_key, $ip_rate, max(1, $ip_rate['until'] - $now));
        // Do not disclose whether an existing account owns an email address.
        if (!email_exists($email)) {
            $code = (string) random_int(100000, 999999);
            $record = ['email' => loomara_otp_key($email), 'digest' => loomara_otp_key($token.'|'.$email.'|'.$code), 'expires' => $now + 600, 'attempts' => 0];
            set_transient('loomara_otp_c_'.$token, $record, 600);
            $sent = wp_mail($email, 'Your Loomara Living signup code', "Your Loomara Living verification code is: {$code}\n\nIt expires in 10 minutes and can be used once to create your account. Never share this code. If you did not request it, ignore this email.\n\nLoomara Living · India\nhello@loomaraliving.com", ['Content-Type: text/plain; charset=UTF-8']);
            if (!$sent) {
                delete_transient('loomara_otp_c_'.$token);
                return new WP_Error('otp_mail', 'We could not send a code right now. Please try again later or contact support.');
            }
        }
        return ['token' => $token, 'retryAfter' => 60, 'message' => 'If this email is eligible for signup, a code has been requested. Check your inbox and spam folder. Already have an account? Sign in instead.'];
    });
}
function loomara_otp_consume($email, $token, $code) {
    $email = loomara_otp_email($email);
    if (!preg_match('/^[a-f0-9]{48}$/', $token)) { return new WP_Error('otp_missing', 'Request an email verification code before creating your account.'); }
    return loomara_otp_locked(['challenge:'.$token], function () use ($email, $token, $code) {
        $key = 'loomara_otp_c_'.$token;
        $record = get_transient($key);
        if (!$record || $record['expires'] <= time()) { delete_transient($key); return new WP_Error('otp_expired', 'This code has expired or was already used. Request a new code.'); }
        if (!hash_equals($record['email'], loomara_otp_key($email))) { return new WP_Error('otp_email_changed', 'Your email changed. Request a new code for this email address.'); }
        $record['attempts']++;
        $valid = preg_match('/^[0-9]{6}$/', $code) && hash_equals($record['digest'], loomara_otp_key($token.'|'.$email.'|'.$code));
        if (!$valid) {
            if ($record['attempts'] >= 5) { delete_transient($key); }
            else { set_transient($key, $record, max(1, $record['expires'] - time())); }
            return new WP_Error('otp_invalid', $record['attempts'] >= 5 ? 'Too many incorrect codes. Request a new code.' : 'The verification code is incorrect. Please try again.');
        }
        delete_transient($key);
        return true;
    });
}
add_action('wp_ajax_nopriv_loomara_signup_otp', function () {
    if (!check_ajax_referer('loomara_signup_otp', 'nonce', false)) { wp_send_json_error(['message' => 'Please reload this page and try again.'], 403); }
    if (get_option('woocommerce_enable_myaccount_registration') !== 'yes') { wp_send_json_error(['message' => 'Signup is currently unavailable.'], 403); }
    $email = isset($_POST['email']) && is_string($_POST['email']) ? wp_unslash($_POST['email']) : '';
    $result = loomara_otp_issue($email, (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    if (is_wp_error($result)) { wp_send_json_error(['message' => $result->get_error_message()], 400); }
    wp_send_json_success($result);
});
add_action('woocommerce_register_form', function () {
    // Preserve the random challenge after unrelated form errors, never the OTP.
    $token = isset($_POST['loomara_email_challenge']) && is_string($_POST['loomara_email_challenge']) ? sanitize_text_field(wp_unslash($_POST['loomara_email_challenge'])) : '';
    echo '<fieldset class="loomara-email-otp"><legend>Verify your email</legend><p>We’ll email a six-digit code. Enter it below, then create your account.</p><button type="button" class="button" data-email-otp-send>Send email code</button><p class="form-row-wide"><label for="loomara_email_code">Email verification code <span aria-hidden="true">*</span></label><input id="loomara_email_code" name="loomara_email_code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required aria-describedby="email-otp-status"></p><input type="hidden" name="loomara_email_challenge" value="'.esc_attr($token).'"><p id="email-otp-status" role="status" aria-live="polite"></p><noscript><p>Enable JavaScript to request your email code, or contact hello@loomaraliving.com for help.</p></noscript></fieldset>';
}, 25);
add_filter('woocommerce_registration_errors', function ($errors, $username, $email) {
    // Google OAuth and checkout customer creation are not this signup form.
    if (!isset($_POST['register'])) { return $errors; }
    if ($errors->has_errors()) { return $errors; }
    $nonce = isset($_POST['woocommerce-register-nonce']) && is_string($_POST['woocommerce-register-nonce']) ? sanitize_text_field(wp_unslash($_POST['woocommerce-register-nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'woocommerce-register')) { $errors->add('otp_nonce', 'Reload this page before signing up.'); return $errors; }
    $token = isset($_POST['loomara_email_challenge']) && is_string($_POST['loomara_email_challenge']) ? sanitize_text_field(wp_unslash($_POST['loomara_email_challenge'])) : '';
    $code = isset($_POST['loomara_email_code']) && is_string($_POST['loomara_email_code']) ? trim(wp_unslash($_POST['loomara_email_code'])) : '';
    $result = loomara_otp_consume($email, $token, $code);
    if (is_wp_error($result)) { $errors->add($result->get_error_code(), $result->get_error_message()); }
    else { $GLOBALS['loomara_signup_verified_email'] = loomara_otp_email($email); }
    return $errors;
}, PHP_INT_MAX, 3);
add_action('woocommerce_created_customer', function ($id) {
    $user = get_userdata($id);
    if ($user && !empty($GLOBALS['loomara_signup_verified_email']) && loomara_otp_email($user->user_email) === $GLOBALS['loomara_signup_verified_email']) {
        update_user_meta($id, '_loomara_verified_email', loomara_otp_email($user->user_email));
        update_user_meta($id, '_loomara_email_verified_at', time());
        unset($GLOBALS['loomara_signup_verified_email']);
    }
}, 30);
add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_account_page') || !is_account_page() || is_user_logged_in()) { return; }
    wp_enqueue_script('loomara-email-otp', get_template_directory_uri().'/assets/email-otp.js', [], filemtime(get_template_directory().'/assets/email-otp.js'), true);
    wp_localize_script('loomara-email-otp', 'loomaraEmailOtp', ['url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('loomara_signup_otp')]);
    wp_enqueue_style('loomara-email-otp', get_template_directory_uri().'/assets/email-otp.css', ['loomara-refinements'], filemtime(get_template_directory().'/assets/email-otp.css'));
});
