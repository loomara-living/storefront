# Loomara Living — Release history

All release details are maintained in this single file, newest first. Historical notes describe the behaviour and policies at the time of each release.

## v6.2.3 — QC corrections

- Bind Ready to Ship to the existing readiness email and verified-tracking worker, preserving legacy settings IDs and once-only markers; no historical replay or additional pickup-stage email enrollment.
- Preserve filter context/pagination in canonical URLs while dropping empty form fields/defaults; noindex category/subcategory filter-only Shop views as well as other existing filters.
- Restore Out for Pickup and Picked Up options in customer order filtering; keep unfinished payment attempts and retired Pickup Scheduled hidden from choices.
- Correct singular/plural wishlist likes wording.
- Owner explicitly retains Sample publicly; supplied dimensions are confirmed parcel sizes, not finished fabric measurements. No product data/dimension changes included.

## v6.2.2 — Mobile introduction placement

- Move the existing mobile introduction and its actions to the end of the homepage, immediately before the bedroom image. Preserve desktop placement, single-copy markup and focus protection.

## v6.2.1 — Bedroom artwork and desktop filters

- Replace the illustrated homepage bed with the supplied Rest, considered bedroom image, preserving its natural proportions, existing copy and responsive placement.
- Refine desktop catalogue dropdowns with cream/navy styling, rounded borders, accessible focus states and reduced-motion support. Keep native menus and mobile filtering unchanged.

## v6.2.0 — Search indexing and homepage introduction

- On mobile, place the existing homepage introduction below the offer slider and before categories; keep artwork at the bottom and restore original desktop placement. No duplicated text or artwork.
- Filter rendered term sitemap entries only: omit Uncategorized and invalid 1970 dates, keeping contained products and genuine modification dates intact.

- Preserve colour pagination canonicals when explicit unfiltered collection/sort defaults are present; retain staff-custom canonical destinations and filtered-query guards.
- Redirect the exact retired About route to the published Our story page for GET/HEAD only. Keep unavailable destinations, POST requests and unrelated paths untouched.
- Noindex filter-only catalogue views, feeds, author archives and the Uncategorized archive without changing product indexing, catalogue queries, customer actions or robots.txt. Preserve other robots directives.
- Empty results offer a clear link back to the unfiltered collection, without silently replacing selected filters or displaying unrelated products.

## v6.1.2 — Overlay navigation and collection headings

- Place offer arrows on the image edges and testimonial arrows beside the quote, removing separate control-row space. Keep 44px targets, link/swipe access and keyboard pause.
- Shop and Sale use one “The Loomara collection” heading instead of a repeated Shop title. Category and search headings remain unchanged.

## v6.1.1 — Cleaner carousel controls

- Hide numeric slide counters and visible Pause buttons on homepage offers and testimonials. Retain pause on keyboard focus for accessible motion control, and preserve arrows, swipe, rotation and reduced-motion handling.

## v6.1.0 — Homepage discovery, sizing and order layout

- Rotate Diwali, WELCOME20 and collection artwork every three seconds with pause, swipe, keyboard and reduced-motion support.
- Hide Top rated until genuine qualifying product ratings exist.
- Add an approximate bedding size reference beside size choices, with confirmed 16-inch cushion-cover sizing.
- Full-width desktop order cards; keep mobile layout unchanged.
- Prevent hidden native product-option selectors from causing horizontal overflow.
- Preserve the deployed shipping adapter and existing order/payment/refund safeguards. No historical replay or settings changes.

## v5.3.3 — Shipping API label compatibility

- Normalize the eight configured shipping labels to existing WooCommerce status keys at modern single-order REST update and legacy order-edit boundaries. Accept equivalent casing and whitespace/hyphen/underscore separators.
- Preserve native authentication/permissions and existing validation, status/email/refund hooks. No direct order writes; unknown/unmapped, payment, Processing, return and refund values remain unchanged.
- 316 local PHP boundary/unit checks passed. No live order mutation or historical replay was used for verification. This adapter does not recover a status push that never arrives.
- All unrelated pending local changes remain excluded.

## v5.3.2 — Distinct shipping status labels

- Match the configured Shiprocket stages: Ready to Ship, Out for Pickup, Picked Up, Shipped, In Transit, Out for Delivery and Delivered. Cancelled and all non-shipping statuses retain their existing behaviour.
- Remove the previous Ready to ship display/filter consolidation. Keep the distinct mapped stages available in the admin editor.
- Retire unselected Pickup Scheduled from new admin choices, while preserving its registered key and selected historical values. No order migrations, historical status replay or email-trigger changes.
- This release excludes all other pending local work, including homepage offers, size guide, order-card width and the separate variation-layout fix.

## v5.3.0 — Category motion and product shopping controls

- Replace colour/size dropdown presentation with accessible labelled option buttons linked to the native WooCommerce selectors, preserving exact labels and disabled options. Navy selected states and subtle hover/tap motion, with reduced-motion support and native dropdown fallback without JavaScript.
- Product quantity +/− controls show the selected variant's current cart units. Existing cart lines update/remove through WooCommerce Store API using its fresh nonce and native validation; no separate Update cart action. Main CTA is View cart for a positive quantity and Add to cart after removal. Before first add, controls choose the quantity to submit natively. Do not automatically repeat uncertain writes.
- Apply the lightweight native notice presentation outside WooCommerce wrappers too, removing the competing gold/orange border/background while keeping message roles, error cues and the accessible View cart action.

- Give homepage category artwork a gentle eight-second light shift while visible, plus a small desktop hover lift. No image zoom/crop or duplicate text. Pause off-screen/hidden-tab motion and disable animation for reduced-motion preferences; category navigation remains unchanged.

- Replace the square Shop/category navigation boxes with rounded cream pills, subtle gold borders, a clear navy selected state and gentle desktop hover lift/tap feedback. Preserve native category links and sale/filter behaviour. Keep mobile two-column layout with 46px minimum targets and disable animation for reduced-motion preferences.

## v5.2.1 — Restore simple footer and mobile homepage layout

- Restore only the original Explore, Say hello and Policies footer links, with a subdued photographic background. Remove the new footer introduction, guide/category links and separate image panel; preserve published guide pages without deleting them. Compact two-column mobile links, full-width contact details and readable touch targets.
- Contact channel images are the clickable email/phone/WhatsApp/Instagram links; remove separate visible contact captions while retaining accessible destination labels.
- On mobile, move the one existing homepage hero to the end of homepage content, immediately above the footer. Keep desktop placement unchanged, avoid duplicate headings and follow visual placement in keyboard order.
- Mobile category artwork uses full-width, uncropped rectangular images, with no square card border/background or forced square image ratio.

## v5.2.0 — Storefront imagery, navigation and product presentation

- Cart estimates reuse the installed Shiprocket plugin's public integration identifier instead of duplicating a hardcoded header. Missing plugin configuration returns an honest unavailable message; private account credentials and provider settings stay unchanged.

- Replace the Contact page’s old channel boxes with the four supplied contact images, uncropped and responsive. Real email/telephone/WhatsApp/Instagram links and contact details sit with each image; add secure account-order and FAQ help links. Existing introductory/safety copy preserved; no page-record changes or messages sent.
- Add the supplied Shop and Sale artwork at the top of their respective Shop collection views, before navigation/heading/filters/products. Sale is selected through the existing collection=sale parser. Proportional, uncropped responsive images with intrinsic dimensions; category, search and product pages unchanged. No pricing or sale-filter changes.
- Replace the prior footer (not append) with image-inspired Shop, About, Customer Support and Policies navigation. Use the supplied artwork’s photographic right edge, real accessible responsive links, confirmed Instagram/email/phone, current year and existing free India shipping wording. Reuse the existing returns/shipping policy pages rather than publish duplicates; Track Your Order leads to the secure account Orders page. Prepare only missing Our Materials, Care Guide and Size Guide pages through a protected explicit administrator action; preserve existing published pages and stop for review of unpublished collisions. No invented textile dimensions, certifications or social accounts.
- Restyle shared WooCommerce success/info/error and Cart/Checkout block notices as lightweight icon-and-text confirmations instead of heavy boxes. Add-to-cart View cart becomes a clear underlined action with a 44px target. Preserve native messages, roles, live announcements, links and visible error/warning cues; no auto-dismiss or validation changes.
- Replace the four Layers for living category cards with the supplied Images/Home-Page artwork. Remove HTML text/arrow overlays; retain accessible category labels and corresponding category links. Preserve the full artwork without cropping at desktop/mobile widths, with optimized lazy-loaded JPEGs and intrinsic dimensions.
- Place the owner-supplied Ads/WELCOME20 promotion at the top of homepage content, below the existing header and above the unchanged original hero. Full-width proportional image without cropping, accessible Shop link, explicit dimensions and high loading priority. Original PNG retained; optimized JPEG used on the website.
- Make product Add to cart and View cart buttons full-width, at least 58px tall, with readable 16px text and rounded corners overriding the generic WooCommerce small-font/square-corner rules. Keep quantity on its own row and preserve hidden/disabled native states. Align mobile price note/coupon immediately after price despite existing flex ordering.
- Restyle the top-of-cart delivery estimate as a compact, softly bordered rectangular card with delivery icon, heading and Change PIN control. Expand an accessible PIN editor and recheck the current cart without changing saved addresses. Keep invalid/failed checks editable and never invent delivery dates.
- Use “Price inclusive of taxes” beneath the price and put the coupon card immediately after it, before the short description/options. Remove the WELCOME20 sign-in sentence from that card only. Preserve first-order enforcement, stored coupon descriptions, tax settings and checkout calculations.

## v5.1.1 — Product description heading and delivery presentation

- Place cart delivery at the top, above cart items, for both classic and block carts. Keep add-ons below the cart and avoid duplicating the delivery checker.
- Present cart delivery as a quiet inline delivery-icon/text row, without a background, enclosing border or box shadow. Remove the box around the missing-default-PIN fallback checker too. Saved-PIN lookup, courier estimate and error behaviour unchanged.
- Restore the visible Description heading inside the description panel. Place the coupon card directly before the delivery-estimate panel, after product options/cart controls. Coupon eligibility, pricing and delivery requests unchanged.

## v5.1.0 — WELCOME20 and catalogue controls

- Change the first-order welcome offer to WELCOME20 at 20%, including the announcement banner, FAQ, product offer and Magic Checkout public-code list. Explicit snapshot-protected administrator update renames the saved coupon while retaining restrictions and usage history; historical orders and other coupons remain unchanged.
- Restore the pre-v4.3.3 coupon presentation and behaviour, retaining “A little more comfort”, the offer description and Copy button. Remove only the sentence “Eligibility and final discount are checked at checkout.” No coupon-record or checkout changes.
- Remove collection search on Shop and product-category pages at every screen size. Desktop uses one top Sort by dropdown, with sidebar sorting hidden. Mobile hides that top dropdown and keeps Sort by inside the filter drawer. Preserve selected sorting when applying filters; header search stays unchanged.

