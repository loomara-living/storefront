(() => {
  const trigger=document.querySelector('[data-offers-open]'),dialog=document.querySelector('#loomara-offers-dialog'),cfg=window.loomaraHeaderOffers;
  if(!trigger||!dialog||!cfg)return;
  const list=dialog.querySelector('[data-offers-list]'),message=dialog.querySelector('[data-offers-message]');
  let controller;
  const close=()=>{controller?.abort();dialog.close();trigger.focus();};
  dialog.querySelector('[data-offers-close]').addEventListener('click',close);
  dialog.addEventListener('cancel',()=>controller?.abort());
  trigger.addEventListener('click',async()=>{
    if(!dialog.open)dialog.showModal();
    controller?.abort();controller=new AbortController();const request=controller;
    list.replaceChildren();message.textContent='Checking current offers…';
    try{
      const url=new URL(cfg.url,location.origin);if(url.origin!==location.origin)throw Error();
      const response=await fetch(url,{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=loomara_public_offers',signal:request.signal});
      const data=await response.json();if(!response.ok||!data.success||!Array.isArray(data.data))throw Error();
      if(request!==controller||!dialog.open)return;
      for(const offer of data.data){
        if(typeof offer.code!=='string'||!/^[A-Z0-9_-]{1,40}$/.test(offer.code))continue;
        const card=document.createElement('article'),head=document.createElement('div'),code=document.createElement('strong'),copy=document.createElement('button'),description=document.createElement('p'),status=document.createElement('p');
        card.className='header-offer-card';head.className='header-offer-card-top';code.textContent=offer.code;copy.type='button';copy.textContent='Copy';copy.setAttribute('aria-label',`Copy ${offer.code}`);
        copy.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(offer.code);message.textContent=`${offer.code} copied. Apply it at checkout.`;}catch{message.textContent=`Copy this code: ${offer.code}`;}});
        description.textContent=offer.description;status.className=offer.user_ineligible?'offer-ineligible':'offer-eligible';
        status.textContent=offer.user_ineligible?`Not applicable — ${offer.reason}`:(offer.applicable?'Applicable to your current cart':'');
        status.hidden=!status.textContent;
        head.append(code,copy);card.append(head,description,status);
        if(Number(offer.minimum)>0){const minimum=document.createElement('small');minimum.textContent=`Minimum purchase ₹${Number(offer.minimum).toLocaleString('en-IN')}`;card.append(minimum);}
        if(offer.expires){const expiry=document.createElement('small');expiry.textContent=`Valid through ${offer.expires}`;card.append(expiry);}
        list.append(card);
      }
      message.textContent=list.children.length?'Eligibility is checked again at checkout.':'No public offers are currently running.';
    }catch(error){if(error.name!=='AbortError')message.textContent='Unable to load offers. Please try again or check at checkout.';}
  });
})();
