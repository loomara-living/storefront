(() => {
  const mobileOrders = window.matchMedia('(max-width: 900px)');
  document.querySelectorAll('.orders-filter-disclosure').forEach((details) => {
    const sync = () => { details.open = !mobileOrders.matches; };
    sync();
    mobileOrders.addEventListener('change', sync);
  });
  const hover = window.matchMedia('(min-width: 768px) and (hover: hover) and (pointer: fine)');
  document.querySelectorAll('.order-status-help').forEach((details) => {
    const summary = details.querySelector('summary');
    let pinned = false;
    const close = () => { pinned = false; details.open = false; };
    details.addEventListener('pointerenter', () => { if (hover.matches) details.open = true; });
    details.addEventListener('pointerleave', () => {
      if (hover.matches && !pinned && !details.contains(document.activeElement)) details.open = false;
    });
    summary.addEventListener('click', (event) => {
      event.preventDefault();
      if (hover.matches) {
        pinned = !pinned;
        details.open = pinned;
      } else {
        details.open = !details.open;
        pinned = details.open;
      }
    });
    details.addEventListener('focusin', () => { if (hover.matches) details.open = true; });
    details.addEventListener('focusout', (event) => {
      if (!details.contains(event.relatedTarget) && !pinned && !details.matches(':hover')) details.open = false;
    });
    details.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') { event.preventDefault(); summary.focus(); close(); }
    });
    document.addEventListener('click', (event) => { if (!details.contains(event.target)) close(); });
    hover.addEventListener('change', close);
  });
})();
