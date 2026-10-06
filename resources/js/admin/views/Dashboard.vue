<script setup>
import { computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { COLS, PRESETS, ask, flash, fmtMD, go, inboxStats, isLocal, loadEvents, missingEn, nf, save, state, stats, statsRange, syncDoc, t } from '../store';
import AudienceChart from '../components/AudienceChart.vue';
import BarList from '../components/BarList.vue';
import Panel from '../components/Panel.vue';
import KpiGrid from '../components/KpiGrid.vue';

/*
| Tableau de bord : ce qui demande une action (échanges à traiter, état et complétude des contenus, activité).
| L'analyse d'audience détaillée est dans « Statistiques » ; ici, un seul indicateur sur 30 jours y renvoie.
*/
const router = useRouter();

// Disponibilité du site : maintenance activable en un clic (confirmation demandée pour la couper aux visiteurs).
const maint = computed(() => !!(state.data.settings || {}).maintenance);
const setMaint = (v) => { const msg = v ? 'Site mis en maintenance' : 'Site remis en ligne'; save((d) => { d.settings = { ...(d.settings || {}), maintenance: v }; }, null, () => syncDoc('settings', msg)); flash(msg, v ? 'warning' : 'success'); };
const toggleMaint = () => (maint.value ? setMaint(false) : ask('Mettre le site en maintenance ?', 'Les visiteurs verront une page « site en maintenance » et le contenu ne sera plus indexé. Vous continuerez à voir le site tant que vous êtes connectée.', () => setMaint(true), 'Mettre en maintenance'));
const NEUTRAL = { dBg: 'var(--ln2)', dFg: 'var(--mu)' };
const WARN = { dBg: 'var(--warnBg)', dFg: 'var(--warnFg)' };
const GOOD = { dBg: 'var(--okBg)', dFg: 'var(--okFg)' };

const msgs = computed(() => (state.data.messages || []).slice().sort((a, b) => String(b.date).localeCompare(String(a.date))));
const newCount = computed(() => msgs.value.filter((m) => m.status === 'new').length);
const toHandle = computed(() => msgs.value.filter((m) => m.status === 'new' || m.status === 'read'));
const openMsg = (m) => { go(router, 'messages'); state.msgId = m.id; };

// Période du tableau de bord (indépendante des filtres des statistiques).
const dp = computed({ get: () => state.dp, set: (v) => { state.dp = v; } });
watch(() => state.dp, () => loadEvents());
const DP = PRESETS.filter(([v]) => v !== 'custom');
const dpLabel = computed(() => (DP.find(([v]) => v === state.dp) || DP[1])[1]);
const st = computed(() => { state.events; return stats({ preset: state.dp }); });
const ib = computed(() => inboxStats(statsRange({ preset: state.dp })));
// Ancienneté d'un message en attente.
const age = (m) => { const h = (Date.now() - new Date(m.at || m.date)) / 36e5; return h < 1 ? 'à l’instant' : h < 24 ? 'il y a ' + Math.round(h) + ' h' : 'il y a ' + Math.round(h / 24) + ' j'; };
const lastSubs = computed(() => (state.data.subscribers || []).slice().sort((a, b) => String(b.date).localeCompare(String(a.date))).slice(0, 5));

const kpis = computed(() => {
  state.events; // recalcul quand les événements du suivi intégré arrivent
  const s = st.value, d = state.data;
  const svc = toHandle.value.filter((m) => m.type === 'service').length;
  const pub = (d.projects || []).filter((x) => x.published).length + (d.posts || []).filter((x) => x.published).length;
  const drafts = (d.projects || []).filter((x) => !x.published).length + (d.posts || []).filter((x) => !x.published).length;
  return [
    { label: 'Messages non lus', icon: 'mark_email_unread', value: String(newCount.value), delta: toHandle.value.length + ' en attente de réponse', ...(newCount.value ? WARN : NEUTRAL), onClick: () => go(router, 'messages') },
    { label: 'Demandes de service', icon: 'design_services', value: String(svc), delta: svc ? 'à traiter' : 'Aucune en attente', ...(svc ? WARN : NEUTRAL), onClick: () => go(router, 'messages') },
    { label: 'Inscrits newsletter', icon: 'group_add', value: String(s.subsTotal), delta: (s.nC ? '+' + s.nC : 'Aucun nouveau') + ' · ' + dpLabel.value, ...(s.nC ? GOOD : NEUTRAL), onClick: () => go(router, 'newsletter') },
    { label: 'Projets et articles publiés', icon: 'public', value: String(pub), delta: drafts ? drafts + ' brouillon(s)' : 'Aucun brouillon', ...NEUTRAL, onClick: () => go(router, 'projects') },
    { label: 'Visiteurs · ' + dpLabel.value, icon: 'query_stats', value: s.tracking ? nf(s.V) : '—', delta: s.tracking ? 'Voir les statistiques →' : 'Suivi désactivé', ...NEUTRAL, onClick: () => go(router, 'stats') },
  ];
});

// État des contenus : publiés, brouillons et mis en avant, par type.
const contentRows = computed(() => ['projects', 'posts', 'services', 'experiences', 'certifications', 'testimonials', 'legalPages']
  .filter((k) => COLS[k] && state.data[k])
  .map((k) => {
    const items = state.data[k], hasPub = items.some((x) => 'published' in x);
    return {
      key: k, label: COLS[k].label, icon: COLS[k].icon, total: items.length,
      published: hasPub ? items.filter((x) => x.published).length : items.length,
      drafts: hasPub ? items.filter((x) => !x.published).length : 0,
      featured: COLS[k].featured ? items.filter((x) => x.featured).length : null,
    };
  }));

const health = computed(() => {
  const d = state.data, pr = d.profile, pub = d.projects.filter((x) => x.published);
  const noImg = pub.filter((x) => !(x.images || []).length).length, noYear = pub.filter((x) => !x.year).length, noTech = pub.filter((x) => !(x.tech || []).length).length;
  const postsNoUrl = (d.posts || []).filter((x) => x.published && !(x.body && (x.body.fr || x.body.en)) && !x.url).length;
  const miss = (d.labels || []).filter((l) => l.fr && !l.en).length + missingEn({ profile: d.profile, projects: d.projects, experiences: d.experiences, services: d.services, education: d.education, posts: d.posts, legalPages: d.legalPages, seo: d.seo });
  const noSource = !isLocal();
  const lg = (d.legalPages || []).find((x) => x.key === 'legal');
  const legalOk = !!lg && lg.published && !/\[(À compléter|To be completed)/.test(JSON.stringify(lg.body || {}));
  const items = [
    { label: 'Portrait professionnel', done: !!pr.photo, go: 'profile' },
    { label: 'CV en PDF', done: !!pr.cv, go: 'profile' },
    { label: 'Captures des projets', done: !noImg, detail: noImg ? noImg + ' projet(s) sans capture' : '', go: 'projects' },
    { label: 'Années des projets', done: !noYear, detail: noYear ? noYear + ' projet(s) sans année' : '', go: 'projects' },
    { label: 'Technologies des projets', done: !noTech, detail: noTech ? noTech + ' projet(s) sans stack' : '', go: 'projects' },
    { label: 'Texte complet des articles', done: !postsNoUrl, detail: postsNoUrl ? postsNoUrl + ' article(s) sans contenu ni lien' : '', go: 'posts' },
    { label: 'Traductions anglaises', done: !miss, detail: miss ? miss + ' champ(s) sans traduction EN' : '', go: 'projects' },
    { label: 'Image de partage (réseaux sociaux)', done: !!(d.seo && d.seo.ogImage), go: 'seo' },
    { label: 'Suivi des visites activé', done: !noSource, detail: noSource ? 'Statistiques de visite indisponibles tant que le suivi intégré est désactivé' : '', go: 'settings' },
    { label: 'Témoignages réels', done: (d.testimonials || []).some((x) => x.published), go: 'testimonials' },
    { label: 'Mentions légales publiées et complètes', done: legalOk, detail: legalOk ? '' : 'Coordonnées de l’hébergeur à compléter, puis publier la page', go: 'legalPages' },
  ];
  const doneN = items.filter((x) => x.done).length;
  return { w: Math.round(doneN / items.length * 100) + '%', label: doneN + ' sur ' + items.length + ' éléments complétés', items: items.sort((a, b) => (a.done ? 1 : 0) - (b.done ? 1 : 0)) };
});

const quick = [
  ['add', 'Nouveau projet', () => { go(router, 'projects'); state.edit = { col: 'projects', isNew: true, draft: COLS.projects.blank() }; }],
  ['edit_note', 'Nouvel article', () => { go(router, 'posts'); state.edit = { col: 'posts', isNew: true, draft: COLS.posts.blank() }; }],
  ['person', 'Modifier mon profil', () => go(router, 'profile')],
  ['travel_explore', 'SEO & partage', () => go(router, 'seo')],
  ['open_in_new', 'Voir le site', () => window.open('/', '_blank')],
];
const when = (ts) => { const m = Math.round((Date.now() - ts) / 60000); return m < 1 ? 'à l’instant' : m < 60 ? 'il y a ' + m + ' min' : m < 1440 ? 'il y a ' + Math.round(m / 60) + ' h' : new Date(ts).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }); };
</script>

