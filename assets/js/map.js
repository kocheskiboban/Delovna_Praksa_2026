/* Festival map: clicking a pin updates the venue panel without reloading (links still work without JS). */
(function () {
  'use strict';
  const dataEl = document.getElementById('fmap-data');
  const panel = document.getElementById('venue-panel');
  if (!dataEl || !panel) return;
  const data = JSON.parse(dataEl.textContent);
  const set = (k, fn) => { const n = panel.querySelector('[data-p="' + k + '"]'); if (n) fn(n); };

  function select(slug, focusPanel) {
    const v = data.venues[slug];
    if (!v) return;
    set('image', (n) => { n.src = v.image; });
    set('name', (n) => { n.textContent = v.name; });
    set('description', (n) => { n.textContent = v.description; });
    set('address', (n) => { n.textContent = v.address; });
    set('capacity', (n) => { n.textContent = v.capacity; });
    set('url', (n) => { n.href = v.url; });
    set('events', (n) => {
      n.innerHTML = '';
      if (!v.events.length) { const li = document.createElement('li'); li.className = 'muted'; li.textContent = data.empty; n.appendChild(li); }
      v.events.forEach((ev) => {
        const li = document.createElement('li'); const a = document.createElement('a'); a.href = ev.url; a.textContent = ev.title;
        const s = document.createElement('span'); s.className = 'muted'; s.textContent = ev.date;
        li.append(a, document.createElement('br'), s); n.appendChild(li);
      });
    });
    document.querySelectorAll('.fmap__pin').forEach((p) => p.setAttribute('aria-pressed', String(p.dataset.venue === slug)));
    history.replaceState(null, '', '?' + new URLSearchParams({ ...Object.fromEntries(new URLSearchParams(location.search)), venue: slug }).toString());
    if (focusPanel && window.innerWidth < 1180) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
  document.querySelectorAll('[data-venue]').forEach((a) => a.addEventListener('click', (e) => {
    e.preventDefault();
    select(a.dataset.venue, true);
  }));
})();
