<script setup>
import { computed } from 'vue';
import { barList, delta, nf, spark, state, stats, t } from '../store';
import Panel from '../components/Panel.vue';
import KpiGrid from '../components/KpiGrid.vue';
import BarList from '../components/BarList.vue';
import AudienceChart from '../components/AudienceChart.vue';
import DataBanner from '../components/DataBanner.vue';

const st = computed(() => { state.events; return stats(state.period); });
const topPosts = computed(() => {
  const rows = (state.data.posts || []).filter((x) => x.published).map((x) => ({ label: t(x.title), icon: 'article', n: x.views || 0 })).sort((a, b) => b.n - a.n).slice(0, 6);
  return barList(rows, rows.reduce((s, r) => s + r.n, 0));
});
const kpis = computed(() => {
  const s = st.value, ppv = s.V ? s.PV / s.V : 0, ppv0 = s.V0 ? s.PV0 / s.V0 : 0;
  const cr = s.V ? (s.convTotal + s.mC + s.sC) / s.V * 100 : 0, cr0 = s.V0 ? (s.convTotal0 + s.mC0 + s.sC0) / s.V0 * 100 : 0;
  return [
    { label: 'Visiteurs', icon: 'group', value: nf(s.V), spark: spark(s.vb), ...delta(s.V, s.V0, true) },
    { label: 'Pages vues', icon: 'visibility', value: nf(s.PV), spark: spark(s.pb), ...delta(s.PV, s.PV0, true) },
    { label: 'Pages par visite', icon: 'layers', value: ppv.toFixed(1).replace('.', ','), spark: spark(s.pb.map((x, i) => x / Math.max(1, s.vb[i]))), ...delta(ppv, ppv0, true) },
    { label: 'Taux de contact', icon: 'ads_click', value: cr.toFixed(1).replace('.', ',') + ' %', ...delta(cr, cr0, true) },
  ];
});
</script>

<template>
  <DataBanner />
  <KpiGrid :kpis="kpis" />

  <Panel title="Évolution de l’audience" :sub="st.rangeLabel"><div class="pad-chart"><AudienceChart :chart="st.chart" /></div></Panel>

  <div class="grid g420">
    <Panel title="Pages les plus vues" sub="Pages vues sur la période">
      <table class="tbl">
        <thead><tr><th>Page</th><th class="r">Vues</th><th class="r">Part</th></tr></thead>
        <tbody>
          <tr v-for="tp in st.topPages" :key="tp.path"><td><span class="col"><span class="fw5">{{ tp.label }}</span><span class="path">{{ tp.path }}</span></span></td><td class="r mono">{{ tp.value }}</td><td class="r mono mu">{{ tp.pct }}</td></tr>
        </tbody>
      </table>
    </Panel>
    <Panel title="Projets les plus consultés" sub="Pages d’étude de cas"><BarList :rows="st.topProjects" /></Panel>
    <Panel title="Articles les plus lus" sub="Lectures de la page de chaque article, depuis sa publication"><BarList :rows="topPosts" /></Panel>
  </div>

  <div class="grid g320">
    <Panel title="Provenance" sub="Canaux d’acquisition"><BarList :rows="st.sources" /></Panel>
    <Panel title="Pays" sub="Top pays"><BarList :rows="st.countries" /></Panel>
    <Panel title="Villes" sub="Top villes"><BarList :rows="st.cities" /></Panel>
  </div>

  <div class="grid g420">
    <Panel title="Mots-clés Google" sub="Search Console · requêtes ayant mené au site">
      <div class="scroll-x">
        <table class="tbl wide">
          <thead><tr><th>Requête</th><th class="r">Clics</th><th class="r">Impr.</th><th class="r">CTR</th><th class="r">Position</th></tr></thead>
          <tbody>
            <tr v-if="!st.keywords.length"><td colspan="5" class="mu">Disponible après connexion de Google Search Console.</td></tr>
            <tr v-for="kw in st.keywords" :key="kw.q"><td class="fw5">{{ kw.q }}</td><td class="r mono">{{ kw.clicks }}</td><td class="r mono mu">{{ kw.impr }}</td><td class="r mono mu">{{ kw.ctr }}</td><td class="r"><span class="pos" :style="{ background: kw.posBg, color: kw.posFg }">{{ kw.pos }}</span></td></tr>
          </tbody>
        </table>
      </div>
    </Panel>
    <Panel title="Appareils" sub="Mobile, ordinateur, tablette">
      <div class="devices">
        <div class="dev-bar"><span v-for="dv in st.devices" :key="dv.label" :style="{ width: dv.w, background: dv.color }"></span></div>
        <div v-for="dv in st.devices" :key="dv.label + 'r'" class="dev-row">
          <span class="row center gap10"><span class="dev-sw" :style="{ background: dv.color }"></span>{{ dv.label }}</span>
          <span class="mono13"><strong>{{ dv.pct }}</strong> <span class="mu">· {{ dv.value }}</span></span>
        </div>
        <div class="conv-list">
          <span class="fw6 fs13">Clics de contact</span>
          <div v-for="cv in st.conv" :key="cv.label" class="dev-row"><span class="row center gap8"><span aria-hidden="true" class="ms acc">{{ cv.icon }}</span>{{ cv.label }}</span><strong class="mono">{{ cv.value }}</strong></div>
        </div>
      </div>
    </Panel>
  </div>
</template>
