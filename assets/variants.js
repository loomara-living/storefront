jQuery(function ($) {
  const forms = $('form.variations_form');
  const breadcrumbCode = document.querySelector('[data-breadcrumb-product-code]');
  if (breadcrumbCode) {
    forms.on('found_variation.loomaraBreadcrumb', function (event, variation) {
      breadcrumbCode.textContent = variation.sku || breadcrumbCode.dataset.defaultCode;
    });
    forms.on('reset_data.loomaraBreadcrumb hide_variation.loomaraBreadcrumb', function () {
      breadcrumbCode.textContent = breadcrumbCode.dataset.defaultCode;
    });
  }
  const gallery = document.querySelector('[data-variant-gallery]');
  if (!gallery) { forms.trigger('check_variations'); return; }
  const main = gallery.querySelector('[data-variant-main]');
  const link = gallery.querySelector('[data-variant-main-link]');
  const thumbnails = gallery.querySelector('[data-variant-thumbnails]');
  const caption = gallery.querySelector('[data-variant-caption]');
  const selection = gallery.querySelector('[data-variant-selection]');
  let initial;
  try { initial = JSON.parse(gallery.dataset.initialGallery || '[]'); } catch { return; }
  let images = initial;
  function show(index) {
    const image = images[index];
    if (!image) return;
    main.src = image.src;
    main.removeAttribute('srcset');
    main.alt = image.alt || 'Selected product image';
    main.width = image.width;
    main.height = image.height;
    link.href = image.full;
    const expand = gallery.querySelector('[data-variant-expand]');
    if (expand) expand.href = image.full;
    caption.textContent = image.caption || '';
    thumbnails.querySelectorAll('button').forEach((button, i) => button.setAttribute('aria-pressed', String(i === index)));
  }
  function render(next) {
    images = Array.isArray(next) && next.length ? next : initial;
    thumbnails.replaceChildren();
    images.forEach((image, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.setAttribute('aria-label', `View product image ${index + 1}`);
      button.setAttribute('aria-pressed', String(index === 0));
      button.dataset.galleryIndex = String(index);
      const thumb = document.createElement('img');
      thumb.src = image.thumb;
      thumb.alt = '';
      thumb.width = 72;
      thumb.height = 72;
      button.append(thumb);
      thumbnails.append(button);
    });
    show(0);
  }
  thumbnails.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-gallery-index]');
    if (button) show(Number(button.dataset.galleryIndex));
  });
  forms.on('found_variation.loomaraGallery', function (event, variation) {
    render(variation.loomara_gallery);
    selection.textContent = [...this.querySelectorAll('.variations select')].map(s => s.options[s.selectedIndex]?.text || '').join(' / ');
    gallery.dataset.selectedVariation = String(variation.variation_id);
  });
  forms.on('reset_data.loomaraGallery hide_variation.loomaraGallery', function () {
    render(initial);
    selection.textContent = 'Choose colour and size to see availability.';
    delete gallery.dataset.selectedVariation;
  });
  forms.trigger('check_variations');
});
