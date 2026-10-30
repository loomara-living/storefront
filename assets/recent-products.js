(() => {
  const box=document.querySelector('[data-recent-products]'),cfg=window.loomaraRecent;
  if(!box||!cfg)return;
  const key='loomara_recent_v1',ttl=30*86400000,max=20,current=Number(cfg.product)||0;
  const clean=(rows,now=Date.now())=>Array.isArray(rows)?rows.filter(r=>r&&Number.isSafeInteger(r.id)&&r.id>0&&Number.isSafeInteger(r.parent)&&r.parent>0&&Number.isFinite(r.at)&&r.at<=now&&r.at>now-ttl).filter((r,i,a)=>a.findIndex(x=>x.parent===r.parent)===i).slice(0,max):[];
  let rows=[];try{rows=clean(JSON.parse(localStorage.getItem(key)||'[]'));}catch{}
  const save=()=>{try{localStorage.setItem(key,JSON.stringify(rows));}catch{}};
  const attributes=()=>Object.fromEntries([...document.querySelectorAll('form.variations_form select[name^="attribute_"]')].filter(e=>/^attribute_[a-z0-9_-]+$/i.test(e.name)&&e.value&&e.value.length<=200).map(e=>[e.name,e.value]));
  const remember=(id,parent,attrs={})=>{if(!Number.isSafeInteger(id)||id<1)return;rows=clean([{id,parent,at:Date.now(),attributes:attrs},...rows.filter(r=>r.parent!==parent)]);save();};
  if(current)remember(current,current);
  let revision=0;
  const render=async()=>{
    const version=++revision,ids=rows.filter(r=>r.parent!==current).slice(0,8).map(r=>r.id);box.hidden=true;
    if(!ids.length)return;
    try{
      const url=new URL(cfg.endpoint,location.origin);if(url.origin!==location.origin)return;url.searchParams.set('ids',ids.join(','));
      const response=await fetch(url,{credentials:'omit',cache:'no-store'});if(!response.ok)return;
      const items=await response.json();if(version!==revision||!Array.isArray(items))return;
      const list=box.querySelector('ul');list.replaceChildren();
      for(const item of items.slice(0,8)){
        const href=new URL(item.url,location.origin);if(href.origin!==location.origin||!['http:','https:'].includes(href.protocol))continue;
        const saved=rows.find(r=>r.id===item.id&&r.parent===item.parent);
        for(const [key,value] of Object.entries(saved?.attributes||{})){if(/^attribute_[a-z0-9_-]+$/i.test(key)&&typeof value==='string'&&value.length<=200)href.searchParams.set(key,value);}
        const li=document.createElement('li'),a=document.createElement('a'),img=document.createElement('img'),title=document.createElement('h3'),price=document.createElement('p');
        li.className='product';a.className='woocommerce-LoopProduct-link woocommerce-loop-product__link';title.className='woocommerce-loop-product__title';price.className='price';
        a.href=href.href;img.src=item.image;img.alt=item.name;img.loading='lazy';img.width=320;img.height=320;title.textContent=item.name;price.textContent=item.price;
        const rating=document.createElement('span'),stars=document.createElement('span'),fill=document.createElement('span'),caption=document.createElement('span');
        const average=Math.min(5,Math.max(0,Number(item.rating)||0)),count=Math.max(0,Math.floor(Number(item.rating_count)||0));
        rating.className='loomara-rating';rating.setAttribute('role','img');rating.setAttribute('aria-label',count?`${average.toFixed(1)} out of 5, ${count} ratings`:'No ratings yet');
        stars.className='rating-stars';stars.setAttribute('aria-hidden','true');fill.style.width=`${average*20}%`;fill.textContent='★★★★★';stars.append(fill,document.createTextNode('☆☆☆☆☆'));caption.className='rating-caption';caption.textContent=count?`${average.toFixed(1)} (${count})`:'Be the first to rate';rating.append(stars,caption);
        a.append(img,title,rating,price);if(item.stock){const stock=document.createElement('span');stock.className='recent-stock';stock.textContent=item.stock;a.append(stock);}
        const action=document.createElement('a');action.className='button';action.href=href.href;action.textContent=item.action==='Read more'?'Read more':'Choose options';action.setAttribute('aria-label',`${action.textContent} for ${item.name}`);
        li.append(a,action);list.append(li);
      }
      box.hidden=!list.children.length;
    }catch{/* Catalogue remains usable when storage/network is unavailable. */}
  };
  box.querySelector('[data-recent-clear]').addEventListener('click',()=>{rows=[];save();++revision;box.querySelector('ul').replaceChildren();box.querySelector('[data-recent-status]').textContent='Recently viewed history cleared.';box.hidden=true;});
  window.addEventListener('storage',e=>{if(e.key!==key)return;try{rows=clean(JSON.parse(e.newValue||'[]'));}catch{rows=[];}render();});
  window.addEventListener('pageshow',event=>{
    if(!event.persisted)return;
    try{rows=clean(JSON.parse(localStorage.getItem(key)||'[]'));}catch{rows=[];}
    if(current){const selected=Number(document.querySelector('form.variations_form input.variation_id')?.value)||current;remember(selected,current,attributes());}
    render();
  });
  if(current&&window.jQuery){window.jQuery(document.body).on('found_variation',(_e,v)=>{const id=Number(v?.variation_id);if(id>0)remember(id,current,attributes());});}
  render();
})();
