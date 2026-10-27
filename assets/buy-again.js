document.querySelectorAll('.loomara-buy-again form').forEach(form=>{
  form.addEventListener('submit',event=>{
    if(form.dataset.submitting){event.preventDefault();return;}
    form.dataset.submitting='yes';const button=form.querySelector('button[type="submit"]');if(button){button.disabled=true;button.textContent='Adding selected items…';}
    form.querySelector('.buy-again-status').textContent='Please wait. Do not submit again; you will be taken to your cart.';
  });
});
if(location.hash==='#buy-again'){const review=document.querySelector('#buy-again');if(review)review.open=true;}
window.addEventListener('pageshow',()=>document.querySelectorAll('.loomara-buy-again form').forEach(form=>{
  delete form.dataset.submitting;const button=form.querySelector('button[type="submit"]');if(button){button.disabled=false;button.textContent='Add selected items to cart';}
}));
