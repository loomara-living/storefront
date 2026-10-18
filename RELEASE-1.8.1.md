# v1.8.1 — WooCommerce product URL conflict

Live diagnostics identified Hostinger Ecommerce (Beta) 1.0.7 registering
`^product/([^/]+)/?$` as `hostinger_product_id`, ahead of WooCommerce's route.
Its separate catalogue does not contain WooCommerce products and forces a 404.

Replace only that conflicting generated rule with WooCommerce's native
`product` query variable, leaving public URLs, plugin activation/settings,
products, prices, stock, orders, customers and payment configuration unchanged.
Regenerate rules once. v1.8.0 responsive catalogue fixes remain included.

Rollback: restore v1.7.9 in a new main commit and refresh permalink rules and
generated caches. Reverting just to v1.8.0 restores the plugin routing conflict.
