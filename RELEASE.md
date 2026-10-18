# Loomara Living — Release history

All release details are maintained in this single file, newest first. Historical notes describe the behaviour and policies at the time of each release.

## v1.9.4 — Cart polish and customer order controls

- Compact product likes caption, calmer inline cart-success notice, larger cart/add-on images and smaller discovery headings. Available matching combos precede cover add-ons.
- Signed-in owners can confirm immediate cancellation of Pending, On hold or Processing orders without recorded shipping/tracking evidence. Ownership, nonce and fresh status are checked on POST; terminal/shipped/tracked orders are rejected. Paid cancellations explicitly require a separate manual refund; no refund API is called.
- Future authenticated checkouts persist a server-side owner marker. Magic Checkout saves retain that owner and account email, even when Razorpay returns another email; shipping address/mobile continue through normal gateway handling. Redundant gateway account creation is suppressed for authenticated/bound requests without changing stored gateway settings. Unbound historic orders remain unchanged; no email-based claiming or order-privacy bypass.
- 35 isolated ownership/cancellation assertions plus 33 existing address/timestamp regression assertions passed. Styling fixture checked at 390px and 1366px without overflow. No live cancellation, refund, payment or customer change performed. Full gateway acceptance testing still requires an actual customer checkout.
- Rollback: restore v1.9.3 theme code. Customer-confirmed cancellations are real order changes and cannot be undone by reverting code; refunds remain manual.

## v1.9.3 — Cart delivery and customer order details

- My Account order cards show date/time in IST. Successful Magic Checkout shipping addresses auto-save only when the customer's book does not already contain that PIN; existing addresses/defaults are never overwritten. Razorpay full recipient names are preserved when no separate surname is supplied; manual address validation remains unchanged.
- Read the delivery form action attribute explicitly, avoiding a named action input shadowing the form property. Keep same-origin POST, session cookies and server nonce validation.
- Explain expired checks and non-JSON/server responses; always release the retry button. Preserve backend missing-weight/courier messages.
- Six isolated frontend scenarios and 33 isolated PHP assertions passed. Product-page checker, merchant/payment settings, login rules and historical orders remain unchanged. No real payment or customer-address mutation performed during QA.
- Rollback: restore v1.9.2 code; no database migration. Addresses saved through future paid orders remain customer-editable and are not automatically deleted.

## v1.9.2 — Saved addresses and polished customer stories

- Customer stories show one full-width testimonial at a time rather than cards. Multiple published stories rotate right with looping, touch swipes, previous/next and pause; one quote stays still. Reduced motion disables autoplay. All quotes share a reserved grid row to avoid height shifts.
- Closing homepage links become distinct gold/outlined buttons with keyboard focus and mobile stacking. Signed-in authors see a private pending-review message for their own submitted testimonial; approval requirements remain unchanged.
- Signed-in customers may delete their own saved address using a nonce-protected POST and an in-page confirmation. Past orders and billing fields remain unchanged.
- Deleting the default chooses the next saved address; deleting the last clears native shipping fallback and prevents a stale deleted address from reappearing.
- Remove the Razorpay product-page affordability promotion at its native hooks. Checkout EMI choices, gateway configuration and other payment methods remain unchanged, as explicitly requested.
- Eleven isolated deletion tests and five product-promotion hook checks passed. No real customer addresses deleted during QA. Desktop/mobile local carousel and button checks passed without horizontal overflow; QA quotes remain outside the theme and are never published.
- Rollback: restore v1.9.1 code. Customer-requested address deletions are not automatically restored.

## v1.9.1 — Magic Checkout saved addresses

- Save a complete India shipping address from a paid Razorpay Magic Checkout WooCommerce order into that order owner's private address book.
- Never identify or merge customers by submitted email/phone; guest and unpaid orders do not create account addresses.
- Deduplicate normalized addresses, preserve chosen defaults/native shipping fields and the 20-address limit. Repeated gateway callbacks are idempotent; concurrent writes are deferred.
- No payment/gateway configuration changes, historic order backfill, credentials or new Razorpay API calls.
- Seventeen isolated PHP assertions passed with fake orders/customers only. A real successful customer checkout remains the end-to-end acceptance test; no payment made during QA.
- Rollback: revert this release's include and `inc/magic-address-sync.php`; already saved addresses remain editable in My Account and are not deleted.

