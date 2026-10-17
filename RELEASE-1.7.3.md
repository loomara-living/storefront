# v1.7.3 — Google customer login

- Adds the enabled Nextend Google provider to WooCommerce login and registration.
- Uses the provider's branded Google icon and full-width Continue with Google button.
- Returns customers to My Account after authentication.
- Keeps OAuth credentials in WordPress plugin settings, outside Git.
- Existing password login, SMTP, stock, orders and payment configuration unchanged.
- Other local launch changes are not part of this release.

Requires Nextend Social Login with its Google provider configured and enabled.
Rollback: restore the v1.7.2 theme commit on main using a new revert commit.
Disable the Google provider in Nextend to stop social login without removing accounts.
