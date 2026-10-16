# Loomara Living v1.6.0

## Current release

- Sale in primary navigation with a subtle sparkle and reduced-motion support.
- Separate Best Sellers section; retain Just Arrived; remove the combined tabs.
- Header heart icon links directly to the account wishlist.
- Fixed product-card tracks align titles, stars, prices and buttons on desktop/mobile.
- Native 1–5-star signed-in customer reviews, one per customer/product, nonce-protected
  and held for moderation. Previously archived admin reviews stay archived.
- Top Rated: maximum eight products averaging at least 4 stars; order by approved
  review count, rating, then newest product date/ID (all descending). If none qualify,
  clearly labelled unrated/newest pieces appear without claiming they are top rated.
- Private India-only multi-address book: named shipping addresses, add/edit/default,
  preservation of existing shipping details and saved-address selection at checkout.
- One delivery/contact address also used for billing; prefilled email, manual entry,
  and opt-in to save a new address after order creation.
- Render native classic WooCommerce checkout in place of a checkout block, without
  rewriting the page or changing payment methods/settings.

### v1.6.0 rollback and customer data

Redeploy baseline commit `c055364584d011ead7bc4cbf0a04e2597a1ac1a8` (v1.5.0).
No schema migrations or destructive operations. Customer-submitted address data is stored
in `_loomara_shipping_addresses` and `_loomara_default_shipping_address`; a selected
default is copied into native shipping fields. Addresses/reviews remain after a code
rollback. Never delete customer data to revert the UI. Review option changes are runtime
filters, removed by a code rollback. Checkout page content is not rewritten.

Validation: PHP parsing, JS syntax, 14 local functional assertions, desktop/mobile
alignment and overflow, two saved addresses, checkout selection/manual edits, and a
4-star review held for approval. No live test orders or payments. QA data is local only.

## Prior release: v1.5.0

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
