(() => {
 const root=document.querySelector('[data-bedroom-visualiser]');if(!root)return;
 let catalogue;try{catalogue=JSON.parse(root.querySelector('[data-bv-catalogue]').textContent);}catch{return;}
 const svg=root.querySelector('svg'),status=root.querySelector('[data-bv-status]');
 const validColour=v=>typeof v==='string'&&/^#[0-9a-f]{6}$/i.test(v);
 const selectLayer=select=>{
  const layer=select.dataset.bvSelect;if(!['sheet','comforter','pillow'].includes(layer))return;
  const row=(catalogue[layer]||[]).find(p=>String(p.id)===select.value),card=root.querySelector(`[data-bv-card="${layer}"]`),group=root.querySelector(`[data-bv-layer="${layer}"]`);
  if(group)group.toggleAttribute('hidden',!row);card.replaceChildren();card.hidden=!row;
  const palette=row?.palette||['#d9d0c2','#d9d0c2',false];svg.style.setProperty(`--bv-${layer}`,validColour(palette[0])?palette[0]:'#d9d0c2');svg.style.setProperty(`--bv-${layer}-secondary`,validColour(palette[1])?palette[1]:'#d9d0c2');
  if(!row){status.textContent='Preview updated. No product added to cart.';return;}
  let url;try{url=new URL(row.url,location.origin);if(url.origin!==location.origin||!['http:','https:'].includes(url.protocol))throw Error();}catch{card.hidden=true;return;}
  const image=document.createElement('img'),copy=document.createElement('div'),name=document.createElement('strong'),price=document.createElement('p'),details=document.createElement('p'),link=document.createElement('a');
  try{const src=new URL(row.image,location.origin);if(['http:','https:'].includes(src.protocol))image.src=src.href;}catch{}
  image.alt=row.name;image.width=80;image.height=80;image.loading='lazy';name.textContent=row.name;price.textContent=`${row.price} · ${row.stock}`;details.textContent=`${row.colour||'Colour not recorded'}${row.size?' · '+row.size:''}${!palette[2]?' · Neutral placeholder in scene':''}`;link.href=url.href;link.textContent='View this product →';copy.append(name,price,details,link);card.append(image,copy);status.textContent=`${row.name} selected. Colour preview only; no cart changes.`;
 };
 const selects=[...root.querySelectorAll('[data-bv-select]')];selects.forEach(s=>s.addEventListener('change',()=>selectLayer(s)));
 root.querySelector('[data-bv-reset]').addEventListener('click',()=>{selects.forEach(s=>{s.value='';selectLayer(s);});light.setAttribute('aria-pressed','false');svg.style.setProperty('--bv-wall','#f2eee5');status.textContent='Preview reset. Your cart is unchanged.';});
 const light=root.querySelector('[data-bv-light]');light.addEventListener('click',()=>{const warm=light.getAttribute('aria-pressed')!=='true';light.setAttribute('aria-pressed',String(warm));svg.style.setProperty('--bv-wall',warm?'#eadcc7':'#f2eee5');});
 selects.forEach(selectLayer);
})();
