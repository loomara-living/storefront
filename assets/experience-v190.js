(() => {
  document.querySelectorAll('[data-address-label]').forEach(select=>{
    const custom=select.parentElement.querySelector('.custom-label-editor'),input=custom?.querySelector('input');
    function sync(){const active=select.value==='Custom';if(custom)custom.hidden=!active;if(input){input.disabled=!active;input.required=active;}}
    select.addEventListener('change',sync);sync();
  });
  document.querySelectorAll('[data-customer-testimonials]').forEach(section=>{
    const rail=section.querySelector('.testimonial-rail'),button=section.querySelector('[data-pause-customer-testimonials]');if(!rail||!button)return;
    let paused=matchMedia('(prefers-reduced-motion: reduce)').matches,index=rail.children.length-1;
    if(!paused)rail.scrollLeft=rail.scrollWidth;
    const label=()=>{button.textContent=paused?'Play testimonials':'Pause testimonials';button.setAttribute('aria-pressed',String(paused));};
    button.addEventListener('click',()=>{paused=!paused;label();});label();
    setInterval(()=>{if(paused||document.hidden||section.matches(':hover')||section.contains(document.activeElement))return;index=(index-1+rail.children.length)%rail.children.length;rail.scrollTo({left:rail.children[index].offsetLeft-rail.offsetLeft,behavior:'smooth'});},6500);
  });
  document.querySelectorAll('[data-cart-delivery]').forEach(form=>{
    const result=form.parentElement.querySelector('[data-cart-delivery-result]'),button=form.querySelector('button');
    form.addEventListener('submit',async event=>{event.preventDefault();if(button.disabled)return;button.disabled=true;result.textContent='Checking courier estimate…';
      try{const response=await fetch(form.action,{method:'POST',credentials:'same-origin',body:new FormData(form),cache:'no-store'}),payload=await response.json();result.textContent=payload.data?.message||'Courier estimate unavailable. Please try again.';}catch{result.textContent='Courier estimate unavailable. Please check your connection and try again.';}finally{button.disabled=false;}
    });
    if(form.querySelector('[name=pin]').value)form.requestSubmit();
  });
})();
