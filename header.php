<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f6f0e6">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header" data-header>
  <a class="wordmark" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Loomara Living home">
    <span>Loomara <small>Living</small></span>
  </a>
  <button class="menu-button" type="button" aria-expanded="false" aria-controls="primary-nav" data-menu-button>
    <span></span><span></span><span></span><span class="sr-only">Open menu</span>
  </button>
  <nav id="primary-nav" class="primary-nav" aria-label="Primary navigation" data-nav>
    <?php
    wp_nav_menu([
        'theme_location' => 'primary',
        'container' => false,
        'items_wrap' => '%3$s',
        'fallback_cb' => 'loomara_menu_fallback',
    ]);
    ?>
  </nav>
  <?php if (class_exists('WooCommerce')) : ?>
    <div class="header-actions">
      <?php if (!is_shop() && !is_cart()) : ?>
        <a class="header-cta" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Explore the collection <span aria-hidden="true">↗</span></a>
      <?php endif; ?>
      <?php echo loomara_header_cart_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  <?php endif; ?>
</header>

