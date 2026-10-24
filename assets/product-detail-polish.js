jQuery(function ($) {
  const badge = document.querySelector('[data-product-saving]');
  $('form.variations_form').on('found_variation.loomaraDetail', function (_, v) {
    if (!badge) return;
    const saving = Number(v.loomara_saving_percent) || 0;
    badge.hidden = saving <= 0;
    badge.textContent = saving > 0 ? `Save ${saving}%` : '';
  }).on('reset_data.loomaraDetail hide_variation.loomaraDetail', function () {
    if (badge) { badge.hidden = true; badge.textContent = ''; }
  }).trigger('check_variations');
  document.querySelectorAll('[data-copy-offer]').forEach(button => {
    button.addEventListener('click', async () => {
      const status = button.parentElement.querySelector('[data-copy-status]');
      try {
        await navigator.clipboard.writeText(button.dataset.copyOffer);
        button.textContent = 'Copied';
        if (status) status.textContent = 'Coupon copied. Apply it at checkout.';
      } catch (_) {
        if (status) { status.classList.remove('screen-reader-text'); status.textContent = `Copy code: ${button.dataset.copyOffer}`; }
      }
    });
  });
});
