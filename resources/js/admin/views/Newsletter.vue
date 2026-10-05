<script setup>
import { computed } from 'vue';
import { api } from '../../shared/util';
import { ask, fmtMD, iso, save, state } from '../store';

const subs = computed(() => (state.data.subscribers || []).slice().sort((a, b) => String(b.date).localeCompare(String(a.date))));
const kpis = computed(() => {
  const d30 = iso(new Date(Date.now() - 30 * 864e5));
  return [{ label: 'Inscrits au total', value: subs.value.length }, { label: 'Nouveaux (30 j)', value: subs.value.filter((x) => x.date >= d30).length }, { label: 'En anglais', value: subs.value.filter((x) => x.lang === 'en').length }];
});
const del = (x) => ask('Retirer cet inscrit ?', x.email, () => save((d) => { d.subscribers = d.subscribers.filter((y) => y.id !== x.id); }, 'Inscrit retiré', () => api.delete('/admin/subscribers/' + x.id)));
</script>

<template>
  <div class="kpis">
    <div v-for="k in kpis" :key="k.label" class="kpi simple"><span class="sm-mu">{{ k.label }}</span><span class="kpi-val">{{ k.value }}</span></div>
  </div>
  <div class="panel">
    <div v-if="!subs.length" class="empty-box">
      <span aria-hidden="true" class="ms acc big">mark_email_unread</span>
      <p class="fw6">Aucun inscrit pour le moment</p>
      <p class="mu14">Les inscriptions du formulaire du footer apparaîtront ici.</p>
    </div>
    <div v-else class="scroll-x">
      <table class="tbl wide">
        <thead><tr><th>E-mail</th><th>Langue</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <tr v-for="x in subs" :key="x.id"><td class="fw5">{{ x.email }}</td><td class="mono fs12">{{ (x.lang || 'fr').toUpperCase() }}</td><td class="mu">{{ fmtMD(x.date) }}</td><td class="r"><button type="button" class="ibtn" aria-label="Supprimer" @click="del(x)"><span class="ms">delete</span></button></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
