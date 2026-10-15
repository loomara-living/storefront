const menuButton = document.querySelector('[data-menu-button]');
const navigation = document.querySelector('[data-nav]');
const header = document.querySelector('[data-header]');

if (menuButton && navigation) {
  menuButton.addEventListener('click', () => {
    const open = navigation.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.querySelector('.sr-only').textContent = open ? 'Close menu' : 'Open menu';
  });
}

window.addEventListener('scroll', () => header?.classList.toggle('scrolled', window.scrollY > 12), { passive: true });

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const reveals = document.querySelectorAll('.reveal');
if (prefersReducedMotion || !('IntersectionObserver' in window)) {
  reveals.forEach((element) => element.classList.add('visible'));
} else {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });
  reveals.forEach((element) => observer.observe(element));
}

document.querySelectorAll('[data-newsletter]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const note = form.parentElement.querySelector('[data-form-note]');
    if (note) note.textContent = 'Thanks for your interest. Signup will be activated before the site goes live.';
    form.reset();
  });
});

if (window.jQuery) {
  window.jQuery(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed', () => {
    const cart = document.querySelector('.header-cart');
    if (!cart) return;
    cart.classList.remove('cart-updated');
    window.requestAnimationFrame(() => cart.classList.add('cart-updated'));
    window.setTimeout(() => cart.classList.remove('cart-updated'), 500);
  });
}
