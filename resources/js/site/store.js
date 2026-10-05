import { computed, reactive } from 'vue';
import { labels } from './labels';
import { api, host, isEmail, lines, pad, reducedMotion, tx } from '../shared/util';

// Segments d'URL par route : [fr, en].
export const SLUGS = { blog: ['blog', 'blog'], about: ['a-propos', 'about'], work: ['projets', 'work'], services: ['services', 'services'], contact: ['contact', 'contact'] };

export function href(lang, route, slug) {
    const k = lang === 'en' ? 1 : 0;
    if (route === 'home') return '/' + lang;
    if (route === 'project') return '/' + lang + '/' + SLUGS.work[k] + '/' + slug;
    return '/' + lang + '/' + SLUGS[route][k];
}

export function parseRoute(r) {
    const lang = r.params.lang === 'en' ? 'en' : 'fr';
    let route = 'home', slug = null;
    if (r.name === 'project') { route = 'project'; slug = r.params.slug; }
    else if (r.name === 'page') Object.keys(SLUGS).forEach((k) => { if (SLUGS[k].includes(r.params.page)) route = k; });
    return { lang, route, slug };
}


const TONES = [{ bg: '#0B1530', fg: '#FFFFFF', sub: '#8FA3E8' }, { bg: '#2448C8', fg: '#FFFFFF', sub: '#D3DCF8' }, { bg: '#E8EDFB', fg: '#1B379E', sub: '#2448C8' }];

export const state = reactive({
    data: window.__SITE__ || null,
    lang: 'fr', route: 'home', slug: null,
    menu: false, scrolled: false, pc: 0, w: window.innerWidth, filter: 'all',
    cf: { name: '', email: '', subject: '', message: '' }, errs: {}, fs: 'idle',
    nl: '', nls: '', flip: null, tlh: null,
    svc: null, svcErr: {}, svcFs: 'idle',
});

const t = (v) => tx(v, state.lang);

