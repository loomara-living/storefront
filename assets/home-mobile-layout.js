// Keep mobile artwork at the bottom, but the existing introduction below offers.
(() => {
  const main = document.querySelector('.home-main');
  const hero = main?.querySelector(':scope > .hero');
  if (!main || !hero) return;
  const marker = document.createComment('Original homepage hero position');
  hero.before(marker);
  const copy = hero.querySelector('.hero-copy');
  const copyMarker = document.createComment('Original hero text position');
  const intro = document.createElement('section');
  intro.className = 'home-hero-intro';
  intro.hidden = true;
  if (copy) { copy.before(copyMarker); marker.before(intro); }
  const mobile = window.matchMedia('(max-width:620px)');
  const positionHero = () => {
    // Do not move a focused control out from under a keyboard user on resize.
    if (hero.contains(document.activeElement) || intro.contains(document.activeElement)) return;
    if (mobile.matches) {
      if (copy) { intro.append(copy); intro.hidden = false; }
      main.append(hero);
    } else {
      if (copy) copyMarker.after(copy);
      intro.hidden = true;
      marker.after(hero);
    }
  };
  positionHero();
  mobile.addEventListener('change', positionHero);
  hero.addEventListener('focusout', () => queueMicrotask(positionHero));
  intro.addEventListener('focusout', () => queueMicrotask(positionHero));
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
