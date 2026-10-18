(() => {
  let refreshing=false;
  async function refreshCounts(){
    if(refreshing||!window.loomaraStore?.ajaxUrl)return;
    const nodes=[...document.querySelectorAll('[data-wishlist-count]:not([data-count-ready])')];if(!nodes.length)return;
    const ids=[...new Set(nodes.map(el=>el.dataset.wishlistCount))].slice(0,32);nodes.forEach(el=>{if(ids.includes(el.dataset.wishlistCount))el.dataset.countReady='1';});
    refreshing=true;const body=new URLSearchParams({action:'loomara_wishlist_counts'});ids.forEach(id=>body.append('ids[]',id));
    try{const response=await fetch(window.loomaraStore.ajaxUrl,{method:'POST',body,credentials:'same-origin',cache:'no-store'});const result=await response.json();if(result.success)for(const [id,count] of Object.entries(result.data)){document.querySelectorAll(`[data-wishlist-count="${Number(id)}"]`).forEach(el=>el.textContent=count);}}
    catch{/* Keep server-rendered totals when offline. */}finally{refreshing=false;}
  }
  refreshCounts();document.querySelectorAll('ul.products').forEach(list=>new MutationObserver(refreshCounts).observe(list,{childList:true}));
  const same=document.querySelector('[data-billing-same]'), billing=document.querySelector('[data-separate-billing]');
  if(same&&billing){
    const select=billing.querySelector('[data-billing-select]'),manual=billing.querySelector('[data-manual-billing]');
    const sync=()=>{billing.hidden=same.checked;manual.hidden=!!select?.value;billing.querySelectorAll('input,select').forEach(input=>{input.disabled=same.checked||(input.closest('[data-manual-billing]')&&!!select?.value);});if(window.jQuery)window.jQuery(document.body).trigger('update_checkout');};
    same.addEventListener('change',sync);select?.addEventListener('change',sync);sync();
  }
  function feedback(message,url){
    document.querySelector('.store-feedback')?.remove();
    const box=document.createElement('div');box.className='store-feedback';box.setAttribute('role','status');
    const close=document.createElement('button');close.type='button';close.textContent='×';close.setAttribute('aria-label','Dismiss notification');close.onclick=()=>box.remove();box.append(close,document.createTextNode(message));
    if(url){const a=document.createElement('a');a.href=url;a.textContent='Visit wishlist →';box.append(document.createElement('br'),a);}document.body.append(box);
  }
  document.addEventListener('submit',async event=>{
    const form=event.target.closest('.wishlist-form');if(!form)return;
    event.preventDefault();const button=form.querySelector('button');if(button.disabled)return;button.disabled=true;
    const body=new FormData(form);body.set('action','loomara_toggle_wishlist');
    try{
      const url=new URL(form.getAttribute('action'),location.href);url.pathname=url.pathname.replace(/admin-post\.php$/,'admin-ajax.php');
      const response=await fetch(url,{method:'POST',body,credentials:'same-origin',cache:'no-store'});const result=await response.json();
      if(!response.ok||!result.success)throw new Error(result.data?.message||'Unable to update wishlist. Please refresh and try again.');
      const id=body.get('product_id');
      document.querySelectorAll('.wishlist-form').forEach(other=>{if(other.querySelector('[name=product_id]')?.value!==id)return;other.querySelector('[data-wishlist-heart]').textContent=result.data.saved?'♥':'♡';other.querySelector('button').setAttribute('aria-label',`${result.data.saved?'Remove from':'Save to'} wishlist: ${result.data.name}`);});
      document.querySelectorAll(`[data-wishlist-count="${Number(id)}"]`).forEach(el=>el.textContent=result.data.count);
      feedback(result.data.saved?'Saved to your wishlist. See all your favourites in one place.':'Removed from your wishlist.',result.data.url);
    }catch(error){feedback(error.message);}finally{button.disabled=false;}
  });
  document.addEventListener('click', event => {
    const star=event.target.closest('.loomara-rating');
    const open=event.target.closest('[data-open-rating]');
    if(star||open){const dialog=(star||open).closest('li.product')?.querySelector('.quick-rating-dialog');if(dialog){event.preventDefault();event.stopPropagation();if(star){const rect=star.querySelector('.rating-stars').getBoundingClientRect();if(event.clientX>=rect.left&&event.clientX<=rect.right)dialog.querySelector('[name=rating]').value=Math.min(5,Math.max(1,Math.ceil((event.clientX-rect.left)/rect.width*5)));}dialog.showModal();}}
    const choose=event.target.closest('[data-rating-value]');if(choose){choose.closest('form').querySelector('[name=rating]').value=choose.dataset.ratingValue;choose.parentElement.querySelectorAll('button').forEach(button=>button.setAttribute('aria-pressed',String(Number(button.dataset.ratingValue)<=Number(choose.dataset.ratingValue))));}
    if(event.target.closest('[data-close-rating]'))event.target.closest('dialog')?.close();
    const link=event.target.closest('a');
    if (link && /customer-logout|action=logout/.test(link.href)) {
      event.preventDefault();
      const dialog=document.createElement('dialog');dialog.className='logout-confirm-dialog';
      const title=document.createElement('h2');title.textContent='Leaving for now?';
      const text=document.createElement('p');text.textContent='Log out of your Loomara Living account? Your wishlist and saved addresses will be here when you return.';
      const cancel=document.createElement('button');cancel.type='button';cancel.textContent='Stay signed in';cancel.className='button';cancel.onclick=()=>dialog.close();
      const confirm=document.createElement('button');confirm.type='button';confirm.textContent='Log out';confirm.className='button';confirm.onclick=()=>location.assign(link.href);
      dialog.append(title,text,cancel,confirm);document.body.append(dialog);
      dialog.addEventListener('close',()=>{dialog.remove();link.focus();});dialog.showModal();cancel.focus();
    }
  });
  document.addEventListener('submit',async event=>{
    const form=event.target.closest('[data-quick-rating]');if(!form)return;event.preventDefault();
    const button=form.querySelector('[type=submit]');if(button.disabled)return;button.disabled=true;
    const status=form.querySelector('[data-rating-feedback]');
    try{const response=await fetch(form.getAttribute('action'),{method:'POST',body:new FormData(form),credentials:'same-origin',cache:'no-store'});const result=await response.json();status.textContent=result.data?.message||'Unable to submit. Please refresh and try again.';if(result.success){form.querySelector('select').disabled=true;form.querySelector('textarea').disabled=true;return;}}
    catch{status.textContent='Check your connection and try again.';}button.disabled=false;
  });
  // Improve a gateway's existing processing message without intercepting payment.
  const decorate=()=>{
    document.querySelectorAll('.woocommerce-order-pay p,.woocommerce-order-pay .woocommerce-info,.woocommerce-order-received p,.blockUI.blockMsg').forEach(el=>{
      if (/please wait while we are processing your payment/i.test(el.textContent)) {el.classList.add('payment-processing-note');el.setAttribute('role','status');}
    });
    document.querySelectorAll('form[data-newsletter]').forEach(form=>form.remove());
    document.querySelectorAll('input[type=checkbox]').forEach(input=>{
      const label=input.closest('label')||document.querySelector(`label[for="${CSS.escape(input.id)}"]`);
      if(!/subscribe.*newsletter/i.test(label?.textContent||''))return;
      input.checked=false;input.disabled=true;(input.closest('.form-row')||label).hidden=true;
    });
  };
  decorate(); if(window.jQuery) window.jQuery(document.body).on('updated_checkout',decorate);
  if(document.body.matches('.woocommerce-checkout,.woocommerce-order-pay,.woocommerce-order-received'))new MutationObserver(decorate).observe(document.body,{childList:true,subtree:true});
})();
