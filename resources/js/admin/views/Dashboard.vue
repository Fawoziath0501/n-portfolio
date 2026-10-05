<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { COLS, delta, fmtMD, go, isLocal, missingEn, nf, spark, state, stats, t } from '../store';
import Panel from '../components/Panel.vue';
import KpiGrid from '../components/KpiGrid.vue';
import BarList from '../components/BarList.vue';
import AudienceChart from '../components/AudienceChart.vue';
import DataBanner from '../components/DataBanner.vue';

const router = useRouter();
const st = computed(() => { state.events; return stats(state.period); });
const kpis = computed(() => {
  const s = st.value;
  return [
    { label: 'Visiteurs', icon: 'group', value: nf(s.V), spark: spark(s.vb), ...delta(s.V, s.V0, true) },
    { label: 'Pages vues', icon: 'visibility', value: nf(s.PV), spark: spark(s.pb), ...delta(s.PV, s.PV0, true) },
    { label: 'Messages reçus', icon: 'mail', value: String(s.mC), ...delta(s.mC, s.mC0) },
    { label: 'Demandes de service', icon: 'design_services', value: String(s.sC), ...delta(s.sC, s.sC0) },
    { label: 'Inscrits newsletter', icon: 'mark_email_unread', value: String(s.subsTotal), ...delta(s.nC, s.nC0) },
  ];
});

const msgs = computed(() => (state.data.messages || []).slice().sort((a, b) => String(b.date).localeCompare(String(a.date))));
const newCount = computed(() => msgs.value.filter((m) => m.status === 'new').length);
const openMsg = (m) => { go(router, 'messages'); state.msgId = m.id; };

const health = computed(() => {
  const d = state.data, pr = d.profile, pub = d.projects.filter((x) => x.published);
  const noImg = pub.filter((x) => !(x.images || []).length).length, noYear = pub.filter((x) => !x.year).length, noTech = pub.filter((x) => !(x.tech || []).length).length;
  const postsNoUrl = (d.posts || []).filter((x) => x.published && !x.url).length;
  const miss = missingEn({ profile: d.profile, projects: d.projects, experiences: d.experiences, services: d.services, education: d.education, posts: d.posts, seo: d.seo });
  const noSource = !isLocal() && !(d.settings.analytics && d.settings.analytics.siteId);
  const items = [
    { label: 'Portrait professionnel', done: !!pr.photo, go: 'profile' },
    { label: 'CV en PDF', done: !!pr.cv, go: 'profile' },
    { label: 'Captures des projets', done: !noImg, detail: noImg ? noImg + ' projet(s) sans capture' : '', go: 'projects' },
    { label: 'Années des projets', done: !noYear, detail: noYear ? noYear + ' projet(s) sans année' : '', go: 'projects' },
    { label: 'Technologies des projets', done: !noTech, detail: noTech ? noTech + ' projet(s) sans stack' : '', go: 'projects' },
    { label: 'Texte complet des articles', done: !postsNoUrl, detail: postsNoUrl ? postsNoUrl + ' article(s) sans lien' : '', go: 'posts' },
    { label: 'Traductions anglaises', done: !miss, detail: miss ? miss + ' champ(s) sans traduction EN' : '', go: 'projects' },
    { label: 'Image de partage (réseaux sociaux)', done: !!(d.seo && d.seo.ogImage), go: 'seo' },
    { label: 'Source de statistiques connectée', done: !noSource, detail: noSource ? 'Recommandé : suivi intégré ou Umami + Search Console' : '', go: 'settings' },
    { label: 'Témoignages réels', done: (d.testimonials || []).some((x) => x.published), go: 'testimonials' },
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
  <DataBanner />
  <KpiGrid :kpis="kpis" />

  <div class="grid g420">
    <Panel title="Audience" :sub="st.rangeLabel">
      <template #action><button type="button" class="abtn xs" @click="go(router, 'stats')">Détails</button></template>
      <div class="pad-chart"><AudienceChart :chart="st.chart" /></div>
    </Panel>
    <Panel title="Clics de contact" sub="Ce que font les visiteurs intéressés">
      <div class="conv">
        <div v-for="cv in st.conv" :key="cv.label" class="conv-item">
          <span class="conv-l"><span aria-hidden="true" class="ms">{{ cv.icon }}</span>{{ cv.label }}</span>
          <strong>{{ cv.value }}</strong>
          <span class="conv-note">{{ cv.note }}</span>
        </div>
      </div>
    </Panel>
  </div>

  <div class="grid g320">
    <Panel title="Pages les plus vues" :sub="st.rangeLabel"><BarList :rows="st.topPages5" /></Panel>
    <Panel title="Provenance" sub="D’où viennent les visiteurs"><BarList :rows="st.sources" /></Panel>
    <Panel title="Appareils" sub="Répartition des visites">
      <div class="devices">
        <div class="dev-bar"><span v-for="dv in st.devices" :key="dv.label" :style="{ width: dv.w, background: dv.color }"></span></div>
        <div v-for="dv in st.devices" :key="dv.label + 'r'" class="dev-row">
          <span class="row center gap10"><span class="dev-sw" :style="{ background: dv.color }"></span><span aria-hidden="true" class="ms mu">{{ dv.icon }}</span>{{ dv.label }}</span>
          <span class="mono13"><strong>{{ dv.pct }}</strong> <span class="mu">· {{ dv.value }}</span></span>
        </div>
      </div>
    </Panel>
  </div>

  <div class="grid g360">
    <Panel title="Messages récents" :sub="newCount ? newCount + ' non lu(s)' : 'Tout est lu'">
      <template #action><button type="button" class="abtn xs" @click="go(router, 'messages')">Boîte de réception</button></template>
      <div class="list-pad">
        <p v-if="!msgs.length" class="empty-txt">Aucun message pour le moment.</p>
        <button v-for="m in msgs.slice(0, 5)" :key="m.id" type="button" class="msg-row" @click="openMsg(m)">
          <span class="msg-ico ms">{{ m.type === 'service' ? 'design_services' : 'mail' }}</span>
          <span class="msg-txt"><span class="msg-l1"><strong>{{ m.name }}</strong><span>{{ fmtMD(m.date) }}</span></span><span class="msg-sub">{{ t(m.subject) || '(sans sujet)' }}</span></span>
          <span v-if="m.status === 'new'" class="new-tag">Nouveau</span>
        </button>
      </div>
    </Panel>
    <Panel title="Contenus à compléter" :sub="health.label">
      <div class="health-track"><div :style="{ width: health.w }"></div></div>
      <div class="list-pad">
        <div v-for="hi in health.items" :key="hi.label" class="health-row">
          <span aria-hidden="true" class="ms" :style="{ color: hi.done ? '#1E8A5A' : '#D97706' }">{{ hi.done ? 'check_circle' : 'error' }}</span>
          <span class="health-txt"><span :class="{ done: hi.done }">{{ hi.label }}</span><span v-if="hi.detail" class="sm-mu">{{ hi.detail }}</span></span>
          <button v-if="!hi.done" type="button" class="abtn xxs" @click="go(router, hi.go)">Compléter</button>
        </div>
      </div>
    </Panel>
  </div>

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
