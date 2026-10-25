// Move the existing hero, not a copy, so focus order follows the mobile layout.
(() => {
  const main = document.querySelector('.home-main');
  const hero = main?.querySelector(':scope > .hero');
  if (!main || !hero) return;
  const marker = document.createComment('Original homepage hero position');
  hero.before(marker);
  const mobile = window.matchMedia('(max-width:620px)');
  const positionHero = () => {
    // Do not move a focused control out from under a keyboard user on resize.
    if (hero.contains(document.activeElement)) return;
    if (mobile.matches) main.append(hero);
    else marker.after(hero);
  };
  positionHero();
  mobile.addEventListener('change', positionHero);
  hero.addEventListener('focusout', () => queueMicrotask(positionHero));
})();

// Animate only visible category artwork, without zooming/cropping its text.
(() => {
  const cards = [...document.querySelectorAll('.home-category-section .category-card-artwork')];
  if (!cards.length || !('IntersectionObserver' in window)) return;
  const visible = new Set();
  const update = () => cards.forEach(card => card.classList.toggle('category-art-in-view', !document.hidden && visible.has(card)));
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => entry.isIntersecting ? visible.add(entry.target) : visible.delete(entry.target));
    update();
  }, {threshold: .12});
  cards.forEach(card => observer.observe(card));
  document.addEventListener('visibilitychange', update);
})();
