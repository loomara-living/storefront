<?php get_header(); ?>
<main id="main">
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
    <div class="hero-art reveal" aria-label="An abstract arrangement inspired by layered bed linen" role="img">
      <span class="hero-brand brand-image"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/loomara-logo-transparent.png'); ?>" alt="Loomara Living" width="1536" height="1024"></span><div class="sun-disc"></div><div class="bed-frame"><div class="pillow pillow-one"></div><div class="pillow pillow-two"></div><div class="duvet"></div><div class="gold-thread"></div></div>
      <span class="art-note">Rest, considered.</span>
    </div>
  </section>
  <section class="marquee" aria-label="Loomara values"><div>RESTFUL BY DESIGN <span>✦</span> SOFTLY LAYERED <span>✦</span> MADE FOR HOME <span>✦</span> RESTFUL BY DESIGN <span>✦</span></div></section>
  <section class="collection home-category-section" id="home-categories">
    <div class="section-heading reveal"><div><p class="eyebrow">Shop by category</p><h2>Layers for living</h2></div><?php if (class_exists('WooCommerce')) : ?><a class="text-link" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">View the collection <span aria-hidden="true">→</span></a><?php endif; ?></div>
    <div class="category-grid">
      <a class="category-card reveal" href="<?php echo esc_url(loomara_product_category_url('bedsheets')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/products/botanical-whisper-bedsheet-ivory.jpeg'); ?>" alt="Soft botanical bedsheet styled in a calm bedroom"><div class="category-meta"><span>01</span><div><h3>Bedsheets</h3><p>Breathable foundations for unhurried rest.</p></div><b aria-hidden="true">↗</b></div></a>
      <a class="category-card reveal" href="<?php echo esc_url(loomara_product_category_url('comforters')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/products/cloud-rest-comforter-ivory.jpg'); ?>" alt="Ivory comforter on a warmly styled bed"><div class="category-meta"><span>02</span><div><h3>Comforters</h3><p>Lofty warmth with a light, restful feel.</p></div><b aria-hidden="true">↗</b></div></a>
      <a class="category-card reveal" href="<?php echo esc_url(loomara_product_category_url('combo')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/products/everyday-calm-bedding-combo-sage.jpg'); ?>" alt="Sage bedding combo with coordinated pillowcases"><div class="category-meta"><span>03</span><div><h3>Combo</h3><p>Coordinated sets, ready to layer.</p></div><b aria-hidden="true">↗</b></div></a>
      <a class="category-card reveal" href="<?php echo esc_url(loomara_product_category_url('cushion-covers')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/products/soft-geometry-cushion-cover-pair.jpg'); ?>" alt="Terracotta and oat woven cushion cover pair"><div class="category-meta"><span>04</span><div><h3>Cushion covers</h3><p>Tactile details that finish the room.</p></div><b aria-hidden="true">↗</b></div></a>
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
    <section class="home-products home-top-rated">
      <div class="section-heading"><div><p class="eyebrow">Loved in real homes</p><h2>Top rated</h2></div></div>
      <div class="home-product-grid"><?php loomara_top_rated_collection(); ?></div>
    </section>
  <?php endif; ?>
  <?php loomara_customer_testimonials(); ?>
  <section id="loomara-feeling" class="intro section-grid">
    <div class="intro-heading reveal"><p class="eyebrow">The Loomara feeling</p><h2>Made for the<br><em>rhythm of home.</em></h2></div>
    <div class="intro-copy reveal"><p class="dropcap">Comfort is more than a finishing touch. It is the quiet backdrop to the first cup of tea, the chapter read before sleep, and the unhurried Sunday that asks nothing of you.</p><p>Loomara Living is an invitation to make those everyday rituals feel a little more considered—through calm colour, tactile layers, and details that settle naturally into your space.</p></div>
  </section>
  <section class="newsletter"><p class="eyebrow">At home in India</p><h2>Something softer,<br><em>for your everyday.</em></h2><p>Thoughtful bedding, delivered across serviceable PIN codes in India.</p><div class="home-closing-actions"><a class="button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Find your next layer</a><a class="button" href="<?php echo esc_url(home_url('/contact/')); ?>">Let’s talk home</a></div></section>
</main>
<?php get_footer(); ?>