<template>
  <div class="panel avail-bar" :class="{ off: maint }">
    <span class="avail-state"><span class="avail-dot" aria-hidden="true"></span><strong>{{ maint ? 'Site en maintenance' : 'Site en ligne' }}</strong><span class="sm-mu">{{ maint ? 'Les visiteurs voient la page de maintenance.' : 'Les visiteurs voient le site normalement.' }}</span></span>
    <button type="button" class="abtn" :class="{ primary: maint }" @click="toggleMaint"><span class="ms">{{ maint ? 'public' : 'construction' }}</span>{{ maint ? 'Remettre le site en ligne' : 'Mettre en maintenance' }}</button>
  </div>

  <div class="row-wrap center gap10 dash-period">
    <span class="sm-mu">Période :</span>
    <div role="group" aria-label="Période du tableau de bord" class="periods">
      <button v-for="[v, lb] in DP" :key="v" type="button" :aria-pressed="dp === v" :class="{ on: dp === v }" @click="dp = v">{{ lb }}</button>
    </div>
  </div>

  <KpiGrid :kpis="kpis" />

  <div class="grid g360">
    <Panel title="À traiter" :sub="toHandle.length ? toHandle.length + ' message(s) en attente de réponse, du plus ancien au plus récent' : 'Aucun message en attente'">
      <template #action><button type="button" class="abtn xs" @click="go(router, 'messages')">Boîte de réception</button></template>
      <div class="list-pad">
        <p v-if="!ib.pending.length" class="empty-txt">Tout est traité.</p>
        <button v-for="m in ib.pending.slice(0, 6)" :key="m.id" type="button" class="msg-row" @click="openMsg(m)">
          <span class="msg-ico ms">{{ m.type === 'service' ? 'design_services' : 'mail' }}</span>
          <span class="msg-txt"><span class="msg-l1"><strong>{{ m.name }}</strong><span :title="fmtMD(m.date)">{{ age(m) }}</span></span><span class="msg-sub">{{ t(m.subject) || '(sans sujet)' }}</span></span>
          <span v-if="m.status === 'new'" class="new-tag">Nouveau</span>
        </button>
      </div>
    </Panel>
    <Panel title="Contenus à compléter" :sub="health.label">
      <div class="health-track"><div :style="{ width: health.w }"></div></div>
      <div class="list-pad">
        <div v-for="hi in health.items" :key="hi.label" class="health-row">
          <span aria-hidden="true" class="ms" :style="{ color: hi.done ? 'var(--okFg)' : 'var(--warnFg)' }">{{ hi.done ? 'check_circle' : 'error' }}</span>
          <span class="health-txt"><span :class="{ done: hi.done }">{{ hi.label }}</span><span v-if="hi.detail" class="sm-mu">{{ hi.detail }}</span></span>
          <button v-if="!hi.done" type="button" class="abtn xxs" @click="go(router, hi.go)">Compléter</button>
        </div>
      </div>
    </Panel>
  </div>

  <div class="grid g320">
    <Panel title="Visites" :sub="st.tracking ? st.rangeLabel : 'Suivi des visites désactivé'">
      <template #action><button type="button" class="abtn xs" @click="go(router, 'stats')">Statistiques</button></template>
      <div v-if="st.tracking" class="pad-chart"><AudienceChart :chart="st.chart" /></div>
      <p v-else class="empty-txt">Activez le suivi intégré dans Paramètres.</p>
    </Panel>
    <Panel title="Demandes par service" :sub="ib.requests + ' demande(s) · ' + dpLabel"><BarList :rows="ib.byService" /></Panel>
    <Panel title="Derniers inscrits" :sub="(state.data.subscribers || []).length + ' inscrit(s) à la newsletter'">
      <template #action><button type="button" class="abtn xs" @click="go(router, 'newsletter')">Newsletter</button></template>
      <div class="list-pad">
        <p v-if="!lastSubs.length" class="empty-txt">Aucun inscrit pour le moment.</p>
        <div v-for="x in lastSubs" :key="x.id" class="sub-row"><span class="ms" aria-hidden="true">mail</span><span class="f1 ellipsis">{{ x.email }}</span><span class="mono13 mu">{{ (x.lang || 'fr').toUpperCase() }} · {{ fmtMD(x.date) }}</span></div>
      </div>
    </Panel>
  </div>

  <Panel title="État des contenus" sub="Ce qui est en ligne, en brouillon et mis en avant">
    <div class="scroll-x">
      <table class="tbl">
        <thead><tr><th>Contenu</th><th class="r">Publiés</th><th class="r">Brouillons</th><th class="r">Mis en avant</th><th></th></tr></thead>
        <tbody>
          <tr v-for="r in contentRows" :key="r.key">
            <td><span class="row center gap10"><span aria-hidden="true" class="ms mu">{{ r.icon }}</span><span class="fw5">{{ r.label }}</span></span></td>
            <td class="r mono">{{ r.published }}</td>
            <td class="r mono" :class="{ mu: !r.drafts }">{{ r.drafts }}</td>
            <td class="r mono mu">{{ r.featured === null ? '–' : r.featured }}</td>
            <td class="r"><button type="button" class="abtn xxs" @click="go(router, r.key)">Gérer</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </Panel>

  <Panel title="Raccourcis">
    <div class="quick"><button v-for="[icon, label, fn] in quick" :key="label" type="button" class="abtn hov" @click="fn"><span class="ms">{{ icon }}</span>{{ label }}</button></div>
  </Panel>

  <Panel title="Activité récente" sub="Dernières modifications dans l’administration">
    <div class="activity">
      <p v-if="!(state.data.activity || []).length" class="sm-mu pad16">Aucune modification pour le moment.</p>
      <div v-for="(ac, i) in (state.data.activity || []).slice(0, 8)" :key="i" class="act-row"><span aria-hidden="true" class="ms">history</span><span class="f1">{{ ac.msg }}</span><span class="sm-mu">{{ when(ac.ts) }}</span></div>
    </div>
  </Panel>
</template>
