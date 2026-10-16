# Loomara Living v1.5.0

- Fine-line search, account and shopping-bag icons with accessible labels.
- Session-aware account/cart status refreshed outside cached page HTML.
- Header search, product-only results and compact collection search.
- Eight products per catalog page, four compact desktop columns and two mobile columns.
- Best Sellers and Sale tabs on the home page and catalog, using real sales and active sale prices.
- A Top Rated section which waits for approved, verified customer ratings.
- An expanded Our Story page about the brand's approach to everyday Indian homes.
- The Loomara feeling section is explicitly the penultimate home section.

No products, orders, reviews, payment settings or customer addresses are created or
changed by this release. The existing review-unpublishing policy is retained.
Collections show honest empty states when there is no sales or review data.

## Rollback

Redeploy the existing `v1.4.0` tag (commit
`fa719c3480f5f236d5340c465a27ca6c13bb8c73`) to restore the prior theme. This
release makes no database migrations, so it needs no separate database rollback.
The older `scripts/rollback-1.4.0.php` script applies only to reversing the earlier
v1.4.0 migration and is not needed for a v1.5.0 rollback.

## Validation

All 14 PHP files and the JavaScript passed syntax checks. The existing 16
account/checkout/recovery checks passed in a disposable WordPress installation.
Browser checks covered eight cards within a 1366×900 viewport, two mobile columns
without overflow at 390px, session-aware account labels after login, product search,
sales-ranked results, sale filtering, home tabs, the penultimate editorial section,
and the expanded story page. Test products and sales exist only in the local preview.
