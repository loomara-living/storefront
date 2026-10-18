(() => {
  const input=document.getElementById('loomara-delivery-pin');
  const button=document.querySelector('[data-check-india-pin]');
  const result=document.getElementById('delivery-pin-result');
  if(!input||!button||!result)return;
  function check(){
    const valid=/^[1-9][0-9]{5}$/.test(input.value.trim());
    input.setAttribute('aria-invalid',String(!valid));
    result.className=valid?'pin-valid':'pin-invalid';
    result.textContent=valid?'PIN format accepted. Free India shipping applies. Final courier coverage will be confirmed before dispatch; this is not a live serviceability check.':'Enter a six-digit India PIN code beginning with 1–9, for example 560001.';
  }
  button.addEventListener('click',check);
  input.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();check();}});
  input.addEventListener('input',()=>{result.textContent='';input.removeAttribute('aria-invalid');});
})();
