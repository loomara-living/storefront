<?php get_header(); ?>
<main id="main" class="home-main">
  <section class="home-promotion home-offer-carousel" aria-label="Loomara offers" aria-roledescription="carousel">
    <div class="home-offer-viewport"><div class="home-offer-track">
    <a href="<?php echo esc_url(class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>" aria-label="Shop the Diwali offer — use DIWALI for 10% off eligible purchases, up to ₹500. Terms apply.">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/homepage-diwali-ad.jpg'); ?>" width="1983" height="793" alt="Diwali special — 10% off. Brighten your home with soft comforts. Shop now." fetchpriority="high" decoding="async">
    </a>
    <a href="<?php echo esc_url(class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>" aria-label="Shop bedding — 20% off your first eligible order with WELCOME20">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/homepage-welcome20-ad.jpg'); ?>" width="1983" height="793" alt="Loomara Living — special for new users: 20% off your first order. Shop now. Premium cotton fabric, soft and breathable, long-lasting quality, comfort for every home." loading="lazy" decoding="async">
    </a>
    <a href="<?php echo esc_url(class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>" aria-label="Explore the Loomara collection">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/homepage-shop-ad.jpg'); ?>" width="1983" height="793" alt="Explore our collection — bedsheets, comforters, cushion covers and combo sets. Shop now." loading="lazy" decoding="async">
    </a>
    </div></div>
    <div class="home-offer-controls" hidden><button type="button" data-offer-prev aria-label="Previous offer">‹</button><span data-offer-position aria-live="off">1 / 3</span><button type="button" data-offer-next aria-label="Next offer">›</button><button type="button" data-offer-pause aria-pressed="false">Pause</button></div>
  </section>
  <?php if (function_exists('loomara_combo_home_banner')) { loomara_combo_home_banner(); } ?>
  <section class="hero">
    <div class="hero-copy reveal">
      <p class="eyebrow">A softer way home</p>
      <h1>Wrap your world<br>in <em>comfort.</em></h1>
      <p class="lede">Thoughtful bedding for slower mornings, softer evenings, and the life lived between them.</p>
      <div class="actions">
        <?php if (class_exists('WooCommerce')) : ?><a class="button button-primary" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Explore the collection</a><?php endif; ?>
        <a class="text-link" href="<?php echo esc_url(home_url('/our-story/')); ?>">Discover our point of view <span aria-hidden="true">→</span></a>
      </div>
    </div>
    <div class="hero-art hero-art-photo reveal">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/home-rest-considered.jpg'); ?>" width="1317" height="1194" alt="Loomara Living — Rest, considered. A sunlit bedroom with floral bedding, navy cushions and a soft cream throw." loading="lazy" decoding="async">
    </div>
  </section>
  <section class="marquee" aria-label="Loomara values"><div>RESTFUL BY DESIGN <span>✦</span> SOFTLY LAYERED <span>✦</span> MADE FOR HOME <span>✦</span> RESTFUL BY DESIGN <span>✦</span></div></section>
  <section class="collection home-category-section" id="home-categories">
    <div class="section-heading reveal"><div><p class="eyebrow">Shop by category</p><h2>Layers for living</h2></div><?php if (class_exists('WooCommerce')) : ?><a class="text-link" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">View the collection <span aria-hidden="true">→</span></a><?php endif; ?></div>
    <div class="category-grid">
      <a class="category-card category-card-artwork reveal" href="<?php echo esc_url(loomara_product_category_url('bedsheets')); ?>" aria-label="Shop Bedsheets"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/home-category-bedsheets.jpg'); ?>" width="832" height="466" loading="lazy" decoding="async" alt="Bedsheets — Everyday Comfort for a Brighter Tomorrow. Shop Bedsheets."></a>
      <a class="category-card category-card-artwork reveal" href="<?php echo esc_url(loomara_product_category_url('comforters')); ?>" aria-label="Shop Comforters"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/home-category-comforters.jpg'); ?>" width="832" height="466" loading="lazy" decoding="async" alt="Comforters — Warmth You’ll Love All Year Round. Shop Comforters."></a>
      <a class="category-card category-card-artwork reveal" href="<?php echo esc_url(loomara_product_category_url('combo')); ?>" aria-label="Shop Combo Sets"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/home-category-combo.jpg'); ?>" width="832" height="467" loading="lazy" decoding="async" alt="Combo Sets — Complete Comfort in Perfect Harmony. Shop Combo Sets."></a>
      <a class="category-card category-card-artwork reveal" href="<?php echo esc_url(loomara_product_category_url('cushion-covers')); ?>" aria-label="Shop Cushion Covers"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/home-category-cushion-covers.jpg'); ?>" width="832" height="467" loading="lazy" decoding="async" alt="Cushion Covers — Little Details Make a Big Difference. Shop Cushion Covers."></a>
    </div>
  </section>
  <?php if (class_exists('WooCommerce')) : ?>
    <section class="home-products home-products-new">
      <div class="section-heading reveal"><div><p class="eyebrow">Just arrived</p><h2>New collection</h2></div><a class="text-link" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Explore new arrivals <span aria-hidden="true">→</span></a></div>
      <div class="home-product-grid reveal"><?php echo do_shortcode('[products limit="4" columns="4" orderby="date" order="DESC"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
    </section>
    <section class="home-products home-best-sellers">
      <div class="section-heading"><div><p class="eyebrow">Chosen again and again</p><h2>Best sellers</h2></div><a class="text-link" href="<?php echo esc_url(add_query_arg('collection', 'best-sellers', wc_get_page_permalink('shop'))); ?>">Shop best sellers <span aria-hidden="true">→</span></a></div>
      <div class="home-product-grid"><?php loomara_home_collection('best-sellers'); ?></div>
    </section>
    <?php $top_rated_ids = loomara_top_rated_ids(); if ($top_rated_ids) : ?>
    <section class="home-products home-top-rated">
      <div class="section-heading"><div><p class="eyebrow">Loved in real homes</p><h2>Top rated</h2></div></div>
      <div class="home-product-grid"><?php loomara_top_rated_collection($top_rated_ids); ?></div>
    </section>
    <?php endif; ?>
  <?php endif; ?>
  <?php loomara_recent_products_section(); ?>
  <?php loomara_customer_testimonials(); ?>
  <section id="loomara-feeling" class="intro section-grid">
    <div class="intro-heading reveal"><p class="eyebrow">The Loomara feeling</p><h2>Made for the<br><em>rhythm of home.</em></h2></div>
    <div class="intro-copy reveal"><p class="dropcap">Comfort is more than a finishing touch. It is the quiet backdrop to the first cup of tea, the chapter read before sleep, and the unhurried Sunday that asks nothing of you.</p><p>Loomara Living is an invitation to make those everyday rituals feel a little more considered—through calm colour, tactile layers, and details that settle naturally into your space.</p></div>
  </section>
  <section class="newsletter"><p class="eyebrow">At home in India</p><h2>Something softer,<br><em>for your everyday.</em></h2><p>Thoughtful bedding, delivered across serviceable PIN codes in India.</p><div class="home-closing-actions"><a class="button closing-shop-button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Find your next layer <span aria-hidden="true">↗</span></a><a class="button closing-contact-button" href="<?php echo esc_url(home_url('/contact/')); ?>">Let’s talk home <span aria-hidden="true">→</span></a></div></section>
</main>
<?php get_footer(); ?>
