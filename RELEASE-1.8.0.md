# v1.8.0 — Product routing and responsive catalogue

- Rebuild generated WordPress product permalink rules once, after WooCommerce registers its routes. This is a soft flush; no product, order, customer or .htaccess changes.
- Use the same colour-level catalogue on Shop and category pages, excluding migrated canonical duplicate records.
- Bound product cards and thumbnails to a four-column desktop / two-column mobile grid, with aligned titles and actions.
- Show four compact home category cards on desktop and fix mobile descriptions occupying the hidden number column.
- Provide an explicit not-found page instead of misleading homepage-like fallback content.

Validation: PHP syntax parsing and whitespace checks; responsive browser and live product-link checks during deployment.

Rollback: restore the theme tree at v1.7.9 (af075cece9d4493df5734ecee2b7f581d81e6cbe) in a new main commit, publish a matching patch release, and clear generated caches. The regenerated permalink rules are safe to keep. No inventory or customer data migration is included.
