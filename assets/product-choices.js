(() => {
  document.querySelectorAll('.single-product form.variations_form').forEach(form => {
    const choices = [];
    form.querySelectorAll('.variations select').forEach(select => {
      if (!/attribute_(?:pa_)?(?:colou?r|size)$/i.test(select.name)) return;
      const group = document.createElement('div');
      group.className = 'loomara-variation-choices'; group.setAttribute('role','group');
      group.setAttribute('aria-label',select.closest('tr')?.querySelector('label')?.textContent?.trim() || select.name);
      select.after(group);
      select.classList.add('loomara-choice-native'); select.setAttribute('aria-hidden','true'); select.tabIndex = -1;
      choices.push({select,group});
      group.addEventListener('click',event => {
        const button = event.target.closest('button[data-option]');
        if (!button || button.disabled) return;
        select.value = button.dataset.option;
        select.dispatchEvent(new Event('change',{bubbles:true}));
      });
    });
    function render() {
      choices.forEach(({select,group}) => {
        const focused = group.contains(document.activeElement) ? document.activeElement.dataset.option : null;
        group.replaceChildren(...[...select.options].filter(option => option.value).map(option => {
          const button = document.createElement('button');button.type = 'button';button.dataset.option = option.value;
          button.textContent = option.textContent;button.disabled = select.disabled || option.disabled || option.hidden;
          button.setAttribute('aria-pressed',String(select.value === option.value));return button;
        }));
        if (focused !== null) [...group.children].find(button => button.dataset.option === focused && !button.disabled)?.focus({preventScroll:true});
      });
    }
    form.addEventListener('change',render);
    window.jQuery?.(form).on('woocommerce_update_variation_values found_variation reset_data hide_variation',render);
    render();
    const guide = form.querySelector('.loomara-size-guide');
    const size = choices.find(({select}) => /attribute_(?:pa_)?size$/i.test(select.name));
    if (guide && size) size.group.before(guide);
  });
})();
