document.querySelectorAll('form.register').forEach(form => {
  const send = form.querySelector('[data-email-otp-send]');
  const email = form.querySelector('[name="email"]');
  const token = form.querySelector('[name="loomara_email_challenge"]');
  const code = form.querySelector('[name="loomara_email_code"]');
  const status = form.querySelector('#email-otp-status');
  if (!send || !email || !token || !code || !status || !window.loomaraEmailOtp) return;
  let timer;
  let sending = false;
  let retryAt = 0;
  let version = 0;
  const updateButton = () => {
    const remaining = Math.max(0, Math.ceil((retryAt - Date.now()) / 1000));
    send.disabled = sending || remaining > 0;
    send.textContent = sending ? 'Requesting code…' : remaining ? `Resend in ${remaining}s` : 'Send email code';
    if (!remaining && timer) { clearInterval(timer); timer = null; }
  };
  email.addEventListener('input', () => {
    version++;
    token.value = '';
    code.value = '';
    status.textContent = 'Request a new code after changing your email.';
  });
  send.addEventListener('click', async () => {
    if (sending || Date.now() < retryAt) return;
    if (!email.value || !email.checkValidity()) { email.reportValidity(); email.focus(); return; }
    const requestVersion = version;
    sending = true; updateButton();
    status.textContent = 'Requesting your verification email…';
    try {
      const response = await fetch(loomaraEmailOtp.url, {
        method: 'POST', credentials: 'same-origin', cache: 'no-store',
        body: new URLSearchParams({action: 'loomara_signup_otp', nonce: loomaraEmailOtp.nonce, email: email.value}),
      });
      const result = await response.json();
      if (requestVersion !== version) { status.textContent = 'Your email changed. Request a new code.'; return; }
      if (!response.ok || !result.success) throw new Error(result.data?.message || 'Unable to request a code. Please try again.');
      token.value = result.data.token;
      code.value = '';
      status.textContent = result.data.message;
      retryAt = Date.now() + result.data.retryAfter * 1000;
      clearInterval(timer); timer = setInterval(updateButton, 1000);
      code.focus();
    } catch (error) {
      status.textContent = error.message || 'Check your connection and try again.';
    } finally { sending = false; updateButton(); }
  });
});