## v4.3.3 — Product offers, catalogue controls and cart presentation

- Keep the Description tab label but remove the repeated native Description heading inside its panel. Description content and Product details remain unchanged.
- Remove 01–04 from homepage category cards and reclaim the number column. Shop and category pages share one desktop top sorter with collection search/sidebar sorting hidden; mobile retains collection search and filter-drawer sorting. Refresh the shared stylesheet cache version.
- Remove the newly added footer logo and adjacent tagline. Preserve the original footer links, contact details and copyright row, plus product-photo rotation and soft animations. Local only.
- Remove the cart’s “A little more to look forward to” coupon notice and its dedicated stylesheet enqueue from classic and block carts. Checkout coupon availability and all payment/reminder/order logic unchanged.
- Replace the cart’s delivery section with one compact automatic estimated-date/default-PIN line when a valid default address PIN exists. Show a manual PIN checker in that position only when the default saved PIN is missing/invalid (including guests). Use actual courier dates, with an honest unavailable fallback if no date is returned. Product-page checker unchanged.
- Keep “A little more comfort” and show only the eligible public coupon with the greatest monetary saving on this item, considering selected variant, quantity and configured cap. Retain Copy and native keyboard/touch-accessible “i” terms control; remove the requested checkout disclaimer sentence. Guests discover WELCOME10 only; signed-in users use native coupon validation against the viewed item/current cart with account/first-order rules. Same deliberately public codes as Magic Checkout, no private coupon enumeration or coupon/data writes. Selection/quantity re-ranking; real limits/expiry/caps in terms.
- Product details Colour now follows the exact selected variant option label (including taxonomy/custom Colour/Color attributes); clear selection restores the original colour list. Works with native and custom galleries; no product metadata, pricing or cart changes.
- Revalidate native WooCommerce Cart Blocks data once after page load, browser-history restoration and returning to the cart tab. Avoid relying solely on persisted client cart data when it disagrees with the server cart. Read-only native Store API resolution, coalesced/throttled, no cart writes, storage clearing, optimisation-setting changes or payment invocation. Guest live mismatch observed; isolated fresh guest and signed-in add/cart tests retain the exact variant. Local safeguard pending deployment and affected-session acceptance.

## v4.3.2 — Footer branding and gentle catalogue motion

- Add a compact existing Loomara logo beside the tagline “Wrap your world in comfort” in the footer. Rotate up to four genuine gallery photographs on visible homepage/shop/category product cards every five seconds, using colour-specific galleries for colour listings. Single-image cards remain static. Pause controls, hover/focus/tab visibility and reduced-motion support; appended catalogue cards are supported. No variation, price, stock, order or payment changes.
- Subtle short arrival and hover treatments with reduced-motion fallback. No product, stock, order, payment, email or scheduled-job changes.

## v4.3.1 — Restore original abstract hero and stylesheet cache version

- Owner requested removal of the lifestyle photo from the homepage. Restore the original v4.2.0 abstract bed artwork and remove photo-only styles, preserving copy, logo, buttons and all v4.3.0 product/restock changes. Original photo asset retained unused; no file deletion.

- Live v4.3.0 verification found the main stylesheet still advertised fixed version 1.8.0. Existing browsers could reuse the older sheet without the new photo positioning, rendering the hero image at intrinsic 1280px height. Use the theme release version in its stylesheet URL so browsers fetch the reviewed styling after each release. No layout redesign, product/order/payment change or data migration; v4.3.0 tag remains immutable.

## v4.3.0 — Homepage lifestyle image, product layout and restock customer details

- Replaces the abstract homepage hero illustration with the owner-provided sunlit reading-corner photograph. The theme asset uses explicit dimensions, useful alternative text, high-priority loading and responsive cover crops; the existing Loomara logo and “Rest, considered.” overlay remain. No Media Library write.

- Product-detail visual refresh inspired by owner references: real ratings below title, stronger native prices with real sale percentages, one known public copyable coupon (expiry/restriction checks and explicit checkout eligibility), prominent native purchase button, compact in-stock dispatch/comfort panel, and standalone genuine Customer reviews section after Description/Product details. Verified paid-purchase moderation/duplicate safeguards remain; no fake review/discount/stock or payment changes. Local preview uses labelled sample prices only; live product prices remain authoritative.

- Restock requests already retain user IDs. Add administrator-only customer detail view with current account email, notification opt-in and the exact product/variant purchase link, paginated 50 customers per page.
- Optional explicit back-in-stock email consent for new/repeated requests; historical requests remain unconsented unless the customer opts in later. Do not copy account emails into catalogue metadata. No automatic customer emails or historical campaigns; no stock, weight, product, order or payment record migration.
- 217 mocked regressions passed; synthetic desktop/390px component layout checked, not a full native WooCommerce lifecycle test. Roll back theme to v4.2.0 for prior layout while retaining all private restock requests/consent and existing shipping-weight snapshot. No historical jobs, customer emails or financial tests replayed.

## v4.2.0 — Product details, shipping weights and restock requests

- Hide numeric stock quantities in native customer availability messages; out-of-stock badge/faded product imagery and variant-aware Request restock control. Colour cards use the selected variant stock status. Signed-in customers only, nonce/server stock checks, permanent per-customer/SKU deduplication; private demand counts and team email transport result in Loomara Settings. No stock mutations or automatic customer back-in-stock promises.

- Owner-confirmed shipping weights: bedsheets 1.2 kg, comforters 1.2 kg, combos 2 kg. Existing cushion-cover pair is a set of 2 at 0.3 kg; set-of-4 rule is 0.5 kg (no new pack/variant created). Explicit administrator-only, nonce-protected one-time update saves a private snapshot and preserves prices/stock and other protected fields. Sample/unknown SKUs and unconfirmed cover packs are excluded; no legacy migration replay.

- Move native Additional information into Description, retaining product attributes, saved parcel dimensions and shipping weight without changing product records.
- Default care instruction to Machine washable when absent. Default thread count to 186 for bedsheets and NA for comforters only; retain existing specifications and do not infer thread counts for combos/cushion covers.
- Parcel dimensions remain explicitly labelled, not presented as bedding measurements. Existing product records retain their descriptions and attributes. Rollback theme to v4.1.2 for the prior tab/stock presentation; retain private demand records and migration snapshot. Do not rerun completed weight updates or automatically overwrite stock when restoring weights.

## v4.1.2 — WooCommerce variant schema output compatibility

- Live v4.1.1 verification identified WooCommerce's page-type whitelist filtering out the generated ProductGroup node. Adds productgroup to the native output types on product pages only, keeping one graph with real variants. Order-email and other-page type selections remain unchanged.
- Four native-output selection/duplicate/context regression checks added; variant schema tests now 26, with 537 total mocked checks. The v4.1.1 tag remains immutable. Rollback to v4.1.0 for the prior native Product schema, or retain this output-type filter alongside the variant generator.

## v4.1.1 — Technical SEO and category metadata

- Fixes the reproduced live catalogue canonical mismatch: unfiltered colour_page=2 and later Shop/product-category slices retain their own page URL, while colour_page=1 resolves to the collection base. Existing crawlable Load more anchors and infinite scrolling remain intact. Filter/search/sort combinations are left to the SEO plugin. No visible layout or link changes.
- Extends the existing WooCommerce Product graph into one ProductGroup for suitable variable products, with actual published visible colour/size variants, SKUs, selectable URLs, images, tax-display prices, currency and availability. Existing brand, genuine ratings and shipping/return terms remain. No fabricated GTINs/reviews or second Product graph; simple products, incomplete variants and groups over 100 children retain native output. Read-only, no product-data writes.
- Adds deduplicated Google Font preconnections only when the existing font stylesheet is queued and prioritises the existing hero logo download. Fonts, images, dimensions and design remain the same. No measured performance-score claim.
- Adds the public product-only RSS endpoint /?loomara_product_feed=1 for Merchant Center scheduled fetch. It exports published visible products/variants with actual SKU, colour/size, image, description, price/currency and stock, batched across the full catalogue. Missing images/descriptions and explicit _loomara_google_exclude metadata are excluded; historical canonical aliases are skipped. Fifteen-minute generated cache, anonymous GET/HEAD only, noindex response header, no customer/order data, new WordPress jobs or provider credentials. Daily Google fetch connection and retirement of the initial one-time import remain separate Merchant Center rollout steps.
- Dedicated checks: category metadata/resource hints 65, catalogue canonical 23, variant schema 22, prior search indexing 30 and merchant feed 19. Including catalogue, testimonial, reminder, checkout, payment/refund safety, stock and sharing regressions, 533 mocked checks pass. Google Rich Results live acceptance must be checked after deployment.

- Adds distinct India-focused SEO title/description defaults for the four main product categories through documented AIOSEO filters. No visible content, headings, layout, URL, canonical, schema, indexing-policy or product/order changes. No cotton grades, certifications, dimensions or pack counts invented.
- Only unfiltered first-page root categories qualify; subcategories, search, sorting/filter/pagination URLs, other taxonomies, admin and previews retain their existing metadata. Independently customised titles/descriptions win. No markup, persistent data writes, jobs or additional network requests. No deployment yet.
- The HTML title also changes browser-tab text; storefront page content and styling remain identical. Rollback: first pause any configured Merchant feed fetch, then restore functions.php, front-page.php, inc/search-indexing.php, version and RELEASE.md to v4.1.0; the three unused new helpers may remain on disk. No AIOSEO database-setting rollback is involved. Merchant Center onboarding is separate from the code release.

## v4.1.0 — Collection discovery and testimonial cities

- Testimonial forms suggest the selected default saved-address city, falling back to native shipping city only when no saved default is available. Public city remains editable and opt-in, displayed as Name - City; existing testimonials are not backfilled from private addresses.
- Restyles subcategory links into a labelled Shop by style segmented row, with an All [category] reset and clear current selection. Mobile row scrolls within its container instead of spreading tall pills across the page. Style links retain sale/collection, size/material/price/sorting context but reset pagination and prior subcategory. No category/product data changes.

- Desktop replaces collection search with a compact Sort by dropdown and hides sorting in the left filter pane. Mobile retains Search collection and sorting in its existing filter drawer. Top ordering uses the same six choices as the filter panel, retains collection/category/size/material/price/search context and resets pagination. Native GET Apply fallback works without JavaScript; enhanced selection submits automatically. Header search anchor points to its own panel.
- Both search inputs show matching published catalogue cards while typing (two characters, 300ms debounce), reusing the real search page and its existing fuzzy/SKU/category matching. More matches load through native pagination, with a View all matching products link; no first-result redirect, new API or provider calls. Safe DOM-built title/price/image links, request abort/stale-result protection, empty/network fallback and scrollable result list.
- 176 local mocked checks pass across search, sorting, subcategory links, testimonials, SEO and reminder regressions. No product/order/payment/settings changes, private-city backfill or historical job replay. MaxMind remains unchanged. Rollback: restore the 13 changed theme files to v3.1.4 (remove the two newly introduced live-search assets only after restoring their enqueue references); retain all persistent order/job metadata.

