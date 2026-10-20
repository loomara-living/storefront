/* Native sharing is user-initiated. No provider SDK or automatic message. */
(() => {
  'use strict';
  const mobile = window.matchMedia('(max-width: 767px)');
  document.querySelectorAll('[data-product-share]').forEach(root => {
    const button = root.querySelector('[data-native-product-share]');
    const fallback = root.querySelector('.loomara-share-fallback');
    const status = root.querySelector('.loomara-share-status');
    let data;
    try { data = JSON.parse(root.dataset.productShare); } catch (_) { return; }
    if (!button || !fallback || !status || !data.url) return;
    let imageFile = null, prepared = false, busy = false;
    const prepare = async () => {
      if (prepared || !mobile.matches || !navigator.share || !window.isSecureContext) return;
      prepared = true; button.hidden = false; fallback.hidden = true;
      if (!data.image || !navigator.canShare) return;
      try {
        const url = new URL(data.image, location.href);
        if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) return;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 5000);
        try {
          const response = await fetch(url.href, {credentials:'omit', redirect:'error', signal:controller.signal});
          if (!response.ok || Number(response.headers.get('content-length')) > 5 * 1024 * 1024) return;
          const blob = await response.blob();
          if (!blob.size || blob.size > 5 * 1024 * 1024 || !['image/jpeg','image/png','image/webp'].includes(blob.type)) return;
          const ext = {'image/jpeg':'jpg','image/png':'png','image/webp':'webp'}[blob.type];
          const file = new File([blob], 'loomara-product.' + ext, {type:blob.type});
          if (navigator.canShare({files:[file]})) imageFile = file;
        } finally { clearTimeout(timeout); }
      } catch (_) { /* An unavailable image never blocks text/link sharing. */ }
    };
    // Prepare before the tap, preserving activation when opening the share sheet.
    prepare(); mobile.addEventListener('change', prepare);
    button.addEventListener('click', async () => {
      if (busy || !mobile.matches) return;
      const payload = {title:data.title, text:data.text, url:data.url};
      try { if (imageFile && navigator.canShare && navigator.canShare({...payload, files:[imageFile]})) payload.files = [imageFile]; } catch (_) { /* Use text/link. */ }
      busy = true; button.disabled = true; status.hidden = true;
      try { await navigator.share(payload); }
      catch (error) {
        if (error.name !== 'AbortError') {
          imageFile = null; button.hidden = true; fallback.hidden = false; fallback.open = true;
          status.textContent = 'Choose a sharing option below. Image previews depend on the receiving app.';
          status.hidden = false;
        }
      } finally { busy = false; button.disabled = false; }
    });
  });
})();
