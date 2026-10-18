(()=>{'use strict';
const config=window.loomaraPinLocation;if(!config)return;
const selector='input[name="postcode"],input[name="billing_postcode"],input[name="shipping_postcode"],input[name="loomara_billing_postcode"]';
const jobs=new WeakMap(),hints=new WeakMap();
function fields(pin){const name=pin.name,prefix=name==='postcode'?'':name.slice(0,-8),scope=prefix==='loomara_billing_'?pin.closest('[data-manual-billing]'):pin.closest('form');if(!scope)return null;return {city:scope.querySelector(`[name="${prefix}city"]`),state:scope.querySelector(`[name="${prefix}state"]`),country:scope.querySelector(`[name="${prefix}country"]`)};}
function set(field,value){if(!field||field.disabled)return;field.value=value;field.dataset.loomaraAuto=value;if(window.jQuery&&field.tagName==='SELECT')window.jQuery(field).trigger('change');else field.dispatchEvent(new Event('change',{bubbles:true}));}
function lookup(pin){if(pin.disabled)return;const code=pin.value.trim();const old=jobs.get(pin);old?.controller?.abort();clearTimeout(old?.timer);if(!/^[1-9][0-9]{5}$/.test(code)){const status=hints.get(pin);if(status)status.textContent='';return;}
const f=fields(pin);if(!f?.city||!f.state)return;
let status=hints.get(pin);if(!status){status=document.createElement('small');status.dataset.pinLocationStatus='';status.setAttribute('role','status');status.style.cssText='display:block;font-size:.78rem;line-height:1.5;margin-top:.4rem';(pin.closest('label')||pin).insertAdjacentElement('afterend',status);hints.set(pin,status);}
const controller=new AbortController(),job={controller,timer:null};jobs.set(pin,job);
job.timer=setTimeout(async()=>{status.textContent='Finding city and state…';const start={city:f.city.value,state:f.state.value};try{const response=await fetch(config.url,{method:'POST',credentials:'same-origin',signal:controller.signal,body:new URLSearchParams({action:'loomara_pin_location',nonce:config.nonce,pin:code})});const data=await response.json();if(pin.value.trim()!==code||jobs.get(pin)!==job)return;
if(!data.success){status.textContent=data.data?.message||'Please enter city and state manually.';return;}
const d=data.data;const apply=()=>{if(pin.value.trim()!==code)return;set(f.country,'IN');set(f.city,d.city);set(f.state,d.state);status.textContent='Location filled. Please check it before saving.';if(window.jQuery&&pin.closest('form.checkout'))window.jQuery(document.body).trigger('update_checkout');};
const safe=[['city',f.city],['state',f.state]].every(([key,el])=>el.value===start[key]&&(!el.value||el.value===el.dataset.loomaraAuto||el.value===d[key]));
if(safe)apply();else{status.textContent=`Detected ${d.city}, ${d.state_name}, India. `;const button=document.createElement('button');button.type='button';button.className='button';button.textContent='Use detected location';button.addEventListener('click',apply);status.append(button);}
}catch(e){if(e.name!=='AbortError'&&pin.value.trim()===code)status.textContent='Location lookup unavailable. Please enter city and state manually.';}},350);
}
document.addEventListener('input',e=>{if(e.target.matches(selector))lookup(e.target);});
document.addEventListener('change',e=>{if(e.target.matches(selector))lookup(e.target);});
})();
