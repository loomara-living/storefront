<?php get_header(); ?>
<main id="main" class="wp-page-content"><section class="page-hero"><div><p class="eyebrow">Let’s find your way home</p><h1>Page not found</h1><p>This page may have moved. Your collection is still here.</p><a class="button button-primary" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>">Explore the collection</a></div></section></main>
<?php get_footer(); ?>
