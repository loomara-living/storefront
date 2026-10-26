(() => {
  document.querySelectorAll('.home-offer-carousel').forEach(root => {
    const track=root.querySelector('.home-offer-track'),slides=[...track.children],controls=root.querySelector('.home-offer-controls');
    if(slides.length<2)return;
    const reduced=matchMedia('(prefers-reduced-motion: reduce)');
    let index=0,paused=false,hover=false,timer=null,start=null,suppressClickUntil=0;
    const pause=controls.querySelector('[data-offer-pause]');
    function schedule(){clearTimeout(timer);if(!paused&&!hover&&!document.hidden&&!reduced.matches&&!root.contains(document.activeElement))timer=setTimeout(()=>show(index+1),3000);}
    function show(next){index=(next+slides.length)%slides.length;track.style.transform=`translateX(-${index*100}%)`;slides.forEach((slide,i)=>{slide.inert=i!==index;slide.setAttribute('aria-hidden',String(i!==index));});controls.querySelector('[data-offer-position]').textContent=`${index+1} / ${slides.length}`;schedule();}
    controls.hidden=false;
    controls.querySelector('[data-offer-prev]').addEventListener('click',()=>show(index-1));
    controls.querySelector('[data-offer-next]').addEventListener('click',()=>show(index+1));
    pause.addEventListener('click',()=>{paused=!paused;pause.textContent=paused?'Play':'Pause';pause.setAttribute('aria-pressed',String(paused));schedule();});
    root.addEventListener('mouseenter',()=>{hover=true;schedule();});root.addEventListener('mouseleave',()=>{hover=false;schedule();});
    root.addEventListener('focusin',schedule);root.addEventListener('focusout',()=>setTimeout(schedule,0));
    document.addEventListener('visibilitychange',schedule);reduced.addEventListener('change',schedule);
    track.addEventListener('pointerdown',e=>{if(e.pointerType!=='mouse')start={x:e.clientX,y:e.clientY};});
    track.addEventListener('pointerup',e=>{if(!start)return;const dx=e.clientX-start.x,dy=e.clientY-start.y;start=null;if(Math.abs(dx)>45&&Math.abs(dx)>Math.abs(dy)){e.preventDefault();suppressClickUntil=Date.now()+500;show(index+(dx<0?1:-1));}});
    track.addEventListener('click',e=>{if(Date.now()<suppressClickUntil){e.preventDefault();e.stopPropagation();}},true);
    track.addEventListener('pointercancel',()=>{start=null;});
    show(0);
  });
})();
