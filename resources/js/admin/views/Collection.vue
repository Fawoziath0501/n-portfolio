<script setup>
import { computed } from 'vue';
import { api } from '../../shared/util';
import { COLS, TRASH_HINT, ask, createItem, missingEn, save, state, syncItem, syncOrder, trash } from '../store';

const v = state.view;
const col = COLS[v];
const arr = computed(() => state.data[v] || []);
const rows = computed(() => {
  const q = state.q.trim().toLowerCase();
  return arr.value.map((x, i) => ({ x, i })).filter(({ x }) => !q || (col.title(x) + ' ' + col.meta(x)).toLowerCase().includes(q));
});
const count = computed(() => arr.value.length + ' élément(s) · ' + arr.value.filter((x) => x.published).length + ' publié(s)');
const preview = (x) => '/fr/' + (v === 'projects' ? 'projets/' + x.slug : v === 'posts' ? 'blog/' + x.slug : v === 'legalPages' ? x.slugFr : 'services');

const move = (i, dir) => {
  const j = i + dir;
  if (j < 0 || j >= arr.value.length) return;
  save((d) => { const a = d[v]; [a[i], a[j]] = [a[j], a[i]]; }, null, () => syncOrder(v));
};
const update = (x, patch, msg) => save(() => { Object.assign(x, patch); }, msg, () => syncItem(v, x, msg));
const dup = (x, i) => {
  const c = JSON.parse(JSON.stringify(x));
  c.published = false;
  ['slug', 'slugFr', 'slugEn'].forEach((k) => { if (c[k]) c[k] += '-copie'; });
  save(() => {}, 'Élément dupliqué', async () => { const created = await createItem(v, c, i + 1, 'Élément dupliqué'); state.data[v].splice(i + 1, 0, created); });
};
const del = (x) => ask('Placer « ' + (col.title(x) || 'cet élément') + ' » dans la corbeille ?', TRASH_HINT, () =>
  save((d) => { d[v] = d[v].filter((y) => y.id !== x.id); }, 'Placé dans la corbeille', () => trash(api.delete(`/admin/${v}/${x.id}`, { data: { activity: 'Placé dans la corbeille : ' + col.title(x) } }))), 'Mettre à la corbeille');
const edit = (x) => { state.edit = { col: v, isNew: false, draft: JSON.parse(JSON.stringify(x)) }; };
</script>

<template>
  <div class="row-wrap center gap10">
    <label class="search"><span aria-hidden="true" class="ms">search</span><input v-model="state.q" placeholder="Rechercher…" aria-label="Rechercher" class="ain"></label>
    <span class="sm-mu fs13">{{ count }}</span>
  </div>
  <div class="panel">
    <p v-if="!rows.length" class="empty-txt big">Aucun élément.</p>
    <div v-for="{ x, i } in rows" :key="x.id" class="crow">
      <span class="crow-num">{{ String(i + 1).padStart(2, '0') }}</span>
      <span class="crow-main">
        <span class="crow-title">
          <span class="ellipsis">{{ col.title(x) || '(sans titre)' }}</span>
          <span v-if="col.featured && x.featured" title="Mis en avant" class="ms star">star</span>
          <span v-if="missingEn(x) > 0" class="warn-tag">EN manquant</span>
        </span>
        <span class="crow-meta">{{ col.meta(x) }}</span>
      </span>
      <span class="st-tag lg" :style="{ background: x.published ? '#E7F6EE' : 'var(--ln2)', color: x.published ? '#1E6B45' : 'var(--mu)' }">{{ x.published ? 'Publié' : 'Brouillon' }}</span>
      <span class="row gap6 wrap">
        <button type="button" class="ibtn" aria-label="Monter" title="Monter" @click="move(i, -1)"><span class="ms">arrow_upward</span></button>
        <button type="button" class="ibtn" aria-label="Descendre" title="Descendre" @click="move(i, 1)"><span class="ms">arrow_downward</span></button>
        <button v-if="col.featured" type="button" class="ibtn" aria-label="Mettre en avant" title="Mettre en avant" @click="update(x, { featured: !x.featured }, x.featured ? 'Retiré de l’accueil' : 'Mis en avant')"><span class="ms">star</span></button>
        <button type="button" class="ibtn" :aria-label="x.published ? 'Dépublier' : 'Publier'" :title="x.published ? 'Dépublier' : 'Publier'" @click="update(x, { published: !x.published }, x.published ? 'Passé en brouillon' : 'Publié')"><span class="ms">{{ x.published ? 'visibility' : 'visibility_off' }}</span></button>
        <button type="button" class="ibtn" aria-label="Dupliquer" title="Dupliquer" @click="dup(x, i)"><span class="ms">content_copy</span></button>
        <button type="button" class="ibtn red" aria-label="Mettre à la corbeille" title="Mettre à la corbeille" @click="del(x)"><span class="ms">delete</span></button>
        <a v-if="['projects', 'posts', 'services', 'legalPages'].includes(v) && x.published" :href="preview(x)" target="_blank" rel="noopener" class="ibtn" aria-label="Voir sur le site" title="Voir sur le site"><span class="ms">open_in_new</span></a>
        <button type="button" class="abtn xs" @click="edit(x)"><span class="ms">edit</span>Modifier</button>
      </span>
    </div>
  </div>
</template>
