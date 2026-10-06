<script setup>
import { computed, ref } from 'vue';
import { COLS, ask, createItem, finalize, flash, flashError, save, state, syncItem } from '../store';
import Fields from './Fields.vue';

const ec = computed(() => COLS[state.edit.col]);
const title = computed(() => ec.value.title(state.edit.draft) || '(sans titre)');
const setD = (k, v) => { state.edit.draft = { ...state.edit.draft, [k]: v }; state.edit.dirty = true; };

const close = () => {
  if (state.edit && state.edit.dirty) ask('Quitter sans enregistrer ?', 'Vos modifications seront perdues.', () => { state.edit = null; }, 'Quitter');
  else state.edit = null;
};
const backdrop = (e) => { if (e.target === e.currentTarget) close(); };

// Le tiroir ne se ferme qu'une fois l'enregistrement accepté : en cas d'erreur (slug pris…), le brouillon reste ouvert.
const saving = ref(false);
async function submit() {
  if (saving.value) return;
  const e = state.edit, item = finalize(e.draft), msg = e.isNew ? 'Élément ajouté' : 'Modifications enregistrées';
  saving.value = true;
  try {
    if (e.isNew) {
      const created = await createItem(e.col, item, 0, msg);
      await save((d) => { d[e.col].unshift(created); }, msg);
    } else {
      await syncItem(e.col, item, msg);
      await save((d) => { const i = d[e.col].findIndex((x) => x.id === item.id); if (i >= 0) d[e.col][i] = item; }, msg);
    }
    state.edit = null;
  } catch (er) {
    if (er.response && er.response.status === 401) state.user = null;
    else flashError(er);
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="overlay right z70" @click="backdrop">
    <div role="dialog" aria-modal="true" aria-labelledby="drawer-title" class="drawer" @keydown.esc="close">
      <div class="drawer-head">
        <div class="col"><span class="drawer-kind">{{ (state.edit.isNew ? 'Nouveau · ' : 'Modifier · ') + ec.label }}</span><h2 id="drawer-title">{{ title }}</h2></div>
        <button type="button" class="ibtn lg" aria-label="Fermer" @click="close"><span class="ms">close</span></button>
      </div>
      <div class="drawer-body">
        <Fields :defs="ec.fields" :obj="state.edit.draft" @set="setD" />
      </div>
      <div class="drawer-foot">
        <button type="button" class="abtn" @click="close">Annuler</button>
        <button type="button" class="abtn primary" :disabled="saving" @click="submit"><span class="ms">check</span>Enregistrer</button>
      </div>
    </div>
  </div>
</template>
