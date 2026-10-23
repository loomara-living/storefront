jQuery(function ($) {
  const box = document.querySelector('.loomara-restock');
  if (!box) return;
  const input = box.querySelector('[name="product_id"]');
  $('form.variations_form').on('found_variation.loomaraRestock', function (_, v) {
    box.hidden = !!v.is_in_stock;
    if (input) input.value = v.variation_id;
    document.querySelector('.product')?.classList.toggle('loomara-selected-out-of-stock', !v.is_in_stock);
  }).on('reset_data.loomaraRestock hide_variation.loomaraRestock', function () {
    box.hidden = box.dataset.restockInitial !== '1';
    if (input) input.value = box.dataset.restockParent;
    document.querySelector('.product')?.classList.remove('loomara-selected-out-of-stock');
  }).trigger('check_variations');
});