export const vm = computed(() => {
    const s = state, d = s.data, fr = s.lang === 'fr', r = s.route, pr = d.profile;
    // Faits du profil (base de données) injectés dans les libellés.
    const featuredLangs = (pr.languages || []).filter((l) => l.featured);
    const L = { ...labels(fr),
        fSinceV: (fr ? 'Depuis ' : 'Since ') + pr.since + ' · ' + d.experiences.length + (fr ? ' postes' : ' roles'),
        fLangV: featuredLangs.map((l) => t(l.name) + (l.cefr ? ' (' + l.cefr + ')' : '')).join(' · '),
        iStatusV: t(pr.status), availShort: t(pr.availabilityShort), replyTime: t(pr.replyTime), gReplyV: t(pr.replyDelay),
        gFormatsV: t(pr.formats), gZoneV: t(pr.zone) };
    const mobile = s.w < 960, isHome = r === 'home';
    const H = (route) => href(s.lang, route);
    const hrefs = { home: H('home'), about: H('about'), work: H('work'), services: H('services'), contact: H('contact'), blog: H('blog') };
    const activeNav = r === 'project' ? 'work' : r;
    const navItems = ['home', 'about', 'work', 'services', 'contact'].map((k, i) => ({ key: k, label: L.nav[i], num: pad(i + 1), href: hrefs[k], current: activeNav === k }));

    const en = {}; d.home.sections.forEach((x) => { en[x.type] = x.enabled; });
    const show = {
        aboutShort: isHome && en.about !== false, approach: r === 'about', blog: (isHome && en.blog !== false) || r === 'blog', work: (isHome && en.projects) || r === 'work',
        exp: r === 'about', edu: r === 'about' && en.education !== false,
        skills: (isHome && en.skills) || r === 'about', services: (isHome && en.services) || r === 'services',
    };
    const socials = (pr.socials || []).map((x) => ({ label: x.label, url: x.url, icon: x.icon || 'link' }));
    const wa = (pr.socials || []).find((x) => x.label === 'WhatsApp' && x.visible && x.url);
    const p = {
        lastUp: (pr.lastName || '').toUpperCase(), first: pr.firstName, middle: pr.middleName || '', title: t(pr.title), stack: t(pr.stack), tagline: t(pr.tagline), location: t(pr.location),
        email: pr.email, mailto: 'mailto:' + pr.email, phone: pr.phone, photo: pr.photo, cv: pr.cv,
        photoAlt: fr ? 'Portrait de Fawoziath Modjissola Salou' : 'Portrait of Fawoziath Modjissola Salou',
    };
    const contactRows = [
        pr.email && { icon: 'mail', label: L.email, value: pr.email, href: 'mailto:' + pr.email },
        pr.phone && { icon: 'call', label: L.phone, value: pr.phone, href: 'tel:' + pr.phone.replace(/\s/g, '') },
        t(pr.location) && { icon: 'location_on', label: L.location, value: t(pr.location) },
        t(pr.hours) && { icon: 'schedule', label: L.hours, value: t(pr.hours) },
    ].filter(Boolean).map((c, i) => ({ ...c, num: pad(i + 1) }));

    const exps = d.experiences;
    const cur = exps.find((e) => e.current);
    const pub = d.projects;
    const kindOf = (x) => /site|website|vitrine/i.test(t(x.category)) ? 'site' : 'app';
    const num = (x) => pad(pub.indexOf(x) + 1);
    let list = isHome ? pub.filter((x) => x.featured).slice(0, 6) : pub;
    if (r === 'work' && s.filter !== 'all') list = list.filter((x) => kindOf(x) === s.filter);
    const cards = list.map((x, i) => ({ ...TONES[i % 3], id: x.id, num: num(x), title: t(x.title), cat: t(x.category), summary: t(x.summary), kind: kindOf(x) === 'site' ? L.kSite : L.kApp,
        href: href(s.lang, 'project', x.slug), link: x.link, host: host(x.link), tags: x.tech || [] }));
    const filters = [['all', L.fAll], ['app', L.kApp], ['site', L.kSite]].map(([k, label]) => ({ key: k, label, count: pub.filter((x) => k === 'all' || kindOf(x) === k).length, on: s.filter === k }));

    let cs = null;
    const cp = r === 'project' && pub.find((x) => x.slug === s.slug);
    if (cp) {
        const i = pub.indexOf(cp), pv = pub[(i - 1 + pub.length) % pub.length], nx = pub[(i + 1) % pub.length];
        const blocks = [[L.bContext, t(cp.context), false], [L.bChallenge, t(cp.problem), false], [L.bContrib, t(cp.contribution), true], [L.bSolution, t(cp.solution), true], [L.bResult, t(cp.results), false]]
            .filter((b) => b[1].trim()).map((b, k) => ({ num: pad(k + 1), label: b[0], text: b[1], isList: b[2], items: lines(b[1]) }));
        const gallery = (Array.isArray(cp.images) ? cp.images : []).map((im) => ({ src: typeof im === 'string' ? im : im.src, alt: t(cp.title) })).filter((g) => g.src);
        cs = { ...TONES[i % 3], num: num(cp), title: t(cp.title), link: cp.link, host: host(cp.link),
            meta: [{ label: L.mType, value: t(cp.category) }, { label: L.mRole, value: t(cp.role) }, cp.year && { label: L.mYear, value: cp.year }].filter(Boolean),
            tech: cp.tech || [], blocks, gallery,
            prev: { title: t(pv.title), href: href(s.lang, 'project', pv.slug) }, next: { title: t(nx.title), href: href(s.lang, 'project', nx.slug) } };
    }

    const sinceY = parseInt(pr.since || '0', 10);
    const heroStats = [
        sinceY && { value: Math.max(1, new Date().getFullYear() - sinceY) + '+', label: L.sYears },
        { value: String(pub.length), label: L.sProjects },
        { value: String(exps.length), label: L.sRoles },
    ].filter(Boolean);
    const annots = [{ label: L.aRole, value: t(pr.title), top: '14%', dot: '#FFFFFF' }, { label: L.aLoc, value: t(pr.location), top: '46%', dot: '#FFFFFF' }, { label: L.aStatus, value: L.availShort, top: '78%', dot: '#2448C8' }];
    const aboutInfo = [
        { label: L.iName, value: [pr.firstName, pr.middleName, pr.lastName].filter(Boolean).join(' ') },
        { label: L.iLoc, value: t(pr.location) },
        { label: L.iLang, value: (pr.languages || []).map((l) => t(l.name)).join(' · ') },
        { label: L.iStatus, value: L.iStatusV },
    ].map((x, i) => ({ ...x, num: pad(i + 1) }));
    const kindOfExp = (e) => { const ro = (e.role && (e.role.fr || '')) || ''; return /stag/i.test(ro) ? L.kStage : /cdd/i.test(ro) ? L.kCdd : L.kCdi; };
    let tlSrc = exps.map((e) => ({ kind: kindOfExp(e), title: t(e.role), org: [e.company, t(e.location)].filter(Boolean).join(' · '), period: t(e.start) + ' → ' + t(e.end), desc: t(e.description), duties: lines(t(e.duties)), current: !!e.current }));
    if (r === 'about') tlSrc = tlSrc.concat(d.education.map((x) => ({ kind: L.kEdu, title: t(x.degree), org: x.school, period: x.period, desc: '', duties: [], current: false })));
    const tlItems = tlSrc.map((x, i) => ({ ...x, col: mobile ? '2' : (i % 2 ? '3' : '1'), on: s.tlh === i }));
    const tl = mobile ? { cols: '20px minmax(0,1fr)', colGap: '16px', dotCol: '1', line: '9px', gap: '36px' } : { cols: 'minmax(0,1fr) 40px minmax(0,1fr)', colGap: '32px', dotCol: '2', line: '50%', gap: '8px' };
    const softList = String(t(pr.softSkills) || '').split('·').map((x) => x.trim()).filter(Boolean);

    const allSkills = d.skillGroups.flatMap((g) => g.skills.map((k) => ({ name: t(k.name), logo: k.logo, icon: k.icon || 'code' })));
    const heroStack = d.skillGroups.flatMap((g) => g.skills).filter((k) => k.heroPosition).sort((a, b) => a.heroPosition - b.heroPosition).map((k) => t(k.name));
        const fmtDate = (iso) => { try { return new Date(iso + 'T12:00:00').toLocaleDateString(fr ? 'fr-FR' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }); } catch (e) { return iso; } };
    const allPosts = (d.posts || []).slice().sort((a, b) => String(b.date).localeCompare(String(a.date)));
    const posts = (isHome ? allPosts.slice(0, 3) : allPosts).map((x, i) => ({ id: x.id, num: pad(i + 1), title: t(x.title), excerpt: t(x.excerpt), date: fmtDate(x.date), read: (x.readMin || 1) + ' ' + L.read, icon: x.icon || 'article', url: x.url, tags: (x.tags || []).map((v) => t(v)) }));
    if (isHome && !posts.length) show.blog = false;
    const aboutChips = [{ icon: 'location_on', text: t(pr.location) }, cur && { icon: 'work', text: cur.company + ' · ' + (fr ? 'depuis ' : 'since ') + t(cur.start) }, featuredLangs.length && { icon: 'translate', text: featuredLangs.map((l) => t(l.name) + (l.cefr ? ' ' + l.cefr : '')).join(' · ') }].filter(Boolean);

    const pages = { blog: L.pBlog, about: L.pAbout, work: L.pWork, services: L.pServices, contact: L.pContact };
    const picons = { blog: 'article', about: 'person', work: 'grid_view', services: 'design_services', contact: 'mail' };
    let page = {};
    if (pages[r]) page = { eyebrow: pages[r][0], title: pages[r][1], intro: pages[r][2], crumb: pages[r][1], icon: picons[r] };
    if (cp) page = { eyebrow: t(cp.category), title: t(cp.title), intro: t(cp.summary), crumb: t(cp.title), icon: 'folder_open' };
    const pIdx = ['home', 'about', 'work', 'services', 'contact'].indexOf(r === 'project' ? 'work' : r);
    page.index = pIdx > 0 ? pad(pIdx + 1) + ' / 05' : (r === 'blog' ? 'Blog' : '');
    const panels = {
        about: [{ label: L.fWhere, value: t(pr.location) }, cur && { label: L.fNow, value: cur.company }, { label: L.fSince, value: L.fSinceV }, { label: L.fLang, value: L.fLangV }],
        work: [{ label: L.gProjects, value: String(pub.length) }, { label: L.kApp, value: String(pub.filter((x) => kindOf(x) === 'app').length) }, { label: L.kSite, value: String(pub.filter((x) => kindOf(x) === 'site').length) }, { label: L.gOnline, value: String(pub.filter((x) => x.link).length) }],
        services: [{ label: L.gServices, value: String(d.services.length) }, { label: L.gFormats, value: L.gFormatsV }, { label: L.gZone, value: L.gZoneV }],
        contact: [{ label: L.email, value: pr.email }, { label: L.phone, value: pr.phone }, { label: L.gReply, value: L.gReplyV }, { label: L.hours, value: t(pr.hours) }],
        blog: [{ label: L.gArticles, value: String(allPosts.length) }, { label: L.gTopics, value: [...new Set(allPosts.flatMap((x) => (x.tags || []).map((v) => t(v))))].slice(0, 4).join(' · ') }],
    };
    page.rows = (panels[r] || []).filter((x) => x && x.value).map((x, k) => ({ ...x, num: pad(k + 1) }));
    page.jumps = r === 'about' ? [['approach', L.jMethod], ['experience', L.jPath], ['languages', L.jLang], ['skills', L.jSkills]].map(([id, label]) => ({ id, label })) : [];

    const testis = (d.testimonials || []).filter((x) => t(x.quote)).map((x) => ({ id: x.id, quote: t(x.quote), name: x.name, initial: (x.name || '?')[0], role: [t(x.role), x.company].filter(Boolean).join(', ') }));
    const svcList = d.services.map((x) => ({ id: x.id, icon: x.icon || 'code', title: t(x.title), desc: t(x.description) }));

    return {
        L, fr, mobile, isHome, r, hrefs, navItems, show, p, socials, contactRows, wa: wa ? wa.url : '',
        isWork: r === 'work', isContact: r === 'contact', isProject: !!cp, hasPageHead: r !== 'home', showCta: r !== 'contact', ctaTitle: cp ? L.ctaCase : L.ctaHome,
        showTesti: isHome && en.testimonials && testis.length > 0, testis,
        home: { cta1: t(d.home.ctaPrimary), cta2: t(d.home.ctaSecondary) },
        bio: lines(t(pr.bio)),
        values: (pr.values || []).map((v, i) => ({ ...[{ bg: '#0B1530', fg: '#FFFFFF', sub: '#8FA3E8' }, { bg: '#2448C8', fg: '#FFFFFF', sub: '#D3DCF8' }, { bg: '#E8EDFB', fg: '#0B1530', sub: '#2448C8' }][i % 3],
            num: pad(i + 1), title: t(v.title), text: t(v.text), icon: v.icon, keys: (v.keys || []).map((k) => t(k)), open: s.flip === i })),
        cards, filters, projCount: pad(pub.length), cs, page,
        skillGroups: d.skillGroups.map((g, gi) => ({ id: g.id, num: pad(gi + 1), label: t(g.label), skills: g.skills.map((k) => ({ name: t(k.name), note: t(k.note), logo: k.logo, icon: k.icon || 'code' })) })),
        allSkills, posts, aboutChips, heroStats, heroStack, annots, annotPad: mobile ? '0px' : '150px',
        aboutInfo, tlItems, tl, softList, services: svcList,
        languages: (pr.languages || []).map((l) => ({ name: t(l.name), level: t(l.level) })),
        certs: (d.certifications || []).map((c) => ({ id: c.id, name: t(c.name), issuer: c.issuer, date: c.date, verify: c.verify })),
        resItems: [{ label: L.skillsLabel, href: hrefs.about }, { label: L.eduLabel, href: hrefs.about }, { label: L.nav2, href: hrefs.work }, { label: 'Blog', href: hrefs.blog }],
        availNum: pad(contactRows.length + 1), year: new Date().getFullYear(),
    };
});

