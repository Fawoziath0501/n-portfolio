<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { barList, delta, go, nf, spark, state, stats, t } from '../store';
import Panel from '../components/Panel.vue';
import KpiGrid from '../components/KpiGrid.vue';
import BarList from '../components/BarList.vue';
import AudienceChart from '../components/AudienceChart.vue';
import DataBanner from '../components/DataBanner.vue';

const router = useRouter();
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

  <!-- Suivi désactivé : aucune donnée inventée, seulement l'explication et le moyen de l'activer. -->
  <div v-if="!st.tracking" class="panel empty-state">
    <span class="ms big acc" aria-hidden="true">query_stats</span>
    <h2>Le suivi des visites est désactivé</h2>
    <p class="mu14">Activez le suivi intégré dans Paramètres : il compte les visites, les pages vues, la provenance et les clics de contact, sans cookie ni adresse IP.</p>
    <button type="button" class="abtn primary" @click="go(router, 'settings')"><span class="ms">settings</span>Ouvrir les paramètres</button>
  </div>

  <template v-else>
    <KpiGrid :kpis="kpis" />

    <Panel title="Évolution de l’audience" :sub="st.rangeLabel"><div class="pad-chart"><AudienceChart :chart="st.chart" /></div></Panel>

    <div class="grid g420">
      <Panel title="Pages les plus vues" sub="Pages vues sur la période">
        <p v-if="!st.topPages.length" class="empty-txt">Aucune visite enregistrée sur la période.</p>
        <table v-else class="tbl">
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
      <Panel title="Provenance" sub="Site d’où arrivent les visiteurs"><BarList :rows="st.sources" /></Panel>
      <Panel title="Appareils" sub="Mobile, ordinateur, tablette">
        <div class="devices">
          <div class="dev-bar"><span v-for="dv in st.devices" :key="dv.label" :style="{ width: dv.w, background: dv.color }"></span></div>
          <div v-for="dv in st.devices" :key="dv.label + 'r'" class="dev-row">
            <span class="row center gap10"><span class="dev-sw" :style="{ background: dv.color }"></span>{{ dv.label }}</span>
            <span class="mono13"><strong>{{ dv.pct }}</strong> <span class="mu">· {{ dv.value }}</span></span>
          </div>
        </div>
      </Panel>
      <Panel title="Clics de contact" sub="Ce que font les visiteurs intéressés">
        <div class="devices">
          <div v-for="cv in st.conv" :key="cv.label" class="dev-row"><span class="row center gap8"><span aria-hidden="true" class="ms acc">{{ cv.icon }}</span>{{ cv.label }}</span><span class="mono13"><strong>{{ cv.value }}</strong> <span class="mu">· {{ cv.note }}</span></span></div>
        </div>
      </Panel>
    </div>

    <Panel title="Recherches Google" sub="Mots-clés, positions et clics depuis Google">
      <div class="pad-form col gap10">
        <p class="mu14">Ces données sont fournies par Google Search Console, gratuitement : déclarez le site, collez le code de vérification dans « SEO &amp; partage », puis envoyez le plan du site (/sitemap.xml). Les premières données apparaissent après quelques jours.</p>
        <a href="https://search.google.com/search-console" target="_blank" rel="noopener" class="abtn self-start"><span class="ms">open_in_new</span>Ouvrir Google Search Console</a>
      </div>
    </Panel>
  </template>
</template>
