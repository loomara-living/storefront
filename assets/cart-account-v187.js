(() => {
  let state;
  function syncCart() {
    if (!state) return;
    const contains=(product,variation)=>state.items.some(item=>variation ? item.variation===variation : item.product===product);
    document.querySelectorAll('a[data-cart-product]').forEach(link=>{
      if (!link.dataset.originalCartLink) {
        link.dataset.originalCartLink=link.getAttribute('href');
        link.dataset.originalCartText=link.textContent;
        link.dataset.originalCartClass=link.className;
        link.dataset.originalCartLabel=link.getAttribute('aria-label')||'';
      }
      const added=contains(Number(link.dataset.cartProduct),Number(link.dataset.cartVariation)||0);
      link.className=added?'button loomara-view-cart':link.dataset.originalCartClass;
      link.href=added?state.cartUrl:link.dataset.originalCartLink;
      link.textContent=added?'View cart':link.dataset.originalCartText;
      link.setAttribute('aria-label',added?'View cart':link.dataset.originalCartLabel);
    });
    document.querySelectorAll('form.cart').forEach(form=>{
      const button=form.querySelector('.single_add_to_cart_button');if(!button)return;
      const variation=Number(form.querySelector('[name=variation_id]')?.value)||0;
      const product=Number(form.querySelector('[name=product_id]')?.value||button.value)||0;
      const added=contains(product,variation)&&(!form.classList.contains('variations_form')||variation>0);
      let view=form.querySelector('.single-view-cart');
      if(!view){view=document.createElement('a');view.className='button single-view-cart';view.textContent='View cart';button.after(view);}
      view.href=state.cartUrl;view.hidden=!added;button.hidden=added;
    });
  }
  document.addEventListener('loomara:cart-state',event=>{state=event.detail;syncCart();});
  window.jQuery(document.body).on('found_variation reset_data hide_variation',()=>queueMicrotask(syncCart));
  document.querySelectorAll('ul.products').forEach(list=>new MutationObserver(()=>{if(state)syncCart();}).observe(list,{childList:true}));
  // Native title works on hover; the readable description also works on focus/touch.
  function wishlistTips(){
    document.querySelectorAll('[data-wishlist-count]').forEach(count=>{
      const button=count.closest('.wishlist-button');if(!button)return;
      const n=Number(count.textContent)||0;
      const text=`${n} ${n===1?'person likes':'people like'} this product`;
      button.title=text;if(count.textContent!==String(n))count.textContent=String(n);count.setAttribute('aria-label',text);
      let tip=button.querySelector('.wishlist-hover-tip');
      if(!tip){tip=document.createElement('span');tip.className='wishlist-hover-tip';button.append(tip);}
      if(tip.textContent!==text)tip.textContent=text;
    });
  }
  wishlistTips();
  let pending=false;
  new MutationObserver(()=>{if(pending)return;pending=true;queueMicrotask(()=>{pending=false;wishlistTips();});}).observe(document.querySelector('main')||document.body,{childList:true,subtree:true,characterData:true});
  if(typeof refreshHeaderState==='function')refreshHeaderState();
})();
