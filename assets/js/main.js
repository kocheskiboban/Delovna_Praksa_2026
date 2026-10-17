/* Ohrid Summer — core interactions (vanilla JS, progressive enhancement).
   Every feature here enhances markup that already works without JavaScript. */
(function () {
  'use strict';

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const i18n = (() => { try { return JSON.parse($('#js-i18n')?.textContent || '{}'); } catch (e) { return {}; } })();
  const csrf = () => $('input[name="_csrf"]')?.value || '';

  /* ------------------------------------------------------------ toast */
  function toast(message, type = 'success') {
    let stack = $('.toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      stack.setAttribute('role', 'status');
      stack.setAttribute('aria-live', 'polite');
      document.body.appendChild(stack);
    }
    const el = document.createElement('div');
    el.className = 'toast toast--' + type;
    el.textContent = message;
    stack.appendChild(el);
    setTimeout(() => { el.classList.add('is-leaving'); setTimeout(() => el.remove(), 300); }, 3800);
  }
  window.OhridToast = toast;

  /* ------------------------------------------------------------ navigation */
  const navToggle = $('[data-nav-toggle]');
  if (navToggle) {
    navToggle.addEventListener('click', () => {
      const open = document.body.classList.toggle('nav-open');
      navToggle.setAttribute('aria-expanded', String(open));
    });
  }
  $$('.sub-toggle').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const li = btn.closest('.has-sub');
      const open = !li.classList.contains('is-open');
      $$('.has-sub.is-open').forEach((o) => { if (o !== li) { o.classList.remove('is-open'); $('.sub-toggle', o).setAttribute('aria-expanded', 'false'); } });
      li.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', String(open));
    });
  });
  $$('[data-dropdown]').forEach((dd) => {
    const btn = $('button', dd);
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = !dd.classList.contains('is-open');
      dd.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', String(open));
    });
  });
  document.addEventListener('click', (e) => {
    $$('[data-dropdown].is-open').forEach((dd) => { if (!dd.contains(e.target)) { dd.classList.remove('is-open'); $('button', dd).setAttribute('aria-expanded', 'false'); } });
    $$('.has-sub.is-open').forEach((li) => { if (!li.contains(e.target) && window.innerWidth >= 1180) { li.classList.remove('is-open'); $('.sub-toggle', li).setAttribute('aria-expanded', 'false'); } });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    $$('[data-dropdown].is-open').forEach((dd) => { dd.classList.remove('is-open'); $('button', dd).setAttribute('aria-expanded', 'false'); $('button', dd).focus(); });
    $$('.has-sub.is-open').forEach((li) => { li.classList.remove('is-open'); $('.sub-toggle', li).setAttribute('aria-expanded', 'false'); });
    if (document.body.classList.contains('nav-open')) { document.body.classList.remove('nav-open'); navToggle?.setAttribute('aria-expanded', 'false'); navToggle?.focus(); }
  });

  /* ------------------------------------------------------------ dialogs */
  function openDialog(d) {
    if (!d) return;
    if (typeof d.showModal === 'function') { if (!d.open) d.showModal(); } else { d.setAttribute('open', ''); }
  }
  function closeDialog(d) { if (!d) return; if (typeof d.close === 'function') d.close(); else d.removeAttribute('open'); }
  $$('[data-close-dialog]').forEach((b) => b.addEventListener('click', () => closeDialog(b.closest('dialog'))));
  $$('dialog').forEach((d) => d.addEventListener('click', (e) => { if (e.target === d) closeDialog(d); }));

  const searchDialog = $('#search-dialog');
  $$('[data-open-search]').forEach((b) => b.addEventListener('click', () => { openDialog(searchDialog); setTimeout(() => $('#global-search')?.focus(), 30); }));
  $$('[data-close-search]').forEach((b) => b.addEventListener('click', () => closeDialog(searchDialog)));
  document.addEventListener('keydown', (e) => {
    const tag = (e.target.tagName || '').toLowerCase();
    if (e.key === '/' && !['input', 'textarea', 'select'].includes(tag) && !e.target.isContentEditable && searchDialog) {
      e.preventDefault(); openDialog(searchDialog); $('#global-search')?.focus();
    }
  });

  /* ------------------------------------------------------------ autocomplete */
  $$('form[data-autocomplete]').forEach((form) => {
    const input = $('input[type="search"]', form);
    const list = $('.autocomplete', form);
    if (!input || !list) return;
    let timer = null; let active = -1; let ctrl = null;
    const close = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; };
    const render = (data) => {
      list.innerHTML = '';
      if (!data.items || !data.items.length) { close(); return; }
      data.items.forEach((it, i) => {
        const li = document.createElement('li');
        li.setAttribute('role', 'option'); li.id = list.id + '-opt-' + i;
        const a = document.createElement('a'); a.href = it.url;
        const img = document.createElement('img'); img.src = it.image; img.alt = ''; img.width = 44; img.height = 44;
        const span = document.createElement('span');
        const strong = document.createElement('strong'); strong.textContent = it.title;
        const small = document.createElement('small'); small.textContent = it.type + ' — ' + it.meta;
        span.append(strong, small); a.append(img, span); li.appendChild(a); list.appendChild(li);
      });
      if (data.all) {
        const li = document.createElement('li'); li.className = 'ac-all'; li.setAttribute('role', 'option'); li.id = list.id + '-opt-all';
        const a = document.createElement('a'); a.href = data.all.url; a.textContent = data.all.label; li.appendChild(a); list.appendChild(li);
      }
      list.hidden = false; input.setAttribute('aria-expanded', 'true');
    };
    input.addEventListener('input', () => {
      clearTimeout(timer);
      const q = input.value.trim();
      if (q.length < 2) { close(); return; }
      timer = setTimeout(() => {
        ctrl?.abort(); ctrl = new AbortController();
        const url = new URL(form.dataset.autocomplete, window.location.href);
        url.searchParams.set('q', q);
        url.searchParams.set('lang', document.documentElement.lang);
        fetch(url.toString(), { headers: { Accept: 'application/json' }, signal: ctrl.signal }).then((r) => r.json()).then(render).catch(() => {});
      }, 180);
    });
    input.addEventListener('keydown', (e) => {
      const opts = $$('[role="option"]', list);
      if (list.hidden || !opts.length) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        active = (active + (e.key === 'ArrowDown' ? 1 : -1) + opts.length) % opts.length;
        opts.forEach((o, i) => o.setAttribute('aria-selected', String(i === active)));
        input.setAttribute('aria-activedescendant', opts[active].id);
      } else if (e.key === 'Enter' && active >= 0) {
        e.preventDefault(); window.location.href = $('a', opts[active]).href;
      } else if (e.key === 'Escape') { close(); }
    });
    document.addEventListener('click', (e) => { if (!form.contains(e.target)) close(); });
  });

  /* ------------------------------------------------------------ cookie consent */
  const banner = $('#cookie-banner');
  const cookieDialog = $('#cookie-dialog');
  $$('[data-open-cookie-settings]').forEach((b) => b.addEventListener('click', () => openDialog(cookieDialog)));
  $$('form[data-cookie-form]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      if (e.submitter && e.submitter.name) fd.set(e.submitter.name, e.submitter.value);
      fetch(form.action, { method: 'POST', body: fd, headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then((r) => r.json())
        .then((data) => {
          if (!data.ok) throw new Error(data.error || 'error');
          if (banner) banner.hidden = true;
          closeDialog(cookieDialog);
          $$('#cookie-dialog input[name="preferences"]').forEach((i) => { i.checked = !!data.preferences; });
          $$('#cookie-dialog input[name="analytics"]').forEach((i) => { i.checked = !!data.analytics; });
          toast(data.message);
        })
        .catch(() => form.submit());
    });
  });

  /* ------------------------------------------------------------ AJAX forms (newsletter) */
  $$('form.js-ajax-form').forEach((form) => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const out = $('.form-feedback', form);
      const email = $('input[type="email"]', form);
      const consent = $('input[name="consent"]', form);
      const show = (msg, ok) => { out.innerHTML = ''; const d = document.createElement('div'); d.className = 'alert alert--' + (ok ? 'success' : 'error'); d.setAttribute('role', ok ? 'status' : 'alert'); d.textContent = msg; out.appendChild(d); };
      if (!email.value.trim() || !email.checkValidity()) { show(i18n.email || 'Invalid email', false); email.focus(); return; }
      if (consent && !consent.checked) { show(i18n.consent || 'Consent required', false); consent.focus(); return; }
      const btn = $('button[type="submit"]', form); btn.disabled = true;
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then((r) => r.json())
        .then((d) => { show(d.message || d.error, d.ok); if (d.ok) form.reset(); })
        .catch(() => show(i18n.error || 'Error', false))
        .finally(() => { btn.disabled = false; });
    });
  });

  /* ------------------------------------------------------------ favourites */
  document.addEventListener('submit', (e) => {
    const form = e.target.closest('.fav-form');
    if (!form) return;
    e.preventDefault();
    const btn = $('button', form);
    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then((r) => r.json().then((d) => ({ status: r.status, d })))
      .then(({ status, d }) => {
        if (status === 401 && d.login) { window.location.href = d.login; return; }
        if (!d.ok) throw new Error(d.error);
        $$('.fav-form').forEach((f) => {
          if ($('input[name="type"]', f).value === $('input[name="type"]', form).value && $('input[name="id"]', f).value === $('input[name="id"]', form).value) {
            const b = $('button', f);
            b.classList.toggle('is-on', d.on);
            b.setAttribute('aria-pressed', String(d.on));
            const label = d.on ? b.dataset.labelOn : b.dataset.labelOff;
            b.setAttribute('aria-label', label); b.title = label;
            const span = $('span', b); if (span) span.textContent = label;
          }
        });
        toast(d.message);
        btn.animate?.([{ transform: 'scale(1)' }, { transform: 'scale(1.25)' }, { transform: 'scale(1)' }], { duration: 260 });
      })
      .catch(() => form.submit());
  });

  /* ------------------------------------------------------------ share */
  $$('[data-copy]').forEach((b) => b.addEventListener('click', () => {
    const done = () => toast(b.dataset.copied || 'Copied');
    if (navigator.clipboard) navigator.clipboard.writeText(b.dataset.copy).then(done).catch(() => window.prompt('', b.dataset.copy));
    else window.prompt('', b.dataset.copy);
  }));
  if (navigator.share) {
    $$('.js-native-share').forEach((b) => {
      b.hidden = false;
      b.addEventListener('click', () => navigator.share({ title: b.dataset.title, url: b.dataset.url }).catch(() => {}));
    });
  }

  /* ------------------------------------------------------------ countdown */
  $$('[data-countdown]').forEach((el) => {
    const target = new Date(el.dataset.countdown).getTime();
    const units = { d: $('[data-unit="d"]', el), h: $('[data-unit="h"]', el), m: $('[data-unit="m"]', el), s: $('[data-unit="s"]', el) };
    const tick = () => {
      let diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
      units.d.textContent = Math.floor(diff / 86400); diff %= 86400;
      units.h.textContent = String(Math.floor(diff / 3600)).padStart(2, '0'); diff %= 3600;
      units.m.textContent = String(Math.floor(diff / 60)).padStart(2, '0');
      units.s.textContent = String(diff % 60).padStart(2, '0');
    };
    tick(); setInterval(tick, 1000);
  });

  /* ------------------------------------------------------------ misc */
  document.addEventListener('click', (e) => {
    const d = e.target.closest('[data-dismiss]');
    if (d) d.closest('.alert')?.remove();
    if (e.target.closest('[data-print]')) window.print();
  });
  $$('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => { if (!window.confirm(f.dataset.confirm)) e.preventDefault(); }));

  /* ------------------------------------------------------------ client-side validation (server re-validates) */
  const showError = (input, msg) => {
    const field = input.closest('.field');
    if (!field) return;
    let p = $('.field__error', field);
    if (!msg) { if (p && p.dataset.js) p.remove(); input.removeAttribute('aria-invalid'); field.classList.remove('has-error'); return; }
    if (!p) { p = document.createElement('p'); p.className = 'field__error'; p.dataset.js = '1'; p.id = input.id + '-err'; field.appendChild(p);
      input.setAttribute('aria-describedby', ((input.getAttribute('aria-describedby') || '') + ' ' + p.id).trim()); }
    p.textContent = msg; input.setAttribute('aria-invalid', 'true'); field.classList.add('has-error');
  };
  const messageFor = (input) => {
    const v = input.validity;
    if (input.dataset.match) { const other = document.getElementById(input.dataset.match); if (other && other.value !== input.value) return i18n.match; }
    if (v.valid) return '';
    if (v.valueMissing) return input.type === 'checkbox' ? i18n.check : i18n.required;
    if (v.typeMismatch && input.type === 'email') return i18n.email;
    if (v.tooShort) return (i18n.minlength || '').replace(':n', input.minLength);
    if (v.patternMismatch) return i18n.pattern;
    return input.validationMessage;
  };
  $$('form[data-validate]').forEach((form) => {
    form.setAttribute('novalidate', '');
    form.addEventListener('submit', (e) => {
      let first = null;
      $$('input, select, textarea', form).forEach((input) => {
        if (input.type === 'hidden' || input.disabled || input.closest('[hidden]')) return;
        const msg = messageFor(input);
        showError(input, msg);
        if (msg && !first) first = input;
      });
      if (first) { e.preventDefault(); e.stopImmediatePropagation(); first.focus(); }
    });
    $$('input, select, textarea', form).forEach((input) => {
      input.addEventListener('blur', () => { if (input.value !== '' || input.getAttribute('aria-invalid')) showError(input, messageFor(input)); });
    });
  });

  /* password strength */
  $$('[data-pw-meter]').forEach((input) => {
    const bar = input.closest('div:not(.field)')?.querySelector('.pw-meter span');
    if (!bar) return;
    input.addEventListener('input', () => {
      const v = input.value; let s = 0;
      if (v.length >= 8) s++; if (v.length >= 12) s++; if (/\d/.test(v) && /[a-zа-ш]/i.test(v)) s++; if (/[^\w\s]/.test(v) || /[A-ZА-Ш]/.test(v)) s++;
      bar.style.width = (s * 25) + '%';
      bar.style.background = ['#8e2c2c', '#8e2c2c', '#8a5a00', '#2c8b93', '#2f6b45'][s];
    });
  });

  /* FAQ instant filter */
  const faqForm = $('[data-faq-filter]');
  if (faqForm) {
    const input = $('input[type="search"]', faqForm);
    input.addEventListener('input', () => {
      const q = input.value.trim().toLowerCase(); let shown = 0;
      $$('.faq-item').forEach((d) => { const m = !q || d.textContent.toLowerCase().includes(q); d.hidden = !m; if (m) shown++; if (q && m) d.open = true; });
      $$('[data-faq-group]').forEach((g) => { g.hidden = !$$('.faq-item', g).some((d) => !d.hidden); });
      const empty = $('[data-faq-empty]'); if (empty) empty.hidden = shown > 0;
    });
  }
})();
