const menuButton = document.querySelector('[data-menu-button]');
const navigation = document.querySelector('[data-nav]');
const header = document.querySelector('[data-header]');

const searchToggle = document.querySelector('[data-search-toggle]');
const searchPanel = document.getElementById('header-search-panel');
function setSearchOpen(open) {
  if (!searchPanel || !searchToggle) return;
  searchPanel.hidden = !open;
  searchToggle.setAttribute('aria-expanded', String(open));
  if (open) searchPanel.querySelector('input[type="search"]')?.focus();
  else searchToggle.focus();
}
searchToggle?.addEventListener('click', (event) => {
  event.preventDefault();
  setSearchOpen(searchPanel?.hidden ?? false);
});
document.querySelector('[data-search-close]')?.addEventListener('click', () => setSearchOpen(false));
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && searchPanel && !searchPanel.hidden) setSearchOpen(false);
});

async function refreshHeaderState() {
  if (!window.loomaraStore?.ajaxUrl) return;
  try {
    const response = await fetch(window.loomaraStore.ajaxUrl, {
      method: 'POST', credentials: 'same-origin', cache: 'no-store',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'action=loomara_header_state',
    });
    if (!response.ok) return;
    const payload = await response.json();
    if (!payload.success) return;
    const account = document.querySelector('.header-account');
    if (account) {
      const label = payload.data.signedIn ? 'My account' : 'Sign in or create an account';
      account.setAttribute('aria-label', label);
      account.setAttribute('title', label);
      account.dataset.signedIn = String(payload.data.signedIn);
    }
    const cart = document.querySelector('.header-cart');
    const count = Math.max(0, Number(payload.data.count) || 0);
    if (cart) {
      cart.setAttribute('aria-label', `View cart, ${count} ${count === 1 ? 'item' : 'items'}`);
      const badge = cart.querySelector('.header-cart-count');
      if (badge) badge.textContent = String(count);
    }
  } catch { /* Generic icon links continue to work if the status request fails. */ }
}
refreshHeaderState();
window.addEventListener('pageshow', (event) => { if (event.persisted) refreshHeaderState(); });
if (window.jQuery) {
  window.jQuery(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed', refreshHeaderState);
}

document.querySelectorAll('[data-collection-tabs]').forEach((group) => {
  const tabs = [...group.querySelectorAll('[data-collection-tab]')];
  const panels = [...group.querySelectorAll('[data-collection-panel]')];
  group.querySelector('nav').setAttribute('role', 'tablist');
  function activate(tab, focus = false) {
    tabs.forEach((item) => {
      const active = item === tab;
      item.setAttribute('role', 'tab');
      item.setAttribute('aria-selected', String(active));
      item.setAttribute('aria-controls', `collection-${item.dataset.collectionTab}`);
      item.tabIndex = active ? 0 : -1;
    });
    panels.forEach((panel) => {
      panel.setAttribute('role', 'tabpanel');
      panel.setAttribute('aria-labelledby', `tab-${panel.dataset.collectionPanel}`);
      panel.hidden = panel.dataset.collectionPanel !== tab.dataset.collectionTab;
    });
    if (focus) tab.focus();
  }
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', (event) => { event.preventDefault(); activate(tab); });
    tab.addEventListener('keydown', (event) => {
      let next;
      if (event.key === 'ArrowRight') next = tabs[(index + 1) % tabs.length];
      if (event.key === 'ArrowLeft') next = tabs[(index + tabs.length - 1) % tabs.length];
      if (event.key === 'Home') next = tabs[0];
      if (event.key === 'End') next = tabs[tabs.length - 1];
      if (next) { event.preventDefault(); activate(next, true); }
    });
  });
  if (tabs[0]) activate(tabs[0]);
});

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

document.querySelectorAll('[data-registration-password]').forEach((input) => {
  const tip = document.getElementById('password-tip');
  input.addEventListener('input', () => {
    const checks = [
      [input.value.length >= 6, '6+ characters'],
      [/[a-zA-Z]/.test(input.value), 'a letter'],
      [/[0-9]/.test(input.value), 'a number'],
      [/[^a-zA-Z0-9\s]/.test(input.value), 'a special character'],
    ];
    const valid = checks.every(([passed]) => passed);
    input.setCustomValidity(valid || !input.value ? '' : 'Use at least 6 characters with a letter, a number and a special character.');
    if (tip) {
      tip.textContent = checks.map(([passed, label]) => `${passed ? '✓' : '○'} ${label}`).join(' · ');
      tip.classList.toggle('is-valid', valid);
    }
  });
});

// A failed gallery enhancement must never leave the product photo invisible.
document.querySelectorAll('.woocommerce-product-gallery').forEach((gallery) => {
  gallery.style.opacity = '1';
});
