/* Pokémon Pre-Flight prototype core: fake data, fake auth, profiles, "I'm going" flow.
   Shared by all three prototypes. Each page styles the sheets through --ppf-* variables. */
(function () {
  const DAY = 86400000;
  const now = new Date();
  const midnight = new Date(now.getFullYear(), now.getMonth(), now.getDate());

  const SHOPS = [
    { id: 'dado', name: 'Dado Dourado', city: 'Lisboa' },
    { id: 'cartaalta', name: 'Carta Alta', city: 'Porto' },
    { id: 'toca', name: 'Toca do Tabuleiro', city: 'Braga' },
    { id: 'meeples', name: 'Casa dos Meeples', city: 'Coimbra' },
    { id: 'farol', name: 'Farol Geek', city: 'Faro' },
    { id: 'ria', name: 'Ria Cartas & Jogos', city: 'Aveiro' },
    { id: 'sado', name: 'Sado Games', city: 'Setúbal' },
    { id: 'levada', name: 'Levada TCG', city: 'Funchal' },
    { id: 'liz', name: 'Liz Card Club', city: 'Leiria' }
  ];
  const TYPES = ['Cup', 'Challenge', 'Friendly', 'League'];
  const TYPE_LONG = { Cup: 'League Cup', Challenge: 'League Challenge', Friendly: 'Friendly', League: 'League night' };

  // [dayOffset, start, type, shop, going, hours, gainedThisWeek]
  const RAW = [
    [-2, '15:00', 'Challenge', 'dado', 14, 5, 0],
    [0, 'NOW', 'League', 'cartaalta', 9, 4, 3],
    [1, '18:30', 'League', 'dado', 6, 3, 2],
    [2, '19:00', 'Friendly', 'ria', 4, 3, 1],
    [3, '10:00', 'Cup', 'toca', 27, 9, 9],
    [3, '14:00', 'Challenge', 'sado', 11, 5, 4],
    [4, '10:00', 'Cup', 'dado', 38, 9, 12],
    [4, '15:00', 'Challenge', 'meeples', 8, 5, 1],
    [6, '18:30', 'League', 'toca', 5, 3, 0],
    [7, '19:00', 'League', 'liz', 3, 3, 1],
    [9, '18:00', 'Friendly', 'cartaalta', 12, 3, 5],
    [10, '10:00', 'Cup', 'cartaalta', 31, 9, 14],
    [10, '14:30', 'Challenge', 'farol', 7, 5, 2],
    [11, '10:00', 'Challenge', 'levada', 6, 5, 2],
    [11, '14:00', 'Cup', 'meeples', 16, 9, 6],
    [13, '18:30', 'League', 'dado', 7, 3, 1],
    [16, '19:00', 'Friendly', 'sado', 5, 3, 2],
    [17, '10:00', 'Cup', 'farol', 13, 9, 7],
    [17, '14:00', 'Challenge', 'ria', 9, 5, 3],
    [18, '10:00', 'Cup', 'liz', 10, 9, 4],
    [20, '18:30', 'League', 'meeples', 4, 3, 0],
    [24, '10:00', 'Challenge', 'toca', 12, 5, 5],
    [24, '14:00', 'Friendly', 'dado', 9, 4, 3],
    [25, '10:00', 'Cup', 'sado', 19, 9, 8],
    [31, '10:00', 'Cup', 'toca', 8, 9, 5],
    [31, '14:00', 'Challenge', 'cartaalta', 3, 5, 2],
    [32, '10:00', 'Challenge', 'dado', 2, 5, 2],
    [38, '10:00', 'Cup', 'ria', 5, 9, 4],
    [39, '14:00', 'Challenge', 'farol', 1, 5, 1]
  ];

  const shopById = Object.fromEntries(SHOPS.map(s => [s.id, s]));
  const EVENTS = RAW.map((r, i) => {
    const [off, t, type, shop, base, hours, gain] = r;
    let start;
    if (t === 'NOW') start = new Date(now.getTime() - 90 * 60000);
    else {
      const [h, m] = t.split(':').map(Number);
      start = new Date(midnight.getTime() + off * DAY);
      start.setHours(h, m, 0, 0);
    }
    const end = new Date(start.getTime() + hours * 3600000);
    const yy = String(start.getFullYear()).slice(-2);
    const mm = String(start.getMonth() + 1).padStart(2, '0');
    const tid = `${yy}-${mm}-${String(400123 + i * 3719).slice(-6)}`;
    // 8-day history ending at base, for sparklines
    const history = [];
    for (let d = 7; d >= 0; d--) history.push(Math.max(0, Math.round(base - gain * (d / 7) ** 1.3)));
    return { id: 'e' + i, tid, type, shop: shopById[shop], start, end, base, gain, history };
  });

  function upcoming() {
    const t = Date.now();
    return EVENTS.filter(e => e.end.getTime() > t).sort((a, b) => a.start - b.start);
  }
  const isOngoing = e => e.start.getTime() <= Date.now() && e.end.getTime() > Date.now();

  // ---------- state ----------
  const KEY = 'ppf-' + (window.PPF_KEY || 'proto');
  let S = { user: null, going: {} };
  try { const raw = localStorage.getItem(KEY); if (raw) S = Object.assign(S, JSON.parse(raw)); } catch (e) { /* storage blocked */ }
  function save() { try { localStorage.setItem(KEY, JSON.stringify(S)); } catch (e) { /* ignore */ } }
  function emit() { save(); document.dispatchEvent(new CustomEvent('ppf:change')); }

  const goingFor = e => (S.going[e.id] || []).filter(pid => S.user && S.user.profiles.some(p => p.id === pid));
  const count = e => e.base + goingFor(e).length;

  const DEMO = {
    solo: { name: 'Rui Matos', email: 'rui@example.com', profiles: [{ id: 'p1', name: 'Rui Matos', pid: '3489123', by: 1993 }] },
    family: {
      name: 'Ana Lopes', email: 'ana@example.com', profiles: [
        { id: 'p1', name: 'Ana Lopes', pid: '2210457', by: 1986 },
        { id: 'p2', name: 'Tiago Lopes', pid: '5531902', by: 2016 },
        { id: 'p3', name: 'Beatriz Lopes', pid: '5531877', by: 2012 }
      ]
    }
  };
  // 2027 season: Junior born 2015+, Senior 2011–2014, Masters 2010 or earlier
  const division = by => (by >= 2015 ? 'Junior' : by >= 2011 ? 'Senior' : 'Masters');

  // ---------- formatting ----------
  const WD = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  const MO = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const fmt = {
    wd: d => WD[d.getDay()],
    day: d => d.getDate(),
    mon: d => MO[d.getMonth()],
    date: d => `${WD[d.getDay()]} ${d.getDate()} ${MO[d.getMonth()]}`,
    time: d => `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`,
    rel: e => {
      if (isOngoing(e)) return 'Happening now';
      const days = Math.round((new Date(e.start.getFullYear(), e.start.getMonth(), e.start.getDate()) - midnight) / DAY);
      if (days === 0) return 'Today';
      if (days === 1) return 'Tomorrow';
      if (days < 7) return `In ${days} days`;
      if (days < 14) return 'Next week';
      return `In ${Math.round(days / 7)} weeks`;
    }
  };
  const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const label = e => `${TYPE_LONG[e.type]} · ${e.shop.name} · ${fmt.date(e.start)}`;

  // ---------- sheet + toast ----------
  function sheet(html) {
    const bd = document.createElement('div');
    bd.className = 'ppf-backdrop';
    bd.innerHTML = '<div class="ppf-sheet" role="dialog" aria-modal="true"><button class="ppf-x" type="button" aria-label="Close">×</button><div class="ppf-body"></div></div>';
    const body = bd.querySelector('.ppf-body');
    body.innerHTML = html;
    document.body.appendChild(bd);
    const onKey = ev => { if (ev.key === 'Escape') close(); };
    function close() { bd.classList.remove('open'); document.removeEventListener('keydown', onKey); setTimeout(() => bd.remove(), 180); }
    document.addEventListener('keydown', onKey);
    bd.addEventListener('click', ev => { if (ev.target === bd) close(); });
    bd.querySelector('.ppf-x').onclick = close;
    requestAnimationFrame(() => bd.classList.add('open'));
    return { el: body, close, set(h) { body.innerHTML = h; } };
  }
  let toastTimer;
  function toast(msg) {
    let t = document.querySelector('.ppf-toast');
    if (!t) { t = document.createElement('div'); t.className = 'ppf-toast'; t.setAttribute('role', 'status'); document.body.appendChild(t); }
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2600);
  }

  // ---------- auth ----------
  const ICONS = {
    google: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.4-.2-2H12v3.8h5.4a4.6 4.6 0 0 1-2 3v2.5h3.2c1.9-1.7 3-4.3 3-7.3z"/><path fill="#34A853" d="M12 22c2.7 0 5-.9 6.6-2.5l-3.2-2.5c-.9.6-2 1-3.4 1-2.6 0-4.8-1.8-5.6-4.1H3.1v2.6A10 10 0 0 0 12 22z"/><path fill="#FBBC05" d="M6.4 13.9a6 6 0 0 1 0-3.8V7.5H3.1a10 10 0 0 0 0 9z"/><path fill="#EA4335" d="M12 6c1.5 0 2.8.5 3.8 1.5l2.9-2.9A10 10 0 0 0 3.1 7.5l3.3 2.6C7.2 7.8 9.4 6 12 6z"/></svg>',
    apple: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M16.4 12.6c0-2.4 2-3.6 2.1-3.7a4.6 4.6 0 0 0-3.6-2c-1.5-.2-3 .9-3.7.9-.8 0-2-.9-3.3-.8a4.9 4.9 0 0 0-4.1 2.5c-1.8 3-.5 7.6 1.3 10.1.8 1.2 1.8 2.6 3.1 2.5 1.3 0 1.7-.8 3.3-.8 1.5 0 1.9.8 3.3.8s2.2-1.2 3-2.5a10 10 0 0 0 1.4-2.8 4.3 4.3 0 0 1-2.8-4.2zM14 5.3c.7-.8 1.1-2 1-3.1-1 0-2.2.7-2.9 1.5-.6.7-1.2 1.9-1 3 1.1.1 2.2-.6 2.9-1.4z"/></svg>',
    discord: '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="#5865F2" d="M19.6 5.3A16.5 16.5 0 0 0 15.5 4l-.5 1a15.3 15.3 0 0 0-6 0l-.5-1a16.5 16.5 0 0 0-4.1 1.3A17 17 0 0 0 1.5 17a16.6 16.6 0 0 0 5 2.5l1.1-1.7c-.6-.2-1.2-.5-1.7-.9l.4-.3a11.8 11.8 0 0 0 11.4 0l.4.3-1.7.9 1 1.7a16.6 16.6 0 0 0 5.1-2.5 17 17 0 0 0-2.9-11.7zM8.7 14.7c-1 0-1.8-.9-1.8-2s.8-2 1.8-2 1.8.9 1.8 2-.8 2-1.8 2zm6.6 0c-1 0-1.8-.9-1.8-2s.8-2 1.8-2 1.8.9 1.8 2-.8 2-1.8 2z"/></svg>'
  };

  function signIn(then, mode) {
    const up = mode === 'up';
    const sh = sheet('');
    function step1() {
      sh.set(`
        <h3>${up ? 'Create your account' : 'Sign in'}</h3>
        <p class="ppf-muted">One account can hold several player profiles, for example you and your kids.</p>
        <div class="ppf-stack">
          <button class="ppf-provider" data-p="Google">${ICONS.google}<span>Continue with Google</span></button>
          <button class="ppf-provider" data-p="Apple">${ICONS.apple}<span>Continue with Apple</span></button>
          <button class="ppf-provider" data-p="Discord">${ICONS.discord}<span>Continue with Discord</span></button>
        </div>
        <div class="ppf-or"><span>or</span></div>
        <form class="ppf-stack" id="ppf-mail">
          <label class="ppf-label" for="ppf-email">Email</label>
          <input class="ppf-input" id="ppf-email" type="email" placeholder="you@example.com" autocomplete="email" required>
          <button class="ppf-btn primary" type="submit">Email me a sign-in link</button>
        </form>
        <p class="ppf-note">Prototype: nothing is sent and no real account is created.</p>`);
      sh.el.querySelectorAll('.ppf-provider').forEach(b => (b.onclick = () => step2(b.dataset.p)));
      sh.el.querySelector('#ppf-mail').onsubmit = ev => {
        ev.preventDefault();
        const mail = sh.el.querySelector('#ppf-email').value;
        sh.set(`<h3>Check your inbox</h3>
          <p class="ppf-muted">We sent a sign-in link to <strong>${esc(mail)}</strong>. It works once and expires in 15 minutes.</p>
          <button class="ppf-btn primary" id="ppf-open">Open the link (prototype)</button>`);
        sh.el.querySelector('#ppf-open').onclick = () => step2('email');
      };
    }
    function step2(provider) {
      sh.set(`<h3>Pick a test account</h3>
        <p class="ppf-muted">Prototype only. Signing in with ${esc(provider)} would land you here. Try both to see the family flow.</p>
        <div class="ppf-stack">
          <button class="ppf-pick" data-k="solo"><strong>Rui Matos</strong><span>1 player profile (Masters)</span></button>
          <button class="ppf-pick" data-k="family"><strong>Ana Lopes, parent account</strong><span>3 profiles: Ana (Masters), Beatriz (Senior), Tiago (Junior)</span></button>
        </div>`);
      sh.el.querySelectorAll('.ppf-pick').forEach(b => (b.onclick = () => {
        S.user = JSON.parse(JSON.stringify(DEMO[b.dataset.k]));
        S.going = {};
        emit();
        sh.close();
        toast(`Signed in as ${S.user.name}`);
        if (then) setTimeout(then, 220);
      }));
    }
    step1();
  }
  function signOut() { S.user = null; S.going = {}; emit(); toast('Signed out'); }

  // ---------- going ----------
  function setGoing(e, ids) { if (ids.length) S.going[e.id] = ids; else delete S.going[e.id]; emit(); }

  function chooseProfiles(e, after) {
    const sel = new Set(goingFor(e));
    const sh = sheet('');
    function render() {
      sh.set(`<h3>Who's going?</h3>
        <p class="ppf-muted">${esc(label(e))}</p>
        <div class="ppf-stack">
          ${S.user.profiles.map(p => `
            <div class="ppf-prow">
              <div><strong>${esc(p.name)}</strong><span>${division(p.by)} · ID ${esc(p.pid)}</span></div>
              <div class="ppf-seg" role="group" aria-label="${esc(p.name)}">
                <button type="button" data-id="${p.id}" data-v="1" aria-pressed="${sel.has(p.id)}">Going</button>
                <button type="button" data-id="${p.id}" data-v="0" aria-pressed="${!sel.has(p.id)}">Not going</button>
              </div>
            </div>`).join('')}
        </div>
        <button class="ppf-btn primary" id="ppf-save">Save</button>
        <p class="ppf-note">This tells other players how many are coming. It is not a registration: sign up with the shop as usual.</p>`);
      sh.el.querySelectorAll('.ppf-seg button').forEach(b => (b.onclick = () => {
        b.dataset.v === '1' ? sel.add(b.dataset.id) : sel.delete(b.dataset.id);
        render();
      }));
      sh.el.querySelector('#ppf-save').onclick = () => {
        const before = goingFor(e).length;
        setGoing(e, [...sel]);
        sh.close();
        const n = sel.size;
        toast(n ? `${n} of ${S.user.profiles.length} profiles going` : before ? 'Removed you from this event' : 'Nobody marked going');
        if (after) after(n > 0);
      };
    }
    render();
  }

  // Quick "I'm going": signed out -> sign in, then continue. One profile -> toggle. Several -> ask.
  function quickGoing(e, after, opts) {
    opts = opts || {};
    if (!S.user) { signIn(() => quickGoing(e, after, opts)); return; }
    if (S.user.profiles.length > 1) { chooseProfiles(e, after); return; }
    const pid = S.user.profiles[0].id;
    const on = goingFor(e).includes(pid);
    if (on && opts.onlyAdd) { if (after) after(true); return; }
    setGoing(e, on ? [] : [pid]);
    toast(on ? 'Removed you from this event' : `You're going. ${count(e)} players so far`);
    if (after) after(!on);
  }

  // ---------- profiles ----------
  function manageProfiles() {
    if (!S.user) return;
    const sh = sheet('');
    let err = '';
    function render() {
      const ps = S.user.profiles;
      sh.set(`<h3>Player profiles</h3>
        <p class="ppf-muted">Signed in as ${esc(S.user.email)}. Other players never see these names, only counts.</p>
        <div class="ppf-stack">
          ${ps.map(p => `
            <div class="ppf-prow">
              <div><strong>${esc(p.name)}</strong><span>${division(p.by)} · born ${p.by} · ID ${esc(p.pid)}</span></div>
              <button class="ppf-btn ghost small" data-rm="${p.id}" ${ps.length < 2 ? 'disabled title="Keep at least one profile"' : ''}>Remove</button>
            </div>`).join('')}
        </div>
        <form id="ppf-add" class="ppf-add">
          <strong>Add a profile</strong>
          <label class="ppf-label" for="ppf-n">Name</label>
          <input class="ppf-input" id="ppf-n" placeholder="e.g. Tiago Lopes" required>
          <div class="ppf-two">
            <div><label class="ppf-label" for="ppf-pid">Player ID</label><input class="ppf-input" id="ppf-pid" inputmode="numeric" placeholder="1234567"></div>
            <div><label class="ppf-label" for="ppf-by">Birth year</label><input class="ppf-input" id="ppf-by" inputmode="numeric" placeholder="2015" required></div>
          </div>
          ${err ? `<p class="ppf-err">${esc(err)}</p>` : ''}
          <button class="ppf-btn primary" type="submit">Add profile</button>
          <p class="ppf-note">Division is worked out from the birth year for the current season.</p>
        </form>`);
      sh.el.querySelectorAll('[data-rm]').forEach(b => (b.onclick = () => {
        S.user.profiles = ps.filter(p => p.id !== b.dataset.rm);
        Object.keys(S.going).forEach(k => (S.going[k] = S.going[k].filter(x => x !== b.dataset.rm)));
        emit(); render();
      }));
      sh.el.querySelector('#ppf-add').onsubmit = ev => {
        ev.preventDefault();
        const name = sh.el.querySelector('#ppf-n').value.trim();
        const pid = sh.el.querySelector('#ppf-pid').value.trim();
        const by = Number(sh.el.querySelector('#ppf-by').value);
        const y = new Date().getFullYear();
        if (!name) err = 'Add a name.';
        else if (pid && !/^\d{4,10}$/.test(pid)) err = 'Player ID is the number on your Play! Pokémon account, digits only.';
        else if (!(by >= y - 100 && by <= y - 3)) err = `Birth year should be between ${y - 100} and ${y - 3}.`;
        else err = '';
        if (!err) { S.user.profiles.push({ id: 'p' + Date.now(), name, pid: pid || '—', by }); emit(); toast(`Added ${name} (${division(by)})`); }
        render();
      };
    }
    render();
  }

  // ---------- base sheet styles (pages theme them with --ppf-* vars) ----------
  const css = `
  .ppf-backdrop{position:fixed;inset:0;z-index:100;background:rgba(15,20,26,.5);display:flex;align-items:flex-end;justify-content:center;opacity:0;transition:opacity .18s}
  .ppf-backdrop.open{opacity:1}
  .ppf-sheet{position:relative;width:100%;max-width:460px;max-height:88vh;overflow:auto;background:var(--ppf-bg,#fff);color:var(--ppf-fg,#1F2933);border-radius:var(--ppf-radius,18px) var(--ppf-radius,18px) 0 0;padding:22px max(20px,env(safe-area-inset-right,0px)) calc(22px + env(safe-area-inset-bottom,0px)) max(20px,env(safe-area-inset-left,0px));transform:translateY(24px);transition:transform .2s;font-family:var(--ppf-font,inherit);box-sizing:border-box}
  .ppf-backdrop.open .ppf-sheet{transform:none}
  @media (min-width:640px){.ppf-backdrop{align-items:center}.ppf-sheet{border-radius:var(--ppf-radius,18px)}}
  .ppf-body{display:flex;flex-direction:column;gap:14px}
  .ppf-body h3{margin:0;font-family:var(--ppf-display,inherit);font-size:1.35rem;padding-right:32px}
  .ppf-x{position:absolute;top:12px;right:12px;width:36px;height:36px;border-radius:50%;border:0;background:var(--ppf-soft,#F1F4F8);color:inherit;font-size:22px;line-height:1;cursor:pointer}
  .ppf-muted{margin:0;color:var(--ppf-muted,#52606D);line-height:1.45}
  .ppf-note{margin:0;font-size:.8rem;color:var(--ppf-muted,#52606D);line-height:1.4}
  .ppf-err{margin:0;color:var(--ppf-accent,#E63946);font-weight:600;font-size:.88rem}
  .ppf-stack{display:flex;flex-direction:column;gap:10px}
  .ppf-provider,.ppf-pick{display:flex;align-items:center;gap:12px;width:100%;min-height:48px;padding:10px 14px;border:1px solid var(--ppf-line,#D9E0E8);border-radius:12px;background:var(--ppf-bg,#fff);color:inherit;font:inherit;font-weight:600;cursor:pointer;text-align:left}
  .ppf-pick{flex-direction:column;align-items:flex-start;gap:2px}
  .ppf-pick span{font-weight:400;color:var(--ppf-muted,#52606D);font-size:.88rem}
  .ppf-provider:hover,.ppf-pick:hover{border-color:var(--ppf-fg,#1F2933)}
  .ppf-or{display:flex;align-items:center;gap:10px;color:var(--ppf-muted,#52606D);font-size:.8rem}
  .ppf-or::before,.ppf-or::after{content:"";flex:1;height:1px;background:var(--ppf-line,#D9E0E8)}
  .ppf-label{font-size:.8rem;font-weight:600;color:var(--ppf-muted,#52606D)}
  .ppf-input{width:100%;box-sizing:border-box;min-height:46px;padding:10px 12px;border:1px solid var(--ppf-line,#D9E0E8);border-radius:10px;font:inherit;font-size:16px;background:var(--ppf-bg,#fff);color:inherit}
  .ppf-input:focus{outline:2px solid var(--ppf-accent,#E63946);outline-offset:1px}
  .ppf-btn{min-height:48px;padding:0 18px;border-radius:12px;border:1px solid transparent;font:inherit;font-weight:700;cursor:pointer;background:var(--ppf-soft,#F1F4F8);color:inherit}
  .ppf-btn.primary{background:var(--ppf-accent,#E63946);color:var(--ppf-on-accent,#fff)}
  .ppf-btn.ghost{background:transparent;border-color:var(--ppf-line,#D9E0E8)}
  .ppf-btn.small{min-height:36px;padding:0 12px;font-size:.85rem;font-weight:600}
  .ppf-btn:disabled{opacity:.4;cursor:not-allowed}
  .ppf-prow{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px;border:1px solid var(--ppf-line,#D9E0E8);border-radius:12px;flex-wrap:wrap}
  .ppf-prow>div:first-child{display:flex;flex-direction:column;gap:2px;min-width:0}
  .ppf-prow span{font-size:.82rem;color:var(--ppf-muted,#52606D)}
  .ppf-seg{display:inline-flex;border:1px solid var(--ppf-line,#D9E0E8);border-radius:999px;padding:3px;gap:2px}
  .ppf-seg button{border:0;background:transparent;color:inherit;font:inherit;font-size:.85rem;font-weight:600;padding:7px 12px;border-radius:999px;cursor:pointer}
  .ppf-seg button[aria-pressed="true"][data-v="1"]{background:var(--ppf-accent,#E63946);color:var(--ppf-on-accent,#fff)}
  .ppf-seg button[aria-pressed="true"][data-v="0"]{background:var(--ppf-fg,#1F2933);color:var(--ppf-bg,#fff)}
  .ppf-add{display:flex;flex-direction:column;gap:8px;padding:14px;border-radius:12px;background:var(--ppf-soft,#F1F4F8)}
  .ppf-two{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .ppf-two>div{display:flex;flex-direction:column;gap:6px;min-width:0}
  .ppf-toast{position:fixed;left:50%;bottom:calc(20px + env(safe-area-inset-bottom,0px));transform:translate(-50%,20px);background:var(--ppf-fg,#1F2933);color:var(--ppf-bg,#fff);padding:12px 18px;border-radius:12px;font-weight:600;z-index:200;opacity:0;transition:.2s;pointer-events:none;max-width:calc(100% - 32px);box-sizing:border-box;text-align:center;font-family:var(--ppf-font,inherit)}
  .ppf-toast.show{opacity:1;transform:translate(-50%,0)}
  .ppf-body button:focus-visible{outline:2px solid var(--ppf-accent,#E63946);outline-offset:2px}`;
  const st = document.createElement('style');
  st.textContent = css;
  document.head.appendChild(st);

  window.PPF = {
    SHOPS, TYPES, TYPE_LONG, EVENTS, upcoming, isOngoing, count, goingFor, division, fmt, esc, label,
    state: () => S, signIn, signOut, quickGoing, chooseProfiles, manageProfiles, sheet, toast, emit
  };
})();
