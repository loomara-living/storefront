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
<?php loomara_offer_banner(); ?>
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
      <?php if (function_exists('loomara_header_offers_button')) { loomara_header_offers_button(); } ?>
      <a class="header-icon header-search" href="#header-search-panel" aria-label="Search products" title="Search products" data-search-toggle aria-expanded="false" aria-controls="header-search-panel"><?php echo loomara_icon('search'); ?></a>
      <a class="header-icon header-account" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" aria-label="Your account" title="Your account"><?php echo loomara_icon('user'); ?></a>
      <a class="header-icon header-wishlist" href="<?php echo esc_url(wc_get_account_endpoint_url('wishlist')); ?>" aria-label="Wishlist" title="Wishlist"><?php echo loomara_icon('heart'); ?></a>
      <?php echo loomara_header_cart_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  <?php endif; ?>
</header>
<?php if (class_exists('WooCommerce')) : ?>
<div id="header-search-panel" class="header-search-panel" hidden>
  <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <label for="header-product-search">Find your softer layer</label>
    <div class="search-input-row"><input type="search" name="s" id="header-product-search" placeholder="Try bedsheets, comforter, sage…" required><input type="hidden" name="post_type" value="product"><button class="button button-primary" type="submit">Search</button><button class="search-close" type="button" aria-label="Close search" data-search-close>×</button></div>
  </form>
</div>
<?php endif; ?>
