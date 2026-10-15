<footer class="site-footer">
  <div class="footer-brand"><span class="footer-mark">L</span><div><strong>Loomara</strong><small>Living</small></div></div>
  <div class="footer-links"><p>Explore</p>
    <?php if (class_exists('WooCommerce')) : ?><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Collection</a><?php endif; ?>
    <a href="<?php echo esc_url(home_url('/our-story/')); ?>">Our story</a>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a>
  </div>
  <div class="footer-contact"><p>Say hello</p><a href="mailto:hello@loomaraliving.com">hello@loomaraliving.com</a><a href="tel:+918175950351">+91 81759 50351</a></div>
  <div class="footer-bottom"><span>© <?php echo esc_html(wp_date('Y')); ?> Loomara Living</span><span>Wrap your world in comfort</span></div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