## v1.9.0 — Customer stories, cart discovery and catalogue organisation

- Hero logo moves close to the artwork top; remove the feeling-section 01 and
  add Find your next layer beside Let’s talk home on desktop/mobile.
- Likes display only at two or more: homepage hover/focus, product-detail text
  under the heart, no listing tooltip. Text is “N customers likes this product”.
  Filled hearts follow the current customer’s wishlist, never aggregate counts.
- Future order references start OD; existing stored order references are retained.
- Address label defaults Home with Home/Work/Custom choices; first/last names
  share a row and phone occupies the next. Desktop filters scroll with the page.
- Genuine paid customers can submit opt-in testimonials on Orders and Home;
  submissions are pending until manually approved in Customer testimonials.
  Homepage shows only the latest five published quotes, with left-to-right
  rotation, pause/focus/hover/reduced-motion support and a thoughtful empty state.
  No sample testimonials are deployed. FAQ policies become numbered question 08;
  unambiguous fuzzy corrections appear in “Showing results for: …”. Shipping
  debug-zone notices are suppressed without removing real shipping errors.
- Cart adds compact cover recommendations and exact-SKU matching combos, plus
  Shiprocket estimates using the default address PIN with manual recheck. The
  PIN does not change saved addresses. API failures are unavailable estimates,
  not proof of unserviceability; quotes do not create shipments or payments.
- Local images now sit Category/Subcategory/Design/SKU; all 27 image hashes
  verified unchanged. Six disabled local combo draft designs/seven variant
  examples await owner-confirmed contents, prices, stock and genuine photographs.
  These drafts are NOT published, imported or included in theme deployment.
- Explicit administrator-only migration snapshots 28 fixed existing identities,
  sets bedsheets 500 g, comforters 1 kg, combos 1.5 kg using the configured Woo
  weight unit, and updates variant folder metadata. Prices, stock, image IDs,
  sizes and URLs are protected. Sample, legacy Rose and the existing cushion-cover
  pair are excluded; 500 g is reserved for confirmed six-piece cover packs.
- Verification: 25 isolated assertions, latest-five moderation checks and mocked
  courier success/failure cases passed. Desktop/mobile layout checks cover hero,
  addresses, cart, FAQ and corrected search. No real orders/payments/testimonials
  created by testing. QA fixtures remain outside the theme.
- Rollback: restore v1.8.9 theme; retain real orders/customer testimonials. If
  reverting catalogue weights/folders, restore only those fields from private
  loomara_catalogue_190_snapshot, never an old database or stock snapshot over
  subsequent customer transactions. Existing OD references remain immutable.

## v1.8.9 — Restore native Shiprocket PIN checking

- Owner reports correcting Shiprocket integration and requests its actual
  serviceability checker again. Theme no longer removes the native product-page
  callback. Format-only UI/script is suppressed when Shiprocket is installed;
  the clearly labelled fallback remains for plugin-absent local previews only.
- Credentials, merchant settings, product weights, shipping methods, products,
  orders, payments and v1.8.8 order improvements remain unchanged.
- Native serviceability result is supplied by Shiprocket, not inferred from PIN
  format. Live API acceptance is verified separately after deployment.
- Rollback: restore v1.8.8 to return to the explicit format-only fallback. Do not
  roll back the database or overwrite owner-updated Shiprocket settings.

## v1.8.8 — Orders and India PIN feedback

- New native WooCommerce orders receive a persisted LM-YYYYMM-ID reference,
  with a six-digit-minimum database ID suffix. No sequence-reset races, order
  ID replacement or historical order-number rewriting. Existing references stay.
- Customer-only orders default to Processing/Completed (plus registered
  Shipped/Delivered statuses). A status selector exposes pending, failed and
  other registered statuses. Native view/pay/cancel actions and pagination remain.
- Responsive order cards show the actual current status, date, items and total.
  Completed is not falsely renamed Delivered; no fabricated shipment events.
