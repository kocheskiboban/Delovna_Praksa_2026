/* Booking flow helpers: quantity steppers, hold countdown, demo payment form. */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  /* ---------- step 1: quantities */
  const qtyForm = $('[data-qty-form]');
  if (qtyForm) {
    const max = Number(qtyForm.dataset.max) || 10;
    const cur = qtyForm.dataset.currency || ' MKD';
    const inputs = $$('input[type="number"]', qtyForm);
    const fmt = (n) => Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + cur;
    const update = () => {
      let total = 0; let sum = 0;
      inputs.forEach((i) => { const n = Math.max(0, parseInt(i.value || '0', 10)); total += n; sum += n * Number(i.dataset.unit); });
      $('[data-qty-total]', qtyForm).textContent = total;
      $('[data-qty-sum]', qtyForm).textContent = fmt(sum);
      $$('[data-step="1"]', qtyForm).forEach((b) => { b.disabled = total >= max; });
    };
    $$('[data-stepper]', qtyForm).forEach((st) => {
      const input = $('input', st);
      $$('button', st).forEach((b) => b.addEventListener('click', () => {
        const total = inputs.reduce((a, i) => a + (parseInt(i.value || '0', 10) || 0), 0);
        const step = Number(b.dataset.step);
        if (step > 0 && total >= max) return;
        input.value = Math.max(0, (parseInt(input.value || '0', 10) || 0) + step);
        update();
      }));
      input.addEventListener('input', update);
    });
    update();
  }

  /* ---------- hold timer */
  $$('[data-hold-until]').forEach((el) => {
    const until = new Date(el.dataset.holdUntil).getTime();
    const clock = $('[data-hold-clock]', el);
    const tick = () => {
      const s = Math.max(0, Math.floor((until - Date.now()) / 1000));
      clock.textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
      if (s === 0) { el.classList.add('is-expired'); el.querySelector('span').textContent = el.dataset.expiredText; clearInterval(timer); }
    };
    const timer = setInterval(tick, 1000); tick();
  });

  /* ---------- step 4: payment */
  const pay = $('[data-payment-form]');
  if (pay) {
    const labels = $('[data-pay-labels]')?.content?.firstElementChild;
    const cardMethods = ['visa', 'mastercard', 'maestro'];
    const panels = $$('[data-pay-panel]', pay);
    const setMethod = (m) => {
      panels.forEach((p) => {
        const show = p.dataset.payPanel === (cardMethods.includes(m) ? 'card' : m);
        p.hidden = !show;
        $$('input', p).forEach((i) => { i.disabled = !show; });
      });
      if (labels) $('[data-pay-label]', pay).textContent = cardMethods.includes(m) ? labels.dataset.card : labels.dataset.other;
    };
    $$('input[name="method"]', pay).forEach((r) => r.addEventListener('change', () => setMethod(r.value)));
    setMethod($('input[name="method"]:checked', pay)?.value || 'visa');

    const num = $('[data-card-number]', pay);
    const exp = $('[data-card-expiry]', pay);
    const name = $('input[name="card_name"]', pay);
    const prevNum = $('[data-card-preview]', pay);
    const prevExp = $('[data-exp-preview]', pay);
    const prevName = $('[data-name-preview]', pay);
    const detect = (d) => /^4/.test(d) ? 'visa' : /^(5[1-5]|2[2-7])/.test(d) ? 'mastercard' : /^(50|5[6-9]|6)/.test(d) ? 'maestro' : null;
    num?.addEventListener('input', () => {
      const d = num.value.replace(/\D/g, '').slice(0, 19);
      num.value = d.replace(/(.{4})/g, '$1 ').trim();
      prevNum.textContent = (d + '•'.repeat(Math.max(0, 16 - d.length))).replace(/(.{4})/g, '$1 ').trim();
      const brand = detect(d);
      if (brand) { const r = $('input[name="method"][value="' + brand + '"]', pay); if (r && !r.checked) { r.checked = true; setMethod(brand); } }
    });
    exp?.addEventListener('input', () => {
      let d = exp.value.replace(/\D/g, '').slice(0, 4);
      if (d.length >= 3) d = d.slice(0, 2) + '/' + d.slice(2);
      exp.value = d; prevExp.textContent = d || 'MM/YY';
    });
    name?.addEventListener('input', () => { prevName.textContent = name.value || '—'; });
    if (name) prevName.textContent = name.value || prevName.textContent;

    // processing state: show the overlay, then submit for real
    let sending = false;
    pay.addEventListener('submit', (e) => {
      if (sending) return;
      e.preventDefault();
      sending = true;
      const ov = document.createElement('div');
      ov.className = 'processing'; ov.setAttribute('role', 'alert'); ov.setAttribute('aria-live', 'assertive');
      ov.innerHTML = '<div class="processing__box"><div class="spinner"></div><h2 style="color:#fff"></h2><p></p></div>';
      ov.querySelector('h2').textContent = pay.dataset.processing;
      ov.querySelector('p').textContent = pay.dataset.processingSub;
      document.body.appendChild(ov);
      $('[data-pay-button]', pay).disabled = true;
      setTimeout(() => HTMLFormElement.prototype.submit.call(pay), 1400);
    });
  }
})();
