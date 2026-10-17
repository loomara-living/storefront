# v1.7.8 — Unclipped hero logo

- Moves the hero logo slightly left and down on desktop.
- Gives mobile its own vertical offset so the logo stays inside the artwork rather than being cropped above it.
- Keeps the header text wordmark and artwork caption unchanged.
- Local bounds checks passed at 390px mobile and 1366px desktop; mobile logo has 20px top clearance.
- Other pending local homepage/footer/FAQ/testimonial changes are excluded.

Rollback: restore v1.7.7 (f4acc4d8225f35934fea6e06a66a00066204b0c8)
with a new revert commit on main. No customer, inventory or order data changes.
