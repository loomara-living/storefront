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
