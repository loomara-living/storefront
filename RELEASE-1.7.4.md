# v1.7.4 — Account mobile number

- Adds Mobile number to My Account → Account details using the WooCommerce billing phone.
- Saves the contact in the account and synchronizes the existing mobile-login lookup.
- Validates Indian mobile numbers, with optional +91 prefix.
- Requires a valid mobile contact at classic and block checkout, including Google-created accounts.
- A saved mobile contact is reused by existing checkout prefill; customers can edit it.
- No OTP verification is claimed. No Google or SMTP credentials are changed.
- Other pending local launch changes remain excluded.

Rollback: restore v1.7.3 with a new revert commit on main. Saved phone values remain in WooCommerce.
