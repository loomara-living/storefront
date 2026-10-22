// Presentation only: retain a selected legacy courier key to avoid changing an
// order merely because staff opens/saves it. New selections use ready-to-ship.
(() => {
  const stages=new Set(['ready-to-ship','pickup-scheduled','out-for-pickup','picked-up']);
  function tidy(select){
    const options=[...select.options].filter(o=>stages.has(o.value.replace(/^wc-/,'')));
    if(!options.length)return;
    const selected=options.find(o=>o.selected);
    const keep=selected||options.find(o=>o.value.replace(/^wc-/,'')==='ready-to-ship')||options[0];
    let changed=false;
    if(!selected&&keep.value.replace(/^wc-/,'')!=='ready-to-ship'){keep.value=(keep.value.startsWith('wc-')?'wc-':'')+'ready-to-ship';changed=true;}
    for(const option of options){if(option!==keep){option.remove();changed=true;}}
    if(changed&&window.jQuery){window.jQuery(select).trigger('change.select2');}
  }
  function scan(){document.querySelectorAll('select#order_status').forEach(tidy);}
  scan();
  document.addEventListener('change',event=>{if(event.target.matches('select#order_status'))tidy(event.target);});
  new MutationObserver(scan).observe(document.body,{childList:true,subtree:true});
})();
