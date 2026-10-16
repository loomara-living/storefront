<?php get_header(); ?>
<main id="main" class="wp-page-content">
  <?php while (have_posts()) : the_post(); ?>
    <article <?php post_class(); ?>>
      <?php if (!(function_exists('is_account_page') && is_account_page() && !is_user_logged_in())) : ?>
        <header class="page-hero"><div><p class="eyebrow"><?php echo function_exists('is_checkout') && is_checkout() ? 'Almost home · India delivery' : 'Loomara Living'; ?></p><h1><?php the_title(); ?></h1></div></header>
      <?php endif; ?>
      <div class="entry-content"><?php the_content(); ?></div>
    </article>
  <?php endwhile; ?>
</main>
<?php get_footer(); ?>
