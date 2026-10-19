/* Server-side new-order gate remains authoritative, including without JavaScript. */
(() => {
  'use strict';
  const selector = '#btn-1cc, #btn-1cc-mini-cart, #btn-1cc-pdp';
  let attempted = false;
  let observer;
  let timeout;
  function prepareButtons() {
    document.querySelectorAll(selector).forEach(button => {
      if (button.textContent.trim() !== 'Continue to payment') button.textContent = 'Continue to payment';
      button.setAttribute('aria-label', 'Continue to payment');
    });
  }
  document.addEventListener('click', event => {
    const button = event.target.closest && event.target.closest(selector);
    const config = window.loomaraCheckoutIdentity;
    if (!button || !config || config.loggedIn || !config.loginUrl) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    window.location.assign(config.loginUrl);
  }, true);
  function resumePayment() {
    const config = window.loomaraCheckoutIdentity;
    if (attempted || !config || !config.loggedIn || !config.resumeIntent || document.readyState !== 'complete') return;
    const button = Array.from(document.querySelectorAll('#btn-1cc')).find(node => !node.disabled && node.getClientRects().length);
    if (!button) return; // Empty cart/missing native integration: never invent a payment.
    attempted = true;
    const key = 'loomara-payment-resumed:' + config.resumeIntent;
    // Remove the navigation trigger before initiating; reload/back cannot replay it.
    const url = new URL(window.location.href);
    url.searchParams.delete('loomara-payment-intent');
    window.history.replaceState(window.history.state, '', url);
    try {
      if (window.sessionStorage.getItem(key)) return;
      window.sessionStorage.setItem(key, '1');
    } catch (_) { return; } // Storage unavailable: retain the manual button, no automatic retry.
    // Reuse the installed gateway's own handler; no direct payment/capture API calls.
    button.click();
  }
  function update() { prepareButtons(); resumePayment(); }
  function start() {
    update();
    observer = new MutationObserver(update);
    observer.observe(document.body, {childList: true, subtree: true});
    timeout = window.setTimeout(() => observer.disconnect(), 15000);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once: true});
  else start();
  window.addEventListener('load', () => window.setTimeout(update, 0), {once: true});
  window.addEventListener('pagehide', () => { if (observer) observer.disconnect(); window.clearTimeout(timeout); }, {once: true});
})();
