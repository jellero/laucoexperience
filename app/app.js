'use strict';

const routes = [
  {id:'vinadia',name:'Anello della Forra del Vinadia',area:'Lauco · Avaglio',type:'Trekking',km:8.6,time:'3 h 20',up:410,diff:'Media',level:'',img:'../assets/img/home2.jpg',x:42,y:48,desc:'Un itinerario ad anello tra boschi, borghi e scorci sulla forra del Vinadia. Il tracciato alterna sentieri, strade forestali e brevi tratti asfaltati.'},
  {id:'arvenis',name:'Panoramica del Monte Arvenis',area:'Vinaio · Val di Lauco',type:'Trekking',km:11.8,time:'4 h 45',up:760,diff:'Impegnativa',level:'hard',img:'../assets/img/alpini.jpg',x:69,y:31,desc:'Una salita panoramica verso gli ambienti aperti dell’Arvenis, con ampie vedute sulla Carnia. Consigliata a escursionisti allenati e con meteo stabile.'},
  {id:'borghi',name:'Borghi dell’altopiano',area:'Lauco · Trava · Avaglio',type:'Passeggiata',km:5.4,time:'1 h 55',up:180,diff:'Facile',level:'easy',img:'../assets/img/contact.jpg',x:31,y:66,desc:'Una passeggiata che collega piccoli borghi, fontane e punti panoramici. Ideale per una mezza giornata lenta e accessibile.'},
  {id:'mtb',name:'Lauco MTB Explorer',area:'Lauco · Allegnidis',type:'MTB',km:22.3,time:'2 h 50',up:690,diff:'Media',level:'',img:'../assets/img/cronoradima.jpg',x:74,y:68,desc:'Un anello per mountain bike tra piste forestali, salite regolari e discese panoramiche. Fondo misto, prevalentemente sterrato.'}
];

const state = {
  page: location.hash.slice(1) || 'home',
  favorites: new Set(JSON.parse(localStorage.getItem('lauco-demo:favorites') || '[]')),
  offline: new Set(JSON.parse(localStorage.getItem('lauco-demo:offline') || '[]')),
  filter: 'Tutti',
  query: '',
  activeRoute: 'vinadia',
  installPrompt: null
};

const view = document.querySelector('#view');
const nav = document.querySelector('#nav');
const modal = document.querySelector('#modal');
const toastRoot = document.querySelector('#toast');
const icon = name => `<svg aria-hidden="true"><use href="#i-${name}"></use></svg>`;

const navItems = [
  ['home','Home','home'],
  ['explore','Esplora','compass'],
  ['map','Mappa','map'],
  ['report','Segnala','flag'],
  ['saved','Salvati','heart']
];

function renderNav() {
  nav.innerHTML = navItems.map(([page,label,iconName]) => `
    <button type="button" data-go="${page}" class="${state.page === page ? 'active ' : ''}${page === 'report' ? 'report' : ''}">
      ${page === 'report' ? `<i>${icon(iconName)}</i>` : icon(iconName)}<span>${label}</span>
    </button>`).join('');
}

function routeCard(route) {
  return `
    <article class="route-card" data-route="${route.id}" tabindex="0">
      <div class="route-photo">
        <img src="${route.img}" alt="Paesaggio del percorso ${route.name}" loading="lazy">
        <span class="badge">${route.type}</span>
        <button class="icon-btn favorite ${state.favorites.has(route.id) ? 'active' : ''}" type="button" data-favorite="${route.id}" aria-label="Salva nei preferiti">${icon('heart')}</button>
      </div>
      <div class="route-body">
        <h3>${route.name}</h3>
        <div class="route-meta">
          <span>${icon('route')}${route.km} km</span>
          <span>${icon('clock')}${route.time}</span>
          <span>${icon('up')}+${route.up} m</span>
        </div>
        <div class="route-foot">
          <span class="difficulty ${route.level}">${route.diff}</span>
          <span>${state.offline.has(route.id) ? '✓ Offline' : route.area}</span>
        </div>
      </div>
    </article>`;
}