/* ---------- Actions ---------- */

export function scrollToId(id) {
    const el = document.getElementById(id);
    if (el) window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - 72, behavior: reducedMotion() ? 'auto' : 'smooth' });
}

export function setField(k, v) {
    state.cf[k] = v;
    state.errs = { ...state.errs, [k]: null };
    if (state.fs === 'sent') state.fs = 'idle';
}

export async function submitContact() {
    const L = labels(state.lang === 'fr'), c = state.cf, errs = {};
    if (!c.name.trim()) errs.name = L.eName;
    if (!isEmail(c.email)) errs.email = L.eEmail;
    if (c.message.trim().length < 10) errs.message = L.eMsg;
    if (Object.keys(errs).length) { state.errs = errs; state.fs = 'error'; return; }
    state.fs = 'sending';
    try {
        await api.post('/messages', { type: 'contact', name: c.name, email: c.email, subject: c.subject, message: c.message, lang: state.lang });
        state.fs = 'sent';
        state.cf = { name: '', email: '', subject: '', message: '' };
        state.errs = {};
    } catch (e) {
        state.fs = 'failed';
    }
}

let svcPrev = null;
export function askService(title, icon) {
    svcPrev = document.activeElement;
    state.svc = { service: title, icon: icon || 'design_services', name: '', email: '', phone: '', when: '', message: '' };
    state.svcErr = {};
    state.svcFs = 'idle';
    document.body.style.overflow = 'hidden';
    setTimeout(() => { const el = document.querySelector('[name="svc-name"]'); if (el) el.focus(); }, 30);
}

