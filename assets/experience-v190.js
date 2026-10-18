(() => {
  document.querySelectorAll('[data-delete-saved-address]').forEach(form=>{
    form.addEventListener('submit',event=>{
      if(form.dataset.confirmed==='1')return;
      event.preventDefault();
      const dialog=document.createElement('dialog');dialog.className='logout-confirm-dialog';
      const title=document.createElement('h2');title.textContent='Delete this saved address?';
      const text=document.createElement('p');text.textContent='This removes it from your account, not from past orders. If it is your default, another saved address becomes the default.';
      const cancel=document.createElement('button');cancel.type='button';cancel.className='button';cancel.textContent='Keep address';cancel.onclick=()=>dialog.close();
      const remove=document.createElement('button');remove.type='button';remove.className='button address-delete-button';remove.textContent='Delete address';remove.onclick=()=>{form.dataset.confirmed='1';dialog.close();form.requestSubmit();};
      dialog.append(title,text,cancel,remove);document.body.append(dialog);dialog.addEventListener('close',()=>{dialog.remove();form.querySelector('button')?.focus();});dialog.showModal();cancel.focus();
    });
  });
  document.querySelectorAll('[data-address-label]').forEach(select=>{
    const custom=select.parentElement.querySelector('.custom-label-editor'),input=custom?.querySelector('input');
    function sync(){const active=select.value==='Custom';if(custom)custom.hidden=!active;if(input){input.disabled=!active;input.required=active;}}
    select.addEventListener('change',sync);sync();
  });
  document.querySelectorAll('[data-customer-testimonials]').forEach(section=>{
    const rail=section.querySelector('.testimonial-rail');if(!rail)return;
    const slides=[...rail.querySelectorAll('figure')],button=section.querySelector('[data-pause-customer-testimonials]'),counter=section.querySelector('[data-testimonial-position]');if(!slides.length)return;
    const motion=matchMedia('(prefers-reduced-motion: reduce)');let paused=motion.matches,index=0,busy=false,start;
    rail.dataset.carouselReady='1';
    function settle(){slides.forEach((slide,i)=>{slide.classList.toggle('is-active',i===index);slide.setAttribute('aria-hidden',String(i!==index));slide.inert=i!==index;});if(counter)counter.textContent=`${index+1} / ${slides.length}`;}
    settle();if(slides.length<2)return;
    async function move(direction=1){
      if(busy)return;busy=true;
      const old=slides[index];index=(index+direction+slides.length)%slides.length;const next=slides[index];
      next.classList.add('is-active');old.setAttribute('aria-hidden','true');next.setAttribute('aria-hidden','false');
      if(!motion.matches&&typeof next.animate==='function'){
        const duration=450,easing='cubic-bezier(.22,.61,.36,1)';
        await Promise.all([old.animate([{transform:'translateX(0)',opacity:1},{transform:`translateX(${direction*100}%)`,opacity:0}],{duration,easing}).finished,next.animate([{transform:`translateX(${-direction*100}%)`,opacity:0},{transform:'translateX(0)',opacity:1}],{duration,easing}).finished]).catch(()=>{});
      }
      settle();busy=false;
    }
    const label=()=>{if(button){button.textContent=paused?'Play testimonials':'Pause testimonials';button.setAttribute('aria-pressed',String(paused));}};
    button?.addEventListener('click',()=>{paused=!paused;label();});label();
    motion.addEventListener('change',()=>{if(motion.matches){paused=true;label();}});
    section.querySelector('[data-testimonial-next]')?.addEventListener('click',()=>move(1));
    section.querySelector('[data-testimonial-previous]')?.addEventListener('click',()=>move(-1));
    rail.addEventListener('pointerdown',e=>{if(e.isPrimary)start={x:e.clientX,y:e.clientY};});
    rail.addEventListener('pointercancel',()=>{start=null;});
    rail.addEventListener('pointerup',e=>{if(!start)return;const dx=e.clientX-start.x,dy=e.clientY-start.y;start=null;if(Math.abs(dx)>45&&Math.abs(dx)>Math.abs(dy)*1.4)move(dx>0?1:-1);});
    setInterval(()=>{if(paused||document.hidden||section.matches(':hover')||section.contains(document.activeElement))return;move(1);},6500);
  });
  document.querySelectorAll('[data-cart-delivery]').forEach(form=>{
    const result=form.parentElement.querySelector('[data-cart-delivery-result]'),button=form.querySelector('button');
    form.addEventListener('submit',async event=>{event.preventDefault();if(button.disabled)return;button.disabled=true;result.textContent='Checking courier estimate…';
      try{
        // A named input called "action" can shadow HTMLFormElement.action.
        const target=new URL(form.getAttribute('action'),location.href);
        if(target.origin!==location.origin)throw new Error('Unexpected endpoint');
        const response=await fetch(target.href,{method:'POST',credentials:'same-origin',body:new FormData(form),cache:'no-store'});
        if(!response.headers.get('content-type')?.includes('application/json')){result.textContent='The delivery service could not respond correctly. Refresh this page and try again.';return;}
        const payload=await response.json();
        result.textContent=payload===-1?'This delivery check has expired. Refresh the page and try again.':payload?.data?.message||'Courier estimate unavailable. Please try again.';
      }catch{result.textContent='Courier estimate unavailable. Please check your connection and try again.';}finally{button.disabled=false;}
    });
    if(form.querySelector('[name=pin]').value)form.requestSubmit();
  });
})();
