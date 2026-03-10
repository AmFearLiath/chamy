document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('chamy-wizard');
  if (!form) return;

  var panels = Array.from(form.querySelectorAll('[data-panel]'));
  var steps = Array.from(form.querySelectorAll('.wizard-steps .step'));
  var progress = form.querySelector('[data-progress]');
  var btnNext = form.querySelector('[data-next]');
  var btnPrev = form.querySelector('[data-prev]');
  var pwd = form.querySelector('input[name="admin_password"]');
  var pwdConfirm = form.querySelector('input[name="admin_password_confirm"]');
  var pwdErr = form.querySelector('.password-error');
  var confErr = form.querySelector('.password-confirm-error');
  var meterBar = form.querySelector('.password-meter-bar');
  var index = 0;
  var modalOpen = false;

  function show(i) {
    panels.forEach(function (p) { p.hidden = true; });
    steps.forEach(function (s) { s.classList.remove('active'); });
    if (!panels[i]) return;
    panels[i].hidden = false;
    if (steps[i]) steps[i].classList.add('active');
    var percent = panels.length > 1 ? (i / (panels.length - 1)) * 100 : 100;
    if (progress) progress.style.setProperty('--wizard-progress', percent + '%');

    var prev = form.querySelector('[data-prev]');
    var next = form.querySelector('[data-next]');
    var install = form.querySelector('[data-install]');
    if (prev) prev.hidden = (i === 0);
    if (next) next.hidden = (i === panels.length - 1);
    if (install) install.hidden = (i !== panels.length - 1);
  }

  function scorePassword(value) {
    var score = 0;
    if (!value) return 0;
    if (value.length >= 8) score++;
    if (value.length >= 12) score++;
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
    if (/[0-9]/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;
    return Math.max(0, Math.min(5, score));
  }

  function updatePasswordMeter() {
    if (!pwd || !meterBar) return;
    var score = scorePassword(pwd.value || '');
    var pct = Math.round((score / 5) * 100);
    meterBar.style.width = pct + '%';
    meterBar.className = 'password-meter-bar meter-' + score;
  }

  function validatePasswords(showMessages) {
    if (!pwd || !pwdConfirm) return true;

    var password = pwd.value || '';
    var confirm = pwdConfirm.value || '';
    var matches = password === confirm;

    if (!matches) {
      pwdConfirm.setCustomValidity('Passwort und Bestätigung stimmen nicht überein.');
      if (showMessages && confErr) {
        confErr.textContent = 'Passwort und Bestätigung stimmen nicht überein.';
        confErr.style.display = 'block';
      }
      return false;
    }

    pwdConfirm.setCustomValidity('');
    if (confErr) confErr.style.display = 'none';

    var lengthOk = password.length >= 3 && password.length <= 72;
    if (!lengthOk) {
      if (showMessages && pwdErr) {
        pwdErr.textContent = 'Passwort muss zwischen 3 und 72 Zeichen haben.';
        pwdErr.style.display = 'block';
      }
      return false;
    }

    if (pwdErr) pwdErr.style.display = 'none';
    return true;
  }

  function validatePanel(i) {
    var panel = panels[i];
    if (!panel) return true;
    var inputs = Array.from(panel.querySelectorAll('input,select,textarea'));

    for (var x = 0; x < inputs.length; x++) {
      var el = inputs[x];
      if (!el.checkValidity()) {
        el.reportValidity();
        return false;
      }
    }

    // panel 3 (admin) also requires password match check
    if (i === 2) {
      var passOk = validatePasswords(true);
      if (!passOk) {
        if (pwdConfirm && !pwdConfirm.checkValidity()) {
          pwdConfirm.reportValidity();
          pwdConfirm.focus();
        }
        return false;
      }
    }

    return true;
  }

  function collectSummaryRows() {
    var rows = [];
    var fields = Array.from(form.querySelectorAll('[data-summary="true"]'));
    fields.forEach(function (el) {
      var labelNode = el.closest('.field') ? el.closest('.field').querySelector('label') : null;
      var label = labelNode ? labelNode.textContent.trim() : (el.name || el.id || 'Value');
      var value = '';
      if (el.tagName === 'SELECT') {
        value = el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : el.value;
      } else {
        value = (el.value || '').trim();
      }
      if (!value) return;
      rows.push({ label: label, value: value });
    });
    return rows;
  }

  function closeModal(overlay) {
    if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
    modalOpen = false;
  }

  function openInstallSummary(onConfirm) {
    if (modalOpen) return;
    modalOpen = true;

    var overlay = document.createElement('div');
    overlay.className = 'modal-overlay';

    var modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.maxWidth = '780px';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', 'Installation bestaetigen');

    var header = document.createElement('div');
    header.className = 'modal-header';
    header.innerHTML = '<h3>Installation bestaetigen</h3><button type="button" class="modal-close" aria-label="Schliessen">&times;</button>';

    var body = document.createElement('div');
    body.className = 'modal-body';

    var intro = document.createElement('p');
    intro.textContent = 'Bitte pruefen Sie die Einstellungen. Erst nach Bestaetigung startet die Installation.';
    body.appendChild(intro);

    var list = document.createElement('ul');
    list.className = 'confirm-list';
    var rows = collectSummaryRows();
    rows.forEach(function (row) {
      var li = document.createElement('li');
      li.textContent = row.label + ': ' + row.value;
      list.appendChild(li);
    });
    body.appendChild(list);

    var note = document.createElement('p');
    note.className = 'muted';
    note.textContent = 'Bei Bestaetigung werden Datenbank/Migrationen ausgefuehrt sowie .env und storage/install.lock geschrieben.';
    body.appendChild(note);

    var footer = document.createElement('div');
    footer.className = 'modal-footer';
    footer.innerHTML = '<button type="button" class="btn btn-secondary" data-cancel>Abbrechen</button><button type="button" class="btn" data-confirm>Installation starten</button>';

    modal.appendChild(header);
    modal.appendChild(body);
    modal.appendChild(footer);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    var closeBtn = header.querySelector('.modal-close');
    var cancelBtn = footer.querySelector('[data-cancel]');
    var confirmBtn = footer.querySelector('[data-confirm]');

    function cancel() { closeModal(overlay); }

    if (closeBtn) closeBtn.addEventListener('click', cancel);
    if (cancelBtn) cancelBtn.addEventListener('click', cancel);
    if (confirmBtn) {
      confirmBtn.addEventListener('click', function () {
        closeModal(overlay);
        form.dataset.installConfirmed = '1';
        onConfirm();
      });
    }
    overlay.addEventListener('click', function (ev) {
      if (ev.target === overlay) cancel();
    });
    overlay.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') cancel();
    });
    overlay.tabIndex = -1;
    overlay.focus();
  }

  if (btnNext) {
    btnNext.addEventListener('click', function () {
      if (!validatePanel(index)) return;

      // On last Next click, show mandatory summary and only install after confirmation.
      if (index === panels.length - 2) {
        openInstallSummary(function () {
          if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
          } else {
            form.submit();
          }
        });
        return;
      }

      index = Math.min(index + 1, panels.length - 1);
      show(index);
    });
  }

  if (btnPrev) {
    btnPrev.addEventListener('click', function () {
      index = Math.max(index - 1, 0);
      show(index);
    });
  }

  steps.forEach(function (s, idx) {
    s.style.cursor = 'pointer';
    s.addEventListener('click', function (ev) {
      ev.preventDefault();
      if (idx === index) return;
      if (idx > index && !validatePanel(index)) return;
      index = idx;
      show(index);
    });
  });

  if (pwd) {
    pwd.addEventListener('input', function () {
      updatePasswordMeter();
      validatePasswords(false);
    });
  }
  if (pwdConfirm) {
    pwdConfirm.addEventListener('input', function () {
      validatePasswords(false);
    });
  }

  form.addEventListener('submit', function (ev) {
    // If not yet confirmed, stop submit and show mandatory summary.
    if (form.dataset.installConfirmed !== '1') {
      ev.preventDefault();
      ev.stopImmediatePropagation();
      if (validatePanel(index) && validatePasswords(true)) {
        openInstallSummary(function () {
          if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
          } else {
            form.submit();
          }
        });
      }
      return false;
    }

    // final validation before real submit
    if (!validatePanel(index) || !validatePasswords(true)) {
      ev.preventDefault();
      ev.stopImmediatePropagation();
      form.dataset.installConfirmed = '';
      return false;
    }
    return true;
  }, { capture: true });

  updatePasswordMeter();
  show(index);
});
