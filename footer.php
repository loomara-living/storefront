<footer class="site-footer footer-artwork">
  <div class="footer-layout">
    <div class="footer-native">
      <div class="footer-intro"><p class="footer-tagline">Wrap your world<br>in comfort.</p><p>Thoughtfully designed home textiles for a more comfortable, beautiful everyday.</p>
        <a href="https://www.instagram.com/loomara.living/" target="_blank" rel="noopener noreferrer" aria-label="Loomara Living on Instagram (opens in a new tab)">Instagram ↗</a>
        <a href="mailto:hello@loomaraliving.com">hello@loomaraliving.com</a>
        <a href="tel:+918175950351">+91 81759 50351</a>
      </div>
      <nav class="footer-column" aria-label="Footer Shop"><h2>Shop</h2>
        <?php foreach (['bedsheets'=>'Bedsheets','comforters'=>'Comforters','combo'=>'Combo Sets','cushion-covers'=>'Cushion Covers'] as $slug=>$label) : ?>
          <a href="<?php echo esc_url(loomara_product_category_url($slug)); ?>"><?php echo esc_html($label); ?></a>
        <?php endforeach; ?>
        <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>">All Products</a>
      </nav>
      <nav class="footer-column" aria-label="Footer About"><h2>About</h2>
        <a href="<?php echo esc_url(home_url('/our-story/')); ?>">Our Story</a>
        <a href="<?php echo esc_url(home_url('/our-materials/')); ?>">Our Materials</a>
        <a href="<?php echo esc_url(home_url('/care-guide/')); ?>">Care Guide</a>
        <a href="<?php echo esc_url(home_url('/size-guide/')); ?>">Size Guide</a>
        <a href="<?php echo esc_url(home_url('/faqs/')); ?>">FAQ</a>
      </nav>
      <nav class="footer-column" aria-label="Footer Customer Support"><h2>Customer Support</h2>
        <a href="<?php echo esc_url(function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('orders') : home_url('/my-account/')); ?>">Track Your Order</a>
        <a href="<?php echo esc_url(home_url('/shipping-delivery/')); ?>">Shipping &amp; Delivery</a>
        <a href="<?php echo esc_url(home_url('/returns-refunds/')); ?>">Returns &amp; Refunds</a>
        <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact Us</a>
      </nav>
      <nav class="footer-column" aria-label="Footer Policies"><h2>Policies</h2>
        <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a>
        <a href="<?php echo esc_url(home_url('/terms-and-conditions/')); ?>">Terms &amp; Conditions</a>
        <a href="<?php echo esc_url(home_url('/returns-refunds/')); ?>">Refund Policy</a>
        <a href="<?php echo esc_url(home_url('/shipping-delivery/')); ?>">Shipping Policy</a>
      </nav>
    </div>
    <div class="footer-bedding-art" aria-hidden="true"></div>
  </div>
  <div class="footer-assurances" aria-label="Shopping with Loomara">
    <span>© <?php echo esc_html(wp_date('Y')); ?> Loomara Living. All rights reserved.</span>
    <a href="<?php echo esc_url(home_url('/shipping-delivery/')); ?>">Free India shipping</a>
    <span>Secure payments</span>
    <a href="<?php echo esc_url(home_url('/returns-refunds/')); ?>">Eligible returns</a>
    <span>Thoughtfully crafted</span>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
