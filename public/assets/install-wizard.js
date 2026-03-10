document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('chamy-wizard');
  if (!form) return;

  const panels = Array.from(form.querySelectorAll('[data-panel]'));
  const steps = Array.from(form.querySelectorAll('.wizard-steps .step'));
  const progress = form.querySelector('[data-progress]');
  const btnNext = form.querySelector('[data-next]');
  const btnPrev = form.querySelector('[data-prev]');
  const btnInstall = form.querySelector('[data-install]');

  let index = 0;

  function show(i) {
    panels.forEach(p => p.hidden = true);
    steps.forEach(s => s.classList.remove('active'));
    panels[i].hidden = false;
    steps[i].classList.add('active');
    const percent = ((i) / (panels.length - 1)) * 100;
    if (progress) progress.style.setProperty('--progress', percent + '%');
    btnPrev.hidden = i === 0;
    btnNext.hidden = i === panels.length - 1;
    btnInstall.hidden = i !== panels.length - 1;
  }

  function validatePanel(i) {
    const panel = panels[i];
    const inputs = Array.from(panel.querySelectorAll('input,select,textarea'));
    // Basic HTML5 validity checks
    for (const el of inputs) {
      if (!el.checkValidity()) {
        el.reportValidity();
        return false;
      }
    }
    return true;
  }

  btnNext.addEventListener('click', function () {
    if (!validatePanel(index)) return;
    index = Math.min(index + 1, panels.length - 1);
    show(index);
  });

  btnPrev.addEventListener('click', function () {
    index = Math.max(index - 1, 0);
    show(index);
  });

  // keyboard support: Enter -> next (if not last)
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      const active = document.activeElement;
      if (active && (active.tagName === 'INPUT' || active.tagName === 'SELECT' || active.tagName === 'TEXTAREA')) {
        // let browser validate normally
      }
    }
  });

  // initialize
  show(index);
});
