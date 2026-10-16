<?php get_header(); ?>
<main id="main" class="woocommerce-shell">
  <?php if (is_shop() || is_product_taxonomy() || is_search()) : ?>
    <header class="shop-heading">
      <p class="eyebrow">The Loomara collection</p>
      <h1><?php if (is_search()) { echo 'Search: ' . esc_html(get_search_query()); } else { woocommerce_page_title(); } ?></h1>
      <p class="shop-intro">Considered layers for softer rooms, slower evenings, and everyday comfort.</p>
    </header>
    <?php loomara_shop_discovery(); ?>
  <?php endif; ?>
  <div class="woocommerce-content"><?php woocommerce_content(); ?></div>
</main>
<?php get_footer(); ?>

