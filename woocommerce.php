<?php get_header(); ?>
<main id="main" class="woocommerce-shell">
  <?php loomara_catalog_navigation(); ?>
  <?php if (is_shop() || is_product_taxonomy() || is_search()) : ?>
    <header class="shop-heading">
      <p class="eyebrow">The Loomara collection</p>
      <h1><?php if (is_search()) { echo 'Search: ' . esc_html(get_search_query()); } else { woocommerce_page_title(); } ?></h1>
      <p class="shop-intro">Considered layers for softer rooms, slower evenings, and everyday comfort.</p>
      <?php loomara_catalog_category_links('Shop by category'); ?>
    </header>
    <?php loomara_shop_discovery(); ?>
  <?php endif; ?>
  <div class="woocommerce-content"><?php if (is_shop() || is_product_taxonomy() || (is_search() && in_array('product',(array)get_query_var('post_type'),true))) { loomara_category_colour_content(); } else { woocommerce_content(); } ?></div>
</main>
<?php get_footer(); ?>