function homeView() {
  return `
    <section class="hero">
      <div>
        <span class="pill">⌖ Altopiano di Lauco · Carnia</span>
        <h1>La montagna,<br>nel tuo ritmo.</h1>
        <p>Percorsi, borghi e natura da esplorare. Porta le mappe con te, anche dove il segnale non arriva.</p>
        <button class="btn btn-primary" type="button" data-go="explore">Esplora i percorsi →</button>
        <button class="btn" type="button" style="background:#ffffff22;color:#fff" data-go="map">Apri la mappa</button>
      </div>
    </section>
    <section class="quick-grid">
      <button class="quick-card" type="button" data-go="map">${icon('nav')}<b>Vicino a me</b><small>Esplora i dintorni</small></button>
      <button class="quick-card" type="button" data-go="offline">${icon('download')}<b>Mappe offline</b><small>${state.offline.size} scaricate</small></button>
      <button class="quick-card" type="button" data-go="report">${icon('flag')}<b>Segnala</b><small>Nota dal sentiero</small></button>
      <button class="quick-card" type="button" data-go="saved">${icon('heart')}<b>Preferiti</b><small>${state.favorites.size} salvati</small></button>
    </section>
    <div class="section-head"><div><p class="eyebrow">Scelti per te</p><h2>Parti da qui</h2></div><button class="text-btn" type="button" data-go="explore">Vedi tutti →</button></div>
    <section class="route-grid">${routes.slice(0,3).map(routeCard).join('')}</section>`;
}

function exploreView() {
  const filtered = routes.filter(route => {
    const filterMatches = state.filter === 'Tutti' || (state.filter === 'Facile' ? route.diff === 'Facile' : route.type === state.filter);
    const queryMatches = `${route.name} ${route.area}`.toLowerCase().includes(state.query.toLowerCase());
    return filterMatches && queryMatches;
  });
  const filters = ['Tutti','Trekking','Passeggiata','MTB','Facile'];
  return `
    <div class="section-head"><div><p class="eyebrow">Esplora</p><h1>Trova il tuo percorso</h1></div></div>
    <div class="search-row"><label>${icon('search')}<input id="route-search" type="search" value="${escapeHtml(state.query)}" placeholder="Cerca luogo o percorso"></label><button class="icon-btn" type="button" aria-label="Filtri">☰</button></div>
    <div class="chips">${filters.map(filter => `<button class="chip ${state.filter === filter ? 'active' : ''}" type="button" data-filter="${filter}">${filter}</button>`).join('')}</div>
    ${filtered.length ? `<section class="route-grid">${filtered.map(routeCard).join('')}</section>` : emptyState('Nessun percorso trovato','Cambia ricerca o filtro.')}`;
}

function mapView() {
  const active = routes.find(route => route.id === state.activeRoute) || routes[0];
  return `
    <div class="section-head"><div><p class="eyebrow">Mappa</p><h1>Esplora Lauco</h1></div></div>
    <section class="map-panel" aria-label="Mappa topografica dimostrativa">
      <svg class="map-bg" viewBox="0 0 1000 700" preserveAspectRatio="none" aria-hidden="true">
        <rect width="1000" height="700" fill="#dce8d5"/>
        <g fill="none" stroke="#28605022" stroke-width="2"><path d="M-50 150C180 20 260 160 420 130s250-120 620 20"/><path d="M-50 220C180 90 260 230 420 200s250-120 620 20"/><path d="M-50 300C180 170 260 310 420 280s250-120 620 20"/><path d="M-50 390C180 260 260 400 420 370s250-120 620 20"/><path d="M-50 490C180 360 260 500 420 470s250-120 620 20"/></g>
        <path class="map-route" d="M210 500 330 400 430 430 530 315 635 350 740 250 820 330"/>
        <path class="map-route-highlight" d="M210 500 330 400 430 430 530 315 635 350 740 250 820 330"/>
      </svg>
      ${routes.map(route => `<button class="map-pin ${route.id === active.id ? 'active' : ''}" style="left:${route.x}%;top:${route.y}%" type="button" data-map-route="${route.id}" aria-label="${route.name}">${icon(route.type === 'MTB' ? 'route' : 'compass')}</button>`).join('')}
      <div class="map-tools"><button class="icon-btn" type="button" data-locate aria-label="Rileva posizione">${icon('nav')}</button><button class="icon-btn" type="button" data-demo="Zoom aumentato">＋</button><button class="icon-btn" type="button" data-demo="Zoom ridotto">−</button></div>
      <article class="map-sheet" data-route="${active.id}"><img src="${active.img}" alt=""><div><h3>${active.name}</h3><p>${active.km} km · ${active.time} · ${active.diff}</p></div><button class="icon-btn" type="button" aria-label="Apri">›</button></article>
    </section>`;
}

