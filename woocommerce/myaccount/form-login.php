<?php
/** Sequential account access using WooCommerce's standard authentication handlers. */
defined('ABSPATH') || exit;
$register = loomara_registration_mode() && get_option('woocommerce_enable_myaccount_registration') === 'yes';
$account_url = wc_get_page_permalink('myaccount');
do_action('woocommerce_before_customer_login_form');
?>
<div class="account-experience">
  <aside class="account-editorial">
    <p class="eyebrow">Your everyday, a little softer</p>
    <h2>A place for<br>your kind of <em>home.</em></h2>
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/products/everyday-calm-bedding-combo-sage.jpg'); ?>" alt="Soft sage bedding, layered for a restful home" width="900" height="900">
    <div class="account-perks"><span>♡ Save your favourites</span><span>↗ Follow every order</span><span>⌂ Keep your addresses ready</span></div>
    <p>Thoughtful layers. Considered details. At home in India.</p>
  </aside>
  <section class="account-panel" aria-labelledby="account-title">
    <p class="eyebrow"><?php echo $register ? 'Make yourself at home' : 'Your Loomara account'; ?></p>
    <h2 id="account-title"><?php echo $register ? 'Your softer days start here.' : 'Good to have you back.'; ?></h2>
    <p><?php echo $register ? 'One account for favourite finds, saved addresses and easier checkouts.' : 'Sign in to pick up where you left off.'; ?></p>
    <?php if (!$register) : ?>
      <form class="woocommerce-form woocommerce-form-login login" method="post">
        <?php do_action('woocommerce_login_form_start'); ?>
        <p class="woocommerce-form-row form-row-wide"><label for="username">Username, email or mobile number <span aria-hidden="true">*</span></label><input type="text" class="input-text" name="username" id="username" autocomplete="username" required value="<?php echo isset($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>"></p>
        <p class="woocommerce-form-row form-row-wide"><label for="password">Password <span aria-hidden="true">*</span></label><input class="input-text" type="password" name="password" id="password" autocomplete="current-password" required></p>
        <?php do_action('woocommerce_login_form'); ?>
        <p class="form-row"><label class="woocommerce-form-login__rememberme"><input name="rememberme" type="checkbox" value="forever"> Remember me</label></p>
        <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
        <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="Log in">Sign in</button>
        <p class="woocommerce-LostPassword"><a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Forgot your password?</a></p>
        <?php do_action('woocommerce_login_form_end'); ?>
      </form>
      <?php if (get_option('woocommerce_enable_myaccount_registration') === 'yes') : ?><p class="account-switch">New to Loomara? <a href="<?php echo esc_url(add_query_arg('action', 'register', $account_url)); ?>">Create an account →</a></p><?php endif; ?>
    <?php else : ?>
      <form method="post" class="woocommerce-form woocommerce-form-register register">
        <?php do_action('woocommerce_register_form_start'); ?>
        <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?><p class="form-row-wide"><label for="reg_username">Username *</label><input type="text" class="input-text" name="username" id="reg_username" autocomplete="username" required value="<?php echo isset($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>"></p><?php endif; ?>
        <p class="form-row-wide"><label for="reg_email">Email address *</label><input type="email" class="input-text" name="email" id="reg_email" autocomplete="email" required value="<?php echo isset($_POST['email']) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>"></p>
        <p class="form-row-wide"><label for="reg_password">Create a password *</label><input type="password" class="input-text" name="password" id="reg_password" autocomplete="new-password" required minlength="6" pattern="(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9\s]).{6,}" aria-describedby="password-tip" data-registration-password><span id="password-tip" class="password-tip" aria-live="polite">Use at least 6 characters with a letter, a number and a special character.</span></p>
        <?php do_action('woocommerce_register_form'); ?>
        <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
        <button type="submit" class="woocommerce-Button button" name="register" value="Register">Create account</button>
        <?php do_action('woocommerce_register_form_end'); ?>
      </form>
      <p class="account-switch">Already at home here? <a href="<?php echo esc_url($account_url); ?>">Sign in →</a></p>
    <?php endif; ?>
  </section>
</div>
<?php do_action('woocommerce_after_customer_login_form'); ?>
