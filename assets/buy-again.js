document.querySelectorAll('.loomara-buy-again form').forEach(form=>{
  form.addEventListener('submit',event=>{
    if(form.dataset.submitting){event.preventDefault();return;}
    form.dataset.submitting='yes';const button=form.querySelector('button[type="submit"]');if(button){button.disabled=true;button.textContent='Adding selected items…';}
    form.querySelector('.buy-again-status').textContent='Please wait. Do not submit again; you will be taken to your cart.';
  });
});
// Repeated same-page anchors do not reload the page (or always fire hashchange).
// Open first, then align/focus the actual review rather than the old collapsed box.
const openBuyAgain=()=>{
  const review=document.querySelector('.loomara-buy-again#buy-again');if(!review)return;
  review.open=true;
  requestAnimationFrame(()=>{
    review.querySelector('summary')?.focus({preventScroll:true});
    review.scrollIntoView({block:'start',behavior:'auto'});
  });
};
document.addEventListener('click',event=>{
  if(event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
  const link=event.target.closest?.('a[href]');if(!link||link.hasAttribute('download')||(link.target&&link.target!=='_self'))return;
  const url=new URL(link.href,location.href);
  if(url.origin!==location.origin||url.pathname!==location.pathname||url.search!==location.search||url.hash!=='#buy-again'||!document.querySelector('.loomara-buy-again#buy-again'))return;
  event.preventDefault();
  if(location.hash!=='#buy-again'){location.hash='buy-again';}
  openBuyAgain();
});
if(location.hash==='#buy-again')openBuyAgain();
window.addEventListener('hashchange',()=>{if(location.hash==='#buy-again')openBuyAgain();});
window.addEventListener('pageshow',()=>{if(location.hash==='#buy-again')openBuyAgain();document.querySelectorAll('.loomara-buy-again form').forEach(form=>{
  delete form.dataset.submitting;const button=form.querySelector('button[type="submit"]');if(button){button.disabled=false;button.textContent='Add selected items to cart';}
});});
