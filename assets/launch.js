document.querySelectorAll('[data-testimonials]').forEach(carousel => {
  const slides = [...carousel.querySelectorAll('.testimonial-slide')];
  let index = 0;
  let paused = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const pause = carousel.querySelector('[data-testimonial-pause]');
  const render = () => {
    slides.forEach((slide, i) => { slide.hidden = i !== index; });
    carousel.querySelector('[data-testimonial-count]').textContent = `${index + 1} / ${slides.length}`;
    pause.textContent = paused ? 'Play' : 'Pause';
  };
  carousel.querySelector('[data-testimonial-prev]').addEventListener('click', () => { index = (index + slides.length - 1) % slides.length; render(); });
  carousel.querySelector('[data-testimonial-next]').addEventListener('click', () => { index = (index + 1) % slides.length; render(); });
  pause.addEventListener('click', () => { paused = !paused; render(); });
  setInterval(() => {
    if (paused || document.hidden || carousel.matches(':hover') || carousel.contains(document.activeElement)) return;
    index = (index + 1) % slides.length; render();
  }, 6000);
  render();
});
