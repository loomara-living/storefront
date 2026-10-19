<?php
/** v1.12.0 explicit administrator registration; never alters the native Razorpay webhook. */
defined('ABSPATH') || exit;
function loomara_pf_url_120() { return home_url('/wp-json/loomara/v1/razorpay-failures'); }
function loomara_pf_webhooks_120() {
    $all=[];
    for ($skip=0;$skip<500;$skip+=100) {
        $r=loomara_pf_request_120('webhooks?count=100&skip='.$skip);
        if (is_wp_error($r) || !is_array($r['items']??null) || ($r['count']??null)!==count($r['items'])) { return new WP_Error('webhooks','Could not verify existing webhook configuration.'); }
        $all=array_merge($all,$r['items']); if (count($r['items'])<100) { return $all; }
    }
    return new WP_Error('webhooks','Webhook list requires manual review.');
}
function loomara_pf_register_120() {
    $key=loomara_pf_key_120(); $url=loomara_pf_url_120();
    if (!$key || strpos($url,'https://')!==0) { return new WP_Error('connection','An HTTPS site and Razorpay credentials are required.'); }
    $lock='loomara_failure_setup_lock_120'; if (!add_option($lock,time(),'',false)) { return new WP_Error('locked','Setup is already running or needs review after an interrupted request.'); }
    try {
        $list=loomara_pf_webhooks_120(); if (is_wp_error($list)) { return $list; }
        $matches=array_values(array_filter($list,function($w)use($url){return ($w['url']??'')===$url;}));
        $c=loomara_pf_config_120();
        if ($c && (($c['key']??'')!==$key || !loomara_pf_secret_120())) { return new WP_Error('changed','Credential identity/storage changed. Reconcile the existing dedicated webhook manually; no webhook was modified.'); }
        if (!$c) {
            if ($matches) { return new WP_Error('existing','An unrecognised webhook already uses this URL. Review it before connecting.'); }
            if (!function_exists('openssl_encrypt')) { return new WP_Error('crypto','Secure secret storage unavailable.'); }
            $secret=bin2hex(random_bytes(32)); $iv=random_bytes(12); $tag='';
            $cipher=openssl_encrypt($secret,'aes-256-gcm',hash('sha256',wp_salt('auth'),true),OPENSSL_RAW_DATA,$iv,$tag,'loomara-payment-failure-120');
            if ($cipher===false) { return new WP_Error('crypto','Could not secure the webhook secret.'); }
            // Persist intent BEFORE the network write. Uncertain submissions are GET-only reconciled.
            $c=['stage'=>'submitting','key'=>$key,'secret'=>base64_encode($iv.$tag.$cipher),'created'=>time(),'url'=>$url];
            if (!add_option('loomara_payment_failure_connection_120',$c,'',false)) { return new WP_Error('storage','Could not save setup intent.'); }
            loomara_pf_request_120('webhooks','POST',['url'=>$url,'active'=>true,'events'=>['payment.failed'=>true],'secret'=>$secret]);
            $list=loomara_pf_webhooks_120(); if (is_wp_error($list)) { return new WP_Error('uncertain','Registration response is uncertain. Use Check connection to reconcile; never create another webhook blindly.'); }
            $matches=array_values(array_filter($list,function($w)use($url){return ($w['url']??'')===$url;}));
        }
        if (count($matches)!==1) { return new WP_Error('match','Exactly one dedicated webhook could not be verified. No additional webhook will be submitted automatically.'); }
        $w=$matches[0];
        if (empty($w['id']) || !is_string($w['id']) || !preg_match('/^[A-Za-z0-9_]{6,100}$/D',$w['id']) || empty($w['active']) || empty($w['events']['payment.failed']) || !empty($c['id']) && $c['id']!==$w['id']) { return new WP_Error('state','Dedicated webhook is not active with the expected identity and event.'); }
        $c['id']=$w['id']; $c['stage']='connected'; $c['enabled_at']=$c['enabled_at']??time(); $c['checked_at']=time();
        update_option('loomara_payment_failure_connection_120',$c,false);
        return true;
    } finally { delete_option($lock); }
}
add_action('admin_menu',function(){add_submenu_page('woocommerce','Verified payment failures','Payment failure sync','manage_options','loomara-payment-failures','loomara_pf_page_120');});
function loomara_pf_page_120() {
    if (!current_user_can('manage_options')) { return; }
    $c=loomara_pf_config_120();
    echo '<div class="wrap"><h1>Verified payment-failure syncing</h1><p>Connection: <strong>'.(loomara_pf_ready_120()?'Dedicated failure webhook registered':'Not verified').'</strong></p><p>Future eligible checkouts only. A signed failure event queues read-only Razorpay verification. The order becomes Payment failed only when every saved gateway order and payment attempt is confirmed unpaid and failed. Authorised, captured, refunded, mismatched or uncertain cases are protected or require review. Abandoned checkouts do not trigger this workflow.</p><p>Confirmed failures appear in the customer’s order history with Retry payment, using the same order’s secure payment URL. No reminders, abandonment timeouts, capture, refund or historical scan are enabled here. Background verification uses the existing WooCommerce Action Scheduler and can run late.</p><p>This creates one separate payment.failed webhook at <code>'.esc_html(loomara_pf_url_120()).'</code>. Existing success/refund webhooks are untouched. A new webhook secret is generated and encrypted server-side; no secret is shown here or stored in GitHub. Changing WordPress authentication salts requires connection review.</p>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="loomara_pf_connect_120">';wp_nonce_field('loomara_pf_connect_120');
    echo '<button class="button button-primary">'.($c?'Check connection (no second webhook submission)':'Connect dedicated failure webhook').'</button></form><p>Registration alone does not prove delivery. A future genuine or test-mode failed payment must confirm end-to-end event delivery. No test payment is submitted here.</p></div>';
}
add_action('admin_post_loomara_pf_connect_120',function(){
    if (!current_user_can('manage_options')) { wp_die('Administrator access required.',403); }
    check_admin_referer('loomara_pf_connect_120'); $r=loomara_pf_register_120();
    if (is_wp_error($r)) { wp_die(esc_html($r->get_error_message()),409); }
    wp_safe_redirect(admin_url('admin.php?page=loomara-payment-failures'));exit;
});
