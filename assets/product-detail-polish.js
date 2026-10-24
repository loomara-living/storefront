jQuery(function ($) {
  let selectedVariation=0;
  function filterOffers(variationId=selectedVariation) {
    selectedVariation=Number(variationId)||0;
    const raw=Number(document.querySelector('form.cart input.qty')?.value||1);
    const quantity=Number.isFinite(raw)&&raw>0?raw:1;
    document.querySelectorAll('.loomara-product-offers').forEach(group=>{
      let best=null,saving=0;
      const rows=[...group.querySelectorAll('[data-offer-variants]')];
      rows.forEach(row=>{
        row.hidden=true;
        let pricing,ids;try{pricing=JSON.parse(row.dataset.offerPricing);ids=JSON.parse(row.dataset.offerVariants);}catch{return;}
        const id=selectedVariation||Number(group.dataset.offerProduct);
        const price=Number(pricing.prices[id]??(!selectedVariation?Object.values(pricing.prices)[0]:NaN));
        if((selectedVariation&&!ids.includes(id))||!Number.isFinite(price)||price<=0)return;
        const total=price*quantity,amount=Number(pricing.amount),cap=Number(pricing.cap);
        let value=pricing.type==='percent'?total*amount/100:pricing.type==='fixed_product'?amount*quantity:pricing.type==='fixed_cart'?amount:0;
        value=Math.min(total,cap>0?Math.min(value,cap):value);
        if(Number.isFinite(value)&&value>saving){saving=value;best=row;}
      });
      if(best)best.hidden=false;
      group.hidden=!best;
    });
  }
  document.querySelector('form.cart input.qty')?.addEventListener('input',()=>filterOffers());
  document.querySelector('form.cart input.qty')?.addEventListener('change',()=>filterOffers());
  const badge = document.querySelector('[data-product-saving]');
  $('form.variations_form').on('found_variation.loomaraDetail', function (_, v) {
    filterOffers(v.variation_id);
    if (!badge) return;
    const saving = Number(v.loomara_saving_percent) || 0;
    badge.hidden = saving <= 0;
    badge.textContent = saving > 0 ? `Save ${saving}%` : '';
  }).on('reset_data.loomaraDetail hide_variation.loomaraDetail', function () {
    filterOffers(0);
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
