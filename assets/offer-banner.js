document.querySelectorAll('.launch-strip').forEach(offer => {
  offer.querySelector('[data-offer-pause]')?.addEventListener('click', event => {
    const paused = offer.classList.toggle('is-paused');
    const button = event.currentTarget;
    button.textContent = paused ? 'Play' : 'Pause';
    button.setAttribute('aria-pressed', String(paused));
    button.setAttribute('aria-label', paused ? 'Play offer animation' : 'Pause offer animation');
  });
});
