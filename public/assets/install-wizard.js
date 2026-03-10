document.addEventListener('DOMContentLoaded', function () {
  try {
    console.debug('install-wizard: init');
    const form = document.getElementById('chamy-wizard');
    if (!form) { console.warn('install-wizard: form not found'); return; }

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
    if (progress) progress.style.setProperty('--wizard-progress', percent + '%');
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
  // allow clicking the step indicators to jump between steps
  steps.forEach((s, idx) => {
    try {
      s.style.cursor = 'pointer';
      s.addEventListener('click', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        if (idx === index) return;
        // if jumping forward, validate current panel first
        if (idx > index) {
          if (!validatePanel(index)) return;
        }
        index = idx;
        show(index);
      });
    } catch (e) { console.warn('install-wizard: step attach failed', e); }
  });

  } catch (err) {
    console.error('install-wizard: initialization error', err);
  }
  // keyboard support: Enter -> next (if not last)
  try {
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
  } catch (e) {
    console.warn('install-wizard: keyboard/init attach failed', e);
  }
});

// Password strength logic (separate init in case script inlined earlier)
document.addEventListener('DOMContentLoaded', function () {
  try {
    var form = document.getElementById('chamy-wizard');
    if (!form) return;
    var pwd = form.querySelector('input[name="admin_password"]');
    var meterBar = form.querySelector('.password-meter-bar');
    if (!pwd || !meterBar) return;

    function scorePassword(pw) {
      var score = 0;
      if (!pw) return 0;
      // length
      if (pw.length >= 8) score++;
      if (pw.length >= 12) score++;
      // lower/upper
      if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
      // numbers
      if (/[0-9]/.test(pw)) score++;
      // symbols
      if (/[^A-Za-z0-9]/.test(pw)) score++;
      // clamp 0..5
      return Math.max(0, Math.min(5, score));
    }

    pwd.addEventListener('input', function () {
      var s = scorePassword(pwd.value);
      var pct = Math.round((s / 5) * 100);
      console.debug('install-wizard: password score', s, 'pct', pct);
      if (meterBar) {
        meterBar.style.width = pct + '%';
        // adjust class
        meterBar.className = 'password-meter-bar meter-' + s;
      } else {
        console.warn('install-wizard: meterBar not found');
      }
    });
  } catch (e) {
    console.warn('install-wizard: password-meter init failed', e);
  }
});
