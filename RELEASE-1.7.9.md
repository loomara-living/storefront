# v1.7.9 — Remaining reviewed website updates

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
