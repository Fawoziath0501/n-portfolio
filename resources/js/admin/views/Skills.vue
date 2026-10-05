<script setup>
import { api } from '../../shared/util';
import { ask, createItem, save, state, syncItem, syncItemLater } from '../store';

const groups = () => state.data.skillGroups;
const name = (k) => (typeof k.name === 'object' ? (k.name.fr || '') : (k.name || ''));
const setLabel = (g, lang, v) => save(() => { g.label = { ...g.label, [lang]: v }; }, null, () => syncItemLater('skillGroups', g));
const setSkill = (g, k, v) => save(() => { k.name = typeof k.name === 'object' ? { ...k.name, fr: v } : v; }, null, () => syncItemLater('skillGroups', g));
const toggle = (g) => save(() => { g.visible = !g.visible; }, null, () => syncItem('skillGroups', g));
const addSkill = (g) => save(() => { g.skills.push({ name: 'Nouvelle' }); }, null, () => syncItem('skillGroups', g));
const delSkill = (g, ki) => save(() => { g.skills.splice(ki, 1); }, null, () => syncItem('skillGroups', g));
const delGroup = (g) => ask('Supprimer ce groupe ?', 'Ses compétences seront aussi supprimées.', () =>
  save((d) => { d.skillGroups = d.skillGroups.filter((x) => x.id !== g.id); }, 'Groupe supprimé', () => api.delete('/admin/skillGroups/' + g.id, { data: { activity: 'Groupe supprimé' } })));
const addGroup = () => save(() => {}, 'Groupe ajouté', async () => {
  const created = await createItem('skillGroups', { label: { fr: 'Nouveau groupe', en: 'New group' }, visible: true, skills: [] }, groups().length, 'Groupe ajouté');
  state.data.skillGroups.push(created);
});
</script>

<template>
  <div v-for="g in groups()" :key="g.id" class="panel">
    <div class="skg">
      <div class="row-wrap center gap10">
        <input :value="g.label.fr" aria-label="Nom FR" class="ain grow fw7" @input="setLabel(g, 'fr', $event.target.value)">
        <input :value="g.label.en" aria-label="Nom EN" class="ain grow" @input="setLabel(g, 'en', $event.target.value)">
        <button type="button" class="abtn h44" @click="toggle(g)"><span class="ms">{{ g.visible ? 'visibility' : 'visibility_off' }}</span>{{ g.visible ? 'Visible' : 'Masqué' }}</button>
        <button type="button" class="abtn h44" @click="addSkill(g)"><span class="ms">add</span>Compétence</button>
        <button type="button" class="ibtn red h44" aria-label="Supprimer le groupe" @click="delGroup(g)"><span class="ms">delete</span></button>
      </div>
      <div class="row-wrap gap8">
        <span v-for="(k, ki) in g.skills" :key="ki" class="skill-chip">
          <input :value="name(k)" aria-label="Compétence" :style="{ width: Math.max(60, name(k).length * 8.6 + 8) + 'px' }" @input="setSkill(g, k, $event.target.value)">
          <button type="button" aria-label="Retirer" class="ms" @click="delSkill(g, ki)">close</button>
        </span>
      </div>
    </div>
  </div>
  <button type="button" class="abtn self-start" @click="addGroup"><span class="ms">add</span>Ajouter un groupe</button>
</template>