## v3.1.4 — Search indexing cleanup

- Adds clean canonical hints for unfiltered home, Shop and taxonomy archives, retaining pagination and omitting tracking/cache parameters. Native singular canonical URLs are cleaned through their existing filter; SEO-plugin archive ownership is respected. Search, private/account/cart/checkout, 404 and filtered collection views are not declared equivalent to an unfiltered archive. No robots/noindex changes.
- Retired /returns-policy GET/HEAD requests permanently redirect to the published /returns-refunds page. POST requests, unrelated paths and absent/unpublished replacement pages remain untouched. The literal uploads/* URL remains a 404; no blanket missing-page redirects. No Search Console validation submitted or indexing guarantee. Thirty mocked SEO checks and 72 reminder regression checks pass. Rollback: restore functions.php and version metadata from v3.1.3; the unused helper may remain on disk. No order, payment, email, stock or noindex-setting changes.

## v3.1.3 — Reminder product images

- HTML payment-attempt reminders include a compact linked product thumbnail beside each exact product/variant link, with a published-parent image fallback. Missing images are omitted without losing the product link. Plain-text email remains text-only. Reminder timing, enabled settings, eligibility and once-only safeguards are unchanged; no historical resend or test email. All 72 mocked reminder checks pass. Rollback: restore the email template and version metadata from v3.1.2; retain reminder jobs and sent markers.

## v3.1.2 — Ready to ship admin choice, reminder controls and tracking emails

- Ready to ship email starts only at Pickup Scheduled, not Ready to ship/Out for pickup/Picked up. Existing customer display labels remain combined. A separately keyed read-only tracking job reuses exact Shiprocket order/channel/shipment/AWB matching and approved HTTPS tracking URLs, retries up to four checks, then sends available details without inventing missing links. Later shipping/cancellation suppresses stale pickup mail; Shipped keeps its independent once-only email and verified courier/AWB/Track your order button. Existing sent markers are preserved; no historical resend.
- Recognised automatic Shiprocket tracking-link notes are suppressed from customer-note emails even when AWB metadata arrives later. Future matching notes become private audit records after insertion, not deleted. Unrelated/manual messages remain unchanged. No historical note conversion, booking, shipping-status mutation or financial test. Scheduling and real inbox/carrier acceptance are not guaranteed by mock checks.

### Reminder master switch

- Administrator-only nonce-protected Enable/Disable reminders control on Loomara Settings. Existing enabled behaviour is preserved by default; the native WooCommerce email setting is additionally respected. Disable suppresses new enrollment and outstanding queued work through a persisted cutoff; re-enable admits only new orders, never historical replay. Worker rechecks before sending; an email already handed to mail transport cannot be recalled. No order-status, stock, gateway or financial change; no live setting toggled.

### Reminder private order-note audit

- Writes one private Reminder email queued note only after a new reminder schedule succeeds, plus truthful accepted/skipped/disabled/verification/failed/uncertain outcome notes. Durable note markers prevent duplicate entries; audit failures do not change reminder state or cause resends. No customer-note emails, historical queue replay or order215 backfill; timing/eligibility/status rules remain unchanged.

### One Ready to ship option in the order editor

- Consolidates the native WooCommerce order-editor status dropdown to one Ready to ship choice. Preserves an already-selected internal courier key, so opening/saving an order does not rewrite its shipping stage; fresh manual selections use the canonical ready-to-ship key. Registered states, Shiprocket mappings, cancellation checks and once-only emails are unchanged. No historical order migration or live deployment.

## v3.1.1 — Cashfree storefront widget removal

- Dequeues only the observed cf-woocommerce-js handle when its source is sdk.cashfree.com/js/widget/. Covers late head/footer enqueues, without disabling or deregistering the Cashfree gateway, payment SDK, Razorpay, or changing any payment settings. No live payment or refund test. Rollback: restore functions.php and style.css from v3.1.0; the inert helper may remain on disk. Orders, inventory and financial settings are unchanged.

## v3.1.0 — Combined Ready to ship milestone and pending QC fixes

- Ready to ship, Pickup scheduled, Out for pickup and Picked up share the displayed label Ready to ship. The customer status filter offers one choice and queries all four underlying courier stages. Existing records and exact courier keys remain intact for cancellation safety; collected parcels are not made cancellable by a display rename.
- One Ready to ship email uses the existing pickup email's settings ID and durable send marker. The first qualifying event sends it; later pickup-stage events cannot resend it, and old pickup-email markers prevent renamed duplicate sends. No separate pickup notifications before Shipped. Shipped/AWB, Out for delivery and Delivered emails remain unchanged. No historical email replay, provider mapping, order status migration or live setting change.

- Retains the original WordPress submenu registrations for protected integration tools, hiding only their menu presentation. This restores native parent/access discovery for Loomara Settings links without changing page capabilities, settings or actions. Legacy migration tools remain hidden and are not rerun.
- Adds narrowly scoped form compatibility before payment SDK loading: recognised Loomara same-origin admin-post/admin-ajax forms move their hidden action route into the endpoint query, retaining POST nonces/private fields and native no-JavaScript markup. AJAX wishlist routing uses the same query/body handler. No Razorpay SDK, risk protection, payment fields or credentials are modified. Local native browser reproduction and routing tests pass; live vendor/payment acceptance still needs verification after deployment.
- Product-gallery likes descriptions sit below the heart as a separate compact element, with mobile sharing below them. Counts under two stay hidden; saved-heart state and desktop sharing visibility remain unchanged.
- Future newly completed cancellation refunds without a bank reference receive a separate GET-only follow-up: first after one hour, then every six hours, bounded to fourteen days/56 checks. Exact original payment/refund/receipt/amount, native refund parent/metadata and balance must match; conflicting references require review. Valid late ARN/RRN/UTR values update the existing refund and issue once-only customer/team reference notices. Exhaustion requires review and does not create another refund. No historical enrollment, financial POST, refund ledger/status mutation or existing intent reset.
- Repairs the cart JavaScript test fixture's missing dataset object; this was a test fixture defect, not a newly reproduced cart failure. Prior return recovery/review notifications, exclusions and bounded refund reconciliation remain included from v2.2.1.
- Rollback: restore presentation/form assets from v2.2.3 if necessary, but retain the bank-reference worker until any enrolled jobs settle. Preserve all existing provider/financial intents, locks, mail markers and refund metadata. No activation changes, historical jobs/emails, numbering, product/stock changes or real financial testing in this release.

## v2.2.3 — Transit stages and four shipment email milestones

- Adds native in-transit and out-for-delivery stages, customer/admin filters, recorded-payment checks, owned tracking access and active-return forward-update protection. Both remain ineligible for customer cancellation.
- Automatic shipment emails only at Pickup scheduled, Shipped, Out for delivery and Delivered. Adds native settings/HTML/plain-text templates and per-stage once-only mail markers for the two new emails, with bound-account recipient routing. No separate Out for pickup, Picked up or In transit notification. Existing cancellation/payment/refund notifications remain unchanged. Matching known automatic Shiprocket tracking-link customer-note emails are suppressed without deleting notes; unrelated team/customer notes remain enabled.
- Shipped email read-only courier/AWB/tracking enrichment survives progression to In transit or Out for delivery without resending an already-notified shipment. No first-observed transit notification or historical email replay. Delivered/cancelled/refunded states suppress stale queued shipping mail.
- Channel mapping: IN_TRANSIT to in-transit and Out for Delivery to out-for-delivery. No order214 change beyond the already-approved pickup correction. No historical stage email replay, provider booking, financial action or feature-activation change. Rollback follows v2.2.2's registered-status precautions; retain all email/job markers to avoid duplicate sends.

## v2.2.2 — Forward pickup status mapping

- Registers native pickup-scheduled, out-for-pickup and picked-up stages for WooCommerce/admin/customer order filters, preserving recorded paid-date checks rather than inferring payment from shipping. All stored status keys fit WordPress limits.
- Pickup tracking remains visible on owned order details. Active customer returns reject forward pickup overwrites. Paid Razorpay pre-collection cancellation requests retain exact remote verification; picked-up is not eligible for customer cancellation. No automatic shipping email is sent before Shipped; no return/refund/job activation or historical replay.
- Shiprocket channel mapping must use these exact canonical slugs. Registration alone does not poll or replay provider events; order214 requires fresh exact verification before a one-order correction. Rollback must remap affected provider statuses and settle or deliberately reclassify native pickup records; do not simply remove registered statuses from existing orders.

## v2.2.1 — Return recovery, refund progress and gallery controls

- Cancellation refunds use Refund initiated after their durable submission intent, and Refunded only after verified processed Razorpay response and matching full native refund ledger. Pending/uncertain requests reconcile without a duplicate POST; payment identity/status conflicts require review. No historical replay or activation-setting change.

- Product gallery controls: custom imported colour galleries now render the same wishlist/mobile-share controls as native galleries, plus an accessible full-image expand control that follows colour and thumbnail changes. Shared rendering covers current and future imported products; desktop sharing stays hidden.

- Distinguishes temporary provider transport/response failures from identity/status conflicts. Temporary quote, reverse-detail and refund verification failures retain their phase; retry every five minutes for six checks, then every fifteen minutes, with a 24-hour/112-attempt bound. Early duplicate events respect saved next-check time. Conflicts and exhausted retries require review; a missing response never authorizes a duplicate booking or refund.
- Adds bounded staff return-work queue, last/next check information, permission/nonce-protected recovery with required resolution note, and one customer/team review notification. Submitted carrier/refund intents resume GET-only reconciliation even when new returns are disabled. Unsubmitted quote reviews return to explicit staff quote/booking approval, never automatic rebooking; an approved-but-unsubmitted refund exception returns through received-shipment verification and inspection approval. Dispute/identity/status safeguards remain mandatory. Existing historical review records are not automatically replayed.
- Adds reason classification, a native product return-policy selector and conservative final-sale/clearance/personalised/made-to-order tag checks. Restricted change-of-mind requests are blocked before booking; defect/incorrect-item exceptions require explicit staff eligibility approval, in addition to pickup charge approval. Ordinary eligible fresh quotes strictly below ₹100 retain automatic booking; ₹100 or above remains manual. No existing product metadata is migrated.
- Bounds unavailable/incomplete refund reconciliation instead of polling indefinitely. A verified pending gateway refund can remain pending for up to seven days before review. Original receipt/payment/amount identity and single financial intent are preserved; full/partial ledgers, mandatory inspection and no automatic stock increases remain unchanged.
- For future return refunds missing a bank reference, adds read-only enrichment of exactly matched processed refund ARN/RRN/UTR: first check after one hour, then six-hour checks bounded by 14 days/56 attempts. No gateway write, new ledger refund, status rewrite or historic backfill. Existing verified references are not overwritten on conflicts. One reference-available update or exhausted-follow-up notice goes to customer/team; real bank/inbox acceptance remains untested.
- Local tests: 36 new recovery checks plus 70 existing return lifecycle checks; cancellation112+baseline64, refund64, delivery41, payment-email60, account44+prior60, Processing-age28+prior176 and payment-failure87 pass (overlapping). Local desktop recovery screen and 390px iframe customer component checked; no full native live-mobile or real carrier/financial acceptance claim.
- Not published, deployed or enabled. Preserve all books, intents, locks, mail markers and schedules on rollback; submitted operations must keep reconciliation code until settled. No historical replay, renumbering, production email/payment/refund/pickup, server cron or unrelated QC fix performed.

## v2.2.0 — Item-level returns and inspected refunds

- Adds a customer Request return action and mobile-friendly item/quantity/reason form in owned account orders. It is available only before actual verified courier delivery + seven calendar days; the server rechecks eligibility on submission. Delivery observations alone never authorize returns. Missing dates, split forward deliveries and unknown shipment identities require review. Existing eligible orders can have delivery timestamps verified read-only on account visits; no historic return or refund is automatically enrolled.
- Separate durable LM-RET references link each request to its immutable owner, payment, item/tax snapshot and exact forward shipment. One active request per order; ordered, previously returned, reserved and manually refunded quantities are accounted for. Selected amounts use saved post-coupon line totals/taxes with cumulative paise rounding, not catalogue prices or the original undiscounted total.
- Reverse shipments go from the original customer delivery address to the verified Shiprocket Home location. Fresh INR quotes strictly below ₹100 can be automatically approved using saved parcel measurements; ₹100 or above, missing/changed quotes, unsupported data and failed checks require staff review. The threshold applies to the quote, not later carrier weight adjustments. Staff can confirm actual parcel measurements and approve a quote in WooCommerce > Loomara Settings > Customer returns. One durable create intent, then GET-only reconciliation after any outcome; no blind duplicate booking or courier-wallet retry.
- Reverse AWB and a validated Shiprocket tracking link appear in customer order details. Exact reverse order/channel/type and shipment ownership are checked. Return pickup/collection/received statuses never imply a refund. Forward callbacks cannot overwrite an active return through normal WooCommerce CRUD; submitted reverse bookings continue read-only reconciliation even when new enrollment is disabled. Scheduling depends on normal WordPress traffic, not a new server cron.
- Return received waits for explicit team inspection in WooCommerce. Approval records Return complete and queues an independent guarded Razorpay refund; rejection does not refund. Fresh reverse-delivery proof, captured-payment identity, coupon-adjusted selected amount and gateway/local balances are required. Refund initiated remains unconfirmed until Razorpay GET reconciliation reports processed. Only then is a native item-level refund ledger recorded, with verified refund/bank references available to existing customer emails and order details. Full refunds use Refunded; partial refunds use Partially refunded. No automatic stock increase or historical replay. Shared financial locks prevent overlap with cancellation refunds; existing cancellation logic is unchanged.
- Customer and configured order-email recipients receive separate bounded lifecycle notifications. Existing email acceptance, real reverse-booking permission, courier pickup, inspection and bank processing are not proven by mocked tests. The feature defaults off; an administrator must run the read-only connection preflight and perform the final financial activation. No automatic policy enable, order renumbering or unrelated settings changes.
- Regression coverage: return lifecycle/expiry/owner/quantity/rounding/full+partial/refund safety, admin navigation, cancellation/refund, delivery lifecycle, account references, payment-failure, next-day reminder and seven-day Processing policy mocked suites pass (overlapping). Local mobile390 and desktop1280 component checks show no horizontal overflow. No real customer request, email, pickup, payment or refund submitted during QA.
- Rollback: first disable new returns/bookings/refunds in Customer returns. Preserve all return books, delivery baselines, mail markers and booking/refund intents/locks. Keep reconciliation code loaded until submitted carrier/financial operations settle or are manually reconciled; do not remove active worker code or replay intents. After settlement restore changed theme files from v2.1.1. Code rollback cannot undo money movement or a courier booking.

## v2.1.1 — White circular gallery controls

- Wishlist and mobile share controls use solid white circular backgrounds like image expansion, with subtle hover feedback and visible keyboard focus. Existing 44px targets and mobile-only sharing remain unchanged. Sharing stylesheet cache version advances to4.
- Local390px computed-style checks confirm white backgrounds, 50% radius and44px targets; PHP17/JavaScript19 sharing regressions pass. No order, payment, refund, job or setting changes. Rollback: restore the four changed files (style.css, RELEASE.md, inc/product-sharing.php and assets/product-sharing.css) from v2.1.0; no data rollback or job replay.

## v2.1.0 — Order safeguards, seven-day Processing policy and product controls

- Move the standalone Paid order push to Shiprocket menu link into the Loomara Settings hub. Original protected URL, plugin registration, export toggle, jobs and saved intents remain unchanged; menu presentation only.

- Product detail wishlist now sits beneath image expansion, with mobile-only share directly below it; removed their old summary/metadata placements. Existing save/share behaviour retained, gallery overlay uses 44px controls.
- Read-only coupon code, recorded savings (including discount tax) and saved final order amount shown in WooCommerce admin and owner account order details. Historical amounts are never recalculated; no coupon is fabricated when no coupon item is stored.
- Disabled-by-default seven-day paid Processing cancellation policy with administrator activation/stop control in Loomara Settings. Records future Processing entry time, uses the later of paid/Processing time; legacy records without the latter conservatively use last modification. Hourly bounded 50-record batches include historical eligible orders once enabled. Existing intents/review records are never reset. Paid owner-bound Razorpay orders only; known shipped/return/dispute/refunded/unsupported records blocked. Starts the existing locked carrier verification/cancellation flow, requires exact carrier cancellation confirmation, then the independent captured-payment/refundable-balance engine. No direct blind cancellation/refund or historical financial action during testing. Scheduling is traffic-dependent; seven days is an eligibility boundary, not an exact execution guarantee. Disabling stops new enrollment; preserve and reconcile all previously started cancellation/refund jobs, intents and locks.
- New processing policy 28 fake-data checks (plus existing request112/refund64), coupon3, sharing17/JS19, menu20, history82, reminder49, failure87 and stock11 regressions pass (overlapping). Local gallery component at390px has expand/like/share in vertical order without overflow, desktop1280 hides share. Actual carrier/bank/inbox acceptance is not proven by mocks.

- Protect native Payment attempted orders carrying the explicit Magic Checkout marker even when their payment-method field is blank. This matches next-day reminder eligibility and closes the observed gap on order 208. Blank unrelated gateways and genuine payment-failure/payment-success/explicit cancellation paths remain unchanged.
- No historical order restoration or abandonment-reminder replay, stock-setting or numbering change. Existing stock holds expire independently; the guarded one-time next-day reminder remains unchanged.
- Rollback: restore this release's changed theme files from v1.12.12, first disabling new seven-day enrollments in Loomara Settings. Do not delete/reset durable cancellation/refund intents, locks, mail markers or seven-day baseline metadata; already submitted carrier/financial requests need reconciliation, not replay. Financial consequences cannot be undone by code rollback.

## v1.12.12 — Sharing fallback link separator

- Live native WordPress esc_url sanitisation strips encoded newlines from non-mailto links. Use a plain space between public product text and its URL in WhatsApp/SMS/email fallbacks so the canonical link remains clearly separated. Native Web Share title/price/image/link payload and mobile-only icon unchanged.
- Seventeen PHP sharing checks plus nineteen JavaScript native/fallback regressions pass. No recipient selection, messages sent, order/payment/refund/job/numbering or settings changes. Rollback: restore the three changed files (sharing PHP, style.css and this consolidated history) from v1.12.11; no data rollback or historic replay.

## v1.12.11 — Mobile product sharing

- Adds only a small 20px share symbol with an accessible 44px touch target on published product details at mobile widths up to767px. Desktop sharing is hidden. Tapping opens the phone's native share sheet when available; unsupported browsers/no-JS retain manual WhatsApp, Email and Message/SMS choices behind the icon.
- Shared payload includes public product title, current display price (From for variable products), canonical product permalink and brand. On secure supported mobile browsers, a public same-origin JPEG/PNG/WebP image is prepared in advance (5MB limit, five-second timeout, no credentials/redirects) and attached only if the native API accepts it. Missing/unsupported images do not block text/link sharing. Recipient/app selection and final sending remain entirely with the customer. No current cart/session/account/order query copied, no SDK or automatic send.
- Public Open Graph title/price-description/image/link metadata supports WhatsApp link previews unless a known SEO plugin owns that metadata. WhatsApp text URLs cannot force an image attachment; receiving apps control previews and whether they preserve combined files/text/links. No guaranteed app-specific image rendering or external sharing acceptance claim.
- Sixteen PHP payload/metadata tests and nineteen JavaScript mocked native/fallback/file/security/cancel/double-tap checks pass, plus prior account44+email60/testimonial20 regressions. No real messages sent during QA. Canonical design/minimum price shared, not a selected variation snapshot. No order, inventory, payment, refund, scheduler or numbering changes. Rollback: restore functions.php/version/RELEASE.md from v1.12.10 and leave the three now-unused sharing files unreferenced; no data rollback needed.

## v1.12.10 — Customer order details, cancellation timing and testimonials

- Testimonial submissions and edits return to the homepage testimonial section with a saved/awaiting-review message. One testimonial per authenticated customer, without the previous thirty-day resubmission loophole; server-side owner lookup and per-user submission lock retained. Existing customers get Edit your testimonial; edits update the same record and return it to Pending moderation. Archived records require team restoration, no silent undelete or second submission. Existing historical records are not deleted or merged.
- Testimonial captions use public name — consented public city, never the generic Loomara customer suffix. Form includes an optional editable public city, with saved shipping city suggested only for a new submission and explicit name/city publication consent required. Legacy quotes with no public city show name only; no private address backfill. Twenty mocked create/edit/owner/duplicate/consent/moderation/redirect checks pass; no live testimonial writes.
- Every owned account View order ends with Contact us and Find your next layer buttons linking to Contact and Shop. Footer runs after the native account endpoint output including addresses; wraps on desktop/stacks on small screens, keyboard focus and reduced-motion styling included. Owner/guest/missing-order guards; all statuses supported. Account-reference suite44 plus prior email60 mocks pass.
- Refunded owner View order removes only the exact internal Automatic cancellation refund [LM-CANCEL-…] reason from native refund totals markup. Refund amounts, other reasons, original order total, admin/email/API output and stored reconciliation reasons remain unchanged. Razorpay refund/bank details remain in the dedicated panel. Official WooCommerce source checked; presentation filter only, never a get_reason/data rewrite. Account/reference suite34 plus prior email60 checks pass.
- First cancellation verification is now scheduled at request+10 seconds through Action Scheduler and the durable WordPress fallback, rather than immediate enqueue. A persisted next_check guards against early execution. No blocking sleep, new server cron, historical replay or timing guarantee; existing 3-minute/15-minute/24-hour retry limits and carrier/payment safeguards remain. Root cause of the reported live delay has not been established by local QA.
- Paid Cancelled orders awaiting an enabled automatic refund show a compact Refund details control: "Your refund will be initiated shortly." Shared between Orders cards and View order; removes the duplicate old verification box for those queued cases. Review/skipped/submitted/processed states retain their accurate messages; unpaid cancellations and disabled automation do not promise a refund. No refund worker financial logic changed.
- Owner-only View order panel displays recorded paid Razorpay payment ID, total refund recorded, verified refund IDs and available bank ARN/RRN/UTR from matching refund metadata. Missing references are not fabricated or fetched during page rendering. Original order totals/details remain unchanged.
- Orders cards show Total refunded instead of Order total once a positive refund is recorded (including partial refunds), or zero if manually marked Refunded without a ledger amount. View order retains the original amount. No status or financial changes.
- Customer full/partial refund emails explicitly separate the amount of this refund from cumulative Total refunded to date. Only the refund-email table replaces its original total summary with the refunded total; account, invoice and unrelated/admin email totals stay untouched. HTML/plain supported, no resend.
- Once Shipped/Delivered/Completed, View order shows saved verified Shiprocket AWB/courier and a Track shipment button for each available approved HTTPS tracking URL. Unsafe/missing links omitted; no carrier/API request while rendering. Responsive owner-only panel, no customer data in public pages.
- Final isolated account/reference/amount/context suite44, testimonial20, email60, request112+prior64, refund64, payment-failure87, refund-display14+prior75 and tracking72 checks passed (overlapping suites). No live email, financial test or order mutation during QA. Desktop and390px synthetic component widths checked, not a full live mobile-site acceptance claim.
- No historical cancellation/refund job replay, resends, automatic testimonial approval, private-city backfill, order renumbering, server cron or standalone-plugin settings change. Existing testimonial edits are moderated; no historical records deleted/merged. Rollback: restore this release's eleven theme files from v1.12.9 after reconciling active cancellation/refund/mail intents. Retain all durable markers and existing public-city/consent metadata; do not reset jobs or restore public status of edited testimonials without review.

## v1.12.9 — Immediate cancellation checks and customer email references

- Future customer cancellation requests immediately enqueue the existing locked verification worker through a unique Action Scheduler async action, with a durable WordPress event due one second later as fallback. No synchronous financial call holds the customer redirect open. Background execution can be delayed by server scheduling.
- Exact unshipped Shiprocket eligibility is checked before requesting carrier cancellation. WooCommerce becomes Cancelled only after exact order/all-shipment cancellation confirmation; the existing refund engine then independently verifies captured payment and refundable balance. Missing records retry every three minutes initially, then every fifteen minutes after the initial forty-five-minute window, up to twenty-four hours. Existing safeguards, one submission intent and read-only reconciliation remain unchanged. Updated the new-request audit note to describe this flow.
- Paid Razorpay customer cancellation emails include the original payment reference and explain that cancellation does not itself confirm a refund. Separate verified refund emails include their matching payment reference, refund ID and available bank ARN/RRN/UTR. Never invent missing references or call a gateway while rendering email.
- Successful-order payment references and verified Shiprocket AWB/courier/clickable tracking links were already implemented and remain unchanged. Tracking links are included only when verified and available; no guaranteed inbox delivery or real financial/carrier acceptance claim.
- Mocked regression checks passed: cancellation96 plus prior refund64, customer-email60, refund64, owner-safety39, tracking72 and preview11 (overlapping suites). No real payment, cancellation, refund or email was submitted during QA.
- No historical job replay, resend, status migration, numbering change, standalone-plugin setting change or server cron. Rollback: restore the four changed files from v1.12.8 after reconciling active cancellation/refund intents; retain all durable jobs, locks, reference and mail markers.

## Standalone WordPress integration — 20 October 2026

- Installed and enabled **Loomara — Paid Order Push to Shiprocket 1.0.0**, independently of the theme/GitHub main. Future newly created paid Processing Razorpay orders enqueue a background export to existing WooCommerce channel12607102, pickupHome; numeric and full Loomara references included. No historical backfill. Existing importer stays enabled.
- Exact duplicate searches, fresh identity/status/payload checks, durable per-order locks and single submission intents. Existing records skipped; uncertain submissions reconciled read-only, never blindly resent. Pre-submission checks retry every five minutes with bounded attempts. Traffic-dependent WordPress scheduling may run late. Off switch and recent-job dashboard under WooCommerce → Shiprocket order push. No courier booking, AWB assignment, pickup, capture, cancellation or refund request.
- Multi-item physical/free-shipping orders reconcile paid invoice amounts exactly; saved weights summed, dimensions conservatively stacked by quantity. Verify actual packing before dispatch. Missing measurements, fees/non-free shipping, unsupported items or rounding mismatches stop for manual review. Supports paid Razorpay/INR with matching account email; does not claim other gateways or all parcel configurations.
- Deployed source matched tested source; enabled setting persisted and no historical orders enrolled. Forty-seven exporter mocked assertions plus existing regression suites passed. Storefront healthy; real future automatic order acceptance remains to be verified.
- Removed only the temporary **Isolated Shiprocket Push Test** plugin with separate approval. Preserved test submission state/intents, local recovery source and Shiprocket orders202/203. Permanent Order Reference Search stays active. Other pending changes remain local.
- Rollback: disable export on its settings page, reconcile submitted jobs, then deactivate the exporter. Never erase durable jobs/intents/locks to force retry. No database/order-numbering reset or financial replay.

## v1.12.8 — Payment attempts, cancellation checks and customer email references

- Retains the already-live standalone Order Reference Search and automatic Shiprocket push plugins without changing their settings or reinstalling the removed test tool. No duplicate theme search implementation is loaded. Completes the previously published v1.12.7 administrator-menu cleanup on the live site while preserving original protected tool URLs.
- Successful Razorpay customer order emails (Processing, Completed, paid invoice) include **Payment reference number: pay_…** from the saved transaction. Recorded paid date and successful status required; no success reference in abandoned/failed/cancelled/refunded or unrelated/admin notifications. HTML/plain supported, no extra email or API call. Customer payment-email suite now51 mocked checks.
- Route bound Magic Checkout customer order/payment/refund notifications to the original account owner’s email, not a gateway-returned saved email. Prefill the authenticated email in the supported Magic Checkout response; this does not guarantee Razorpay’s independently generated notifications override its saved profile. Add verified automatic-refund ID and available bank ARN/RRN/UTR to native HTML/plain refund emails before their first trigger. No guessed references, duplicate customer message, historical resend, provider-email rewrite or change to financial operations. 28 isolated recipient/reference/prefill tests pass alongside refund/cancellation regressions.
- Keep abandoned Razorpay orders as Payment attempted by vetoing native stock-timeout cancellation. Native stock reservations still expire independently; standard order-pay retries reacquire stock through WooCommerce before payment. Verified failures and successful payment remain separate flows. No historical status rewrite.
- One next-day reminder for future mapped, owner-bound unpaid attempts: 24-hour single event, fresh gateway verification, subsequent-purchase suppression, customer/day dedupe and durable at-most-once transport markers. Links to products and collection; no direct payment-token links. No recurring server cron, old five-minute timers or historical enrollment. Scheduling and inbox timing are not guaranteed.
- Cancellation requests retain their initial 45-minute fast checks, then recheck every 15 minutes for up to 24 hours /112 attempts. A durable carrier cancellation intent is never resubmitted; confirmation is reconciled read-only before the existing guarded refund workflow runs. Ready-to-ship/AWB-assigned paid requests can be checked remotely; shipped, ambiguous, changed-identity and mixed-dispatch shipments remain blocked. Documented additional pre-pickup statuses supported using exact codes, not broad dashboard labels.
- Existing manual-review records are not automatically reset or replayed. Any targeted old-request resumption requires separate approval. No live payments, refunds, cancellations, emails, inventory or order records were changed during local QA. Mocked checks: reminder49, stock11, history52, request91 plus refund64, payment87, customer-email51 and owner-safety39, with navigation/search/exporter regressions also passing.
- Native order IDs, historical references and numbering stay unchanged. No historical reminder enrollment, cancellation/refund replay, server cron, inventory migration or standalone integration-setting change.
- Rollback: pause the new abandoned-reminder email and reconcile active cancellation/refund/mail intents before restoring v1.12.7 code. Preserve all request, submission, lock, dedupe and mail markers; never erase them to force retries. Do not restore an immediate-cancellation handler over active requests. Standalone plugins can remain unchanged.

## v1.12.7 — Consolidated Loomara administration menu

- Adds one administrator-only **WooCommerce → Loomara Settings** hub linking the existing Shiprocket connection, Automatic refunds and Payment failure sync pages. Existing page registrations, capabilities, callbacks and protected direct URLs remain intact; opening the hub performs no settings write or order action.
- Hides exactly the nine known Loomara submenu entries after their registration: three ongoing tools now reached through the hub, and six inventory/catalogue/weight/parcel/offer/retired-timer setup tools no longer cluttering routine navigation. Native WooCommerce and unrelated plugin menus stay unchanged. Known direct tool URLs highlight the hub without re-registering their actions or changing permissions.
- Completed setup code and private migration snapshots/audit markers are retained, not deleted or rerun. Shipping/refund/payment configuration and workers, customer records, orders, products, stock, email settings, IDs and numbering remain unchanged.
- Eighteen isolated admin navigation/capability/link/malformed-input/no-migration checks passed along with existing request/refund/display regressions. QA does not submit any live settings, migrations, email, cancellation, payment or refund action.
- Rollback: restore functions.php and version/release notes from v1.12.6; the unused admin-navigation module may be removed. Original individual menu registrations will reappear. No database, integration or financial replay is required.

## v1.12.6 — Compact cancellation and refund details

- Replaces long always-visible customer cancellation/refund messages on account order cards/details with compact **Cancellation details** / **Refund details** information controls. The status badge and full existing explanatory copy remain unchanged. Manual-review and partial-refund guidance retain their existing conditions; no order, job or financial logic changes.
- Desktop fine-pointer users can hover or focus the control to open a popover; clicking pins/unpins it. Mobile/coarse-pointer users tap to expand inline and tap again to close. Escape and outside click dismiss; native details/summary remains keyboard/touch usable without JavaScript. Contact links stay accessible. Desktop open popovers can escape the order-card clipping boundary; mobile content remains in normal flow.
- Tiny account-page-only interaction script; existing order stylesheet handles both layouts. Fifteen isolated JavaScript interaction checks passed, plus collapsed-control backend checks and prior refund/cancellation/history/payment/email regressions. Synthetic actual 390px tap-open/close and 1366px keyboard-open popover checks show no horizontal overflow. No real email, cancellation, payment or refund submitted for QA.
- This is presentation only. The 15–30 minute estimated check-back copy, bounded cancellation checks, Shiprocket confirmation, Razorpay refund guards, email recipients, stored statuses, order records and numbering remain unchanged.
- Rollback: restore the changed presentation PHP/CSS and version/release notes from v1.12.5 and remove the unused account help script. Preserve cancellation/refund workers, all durable request/mail/financial intent/lock markers and current records. No migration, scheduler change or financial replay.

## v1.12.5 — Visible refund guidance in customer orders

- Retains native **Refunded** status badges, the Refunded filter and refunded orders under All orders. Adds owner-only guidance on order cards and order details: check the refund email for the amount and further details; bank processing times may vary, and contact us if the email is missing.
- A positive recorded refund ledger on an otherwise active/cancelled order shows **refund recorded**, explicitly noting it may be partial, without rewriting its status or claiming a full refund. Cancellation requests, queued refunds and Cancelled status alone never display a completed-refund claim. Foreign/guest records do not receive account refund guidance.
- Suppresses only the duplicate completed-automatic-refund detail message when the shared recorded-refund notice already applies. Pending/manual-review messages remain intact. No refund email is generated or resent, and no order/payment/refund ledger/status, historical reference or numbering changes.
- Twelve new isolated display/filter/ownership/partial-refund checks passed along with existing cancellation/refund/history/email regressions. QA uses mocked orders; no actual email, cancellation, payment or refund submitted. Real customer inbox delivery is not verified by these display checks.
- Rollback: restore the three presentation files and style/version/release notes from v1.12.4. Preserve v1.12.4 cancellation-request workers, durable request/mail markers and all financial intent/lock records. No data migration or financial replay is needed.

## v1.12.4 — Customer cancellation requests before carrier confirmation

- Future owner/nonce-confirmed, recorded-paid Razorpay cancellations now record a durable **Cancellation requested** marker, owner/payment/amount identity and request timestamp. Native Processing/on-hold/pending status is retained for existing Shiprocket import; no immediate cancellation, restock, cancellation email or refund. Unpaid cancellation behaviour and team/external transitions remain unchanged. Existing/bookmarked native paid one-click cancellation links cannot bypass this workflow.
- Customer order cards/details display the request and estimated **15–30 minute check-back** message, not a completion promise. Repeat requests/buttons are suppressed. Team order details and notes warn **DO NOT PACK OR DISPATCH**. This warning is operational, not a remote Shiprocket dispatch lock: staff must honour it.
- Separate one-time request emails go to the configured WooCommerce New order recipient and the owning customer’s account email (not a Razorpay-submitted identity). Team copy includes the reference, customer name, IST request time and admin link; customer copy includes the check-back message and own order/contact links, never internal warnings/admin links. Independent durable at-most-once transport markers; failed/uncertain delivery requires mail-log review, not blind resend. Existing SMTP configuration and transactional emails are unchanged. No test email was sent to a real recipient.
- Bounded per-request WP-Cron checks roughly every three minutes, with a 45-minute wall-clock deadline/16-attempt limit. Exact channel/reference/order/all-shipment matching and current status remain mandatory. Missing/unsynced/network/ambiguous data never authorises cancellation or refund. Dispatched/changed/expired cases remain flagged for manual review. Traffic-based scheduling can run late; no hourly sweep, server cron, new export or importer configuration change.
- A durable exact carrier cancellation intent is saved before one cancellation POST. Every later check is read-only; even HTTP success is only acknowledgement. Only fresh confirmed order/all-shipment cancellation transitions WooCommerce to Cancelled. Existing cancellation notification and captured-payment/refund-ledger/idempotency verification then run independently. Duplicate requests/events, changed identities and concurrent workers fail closed. No historical cancellation/refund job is reset, adopted or replayed.
- Local mocked cancellation/notification/handler tests plus existing refund/payment/ownership/history/tracking/coupon/address regressions passed; desktop/mobile synthetic presentation has no horizontal overflow. Real future customer-to-carrier-to-bank and inbox acceptance remains untested; no actual live cancellation, refund, payment or email was submitted for QA.
- Rollback: first resolve/reconcile every active `loomara_cancel_request_124_*` request, any submitted carrier cancellation, related refund intent and uncertain email. Do not delete durable request/mail/financial intent/lock markers or blindly replay writes. Only then restore v1.12.3; do not revert the handler while requests remain outstanding. IDs, historical references, prices, inventory and payment/Shiprocket settings are unchanged.

## v1.12.3 — Payment-attempt label and visible tracking preview

- Relabels native Pending payment as **Payment attempted** through WooCommerce's status-label filter. Internal `pending` keys and gateway/stock/retry hooks remain unchanged, so existing records receive the new label without a database migration. Hidden draft presentation uses the same wording. Verified failures retain **Payment failed**, and unpaid checkout attempts remain hidden from customers.
- The native **Loomara — Shipped order** settings preview now shows a clearly labelled example AWB/courier block when its dummy order has no tracking. Uses WooCommerce's official preview hook without saving fake order metadata or fetching a shipment. Real saved tracking takes precedence; every real transactional trigger clears preview state. HTML and plain text supported; no invented tracking URL is shown.
- Clarifies in email settings that real AWB/courier/Track your order details are automatically inserted when available; no subject placeholder is needed. Existing bounded tracking verification/fallback and at-most-once delivery are unchanged: missing/unverified tracking cannot be guaranteed in the final email.
- 587 unique isolated backend/regression checks passed, including 11 new preview/non-leakage and 7 status-label cases plus the existing 569 history/email/payment/refund checks. No real payment, cancellation, refund or customer email submitted for QA.
- Automatic refunds were verified already enabled. A read-only check for order OD-202610-000185 found no exact Shiprocket record in the verified WooCommerce channel; it remains manual review. This release does not bypass shipment checks, replay that historical job, sync shipments or issue money movement. Refund/stock/email settings, order IDs, references and historical notes stay unchanged.
- Rollback: restore v1.12.2 for the prior display label/preview. No data migration, webhook change, financial replay or numbering reset is required.

## v1.12.2 — No customer email for unpaid stock timeouts

- Suppresses the native customer Cancelled order email only while the order is Cancelled, has no recorded paid date or refund ledger, and has an internal WooCommerce system note explicitly identifying the unpaid hold-stock timeout. It does not globally disable customer cancellation emails. Manual/customer/team cancellations, paid/refunded records, administrator emails and refund/shipping/payment notifications keep their existing settings.
- Reuses the v1.12.1 read-only native timeout-note classifier. A failed transaction reference does not cause an unpaid timeout email to send; v1.12.1's conservative transaction-protected history visibility remains unchanged. Preview/missing-order and unknown-reason cases keep the original email setting. Existing notes/emails are not deleted or resent.
- 569 isolated backend/regression assertions passed, including 16 new email-suppression cases and the prior 553 order/history/payment/refund checks. The installed native WooCommerce source confirms the enabled filter runs before transport. No actual customer email, cancellation, payment, refund or historical status is changed for QA.
- Native stock-release scheduling and the 60-minute setting, customer order records, payment-failure retries, numbering and all other email settings remain unchanged. Rollback to v1.12.1 restores the previous cancellation-email behaviour; there is no data migration or webhook change.

## v1.12.1 — Hide unpaid checkout timeouts from customer history

- Customer order history now excludes unpaid Cancelled attempts only when an internal WooCommerce system note explicitly identifies the native “Unpaid order cancelled - time limit reached” reason. The rule applies before pagination and in the defensive card renderer, including the Cancelled filter. It works for existing records such as OD-202610-000174 without migrating or changing them.
- Manual/unknown cancellations remain visible. Recorded paid dates, refund totals/refund records or a saved gateway transaction protect cancellation records from hiding. Verified Payment failed records and their same-order Retry payment controls remain unchanged. Notes are read only for unpaid cancelled candidates, with a request-local classification cache.
- This is display-only: no order/status/note deletion or mutation, payment/refund request, stock change, email-setting change, reminder/cron restoration or renumbering. The native 60-minute hold-stock cancellation remains configured and can still release unpaid stock; existing administrator records/direct authorised access remain intact.
- 553 isolated backend/regression assertions passed, including 16 new timeout/manual/paid/refunded/ownership/pagination cases and the existing payment-failure, identity, refund, cancellation, tracking, coupon and address checks. No live cancellation, payment, refund or email is submitted for QA.
- Rollback: restore v1.12.0 to restore the previous customer-list visibility. There is no data migration to undo; do not alter the dedicated v1.12.0 failure webhook or order numbering.

## v1.12.0 — Verified payment failures and secure retries

- Adds a separate signed `payment.failed` receiver and explicit administrator connection page. A server-generated secret is encrypted using WordPress authentication salts; existing native success/refund webhooks and vendor plugin files are not changed. Registration saves intent before its one POST and reconciles uncertain responses read-only rather than submitting duplicates. Credential/salt changes require manual connection review.
- Future eligible checkouts only: exact WooCommerce/gateway mapping, INR totals and complete payment-attempt lists for every saved gateway order must confirm failed and unpaid. Authorised, captured, refunded, partial, unknown, mismatched or incomplete results do not downgrade the order. No historical scan, abandonment timer, reminder, server cron, capture or refund is introduced. Existing success callbacks remain active, including later successful retries.
- Signed failure events enqueue bounded Action Scheduler verification with durable event deduplication, per-order locks and at most three verification attempts. Inconclusive cases leave a review note rather than inventing a failed payment. Final status persistence rechecks the stored order under an InnoDB row lock; payment state is reread after network waits. Background processing can be delayed.
- Verified failures use the native Failed state, labelled **Payment failed**, appear in the owning customer's All orders/failed view, and offer **Retry payment** using that same order's native secure payment URL. Pending/draft/abandoned and unverified unpaid failures remain hidden before pagination. Paid/refunded records, ownership checks and historical numbering remain unchanged. A debit/processing warning discourages duplicate payment.
- 537 isolated backend/regression assertions passed with mocked orders, database and HTTP, including real HMAC/encryption checks in the PHP runtime. Six native-template assertions and mobile/desktop layout checks cover the visible failure/retry controls. No real failed payment, customer order, email, capture or refund is submitted for QA; actual provider-to-order delivery requires a genuine future event or separately approved test-mode acceptance.
- Rollback: disable the dedicated failure webhook in Razorpay before restoring v1.11.8; retain its encrypted connection/intent options and event/lock markers for reconciliation. Do not remove or replay existing financial jobs, change the native success/refund webhook or renumber orders. Already verified failures retain native records; this release does not backfill history.

## v1.11.8 — Checkout route matching hardening

- Final deployment verification hardens the v1.11.7 new-order sign-in gate to normalize route case and trailing slashes, matching WordPress REST route handling. Equivalent endpoint spellings cannot bypass the identity check. Only the new Magic Checkout order-creation endpoint is affected; gateway callbacks, shipping/coupon services and historical orders remain untouched.
- 35 isolated identity assertions passed, including three equivalent-route cases, alongside the previously passed ownership/payment-safety regressions. No actual payment or customer order was created for QA. All v1.11.7 login-before-payment and one-time automatic handoff behaviour remains included.
- Rollback: restore v1.11.6 to remove the complete checkout change. No order/customer migration, account merging, refund-setting change, timer restoration or renumbering occurs.

## v1.11.7 — Account-linked Magic Checkout

- The cart action reads Continue to payment. Signed-out customers sign in with Google or their Loomara account first; a successful checkout-intent login/signup returns to the cart and automatically invokes the installed Magic Checkout handler once, without a second checkout click. Ordinary logins do not auto-launch payment. Navigation trigger removal and per-tab consumed markers prevent reload/back replay; empty carts, unavailable buttons/storage retain a manual fallback. Opening the gateway does not submit or capture payment.
- Standard checkout still accepts guests with a mandatory email field placed first.
- The exact native POST `/1cc/v1/order/create` endpoint has a server-side authenticated-account/email gate before order creation, independently of the browser prompt. Native permission and nonce validation remain intact. New Magic orders capture their owner and billing email from the authenticated account, never submitted email/mobile, and retain the existing gateway ownership protection.
- Shipping/coupon services, payment callbacks/webhooks and existing order access are not gated or reassigned. No historical account merging, numbering changes, payment/refund requests, timer enrolment or cron restoration. Existing password/OTP login and Google credentials remain unchanged.
- 32 isolated identity checks plus ownership, refund, tracking, coupon, history, address and timestamp regressions passed. Mobile 390px and desktop 1366px simulated sign-in automatically opened one placeholder payment window without overflow; refresh did not reopen it. This local demo is not a real OAuth/payment test. Actual new-user Google-to-paid-order flow requires owner acceptance testing; QA creates no real customer/order or payment.
- Rollback: restore the preceding theme release; no data migration or historical repair is performed by this change.

## v1.11.6 — Retire unpaid-order timers and simplify order history

- Removes the custom pending-payment enrolment, reminder email registration, gateway timeout checks and automatic Failed transition. No new pending timer jobs are created and the retired hook has no worker. Cart coupon notice remains. The dormant old email source/settings and all previous email/expiry/lock/snapshot markers are retained for audit, not loaded into active email settings.
- Explicit admin cleanup clears only `loomara_pending_check_114` events. No order record, payment, refund or customer data is deleted. Previously enrolled orders retain the existing native hold-stock cancellation guard so retirement itself does not expose them to an unexpected cancellation. Native policy for untracked orders remains unchanged.
- Removes only Hostinger server cron job EqNoZQjZuj on account u384983524, after owner confirmation. Configuration is recorded in the previous release/agent notes and can be recreated. Core traffic-triggered WordPress cron remains enabled for existing refunds, fulfilment notifications and delivery completion. No unrelated scheduled hook is removed.
- Unpaid Pending payment, checkout Draft and Failed attempts are excluded from all customer order-list queries before pagination, with a defensive template guard. Pending/Draft filter options are removed; old filter URLs resolve to All orders. Recorded paid/refunded records and accepted offline/COD statuses remain visible. WooCommerce admin records and authorised direct order access are unchanged.
- Orders now use a filled navy View order button and an outlined Cancel order control with an explicit confirmation panel. Existing owner/nonce/pre-dispatch checks, native order URLs, cancellation and refund behaviour remain unchanged. Keyboard focus, minimum 44px targets and reduced-motion styles retained.
- 396 isolated backend/regression checks and six native-template assertions passed (402 total). Synthetic mobile 390px and desktop 1366px checks include confirmation-panel layout; no live cancellation, payment, refund or email performed during QA. No order renumbering.
- Rollback: review retained timer/email markers and removed cron configuration before restoring v1.11.5. Never automatically replay reminders, expiry events or financial jobs. Restoring theme code alone does not recreate the deleted server cron job or cleaned timer events.

## v1.11.5 — Five-minute reminders and clearer order history

- Revises enrolled Pending Razorpay deadlines to first-pending +5 minutes for the single customer reminder and +30 minutes for a verified-unpaid Failed transition. Preserves existing gateway identity/payment checks, at-most-once email/expiry markers, locks and callbacks. Missing/ambiguous payment confirmation still pauses for review; no capture/refund/cancellation call is added. No stale reminder after 30 minutes. Scheduling/inbox delays can occur; no five-minute inbox guarantee.
- Explicit administrator-only schedule-retiming page snapshots old jobs before moving already-enrolled unpaid Pending orders to the revised original-start deadlines. No historical enrolment, renumbering, duplicate reminder or immediate status/email action by the button. Due tasks can run on the next cron opportunity; active locks, large batches or partial attempts require review.
- My Account Orders defaults to All orders. Pending payment includes checkout drafts; separate Draft option is removed and draft badges read Pending payment. Native status storage, ownership, payment actions and order IDs are not rewritten.
- Failed orders with no paid date/refund records are excluded before customer-list pagination and defensively skipped by the template, including All orders and Failed filters. Paid/refunded financial records stay visible. All records remain in WooCommerce admin and authorised direct order access is unchanged.
- Removes only the repeated product-detail shipping/returns policy paragraph. Shipping/return pages, structured data and destination-specific delivery panels remain unchanged.
- 441 local mocked timer/history/retiming and refund/cancellation/tracking/coupon/timestamp checks plus three native template-render assertions pass (444 total). Synthetic Orders preview at actual 390px/1366px has no overflow; five changed PHP files parse. No actual customer email, payment, refund or live order status change is used for QA. Live preflight confirms the reminder is enabled/HTML, but live still has v1.11.4 timing until publication. No Hostinger server cron existed during preflight; every-minute runner requires separate approval. Real timed inbox receipt remains acceptance testing.
- Owner approved deployment and enrolled-schedule retiming, choosing a five-minute server scheduler rather than every minute. A fixed HTTPS WordPress-cron request every five minutes avoids depending solely on traffic; usual reminder processing is around minute 5–10 and expiry verification around minute 30–35, subject to worker/network delays. Existing native cron locking and all payment safeguards remain; actual inbox timing is not guaranteed.
- Rollback to v1.11.4 restores 15/60 logic and successful-order default; review the saved schedule snapshot and queued deadlines before reverting. Preserve all durable email/expiry markers and existing references. Hostinger scheduler configuration, if separately approved, is external to the theme release.

## v1.11.4 — Pending-payment reminders and checkout offer note

- New orders first observed Pending after installation receive fixed 15-minute reminder and one-hour expiry jobs. No historical scan or renumbering. Only positive-total, unpaid Razorpay orders still Pending qualify; COD, offline, paid, refunded, cancelled and fulfilment orders are skipped. Repeated hooks cannot extend the first deadline. WP-Cron can execute late, never intentionally early; stale reminders after one hour are skipped.
- Before either action, fixed HTTPS read-only Razorpay order/payment queries verify the saved native gateway mapping, INR amount, zero paid/due reconciliation and complete attempt list. Captured, authorised, refunded, unknown or unmatched payments, missing mapping, changed order identity or service failures block automation. At expiry, inconclusive cases receive one internal review note rather than a false Failed status. No gateway capture, cancellation or refund is submitted.
- Reloads persistent order fields after gateway checks; serial worker lock, durable at-most-once email/expiry markers and exact first deadlines limit duplicate work. Native hold-stock automatic cancellation is suppressed only for enrolled Razorpay orders so it cannot conflict with this verified Failed transition. Normal gateway callbacks and future payment completion remain enabled. Uncertain/crashed mail/worker markers require manual review, not blind replay.
- Native WooCommerce customer email setting “Loomara — Pending payment reminder” is enabled by default; branded HTML/plain content includes the native secure payment URL and an already-paid/do-not-pay-again warning. Failed or uncertain mail transport is noted without automatic resend. SMTP credentials/settings remain unchanged; real inbox delivery is acceptance testing.
- Adds a responsive cream/gold cart notice explaining that applicable offers are shown at checkout and an eligible coupon can be selected before payment. Classic/Magic cart and Woo cart-block hooks supported, with duplicate rendering protection.
- 55 isolated pending-order/email/API/timing/duplicate assertions plus 355 refund/cancellation/tracking/coupon regressions passed (410 total), with mocked HTTP/orders/email only. Synthetic cart/email preview checked at actual 390px and 1366px without overflow. No real email, payment, order transition or refund used in QA.
- Rollback to v1.11.3 removes the future workers and cart note; retain order metadata and all email/worker markers. Review queued events and pending orders explicitly before replay or changing timeout policy. Existing IDs/references and financial/refund logic are unchanged.

## v1.11.3 — Public checkout offers and delivery panels

- Adds explicit administrator-only offer setup: FLAT5 = 5% capped at ₹500 through 31 October 2026; DIWALI = 10% capped at ₹500 through 15 November 2026. Cutoffs are 23:59:59 India time. New offers are individually usable, not stacked. Public descriptions allow native Magic Checkout discovery. Retains WELCOME10 settings/usage history and adds its first-order description; moves duplicate NEWUSER to recoverable Trash. Takes a private snapshot first and stops on identity collisions or a prior attempt. No automatic frontend migration.
- Server-side WooCommerce discount-instance cap protects both cart and order calculations, including native Magic Checkout calculations. Keeps the advertised percentage intact; tax-exclusive bases include tax for the cap and use a minor-unit rounding reserve so the combined benefit cannot exceed ₹500. Existing unrelated coupons are untouched.
- Native authenticated Magic Checkout list response is reduced to approved, natively valid offers only. WELCOME10 requires verified customer ownership and no prior eligible orders, excluding the checkout order itself. No user impersonation/email-based account matching; no new public coupon-list endpoint or authentication bypass.
- Removes only the product-page native Razorpay Buy Now callback. Cart/checkout payment methods, Add to cart/View cart and existing order references are untouched.
- Product delivery panel privately reads the current customer's default India PIN via nonce-protected AJAX. Uses chosen variation/quantity and real package weight for the existing fixed Shiprocket serviceability endpoint. Manual PIN changes do not change saved addresses; option changes refresh estimates and stale browser responses are discarded. Plugin-absent fallback remains separate and is not presented as courier verification.
- Product and cart estimates use compact highlighted result panels with space below, success/error styling and estimate disclaimers. Verified synthetic local preview at actual 390px and 1366px without horizontal overflow. 186 coupon +19 delivery mocked backend checks passed; no actual customer address, coupon, order, payment or live courier request used during preparation.
- Rollback: stop advertising/coupon usage and review the private offer snapshot before reverting code to v1.11.2. Never leave capped percentage coupons active without the cap implementation. Restore NEWUSER from Trash only on explicit direction. No historical order recalculation or renumbering.

## v1.11.2 — Shiprocket tracking in Shipped email

- Future Shipped notifications queue a read-only tracking lookup before sending. Exact WooCommerce channel/order mapping and shipment ownership are verified using the existing preflight; no email/address matching or historic email resend.
- Adds verified AWB, courier name and a Track your order button to HTML, with tracking links in plain text. Only HTTPS Shiprocket-domain URLs returned for the matching AWB are accepted; no invented URLs, remote redirects or fetching response-supplied links. Multiple shipments retain their separate links.
- Checks after approximately 1, 3, 8 and 18 minutes (WP-Cron can run later). If details remain unavailable, sends the normal Shipped email with available verified tracking and adds an internal note. Never sends a second automatic email when details later arrive. Delivered/cancelled/refunded/completed orders skip stale Shipped notifications.
- Durable existing email markers and a per-order worker lock protect duplicate callbacks/concurrent workers. Crashed/uncertain workers require review, not blind replay. Email settings, delivery completion, orders, shipment states and financial behaviour remain unchanged.
- 72 isolated assertions passed (41 lifecycle regressions plus 31 tracking/queue/URL/identity checks); HTTP, credentials, orders and email are mocked. No real customer email or live Shiprocket lookup performed during preparation.
- Rollback to v1.11.1 removes tracking enrichment. Retain mail/job/lock markers; review queued jobs before replay to avoid duplicate notifications.

## v1.11.1 — Independent fulfilment-email settings

- Gives Shipped and Delivered notifications separate concrete WooCommerce email classes so their management links and preview selectors resolve independently. Sending, deduplication, return holds and seven-day completion rules remain unchanged.
- All 41 isolated lifecycle/email assertions pass. No customer email or real order transition is used for deployment verification.
- Rollback: v1.11.0 retains lifecycle behaviour but its two email-management links/previews are ambiguous; prefer v1.11.1 for administration.

## v1.11.0 — Shipped/Delivered emails and seven-day completion

- Adds native WooCommerce customer Shipped and Delivered email settings/notifications through the transactional email dispatcher. Uses the existing WooCommerce mail transport and branding. Includes order reference, account link and saved tracking numbers only; no fabricated tracking links. Delivered email explains eligible seven-calendar-day returns and links the policy/contact pages.
- Durable per-order/status at-most-once automatic email markers suppress duplicate courier callbacks. Failed/uncertain mail transport is noted for review, not blindly retried. Late Shipped messages are skipped after a recorded delivery. Actual inbox receipt is not claimed from SMTP acceptance or isolated tests.
- First future Delivered transition records its observation time and schedules WP-Cron completion after seven calendar days. Duplicate delivery callbacks cannot extend the deadline. No historical delivery dates, retroactive emails or order backfill. Jobs may run after the deadline on the next cron opportunity, never intentionally before it.
- Completion rechecks Delivered status, recorded payment (unless zero total), refund records, administrator return/dispute hold and integration hold filter. Order admin exposes a hold checkbox; return requests arriving via email/external systems must be marked on hold there. Clearing a hold reschedules a check. No refund/payment/shipping changes are made by this lifecycle job.
- Automatic post-delivery completion suppresses the native Completed/shipped email to avoid misleading customers seven days later. Manual/native completion email behaviour remains unchanged for orders without the auto-completion marker.
- 41 isolated lifecycle/email assertions passed with fake orders and intercepted mail; earlier cancellation/refund/status/security suites remain regression checks. Deployment verification does not send real customer emails or transition real orders.
- Rollback: v1.10.0 code stops the lifecycle/email handlers; retained timestamp/mail markers prevent invented delivery dates or blind resend on reactivation. Retain hold/timestamp metadata and review any scheduled completion before re-enabling. Already completed orders are not automatically reverted.

## v1.10.0 — Verified automatic cancellation refunds

- Explicit administrator activation applies to future Cancelled transitions only, including customers, team and external integrations. No historical scan/backfill. Existing customer Processing cancellation remains owner/nonce/tracking/status protected; Ready to ship/Shipped/Delivered cancellation eligibility is not broadened.
- Durable per-order intent and non-expiring concurrent-worker lock; background WP-Cron verification uses exact native/public reference, verified WooCommerce channel, fresh order AND shipment statuses. Dispatch/unknown/missing/ambiguous matches fail closed. Shiprocket cancellation acknowledgement is not final confirmation; cancellation is submitted once then independently polled before any payment refund.
- Razorpay captured INR payment must match the saved transaction ID, gateway order ID, amount and optional native-order notes. Existing processed partial refunds must reconcile with WooCommerce. Unknown/pending/refund balance conflicts require review. Only the remaining amount is submitted as a normal original-method refund with a stable receipt/body and X-Refund-Idempotency.
- Uncertain submission is reconciled GET-only, never blindly resubmitted. Only a verified processed refund creates the WooCommerce ledger without another gateway refund or restock. Stable reason/refund ID and native Razorpay refund_from_website note prevent duplicate bookkeeping. Pending refunds are polled; failed/unknown/expired cases require manual review in order notes.
- Feature is off until explicitly enabled on WooCommerce → Automatic refunds. Master off stops workers but retains durable intent for review. A crash-held lock must be investigated, not automatically stolen. No refunds/cancellations/real payment transactions are made during deployment verification.
- 58 isolated automatic-refund assertions plus 39 cancellation, 20 fulfilment-status, 19 Shiprocket-matching and 16 secure-connection assertions passed using fake orders/HTTP only. Installed Razorpay 4.8.8 source confirms dynamic order metadata and the native refund_from_website webhook duplicate guard. Real end-to-end money movement is not performed during QA.
- Rollback: disable automation first and reconcile every submitted/pending intent against Razorpay before reverting code. Do not delete job/lock metadata or retry money movements blindly. v1.9.10 restores previous manual-refund behaviour but does not reverse refunds already submitted.

## v1.9.10 — Ready to ship status

- Registers wc-ready-to-ship between Processing and Shipped for native administration, REST updates and customer order filters. Includes it in the account's accepted/fulfilment view without treating readiness alone as proof of payment; recorded payment dates remain required for paid-purchase eligibility.
- Approved Shiprocket channel 12607102 Ready to Ship mapping becomes ready-to-ship. Prior shipped/delivered mappings and all other channel settings remain unchanged. No historical order backfill, payment or stock migration. Existing cancellation rules remain unchanged, including rejection of the new fulfilment state.
- Twenty isolated status assertions plus cancellation regressions cover payment independence and existing status preservation. No live order transitions, cancellation or refunds during verification; actual status push awaits future courier events.
- Rollback: restore Ready to Ship mapping to its previous Ready to Ship value before code rollback; retain registration if orders already use the new status. Never bulk-relabel existing orders.

## v1.9.9 — Separate Shipped and Delivered statuses

- Registers native wc-shipped and wc-delivered statuses for WooCommerce admin, REST updates and customer order filters, without relabelling Completed or migrating historical orders.
- Shipping is not proof of payment: these statuses preserve is_paid only when a payment date was already recorded. They are not added to global paid-status lists. Paid delivered purchases remain eligible for ratings; testimonial queries include shipping states and still verify payment.
- Approved Shiprocket channel 12607102 mapping: Shipped, In Transit and Out for Delivery to shipped; Delivered to delivered. All other mappings/settings, including disabled inventory sync, remain unchanged. Configuration acceptance and live-source checks are performed after publication; actual courier transitions require future events.
- Seventeen isolated status assertions passed. Existing customer cancellation rejects Shipped/Delivered; no live order transition, payment, refund or testimonial submitted during QA.
- Rollback: restore the saved Shiprocket mapping before code rollback. Orders already using custom statuses must retain their registration or be explicitly reviewed; never bulk-relabel them Completed or infer delivery/payment from old status values. No stock or order-number migration.

## v1.9.8 — Cancellation confirmation fatal-error repair

- Fixes the logged undefined wc_add_notice fatal error after customer cancellation through admin-post.php. The admin handler queues only an allowlisted, short-lived, customer-scoped outcome; the account frontend consumes it once using WooCommerce's normal notice/session lifecycle.
- Existing ownership, nonce, fresh status, tracking and concurrent-request checks remain unchanged. Paid cancellations still require manual refunds; no refund automation or historical order changes.
- 39 isolated regression assertions passed, including admin requests without frontend notice availability, customer isolation and single-use confirmation. No live cancellation or refund performed during verification.
- Rollback: restore v1.9.7 theme code. No database migration; expiring confirmation notices require no restoration. Do not retry a previously cancelled order solely because its confirmation failed.

## v1.9.7 — Parcel dimensions and India PIN autofill

- Explicit, administrator-triggered snapshot migration sets physical products and variations to packed parcel dimensions of 30 × 20 × 5 cm, converted to the configured WooCommerce dimension unit. Preserves weights, prices, stock, images and product identities. No historical orders are edited. Future new physical products default to these dimensions when all three fields are blank.
- Organised local inventory metadata records package dimensions separately from unconfirmed textile dimensions. Customer-facing dimensions are labelled Packed parcel dimensions.
- Saved-address and classic checkout PIN fields can suggest city, Indian state/UT and country using a fixed server-side Shiprocket locality endpoint. Only the PIN is sent; credentials are not exposed. Nonce/rate limits, short encrypted token cache, successful-location cache and manual fallback are retained. Conflicting customer-entered values require explicit opt-in before replacement; stale responses are ignored. Razorpay-hosted forms are not modified.
- Eighteen isolated backend assertions passed; mocked browser Bengaluru/Mumbai autofill, manual-value preservation, explicit replacement and 390px no-overflow checks passed. Live Shiprocket lookup remains part of deployment verification; no customer address saved during QA.
- No refund automation, shipping-status changes, stock changes or payment changes.
- Rollback: restore v1.9.6 theme code. Product dimensions require an explicit snapshot-based restoration; code rollback alone does not change saved dimensions. Never restore snapshot prices or stock over newer live values.

## v1.9.6 — Read-only Shiprocket order matching

- Private administrator preflight matches the exact native/public order reference within the single verified WooCommerce channel. Revalidates Shiprocket order detail and every shipment identity; duplicate, missing, foreign-channel, return/exchange and unknown responses fail closed.
- Uses saved encrypted credentials only server-side, fixed HTTPS authentication/read endpoints and no redirects. Results contain reference/status identifiers only, not customer contacts or tokens. Administrator/nonce checks and request limits protect the form.
- Nineteen isolated mocked tests passed. Owner approved a live read-only check for OD-202610-000155, replacing the originally proposed 156.
- No cancellation/refund APIs or activation controls; existing payment, order, credential and cancellation behaviour unchanged. Automatic refunds remain disabled pending verified live shipment mapping.
- Rollback: restore v1.9.5 code. No order migration or financial transaction to reverse.

## v1.9.5 — Private Shiprocket connection setup

- Administrator-only WooCommerce > Shiprocket connection page with nonce-protected submission. Owner enters dedicated API-user credentials directly; fields are never prefilled or echoed back.
- Credentials are encrypted using AES-256-GCM and the WordPress authentication salt. Successful fixed-endpoint HTTPS authentication and read-only channel access are required before saving. Failed verification preserves previous credentials; raw API errors and bearer tokens are not displayed or stored.
- Setup only: no shipment lookup, cancellation, automatic refund, payment changes or historical-order updates. Automatic refunds remain disabled pending connection and exact channel/order matching verification.
- Sixteen isolated security assertions passed, including encryption/tampering, permission/nonce checks, failure preservation and absence of cancellation/refund API calls. No real credentials or live transactions used during QA.
- Rollback: restore v1.9.4 code. Saved encrypted connection data remains private in WordPress; rotating authentication salts requires reconnecting.

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
