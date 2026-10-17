/* Interactive seat map — renders an accessible SVG from #seatmap-data.
   Mouse/touch: click a seat. Keyboard: arrow keys move, Enter/Space selects. */
(function () {
  'use strict';
  const host = document.querySelector('[data-seatmap]');
  const dataEl = document.getElementById('seatmap-data');
  if (!host || !dataEl) return;
  const data = JSON.parse(dataEl.textContent);
  const NS = 'http://www.w3.org/2000/svg';
  const form = document.querySelector('[data-seat-form]');
  const inputs = form.querySelector('[data-seat-inputs]');
  const list = form.querySelector('[data-selected-list]');
  const counter = form.querySelector('[data-seat-counter]');
  const submit = form.querySelector('[data-seat-submit]');
  const T = data.i18n;
  const selected = new Map(); // id -> seat
  const byId = new Map(data.seats.map((s) => [s.id, s]));

  const label = (s) => T.label.replace(':section', data.sections[s.s] || s.s).replace(':row', s.r).replace(':seat', s.n);
  const describe = (s, st) => {
    const extra = s.t === 'vip' ? ', ' + T.vip : s.t === 'accessible' ? ', ' + T.accessible : '';
    const state = { available: T.available, occupied: T.occupied, unavailable: T.unavailable, selected: T.selected }[st] || '';
    return label(s) + extra + ' — ' + state;
  };
  const el = (name, attrs, text) => {
    const n = document.createElementNS(NS, name);
    Object.entries(attrs || {}).forEach(([k, v]) => n.setAttribute(k, v));
    if (text) n.textContent = text;
    return n;
  };

  // ---------- draw
  const svg = el('svg', { class: 'seatmap', viewBox: '0 0 1000 ' + data.height, role: 'group', 'aria-label': T.stage + ' / ' + data.need });
  svg.dataset.zoom = '1';
  if (data.layout === 'amphitheatre') {
    svg.appendChild(el('path', { class: 'stage', d: 'M380,70 A120,120 0 0 0 620,70 Z' }));
    svg.appendChild(el('text', { class: 'stage-label', x: 500, y: 100, 'text-anchor': 'middle' }, T.stage));
  } else {
    svg.appendChild(el('rect', { class: 'stage', x: 300, y: 40, width: 400, height: 56, rx: 8 }));
    svg.appendChild(el('text', { class: 'stage-label', x: 500, y: 74, 'text-anchor': 'middle' }, T.stage));
  }
  // section labels + row numbers
  const groups = {};
  data.seats.forEach((s) => { (groups[s.s] = groups[s.s] || []).push(s); });
  Object.entries(groups).forEach(([sec, seats]) => {
    if (Object.keys(groups).length > 1) {
      const minY = Math.min(...seats.map((s) => s.y));
      const cx = seats.reduce((a, s) => a + s.x, 0) / seats.length;
      svg.appendChild(el('text', { class: 'section-label', x: Math.round(cx), y: minY - 16, 'text-anchor': 'middle' }, data.sections[sec] || sec));
    }
    const rows = {};
    seats.forEach((s) => { if (!rows[s.r] || s.n < rows[s.r].n) rows[s.r] = s; });
    Object.values(rows).forEach((s) => {
      if (data.layout === 'amphitheatre' && sec !== 'L') return;
      if ((sec === 'B' && data.layout !== 'hall') || sec === 'R') return;
      svg.appendChild(el('text', { class: 'section-label', x: s.x - 20, y: s.y + 4, 'text-anchor': 'end', 'font-size': '10' }, s.r));
    });
  });
  const nodes = new Map();
  data.seats.forEach((s) => {
    const g = el('g', { class: 'seat', 'data-st': s.st === 'mine' ? 'selected' : s.st, 'data-t': s.t, 'data-id': s.id, tabindex: '-1' });
    const interactive = s.st === 'available' || s.st === 'mine';
    g.setAttribute('role', 'button');
    if (interactive) g.setAttribute('aria-pressed', s.st === 'mine' ? 'true' : 'false'); else g.setAttribute('aria-disabled', 'true');
    g.setAttribute('aria-label', describe(s, s.st === 'mine' ? 'selected' : s.st));
    g.appendChild(el('circle', { cx: s.x, cy: s.y, r: 9 }));
    if (s.t === 'accessible') g.appendChild(el('text', { x: s.x, y: s.y + 3.5, 'text-anchor': 'middle', 'font-size': '9', fill: '#102a36', 'pointer-events': 'none' }, '♿'));
    svg.appendChild(g);
    nodes.set(s.id, g);
    if (s.st === 'mine') selected.set(s.id, s);
  });
  host.innerHTML = '';
  host.appendChild(svg);

  // roving tabindex: first selectable seat
  const focusables = data.seats.filter((s) => s.st === 'available' || s.st === 'mine');
  let current = (focusables.find((s) => s.st === 'mine') || focusables[0] || data.seats[0]);
  if (current) nodes.get(current.id).setAttribute('tabindex', '0');

  // ---------- state
  function sync() {
    inputs.innerHTML = '';
    list.innerHTML = '';
    [...selected.values()].forEach((s) => {
      const i = document.createElement('input'); i.type = 'hidden'; i.name = 'seats[]'; i.value = s.id; inputs.appendChild(i);
      const li = document.createElement('li'); li.textContent = label(s) + (s.t === 'vip' ? ' · VIP' : ''); list.appendChild(li);
    });
    counter.textContent = selected.size + ' / ' + data.need;
    submit.disabled = selected.size !== data.need;
  }
  function toggle(s) {
    const g = nodes.get(s.id);
    const st = g.getAttribute('data-st');
    if (st === 'occupied' || st === 'unavailable') return;
    if (st === 'selected') {
      selected.delete(s.id); g.setAttribute('data-st', 'available'); g.setAttribute('aria-pressed', 'false');
      g.setAttribute('aria-label', describe(s, 'available'));
    } else {
      if (selected.size >= data.need) { window.OhridToast ? window.OhridToast(T.limit, 'error') : alert(T.limit); return; }
      selected.set(s.id, s); g.setAttribute('data-st', 'selected'); g.setAttribute('aria-pressed', 'true');
      g.setAttribute('aria-label', describe(s, 'selected'));
    }
    sync();
  }
  function focusSeat(s) {
    if (!s) return;
    nodes.get(current.id)?.setAttribute('tabindex', '-1');
    current = s;
    const g = nodes.get(s.id);
    g.setAttribute('tabindex', '0');
    g.focus({ preventScroll: false });
  }
  function nearest(from, dx, dy) {
    let best = null; let bestD = Infinity;
    focusables.forEach((s) => {
      if (s.id === from.id) return;
      const vx = s.x - from.x; const vy = s.y - from.y;
      const along = vx * dx + vy * dy;
      if (along <= 4) return;
      const across = Math.abs(vx * dy - vy * dx);
      const d = along + across * 2.5;
      if (d < bestD) { bestD = d; best = s; }
    });
    return best;
  }

  svg.addEventListener('click', (e) => {
    const g = e.target.closest('.seat');
    if (!g) return;
    const s = byId.get(Number(g.dataset.id));
    if (focusables.includes(s)) { nodes.get(current.id)?.setAttribute('tabindex', '-1'); current = s; g.setAttribute('tabindex', '0'); }
    toggle(s);
  });
  svg.addEventListener('keydown', (e) => {
    const map = { ArrowRight: [1, 0], ArrowLeft: [-1, 0], ArrowDown: [0, 1], ArrowUp: [0, -1] };
    if (map[e.key]) { e.preventDefault(); focusSeat(nearest(current, ...map[e.key])); }
    else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(current); }
  });

  // tooltip
  const tip = document.createElement('div'); tip.className = 'seat-tooltip'; tip.hidden = true; document.body.appendChild(tip);
  svg.addEventListener('mousemove', (e) => {
    const g = e.target.closest('.seat');
    if (!g) { tip.hidden = true; return; }
    const s = byId.get(Number(g.dataset.id));
    const price = s.t === 'vip' && data.vip ? data.vip : data.price;
    tip.textContent = describe(s, g.getAttribute('data-st')) + (['available', 'selected'].includes(g.getAttribute('data-st')) ? ' · ' + Math.round(price) + ' MKD' : '');
    tip.hidden = false;
    tip.style.left = (e.clientX + 14) + 'px'; tip.style.top = (e.clientY + 14) + 'px';
  });
  svg.addEventListener('mouseleave', () => { tip.hidden = true; });

  // zoom
  document.querySelectorAll('[data-zoom]').forEach((b) => b.addEventListener('click', () => {
    const z = Math.min(3, Math.max(1, Number(svg.dataset.zoom) + Number(b.dataset.zoom)));
    svg.dataset.zoom = String(z);
  }));
  sync();
})();
