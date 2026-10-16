<?php
defined('ABSPATH') || exit;
if (function_exists('woocommerce_content') && in_array('product', (array) get_query_var('post_type'), true)) {
    require get_template_directory() . '/woocommerce.php';
    return;
}
get_header();
?>
<main id="main" class="woocommerce-shell"><header class="shop-heading"><p class="eyebrow">Find something lovely</p><h1>Search results</h1></header>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <article><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article>
<?php endwhile; the_posts_pagination(); else : ?>
  <p>No results yet. Try another word or browse the collection.</p>
<?php endif; ?></main>
<?php get_footer(); ?>