function collectionView(offline = false) {
  const selected = routes.filter(route => (offline ? state.offline : state.favorites).has(route.id));
  return `
    <div class="section-head"><div><p class="eyebrow">${offline ? 'Senza rete' : 'Raccolta'}</p><h1>${offline ? 'Mappe offline' : 'I tuoi percorsi'}</h1></div></div>
    ${selected.length ? `<section class="route-grid">${selected.map(routeCard).join('')}</section>` : emptyState(offline ? 'Nessuna mappa scaricata' : 'Ancora nessun preferito',offline ? 'Apri un percorso e premi “Scarica offline”.' : 'Salva i percorsi che ti interessano.')}`;
}

function profileView() {
  return `
    <div class="section-head"><div><p class="eyebrow">Profilo demo</p><h1>Il tuo spazio</h1></div></div>
    <section class="profile-card"><h2>Esploratore JE</h2><p>${state.favorites.size} preferiti · ${state.offline.size} mappe offline · ${localStorage.getItem('lauco-demo:reports') || 0} segnalazioni</p><button class="btn btn-primary" type="button" data-install>Installa la web app</button></section>`;
}

function emptyState(title, text) {
  return `<section class="empty-state"><h2>${title}</h2><p>${text}</p><button class="btn btn-dark" type="button" data-go="explore">Esplora i percorsi</button></section>`;
}

function render() {
  renderNav();
  window.scrollTo(0,0);
  if (state.page === 'explore') view.innerHTML = exploreView();
  else if (state.page === 'map') view.innerHTML = mapView();
  else if (state.page === 'saved') view.innerHTML = collectionView(false);
  else if (state.page === 'offline') view.innerHTML = collectionView(true);
  else if (state.page === 'profile') view.innerHTML = profileView();
  else view.innerHTML = homeView();
  const search = document.querySelector('#route-search');
  if (search) search.addEventListener('input', event => { state.query = event.target.value; view.innerHTML = exploreView(); });
}

function openRoute(routeId) {
  const route = routes.find(item => item.id === routeId);
  if (!route) return;
  modal.innerHTML = `
    <div class="modal-backdrop" data-close-modal>
      <article class="modal" role="dialog" aria-modal="true" aria-label="${route.name}">
        <div class="modal-top"><button class="icon-btn" type="button" data-close-modal aria-label="Chiudi">${icon('close')}</button><h2>Dettaglio percorso</h2><span></span></div>
        <div class="detail-hero"><img src="${route.img}" alt=""><h1>${route.name}</h1></div>
        <div class="stats"><div class="stat"><b>${route.km} km</b><small>Distanza</small></div><div class="stat"><b>${route.time}</b><small>Tempo</small></div><div class="stat"><b>+${route.up} m</b><small>Dislivello</small></div><div class="stat"><b>${route.diff}</b><small>Difficoltà</small></div></div>
        <div class="detail-copy"><h3>Il percorso</h3><p>${route.desc}</p></div>
        <div style="display:grid;grid-template-columns:1fr 1.1fr;gap:8px"><button class="btn btn-soft" type="button" data-offline="${route.id}">${state.offline.has(route.id) ? '✓ Offline' : `${icon('download')} Scarica`}</button><button class="btn btn-dark" type="button" data-start="${route.id}">${icon('nav')} Inizia</button></div>
      </article>
    </div>`;
  document.body.style.overflow = 'hidden';
}

