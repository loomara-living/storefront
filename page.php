<?php get_header(); ?>
<main id="main" class="wp-page-content">
  <?php while (have_posts()) : the_post(); ?>
    <article <?php post_class(); ?>>
      <header class="page-hero"><div><p class="eyebrow">Loomara Living</p><h1><?php the_title(); ?></h1></div></header>
      <div class="entry-content"><?php the_content(); ?></div>
    </article>
  <?php endwhile; ?>
</main>
<?php get_footer(); ?>

