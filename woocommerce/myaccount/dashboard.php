<?php
/** Profile-first account dashboard; retain third-party/native dashboard hooks. */
defined('ABSPATH') || exit;
do_action('woocommerce_account_dashboard');
$user=wp_get_current_user();
?>
<div class="account-dashboard-help">
  <p>Hello <strong><?php echo esc_html($user->display_name); ?></strong> (not you? <a href="<?php echo esc_url(wc_logout_url()); ?>">Log out</a>).</p>
  <p>From your account dashboard you can view your <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">recent orders</a>, manage your <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-address')); ?>">saved addresses</a>, and edit your <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>">password and account details</a>.</p>
</div>
<?php do_action('woocommerce_after_account_dashboard'); ?>