function openReport() {
  state.page = 'report';
  renderNav();
  modal.innerHTML = `
    <div class="modal-backdrop" data-close-modal>
      <form class="modal form" id="report-form">
        <div class="modal-top"><button class="icon-btn" type="button" data-close-modal aria-label="Chiudi">${icon('close')}</button><h2>Nuova segnalazione</h2><span></span></div>
        <p style="color:var(--muted)">La demo salva la segnalazione solo sul dispositivo.</p>
        <label>Tipo<select required><option value="">Seleziona</option><option>Segnaletica danneggiata</option><option>Sentiero ostruito</option><option>Pericolo o criticità</option><option>Altro</option></select></label>
        <label>Percorso<select><option>Nessun percorso specifico</option>${routes.map(route => `<option>${route.name}</option>`).join('')}</select></label>
        <label>Descrizione<textarea required placeholder="Descrivi cosa hai trovato…"></textarea></label>
        <button class="btn btn-soft" type="button" data-locate>${icon('nav')} Rileva posizione</button>
        <button class="btn btn-dark" type="submit">Invia segnalazione</button>
      </form>
    </div>`;
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.body.style.overflow = '';
  modal.innerHTML = '';
  if (state.page === 'report') go('home');
}

function go(page) {
  if (page === 'report') { openReport(); return; }
  state.page = page;
  history.replaceState(null,'',`#${page}`);
  render();
}

function showToast(message) {
  toastRoot.innerHTML = `<div class="toast">${escapeHtml(message)}</div>`;
  window.setTimeout(() => { toastRoot.innerHTML = ''; }, 2400);
}

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[char]);
}

async function installApp() {
  if (!state.installPrompt) { showToast('Usa “Aggiungi a schermata Home” dal browser'); return; }
  state.installPrompt.prompt();
  await state.installPrompt.userChoice;
  state.installPrompt = null;
}

document.addEventListener('click', event => {
  const close = event.target.closest('[data-close-modal]');
  if (close && (close === event.target || close.tagName === 'BUTTON')) { closeModal(); return; }
  const goButton = event.target.closest('[data-go]');
  if (goButton) { go(goButton.dataset.go); return; }
  const favorite = event.target.closest('[data-favorite]');
  if (favorite) {
    event.stopPropagation();
    const id = favorite.dataset.favorite;
    state.favorites.has(id) ? state.favorites.delete(id) : state.favorites.add(id);
    localStorage.setItem('lauco-demo:favorites',JSON.stringify([...state.favorites]));
    render();
    return;
  }
  const route = event.target.closest('[data-route]');
  if (route) { openRoute(route.dataset.route); return; }
  const mapRoute = event.target.closest('[data-map-route]');
  if (mapRoute) { state.activeRoute = mapRoute.dataset.mapRoute; render(); return; }
  const filter = event.target.closest('[data-filter]');
  if (filter) { state.filter = filter.dataset.filter; render(); return; }
  const offline = event.target.closest('[data-offline]');
  if (offline) {
    const id = offline.dataset.offline;
    state.offline.has(id) ? state.offline.delete(id) : state.offline.add(id);
    localStorage.setItem('lauco-demo:offline',JSON.stringify([...state.offline]));
    openRoute(id);
    showToast('Disponibilità offline aggiornata');
    return;
  }
  const start = event.target.closest('[data-start]');
  if (start) { closeModal(); state.activeRoute = start.dataset.start; go('map'); showToast('Navigazione demo avviata'); return; }
  if (event.target.closest('[data-locate]')) {
    if (!navigator.geolocation) { showToast('GPS non disponibile'); return; }
    navigator.geolocation.getCurrentPosition(() => showToast('Posizione rilevata'),() => showToast('Posizione non disponibile'));
    return;
  }
  const demo = event.target.closest('[data-demo]');
  if (demo) { showToast(demo.dataset.demo); return; }
  if (event.target.closest('[data-install]')) installApp();
});

document.addEventListener('submit', event => {
  if (event.target.id !== 'report-form') return;
  event.preventDefault();
  localStorage.setItem('lauco-demo:reports',String(Number(localStorage.getItem('lauco-demo:reports') || 0) + 1));
  closeModal();
  showToast('Segnalazione demo registrata');
});

window.addEventListener('beforeinstallprompt', event => { event.preventDefault(); state.installPrompt = event; });
window.addEventListener('hashchange', () => { state.page = location.hash.slice(1) || 'home'; render(); });
if ('serviceWorker' in navigator) window.addEventListener('load',() => navigator.serviceWorker.register('./sw.js').catch(() => {}));
render();
