/* Admin helpers: language tabs, seat blocking map, slug preview. */
(function () {
  'use strict';
  /* tabs */
  document.querySelectorAll('[data-tabs]').forEach((tabs) => {
    const buttons = [...tabs.querySelectorAll('[role="tab"]')];
    const panels = buttons.map((b) => document.getElementById(b.getAttribute('aria-controls')));
    const activate = (i) => {
      buttons.forEach((b, j) => { b.setAttribute('aria-selected', String(i === j)); b.tabIndex = i === j ? 0 : -1; });
      panels.forEach((p, j) => { p.hidden = i !== j; });
    };
    // open the first tab that has an error
    const errIdx = panels.findIndex((p) => p.querySelector('.has-error'));
    activate(errIdx >= 0 ? errIdx : 0);
    buttons.forEach((b, i) => {
      b.addEventListener('click', () => activate(i));
      b.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
          const n = (i + (e.key === 'ArrowRight' ? 1 : -1) + buttons.length) % buttons.length;
          activate(n); buttons[n].focus();
        }
      });
    });
  });

  /* seat blocking */
  const host = document.querySelector('[data-admin-seatmap]');
  const dataEl = document.getElementById('admin-seat-data');
  if (host && dataEl) {
    const seats = JSON.parse(dataEl.textContent);
    const NS = 'http://www.w3.org/2000/svg';
    const inputs = document.querySelector('[data-blocked-inputs]');
    const h = Math.max(...seats.map((s) => s.y)) + 40;
    const svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('viewBox', '0 0 1000 ' + h); svg.setAttribute('class', 'seatmap');
    const stage = document.createElementNS(NS, host.dataset.layout === 'amphitheatre' ? 'path' : 'rect');
    if (host.dataset.layout === 'amphitheatre') stage.setAttribute('d', 'M380,70 A120,120 0 0 0 620,70 Z');
    else { stage.setAttribute('x', 300); stage.setAttribute('y', 40); stage.setAttribute('width', 400); stage.setAttribute('height', 56); stage.setAttribute('rx', 8); }
    stage.setAttribute('class', 'stage'); svg.appendChild(stage);
    const stMap = { available: 'available', sold: 'occupied', blocked: 'unavailable', inactive: 'unavailable' };
    const sync = () => {
      inputs.innerHTML = '';
      seats.filter((s) => s.st === 'blocked').forEach((s) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'blocked[]'; i.value = s.id; inputs.appendChild(i); });
    };
    seats.forEach((s) => {
      const g = document.createElementNS(NS, 'g');
      g.setAttribute('class', 'seat'); g.setAttribute('data-st', stMap[s.st]); g.setAttribute('data-t', s.t);
      const editable = s.st === 'available' || s.st === 'blocked';
      if (editable) { g.setAttribute('tabindex', '0'); g.setAttribute('role', 'switch'); g.setAttribute('aria-checked', String(s.st === 'blocked')); }
      g.setAttribute('aria-label', s.l);
      const t = document.createElementNS(NS, 'title'); t.textContent = s.l + ' (' + s.st + ')'; g.appendChild(t);
      const c = document.createElementNS(NS, 'circle'); c.setAttribute('cx', s.x); c.setAttribute('cy', s.y); c.setAttribute('r', 9); g.appendChild(c);
      const toggle = () => {
        if (!editable) return;
        s.st = s.st === 'blocked' ? 'available' : 'blocked';
        g.setAttribute('data-st', stMap[s.st]); g.setAttribute('aria-checked', String(s.st === 'blocked')); sync();
      };
      g.addEventListener('click', toggle);
      g.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
      svg.appendChild(g);
    });
    host.appendChild(svg);
  }
})();
document.querySelectorAll('select[data-autosubmit]').forEach((s) => s.addEventListener('change', () => s.form.submit()));
