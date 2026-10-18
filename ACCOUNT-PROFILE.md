# Account experience update — v1.7.6

Deployed at 0b3399f9752df45753d15e23b240cc03cfc7faca with matching GitHub
release. Public signup checks passed. After separate permission and sign-in,
the live dashboard's name/mobile/email and all ten avatar controls were verified
without changing customer details or avatar selections.

- Signup mobile is optional and may be shared; native WooCommerce email uniqueness remains enforced.
- Provided mobile values still undergo validation. Checkout requires a valid Indian mobile contact.
- Account details also accepts shared mobile contacts. Email and username remain unambiguous login identifiers.
- Mobile lookup checks all billing contacts, including legacy/unindexed records; shared numbers do not select an account.
- Signup uses native WooCommerce processing through a same-origin POST. Errors update a text-only message without replacing the form or saving passwords in storage.
- Password fields have show/hide controls, reusing native WooCommerce controls when available.
- Dashboard shows name, billing mobile and registered email; editing remains in Account details.
- Ten local vector avatars (five male, five female) plus initials. Allowlisted choice is stored privately per user with nonce-protected authenticated saving.

Local validation: ten PHP integration assertions passed (optional/shared/invalid
signup mobile, unique email, mandatory checkout mobile, avatar count/grouping,
allowlist, SVG rendering, ambiguous mobile lookup). Browser checks exercised OTP
error feedback without navigation, show/hide password, dashboard fields and avatar
save/reload persistence on previewcustomer. No real email or live account changes.

Deployment scope: functions.php include; inc/store-setup.php mobile handling;
inc/account-mobile.php shared contacts; inc/account-profile.php; assets/account-profile.js
and assets/account-profile.css; version/release notes. Do not publish unrelated
pending local launch edits or QA fixtures.
