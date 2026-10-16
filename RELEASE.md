# Loomara Living v1.4.0

Sequential sign-in and registration, explicit password validation, a saved account
wishlist, India-only checkout and address forms, compact commerce headings,
four-column desktop/two-column mobile products, resilient product galleries,
expanded India policies and an India transactional-email footer.

Categories follow the home hero. The editorial introduction is the penultimate
home section. Product reviews are unpublished (not deleted) and ratings hidden.

## Recovery

The annotated `v1.3.0` tag records the GitHub theme immediately before this release.
Redeploy that tag to restore the prior code. No new release branches are needed.

The migration saves original options in `loomara_before_1_4_0_options`, original
policy content in `_loomara_policy_before_1_4_0` post metadata and revisions, and
previous review statuses in `_loomara_previous_status_1_4_0` comment metadata.
These are database changes; redeploying old code alone does not undo them.
To reverse them too, first redeploy the prior theme, then run the supplied
`scripts/rollback-1.4.0.php` through WP-CLI, using a retained local copy.
The script must never be run through a web request.

The business's exact postal address and legal registration details must be
maintained by its owner in WooCommerce settings. No address is invented by this
release. Customer addresses are never overwritten in the database.

The working Gmail From Email configuration is not modified.

## Validation

- PHP syntax: all 11 PHP files; JavaScript syntax; clean whitespace diff.
- Disposable WordPress/WooCommerce: 16 functional checks passed, including
  server-side password rejection, native password hashing, India country lists,
  policy migration, backup data and reversible review unpublishing.
- Browser: registration, customer login, address saving, persistent wishlist,
  block checkout with saved billing address and email, visible product gallery,
  hidden duplicate cart unit price and homepage section ordering.
- Responsive checks: four columns at 1366px, two at 390px, no horizontal overflow.
- No live orders, payments or customer test emails were submitted.