export function closeSvc() {
    document.body.style.overflow = '';
    state.svc = null;
    state.svcFs = 'idle';
    if (svcPrev && svcPrev.focus) svcPrev.focus();
}

export async function submitSvc() {
    const L = labels(state.lang === 'fr'), c = state.svc, errs = {};
    if (!c.name.trim()) errs.name = L.eName;
    if (!isEmail(c.email)) errs.email = L.eEmail;
    if (c.message.trim().length < 10) errs.message = L.eMsg;
    if (Object.keys(errs).length) { state.svcErr = errs; return; }
    state.svcFs = 'sending';
    try {
        await api.post('/messages', { type: 'service', service: c.service, name: c.name, email: c.email, phone: c.phone, when: c.when === L.choose ? '' : c.when, message: c.message, lang: state.lang });
        state.svcFs = 'sent';
    } catch (e) {
        state.svcFs = 'idle';
        state.svcErr = { message: L.errSend };
    }
}

export async function subscribe() {
    const v = state.nl.trim();
    if (!isEmail(v)) { state.nls = 'err'; return; }
    try {
        await api.post('/subscribers', { email: v, lang: state.lang });
        state.nl = '';
        state.nls = 'ok';
    } catch (e) {
        state.nls = e.response && e.response.status === 409 ? 'dup' : 'err';
    }
}

