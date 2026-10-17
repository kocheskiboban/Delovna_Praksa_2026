/* Accessible lightbox for [data-lightbox] links inside [data-lightbox-group]. */
(function () {
  'use strict';
  const groups = document.querySelectorAll('[data-lightbox-group]');
  if (!groups.length) return;
  const lang = document.documentElement.lang;
  const L = { mk: ['Затвори', 'Претходна', 'Следна'], en: ['Close', 'Previous', 'Next'], sq: ['Mbyll', 'E mëparshmja', 'Tjetra'],
    de: ['Schließen', 'Zurück', 'Weiter'], fr: ['Fermer', 'Précédente', 'Suivante'], it: ['Chiudi', 'Precedente', 'Successiva'] }[lang] || ['Close', 'Previous', 'Next'];
  let items = []; let index = 0; let box = null; let lastFocus = null;

  function build() {
    box = document.createElement('div');
    box.className = 'lightbox'; box.setAttribute('role', 'dialog'); box.setAttribute('aria-modal', 'true'); box.hidden = true;
    box.innerHTML = '<div class="lightbox__top"><span class="lightbox__count" aria-live="polite"></span>'
      + '<button type="button" class="icon-btn" data-lb="close"><svg class="icon"><use href="#i-close"/></svg></button></div>'
      + '<div class="lightbox__stage"><button type="button" class="icon-btn lightbox__prev" data-lb="prev"><svg class="icon"><use href="#i-chevron-left"/></svg></button>'
      + '<img alt=""><button type="button" class="icon-btn lightbox__next" data-lb="next"><svg class="icon"><use href="#i-chevron-right"/></svg></button></div>'
      + '<p class="lightbox__caption"></p>';
    box.querySelector('[data-lb="close"]').setAttribute('aria-label', L[0]);
    box.querySelector('[data-lb="prev"]').setAttribute('aria-label', L[1]);
    box.querySelector('[data-lb="next"]').setAttribute('aria-label', L[2]);
    document.body.appendChild(box);
    box.addEventListener('click', (e) => {
      const b = e.target.closest('[data-lb]');
      if (b) { ({ close, prev: () => show(index - 1), next: () => show(index + 1) })[b.dataset.lb](); }
      else if (e.target === box || e.target.classList.contains('lightbox__stage')) close();
    });
    box.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') show(index - 1);
      if (e.key === 'ArrowRight') show(index + 1);
      if (e.key === 'Tab') { // keep focus inside
        const f = [...box.querySelectorAll('button')]; const first = f[0]; const last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    let x0 = null;
    box.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', (e) => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1)); x0 = null; });
  }
  function show(i) {
    index = (i + items.length) % items.length;
    const a = items[index];
    const img = box.querySelector('img');
    img.src = a.href; img.alt = a.querySelector('img')?.alt || '';
    box.querySelector('.lightbox__caption').textContent = a.dataset.caption || '';
    box.querySelector('.lightbox__count').textContent = (index + 1) + ' / ' + items.length;
  }
  function open(group, a) {
    if (!box) build();
    items = [...group.querySelectorAll('[data-lightbox]')];
    lastFocus = document.activeElement;
    box.setAttribute('aria-label', a.dataset.caption || '');
    show(items.indexOf(a));
    box.hidden = false; document.body.style.overflow = 'hidden';
    box.querySelector('[data-lb="close"]').focus();
  }
  function close() { box.hidden = true; document.body.style.overflow = ''; lastFocus?.focus(); }
  groups.forEach((g) => g.addEventListener('click', (e) => {
    const a = e.target.closest('[data-lightbox]');
    if (!a) return;
    e.preventDefault(); open(g, a);
  }));
})();
