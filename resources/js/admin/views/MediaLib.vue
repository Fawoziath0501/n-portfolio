<script setup>
import { computed } from 'vue';
import { api } from '../../shared/util';
import { ask, errorText, flash, save, state, syncDoc } from '../store';

const media = computed(() => state.data.media || []);
const used = (m) => JSON.stringify({ p: state.data.profile, pr: state.data.projects, s: state.data.seo }).includes(m.src);
const info = computed(() => {
  const bytes = media.value.reduce((s, m) => s + (m.size || 0), 0);
  return media.value.length + ' fichier(s) · ' + (Math.round(bytes / 1024 / 10.24) / 100).toLocaleString('fr-FR') + ' Mo';
});
const metaOf = (m) => [m.kind === 'doc' ? 'PDF' : (m.w ? m.w + '×' + m.h : 'Image'), Math.round((m.size || 0) / 1024) + ' Ko', used(m) ? 'utilisé' : ''].filter(Boolean).join(' · ');

async function upload(e) {
  const files = Array.from(e.target.files || []);
  e.target.value = '';
  if (!files.length) return;
  const fd = new FormData();
  files.forEach((f) => fd.append('files[]', f));
  try {
    const { data } = await api.post('/admin/media', fd);
    state.data.media = [...data, ...media.value];
    flash(data.length + ' fichier(s) importé(s)');
  } catch (er) { flash(errorText(er)); }
}
async function addUrl() {
  const u = state.mUrl.trim();
  if (!/^https?:\/\//.test(u)) return flash('Adresse invalide');
  try {
    const { data } = await api.post('/admin/media/url', { url: u });
    state.data.media = [data, ...media.value];
    state.mUrl = '';
    flash('Fichier ajouté');
  } catch (er) { flash(errorText(er)); }
}
const copy = (m) => { try { navigator.clipboard.writeText(m.src); flash('Adresse copiée'); } catch (er) { flash(m.src); } };
const setProfile = (k, m, msg) => save((d) => { d.profile[k] = m.src; }, msg, () => syncDoc('profile', msg));
const del = (m) => ask('Supprimer « ' + m.name + ' » ?', used(m) ? 'Ce fichier est utilisé sur le site : il disparaîtra des pages concernées.' : 'Cette action est définitive.', () =>
  save((d) => { d.media = d.media.filter((x) => x.id !== m.id); }, 'Fichier supprimé', () => api.delete('/admin/media/' + m.id)));
</script>

<template>
  <div class="row-wrap center gap10">
    <label class="abtn primary rel"><span class="ms">upload</span>Importer des fichiers<input type="file" multiple accept="image/*,application/pdf" class="file-cover" @change="upload"></label>
    <form class="url-form" @submit.prevent="addUrl">
      <input v-model="state.mUrl" name="murl" placeholder="…ou coller l’adresse d’une image (https://)" aria-label="Adresse d’une image" class="ain">
      <button type="submit" class="abtn h44">Ajouter</button>
    </form>
    <span class="sm-mu fs13">{{ info }}</span>
  </div>

  <div v-if="!media.length" class="empty-box dashed">
    <span aria-hidden="true" class="ms big">perm_media</span>
    <p class="fw6">La médiathèque est vide</p>
    <p class="sm-mu">Importez votre portrait, votre CV et les captures de vos projets.</p>
  </div>

  <div class="media-grid">
    <div v-for="m in media" :key="m.id" class="media-card">
      <div class="media-img" :style="{ backgroundImage: m.kind === 'doc' ? 'none' : 'url(&quot;' + m.src + '&quot;)' }"><span v-if="m.kind === 'doc'" aria-hidden="true" class="ms big">picture_as_pdf</span></div>
      <div class="media-info"><span class="media-name">{{ m.name }}</span><span class="sm-mu">{{ metaOf(m) }}</span></div>
      <div class="media-actions">
        <button type="button" class="ibtn" aria-label="Copier l’adresse" title="Copier l’adresse" @click="copy(m)"><span class="ms">link</span></button>
        <button v-if="m.kind !== 'doc'" type="button" class="ibtn" aria-label="Utiliser comme portrait" title="Utiliser comme portrait" @click="setProfile('photo', m, 'Portrait mis à jour')"><span class="ms">account_circle</span></button>
        <button v-else type="button" class="ibtn" aria-label="Utiliser comme CV" title="Utiliser comme CV" @click="setProfile('cv', m, 'CV mis à jour')"><span class="ms">description</span></button>
        <span class="f1"></span>
        <button type="button" class="ibtn red" aria-label="Supprimer" title="Supprimer" @click="del(m)"><span class="ms">delete</span></button>
      </div>
    </div>
  </div>
</template>
