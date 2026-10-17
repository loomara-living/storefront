// Passwords remain in the existing field only: never storage, URLs or new HTML.
document.querySelectorAll('.woocommerce input[type="password"]').forEach(input => {
  if (input.closest('.password-input')?.querySelector('.show-password-input')) return;
  const button = document.createElement('button');
  button.type = 'button'; button.className = 'loomara-password-toggle';
  button.textContent = 'Show password'; button.setAttribute('aria-pressed', 'false');
  if (input.id) button.setAttribute('aria-controls', input.id);
  button.addEventListener('click', () => {
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.textContent = show ? 'Hide password' : 'Show password';
    button.setAttribute('aria-pressed', String(show));
  });
  input.insertAdjacentElement('afterend', button);
});

// Submit the native WooCommerce form without destroying the current fields on
// errors. WooCommerce still handles nonces, validation, OTP and account creation.
document.querySelectorAll('form.register').forEach(form => {
  let submitting = false;
  const status = document.createElement('div');
  status.className = 'loomara-signup-feedback'; status.setAttribute('role', 'alert');
  form.prepend(status);
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (submitting || !form.reportValidity()) return;
    submitting = true;
    const button = form.querySelector('[name="register"]');
    const original = button?.textContent;
    const body = new FormData(form);
    body.set('register', 'Register');
    if (button) { button.disabled = true; button.textContent = 'Creating account…'; }
    status.textContent = '';
    try {
      const response = await fetch(form.action || location.href, {method: 'POST', body, credentials: 'same-origin', cache: 'no-store'});
      const html = await response.text();
      const result = new DOMParser().parseFromString(html, 'text/html');
      const errors = [...new Set([...result.querySelectorAll('.woocommerce-error li')].map(el => el.textContent.trim()).filter(Boolean))];
      if (errors.length) {
        status.textContent = errors.join(' ');
        status.scrollIntoView({block: 'nearest', behavior: 'auto'});
        // Refresh only CSRF tokens, not password or other customer inputs.
        for (const name of ['woocommerce-register-nonce', '_wp_http_referer']) {
          const fresh = result.querySelector(`[name="${name}"]`);
          const current = form.querySelector(`[name="${name}"]`);
          if (fresh && current) current.value = fresh.value;
        }
        return;
      }
      const destination = new URL(response.url);
      if (response.ok && destination.origin === location.origin && result.querySelector('.woocommerce-MyAccount-navigation')) {
        location.assign(destination.href); return;
      }
      throw new Error('Unable to confirm signup. Your entries are still here. Please try again, or sign in if your account was created.');
    } catch (error) { status.textContent = error.message || 'Check your connection and try again. Your entries have been kept.'; }
    finally {
      submitting = false;
      if (button) { button.disabled = false; button.textContent = original; }
    }
  });
});
