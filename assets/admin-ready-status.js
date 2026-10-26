// Retire unmapped Pickup Scheduled from new selections, preserving selected
// historical values. All other mapped shipping stages remain distinct.
(() => {
  const stages=new Set(['pickup-scheduled']);
  function tidy(select){
    const options=[...select.options].filter(o=>stages.has(o.value.replace(/^wc-/,'')));
    if(!options.length)return;
    const selected=options.find(o=>o.selected);
    const keep=selected;
    let changed=false;
    for(const option of options){if(option!==keep){option.remove();changed=true;}}
    if(changed&&window.jQuery){window.jQuery(select).trigger('change.select2');}
  }
  function scan(){document.querySelectorAll('select#order_status').forEach(tidy);}
  scan();
  document.addEventListener('change',event=>{if(event.target.matches('select#order_status'))tidy(event.target);});
  new MutationObserver(scan).observe(document.body,{childList:true,subtree:true});
})();
