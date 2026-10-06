<script setup>
// Liste d'éléments FR / EN (contributions, fonctionnalités, missions) : une ligne par élément,
// ajout, modification, réordonnancement, suppression. Stockée comme avant : un élément par ligne dans { fr, en }.
import { nextTick, ref, watch } from 'vue';

const props = defineProps({ modelValue: { type: Object, default: () => ({ fr: '', en: '' }) }, label: String, addLabel: { type: String, default: 'Ajouter un élément' } });
const emit = defineEmits(['update:modelValue']);
const root = ref(null);

const split = (s) => String(s || '').split('\n').map((x) => x.trim());
const toRows = (v) => {
  const fr = split(v && v.fr), en = split(v && v.en), n = Math.max(fr.length, en.length);
  return Array.from({ length: n }, (_, i) => ({ fr: fr[i] || '', en: en[i] || '' })).filter((r) => r.fr || r.en);
};
const join = (rows) => ({ fr: rows.map((r) => r.fr.trim()).join('\n').replace(/\n+$/, ''), en: rows.map((r) => r.en.trim()).join('\n').replace(/\n+$/, '') });
const rows = ref(toRows(props.modelValue));

// Valeur changée de l'extérieur (autre élément ouvert…) : on recharge les lignes.
watch(() => props.modelValue, (v) => {
  const cur = join(rows.value), nv = join(toRows(v));
  if (cur.fr !== nv.fr || cur.en !== nv.en) rows.value = toRows(v);
});

const commit = () => emit('update:modelValue', join(rows.value));
const set = (i, lang, v) => { rows.value[i][lang] = v; commit(); };
async function add(at = rows.value.length) {
  rows.value.splice(at, 0, { fr: '', en: '' });
  await nextTick();
  const inputs = root.value && root.value.querySelectorAll('.le-row');
  if (inputs && inputs[at]) inputs[at].querySelector('input').focus();
}
const remove = (i) => { rows.value.splice(i, 1); commit(); };
const move = (i, dir) => {
  const j = i + dir;
  if (j < 0 || j >= rows.value.length) return;
  [rows.value[i], rows.value[j]] = [rows.value[j], rows.value[i]];
  commit();
};
</script>

<template>
  <div ref="root" class="le">
    <p v-if="!rows.length" class="le-empty">Aucun élément pour le moment.</p>
    <div v-for="(r, i) in rows" :key="i" class="le-row">
      <span class="le-num" aria-hidden="true">{{ String(i + 1).padStart(2, '0') }}</span>
      <div class="le-ins">
        <label class="lang-in le-in"><span>FR</span><input class="ain" :value="r.fr" :aria-label="label + ' ' + (i + 1) + ' (FR)'" @input="set(i, 'fr', $event.target.value)" @keydown.enter.prevent="add(i + 1)"></label>
        <label class="lang-in le-in"><span>EN</span><input class="ain" :value="r.en" :aria-label="label + ' ' + (i + 1) + ' (EN)'" @input="set(i, 'en', $event.target.value)" @keydown.enter.prevent="add(i + 1)"></label>
      </div>
      <span class="le-acts">
        <button type="button" class="ibtn" aria-label="Monter" title="Monter" :disabled="i === 0" @click="move(i, -1)"><span class="ms">arrow_upward</span></button>
        <button type="button" class="ibtn" aria-label="Descendre" title="Descendre" :disabled="i === rows.length - 1" @click="move(i, 1)"><span class="ms">arrow_downward</span></button>
        <button type="button" class="ibtn red" aria-label="Supprimer" title="Supprimer" @click="remove(i)"><span class="ms">delete</span></button>
      </span>
    </div>
    <button type="button" class="abtn sm self-start" @click="add()"><span class="ms">add</span>{{ addLabel }}</button>
  </div>
</template>