/* ---------- Suivi d'audience intégré (si activé dans l'administration) ---------- */

function sid() {
    try {
        let v = sessionStorage.getItem('fz.sid');
        if (!v) { v = Math.random().toString(36).slice(2) + Date.now().toString(36); sessionStorage.setItem('fz.sid', v); }
        return v;
    } catch (e) { return 'anon'; }
}
const device = () => { const w = window.innerWidth; return w < 768 ? 'mobile' : w < 1100 ? 'tablet' : 'desktop'; };
let firstHit = true;

export function track(t, extra = {}) {
    if (!state.data || !state.data.settings || !state.data.settings.tracking) return;
    const body = { t, sid: sid(), dev: device(), path: location.pathname, ...extra };
    if (t === 'pv' && firstHit) { body.ref = document.referrer; firstHit = false; }
    api.post('/track', body).catch(() => {});
}

export function trackClicks(e) {
    const a = e.target.closest && e.target.closest('a[href]');
    if (!a) return;
    const h = a.getAttribute('href') || '', cv = state.data && state.data.profile.cv;
    const k = /wa\.me|whatsapp/i.test(h) ? 'whatsapp' : h.startsWith('mailto:') ? 'email' : /linkedin\.com/i.test(h) ? 'linkedin' : cv && h === cv ? 'cv' : null;
    if (k) track('click', { k });
}
