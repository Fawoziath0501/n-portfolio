<script setup>
import { computed } from 'vue';
import { save, setPath, state, syncDoc } from '../store';
import Panel from '../components/Panel.vue';
import Fields from '../components/Fields.vue';

const ctaDefs = [{ k: 'ctaPrimary', label: 'Bouton principal du hero', type: 'i18n' }, { k: 'ctaSecondary', label: 'Bouton secondaire du hero', type: 'i18n' }];
const setCta = (k, v) => save((d) => { setPath(d.home, k, v); }, null, () => syncDoc('home'));

const LABELS = { about: ['À propos', 'person'], projects: ['Projets sélectionnés', 'grid_view'], experience: ['Expérience (page À propos)', 'work_history'], skills: ['Environnement technique', 'bolt'], services: ['Services', 'design_services'], education: ['Formation (page À propos)', 'school'], testimonials: ['Témoignages', 'format_quote'], blog: ['Blog & actualités', 'article'], contact: ['Contact', 'mail'] };
const secs = computed(() => state.data.home.sections);
const toggle = (x) => save(() => { x.enabled = !x.enabled; }, x.enabled ? 'Section masquée' : 'Section affichée', () => syncDoc('home'));
const move = (i, dir) => {
  const j = i + dir, a = secs.value;
  if (j < 0 || j >= a.length) return;
  save(() => { [a[i], a[j]] = [a[j], a[i]]; }, null, () => syncDoc('home'));
};
</script>

<template>
  <Panel title="Sections de la page d’accueil" sub="Activez, masquez et réordonnez les sections">
    <div class="home-rows">
      <div v-for="(x, i) in secs" :key="x.id" class="home-row">
        <span class="mono fs12 mu w24">{{ String(i + 1).padStart(2, '0') }}</span>
        <span aria-hidden="true" class="ms acc">{{ (LABELS[x.type] || [0, 'widgets'])[1] }}</span>
        <span class="f1 fw6">{{ (LABELS[x.type] || [x.type])[0] }}</span>
        <button type="button" class="ibtn" aria-label="Monter" @click="move(i, -1)"><span class="ms">arrow_upward</span></button>
        <button type="button" class="ibtn" aria-label="Descendre" @click="move(i, 1)"><span class="ms">arrow_downward</span></button>
        <button type="button" role="switch" :aria-checked="x.enabled" aria-label="Afficher" class="switch" :class="{ on: x.enabled }" @click="toggle(x)"><span></span></button>
      </div>
    </div>
  </Panel>
  <Panel title="Boutons du hero" sub="Textes des deux boutons sous la présentation">
    <div class="pad-form"><Fields :defs="ctaDefs" :obj="state.data.home" @set="setCta" /></div>
  </Panel>
</template>
