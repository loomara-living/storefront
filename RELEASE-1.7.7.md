# v1.7.7 — Responsive launch offer

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
