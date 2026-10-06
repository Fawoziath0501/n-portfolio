import { reactive } from 'vue';
import { api, plainText, tx } from '../shared/util';

export const VIEWS = ['dashboard', 'stats', 'messages', 'newsletter', 'projects', 'posts', 'experiences', 'education', 'skills', 'services', 'certifications', 'testimonials', 'profile', 'home', 'media', 'seo', 'menus', 'legalPages', 'labels', 'settings', 'account', 'trash'];

export const state = reactive({
    data: null, user: null, ready: false,
    view: 'dashboard', theme: 'light', period: 30, menu: false, w: window.innerWidth,
    edit: null, mf: 'all', msgId: null, confirm: null, toast: '', q: '', pick: null, mUrl: '',
    events: null, eventsTotal: 0,
});

try {
    state.theme = localStorage.getItem('fz.admin.theme') || (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
} catch (e) { /* stockage indisponible */ }

/* ---------- Helpers ---------- */
export const t = (v) => tx(v, 'fr');
export const iso = (dt) => dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0') + '-' + String(dt.getDate()).padStart(2, '0');
export const fmtD = (dt) => dt.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
export const fmtMD = (s) => { try { return new Date(s + 'T12:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }); } catch (e) { return s; } };
export const nf = (n) => Number(n).toLocaleString('fr-FR');
export const spark = (arr) => { const n = arr.length, mx = Math.max(...arr, 1); return arr.map((v, i) => (i ? 'L' : 'M') + (n > 1 ? (i * 100 / (n - 1)).toFixed(1) : 0) + ' ' + (30 - v / mx * 26).toFixed(1)).join(' '); };
const buckets = (arr, k, nb) => { const size = Math.max(1, Math.ceil(arr.length / nb)), out = []; for (let i = 0; i < arr.length; i += size) out.push(arr.slice(i, i + size).reduce((s, x) => s + x[k], 0)); return out; };
export function delta(cur, prev, isPct) {
    if (isPct) {
        if (!prev) return { delta: 'Nouveau', dBg: '#E7F6EE', dFg: '#1E6B45' };
        const p = Math.round((cur - prev) / prev * 100);
        return { delta: (p >= 0 ? '↑ ' : '↓ ') + Math.abs(p) + ' % vs période préc.', dBg: p >= 0 ? '#E7F6EE' : '#FDECEC', dFg: p >= 0 ? '#1E6B45' : '#B42318' };
    }
    const dd = cur - prev;
    if (!cur && !prev) return { delta: 'Aucun sur la période', dBg: 'var(--ln2)', dFg: 'var(--mu)' };
    return { delta: (dd > 0 ? '+' : '') + dd + ' vs période préc.', dBg: dd >= 0 ? '#E7F6EE' : '#FDECEC', dFg: dd >= 0 ? '#1E6B45' : '#B42318' };
}
export const barList = (rows, total) => { const mx = Math.max(...rows.map((r) => r.n), 1); return rows.map((r) => ({ label: r.label, icon: r.icon || 'circle', value: nf(r.n), pct: total ? Math.round(r.n / total * 100) + ' %' : '', w: Math.max(3, Math.round(r.n / mx * 100)) + '%' })); };
export const missingEn = (o) => { let n = 0; const walk = (x) => { if (!x || typeof x !== 'object') return; if (Array.isArray(x)) return x.forEach(walk); if ('fr' in x && 'en' in x && typeof x.fr === 'string') { if (x.fr.trim() && !String(x.en || '').trim()) n++; return; } Object.values(x).forEach(walk); }; walk(o); return n; };
export const getPath = (o, path) => path.split('.').reduce((a, k) => (a == null ? a : a[k]), o);
export const setPath = (o, path, v) => { const ks = path.split('.'); let c = o; for (let i = 0; i < ks.length - 1; i++) { if (c[ks[i]] == null) c[ks[i]] = {}; c = c[ks[i]]; } c[ks[ks.length - 1]] = v; };
export const providerOf = () => ((state.data.settings && state.data.settings.analytics) || {}).provider || 'none';
export const isLocal = () => providerOf() === 'local';

/* ---------- Session et chargement ---------- */
export async function boot() {
    try {
        // Les données sont chargées avant d'afficher l'interface : ses vues lisent state.data dès leur montage.
        const { data } = await api.get('/admin/me');
        if (data.user) await reload();
        state.user = data.user;
    } finally {
        state.ready = true;
    }
}

export async function reload() {
    const { data } = await api.get('/admin/data');
    state.data = data;
}

export async function login(email, password, remember = false) {
    await api.get('/admin/me'); // initialise le cookie CSRF
    const { data } = await api.post('/admin/login', { email, password, remember });
    await reload();
    state.user = data.user;
}

export async function logout() {
    try { await api.post('/admin/logout'); } catch (e) { /* session déjà expirée */ }
    state.user = null;
    state.data = null;
}

/* ---------- Retours visuels ---------- */
let toastTimer = null;
export function flash(m) {
    state.toast = m;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { state.toast = ''; }, 2200);
}
export function ask(title, msg, ok, okLabel) { state.confirm = { title, msg, ok, okLabel: okLabel || 'Supprimer' }; }
function logActivity(msg) { if (msg) state.data.activity = [{ ts: Date.now(), msg }, ...(state.data.activity || [])].slice(0, 30); }

/** Applique une modification locale puis la synchronise avec l'API ; recharge en cas d'échec. */
export async function save(mutate, msg, sync) {
    mutate(state.data);
    logActivity(msg);
    if (msg) flash(msg);
    try {
        if (sync) await sync();
    } catch (e) {
        if (e.response && e.response.status === 401) { state.user = null; return; }
        flash(errorText(e));
        await reload();
    }
}
export async function trash(req, msg) {
    const { data } = await req;
    if (data && data.trashCount != null) state.data.trashCount = data.trashCount;
    if (msg) logActivity(msg);
}
export const TRASH_HINT = 'Il restera 30 jours dans la corbeille, d’où vous pourrez le restaurer.';
export const errorText = (e) => {
    const r = e && e.response && e.response.data;
    if (r && r.errors) return Object.values(r.errors)[0][0];
    return (r && r.message) || 'Erreur : enregistrement impossible';
};

/* ---------- Synchronisation ---------- */
const clean = (item) => { const o = { ...item }; delete o.id; return o; };
const adoptIds = (local, remote) => (local || []).forEach((x, i) => { if (remote && remote[i] && x.id == null) x.id = remote[i].id; });
export const syncItem = (col, item, activity) => api.put(`/admin/${col}/${item.id}`, { item: clean(item), activity }).then((r) => {
    if (col === 'skillGroups') adoptIds(item.skills, r.data.skills);
    return r;
});
export const syncOrder = (col, activity) => api.post(`/admin/${col}/reorder`, { ids: state.data[col].map((x) => x.id), activity });

// Les documents (profil, SEO…) sont enregistrés automatiquement, avec un léger délai.
const docTimers = {};
export function syncDoc(key, activity) {
    clearTimeout(docTimers[key]);
    return new Promise((resolve, reject) => {
        docTimers[key] = setTimeout(() => api.put(`/admin/documents/${key}`, { value: state.data[key], activity }).then((r) => {
            if (key === 'profile') ['languages', 'values', 'socials'].forEach((k) => adoptIds(state.data.profile[k], r.data[k]));
            resolve(r);
        }, reject), 450);
    });
}
const itemTimers = {};
export function syncItemLater(col, item) {
    const k = col + item.id;
    clearTimeout(itemTimers[k]);
    return new Promise((resolve, reject) => {
        itemTimers[k] = setTimeout(() => syncItem(col, item).then(resolve, reject), 450);
    });
}

export async function createItem(col, item, index, activity) {
    const order = state.data[col].map((x) => x.id);
    order.splice(index, 0, 0);
    const { data } = await api.post(`/admin/${col}`, { item: clean(item), order, activity });
    return data;
}

/* ---------- Statistiques (uniquement les visites réellement enregistrées par le suivi intégré) ---------- */
function localDays(n) {
    const ev = (state.events || []).filter((e) => e.t === 'pv'), now = new Date(), out = [];
    for (let i = n - 1; i >= 0; i--) {
        const dt = new Date(now.getFullYear(), now.getMonth(), now.getDate() - i), a0 = dt.getTime(), a1 = a0 + 864e5;
        const day = ev.filter((e) => e.ts >= a0 && e.ts < a1);
        out.push({ dt, v: new Set(day.map((e) => e.sid)).size, pv: day.length });
    }
    return out;
}
export async function loadEvents() {
    if (!state.data || !isLocal()) return; // pas encore connecté
    try {
        const { data } = await api.get('/admin/events', { params: { days: 180 } });
        state.events = data.events;
        state.eventsTotal = data.total;
    } catch (e) { state.events = []; }
}

export function stats(p) {
    const d = state.data, tracking = isLocal(), all = localDays(2 * p), cur = all.slice(-p), prev = all.slice(-2 * p, -p);
    const sum = (a, k) => a.reduce((s, x) => s + x[k], 0);
    const V = sum(cur, 'v'), PV = sum(cur, 'pv'), V0 = sum(prev, 'v'), PV0 = sum(prev, 'pv');
    const n = cur.length, maxY = Math.max(4, ...cur.map((x) => x.pv)) * 1.12;
    const X = (i) => (n > 1 ? i * 640 / (n - 1) : 0), Y = (v) => 205 - v / maxY * 190;
    const line = (k) => cur.map((x, i) => (i ? 'L' : 'M') + X(i).toFixed(1) + ' ' + Y(x[k]).toFixed(1)).join(' ');
    const area = (k) => line(k) + ' L 640 205 L 0 205 Z';
    const grid = [0.25, 0.5, 0.75, 1].map((f) => { const val = Math.round(maxY * f / 1.12); return { y: Y(val).toFixed(1), ty: (Y(val) - 4).toFixed(1), label: nf(val) }; });
    const chart = { vLine: line('v'), pvLine: line('pv'), vArea: area('v'), pvArea: area('pv'), grid, xl: [cur[0], cur[Math.floor(n / 2)], cur[n - 1]].map((x) => fmtD(x.dt)), aria: 'Visiteurs et pages vues sur ' + p + ' jours' };

    // Visites enregistrées sur la période : pages, provenance, appareils, clics de contact.
    const ev = state.events || [], a0 = cur[0].dt.getTime(), b0 = prev[0] ? prev[0].dt.getTime() : a0;
    const cE = ev.filter((e) => e.ts >= a0), pE = ev.filter((e) => e.ts >= b0 && e.ts < a0), pvE = cE.filter((e) => e.t === 'pv');
    const label = (path) => {
        if (path === '/fr' || path === '/en' || path === '/') return ['Accueil', 'home'];
        const seg = path.split('/'), pj = seg[3] && (seg[2] === 'projets' || seg[2] === 'work') && d.projects.find((x) => x.slug === seg[3]);
        if (pj) return [t(pj.title), 'folder_open'];
        const po = seg[3] && seg[2] === 'blog' && (d.posts || []).find((x) => x.slug === seg[3]);
        if (po) return [t(po.title), 'article'];
        const m = { 'a-propos': ['À propos', 'person'], about: ['À propos', 'person'], projets: ['Projets', 'grid_view'], work: ['Projets', 'grid_view'], services: ['Services', 'design_services'], contact: ['Contact', 'mail'], blog: ['Blog', 'article'] };
        const lg = !seg[3] && (d.legalPages || []).find((x) => x.slugFr === seg[2] || x.slugEn === seg[2]);
        return m[seg[2]] || (lg ? [t(lg.title), 'gavel'] : [path, 'description']);
    };
    const grp = (arr, key) => { const o = {}; arr.forEach((e) => { const k = key(e); if (k != null) o[k] = (o[k] || 0) + 1; }); return o; };
    const rowsP = Object.entries(grp(pvE, (e) => e.path || '/')).map(([path, c]) => { const [lb, ic] = label(path); return { label: lb, path, icon: ic, n: c }; }).sort((x, y) => y.n - x.n);
    const firsts = {}; pvE.forEach((e) => { if (!firsts[e.sid]) firsts[e.sid] = e; });
    const srcName = (r) => (!r ? ['Accès direct', 'link'] : /google/.test(r) ? ['Google', 'search'] : /bing/.test(r) ? ['Bing', 'search'] : /linkedin/.test(r) ? ['LinkedIn', 'work'] : /whatsapp|wa\.me/.test(r) ? ['WhatsApp', 'chat'] : /github/.test(r) ? ['GitHub', 'code'] : /instagram/.test(r) ? ['Instagram', 'photo_camera'] : /facebook/.test(r) ? ['Facebook', 'public'] : [r, 'public']);
    const bySrc = {}; Object.values(firsts).forEach((e) => { const [lb, ic] = srcName(e.ref || ''); bySrc[lb] = bySrc[lb] || { label: lb, icon: ic, n: 0 }; bySrc[lb].n++; });
    const devN = { mobile: 0, desktop: 0, tablet: 0 }; Object.values(firsts).forEach((e) => { devN[e.dev || 'desktop']++; }); const devT = Math.max(1, devN.mobile + devN.desktop + devN.tablet);
    const devices = [['Mobile', 'smartphone', devN.mobile, '#2448C8'], ['Ordinateur', 'computer', devN.desktop, '#8FA3E8'], ['Tablette', 'tablet', devN.tablet, '#C9D3F2']]
        .map(([lb, icon, c, color]) => ({ label: lb, icon, color, w: (c / devT * 100) + '%', pct: Math.round(c / devT * 100) + ' %', value: nf(c) }));
    const clicks = grp(cE.filter((e) => e.t === 'click'), (e) => e.k), convTotal0 = pE.filter((e) => e.t === 'click').length;
    const hasCv = !!d.profile.cv;
    const conv = [['WhatsApp', 'chat', 'whatsapp'], ['E-mail', 'mail', 'email'], ['Téléchargement CV', 'description', 'cv'], ['LinkedIn', 'work', 'linkedin']].map(([lb, icon, k]) => {
        const c = clicks[k] || 0;
        return { label: lb, icon, n: c, value: nf(c), note: k === 'cv' && !hasCv ? 'CV non publié' : (V ? (c / V * 100).toFixed(1).replace('.', ',') : '0') + ' % des visiteurs' };
    });
    const convTotal = conv.reduce((s, c) => s + c.n, 0);

    const start = iso(cur[0].dt), start0 = iso(prev[0] ? prev[0].dt : cur[0].dt);
    const msgs = d.messages || [], inP = (m, a, b) => m.date >= a && (!b || m.date < b);
    const mC = msgs.filter((m) => m.type !== 'service' && inP(m, start)).length, mC0 = msgs.filter((m) => m.type !== 'service' && inP(m, start0, start)).length;
    const sC = msgs.filter((m) => m.type === 'service' && inP(m, start)).length, sC0 = msgs.filter((m) => m.type === 'service' && inP(m, start0, start)).length;
    const subs = d.subscribers || [], nC = subs.filter((m) => inP(m, start)).length, nC0 = subs.filter((m) => inP(m, start0, start)).length;
    return { tracking, V, PV, V0, PV0, chart,
        topPages: rowsP.slice(0, 10).map((r) => ({ label: r.label, path: r.path, value: nf(r.n), pct: PV ? Math.round(r.n / PV * 100) + ' %' : '' })),
        topProjects: barList(rowsP.filter((r) => r.icon === 'folder_open'), PV),
        sources: barList(Object.values(bySrc).sort((x, y) => y.n - x.n), V), devices,
        conv, convTotal, convTotal0, mC, mC0, sC, sC0, nC, nC0, subsTotal: subs.length, vb: buckets(cur, 'v', 14), pb: buckets(cur, 'pv', 14),
        rangeLabel: p + ' derniers jours · du ' + fmtD(cur[0].dt) + ' au ' + fmtD(cur[n - 1].dt) };
}

/* ---------- Définition des collections éditables ---------- */
const T = (fr, en) => ({ fr, en });
const F = (k, label, type, o = {}) => ({ k, label, type, ...o });
const pubF = F('published', 'Publication', 'switch', { on: 'Publié', off: 'Brouillon' });

export const COLS = {
    projects: { label: 'Projets', icon: 'grid_view', featured: true, title: (x) => t(x.title), meta: (x) => [t(x.category), x.year, (x.tech || []).join(', ')].filter(Boolean).join(' · '),
        blank: () => ({ published: false, featured: false, slug: 'nouveau-projet-' + Date.now().toString(36), title: T('Nouveau projet', 'New project'), year: '', link: '', repo: '', images: [], category: T('', ''), role: T('', ''), summary: T('', ''), context: T('', ''), problem: T('', ''), contribution: T('', ''), solution: T('', ''), results: T('', ''), tech: [] }),
        fields: [F('title', 'Titre', 'i18n'), F('category', 'Catégorie', 'i18n'), F('role', 'Rôle', 'i18n'), F('slug', 'Slug (adresse)', 'text'), F('year', 'Année', 'text'), F('link', 'Lien du site', 'url', { ph: 'https://' }), F('summary', 'Résumé', 'i18nArea', { full: true }), F('context', 'Contexte', 'i18nRich', { full: true }), F('problem', 'Enjeu', 'i18nRich', { full: true }), F('contribution', 'Ma contribution', 'i18nArea', { full: true, hint: 'Un point par ligne' }), F('solution', 'Fonctionnalités', 'i18nArea', { full: true, hint: 'Un point par ligne' }), F('results', 'Résultat', 'i18nRich', { full: true, hint: 'Uniquement des résultats réels' }), F('tech', 'Technologies', 'tags', { full: true, hint: 'Séparées par des virgules' }), F('images', 'Captures', 'mediaList', { full: true, hint: 'Ajoutez depuis la médiathèque (adresses séparées par des virgules)' }), F('featured', 'Accueil', 'switch', { on: 'Mis en avant', off: 'Non mis en avant' }), pubF] },
    posts: { label: 'Articles', icon: 'article', title: (x) => t(x.title), meta: (x) => [x.date, (x.tags || []).map((v) => t(v)).join(', '), (x.views || 0) + ' lecture(s)', (x.body && (x.body.fr || x.body.en)) ? '' : 'Contenu à rédiger'].filter(Boolean).join(' · '),
        blank: () => ({ published: false, icon: 'article', slug: '', date: iso(new Date()), readMin: 3, url: '', tags: [], title: T('Nouvel article', 'New article'), excerpt: T('', ''), body: T('', '') }),
        fields: [F('title', 'Titre', 'i18n', { full: true }), F('excerpt', 'Extrait', 'i18nArea', { full: true, hint: 'Affiché sur les cartes et en introduction de l’article' }), F('body', 'Contenu de l’article', 'i18nRich', { full: true }), F('slug', 'Adresse (/fr/blog/…)', 'text', { hint: 'Générée depuis le titre si vide' }), F('tags', 'Étiquettes', 'i18nTags', { full: true, hint: 'Séparées par des virgules, dans le même ordre en FR et EN' }), F('date', 'Date', 'date'), F('readMin', 'Lecture (min)', 'number', { hint: 'Calculée automatiquement depuis le contenu' }), F('icon', 'Icône', 'text', { hint: 'Nom d’icône Material Symbols (ex. shopping_bag, travel_explore, code_blocks)' }), F('url', 'Lien externe (facultatif)', 'url', { full: true, ph: 'https://', hint: 'Ex. version publiée sur LinkedIn ou Medium' }), pubF] },
    experiences: { label: 'Expériences', icon: 'work_history', title: (x) => t(x.role), meta: (x) => [x.company, t(x.start) + ' → ' + t(x.end)].join(' · '),
        blank: () => ({ published: false, current: false, company: '', location: T('', ''), role: T('Nouveau poste', 'New role'), start: T('', ''), end: T('', ''), description: T('', ''), duties: T('', '') }),
        fields: [F('role', 'Poste', 'i18n'), F('company', 'Entreprise', 'text'), F('location', 'Lieu', 'i18n'), F('start', 'Début', 'i18n'), F('end', 'Fin', 'i18n'), F('description', 'Description', 'i18nRich', { full: true }), F('duties', 'Missions', 'i18nArea', { full: true, hint: 'Une mission par ligne' }), F('current', 'Poste actuel', 'switch', { on: 'Oui', off: 'Non' }), pubF] },
    education: { label: 'Formation', icon: 'school', title: (x) => t(x.degree), meta: (x) => [x.school, x.period].filter(Boolean).join(' · '),
        blank: () => ({ published: false, degree: T('Nouvelle formation', 'New programme'), school: '', period: '' }),
        fields: [F('degree', 'Diplôme', 'i18n', { full: true }), F('school', 'Établissement', 'text'), F('period', 'Période', 'text', { ph: '2020 → 2023' }), pubF] },
    services: { label: 'Services', icon: 'design_services', title: (x) => t(x.title), meta: (x) => plainText(t(x.description)).slice(0, 90),
        blank: () => ({ published: false, icon: 'code', title: T('Nouveau service', 'New service'), description: T('', '') }),
        fields: [F('title', 'Titre', 'i18n'), F('icon', 'Icône', 'text', { hint: 'Nom d’icône Material Symbols (ex. code, search, school)' }), F('description', 'Description', 'i18nRich', { full: true }), pubF] },
    legalPages: { label: 'Pages légales', icon: 'gavel', title: (x) => t(x.title), meta: (x) => ['/fr/' + x.slugFr, '/en/' + x.slugEn, x.updatedAt ? 'mis à jour le ' + x.updatedAt : ''].filter(Boolean).join(' · '),
        blank: () => ({ published: false, title: T('Nouvelle page', 'New page'), slugFr: 'nouvelle-page-' + Date.now().toString(36), slugEn: 'new-page-' + Date.now().toString(36), body: T('', '') }),
        fields: [F('title', 'Titre', 'i18n', { full: true }), F('slugFr', 'Adresse FR (/fr/…)', 'text'), F('slugEn', 'Adresse EN (/en/…)', 'text'), F('body', 'Contenu', 'i18nRich', { full: true, hint: 'Variables : {name} (votre nom), {email} (votre e-mail cliquable), {site} (nom du site)' }), pubF] },
    certifications: { label: 'Certifications', icon: 'verified', title: (x) => t(x.name), meta: (x) => [x.issuer, x.date].filter(Boolean).join(' · '),
        blank: () => ({ published: false, name: T('Nouvelle certification', 'New certification'), issuer: '', date: '', verify: '' }),
        fields: [F('name', 'Nom', 'i18n', { full: true }), F('issuer', 'Organisme', 'text'), F('date', 'Date', 'text'), F('verify', 'Lien de vérification', 'url', { full: true }), pubF] },
    testimonials: { label: 'Témoignages', icon: 'format_quote', title: (x) => x.name || '(sans nom)', meta: (x) => [t(x.role), x.company].filter(Boolean).join(' · '),
        blank: () => ({ published: false, name: '', company: '', role: T('', ''), quote: T('', '') }),
        fields: [F('name', 'Nom', 'text'), F('company', 'Entreprise', 'text'), F('role', 'Fonction', 'i18n', { full: true }), F('quote', 'Témoignage', 'i18nArea', { full: true, hint: 'Uniquement des témoignages réels, avec l’accord de la personne' }), pubF] },
};

export const FORMS = {
    profile: [
        { title: 'Identité', sub: 'Affichée dans le hero et le footer', fields: [F('firstName', 'Prénom', 'text'), F('middleName', 'Deuxième prénom', 'text'), F('lastName', 'Nom', 'text'), F('since', 'Début d’activité (année)', 'text'), F('title', 'Titre', 'i18n'), F('stack', 'Stack principale', 'i18n')] },
        { title: 'Présentation', fields: [F('tagline', 'Proposition de valeur (hero)', 'i18nArea', { full: true }), F('bio', 'Biographie', 'i18nRich', { full: true }), F('availability', 'Disponibilité', 'i18n', { full: true }), F('softSkills', 'Qualités', 'i18n', { full: true, hint: 'Séparées par « · »' })] },
        { title: 'Statut & conditions', sub: 'Affichés dans le hero, « En bref » et la page Contact', fields: [F('availabilityShort', 'Disponibilité (courte)', 'i18n'), F('status', 'Statut', 'i18n'), F('replyTime', 'Délai de réponse (phrase)', 'i18n'), F('replyDelay', 'Délai de réponse (court)', 'i18n'), F('formats', 'Formats de mission', 'i18n'), F('zone', 'Zone d’intervention', 'i18n')] },
        { title: 'Coordonnées', fields: [F('email', 'E-mail', 'email'), F('phone', 'Téléphone', 'text'), F('location', 'Localisation', 'i18n'), F('hours', 'Horaires', 'i18n')] },
        { title: 'Médias', sub: 'Adresses depuis la médiathèque ou un hébergement', fields: [F('photo', 'Portrait', 'media', { full: true }), F('cv', 'CV (PDF)', 'media', { full: true, accept: 'doc' })] },
    ],
    seo: [
        { title: 'Référencement', sub: 'Titre et description affichés dans Google, en FR et en EN', fields: [F('siteTitle', 'Titre du site', 'i18n', { full: true }), F('metaDescription', 'Méta-description', 'i18nArea', { full: true, hint: '150 à 160 caractères conseillés' }), F('canonical', 'URL canonique', 'url', { ph: 'https://' }), F('favicon', 'Favicon', 'media')] },
        { title: 'Partage', sub: 'Aperçu sur LinkedIn, WhatsApp, Facebook', fields: [F('ogImage', 'Image de partage 1200×630', 'media', { full: true }), F('indexable', 'Indexation Google', 'switch', { on: 'Autorisée', off: 'Bloquée' }), F('structuredData', 'Données structurées (schema.org)', 'switch', { on: 'Activées', off: 'Désactivées' })] },
        { title: 'Moteurs de recherche', sub: 'Codes de vérification fournis par Google Search Console et Bing Webmaster Tools (méthode « balise HTML » : ne collez que la valeur de content).', fields: [F('googleVerification', 'Google Search Console', 'text', { ph: 'ex. AbC123…' }), F('bingVerification', 'Bing Webmaster Tools', 'text', { ph: 'ex. 1A2B3C…' })] },
    ],
    settings: [
        { title: 'Statistiques', sub: 'Le suivi intégré compte les visites, pages vues, provenances et clics de contact dans la base du site, sans cookie ni adresse IP. Les mots-clés Google se consultent dans Google Search Console.', fields: [F('analytics.provider', 'Suivi des visites', 'select', { options: [{ v: 'local', label: 'Suivi intégré (activé)' }, { v: 'none', label: 'Désactivé' }] })] },
        { title: 'Notifications', fields: [F('notifyEmail', 'E-mail de notification', 'email'), F('notifyOnMessage', 'Alerte à chaque message', 'switch', { on: 'Activée', off: 'Désactivée' })] },
        { title: 'Site', fields: [F('siteName', 'Nom du site', 'text'), F('maintenance', 'Mode maintenance', 'switch', { on: 'Site en maintenance', off: 'Site en ligne' })] },
    ],
};

/** Reconstruit un élément depuis le brouillon (champs « __raw » des listes saisies au clavier). */
export function finalize(dr) {
    const out = {};
    Object.keys(dr).forEach((k) => {
        if (k.endsWith('__raw')) out[k.slice(0, -5)] = dr[k].split(',').map((s) => s.trim()).filter(Boolean);
        else if (!k.endsWith('__rawFr') && !k.endsWith('__rawEn') && !(k in out)) out[k] = dr[k];
    });
    Object.keys(dr).filter((k) => k.endsWith('__rawFr') || k.endsWith('__rawEn')).forEach((k) => {
        const base = k.replace(/__raw(Fr|En)$/, '');
        const fr = (dr[base + '__rawFr'] != null ? dr[base + '__rawFr'] : (dr[base] || []).map((x) => x.fr).join(',')).split(',').map((s) => s.trim()).filter(Boolean);
        const en = (dr[base + '__rawEn'] != null ? dr[base + '__rawEn'] : (dr[base] || []).map((x) => x.en).join(',')).split(',').map((s) => s.trim());
        out[base] = fr.map((f, i) => ({ fr: f, en: en[i] || '' }));
    });
    return out;
}

export function go(router, v) {
    router.push('/admin/' + (v === 'dashboard' ? '' : v));
    state.menu = false;
    state.q = '';
    state.edit = null;
    window.scrollTo(0, 0);
}
