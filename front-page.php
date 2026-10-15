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
      <img class="hero-seal" src="https://horizons-cdn.hostinger.com/afc76bf0-621b-4376-8baa-6188eca190be/d684dbb59d647d2f19b70f7273f8bb18.jpg" alt="Loomara Living">
      <div class="sun-disc"></div><div class="bed-frame"><div class="pillow pillow-one"></div><div class="pillow pillow-two"></div><div class="duvet"></div><div class="gold-thread"></div></div>
      <span class="art-note">Rest, considered.</span>
    </div>
  </section>
  <section class="marquee" aria-label="Loomara values"><div>RESTFUL BY DESIGN <span>✦</span> SOFTLY LAYERED <span>✦</span> MADE FOR HOME <span>✦</span> RESTFUL BY DESIGN <span>✦</span></div></section>
  <section class="intro section-grid">
    <div class="section-number">01</div>
    <div class="intro-heading reveal"><p class="eyebrow">The Loomara feeling</p><h2>Made for the<br><em>rhythm of home.</em></h2></div>
    <div class="intro-copy reveal"><p class="dropcap">Comfort is more than a finishing touch. It is the quiet backdrop to the first cup of tea, the chapter read before sleep, and the unhurried Sunday that asks nothing of you.</p><p>Loomara Living is an invitation to make those everyday rituals feel a little more considered—through calm colour, tactile layers, and details that settle naturally into your space.</p></div>
  </section>
  <section class="collection">
    <div class="section-heading reveal"><div><p class="eyebrow">The first collection</p><h2>Layers for living</h2></div><?php if (class_exists('WooCommerce')) : ?><a class="text-link" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">View the collection <span aria-hidden="true">→</span></a><?php endif; ?></div>
    <div class="collection-grid">
      <a class="collection-card card-large reveal" href="<?php echo esc_url(class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/')); ?>"><div class="fabric fabric-bedding"><i></i><i></i><i></i></div><div class="card-meta"><span>01</span><div><h3>Bedding</h3><p>Calm foundations for the room you return to.</p></div><b aria-hidden="true">↗</b></div></a>
      <a class="collection-card reveal" href="<?php echo esc_url(class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/')); ?>"><div class="fabric fabric-pillows"><i></i><i></i></div><div class="card-meta"><span>02</span><div><h3>Pillows</h3><p>Soft support, thoughtfully shaped.</p></div><b aria-hidden="true">↗</b></div></a>
      <a class="collection-card card-dark reveal" href="<?php echo esc_url(class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/')); ?>"><div class="fabric fabric-layers"><i></i><i></i><i></i></div><div class="card-meta"><span>03</span><div><h3>Finishing layers</h3><p>Texture and warmth for the final touch.</p></div><b aria-hidden="true">↗</b></div></a>
    </div>
  </section>
  <section class="material-story"><div class="material-visual reveal" role="img" aria-label="Abstract close-up inspired by gently folded woven fabric"><span class="weave-line line-a"></span><span class="weave-line line-b"></span><span class="weave-line line-c"></span><p>Texture / tone / touch</p></div><div class="material-copy reveal"><p class="eyebrow">A thoughtful approach</p><h2>Beauty you can<br><em>feel.</em></h2><p>We believe the most enduring rooms begin with pieces that feel as good as they look. Our point of view favours breathable textures, restful palettes, and construction that earns a place in daily life.</p><a class="text-link" href="<?php echo esc_url(home_url('/our-story/')); ?>">Read our story <span aria-hidden="true">→</span></a></div></section>
  <section class="newsletter"><p class="eyebrow">Notes from Loomara</p><h2>A quieter inbox,<br><em>when there is news worth sharing.</em></h2><p>Join the list for collection updates and considered notes on making home feel softer.</p><p class="form-note">Newsletter signup will be connected before launch.</p></section>
</main>
<?php get_footer(); ?>

