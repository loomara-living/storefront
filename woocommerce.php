<?php get_header(); ?>
<main id="main" class="woocommerce-shell">
  <?php if (is_shop() || is_product_taxonomy()) : ?>
    <header class="shop-heading">
      <p class="eyebrow">The Loomara collection</p>
      <h1><?php woocommerce_page_title(); ?></h1>
      <p class="shop-intro">Considered layers for softer rooms, slower evenings, and everyday comfort.</p>
    </header>
  <?php endif; ?>
  <div class="woocommerce-content"><?php woocommerce_content(); ?></div>
</main>
<?php get_footer(); ?>

