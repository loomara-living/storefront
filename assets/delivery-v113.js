(() => {
  const form=document.querySelector('[data-product-delivery]');if(!form)return;
  const result=form.querySelector('[data-product-delivery-result]'),pin=form.elements.namedItem('pin'),button=form.querySelector('button');
  const cart=document.querySelector('.summary form.cart'),variation=cart?.querySelector('[name=variation_id]'),qty=cart?.querySelector('[name=quantity]');
  let sequence=0,controller,edited=false,debounce;
  const paint=(message,state='')=>{result.textContent=message;result.dataset.state=state;};
  function sync(){form.elements.namedItem('variation_id').value=variation?.value||'0';form.elements.namedItem('quantity').value=qty?.value||'1';}
  async function check(){
    const current=++sequence;controller?.abort();controller=new AbortController();sync();
    if(!/^[1-9]\d{5}$/.test(pin.value)){paint('Enter a valid six-digit India PIN code.','error');return;}
    if(variation&&!Number(variation.value)){paint('Choose colour and size to get an accurate delivery estimate.');return;}
    button.disabled=true;paint('Checking courier estimate…','loading');
    try{const url=new URL(form.getAttribute('action'),location.href);if(url.origin!==location.origin)throw new Error('Endpoint');
      const response=await fetch(url,{method:'POST',body:new FormData(form),credentials:'same-origin',cache:'no-store',signal:controller.signal});
      if(!response.headers.get('content-type')?.includes('application/json'))throw new Error('Response');const data=await response.json();
      if(current!==sequence)return;paint(data?.data?.message||'This delivery check has expired. Refresh the page and try again.',data?.success?'success':'error');
    }catch(error){if(current===sequence&&error.name!=='AbortError')paint('Courier estimate unavailable. Please try again.','error');}
    finally{if(current===sequence)button.disabled=false;}
  }
  form.addEventListener('submit',event=>{event.preventDefault();check();});
  pin.addEventListener('input',()=>{edited=true;++sequence;controller?.abort();button.disabled=false;paint('Check delivery for this PIN.');});
  function changed(){++sequence;controller?.abort();button.disabled=false;clearTimeout(debounce);paint('Choose options and check delivery.');if(/^[1-9]\d{5}$/.test(pin.value))debounce=setTimeout(check,250);}
  qty?.addEventListener('change',changed);if(window.jQuery&&cart)window.jQuery(cart).on('found_variation reset_data hide_variation',changed);
  if(window.loomaraDelivery113?.signedIn){
    const body=new FormData();body.set('action','loomara_default_delivery_pin_113');body.set('delivery_nonce',window.loomaraDelivery113.nonce);
    fetch(form.getAttribute('action'),{method:'POST',body,credentials:'same-origin',cache:'no-store'}).then(r=>r.json()).then(data=>{if(!edited&&/^[1-9]\d{5}$/.test(data?.data?.pin||'')){pin.value=data.data.pin;check();}}).catch(()=>{});
  }
})();
