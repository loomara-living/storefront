# v1.8.3 — Responsive category shopping and filters

- Larger desktop homepage category destinations in a two-by-two grid; mobile category layout retained.
- Desktop catalogue filters in a left sidebar; mobile floating Filters & sort button opens an accessible modal drawer.
- Preserve category/size filtering, sorting and infinite-scroll/manual fallback.
- Mobile product colour and size options appear directly below the price, ahead of the short description.
- Display-only release; no changes to product records, stock, orders, customers or payments. QA fixtures remain local.

Rollback: revert this release commit on main and publish a matching patch release, restoring v1.8.2 theme code. No database rollback is required.
