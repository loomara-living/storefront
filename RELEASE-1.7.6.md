# v1.7.6 — Signup and customer profile

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
