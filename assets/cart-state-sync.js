/* Native read-only cart revalidation; no cart-token, storage or payment changes. */
(() => {
  if (!document.querySelector('.wp-block-woocommerce-cart')) return;
  const data = window.wp?.data;
  if (!data?.dispatch || !data?.resolveSelect) return;
  const key = window.wc?.wcBlocksData?.CART_STORE_KEY || 'wc/store/cart';
  let queued = false, busy = false, last = -Infinity;
  function refresh() {
    queued = false;
    if (document.hidden || busy || Date.now() - last < 3000) return;
    try {
      const actions = data.dispatch(key);
      const selector = data.resolveSelect(key);
      if (!actions?.invalidateResolutionForStore || !selector?.getCartData) return;
      busy = true; last = Date.now();
      actions.invalidateResolutionForStore();
      Promise.resolve(selector.getCartData()).catch(() => {
        // Native Cart Blocks error/retry UI remains authoritative on failure.
      }).finally(() => { busy = false; });
    } catch { busy = false; }
  }
  function queueRefresh() {
    if (queued || document.hidden) return;
    queued = true;
    // Woo's load handler may mark its persisted initial cart resolved. Revalidate
    // after that handler rather than allowing it to overwrite our invalidation.
    setTimeout(refresh, 0);
  }
  if (document.readyState === 'complete') queueRefresh();
  else window.addEventListener('load', queueRefresh, {once:true});
  window.addEventListener('pageshow', e => { if (e.persisted) queueRefresh(); });
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) queueRefresh();
  });
})();