- Owner approved replacing the broken Shiprocket product-page checker with
  local six-digit India PIN-format validation. Live checker was missing merchant
  integration ID and product weight and mapped every API error to unserviceable.
  Replacement explicitly does not verify courier coverage or postal existence;
  free India shipping remains the merchant policy. Shipping/payment settings,
  Shiprocket account and product weights are not modified or invented.
- Twelve local assertions passed for uniqueness/immutability, historic numbers,
  status filters, ownership and PIN validation. Desktop/mobile browser checks
  passed; 560001 accepted as format, 000000 rejected. No live orders/emails made.
- Rollback: restore v1.8.7 theme. Persisted new order references remain metadata;
  do not delete orders or restore an old database over subsequent transactions.

## v1.8.7 — Cart and checkout accounts

- Replace the purchase button with View cart for items already in the cart;
  colour-card and selected product-variation state remain distinct.
- Optional checkout accounts require no password entry. Native secure password
  setup email is deferred until an accepted order (processing, completed or
  on-hold), with duplicate notification guards. Failed/pending orders do not send.
- Link matching guest orders after authenticated password or Google sign-in,
  using WooCommerce's native customer-order association; never overwrite an
  order belonging to another customer or trust a checkout email as authentication.
- Hide visible wishlist counters; show readable like counts on hover/focus.
- Highlight FAQ Contact us as a touch-friendly button.
- Thirteen isolated local assertions passed with all email intercepted, including
  preservation of the sent setup key across delayed native notifications. Mobile
  390px checkout has no password field/overflow; simple-product cart replacement
  and variable-colour switching tested. Live deployment does not place orders
  or send verification emails; inbox receipt remains a customer acceptance check.
- Rollback: revert this theme update; no inventory/schema migration required.

## v1.8.6 — Subcategories and identity-preserving catalogue update

- Local inventory now uses LM-{CATEGORY}-{SUBCATEGORY}-{DESIGN:5} and
  LM-{CATEGORY}-{SUBCATEGORY}-{COLOR}{DESIGN:5}-{SIZE}; same design numbers,
  old-ID aliases, confirmed sizes and original photographs are preserved.
- Visual-style codes: FL Floral, PL Plain, PT Patterned, GE Geometric,
  CT Coordinated. No unsupported block-print/weaving-process claims.
- Nine design folders and twenty-one variant folders reorganised locally;
  all twenty-seven source-image hashes are unchanged.
- Owner-authorized material defaults: Cotton for missing bedding materials;
  Mixed for cushion covers. Existing explicit material details take priority.
- Explicit administrator-only, nonce-protected migration preflights existing
  WooCommerce parent/variation identities, colour/size agreement, extra variations
  and SKU collisions before any write. No automatic frontend migration.
- A private snapshot is saved in loomara_catalogue_186_snapshot; protected prices,
  stock, images, sizes, descriptions, tax data and slugs are verified unchanged.
  Existing explicit materials, other categories and old SKU aliases are retained.
- Only the mapped nine parents/twenty-one children are updated. The separate
  Sample #120 and legacy Rose #14 records are excluded. No product recreation,
  media upload, order/customer change or payment-setting change is involved.
- Search includes former SKU aliases, so old product codes remain discoverable.
- Thirteen local migration assertions passed: wrong identity/collision rejection,
  all thirty identity-preserving SKU updates, material/category assignment, old
  aliases, protected data, unrelated-product preservation, snapshot and safe repeat.

Rollback: restore v1.8.5 theme code in a new release. For catalogue metadata,
an administrator must use the private snapshot to restore only SKUs, affected
attributes/categories and migration metadata; never restore saved stock/prices
or a whole database over newer orders. Theme rollback alone does not reverse
the catalogue migration. Newly created empty style terms may safely remain.

## v1.8.5 — Storefront, wishlist and account experience

- Price-range, material and style filters with responsive sidebar/drawer;
  preserve filters in collection tabs, and Sale when switching categories.
- Larger, less panoramic desktop category cards; aligned right-indented category
  intro, top-left hero logo on mobile/desktop, consistent filter/policy fonts.
- Rounded cart notices, removal of newsletter signup controls, compact Contact
  cards (email/call/WhatsApp/Instagram), numbered compact FAQs with Contact link,
  corrected FAQ size guidance and bounded typo-tolerant product search.
