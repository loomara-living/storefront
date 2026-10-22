// Only Loomara's own same-origin WordPress routes. No SDK patches, prototype
// overrides, payment-field changes, network calls or credential handling.
(() => {
  function prepare(form) {
    const fields=[...form.querySelectorAll('input[name="action"]')];
    if(fields.length!==1||fields[0].type!=='hidden'||!/^loomara_[a-z0-9_]+$/i.test(fields[0].value))return;
    let target;try{target=new URL(form.getAttribute('action'),location.href);}catch{return;}
    if(target.origin!==location.origin||!/\/wp-admin\/admin-(?:post|ajax)\.php$/.test(target.pathname))return;
    const route=fields[0].value;
    if(target.searchParams.has('action')&&target.searchParams.get('action')!==route)return;
    // WordPress reads action from $_REQUEST, so native POST and AJAX retain
    // exactly the same handler while nonce/private fields remain in POST.
    target.searchParams.set('action',route);
    form.setAttribute('action',target.href);
    fields[0].removeAttribute('name');
    fields[0].dataset.loomaraRoute=route;
  }
  function scan(){document.querySelectorAll('form').forEach(prepare);}
  scan();
  new MutationObserver(scan).observe(document.documentElement,{childList:true,subtree:true});
})();
