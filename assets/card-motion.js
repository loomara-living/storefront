/* One shared timer; only visible cards load their next image. */
(() => {
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  if (!('IntersectionObserver' in window)) return;
  const cards = new Set();
  const seen = new WeakSet();
  const observer = new IntersectionObserver(entries => entries.forEach(e => {
    e.target._loomaraVisible = e.isIntersecting;
  }), {threshold: .35});
  function initialise() {
    document.querySelectorAll('[data-card-photos]').forEach(box => {
      if (seen.has(box)) return;
      seen.add(box);
      let images;
      try { images = JSON.parse(box.dataset.cardPhotos); } catch { return; }
      if (!Array.isArray(images) || images.length < 2) return;
      const img = box.querySelector('img');
      const card = box.closest('li.product');
      if (!img || !card) return;
      const state = {box, img, card, images, index:0, paused:false, busy:false};
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'card-motion-toggle';
      button.textContent = 'Ⅱ'; button.setAttribute('aria-label','Pause product photos');
      button.setAttribute('aria-pressed','false');
      button.addEventListener('click', () => {
        state.paused = !state.paused;
        button.textContent = state.paused ? '▶' : 'Ⅱ';
        button.setAttribute('aria-label', state.paused ? 'Resume product photos' : 'Pause product photos');
        button.setAttribute('aria-pressed', String(state.paused));
      });
      button.hidden = reduced.matches;
      reduced.addEventListener('change', () => { button.hidden = reduced.matches; });
      card.append(button); observer.observe(box); cards.add(state);
    });
  }
  initialise();
  new MutationObserver(initialise).observe(document.body, {childList:true, subtree:true});
  setInterval(() => {
    if (document.hidden || reduced.matches) return;
    cards.forEach(s => {
      if (!s.box.isConnected) { cards.delete(s); observer.unobserve(s.box); return; }
      if (!s.box._loomaraVisible || s.paused || s.busy || s.card.matches(':hover') || s.card.contains(document.activeElement)) return;
      const next = (s.index + 1) % s.images.length;
      const data = s.images[next];
      if (!data || typeof data.src !== 'string') return;
      const url = new URL(data.src, location.href);
      if (!['https:', 'http:'].includes(url.protocol) || url.origin !== location.origin) return;
      s.busy = true;
      const preload = new Image();
      preload.onload = () => {
        if (!s.box.isConnected || reduced.matches || s.paused) { s.busy=false; return; }
        s.box.classList.add('is-changing');
        setTimeout(() => {
          s.img.src = data.src;
          if (data.srcset) s.img.srcset=data.srcset; else s.img.removeAttribute('srcset');
          s.index=next; s.box.classList.remove('is-changing'); s.busy=false;
        }, 180);
      };
      preload.onerror = () => { s.busy=false; };
      preload.src=url.href;
    });
  }, 14000);
})();