- AJAX wishlist actions retain the current page and show feedback with a wishlist
  link; image-corner hearts show aggregate saved-user counts, refreshed outside cache.
- Homepage star interactions and a rating dialog only for completed paid purchases
  with unrefunded quantity. Server validation, CSRF protection, duplicate protection
  and moderation apply; no fake or automatically published ratings.
- Profile-first dashboard; selected avatar hidden behind Change avatar; default
  address action; optional distinct saved/manual billing address, optional saving
  after order creation, India/mobile validation and unchanged shipping destination.
- Branded in-page logout confirmation and reduced-motion-aware styling for the
  existing in-document payment-processing message. No gateway logic is intercepted.
- Validation: all PHP parsed, JS syntax and whitespace checks passed; fourteen
  isolated local assertions passed. Browser checks cover desktop/mobile filters,
  category/intro/hero geometry, no overflow, wishlist without navigation, collapsed
  avatar, default-address switching, separate billing picker and a moderated rating.
- Owner authorized theme/main/release publication on 18 October 2026, keeping
  the v1.8.6 inventory migration local. No product migration, order, email or
  payment is submitted by deployment. The
  payment-provider-specific processing screen still needs verification after an
  authorized deployment; third-party hosted gateway pages are outside theme styling.

Rollback: restore the v1.8.4 theme tree in a new release. Wishlist, address and
review submissions must be preserved, not deleted. No database schema migration.

## v1.8.4 — Consolidated release documentation

- Consolidate every separate release-note file into RELEASE.md without dropping its notes.
- Remove the eleven superseded RELEASE-version.md files.
- Keep future release notes in this document. Existing GitHub tags/releases remain unchanged.
- Documentation only; v1.8.3 storefront code and store data are unchanged.

Rollback: restore the release documents from v1.8.3; no database rollback needed.

## v1.8.3 — Responsive category shopping and filters

- Larger desktop homepage category destinations in a two-by-two grid; mobile category layout retained.
- Desktop catalogue filters in a left sidebar; mobile floating Filters & sort button opens an accessible modal drawer.
- Preserve category/size filtering, sorting and infinite-scroll/manual fallback.
- Mobile product colour and size options appear directly below the price, ahead of the short description.
- Display-only release; no changes to product records, stock, orders, customers or payments. QA fixtures remain local.

Rollback: revert this release commit on main and publish a matching patch release, restoring v1.8.2 theme code. No database rollback is required.

---

## v1.8.2 — Shared Shop/category browsing

- One compact colour-level grid for Shop, categories and product search.
- Category links (Bedsheets, Comforters, Combo, Cushion Covers) inside the
  catalogue heading, with active-category indication and mobile-friendly layout.
- Server-side category/size filters and deterministic sorting: recommended,
  newest, price ascending/descending, best selling and highest rated.
- Filter individual size variants before grouping colours. Product links open
  the matching selected colour and size; stock/catalogue visibility stays native.
- Infinite scroll appends eight cards at a time, retaining all active filters
  and sort. Accessible load-more, pause-auto-load, retry and normal-link fallback
  remain available. No numbered pagination; no duplicate cards.
- Fresh versioned CSS avoids stale combined-asset URLs; bounded images/icons,
  four desktop columns / two mobile columns, aligned titles/prices/actions.

Local validation: PHP/JS syntax, size-filtered variation selection, variable
  price sorting, 16-card scroll completion with no duplicates, 390px/1366px
  responsive checks and zero overflow. Live verification follows deployment.

No products, price/stock records, orders, customers or payment settings change.
QA fixtures/importers remain local and are excluded from deployment.

Rollback: restore the v1.8.1 theme tree (e1c95826e449e642edc091ec2e95c8d24c02dc74)
in a new main commit, publish a matching patch release and purge generated caches.
The WooCommerce product-route conflict repair remains included.

---

## v1.8.1 — WooCommerce product URL conflict

Live diagnostics identified Hostinger Ecommerce (Beta) 1.0.7 registering
`^product/([^/]+)/?$` as `hostinger_product_id`, ahead of WooCommerce's route.
Its separate catalogue does not contain WooCommerce products and forces a 404.

