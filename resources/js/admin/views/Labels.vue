<script setup>
// Textes de l'interface publique (table ui_labels), regroupés par page, avec enregistrement automatique.
import { computed, ref } from 'vue';
import { api } from '../../shared/util';
import { flash, flashError, state } from '../store';
import Panel from '../components/Panel.vue';

const group = ref('all');
const onlyMissing = ref(false);

const all = computed(() => state.data.labels || []);
const groups = computed(() => {
  const g = {};
  all.value.forEach((l) => { g[l.group] = (g[l.group] || 0) + 1; });
  return Object.entries(g).map(([label, count]) => ({ label, count }));
});
const missingCount = computed(() => all.value.filter((l) => l.fr && !l.en).length);
const shown = computed(() => {
  const q = state.q.trim().toLowerCase();
  return all.value.filter((l) => (group.value === 'all' || l.group === group.value)
    && (!onlyMissing.value || (l.fr && !l.en))
    && (!q || (l.key + ' ' + l.fr + ' ' + l.en).toLowerCase().includes(q)));
});
const byGroup = computed(() => {
  const out = {};
  shown.value.forEach((l) => { (out[l.group] = out[l.group] || []).push(l); });
  return Object.entries(out);
});

// Enregistrement groupé, 600 ms après la dernière frappe.
const dirty = new Map();
let timer = null;
function set(l, lang, v) {
  l[lang] = v;
  dirty.set(l.id, l);
  clearTimeout(timer);
  timer = setTimeout(flush, 600);
}
async function flush() {
  const items = [...dirty.values()].map(({ id, fr, en }) => ({ id, fr, en }));
  dirty.clear();
  if (!items.length) return;
  try {
    await api.put('/admin/labels', { items, activity: items.length === 1 ? 'Texte modifié : ' + items[0].fr.slice(0, 40) : items.length + ' textes modifiés' });
  } catch (e) { flashError(e); }
}
const hasVars = (s) => /\{\w+\}/.test(s || '');
const multiline = (s) => (s || '').length > 70;
</script>

<template>
  <div class="banner">
    <span aria-hidden="true" class="ms">translate</span>
    <span class="f-grow">Tous les textes affichés sur le site public, en français et en anglais. Les mots entre accolades comme <code>{name}</code> sont remplacés automatiquement : gardez-les tels quels.</span>
  </div>

  <div class="row-wrap center gap10">
    <label class="search"><span aria-hidden="true" class="ms">search</span><input v-model="state.q" placeholder="Rechercher un texte…" aria-label="Rechercher" class="ain"></label>
    <button type="button" class="mfilter" :class="{ on: onlyMissing }" :aria-pressed="onlyMissing" @click="onlyMissing = !onlyMissing">EN manquant <span class="mono op75">{{ missingCount }}</span></button>
    <span class="sm-mu fs13">{{ shown.length }} texte(s)</span>
  </div>
  <div class="row-wrap gap8">
    <button type="button" class="mfilter" :class="{ on: group === 'all' }" :aria-pressed="group === 'all'" @click="group = 'all'">Toutes les pages <span class="mono op75">{{ all.length }}</span></button>
    <button v-for="g in groups" :key="g.label" type="button" class="mfilter" :class="{ on: group === g.label }" :aria-pressed="group === g.label" @click="group = g.label">{{ g.label }} <span class="mono op75">{{ g.count }}</span></button>
  </div>

  <p v-if="!byGroup.length" class="empty-txt big">Aucun texte ne correspond.</p>
  <Panel v-for="[g, rows] in byGroup" :key="g" :title="g" :sub="rows.length + ' texte(s)'">
    <div class="lbl-list">
      <div v-for="l in rows" :key="l.id" class="lbl-row">
        <span class="lbl-key" :title="l.key">{{ l.key }}<span v-if="hasVars(l.fr)" class="lbl-var">variables</span></span>
        <label class="lang-in">
          <span>FR</span>
          <textarea v-if="multiline(l.fr)" rows="2" class="ain area" :value="l.fr" @input="set(l, 'fr', $event.target.value)"></textarea>
          <input v-else class="ain" :value="l.fr" @input="set(l, 'fr', $event.target.value)">
        </label>
        <label class="lang-in">
          <span>EN</span>
          <textarea v-if="multiline(l.en || l.fr)" rows="2" class="ain area" :class="{ miss: l.fr && !l.en }" :value="l.en" @input="set(l, 'en', $event.target.value)"></textarea>
          <input v-else class="ain" :class="{ miss: l.fr && !l.en }" :value="l.en" @input="set(l, 'en', $event.target.value)">
        </label>
      </div>
    </div>
  </Panel>
</template>
