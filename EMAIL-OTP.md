# Email OTP signup verification

Deployed in v1.7.5 at caf248f1e1cd41ad2763ca778bc33025c3380fb5.
Normal WooCommerce My Account signup
requests a six-digit code, then submits that code with the normal registration
form. No account is created until the server validates and consumes the code.
Google signup and checkout-created accounts are separate paths and unchanged.
Existing accounts and password recovery are unchanged.

- Delivery uses wp_mail and the existing SMTP configuration; no new mail keys.
- Codes expire after 10 minutes; five failed attempts invalidate a challenge.
- Sixty-second resend wait, five requests per email per hour, twenty per IP.
- Resending invalidates the previous challenge; codes are single-use.
- The stored code is a keyed digest bound to email and a random challenge token.
- Database locks serialize counters and consumption across concurrent requests.
- Verification metadata records the exact verified email, not a permanent boolean.
- Mail acceptance is not proof of inbox delivery. The owner subsequently confirmed real email OTP signup works end to end.
- No local test endpoints, test codes, QA plugins or fixture accounts may deploy.

Validation: PHP parser and JavaScript syntax checks passed. Seventeen local
assertions passed for issuance, digest-only storage, cooldown, mismatched email,
wrong/correct code, replay, expiry, attempt lockout, email/IP budgets, resend
invalidation, native Woo signup blocking/success, OAuth exemption and mail
failure. The browser send action returned a challenge and disabled resend.
Local mail was suppressed; no inbox delivery is claimed.

Deployment scope: include inc/email-otp.php, assets/email-otp.js,
assets/email-otp.css and the corresponding functions.php include, with version
and release notes. Exclude all other pending local launch changes.
Rollback removes the include or restores the previous theme; verified customer
accounts remain, and no passwords or orders are deleted.
