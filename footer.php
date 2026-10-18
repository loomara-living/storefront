<footer class="site-footer">
  <div class="footer-links"><p>Explore</p>
    <?php if (class_exists('WooCommerce')) : ?><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Collection</a><?php endif; ?>
    <a href="<?php echo esc_url(home_url('/our-story/')); ?>">Our story</a>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a>
    <a href="<?php echo esc_url(home_url('/faqs/')); ?>">FAQs</a>
  </div>
  <div class="footer-contact"><p>Say hello</p><a href="mailto:hello@loomaraliving.com">hello@loomaraliving.com</a><a href="tel:+918175950351">+91 81759 50351</a>
    <a class="footer-instagram" href="https://www.instagram.com/loomara.living/" target="_blank" rel="noopener noreferrer" aria-label="Instagram: @loomara.living (opens in a new tab)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg><span>@loomara.living</span></a>
  </div>
  <div class="footer-policies"><p>Policies</p><a href="<?php echo esc_url(home_url('/returns-refunds/')); ?>">Returns &amp; refunds</a><a href="<?php echo esc_url(home_url('/shipping-delivery/')); ?>">Shipping &amp; delivery</a><a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy</a><a href="<?php echo esc_url(home_url('/terms-and-conditions/')); ?>">Terms &amp; conditions</a></div>
  <div class="footer-bottom"><span>© <?php echo esc_html(wp_date('Y')); ?> Loomara Living</span><span>Wrap your world in comfort</span></div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
