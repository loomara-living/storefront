(() => {
  // Both inputs use the real public search page, including its fuzzy/category/SKU
  // matching and visibility rules. Returned scripts are never inserted or run.
  document.querySelectorAll('#header-search-panel form, #collection-search').forEach((form, index) => {
    const input = form.querySelector('input[name="s"]');
    if (!input) return;
    const panel = document.createElement('section');
    panel.className = 'live-product-search';
    panel.id = `live-product-search-${index}`;
    panel.hidden = true;
    panel.setAttribute('aria-label', 'Matching products');
    const status = document.createElement('p');
    status.className = 'live-search-status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    const list = document.createElement('ul');
    const more = document.createElement('button');
    more.type = 'button';
    more.className = 'live-search-more';
    more.textContent = 'Show more matches';
    more.hidden = true;
    const all = document.createElement('a');
    all.className = 'live-search-all';
    all.textContent = 'View all matching products';
    panel.append(status, list, more, all);
    form.append(panel);
    input.setAttribute('aria-controls', panel.id);
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('autocomplete', 'off');
    let timer, controller, generation = 0, next = null, loading = false;
    const seen = new Set();
    const visited = new Set();
    function close() {
      clearTimeout(timer);
      generation++;
      controller?.abort();
      loading = false;
      panel.hidden = true;
      input.setAttribute('aria-expanded', 'false');
    }
    function localUrl(raw, base) {
      try {
        const url = new URL(raw, base);
        return url.origin === location.origin && /^https?:$/.test(url.protocol) ? url : null;
      } catch { return null; }
    }
    async function load(url, token, append = false) {
      if (loading || token !== generation) return;
      loading = true;
      more.disabled = true;
      controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 15000);
      status.textContent = append ? 'Loading more matches…' : 'Searching products…';
      try {
        const response = await fetch(url.href, {credentials: 'same-origin', signal: controller.signal, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        if (!response.ok || !(response.headers.get('content-type') || '').includes('text/html')) throw Error('Search unavailable');
        if(response.url && !localUrl(response.url, url.href)) throw Error('Foreign search response');
        const html = await response.text();
        if (token !== generation) return;
        const page = new DOMParser().parseFromString(html, 'text/html');
        const results = page.querySelector('[data-catalog-browser]');
        if (!results) throw Error('Search unavailable');
        results.querySelectorAll('ul.products > li.product').forEach(card => {
          const source = card.querySelector('a.woocommerce-LoopProduct-link');
          const title = card.querySelector('.woocommerce-loop-product__title')?.textContent?.trim();
          const target = source && localUrl(source.getAttribute('href'), response.url || url.href);
          if (!title || !target || seen.has(target.href)) return;
          seen.add(target.href);
          const row = document.createElement('li');
          const link = document.createElement('a');
          link.href = target.href;
          const sourceImage = source.querySelector('img');
          if (sourceImage) {
            try {
              const src = new URL(sourceImage.getAttribute('src'), response.url || url.href);
              if (src.protocol === 'https:' || (src.protocol === 'http:' && src.origin === location.origin)) {
                const image = document.createElement('img');
                image.src = src.href; image.alt = ''; image.width = 56; image.height = 56; image.loading = 'lazy';
                link.append(image);
              }
            } catch { /* Product title/link remain usable without an image. */ }
          }
          const text = document.createElement('span');
          const name = document.createElement('strong'); name.textContent = title;
          const price = document.createElement('span'); price.className = 'live-search-price';
          price.textContent = card.querySelector('.price')?.textContent?.trim() || '';
          text.append(name, price); link.append(text); row.append(link); list.append(row);
        });
        const following = results.querySelector('[data-catalog-next]')?.getAttribute('href');
        visited.add(url.href);
        next = following ? localUrl(following, response.url || url.href) : null;
        if (next && visited.has(next.href)) next = null;
        more.hidden = !next;
        const total = Number(results.dataset.total);
        status.textContent = seen.size ? `${seen.size}${Number.isFinite(total) && total > seen.size ? ` of ${total}` : ''} matching products${next ? ' — more available below' : ''}.` : 'No matching products. Try another word.';
      } catch (error) {
        if (token !== generation) return;
        status.textContent = 'Could not load matches. Press Search or use View all matching products.';
        more.hidden = !next;
      } finally {
        clearTimeout(timeout);
        if (token === generation) { loading = false; more.disabled = false; }
      }
    }
    input.addEventListener('input', () => {
      close();
      const phrase = input.value.trim();
      if (phrase.length < 2) return;
      const target = localUrl(form.getAttribute('action') || '/', location.href);
      if (!target) return;
      target.search = ''; target.hash = '';
      target.searchParams.set('s', phrase); target.searchParams.set('post_type', 'product');
      all.href = target.href;
      next = null; seen.clear(); visited.clear(); list.replaceChildren(); more.hidden = true;
      panel.hidden = false; input.setAttribute('aria-expanded', 'true');
      status.textContent = 'Searching products…';
      const token = generation;
      timer = setTimeout(() => load(target, token), 300);
    });
    more.addEventListener('click', () => { if (next && !loading) load(next, generation, true); });
    form.addEventListener('submit', close);
    input.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
    document.querySelector('[data-search-close]')?.addEventListener('click', close);
    document.addEventListener('click', event => { if (!form.contains(event.target)) close(); });
  });
})();
