# Loomara Living v1.7.1

## v1.7.1 — category colours and catalogue navigation

- Top-of-catalog breadcrumbs: Home → Category on category pages; Home →
  Category → selected variant SKU on product pages. Nested categories supported.
- Direct category-switch links for all four collections; keyboard-focus styles
  and mobile wrapping. Breadcrumb SKU follows colour/size selection and reset.

- Category grids show one card per available colour, with its own image, label,
  variation price and colour/size-preselected product link.
- Expand colours before eight-card pagination; don't duplicate cards across sizes.
- Preserve category visibility/collection filters and price sorting. No inventory
  mutations or new sellable product records.
- Code-only rollback: redeploy v1.7.0 (edd73bfee9f0aec41585428c71036bffbf3f560b).
  Existing WooCommerce inventory remains unchanged; do not rerun its migration.

## Prior release: v1.7.0

- King-size bedsheets, comforters and combos with canonical -K variant SKUs.
- Colour/size selectors and corresponding image galleries; inventory respects deleted files.
- Neutral customer-facing image text; historical provenance remains internal.
- Administrator-only, nonce-protected WooCommerce migration with identity preflight,
  private product snapshot and status report. Prices/stock copied from live products,
  never from local fixtures. Four designs/five variants; old rose URL redirects.
- No customer/order/payment configuration changes and no QA products deployed.

- Fix mobile block-cart grid placement so product names, descriptions, quantities
  and line totals occupy separate rows without overlap; retain classic-cart support.
- Add a line-art coupon/discount icon and a clear coupon-entry control.
- Tighten homepage section spacing and remove the Texture / Tone / Touch section.
- Present WooCommerce's email-confirmation action as a proper button, moving the
  explanation into an expandable Why confirm? detail. Preserve the native signed URL
  and verification behaviour; no emails sent by the update itself.
- Preserve the original text wordmark in the header. Use the supplied transparent
  logo in the hero without a background card; remove the footer logo.
- Add restrained card, icon, button and hero transitions with reduced-motion support.
- Expand product search to match category names/slugs, tags and SKUs as well as
  titles/descriptions; support bedsheet/bed sheet spelling variants and retain
  the results grid instead of redirecting single matches to a product page.

Rollback: redeploy v1.6.0 (`5b885dcc43a856d6841fcba5a7a11dbb5dc408be`).
Inventory migration is an explicit admin action, not an automatic storefront hook.
Private snapshots are stored in option loomara_inventory_backup_170; status and child
IDs in loomara_inventory_release_170. Reverting theme code alone DOES NOT reverse
variable-product conversion. Review the snapshot with an administrator and reconcile
orders/stock since migration before restoring product metadata. Do not restore the
whole database over newer orders. Legacy product records are retained, not deleted.

Validation: PHP/JS syntax; mobile cart at 320px/390px/658px, desktop layout,
coupon expansion, compact email-confirmation fixture preserving its href,
homepage section order/spacing, supplied logo, and existing account/rating checks.
No live orders, payment attempts or confirmation emails submitted.

## Prior release: v1.6.0

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