Replace only that conflicting generated rule with WooCommerce's native
`product` query variable, leaving public URLs, plugin activation/settings,
products, prices, stock, orders, customers and payment configuration unchanged.
Regenerate rules once. v1.8.0 responsive catalogue fixes remain included.

Rollback: restore v1.7.9 in a new main commit and refresh permalink rules and
generated caches. Reverting just to v1.8.0 restores the plugin routing conflict.

---

## v1.8.0 — Product routing and responsive catalogue

- Rebuild generated WordPress product permalink rules once, after WooCommerce registers its routes. This is a soft flush; no product, order, customer or .htaccess changes.
- Use the same colour-level catalogue on Shop and category pages, excluding migrated canonical duplicate records.
- Bound product cards and thumbnails to a four-column desktop / two-column mobile grid, with aligned titles and actions.
- Show four compact home category cards on desktop and fix mobile descriptions occupying the hidden number column.
- Provide an explicit not-found page instead of misleading homepage-like fallback content.

Validation: PHP syntax parsing and whitespace checks; responsive browser and live product-link checks during deployment.

Rollback: restore the theme tree at v1.7.9 (af075cece9d4493df5734ecee2b7f581d81e6cbe) in a new main commit, publish a matching patch release, and clear generated caches. The regenerated permalink rules are safe to keep. No inventory or customer data migration is included.

---

## v1.7.9 — Remaining reviewed website updates

- Adds the FAQ footer link and an editable WordPress FAQ page with seven questions. Creates the page only if /faqs/ does not exist; existing page content is never overwritten. The native page can appear in the WordPress sitemap.
- Adds an Instagram icon and @loomara.living under Say hello in the footer.
- Publishes the remaining launch presentation assets with a strict localhost-only testimonial preview. No placeholder testimonials or QA fixtures appear on production.
- Uses a release-named refinement stylesheet to avoid stale optimized hero-logo CSS, retaining the mobile logo placement and desktop refinements.
- Keeps the responsive offer banner, signup OTP, Google login, account profile, checkout rules, product data and payments unchanged.
- Excludes unused large image copies, backups, credentials and all local QA files.

Validation: all 27 PHP files parsed successfully; launch JavaScript syntax and
diff whitespace checks passed. Local FAQ returned HTTP 200, seven expandable
questions, correct title, no noindex tag and no horizontal overflow at 390px.
Instagram placement verified under Say hello. Hero placement was previously
checked at 390px and 1366px; live release checks are performed after publishing.

Rollback: restore v1.7.8 (f3c2a3c7bb85fa7e12fb61a35e746a084a9f4bfa)
using a new revert commit on main. The editable FAQ page remains in WordPress;
customer accounts, orders and inventory are not deleted.

---

## v1.7.8 — Unclipped hero logo

- Moves the hero logo slightly left and down on desktop.
- Gives mobile its own vertical offset so the logo stays inside the artwork rather than being cropped above it.
- Keeps the header text wordmark and artwork caption unchanged.
- Local bounds checks passed at 390px mobile and 1366px desktop; mobile logo has 20px top clearance.
- Other pending local homepage/footer/FAQ/testimonial changes are excluded.

Rollback: restore v1.7.7 (f4acc4d8225f35934fea6e06a66a00066204b0c8)
with a new revert commit on main. No customer, inventory or order data changes.

---

## v1.7.7 — Responsive launch offer

- Publishes the launch offer strip on desktop and mobile, with WELCOME10 and dispatch within 24 hours.
- Uses a reserved 44px strip, seamless text scrolling, pause control and static wrapping for reduced-motion preferences.
- Supports a 10% first-order WELCOME10 virtual fallback if no merchant-managed coupon exists; existing coupons are not overwritten.
- Requires sign-in and checks customer ID and billing/account email history; previous non-cancelled/non-failed orders prevent reuse.
- Aligns shipping policy, product text and structured-data dispatch handling to the owner-confirmed 24-hour promise; delivery remains 5–10 business days after dispatch.
- Other pending homepage/footer/FAQ changes and placeholder testimonials stay local.

Local checks passed at 1366px and 390px, including full-width visibility and pause.
Ten offer/schema integration assertions passed using local fixtures only.
No live test orders or payments were submitted.

Rollback: restore v1.7.6 (0b3399f9752df45753d15e23b240cc03cfc7faca)
with a new revert commit on main. Existing merchant-managed coupon records are unchanged.

