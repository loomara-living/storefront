# v1.7.5 — Email OTP for normal signup

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
