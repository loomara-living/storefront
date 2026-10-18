# v1.8.2 — Shared Shop/category browsing

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