---

## v1.7.6 — Signup and customer profile

- Mobile is optional at signup and may be shared across accounts; email remains unique.
- Checkout still requires a valid Indian mobile number.
- Shared numbers do not select an account for mobile login; use email or username.
- Signup errors are displayed without replacing the form, retaining entered passwords in the current page only, never browser storage.
- Adds show/hide password controls, reusing native WooCommerce controls where present.
- Dashboard displays customer name, mobile and registered email.
- Adds ten selectable local vector avatars (five male, five female) and an initials fallback, saved privately per customer.
- Email OTP, Google login, orders, inventory and payment settings remain unchanged.
- Unrelated pending local launch changes and all QA fixtures are excluded.

Validation: ten local PHP integration assertions passed; browser checks covered
OTP-error feedback, password visibility, dashboard data and avatar save/reload.

Rollback: restore v1.7.5 (caf248f1e1cd41ad2763ca778bc33025c3380fb5)
using a new revert commit on main. Existing customer and avatar metadata remain.

---

## v1.7.5 — Email OTP for normal signup

- Adds six-digit email verification to normal My Account registration.
- No account is created until server-side code validation succeeds.
- Codes expire after 10 minutes and are single-use, with five attempts.
- Resend cooldown of 60 seconds; five email requests and twenty IP requests per hour.
- Stores keyed digests, binds challenges to the submitted email, and serializes rate counters and consumption.
- Sends through the existing WordPress/SMTP mail path, without credential changes.
- Google login, existing customers, password recovery and checkout account creation are unchanged.
- Other local launch changes and all QA fixtures are excluded.

Seventeen local tests passed with outgoing mail suppressed. Live email receipt
must be confirmed by the recipient; mail acceptance alone is not delivery proof.

Rollback: restore v1.7.4 (638120b7e7779962d4a244182169ce1991f6c9d1)
using a new revert commit on main. Existing accounts, stock and orders remain.

---

## v1.7.4 — Account mobile number

- Adds Mobile number to My Account → Account details using the WooCommerce billing phone.
- Saves the contact in the account and synchronizes the existing mobile-login lookup.
- Validates Indian mobile numbers, with optional +91 prefix.
- Requires a valid mobile contact at classic and block checkout, including Google-created accounts.
- A saved mobile contact is reused by existing checkout prefill; customers can edit it.
- No OTP verification is claimed. No Google or SMTP credentials are changed.
- Other pending local launch changes remain excluded.

Rollback: restore v1.7.3 with a new revert commit on main. Saved phone values remain in WooCommerce.

---

## v1.7.3 — Google customer login

- Adds the enabled Nextend Google provider to WooCommerce login and registration.
- Uses the provider's branded Google icon and full-width Continue with Google button.
- Returns customers to My Account after authentication.
- Keeps OAuth credentials in WordPress plugin settings, outside Git.
- Existing password login, SMTP, stock, orders and payment configuration unchanged.
- Other local launch changes are not part of this release.

Requires Nextend Social Login with its Google provider configured and enabled.
Rollback: restore the v1.7.2 theme commit on main using a new revert commit.
Disable the Google provider in Nextend to stop social login without removing accounts.

---

## Earlier release history (previous RELEASE.md)

## v1.7.2 — confirmed merchant terms and product schema

- Owner confirmed Loomara Living brand, free India shipping, dispatch in 1–2
  business days, delivery in 5–10 business days after dispatch and free eligible
  returns within 7 days of receipt (17 October 2026).
- Extend native WooCommerce Product/Offer schema rather than emitting duplicates.
- Match visible product/policy copy to confirmed terms. Preserve genuine native
  reviews; never invent GTINs, ratings or offer dates. Dates only from a real sale.
- India-only free shipping with no minimum order is configured separately in
  WooCommerce under explicit owner approval. No payment settings changed.
- Code-only rollback: redeploy v1.7.1 (f6e01a54411ad7978c01d5b5426066e2c8b2480e).
  Shipping-zone configuration is separate from Git and remains in place on rollback.
- Policy copy is updated at render time; the existing editor content and historical
  revisions are retained. No product, customer or order data migration is required.

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
