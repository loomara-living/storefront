(() => {
  const config = window.loomaraProductQuantity;
  if (!config?.cartEndpoint) return;
  const endpoint = new URL(config.cartEndpoint, location.href);
  if (endpoint.origin !== location.origin) return;
  let cart, nonce = '', loading, busy = false, revision = 0;
  const controls = [];
  const selected = form => {
    const variation = Number(form.querySelector('[name=variation_id]')?.value) || 0;
    if (form.classList.contains('variations_form')) return variation;
    return Number(form.querySelector('[name=product_id]')?.value || form.querySelector('.single_add_to_cart_button')?.value) || 0;
  };
  const itemFor = id => cart?.items?.filter(item => Number(item.id) === id) || [];
  const maximumFor = (c,item) => {
    const value = item?.quantity_limits?.maximum ?? c.input.max;
    return value !== '' && Number.isFinite(Number(value)) ? Number(value) : Infinity;
  };
  function render() {
    controls.forEach(c => {
      const id = selected(c.form), items = id ? itemFor(id) : [], item = items[0];
      const quantity = items.reduce((sum, line) => sum + Number(line.quantity), 0);
      const editable = items.length <= 1 && item?.quantity_limits?.editable !== false;
      c.form.setAttribute('aria-busy', String(busy));
      c.input.disabled = busy || !id || !editable;
      c.minus.disabled = busy || !id || !cart || !editable || (!quantity && Number(c.input.value) <= Number(c.input.min || 1));
      const maximum = maximumFor(c,item);
      c.plus.disabled = busy || !id || !cart || !editable || Number(c.input.value) >= maximum;
      if (!c.error) {
        c.status.textContent = !cart ? 'Checking cart…' : !id ? 'Choose your options first.' : busy ? 'Updating cart…' : quantity ? `${quantity} ${quantity === 1 ? 'unit' : 'units'} in cart${editable ? '' : ' · Edit in cart'}` : '0 units in cart · Choose quantity to add';
        c.status.dataset.error = 'false';
      }
      if (cart && !busy) {
        if (quantity) c.input.value = String(quantity);
        else if (c.lastId !== id || c.wasInCart) c.input.value = String(Math.max(1, Number(c.input.min) || 1));
        c.wasInCart = quantity > 0; c.lastId = id;
        let view = c.form.querySelector('.single-view-cart');
        if (!view) {view = document.createElement('a'); view.className = 'button single-view-cart'; view.textContent = 'View cart'; c.submit.after(view);}
        view.href = config.cartUrl; view.hidden = quantity < 1; c.submit.hidden = quantity > 0;
      }
    });
  }
  async function refresh() {
    if (loading) return loading;
    const readRevision = revision;
    loading = (async () => {
      const response = await fetch(endpoint.href, {credentials:'same-origin', cache:'no-store', headers:{Accept:'application/json'}});
      const data = await response.json();
      if (!response.ok || !Array.isArray(data.items)) throw Error('Unable to read cart');
      if (readRevision !== revision) return;
      nonce = response.headers.get('Nonce') || nonce; cart = data;
      controls.forEach(c => {c.error = false;}); render();
    })().catch(() => {
      controls.forEach(c => {c.error = true; c.status.dataset.error = 'true'; c.status.textContent = 'Cart quantity unavailable. Refresh the page or open your cart.';});
      render();
    }).finally(() => {loading = null;});
    return loading;
  }
  async function change(c, target) {
    if (busy || !cart) return;
    const id = selected(c.form), items = itemFor(id), item = items[0];
    if (!id || items.length > 1) return;
    const minimum = Number(item?.quantity_limits?.minimum ?? c.input.min) || 1;
    const maximum = maximumFor(c,item);
    const step = Number(item?.quantity_limits?.multiple_of ?? c.input.step) || 1;
    if (!Number.isInteger(target) || target < 0 || (target > 0 && (target < minimum || target > maximum || target % step !== 0))) {render(); return;}
    if (!item) {c.input.value = String(Math.max(minimum, target)); c.input.dispatchEvent(new Event('change',{bubbles:true})); render(); return;}
    if (!nonce || item.quantity_limits?.editable === false) return;
    busy = true; revision++; c.error = false; render();
    try {
      const writeUrl = new URL(endpoint);
      const action = target ? '/update-item' : '/remove-item';
      if (writeUrl.searchParams.has('rest_route')) writeUrl.searchParams.set('rest_route',writeUrl.searchParams.get('rest_route').replace(/\/$/,'') + action);
      else writeUrl.pathname = writeUrl.pathname.replace(/\/$/,'') + action;
      const response = await fetch(writeUrl.href, {
        method:'POST', credentials:'same-origin', cache:'no-store',
        headers:{'Content-Type':'application/json', Nonce:nonce},
        body:JSON.stringify(target ? {key:item.key, quantity:target} : {key:item.key}),
      });
      const data = await response.json(); nonce = response.headers.get('Nonce') || nonce;
      if (!response.ok || !Array.isArray(data.items)) throw Error('Update failed');
      cart = data;
      if (typeof refreshHeaderState === 'function') refreshHeaderState();
      window.jQuery?.(document.body).trigger('wc_fragment_refresh');
      document.body.dispatchEvent(new CustomEvent(target ? 'wc-blocks_added_to_cart' : 'wc-blocks_removed_from_cart',{bubbles:true}));
    } catch {
      c.error = true; c.status.dataset.error = 'true'; c.status.textContent = 'Could not confirm the update. Check your cart before trying again.';
      // Never automatically repeat a write after an uncertain response.
      if (loading) await loading;
      await refresh();
    } finally {busy = false; render();}
  }
  document.querySelectorAll('.single-product form.cart').forEach(form => {
    const input = form.querySelector('.quantity input.qty'), submit = form.querySelector('.single_add_to_cart_button');
    if (!input || !submit || form.classList.contains('grouped_form') || input.type === 'hidden') return;
    const wrapper = input.closest('.quantity'); wrapper.classList.add('loomara-quantity');
    const minus = document.createElement('button'), plus = document.createElement('button'), status = document.createElement('p');
    minus.type = plus.type = 'button'; minus.textContent = '−'; plus.textContent = '+';
    minus.setAttribute('aria-label','Decrease quantity'); plus.setAttribute('aria-label','Increase quantity');
    status.className = 'loomara-cart-unit-status'; status.setAttribute('role','status'); status.setAttribute('aria-live','polite');
    wrapper.prepend(minus); wrapper.append(plus); wrapper.after(status);
    const c = {form,input,submit,minus,plus,status}; controls.push(c);
    form.addEventListener('submit', event => {if (busy) event.preventDefault();});
    minus.addEventListener('click', () => change(c, Math.max(0, Number(input.value) - (Number(itemFor(selected(form))[0]?.quantity_limits?.multiple_of ?? input.step) || 1))));
    plus.addEventListener('click', () => change(c, Number(input.value) + (Number(itemFor(selected(form))[0]?.quantity_limits?.multiple_of ?? input.step) || 1)));
    input.addEventListener('change', () => {if (itemFor(selected(form)).length) change(c, Number(input.value));});
  });
  if (!controls.length) return;
  render(); refresh();
  document.addEventListener('loomara:cart-state', () => {if (!busy) refresh();});
  window.jQuery?.(document.body).on('found_variation reset_data hide_variation', () => queueMicrotask(render));
  window.addEventListener('pageshow', event => {if (event.persisted && !busy) refresh();});
  document.addEventListener('visibilitychange', () => {if (!document.hidden && !busy) refresh();});
})();
