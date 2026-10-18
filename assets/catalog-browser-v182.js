(() => {
  const root = document.querySelector('[data-catalog-browser]');
  if (!root) return;
  const grid = root.querySelector('ul.products');
  const link = root.querySelector('[data-catalog-next]');
  const status = root.querySelector('[data-catalog-status]');
  const toggle = root.querySelector('[data-catalog-auto]');
  if (!grid || !link || !status) return;
  const total = Number(root.dataset.total) || 0;
  let shown = Number(root.dataset.shown) || grid.children.length;
  let loading = false;
  let failed = false;
  const visited = new Set();
  const key = card => card.querySelector('.woocommerce-LoopProduct-link')?.href;
  const existing = new Set([...grid.children].map(key).filter(Boolean));
  const checkbox = toggle?.querySelector('input');
  let observer;

  async function loadMore() {
    if (loading || link.hidden) return;
    const next = new URL(link.href, location.href);
    if (next.origin !== location.origin || visited.has(next.href)) return;
    loading = true;
    failed = false;
    root.setAttribute('aria-busy', 'true');
    link.setAttribute('aria-disabled', 'true');
    link.textContent = 'Loading…';
    status.textContent = 'Loading more products…';
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 20000);
    try {
      const response = await fetch(next.href, {credentials:'same-origin', signal:controller.signal, headers:{'X-Requested-With':'XMLHttpRequest'}});
      if (!response.ok || !(response.headers.get('content-type') || '').includes('text/html')) throw Error('Unable to load catalogue');
      const page = new DOMParser().parseFromString(await response.text(), 'text/html');
      const results = page.querySelector('[data-catalog-browser]');
      const cards = results?.querySelectorAll('ul.products > li.product');
      if (!cards?.length) throw Error('No product cards in response');
      let added = 0;
      cards.forEach(card => {
        const id = key(card);
        if (!id || existing.has(id)) return;
        card.querySelectorAll('script').forEach(script => script.remove());
        grid.append(document.importNode(card, true));
        existing.add(id);
        added++;
      });
      if (!added) throw Error('No new products in response');
      visited.add(next.href);
      shown = Math.min(total, shown + added);
      root.dataset.shown = String(shown);
      const count = root.querySelector('.woocommerce-result-count');
      if (count) count.textContent = `Showing ${shown} of ${total} results`;
      status.textContent = shown >= total ? `All ${total} matching products are loaded.` : `${shown} of ${total} products loaded.`;
      const following = results.querySelector('[data-catalog-next]');
      if (following && shown < total) {
        const followingUrl = new URL(following.getAttribute('href'), next.href);
        if (followingUrl.origin !== location.origin || visited.has(followingUrl.href)) throw Error('Invalid next page');
        link.href = followingUrl.href;
      } else {
        observer?.disconnect();
        if (document.activeElement === link) { status.tabIndex = -1; status.focus({preventScroll:true}); }
        link.hidden = true;
        if (toggle) toggle.hidden = true;
      }
      document.dispatchEvent(new CustomEvent('loomara:catalog-appended', {detail:{added}}));
    } catch {
      failed = true;
      status.textContent = 'Could not load more products. Try again, or open the next page.';
      if (!root.querySelector('[data-catalog-fallback]')) {
        const fallback = document.createElement('a');
        fallback.dataset.catalogFallback = '';
        fallback.textContent = 'Open next page';
        fallback.href = next.href;
        fallback.className = 'catalog-clear';
        status.append(' ', fallback);
      }
    } finally {
      clearTimeout(timeout);
      loading = false;
      root.removeAttribute('aria-busy');
      link.removeAttribute('aria-disabled');
      link.textContent = failed ? 'Try loading again' : 'Load more products';
      // Re-observe after appending so a tall viewport can continue loading.
      if (observer && !link.hidden && !failed) { observer.unobserve(link); observer.observe(link); }
    }
  }
  link.addEventListener('click', event => {
    if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button) return;
    event.preventDefault();
    loadMore();
  });
  if ('IntersectionObserver' in window) {
    if (toggle) toggle.hidden = false;
    observer = new IntersectionObserver(entries => {
      if (entries.some(entry => entry.isIntersecting) && checkbox?.checked && !failed) loadMore();
    }, {rootMargin:'160px 0px', threshold:0});
    observer.observe(link);
    checkbox?.addEventListener('change', () => {
      failed = false;
      observer.unobserve(link);
      if (checkbox.checked && !link.hidden) observer.observe(link);
    });
  }
})();
