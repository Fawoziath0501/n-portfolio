<script setup>
// Éléments liés au profil : langues (table languages) et méthode de travail (profile_values + value_keywords).
import { computed } from 'vue';
import { ask, save, state, syncDoc } from '../store';
import Panel from './Panel.vue';

const p = computed(() => state.data.profile);
const sync = (msg) => () => syncDoc('profile', msg);
const T = () => ({ fr: '', en: '' });

const setLang = (l, k, lang, v) => save(() => { if (lang) l[k] = { ...(l[k] || T()), [lang]: v }; else l[k] = v; }, null, sync());
const addLang = () => save(() => { p.value.languages.push({ name: T(), level: T(), cefr: null, featured: false }); }, 'Langue ajoutée', sync('Langue ajoutée'));
const delLang = (i) => ask('Retirer cette langue ?', 'Elle disparaîtra du site.', () => save(() => { p.value.languages.splice(i, 1); }, 'Langue retirée', sync('Langue retirée')), 'Retirer');
const moveLang = (i, dir) => { const a = p.value.languages, j = i + dir; if (j < 0 || j >= a.length) return; save(() => { [a[i], a[j]] = [a[j], a[i]]; }, null, sync()); };

const setVal = (v, k, lang, val) => save(() => { if (lang) v[k] = { ...(v[k] || T()), [lang]: val }; else v[k] = val; }, null, sync());
const keysText = (v, lang) => (v.keys || []).map((k) => k[lang] || '').join(', ');
const setKeys = (v, lang, text) => save(() => {
  const parts = text.split(',').map((x) => x.trim());
  const n = Math.max(parts.length, (v.keys || []).length);
  v.keys = Array.from({ length: n }, (_, i) => ({ ...((v.keys || [])[i] || T()), [lang]: parts[i] || '' })).filter((k) => k.fr || k.en);
}, null, sync());
</script>

<template>
  <Panel title="Langues" sub="« Mise en avant » : affichée dans les résumés (hero, À propos)">
    <template #action><button type="button" class="abtn xs" @click="addLang"><span class="ms">add</span>Langue</button></template>
    <div class="rel-list">
      <div v-for="(l, i) in p.languages" :key="l.id || 'n' + i" class="rel-row">
        <input :value="l.name.fr" placeholder="Langue (FR)" aria-label="Langue FR" class="ain" @input="setLang(l, 'name', 'fr', $event.target.value)">
        <input :value="l.name.en" placeholder="Language (EN)" aria-label="Langue EN" class="ain" @input="setLang(l, 'name', 'en', $event.target.value)">
        <input :value="(l.level || {}).fr" placeholder="Niveau (FR)" aria-label="Niveau FR" class="ain" @input="setLang(l, 'level', 'fr', $event.target.value)">
        <input :value="(l.level || {}).en" placeholder="Level (EN)" aria-label="Niveau EN" class="ain" @input="setLang(l, 'level', 'en', $event.target.value)">
        <input :value="l.cefr || ''" placeholder="CECRL" aria-label="Niveau CECRL" class="ain w80" maxlength="4" @input="setLang(l, 'cefr', null, $event.target.value || null)">
        <button type="button" role="switch" :aria-checked="l.featured" class="switch-btn" @click="setLang(l, 'featured', null, !l.featured)"><span class="switch" :class="{ on: l.featured }"><span></span></span>Mise en avant</button>
        <span class="row gap6">
          <button type="button" class="ibtn" aria-label="Monter" @click="moveLang(i, -1)"><span class="ms">arrow_upward</span></button>
          <button type="button" class="ibtn" aria-label="Descendre" @click="moveLang(i, 1)"><span class="ms">arrow_downward</span></button>
          <button type="button" class="ibtn red" aria-label="Retirer" @click="delLang(i)"><span class="ms">delete</span></button>
        </span>
      </div>
    </div>
  </Panel>

  <Panel title="Ma méthode" sub="Cartes « Ma méthode en 3 temps » de la page À propos">
    <div class="rel-list">
      <div v-for="(v, i) in p.values" :key="v.id || 'v' + i" class="val-card">
        <div class="row-wrap center gap8">
          <span class="mono fs12 mu w24">{{ String(i + 1).padStart(2, '0') }}</span>
          <span class="ms acc">{{ v.icon }}</span>
          <input :value="v.icon" aria-label="Icône" placeholder="icône" class="ain w140" @input="setVal(v, 'icon', null, $event.target.value)">
          <input :value="v.title.fr" aria-label="Titre FR" placeholder="Titre (FR)" class="ain grow" @input="setVal(v, 'title', 'fr', $event.target.value)">
          <input :value="v.title.en" aria-label="Titre EN" placeholder="Title (EN)" class="ain grow" @input="setVal(v, 'title', 'en', $event.target.value)">
        </div>
        <div class="area-pair">
          <textarea rows="2" class="ain area" :value="(v.text || {}).fr" aria-label="Texte FR" @input="setVal(v, 'text', 'fr', $event.target.value)"></textarea>
          <textarea rows="2" class="ain area" :value="(v.text || {}).en" aria-label="Texte EN" @input="setVal(v, 'text', 'en', $event.target.value)"></textarea>
          <input :value="keysText(v, 'fr')" aria-label="Mots-clés FR" placeholder="Mots-clés FR, séparés par des virgules" class="ain" @change="setKeys(v, 'fr', $event.target.value)">
          <input :value="keysText(v, 'en')" aria-label="Mots-clés EN" placeholder="Keywords EN, comma-separated" class="ain" @change="setKeys(v, 'en', $event.target.value)">
        </div>
      </div>
    </div>
  </Panel>
</template>
