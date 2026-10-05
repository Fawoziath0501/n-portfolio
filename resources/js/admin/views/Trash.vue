<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../../shared/util';
import { ask, errorText, flash, reload, state } from '../store';

const items = ref(null);
const days = ref(30);
const type = ref('all');

async function load() {
  const { data } = await api.get('/admin/trash');
  items.value = data.items;
  days.value = data.days;
  state.data.trashCount = data.items.length;
}
onMounted(load);

const types = computed(() => {
  const all = items.value || [];
  const seen = {};
  all.forEach((x) => { seen[x.type] = seen[x.type] || { k: x.type, label: x.typeLabel, count: 0 }; seen[x.type].count++; });
  return [{ k: 'all', label: 'Tout', count: all.length }, ...Object.values(seen)];
});
const shown = computed(() => (items.value || []).filter((x) => type.value === 'all' || x.type === type.value));

const fmt = (s) => new Date(s).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
const left = (s) => { const d = Math.max(0, Math.ceil((new Date(s) - Date.now()) / 864e5)); return d <= 1 ? 'Supprimé définitivement demain' : 'Suppression définitive dans ' + d + ' jours'; };

async function restore(x) {
  try {
    await api.post(`/admin/trash/${x.type}/${x.id}/restore`);
    await Promise.all([load(), reload()]);
    flash(x.typeLabel + ' restauré');
  } catch (e) { flash(errorText(e)); }
}
const destroy = (x) => ask('Supprimer définitivement « ' + x.label + ' » ?', 'Cette action est irréversible' + (x.type === 'media' ? ' : le fichier sera effacé du serveur.' : '.'), async () => {
  try { await api.delete(`/admin/trash/${x.type}/${x.id}`); await load(); flash('Supprimé définitivement'); } catch (e) { flash(errorText(e)); }
});
const empty = () => ask('Vider la corbeille ?', (items.value || []).length + ' élément(s) seront supprimés définitivement. Cette action est irréversible.', async () => {
  try { await api.delete('/admin/trash'); await load(); flash('Corbeille vidée'); } catch (e) { flash(errorText(e)); }
}, 'Vider');
</script>

<template>
  <div class="banner">
    <span aria-hidden="true" class="ms">auto_delete</span>
    <span class="f-grow">Les éléments supprimés restent <strong>{{ days }} jours</strong> dans la corbeille, puis sont effacés automatiquement. Restaurer un élément le remet à sa place, avec ses éléments liés.</span>
    <button v-if="items && items.length" type="button" class="abtn sm red" @click="empty"><span class="ms">delete_forever</span>Vider la corbeille</button>
  </div>

  <div v-if="items && items.length" class="row-wrap gap8">
    <button v-for="f in types" :key="f.k" type="button" class="mfilter" :class="{ on: type === f.k }" :aria-pressed="type === f.k" @click="type = f.k">{{ f.label }} <span class="mono op75">{{ f.count }}</span></button>
  </div>

  <div class="panel">
    <p v-if="!items" class="empty-txt big">Chargement…</p>
    <div v-else-if="!items.length" class="empty-box">
      <span aria-hidden="true" class="ms acc big">delete</span>
      <p class="fw6">La corbeille est vide</p>
      <p class="mu14">Les projets, articles, messages ou fichiers supprimés apparaîtront ici.</p>
    </div>
    <div v-for="x in shown" :key="x.type + x.id" class="crow">
      <span class="trash-type">{{ x.typeLabel }}</span>
      <span class="crow-main">
        <span class="crow-title"><span class="ellipsis">{{ x.label || '(sans titre)' }}</span></span>
        <span class="crow-meta">{{ [x.meta, 'supprimé le ' + fmt(x.deletedAt)].filter(Boolean).join(' · ') }}</span>
      </span>
      <span class="trash-left">{{ left(x.purgeAt) }}</span>
      <span class="row gap6">
        <button type="button" class="abtn xs" @click="restore(x)"><span class="ms">restore_from_trash</span>Restaurer</button>
        <button type="button" class="ibtn red" aria-label="Supprimer définitivement" title="Supprimer définitivement" @click="destroy(x)"><span class="ms">delete_forever</span></button>
      </span>
    </div>
  </div>
</template>
